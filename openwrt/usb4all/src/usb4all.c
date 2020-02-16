// ----------------------------------------------------------------------
// Foto-Webcam.eu
// usb4all-webcam - command line tool for OpenWRT
//
// Flori Radlherr, http://www.radlherr.de
// This is free software, see COPYING for details.
//
// For temperature output assume LM335 sensors with 2k2 pullup to +5V
// ----------------------------------------------------------------------

#include <stdio.h>
#include <string.h>
#include <errno.h>
#include <sys/stat.h>
#include <fcntl.h>
#include <unistd.h>
#include <stdlib.h>

// ----------------------------------------------------------------------
// Configuration
unsigned vref= 4096;  // assuming external reference 4096mV
int  verbose= 0;      // debug messages on or off
int  anPorts= 5;      // scan up to this port number
int  anSamples= 3;    // average this times when reading a/d
int  wasError= 0;     // remember error situation globally

#define LOCK "/tmp/usb4all.lock"

// ----------------------------------------------------------------------
// Try to open ACM serial device
int openDev(const char *dev, int msg) {
  int hAcm= open(dev, O_RDWR | O_NONBLOCK);
  if (msg && hAcm<0) {
    printf("usb4all: cannot open acm device: %s\n", dev);
    exit(1);
  }
  return hAcm;
}

// ----------------------------------------------------------------------
// Execute one usb4all command
const char *docmd(char *cmd) {
  static char buf[100];
  struct timeval tv;
  int nread= 0;
  int count= 0;
  int hAcm= (-1); 

  // HACK: initialize interface on each command
  // Recycling the connection was not stable for multi-command sequences
  if ((hAcm= openDev("/dev/ttyACM0", 0)) < 0) {
    // another HACK: try both devices for kernel 2.4 and 2.6
    hAcm= openDev("/dev/usb/acm/0", 1);
  }

  // Swallow possible garbage on the interface
  memset(buf, 0, sizeof(buf));
  do {
    nread= read(hAcm, buf, sizeof(buf));
    tv.tv_sec= 0;
    tv.tv_usec= 1000; // 1ms
    select(0, NULL, NULL, NULL, &tv);
    count++;
    if (nread>0) {
      printf("ERROR: got garbage: %d: %s\n", strlen(buf), buf);
      wasError= 1;
    }
  } while (count<4);

  memset(buf, 0, sizeof(buf));
  int written= write(hAcm, cmd, strlen(cmd)+1);
  if (written != (strlen(cmd)+1)) {
    printf("ERROR: write %d, got %d: %s\n", strlen(cmd)+1, written,
    strerror(errno));
    wasError= 1;
  }
  do {
    nread= read(hAcm, buf, 50);
    tv.tv_sec= 0;
    tv.tv_usec= 5000; // 5ms
    select(0, NULL, NULL, NULL, &tv);
    count++;
    if (count>100) {
      printf("ERROR: nothing received %d: %s\n", nread, strerror(errno));
      wasError= 1;
    }
  } while(nread!=50 && !wasError);

  if (verbose) {
    buf[48]= 0;
    printf("%s -> %s\n", cmd, buf);
  }

  close(hAcm);
  return buf;
}

// ----------------------------------------------------------------------
// Read one analog value from usb4all
int getAnalogValue(int port) {
  char cmd[20];
  const char *result;

  sprintf(cmd, "#51-2-%d", port);
  docmd(cmd);
  result= docmd("#51-3");

  unsigned lo=0, hi=0;
  if (sscanf(result, "@51-03-%2X-%2X", &lo, &hi)) {
    return lo+256*hi;
  }
}

// ----------------------------------------------------------------------
// Read all analog values from usb4all and print to stdout
void getAnalog(void) {
  long anValues[20];    // for average calculating
  int port, sample;
  float vr= vref;
  int trial= 0;

  do {
    wasError= 0;
    for (port=0; port<anPorts; port++) {
      anValues[port]= 0;
    }
    if (vref>0) {
      docmd("#51-1-5-1");
    }
    else {
      vr= 5000.0;  // using internal reference
      docmd("#51-1-5-0");
      printf("warning: using internal reference.\n");
    }
    // Take more samples for better precision
    for (sample= 0; sample<anSamples; sample++) {
      for (port=0; port<anPorts; port++) {
        if (port!=3 || !vref) {
          anValues[port]+= getAnalogValue(port);
        }
      }
    }
    if ((trial++)>5) {
      printf("ERROR: too many errors, giving up\n");
      exit(1);
    }
  } while(wasError);

  // print raw, voltage and temp values
  for (port=0; port<anPorts; port++) {
    if (port!=3 || !vref) {
      printf("an%d=%04.1f  ", port, ((float)anValues[port])/anSamples);
      float volt= (((anValues[port]*vr)/anSamples)/4096000.0);
      printf("volt%d=%1.3f  ", port, volt);
      float temp= (((anValues[port]*vr)/anSamples)/40960.0)-273.0;
      printf("temp%d=%0.1f\n", port, temp);
    }
  }
}

