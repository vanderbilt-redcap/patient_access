<?php
namespace Vanderbilt\PatientAccessSplit;

class PatientAccessSplit extends \ExternalModules\AbstractExternalModule {
	function redcap_survey_page($project_id, $record = NULL, $instrument, $event_id, $group_id = NULL, $survey_hash, $response_id = NULL, $repeat_instance = 1) {
		// file_put_contents("C:/vumc/log.txt", "beginning log...\n");
		
		// get settings so we can fetch icon and link info
		$settings = $this->framework->getProjectSetting($instrument);
		if (!empty($settings)) {
			$settings = json_decode($settings, true);
		} else {
			return null;
		}
		
		file_put_contents("C:/vumc/log.txt", "\$settings from redcapsurveypage :\n" . print_r($settings, true) . "\n");
		
		// build icons array so we can send 1 query to db for icon file paths
		$edoc_ids = [];
		foreach ($settings["icons"] as $icon) {
			if (!empty($icon['edoc_id'])) {
				$edoc_ids[] = $icon['edoc_id'];
			}
		}
		
		// query db for icon file paths on server
		$edoc_ids = "(" . implode(", ", $edoc_ids) . ")";
		$sql = "SELECT doc_id, stored_name, mime_type FROM redcap_edocs_metadata WHERE doc_id in $edoc_ids";
		$result = db_query($sql);
		while ($row = db_fetch_assoc($result)) {
			foreach ($settings["icons"] as $i => $icon) {
				if ($icon["edoc_id"] == $row["doc_id"]) {
					$settings["icons"][$i]["stored_name"] = $row["stored_name"];
					$settings["icons"][$i]["mime_type"] = $row["mime_type"];
				}
			}
		}
		
		// start building html string
		$html = '
<div id="dashboard">
	<div id="icons" class="card">
		<h3 class="card-title">' . $settings['dashboard_title'] . '</h3>
		<div class="card-body">';
		
		foreach ($settings["icons"] as $i => $icon) {
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
		$dashboardScript = file_get_contents($this->getUrl("js/dash.js"));
		$dashboardScript = str_replace("CSS_URL", $this->getUrl("css/dash.css"), $dashboardScript);
		$dashboardScript = str_replace("BOOTSTRAP_URL", $this->getUrl("js/bootstrap.min.js"), $dashboardScript);
		$dashboardScript = str_replace("DASH_HTML", "`$html`", $dashboardScript);
		$dashboardScript = str_replace("FOOTER_HTML", "`$footer_html`", $dashboardScript);
		$linksTableJSON = json_encode($iconLinks);
		$settings = json_encode($settings);
		// file_put_contents("C:/vumc/log.txt", "\$settings:\n" . print_r($settings, true) . "\n", FILE_APPEND);
		$js = <<< EOF
		<script type="text/javascript">
			PatientAccessModule = {
				"iconLinks": JSON.parse(`$linksTableJSON`),
				"settings": JSON.parse(`$settings`)
			};
			$dashboardScript
		</script>
EOF;
		echo($js);
	}
	
	function make_config_page($form_name) {
		$settings = $this->getProjectSettings();
		
		// add dashboard title input option
		?>
		<h5 class="mt-3">Dashboard Title</h5>
		<input type="text" style="width: 400px" class="form-control" id="dashboard_title" aria-describedby="dashboard_title"></input>
		<h5 class='mt-3'>Icons</h5>
		<button type='button' class='btn btn-outline-secondary small-text new-icon'><i class="fas fa-plus"></i> New Icon</button>
		<div id='icons' class='mt-3'>
		</div>
		<button id='save_changes' class='btn btn-outline-primary mt-3' type='button'>Save Changes</button>
		<link rel="stylesheet" href="<?=$this->getUrl('css/config.css')?>"/>
		<?php
	}
}