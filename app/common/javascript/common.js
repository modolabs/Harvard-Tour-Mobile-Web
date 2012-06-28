String.prototype.strip = function() {
    return this.replace(/^\s+/, '').replace(/\s+$/, '');
}

function showTab(id) {
    var tabId = id+'-tab';
    var tabbodyId = id+'-tabbody';
    
    var tab = document.getElementById(tabId);
    var tabbody = document.getElementById(tabbodyId);
    if (!tab || !tabbody) { return; } // safety check
    
    var tabs = tab.parentNode.getElementsByTagName('li');
    if (!tabs) { return; } // safety check
    
    var tabBodies = tabbody.parentNode.childNodes;
    if (!tabBodies) { return; } // safety check
    
    // Display the tab body and hide others
    for (var i = 0; i < tabBodies.length; i++) {
        if (tabBodies[i].id == tabbodyId) {
            show(tabBodies[i].id);
        } else {
            hide(tabBodies[i].id);
        }
    }
    
    // Display the tab and hide others
    for (var i = 0; i < tabs.length; i++) {
        if (tabs[i].id == tabId) {
            addClass(tabs[i], 'active');
        } else {
            removeClass(tabs[i], 'active');
        }
    }
    
    // fake resize event in case tab body was resized while hidden 
    if (document.createEvent) {
        var e = document.createEvent('HTMLEvents');
        e.initEvent('resize', true, true);
        window.dispatchEvent(e);
    
    } else if( document.createEventObject ) {
        var e = document.createEventObject();
        document.documentElement.fireEvent('onresize', e);
    }
    
    onDOMChange();
}

function onOrientationChange() {
    /* the galaxy tab sends orientation change events constantly */
    if (typeof onOrientationChange.lastOrientation == 'undefined') {
        onOrientationChange.lastOrientation = null;
    }
    
    var newOrientation = getOrientation();
    
    if (newOrientation != onOrientationChange.lastOrientation) {
        rotateScreen();
        
        if (typeof onOrientationChange.callbackFunctions !== 'undefined') {
            for (var i = 0; i < onOrientationChange.callbackFunctions.length; i++) {
                onOrientationChange.callbackFunctions[i]();
            }
        }
        
        onOrientationChange.lastOrientation = newOrientation;
    }
}

function onResize() {
    if (typeof onResize.callbackFunctions !== 'undefined') {
        for (var i = 0; i < onResize.callbackFunctions.length; i++) {
            onResize.callbackFunctions[i]();
        }
    }
}

function addOnOrientationChangeCallback(callback) {
    if (typeof onOrientationChange.callbackFunctions == 'undefined') {
        onOrientationChange.callbackFunctions = [];
    }
    onOrientationChange.callbackFunctions.push(callback);
    
    if (typeof onResize.callbackFunctions == 'undefined') {
        onResize.callbackFunctions = [];
    }
    onResize.callbackFunctions.push(callback);
}

function setupOrientationChangeHandlers() {
    if (window.addEventListener) {
        window.addEventListener("orientationchange", onOrientationChange, false);
    } else if (window.attachEvent) {
        window.attachEvent("onorientationchange", onOrientationChange);
    }
    if (window.addEventListener) {
        window.addEventListener("resize", onResize, false);
    } else if (window.attachEvent) {
        window.attachEvent("onresize", onResize);
    }
}

function rotateScreen() {
  setOrientation(getOrientation());
  setTimeout(scrollToTop, 500);
}

