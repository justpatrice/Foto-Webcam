<?
// --------------------------------------------------------------------------
// Foto-Webcam.eu
// Create JSON listing for archive navigation
//
// Flori Radlherr, http://www.radlherr.de
// This is free software, see COPYING for details.
// --------------------------------------------------------------------------
//
require "common.php";

if (! chdir($webcam['workPath'])) {
  die("cannot change to $workdir");
}
header("Content-Type: application/json");
header("Expires: 0");

$samehour= (int)$_GET['samehour'];
$thumbs=   (int)$_GET['thumbs'];

$useDB= false;
if ($webcam['useDatabase'] && openMysql()) {
  $useDB= true;
}

// The path of the particular image
$when=   $webcam['parImg'];
$years=  array(); 
$months= array(); 
$days=   array(); 
$hours=  array();
$isAct=  0;
$img=    "";
$fwd=    array();
$back=   array();
// The thumbnail or video frame list
$thumbList= array();

$p= explode("/", $when);
for ($i= 0; $i<4; $i++) {
  if (!isset($p[$i])) {
    $p[$i]= "";
  }
}

if ($useDB) {
  // Scan database around the current time stamp
  $year=  dbscan($p[0], "",                  1, $years);
  $month= dbscan($p[1], "$year",             2, $months);
  $day=   dbscan($p[2], "$year/$month",      3, $days);
  $hour=  dbscan($p[3], "$year/$month/$day", 4, $hours);

  // The history
  $img= "$year/$month/$day/$hour";
  #check_image($img);

  $fwd=  dbtraverse($img, 35, 0);
  $back= dbtraverse($img, -35, 0);

  // The thumbnail or video frame list
  if ($thumbs) {
    $thumbList= dbtraverse($img, -$thumbs, $samehour);
  }
}
else {
  // Scan file system directories around the current time stamp
  $year=  dirscan($p[0], ".",                 "[0-9]{4}",        $years);
  $month= dirscan($p[1], "$year",             "[0-9]{2}",        $months);
  $day=   dirscan($p[2], "$year/$month",      "[0-9]{2}",        $days);
  $hour=  dirscan($p[3], "$year/$month/$day", "[0-9]{4}_la.jpg", $hours);

  // The history
  $img= "$year/$month/$day/$hour";
  $fwd=  traverse($img, 35, 0);
  $back= traverse($img, -35, 0);

  // The thumbnail or video frame list
  if ($thumbs) {
    $thumbList= traverse($img, -$thumbs, $samehour);
  }
}
// Determine if given imag name is the most recent
if (($years[count($years)-1] == $year) &&
    ($months[count($months)-1] == $month) &&
    ($days[count($days)-1] == $day) &&
    ($hours[count($hours)-1] == $hour)) {
  $isAct= 1;
}

// Allow cross-domain access through JSONP function call
$jsonp= $_GET['callback'];
if (preg_match("/^[a-z0-9_]+$/i", $jsonp)) { // Avoid XSS
  echo($jsonp."(");
}

