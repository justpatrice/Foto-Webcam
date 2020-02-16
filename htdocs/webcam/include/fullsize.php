<?
// --------------------------------------------------------------------------
// Foto-Webcam.eu
// Render full-sized image in a maximum size window with scrolling
//
// Flori Radlherr, http://www.radlherr.de
// This is free software, see COPYING for details.
// --------------------------------------------------------------------------
//
require "common.php";

$img= "/".$webcam['parImg']."_hu.jpg";
$imgUri=  $webcam['workUri'].$img;
$imgPath= $webcam['workPath'].$img;
$inc=     $webcam['includeUri'];

// ----------------------------------------------------------------------------
// Read actual image size to have the correct rectangle
$imageWidth= 0;
$imageHeight= 0;
$size= @getimagesize($imgPath);
if (isset($size)) {
  $imageWidth= $size[0];
  $imageHeight= $size[1];
}
else {
  exit();
}

// ----------------------------------------------------------------------------
// Avoid deep links into fullscreen image
$regularUrl= "http://$currentHost".$webcam['workUri']."/#/".$webcam['parImg'];
$frameOk= "xxx";
if (isset($webcam["frameOk"])) {
  $frameOk= $webcam["frameOk"];
}
if (isset($_SERVER['HTTP_REFERER'])) {
  $ref= $_SERVER['HTTP_REFERER'];
  if (strlen($ref)>5) {
    if (!strstr($ref,$currentHost) && !preg_match("/$frameOk/", $ref))  {
      Header("302 Moved");
      Header("Location: $regularUrl");
      exit();
    }
  }
}

// ----------------------------------------------------------------------------
// Localisation
$msgFit=   "fitscreen.png";
$msgFitT=  "Fit image to screen size (Key F)";
$msgTitle= "View webcam image with full resolution";
$msgMove=  "Move mouse to scroll image<br>".
           "Click or Esc to close window";
$msgZoom=  "Mouse-wheel or keys +/- zoom the image - Key 0: original size";

if ($lang == "de") {
  $msgFit=   "anpassen.png";
  $msgFitT=  "Zeige gesamtes Bild im Fenster (Taste F)";
  $msgTitle= "Webcam-Bild in voller Auflösung";
  $msgMove=  "Maus bewegen, um Bild zu scrollen<br>".
             "Klick bzw. Esc schlie&szlig;t das Fenster";
  $msgZoom=  "Mausrad oder Taste +/- zoomt das Bild - Taste 0: Originalgröße";
}

if ($lang == "it") {
  $msgFit=   "adattaschermo.png";
  $msgFitT=  "Adatta immagine allo schermo (tasto F)";
  $msgTitle= "Mostra immagine ad alta risoluzione";
  $msgMove=  "Sposta immagine con il mouse<br>".
             "Cliccare o premere Esc per chiudere la finestra";
  $msgZoom=  "Ingrandisci immagine con i tasti +/- o la ruotella del mouse, tasto 0 per dimensione originale";
}

// ----------------------------------------------------------------------------
print <<<END
<!DOCTYPE html>
<html>
<head><title>$msgTitle</title>
<style>
body { 
  margin: 0;
  font-family: arial,helvetica,sans-serif;
}
</style>
END;

// ----------------------------------------------------------------------------
$ua= $_SERVER['HTTP_USER_AGENT'];
if (preg_match("/(symbian|mobile|fennec|android|iphone|ipad|touch)/i", $ua)) {
  print <<<END
    <meta name="viewport" content="initial-scale=0.5,user-scalable=yes">
    </head>
    <body>
      <img src="$imgUri">
    </body>
    </html>
END;
  exit;
}

