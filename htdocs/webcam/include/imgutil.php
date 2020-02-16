<?
// --------------------------------------------------------------------------
// Foto-Webcam.eu
// Helper functions for acquiring webcam images
//
// Flori Radlherr, http://www.radlherr.de
// This is free software, see COPYING for details.
// --------------------------------------------------------------------------
//
require "common.php";

// --------------------------------------------------------------------------
// Fetch EXIF values from a given JPEG image file (referenced by filename)
// Extract some special values as decimal float number
// --------------------------------------------------------------------------
function getExif($filename) {
  function exVal($s) {
    if (preg_match('/^(\d+)\/(\d+)$/', $s, $ab) && $ab[2]>0) {
      if (($ab[1]/$ab[2]) < 10) {
        return sprintf("%1.1f", $ab[1]/$ab[2]);
      }
      else {
        return round($ab[1]/$ab[2]);
      }
    }
    return $s;
  }
  $exif= exif_read_data($filename);
  if ($exif && isset($exif['FNumber'])) {
    $model=   $exif['Model'];
    $iso=     $exif['ISOSpeedRatings'];
    $fnumber= exVal($exif['FNumber']);
    $exptime= exVal($exif['ExposureTime']);
    $bias=    exVal($exif['ExposureBiasValue']);
    $foclen=  exVal($exif['FocalLength']);

    // Canon only: lens name
    $lens= "";
    if (isset($exif['UndefinedTag:0x0095'])) {
      $lens=   $exif['UndefinedTag:0x0095'];
    }

    // Canon only: camera serial number
    $serial= "";
    if (isset($exif['UndefinedTag:0x0096'])) {
      $serial= $exif['UndefinedTag:0x0096'];
    }

    // Canon only: take the actual light metering
    $measuredEV= "";
    if (isset($exif["ImageInfo"][3]) && $exif["ImageInfo"][3]>0) {
      $measuredEV= $exif["ImageInfo"][3];
      if ($measuredEV>32767) {
        $measuredEV= $measuredEV-65536;
      }
      $exif["MeasuredEV"]= $measuredEV/32;
      $measuredEV= sprintf("MeasuredEV|%1.1f eV\n", $measuredEV/32);
    }

    // Remember some raw values for later usage (auto-exposure)
    $exif['iso']=  $iso;
    $exif['fn']=   $fnumber;;
    $exif['et']=   $exptime;
    $exif['bias']= $bias;
    $exif['ExposureTime']= 
       preg_replace("/10\/(\d+)0/", "1/$1", $exif['ExposureTime']);
    if ($exptime<0.3) {
      $exptime= $exif['ExposureTime'];
    }
    // Remember text representation for image text
    if ($iso && $foclen) {
      $exif['imgtxt']= "(f/$fnumber  ${exptime}s  iso$iso)";
    }
    // Remember text file format compatible with exif command line 
    $exif['file']= 
      "Model|$model\n".
      "Exposure Time|${exptime} sec.\n".
      "FNumber|f/$fnumber\n".
      "ISO|$iso\n".
      "Exposure Bias|$bias eV\n".
      "Focal Length|${foclen}mm\n$measuredEV".
      "Lens Name|$lens\n".
      "Serial Number|$serial\n";
  }
  return $exif;
}