// Generate JSON structure
json_obj();
  json_var($img, "image");
  $huge= str_replace("_la", "_hu", $img);
  if (! is_readable($huge)) {
    $huge= "";
  }
  $hd= str_replace("_la", "_lm", $img);
  if (! is_readable($hd)) {
    $hd= "";
  }
  json_var($huge, "hugeimg");
  json_var($hd, "hdimg");
  json_var(($isAct?"true":"false"), "newest");

  if (is_readable("bestof/$img")) {
    json_var("true", "isbestof");
  }
  else {
    json_var("false", "isbestof");
  }

  // The list of actual available calendar controls
  json_obj("ids");
    foreach ($years as $y) {
      json_var("$y/$month/$day/$hour",  "zy$y"); 
    }
    foreach ($months as $m) {
      json_var("$year/$m/$day/$hour",   "zm$m"); 
    }
    foreach ($days as $d) {
      json_var("$year/$month/$d/$hour", "zd$d"); 
    }
    foreach ($hours as $h) {
      if (preg_match("/^(..)/", $h, $rr)) {
        $allHours[$rr[1]]= 1;
      }
    }
    $hours= array_keys($allHours);
    sort($hours);
    foreach ($hours as $h) {
      json_var("$year/$month/$day/${h}00", "zh$h");
    }
    $tcount= 0;
    foreach (array(@$back[1], @$back[0], $img, @$fwd[0], @$fwd[1]) as $t) {
      $tt= preg_replace("/_.*/", "", $t);
      if ($t) {
        json_var($tt, "zt$tcount");
      }
      $tcount++;
    }
  json_obj_end();

  json_obj("history", 1);
  $hist= array_merge($back, (array)$img, $fwd);
  sort($hist);
  foreach ($hist as $i) {
    $ii= str_replace("_la.jpg", "", $i);
    if ($ii) {
      json_var($ii);
    }
  }
  json_obj_end(1);
  json_var(count($back), "histptr");

  json_obj("thumbs", 1);
  $thumbList= array_reverse($thumbList);
  foreach ($thumbList as $i) {
    $i= preg_replace("/_.*/", "", $i);
    json_var($i."_la.jpg"); 
  }
  if (count($thumbList)) {
    json_var($img);
  }
  json_obj_end(1);

  if (@$_GET['exif'] > 0) {
    json_var(getExif($img), "exif");
  }
  if (@$_GET['bestof'] > 0) {
    $bestof= array();
    bestofList("bestof", $bestof);
    rsort($bestof);
    json_obj("bestof", 1);
    foreach ($bestof as $i) {
      json_var($i); 
    }
    json_obj_end(1);
  }
  if ($isAct && isset($webcam['errorMsg'])) {
    if (filemtime($webcam['workPath']."/current/full.jpg") <
       (time()-$webcam['captureInterval']*3)) {
      json_var($webcam['errorMsg'], "errorMsg");
    }
  }
json_obj_end();
if ($jsonp) {
  echo(");");
}


// --------------------------------------------------------------------------
// Read one level of the file system and scan for available images/folders
function dirscan($want, $path, $regex, &$arr) {
  $dh= opendir($path);
  if ($dh) {
    while (($file= readdir($dh)) !== false) {
      if (preg_match("/$regex/", $file)) {
        $arr[]= $file;
      }
    }
    closedir($dh);
    sort($arr);

    if (count($arr)==0) {
      return $want;
    }
    $last= $arr[count($arr)-1];

    if ($want=="" || $want>$last) {
      return $last;
    }
    if ($want<$arr[0]) {
      return $arr[0];
    }

    foreach ($arr as $v) {
      if ($v>=$want) {
        return $v;
      }
    }
  }
  return "";
}

// --------------------------------------------------------------------------
// Read one level of the image name from the database
function dbscan($want, $path, $part, &$arr) {
  global $webcam;
  $mysqli= openMysql();
  $ret= "";
  $len= 4;
  $crop= 0;
  $tab= "day";

  if ($part==2) {
    $len= 7;
    $crop= 5;
  }
  if ($part==3) {
    $len= 10;
    $crop= 8;
  }
  if ($part==4) {
    $len= 15;
    $crop= 11;
    $tab= "image";
  }

  $res= $mysqli->query("select path from webcam_$tab ".
       "where cam='".$webcam['name']."' and length(path)=$len ".
       "and path like '$path%' order by path");

  if ($res) {
    while ($row= $res->fetch_assoc()) {
      $p= substr($row['path'], $crop);
      if ($part==4) {
        $p.= "_la.jpg";
      }
      $arr[]= $p; 
    }
    $res->free();
  }
  if (count($arr)==0) {
    return $want;
  }
  $last= $arr[count($arr)-1];

  if ($want=="" || $want>$last) {
    return $last;
  }
  if ($want<$arr[0]) {
    return $arr[0];
  }

  foreach ($arr as $v) {
    if ($v>=$want) {
      return $v;
    }
  }
  return $ret;
}

