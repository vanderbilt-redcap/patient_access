<?php

file_put_contents("C:/log.txt", 'logging\n')
file_put_contents("C:/log.txt", print_r($_FILES, true), FILE_APPEND)
file_put_contents("C:/log.txt", print_r($_POST, true), FILE_APPEND)

// // sanitize user input
// $filtered = [];
// $filtered['form_name'] = filter_var($data['form_name'], FILTER_SANITIZE_STRING, FILTER_NULL_ON_FAILURE);
// $filtered['dashboard_title'] = filter_var($data['dashboard_title'], FILTER_SANITIZE_STRING, FILTER_NULL_ON_FAILURE);
// $filtered["icons"] = [];

// foreach($data["icons"] as $icon) {
	
// }

// if (empty($filtered['dashboard_title']) and empty($filtered['icons'])) {
	// // delete settings and send user feedback
// }

echo json_encode([
	"success" => true
]);