// --------------------------------------------------------------------------
// Sample the grey-scale luminance value of a given image.
// Use only the rectangle 0,0,xmax,ymax
// --------------------------------------------------------------------------
function getLuminance($img, $xmax= 0, $ymax= 0) {
  global $webcam;
  $xmaxx= imagesx($img);
  $ymaxx= imagesy($img);
  $ymin= 0;
  if ($ymax>0) {
    $ymin=  $webcam['topOffset'];
    $ymax+= $webcam['topOffset'];
  }
  if ($xmax>$xmaxx || $xmax==0) { $xmax= $xmaxx; }
  if ($ymax>$ymaxx || $ymax==0) { $ymax= $ymaxx; }
  $step= ceil(($xmax+$ymax)/50); # do only rough sampling due to performance
  $av= 0;
  $count= 0;
  for($y= $ymin; $y<$ymax; $y+= $step) {
    for($x= 0; $x<$xmax; $x+= $step) {
      $pos= imagecolorat($img, $x, $y);
      $f= imagecolorsforindex($img, $pos);
      $gst= $f["red"]*0.15 + $f["green"]*0.5 + $f["blue"]*0.35;
      $av+= $gst;
      $count++;
    }
  }
  if ($count) {
    return round(($av/$count)/0.256)/10;
  }
  return 0;
}

// --------------------------------------------------------------------------
// Scramble parts of the image by randomizing tiles of different sizes
// Is configured by an array of arrays containing
// - rectangle xmin,ymin,xmax,ymax,minimal exposure time,tile count
// --------------------------------------------------------------------------
function scrambleRegions($img, $exif) {
  global $webcam;

  if (isset($webcam['scrambleRegions'])) {
    for ($rectnum= 0; isset($webcam['scrambleRegions'][$rectnum]); $rectnum++) {
      $rect= $webcam['scrambleRegions'][$rectnum];
      $xmin= $rect[0];
      $ymin= $rect[1];
      $xmax= $rect[2];
      $ymax= $rect[3];
      // Do scrambling only above a defined exposure time
      if (isset($rect[4]) && isset($exif) && isset($exif['et'])) {
        $minExptime= $rect[4];
        if ($exif['et'] < $minExptime) {
          return;
        }
      }
      // Count of tiles for randomized reorder
      $count= 100;
      if (isset($rect[5])) {
        $count= $rect[5];
      }
      $srcimg= ImageCreateTrueColor($xmax-$xmin, $ymax-$ymin);
      ImageCopy($srcimg, $img, 0,0, $xmin, $ymin, $xmax-$xmin,$ymax-$ymin);
      ImageFilter($srcimg, IMG_FILTER_SMOOTH, 0);
      for ($i= 0; $i<$count; $i++) {
        // Use samples with different size
        // Begin with larger samples, finish with smaller ones
        $szhint= min($ymax-$ymin,$xmax-$xmin)/2+50;
        if ($i>20) {
          $szhint/= 2;
        }
        if ($i>100) {
          $szhint/= 2;
        }
        $sz= rand($szhint/2, $szhint);
        // Mix source and destination samples by random order
        $xmargin= ($xmax-$xmin)/20;
        $ymargin= ($ymax-$ymin)/20;
        $xsrc= rand($xmin+$xmargin, $xmax-$sz-$xmargin);
        $ysrc= rand($ymin+$ymargin, $ymax-$sz-$ymargin);
        $xdst= rand($xmin, $xmax-$sz);
        $ydst= rand($ymin, $ymax-$sz);

        $tile= ImageCreateTrueColor($sz, $sz);
        ImageCopy($tile, $srcimg, 0, 0, $xsrc-$xmin, $ysrc-$ymin, $sz, $sz);

        // Increase transparency when iterating from border to middle
        for ($margin=0.0; $margin<$sz/2; $margin+=($margin*0.1+1)) {
          $trans= 100*(($margin*2)/$sz);
          if ($trans>100) {
            $trans= 100;
          }
          ImageCopyMerge($img, $tile, $xdst+$margin, $ydst+$margin, 
            $margin,$margin, $sz-(2*$margin), $sz-(2*$margin), $trans);
        }
        ImageDestroy($tile);
      }
      ImageDestroy($srcimg);
    }
  }
}