// ----------------------------------------------------------------------
// Read some digital values. Assume dl5mgd's usb4all-webcam board
void getDigital(void) {
  const char *result;
  unsigned pa= 0,pb= 0,pc= 0;

  result= docmd("#50-3");
  if (sscanf(result, "@50-03-%2X-%2X-%2X", &pa, &pb, &pc)) {
    int b7= (pb & 0x80)?1:0;
    int b6= (pb & 0x40)?1:0;
    int b5= (pb & 0x20)?1:0;
    int b4= (pb & 0x10)?1:0;
    int c0= (pc & 0x01)?1:0;
    int c1= (pc & 0x02)?1:0;
    int c2= (pc & 0x04)?1:0;

    printf("b7=%d reset=%s\n", b7, b7?"active":"inactive");
    printf("b6=%d camera=%s\n", b6, b6?"off":"on");
    printf("b5=%d heater=%s\n", b5, b5?"on":"off");
    printf("b4=%d\n", b4);
    printf("c0=%d pin5 on k1\n", c0);
    printf("c1=%d pin3 on k1\n", c1);
    printf("c2=%d pin1 on k1\n", c2);
  }
}

// ----------------------------------------------------------------------
void usage(const char *a0) {
  printf("\nUsage: %s [ -v ] [ -r <vref> ] <hexcode> | <cmd>\n", a0);
  printf("  -v     - debug output on cooked commands\n");
  printf("  -r <millivolt> - set reference (0=internal default:4096)\n");
  printf("  wd     - trigger watchdog (avoid resetting host power)\n");
  printf("  reset  - immediate reboot (switch host power off/on)\n");
  printf("  a      - read A/D ports and calculate voltage and temperature\n");
  printf("  d      - read digital ports\n");
  printf("  con    - camera on  (Port RB6)\n");
  printf("  coff   - camera off (Port RB6)\n");
  printf("  hon    - heater on  (Port RB5)\n");
  printf("  hoff   - heater off (Port RB5)\n");
  printf("  c00 / c01 - switch K1 Pin5 to 0/1 (Port RC0)\n");
  printf("  c10 / c11 - switch K1 Pin3 to 0/1 (Port RC1)\n");
  printf("  c20 / c21 - switch K1 Pin1 to 0/1 (Port RC2)\n");
  printf("\n");
  exit(1);
}

// ----------------------------------------------------------------------
void releaseLock(void) {
  unlink(LOCK);
}

// ----------------------------------------------------------------------
int main(int argc, char *argv[]) {
  char *arg= NULL;

  FILE *lock= NULL;
  int lockCount= 0;
  while (lock= fopen(LOCK, "r")) {
    fclose(lock);
    sleep(1);
    lockCount++;
    if (lockCount>5) {
      printf("ERROR: lock not released within usual time period\n");
      break;
    }
  }
  lock= fopen(LOCK, "w");
  if (lock) {
    fputc('\n', lock);
    fclose(lock);
    atexit(releaseLock);
  }
  else {
    printf("ERROR: cannot obtain lock file\n");
    exit(1);
  }

  if (argc>1) {
    arg= argv[1];
    if (argc>2 && strcmp(arg, "-v")==0) {
      arg= argv[2];
      verbose= 1;
      argc--;
    }
    if (argc>3 && strcmp(arg, "-r")==0) {
      vref= atoi(argv[2]);
      arg= argv[3];
      argc-=2;
    }
  }
  if (! arg) {
    usage(argv[0]);
  }

  if (strcmp(arg, "wd") == 0) { 
    docmd("#64-0");
  }
  else if (strcmp(arg, "reset") == 0) {
    docmd("#64-1");
  }
  else if (strcmp(arg, "coff") == 0) { 
    docmd("#50-5-0-60-0"); // cam and heater to output
    docmd("#50-6-0-40-0");
  }
  else if (strcmp(arg, "con") == 0) { 
    docmd("#50-5-0-60-0"); // cam and heater to output
    docmd("#50-7-0-40-0");
  }
  else if (strcmp(arg, "hon") == 0) { 
    docmd("#50-5-0-60-0"); // cam and heater to output
    docmd("#50-6-0-20-0");
  }
  else if (strcmp(arg, "hoff") == 0) { 
    docmd("#50-5-0-60-0"); // cam and heater to output
    docmd("#50-7-0-20-0");
  }
  else if (strcmp(arg,"c00")==0) { docmd("#50-5-0-0-1");docmd("#50-7-0-0-1");}
  else if (strcmp(arg,"c01")==0) { docmd("#50-5-0-0-1");docmd("#50-6-0-0-1");}
  else if (strcmp(arg,"c10")==0) { docmd("#50-5-0-0-2");docmd("#50-7-0-0-2");}
  else if (strcmp(arg,"c11")==0) { docmd("#50-5-0-0-2");docmd("#50-6-0-0-2");}
  else if (strcmp(arg,"c20")==0) { docmd("#50-5-0-0-4");docmd("#50-7-0-0-4");}
  else if (strcmp(arg,"c21")==0) { docmd("#50-5-0-0-4");docmd("#50-6-0-0-4");}

  else if (strcmp(arg, "a")==0) { 
    getAnalog();
  }
  else if (strcmp(arg, "temp")==0) { 
    anSamples= 10;
    getAnalog();
  }
  else if (strcmp(arg, "d")==0 || strcmp(arg, "ports")==0) { 
    getDigital();
  }
  else {
    char cmd[100];
    int i;
    int ok= 1;

    cmd[0]= '#';
    strcpy(cmd+1,arg);
    // check for valid hex chars to avoid sending garbage to usb4all
    for (i= 0; i<strlen(arg); i++) {
      char c= cmd[i];
      if (! ((c>='a' && c<='f') || (c>='0' && c<='9') || c=='-')) {
        ok= 0;
      }
    }
    if (ok) {
      verbose= 1;        // in hex mode write always debug output
      docmd(cmd);
    }
    else {
      usage(argv[0]);
    }
  }
  return(0);
}


