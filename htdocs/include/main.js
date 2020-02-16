// Floris JS-Fragmente

var windowWidth= 0;
var windowHeight= 0;

if(top.frames.length > 0) {
  top.location.href= self.location;
}

var lastWebcam= false;
var lastRight= 0;


function setSizes() {
  var $= jQuery;
  windowWidth= $(window).width();
  windowHeight= $("#left").height();
  if ($("#left-box").height()>0) {
    windowHeight-=60;
  }
  if (windowHeight<680) {
    $(".left").css("position","absolute");
    $(".left").css("height","700");
    $(".left").css("top","0px");
  }
  else {
    $(".left").css("position","fixed");
    $(".left").css("top","0px");
    $(".left").css("bottom","0px");
  }
  if (typeof(setLocalSize)!="undefined") {
    setLocalSize(windowWidth, windowHeight);
  }
  else {
    var right= (windowWidth-800)/3+108;
    if (typeof(webcam)!="undefined" && webcam.isHdSize) {
      right-= 120;
    }
    if (location.pathname=="/" && windowWidth>1400) {
      right-= 132;
    }

    if (right>220) { right= 220; }
    if (right<172) { right= 172; }

    if (lastRight!=right) {
      $("#right").css("left",right+"px");
      $("#right").css("display","block");
      lastRight= right;
    }
  }
}

jQuery(document).ready(function() {
  jQuery(window).resize(function() {
    setSizes();
  });
  setTimeout("setSizes()", 100);
  if (opener) {
    if (jQuery(window).width()<950) {
      if (window.scrollbars.visible == true) {
        window.resizeTo(1080,1200);
      }
      else {
        opener.location.href= location.href;
        //window.open(location.href,"fotowebcam","width=1080,height=1200,"+
        //  "scrollbars=yes,menubar=yes,status=yes,location=yes,"+
        //  "toolbar=yes,resizable=yes");
        window.close();
      }
    }
  }
});