// --------------------------------------------------------------------------
// Copy regions to other parts within the image.
// The border of the area is softened with semi-transparent border
//
// Accept an array of $webcam['copyRegions'] with following parts
//  (xmin,ymin,xmax,ymax,x destination,y destination,opacity)
// Opacity defaults to 100, more is harder, less is softer
// --------------------------------------------------------------------------
function copyRegions($img) {
  global $webcam;

  if (isset($webcam['copyRegions'])) {
    for ($rectnum= 0; isset($webcam['copyRegions'][$rectnum]); $rectnum++) {
      $rect= $webcam['copyRegions'][$rectnum];
      $xmin= $rect[0];
      $ymin= $rect[1];
      $xmax= $rect[2];
      $ymax= $rect[3];
      $xdst= $rect[4];
      $ydst= $rect[5];
      $opacity= 100;
      if (isset($rect[6])) {
        $opacity= $rect[6];
      }
      $srcimg= ImageCreateTrueColor($xmax-$xmin, $ymax-$ymin);
      ImageCopy($srcimg, $img, 0,0, $xmin, $ymin, $xmax-$xmin, $ymax-$ymin);
      $sz= min($xmax-$xmin,$ymax-$ymin);
      $trans= 0;

      // Increase transparency when iterating from border to middle
      for ($margin=0; $margin<$sz/2 && $trans<100; $margin+=5) {
        $trans= min($opacity*($margin/$sz),100);

        ImageCopyMerge($img, $srcimg, $xdst+$margin, $ydst+$margin, $margin,
          $margin, $xmax-$xmin-(2*$margin), $ymax-$ymin-(2*$margin), $trans);
      }
      ImageDestroy($srcimg);
    }
  }
}

// --------------------------------------------------------------------------
// Filter regions through "--noise"-function if ImageMagick
// The border of the area is softened with semi-transparent border
//
// Accept an array of $webcam['filterRegions'] with following parts
//  (xmin,ymin,xmax,ymax,noise lel,opacity)
// Noise level defaults to 5, regulates the blurring intensity
// Opacity defaults to 100, more is harder, less is softer
// --------------------------------------------------------------------------
function filterRegions($img) {
  global $webcam;

  if (isset($webcam['filterRegions'])) {
    $fn= "/tmp/filter-".$webcam['name'].".jpg";
    for ($rectnum= 0; isset($webcam['filterRegions'][$rectnum]); $rectnum++) {
      $rect= $webcam['filterRegions'][$rectnum];
      $xmin= $rect[0];
      $ymin= $rect[1];
      $xmax= $rect[2];
      $ymax= $rect[3];
      $noise= 5;
      $opacity= 100;
      if (isset($rect[4])) {
        $noise= $rect[4];
      }
      if (isset($rect[5])) {
        $opacity= $rect[5];
      }
      $srcimg= ImageCreateTrueColor($xmax-$xmin, $ymax-$ymin);
      ImageCopy($srcimg, $img, 0,0, $xmin, $ymin, $xmax-$xmin, $ymax-$ymin);
      ImageJPEG($srcimg, $fn); 
      ImageDestroy($srcimg);
      if (system("mogrify -noise $noise $fn") == 0) {
        $srcimg= ImageCreateFromJPEG($fn);
        unlink($fn);

        $sz= min($xmax-$xmin,$ymax-$ymin);
        $trans= 0;

        // Increase transparency when iterating from border to middle
        for ($margin=0; $margin<$sz/2 && $trans<100; $margin+=5) {
          $trans= min($opacity*($margin/$sz),100);

          ImageCopyMerge($img, $srcimg, $xmin+$margin, $ymin+$margin, $margin,
            $margin, $xmax-$xmin-(2*$margin), $ymax-$ymin-(2*$margin), $trans);
        }
        ImageDestroy($srcimg);
      }
    }
  }
}

