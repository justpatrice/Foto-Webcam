#!/usr/bin/php
<?
// --------------------------------------------------------------------------
// Foto-Webcam.eu - Website main menu
// Exe wrapper for navigation script (for usage with perl etc)
//
// Flori Radlherr, http://www.radlherr.de
// This is free software, see COPYING for details.
// --------------------------------------------------------------------------
//
$title= getenv('title');
$short= getenv('short');
$keywords= getenv('keywords');
$description= getenv('description');
$varsize= false;
if (getenv('varsize')) {
  $varsize= true;
}

$_SERVER['DOCUMENT_URI']= getenv('DOCUMENT_URI');
$_SERVER['REQUEST_URI']=  getenv('REQUEST_URI');
$_SERVER['HTTP_HOST']=    getenv('HTTP_HOST');
$_SERVER['DOCUMENT_ROOT']=getenv('DOCUMENT_ROOT');
require("navigation.php");

// For generating footer, call with argument or symlink to end.cgi
if (preg_match("/end.cgi/", $argv[0])) {
  print("Content-Type: text/html\n\n");
  navEnd();
  exit();
}
if ($argc==1) {
  print("Content-Type: text/html\n\n");
  navFullHeader($title, $short, $description, $keywords, $varsize);
}
else {
  navEnd();
}
?>
