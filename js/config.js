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
		$("#icons").sortable({
			opacity: 0.7,
			helper: 'clone',
			placeholder: "ui-state-highlight icon-form",
			tolerance: 'pointer'
		})
		$("#icons").disableSelection()
	})
	$("#form_picker").text($(this).text())
})

// dashboard logo/image upload
$('body').on('change', ".logo-upload .custom-file-input", function() {
	// var iconForm = $(this).closest('.icon-form')
	// $(iconForm).attr('data-icon-edoc-id', null)
	var fileName = $(this).val().split('\\').pop()
	// $(this).next('.custom-file-label').html(fileName)
	$('.logo-upload .custom-file-label').html(fileName)
	
	var fileCount = $(this).prop('files').length
	if (fileCount > 0) {
		// throw warning if more than 20 files attached
		var currentInput = this
		var filesAttached = 0
		$('.custom-file-input').each(function(i, input) {
			if ($(input).prop('files')[0])
				filesAttached ++
			if (filesAttached > PatientAccessSplit.max_file_uploads) {
				$('.logo-upload .custom-file-label').html("Choose an image")
				simpleDialog("This instance of REDCap can only upload " + PatientAccessSplit.max_file_uploads + " files at a time. Please save changes and continue adding icons.")
				return
			}
		})
		
		if (filesAttached <= PatientAccessSplit.max_file_uploads) {
			var reader = new FileReader()
			reader.onloadend = function (e) {
				var imgElement = "<img id='logo-preview-image' src='" + e.target.result + "'/>"
				$('.logo-preview').html(imgElement)
			}
			reader.readAsDataURL($(this).prop('files')[0])
		}
	} else {
		$('#logo-preview-image').remove()
	}
})
$('body').on('blur', ".logo-upload .custom-file-input", function() {
	if ($(this).prop('files').length == 0) {
		$('#logo-preview-image').remove()
		$('.logo-preview').removeAttr('data-edoc-id', null)
	}
})

// ICONS
// change label on uploaded file
$('body').on('change', "#icons .custom-file-input", function() {
	var iconForm = $(this).closest('.icon-form')
	$(iconForm).attr('data-icon-edoc-id', null)
	var fileName = $(this).val().split('\\').pop()
	$(this).next('.custom-file-label').html(fileName)
	
	var fileCount = $(this).prop('files').length
	if (fileCount > 0) {
		// throw warning if more than 20 files attached
		var currentInput = this
		var filesAttached = 0
		$('.custom-file-input').each(function(i, input) {
			if ($(input).prop('files')[0])
				filesAttached ++
			if (filesAttached > PatientAccessSplit.max_file_uploads) {
				$(iconForm).find('.custom-file-label').html("Choose a icon")
				simpleDialog("This instance of REDCap can only upload " + PatientAccessSplit.max_file_uploads + " files at a time. Please save changes and continue adding icons.")
				return
			}
		})
		
		if (filesAttached <= PatientAccessSplit.max_file_uploads) {
			var reader = new FileReader()
			reader.onloadend = function (e) {
				var imgElement = "<img class='icon-preview' id='icon-preview-" + $(iconForm).index() + "' src='" + e.target.result + "'/>"
				$(iconForm).find('.preview').html(imgElement)
			}
			reader.readAsDataURL($(this).prop('files')[0])
		}
	} else {
		$(this).closest('.icon-form').find('.preview img').remove()
	}
})
$('body').on('blur', "#icons .custom-file-input", function() {
	if ($(this).prop('files').length == 0) {
		$(this).closest('.icon-form').find('.preview img').remove()
		$(this).closest('.icon-form').removeAttr('data-icon-edoc-id', null)
	}
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

// FOOTER LINKS
// add link
$("body").on('click', 'button.new-footer-link', function(i, e) {
	PatientAccessSplit.newFooterLink(this)
})

// delete link
$("body").on('click', 'button.delete-footer-link', function(i, e) {
	PatientAccessSplit.deleteFooterLink(this)
})

// SAVE CHANGES
$("body").on('click', '#save_changes', function(i, e) {
	// SETTINGS holds everything except icon files that were attached by user
	// FORM_DATA holds all icon image files that were attached by user
	// form_data.settings will hold the encoded json string containing settings values
	
	//
	var files_attached = 0
	//
	
	var form_data = new FormData()
	
	var settings = {}
	settings.form_name = PatientAccessSplit.formName
	if ($("#dashboard_title").val())
		settings.dashboard_title = $("#dashboard_title").val()
	
	// logo/image for dashboard
	var logoInput = $('.logo-upload .custom-file-input')
	if (logoInput.prop('files') && logoInput.prop('files')[0]) {
		form_data.append('dashboard-logo', logoInput.prop('files')[0])
	} else {
		if ($('.logo-preview').attr('data-edoc-id')) {
			settings['dashboard-logo'] = PatientAccessSplit.settings['dashboard-logo']
		}
	}
	
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
		} else if ($(iconForm).attr('data-icon-edoc-id')) {
			icon.edoc_id = $(iconForm).attr('data-icon-edoc-id')
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
	
	// add footer link data to settings
	$(".footer-link").each(function(i, link) {
		var label = $(link).find('.link-label').val()
		var url = $(link).find('.link-url').val()
		if (url || label) {
			if (!settings.footer_links)
				settings.footer_links = []
			settings.footer_links.push({})
			if (url)
				settings.footer_links[settings.footer_links.length-1].url = url
			if (label)
				settings.footer_links[settings.footer_links.length-1].label = label
		}
	})
	
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
			simpleDialog(response.message)
			if (response.new_settings) {
				PatientAccessSplit.settings = JSON.parse(response.new_settings)
			}
		},
		error: function(response){
			simpleDialog(response.message)
		}
	})
})

