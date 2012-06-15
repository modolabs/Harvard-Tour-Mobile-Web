function scrollContentToTop() {
    if (courseDetailScroller) {
        courseDetailScroller.scrollTo(0, 0, 50);
    } else {
        scrollToTop();
    }
}

function loadCourseUpdateIcons(childImage, contentURL) {
    var container = childImage.parentNode;
    if (container) {
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

function updateTabletDetail(linkId, contentURL, cookieName, cookiePath) {
    var link = document.getElementById(linkId);
    var list = document.getElementById('coursesListWrapper');
    var container = document.getElementById(linkId+"_detail");
    var parent = container.parentNode;
    if (!link || !list || !container || !parent) { return; }
    
    setCookie(cookieName, link.id, 0, cookiePath);
    
    var links = list.getElementsByTagName('a');
    for (var i = 0; i < links.length; i++) {
        if (links[i].id == link.id) {
            addClass(link, 'selected');
        } else {
            removeClass(links[i], 'selected');
        }
    }
    
    var containers = parent.childNodes;
    for (var i = 0; i < containers.length; i++) {
        containers[i].style.display = (containers[i].id == container.id) ? "block" : "none";
    }
    
    if (courseDetailScroller) {
        courseDetailScroller.scrollTo(0, 0, 50);
    }
    
    if (!hasClass(container, 'loaded')) {
        ajaxContentIntoContainer({ 
            url: contentURL, // the url to get the page content from 
            container: container, // the container to dump the content into 
            timeout: 30, // how long to wait for the server before returning an error 
            success: function() {
                addClass(container, 'loaded');
                onAjaxContentLoad();
            },
            error: function(e) {
                onAjaxContentLoad();
            }
        });
    }
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
                // add up the heights of elements above and below the splitview
                marginHeight += Math.max(0, elements[i].offsetHeight
                    + parseFloat(getCSSValue(elements[i], 'margin-top'))
                    + parseFloat(getCSSValue(elements[i], 'margin-bottom')));
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
