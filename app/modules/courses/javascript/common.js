function loadSection(select, page) {
    window.location = "./" + page + "?term=" + select.value;
}

var tabsLoaded = {};

function updateTab(tab, contentURL) {
    if (tab in tabsLoaded) {
        return;
    }
//    console.log('loading ' + tab + ' from ' + contentURL);
    var element = document.getElementById(tab + '-tabbody');
    ajaxContentIntoContainer({ 
        url: contentURL, // the url to get the page content from 
        container: element, // the container to dump the content into 
        timeout: 10, // how long to wait for the server before returning an error 
        success: function() { tabsLoaded[tab] = true; },
        error: function() { element.innerHTML = 'Error loading content';}
        
    });
}