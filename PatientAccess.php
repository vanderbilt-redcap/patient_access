<?php
namespace Vanderbilt\PatientAccess;

class PatientAccess extends \ExternalModules\AbstractExternalModule {
	function redcap_survey_page($project_id, $record = NULL, $instrument, $event_id, $group_id = NULL, $survey_hash, $response_id = NULL, $repeat_instance = 1) {
		file_put_contents("C:/xampp/htdocs/redcap/modules/patient_access_v0.1/log.txt", "beginning log...\n");
		
		// get settings so we can fetch icon and link info
		$settings = $this->getProjectSettings();
		
		// build icons array so we can send 1 query to db for icon file paths
		$icons = [];
		$doc_ids = [];
		foreach ($settings["icon_upload"]["value"] as $i => $doc_id) {
			if (!empty($doc_id)) {
				$icons[$i] = [
					"doc_id" => $doc_id,
					"label" => $settings["icon_label"]["value"][$i]
				];
				$doc_ids[] = $doc_id;
			}
		}
		
		// query db for icon file paths on server
		$doc_ids = "(" . implode($doc_ids, ", ") . ")";
		$sql = "SELECT doc_id, stored_name FROM redcap_edocs_metadata WHERE doc_id in $doc_ids";
		$result = db_query($sql);
		while ($row = db_fetch_assoc($result)) {
			foreach ($icons as $i => $iconArray) {
				if ($iconArray["doc_id"] == $row["doc_id"]) {
					$icons[$i]["path"] = $row["stored_name"];
				}
			}
		}
		
		// start building html string
		$html = '
<div id="dashboard">';
		
		$counter = 0;
		$maxIconsPerRow = 5;
		$rowCounter = 1;
		foreach ($icons as $i => $icon) {
			// if counter == 0, need new row
			if ($counter == 0) {
				$targetLinkSet = "linkset$rowCounter";
				$html .= "
	<div class=\"card\">
		<div class=\"icons card-title\">";
			}
			// add icon button to row
			// $iconPath = $icon["path"];
			// $iconLabel = $icon["label"];
			$html .= "
			<button class=\"btn btn-primary\" target=\"$targetLinkSet\" type=\"button\">
				<img src=\"/redcap/edocs/{$icon["path"]}\" width=\"24\" height=\"24\"></img>&nbsp&nbsp&nbsp{$icon["label"]}
			</button>";
			$counter++;
			
			// finish row if at max icons or last icon
			if ($counter == $maxIconsPerRow or $i == (count($icons) - 1)) {
				$html .= "
		</div>
		<div class=\"collapse linkset\" id=\"$targetLinkSet\">
			<div class=\"card card-body\">
				blah blah blah
			</div>
		</div>
	</div>";
				$rowCounter++;
				$counter = $maxIconsPerRow;
			}
		}
		$html .= "
</div>";
		
		// // alternatively, for testing, use baked html shim
		// $html = file_get_contents($this->getUrl("html/dashboard.html"));
		
		// inject some js to the survey page (e.g., http://localhost/redcap/surveys/?s=YAECPTMT8F) to clear container div and inject our own dashboard
		$dashboardScript = file_get_contents($this->getUrl("js/dash.js"));
		$dashboardScript = str_replace("CSS_URL", $this->getUrl("css/dash.css"), $dashboardScript);
		$dashboardScript = str_replace("BOOTSTRAP_URL", $this->getUrl("js/bootstrap.min.js"), $dashboardScript);
		$dashboardScript = str_replace("DASH_HTML", "`$html`", $dashboardScript);
		$js = <<< EOF
		<script type="text/javascript">
			$dashboardScript
		</script>
EOF;
		echo($js);
	}
}