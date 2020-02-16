<?
// --------------------------------------------------------------------------
// Foto-Webcam.eu
// Store Webcam labels into database
//
// Flori Radlherr, http://www.radlherr.de
// This is free software, see COPYING for details.
// --------------------------------------------------------------------------
//
require "common.php";

$webcamName=   $webcam['name'];
$redirect=     $webcam['workUri']."/#/editlabels";
$errorMessage= "Unbekannter Fehler";

$mysqli= openMysql();

if (isset($_GET['lab_txt'])) {
  if ($mysqli) {
    if (preg_match("/wcoleditpw=mbt/", $_SERVER['HTTP_COOKIE'])) {
      
      $id= (int)$_GET['lab_id'];
      $txt= $_GET['lab_txt'];
      $href= $_GET['lab_href'];
      $res= (int)$_GET['lab_res'];
      $rev= (int)$_GET['lab_rev'];
      $x= (float)$_GET['lab_x'];
      $y= (float)$_GET['lab_y'];

      $set= sprintf("set txt='%s', href='%s', x='%s', y='%s', res='%s', ".
            "rev='%s', wc='$webcamName'",
            $mysqli->escape_string($txt),
            $mysqli->escape_string($href),
            $mysqli->escape_string($x),
            $mysqli->escape_string($y),
            $mysqli->escape_string($res),
            $mysqli->escape_string($rev));

      if ($id>0) {
        $mysqli->query("update webcam_labels $set where id='$id'");
      }
      else {
        $mysqli->query("insert webcam_labels $set");
      }
      Header("Location: $redirect");
      exit;
    }
    else {
      $errorMessage= "Kennwort ist falsch oder wurde nicht eingegeben";
    }
  }
  else {
    $errorMessage= "MySQL-Zugangsdaten sind nicht konfiguriert";
  }
}
else {
  $errorMessage= "Formulardaten nicht gefunden";
}

$webcam['navHeader']();

print <<<END
  <h2>Fehler beim Speichern der Beschriftung</h2>
  <br>
  <b style='color:#a00000'>
  $errorMessage.
  </b><br><br>
  <a href="$redirect">Zurück zur Eingabe</a>
END;

$webcam['navFooter']();


?>
