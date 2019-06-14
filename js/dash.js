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
		console.log($(this).find("small").text());
		$("#iconLinks h5").text($(this).find("small").text() + " Links");
		$("#iconLinks").show();
	});
});

PatientAccessModule.openLink = function(url) {
	$("#survey").remove();
	$("#container").append(`
	<div id="survey">
		<iframe src="${url}"text</iframe>
	</div>`);
}