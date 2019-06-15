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

function setWeekPreference($token, $desiredDate, $today) {
	$overviewGET = getParameter("overview");
	$overviewCookie = getCookie("overview");

	$overview = getInput($overviewGET, $overviewCookie);
	
	if($overview === "week" || $overview === "day") {
		if (!empty($overviewGET) && isDifferent($overviewGET, $overviewCookie)) {
			if (validToken(getParameter("token"), $token)) {
				writeCookie("overview", $overview, "1 year");
			}
			if($desiredDate == $today) {
				redirect(".");
			} else {
				redirect("?date=" . $desiredDate);
			}
		}
		return $overview === "week";
	}
	return true; // fall back to week overview
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
		$calendar_json = file_get_contents($cache_filename);
	} else {
		try {
			$calendar_json = file_get_contents($api);
			if (empty($calendar_json) || $calendar_json === false) {
				die("Error connecting to API.");
			}
		} catch (Exception $ex) {
			die("Unable to reach API.");
		}
		//refresh cache
		if($type == 'ical') {
			$calendar_json = $cal->parse($calendar_json, 'json');
		}
		file_put_contents($cache_filename, $calendar_json, LOCK_EX);
	}

	$calendar = json_decode($calendar_json, true);
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



$cache_folder = "cache/";
createCache($cache_folder);

$cache_file = $cache_folder . $cache_filename;
$calendar = retrieveData($desiredAPI, $cache_file, $type);