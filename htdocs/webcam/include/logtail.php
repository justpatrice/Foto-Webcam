<?
// --------------------------------------------------------------------------
// Foto-Webcam.eu
// Read log data from file system and drop them to the status page
//
// Flori Radlherr, http://www.radlherr.de
// This is free software, see COPYING for details.
// --------------------------------------------------------------------------
//
require "common.php";

// Show only the log of public cameras (security by obscurity)
foreach ($webcam['overview'] as $wc) {
  $wcok[$wc[0]]= true;
}
$newer= @$_GET['newer']+0.0;
$res= Array();

$logFile= fopen($webcam['workPath']."/tmp/log.txt", "rt");
if ($logFile) {
  $loops= 0;
  while (true) {
    fseek($logFile, ($newer>0)?-50000:-500000, SEEK_END);
    fgets($logFile);     // swallow possible broken line

    while (! feof($logFile)) {
      $line= str_replace("\n","", fgets($logFile));
      $f= explode(";", $line);
      if ($newer>0 && $f[2]<=$newer) {
        continue;
      }
      if ($f[3]=="debug") {
        continue;
      }
      if (isset($wcok[$f[0]])) {
        $res[]= $f;
      }
    }
    $loops++;
    if (count($res)>0 || $loops>30) {
      break;
    }
    sleep(1);
  }
  fclose($logFile);
}
header("Content-Type: application/json");
header("Expires: 0");
print(json_encode($res));
