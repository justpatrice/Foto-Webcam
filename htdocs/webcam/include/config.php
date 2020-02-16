<?
// --------------------------------------------------------------------------
// Foto-Webcam.eu
// Global configuration
//
// Flori Radlherr, http://www.radlherr.de
// This is free software, see COPYING for details.
// --------------------------------------------------------------------------

// The website
#$webcam['server']= "www.foto-webcam.eu";
$webcam['uri']=    "/webcam";

// Image upload
$webcam['uploadkey']=   "replacethistoyourkey";
$webcam['topOffset']=   200;
$webcam['maxGamma']=    1.5;
$webcam['maxIso']=      1600;
$webcam['maxExposure']= 5;

// Standard image formats
$webcam['aspectRatio']= 16/9;
$webcam['mainWidth']=   816;
$webcam['hdWidth']=     1200;
$webcam['thumbWidth']=  114;
$webcam['hugeWidth']=   4272;
$webcam['hugeHeight']=  2848;

// Additional formats stored in "current" folder
$webcam['resolutions']= Array(150,180,240,320,400,640,720,816,1200,1600);

// When to refresh a shown image
$webcam['actMinute']=   2;

// When to capture the image
$webcam['captureInterval']= 600;
$webcam['captureOffset']= 0;
// Special capture times mmddHHMM (regexp format) -> New year firework
$webcam['specialTimes']= "/(12312354|12312357|01010003|01010006)/";

// This message is shown if no current image could be obtained
$webcam['errorMsg']= 
        "Wegen einer technischen Störung ist die Kamera derzeit offline";

// List additional cameras on this site or cross-linked
// each is: Array(Name, Current-Image-Url, Link-Url, Title)
// Hint: for local cams, both Urls shall be null
$webcam['overview']= Array(
  Array("beispiel", null, null,
        "Beispiel zum Test")
);

// Hide camera images older than this time (seconds)
$webcam['overviewHideTime']= 1800;

$webcam['extOverview']= Array(
  Array("lienz", 
        "http://www.foto-webcam.eu/webcam/lienz/current/", 
        "http://www.foto-webcam.eu/webcam/lienz/",
        "foto-webcam.eu: Lienz / Zettersfeld"),
  Array("lorenzalm",
        "http://www.panorama-blick.at/webcam/lorenzalm/current",
        "http://www.panorama-blick.at/webcam/lorenzalm/",
        "panorama-blick.at: Mörtschach im Mölltal / Kärnten"),
);

// ----------------------------------------------------------------------------
// Tell that we have already jQuery included in our nav header
$webcam['navHasjQuery']= true;

// Generate HTML navigation header
$webcam['navHeader']= function() {
  global $webcam;
  global $currentRoot;
  require_once("$currentRoot/include/navigation.php");
  $webcamWorkuri= $webcam['workUri'];

  navLeftBox("
  <div style='margin: 8px;'>
  <a class='menu menu-norm' href='$webcamWorkuri/infos/'>Infos zur Webcam</a>
  <a class='menu menu-norm' href='$webcamWorkuri/'>Zum aktuellen Bild</a>
  <div style='margin: 5px;'></div>
  </div>
  <noscript>
  <div style='margin-left: 8px;'>
  Webcam-Archiv ist<br>
  nur mit JavaScript<br>
  nutzbar.
  </div>
  </noscript>
  <div style='margin: 5px;'></div>");

  navFullHeader($webcam['title'], $webcam['short'], 
        "Fotokamera als Webcam: ".$webcam['title'], @$webcam['keywords']);
};

// ----------------------------------------------------------------------------
// Generate page footer
$webcam['navFooter']= function() {
  navEnd();
};
?>
