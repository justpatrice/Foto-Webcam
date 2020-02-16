<?
// --------------------------------------------------------------------------
// Foto-Webcam.eu
// Generate captcha image for marking bestof
//
// Flori Radlherr, http://www.radlherr.de
// This is free software, see COPYING for details.
// --------------------------------------------------------------------------
//
require "common.php";

// Generate random string
$s= "";
for ($i=0;$i<4;$i++) {
  $s.= chr(rand(ord('a'),ord('z')));
}

// Store for server-side comparison

$fn= $webcam['workPath']."/tmp/captcha.txt";
if (@filemtime($fn)<time()-300) {
  @unlink($fn);
}
$txt= fopen($fn, "a");
fwrite($txt, time()." $s\n");
fclose($txt);

$font= dirname(__FILE__)."/ubuntu-r.ttf";
$textSize= 14;

// Create transparent image with text
$bbox= ImageTTFBBox($textSize, 0, $font, $s);
$im= ImageCreateTrueColor($bbox[2]+4, 25);
ImageSaveAlpha($im, true);
ImageFill($im, 0, 0, ImageColorAllocateAlpha($im, 255,255,255, 127));
ImageTTFText($im,$textSize,0,2,17,ImageColorAllocate($im, 0,0,0),$font,$s);

// Deliver to browser
Header("Content-Type: image/png\n");
Header("Expires: 0\n");
ImagePNG($im);
ImageDestroy($im);
?>
