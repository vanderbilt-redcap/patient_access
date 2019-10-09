<?php
require_once str_replace("temp" . DIRECTORY_SEPARATOR, "", APP_PATH_TEMP) . "redcap_connect.php";

// define image uploading helper functions
/////////////
// from: https://stackoverflow.com/questions/15188033/human-readable-file-size
function humanFileSize($size,$unit="") {
  if( (!$unit && $size >= 1<<30) || $unit == "GB")
    return number_format($size/(1<<30),2)."GB";
  if( (!$unit && $size >= 1<<20) || $unit == "MB")
    return number_format($size/(1<<20),2)."MB";
  if( (!$unit && $size >= 1<<10) || $unit == "KB")
    return number_format($size/(1<<10),2)."KB";
  return number_format($size)." bytes";
}

function parse_size($size) {
  $unit = preg_replace('/[^bkmgtpezy]/i', '', $size); // Remove the non-unit characters from the size.
  $size = preg_replace('/[^0-9\.]/', '', $size); // Remove the non-numeric characters from the size.
  if ($unit) {
    // Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
    return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
  }
  else {
    return round($size);
  }
}

// from: https://stackoverflow.com/questions/13076480/php-get-actual-maximum-upload-size
function file_upload_max_size() {
  static $max_size = -1;

  if ($max_size < 0) {
    // Start with post_max_size.
    $post_max_size = parse_size(ini_get('post_max_size'));
    if ($post_max_size > 0) {
      $max_size = $post_max_size;
    }

    // If upload_max_size is less, then reduce. Except if upload_max_size is
    // zero, which indicates no limit.
    $upload_max = parse_size(ini_get('upload_max_filesize'));
    if ($upload_max > 0 && $upload_max < $max_size) {
      $max_size = $upload_max;
    }
  }
  return $max_size;
}

function save_icon($file, $iconIndex) {
// return saved icon's edoc_id
// or collect $errors and return NULL
	global $filtered;
	global $errors;
	global $settings;
	global $module;
	$form_name = $filtered["form_name"];
	// check for transfer errors
	if ($file["error"] !== 0) {
		$errors[] = "There was a file transfer error for icon #$iconIndex.";
	}
	
	// have file, so check name, size
	$filename = $file["name"];
	if (preg_match("/[^A-Za-z0-9. ()-]/", $filename)) {
		$errors[] = "Filename for icon #$iconIndex had characters other than what is allowed: A-Z a-z 0-9 . ( ) -";
	}
	
	if (strlen($filename) > 127) {
		$errors[] = "Filename for icon #$iconIndex has a name that exceeds the limit of 127 characters.";
	}
	
	$maxsize = file_upload_max_size();
	if ($maxsize !== -1) {
		if ($file["size"] > $maxsize) {
			$fileReadable = humanFileSize($file["size"], "MB");
			$serverReadable = humanFileSize($maxsize, "MB");
			$errors[] = "File size for icon #$iconIndex ($fileReadable) exceeds server maximum upload size of $serverReadable.";
		}
	}
	
	if(!exif_imagetype($file['tmp_name'])) {
		$errors[] = "File for icon #$iconIndex does not appear to be an image (.jpg, .jpeg, .png, .svg, or .gif).";
	}
	
	if (!empty($errors)) {
		return;
	} else {
		// save file and return edoc_id
		
		// but first delete old icon if exists
		if (!empty($settings) and !empty($settings['icons']) and !empty($settings['icons'][$iconIndex])) {
			$old_edoc_id = $settings['icons'][$iconIndex];
			if (!empty($old_edoc_id)) {
				$sql = "SELECT * FROM redcap_edocs_metadata WHERE doc_id=$old_edoc_id";
				$result = db_query($sql);
				while ($row = db_fetch_assoc($result)) {
					unlink(EDOC_PATH . $row["stored_name"]);
				}
			}
		}
		$new_edoc_id = $module->framework->saveFile($file['tmp_name']);
		return $new_edoc_id;
	}
}
/////////////

file_put_contents("C:/vumc/log.txt", print_r($_FILES, true));
file_put_contents("C:/vumc/log.txt", print_r($_POST, true), FILE_APPEND);
exit();

