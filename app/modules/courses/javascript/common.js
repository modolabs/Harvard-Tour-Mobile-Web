function loadSection(select, page) {
    window.location = "./" + page + "?term=" + select.value;
}

function onAjaxContentLoad() {
    onDOMChange();
}

function switchPage(tab, contentURL) {
    loadTab(tab, contentURL, true);
}

function loadTab(tabId, contentURL, force) {
    //console.log('loading ' + tab + ' from ' + contentURL);
    var element = document.getElementById(tabId+'-tabbody');
    
    if (force || !hasClass(element, 'loaded')) {
        element.innerHTML = AJAX_CONTENT_LOADING;
        
        ajaxContentIntoContainer({ 
            url: contentURL, // the url to get the page content from 
            container: element, // the container to dump the content into 
            timeout: 30, // how long to wait for the server before returning an error 
            success: function() {
                addClass(element, 'loaded');
                onAjaxContentLoad();
            },
            error: function(e) {
                element.innerHTML = AJAX_CONTENT_LOAD_FAILED;
                onAjaxContentLoad();
            }
        });
    }
}

function updateGroupTab(tab, index, contentURL) {
    var groupList = document.getElementById(tab + '-grouplist');
    var element = document.getElementById(tab + '-content');
    
    var items = groupList.getElementsByTagName('li');
    for (var i = 0; i < items.length; i++) {
        items[i].className = items[i].getAttribute('index') == index ? 'active' :'';
    }
    
    element.innerHTML = AJAX_CONTENT_LOADING;

    ajaxContentIntoContainer({ 
        url: contentURL + '&ajaxgroup=1', // the url to get the page content from 
        container: element, // the container to dump the content into 
        timeout: 30, // how long to wait for the server before returning an error 
        success: function() {
            onAjaxContentLoad();
        },
        error: function(e) {
            element.innerHTML = AJAX_CONTENT_LOAD_FAILED;
            onAjaxContentLoad();
        }
    });
    
    return false;
}