// --------------------------------------------------------------------------
// Generate a transparent image and place text at the top of it
// The text color is determined by the luminance value of the main image
// To be merged with further images
// --------------------------------------------------------------------------
function createTextImage($img, $text, $video= false) {
  global $webcam;
  $x= 40;
  $y= 95;
  $size= 56;
  $font= dirname(__FILE__)."/ubuntu-r.ttf";

  $threshold= 45;    // at day, prefer black text
  if (isNight()) {
    $threshold= 55;  // at night, prefer white text
  }
  $b= 255;
  if (getLuminance($img, 1000, 100) > $threshold) {
    $b= 0;
  }
  if ($video) {
    $b= 0;
  }
  $s= $b;
  $al= 127;
  if (isset($webcam['textShadow'])) {
    if ($webcam['textShadow'] == "white") $b= 0;
    if ($webcam['textShadow'] == "black") $b= 255;
    $s= 0; if ($b<128) $s= 255;
    #$al= 126; // 127 leads to ugly effects :-(
  }

  $textImg= ImageCreateTrueColor(4000, 200);
  ImageSaveAlpha($textImg, true);
  imagefill($textImg, 0, 0, ImageColorAllocateAlpha($textImg, $s,$s,$s, $al));

  if (isset($webcam['textShadow'])) {
    // draw text shadow around the text
    for ($xo= -1; $xo<=1; $xo++) {
      for ($yo= -1; $yo<=1; $yo++) {
        ImageTtfText($textImg, $size, 0, $x+($xo*5), $y+($yo*5), 
               ImageColorAllocate($textImg, $s,$s,$s), $font, $text);
      }
    }
    ImageFilter($textImg, IMG_FILTER_SMOOTH, 0);
  }
  ImageTtfText($textImg, $size, 0, $x, $y, 
               ImageColorAllocate($textImg, $b,$b,$b), $font, $text);

  return $textImg;
}

// --------------------------------------------------------------------------
// Sharpen a resampled image
// --------------------------------------------------------------------------
function sharpenImage($image) {
  $sharpenMatrix = array(
    array(-1.2, -1, -1.2),
    array(-1,   20, -1),
    array(-1.2, -1, -1.2)
  );
  $divisor = array_sum(array_map('array_sum', $sharpenMatrix));           
  $offset = 0;
  ImageConvolution($image, $sharpenMatrix, $divisor, $offset);
}

