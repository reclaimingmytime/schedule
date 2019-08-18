<?php

/* Date preparation */
if(empty($minDate)) {
	$minDate = date("d.m.Y", 0);
}

if(empty($maxDate)) {
	$maxDate = "31.12." . (date("Y") + 100);
}

$min = new DateTime($minDate);
$max = new DateTime($maxDate);

function hasNoDateErrors($date_errors) {
	return $date_errors['warning_count'] === 0 && $date_errors['error_count'] === 0;
}

function validDate($min, $max, $input) {
	//The ! resets the time to midnight
	$date = DateTime::createFromFormat('!Y-m-d', $input);
	$date_errors = DateTime::getLastErrors();
	
	return isBetween($date, $min, $max) && hasNoDateErrors($date_errors);
}

function getCustomDate($param, $today, $min, $max) {
	if (isset($_GET[$param])) {
	  if(validDate($min, $max, $_GET[$param])) {
		  return $_GET[$param];
    }
    $_SESSION['validDate'] = false;
	  redirect(".");
	}
	return $today;
}

function isWeekend($date) {
	$weekDay = date('w', strtotime($date));
	return ($weekDay == 0 || $weekDay == 6);
}

$today = date("Y-m-d");
$currentTime = date("H:i");

$desiredDate = getCustomDate("date", $today, $min, $max);
$desiredDateMidWeek = $desiredDate;

$weekOverview = setWeekPreference($token, $desiredDate, $today);
if($weekOverview === true) {
	if(formatWeekDay($desiredDate) !== "Mon") {
		$lastMonday = getDateFromInterval($desiredDate, "last monday");
		$desiredDateMidWeek = $desiredDate;
		$desiredDate = $lastMonday;
	}
}

$weekBump = false;

if(isset($excludeWeekends) && isTrue($excludeWeekends)) {
	if(isWeekend($desiredDateMidWeek)) {
		$desiredDate = getDateFromInterval($desiredDateMidWeek, "1 weekday");
		$weekBump = true;
	}
	
	if(isWeekend($today)) {
		$today = getDateFromInterval($today, "1 weekday");
		if($today == $desiredDate) {
			$weekBump = true;
		}
	}
	
	$weekDayString = "weekday";
} else {
	$weekDayString = "day";
}

$desiredDatePretty = formatReadableDate($desiredDate);
$weekDay = formatWeekDay($desiredDate);
$displayedDateFull = $weekDay . ", " . $desiredDatePretty;
$displayedDate = $desiredDatePretty;

$nextWeek = getDateFromInterval($desiredDate, "1 week");
$nextDay = getDateFromInterval($desiredDate, "1 $weekDayString");

$prevDay = getDateFromInterval($desiredDate, "1 $weekDayString ago");
$prevWeek = getDateFromInterval($desiredDate, "1 week ago");

if($prevWeek < getDateFromInterval($minDate)) {
	$prevWeek = "none";
}

if($prevDay < getDateFromInterval($minDate)) {
	$prevDay = "none";
}

if($nextWeek > getDateFromInterval($maxDate)) {
	$nextWeek = "none";
}

if($nextDay > getDateFromInterval($maxDate)) {
	$nextDay = "none";
}

if($weekOverview === true) {
	$nextDay = "none";
	$prevDay = "none";
}

/* API connection */
function validClass($class, $allowedClasses) {
	return !empty($class) && in_array($class, $allowedClasses);
}

function getClass($defaultClass, $allowedClasses, $desiredDate, $today, $token) {
	$classGET = getParameter("class");
	$classCookie = getCookie("class");

	$class = getInput($classGET, $classCookie);

	if (validClass($class, $allowedClasses)) {
		if (!empty($classGET)) {
			getToCookie("class", $class, $token);
			redirectToDate($desiredDate, $today);
		}
		return $class;
	}
	return $defaultClass;
}

$displayExtraEvents = setExtraEventsPreference($token, $desiredDate, $today);

function setWeekPreference($token, $desiredDate, $today) {
	$overviewGET = getParameter("overview");
	$overviewCookie = getCookie("overview");

	$overview = getInput($overviewGET, $overviewCookie);
	
	if($overview === "week" || $overview === "day") {
		if (!empty($overviewGET)) {
			getToCookie("overview", $overview, $token);
			redirectToDate($desiredDate, $today);
		}
		return $overview === "week";
	}
	
	return true; // fall back to week overview
}

function setExtraEventsPreference($token, $desiredDate, $today) {
	$extraEventsGET = getParameter("extraEvents");
	$extraEventsCookie = getCookie("extraEvents");

	$extraEvents = getInput($extraEventsGET, $extraEventsCookie);
	
	if($extraEvents === "true" || $extraEvents === "false") {
		if (!empty($extraEventsGET)) {
			getToCookie("extraEvents", $extraEvents, $token);
			redirectToDate($desiredDate, $today);
		}
		return $extraEvents === "true";
	}
	
	return false;
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
		//retrieve cache
		$calendar = file_get_contents($cache_filename);
	} else {
		//refresh or create cache
		try {
			$calendar = file_get_contents($api);
			if (empty($calendar) || $calendar === false) {
				die("Error connecting to API.");
			}
		} catch (Exception $ex) {
			die("Unable to reach API.");
		}
		if($type == 'ical') {
			file_put_contents($cache_filename, $calendar, LOCK_EX); // write tmp file for CalFileParser to process
			$calendar = $cal->parse($cache_filename, 'json');
		}
		file_put_contents($cache_filename, $calendar, LOCK_EX);
	}

	$calendarArray = json_decode($calendar, true);
	return defined('CALENDAR') ? $calendarArray[CALENDAR] : $calendarArray;
}

if(!isset($allowedClasses)) {
	$allowedClasses = [];
}

if(!isset($api)) {
	die('Undefined API');
}
if(isset($type) && $type !== 'ical') {
	if((!isset($defaultClass))) {
		die('A default class is required for the JSON api.');
	}
	$desiredClass = getClass($defaultClass, $allowedClasses, $desiredDate, $today, $token);
	$desiredAPI = getAPIUrl($api, $desiredClass, $defaultClass);
	$cache_filename = $desiredClass . ".json";
} else {
	if(isset($defaultClass)) {
		$desiredClass = getClass($defaultClass, $allowedClasses, $desiredDate, $today, $token);
	}
	$desiredAPI = $api;
	$cache_filename = "api.json";
}


function getExtraSubjects($extraEvents) {
	$extraSubjects = [];

	foreach ($extraEvents as $classes) {
		foreach ($classes as $days) {
			foreach ($days as $subjects) {
					if (!in_array($subjects, $extraSubjects)) {
						$extraSubjects[] = $subjects;
					}
			}
		}
	}
	return $extraSubjects;
}

if(!empty($extraEvents)) {
	$extraSubjects = getExtraSubjects($extraEvents);
}


$cache_folder = "cache/";
createCache($cache_folder);

$cache_file = $cache_folder . $cache_filename;
$calendar = retrieveData($desiredAPI, $cache_file, $type);
