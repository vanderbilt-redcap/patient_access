<?php
namespace Vanderbilt\PatientAccessSplit;

class PatientAccessSplit extends \ExternalModules\AbstractExternalModule {
	function redcap_survey_page($project_id, $record = NULL, $instrument, $event_id, $group_id = NULL, $survey_hash, $response_id = NULL, $repeat_instance = 1) {
		// get settings so we can fetch icon and link info
		$settings = $this->framework->getProjectSetting($instrument);
		if (!empty($settings)) {
			$settings = json_decode($settings, true);
		} else {
			return null;
		}
		
		$this->add_icon_db_info($settings);
		
		// start building html string
		$html = '
<div id="dashboard">
	<h2 class="title mt-2 mb-2">' . $settings['dashboard_title'] . '</h2>
	<div id="icons">';
		
		foreach ($settings["icons"] as $i => $icon) {
			$uri = base64_encode(file_get_contents(EDOC_PATH . $icon["stored_name"]));
			$iconSrc = "data: {$icon["mime_type"]};base64,$uri";
			$html .= "
			<button class='btn icon-button' data-icon-index='$i' type='button'>
				<img class='icon' src='$iconSrc' ></img><br><small>{$icon["label"]}</small>
			</button>";
		}
		$html .= '
	</div>
	<div id="iconLinks">
		<h5>Links</h5>
		<div>
			<ul>
			</ul>
		</div>
	</div>
</div>';
		
		// table used to ensure odd/even numbering of footer links
		$footer_html = "
		<div id='pasfooter'>
			<table>
				<tr>";
		
		// add odd numbered footer links row
		foreach ($settings["foot_link_url"]["value"] as $j => $footerLink) {
			if ($j % 2 == 0) {
				$footer_html .= "
					<td><a class='footer' target='_blank' rel='noopener noreferrer' href='$footerLink'>{$settings["foot_link_label"]["value"][$j]}</a></td>";
			}
		}
		$footer_html .= "
				</tr>
				<tr>";
		
		// add even numbered footer links row
		foreach ($settings["foot_link_url"]["value"] as $j => $footerLink) {
			if ($j % 2 != 0) {
				$footer_html .= "
					<td><a class='footer' target='_blank' rel='noopener noreferrer' href='$footerLink'>{$settings["foot_link_label"]["value"][$j]}</a></td>";
			}
		}
		$footer_html .= "
				</tr>
			</table>
		</div>";
		
		// inject some js to the survey page (e.g., http://localhost/redcap/surveys/?s=YAECPTMT8F) to clear container div and inject our own dashboard
		$dashboardScript = file_get_contents($this->getUrl("js/dash.js"));
		$dashboardScript = str_replace("CSS_URL", $this->getUrl("css/dash.css"), $dashboardScript);
		$dashboardScript = str_replace("DASH_HTML", "`$html`", $dashboardScript);
		$dashboardScript = str_replace("FOOTER_HTML", "`$footer_html`", $dashboardScript);
		$linksTableJSON = json_encode($iconLinks);
		$settings = json_encode($settings);
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
		$settings = $this->framework->getProjectSetting($form_name);
		if (!empty($settings)){
			$settings = json_decode($settings, true);
			
			// add icons to document to be later moved via config js
			$rich_settings = $this->add_icon_db_info($settings);
			foreach ($rich_settings['icons'] as $i => $icon) {
				echo $this->get_icon_img($form_name, $i);
			}
			unset($rich_settings);
			
			?>
			<script type='text/javascript'>
				PatientAccessSplit.settings = JSON.parse('<?=json_encode($settings)?>')
				
				// // convert object to array // (not needed now thanks to 0-based everywhere)
				// if (PatientAccessSplit.settings.icons) {
					// var temp_icons_obj = PatientAccessSplit.settings.icons
					// PatientAccessSplit.settings.icons = []
					// for (var i in temp_icons_obj) {
						// PatientAccessSplit.settings.icons[i] = temp_icons_obj[i]
					// }
					// delete temp_icons_obj
				// }
				
				if (PatientAccessSplit.settings.dashboard_title) {
					$("#dashboard_title").val(PatientAccessSplit.htmlDecode(PatientAccessSplit.settings.dashboard_title))
				}
				for (var i in PatientAccessSplit.settings.icons) {
					var icon = PatientAccessSplit.settings.icons[i]
					PatientAccessSplit.newIcon()
					var iconElement = $("#icons").children(":last")
					
					// move preview img to this icon sub-form
					$(iconElement).find('.preview').append($("#icon-preview-" + i).detach())
					$("#icon-preview-" + i).show()
					
					// icon label
					$(iconElement).find('.icon-label').val(icon.label)
					
					// icon edoc_id
					if (icon.edoc_id)
						$(iconElement).attr('data-icon-edoc-id', icon.edoc_id)
					
					for (var j in icon.links) {
						var link = icon.links[j]
						PatientAccessSplit.newLink(iconElement)
						var linkElement = $(iconElement).find('.link-form:last')
						
						// link label and url
						$(linkElement).find('.link-label').val(link.label)
						$(linkElement).find('.link-url').val(link.url)
					}
				}
				
				// TODO: update footer links if able
			</script>
			<?php
		}
	}
	
	function add_icon_db_info($settings) {
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
		return $settings;
	}
	
	function get_icon_img($form_name, $iconIndex) {
		$settings = $this->framework->getProjectSetting($form_name);
		$settings = json_decode($settings, true);
		if (!empty($settings) and !empty($settings['icons']) and !empty($settings['icons'][$iconIndex])) {
			$this->add_icon_db_info($settings);
			$path = EDOC_PATH . $settings['icons'][$iconIndex]['stored_name'];
			$uri = base64_encode(file_get_contents($path));
			$iconSrc = "data: {$icon["mime_type"]};base64,$uri";
			return "<img style='display: none' class='icon-preview' id='icon-preview-$iconIndex' src = '$iconSrc'>";
		}
	}
}