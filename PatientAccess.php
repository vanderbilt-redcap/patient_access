<?php
namespace Vanderbilt\PatientAccess;

class PatientAccess extends \ExternalModules\AbstractExternalModule {
	function redcap_survey_page($project_id, $record = NULL, $instrument, $event_id, $group_id = NULL, $survey_hash, $response_id = NULL, $repeat_instance = 1) {
		// get and print dashboard elements
		$dashboardHtml = file_get_contents($this->getUrl("html/dashboard.html"));
		
		// inject some js to the survey page (e.g., http://localhost/redcap/surveys/?s=YAECPTMT8F) to clear container div and inject our own dashboard
		$dashboardScript = file_get_contents($this->getUrl("js/dash.js"));
		$dashboardScript = str_replace("CSS_URL", $this->getUrl("css/dash.css"), $dashboardScript);
		$dashboardScript = str_replace("BOOTSTRAP_URL", $this->getUrl("js/bootstrap.min.js"), $dashboardScript);
		$dashboardScript = str_replace("DASH_HTML", "`$dashboardHtml`", $dashboardScript);
		// $dashboardScript = str_replace("ICONPATH", $this->getUrl("resources/001-letter-a.png"), $dashboardScript);
		
		// get path to uploaded icon 1
		$settings = $this->getProjectSettings();
		// file_put_contents("C:/xampp/htdocs/redcap/modules/patient_access_v0.1/log.txt", print_r($settings, true));
		$doc_id = $settings["icon_upload"]["value"][0];
		$sql = "SELECT stored_name FROM redcap_edocs_metadata WHERE doc_id=$doc_id";
		$result = db_query($sql);
		$path = db_fetch_assoc($result)["stored_name"];
		file_put_contents("C:/xampp/htdocs/redcap/modules/patient_access_v0.1/log.txt", print_r("/redcap/edocs/$path", true));
		$dashboardScript = str_replace("ICONPATH", "/redcap/edocs/$path", $dashboardScript);
		$js = <<< EOF
		<script type="text/javascript">
			$dashboardScript
		</script>
EOF;
		echo($js);
	}
}