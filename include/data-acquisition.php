<?php

/* General functions */
function validOption($option, $allowedValues) {
	if(contains($option, ",")) {
		$option = getArray($option);
		return containsAllValues($option, $allowedValues);
	}
	return !empty($option) && in_array($option, $allowedValues);
}

function getOption($name, $allowedValues, $fallback, $token, $desiredDate, $today) {
	$get = getParameter($name);
	$cookie = getCookie($name);

	$value = getInput($get, $cookie);

	if (validOption($value, $allowedValues)) {
		if (!empty($get)) {
			getToCookie($name, $value, $token);
			redirectToDate($desiredDate, $today);
		}
		return $value;
	}
	return $fallback;
}

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
	return ($weekDay == "0" || $weekDay == "6");
}

$today = date("Y-m-d");
$currentTime = date("H:i");

$desiredDate = getCustomDate("date", $today, $min, $max);
$desiredDateMidWeek = $desiredDate;

$overviewType = getOption("overview", ["week", "day"], "week", $token, $desiredDate, $today);
$weekOverview = $overviewType === "week";

if($weekOverview === true) {
	if(formatWeekDay($desiredDate) !== "Mon") {
		$lastMonday = getDateFromInterval($desiredDate, "last monday");
		$desiredDateMidWeek = $desiredDate;
		$desiredDate = $lastMonday;
	}
}

$weekBump = false;

if(isset($excludeWeekends) && $excludeWeekends == true) {
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
$displayedDateFull = formatFullReadableDate($desiredDate);
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
//$extraEventsOption = getOption("extraEvents", ["true", "false"], "false", $token, $desiredDate, $today);
//$displayExtraEvents = $extraEventsOption === "true"; //now handled by $chosenExtraSubjects

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

function retrieveData($api, $cache_filename, $type, $cache_time) {
	include('classes/CalFileParser.php');
	$cal = new CalFileParser();
	
	$cacheAge = filemtime($cache_filename);
	$refreshAt = strtotime('now -' . $cache_time);
	if (is_writable($cache_filename) && $cacheAge > $refreshAt) {
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
	$desiredClass = getOption("class", $allowedClasses, $defaultClass, $token, $desiredDate, $today);
	$desiredAPI = getAPIUrl($api, $desiredClass, $defaultClass);
	$cache_filename = $desiredClass . ".json";
} else {
	if(isset($defaultClass)) {
		$desiredClass = getOption("class", $allowedClasses, $defaultClass, $token, $desiredDate, $today);
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
	
	$allowedSubjectsInput = getArrayWith(lowercaseArray($extraSubjects), "none");
	$chosenExtraSubjectsString = getOption("extraSubjects", $allowedSubjectsInput, "", $token, $desiredDate, $today); //not-entirely-processed user input
	$chosenExtraSubjects = getArray($chosenExtraSubjectsString, true, true); //cleans up duplicates and turns "none" into empty array
}
$displayExtraEvents = !empty($chosenExtraSubjects);


$cache_folder = "cache/";
createCache($cache_folder);

$cache_file = $cache_folder . $cache_filename;
$cache_time = "1 day";
$calendar = retrieveData($desiredAPI, $cache_file, $type, $cache_time);
