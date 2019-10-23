// include css and bootstrap
$('head').append('<link rel="stylesheet" type="text/css" href="CSS_URL">')

// load dashboard content
$(function() {
	// add dashboard html
	$("#container").html(DASH_HTML)
	$("#container").after(FOOTER_HTML)
	$("#iconLinks").hide()
	
	$("button").on("click", function() {
		if ($("#iconLinks").css('display') !== 'none' && this == PatientAccessModule.lastClicked) {
			$("#iconLinks").hide()
		} else {
			let iconIndex = $(this).attr("data-icon-index")
			let html = ""
			if (!PatientAccessModule.settings.icons[iconIndex].links) {
				$("#iconLinks").hide()
				return
			}
			for (var linkIndex in PatientAccessModule.settings.icons[iconIndex].links) {
				var link = PatientAccessModule.settings.icons[iconIndex].links[linkIndex]
				html += `
						<li><a href="javascript:PatientAccessModule.openLink('${link.url}')">${link.label}</li>`
			}
			$("#iconLinks ul").html(html)
			// change links div card title header
			$("#iconLinks h5").text($(this).find("small").text() + " Links")
			
			// add (or replace) icon in #iconLinks
			$("#iconLinks").find("button").remove()
			$("#iconLinks").append($(this).clone())
			$("#iconLinks").find("button img").css({
				"height": 64,
				"width": 64
			})
			
			$("#iconLinks").show()
			$([document.documentElement, document.body]).animate({
				scrollTop: $("#iconLinks").offset().top
			}, 200);
		}
		PatientAccessModule.lastClicked = this
	})
})

PatientAccessModule.openLink = function(url) {
	$("#survey").remove()
	$("#container").append(`
	<div id="survey">
		<iframe src="${url}"</iframe>
	</div>`)
	$("#pagecontainer").css('margin', '0px')
	$("#pagecontainer").css('max-width', 'none')
	$("#dashboard").css('max-width', '20%')
	$("#dashboard").css('padding-left', '8px')
	$("#dashboard").css('padding-right', '8px')
	$("#dashboard").css('margin-top', '8px')
	$(".icon").css('max-width', '64px')
	$(".icon").css('max-height', '64px')
	$("#container").css('padding-top', '0px')
	$("#container").css('flex-direction', 'row')
	$("iframe").css('width', '100%')
	$("#survey").css('width', '100%')
	$(window).scrollTop(0)
	// expand survey container to fill width
	// $('iframe').on('load', function() {
		// $('iframe').contents().find("head")
			// .append("<style type='text/css'>  #pagecontainer{max-width: none}  </style>")
	// })
}