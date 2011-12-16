function paramStringToHash(queryString) {
  var params = {};

  if (queryString.length > 1) {
    queryString = queryString.substring(1, queryString.length);
    
    var queryParts = queryString.split('&');
    for (var i = 0; i < queryParts.length; i++) {
      var paramParts = queryParts[i].split('=');
      if (paramParts.length > 1) {
        params[paramParts[0]] = paramParts[1];
      } else if (paramParts.length) {
        params[paramParts[0]] = '';
      }
    }
  }
  
  return params;
}

function hashToParamString(params) {
  queryString = '';
  for (var key in params) {
    var separator = '&';
    if (!queryString.length) { separator = '?'; }
    queryString += separator+key+'='+params[key];
  }
  
  return queryString;
}

function autoReload(reloadTime) {
  var params = paramStringToHash(window.location.search);
  
  if (params['y']) {
    setTimeout(function () {
      window.scrollTo(0, params['y']);
    }, 500);
  }
  
  setTimeout(function () {
    var date = new Date();
    params['t'] = date.getTime(); // prevent caching
  
    var tabbodies = document.getElementById("tabbodies");
    if (tabbodies) {
      var tabs = tabbodies.childNodes;
      for(var i = 0; i < tabs.length; i++) {
        if (tabs[i].className.match(/tabbody/) && tabs[i].style.display != "none") {
          params['tab'] = tabs[i].id.replace('Tab', '');
          break;
        }
      }
    }
    
    params['y'] = 0;
    if (typeof window.pageYOffset != 'undefined') {
      params['y'] = window.pageYOffset;
      
    } else if (typeof document.body.scrollTop != 'undefined') {
      params['y'] = document.body.scrollTop;
    }
    
    var href = window.location.protocol+'//'+window.location.hostname;
    if (window.location.port.length) {
      href += ':'+window.location.port;
    }
    href += window.location.pathname + 
      hashToParamString(params) + 
      window.location.hash;
    
    window.location.replace(href);
  }, reloadTime * 1000);
  
  var counter = document.getElementById("reloadCounter");
  counter.innerHTML = reloadTime;
  setInterval(function () {
    counter.innerHTML = Math.max(--reloadTime, 0);
  }, 1000);
}

var mapResizeHandler;

var updateMapDimensionsTimeoutIds = [];
function clearUpdateMapDimensionsTimeouts() {
    for(var i = 0; i < updateMapDimensionsTimeoutIds.length; i++) {
        window.clearTimeout(updateMapDimensionsTimeoutIds[i]);
    }
    updateMapDimensionsTimeoutIds = [];
}

// Prevent firebombing the browser with Ajax calls on browsers which fire lots
// of resize events
function handleMapResize() {
    clearUpdateMapDimensionsTimeouts();
    
    if (typeof mapResizeHandler != 'undefined') {
      updateMapDimensionsTimeoutIds.push(window.setTimeout(mapResizeHandler, 200));
      updateMapDimensionsTimeoutIds.push(window.setTimeout(mapResizeHandler, 500));
    }
}

// id7 doesn't understand window.innerWidth and window.innerHeight
function getWindowHeight() {
    if (window.innerHeight !== undefined) {
        return window.innerHeight;
    } else {
        return document.documentElement.clientHeight;
    }
}

function getWindowWidth() {
    if (window.innerWidth !== undefined) {
        return window.innerWidth;
    } else {
        return document.documentElement.clientWidth;
    }
}

function findPosY(obj) {
    // Function for finding the y coordinate of the object passed as an argument.
    // Returns the y coordinate as an integer, relative to the top left origin of the document.
    var intCurlTop = 0;
    if (obj && obj.offsetParent) {
        while (obj.offsetParent) {
            intCurlTop += obj.offsetTop;
            obj = obj.offsetParent;
        }
    }
    return intCurlTop;
}

