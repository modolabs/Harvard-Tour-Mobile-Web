function updateTabletDetail(contentURL) {
    var element = document.getElementById('courseDetail');
    
    element.innerHTML = AJAX_CONTENT_LOADING;
    
    ajaxContentIntoContainer({ 
        url: contentURL, // the url to get the page content from 
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

}

function showCourse(url) {
    updateTabletDetail(url+'&ajax=1');
}

var courseListScroller = null;
var courseDetailScroller = null;

function moduleInit() {
    // no split view on this page
    var list = document.getElementById('coursesListWrapper');
    var detail = document.getElementById('courseDetailWrapper');
    if (!list || !detail) { return; } // safety check
    
    containerScroller.destroy();
    delete containerScroller;
    containerScroller = null;
    document.getElementById('container').style.height = "100%";
    document.getElementById('container').style.overflow = "hidden";
    
    document.getElementById('footer').style.display = 'none';
    
    courseListScroller = new iScroll('coursesListWrapper', { 
        checkDOMChanges: false, 
        hScrollbar: false,
        desktopCompatibility: true,
        bounce: false,
        bounceLock: true
    });
    
    courseDetailScroller = new iScroll('courseDetailWrapper', { 
        checkDOMChanges: true, 
        hScrollbar: false,
        desktopCompatibility: true,
        bounce: false,
        bounceLock: true
    });
    
    moduleHandleWindowResize();
}

function moduleHandleWindowResize() {
    var wrapper = document.getElementById('wrapper');
    var header = document.getElementById('term-selector');
    var splitview = document.getElementById('tabletCourses');
    if (!header || !splitview) { return; }  // safety check
    
    var height = wrapper.offsetHeight - header.offsetHeight;
    splitview.style.height = height+"px";
    
    if (courseListScroller) {
        courseListScroller.refresh();
    }
    if (courseDetailScroller) {
        courseDetailScroller.refresh();
    }
}

function onAjaxContentLoad() {
    if (courseListScroller) {
        courseListScroller.refresh();
    }
    if (courseDetailScroller) {
        courseDetailScroller.refresh();
    }
    onDOMChange();
}
