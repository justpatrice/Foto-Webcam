// --------------------------------------------------------------------------
// Foto-Webcam.eu
// Ajax-functions for webcam archive navigation
//
// Flori Radlherr, http://www.radlherr.de
// This is free software, see COPYING for details.
// --------------------------------------------------------------------------

// --------------------------------------------------------------------------
// User defaults (shall be valid user-selectable values, see renderSettings)
webcam.def= new Object;
webcam.def.fadeDelay= 500;
webcam.def.videoFrames= 60;
webcam.def.videoDelay= 800;
webcam.def.videoOrder= 0;
webcam.def.slideDelay= 3;
webcam.def.imgSize= 0;

// --------------------------------------------------------------------------
// General settings (change only if you are quite sure what you are doing)
webcam.layers= 3;
webcam.thumbnails= 69;
webcam.thumbnailsHd= 109;
webcam.thumbnailsDaily= 209;
webcam.keyDelay= 350;
webcam.debug= true;

// --------------------------------------------------------------------------
// Class specific declarations
webcam.navdivs= new Array;
webcam.data= new Object;
webcam.layer= 0;
webcam.layerId= new Array;
webcam.messages= "";
webcam.vidarr= new Array;
webcam.vidptr= 0;
webcam.samehour= 0;
webcam.mode= "idle";  // idle - video - thumb
webcam.hashsense= true;
webcam.imageReady= false;
webcam.videoTimer= null;
webcam.options= new Array("webcams","bestof","infos","exif","help","settings");
webcam.actopt= "webcams";
webcam.thumbOffset= 0;
webcam.thumbTarget= 0;
webcam.thumbTimer= null;
webcam.isHdSize= false;
webcam.actWidth= webcam.mainWidth;
webcam.actHeight= webcam.mainHeight;
webcam.thumbNum= Math.floor(webcam.actWidth/webcam.thumbWidth);
webcam.requestNum= 0;
webcam.bestofIndex= 0;
webcam.slideTimer= null;

msg= new Object;
msg.act=     "Current";
msg.act_t=   "The most recent image";
msg.thumb_d= "Thumbnails of the last days at the current time";
msg.thumb_h= "Thumbnails of the last several hours";
msg.video_d= "Time-lapse of the last days at the current time";
msg.video_h= "Time-lapse of the last several hours";
msg.time=    "Time";
msg.day=     "Day";
msg.mon=     "Month";
msg.year=    "Year";
msg.wdnames= new Array("Sunday", "Monday", "Tuesday", "Wednesday",
                       "Thursday", "Friday", "Saturday");
msg.maxim=   "Click to enlarge image";
msg.ismax=   "No larger image available";
msg.images=  "Images";
msg.image=   "Image";
msg.of=      "of";
msg.slide=   "Start slide show";
msg.slidest= "Stop slide show";
msg.imgupto= "Images up to";
msg.daily=   "Daily at"

if (webcam.lang=='de') {
  msg.act=     "Aktuell";
  msg.act_t=   "Das neueste Bild";
  msg.thumb_d= "Miniaturansichten (Thumbnails) der letzten Tage "+
               "um jeweils die gleiche Uhrzeit";
  msg.thumb_h= "Miniaturansichten (Thumbnails) der letzten Stunden anzeigen";
  msg.video_d= "Zeitraffer der letzten Tage um jeweils die gleiche Stunde";
  msg.video_h= "Zeitraffer mit den Bildern der letzten Stunden";
  msg.time=    "Zeit";
  msg.day=     "Tag";
  msg.mon=     "Monat";
  msg.year=    "Jahr";
  msg.wdnames= new Array("Sonntag", "Montag", "Dienstag", "Mittwoch",
                         "Donnerstag", "Freitag", "Samstag");
  msg.maxim=   "Klick zum Anzeigen des Bildes in voller Größe";
  msg.ismax=   "Bild hat bereits maximale Größe";
  msg.images=  "Bilder";
  msg.image=   "Bild";
  msg.of=      "von";
  msg.slide=   "Diashow starten";
  msg.slidest= "Diashow beenden";
  msg.imgupto= "Bilder bis";
  msg.daily=   "Täglich um jeweils"
}

if (webcam.lang=='it') {
  msg.act=     "attuale";
  msg.act_t=   "Immagine più recente";
  msg.thumb_d= "Anteprima (Thumbnails) dei ultimi giorni "+
               "sempre alla stessa ora";
  msg.thumb_h= "Anteprima (Thumbnails) delle ultime ore";
  msg.video_d= "Riprese all´acceleratore dei ultimi giorni sempre alla stessa ora";
  msg.video_h= "Riprese all´acceleratore delle ultime ore";
  msg.time=    "Ora";
  msg.day=     "Giorno";
  msg.mon=     "Mese";
  msg.year=    "Anno";
  msg.wdnames= new Array("Domenica", "Lunedì", "Martedì", "Mercoledì",
                         "Giovedì", "Venerdì", "Sabato");
  msg.maxim=   "Cliccare per visualizzare immagine nella dimensione originale";
  msg.ismax=   "L´immagine è già dimensionato al massimo";
  msg.images=  "immagini";
  msg.image=   "immagine";
  msg.of=      "da";
  msg.slide=   "Avvia visualizzazione automatica";
  msg.slidest= "Ferma visualizzazione automatica";
  msg.imgupto= "Immagini fino a";
  msg.daily=   "Giornaliero alle"
}


