<?
// --------------------------------------------------------------------------
// Foto-Webcam.eu
// Webcam archive navigation, main program
//
// Flori Radlherr, http://www.radlherr.de
// This is free software, see COPYING for details.
// --------------------------------------------------------------------------
//
require "common.php";

$webcamName=      $webcam['name'];
$webcamInclude=   $webcam['includeUri'];
$webcamWorkuri=   $webcam['workUri'];
$webcamWorkpath=  $webcam['workPath'];
$webcamForceSize= 0;
$webcamIgnoreHash= "false";

$msgAll=      "All webcams";
$msgBest=     "The best shots";
$msgInfos=    "Site information";
$msgHelp=     "Help";
$msgExif=     "Exif data";
$msgSettings= "Settings";
$msgOthers=   "Views from other Foto-Webcam sites:";

if ($lang == "de") {
  $msgAll=      "Alle Webcams";
  $msgBest=     "Die besten Bilder";
  $msgInfos=    "Webcam-Infos";
  $msgHelp=     "Bedienungs-Tipps";
  $msgExif=     "Exif-Daten";
  $msgSettings= "Einstellungen";
  $msgOthers=   "Foto-Webcams von Partnern mit ähnlicher Technik:";
}

if ($lang == "it") {
  $msgAll=      "Tutte le telecamere";
  $msgBest=     "Le immagini più belli";
  $msgInfos=    "Informazione"; // sulla telecamera";
  $msgHelp=     "Guida";
  $msgExif=     "Dati Exif";
  $msgSettings= "Impostazioni";
  $msgOthers=   "Telecamere da altri partner che stanno usando un sistema simile:";
}

// ----------------------------------------------------------------------------
// The main page layout
$adm= "";
$username= "";
if ($session['valid']) {
  $adm= '<td id="wcopt-admin">Admin</td>';
  $username= $session['username'];
}
$webcamMenu= <<<END
<table class="wcopttab wctext"><tr>
  <td id="wcopt-webcams" class="wcoptact">$msgAll</td>
  <td id="wcopt-bestof">$msgBest</td>
  <td id="wcopt-infos">$msgInfos</td>
  <td id="wcopt-help">$msgHelp</td>
  <td id="wcopt-exif">$msgExif</td>
  $adm
  <td id="wcopt-settings" title="$msgSettings" style="width:20px;">
  <img id="wcopt-settings-img" src="$webcamInclude/settings.png"></td>
</tr></table>
<div id="wcinfo-webcams" class="wcvgrad wctext">
END;

// Render cam overview
if (isset($webcam['overview'])) {
  $num= 0;
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
    $lazy1= "";
    $lazy2= "";
    if ($num>=20) {
      $lazy1= "<img src='$webcamInclude/trans.png' style='display:none' ".
              "data-original='$current/180.jpg' class='wcminicont'><noscript>";
      $lazy2= "</noscript>";
    }
    $webcamMenu.= "<a class='wcminilink' href='$link' title='$title'>".
      "$lazy1<img src='$current/180.jpg' class='wcminiimg'>$lazy2</a>\n";
    $num++;
  }
  $webcamMenu.= '
    <span class="wcminilink"></span>
    <span class="wcminilink"></span>
    <span class="wcminilink"></span>
    <span class="wcminilink"></span>
    <span class="wcminilink"></span>
    <span class="wcminilink"></span>
  ';
}

if (isset($webcam['extOverview'])) {
  $webcamMenu.= "<div class='menu menu-norm'>$msgOthers</div>";
  foreach ($webcam['extOverview'] as $cam) {
    $name= $cam[0];
    $current= $cam[1];
    $link= $cam[2];
    $title= $cam[3];
    $style= "";
    if (isset($cam[4])) {
      $style= $cam[4];
    }
    $current= preg_replace("#/$#", "", $current);
    $target= "";
    if (preg_match("/^http/", $link)) {
      $target= "target='_blank'";
    }
    $webcamMenu.= 
      "<a class='wcminilink' $target href='$link' title='$title'>".
      "<img src='$webcamInclude/trans.png' style='display:none' ".
      "data-original='$current/180.jpg' class='wcminicont'>".
      "<noscript><img src='$current/180.jpg' class='wcminimg'></noscript>".
      "</a>\n";
  }
  $webcamMenu.= '
    <span class="wcminilink"></span>
    <span class="wcminilink"></span>
    <span class="wcminilink"></span>
    <span class="wcminilink"></span>
    <span class="wcminilink"></span>
    <span class="wcminilink"></span>
  ';
}

