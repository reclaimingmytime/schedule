<?php
header('Content-Type: application/manifest+json');

require_once('config.php');

function arrayToUnescapedJson($array) {
	return json_encode($array, JSON_UNESCAPED_SLASHES);
}

if(isset($manifest)) echo arrayToUnescapedJson($manifest);