function doUpdateContainerDimensions() {
    if (isFullscreen) {
        var container = document.getElementById("container");
        if (container) {
            var newWidth = getWindowWidth() + "px";
            var newHeight = getWindowHeight() + "px";
            
            // check to see if the container height and width actually changed
            if (container.style && container.style.width && container.style.width == newWidth
                                && container.style.height && container.style.height == newHeight) {
                
                return; // nothing changed so exit early
            }
            
            container.style.width = newWidth;
            container.style.height = newHeight;
            container.style.minHeight = "0"; // so we don't get extra space at the bottom
        }
    } else if (fitMapToScreen) {
        var mapimage = document.getElementById("map_dynamic");
        var maptab = document.getElementById("mapTab");
        if (mapimage) {
            var mapcontainer = document.getElementById("tabbodies");
            if (mapcontainer === null) {
                mapcontainer = document.getElementById("mapcontainer")
            }
            var topoffset = findPosY(mapcontainer);
            var bottomoffset = 0;
            // TODO lots of hard coding here, need better way to get these values
            var zoomControlsHeight = 45;
            var footernav = document.getElementById("footernav");
            if (footernav) {
                bottomoffset = 75;
            }
            var tabHeight = getWindowHeight() - topoffset - bottomoffset;
            var tabPadding = 8 * 2;
            if (maptab) {
                maptab.style.height = (tabHeight - tabPadding) + "px";
                maptab.style.minHeight = "0"; // so we don't get extra space at the bottom
            }
            mapimage.style.height = (tabHeight - zoomControlsHeight - tabPadding) + "px";
            mapimage.style.minHeight = "0"; // so we don't get extra space at the bottom
        }
    }
}

function showMap() {
  var mapElement = document.getElementById('map_canvas');
  if (mapElement) {
    var options = {
      'zoom' : 17,
      'mapTypeId' : google.maps.MapTypeId.ROADMAP,
      'mapTypeControl' : false,
      'panControl' : false,
      'streetViewControl' : false,
      'zoomControl' : false
    };
    
    var map = new google.maps.Map(mapElement, options);

    for (var id in mapMarkers) {
      setMapMarker(map, id, mapMarkers[id]);
    }
    
    for (var id in mapPaths) {
      var mapPath = mapPaths[id];
      
      var path = [];
      for (var i = 0; i < mapPath.length; i++) {
        var pathPoint = new google.maps.LatLng(mapPath[i]['lat'], mapPath[i]['lon']);
        path.push(pathPoint);
      }
      
      mapPaths[id]['polyline'] = new google.maps.Polyline({
        'clickable'     : false,
        'map'           : map, 
        'path'          : path,
        'strokeColor'   : mapPathColor,
        'strokeOpacity' : 1,
        'strokeWeight'  : 2
      });
    }
    
    fitMapBounds(map, null);
    
    mapResizeHandler = function () {
      if (typeof doUpdateContainerDimensions != 'undefined') {
        doUpdateContainerDimensions();
      }
      
      google.maps.event.trigger(map, 'resize');
      fitMapBounds(map, null);
    };
    
    // Map controls
    document.getElementById("zoomin").onclick = function () {
      map.setZoom(map.getZoom() + 1);
    };
    document.getElementById("zoomout").onclick = function () {
      map.setZoom(map.getZoom() - 1);
    };
    document.getElementById("recenter").onclick = function () {
      fitMapBounds(map, null);
    };
    var locateMeButton = document.getElementById("locateMe");
    if ("geolocation" in navigator) {
      initGeolocation(map);
    } else {
      locateMeButton.parentNode.removeChild(locateMeButton);
      document.getElementById("zoomout").style.left = "35%";
      document.getElementById("recenter").style.left = "64%";
    }
    
    // Make map visible
    var elem = document.getElementById('map_canvas');
    elem.style.visibility = 'visible';
    var elem = document.getElementById('map_loading');
    elem.style.display = 'none';
    
    if (markerUpdateURL.length && markerUpdateFrequency) {
      setInterval(function () { updateMarkers(map, locateMeButton); }, markerUpdateFrequency*1000);
    }
    if (typeof onMapLoad != 'undefined') {
      // Allows sites to make additional changes to the map after it loads
      onMapLoad(map);
    }
  }
}

function initListUpdate() {
  if (htmlUpdateURL.length && listUpdateFrequency) {
    setInterval(updateHTML, listUpdateFrequency*1000);
  }
}

