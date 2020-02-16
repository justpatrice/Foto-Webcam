// --------------------------------------------------------------------------
// Foto-Webcam.eu
// Ajax-functions for webcam upload status display
//
// Flori Radlherr, http://www.radlherr.de
// This is free software, see COPYING for details.
// --------------------------------------------------------------------------

var lastStamp= 0.0;
var lastSmiley= "";
var stat= new Array;
var loadTimer= null;

// --------------------------------------------------------------------------
// The main AJAX loader functioon gets recent log information as JSON-Array
function reload(first) {
  $.getJSON(webcamInclude+"/logtail.php?wc=status&newer="+lastStamp, 
    function(data) {
    var toUpdate= new Array;
    for (var i=0; i<data.length; i++) {
      var r= new Array;
      var name= data[i][0];
      var txt= data[i][5];
      lastStamp= parseFloat(data[i][2]);
      stat[name].lastDate= data[i][1];
      stat[name].lastStamp= lastStamp;

      var r= /state=([^ ]+)/;
      var x= r.exec(txt);
      if (x) {
        stat[name].state= x[1];
        if (stat[name].state == "capture") {
          stat[name].imageSize= 0;
          stat[name].uploadRate= "";
          stat[name].processTime= "";
        }
        if (stat[name].state == "upload") {
          stat[name].uploadBegin= lastStamp;
        }
      }

      r= /imagesize=([0-9]+)/;
      x= r.exec(txt);
      if (x) {
        stat[name].imageSize= parseInt(x[1]);
        stat[name].uploadRate= Math.round((stat[name].imageSize/1000) /
                               (lastStamp-stat[name].uploadBegin))+"kB/s";
      }
      r= /ready elapsed=([0-9]+)/;
      x= r.exec(txt);
      if (x) {
        stat[name].processTime= parseFloat(parseInt(x[1]/100)/10)+"s";
        if (! first) {
          $("#img_"+name).attr("src", 
                  webcamUri+"/"+name+"/current/150.jpg?"+lastStamp);
        }
        stat[name].lastImgDate= stat[name].lastDate;
        stat[name].lastImgStamp= stat[name].lastStamp;
      }
      toUpdate[name]= true;
    }
    for (var i=0; i<names.length; i++) {
      if (toUpdate[names[i]]) {
        update(names[i]);
      }
    }
    overallStatus();
    clearTimeout(loadTimer);
    loadTimer= setTimeout(reload, 300);
  });
}

// --------------------------------------------------------------------------
// Some formatting fcacilities
function wcs(txt) {
  return "<div class='wcsbox'>"+txt+"</div>";
}
function b(txt,col) {
  var st= "";
  if (col) {
    st=" style='color: #"+col+"'";
  }
  return "<b"+st+">"+txt+"</b>";
}

// --------------------------------------------------------------------------
// Write dynamic information on the screen
function update(name) {
  var st= stat[name];
  var sz= parseFloat(parseInt(st.imageSize/10000)/100)+"MB";
  if (st.imageSize==0) {
    sz= "";
  }
  var loading= "";
  if (st.state!="ready") {
    loading= "<br><img src='"+webcamInclude+"/loading.gif'>";
  }
  var html= wcs(b(st.title)+loading);

  var stcol= "008000";
  if (st.state != "ready") {
    stcol= "c00000";
  }
  if (st.state == "upload") {
    stcol= "0000c0";
  }
  html+= wcs(b(st.state,stcol)+"<div style='height:10px'></div>"+
             "<table cellspacing=0 cellpadding=0 border=0>"+
             "<tr><td>Upload:</td><td>"+st.uploadRate+"</td></tr>"+
             "<tr><td>Img-Size:</td><td>"+sz+"</td></tr>"+
             "<tr><td>Processing: &nbsp;</td><td>"+st.processTime+"</td></tr>"+
             "</table>");

  html+= wcs("<small>Last activity:<br>"+st.lastDate+
             "<div style='height:10px'></div>"+
             "Last image:<br>"+st.lastImgDate+
             "</small>");

  $("#status_"+name).html(html);
}

// --------------------------------------------------------------------------
// Check the status of the whole system and create an appropriate smiley
function overallStatus() {
  var act= new Date().getTime();
  var tolerance= act/1000-15*60;
  var phrase= "";

  for (var i=0; i<names.length; i++) {
    var n= names[i];
    if (stat[n].lastStamp==null     || stat[n].lastImgStamp==null ||
        stat[n].lastStamp<tolerance || stat[n].lastImgStamp<tolerance) {
      phrase+= n+" ";
    }
  }
  var smileyType= "ok";
  var addTxt= "";
  if (phrase=="") {
    phrase= "Status OK";
  }
  else {
    addTxt= "<br><b>"+phrase+"</b>";
    phrase= "Problem: "+phrase;
    smileyType= "fail";
  }
  if (smileyType != lastSmiley) {
    $("#left-box").html("<center><img title='"+phrase+"' src='"+
      webcamInclude+"/status-120-"+smileyType+".png'>"+addTxt+"</center>");
  }
}

// --------------------------------------------------------------------------
// Initialise upon page load
$(document).ready(function() {
  for (var i=0; i<names.length; i++) {
    var n= names[i];
    stat[n]= new Object;
    stat[n].name= n;
    stat[n].title= titles[i];
    stat[n].lastStamp= 0.0;
    stat[n].uploadRate= 0;
    stat[n].uploadBegin= 0.0;
    stat[n].lastImgDate= "";
    stat[n].lastDate= "";
    stat[n].imageSize= 0;
    stat[n].processTime= 0;
    stat[n].state= "idle";
  }
  // If somewhat went wrong, try it again, maybe it was a network error
  jQuery(document).ajaxError(function(e, xhr, settings, exception) {
    clearTimeout(loadTimer);
    loadTimer= setTimeout(reload, 10000);
  });
  reload(true);
});

$("#left-box").html("");
