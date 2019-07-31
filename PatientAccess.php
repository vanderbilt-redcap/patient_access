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
		$sql = "SELECT doc_id, stored_name, mime_type FROM redcap_edocs_metadata WHERE doc_id in $doc_ids";
		$result = db_query($sql);
		while ($row = db_fetch_assoc($result)) {
			foreach ($icons as $i => $iconArray) {
				if ($iconArray["doc_id"] == $row["doc_id"]) {
					$icons[$i]["stored_name"] = $row["stored_name"];
					$icons[$i]["mime_type"] = $row["mime_type"];
				}
			}
		}
		
		// start building html string
		$html = '
<div id="menu" class="container-fluid">
	<button class=\"btn\" type=\"button\">
		<i class="fas fa-bars"></i>
	</button>
</div>
<div id="dashboard">
	<div id="icons" class="card">
		<h3 class="card-title">' . $settings['dashboard_title']['value'] . '</h3>
		<div class="card-body">';
		
		foreach ($icons as $i => $icon) {
			$uri = base64_encode(file_get_contents(EDOC_PATH . $icon["stored_name"]));
			$iconSrc = "data: {$icon["mime_type"]};base64,$uri";
			$html .= "
			<button class=\"btn\" data-icon-index=\"$i\" type=\"button\">
				<img src=\"$iconSrc\" ></img><br><small>{$icon["label"]}</small>
			</button>";
		}
		$html .= '
		</div>
	</div>
	<div id="iconLinks" class="card">
		<h5 class="card-title">Links</h5>
		<div>
			<ul class="card-body">
			</ul>
		</div>
	</div>
</div>';
		
		$footer_html = "
		<div id='pasfooter'>
			<table>
				<tr>";
		
		// add odd numbered footer links row
		foreach ($settings["foot_link_url"]["value"] as $j => $footerLink) {
			if ($j % 2 == 0) {
				$footer_html .= "
					<td><a target=\"_blank\" rel=\"noopener noreferrer\" href=\"$footerLink\">{$settings["foot_link_label"]["value"][$j]}</a></td>";
			}
		}
		$footer_html .= "
				</tr>
				<tr>";
		
		// add even numbered footer links row
		foreach ($settings["foot_link_url"]["value"] as $j => $footerLink) {
			if ($j % 2 != 0) {
				$footer_html .= "
					<td><a target=\"_blank\" rel=\"noopener noreferrer\" href=\"$footerLink\">{$settings["foot_link_label"]["value"][$j]}</a></td>";
			}
		}
		$footer_html .= "
				</tr>
			</table>
		</div>";
		
		// // alternatively, for testing, use baked html shim
		// $html = file_get_contents($this->getUrl("html/dashboard.html"));
		
		// inject some js to the survey page (e.g., http://localhost/redcap/surveys/?s=YAECPTMT8F) to clear container div and inject our own dashboard
		$dashboardScript = file_get_contents($this->getUrl("js/dash2.js"));
		$dashboardScript = str_replace("CSS_URL", $this->getUrl("css/dash2.css"), $dashboardScript);
		$dashboardScript = str_replace("BOOTSTRAP_URL", $this->getUrl("js/bootstrap.min.js"), $dashboardScript);
		$dashboardScript = str_replace("DASH_HTML", "`$html`", $dashboardScript);
		$dashboardScript = str_replace("FOOTER_HTML", "`$footer_html`", $dashboardScript);
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