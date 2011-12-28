var map;
var mapLoader;
var browseGroups = {};

function sortGroupsByDistance() {
    if ('geolocation' in navigator) {
        navigator.geolocation.getCurrentPosition(function(location) {
            var navCategories = document.getElementById("categories").children;
            for (var i = 0; i < navCategories.length; i++) {
                var category = navCategories[i];
                var categoryId = category.getAttribute("class");
                browseGroups[categoryId] = category;
            }

            makeAPICall(
                'GET', 'map', 'sortGroupsByDistance',
                {"lat": location.coords.latitude, "lon": location.coords.longitude},
                function(response) {
                    var sortedGroups = [];
                    for (var i = 0; i < response.length; i++) {
                        var id = response[i]["id"];
                        if (id in browseGroups) {
                            if ("distance" in response[i]) {
                                browseGroups[id].innerHTML = browseGroups[id].innerHTML + "<div class=\"smallprint\">" + response[i]["distance"] + "</div>";
                            }
                            sortedGroups.push(browseGroups[id]);
                        }
                    }
                    var navList = document.getElementById("categories");
                    if (navList.children.length == sortedGroups.length) {
                        while (navList.children.length > 0) {
                            navList.removeChild(navList.children[0]);
                        }
                        for (var i = 0; i < sortedGroups.length; i++) {
                            navList.appendChild(sortedGroups[i]);
                        }
                    }
                }
            );
        },
        function() {},
        {maximumAge:3600000, timeout:5000});
    }
}

////// expanding search bar

function submitMapSearch(form) {
    if (form.filter.value.length > 0) {
        mapLoader.clearMarkers();
        params = {'q': form.filter.value};
        if (form.group.value) {
            params['group'] = form.group.value;
        }
        if ('projection' in mapLoader) {
            params['projection'] = mapLoader.projection;
        }
        makeAPICall('GET', 'map', 'search', params, function(response) {
            hideSearchFormButtons();
            if (response.results.length > 0) {
                // TODO: make the "browse" button bring up results in a list
                var minLat = 10000000;
                var maxLat = -10000000;
                var minLon = 10000000;
                var maxLon = -10000000;
                for (var i = 0; i < response.results.length; i++) {
                    var markerData = response.results[i];
                    mapLoader.createMarker(
                        markerData.title, markerData.subtitle,
                        markerData.lat, markerData.lon, markerData.url);
                    minLat = Math.min(minLat, markerData.lat);
                    minLon = Math.min(minLon, markerData.lon);
                    maxLat = Math.max(maxLat, markerData.lat);
                    maxLon = Math.max(maxLon, markerData.lon);
                }
                mapLoader.setMapBounds(minLat, minLon, maxLat, maxLon);
            }
        });
        var addFilterToHref = function(link) {
            var reg = new RegExp('&?filter=.+(&|$)');
            if (link.href.match(reg)) {
                link.href = link.href.replace(reg, '&filter='+form.filter.value);
            } else {
                link.href = link.href + '&filter='+form.filter.value;
            }
        }
        var mapButton = document.getElementById("mapLink");
        if (mapButton) {
            addFilterToHref(mapButton);
        }
        var browseButton = document.getElementById("browseLink");
        if (browseButton) {
            addFilterToHref(browseButton);
        }
    }
}

function clearSearch(form) {
    form.filter.value = '';
}

function showSearchFormButtons() {
    var header = document.getElementById("header");
    addClass(header, "expanded");
    if (document.getElementById("campus-select")) {
        addClass(header, "multi-campus");
    } else {
        addClass(header, "single-campus");
    }
}

function hideSearchFormButtons() {
    var header = document.getElementById("header");
    removeClass(header, "expanded");
    if (document.getElementById("campus-select")) {
        removeClass(header, "multi-campus");
    } else {
        removeClass(header, "single-campus");
    }
}

///// window size

// ie7 doesn't understand window.innerWidth and window.innerHeight
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

// assuming only one of updateMapDimensions or updateContainerDimensions
// gets used so they can reference the same ids
// updateMapDimensions is called for static maps
// updateContainerDimensions is called for dynamic maps
var updateMapDimensionsTimeoutIds = [];
function clearUpdateMapDimensionsTimeouts() {
    for(var i = 0; i < updateMapDimensionsTimeoutIds.length; i++) {
        window.clearTimeout(updateMapDimensionsTimeoutIds[i]);
    }
    updateMapDimensionsTimeoutIds = [];
}

// resizing counterpart for dynamic maps
function updateContainerDimensions() {
    clearUpdateMapDimensionsTimeouts();
    var timeoutId = window.setTimeout(doUpdateContainerDimensions, 200);
    updateMapDimensionsTimeoutIds.push(timeoutId);
    timeoutId = window.setTimeout(doUpdateContainerDimensions, 500);
    updateMapDimensionsTimeoutIds.push(timeoutId);
    timeoutId = window.setTimeout(doUpdateContainerDimensions, 1000);
    updateMapDimensionsTimeoutIds.push(timeoutId);
}

function doUpdateContainerDimensions() {
    var mapimage = document.getElementById("mapimage");
    if (mapimage) {
        var topoffset = findPosY(mapimage);
        mapimage.style.height = (getWindowHeight() - topoffset) + "px";
    }

    if (typeof mapLoader.resizeMapOnContainerResize == 'function') {
        mapLoader.resizeMapOnContainerResize();
    }
}

function findPosY(obj) {
// Function for finding the y coordinate of the object passed as an argument.
// Returns the y coordinate as an integer, relative to the top left origin of the document.
    var intCurlTop = 0;
    if (obj.offsetParent) {
        while (obj.offsetParent) {
            intCurlTop += obj.offsetTop;
            obj = obj.offsetParent;
        }
    }
    return intCurlTop;
}



