// resizing counterpart for dynamic maps
function resizeMapOnChange() {
  if (resizeMapOnChange.resizeMapTimeout !== undefined) {
    window.clearTimeout(resizeMapOnChange.resizeMapTimeout); 
  }
  resizeMapOnChange.resizeMapTimeout = window.setTimeout(resizeMap, 500);
}

function resizeMap() {
    var nonfooternav = document.getElementById('nonfooternav');
    var navbar = document.getElementById('navbar');
    var pagehead = document.getElementById('pagehead');
    var helptext = document.getElementById('helptext');
    var mapcontainer = document.getElementById('map_container');
    
    var headerHeight = navbar.offsetHeight + pagehead.offsetHeight;
    if (helptext) {
      headerHeight += helptext.offsetHeight;
    }
    
    var windowHeight = 0;
    if (window.innerHeight !== undefined) {
      windowHeight = window.innerHeight;
    } else if (typeof document.body != "undefined" && 
               typeof document.body.clientHeight != "undefined" && 
               document.body.clientHeight != 0){
      windowHeight = document.body.clientHeight; // ie7
    }
    
    mapcontainer.style.height = (windowHeight - headerHeight)+'px';
}

function showMap() {
  resizeMap();
  
  var mapElement = document.getElementById('map_canvas');
  if (mapElement) {
    var options = {
      'zoom' : 19, // make sure zoom level and bounds change when fitBounds is called
      'center' : new google.maps.LatLng(centerCoords['lat'], centerCoords['lon']),
      'mapTypeId' : google.maps.MapTypeId.ROADMAP,
      'mapTypeControl' : false,
      'panControl' : false,
      'streetViewControl' : false,
      'zoomControlOptions' : { 
        'position' : google.maps.ControlPosition.RIGHT_BOTTOM,
        'style'    : google.maps.ZoomControlStyle.SMALL
      }
    };
    
    var map = new google.maps.Map(mapElement, options);  

    var shadowMarkerImage = getMarkerImage(tourIcons['shadow']);

    for (var i = 0; i < tourStops.length; i++) {
      var stop = tourStops[i];
    
      var icon = tourIcons['other'];
      if (stop['visited']) {
        icon = tourIcons['visited'];
      }
      stop['defaultIcon'] = icon; // remember non-current icon state
      
      if (stop['current']) {
        icon = tourIcons['current'];
      }
      
      tourStops[i]['marker'] = new google.maps.Marker({
        'clickable' : true,
        'map'       : map, 
        'position'  : new google.maps.LatLng(stop['lat'], stop['lon']),
        'title'     : stop['title'],
        'icon'      : getMarkerImage(icon),
        'shadow'    : shadowMarkerImage,
        'flat'      : false
      });
      tourStops[i]['marker'].tourStopIndex = i;
      
      google.maps.event.addListener(tourStops[i]['marker'], 'click', function() {
        selectStop(this.tourStopIndex);
      });
    }
    
    var bounds = new google.maps.LatLngBounds();
    for (var i = 0; i < fitToBounds.length; i++) {
      bounds.extend(new google.maps.LatLng(fitToBounds[i]['lat'], fitToBounds[i]['lon']));
    }
    
    // Restrict the zoom level while fitting to bounds
    // Listeners will definitely get called because the initial zoom level is 19
    map.setOptions({ minZoom: 16, maxZoom: 18 });
    var zoomChangeListener = google.maps.event.addListener(map, 'zoom_changed', function() {
      var zoomChangeBoundsListener = google.maps.event.addListener(map, 'bounds_changed', function(event) {
        map.setOptions({minZoom: null, maxZoom: null});
        if (fitToBounds.length > 2) {
          map.panTo(new google.maps.LatLng(centerCoords['lat'], centerCoords['lon']));
        }
        google.maps.event.removeListener(zoomChangeBoundsListener);
      });
      google.maps.event.removeListener(zoomChangeListener);
    });
    map.fitBounds(bounds);

    /*
    if (navigator.geolocation) {
      updateCurrentPosition(map);
      setInterval(function() {
        updateCurrentPosition(map);
      }, 2000);
    }
    */
    
    var elem = document.getElementById('map_canvas');
    elem.style.visibility = 'visible';
    var elem = document.getElementById('map_loading');
    elem.style.display = 'none';
  }
}

