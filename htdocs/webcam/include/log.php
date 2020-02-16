<?
// --------------------------------------------------------------------------
// Foto-Webcam.eu
// Store log data as response of webcam host commands
//
// Flori Radlherr, http://www.radlherr.de
// This is free software, see COPYING for details.
// --------------------------------------------------------------------------
//
require "imgutil.php"; checkUploadVars() or die;

$tmpName= $webcam['workPath']."/tmp/response.tmp";

if (!move_uploaded_file($_FILES["log"]["tmp_name"], $tmpName)) {
  echoLog("No upload file.");
  die;
}

$serial= 0;
if (isset($_POST['serial'])) {
  $serial= $_POST['serial'];
}

$tmpfile= fopen($tmpName, "rt");
if ($tmpfile) {
  doLog(fread($tmpfile, 100000), "response", $serial);
  fclose($tmpfile);
  unlink($tmpName);
  echo("log ok ($serial).\n");
}

?>