// ----------------------------------------------------------------------------
print <<<ENDX
<style>
#img {
  width: ${imageWidth}px; height: ${imageHeight}px;
}
#div {
  position: fixed;
  z-index: 0;
  top: 0;
  bottom: 0;
  left: 0;
  right: 0;
  overflow: hidden;
}
#help {
  position: fixed;
  z-index: 2;
  opacity: 0.6;
  -ms-filter:"progid:DXImageTransform.Microsoft.Alpha(Opacity=60)";
  top: 0;
  right: 0;
  background-color: white;
  color: black;
  font-size: 13px;
  text-align: right;
  padding: 2px;
}
#zoom {
  position: fixed;
  z-index: 2;
  opacity: 0.6;
  -ms-filter:"progid:DXImageTransform.Microsoft.Alpha(Opacity=60)";
  top: 0;
  left: 0;
  background-color: white;
  color: black;
  font-size: 13px;
  text-align: right;
  padding: 2px;
}
#showframe {
  position: fixed;
  z-index: 2;
  bottom: 0;
  right: 0;
  opacity: 0.3;
  -ms-filter:"progid:DXImageTransform.Microsoft.Alpha(Opacity=30)";
  background-color: white;
}
#showpos {
  position: fixed;
  z-index: 3;
  opacity: 0.5;
  -ms-filter:"progid:DXImageTransform.Microsoft.Alpha(Opacity=50)";
  background-color: white;
}
#fullpic {
  display: block;
  position: fixed;
  z-index: 3;
  bottom: 0;
  left: 0;
  opacity: 0.2;
  -ms-filter:"progid:DXImageTransform.Microsoft.Alpha(Opacity=20)";
  cursor: pointer;
}
body {
  background-color: black;
}
</style>
<script language="JavaScript" src="$inc/jquery.js"></script>
</head>
<body>
<div id='help'>
$msgMove
</div>
<div id='zoom' title='$msgZoom'></div>
<div id="div">
<img id="img" src="$imgUri">
</div>
<div id="showframe"></div>
<div id="showpos"></div>
<div id="fullpic" onclick="showFullPic()">
<img src="$inc/$msgFit" 
width=80 height=45 title="$msgFitT - $msgZoom"></div>
<script language="JavaScript">
if (! opener) {
  location.href= "$regularUrl";
}
var imageWidth= $imageWidth;
var imageHeight= $imageHeight;
var zoom= 1.0;
var oldZoom= zoom;
var zoomTimer= null;
var zoomFit= false;
var pos= 50;
var dw= $(window).width();
var dh= $(window).height();
var lastMouseX= dw/2;
var lastMouseY= dh/2;
var isRunning= false;

function setPos(z) {
  if (isRunning) {
    return;
  }
  var mw= lastMouseX;
  var mh= lastMouseY;
  if (mw>(dw-dw/10)) { mw= dw-dw/10; } mw-=dw/10; if (mw<0) { mw=0; }
  if (mh>(dh-dh/10)) { mh= dh-dh/10; } mh-=dh/10; if (mh<0) { mh=0; }
  var offw= ((imageWidth*zoom-dw)*mw)/(dw-dw/5);
  var offh= ((imageHeight*zoom-dh)*mh)/(dh-dh/5);

  if ((imageWidth*zoom)<dw) {
    offw= -(dw-(imageWidth*zoom))/2;
    $("#showpos").css("left", (dw-imageWidth/pos)+"px");
    $("#showpos").css("width", (imageWidth/pos)+"px");
  }
  else {
    $("#showpos").css("left", ((dw-imageWidth/pos)+(offw/zoom)/pos+1)+"px");
    $("#showpos").css("width", ((dw/zoom)/pos)+"px");
  }
  if ((imageHeight*zoom)<dh) {
    offh= -(dh-(imageHeight*zoom))/2;
    $("#showpos").css("top", (dh-imageHeight/pos)+"px");
    $("#showpos").css("height", (imageHeight/pos)+"px");
  }
  else {
    $("#showpos").css("top", ((dh-imageHeight/pos)+(offh/zoom)/pos+1)+"px");
    $("#showpos").css("height", ((dh/zoom)/pos)+"px");
  }
  if (z && zoom != oldZoom) {
    $("#img").animate({ 
      "width": (imageWidth*zoom), 
      "height": (imageHeight*zoom),
      "margin-left": (-offw),
      "margin-top": (-offh) 
      }, {
      duration: 100,
      queue: false,
      progress: function() {
        isRunning= true;
      },
      always: function() { 
        oldZoom= zoom; 
        isRunning= false; 
        setPos(false);
      } 
    });
  }
  else {
    $("#img").css("width", (imageWidth*zoom));
    $("#img").css("height", (imageHeight*zoom));
    $("#img").css("margin-left", (-offw)+"px");
    $("#img").css("margin-top", (-offh)+"px");
  }
  $("#zoom").text(Math.round(zoom*100)+"%");
}
function initScrollable() {
  $("#showframe").show();
  $("#showpos").show();
  $("#help").show();
  $("#zoom").show();
  $("#fullpic").show();
  $("#showframe").css("width", (imageWidth/pos)+"px");
  $("#showframe").css("height", (imageHeight/pos)+"px");
  doZoom(zoom);
}
function showFullPic() {
  if (zoomFit) {
    doZoom(1);
  }
  else {
    doZoom(0);
  }
}
function doZoom(n) {
  zoom= n;
  if (zoom>2) {
    zoom=2;
  }
  if (zoom>0.9 && zoom<1.1) {
    zoom= 1;
  }
  zoomFit= false;
  if (imageHeight/dh > imageWidth/dw) {
    if (zoom < dh/imageHeight) {
      zoom= dh/imageHeight;
      zoomFit= true;
    }
  }
  else {
    if (zoom < dw/imageWidth) {
      zoom= dw/imageWidth;
      zoomFit= true;
    }
  }
  setPos(true);
}

