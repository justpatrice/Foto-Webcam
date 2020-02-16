<?
// Webcam Configuration
//
$webcam['title']=       "Beispiel-Webcam BOS Bad Tölz";
$webcam['short']=       "Beispiel-Webcam<br>BOS Bad Tölz";
$webcam['keywords']=    "Webcam,DSLR";
$webcam['longitude']=   11.567995;
$webcam['latitude']=    47.764091;
$webcam['elevation']=   736;

// Image-upload
$webcam['topOffset']=   200;
$webcam['maxGamma']=    1.2;
$webcam['maxIso']=      1600;
$webcam['maxExposure']= 5;

// Image-display
$webcam['hugeWidth']=   4272;
$webcam['hugeHeight']=  2848;
$webcam['actMinute']=    2;

$webcam['errorMsg']= "Nur Demo, keine echte Webcam";

$webcam['boxAdd']= 
  "<a class='menu-box' target='_blank' ".
  "href='http://www.fosbos-badtoelz.de' ".
  "title='Berufliche Oberschule Bad Tölz'>".
  "FOS/BOS Bad Tölz</a>";

//----------------------------------------------------------------------------
// Receive weather data from usb4all-module
$webcam['wxFunc']= function($values, &$fields, &$raw_fields, &$camtxt) {
  $rr= array();
  if (preg_match("/(temp\d)=([\d\.\-]+)/", $values[0], $rr)) {
    $fields['temp1']= $rr[2]-0.0;  // change correction value here!
    $raw_fields['temp1']= $values[0];
  }
  if (preg_match("/(temp\d)=([\d\.\-]+)/", $values[1], $rr)) {
    $fields['temp2']= $rr[2]-0.0;  // change correction value here!
    $raw_fields['temp2']= $values[1];
  }
  if (preg_match("/(temp\d)=([\d\.\-]+)/", $values[2], $rr)) {
    $fields['temp3']= $rr[2];
    $raw_fields['temp3']= $values[2];
  }
  if (preg_match("/(volt\d)=([\d\.\-]+)/", $values[3], $rr)) {
    $fields['volt']= $rr[2]*2;
    $raw_fields['volt']= $values[3];
  }
  $fields['temp']= min($fields['temp1'], $fields['temp2']);
  $raw_fields['temp']= $fields['temp1'].";".$fields['temp2'];
  if (isset($fields['temp1']) && isset($fields['temp2'])) {
    $camtxt= sprintf("%0.1f°C", $fields['temp']);
  }
  return true;
};
?>
