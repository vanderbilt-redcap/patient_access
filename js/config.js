$(".form_picker_dd a").click(function(i, e) {
	// console.log("form name: " + $(this).attr("value"))
	PatientAccessSplit.formName = $(this).attr("value")
	$.ajax({
		method: 'POST',
		url: PatientAccessSplit.configAjaxUrl,
		data: {
			action: "get_config_page",
			form_name: PatientAccessSplit.formName
		},
		dataType: "html"
	}).always(function(msg) {
		// console.log('result', msg)
		$("#form_assocs").html(msg);
	});
	$("#form_picker").text($(this).text());
})

$("body").on('click', '#save_changes', function(i, e) {
	// send to server to save on db
	var data = {
		action: "save_settings",
		form_name: PatientAccessSplit.formName,
		dashboard_title: $("#dashboard_title").val()
	};
	
	console.log('sending data:', data);
	
	$.ajax({
		method: "POST",
		url: PatientAccessSplit.configAjaxUrl,
		data: {
			action: "save_changes",
			data: JSON.stringify(data)
		},
		dataType: "json",
		success: function(response){
			console.log(response)
		},
		error: function(){
			console.log(response)
		}
	});
})