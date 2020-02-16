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
  var webcamUri= '${webcam['uri']}';
  var webcamInclude= '${webcam['includeUri']}';
  var names= new Array();
  var titles= new Array();
  </script>
  <script language='JavaScript' src='${webcam['includeUri']}/status.js'>
  </script>
  <style>
  .wcstatus {
    /*align: left;*/
    height: 84px;
    vertical-align: top;
  }
  .wcsbox {
    vertical-align: top;
    display: inline-block;
    width: 200px;
    height: 84px;
    margin: 0px;
    overflow: hidden;
  }
  hr {
    clear: both;
    margin: 0px;
  }
  </style><hr>
";


foreach ($webcam['overview'] as $cam) {
  $name= $cam[0];
  $current= $cam[1];
  $link= $cam[2];
  $title= ucfirst($name);
  if ($link !== null || $current !== null) {
    continue;
  }
  $current= $webcam['uri']."/$name/current";
  $current= preg_replace("#/$#", "", $current);
  $link= $webcam['uri']."/$name/";

  print "
   <a href='$link' title='$title'>
   <img align='right' id='img_$name' src='$current/150.jpg'></a>
   <div id='status_$name' class='wcstatus'></div>
   <script>
   names.push('$name');
   titles.push('$title');
   </script>
   <hr>
  ";
}

$webcam['navFooter']();
?>
