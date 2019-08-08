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
		if ($("#iconLinks").css('display') !== 'none' && $(this).find("small").text() === $("#iconLinks button small").text()) {
			$("#iconLinks").hide();
		} else {
			let iconIndex = $(this).attr("data-icon-index");
			let html = "";
			if (!PatientAccessModule.iconLinks[iconIndex]) {
				$("#iconLinks").hide();
				return false;
			}
			PatientAccessModule.iconLinks[iconIndex].forEach(function(link) {
				html += `
						<li><a href="javascript:PatientAccessModule.openLink('${link.url}')">${link.label}</li>`;
			});
			$("#iconLinks ul").html(html);
			// change links div card title header
			$("#iconLinks h5").text($(this).find("small").text() + " Links");
			
			// add (or replace) icon in #iconLinks
			$("#iconLinks div").find("button").remove();
			$("#iconLinks div").append($(this).clone());
			$("#iconLinks div").find("button img").css({
				"height": 64,
				"width": 64
			});
			
			$("#iconLinks").show();
		}
	});
});

PatientAccessModule.openLink = function(url) {
	$("#survey").remove();
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