function updateCurrentPosition(map) {
  navigator.geolocation.getCurrentPosition(function(position) {
    var location = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);

    if (typeof updateCurrentPosition.selfMarker == 'undefined') {
      updateCurrentPosition.selfMarker = new google.maps.Marker({
        'clickable' : false,
        'map'       : map, 
        'position'  : location,
        'flat'      : true,
        'icon'      : getMarkerImage(tourIcons['self'])
      });
    } else {
      updateCurrentPosition.selfMarker.setPosition(location);
    }
  }, function() {}, { enableHighAccuracy: true });
}

function selectStop(tourStopIndex) {
  var stopElems = [
    {
      'key'  : 'title',
      'elem' : document.getElementById('navstoptitle'),
      'attr' : 'innerHTML'
    },
    {
      'key'  : 'title',
      'elem' : document.getElementById('stoptitle'),
      'attr' : 'innerHTML'
    },
    {
      'key'  : 'subtitle',
      'elem' : document.getElementById('stopsubtitle'),
      'attr' : 'innerHTML'
    },
    {
      'key'  : 'thumbnail',
      'elem' : document.getElementById('zoomthumb'),
      'attr' : 'src'
    },
    {
      'key'  : 'photo',
      'elem' : document.getElementById('zoomphoto'),
      'attr' : 'src'
    },
    {
      'key'  : 'url',
      'elem' : document.getElementById('stoplink'),
      'attr' : 'href'
    },
    {
      'key'  : 'url',
      'elem' : document.getElementById('doneURL'),
      'attr' : 'href'
    },
    {
      'key'  : 'url',
      'elem' : document.getElementById('next'),
      'attr' : 'href'
    }
  ];
  
  var stop = tourStops[tourStopIndex];

  for (var i = 0; i < stopElems.length; i++) {
    var element = stopElems[i]['elem'];
    
    if (element) {
      var attr = stopElems[i]['attr'];
      var value = stop[stopElems[i]['key']];
      
      if (attr == 'innerHTML') {
        element.innerHTML = value;
      } else if (attr == 'src') {
        element.src = value;
      } else if (attr == 'href') {
        element.href = value;
      }
    }
    
    // select marker
    for (var j = 0; j < tourStops.length; j++) {
      var icon = tourStops[j]['defaultIcon'];
      if (j == tourStopIndex) {
        icon = tourIcons['current'];
      }
      tourStops[j]['marker'].setIcon(getMarkerImage(icon));
    }
  }
  selectedStopIndex = tourStopIndex;
}

function getMarkerImage(icon) {
  return new google.maps.MarkerImage(icon['src'], null, null,
    new google.maps.Point(icon['anchor'][0], icon['anchor'][1]),
    new google.maps.Size(icon['size'][0], icon['size'][1]));
}

function confirmStopChange() {
  if (selectedStopIndex != currentStopIndex) {
    $direction = (selectedStopIndex < currentStopIndex) ? 'back' : 'ahead';
    $count = Math.abs(selectedStopIndex - currentStopIndex);
    
    if (!confirm("Are you sure you want to jump "+$direction+" "+$count+" stop"+($count > 1 ? "s" : "")+"?")) {
      selectStop(currentStopIndex);
      return false;  // let the user try again
    }
  }
  return true;
}

function zoomUpDown(strID) {
  var objZoomup = document.getElementById(strID);
  if(objZoomup) {
    var strZoomupClass = objZoomup.className;
    if(strZoomupClass.indexOf("zoomed")>-1) {
      strZoomupClass = strZoomupClass.replace(" zoomed", "");
      objZoomup.className = strZoomupClass;
    } else {
      objZoomup.className+=" zoomed";
    }
  }
}