// --------------------------------------------------------------------------
// Generate a target image with desired size
// --------------------------------------------------------------------------
function createResizedImage($img, $outputFile, $newWidth, $newHeight=0, 
  $topOffset=0, $textImg=NULL, $fwlogo=NULL, $logo= NULL, $video= false) {
  global $webcam;

  $aspectRatio= 16/9;
  if (isset($webcam['aspectRatio'])) {
    $aspectRatio= $webcam['aspectRatio'];
  }

  $width=  ImageSX($img);
  $height= ImageSY($img);
  if ($newWidth==0) {
    // Do not resample the original image
    $resImg= $img;
  }
  else {
    if ($newHeight<=0) {
      // The new image frame
      $newHeight= floor($newWidth/$aspectRatio);
      if ($topOffset<0) {
        $newHeight= floor(($newWidth/$width)*$height);
        $topOffset= 0;
      }
    }
    $resImg= ImageCreateTrueColor($newWidth, $newHeight);
  }
  $resX=   ImageSX($resImg);
  $resY=   ImageSY($resImg);
  if ($newWidth) {
    ImageCopyResampled($resImg ,$img, 0, 0, 0, $topOffset, $resX, $resY,
                       $width, ($width*$resY)/$resX);
  }

  $iso= 100;
  if (isset($webcam['exif']['iso'])) {
    $iso= round($webcam['exif']['iso']);
    // Olympus 5050 is extra-noisy
    if (preg_match("/5050/", $webcam['exif']['Model'])) {
      $iso*= 10;
    }
  }
  if ($iso<400 || $resX<200 ||
     ($resX<1000 && $iso<6400) || 
     ($resX<2000 && $iso<3200)) {
    // Sharpen image only if iso is not too high
    // sharpening noise is so ugly..
    sharpenImage($resImg);
  }

  // Add prepared text to the image
  if ($textImg) {
    $txtXoffset= 0;
    if (isset($webcam['txtXoffset'])) {
      $txtXoffset= round($resX*($webcam['txtXoffset']/100.0));
    }
    $div= $resX/1333+0.4; 
    if ($div<0.8) $div= 0.8;
    if ($video)   $div= 1.2;
    ImageCopyResampled($resImg, $textImg, $txtXoffset, 0, 0, 0,
      $resX/$div, $resX/($div*20),  
      ImageSX($textImg),ImageSY($textImg)
    );
  }

  // Add the general logo to the right bottom corner
  if ($fwlogo) {
    $div= $resX/100;
    if ($div < 6)  $div= 6;
    if ($div > 12) $div= 6+$div/2;
    if ($video)    $div= 8;
    ImageCopyResampled($resImg, $fwlogo, 
      $resX-($resX/$div), $resY-($resX/($div*5)), 0, 0,
      $resX/$div, $resX/($div*5),  
      ImageSX($fwlogo), ImageSY($fwlogo)
    );
  }

  // Add the special logo at the right up corner
  if ($logo) {
    $scale= 1.0;
    if (isset($webcam['logoScale'])) {
      $scale= $webcam['logoScale'];
    }
    $logX= ImageSX($logo);
    $logY= ImageSY($logo);
    $div=8;
    if ($resX > 1000) $div= 10;
    if ($resX > 2000) $div= 16;
    if ($video)       $div= 9;
    $margin= $resX/($div*12);
    $div/= $scale;
    ImageCopyResampled($resImg, $logo, 
      $resX-($resX/$div)-$margin, $margin, 0, 0,
      $resX/$div, $resX/($div*($logX/$logY)),  
      $logX, $logY
    );
  }

  // Larger quality for smaller images
  $quality= 93;
  if ($resX>1000) $quality= 88;
  if ($resX>1400) $quality= 86;
  if ($video)     $quality= 95;

  // Write image as jpeg file
  ImageJPEG($resImg, $outputFile, $quality); 
  if ($newWidth>0) {
    // Only destroy it, if it was really generated and not copied
    ImageDestroy($resImg);
  }
}


// --------------------------------------------------------------------------
// Do all that is necessary to operate a Foto-Webcam within a directory
// --------------------------------------------------------------------------
function prepareWebcamPath($webcamPath) {
  if (! file_exists("$webcamPath/current/.htaccess")) {
    $dirs= Array("current", "bestof", "tmp", "infos", "wetter");
    foreach ($dirs as $dir) {
      $fulldir= "$webcamPath/$dir";

      @mkdir($fulldir, 0775, true);
      @chmod($fulldir, 0775);
    }
    @mkdir("$webcamPath/wetter/cache", 0775, true);

    $ht= @fopen("$webcamPath/.htaccess", "w");
    if ($ht) {
      fwrite($ht, 
        'RewriteEngine On'."\n".
        'RewriteRule !\.jpg /webcam/include/webcam.php'."\n".
        'ExpiresActive On'."\n".
        'ExpiresByType image/jpeg "access plus 10 weeks"'."\n".
        'ExpiresByType image/png "access plus 10 weeks"'."\n");
      fclose($ht);
    }
    $ht= @fopen("$webcamPath/current/.htaccess", "w");
    if ($ht) {
      fwrite($ht, 
        'RewriteEngine Off'."\n".
        'ExpiresActive On'."\n".
        'ExpiresByType image/jpeg "access plus 10 minutes"'."\n");
      fclose($ht);
    }
    $ht= @fopen("$webcamPath/infos/.htaccess", "w");
    if ($ht) {
      fwrite($ht, 
        'RewriteEngine Off'."\n".
        'ExpiresActive Off'."\n");
      fclose($ht);
    }
    $ht= @fopen("$webcamPath/wetter/.htaccess", "w");
    if ($ht) {
      fwrite($ht, 
        'RewriteEngine Off'."\n".
        'ExpiresActive Off'."\n");
      fclose($ht);
    }
  }
}

