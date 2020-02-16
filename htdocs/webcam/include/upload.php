<?
// --------------------------------------------------------------------------
// Foto-Webcam.eu
// Web-based upload of webcam images
//
// Flori Radlherr, http://www.radlherr.de
// This is free software, see COPYING for details.
// --------------------------------------------------------------------------
//
require "imgutil.php"; checkUploadVars() or die;

// Extract date and time out of the timestamp string
// timestamp consists of yyyymmdd_hhmm
$now= $_POST['now'];
if (preg_match("/^(\d{2})(\d{2})(\d{2})(\d{2})_(\d{2})(\d{2})$/", $now, $rr)) {
  $now_yh= $rr[1];
  $now_yy= $rr[2];
  $now_m=  $rr[3];
  $now_d=  $rr[4];
  $now_th= $rr[5];
  $now_tm= $rr[6];
}
else {
  // If no time is specified, use the current time
  $now_yh= "20";
  $now_yy= strftime("%y");
  $now_m=  strftime("%m");
  $now_d=  strftime("%d");
  $now_th= strftime("%H");
  $now_tm= strftime("%M");
}

$webcamImageName= $webcam['workPath']."/current/raw.jpg";
if (!move_uploaded_file($_FILES["upload"]["tmp_name"], $webcamImageName)) {
  echoLog("No upload file.", "error");
  exit;
}
$size= filesize($webcamImageName);

// Avoid too many processes doing the same thing simultanous
$lockf= "/tmp/webcam-processing.lock";
while (@filemtime($lockf) > time()-40) {
  $f= @fopen($lockf, "r");
  if ($f) {
    $inst= fgets($f);
    fclose($f);
    doLog("state=locked-$inst", "info");
    sleep(1);
  }
  else {
    break;
  }
}
$f= fopen($lockf, "w"); 
fputs($f, $webcam['name']); 
fclose($f);

doLog("state=processing imagesize=$size", "info");
$btime= gettimeofday(true);

// Seek for weather data
$wx= "";
$wxname= $webcam['workPath']."/wetter/cam.txt";
if (! @is_file($wxname)) {
  $wxname= "../../../wx/{$webcam['name']}cam.txt";
}
if (isset($webcam['wxFile']) && is_file($webcam['wxFile'])) {
  $wxname= $webcam['wxFile'];
}
if (@filemtime($wxname) > (time()-1800)) {
  $wxfile= fopen($wxname, "r");
  if ($wxfile) {
    $wx= fgets($wxfile);
    $wx= preg_replace("/\s+$/","",$wx);
    fclose($wxfile);
    $wx= "  $wx  ";
  }
}

$exif= getExif($webcamImageName);
$webcam['exif']= $exif;
$webcamImageCorr= $webcam['workPath']."/current/raw-corr.jpg";
if (is_callable(@$webcam['corrFunc'])) {
  if ($webcam['corrFunc']($exif, $webcamImageName, $webcamImageCorr)) {
    $webcamImageName= $webcamImageCorr;
  }
}
$img= @ImageCreateFromJPEG($webcamImageName);
if (! $img) {
  echoLog("No valid JPG input.", "error");
  @unlink($lockf);
  exit;
}
$exiftxt= "";
if (isset($exif['imgtxt'])) {
  $exiftxt= $exif['imgtxt'];
}
$textImg= createTextImage($img, $webcam['title']."\n".
  "$now_d.$now_m.$now_yy $now_th:$now_tm  $wx  $exiftxt");

$fwlogoname= "foto-webcam.eu-logo.png";
if (isset($webcam['fwLogo'])) {
  $fwlogoname= $webcam['fwLogo'];
}
$fwlogo= @ImageCreateFromPNG($fwlogoname);
$logo= @ImageCreateFromPNG($webcam['workPath']."/logo.png");

if (! isset($webcam['topOffset'])) {
  $webcam['topOffset']= 0;
}

// Try to fine-tune gamma, iso and exposure compensation for next image
$luminance= tuneExposure($exif, $img);
$exif['file'].= "Luminance|$luminance %\n";

// If necessary, copy regions to other places due to privacy
copyRegions($img);

// If necessary, scramble some regions due to privacy
scrambleRegions($img, $exif);