// --------------------------------------------------------------------------
// Traverse a particular number of available images forward or back
function traverse($act, $number, $sameHour) {
  $ret= array();
  $count= 0;
  $dayModified= false;
  $reverse= false;
  if ($number<0) {
    $reverse= true;
    $number= abs($number);
  }
  if ($number>500) {
    $number= 500;
  }

  $rr= array();
  preg_match("/(\d{4})\/(\d{2})\/(\d{2})\/(\d{4})/", $act, $rr);
  if (count($rr)==5) {
    $year= $rr[1]; 
    $mon=  $rr[2]; 
    $day=  $rr[3]; 
    $hour= (int)$rr[4];

    while ($number>0) {
      $dh= @opendir(sprintf("%02d/%02d/%02d", $year, $mon, $day));
      if ($dh) {
        $hours= array();
        while (($file= readdir($dh)) !== false) {
          if (preg_match("/^\d{4}_la.jpg$/", $file)) {
            $hours[]= $file;
          }
        }
        closedir($dh);
        sort($hours);
        if ($reverse) {
          $hours= array_reverse($hours);
        }

        foreach ($hours as $h) {
          if ($sameHour) {
            if ($dayModified && ((int)$h == $hour)) {
              $ret[]= sprintf("%02d/%02d/%02d/%s",$year,$mon,$day,$h);
              $number--;
              if ($number==0) {
                break;
              }
            }
          }
          elseif ($dayModified || ($reverse?((int)$h<$hour):((int)$h>$hour))) {
            $ret[]= sprintf("%02d/%02d/%02d/%s",$year,$mon,$day,$h);
            $number--;
            if ($number==0) {
              break;
            }
          }
        }
      }
      if ($reverse) {
        $day--; if ($day==0) {
          $day=31; $mon--; if ($mon==0) {
            $mon=12; $year--;
          }
        }
      }
      else {
        $day++; if ($day>31) {
          $day=1; $mon++; if ($mon>12) {
            $mon=1; $year++;
          }
        }
      }
      $dayModified= true;
      if ($year<1998) {
        break;
      }
      if (($count++)>500) {
        break;
      }
    }
  }
  return $ret;
}

// --------------------------------------------------------------------------
// Traverse a particular number of available images through the database
function dbtraverse($act, $number, $sameHour) {
  global $webcam;
  $mysqli= openMysql();
  $act= preg_replace("/_.*/", "", $act);
  $ret= array();
  $op= ">";
  $desc= "";
  $filter= "";

  if ($number<0) {
    $number= abs($number);
    $op= "<";
    $desc= "desc";
  }
  if ($number>500) {
    $number= 500;
  }
  if ($sameHour) {
    $hour= preg_replace("_.*/_", "", $act);
    $filter= " and path like '%$hour'";
  }

  $res= $mysqli->query("select path from webcam_image ".
       "where cam='".$webcam['name']."' ".
       "and path $op '$act' $filter order by path $desc limit 0,$number");

  if ($res) {
    while ($row= $res->fetch_assoc()) {
      $ret[]= $row['path'];
    }
    $res->free();
  }
  return $ret;
}