// --------------------------------------------------------------------------
// Determine if we have day or night
// --------------------------------------------------------------------------
function isNight($verbose= false) {
  $zenith= 98;   // early twilight
  $lat= 47.5;
  $long= 11.5;
  $ret=  false;

  // Check if we have configured a real place
  global $webcam;
  if (isset($webcam['latitude']))  { 
    $lat=  $webcam['latitude'];  
  }
  if (isset($webcam['longitude'])) { 
    $long= $webcam['longitude']; 
  }

  $ss= date_sunset(time(),  SUNFUNCS_RET_TIMESTAMP, $lat, $long, $zenith);
  if (time() > ($ss-1800)) { # switch to night 1/2h before sunset
    $ret= true;
  }
  $sr= date_sunrise(time(), SUNFUNCS_RET_TIMESTAMP, $lat, $long, $zenith);
  if (time() < ($sr-$webcam['captureInterval'])) {  # for next(!) image
    $ret= true;
  }
  if ($verbose) {
    $xss= strftime("%H:%M:%S", $ss);
    $xsr= strftime("%H:%M:%S", $sr);
    doLog("night=$ret lat=$lat long=$long sr=$xsr ss=$xss", "info");
  }
  return $ret;
}

// --------------------------------------------------------------------------
// Check if image shall be gamma-tuned if too dark
// --------------------------------------------------------------------------
function webcamGammaCorrect($img, $lum) {
  global $webcam;
  $maxGamma= 1.3;
  if (isset($webcam['maxGamma'])) {
    $maxGamma= $webcam['maxGamma'];
  }
  $newLum= $lum;
  $gamma= 1.0;

  if (isset($maxGamma)) {
    if ($lum<32) $gamma+= 0.3;
    if ($lum<16) $gamma+= 0.3;
    if ($lum<8)  $gamma+= 0.3;
    if ($lum<4)  $gamma+= 0.3;
    if ($gamma > $maxGamma) {
      $gamma= $maxGamma;
    }
    if ($gamma > 1.0) {
      ImageGammacorrect($img, 1.0, $gamma);
      $newLum= getLuminance($img);
    }
  }
  doLog("luminance=$lum gamma=$gamma new_lum=$newLum", "info");
}

// --------------------------------------------------------------------------
// Send ISO and exposure parameters to camera to correct image if necessary
// Return: luminance (in %)
// --------------------------------------------------------------------------
function tuneExposure($exif, $img) {
  global $webcam;
  if (! isset($exif['Model'])) {
    // No exif values found, we do not seem to have valid exif header
    return;
  }
  $model= $exif['Model'];
  $iso=   round($exif['iso']);
  $et=    $exif['et'];
  $bias=  round($exif['bias']);
  $night= isNight(true);
  $lum=   getLuminance($img);
  doLog("model=$model iso=$iso exptime=$et bias=$bias lum=$lum", "info");
  if (preg_match("/5050/", $model)) {
    if ($et>3 && $iso!=400) {
      sendCommand("webcam_isoexp 400");
    }
    if ($et<2 && $iso==400) {
      sendCommand("webcam_isoexp Auto");
    }
  }
  if (preg_match("/EOS|Nikon/i", $model)) {
    $shallIso= 0;
    $shallExp= -1;
    if ($bias<0) {
      $shallExp= 0;
    }
    if ($et>25 && $iso<$webcam['maxIso'] && $lum<35) {
      $shallIso= $iso*2;
      if ($lum<10 && $shallIso<$webcam['maxIso']) {
        $shallIso= $shallIso*2;
      }
    }
    if (($et<20 || $lum>40) && $iso>=200) {
      $shallIso= $iso/2;
    }
    if ($et<1 && $iso>=400) {
      $shallIso= $iso/4;
    }
    if ($night) {
      if ($lum<20 && $bias<$webcam['maxExposure']) {
        $shallExp= $bias+1;
        # If image is extremely dark, enlight even more
        if ($lum<10 && $shallExp<$webcam['maxExposure']) {
          $shallExp= $shallExp+1;
        }
      }
      if ($lum>40 && $bias>0) {
        $shallExp= $bias-1;
      }
      if ($lum>50 && $bias>1) {
        $shallExp= $bias-2;
      }
    }
    elseif ($bias>0) {
      $shallExp= 0;
    }
    if ($shallIso>0 || $shallExp>=0) {
      if ($shallExp<0) $shallExp= "";
      sendCommand("webcam_isoexp $shallIso $shallExp");
    }
  }
  // On very dark images, do some gamma correction
  webcamGammaCorrect($img, $lum);
  return $lum;
}

