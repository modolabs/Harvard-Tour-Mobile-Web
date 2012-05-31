function loadSection(select, page) {
    window.location = "./" + page + "?term=" + select.value;
}

var tabsLoaded = {};

function loadTab(tab, contentURL) {
    if (tab in tabsLoaded) {
        return;
    }
    updateTab(tab, contentURL);
}

function updateTab(tab, contentURL) {
    console.log('loading ' + tab + ' from ' + contentURL);
    var element = document.getElementById(tab + '-tabbody');
    ajaxContentIntoContainer({ 
        url: contentURL, // the url to get the page content from 
        container: element, // the container to dump the content into 
        timeout: 10, // how long to wait for the server before returning an error 
        success: function() { tabsLoaded[tab] = true; },
        error: function(e) { console.log(e); element.innerHTML = 'Error loading content';}
    });
}

function updateGroupTab(tab, index, contentURL) {
    var groupList = document.getElementById(tab + '-grouplist');
    var element =   document.getElementById(tab + '-content');
    
    var items = groupList.getElementsByTagName('li');
    for (var i=0; i<items.length;i++) {
        items[i].className = items[i].getAttribute('index') == index ? 'active' :'';
    }
    element.innerHTML = 'Loading...';

    ajaxContentIntoContainer({ 
        url: contentURL + '&ajaxgroup=1', // the url to get the page content from 
        container: element, // the container to dump the content into 
        timeout: 10, // how long to wait for the server before returning an error 
        success: function() {  },
        error: function(e) { console.log(e); element.innerHTML = 'Error loading content';}
    });
    
    return false;
}