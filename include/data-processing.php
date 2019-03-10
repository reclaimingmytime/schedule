<?php

/* Date preparation */
if(empty($minDate)) {
	$minDate = date("d.m.Y", 0);
}

$min = new DateTime($minDate);

function validDate($input) {
	//DateTime even detects 31st Feb and 31st Nov as errors
	$date = DateTime::createFromFormat('Y-m-d', $input);
	$date_errors = DateTime::getLastErrors();

	global $min;
	return $date >= $min && $date_errors['warning_count'] === 0 && $date_errors['error_count'] === 0;
}

function getCustomDate($param, $today) {
	if (isset($_GET[$param]) && validDate($_GET[$param])) {
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

function hasExcludedWeekends() {
	global $excludeWeekends;
	return isset($excludeWeekends) && $excludeWeekends === true;
}

$today = date("Y-m-d");
$currentTime = date("H:i");

$desiredDate = getCustomDate("date", $today);

$weekBump = false;
if(hasExcludedWeekends() && isWeekend($desiredDate)) {
	$desiredDate = createNewDate($desiredDate, "1 weekday");
	$weekBump = true;
}
if(hasExcludedWeekends() && isWeekend($today)) {
	$today = createNewDate($today, "1 weekday");
	if($today == $desiredDate) {
		$weekBump = true;
	}
}

$desiredDateObj = new DateTime($desiredDate);

$desiredDatePretty = $desiredDateObj->format("d.m.y");
$weekDay = $desiredDateObj->format("D");
$displayedDateFull = $weekDay . ", " . $desiredDatePretty;
$displayedDate = $desiredDatePretty;

if(hasExcludedWeekends()) {
	$weekDayString = "weekday";
} else {
	$weekDayString = "day";
}

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

/* Schedule preparation */

//General Functions
function lookup($room, $rooms) {
	if(array_key_exists($room, $rooms)) {
		return $rooms[$room];
	}
	return $room;
}

//Room Functions
if(!isset($roomPrefix)) {
	$roomPrefix = "";
}

function trimRoom($raw, $roomPrefix) {
	return !empty($roomPrefix) ? str_replace($roomPrefix, "", $raw) : $raw;	
}

//Prof Functions
function trimPlaceholders($raw, $placeholders) {
	if (in_array($raw, $placeholders)) {
		return "";
	}
	return $raw;
}

if(!isset($profs)) {
	$profs = [];
}

if(!isset($emptyProfs)) {
	$emptyProfs = [];
}

function lookupProfs($prof, $emptyProfs, $profs) {
	$realProf = trimPlaceholders($prof, $emptyProfs);
	return lookup($realProf, $profs);
}

//Time functions
function extractTime($dateTime) {
	$info = explode(" ", $dateTime);
	return substr($info[1], 0, -3);
}

function extractDate($dateTime) {
	$info = explode(" ", $dateTime);
	return $info[0];
}

function isWeekend($date) {
	$weekDay = date('w', strtotime($date));
	return ($weekDay == 0 || $weekDay == 6);
}

//Ensure defined constants
function ensureDefined($constant) {
	if (!defined($constant)) {
		die("Undefined constant $constant. Please define in config file.");
	}
}

function ensureAllDefined($constants) {
	foreach ($constants as $constant) {
		ensureDefined($constant);
	}
}

ensureAllDefined(['SUBJECT', 'START', 'END', 'ROOM', 'PROF']);

//Duplicate check
function sameEvent($e, $new) {
	return equals($e["start"], $new["start"]) && equals($e["end"], $new["end"]) && equals($e["subject"], $new["subject"]);
}

function validProf($profs, $emptyProfs) {
	return !in_array($profs, $emptyProfs);
}

function containsNewRoom($e, $new) {
	return !empty($e["room"]) && notExists($e["room"], $new["room"]);
}

function containsNewProf($e, $new) {
	global $emptyProfs;
	return !empty($e["prof"]) && notExists($e["prof"], $new["prof"]) && validProf($new["prof"], $emptyProfs);
}

//Populating schedule array
$schedule = [];

foreach ($calendar as $entry) {
	$date = extractDate($entry[START]);

	if ($date == $desiredDate) {
		$new = [];
		$new["start"] = extractTime($entry[START]);
		$new["end"] = extractTime($entry[END]);
		$new["subject"] = !empty($subjects) ? lookup($entry[SUBJECT], $subjects) : $entry[SUBJECT];
		
		$shortRoom = !empty($roomPrefix) ? trimRoom($entry[ROOM], $roomPrefix) : $entry[ROOM];
		$new["room"] = lookup($shortRoom , $rooms);
		
		$new["prof"] = !empty($emptyProfs) && !empty($profs) ? lookupProfs($entry[PROF], $emptyProfs, $profs) : $entry[PROF];
		
		$add = true;

		foreach ($schedule as $key => $existing) {
			if (sameEvent($existing, $new)) {
				$add = false;
				if (containsNewRoom($existing, $new)) {
					$schedule[$key]["room"] .= ", " . $new["room"];
				}
				if (containsNewProf($existing, $new)) {
					$schedule[$key]["prof"] .= ", " . $new["prof"];
				}
			}
		}

		if ($add === true) {
			$schedule[] = $new;
		}
	}
}

if (!empty($schedule)) {
	//Sanitize input
	escapeArray($schedule);
}

function onGoingEvent($event) {
	global $desiredDate;
	global $today;
	global $currentTime;
	global $weekBump;
	
	return $desiredDate == $today && isBetween(createTime($currentTime), createTime($event['start']), createTime($event['end'])) && $weekBump === false;
}