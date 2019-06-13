// include css and bootstrap
$('head').append('<link rel="stylesheet" type="text/css" href="CSS_URL">');

// load dashboard content
$(function() {
	// append bootstrap js to docvar
	let s = document.createElement("script");
    s.type = "text/javascript";
    s.src = "BOOTSTRAP_URL";
    // Use any selector
    $("head").append(s);
	
	// add dashboard html
	$("#container").html(DASH_HTML);
	
	$("button").on("click", function() {
		$(".linkset").hide();
		let target = $("#" + $(this).attr("target"));
		target.html("<span>Links</span>");
		target.show();
	});
});