function getOrientation() {
    if (typeof getOrientation.orientationIsFlipped == 'undefined') {
        // detect how we are detecting orientation
        getOrientation.orientationIsFlipped = false;
        
        if (!('orientation' in window)) {
            getOrientation.orientationMethod = 'size';
        } else {
            getOrientation.orientationMethod = 'orientation';
            var width = document.documentElement.clientWidth || document.body.clientWidth;
            var height = document.documentElement.clientHeight || document.body.clientHeight;
            
            /* at this point the method of orientation detection is not perfect */
            if (navigator.userAgent.match(/(PlayBook.+RIM Tablet|Xoom|Android 3\.\d)/)) {
                getOrientation.orientationIsFlipped = true;
            }
        }
    }

    switch (getOrientation.orientationMethod) {
        case 'size':
            var width = document.documentElement.clientWidth || document.body.clientWidth;
            var height = document.documentElement.clientHeight || document.body.clientHeight;

            return (width > height) ? 'landscape' : 'portrait';
            break;

        case 'orientation':
            switch (window.orientation) {
                case 0:
                case 180:
                    return getOrientation.orientationIsFlipped ? 'landscape' : 'portrait';
                    break;
                
                case 90:
                case -90:
                    return getOrientation.orientationIsFlipped ? 'portrait': 'landscape';
                    break;
            }
    }
}

function setOrientation(orientation) {
    var body = document.getElementsByTagName("body")[0];
 
 //remove existing portrait/landscape class if there
    removeClass(body, 'portrait');
    removeClass(body, 'landscape');
    addClass(body, orientation);
}

// Localized ajax loading and error content
// takes either an element or an id
function showAjaxLoadingMsg(e) {
    if (typeof e == 'string') {
        e = document.getElementById(element);
    }
	if (e) {
		e.innerHTML = AJAX_CONTENT_LOADING_HTML;
	}
	onDOMChange();
}

function showAjaxErrorMsg(e) {
    if (typeof e == 'string') {
        e = document.getElementById(element);
    }
	if (e) {
		e.innerHTML = AJAX_CONTENT_ERROR_HTML;
	}
	onDOMChange();
}

function hide(strID) {
// Hides the object with ID strID 
	var objToHide = document.getElementById(strID);
	if(objToHide) {
		objToHide.style.display = "none";
	}
	
	onDOMChange();
}

function show(strID) {
// Displays the object with ID strID 
	var objToHide = document.getElementById(strID);
	if(objToHide) {
		objToHide.style.display = "block";
	}
	
	onDOMChange();
}

function showHideFull(objContainer) {
	var strClass = objContainer.className;
	if(strClass.indexOf("collapsed") > -1) {
		strClass = strClass.replace("collapsed","expanded");
	} else {
		strClass = strClass.replace("expanded","collapsed");
	}
	objContainer.className = strClass;
	objContainer.blur();
	
	onDOMChange();
}

function clearField(objField,strDefault) {
// Clears the placeholder text in an input field if it matches the default string - fixes a bug in Android
	if((objField.value==strDefault) || (objField.value=="")) {
		objField.value="";
	}
}

// Android doesn't respond to onfocus="clearField(...)" until the 
// input field loses focus
function androidPlaceholderFix(searchbox) {
    // this forces the search box to display the empty string
    // instead of the place holder when the search box takes focus
    if (searchbox.value == "") {
        searchbox.value = "";
    }
}

function getCookie(name) {
  var cookie = document.cookie;
  var result = "";
  var start = cookie.indexOf(name + "=");
  if (start > -1) {
    start += name.length + 1;
    var end = cookie.indexOf(";", start);
    if (end < 0) {
      end = cookie.length;
    }
    result = unescape(cookie.substring(start, end));
  }
  return result;
}

function setCookie(name, value, expireseconds, path) {
  var exdate = new Date();
  exdate.setTime(exdate.getTime() + (expireseconds * 1000));
  var exdateclause = (expireseconds == 0) ? "" : "; expires=" + exdate.toGMTString();
  var pathclause = (path == null) ? "" : "; path=" + path;
  document.cookie = name + "=" + escape(value) + exdateclause + pathclause;
}

function getCookieArrayValue(name) {
  var value = getCookie(name);
  if (value && value.length) {
    return value.split('@@');
  } else {
    return new Array();
  }
}

function setCookieArrayValue(name, values, expireseconds, path) {
  var value = '';
  if (values && values.length) {
    value = values.join('@@');
  }
  setCookie(name, value, expireseconds, path);
}

function hasClass(ele,cls) {
    return ele.className.match(new RegExp('(\\s|^)'+cls+'(\\s|$)'));
}
        
function addClass(ele,cls) {
    if (!this.hasClass(ele,cls)) ele.className += " "+cls;
}

