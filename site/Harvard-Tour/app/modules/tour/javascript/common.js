function showMap(center, stops, tourIcons, stopOverviewMode) {
  var stopElems = [
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
    }
  ];


  var mapElement = document.getElementById('map_canvas');
  if (mapElement) {
    var options = {
      'zoom'      : 17,
      'center'    : new google.maps.LatLng(center['lat'], center['lon']),
      'mapTypeId' : google.maps.MapTypeId.ROADMAP,
      'mapTypeControlOptions' : { 
        'mapTypeIds' : [
          google.maps.MapTypeId.ROADMAP,
          google.maps.MapTypeId.SATELLITE
        ],
        'position' : google.maps.ControlPosition.TOP_LEFT,
        'style'    : google.maps.MapTypeControlStyle.HORIZONTAL_BAR
      },
      'panControl' : false,
      'streetViewControl' : false,
      'zoomControlOptions' : { 
        'position' : google.maps.ControlPosition.RIGHT_BOTTOM,
        'style'    : google.maps.ZoomControlStyle.SMALL
      }
    };
    
    var map    = new google.maps.Map(mapElement, options);  
    var bounds = new google.maps.LatLngBounds();

    for (var i = 0; i < stops.length; i++) {
      var stop = stops[i];
    
      var stopLatLng = new google.maps.LatLng(stop['lat'], stop['lon']);
      if (stop['current'] || stopOverviewMode) {
        bounds.extend(stopLatLng);
      }
      
      var icon = tourIcons['other'];
      if (stop['visited']) {
        icon = tourIcons['visited'];
      }
      stop['defaultIcon'] = icon; // remember non-current icon state
      
      if (stop['current']) {
        icon = tourIcons['current'];
      }
      
      stops[i]['marker'] = new google.maps.Marker({
        'map'      : map, 
        'position' : stopLatLng,
        'title'    : stop['title'],
        'icon'     : new google.maps.MarkerImage(icon)
      });
      stops[i]['marker'].tourStop = stop;
      stops[i]['marker'].tourStopIndex = i;
      
      if (stopOverviewMode) {
        google.maps.event.addListener(stops[i]['marker'], 'click', function() {
          var stop = this.tourStop;
          
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
            for (var j = 0; j < stops.length; j++) {
              var icon = stops[j]['defaultIcon'];
              if (j == this.tourStopIndex) {
                icon = tourIcons['current'];
              }
              stops[j]['marker'].setIcon(new google.maps.MarkerImage(icon));
            }
          }
        });
      }
    }
    map.panToBounds(bounds);
    
    navigator.geolocation.getCurrentPosition(function(position) {
      var location = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);

      var marker = new google.maps.Marker({
        'clickable' : false,
        'map'       : map, 
        'position'  : location,
        'icon'      : new google.maps.MarkerImage(tourIcons['self'])
      });
    });
    
    var elem = document.getElementById('map_canvas');
    elem.style.visibility = 'visible';
    var elem = document.getElementById('map_loading');
    elem.style.display = 'none';
  }
}

function confirmStop() {
  return confirm("Are you sure you want to end your Tour and return to the welcome screen?");
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