function changeSlide(tab, dir) {
  var selectedIndex = 0;
  var count = 0;

  for (var i = 0; i < 100; i++) {
    var dot = document.getElementById('slidedot_'+tab+'_'+i);
    var slide = document.getElementById('slide_'+tab+'_'+i);
    if (!dot || !slide) { break; }
    
    if (hasClass(dot, 'active')) {
      selectedIndex = i;
    }
    count++;
  }
  
  if (dir == 'prev' && selectedIndex > 0) {
    selectedIndex--;
  } else if (dir == 'next' && selectedIndex < count-1) {
    selectedIndex++;
  } else {
    return;
  }
  
  for (var i = 0; i < count; i++) {
    var dot = document.getElementById('slidedot_'+tab+'_'+i);
    var slide = document.getElementById('slide_'+tab+'_'+i);
    
    if (i == selectedIndex) {
      addClass(dot, 'active');
      addClass(slide, 'active');
    } else {
      removeClass(dot, 'active');
      removeClass(slide, 'active');
    }    
  }

  var next = document.getElementById('slidenext_'+tab);
  var prev = document.getElementById('slideprev_'+tab);
  if (selectedIndex <= 0) {
    removeClass(prev, 'active');
  } else {
    addClass(prev, 'active');
  }
  if (selectedIndex >= count-1) {
    removeClass(next, 'active');
  } else {
    addClass(next, 'active');
  }
}

function nextSlide(tab) {
  changeSlide(tab, 'next');
}

function previousSlide(tab) {
  changeSlide(tab, 'prev');
}

var videoFrameOriginalRatios = {};

function setupVideoFrames() {
  var iframes = document.getElementsByTagName('IFRAME');
  
  for (var i = 0; i < iframes.length; ++i) {
    var videoFrame = iframes[i];
    
    if (hasClass(videoFrame, 'videoFrame')) {
      var id = videoFrame.id;
    
      if (videoFrame.width && videoFrame.height) {
        videoFrameOriginalRatios[id] = videoFrame.height/videoFrame.width;
      } else {
        videoFrameOriginalRatios[id] = videoFrame.offsetHeight/videoFrame.offsetWidth;
      }
    }
  }
}

function resizeVideoFrames() {
  for (var id in videoFrameOriginalRatios) {
    var videoFrame = document.getElementById(id);
  
    var newWidth = document.body.offsetWidth - 8; // 4px left and right margin
    var newHeight = Math.round(newWidth*videoFrameOriginalRatios[id]);
    
    videoFrame.width = newWidth;
    videoFrame.height = newHeight;
    
    // Run a second time in case the scroll bar disappeared when we resized
    var newWidth = document.body.offsetWidth - 8; // 4px left and right margin
    var newHeight = Math.round(newWidth*videoFrameOriginalRatios[id]);
    
    videoFrame.width = newWidth;
    videoFrame.height = newHeight;
  }
}

function checkTourTab() {
  var anchor = location.hash;
  if (anchor.length > 1) {
    var possibleTabName = anchor.replace('#tab_', '');
    var possibleTab = document.getElementById(possibleTabName+'TourTab');
    if (possibleTab) {
      setTimeout(function() { 
        if (anchor == location.hash) {
          showTourTab(possibleTabName) 
        }
      }, 250);
    }
  }
}

function showTourTab(newTourTab) {
  // Displays the tab with ID strID
  if (currentTourTab != newTourTab) {
    var currentTab     = document.getElementById(currentTourTab+'TourTab');
    var currentTabbody = document.getElementById(currentTourTab+'TourTabbody');
    var newTab         = document.getElementById(newTourTab+'TourTab');
    var newTabbody     = document.getElementById(newTourTab+'TourTabbody');
  
    if (currentTab && currentTabbody && newTab && newTabbody) {
      removeClass(currentTab, 'active');
      removeClass(currentTabbody, 'active');
      addClass(newTab, 'active');
      addClass(newTabbody, 'active');
      currentTourTab = newTourTab; // Remember which is the currently displayed tab
      
      // fake resize event in case tab body was resized while hidden 
      if (document.createEvent) {
        var e = document.createEvent('HTMLEvents');
        e.initEvent('resize', true, true);
        window.dispatchEvent(e);
      
      } else if( document.createEventObject ) {
        var e = document.createEventObject();
        document.documentElement.fireEvent('onresize', e);
      }
      
      var hash = '#tab_'+newTourTab;
      if (window.history && window.history.pushState && window.history.replaceState && // Regexs from history js plugin
        !((/ Mobile\/([1-7][a-z]|(8([abcde]|f(1[0-8]))))/i).test(navigator.userAgent) || // disable for versions of iOS < 4.3 (8F190)
					 (/AppleWebKit\/5([0-2]|3[0-2])/i).test(navigator.userAgent))) { // disable for the mercury iOS browser and older webkit
        history.pushState({}, document.title, hash);
      } else {
        location.hash = hash;
      }
      
      onDOMChange();
    }
  }
}