function removeClass(ele,cls) {
    if (hasClass(ele,cls)) {
        var reg = new RegExp('(\\s|^)'+cls+'(\\s|$)');
        ele.className=ele.className.replace(reg,' ').strip();
    }
}
        
function toggleClass(ele, cls) {
    if (hasClass(ele, cls)) {
        removeClass(ele, cls);
    } else {
        addClass(ele, cls);
    }
}

// Share-related functions
function showShare() {
    if (!document.getElementById("sharesheet")) {
        return;
    }
	document.getElementById("sharesheet").style.display="block";
	var iframes = document.getElementsByTagName('iframe');
	for (var i=0; i<iframes.length; i++) {
	    iframes[i].style.visibility = 'hidden';
	    iframes[i].style.height = '0';
	}
	window.scrollTo(0,0);
}
function hideShare() {
    if (!document.getElementById("sharesheet")) {
        return;
    }
	document.getElementById("sharesheet").style.display="none";
	var iframes = document.getElementsByTagName('iframe');
	for (var i=0; i<iframes.length; i++) {
	    iframes[i].style.visibility = 'visible';
	    iframes[i].style.height = '';
	}
}

// Bookmarks
function toggleBookmark(name, item, expireseconds, path, bookmarkId) {
  // facility for module to respond to bookmark state change
  if (typeof moduleBookmarkWillToggle != 'undefined') {
    $result = moduleBookmarkWillToggle(name, item, expireseconds, path);
    if ($result === false) { return; }
  }

  if (!bookmarkId) {
    bookmarkId = "bookmark";
  }
  var bookmark = document.getElementById(bookmarkId);
  toggleClass(bookmark, "on");
  var items = getCookieArrayValue(name);
  var newItems = new Array();
  if (items.length == 0) {
    newItems[0] = item;
  } else {
    var found = false;
    for (var i = 0; i < items.length; i++) {
      if (items[i] == item) {
        found = true;
      } else {
        newItems.push(items[i]);
      }
    }
    if (!found) {
      newItems.push(item);
    }
  }
  setCookieArrayValue(name, newItems, expireseconds, path);
  
  // facility for module to respond to bookmark state change
  if (typeof moduleBookmarkToggled != 'undefined') {
    moduleBookmarkToggled(name, item, expireseconds, path);
  }
}

// TODO this needs to handle encoded strings and parameter separators (&amp;)
if (typeof makeAPICall === 'undefined' && typeof jQuery === 'undefined') {
  function makeAPICall(type, module, command, data, callback) {
    var urlParts = [];
    for (var param in data) {
      urlParts.push(param + "=" + data[param]);
    }
    url = URL_BASE + API_URL_PREFIX + '/' + module + '/' + command + '?' + urlParts.join('&');
    var handleError = function(errorObj) {}

    var httpRequest = new XMLHttpRequest();
    httpRequest.open("GET", url, true);
    httpRequest.onreadystatechange = function() {
      if (httpRequest.readyState == 4 && httpRequest.status == 200) {
        var obj;
        if (window.JSON) {
            obj = JSON.parse(httpRequest.responseText);
            // TODO: catch SyntaxError
        } else {
            obj = eval('(' + httpRequest.responseText + ')');
        }
        if (obj !== undefined) {
          if ("response" in obj) {
            callback(obj["response"]);
          }

          if ("error" in obj && obj["error"] !== null) {
            handleError(obj["error"]);
          } else {
            handleError("response not found");
          }
        } else {
          handleError("failed to parse response");
        }
      }
    }
    httpRequest.send(null);
  }
}

