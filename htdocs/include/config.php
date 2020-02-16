<?
// --------------------------------------------------------------------------
// Foto-Webcam.eu - Website main menu
// Global configuration
//
// Flori Radlherr, http://www.radlherr.de
// This is free software, see COPYING for details.
// --------------------------------------------------------------------------

// Basics
$navData['includeUri']=    "/include";
$navData['webcamPath']=    "../webcam";
$navData['webcamInclude']= "../webcam/include";

// Site names and default description
$navData['suffix']= "Foto-Webcam.eu";
$navData['description']= "Fotokamera als Webcam: ".
         "Die Webcam-Seite mit der wirklich brauchbaren Bildqualität";
$navData['author']= "Flori Radlherr, www.radlherr.de, www.foto-webcam.eu";

// The main menu tree
$navData['menu']= Array();
$navData['menu'][]= Array(
  Array("Webcams", "/", true, "Webcam-Übersicht"),
  Array("-webcams-"), // generate from webcam-overview config
);
$navData['menu'][]= Array(
  Array("Infos zur Technik", "http://www.foto-webcam.eu/wiki/"),
  Array("-box-"),    // stop for left-box here if applicable
  Array("Technik-Wiki", "http://www.foto-webcam.eu/wiki/"),

  Array("Webcam-Status", "/webcam/status/", false),
);
$navData['menu'][]= Array(
  Array("Partnerseiten", "/", true, "Weitere Betreiber von Foto-Webcams"),
  Array("Foto-Webcam.eu", "http://www.foto-webcam.eu/"),
  Array("Foto-Webcam.com", "http://www.foto-webcam.com/"),
  Array("Addicted-Sports.com", 
        "http://www.addicted-sports.com/windsurfen/webcam/"),
  Array("ASAM-live.de", "http://www.asam-live.de/"),
  Array("Panorama-Blick.at", "http://www.panorama-blick.at/"),
);

// Submenu-definitions (second para is regexp)
$navData['afterMenu']= Array();
$navData['afterMenu'][]= Array("Infos zur Webcam", "/webcam.*\/infos\//");
$navData['afterMenu'][]= Array("Wetterdaten", "/webcam.*\/wetter\//");

// Imprint, left bottom link
$navData['imprintUri']= "/impressum/";
$navData['imprintTxt']= "Impressum - Kontakt";

// Timeouts to let webcams become grey or disapper
$navData['webcamGreyTime']= 1800;
$navData['webcamHideTime']= 86400*4;

?>
