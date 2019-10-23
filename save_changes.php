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

function save_icon($file, $img_name) {
// return saved icon's edoc_id
// or collect $errors and return NULL
	global $module;
	global $errors;
	// global $settings;
	global $old_settings;
	global $new_settings;
	
	// check for transfer errors
	if ($file["error"] !== 0) {
		$errors[] = "There was a file transfer error for $img_name.";
	}
	
	// have file, so check name, size
	$filename = $file["name"];
	if (preg_match("/[^A-Za-z0-9. ()-]/", $filename)) {
		$errors[] = "Filename for $img_name had characters other than what is allowed: A-Z a-z 0-9 . ( ) -";
	}
	
	if (strlen($filename) > 127) {
		$errors[] = "Filename for $img_name has a name that exceeds the limit of 127 characters.";
	}
	
	$maxsize = file_upload_max_size();
	if ($maxsize !== -1) {
		if ($file["size"] > $maxsize) {
			$fileReadable = humanFileSize($file["size"], "MB");
			$serverReadable = humanFileSize($maxsize, "MB");
			$errors[] = "File size for $img_name ($fileReadable) exceeds server maximum upload size of $serverReadable.";
		}
	}
	
	if(!exif_imagetype($file['tmp_name'])) {
		$errors[] = "File for $img_name does not appear to be an image (.jpg, .jpeg, .png, .svg, or .gif).";
	}
	
	if (!empty($errors)) {
		$errors[] = "Due to the error(s) above, the icon file for $img_name wasn't saved in the settings. You can correct the errors and try to upload again.";
		return;
	} else {
		// save file and return edoc_id
		$new_edoc_id = $module->framework->saveFile($file['tmp_name']);
		return $new_edoc_id;
	}
}

function delete_icon_file($edoc_id) {
	$sql = "SELECT * FROM redcap_edocs_metadata WHERE doc_id=$edoc_id";
	$result = db_query($sql);
	while ($row = db_fetch_assoc($result)) {
		unlink(EDOC_PATH . $row["stored_name"]);
	}
}

function sanitize(&$array) {
	foreach ($array as $key => $val) {
		if (gettype($val) === 'string') {
			$array[$key] = filter_var($val, FILTER_SANITIZE_STRING);
		} elseif (gettype($val) === 'integer') {
			$array[$key] = intval($val);
		} elseif (gettype($val) === 'array') {
			sanitize($val);
		} 
	}
}

// uncomment to log locally
// file_put_contents("C:/vumc/log.txt", __FILE__ . " log:\n");
function _log($str) {
	// file_put_contents("C:/vumc/log.txt", $str . "\n", FILE_APPEND);
}
/////////////
/* strategy:
	save icon files, deleting old edoc_ids as necessary, collecting new edoc_ids generated
	save new settings
	delete any old, unnecessary edocs
*/

_log("\$_FILES: " . print_r($_FILES, true));
_log("\$_POST: " . print_r($_POST, true));

// sanitize new settings
if (isset($_POST['settings'])) {
	$new_settings = $_POST['settings'];
	$new_settings = json_decode($new_settings, true);
	sanitize($new_settings);
	_log("\$new_settings after sanitization: " . print_r($new_settings, true));
} else {
	$new_settings = [];
}

$form_name = $new_settings['form_name'];
if (empty($form_name)) {
	echo json_encode([
		"message" => "The Patient Access Module couldn't determine the form name, please contact your REDCap administrator."
	]);
	return;
}

// fetch old settings
$old_settings = $module->framework->getProjectSetting($form_name);
if (!empty($old_settings))
	$old_settings = json_decode($old_settings, true);

_log("\$old_settings: " . print_r($old_settings, true));


// collect old but still valid edoc ids (from iconForm)
$all_new_edoc_ids = [];
if (isset($new_settings['icons'])) {
	foreach ($new_settings['icons'] as $index => $icon) {
		if (!empty($new_settings['icons'][$index]['edoc_id'])) {
			$all_new_edoc_ids[$index] = $new_settings['icons'][$index]['edoc_id'];
		}
	}
}

// message we will send back: will append errors
$message = "Configuration settings have been saved.";

// save new dashboard logo if uploaded
if (!empty($_FILES['dashboard-logo'])) {
	$errors = [];
	$edoc_id = save_icon($_FILES['dashboard-logo'], 'the dashboard logo');
	if (!empty($errors)) {
		$message .= "<br>" . implode("<br", $errors);
	} else {
		// delete old icon if exists
		if (!empty($old_settings['dashboard-logo'])) {
			delete_icon_file($old_settings['dashboard-logo']);
		}
		_log("collected new logo/image file for $img_name: " . $edoc_id);
		$new_settings['dashboard-logo'] = intval($edoc_id);
	}
	unset($_FILES['dashboard-logo']);
}

// save new icon files, collecting edoc_ids
$iconIndex = 0;
$keys = array_keys($_FILES);
foreach ($keys as $key) {
	$iconIndex = intval(array_pop(explode("-", $key)));
	$img_name = "icon #$iconIndex";
	$errors = [];
	$edoc_id = save_icon($_FILES[$key], $img_name);
	if (!empty($errors)) {
		$message .= "<br>" . implode("<br", $errors);
	} elseif (!empty($edoc_id)) {
		_log("collected new icon file edoc_id for $img_name: " . $edoc_id);
		$all_new_edoc_ids[$iconIndex] = intval($edoc_id);
		$new_settings['icons'][$iconIndex]['edoc_id'] = intval($edoc_id);
	}
}

_log("\$all_new_edoc_ids: \n" . print_r($all_new_edoc_ids, true));

// remove unnecessary icon files from server
if (isset($old_settings['icons'])) {
	foreach ($old_settings['icons'] as $index => $icon) {
		// if new settings don't include this edoc_id, then delete file
		$id_needed = !(array_search(intval($icon['edoc_id']), $all_new_edoc_ids) === false);
		if (!empty($icon['edoc_id']) and !$id_needed) {
			_log("deleting old icon for icon $index -- edoc_id: " . $icon['edoc_id']);
			delete_icon_file($icon['edoc_id'], $index);
		}
	}
}

_log("Final settings after adding uploaded_edoc_ids...\n" . print_r($new_settings, true));

$module->framework->setProjectSetting($form_name, json_encode($new_settings));

echo json_encode([
	"success" => true,
	"message" => "$message",
	"new_settings" => json_encode($new_settings)
]);