// --------------------------------------------------------------------------
// Send one command to the webcam host
// --------------------------------------------------------------------------
function sendCommand($cmd) {
  global $webcam;
  $serial= time();
  $webcam['lastCommand']= $serial;

  echoLog($cmd, "command", $serial);
  
  // If there is already a command pending, overwrite it silently
  // This is not really polite but avoids deadlock and command accumulation
  $cmdName= $webcam['workPath']."/tmp/command.txt";
  $cmdf= @fopen($cmdName, "w");
  if ($cmdf) {
    fwrite($cmdf, "$serial\n$cmd\n");
    fclose($cmdf);
  }
}

// --------------------------------------------------------------------------
// Write some information to standard output and additionally to the log file
// --------------------------------------------------------------------------
function echoLog($phrase, $facility= "echo", $serial= 0) {
  echo("$phrase\n");
  doLog($phrase, $facility, $serial);
}

// --------------------------------------------------------------------------
// Write some debugging output to log file
// --------------------------------------------------------------------------
function doLog($phrase, $facility= "log", $serial= 0) {
  global $webcam;
  $logName= $webcam['workPath']."/tmp/log.txt";

  $logFile= fopen($logName, "a+t");
  if ($logFile) {
    // Maintain additionally a global logfile, if open succeeds.
    // It is used for a global status recording, but not really needed
    $globalFile= @fopen($webcam['workPath']."/../status/tmp/log.txt","a+t");
    $stamp= strftime("%d.%m.%y %H:%M:%S;",time());
    $stamp.= gettimeofday(true).";$facility;$serial;";
    foreach (explode("\n", $phrase) as $line) {
      $line= preg_replace("/\r/", "", $line);
      if ($line != "") {
        $line= $webcam['name'].";".$stamp.$line."\n";
        fputs($logFile, $line);
        if ($globalFile) {
          fputs($globalFile, $line);
        }
      }
    }
    fclose($logFile);
    if ($globalFile) {
      fclose($globalFile);
    }
  }
}

// --------------------------------------------------------------------------
// For all upload and command/response scripts some basic vars ar required
// --------------------------------------------------------------------------
function checkUploadVars() {
  global $webcam;
  Header("Content-Type: text/plain");

  if (! isset($_POST['wc'])) {
    echoLog("No cam parameter given.", "error");
    die;
  }
  if (! preg_match("/^[a-z0-9\-\_]+$/i", $webcam['name'])) {
    echoLog("No cam specified.", "error");
    die;
  }

  // at minimum the title MUST be set
  if (!isset($webcam['title'])) {
    echoLog("Cannot load config for {$webcam['name']}", "error");
    die;
  }

  // Some kind of minimal security
  if ($webcam['uploadkey'] != $_POST['key']) {
    echoLog("Unauthorized.", "error");
    die;
  }
  prepareWebcamPath($webcam['workPath']);
  return true;
}

// --------------------------------------------------------------------------
?>
