<?
// --------------------------------------------------------------------------
// Foto-Webcam.eu
// Some general utilities to prepare path and configuration
//
// Flori Radlherr, http://www.radlherr.de
// This is free software, see COPYING for details.
// --------------------------------------------------------------------------

// Get the very basics
$webcam['includePath']= realpath(dirname(__FILE__));
$currentUri=  @$_SERVER{'REQUEST_URI'};
$currentUri=  preg_replace("#/$#", "", $currentUri);
$currentRoot= realpath($_SERVER{'DOCUMENT_ROOT'});
$currentHost= @$_SERVER{'HTTP_HOST'};

require $webcam['includePath']."/config.php";

if (isset($_GET['wc'])) {
  $webcam['name']= $_GET['wc'];
}
if (isset($_POST['wc'])) {
  $webcam['name']= $_POST['wc'];
}

if (! isset($webcam['name'])) {
  // Name was not given by parmeter, maybe its in the uri due to rewriting
  $wcuri= $webcam['uri'];
  $wc= preg_replace("#^$wcuri#", "", $currentUri);
  $wc= preg_replace("#^/#", "", $wc);
  $wc= preg_replace("#/.*$#", "", $wc);
  $webcam['name']= $wc;
}

// Beware of maybe malicous parts in the name
$webcam['name']= preg_replace("/[^a-zA-Z0-9\-\_]/", "", $webcam['name']);

// Accept CGI-Parameter img and remove potentially unusable parts
if (isset($_GET['img'])) {
  $webcam['parImg']= $_GET['img'];
  if (! preg_match("/^20\d\d/", $webcam['parImg'])) {
    $webcam['parImg']= "";
  }
  $webcam['parImg']= preg_replace("/_[a-z].*$/", "", $webcam['parImg']);
  $webcam['parImg']= preg_replace("/[^\d\/]/", "", $webcam['parImg']);
}

$webcam['workUri']= $webcam['uri']."/".$webcam['name'];
$webcam['workPath']= $currentRoot.$webcam['workUri'];
$webcam['includeUri']= str_replace($currentRoot, "", $webcam['includePath']);
$webcam['boxAdd']= "";

// load cam specific configuration file
if (is_file($webcam['workPath']."/config.php")) {
  require_once $webcam['workPath']."/config.php";
}

// at minimum the title MUST be set, otherwise we seem not to live
if (! isset($webcam['title'])) {
  die("Cannot load config for {$webcam['name']}\n");
}

if (! isset($webcam['useDatabase'])) {
  $webcam['useDatabase']= false;
}

// Maybe the image dimensions are only defined as width
if (! isset($webcam['mainHeight'])) {
  $webcam['mainHeight']= floor($webcam['mainWidth']/$webcam['aspectRatio']);
}
if (! isset($webcam['hdHeight'])) {
  $webcam['hdHeight']= floor($webcam['hdWidth']/$webcam['aspectRatio']);
}
if (! isset($webcam['thumbHeight'])) {
  $webcam['thumbHeight']= floor($webcam['thumbWidth']/$webcam['aspectRatio']);
}
if (! isset($webcam['hugeHeight'])) {
  $webcam['hugeHeight']= floor($webcam['hugeWidth']/$webcam['aspectRatio']);
}

// Determine browser language
$lang= "en";
if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
  // prefer language if any acceptance for it was found
  if (strstr($_SERVER['HTTP_ACCEPT_LANGUAGE'], "de")) {
    $lang= "de";
  }
  else if (strstr($_SERVER['HTTP_ACCEPT_LANGUAGE'], "it")) {
    $lang= "it";
  }
}
$webcam['lang']= $lang;

// --------------------------------------------------------------------------
// Check if somebody is logged in as user or admin
// --------------------------------------------------------------------------
$session= Array();
$session['valid']= false;
$mysqli= openMysql();

if ($mysqli) {
  if (isset($_COOKIE['FW_SESSION'])) {
    $token= $mysqli->escape_string($_COOKIE['FW_SESSION']);
    // Find session cookie in database
    $res= $mysqli->query("select ".
          "webcam_session.username,fullname,email,perm,token ".
          "from webcam_session left join webcam_user ".
          "on webcam_session.username=webcam_user.username ".
          "where expires>now() and token='$token'");
    if ($res && $res->num_rows>0) {
      $session= $res->fetch_assoc();
      $ip= $mysqli->escape_string($_SERVER['REMOTE_ADDR']);

      // Update last access time stamp
      $mysqli->query("update webcam_session set last_act=now(),last_ip='$ip'".
                     " where token='$token'");
      $session['valid']= true;
    }
  }
}

// --------------------------------------------------------------------------
// Check if valid MySQL connection is configured and open it
// --------------------------------------------------------------------------
function openMysql() {
  global $webcam;
  if (isset($webcam['mysqli'])) {
    return $webcam['mysqli'];
  }
  if (isset($webcam['mysqlLogin'])) {
    $mysqli= new mysqli($webcam['mysqlHost'], $webcam['mysqlLogin'], 
                        $webcam['mysqlPassword'], $webcam['mysqlDatabase']);
    if ($mysqli->connect_errno) {
      return null;
    }
    $webcam['mysqli']= $mysqli;
    return $mysqli;
  }
  return null;
}

// --------------------------------------------------------------------------
// Generate a random password
// --------------------------------------------------------------------------
function pwGenerate($length) {
  $res= "";
  while (strlen($res)<$length) {
    $ch= chr(rand(ord('a'), ord('z')));
    if (rand(1,5)==1) {
      $ch= chr(rand(ord('0'), ord('9')));
    }
    if (rand(1,5)==1) {
      $ch= chr(rand(ord('A'), ord('Z')));
    }
    $res.= $ch;
  }
  return $res;
}

?>