// PatientAccessSplit icon/link functions
PatientAccessSplit.newIcon = function() {
	var icons = $('#icons')
	var index = $(icons).children().length
	var newIconForm = "\
			<div class='icon-form ui-state-default'>\
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
	$(link).closest('.link-form').remove()
	PatientAccessSplit.renumberLinks()
}
PatientAccessSplit.newFooterLink = function() {
	$("#footer-links").css('display', 'flex')
	$('#footer-links').append("\
					<div class='footer-link mt-2'>\
						<div class='ml-2 row'>\
							<span class='mt-1'></span>\
							<button type='button' class='btn btn-outline-secondary smaller-text delete-footer-link ml-3'><i class='fas fa-trash-alt'></i></i> Delete Link</button>\
						</div>\
						<label class='ml-2'>Label</label>\
						<input class='link-label ml-2' type='text'/>\
						<label class='ml-2'>URL</label>\
						<input class='link-url ml-2' type='text'/>\
					</div>")
	PatientAccessSplit.renumberLinks()
}
PatientAccessSplit.deleteFooterLink = function(link) {
	$(link).closest('.footer-link').remove()
	if ($("#footer-links").children().length == 0) {
		$("#footer-links").css('display', 'none')
	}
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
	$(".footer-link").each(function(i, link) {
		$(link).find('span').html("Link " + (i+1))
	})
}

// helper funcs
PatientAccessSplit.htmlDecode = function(value) {
	return $("<textarea/>").html(value).text()
}
PatientAccessSplit.htmlEncode = function(value) {
	return $('<textarea/>').text(value).html()
}
PatientAccessSplit.downloadJSON = function(filename, data) {
    var blob = new Blob([data], {type: 'application/json'});
    if(window.navigator.msSaveOrOpenBlob) {
        window.navigator.msSaveBlob(blob, filename);
    }
    else{
        var elem = window.document.createElement('a');
        elem.href = window.URL.createObjectURL(blob);
        elem.download = filename;        
        document.body.appendChild(elem);
        elem.click();        
        document.body.removeChild(elem);
    }
}

// ICONS
// change label on uploaded file
$('body').on('change', "#json-import", function() {
	var files = $(this).prop('files')
	if (files[0]) {
		var data = {
			form_name: PatientAccessSplit.formName
		}
		var file_reader = new FileReader()
		file_reader.onload = function(e) {
			data.settings = JSON.parse(e.target.result)
			// console.log('sending ajax...')
			$.ajax({
				method: 'POST',
				dataType: 'json',
				url: PatientAccessSplit.importSettingsUrl,
				data: {
					json: JSON.stringify(data)
				},
				// processData: false
			}).always(function(response) {
				// console.log('response:', response)
				if (response.alert) {
					simpleDialog(response.alert)
				}
				if (response.html) {
					$("#form_assocs").html(response.html)
					$("#icons").sortable({
						opacity: 0.7,
						helper: 'clone',
						placeholder: "ui-state-highlight icon-form",
						tolerance: 'pointer'
					})
					$("#icons").disableSelection()
				}
			})
		}
		file_reader.readAsText(files[0], "UTF-8")
	}
})