<?
// --------------------------------------------------------------------------
// Foto-Webcam.eu - Website main menu
// Header/Footer/Menu generation
//
// Flori Radlherr, http://www.radlherr.de
// This is free software, see COPYING for details.
// --------------------------------------------------------------------------

$navData= Array();

// --------------------------------------------------------------------------
// The menu bar
function navLeftBar() {
  global $navData;
  global $webcam;

  // Pfad vorbereiten zur leichteren Nachbehandlung
  $uri=      $navData['uri'];
  $path=      explode("/", $uri);
  $pathdepth= count($path);
  $afterMenu= null;
  $afterUri=  "";
  $menuTxt= "";

  foreach ($navData['afterMenu'] as $am) {
    if (preg_match($am[1], $uri)) {
      $afterMenu= $am[0];
      $afterUri= $uri;
    }
  }
  // HACK: Manche Sonderlocken werden hartverdrahtet abgefangen,
  // das sollte aber in anderen Umgebungen nicht stören
  if (preg_match("/webcam/", $uri) && 
      preg_match("/temperatur/i", $navData['title'])) {
    $afterMenu= "Temperaturdaten";
    $afterUri= $uri;
  }
  $rr= array();
  if (preg_match("/wiki\/(.*)/", $uri, $rr)) {
    if (! preg_match("/aktuelles/", $rr[1])) {
      $afterMenu= preg_replace("/[^0-9a-z\-\_:].*/i", "", ucfirst($rr[1]));
      if (preg_match("/foto-webcam/i", $afterMenu)) {
        $afterMenu= null;
      }
      $rr= array();
      if (preg_match("/^(.*-)([a-z]{4}.*)$/", $afterMenu, $rr)) {
        $afterMenu= $rr[1].ucfirst($rr[2]);
      }
      if (preg_match("/^(.*:)([a-z]{4}.*)$/", $afterMenu, $rr)) {
        $afterMenu= $rr[1]." ".ucfirst($rr[2]);
      }
      $afterUri= $uri;
    }
  }
  if (preg_match("/(\d\d\d\d)-(\d\d)-(\d\d)-/", $uri, $rr)) {
    $afterMenu= $rr[3].".".$rr[2].".".$rr[1];
    $afterUri= $uri;
  }

  $last= false;
  foreach ($navData['menu'] as $menu) {
    $menuNum= 0;
    foreach ($menu as $item) {
      if ($last) {
        break;
      }
      if ($menuNum==0) {
        $title= isset($item[3])?$item[3]:$item[0];
        $menuTxt.= "<a href='{$item[1]}' style='margin-top:10px;' ".
                   "class='menu-home' title='$title'>{$item[0]}</a>";
      }
      elseif ($item[0]=="-webcams-") {
        foreach ($webcam['overview'] as $cam) {
          $link= $webcam['uri']."/".$cam[0]."/";
          $title= $cam[3];
          $menuName= isset($cam[4])?$cam[4]:ucfirst($cam[0]);
          $item= Array($menuName, $link, true, $title);
          $menuTxt.= navmenuItem($item, $afterMenu, $afterUri, true);
        }
      }
      elseif ($item[0]=="-box-") {
        if (isset($navData['leftbox'])) {
          $menuTxt.= "</div><div style='margin-top:6px;' class='left-box' ".
                     "id='left-box'>\n".$navData['leftbox'];
          $last= true;
        }
      } 
      elseif ($item[0]=="-separator-") {
        $menuTxt.= "<div style='margin-top:8px;'></div>";
      } 
      else {
        $menuTxt.= navmenuItem($item, $afterMenu, $afterUri);
      }
      $menuNum++;
    }
  }

  $imprint= "";
  if (strstr($uri, $navData['imprintUri'])) {
    $imprint= "<a class='menu menu-sel menu-left' ";
  }
  else {
    $imprint= "<a class='menu menu-norm menu-left' ";
  }
  $imprint.= "href='{$navData['imprintUri']}'>".
             "<nobr>{$navData['imprintTxt']}</nobr></a>";

  $short=  $navData['short'];
  $inc=    $navData['includeUri'];
  return "
    <div style='height: 105%;width:1px;position:absolute;top:0;left:0'></div>
    <div class='left' id='left'>
     <div class='left-bottom'>
      <div style='margin-left:10px'>
      $imprint
      </div>
      <img src='$inc/shadow-bot.png'>
     </div>
     <div class='left-heading'>
      <a href='/' title='Zur Startseite'><img src='$inc/shadow-top.png'></a>
      <div class='left-box'>
        <table cellspacing=0 cellpadding=0 border=0 width='100%'>
        <tr><td class='left-box-td'>$short</td></tr></table>
      </div>
     <div class='left-menu'>
     $menuTxt
     </div>
    </div>
    </div>
  ";
}

