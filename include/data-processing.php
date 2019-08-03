<?php
/* Schedule preparation */

//General Functions
function lookup($key, $array) {
	if(array_key_exists($key, $array)) {
		return $array[$key];
	}
	return $key;
}

if(!isset($infos)) {
	$infos = [];
}

if(!isset($ignoredSubjects)) {
	$ignoredSubjects = [];
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

if(!isset($displayProfs)) {
	$displayProfs = false;
}

if(!isset($profs) && $displayProfs === true) {
	$profs = [];
}

if(!isset($emptyProfs)) {
	$emptyProfs = [];
}

function lookupProfs($prof, $emptyProfs, $profs) {
	if(!empty($emptyProfs)) {
		$prof = trimPlaceholders($prof, $emptyProfs);
	}
	if(!empty($profs)) {
		$prof = lookup($prof, $profs);
	}
	return $prof;
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

function extractIcalDate($string) {
	return DateTime::createFromFormat('Y-m-d H:i:s.000000', $string);
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

ensureAllDefined(['SUBJECT', 'START', 'END', 'ROOM']);

//Duplicate check
function sameEvent($e, $new) {
	return equals($e["date"], $new["date"]) &&
					equals($e["start"], $new["start"]) &&
					equals($e["end"], $new["end"]) &&
					equals($e["subject"], $new["subject"]);
}

function validProf($profs, $emptyProfs) {
	return !in_array($profs, $emptyProfs);
}

function validSubject($subject, $ignoredSubjects) {
	return !in_array($subject, $ignoredSubjects);
}

function containsNewRoom($existing, $new) {
	return !empty($existing["room"]) && notExists($existing["room"], $new["room"]);
}

function containsNewProf($existing, $new, $emptyProfs) {
	return !empty($existing["prof"]) &&
					notExists($existing["prof"], $new["prof"]) &&
					validProf($new["prof"], $emptyProfs);
}

function containsNewInfo($existing, $new) {
	return !empty($new["info"]) && notExists($existing["info"], $new["info"]);
}

//Populating schedule array
$schedule = [];

foreach ($calendar as $entry) {
	$date = ($type == 'ical') ? extractIcalDate($entry[START]["date"])->format('Y-m-d') : extractDate($entry[START]);

	if($weekOverview === true) {
		$desiredDateTo = getDateFromInterval($desiredDate, "+6 days");
	} else {
		$desiredDateTo = $desiredDate;
	}
			
	$isCorrectClass = true;
	if(isset($desiredClass)) {
		$class = stringRange($entry[LESSONCLASS], CLASSSECTION[0], CLASSSECTION[1]);
		if(notExists($class, $desiredClass)) {
			$isCorrectClass = false;
		}
	}
	
	if (isBetween($date, $desiredDate, $desiredDateTo) && $isCorrectClass) {
		$new = [];
		
		$new["date"] = formatReadableDate($date);
		$new["weekDay"] = formatWeekDay($date);
			
		$new["start"] = ($type == 'ical') ? extractIcalDate($entry[START]["date"])->format('H:i') : extractTime($entry[START]);
		$new["end"] = ($type == 'ical') ? extractIcalDate($entry[END]["date"])->format('H:i') : extractTime($entry[END]);
		
		$shortRoom = !empty($roomPrefix) ? trimRoom($entry[ROOM], $roomPrefix) : $entry[ROOM];
		$new["room"] = lookup($shortRoom , $rooms);
		
		if($type == 'ical') {
			$thisSubject = stringRange($entry[SUBJECT], SUBJECTSECTION[0], SUBJECTSECTION[1]);
		} else {
			$thisSubject = $entry[SUBJECT];
		}
		$new["subject"] = !empty($subjects) ? lookup($thisSubject, $subjects) : $thisSubject;
		
		if(defined('INFO')) {
			$rawInfo = stringRange($entry[INFO], INFOSECTION[0], INFOSECTION[1]);
			$new["info"] = lookup($rawInfo, $infos);
		}
		
		if($displayProfs === true) {
			if($type == 'ical') {
				$thisProf = stringRange($entry[PROF], PROFSECTION[0], PROFSECTION[1]);
			} else {
				$thisProf = $entry[PROF];
			}
			
			$new["prof"] = lookupProfs($thisProf, $emptyProfs, $profs);
		}

		$add = true;
		
		if(!validSubject($new["subject"], $ignoredSubjects)) {
			$add = false;
		}

		foreach ($schedule as $key => $existing) {
			if (sameEvent($existing, $new)) {
				$add = false;
				if (containsNewRoom($existing, $new)) {
					$schedule[$key]["room"] .= ", " . $new["room"];
				}
				if (containsNewProf($existing, $new, $emptyProfs)) {
					$schedule[$key]["prof"] .= ", " . $new["prof"];
				}
				if (containsNewInfo($existing, $new)) {
					if(isset($existing['info'])) {
						$schedule[$key]["info"] .= ", ";
					}
					$schedule[$key]["info"] .= $new["info"];
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

function timeIsBetween($time, $start, $end) {
	return isBetween(createTime($time), createTime($start), createTime($end));
}

function isToday($rawDate, $today) {
	return $today === readableToIsoDate($rawDate);
}

function onGoingEvent($event, $currentTime, $today) {
	return timeIsBetween($currentTime, $event['start'], $event['end']) && isToday($event['date'], $today);
}
