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


function showMap() {
  var mapElement = document.getElementById('map_canvas');
  if (mapElement) {
    var options = {
      'zoom' : 19, // make sure zoom level and bounds change when fitBounds is called
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
    var bounds = new google.maps.LatLngBounds();

    for (var id in mapMarkers) {
      setMapMarker(map, id, mapMarkers[id]);
      
      if (!mapPaths.length) {
        bounds.extend(new google.maps.LatLng(mapMarkers[id]['lat'], mapMarkers[id]['lon']));
      }
    }
    
    for (var id in mapPaths) {
      var mapPath = mapPaths[id];
      
      var path = [];
      for (var i = 0; i < mapPath.length; i++) {
        var pathPoint = new google.maps.LatLng(mapPath[i]['lat'], mapPath[i]['lon']);
        path.push(pathPoint);
        bounds.extend(pathPoint);
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
     
    // Restrict the zoom level while fitting to bounds
    // Listeners will definitely get called because the initial zoom level is 19
    map.setOptions({ minZoom: 12, maxZoom: 18 });
    var zoomChangeListener = google.maps.event.addListener(map, 'zoom_changed', function() {
      var zoomChangeBoundsListener = google.maps.event.addListener(map, 'bounds_changed', function(event) {
        map.setOptions({minZoom: null, maxZoom: null});
        google.maps.event.removeListener(zoomChangeBoundsListener);
      });
      google.maps.event.removeListener(zoomChangeListener);
    });
    map.fitBounds(bounds);
    
    var elem = document.getElementById('map_canvas');
    elem.style.visibility = 'visible';
    var elem = document.getElementById('map_loading');
    elem.style.display = 'none';
    
    if (markerUpdateURL.length && markerUpdateFrequency) {
      setInterval(function () { updateMarkers(map); }, markerUpdateFrequency*1000);
    }
    if (htmlUpdateURL.length && listUpdateFrequency) {
      setInterval(updateHTML, listUpdateFrequency*1000);
    }
    if (typeof onMapLoad != 'undefined') {
      // Allows sites to make additional changes to the map after it loads
      onMapLoad(map);
    }
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
        'icon'      : selfMarkerURL
      });
    } else {
      updateCurrentPosition.selfMarker.setPosition(location);
    }
  }, function() {}, { enableHighAccuracy: true });
}

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
      if(window.JSON) {
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
  
  /*if (navigator.geolocation && selfMarkerURL) {
    updateCurrentPosition(map);
  }*/
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
