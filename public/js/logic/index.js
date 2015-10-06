

/** NPROGRESS DEFINED FUNCTIONS */
$(document).ajaxStart(function() {
    NProgress.inc(0.2);
});
$(document).ajaxStop(function() {
    NProgress.done();
});
function startLoad() {
    NProgress.inc(0.2);
}
function stopLoad() {
    NProgress.done(true);
}


/**
 * Mocking Google's function to pull query params from url
 */
(function($) {
    $.qParam = (function(a) {
        if (a == "") return {};
        var b = {};
        for (var i = 0; i < a.length; ++i)
        {
            var p=a[i].split('=');
            if (p.length != 2) continue;
            b[p[0]] = decodeURIComponent(p[1].replace(/\+/g, " "));
        }
        return b;
    })(window.location.search.substr(1).split('&'))
})(jQuery);



$(function() {
    console.log("Page Loaded");
});