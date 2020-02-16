<?
// --------------------------------------------------------------------------
// Foto-Webcam.eu
// Web-based upload of weather data
//
// Flori Radlherr, http://www.radlherr.de
// This is free software, see COPYING for details.
// --------------------------------------------------------------------------
//
require "imgutil.php"; checkUploadVars() or die;

$wx=  $_POST['wx'];
$typ= $_POST['typ'];

$fields= array();
$raw_fields= array();
$camtxt= "";

if ($typ == "hb627") {
  $values= array();
  foreach (explode("\n", $wx) as $line) {
    $rr= array();
    if (preg_match("/(\d+)=(\d+)/", $line, $rr)) {
      $values[]= $rr[2];
    }
  }
  echo count($values)." values found.\n";
  if (count($values)==8) {
    if (is_callable($webcam['wxFunc'])) {
      if (! $webcam['wxFunc']($values, $fields, $raw_fields, $camtxt)) {
        echo "No valid wx data.\n";
        exit;
      }
    }
  }
}
elseif ($typ == "usb4all") {
  $values= array();
  foreach (explode("\n", $wx) as $line) {
    $values[]= $line;
  }
  echo count($values)." values found.\n";
  if (count($values)>=2) {
    if (is_callable($webcam['wxFunc'])) {
      if (! $webcam['wxFunc']($values, $fields, $raw_fields, $camtxt)) {
        echo "No valid wx data.\n";
        exit;
      }
    }
  }
}
elseif ($typ == "ws2350") {
  $values= array();
  foreach (explode("\n", $wx) as $line) {
    $rr= array();
    if (preg_match("/([a-z0-9]+) (.*)/i", $line, $rr)) {
      $values[$rr[1]]= $rr[2];
    }
  }
  $error= null;
  if ($values['To']>50) $error= "To too high";
  if ($values['To']>$values['Tomax']) $error= "To > Tomax";
  if ($values['To']<$values['Tomin']) $error= "To < Tomin";;
  if ($values['Rp']>1200) $error= "Rp too high";
  if ($values['Rp']<800)  $error= "Rp too low";
  if ($values['RHo']>105) $error= "RHo too high";

  if ($error === null) {
    if (is_callable($webcam['wxFunc'])) {
      if (! $webcam['wxFunc']($values, $fields, $raw_fields, $camtxt)) {
        echo "No valid wx data.\n";
        exit;
      }
    }
  }
  else {
    echo "$error\n";
  }
}
else {
  echo "ERROR: typ unknown: $typ\n";
}

if (count($fields)>0) {
  $keys= array_keys($fields);

  $mysqli= openMysql();
  if ($mysqli) {
    // Write values to mysql
    foreach ($keys as $key) {
      $mysqli->query("insert webcam_wx set wc='${webcam['name']}', ".
                     "stamp=now(),day=now(),field='$key',".
                     "val='{$fields[$key]}',raw_val='{$raw_fields[$key]}'");
    }
  }

  // Write values to RRD graph, if present
  if (@is_callable($webcam['rrdFunc'])) {
    $webcam['rrdFunc']($fields);
  }

  // Write camera text
  if ($camtxt != "") {
    $camfile= fopen($webcam['workPath']."/wetter/cam.txt", "w");
    if ($camfile) {
      fwrite($camfile, $camtxt);
      fclose($camfile);
    }
  }

  // Write readable text
  $txtfile= fopen($webcam['workPath']."/wetter/wx.txt", "w");
  if ($txtfile) {
    $txt= "";
    foreach ($keys as $key) {
      $txt.= "$key=".$fields{$key}."\n";
    }
    fwrite($txtfile, $txt);
    fclose($txtfile);
  }
}
?>