// If necessary, add noise filter to regions
filterRegions($img);

// Generate the different resolutions
foreach ($webcam['resolutions'] as $res) {
  $fn= $webcam['workPath']."/current/$res.jpg";
  createResizedImage($img, $fn, $res, 0, $webcam['topOffset'], 
                     $textImg, $fwlogo, $logo);
}

// Generate custom resolutions, if necessary
if (isset($webcam['customRes'])) {
  foreach ($webcam['customRes'] as $cr) {
    $fn= $webcam['workPath']."/current/$cr[0].jpg";
    createResizedImage($img, $fn, $cr[0], $cr[1], $cr[2], 
                       $textImg, $fwlogo, $logo);
  }
}

// Now set archived image in place
$target_dir= $webcam['workPath']."/$now_yh$now_yy/$now_m/$now_d";
@mkdir($target_dir, 0775, true);
$target_file= "$target_dir/${now_th}${now_tm}_";


// Determine sizes
$smSize= 114;
$laSize= 816;
$lmSize= 1200;
if (isset($webcam['thumbWidth'])) $smSize= $webcam['thumbWidth'];
if (isset($webcam['mainWidth']))  $laSize= $webcam['mainWidth'];
if (isset($webcam['hdWidth']))    $lmSize= $webcam['hdWidth'];

// Thumbnail shall not have text and logo
createResizedImage($img,"${target_file}sm.jpg",$smSize,0,$webcam['topOffset']);

// The full size must be the last since it changes the original image
$fn= $webcam['workPath']."/current/full.jpg";
if (isset($webcam['hugeIsRaw'])) {
  copy($webcamImageName, $fn);
}
else {
  createResizedImage($img, $fn, 0, 0, $webcam['topOffset'], 
                     $textImg, $fwlogo, $logo);
}
copy($fn, "${target_file}hu.jpg");

// Be sure the main trigger for meta data is created at latest
copy($webcam['workPath']."/current/$lmSize.jpg", "${target_file}lm.jpg");
copy($webcam['workPath']."/current/$laSize.jpg", "${target_file}la.jpg");

$exlen= 0;
if (isset($exif['file'])) {
  $exlen= strlen($exif['file']);
  $exf= fopen("${target_file}ex.txt", "w");
  if ($exf) {
    fwrite($exf, $exif['file']);
    fclose($exf);
    copy("${target_file}ex.txt", $webcam['workPath']."/current/exif.txt");
  }
}

// Clean up resources
ImageDestroy($img);
ImageDestroy($textImg);
if ($fwlogo) {
  ImageDestroy($fwlogo);
}
if ($logo) {
  ImageDestroy($logo);
}


if ($webcam['useDatabase']) {
  $mysqli= openMysql();
  if ($mysqli) {
    $img= "$now_yh$now_yy/$now_m/$now_d/${now_th}${now_tm}";
    $stamp= "$now_yh$now_yy-$now_m-$now_d ${now_th}:${now_tm}:00";
    $set= "set cam='".$webcam['name']."',";
    
    if ($exlen>50) {
      $ex= $mysqli->escape_string($exif['file']);
      $mysqli->query("replace webcam_exif $set path='$img', exif='$ex'");
    }
    $mysqli->query("replace webcam_image $set path='$img',stamp='$stamp',".
                   "have_lm=1, have_hu=1, have_ex='$exlen'");

    $day= preg_replace("/.\d\d\d\d$/", "", $img);
    $mysqli->query("replace webcam_day $set path='$day'"); //yyyy/mm/dd
    $day= preg_replace("/.\d\d$/", "", $day);
    $mysqli->query("replace webcam_day $set path='$day'"); //yyyy/mm
    $day= preg_replace("/.\d\d$/", "", $day);
    $mysqli->query("replace webcam_day $set path='$day'"); //yyyy
  }
}

@unlink($lockf);
$elapsed= round((gettimeofday(true)-$btime)*1000);
doLog("state=ready elapsed={$elapsed} ms.", "info");

// We have used lots of memory. It should be freed, so
// terminate Apache 2 child process after request has been
// done by sending a SIGWINCH POSIX signal (28).
function kill_on_exit() { posix_kill(getmypid(), 28); }
register_shutdown_function('kill_on_exit'); 
?>