// shrink bounds to compensate for padding introduced by google maps
// when we fit to these bounds
function trimBoundsPadding(bounds) {
  var sw = bounds.getSouthWest();
  var ne = bounds.getNorthEast();
  
  var lat1 = sw.lat();
  var lng1 = sw.lng();
  var lat2 = ne.lat();
  var lng2 = ne.lng();
  
  var dx = (lng1 - lng2) / 2.;
  var dy = (lat1 - lat2) / 2.;
  var cx = (lng1 + lng2) / 2.;
  var cy = (lat1 + lat2) / 2.;
  
  lat1 = cy + dy / 1.3;
  lng1 = cx + dx / 1.3;
  lat2 = cy - dy / 1.3;
  lng2 = cx - dx / 1.3;
  
  sw = new google.maps.LatLng(lat1, lng1);
  ne = new google.maps.LatLng(lat2, lng2);
  
  return new google.maps.LatLngBounds(sw, ne);
}

function fitMapBounds(map, userLocation) {
  var bounds = new google.maps.LatLngBounds();
  
  // create a bounds object for the mapPaths (if any)
  // do this once because the paths don't change
  if (typeof fitMapBounds.pathBounds == 'undefined') {
    fitMapBounds.pathBounds = null;
    for (var id in mapPaths) {
      for (var i = 0; i < mapPaths[id].length; i++) {
        var pathPoint = new google.maps.LatLng(
          mapPaths[id][i]['lat'], mapPaths[id][i]['lon']);
        if (fitMapBounds.pathBounds === null) {
          fitMapBounds.pathBounds = new google.maps.LatLngBounds();
        }
        fitMapBounds.pathBounds.extend(pathPoint);
      }
    }
  }
  
  if (fitMapBounds.pathBounds !== null) {
    // If we have a map path, use that for the bounds
    // sometimes shuttles sit in parking lots with gps on
    // and we don't want to zoom out to show those
    bounds.union(fitMapBounds.pathBounds);
    
  } else {
    // no map path, fit to markers instead
    for (var id in mapMarkers) {
      bounds.extend(new google.maps.LatLng(mapMarkers[id]['lat'], mapMarkers[id]['lon']));
    }
  }
  
  // if the caller provided a user location, include it
  if (userLocation !== null) {
    bounds.extend(userLocation);
  }
  
  bounds = trimBoundsPadding(bounds); // Work around Google's excess bounds padding
  
  // Restrict the zoom level while fitting to bounds
  // Listeners will definitely get called because we set the zoom level to 19
  map.setOptions({ minZoom: 10, maxZoom: 18 });
  map.setZoom(19);
  var zoomChangeListener = google.maps.event.addListener(map, 'zoom_changed', function() {
    var zoomChangeBoundsListener = google.maps.event.addListener(map, 'bounds_changed', function(event) {
      map.setOptions({minZoom: null, maxZoom: null});
      google.maps.event.removeListener(zoomChangeBoundsListener);
    });
    google.maps.event.removeListener(zoomChangeListener);
  });
  map.fitBounds(bounds);
};

function updateHTML() {
  var container = document.getElementById('ajaxcontainer');
  if (container) {
    var httpRequest = new XMLHttpRequest();
    httpRequest.open("GET", htmlUpdateURL, true);
    httpRequest.onreadystatechange = function() {
      if (httpRequest.readyState == 4 && httpRequest.status == 200 && httpRequest.responseText) {
        container.innerHTML = httpRequest.responseText;
      }
    }
    httpRequest.send(null);
  }

  // Update the time on the less frequently updated html      
  var refreshText = document.getElementById('lastrefreshtime');
  if (refreshText) {
    var currentTime = new Date();
    var hours = currentTime.getHours();
    var minutes = currentTime.getMinutes();
  
    var suffix = hours < 12 ? 'am' : 'pm';
    if (hours > 12) { hours -= 12; }
    if (minutes < 10) { minutes = '0'+minutes; }
    
    refreshText.innerHTML = hours+':'+minutes+'<span class="ampm">'+suffix+'</span>';
  }
}

