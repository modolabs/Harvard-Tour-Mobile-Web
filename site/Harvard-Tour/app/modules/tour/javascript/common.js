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
    } else {
      windowHeight = document.documentElement.clientHeight; // ie7
    }
    
    mapcontainer.style.height = (windowHeight - headerHeight)+'px';
}

function showMap() {
  resizeMap();
  
  var mapElement = document.getElementById('map_canvas');
  if (mapElement) {
    var options = {
      'zoom'      : 17,
      'center'    : new google.maps.LatLng(centerCoords['lat'], centerCoords['lon']),
      'mapTypeId' : google.maps.MapTypeId.ROADMAP,
      'mapTypeControl' : false,
      /*'mapTypeControlOptions' : { 
        'mapTypeIds' : [
          google.maps.MapTypeId.ROADMAP,
          google.maps.MapTypeId.SATELLITE
        ],
        'position' : google.maps.ControlPosition.TOP_LEFT,
        'style'    : google.maps.MapTypeControlStyle.HORIZONTAL_BAR
      },*/
      'panControl' : false,
      'streetViewControl' : false,
      'zoomControlOptions' : { 
        'position' : google.maps.ControlPosition.RIGHT_BOTTOM,
        'style'    : google.maps.ZoomControlStyle.SMALL
      }
    };
    
    var map = new google.maps.Map(mapElement, options);  

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
        'icon'      : getMarkerImage(icon)
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
    if (fitToBounds.length > 2) {
      map.fitBounds(bounds);
    } else {
      map.panTo(bounds.getCenter());
    }
    
    /*navigator.geolocation.getCurrentPosition(function(position) {
      var location = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);

      var marker = new google.maps.Marker({
        'clickable' : false,
        'map'       : map, 
        'position'  : location
      });
    });*/
    
    var elem = document.getElementById('map_canvas');
    elem.style.visibility = 'visible';
    var elem = document.getElementById('map_loading');
    elem.style.display = 'none';
  }
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
      'elem' : document.getElementById('zoomup'),
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
  return new google.maps.MarkerImage(
    icon['src'], 
    new google.maps.Size(icon['realSize'][0], icon['realSize'][1]), 
    new google.maps.Point(0, 0),
    new google.maps.Point(icon['anchor'][0], icon['anchor'][1]),
    new google.maps.Size(icon['size'][0], icon['size'][1]));
}

function confirmStopChange() {
  if (selectedStopIndex != currentStopIndex) {
    if (!confirm("Are you sure you want to jump ahead in the tour?")) {
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

// Initalize the ellipsis event handlers
function setupStopList() {
  var stopEllipsizer = new ellipsizer();
  
  // cap at 100 divs to avoid overloading phone
  for (var i = 0; i < 100; i++) {
    var elem = document.getElementById('ellipsis_'+i);
    if (!elem) { break; }
    stopEllipsizer.addElement(elem);
  }
}
function setupSubtitleEllipsis() {
  var elem = document.getElementById('subtitleEllipsis');
  if (elem) {
    var subtitleEllipsizer = new ellipsizer();
    subtitleEllipsizer.addElement(elem);
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
  
  resizeVideoFrames();
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
