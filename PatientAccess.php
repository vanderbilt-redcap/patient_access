<?php
namespace Vanderbilt\PatientAccess;

class PatientAccess extends \ExternalModules\AbstractExternalModule {
	function redcap_survey_page($project_id, $record = NULL, $instrument, $event_id, $group_id = NULL, $survey_hash, $response_id = NULL, $repeat_instance = 1) {
		// file_put_contents("C:/xampp/htdocs/redcap/modules/patient_access_v0.1/log.txt", "beginning log...\n");
		
		// get settings so we can fetch icon and link info
		$settings = $this->getProjectSettings();
		
		if ($settings["survey_hash"]["value"] != $survey_hash) {
			// not the survey we want to override
			return;
		}
		// file_put_contents("C:/xampp/htdocs/redcap/modules/patient_access_v0.1/log.txt", print_r($settings, true) . "\n", FILE_APPEND);
		
		// build icons array so we can send 1 query to db for icon file paths
		$icons = [];
		$iconLinks = [];
		$doc_ids = [];
		foreach ($settings["icon_upload"]["value"] as $i => $doc_id) {
			if (!empty($doc_id)) {
				$icons[$i] = [
					"doc_id" => $doc_id,
					"label" => $settings["icon_label"]["value"][$i]
				];
				foreach ($settings["link_url"]["value"][$i] as $j => $url) {
					if (isset($settings["link_label"]["value"][$i][$j])) {
						if (!isset($iconLinks[$i]))
							$iconLinks[$i] = [];
						$iconLinks[$i][] = [
							"label" => $settings["link_label"]["value"][$i][$j],
							"url" => $url
						];
					}
				}
				$doc_ids[] = $doc_id;
			}
		}
		// file_put_contents("C:/xampp/htdocs/redcap/modules/patient_access_v0.1/log.txt", print_r($settings, true) . "\n", FILE_APPEND);
		
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
<div id="dashboard">
	<div id="icons" class="card">
		<h3 class="card-title">Patient Access Dashboard</h3>
		<div class="card-body">';
		
		foreach ($icons as $i => $icon) {
			$edocPath = EDOC_PATH;
			$html .= "
			<button class=\"btn btn-primary\" data-icon-index=\"$i\" type=\"button\">
				<img src=\"$edocPath{$icon["path"]}\" width=\"24\" height=\"24\"></img><small>{$icon["label"]}</small>
			</button>";
		}
		$html .= '
		</div>
	</div>
	<div id="iconLinks" class="card">
		<h5 class="card-title">Links</h5>
		<ul class="card-body">
		</ul>
	</div>
	<div id="footerLinks" class="card">
		<h5 class="card-title">More Resources</h5>
		<ul class="card-body">';
		foreach ($settings["foot_link_url"]["value"] as $j => $footerLink) {
			$html .= "
			<li><a href=\"$footerLink\">{$settings["foot_link_label"]["value"][$j]}</a></li>";
		}
		$html .= '
		</ul>
	</div>
</div>';
		
		// // alternatively, for testing, use baked html shim
		// $html = file_get_contents($this->getUrl("html/dashboard.html"));
		
		// inject some js to the survey page (e.g., http://localhost/redcap/surveys/?s=YAECPTMT8F) to clear container div and inject our own dashboard
		$dashboardScript = file_get_contents($this->getUrl("js/dash.js"));
		$dashboardScript = str_replace("CSS_URL", $this->getUrl("css/dash.css"), $dashboardScript);
		$dashboardScript = str_replace("BOOTSTRAP_URL", $this->getUrl("js/bootstrap.min.js"), $dashboardScript);
		$dashboardScript = str_replace("DASH_HTML", "`$html`", $dashboardScript);
		$linksTableJSON = json_encode($iconLinks);
		$js = <<< EOF
		<script type="text/javascript">
			PatientAccessModule = {
				"iconLinks": JSON.parse(`$linksTableJSON`)
			};
			$dashboardScript
		</script>
EOF;
		echo($js);
	}
}