// --------------------------------------------------------------------------
// Initiate rendering of a new image. 
// First, load calendar structure as JSON get, then render new image
webcam.go= function(when) {
  $("#wcloadimg").css("display", "block");
  $("#wcbestof").hide();
  if (when===undefined || when==null) {
    when= "";
  }
  // stop possibly running video
  webcam.vidarr= new Array;
  webcam.vidptr= 0;
  webcam.requestNum++;
  if (webcam.videoTimer) {
    clearTimeout(webcam.videoTimer);
    webcam.videoTimer= null;
  }
  // hash-part of URL might contain sub-functions, analyze and execute
  webcam.samehour= when.match(/samehour/)?1:0;
  when= when.replace(/.samehour/, "");
  var thumbNumber= 0;
  if (when.match(/thumb/)) {
    when= when.replace(/.thumb/, "");
    webcam.mode= "thumb";
    thumbNumber= webcam.isHdSize?webcam.thumbnailsHd:webcam.thumbnails;
    if (webcam.samehour) {
      thumbNumber= webcam.thumbnailsDaily;
    }
    for (i=0; i<webcam.layers; i++) { 
      $(webcam.layerId[i]).hide();
    }
    $("#wcmessages").hide();
    $("#progress").hide();
  }
  else if (when.match(/video/)) {
    when= when.replace(/.video/, "");
    webcam.mode= "video";
    thumbNumber= webcam.videoFrames;
    $("#thumbnails").hide();
    $("#wcmessages").show();
    $("#progress").show();
  }
  else {
    webcam.mode= "idle";
    $("#thumbnails").hide();
    $("#wcmessages").show();
    $("#progress").hide();
    if (webcam.thumbTimer) {
      clearInterval(webcam.thumbTimer);
      webcam.thumbTimer= null;
    }
  }
  $("#wcovthumb").stop(true,true).fadeOut(100);
  // options might be also mentioned within hash-part
  // if not, stay in current option state
  for (var i=0; i<webcam.options.length; i++) {
    var opt= webcam.options[i];
    if (when.match(opt)) {
      webcam.actopt= opt;
      when= when.replace("/"+opt, "");
    }
  }
  for (var i=0; i<webcam.options.length; i++) {
    var opt= webcam.options[i];
    if (opt == webcam.actopt) {
      $("#wcopt-"+opt).addClass("wcoptact");
      $("#wcinfo-"+opt).fadeIn(500);
      var url= webcam.url;
      url= url.replace(/http....[^\/]*/, "");
      // fill static text options with content by AJAX load
      if (opt == "infos") {
        if (! webcam.infoPresent) {
          $("#wcinfo-"+opt).load(url+"?infos=1");
          webcam.infoPresent= true;
        }
      }
      if (opt == "help") {
        if (! webcam.helpPresent) {
          $("#wcinfo-"+opt).load(url+"?help=1");
          webcam.helpPresent= true;
        }
      }
    }
    else {
      // inactive options
      $("#wcopt-"+opt).removeClass("wcoptact");
      $("#wcinfo-"+opt).hide();
    }
  }
  // special case: settings icon shall render reverse if active
  if (webcam.actopt == "settings") {
    $("#wcopt-settings-img").attr("src", webcam.inc+"settings-rev.png");
  } else {
    $("#wcopt-settings-img").attr("src", webcam.inc+"settings.png");
  }
  when= when.replace(/^[#\/]*/, "");
  var requestNum= webcam.requestNum;

  // fetch image archive meta data from server by AJAX request
  $.getJSON(webcam.inc+"list.php"+
    "?img="+when+"&wc="+webcam.name+"&thumbs="+thumbNumber+
    "&exif="+((webcam.actopt=="exif")?1:0)+
    "&bestof="+((webcam.actopt=="bestof")?1:0)+
    "&samehour="+webcam.samehour+"&ww="+$(window).width()+"&callback=?",
    function(data) {

    // avoid overlapping calls caused by further inputs
    if (webcam.requestNum != requestNum) {
      return;
    }

    // set hash part of URL to enable browser history and bookmarking
    webcam.hashsense= false;
    var newhash= "";
    if (! data.newest) {
      newhash+= "/"+data.image.replace(/_.*/,"");
    }
    if (webcam.mode == "thumb") {
      newhash+= "/thumb";
    }
    if (webcam.mode == "video") {
      newhash+= "/video";
    }
    if (webcam.samehour>0) {
      newhash+= "/samehour";
    }
    if (webcam.actopt != "webcams") {
      newhash+= "/"+webcam.actopt;
    }
    if (newhash=="") {
      if (webcam.locationModified) {
        webcam.locationModified= false;
        location.hash= "";
      }
    }
    else {
      webcam.locationModified= true;
      location.hash= "#"+newhash;
    }
    // hash changes are ignored in transition phases to avoid confusion
    setTimeout(function() { webcam.hashsense= true; }, 200);

    // grey calendar enties without data available
    for (var i=0; i<webcam.navdivs.length; i++) {
      var id= webcam.navdivs[i];
      var div= $("#"+id);
      if (data.ids[id]===undefined) {
        div.addClass("greyed");
      }
      else {
        div.removeClass("greyed");
      }
    }

    if (!data.isbestof || webcam.actopt!="bestof") {
      webcam.stopSlideshow();
    }
    webcam.data= data;

    // render appropriate data according mode
    if (webcam.mode == "thumb") {
      webcam.markActive(data.image);
      webcam.renderThumbnails();
    }
    else if (webcam.mode == "video") {
      webcam.vidarr= data.thumbs;
      if (webcam.videoOrder==0) {
        webcam.vidptr= webcam.vidarr.length-2;
      }
      webcam.nextVideoFrame();
    }
    else {
      $("#thumbnails").hide();
      webcam.markActive(data.image);
      webcam.renderImage(data.image);
      if (webcam.actopt=="exif" && webcam.data.exif) {
        $("#wcinfo-exif").html(webcam.data.exif);
      }
      else {
        $("#wcinfo-exif").html("<img src='"+webcam.inc+"loading.gif'>");
      }
      if (webcam.actopt=="bestof") {
        webcam.renderBestof();
      }
      if (webcam.actopt=="settings") {
        webcam.renderSettings();
      }
      if (webcam.data.errorMsg) {
        $("#wcerrors").html("<div class='wcmessage' style='width: "+
          webcam.actWidth+"px;background-color: #c04040;color:#ffffff'>"+
          webcam.data.errorMsg+"</div>");
      }
      else {
        $("#wcerrors").html("");
      }
    }
  });
};

// ****************************************************************************
// Generate thumbnail table as DHTML
webcam.renderThumbnails= function() {
  var backPic= "<td align='left'>"+
      "<a title='5 Zeilen früher (Cursortaste links)' class='navpf' "+
      "href='javascript:webcam.goHistory(-35,10)'>"+
      "<img src='"+webcam.inc+"pf_li_20.png'></a></td>";
  var fwdPic= "<td align='right'>"+
      "<a title='5 Zeilen später (Cursortaste rechts)' class='navpf' "+
      "href='javascript:webcam.goHistory(35,10)'>"+
      "<img src='"+webcam.inc+"pf_re_20.png'></a></td>";
  if (webcam.data.histptr >= (webcam.data.history.length-1)) {
    fwdPic= "<td align='right'><img src='"+webcam.inc+"pf_re_20_gr.png'></td>";
  }

  $("#wcloadimg").css("display", "none");
  var dat= webcam.data.image.replace(/_.*/, "").split("/");
  var tm= dat[3].replace(/..$/,"")+":"+dat[3].replace(/^../,"");
  var thumbs= "<table cellspacing=0 cellpadding=0 class='wcwidth'><tr>"+
              backPic+
              "<td align='center' valign='center' width='90%'><b>";
  if (webcam.samehour>0) {
    thumbs+= msg.daily+" "+tm;
  }
  else {
    thumbs+= msg.imgupto+" "+dat[2]+"."+dat[1]+"."+dat[0]+" "+tm;
  }
  thumbs+= "</b></td>"+fwdPic+"</tr></table>";
  var count= 0;
  thumbs+= "<div style='overflow:hidden'><table id='wcthumbs' "+
   "class='wcwidth' style='margin-top:"+webcam.thumbOffset+
   "px;margin-bottom:"+(0-webcam.thumbOffset)+"px' "+
   "cellspacing=0 cellpadding=0 border=0><tr>";
  var smallid= " id='wcthumbsm'";
  for (var i= webcam.data.thumbs.length-1; i>=0; i--) {
    var thumb= webcam.data.thumbs[i].replace(/_l./,"_sm");
    var dat= thumb.replace(/_.*/, "").split("/");
    var tm= dat[3].replace(/..$/,"")+":"+dat[3].replace(/^../,"");
    if (webcam.samehour>0) {
      tm= dat[2]+"."+dat[1]+".";
    }
    thumbs+= "<td class='wcthumbnail' "+
             "onclick='webcam.go(\""+thumb+"\")'><small"+smallid+">"+
             tm+"</small><br><img src='"+webcam.url+thumb+"' width="+
             webcam.thumbWidth+" height="+webcam.thumbHeight+"></td>";
    smallid= "";
    if ((count%webcam.thumbNum)==(webcam.thumbNum-1)) {
      thumbs+= "</tr><tr>";
    }
    else {
      thumbs+= "<td class='wcthumbspacer'></td>";
    }
    count++;
  }
  thumbs+= "</tr></table></div><br><br>";
  $("#thumbnails").html(thumbs);
  $("#thumbnails").show();
  if (webcam.thumbTarget==1) {
    webcam.thumbTarget= 0;
  }
  $(".wcwidth").css("width", webcam.actWidth+"px");
};

// ****************************************************************************
// Open full-sized image in maximum-sized popup window
webcam.hugelock= false;
webcam.openHugeImage= function () {
  if (webcam.hugelock == false) {
    webcam.hugelock= true;
    setTimeout(function() {
      webcam.hugelock= false;
    }, 1000);
    var win= window.open(webcam.inc+"fullsize.php?wc="+webcam.name+
             "&img="+webcam.data.hugeimg.replace(/_.*/, ""),
             webcam.name.replace(/[^a-z]/g,"")+"_hu",
             "width="+webcam.hugeWidth+",height="+webcam.hugeHeight+
             ",scrollbars=yes,resizable=yes,status=no,toolbar=no,menubar=no");
    win.focus();
  }
};

// ****************************************************************************
// Show the current image
webcam.renderImage= function(image) {
  var src= webcam.url+image;
  if (webcam.isHdSize && webcam.data.hdimg.length>0) {
    src= src.replace(/_l..jpg/,"_lm.jpg");
  }

  var img= $(webcam.layerId[webcam.layer]);
  if (webcam.data.hugeimg && webcam.data.hugeimg.length>0) {
    img.css("cursor", "pointer");
    img.attr("title", msg.maxim+" ("+
             webcam.hugeWidth+"x"+webcam.hugeHeight+" Pixel)");
    img.bind("click", webcam.openHugeImage);
  }
  else {
    img.css("cursor", "default");
    img.attr("title", msg.ismax);
    img.unbind("click");
  }
  if (img.attr("src") == src) { // webkit does not fire of no change
    webcam.onImgLoad();
  }
  else {
    img.unbind("load");
    img.bind("load", webcam.onImgLoad);
    img.attr("src", src);
  }
};

// ****************************************************************************
// Start rendering thumbnails
webcam.playThumbnails= function(samehour) {
  if (webcam.mode=="thumb" && webcam.samehour==samehour) {
    webcam.go(webcam.data.image);
  }
  else {
    webcam.go(webcam.data.image+"/thumb"+((samehour>0)?"/samehour":""));
  }
};

// ****************************************************************************
// Start rendering a video
webcam.playVideo= function(samehour) {
  webcam.go(webcam.data.image+"/video"+((samehour>0)?"/samehour":""));
};

// ****************************************************************************
// Continue playing a video, fetch next frame
webcam.nextVideoFrame= function() {
  if (webcam.videoTimer) {
    setTimeout(webcam.nextVideoFrame, 100);
    return;
  }
  if (webcam.mode=="video" && webcam.vidarr.length>0) {
    if (webcam.vidptr>=webcam.vidarr.length || webcam.vidptr<0) {
      // we are ready, the last frame is the current image
      webcam.go(webcam.data.image);
    }
    else {
      // render a simple progress bar below the image
      var percent= Math.round((webcam.vidptr*102)/webcam.vidarr.length);
      $("#progress").html(
        "<div style='background-color:#e0e0e0;border: #a0a0a0 1px solid;"+
        "font-size:0px;'><span style='"+
        "background-color: #b0b0b0; display: inline-block; width: "+percent+
        "%;height: 10px;'></span></div>");
      // show the next image
      webcam.render= false;
      webcam.renderImage(webcam.vidarr[webcam.vidptr]);
      webcam.videoTimer= setTimeout(webcam.renderFrame, webcam.videoDelay);
    }
  }
};

// ****************************************************************************
// When an image has been load into the browser, fade from last to recent image
//
// Is called when
// - image load event is fired
// - video timer has expired and image is ready to display
//
// This is the try to achieve a constant frame rate. Obviously that is not
// really sufficiant.
webcam.renderFrame= function() {
  clearTimeout(webcam.videoTimer);
  webcam.videoTimer= null;
  if (webcam.imageReady) {
    webcam.imageReady= false;
    $("#wcloadimg").css("display", "none"); // hide animated load-icon
    $(webcam.layerId[webcam.layer]).css("z-index", "1");
    var hidelayer= (webcam.layer+(webcam.layers-1))%webcam.layers;
    $(webcam.layerId[hidelayer]).css("z-index", "0");
    var hidelayer2= (webcam.layer+(webcam.layers-2))%webcam.layers;
    $(webcam.layerId[hidelayer2]).css("z-index", "0");
    var delay= webcam.fadeDelay;
    if (webcam.mode == "video") {
      webcam.markActive(webcam.vidarr[webcam.vidptr]);
      delay= webcam.videoDelay-150;
      if (webcam.videoOrder==0) {
        webcam.vidptr--;
      }
      else {
        webcam.vidptr++;
      }
    }
    var requestNum= webcam.requestNum;
    $(webcam.layerId[webcam.layer]).fadeIn(delay, function() {
      if (webcam.requestNum != requestNum) {
        return;
      }
      $(webcam.layerId[hidelayer]).hide();
      if (webcam.data.isbestof) {
        $("#wcbestof").fadeIn(800);
      }
    });
    webcam.layer= (webcam.layer+1)%webcam.layers;

    // if we are ready, check for next frame
    webcam.nextVideoFrame();
  }
};

// ****************************************************************************
// Image has been load and fires this event
webcam.onImgLoad= function() {
  webcam.imageReady= true;
  // wait for video timer to avoid rendering too early
  if (webcam.videoTimer == null) {
    webcam.renderFrame();
  }
};

// ****************************************************************************
// Scroll through history day by day
webcam.goDay= function(forward, delay) {
  var path= webcam.data.image.replace(/_.*/, "").split("/");
  var monlen= new Array(31,28,31, 30,31,30, 31,31,30, 31,30,31);

  if (forward) {
    // we have already reached the date today, do not advance beyond
    var dat= new Date;
    if (path[0]==dat.getFullYear()) {
      if (path[1]==dat.getMonth()+1) {
        if (path[2]==dat.getDate()) {
          return;
        }
      }
    }
    path[2]++;
    if (path[2]> monlen[path[1]-1]) {
      path[2]= 1;
      path[1]++;
      if (path[1]>12) {
        path[1]= 1;
        path[0]++;
      }
    }
  }
  else {
    path[2]--;
    if (path[2]==0) {
      path[1]--;
      if (path[1]==0) {
        path[1]= 12;
        path[0]--;
      }
      path[2]= monlen[path[1]-1];
    }
  }

  // render leading zeroes (missing sprintf ;-)
  if (String(path[1]).length<2) {
    path[1]= "0"+path[1];
  }
  if (String(path[2]).length<2) {
    path[2]= "0"+path[2];
  }

  webcam.data.image= path.join("/");
  webcam.markActive(webcam.data.image);
  if (webcam.histTimer) {
    clearTimeout(webcam.histTimer);
  }
  webcam.histTimer= setTimeout(function() {
    webcam.histTimer= null;
    webcam.go(path.join("/"));
  }, delay);
};


// ****************************************************************************
// Scroll through the image history
webcam.goHistory= function(jump, delay) {
  if (jump > 0) {
    var max= webcam.data.history.length-1;
    if (webcam.data.histptr == max) {
      return;
    }
    webcam.data.histptr= jump+parseInt(webcam.data.histptr);
    if (webcam.data.histptr > max) {
      jump-= webcam.data.histptr-max;
      webcam.data.histptr= max;
    }
  }
  else {
    if (webcam.data.histptr == 0) {
      return;
    }
    webcam.data.histptr= jump+parseInt(webcam.data.histptr);
    if (webcam.data.histptr < 0) {
      jump-= webcam.data.histptr;
      webcam.data.histptr= 0;
    }
  }
  webcam.data.image= webcam.data.history[webcam.data.histptr];
  webcam.markActive(webcam.data.image);
  if (webcam.histTimer) {
    clearTimeout(webcam.histTimer);
  }
  if (webcam.thumbScroll(jump)) {
    webcam.histTimer= setTimeout(function() {
      var thumb= "";
      if (webcam.mode=="thumb") {
        thumb= "/thumb";
      }
      webcam.histTimer= null;
      webcam.go(webcam.data.history[webcam.data.histptr]+thumb);
    }, delay);
  }
};

// ****************************************************************************
// Soft-scroll the thumbnail list when changing the time
webcam.thumbScroll= function(jump) {
  if (webcam.mode=="thumb") {
    clearInterval(webcam.thumbTimer);
    webcam.thumbTimer= setInterval(function() {
      if (webcam.thumbOffset != webcam.thumbTarget) {
        if (webcam.thumbTarget != 1) {
          if (webcam.thumbTarget == 0) {
            webcam.thumbOffset-= webcam.thumbOffset/4-2;
            if (webcam.thumbOffset > 0) {
              webcam.thumbOffset= 0;
            }
          }
          else { 
            webcam.thumbOffset+= (webcam.thumbTarget-webcam.thumbOffset)/4-2;
            if (webcam.thumbOffset < webcam.thumbTarget) {
              webcam.thumbOffset= webcam.thumbTarget;
            }
          }
          $("#wcthumbs").css("margin-top", webcam.thumbOffset+"px");
          $("#wcthumbs").css("margin-bottom", (0-webcam.thumbOffset)+"px");
        }
      }
      if (webcam.thumbOffset == webcam.thumbTarget) {
        clearInterval(webcam.thumbTimer);
        webcam.thumbTimer= null;
        if (webcam.thumbTarget != 0) {
          webcam.thumbOffset= 0;
          webcam.thumbTarget= 0;
          webcam.go(webcam.data.history[webcam.data.histptr]+"/thumb");
        }
      }
    }, 50);
    var h= parseInt($("#wcthumbsm").height()+webcam.thumbHeight+2);
    if (jump>0) {
      webcam.thumbOffset+= Math.round((-jump/webcam.thumbNum)*h);
      webcam.thumbTarget= 1;
    }
    else {
      webcam.thumbTarget+= Math.round((jump/webcam.thumbNum)*h);
      // list is first scrolled, then filled with missing parts
      return false;
    }
  }
  return true;
};

// ****************************************************************************
// mark current image within the calendar
webcam.markActive= function(when) {
  if (when===undefined) {
    when= webcam.data.image;
  }
  when= when.replace(/_.*/, "");
  // year, month, day and hour
  var path= when.split("/");
  var day1= (new Date(path[0], path[1]-1, 1)).getDay();
  for (var i=0; i<webcam.navdivs.length; i++) {
    var id= webcam.navdivs[i];
    if (id.match(/^z[ymdh]/)) {
      var div= $("#"+id);
      var act= false;
      if (id == ("zy"+path[0])) { act= true; }
      if (id == ("zm"+path[1])) { act= true; }
      if (id == ("zd"+path[2])) { act= true; }
      if (id == ("zh"+path[3].replace(/..$/,""))) { act= true; }
      if (act) {
        div.removeClass("greyed");
        div.addClass("wcnavact");
      }
      else {
        div.removeClass("wcnavact");
      }
      // render weekday names
      var md=  parseInt(id.replace(/zd0*/,""));
      if (md>0) {
        var mds= id.replace(/zd/,"");
        var wd= (day1+md+6)%7;
        div.attr("title", msg.wdnames[wd]+", "+mds+"."+path[1]+"."+path[0]);
        if (wd==0 || wd==6) {
          div.addClass("wcnavwe");
        }
        else {
          div.removeClass("wcnavwe");
        }
      }
    }
  }
  // render minute-field appropriate
  if (webcam.data && webcam.data.history) {
    for (var i=0; i<4; i++) {
      var div= $("#zt"+i);
      var ptr= webcam.data.histptr-2+i;
      var len= webcam.data.history.length;
      // a running video has a slighty differen mechanism for the current pic
      if (webcam.mode=="video") {
        ptr= webcam.vidptr-1+i;
        len= webcam.vidarr.length;
      }

      if (ptr<0 || ptr>=len) {
        div.html("---");
        div.addClass("greyed");
      }
      else {
        var t;
        if (webcam.mode=="video") {
          t= webcam.vidarr[ptr].replace(/_.*/, "").split("/");
        }
        else {
          t= webcam.data.history[ptr].split("/");
        }
        var h= t[3].replace(/..$/,"");
        var m= t[3].replace(/^../, "");
        div.html(h+":"+m);
        div.removeClass("greyed");
      }
    }
    // the button for the current image
    if (webcam.data.newest && webcam.mode=="idle" && 
        when==webcam.data.history[webcam.data.history.length-1]) {
      $("#za").addClass("wcnavact");
    }
    else {
      $("#za").removeClass("wcnavact");
    }
  }
  // activate or deactivate the buttons for video/thumbnails
  var icons= new Object;
  icons.zf0= "play";
  icons.zf1= "play-d";
  icons.zn0= "thumb";
  icons.zn1= "thumb-d";
  var act= "";
  if (webcam.mode == "video") {
    act= (webcam.samehour>0)?"zf1":"zf0";
  }
  if (webcam.mode == "thumb") {
    act= (webcam.samehour>0)?"zn1":"zn0";
  }
  for (var id in icons) {
    var div= $("#"+id);
    var rev= "";
    if (id == act) {
      div.addClass("wcnavact");
      rev= "-rev"; // use icon containing white symbol
    }
    else {
      div.removeClass("wcnavact");
    }
    div.html("<img src='"+webcam.inc+icons[id]+rev+".png'>");
  }
  webcam.fillLeftBox(when);
};

// ****************************************************************************
// Mark image es "bestof" if or remove it from list if active
webcam.toggleBestof= function() {
  var del= "";
  if (webcam.data.isbestof) {
    del= "&delete=1";
  }
  var best= window.open(webcam.inc+"best.php?img="+
        webcam.data.image+"&wc="+webcam.name+del,"bestof",
        "width=500,height=230,"+
        "scrollbars=no,resizable=no,status=no,toolbar=no,menubar=no");
  best.focus();
}

// ****************************************************************************
// If appropriate, show star-icon for best images
webcam.showBestofIcon= function(show) {
  clearTimeout(webcam.bestofTimer);
  webcam.bestofTimer= setTimeout(function() {
    if (show || webcam.data.isbestof) {
      $("#wcbestof").fadeIn(800);
    }
    else {
      $("#wcbestof").fadeOut(800);
    }
  }, 200);
  return false;
}

// ****************************************************************************
// Show the page with the stored "bestof"-images
webcam.renderBestof= function() {
  var ret= "Keine Bilder gefunden.";
  var found= false;
  if (webcam.data.bestof && webcam.data.bestof.length) {
    for (var i=0; i<webcam.data.bestof.length; i++) {
      if (webcam.data.image.match(webcam.data.bestof[i])) {
        webcam.bestofIndex= i;
        found= true;
        break;
      }
    }
    ret=  "<div style='text-align:center;margin-bottom:10px'>";
    ret+= "<a href='javascript:webcam.seekBestof(false)'>"+
          "<img align='absmiddle' src='"+webcam.inc+"pf_li_20.png'></a>";
    ret+= " &nbsp; ";

    if (found) {
      ret+= msg.image+" "+(webcam.bestofIndex+1)+" "+msg.of+" "+
                    webcam.data.bestof.length+" - ";
    }
    else {
      ret+= webcam.data.bestof.length+" "+msg.images+" - ";
    }

    if (webcam.slideTimer) {
      ret+= 
        "<a href='javascript:webcam.stopSlideshow();webcam.renderBestof()'>"+
        "<img align='absmiddle' src='"+webcam.inc+
        "loading-16.gif'> "+msg.slidest+"</a>";
    }
    else {
      ret+= 
        "<a href='javascript:webcam.startSlideshow();webcam.renderBestof()'>"+
        msg.slide+"</a>";
    }
    ret+= " &nbsp; ";

    ret+= "<a href='javascript:webcam.seekBestof(true)'>"+
          "<img align='absmiddle' src='"+webcam.inc+"pf_re_20.png'></a>";
    ret+= "</div>";

    for (var i=0; i<webcam.data.bestof.length; i++) {
      var go= webcam.data.bestof[i];
      var dat= webcam.humanDate(go);
      var cl= "wcbestof-norm";
      if (found && i == webcam.bestofIndex) {
        cl= "wcbestof-hi";
      }
      var lazy= "";
      if (i>17 && !webcam.bestofShown) {
        // Do lazy-loading for the lines more below
        lazy= webcam.inc+"trans.png' data-original='";
      }
      ret+= "<span class='wcbestof "+cl+"' "+
            "onclick='window.scrollTo(0,0);webcam.go(\""+go+"\")'>"+dat+
            "<br><img src='"+lazy+webcam.url+"bestof/"+go+"_sm.jpg' width='"+
            webcam.thumbWidth+"' height='"+webcam.thumbHeight+"'></span> ";
    }
    for (var i=0;i<15;i++) {
      ret+= "<span style='display:inline-block;width:"+
            webcam.thumbWidth+"px;height:1px;padding:0px;'></span> ";
    }
  }
  $("#wcinfo-bestof").html(ret);
  if (! webcam.bestofShown) {
    $(".wcbestof>img").lazyload({effect : "fadeIn"});
    webcam.bestofShown= true;
  }
}

// ****************************************************************************
// Position to a the next/recent image in the bestof-list
webcam.seekBestof= function(back) {
  if (webcam.data.bestof && webcam.data.bestof.length) {
    if (back) {
      webcam.bestofIndex++;
      if (webcam.bestofIndex>=webcam.data.bestof.length) {
        webcam.bestofIndex= 0;
      }
    }
    else {
      if (webcam.bestofIndex>0) {
        webcam.bestofIndex--;
      }
      else {
        webcam.bestofIndex= webcam.data.bestof.length-1;
      }
    }
    webcam.go(webcam.data.bestof[webcam.bestofIndex]);
  }
}

// ****************************************************************************
// Show a slide show of best images
webcam.stopSlideshow= function() {
  if (webcam.slideTimer) {
    clearTimeout(webcam.slideTimer);
    webcam.slideTimer= null;
  }
}

webcam.startSlideshow= function() {
  webcam.stopSlideshow();
  if (webcam.data.bestof && webcam.data.bestof.length) {
    if (! webcam.data.isbestof) {
      webcam.go(webcam.data.bestof[0]);
    }
    webcam.slideTimer= setTimeout(function() {
      webcam.seekBestof(true);
      webcam.startSlideshow();
    }, webcam.slideDelay*1000);
  }
}



// ****************************************************************************
// Set settings to the default values (initially called)
webcam.defaultSettings= function() {
  webcam.fadeDelay=   webcam.def.fadeDelay;
  webcam.videoFrames= webcam.def.videoFrames;
  webcam.videoDelay=  webcam.def.videoDelay;
  webcam.videoOrder=  webcam.def.videoOrder;
  webcam.slideDelay=  webcam.def.slideDelay;
  webcam.imgSize=     webcam.def.imgSize;
}

// ****************************************************************************
// Read possibly prior stored settings from cookie values
webcam.readSettings= function() {
  webcam.defaultSettings();
  var m= /wcsettings=([0-9a-z,:]+)/i;
  var r= m.exec(document.cookie);
  if (r) {
    var args= r[1].split(",")
    for (var i=0; i<args.length; i++) {
      var par= args[i].split(":");
      if (par.length==2) {
        webcam[par[0]]= parseInt(par[1]);
      }
    }
  }
}

// ****************************************************************************
// Set settings to default and remove settings cookie
webcam.removeSettings= function() {
  webcam.defaultSettings();
  // cookie expires now and thus is not longer stored
  document.cookie= "wcsettings=; path=/; expires="+new Date().toGMTString();
  webcam.renderSettings();
}

// ****************************************************************************
// Store settings changes as class variables and in a persistent cookie
webcam.changeCombo= function(ele) {
  var name= $(ele).attr('name');
  webcam[name]= parseInt($(ele).val());
  var settings= "videoFrames:"+webcam.videoFrames+","+
                "videoDelay:"+webcam.videoDelay+","+
                "videoOrder:"+webcam.videoOrder+","+
                "slideDelay:"+webcam.slideDelay+","+
                "imgSize:"+webcam.imgSize+","+
                "fadeDelay:"+webcam.fadeDelay;

  document.cookie= "wcsettings="+settings+"; path=/; expires="+
           new Date(new Date().getFullYear()+5,1,1,0,0,0).toGMTString();
  webcam.setSize(false);
}

// ****************************************************************************
// Generic select box for numeric values
webcam.comboBox= function(name, begin, end, step, selected, def) {
  var ret= "<select style='width:120px' onchange='webcam.changeCombo(this)' "+
           "name='"+name+"'>";
  for (var val= begin; val<=end; val+= step) {
    var sel= "";
    if (val == selected) {
      sel= " selected";
    }
    ret+= "<option value='"+val+"'"+sel+">"+val;
    if (val == def) {
      ret+= " *";
    }
  }
  ret+= "</select>";
  return ret;
}

// ****************************************************************************
// Enumeration select box with textual representaion of index values
webcam.comboBoxText= function(name, labels, selected, def) {
  var ret= "<select style='width:120px' onchange='webcam.changeCombo(this)' "+
           "name='"+name+"'>";
  for (var val= 0; val<labels.length; val++) {
    var sel= "";
    if (val == selected) {
      sel= " selected";
    }
    ret+= "<option value='"+val+"'"+sel+">"+labels[val];
    if (val == def) {
      ret+= " *";
    }
  }
  ret+= "</select>";
  return ret;
}

// ****************************************************************************
// Show login specific iframe within settings page
webcam.openLogin= function() {
  $("#logindiv").html("<iframe src='"+webcam.inc+"login.php?wc="+webcam.name+
       "' width=800 height=110 style='border:0'></iframe>");
}

// ****************************************************************************
// Show settings page
webcam.renderSettings= function() {
  var ret= "<div id='logindiv' style='display:none'>"+
    "<a style='display:inline-block;float:right;cursor:pointer;' "+
    "onclick='webcam.openLogin()'>Login</a></div>";

  ret+= "<table cellspacing=2 cellpadding=2 border=0>";
  if (webcam.lang == "de") {
    ret+= 
      "<tr><td colspan=2><h2>Einstellungen zum Webcam-Archiv</h3></td></tr>";

    ret+= "<tr><td>Überblendezeit zwischen zwei Bildern:</td><td>";
    ret+= webcam.comboBox("fadeDelay", 0, 1000, 100,
                          webcam.fadeDelay, webcam.def.fadeDelay);
    ret+= " ms</td></tr>";

    ret+= "<tr><td>Anzahl der Bilder für die Zeitraffer-Sequenz:</td><td>";
    ret+= webcam.comboBox("videoFrames", 10, 500, 10, 
                          webcam.videoFrames, webcam.def.videoFrames);
    ret+= " Bilder</td></tr>";

    ret+= "<tr><td>Verzögerung zwischen den Zeitraffer-Bildern:</td><td>";
    ret+= webcam.comboBox("videoDelay", 100, 3000, 100,
                          webcam.videoDelay, webcam.def.videoDelay);
    ret+= " ms</td></tr>";

    ret+= "<tr><td>Abspielrichtung der Zeitraffer-Sequenz:</td><td>";
    ret+= webcam.comboBoxText("videoOrder", 
                          new Array("Umgekehrt", "Chronologisch"),
                          webcam.videoOrder, webcam.def.videoOrder);
    ret+= "</td></tr>";

    ret+= "<tr><td>Wartezeit für Diashow der besten Bilder:</td><td>";
    ret+= webcam.comboBox("slideDelay", 1, 20, 1,
                          webcam.slideDelay, webcam.def.slideDelay);
    ret+= " s</td></tr>";

    ret+= "<tr><td>Größe des dargestellten Bildes:</td><td>";
    ret+= webcam.comboBoxText("imgSize", 
                          new Array("Automatisch", "Normales Bild", "HD-Bild"),
                          webcam.imgSize, webcam.def.imgSize);
    ret+= "</td></tr>";

    ret+= "<tr><td colspan=2>&nbsp;</td></tr>";
    ret+= "<tr><td><small><b>Hinweise:</b><br>"+
          "- Werte mit * sind die empfohlene Grundeinstellung.<br>"+
          "- Die Einstellungen bleiben erhalten, wenn Cookies erlaubt sind."+
          "</td><td valign='top'>"+
          "<input type='button' onclick='webcam.removeSettings();' "+
          "value='Auf Grundeinstellung zurücksetzen'></td></tr></table>";
  }
  else {
    ret+= 
      "<tr><td colspan=2><h2>Settings</h2></td></tr>";

    ret+= "<tr><td>Cross-fade delay when selecting image:</td><td>";
    ret+= webcam.comboBox("fadeDelay", 0, 1000, 100,
                          webcam.fadeDelay, webcam.def.fadeDelay);
    ret+= " ms</td></tr>";

    ret+= "<tr><td>Count of single frames for the time-lapse video:</td><td>";
    ret+= webcam.comboBox("videoFrames", 10, 500, 10, 
                          webcam.videoFrames, webcam.def.videoFrames);
    ret+= " Images</td></tr>";

    ret+= "<tr><td>Delay between frames for the time-lapse video:</td><td>";
    ret+= webcam.comboBox("videoDelay", 100, 3000, 100,
                          webcam.videoDelay, webcam.def.videoDelay);
    ret+= " ms</td></tr>";

    ret+= "<tr><td>Play-order of the time-lapse video:</td><td>";
    ret+= webcam.comboBoxText("videoOrder", 
                          new Array("Reverse", "Chronological"),
                          webcam.videoOrder, webcam.def.videoOrder);
    ret+= "</td></tr>";

    ret+= "<tr><td>Delay of slide show:</td><td>";
    ret+= webcam.comboBox("slideDelay", 1, 20, 1,
                          webcam.slideDelay, webcam.def.slideDelay);
    ret+= " s</td></tr>";

    ret+= "<tr><td>Size of the image:</td><td>";
    ret+= webcam.comboBoxText("imgSize", 
                          new Array("Auto", "Normal size", "HD size"),
                          webcam.imgSize, webcam.def.imgSize);
    ret+= "</td></tr>";

    ret+= "<tr><td colspan=2>&nbsp;</td></tr>";
    ret+= "<tr><td><small><b>Hints:</b><br>"+
          "- Recommended default-values are marked with *<br>"+
          "- To store the settings cookies must be allowed."+
          "</td><td valign='top'>"+
          "<input type='button' onclick='webcam.removeSettings();' "+
          "value='Reset to default'></td></tr></table>";

  }

  $("#wcinfo-settings").html(ret);
  if (webcam.username.length > 0) {
    webcam.openLogin();
  }
}

// ****************************************************************************
// Create contents for an additional navigation box (normally on the left)
webcam.fillLeftBox= function(when) {
  var path= when.replace(/_.*/, "").split("/");
  var fwdPic= "<a title='Um ein Bild vorspringen (Cursortaste runter)' "+
              "class='navpf' "+
              "href='javascript:webcam.goHistory(1,10)'>"+
              "<img src='"+webcam.inc+"pf_re_20.png'></a>";
  var fwdDay= "<a title='Um einen Tag vorspringen (Cursortaste rechts)' "+
              "class='navpf' "+
              "href='javascript:webcam.goDay(true,10)'>"+
              "<img src='"+webcam.inc+"pf_re_20.png'></a>";

  // if date is today, do not enable switch to the future
  var nofu= "title='Kein Bild aus der Zukunft gefunden'";
  var actdat= new Date;
  if (path[0]==actdat.getFullYear()) {
    if (path[1]==actdat.getMonth()+1) {
      if (path[2]==actdat.getDate()) {
        fwdDay= "<img src='"+webcam.inc+"pf_re_20_gr.png' "+nofu+">";
      }
    }
  }
  if (webcam.data.histptr >= (webcam.data.history.length-1)) {
    fwdPic= "<img src='"+webcam.inc+"pf_re_20_gr.png' "+nofu+">";
  }
  var dtarr= when.replace(/_.*/, "").split("/");
  var tm= dtarr[3].replace(/..$/,"")+":"+dtarr[3].replace(/^../,"");
  var dt= dtarr[2]+"."+dtarr[1]+"."+dtarr[0].replace(/^../,"");

  var box= 
    "<div id='left-replacement' style='margin:5px;'>"+
    "<div style='margin:2px;'>"+
    "<table class='wcnav' cellspacing=0 cellpadding=0 border=0 width='100%'>"+
    "<tr><td><a href='javascript:webcam.goHistory(-1,10)' class='navpf' "+
    "title='Um ein Bild zurückspringen (Cursortaste rauf)'>"+
    "<img src='"+webcam.inc+"pf_li_20.png'></a></td>"+
    "<td width='50%'>"+tm+"</td><td>"+fwdPic+"</td></tr>"+
    "<tr><td><a href='javascript:webcam.goDay(false,10)' class='navpf' "+
    "title='Um einen Tag zurückspringen (Cursortaste links)'>"+
    "<img src='"+webcam.inc+"pf_li_20.png'></a></td>"+
    "<td width='50%'>"+dt+"</td><td>"+fwdDay+"</td></tr>"+
    "</table>";
  if (webcam.boxadd) {
    box+= "<div style='margin-top:8px;'></div>"+webcam.boxadd;
  }
  box+= "</div>";

  var rep= $("#left-replacement");
  if (rep.length) {
    rep.replaceWith(box); // does not flicker on webkit rather than .html()
  }
  else {
    $("#left-box").html(box);
  }
}

// ****************************************************************************
// Process keyboard events
webcam.keypress= function(event) {
  //webcam.msg(event.keyCode);

  if (event.shiftKey || event.altKey || event.ctrlKey || event.metaKey) {
    return true;
  }
  if (webcam.mode=="thumb") {
    if (webcam.samehour) {
      return true;
    }
    switch (event.keyCode) {
      case 38: // up
               webcam.goHistory(webcam.thumbNum, 10);
               return false;
      case 40: // down
               webcam.goHistory(-webcam.thumbNum, 10);
               return false;
      case 37: // left
               webcam.goHistory(-webcam.thumbNum*5, 10);
               return false;
      case 39: // right
               webcam.goHistory(webcam.thumbNum*5, 10);
               return false;
    }
  }
  switch (event.keyCode) {
    case 38: // up
             webcam.goHistory(-1, webcam.keyDelay);
             return false;
    case 40: // down
             webcam.goHistory(1, webcam.keyDelay);
             return false;
    case 37: // left
             webcam.goDay(false, webcam.keyDelay);
             return false;
    case 39: // right
             webcam.goDay(true, webcam.keyDelay);
             return false;
    case 27: // esc
    case 65: // a
             webcam.go("");
             return false;
    case 84: // t
             webcam.playThumbnails(0);
             return false;
    case 86: // v
             webcam.playVideo(0);
             return false;
    case 70:  // f
    case 107: // +
             webcam.openHugeImage();
             return false;
  }
  return true;
};

// ****************************************************************************
// Process mouse wheel events
webcam.wheel= function(event) {
  var delta= 0;
  var up= 38;
  var down= 40;
  if ($(this).attr("id").match("wcnavtab")) {
    up= 37; down= 39;
  }
  var ev= new Object;
  if (!event) { event= window.event; }
  if (event.wheelDelta) {
    delta= event.wheelDelta; 
    if (window.opera) delta= -delta;
  } else if (event.detail) { delta= -event.detail; }
  if (delta<0) { 
    ev.keyCode= down;
    webcam.keypress(ev);
  }
  else if (delta>0) { 
    ev.keyCode= up;
    webcam.keypress(ev);
  }
  if (event.preventDefault) { event.preventDefault(); }
  event.returnValue= false;
  return false;
};

// ****************************************************************************
// Debugging (mostly not used)
webcam.msg= function(m) {
  if (webcam.debug) {
    webcam.messages= "<br>l="+webcam.layer+
                     " i="+webcam.data.image+
                     " m="+webcam.mode+
                     " "+m+webcam.messages;
    $("#wcmessages").html(webcam.messages);
  }
};

// ****************************************************************************
// Reload image if appropriate minute after image-shot has come
webcam.checkAct= function() {
  var thisMinute= (new Date().getMinutes())%10;
  if (! webcam.actMinute) {
    webcam.actMinute= 1;
  }
  if (thisMinute == webcam.actMinute) {
    if (webcam.mode=="idle" && webcam.data.newest) {
      webcam.go();
    }
  }
  if (thisMinute == (webcam.actMinute+1)) {
    if (webcam.actopt=="webcams") {
      $(".wcminiimg").each(function(img) {
        var src= $(this).attr("src");
        if (! src.match(/trans.png/)) {
          src= src.replace(/\?.*$/, "");
          src+= "?"+new Date().getTime();
          $(this).attr("src", src);
        }
      });
    }
  }
};

// ****************************************************************************
// Catch mouse wheel events
webcam.registerWheel= function(id, callback) {
  var ele= document.getElementById(id);
  if (ele) {
    if (ele.addEventListener) {
      ele.addEventListener('DOMMouseScroll', callback, false);
    }
    ele.onmousewheel= callback; 
  }
};

// ****************************************************************************
// The options menu (below image) has been clicked
webcam.clickOption= function() {
  for (var i=0; i<webcam.options.length; i++) {
    var opt= webcam.options[i];
    if ($(this).attr("id")=="wcopt-"+opt) {
      webcam.go(webcam.data.image+"/"+opt);
    }
  }
}

// ****************************************************************************
// Care about the overlay div which is on top of the image
webcam.renderLabels= function() {
  var ov= $("#wcoverlay");
  if (webcam.data.labels && 
     (webcam.actopt=="infos" || webcam.actopt=="editlabels")) {

    var ovcontent= "";
    var xmax= ov.width();
    var ymax= ov.height();

    for (i= 0; i<webcam.data.labels.length; i++) {
      var o= webcam.data.labels[i];
      var x= (o.x*xmax)/100;
      var y= ymax-(o.y*ymax)/100;
      var txt= o.txt;

      if (o.href && o.href!="" && webcam.actopt!="editlabels") {
        txt= "<a href='"+o.href+"'><img src='"+webcam.inc+
        "extlink.png' align='top'> "+txt+"</a>";
      }
      ovcontent+= "<span id='lab_"+o.id+"' style='bottom:"+y+"px;";
      if (webcam.selectedLabel == o.id) {
        ovcontent+= "margin:-2px;border: 2px solid red;";
      }
      if (xmax-x < 500) {
        ovcontent+=  "right:"+(xmax-x-5)+
          "px;' class='wclabel wclabel-1'>"+txt+"</span>";
      }
      else {
        ovcontent+= "left:"+(x-5)+
          "px;' class='wclabel wclabel-0'>"+txt+"</span>";
      }
    }
    ov.html(ovcontent);
    ov.css("cursor", "default");
    ov.attr("title", "");
    ov.unbind("click");
    if (webcam.labelsVisible) {
      $(".wclabel").show();
    }
    else {
      $(".wclabel").fadeIn(1000);
      webcam.labelsVisible= true;
    }
  }
  else {
    $(".wclabel").fadeOut(1000, function() {
      ov.html("");
      webcam.labelsVisible= false;
    });
    if (webcam.data.hugeimg && webcam.data.hugeimg.length>0) {
      ov.css("cursor", "pointer");
      ov.attr("title", "Klick zum Anzeigen des Bildes in voller Größe ("+
              webcam.hugeWidth+"x"+webcam.hugeHeight+" Pixel)");
      ov.bind("click", webcam.openHugeImage);
    }
    else {
      ov.css("cursor", "default");
      ov.attr("title", "Bild hat bereits maximale Größe");
      ov.unbind("click");
    }
  }
}

// ****************************************************************************
// Convert date/time into a human readable format
webcam.humanDate= function(go) {
  var m= /([0-9][0-9])\/(.*)\/(.*)\/([0-9][0-9])([0-9][0-9])/;
  var r= m.exec(go);
  if (r) {
    return r[3]+"."+r[2]+"."+r[1]+" "+r[4]+":"+r[5];
  }
  return go;
}

// ****************************************************************************
// Honour browser window resize events
webcam.setSize= function(first) {
  var changed= false;
  var height= $(window).height();
  var width= $(window).width();

  // maybe an external requirement forces fix size
  if (webcam.forceSize && webcam.forceSize>0) {
    webcam.imgSize= webcam.forceSize;
  }

  if ((width>1400 && height>780 && webcam.imgSize==0) || webcam.imgSize==2) {
    if (webcam.isHdSize == false && webcam.hdWidth>webcam.mainWidth) {
      webcam.actWidth= webcam.hdWidth;
      webcam.actHeight= webcam.hdHeight;
      webcam.isHdSize= true;
      changed= true;
    }
  }
  else if (((width<1370 || height<760) && webcam.imgSize==0) || 
    webcam.imgSize==1) {
    if (webcam.isHdSize == true) {
      webcam.actWidth= webcam.mainWidth;
      webcam.actHeight= webcam.mainHeight;
      webcam.isHdSize= false;
      changed= true;
    }
  }
  if (changed) {
    $(".wcwidth").css("width", webcam.actWidth+"px");
    $(".wcheight").css("height", webcam.actHeight+"px");
    for (i= 0; i< webcam.layers; i++) {
      $(webcam.layerId[i]).css("max-height",webcam.actHeight+"px");
      $(webcam.layerId[i]).css("max-width",webcam.actWidth+"px");
    }
    $("#wcimgdiv").css("min-height",(webcam.actHeight+5)+"px");
    $("#wcimgdiv").css("min-width",webcam.actWidth+"px");
    $("#wcoverlay").css("height",webcam.actHeight+"px");
    $("#wcoverlay").css("width",webcam.actWidth+"px");
    $("#wcbestsw").css("margin-top",(webcam.actHeight-16)+"px");
    webcam.thumbNum= Math.floor(webcam.actWidth/webcam.thumbWidth);
    if (first==false) {
      webcam.go(webcam.data.image);
    }
    if (setSizes !== undefined) {
      setSizes();
    }
  }
}

// ****************************************************************************
// Initialize when page has load
$(document).ready(function() {
  webcam.readSettings();
  var cal= "<div id='wcarchright'>";
  cal+= "<table class='wcclocktab wctext' id='wcclocktab'>";
  for (var t=0; t<4; t++) {
    cal+= "<tr><td id='zt"+t+"'></td></tr>";
    webcam.navdivs.push("zt"+t);
  }
  cal+= "</table>";
  cal+= "</div>";

  cal+= "<table id='wcnavtab' class='wcnavtab wcnavtabbot wctext'><tr>";
  cal+= "<td colspan=2 class='wcnavlegend'>"+msg.year+"</td>";
  var ay= (new Date()).getFullYear();
  for (var y=ay; y>(ay-8); y--) {
    cal+= "<td colspan=2 id='zy"+y+"'>"+y+"</td>";
    webcam.navdivs.push("zy"+y);
  }
  cal+= "<td colspan=3 class='wcnavlegend' "+
    "style='text-align: right; padding-right: 5px;'>"+msg.mon+"</td>";
  for (var m=1; m<=12; m++) {
    var m0= (m<10?'0':'')+m;
    cal+= "<td id='zm"+m0+"'>"+m0+"</td>";
    webcam.navdivs.push("zm"+m0);
  }
  cal+= "</tr><tr>";
  cal+= "<td colspan=2 class='wcnavlegend'>"+msg.day+"</td>";
  for (var d=1; d<=31; d++) {
    var d0= (d<10?'0':'')+d;
    cal+= "<td id='zd"+d0+"'>"+d0+"</td>";
    webcam.navdivs.push("zd"+d0);
  }
  cal+= "</tr><tr>";
  cal+= "<td colspan=2 class='wcnavlegend'>"+msg.time+"</td>";
  for (var h=0; h<24; h++) {
    var h0= (h<10?'0':'')+h;
    cal+= "<td id='zh"+h0+"'>"+h0+"</td>";
    webcam.navdivs.push("zh"+h0);
  }
  cal+= "<td id='zf0' title='"+msg.video_h+
        "' onclick='webcam.playVideo(0)'>"+
        "<img src='"+webcam.inc+"play.png'></td>";
  cal+= "<td id='zf1' title='"+msg.video_d+
        "' onclick='webcam.playVideo(1)'>"+
        "<img src='"+webcam.inc+"play-d.png'></td>";
  cal+= "<td id='zn0' title='"+msg.thumb_h+
        "' onclick='webcam.playThumbnails(0)'>"+
        "<img src='"+webcam.inc+"thumb.png'></td>";
  cal+= "<td id='zn1' title='"+msg.thumb_d+
        "' onclick='webcam.playThumbnails(1)'>"+
        "<img src='"+webcam.inc+"thumb-d.png'></td>";
  cal+= "<td colspan=3 id='za' title='"+msg.act_t+"' "+
        "onclick='webcam.go()'>"+msg.act+"</td>";
  cal+= "</tr></table>";
  $("#wcnav").html(cal);

  // register mouse events of all navigation fields
  for (var i=0; i<webcam.navdivs.length; i++) {
    $("#"+webcam.navdivs[i]).click(function() {
      webcam.go(webcam.data.ids[$(this).attr("id")]);
    });
    // on mouseover show thumbnail of this time
    $("#"+webcam.navdivs[i]).mouseover(function() {
      var tn= webcam.data.ids[$(this).attr("id")];
      if (tn) {
        tn= tn.replace(/_.*/,"");
        var dat= webcam.humanDate(tn);
        $("#wcovthumb").stop(true,true);
        $("#wcovthumb").html("<div>"+dat+"</div><img src='"+tn+"_sm.jpg'>");
        //$("#wcovthumb").css("display","block");
        $("#wcovthumb").css("top",($(this).position().top+60)+"px");
        $("#wcovthumb").css("left",$(this).position().left+"px");
        $("#wcovthumb").stop(true,true).fadeIn(200);
      }
    });
    $("#"+webcam.navdivs[i]).mouseout(function() {
      $("#wcovthumb").stop(true,true).fadeOut(500);
    });
  }
  // statically activate actual minute position
  $("#zt2").addClass("wcnavact");

  // In some cases the hash shall not be used for navigation
  if (webcam.ignoreHash) {
    location.hash= "";
  }
  // honour AJAX-navigation through hash part of URL
  webcam.go(location.hash);
  window.onhashchange= function() {
    if (webcam.hashsense) {
      webcam.go(location.hash);
    }
  };

  // register keyboard events
  $(document).keydown(webcam.keypress);

  // register mouse wheel
  webcam.registerWheel("wcclocktab", webcam.wheel);
  webcam.registerWheel("wcnavtab", webcam.wheel);
  webcam.registerWheel("thumbnails", webcam.wheel);

  // setup timer for image reload checking
  setInterval(webcam.checkAct, 60000);

  // register event handlers for options menu
  for (var i=0; i<webcam.options.length; i++) {
    $("#wcopt-"+webcam.options[i]).click(webcam.clickOption);
  }
  // align the bestof-icon with actual image size
  $("#wcbestsw").css("margin-top",(webcam.actHeight-16)+"px");

  // Determine browser size and handle size changes
  $(window).resize(function() {
    webcam.setSize(false);
  });
  webcam.setSize(true);
});


// ****************************************************************************
// Initialize when page is loading (not yet ready, see above)
{
  var wclayers= "";
  for (i= 0; i< webcam.layers; i++) {
    webcam.layerId[i]= "#wclayer"+i;

    wclayers+= "<img id='wclayer"+i+"' class='wcimg' "+
               "style='max-height:"+webcam.actHeight+"px;"+
                      "max-width:"+webcam.actWidth+"px;'>";
  }
  document.write(
    "<div id='wcnav' class='wcwidth'></div>"+
    "<div id='wcimgdiv' style='height:"+(webcam.actHeight+5)+"px;"+
                              "min-height:"+(webcam.actHeight+5)+"px;"+
                              "min-width:"+webcam.actWidth+"px;'>"+
    wclayers+
    "<div id='wcoverlay' class='wcimg' style='display:none' "+
               "style='height:"+webcam.actHeight+"px;"+
                      "width:"+webcam.actWidth+"px;'></div>"+
    "<img id='wcloadimg' class='wcimg' src='"+webcam.inc+
         "loading-16.gif'></div>"+
    "<div id='wcbestsw' class='wcimg' onclick='webcam.toggleBestof()' "+
      "onmouseover='webcam.showBestofIcon(true)' "+
      "onmouseout='webcam.showBestofIcon(false)'>"+
      "<img title='Dieses Bild in die Liste der besten Bilder aufnehmen' "+
      "id='wcbestof' src='"+webcam.inc+"star.png' style='display:none'>"+
      "</div>"+
    "<div id='wcovthumb'></div>"+
    "<div id='thumbnails' class='wcimg'></div>"+
    "<div id='progress' class='wcwidth'></div>"
  );
}