$webcamMenu.= <<<END
</div>
<div id="wcinfo-infos" class="wcvgrad wctext" style="display:none">
  <img src="$webcamInclude/loading.gif">
</div>
<div id="wcinfo-help" class="wchelp wcvgrad wctext">
  <img src="$webcamInclude/loading.gif">
</div>
<div id="wcinfo-exif" class="wcvgrad wctext">
  <img src="$webcamInclude/loading.gif">
</div>
<div id="wcinfo-bestof" class="wcvgrad wctext" style="display:none">
  <img src="$webcamInclude/loading.gif">
</div>
<div id="wcinfo-settings" class="wcvgrad wctext" style="display:none">
  <img src="$webcamInclude/loading.gif">
</div>
<div id="wcinfo-editlabels" class="wcvgrad wctext" style="display:none">
  <img src="$webcamInclude/loading.gif">
</div>
END;

// ----------------------------------------------------------------------------
// Fetch configuration of this particular camera
require "$webcamWorkpath/config.php";

$webcamTitle=        $webcam['title'];
$webcamShort=        $webcam['short'];
$webcamKeywords=     isset($webcam['keywords'])?$webcam['keywords']:"";
$webcamMainWidth=    $webcam['mainWidth'];
$webcamMainHeight=   $webcam['mainHeight'];
$webcamHdWidth=      $webcam['hdWidth'];
$webcamHdHeight=     $webcam['hdHeight'];
$webcamThumbWidth=   $webcam['thumbWidth'];
$webcamThumbHeight=  $webcam['thumbHeight'];
$webcamHugeWidth=    $webcam['hugeWidth'];
$webcamHugeHeight=   $webcam['hugeHeight'];
$webcamActMinute=    $webcam['actMinute'];
$webcamBoxAdd=       $webcam['boxAdd'];

$webcamUrl= "";
if (isset($webcam['url'])) {
  $webcamUrl= $webcam['url'];
}


// ----------------------------------------------------------------------------
// If requested, load cam information file
if (isset($_GET["infos"])) {
  $fi= @fopen("$webcamWorkpath/infos/index.html", "rt");
  if ($fi) {
    while (! feof($fi)) {
      $line= fgets($fi);
      if (! preg_match("/^<!--/", $line)) {  // SSI nicht ausgeben
        print $line;
      }
    }
    fclose($fi);
  }
  exit;
}
// ----------------------------------------------------------------------------
// If requested, load help file
if (isset($_GET["help"])) {
  $help= file_get_contents($webcam['includePath']."/help-$lang.html");
  $help= preg_replace("/.webcamInclude/", $webcamInclude, $help);
  print $help;
  exit;
}

// Embedding as frame-content is only allowed for listed (or empty) referers
$doFrame= 0;
if (isset($webcam["frameOk"])) {
  $ok= $webcam["frameOk"];
  $ref= @$_SERVER['HTTP_REFERER'];
  if (strlen($ref)<5 || preg_match("/$ok/", $ref)) {
    $doFrame= isset($_GET["frame"]);
  }
}

// Maybe for some referers the hash navigation shall be ignored
if (isset($webcam["ignoreHash"])) {
  $ign= $webcam["ignoreHash"];
  if (isset($_SERVER['HTTP_REFERER'])) {
    $ref= $_SERVER['HTTP_REFERER'];
    if ($ign && preg_match("/$ign/", $ref)) {
      $webcamIgnoreHash= "true";
    }
  }
}

// Redirect to intended host name, if not already presented
if (isset($webcam['server'])) {
  if ($currentHost != $webcam['server']) {
    Header("301 Moved Permanently");
    Header("Location: http://${webcam['server']}$webcamWorkuri/");
    exit();
  }
}

// Avoid additional garbage beyond base URI
if ($currentUri != $webcamWorkuri && !$doFrame) {
  if (preg_match("/\?\d/", $_SERVER['REQUEST_URI'])) {
    Header("401 Nix");
  }
  elseif (preg_match("/admin/", $_SERVER['REQUEST_URI'])) {
    Header("302 Moved");
    Header("Location: http://$currentHost$webcamInclude/admin.php?wc=".
           $webcamName);
  }
  else {
    Header("302 Moved");
    Header("Location: http://$currentHost$webcamWorkuri/");
  }
  exit();
}

