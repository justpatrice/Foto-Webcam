<?
// --------------------------------------------------------------------------
// Foto-Webcam.eu
// Mobile overview page
//
// Flori Radlherr, http://www.radlherr.de
// This is free software, see COPYING for details.
// --------------------------------------------------------------------------
//
require($_SERVER['DOCUMENT_ROOT']."/webcam/include/config.php");
?>
<!DOCTYPE html>
<html>
<head>
  <meta name="viewport" content="width=device-width">
  <title>Foto-Webcam.eu</title>
  <style>
  img { border: none; }
  body { margin: 0; }
  </style>
</head>
<body>
<?
foreach ($webcam['overview'] as $cam) {
  $name= $cam[0];
  $current= $cam[1];
  $link= $cam[2];
  $title= $cam[3];
  if ($current === null) {
    $current= $webcam['uri']."/$name/current";
    // If hosted locally and older than 5h, do not list
    if ($current[0]=='/') {
      $img= $_SERVER['DOCUMENT_ROOT']."$current/400.jpg";
      if (filemtime($img)<time()-18000){
        continue;
      }
    }
  }
  $current= preg_replace("#/$#", "", $current);
  if ($link === null) {
    $link= $webcam['uri']."/$name/";
  }

  echo "<a title='$title' href='$current/1200.jpg'><img src='$current/400.jpg'></a><br>\n";
}
?>
</body></html>
