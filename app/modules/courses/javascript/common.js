function loadSection(select, page) {
    window.location = "./" + page + "?term=" + select.value;
}

function updateTab(tab, contentURL) {
//    console.log('updating ' + tab + ' to '+ contentURL);
    var element = document.getElementById(tab + '-tabbody');
    ajaxContentIntoContainer({ 
        url: contentURL, // the url to get the page content from 
        container: element, // the container to dump the content into 
        timeout: 10 // how long to wait for the server before returning an error 
    });
}