// --------------------------------------------------------------------------
// Read EXIF meta data file stored with the image
function getExif($when) {
  global $webcam;
  $when= preg_replace("/_.*/", "", $when);
  $rr= array();
  $exDate= "?"; $exModel= ""; $exTime= ""; $exFnum= ""; $exIso= ""; 
  $exBias= ""; $exLen= ""; $exLum= ""; $exLens= ""; $exMeas= null;
  if (preg_match("#(\d+)/(\d+)/(\d+)/(\d\d)(\d\d)#", $when, $rr)) {
    $exDate= "$rr[3].$rr[2].$rr[1] $rr[4]:$rr[5]";
  }
  $fh= @fopen($when."_ex.txt", "r");
  if ($fh) {
    while (! feof($fh)) {
      $line= fgets($fh);
      if (preg_match("/^(.*?) *\|(.*?)\s*$/", $line, $rr)) {
        $key= $rr[1];
        $val= $rr[2];
        if (preg_match("/^Model/i", $key)) {
          if (preg_match("/^C[0-9]/", $val)) {
            $val= "Olympus $val";
          }
          $exModel= $val;
        }
        if (preg_match("/^(Belichtungsz|Exposure Time)/i", $key)) {
          $exTime= $val;
        }
        if (preg_match("/^(F Num|FNum)/i", $key)) {
          $exFnum= $val;
        }
        if (preg_match("/^ISO/i", $key)) {
          $exIso= $val;
        }
        if (preg_match("/^(Exposure Bias|Belichtungsabw)/i", $key)) {
          $exBias= $val;
        }
        if (preg_match("/^(Brennweite|Focal Len)/i", $key)) {
          $exLen= $val;
        }
        if (preg_match("/^(Lens Name)/i", $key) && $val) {
          $exLen.= " &nbsp; ($val)";
        }
        if (preg_match("/^(Luminance)/i", $key)) {
          $exLum= $val;
        }
        if (preg_match("/^(MeasuredEV)/i", $key) && $val) {
          $exMeas= $val;
        }
      }
    }
    fclose($fh);
    if ($exLum && $exMeas) {
      $exLum.= " &nbsp; ($exMeas)";
    }
    if ($webcam['lang']=="de") {
      return "<table>".
        "<tr><td>Aufnahmezeit</td><td>$exDate</td></tr>".
        "<tr><td>Kameramodell</td><td>$exModel</td></tr>".
        "<tr><td>Brennweite</td><td>$exLen</td></tr>".
        "<tr><td>Belichtungszeit</td><td>$exTime</td></tr>".
        "<tr><td>Blende</td><td>$exFnum</td></tr>".
        "<tr><td>ISO-Empfindlichkeit</td><td>$exIso</td></tr>".
        "<tr><td>Belichtungskorrektur</td><td>$exBias</td></tr>".
        "<tr><td>Bildhelligkeit</td><td>$exLum</td></tr>".
        "</table>";
    }
    else {
      return "<table>".
        "<tr><td>Capture time</td><td>$exDate</td></tr>".
        "<tr><td>Camera model</td><td>$exModel</td></tr>".
        "<tr><td>Focal length</td><td>$exLen</td></tr>".
        "<tr><td>Exposure time</td><td>$exTime</td></tr>".
        "<tr><td>Aperture</td><td>$exFnum</td></tr>".
        "<tr><td>ISO speed</td><td>$exIso</td></tr>".
        "<tr><td>Exposure compensation</td><td>$exBias</td></tr>".
        "<tr><td>Luminance</td><td>$exLum</td></tr>".
        "</table>";
    }
  }
  else {
    return "Keine EXIF-Informationen zu diesem Zeitpunkt gefunden.";
  }
}

// --------------------------------------------------------------------------
// Read "Best-Of"-list. Traverses filesystem recursive.
function bestofList($path, &$bestof) {
  $dh= opendir($path);
  if ($dh) {
    $dir= array();
    while (($file= readdir($dh)) !== false) {
      array_push($dir, $file);
    }
    closedir($dh);
    sort($dir);
    foreach ($dir as $ele) {
      if ($ele[0] != ".") {
        $p= "$path/$ele";

        $rr= array();
        if (preg_match("/(\d{4}\/\d{2}\/\d{2}\/\d{4})_sm.jpg/", $p, $rr)) {
          array_push($bestof, $rr[1]);
        }
        elseif (is_dir($p)) {
          bestofList($p, $bestof);
        }
      }
    }
  }
}

// --------------------------------------------------------------------------
// Some ugly JSON helper means.
$json_sent= array();
$json_level= 0;

function json_obj($name= null, $arr= false) {
  global $json_sent;
  global $json_level;
  if ($json_sent[$json_level]) {
    echo(",\n");
  }
  if ($name) {
    echo("\n\"$name\":");
  } 
  echo($arr? "\n[\n": "\n{\n");
  $json_level++;
  $json_sent[$json_level]= 0;
}
function json_obj_end($arr= false) {
  global $json_sent;
  global $json_level;
  echo($arr? "\n]\n": "\n}\n");
  $json_level--;
  $json_sent[$json_level]= 1;
}
function json_var($val, $name= null) {
  global $json_sent;
  global $json_level;
  $val= str_replace('\\', '\\\\', $val);
  $val= str_replace('"', '\\"', $val);
  if ($json_sent[$json_level]) {
    echo(",\n");
  }
  if ($name) {
    echo("\"$name\":");
  }
  if (preg_match("/^(true|false)$/", $val)) {
    echo(" $val");
  }
  else {
    echo(" \"$val\"");
  }
  $json_sent[$json_level]= 1;
}
