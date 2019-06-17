<?php
	header('Content-Type: image/png');
	readfile(EDOC_PATH . $_GET['img']);
?>