// ----------------------------------------------------------------------------
// Generate JS parameters
$webcamActMinute+= 0;
$webcamHdWidth+= 0;
$webcamHdHeight+= 0;
if (!isset($webcamHugeHeight) || $webcamHugeHeight==0) {
  $webcamHugeHeight= $webcamMainHeight;
  $webcamHugeWidth=  $webcamMainWidth;
}

if ($doFrame) {
  print "<!DOCTYPE html>
    <html><head><title>$webcamTitle</title></head>
    <body style='margin:0;'>
    <style>.wcimg { top:88px !important; }</style>
    <script>
    if(top.frames.length==0) {
      top.location.href= self.location.href.replace(/.frame.*/,'');
    }
    </script>";
  $webcam['navHasjQuery']= null;
  $webcamForceSize= $_GET["frame"]+0;
}
else {
  $webcam['navHeader']();
}

// Include jQuery only of not already done by a navigation framework
$jQuery= "";
if (! @$webcam['navHasjQuery']) {
  $jQuery= "<script language='JavaScript' ".
           "src='$webcamInclude/jquery.js'></script>";
}
// If an absolute path is given by configuration, use it for noscript area
if ($webcamUrl) {
  $webcamWorkuri= preg_replace("/\/$/", "", $webcamUrl);
}

// Incorporate the main css and js parts from separate files
$css= file_get_contents($webcam['includePath']."/webcam.css");
$css= preg_replace("/\/\*.*?\*\//s", "", $css);
$css= preg_replace("/url\(/", "url($webcamInclude/", $css);
$css= preg_replace("/\s+/s", " ", $css);

$js=  file_get_contents($webcam['includePath']."/webcam.js");
$js.= file_get_contents($webcam['includePath']."/jquery.lazyload.min.js");
$js=  preg_replace("/\/\/.*$/m", "", $js);
$js=  preg_replace("/^\s+/m", "", $js);

// ----------------------------------------------------------------------------
// Generate JS initialisation and HTML body
print <<<END
  $jQuery
  <style>
  $css
  .wcwidth {
    width: ${webcamMainWidth}px;
  }
  .wcheight {
    width: ${webcamMainHeight}px;
  }
  </style>
  <script language="JavaScript">
  var webcam= new Object;
  webcam.name= "$webcamName";
  webcam.url= "$webcamUrl";
  webcam.inc= "$webcamInclude/";
  webcam.boxadd= "$webcamBoxAdd";
  webcam.mainWidth= $webcamMainWidth;
  webcam.mainHeight= $webcamMainHeight;
  webcam.hdWidth= $webcamHdWidth;
  webcam.hdHeight= $webcamHdHeight;
  webcam.thumbWidth= $webcamThumbWidth;
  webcam.thumbHeight= $webcamThumbHeight;
  webcam.hugeWidth=  $webcamHugeWidth;
  webcam.hugeHeight= $webcamHugeHeight;
  webcam.actMinute= $webcamActMinute;
  webcam.forceSize= $webcamForceSize;
  webcam.ignoreHash= $webcamIgnoreHash;
  webcam.lang= "$lang";
  webcam.username= "$username";
  $js
  </script>
  <noscript>
  <div style="height:4px;"></div>
  <a href="$webcamWorkuri/current/full.jpg">
    <img src="$webcamWorkuri/current/$webcamMainWidth.jpg">
  </a>
  <br>
  <div class="wcmessage"><b>Hinweis:</b>
  Um das Webcam-Archiv zu nutzen, ist es notwendig <b>JavaScript</b> für
  diese Seite zu aktivieren.
  </div>
  </noscript>
  <div id="wcerrors" class="wcwidth"></div>
  <div id="wcmessages" class="wcwidth">
  $webcamMenu
  </div>
  <script>
  jQuery(document).ready(function() {
    jQuery(".wcminicont").addClass("wcminiimg");
    jQuery(".wcminicont").show();
    jQuery(".wcminicont").lazyload({skip_invisible: false,effect : "fadeIn"});
  });
  </script>
END;

if ($doFrame) {
  print "</body></html>";
}
else {
  $webcam['navFooter']();
}

?>
