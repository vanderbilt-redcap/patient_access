// include css and bootstrap
$('head').append('<link rel="stylesheet" type="text/css" href="CSS_URL">');

// load dashboard content
$(function() {
	// append bootstrap js to document
	let s = document.createElement("script");
    s.type = "text/javascript";
    s.src = "BOOTSTRAP_URL";
    $("head").append(s);
	
	// move menu div to pagecontainer frmo container
	$("#container").before(`
<div id="menu">
	<button class=\"btn\" type=\"button\">
		<i class="fas fa-bars"></i>
	</button>
</div>`);
	
	// add dashboard html
	$("#container").html(DASH_HTML);
	$("#container").after(FOOTER_HTML);
	// $("#iconLinks").hide();
	
	// $("button").on("click", function() {
		// if ($("#iconLinks").css('display') !== 'none' && $(this).find("small").text() === $("#iconLinks button small").text()) {
			// $("#iconLinks").hide();
		// } else {
			// let iconIndex = $(this).attr("data-icon-index");
			// let html = "";
			// PatientAccessModule.iconLinks[iconIndex].forEach(function(link) {
				// html += `
						// <li><a href="javascript:PatientAccessModule.openLink('${link.url}')">${link.label}</li>`;
			// });
			// $("#iconLinks ul").html(html);
			// // change links div card title header
			// // console.log($(this).find("small").text());
			// $("#iconLinks h5").text($(this).find("small").text() + " Links");
			
			// // add (or replace) icon in #iconLinks
			// // $("#iconLinks div").find("button").remove();
			// $("#iconLinks").find("button").remove();
			// $("#iconLinks h5").after($(this).clone());
			// // $("#iconLinks div").find("button img").css({
				// // "height": 64,
				// // "width": 64
			// // });
			
			// $("#iconLinks").show();
		// }
	// });
	
	$("#menu").on("click", "button", function(i, e) {
		$("#menu").css('display', 'none');
		$("#container").css("margin", "0px 15%");
		$("#dashboard").css('max-width', '800px');
		$("#dashboard").css('margin-right', '0px');
		$("#survey").remove();
		PatientAccessModule.resizeContainer();
	});
});

PatientAccessModule.openLink = function(url) {
	$("#menu").css('display', 'block');
	$("#container").css("margin", "0px");
	$("#dashboard").css('max-width', '30%');;
	$("#dashboard").css('margin-right', '24px');
	
	// remove existing survey if possible
	$("#survey").remove();
	
	// add new iframe
	$("#container").append(`
	<div id="survey">
		<iframe src="${url}"</iframe>
	</div>`);
	
	// expand survey container to fill width
	$('iframe').on('load', function() {
		$('iframe').contents().find("head")
			.append("<style type='text/css'>  #pagecontainer{max-width: none;}  </style>");
	});
}

PatientAccessModule.resizeContainer = function() {
	if ($('html').width() >= 992) {
		$("#container").css("margin", "0px 15%");
	} else if ($('html').width() >= 768) {
		$("#container").css("margin", "0px 10%");
	} else {
		$("#container").css("margin", "0px");
	}
}

$(window).resize(PatientAccessModule.resizeContainer);