function updateMarkers(map) {
  var httpRequest = new XMLHttpRequest();
  httpRequest.open("GET", markerUpdateURL, true);
  httpRequest.onreadystatechange = function() {
    if (httpRequest.readyState == 4 && httpRequest.status == 200) {
      var obj;
      if (window.JSON) {
          obj = JSON.parse(httpRequest.responseText);
      } else {
          obj = eval('(' + httpRequest.responseText + ')');
      }
      var newMapMarkers = obj['response'];
      
      // used to identify markers to remove
      for (var id in mapMarkers) {
        mapMarkers[id]['found'] = false;
      }
      
      for (var id in newMapMarkers) {
        setMapMarker(map, id, newMapMarkers[id]);
        mapMarkers[id]['found'] = true; // remember we saw this
      }
      
      // remove markers which were not found
      for (var id in mapMarkers) {
        if (!mapMarkers[id]['found']) {
          if ('marker' in mapMarkers[id]) {
            mapMarkers[id]['marker'].setMap(null);
          }
        }
        delete(mapMarkers[id]['found']);
      }
    }
  }
  httpRequest.send(null);
}

function setMapMarker(map, id, attrs) {
  if (!(id in mapMarkers)) {
    mapMarkers[id] = attrs;
  }
  
  if (!('marker' in mapMarkers[id])) {
    mapMarkers[id]['marker'] = new google.maps.Marker({
      'clickable' : false,
      'map'       : map,
      'position'  : new google.maps.LatLng(attrs['lat'], attrs['lon']),
      'title'     : attrs['title'],
      'icon'      : attrs['imageURL'],
      'flat'      : false
    });
    
    if (typeof onCreateMapMarker != 'undefined') {
      // Allows sites to make additional changes to the map pins after they are created
      onCreateMapMarker(map, mapMarkers[id]);
    }
    
  } else {
    if (mapMarkers[id]['lat'] != attrs['lat'] || mapMarkers[id]['lon'] != attrs['lon']) {
      mapMarkers[id]['marker'].setPosition(new google.maps.LatLng(attrs['lat'], attrs['lon']));
      mapMarkers[id]['lat'] = attrs['lat'];
      mapMarkers[id]['lon'] = attrs['lon'];
    }
    
    if (mapMarkers[id]['imageURL'] != attrs['imageURL']) {
      mapMarkers[id]['marker'].setIcon(attrs['imageURL']);
      mapMarkers[id]['imageURL'] = attrs['imageURL'];
    }
    
    if (mapMarkers[id]['marker'].getMap() != map) {
      mapMarkers[id]['marker'].setMap(map);
    }
  
    if (typeof onUpdateMapMarker != 'undefined') {
      // Allows sites to make additional changes to the map pins after they are updated
      onUpdateMapMarker(map, mapMarkers[id]);
    }
  }
}

function initGeolocation(map) {
  var locateMeButton = document.getElementById("locateMe");
  
  if (!("geolocation" in navigator)) {
    locateMeButton.parentNode.removeChild(locateMeButton);
    return;  // no geolocation support
  }

  var locationWatchId = null;
  var firstLocationUpdate = false;
  var userLocationMarker = null;

  locateMeButton.onclick = function() {
    toggleClass(this, 'enabled');
    
    if (hasClass(this, 'enabled')) {
      firstLocationUpdate = true;
      
      locationWatchId = navigator.geolocation.watchPosition(
        function (location) {
          var position = new google.maps.LatLng(location.coords.latitude, location.coords.longitude);
      
          if (userLocationMarker === null) {
            userLocationMarker = new google.maps.Marker({
              'clickable' : false,
              'map'       : map, 
              'position'  : position,
              'flat'      : true,
              'icon'      : new google.maps.MarkerImage(
                userLocationMarkerURL,
                null, // original size
                null, // origin (0, 0)
                new google.maps.Point(8, 8),  // anchor
                new google.maps.Size(16, 16)) // scaled size
            });
          } else {
            if (userLocationMarker.getMap() === null) {
              userLocationMarker.setMap(map);
            }
            userLocationMarker.setPosition(position);
          }
          
          // only recenter on first location so we don't rubber band on scrolling
          // include current map center on map so zoom/pan is not as confusing
          if (firstLocationUpdate) {
            fitMapBounds(map, position);
            firstLocationUpdate = false;
          }
        },
        function (error) {}, // ignore errors
        { enableHighAccuracy: true }
      );
      
    } else if (locationWatchId != null) {
      // remove marker from map and stop watching location
      if (userLocationMarker !== null) {
        userLocationMarker.setMap(null);
      }
      navigator.geolocation.clearWatch(locationWatchId);
      locationWatchId = null;
    }
  };
}