// ----------------------------------------------------------------------------
// Einen Menüpunkt darstellen (mit Highlighting, ggf. Folgepunkt)
function navmenuItem($item, $afterMenu, $afterUri, $isWebcam= false) {
  global $navData;
  global $webcam;

  $res= "";
  $ok =true;
  $phrase= $item[0];
  $path= $item[1];
  $visible= isset($item[2])?$item[2]:true;
  $title= isset($item[3])?$item[3]:$item[0];

  if (preg_match("/wiki.$/", $path)) {
    if (preg_match("/aktuelles/", $navData['uri'])) {
      $ok= false;
    }
  }
  $grey= " title='$title'";
  # Wenn Webcam-Bild zu alt, Menüpunkt unsichtbar machen
  if ($isWebcam) {
    $img= $_SERVER['DOCUMENT_ROOT'].$path."/current/1200.jpg";
    if (file_exists($img)) {
      if (filemtime($img) < time()-$navData['webcamGreyTime']) {
        $grey= " style='color:#c0c0c0;' title='Kamera ist derzeit offline'";
      }
      if (filemtime($img) < time()-$navData['webcamHideTime']) {
        return ""; # ganz ausblenden, aus den Augen aus dem Sinn ;-)
      }
    }
  }
  if (strpos($navData['uri'], $path) === 0 && $ok) {
    $res.= "<a class='menu menu-sel menu-left' href='$path'$grey>$phrase</a>\n";
    if ($afterMenu) {
      $res.= 
        "<a class='menu-after menu-left' href='$afterUri'>$afterMenu</a>\n";
    }
  }
  elseif ($visible) {
    $res.= 
      "<a class='menu menu-norm menu-left' href='$path'$grey>$phrase</a>\n";
  }
  return $res;
}


// ----------------------------------------------------------------------------
// Der oberste HTML-Header
function navBeginHeader() {
  return "<!DOCTYPE html>\n<html><head>";
};


