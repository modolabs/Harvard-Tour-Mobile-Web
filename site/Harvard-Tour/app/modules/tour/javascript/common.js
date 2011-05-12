function showMap(center, stops, selfIcon) {
  var mapElement = document.getElementById('map_canvas');
  if (mapElement) {
    setTimeout(function () {
      var options = {
        'zoom'      : 10,
        'center'    : new google.maps.LatLng(center['lat'], center['lon']),
        'mapTypeId' : google.maps.MapTypeId.ROADMAP
      };
      
      var map    = new google.maps.Map(mapElement, options);  
      var bounds = new google.maps.LatLngBounds();
  
      for (var i = 0; i < stops.length; i++) {
        var stop = stops[i];
      
        var stopLatLng = new google.maps.LatLng(stop['lat'], stop['lon']);
        bounds.extend(stopLatLng);
        
        var markerImage = new google.maps.MarkerImage(stop['icon']);
        var marker = new google.maps.Marker({
          'map'      : map, 
          'position' : stopLatLng,
          'title'    : stop['title'],
          'icon'     : markerImage
        });
        marker.infoWindow = new google.maps.InfoWindow({
          'content'  : '<div class="map_infowindow"><div class="map_name">'+stop['title']+'</div><div class="map_address">'+stop['subtitle']+'</div></div>'
        });
        google.maps.event.addListener(marker, 'click', function() {
          marker.infoWindow.open(map, marker);
        });
      }
      map.fitBounds(bounds);
      
      navigator.geolocation.getCurrentPosition(function(position) {
        var location = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);
        bounds.extend(location);

        var markerImage = new google.maps.MarkerImage(selfIcon);
        var marker = new google.maps.Marker({
          'clickable' : false,
          'map'       : map, 
          'position'  : location,
          'icon'      : markerImage
        });
        map.fitBounds(bounds);
      });
      
      var elem = document.getElementById('map_canvas');
      elem.style.visibility = 'visible';
      var elem = document.getElementById('map_loading');
      elem.style.display = 'none';
      
    }, 0);
  }
}
