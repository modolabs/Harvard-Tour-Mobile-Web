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
