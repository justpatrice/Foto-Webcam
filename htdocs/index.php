<?
// --------------------------------------------------------------------------
// Foto-Webcam.eu
// Main page
//
// Flori Radlherr, http://www.radlherr.de
// This is free software, see COPYING for details.
// --------------------------------------------------------------------------
//
require($_SERVER['DOCUMENT_ROOT']."/include/navigation.php");
navDataPrepare("Webcam-Übersicht", "Webcam<br>Übersicht", "", 
     "foto-webcam,dslr");
echo navBeginHeader();
echo navHeaderData();
echo navEndHeader();
echo navLeftBar();
echo navBeginContent(true);

$webcamInclude= $navData['webcamInclude'];
$w=400;
$h=floor(($w/16)*9);
echo("
<script language='JavaScript' src='$webcamInclude/jquery.lazyload.min.js'>
</script>
<style>
  a.wcovlink {
    max-height: ${h}px;
    overflow: hidden;
    color: black;
    text-decoration: none;
    margin: 0;
    margin-bottom: 1px;
    margin-right: 1px;
    padding: 0;
  }
  .wcovlink {
    display: inline-block;
    width: ${w}px;
  }
  .wcovimg {
    width: 400px;
    min-width: 400px;
    min-height: 225px;
  }
  .wcovcont {
    width: 400px;
    min-width: 400px;
    min-height: 225px;
    display: none;
  }
</style>
<div style='margin-top:10px'></div>
");

$num= 0;
foreach ($webcam['overview'] as $cam) {
  $name= $cam[0];
  $current= $cam[1];
  $link= $cam[2];
  $title= $cam[3];
  if ($current === null) {
    $current= $webcam['uri']."/$name/current";
    if (filemtime($_SERVER['DOCUMENT_ROOT']."$current/400.jpg")<time()-18000) {
      continue;
    }
  }
  $current= preg_replace("#/$#", "", $current);
  if ($link === null) {
    $link= $webcam['uri']."/$name/";
  }
  $num++;
  $lazy= "";
  if ($num>12) {
    $lazy= "'$webcamInclude/trans.png' data-original=";
  }
  echo "<a class='wcovlink' href='$link' title='$title'>".
    "<img src=$lazy'$current/400.jpg' class='wcovcont'>".
    "<noscript><img src='$current/400.jpg' class='wcovimg'></noscript></a>\n";
}
if (isset($webcam['extOverview'])) {
  echo
    "<div class='menu menu-norm' style='margin-top:5px;margin-bottom:5px;'>".
    "Foto-Webcams von anderen Betreibern mit ähnlicher Technik:".
    "</div>";
  foreach ($webcam['extOverview'] as $cam) {
    $name= $cam[0];
    $current= $cam[1];
    $link= $cam[2];
    $title= $cam[3];
    $current= preg_replace("#/$#", "", $current);
    echo
      "<a class='wcovlink' target='_blank' href='$link' title='$title'>".
      "<img src='$webcamInclude/trans.png' ".
      "data-original='$current/400.jpg' class='wcovcont'>".
      "<noscript><img src='$current/400.jpg' class='wcovimg'></noscript></a>\n";
  }
}
?>
<script>

// Auto-Update der Bilder
$(document).ready(function() {
  setInterval(function() {
    var thisMinute= (new Date().getMinutes())%10;
    if (thisMinute == 5) {
      $(".wcovimg").each(function(img) {
        var src= $(this).attr("src");
        if (! src.match(/trans.png/)) {
          src= src.replace(/\?.*$/, "");
          src+= "?"+new Date().getTime();
          $(this).attr("src", src);
        }
      });
    }
  }, 60000);
  $(".wcovcont").addClass("wcovimg");
  $(".wcovcont").fadeIn(800);
  //$(".wcovcont").lazyload({effect : "fadeIn"});
  setTimeout(function() {
    $(".wcovcont").lazyload({effect : "fadeIn"});
  }, 100);
});
</script>
<div class='menu menu-norm' style='margin-top: 5px;'>
Webcam-Bilder für Smartphones optimiert: <a href="/m/">foto-webcam.eu/m</a>
</div>
<br>
<? navEnd(); ?>
