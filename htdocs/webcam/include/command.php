<?
// --------------------------------------------------------------------------
// Foto-Webcam.eu
// Await command for webcam remote host
//
// Flori Radlherr, http://www.radlherr.de
// This is free software, see COPYING for details.
// --------------------------------------------------------------------------
//
require "imgutil.php"; checkUploadVars() or die;

$commandName= $webcam['workPath']."/tmp/command.txt";
doLog("waiting for command...", "idle");

// Wait about 120s for a command
for ($i=0; $i<120; $i++) {
  // Check command file created by other process
  if (@filemtime($commandName)>0) {
    $cfile= fopen($commandName, "r");
    if ($cfile) {
      $cmd= fread($cfile,10000);
      echo("command\n$cmd\n");
      fclose($cfile);
      unlink($commandName);
      $cmd= preg_replace("/\n/", " ", $cmd);
      doLog($cmd, "command-exec");
      exit;
    }
  }
  // Check if capture interval has expired, try capture
  if (isset($webcam['captureInterval']) && $webcam['captureInterval']>0) {
    $lastCapture= $webcam['workPath']."/tmp/lastCapture.txt";
    $now= time();
    // honour configured offset, shown time includes offset
    if (isset($webcam['captureOffset'])) {
      $now-= $webcam['captureOffset'];
    }
    $towait= ($now % $webcam['captureInterval']);

    // Catch some more impressions from new year firework or else
    $mom= strftime("%m%d%H%M", $now);
    if (preg_match($webcam['specialTimes'], $mom)) {
      $towait= 0;
    }

    // Conditions for capturing:
    // - time frame within 20sec after interval has come
    // - last capture trigger is longer than 60sec old
    if ($towait<55 && ($now-@filemtime($lastCapture))>120) {
      // remember this event by file timestamp
      touch($lastCapture, $now);
      // generate image stamp
      $stamp= strftime("%Y%m%d_%H%M", $now);
      // generate command for camera host
      $webcam['lastCommand']= $now;
      echo("command\n$now\nwebcam_capture $stamp\n");
      doLog("state=capture-command stamp=$stamp", "info");
      exit;
    }
  }
  usleep(500000); // wait 500ms
}
//doLog("end of waiting...", "idle");

?>