function wheel(event) {
  if (!event) { event= window.event; }
  if (event.wheelDelta) {
    delta= event.wheelDelta;
    if (window.opera) delta= -delta;
  } else if (event.detail) { delta= -event.detail; }
  doZoom((delta>0)?(zoom*1.12):(zoom/1.12));
  if (event.preventDefault) { event.preventDefault(); }
  event.returnValue= false;
  return false;
}
function setSizes() {
  dw= $(window).width();
  dh= $(window).height();
  initScrollable();
}
$(document).ready(function() {
  var offw= -($("#img").width()-$(window).width())/2;
  var offh= -($("#img").height()-$(window).height())/2;
  $("#img").css("margin-left", offw+"px");
  $("#img").css("margin-top", offh+"px");

  setTimeout(function() {
    $("#div").mousemove(function(event) {
      lastMouseX= event.pageX;
      lastMouseY= event.pageY;
      setPos(false);
    });
    $("#div").click(function() { window.close(); });
    $(document).keydown(function(event) {
      if (event.shiftKey || event.altKey || event.ctrlKey || event.metaKey) {
        return true;
      }
      //alert(event.keyCode);
      switch (event.keyCode) {
        case 27:  // esc 
        case 81:  // q
                  window.close();
                  return false;
        case 187: // +
        case 171: // +
        case 107: // +
                  doZoom(zoom*1.2);
                  return false;
        case 189: // -
        case 173: // -
        case 109: // -
                  doZoom(zoom/1.2);
                  return false;
        case 48:  // 0
        case 49:  // 1
                  doZoom(1);
                  return false;
        case 50:  // 2
                  doZoom(0.5);
                  return false;
        case 51:  // 3
                  doZoom(0.333);
                  return false;
        case 52:  // 4
                  doZoom(0.25);
                  return false;
        case 38:  // up
                  lastMouseY-=30;
                  setPos(false);
                  return false;
        case 40:  // down
                  lastMouseY+=30;
                  setPos(false);
                  return false;
        case 37:  // left
                  lastMouseX-=30;
                  setPos(false);
                  return false;
        case 39:  // right
                  lastMouseX+=30;
                  setPos(false);
                  return false;
        case 70:  // f
                  showFullPic();
                  return false;
        case 73:  // i
        case 82:  // r
                  location.href= "$imgUri";
                  return false;
      }
      return true;
    });
    if (window.addEventListener) {
      window.addEventListener('DOMMouseScroll', wheel, false);
    }
    window.onmousewheel= wheel;

    $(window).resize(function() {
      setSizes();
    });
    setSizes();
  }, 500);
});
</script>
</body>
ENDX;
