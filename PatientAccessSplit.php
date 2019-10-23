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
		
		$settings = $this->add_icon_db_info($settings);
		_log("\$settings: " . print_r($settings, true));
		
		// start building html string
		$html = '
<div id="dashboard">
	<h2 class="title m3">' . $settings['dashboard_title'] . '</h2>';
	
	if (!empty($settings['dashboard-logo'])) {
		$uri = base64_encode(file_get_contents(EDOC_PATH . $settings['dashboard-logo']['stored_name']));
		$iconSrc = "data: {$settings['dashboard-logo']['mime_type']};base64,$uri";
		$html .= "\
		<div id='dash-logo' class='my-3'><img src='$iconSrc'></div>";
	}
	
	$html .='
	<div id="icons" class="my-3">';
		
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
		foreach ($settings["footer_links"] as $j => $link) {
			$label = $link['label'];
			$url = $link['url'];
			if (empty($label))
				$label = $url;
			if ($j % 2 == 0 && !empty($url)) {
				$footer_html .= "
					<td><a class='footer' target='_blank' rel='noopener noreferrer' href='$url'>$label</a></td>";
			}
		}
		$footer_html .= "
				</tr>
				<tr>";
		
		// add even numbered footer links row
		foreach ($settings["footer_links"] as $j => $link) {
			$label = $link['label'];
			$url = $link['url'];
			if (empty($label))
				$label = $url;
			if ($j % 2 != 0 && !empty($url)) {
				$footer_html .= "
					<td><a class='footer' target='_blank' rel='noopener noreferrer' href='$url'>$label</a></td>";
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
		
		<h3 id='logo-title' class='mt-3'>Dashboard Image/Logo</h3>
		<div class='logo-upload custom-file'>
			<input type='file' class='custom-file-input' id='logo-input' aria-describedby='upload'>
			<label class='custom-file-label text-truncate' for='logo-input'>Choose an image</label>
		</div>
		<div class='logo-preview'>
			
		</div>
		
		<h5 class='mt-3'>Icons</h5>
		<button type='button' class='btn btn-outline-secondary small-text new-icon'><i class="fas fa-plus"></i> New Icon</button>
		<div id='icons' class='mt-3'>
		</div>
		<h5 class='mt-3'>Footer Links</h5>
		<button type='button' class='btn btn-outline-secondary small-text new-footer-link'><i class="fas fa-plus"></i> New Footer Link</button>
		<div id='footer-links' class='mt-3'>
		</div>
		
		<br>
		<button id='save_changes' class='btn btn-outline-primary mt-3' type='button'>Save Changes</button>
		<div id='json-buttons' class='d-flex my-3'>
			<button class='btn btn-outline-secondary' onclick='PatientAccessSplit.downloadJSON("patient_access_module_settings_" + new Date().getTime(), JSON.stringify(PatientAccessSplit.settings))'>Settings Export</button>
			<div class='json-upload custom-file ml-3'>
				<input type='file' class='custom-file-input' id='json-import' aria-describedby='upload'>
				<label class='custom-file-label text-truncate' for='json-import'>Settings Import</label>
			</div>
		</div>
		<link rel="stylesheet" href="<?=$this->getUrl('css/config.css')?>"/>
		<?php
		$settings = $this->framework->getProjectSetting($form_name);
		if (!empty($settings)){
			$settings = json_decode($settings, true);
			// file_put_contents("C:/vumc/log.txt", print_r($settings, true));
			// add icons to document to be later moved via config js
			$rich_settings = $this->add_icon_db_info($settings);
			_log("inital rich settings:\n" . print_r($rich_settings, true));
			foreach ($rich_settings['icons'] as $i => $icon) {
				if (isset($icon['edoc_id']))
					echo $this->get_icon_img($rich_settings, $i);
			}
			
			if (!empty($rich_settings['dashboard-logo'])) {
				$logo_edoc_id = $rich_settings['dashboard-logo']['edoc_id'];
				$path = EDOC_PATH . $rich_settings['dashboard-logo']['stored_name'];
				$uri = base64_encode(file_get_contents($path));
				if (!empty($uri)) {
					$iconSrc = "data: {$rich_settings['dashboard-logo']["mime_type"]};base64,$uri";
					echo "<img style='display: none' id='logo-preview-image' src = '$iconSrc'>";
				}
			}
			unset($rich_settings);
			
			?>
			<script type='text/javascript'>
				PatientAccessSplit.settings = JSON.parse('<?=json_encode($settings)?>')
				PatientAccessSplit.max_file_uploads = JSON.parse('<?=ini_get("max_file_uploads")?>')
				if (PatientAccessSplit.settings.dashboard_title) {
					$("#dashboard_title").val(PatientAccessSplit.htmlDecode(PatientAccessSplit.settings.dashboard_title))
				}
				if ($('#logo-preview-image').length > 0) {
					$("div.logo-preview").append($("#logo-preview-image").show().detach())
					$('.logo-preview').attr('data-edoc-id', <?=$logo_edoc_id?>)
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
				
				for (var i in PatientAccessSplit.settings.footer_links) {
					var link = PatientAccessSplit.settings.footer_links[i]
					PatientAccessSplit.newFooterLink()
					var linkElement = $("#footer-links").find('.footer-link:last')
					
					// link label and url
					$(linkElement).find('.link-label').val(link.label)
					$(linkElement).find('.link-url').val(link.url)
				}
			</script>
			<?php
		}
	}
	
	function add_icon_db_info($settings) {
		// build icons array so we can send 1 query to db for icon file paths
		$edoc_ids = [];
		$settings = $settings;
		foreach ($settings["icons"] as $i => $icon) {
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
					_log("enriching icon setting $i: " . $row['stored_name']);
					$settings["icons"][$i]["stored_name"] = $row["stored_name"];
					$settings["icons"][$i]["mime_type"] = $row["mime_type"];
				}
			}
		}
		
		// handle similar for dashboard-logo
		if (!empty($settings['dashboard-logo'])) {
			// file_put_contents("C:/vumc/log.txt", 'not empty ' . $settings['dashboard-logo']);
			$result = db_query("SELECT doc_id, stored_name, mime_type FROM redcap_edocs_metadata WHERE doc_id=" . $settings['dashboard-logo']);
			while ($row = db_fetch_assoc($result)) {
				_log("enriching dashboard-logo setting: " . $row['stored_name']);
				$edoc_id = $settings['dashboard-logo'];
				$settings['dashboard-logo'] = [
					"edoc_id" => $edoc_id,
					"stored_name" => $row["stored_name"],
					"mime_type" => $row["mime_type"]
				];
			}
		}
		
		return $settings;
	}
	
	function get_icon_img($settings, $iconIndex) {
		_log("fetching img for icon: $iconIndex");
		if (!empty($settings) and !empty($settings['icons']) and !empty($settings['icons'][$iconIndex])) {
			$path = EDOC_PATH . $settings['icons'][$iconIndex]['stored_name'];
			$uri = base64_encode(file_get_contents($path));
			if (empty($uri))
				return;
			$iconSrc = "data: {$icon["mime_type"]};base64,$uri";
			return "<img style='display: none' class='icon-preview' id='icon-preview-$iconIndex' src = '$iconSrc'>";
		} else {
			
		}
	}
}

// uncomment to log locally
// file_put_contents("C:/log.txt", __FILE__ . " log:\n");
function _log($str) {
	// file_put_contents("C:/log.txt", $str . "\n\n", FILE_APPEND);
}