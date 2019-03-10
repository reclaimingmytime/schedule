<?php

/* API connection */
function validClass($class, $allowedClasses) {
	return !empty($class) && in_array($class, $allowedClasses);
}

function getClass($defaultClass, $allowedClasses) {
	$classGET = getParameter("class");
	$classCookie = getCookie("class");

	$class = getInput($classGET, $classCookie);

	if (validClass($class, $allowedClasses)) {
		if (isDifferent($classGET, $classCookie)) {
			global $token;
			if(validToken(getParameter("token"), $token)) {
				writeCookie("class", $class, "1 year");
			} else {
				return $defaultClass;
			}
		}
		return $class;
	}
	//use cookie as fallback
	if(validClass($classCookie, $allowedClasses)) {
		return $classCookie;
	}
	return $defaultClass;
}

function getAPIUrl($api, $replace, $default) {
	return str_replace($default, $replace, $api);
}

function createCache($folder) {
	if (!is_writable($folder)) { //checks both, exists & writable
		$errorMsg = 'Insufficient permission to create files and folders. Please give the parent directory sufficient permissions (at least chmod 700).';
		
		if (!file_exists($folder)) {
			try {
				mkdir($folder, 0700, true);
			} catch (Exception $ex) {
				die($errorMsg);
			}
		} 
		if (!is_writable($folder)) {
			die($errorMsg);
		}
	}
}

function retreiveData($api, $cache_file) {
	if (is_writable($cache_file) && (filemtime($cache_file) > (time() - 60 * 30 ))) {
		$file = file_get_contents($cache_file);
	} else {
		try {
			$file = file_get_contents($api);
		} catch (Exception $ex) {
			die("Unable to reach API.");
		}
		//refresh cache
		file_put_contents($cache_file, $file, LOCK_EX);
	}

	if (empty($file) || $file === false) {
		die("Error connecting to API.");
	}

	$calendar = json_decode($file, true);

	return defined('CALENDAR') ? $calendar[CALENDAR] : $calendar;
}

if(!isset($allowedClasses)) {
	$allowedClasses = [];
}

if(!isset($defaultClass) || !isset($api)) {
	die('Empty or invalid API. Please specify $api and $defaultClass in your config file in the following format:<br><b>$api</b> = https://example.com/api.json?class=<b>$defaultClass</b>');
}

$desiredClass = getClass($defaultClass, $allowedClasses);
$desiredAPI = getAPIUrl($api, $desiredClass, $defaultClass);

$folder = "cache/";
createCache($folder);

$cache_file = $folder . $desiredClass . ".json";
$calendar = retreiveData($desiredAPI, $cache_file);