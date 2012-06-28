function loadSection(select, page) {
    redirectTo(page, { "term" : select.value });
}

function onAjaxContentLoad() {
    onDOMChange();
}

// needs to be overridden by tablet
function scrollContentToTop() {
    scrollToTop();
}

function switchPage(link, contentURL) {
    var element = link.parentNode;
    while (element) {
        if (hasClass(element, "tabbody")) {
            break;
        }
        element = element.parentNode;
    }
    if (!element) { return; }
    
    scrollContentToTop();
    
    ajaxContentIntoContainer({ 
        url: contentURL, // the url to get the page content from 
        container: element, // the container to dump the content into 
        timeout: 30, // how long to wait for the server before returning an error 
        success: function() {
            onAjaxContentLoad();
        },
        error: function(e) {
            onAjaxContentLoad();
        }
    });
}

function loadTab(tabId, contentURL) {
    //console.log('loading ' + tab + ' from ' + contentURL);
    var element = document.getElementById(tabId+'-tabbody');
    
    if (element && !hasClass(element, 'loaded')) {
        addClass(element, 'loaded');
        
        ajaxContentIntoContainer({ 
            url: contentURL, // the url to get the page content from 
            container: element, // the container to dump the content into 
            timeout: 30, // how long to wait for the server before returning an error 
            success: function() {
                onAjaxContentLoad();
            },
            error: function(e) {
                removeClass(element, 'loaded');
                onAjaxContentLoad();
            }
        });
    }
}

function updateGroupTab(clicked, tabId, contentURL) {
    var groupList = document.getElementById(tabId + '-tabstrip');
    var element = document.getElementById(tabId + '-content');
    
    var items = groupList.getElementsByTagName('li');
    for (var i = 0; i < items.length; i++) {
        items[i].className = items[i] == clicked.parentNode ? 'active' :'';
    }
    
    ajaxContentIntoContainer({ 
        url: contentURL + '&ajaxgroup=1', // the url to get the page content from 
        container: element, // the container to dump the content into 
        timeout: 30, // how long to wait for the server before returning an error 
        success: function() {
            onAjaxContentLoad();
        },
        error: function(e) {
            onAjaxContentLoad();
        }
    });
    
    return false;
}

function loadFolderCount(childImage, contentURL) {
    var container = childImage.parentNode;
    if (container && !hasClass(container, 'loadstarted')) {
        addClass(container, 'loadstarted');
        
        ajaxContentIntoContainer({ 
            url: contentURL, // the url to get the page content from 
            container: container, // the container to dump the content into 
            timeout: 30, // how long to wait for the server before returning an error 
            success: function() {
                onAjaxContentLoad();
            },
            error: function(e) {
                onAjaxContentLoad();
            }
        });
    }
}
