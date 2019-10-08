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
	var icons = $('#icons')
	var index = $(icons).children().length + 1
	console.log('new icon index', index)
	var newIconForm = "\
			<div class='icon-form'>\
				<button type='button' class='btn btn-outline-secondary smaller-text delete-icon'><i class='fas fa-trash-alt'></i> Delete Icon</button>\
				<div class='icon-upload custom-file mt-2'>\
					<input type='file' class='custom-file-input' id='icon-upload-" + index + "' aria-describedby='upload'>\
					<label class='custom-file-label text-truncate' for='icon-upload-" + index + "'>Choose an icon</label>\
				</div>\
				<label class='mt-1' for='icon-label-" + index + "'>Icon label</label>\
				<input class='w-100' type='text' id='icon-label-" + index + "'/>\
				<h6 class='mt-2'>Links</h6>\
				<div class='link-buttons row'>\
					<button type='button' class='btn btn-outline-secondary smaller-text new-link'><i class='fas fa-plus'></i> New Link</button>\
					<button type='button' class='btn btn-outline-secondary smaller-text delete-link ml-3' disabled><i class='fas fa-trash-alt'></i></i> Delete Link</button>\
				</div>\
				<div class='links'>\
					<div class='link-form'>\
						<span class='mt-1'>Link 1:</span>\
						<label class='ml-2' for='link-label-" + index + "-1'>Label</label>\
						<input class='ml-2' type='text' id='link-label-" + index + "-1'/>\
						<label class='ml-2' for='link-url-" + index + "-1'>URL</label>\
						<input class='ml-2' type='text' id='link-url-" + index + "-1'/>\
					</div>\
				</div>\
			</div>"
	$(icons).append(newIconForm)
})

// delete icon
$("body").on('click', 'button.delete-icon', function(i, e) {
	$(this).closest('.icon-form').remove()
})

// LINKS
// add link
$("body").on('click', 'button.new-link', function(i, e) {
	var iconForm = $(this).closest('div.icon-form')
	var i = iconForm.index() + 1
	var links = $(iconForm).find('div.links')
	var j = $(links).children().length + 1
	var newLinkForm = "\
					<div class='link-form'>\
						<span class='mt-1'>Link " + j + ":</span>\
						<label class='ml-2' for='link-label-" + i + "-" + j + "'>Label</label>\
						<input class='ml-2' type='text' id='link-label-" + i + "-" + j + "'/>\
						<label class='ml-2' for='link-url-" + i + "-" + j + "'>URL</label>\
						<input class='ml-2' type='text' id='link-url-" + i + "-" + j + "'/>\
					</div>"
	links.append(newLinkForm)
	$(this).next('button.delete-link').attr('disabled', false)
})

// delete link
$("body").on('click', 'button.delete-link', function(i, e) {
	var iconForm = $(this).closest('div.icon-form')
	var i = iconForm.index() + 1
	var links = $(iconForm).find('div.links')
	$(links).children(':last').remove()
	if ($(links).children().length <= 1)
		$(this).attr('disabled', true)
})

$("body").on('click', '#save_changes', function(i, e) {
	// send to server to save on db
	var data = {
		action: "save_changes",
		form_name: PatientAccessSplit.formName,
		dashboard_title: $("#dashboard_title").val()
	};
	
	console.log('sending data:', data);
	
	$.ajax({
		method: "POST",
		url: PatientAccessSplit.configAjaxUrl,
		data: {
			data: JSON.stringify(data)
		},
		dataType: "json",
		always: function(response){
			console.log(response)
		}
	});
})