// survey instruments select dropdown
$(".form_picker_dd a").click(function(i, e) {
	// console.log("form name: " + $(this).attr("value"))
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
		// console.log('result', msg)
		$("#form_assocs").html(msg)
	});
	$("#form_picker").text($(this).text())
})

// ICONS
// change label on uploaded file
$('body').on('change', ".custom-file-input", function() {
	var fileName = $(this).val().split('\\').pop()
	$(this).next('.custom-file-label').html(fileName)
});

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
	// send to server to save on db
	var form_data = new FormData()
	form_data.append("form_name", PatientAccessSplit.formName)
	
	// set dashboard title if set in the config page
	if ($("#dashboard_title").val())
		form_data.append("dashboard_title", $("#dashboard_title").val())
	
	// add icons and links
	$("#icons .icon-form").each(function(j, iconForm) {
		// add icon file itself
		var input = $(iconForm).find('.custom-file-input')
		if (input.prop('files') && input.prop('files')[0]) {
			form_data.append('icon-' + (j+1), input.prop('files')[0])
			// var icon_filename = $(iconForm).find('.custom-file-label').val()
			// form_data.append('icon-filename-' + (j+1), icon_filename)
			// console.log('appended icon file name:', icon_filename)
		}
		
		// add icon label
		if ($(iconForm).find('.icon-label').val())
			form_data.append('icon-label-' + (j+1), $(iconForm).find('.icon-label').val())
		
		$(iconForm).find('.link-form').each(function(k, linkForm) {
			// label
			if ($(linkForm).find('.link-label').val())
				form_data.append($(linkForm).find('.link-label').attr('id'), $(linkForm).find('.link-label').val())
			// url
			if ($(linkForm).find('.link-url').val())
				form_data.append($(linkForm).find('.link-url').attr('id'), $(linkForm).find('.link-url').val())
		})
	})
	
	// console.log('sending data:', form_data);
	// console.log('save config url', PatientAccessSplit.saveConfigUrl);
	
	$.ajax({
		url: PatientAccessSplit.saveConfigUrl,
		dataType: 'json',
		cache: false,
		contentType: false,
		processData: false,
		data: form_data,
		type: 'POST',
		success: function(response){
			simpleDialog(response.message)
		},
		error: function(response){
			simpleDialog(response.message)
		}
	});
})

// PatientAccessSplit icon/link functions
PatientAccessSplit.newIcon = function() {
	var icons = $('#icons')
	var index = $(icons).children().length + 1
	// console.log('new icon index', index)
	var newIconForm = "\
			<div class='icon-form'>\
				<button type='button' class='btn btn-outline-secondary smaller-text delete-icon'><i class='fas fa-trash-alt'></i> Delete Icon</button>\
				<div class='icon-upload custom-file mt-2'>\
					<input type='file' class='custom-file-input' id='icon-upload-" + index + "' aria-describedby='upload'>\
					<label class='custom-file-label text-truncate' for='icon-upload-" + index + "'>Choose an icon</label>\
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
}
PatientAccessSplit.newLink = function(link) {
	var iconForm = $(link).closest('div.icon-form')
	var i = iconForm.index() + 1
	var links = $(iconForm).find('div.links')
	var j = $(links).children().length + 1
	var newLinkForm = "\
					<div class='link-form mt-1'>\
						<div class='ml-2 row'>\
							<span class='mt-1'>Link " + j + ":</span>\
							<button type='button' class='btn btn-outline-secondary smaller-text delete-link ml-3'><i class='fas fa-trash-alt'></i></i> Delete Link</button>\
						</div>\
						<label class='ml-2' for='link-label-" + i + "-" + j + "'>Label</label>\
						<input class='link-label ml-2' type='text' id='link-label-" + i + "-" + j + "'/>\
						<label class='ml-2' for='link-url-" + i + "-" + j + "'>URL</label>\
						<input class='link-url ml-2' type='text' id='link-url-" + i + "-" + j + "'/>\
					</div>"
	links.append(newLinkForm)
}
PatientAccessSplit.deleteLink = function(link) {
	var links = $(link).closest('div.links')
	var iconIndex = $(link).closest('div.icon-form').index()+1
	$(link).closest('.link-form').remove()
	
	// renumber remaining links
	$(links).find('.link-form').each(function(i, form) {
		i++
		// console.log('form', form)
		$(form).find('span').html("Link " + i)
		$(form).find('label:first').attr('for', "link-label-" + iconIndex + "-" + i)
		$(form).find('input:first').attr('id', "link-label-" + iconIndex + "-" + i)
		$(form).find('label:last').attr('for', "link-url-" + iconIndex + "-" + i)
		$(form).find('input:last').attr('id', "link-url-" + iconIndex + "-" + i)
	})
}

// helper funcs
PatientAccessSplit.htmlDecode = function(value) {
	return $("<textarea/>").html(value).text();
}
PatientAccessSplit.htmlEncode = function(value) {
	return $('<textarea/>').text(value).html();
}