// ----------------------------------------------------------------------------
// Metadaten aufbereiten
function navHeaderData() {
  global $navData;
  $uri= $navData['uri'];
  $includeUri= $navData['includeUri'];
  $metadata= "";

  # Diesen Unterverzeichnissen wird in den Suchmaschinen nicht gefolgt
  $nofollow= false;
  foreach ($navData['nofollowUri'] as $reg) {
    if (preg_match($reg, $uri)) {
      $nofollow= true;
    }
  }
  if ($nofollow) {
    $metadata.= "\n    <meta name='robots' content='nofollow,noindex' />";
  }
  if (isset($navData['keywords'])) {
    $keywords= $navData['keywords'];
    $metadata.= "\n    <meta name='keywords' content='$keywords' />";
  }
  if (isset($navData['refresh'])) {
    $refresh= $navData['refresh'];
    $metadata.= "\n    <meta http-equiv='refresh' content='$refresh' />";
  }
  $metadata.= 
    "\n    <meta http-equiv='X-UA-Compatible' content='IE=edge' />";

  // Incorporate the main css and js parts from separate files
  $css= file_get_contents($navData['includePath']."/main.css");
  $css= preg_replace("/\/\*.*?\*\//s", "", $css);
  $css= preg_replace("/url\(/", "url($includeUri/", $css);
  $css= preg_replace("/\s+/s", " ", $css);

  $js=  file_get_contents($navData['includePath']."/main.js");
  $js=  preg_replace("/\/\/.*$/m", "", $js);
  $js=  preg_replace("/^\s+/m", "", $js);
  $js=  file_get_contents($navData['includePath']."/jquery.js").$js;

  $suffix=      $navData['suffix'];
  $title=       $navData['title'];
  $description= $navData['description'];
  $author=      $navData['author'];

  return "
    <meta http-equiv='content-type' content='text/html; charset=utf-8' />
    <meta http-equiv='content-language' content='de' />$metadata
    <meta name='description' content='$description' />
    <meta name='author' content='$author' />
    <title>$title - $suffix</title>
    <link rel='icon' type='image/x-icon' href='/favicon.ico' />
    <link rel='shortcut icon' href='/favicon.ico' />
    <style type='text/css'>
    $css
    </style>
    <script language='JavaScript'>
    $js
    </script> 
    <meta name='viewport' content='width=1024,user-scalable=yes'>
    <noscript><style>
    #right { display: block; }
    </style></noscript>
  ";
}


// ----------------------------------------------------------------------------
// Übergang zwischen Head und Body
function navEndHeader() {
  return "</head>\n<body>\n";
}

// ----------------------------------------------------------------------------
// Rechte Seite beginnen
function navBeginContent($noclasscontent= false) {
  $ret= "<div class='right' id='right'>\n";
  if ($noclasscontent) {
    $ret.= "<div id='content'>\n";
  }
  else {
    $ret.= "<div class='content' id='content'>\n";
  }
  return $ret;
}

// ----------------------------------------------------------------------------
// Seitenende nur rechte Seite
function navEndContent() {
  return "\n</div></div>\n";
}

// ----------------------------------------------------------------------------
// Seitenende komplett
function navEndHtml() {
  return "</body></html>\n";
}

// ----------------------------------------------------------------------------
// Basisdaten vorbereiten
function navDataPrepare($title= "", $short= "", 
                        $description= "", $keywords= "") {
  global $navData;
  global $webcam;
  $navData['includePath']= realpath(dirname(__FILE__));
  $navData['host']= $_SERVER['HTTP_HOST'];
  
  require_once $navData['includePath']."/config.php";
  require_once $navData['includePath']."/".
               $navData['webcamInclude']."/config.php";

  $uri= null;
  if (isset($_SERVER['DOCUMENT_URI'])) {
    $uri= $_SERVER['DOCUMENT_URI'];
  }
  if(! $uri) {
    $uri= $_SERVER['REQUEST_URI'];
  }
  $navData['uri']= $uri;
  if ($description == "") {
    $description= $navData['description'];
  }
  if ($title == "") {
    $title= $short;
  }
  if ($short == "") {
    $short= $title;
  }
  $navData['description']= $description;
  $navData['keywords']= $keywords;
  $navData['title']= $title;
  $navData['short']= $short;
}

// ----------------------------------------------------------------------------
// Befüllen einer zusätzlichen Navigationsbox auf der linken Seite
function navLeftBox($boxtext) {
  global $navData;
  $navData['leftbox']= $boxtext;
}

// ----------------------------------------------------------------------------
// Der Normalfall: Vollen Navigationsheader darstellen
function navFullHeader($title= "", $short= "",
         $description= "", $keywords= "",$noclasscontent=false) {

  navDataPrepare($title, $short, $description, $keywords);
  echo navBeginHeader();
  echo navHeaderData();
  echo navEndHeader();
  echo navLeftBar();
  echo navBeginContent($noclasscontent);
}

// ----------------------------------------------------------------------------
// Ende der Seitennavigation darstellen (footer)
function navEnd() {
  echo navEndContent();
  echo navEndHtml();
}

?>