function ajaxContentIntoContainer(options) {
    if (typeof options != 'object') { return; } // safety
    
    if (typeof ajaxContentIntoContainer.pendingRequests == 'undefined') {
        ajaxContentIntoContainer.pendingRequests = new Array();
    }
    
    var _removeRequestsForContainer = function (container) {
        // go backwards so removing items doesn't cause us to skip requests
        for (var i = ajaxContentIntoContainer.pendingRequests.length-1; i >= 0; i--) {
            if (ajaxContentIntoContainer.pendingRequests[i].options.container == container) {
                ajaxContentIntoContainer.pendingRequests[i].httpRequest.abort();
                ajaxContentIntoContainer.pendingRequests.splice(i, 1);
            }
        }
    }
    
    var _removeCompletedRequest = function (httpRequest) {
        for (var i = 0; i < ajaxContentIntoContainer.pendingRequests.length; i++) {
            if (ajaxContentIntoContainer.pendingRequests[i].httpRequest == httpRequest) {
                ajaxContentIntoContainer.pendingRequests.splice(i, 1);
                break;
            }
        }
    }
   
    var defaults = {
        url: null, 
        container: null, 
        timeout: 60, 
        addAjaxParameter: true,
        loadMessage: true,
        errorMessage: true,
        success: function () {},
        error: function (code) {} 
    };
    for (var i in defaults) {
        if (typeof options[i] == 'undefined') {
            options[i] = defaults[i];
        }
    }
    if (!options.url || !options.container) { return; } // safety
    
    if (options.addAjaxParameter && options.url.search(/[\?\&]ajax=/) < 0) {
        options.url += (options.url.search(/\?/) < 0 ? "?" : "&")+"ajax=1";
    }
    
    _removeRequestsForContainer(options.container);
    
    var httpRequest = new XMLHttpRequest();
    httpRequest.open("GET", options.url, true);
    
    var requestTimer = setTimeout(function() {
        // some browsers set readyState to 4 on abort so remove handler first
        httpRequest.onreadystatechange = function() { };
        httpRequest.abort();
        
        options.error(408); // http request timeout status code
    }, options.timeout * 1000);
    
    httpRequest.onreadystatechange = function() {
        // return if still in progress
        if (httpRequest.readyState != 4) { return; }
        
        // Got answer, don't abort
        clearTimeout(requestTimer);
        
        if (httpRequest.status == 200) { // Success
            options.container.innerHTML = "";
            
            // innerHTML outside of DOM hierarchy to avoid drawing issues
            var div = document.createElement("div");
            div.innerHTML = httpRequest.responseText;
            
            // copy elements so we can remove without changing list
            // don't clone because then some browsers execute onload on images twice
            var children = [];
            for (var i = 0; i < div.childNodes.length; i++) {
                children.push(div.childNodes[i]);
            }
            
            // Manually appendChild elements so scripts get evaluated
            for (var i = 0; i < children.length; i++) {
                div.removeChild(children[i]);
                
                if (children[i].nodeName == "SCRIPT") {
                    document.body.appendChild(children[i]);
                } else if (children[i].nodeName == "STYLE") {
                    document.getElementsByTagName("head")[0].appendChild(children[i]);
                } else {
                    options.container.appendChild(children[i]);
                }
            }
            
            options.success();
            
        } else {
            if (options.errorMessage) {
                showAjaxErrorMsg(options.container);
            }
            options.error(httpRequest.status);
        }
        
        _removeCompletedRequest(httpRequest);
    };
    
    if (options.loadMessage) {
        showAjaxLoadingMsg(options.container);
    }
    
    httpRequest.send(null);
    
    ajaxContentIntoContainer.pendingRequests.push({
        'options'     : options,
        'httpRequest' : httpRequest
    });
}

function getCSSValue(element, key) {
    if (window.getComputedStyle) {
        return document.defaultView.getComputedStyle(element, null).getPropertyValue(key);
        
    } else if (element.currentStyle) {
        if (key == 'float') { 
            key = 'styleFloat'; 
        } else {
            var re = /(\-([a-z]){1})/g; // hyphens to camel case
            if (re.test(key)) {
                key = key.replace(re, function () {
                    return arguments[2].toUpperCase();
                });
            }
        }
        return element.currentStyle[key] ? element.currentStyle[key] : null;
    }
    return '';
}

function getCSSHeight(element) {
    return element.offsetHeight
        - parseFloat(getCSSValue(element, 'border-top-width')) 
        - parseFloat(getCSSValue(element, 'border-bottom-width'))
        - parseFloat(getCSSValue(element, 'padding-top'))
        - parseFloat(getCSSValue(element, 'padding-bottom'));
}
