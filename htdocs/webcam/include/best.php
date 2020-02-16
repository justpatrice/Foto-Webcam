<?
// --------------------------------------------------------------------------
// Foto-Webcam.eu
// Store the best images found on a particular webcam image archive
// Images are stored as a copy of the original
//
// Flori Radlherr, http://www.radlherr.de
// This is free software, see COPYING for details.
// --------------------------------------------------------------------------
//
require "common.php";

$bestdir= "bestof";
$path=  $webcam['workPath'];
$name=  $webcam['name'];
$img=   $webcam['parImg'];
$wcdir= $webcam['uri']."/".$webcam['name'];

$capflood= false;
$capok= false;
$captcha= @$_POST['captcha'];
$fs= 0;
if ($captcha) {
  $captcha= preg_replace("/[^a-z]/", "", $captcha);
  $capfile= fopen($webcam['workPath']."/tmp/captcha.txt", "r");
  if ($capfile) {
    while (! feof($capfile)) {
      $line= fgets($capfile);
      print("$line '$captcha'<br>");
      if (strpos($line, $captcha)>0) {
        $capok= true;
      }
      $fs++;
      if ($fs>20) {
        $capok= false;  # seems brute force
        $capflood= true;
      }
    }
    fclose($capfile);
  }
}

$isDelete= false;
if (isset($_GET['delete'])) {
  $isDelete= true;
  $phrase= "
    <b>Das Bild aus der Liste der besten Bilder entfernen?</b>
    <br><br>
    Bitte nur Bilder löschen, bei denen du dir sicher bist, 
    dass vergleichbar gute Bilder bereits in der Sammlung sind.
  ";
  $action= "Bild <b>löschen</b>";
}
else {
  $phrase= "
    <b>Das Bild in die Liste der besten Bilder aufnehmen?</b>
    <br><br>
    Bitte nur Bilder speichern, die wirklich besondere
    Eigenschaften haben und sich vom Durchschnitt deutlich
    unterscheiden.
  ";
  $action= "Bild markieren";
}

$captimg= "<img src='captcha.php?wc=$name' align='absmiddle'>";
if ($capok) {
  $captimg= "";
}

print "<!DOCTYPE hmtl>\n<html><head>
  <title>Die besten Bilder</title>
  <style>
  body,form { margin:0; }
  div {
    padding: 10px;
    font-family: arial,helvetica,sans-serif;
    font-size: 14px;
    background: -moz-linear-gradient(top, #e0e0e0, #f0f0f0);
    background: -webkit-gradient(linear, left top, left bottom, from(#e0e0e0), to(#f0f0f0));
    -ms-filter: 'progid:DXImageTransform.Microsoft.gradient(startColorstr=#e0e0e0, endColorstr=#f0f0f0)';
    position: fixed;
    top: 0px;
    bottom: 0px;
    left: 0px;
    right: 0px;
  }
  </style></head><body><div>
  <img src='$wcdir/{$img}_sm.jpg' align='right'>
  $phrase
  <br><br>
  Zur Sicherheit übertrage die Buchstabenfolge in das Feld:
  <form method='post'>
  Bitte $captimg eintragen:
  <input autocomplete='off' type='text' name='captcha' size='5'>
  <button type='submit' name='submit'>$action</button>
  <button type='button' name='abort' onclick='window.close()'>Abbrechen</button>
  <input type='hidden' name='submitted' value='1'>
  </form>
  <br>
";

$message= 
  "Hinweis: Deine IP-Adresse und die Uhrzeit wird dauerhaft gespeichert.";

$ip= $_SERVER{'REMOTE_ADDR'};

if (isset($_POST['submitted'])) {
  if ($capok) {
    $message= "";
    $src= "$path/$img";
    $dest= "$path/$bestdir/$img";
    $logdir= "$path/$bestdir";

    $destdir= preg_replace("/\/[^\/]+$/", "", $dest);
    $srcpat=  "$src*";

    $message= "";
    $errors= 0;
    $logname= null;
    $mysqli= null;
    if ($webcam['useDatabase']) {
      $mysqli= openMysql();
    }

    # Alle zu einem Bild zugehoerigen Dateien verlinken
    foreach (glob($srcpat) as $filename) {
      $destfile= preg_replace("/^.*\//", "", $filename);

      if (@filesize("$destdir/$destfile")) {
        if ($isDelete) {
          if (! @unlink("$destdir/$destfile")) {
            $message= "<b style='color:red'>Mindestens eine Datei ".
              "konnte nicht gelöscht werden";
          }
          if ($mysqli) {
            $mysqli->query("update webcam_bestof set deleted=now(),".
                  "del_ip='$ip' where cam='$name' and path='$img'");
          }
        }
        $logname= "remove.log";
      }
      else {
        if (@filesize($filename)>0 && !$isDelete) {
          @mkdir($destdir,0775,true);
          if (! copy($filename, "$destdir/$destfile")) {
            print("$filename - $destdir/$destfile<br>");
            $errors+=1;
            $message= "<b style='color:red'>Mindestens eine Datei ".
              "konnte nicht kopiert werden";
          }
          if ($mysqli) {
            $mysqli->query("replace webcam_bestof set added=now(),".
                     "add_ip='$ip',cam='$name',path='$img'");
          }
          $logname= "add.log";
        }
      }
    }

    if ($logname) {
      if ($logf= @fopen("$logdir/$logname", "a")) {
        @fputs($logf, strftime("%d.%m.%y %H:%M:%S").";$ip;$wcdir/$img\n");
        @fclose($logf);
      }
    }
    else {
      $message= "<b style='color:red'>Keine passendes Bild gefunden.</b>";
    }
  }
  else {
    if ($capflood) {
      $message= "<b style='color:red'>Zu viele Zugriffe, bitte in 10 ".
        "Minuten nochmal probieren.</b>";
    }
    else {
      $message= "<b style='color:red'>Bitte Buchstabenfolge in das ".
        "Feld eintragen.</b>";
    }
  }
}

$waitclose= "";
if ($message == "") {
  $message= "<b style='color:green'>OK.</b>";
  $waitclose= "
    if (opener && opener.webcam) {
      opener.webcam.go(opener.webcam.data.image);
    }
    setTimeout(function() { 
      window.close(); 
    }, 800);
  ";
}

print "
  $message
  <script>
  $waitclose
  setTimeout(function() {
    document.forms[0].captcha.focus();
  }, 300);
  </script>
  </div></body></html>
";
?>
