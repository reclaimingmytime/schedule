<?php

/* Date preparation */
if(empty($minDate)) {
	$minDate = date("d.m.Y", 0);
}

$min = new DateTime($minDate);

function validDate($min, $input) {
	//DateTime even detects 31st Feb and 31st Nov as errors
	$date = DateTime::createFromFormat('Y-m-d', $input);
	$date_errors = DateTime::getLastErrors();

	return $date >= $min && $date_errors['warning_count'] === 0 && $date_errors['error_count'] === 0;
}

function getCustomDate($param, $today, $min) {
	if (isset($_GET[$param]) && validDate($min, $_GET[$param])) {
		return $_GET[$param];
	}
	return $today;
}

function createNewDate($date, $interval = "today") {
	return date("Y-m-d", strtotime($interval, strtotime($date)));
}

function createTime($input) {
	return DateTime::createFromFormat('H:i', $input);
}

function isWeekend($date) {
	$weekDay = date('w', strtotime($date));
	return ($weekDay == 0 || $weekDay == 6);
}

$today = date("Y-m-d");
$currentTime = date("H:i");

$desiredDate = getCustomDate("date", $today, $min);

$weekBump = false;

if(isset($excludeWeekends) && isTrue($excludeWeekends)) {
	if(isWeekend($desiredDate)) {
		$desiredDate = createNewDate($desiredDate, "1 weekday");
		$weekBump = true;
	}
	
	if(isWeekend($today)) {
		$today = createNewDate($today, "1 weekday");
		if($today == $desiredDate) {
			$weekBump = true;
		}
	}
	
	$weekDayString = "weekday";
} else {
	$weekDayString = "day";
}

$desiredDateObj = new DateTime($desiredDate);

$desiredDatePretty = $desiredDateObj->format("d.m.y");
$weekDay = $desiredDateObj->format("D");
$displayedDateFull = $weekDay . ", " . $desiredDatePretty;
$displayedDate = $desiredDatePretty;

$nextWeek = createNewDate($desiredDate, "1 week");
$nextDay = createNewDate($desiredDate, "1 $weekDayString");

$prevDay = createNewDate($desiredDate, "1 $weekDayString ago");
$prevWeek = createNewDate($desiredDate, "1 week ago");

if($prevWeek < createNewDate($minDate)) {
	$prevWeek = "none";
}

if($prevDay < createNewDate($minDate)) {
	$prevDay = "none";
}

/* API connection */
function validClass($class, $allowedClasses) {
	return !empty($class) && in_array($class, $allowedClasses);
}

function getClass($defaultClass, $allowedClasses, $desiredDate, $token) {
	$classGET = getParameter("class");
	$classCookie = getCookie("class");

	$class = getInput($classGET, $classCookie);

	if (validClass($class, $allowedClasses)) {
		if (!empty($classGET) && isDifferent($classGET, $classCookie)) {
			if (validToken(getParameter("token"), $token)) {
				writeCookie("class", $class, "1 year");
			}
			redirect("?date=" . $desiredDate);
		}
		return $class;
	}
	//use cookie as fallback
	if (validClass($classCookie, $allowedClasses)) {
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

function retrieveData($api, $cache_filename, $type) {
	include('classes/CalFileParser.php');
	$cal = new CalFileParser();
	
	if (is_writable($cache_filename) && (filemtime($cache_filename) > strtotime('now -1 day'))) {
		$cache_file = file_get_contents($cache_filename);
	} else {
		try {
			$cache_file = file_get_contents($api);
		} catch (Exception $ex) {
			die("Unable to reach API.");
		}
		//refresh cache
		if($type == 'ical') {
			$cache_file = $cal->parse($api, 'json');
		}
		file_put_contents($cache_filename, $cache_file, LOCK_EX);
	}

	if (empty($cache_file) || $cache_file === false) {
		die("Error connecting to API.");
	}

	$calendar = json_decode($cache_file, true);
	return defined('CALENDAR') ? $calendar[CALENDAR] : $calendar;
}

if(!isset($allowedClasses)) {
	$allowedClasses = [];
}

if(isset($type) && $type !== 'ical') {
	if((!isset($defaultClass) || !isset($api))) {
		die('Empty or invalid API. Please specify $api and $defaultClass in your config file in the following format:<br><b>$api</b> = https://example.com/api.json?class=<b>$defaultClass</b>');
	}
	$desiredClass = getClass($defaultClass, $allowedClasses, $desiredDate, $token);
	$desiredAPI = getAPIUrl($api, $desiredClass, $defaultClass);
	$cache_filename = $desiredClass . ".json";
} else {
	$desiredAPI = $api;
	$cache_filename = "cache.json";
}

$folder = "cache/";
createCache($folder);

$cache_file = $folder . $cache_filename;
$calendar = retrieveData($desiredAPI, $cache_file, $type);