function updateTabletDetail(link, contentURL) {
    var detailId = link.id+"_detail"
    
    var list = document.getElementById('coursesListWrapper');
    var links = list ? list.getElementsByTagName('a') : [];
    for (var i = 0; i < links.length; i++) {
        removeClass(links[i], 'selected');
    }
    addClass(link, 'selected');
    
    var detailContainer = document.getElementById('courseDetail');
    var details = detailContainer ? detailContainer.childNodes : [];
    for (var i = 0; i < details.length; i++) {
        details[i].style.display = (details[i].id == detailId) ? "block" : "none";
    }

    if (!hasClass(link, 'loaded')) {
        var element = document.getElementById(detailId);
        
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
                removeClass(link, 'loaded');
                onAjaxContentLoad();
            }
        });
        
        addClass(link, 'loaded');
    }
}

function showCourse(link, url) {
    updateTabletDetail(link, url+'&ajax=1');
}

var courseListScroller = null;
var courseDetailScroller = null;

function moduleInit() {
    // no split view on this page
    var list = document.getElementById('coursesListWrapper');
    var detail = document.getElementById('courseDetailWrapper');
    var container = document.getElementById('container');
    if (list && detail && container) {
        // only splitview in logged-in state
        moduleProvidesScrollers = true;
        
        container.style.height = "100%";
        container.style.overflow = "hidden";
        
        var footer = document.getElementById('footer');
        if (footer) {
            // only suppress footer in logged in state
            footer.style.display = "none";
        }
        
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
    }
    
    moduleHandleWindowResize();
}

function moduleHandleWindowResize() {
    var splitview = document.getElementById('tabletCourses');
    var container = document.getElementById('containerinset');
    if (container && splitview) {
        // logged in state
        var marginHeight = 0;
        var elements = container.childNodes;
        for (var i = 0; i < elements.length; i++) {
            if (elements[i].id != splitview.id && !isNaN(elements[i].offsetHeight)) {
                marginHeight += Math.max(0, elements[i].offsetHeight);
            }
        }
        splitview.style.height = (container.offsetHeight - marginHeight)+"px";
    }
        
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
