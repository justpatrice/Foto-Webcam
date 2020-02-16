<?
// --------------------------------------------------------------------------
// Foto-Webcam.eu
// Webcam Status Page
//
// Flori Radlherr, http://www.radlherr.de
// This is free software, see COPYING for details.
// --------------------------------------------------------------------------
//
require "common.php";

$webcam['navHeader']();

// Include jQuery only of not already done by a navigation framework
$jQuery= "";
if (! @$webcam['navHasjQuery']) {
  $jQuery= "<script language='JavaScript' ".
           "src='${webcam['includeUri']}/jquery.js'></script>";
}

// ----------------------------------------------------------------------------
// Generate JS initialisation and HTML body
print "$jQuery
  <script>
  jQuery('#left-box').css('display','none');
  </script>
  <style>
  hr {
    clear: both;
    margin: 0px;
  }
  h3 {
    margin-bottom: 5px;
  }
  </style>
<h2>Verlinkung und Einbindung der Bilder von www.foto-webcam.eu</h2>
Das Setzen von Links auf diese Webcams ist ausdrücklich erlaubt, wenn
als Linkziel die Adresse<br>
<b>http://www.foto-webcam.eu/webcam/&lt;name&gt;/</b> genutzt wird.
<br><br>
Auch eine Live-Einbindung des Kamerabildes ist erlaubt, sofern beim Klick
auf das Bild obiger Link öffnet.<br>
Für diesen Zweck stehen Momentanbilder mit konstantem Namen zur Verfügung.
<br><br> 
Diese werden in verschiedenen Auflösungen erzeugt (Format 16:9):<br>
150.jpg&nbsp;(150x85),  
180.jpg&nbsp;(180x101), 
240.jpg&nbsp;(240x135), 
320.jpg&nbsp;(320x180), 
400.jpg&nbsp;(400x225), 
640.jpg&nbsp;(640x360), 
720.jpg&nbsp;(720x405), 
816.jpg&nbsp;(816x459), 
1200.jpg&nbsp;(1200x675),
full.jpg&nbsp;(Kamera-Auflösung)
<br><br>
Die Adresse ist:
<b>http://www.foto-webcam.eu/webcam/&lt;name&gt;/current/&lt;breite&gt;.jpg</b>
<br><br>
Eine Einbindung z.B. eines 400-Pixel-Bildes kann also so aussehen:<br>
<tt>&lt;a href='http://www.foto-webcam.eu/webcam/&lt;name&gt;/'&gt;&lt;img<br>
src='http://www.foto-webcam.eu/webcam/&lt;name&gt;/current/400.jpg'&gt;&lt;/a&gt;</tt>
<br><br>
Dabei ist &lt;name&gt; jeweils durch den aktuellen Webcam-Namen 
(siehe URLs im Folgenden) zu ersetzen.
<br><br><br>
<hr>
";

$glob= $webcam;


foreach ($glob['overview'] as $cam) {
  $name= $cam[0];
  $current= $cam[1];
  $link= $cam[2];
  $webcam['elevation']= "";
  require("../$name/config.php");
  $title= $webcam['title'];
  if ($current == null) {
    $current= "http://".$webcam['server'].$webcam['uri']."/$name/current";
  }
  $current= preg_replace("#/$#", "", $current);
  if ($link == null) {
    $link= $webcam['uri']."/$name/";
  }
  $coords= $webcam['latitude'].",".$webcam['longitude'];
  if (preg_match("/^\//", $link)) {
    $link= "http://www.foto-webcam.eu$link";
  }
  $ele= "";
  if ($webcam['elevation']) {
    $ele= " &nbsp;&nbsp;&nbsp; (".$webcam['elevation']."m)";
  }

  $standort= "";
  $kamera= "";
  $state= "idle";

  $fi= @fopen("../$name/infos/index.html", "rt");
  if ($fi) {
    while (! feof($fi)) {
      $line= fgets($fi);
      if (preg_match("/^Kamera:/", $line)) {
        $kamera= $line;
      }
      if (preg_match("/>Standort</", $line)) {
        $state= "st";
      }
      elseif (preg_match("/<h3>/", $line)) {
        $state= "idle";
      }
      elseif ($state == "st") {
        $standort.= $line;
      }
    }
    fclose($fi);
  }

  $standort= preg_replace("/<img/","<ximg", $standort);

  print "
   <h2>$title</h2>
   <a href='$link' title='$title'>
   <img align='right' id='img_$name' src='$current/240.jpg'></a>
   <div id='status_$name' class='wcstatus'>
   <a target='_blank'
      href='../$name/infos/'>Vollständige Infos zu dieser Kamera</a>
   <h3>Standort</h3>
   Position:
   <a target='_blank' href='http://maps.google.de?q=$coords'>$coords</a>$ele
   <br><div style='margin-bottom:5px;'></div>
   $standort
   <h3>Technik</h3>
   $kamera
   <h3>Verlinkung und Einbindung des Bildes</h3>
   Bitte das Bild stets mit der Webcam-URL verlinken, siehe oben!<br>
   Webcam-URL: <a href='$link'>$link</a><br>
   Aktuelles Bild: <a href='${current}/816.jpg'>${current}/816.jpg</a>
   </div>
   <br>
   <br>
   <hr>
  ";
}

$webcam['navFooter']();
?>
