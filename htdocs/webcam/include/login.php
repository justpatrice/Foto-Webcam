<?
// --------------------------------------------------------------------------
// Foto-Webcam.eu
// Handle user login and logout
//
// Flori Radlherr, http://www.radlherr.de
// This is free software, see COPYING for details.
// --------------------------------------------------------------------------
//
require "common.php";

$workUri= $webcam['workUri'];
$inc=     $webcam['includeUri'];
$message= "";

$mysqli= openMysql();
if (! $mysqli) {
  die("cannot open database");
}

if (isset($_GET['logout'])) {
  if (isset($session['token'])) {
    $mysqli->query("update webcam_session set expires=now() where token='".
                   $session['token']."'");
  }
  setcookie("FW_SESSION", "", time()-3600, "/");
  header("Status: 302");
  header("Location: $workUri");
  exit;
}
else if (isset($_POST['username']) && isset($_POST['password'])) {
  $username= $_POST['username'];
  $password= $_POST['password'];
  $cookieExp= 0; 
  $sessionExp= "10 hour";
  if (isset($_POST['remember'])) {
    $cookieExp= time()+365*86400;
    $sessionExp= "12 month";
  }

  $res= $mysqli->query("select fullname from webcam_user".
     " where username='".$mysqli->escape_string($username)."'".
     " and pw=password('".$mysqli->escape_string($password)."')");
  if ($res && $res->num_rows>0) {
    $token= pwGenerate(20)."_".time();
    $ip= $mysqli->escape_string($_SERVER['REMOTE_ADDR']);
    $mysqli->query("insert webcam_session set username='$username', ".
      "token='$token',ip='$ip', begin=now(), ".
      "expires=date_add(now(), interval $sessionExp)");

    setcookie("FW_SESSION", $token, $cookieExp, "/");
    header("Status: 302");
    header("Location: $workUri");
    exit;
  }
  $message= "<b>Login incorrect.</b>";
}

$wc= $webcam['name'];
if ($session['valid']) {
  $username= $session['username'];
  $fullname= $session['fullname'];
  $email= $session['email'];
  print("<table cellspacing=1 cellpadding=1 border=0>
    <tr><td>Login:</td>
        <td>$username</td>
        <td> &nbsp;&nbsp;
        <a href='$inc/login.php?wc=$wc&logout=1'>Logout</a></td></tr>
    <tr><td>Full name: &nbsp;&nbsp;</td>
        <td>$fullname</td><td></td></tr>
    <tr><td>Mail:</td>
        <td>$email</td>
        <td> &nbsp;&nbsp;
        <a href='$inc/login.php?wc=$wc&changepw=1'>Change password</a>
        </td></tr>
    </table>");
}
else {
  print("<form method='POST' action='$inc/login.php'>
    <table cellspacing=1 cellpadding=1 border=0>
    <tr><td>Login:</td>
        <td><input type='text' name='username' size=20></td>
        <td colspan=2>$message</td></tr>
    <tr><td>Password:</td>
        <td><input type='password' name='password' size=20></td>
        <td></td></tr>
    <tr><td></td><td><input type='submit' value='Login'>
    &nbsp;&nbsp;
        <input type='checkbox' name='remember' value='1'>
        Remember login
        <input type='hidden' name='wc' value='$wc'>
        <td></td></td></tr>");
  print("</table></form>");
}