// sanitize user input
$data = $_POST;
$errors = [];
$filtered = [];
$filtered['form_name'] = filter_var($data['form_name'], FILTER_SANITIZE_STRING, FILTER_NULL_ON_FAILURE);

// retrieve any previously saved settings
$settings = $module->getProjectSetting($filtered["form_name"]);
if (!empty($settings))
	$settings = json_decode($settings, true);

if (empty($filtered['form_name'])) {
	// send error feedback
	echo(json_encode([
		"message" => "Couldn't detect a form name. Please contact REDCap administrator with issues regarding the Patient Access (Split) module:<br>" . implode("<br>", $errors)
	]));
	return;
}

$filtered['dashboard_title'] = filter_var($data['dashboard_title'], FILTER_SANITIZE_STRING, FILTER_NULL_ON_FAILURE);
$filtered["icons"] = [];

$i = 1;
while (true) {
	if (empty($_FILES["icon-$i"]) and empty($data["icon-label-$i"])) {
		break;
	} else {
		$filtered["icons"][$i] = [];
		
		// handle keeping or overwriting icon file
		if (empty($_FILES["icon-$i"])) {
			// icon previously saved so keep edoc_id
			$edoc_id = filter_var($data["icon-edoc-id-$i"], FILTER_SANITIZE_NUMBER_INT, FILTER_NULL_ON_FAILURE);
			if (!empty($edoc_id))
				$filtered['icons'][$i]['edoc_id'] = $edoc_id;
		} else {
			// overwrite with new icon and delete old icon file
			$edoc_id = save_icon($_FILES["icon-$i"], $i);
			if (!empty($edoc_id)) {
				$filtered["icons"][$i]["edoc_id"] = $edoc_id;
			}
		}
		
		if (!empty($data["icon-label-$i"])) {
			$filtered["icons"][$i]["label"] = filter_var($data["icon-label-$i"], FILTER_SANITIZE_STRING, FILTER_NULL_ON_FAILURE);
		}
		if (empty($filtered["icons"][$i])) {
			// no valid data, so erase
			unset($filtered["icons"][$i]);
			$i++;
			continue;
		}
		
		// iterate over links in icon
		$filtered["icons"][$i]["links"] = [];
		$j = 1;
		while (true) {
			if (empty($data["link-label-$i-$j"]) and empty($data["link-url-$i-$j"])) {
				break;
			} else {
				$filtered["icons"][$i]["links"][$j] = [];
				if (!empty($data["link-label-$i-$j"])) {
					$filtered["icons"][$i]["links"][$j]['label'] = filter_var($data["link-label-$i-$j"], FILTER_SANITIZE_STRING, FILTER_NULL_ON_FAILURE);
				}
				if (!empty($data["link-url-$i-$j"])) {
					$filtered["icons"][$i]["links"][$j]['url'] = filter_var($data["link-url-$i-$j"], FILTER_SANITIZE_STRING, FILTER_NULL_ON_FAILURE);
				}
				if (empty($filtered["icons"][$i]["links"][$j])) {
					// invalid data
					unset($filtered["icons"][$i]["links"][$j]);
					$j++;
					continue;
				}
			}
			$j++;
		}
		if (empty($filtered["icons"][$i]["links"])) {
			// no valid data, so erase
			unset($filtered["icons"][$i]["links"]);
		}
		$i++;
	}
}
if (empty($filtered["icons"]))
	unset($filtered["icons"]);

if (!empty($errors)) {
	exit(json_encode([
		"message" => "The Patient Access (Split) module encountered errors:<br>" . implode("<br>", $errors)
	]));
}

if (empty($filtered["icons"]) and empty($filtered['dashboard_title'])) {
	$module->framework->setProjectSetting($filtered['form_name'], NULL);
	exit(json_encode([
		"success" => true,
		"message" => "Deleted module settings"
	]));
} else {
	$module->framework->setProjectSetting($filtered['form_name'], json_encode($filtered));
}

echo json_encode([
	"success" => true,
	"message" => "Module settings have been saved"
]);