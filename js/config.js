// survey instruments select dropdown
$(".form_picker_dd a").click(function(i, e) {
	PatientAccessSplit.formName = $(this).attr("value")
	var data = {
		action: "get_config_page",
		form_name: PatientAccessSplit.formName
	}
	
	$.ajax({
		method: 'POST',
		url: PatientAccessSplit.configAjaxUrl,
		data: {
			data: JSON.stringify(data)
		},
		dataType: "html"
	}).always(function(msg) {
		$("#form_assocs").html(msg)
	})
	$("#form_picker").text($(this).text())
})

// ICONS
// change label on uploaded file
$('body').on('change', ".custom-file-input", function() {
	var fileName = $(this).val().split('\\').pop()
	$(this).next('.custom-file-label').html(fileName)
	$(this).parent().next('.preview').hide()
})

// new icon
$("body").on('click', 'button.new-icon', function(i, e) {
	PatientAccessSplit.newIcon()
})

// delete icon
$("body").on('click', 'button.delete-icon', function(i, e) {
	PatientAccessSplit.deleteIcon(this)
})

// LINKS
// add link
$("body").on('click', 'button.new-link', function(i, e) {
	PatientAccessSplit.newLink(this)
})

// delete link
$("body").on('click', 'button.delete-link', function(i, e) {
	PatientAccessSplit.deleteLink(this)
})

// SAVE CHANGES
$("body").on('click', '#save_changes', function(i, e) {
	// SETTINGS holds everything except icon files that were attached by user
	// FORM_DATA holds all icon image files that were attached by user
	// form_data.settings will hold the encoded json string containing settings values
	
	//
	var files_attached = 0
	//
	
	var settings = {}
	var form_data = new FormData()
	
	settings.form_name = PatientAccessSplit.formName
	if ($("#dashboard_title").val())
		settings.dashboard_title = $("#dashboard_title").val()
	
	// add icons and links
	settings.icons = []
	$("#icons .icon-form").each(function(j, iconForm) {
		settings.icons.push({})
		var icon = settings.icons[settings.icons.length-1]
		
		// attach new icon file to form_data OR put edoc_id in settings
		var input = $(iconForm).find('.custom-file-input')
		var file_attached = false
		if (input && input.prop('files') && input.prop('files')[0]) {
			file_attached = true
			form_data.append('icon-' + (settings.icons.length-1), input.prop('files')[0])
			console.log('file appended to form_data for icon ' + j)
		} else if ($(iconForm).attr('edoc_id')) {
			icon.edoc_id = $(iconForm).attr('edoc_id')
		}
		
		// add icon label
		if ($(iconForm).find('.icon-label').val())
			icon.label = $(iconForm).find('.icon-label').val()
		
		// add links
		icon.links = []
		$(iconForm).find('.link-form').each(function(k, linkForm) {
			icon.links.push({})
			var link = icon.links[icon.links.length-1]
			
			// label
			if ($(linkForm).find('.link-label').val())
				link.label = $(linkForm).find('.link-label').val()
			// url
			if ($(linkForm).find('.link-url').val())
				link.url = $(linkForm).find('.link-url').val()
			
			if ($.isEmptyObject(link))
				icon.links.pop()
		})
		if (icon.links.length == 0) 
			delete icon.links
		
		if (!file_attached && !icon.label && !icon.edoc_id && $.isEmptyObject(icon))
			settings.icons.pop()		// effectively ignore this icon
	})
	if (settings.icons.length == 0)
		delete settings.icons
	
	form_data.append('settings', JSON.stringify(settings))
	
	$.ajax({
		url: PatientAccessSplit.saveConfigUrl,
		dataType: 'json',
		cache: false,
		contentType: false,
		processData: false,
		data: form_data,
		type: 'POST',
		success: function(response){
			console.log(response.message)
		},
		error: function(response){
			console.log(response.message)
		}
	})
})

// PatientAccessSplit icon/link functions
PatientAccessSplit.newIcon = function() {
	var icons = $('#icons')
	var index = $(icons).children().length
	var newIconForm = "\
			<div class='icon-form'>\
				<button type='button' class='btn btn-outline-secondary smaller-text delete-icon'><i class='fas fa-trash-alt'></i> Delete Icon</button>\
				<div class='icon-upload custom-file mt-2'>\
					<input type='file' class='custom-file-input' id='icon-upload-" + index + "' aria-describedby='upload'>\
					<label class='custom-file-label text-truncate' for='icon-upload-" + index + "'>Choose an icon</label>\
				</div>\
				<div class='preview'>\
				</div>\
				<label class='mt-1' for='icon-label-" + index + "'>Icon label</label>\
				<input class='icon-label w-100' type='text' id='icon-label-" + index + "'/>\
				<div class='link-buttons row mt-1'>\
					<h6>Links</h6>\
					<button type='button' class='btn btn-outline-secondary smaller-text new-link ml-3'><i class='fas fa-plus'></i> New Link</button>\
				</div>\
				<div class='links'>\
				</div>\
			</div>"
	$(icons).append(newIconForm)
}
PatientAccessSplit.deleteIcon = function(icon) {
	$(icon).closest('.icon-form').remove()
	PatientAccessSplit.renumberLinks()
}
PatientAccessSplit.newLink = function(link) {
	var iconForm = $(link).closest('div.icon-form')
	var links = $(iconForm).find('div.links')
	var newLinkForm = "\
					<div class='link-form mt-1'>\
						<div class='ml-2 row'>\
							<span class='mt-1'></span>\
							<button type='button' class='btn btn-outline-secondary smaller-text delete-link ml-3'><i class='fas fa-trash-alt'></i></i> Delete Link</button>\
						</div>\
						<label class='ml-2'>Label</label>\
						<input class='link-label ml-2' type='text'/>\
						<label class='ml-2'>URL</label>\
						<input class='link-url ml-2' type='text'/>\
					</div>"
	links.append(newLinkForm)
	PatientAccessSplit.renumberLinks()
}
PatientAccessSplit.deleteLink = function(link) {
	var links = $(link).closest('div.links')
	$(link).closest('.link-form').remove()
	
	PatientAccessSplit.renumberLinks()
}

PatientAccessSplit.renumberLinks = function() {
	$(".icon-form").each(function(i, iconForm) {
		$(iconForm).find(".link-form").each(function(j, linkForm) {
			$(linkForm).find('span').html("Link " + (j+1))
			$(linkForm).find('label:first').attr('for', "link-label-" + i + "-" + j)
			$(linkForm).find('input:first').attr('id', "link-label-" + i + "-" + j)
			$(linkForm).find('label:last').attr('for', "link-url-" + i + "-" + j)
			$(linkForm).find('input:last').attr('id', "link-url-" + i + "-" + j)
		})
	})
}

// helper funcs
PatientAccessSplit.htmlDecode = function(value) {
	return $("<textarea/>").html(value).text()
}
PatientAccessSplit.htmlEncode = function(value) {
	return $('<textarea/>').text(value).html()
}