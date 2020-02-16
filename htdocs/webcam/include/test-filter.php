<?
// --------------------------------------------------------------------------
// Foto-Webcam.eu
// Render full-sized original image with region filtering applied
// (for testing purposes only)
//
// Flori Radlherr, http://www.radlherr.de
// This is free software, see COPYING for details.
// --------------------------------------------------------------------------
//
require "imgutil.php";

$fn= $webcam['workPath']."/current/raw.jpg";

$img= @ImageCreateFromJPEG($fn);

// If necessary, copy regions to other places due to privacy
copyRegions($img);

// If necessary, scramble some regions due to privacy
scrambleRegions($img);

// If necessary, add noise filter to regions
filterRegions($img);

Header("Content-Type: image/jpeg");
ImageJPEG($img);



?>

