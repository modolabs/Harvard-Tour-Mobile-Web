function updateTabletDetail(contentURL) {
    var element = document.getElementById('courseDetail');
    ajaxContentIntoContainer({ 
        url: contentURL, // the url to get the page content from 
        container: element, // the container to dump the content into 
        timeout: 10, // how long to wait for the server before returning an error 
        success: function() { },
        error: function(e) { console.log(e); element.innerHTML = 'Error loading content';}
    });

}

function showCourse(url) {
    updateTabletDetail(url+'&ajax=1');
}