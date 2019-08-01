// include css and bootstrap
$('head').append('<link rel="stylesheet" type="text/css" href="CSS_URL">');

// load dashboard content
$(function() {
	// append bootstrap js to document
	let s = document.createElement("script");
    s.type = "text/javascript";
    s.src = "BOOTSTRAP_URL";
    $("head").append(s);
	
	// add dashboard html
	$("#container").html(DASH_HTML);
	$("#container").after(FOOTER_HTML);
	$("#iconLinks").hide();
	
	$("button").on("click", function() {
		let iconIndex = $(this).attr("data-icon-index");
		let html = "";
		PatientAccessModule.iconLinks[iconIndex].forEach(function(link) {
			html += `
					<li><a href="javascript:PatientAccessModule.openLink('${link.url}')">${link.label}</li>`;
		});
		$("#iconLinks ul").html(html);
		// change links div card title header
		// console.log($(this).find("small").text());
		$("#iconLinks h5").text($(this).find("small").text() + " Links");
		
		// add (or replace) icon in #iconLinks
		$("#iconLinks div").find("button").remove();
		$("#iconLinks div").append($(this).clone());
		// $("#iconLinks div").find("button img").css({
			// "height": 64,
			// "width": 64
		// });
		
		$("#iconLinks").show();
	});
	
	$("#menu").on("click", "button", function(i, e) {
		$("#menu").css('display', 'none');
		$("#dashboard").css('display', 'flex')
		$("#pagecontainer").css('max-width', '90%');;
		$("#survey").remove();
	});
	
	$("#pagecontainer").css('max-width', '90%');
});

PatientAccessModule.openLink = function(url) {
	// show menu bar, hide dashboard
	$("#menu").css('display', 'block');
	$("#dashboard").css('display', 'none');
	$("#pagecontainer").css('max-width', '100%');
	
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