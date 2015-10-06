

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
    loadTop10('thai');
});


// On load function loads the top 10 records based on foodtype specified
function loadTop10(foodType) {
	$.ajax({
		url: "/top10/" + foodType,
        type: "GET",
        dataType: "json",
        success: function(resp, text, xhr) {
        	if (resp.status) {
        		if (!resp.data || resp.data.length == 0) {
					showWarning("No restaurants cached");
					return false;
				}

				rows = resp.data;
				var rowsHtml = "";
	            var counter = 1;

	            for (var i in rows) {
	                rowHtml = '<tr> ' +
	                    '<td>'+rows[i].Score+'</td>' +
	                    '<td>'+rows[i].Grade+'</td>' +
	                    '<td>'+rows[i].Restaurant+'</td>' +
	                    '<td>'+rows[i].Address+'</td>' +
	                '</tr>';
	                counter++;
	                rowsHtml += rowHtml;
            	}

            	$('#top10body').append(rowsHtml);
        	} else {
				console.error(resp);
				showError(resp.message);
			}
        },
        error: function(xhr, err) {
			console.error(err);
			showError(err);
		}
	});
}