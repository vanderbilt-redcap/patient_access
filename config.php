<?php

if (!empty($_POST)) {
	$data = json_decode($_POST['data'], true);
	$data['action'] = filter_var($data['action'], FILTER_SANITIZE_STRING, FILTER_NULL_ON_FAILURE);
	$data['form_name'] = filter_var($data['form_name'], FILTER_SANITIZE_STRING, FILTER_NULL_ON_FAILURE);
}
if ($data['action'] == "get_config_page") {
	echo $module->make_config_page($data['form_name']);
} else {
	require_once str_replace("temp" . DIRECTORY_SEPARATOR, "", APP_PATH_TEMP) . "redcap_connect.php";
	require_once APP_PATH_DOCROOT . 'ProjectGeneral' . DIRECTORY_SEPARATOR. 'header.php';

	$project = new \Project($module->framework->getProjectId());
	$surveys = [];
	foreach($project->forms as $form_name => $form) {
		if (!empty($form["survey_id"])) {
			$surveys[] = [
				"form_name" => $form_name,
				"form_menu" => $form["menu"]
			];
		}
	}

	if (count($surveys) == 0) {
		echo "<p>Please enable surveys and add a survey instrument before configuring the dashboard.</p>";
	} else {
		?>
		<div>
			<h5>Survey Configuration</h5>
			<p>Select a survey to configure a dashboard for:</p>
			<div class='dropdown'>
				<button class='btn btn-outline-primary dropdown-toggle' type='button' id='form_picker' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>
					Survey Instruments
				</button>
				<div class='dropdown-menu form_picker_dd' aria-labelledby='form_picker'>
		<?php
		foreach($surveys as $survey) {
			echo '<a value="' . $survey["form_name"] . '"class="dropdown-item" href="#">' . $survey["form_menu"] . '</a>';
		}
		?>
				</div>
			</div>
			<div id='form_assocs'>
			</div>
		</div>
		<script>
			PatientAccessSplit = {
				configAjaxUrl: <?=json_encode($module->getUrl("config.php"))?>,
				saveConfigUrl: <?=json_encode($module->getUrl("save_changes.php"))?>
			}
		</script>
		<script type="text/javascript" src="<?=$module->getUrl("js/config.js")?>"></script>
		<?php
	}

	require_once APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
}