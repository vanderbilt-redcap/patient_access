<?php
// file_put_contents("C:/vumc/log.txt", "import.php:\n");

// file_put_contents("C:/vumc/log.txt", "\$_POST:\n" . print_r($_POST, true) . "\n\n", FILE_APPEND);
$request = json_decode($_POST['json'], true);
// file_put_contents("C:/vumc/log.txt", "request:\n" . print_r($request, true) . "\n\n", FILE_APPEND);

if (strlen($request['settings']) >= 1000000) {
	echo json_encode([
		'alert' => 'Your settings JSON import file exceeds the 1MB limit, import aborted.'
	]);
	return;
}

$decoded = $request['settings'];
if ($request['form_name'] != $decoded['form_name']) {
	echo json_encode([
		'alert' => 'The settings you are importing has a form_name value that differs from the current page\'s form_name, import aborted.'
	]);
	return;
}

$new_settings = [];
$new_settings['form_name'] = filter_var($decoded['form_name'], FILTER_SANITIZE_STRING, FILTER_NULL_ON_FAILURE);
$new_settings['dashboard_title'] = filter_var($decoded['dashboard_title'], FILTER_SANITIZE_STRING, FILTER_NULL_ON_FAILURE);
$new_settings['dashboard-logo'] = filter_var($decoded['dashboard-logo'], FILTER_SANITIZE_NUMBER_INT, FILTER_NULL_ON_FAILURE);
foreach($decoded['icons'] as $i => $icon) {
	if (!isset($new_settings['icons']))
		$new_settings['icons'] = [];
	$new_settings['icons'][$i] = [];
	$new_settings['icons'][$i]['edoc_id'] = filter_var($icon['edoc_id'], FILTER_SANITIZE_NUMBER_INT, FILTER_NULL_ON_FAILURE);
	$new_settings['icons'][$i]['label'] = filter_var($icon['label'], FILTER_SANITIZE_STRING, FILTER_NULL_ON_FAILURE);
	foreach($icon['links'] as $j => $link) {
		if (!isset($new_settings['icons'][$i]['links']))
			$new_settings['icons'][$i]['links'] = [];
		$new_settings['icons'][$i]['links'][$j] = [];
		$new_settings['icons'][$i]['links'][$j]['label'] = filter_var($link['label'], FILTER_SANITIZE_STRING, FILTER_NULL_ON_FAILURE);
		$new_settings['icons'][$i]['links'][$j]['url'] = filter_var($link['url'], FILTER_SANITIZE_URL, FILTER_NULL_ON_FAILURE);
	}
}
foreach($decoded['footer_links'] as $i => $link) {
	if (!isset($new_settings['footer_links']))
		$new_settings['footer_links'] = [];
	$new_settings['footer_links'][$i] = [];
	$new_settings['footer_links'][$i]['label'] = filter_var($link['label'], FILTER_SANITIZE_STRING, FILTER_NULL_ON_FAILURE);
	$new_settings['footer_links'][$i]['url'] = filter_var($link['url'], FILTER_SANITIZE_URL, FILTER_NULL_ON_FAILURE);
}

// file_put_contents("C:/vumc/log.txt", "NEW SETTINGS!:\n" . print_r($new_settings, true), FILE_APPEND);
$module->setProjectSetting($new_settings['form_name'], json_encode($new_settings));
ob_start();
$module->make_config_page($new_settings['form_name']);
$html = ob_get_contents();
ob_end_clean();

// file_put_contents("C:/vumc/log.txt", "ob flush contents:\n" . print_r($html, true), FILE_APPEND);

echo json_encode([
	'alert' => 'Settings imported successfully.',
	'html' => $html
]);
?>