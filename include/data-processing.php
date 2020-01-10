<?php

/* Schedule preparation */

//General Functions

if (!isset($infos)) {
	$infos = [];
}

if (!isset($ignoredSubjects)) {
	$ignoredSubjects = [];
}

//Room Functions
if (!isset($roomPrefix)) {
	$roomPrefix = "";
}

function trimRoom($raw, $roomPrefix) {
	return !empty($roomPrefix) ? str_replace($roomPrefix, "", $raw) : $raw;
}

//Prof Functions
function trimPlaceholders($raw, $placeholders) {
	if(!is_array($placeholders)) return "";
	foreach ($placeholders as $placeholder) {
		$processed = str_replace($placeholder, "", $raw);
	}
	return $processed;
}

if (!isset($displayProfs)) {
	$displayProfs = false;
}

if (!isset($profs) && $displayProfs === true) {
	$profs = [];
}

if (!isset($emptyProfs)) {
	$emptyProfs = [];
}

function lookupProfs($prof, $emptyProfs, $profs) {
	if (!empty($emptyProfs)) {
		$prof = trimPlaceholders($prof, $emptyProfs);
	}
	if (!empty($profs)) {
		$prof = lookup($prof, $profs);
	}
	return $prof;
}

//Time functions
function extractJsonTime($dateTime) {
	$info = explode(" ", $dateTime);
	return substr($info[1], 0, -3);
}

function extractIcalDate($string, $format = 'Y-m-d') {
	return DateTime::createFromFormat('Y-m-d H:i:s.000000', $string)->format($format);
}

function extractApiTime($type, $singleEntry) {
	if ($type == 'ical') {
		return extractIcalDate($singleEntry["date"], 'H:i');
	}
	return extractJsonTime($singleEntry);
}

function extractApiDate($type, $singleEntry) {
	if ($type == 'ical') {
		return extractIcalDate($singleEntry["date"]);
	}
	return extractJsonDate($singleEntry);
}

function extractApiDateTime($type, $singleEntry) {
	if ($type == 'ical') {
		return extractIcalDate($singleEntry["date"], "M j, Y H:i:s");
	}
	return extractJsonDate($singleEntry);
}

function extractJsonDate($dateTime) {
	$info = explode(" ", $dateTime);
	return $info[0];
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

//Get data from event
function getSubject($type, $wholeString) {
	if ($type == 'ical') {
		return stringRange($wholeString, SUBJECTSECTION[0], SUBJECTSECTION[1]);
	}
	return $wholeString;
}

//Duplicate check
function sameEvent($e, $new) {
	return $e["date"] == $new["date"] &&
					$e["start"] == $new["start"] &&
					$e["end"] == $new["end"] &&
					$e["subject"] == $new["subject"];
}

function validProf($profs, $emptyProfs) {
	return !in_array($profs, $emptyProfs);
}

function validSubject($subject, $ignoredSubjects) {
	return !in_array($subject, $ignoredSubjects);
}

function containsNewRoom($existing, $new) {
	return !empty($existing["room"]) && notContains($existing["room"], $new["room"]);
}

function containsNewValidProf($existing, $new, $emptyProfs) {
	return !empty($existing["prof"]) &&
					notContains($existing["prof"], $new["prof"]) &&
					validProf($new["prof"], $emptyProfs);
}

function containsNewInfo($existing, $new) {
	return !empty($new["info"]) && notContains($existing["info"], $new["info"]);
}

function isExtraSubject($subject, $weekDay, $extraEvents, $extraClass, $chosenExtraSubjects) {
	return isset($extraEvents[$extraClass][$weekDay]) && in_array($subject, $extraEvents[$extraClass][$weekDay]) && in_array($subject, $chosenExtraSubjects);
}

//Populating schedule array
$schedule = [];

$desiredDateTo = $desiredDate;
if ($weekOverview === true) {
	$desiredDateTo = getDateFromInterval($desiredDate, "+6 days");
}

foreach ($calendar as $entry) {
	$date = extractApiDate($type, $entry[START]);

	$isCorrectClass = true;
	$isExtraClass = false;
	if (isset($desiredClass)) {
		$class = stringRange($entry[LESSONCLASS], CLASSSECTION[0], CLASSSECTION[1]);

		if (notContains($class, $desiredClass)) {
			$isCorrectClass = false;
			if ($displayExtraEvents == true) {
				foreach ($extraEvents as $extraClass => $value) {
					if (contains($class, $extraClass)) {
						$isCorrectClass = true;
						$isExtraClass = true;
					}
					break;
				}
			}
		}
	}

	if (isBetween($date, $desiredDate, $desiredDateTo) && $isCorrectClass) {
		$new = [];

		$new["date"] = formatReadableDate($date);
		$new["weekDay"] = formatWeekDay($date);

		$new["start"] = extractApiTime($type, $entry[START]);
		$new["end"] = extractApiTime($type, $entry[END]);
		$new["startDateTime"] = extractApiDateTime($type, $entry[START]);
		$new["endDateTime"] = extractApiDateTime($type, $entry[END]);

		$thisSubject = getSubject($type, $entry[SUBJECT]);
		$new["subject"] = !empty($subjects) ? lookup($thisSubject, $subjects) : $thisSubject;

		if (!isset($excludedRoomSubjects) || isset($excludedRoomSubjects) && !in_array($new["subject"], $excludedRoomSubjects)) {
			$shortRoom = !empty($roomPrefix) ? trimRoom($entry[ROOM], $roomPrefix) : $entry[ROOM];
			$descriptiveRoom = lookup($shortRoom, $rooms);
			$new["room"] = !empty($roomDelimiter) ? str_replace($roomDelimiter, ", ", $descriptiveRoom) : $descriptiveRoom;
		}

		if (defined('INFO')) {
			$rawInfo = stringRange($entry[INFO], INFOSECTION[0], INFOSECTION[1]);
			$new["info"] = lookup($rawInfo, $infos);
		}

		if ($displayProfs === true) {
			if ($type == 'ical') {
				$thisProf = stringRange($entry[PROF], PROFSECTION[0], PROFSECTION[1]);
			} else {
				$thisProf = $entry[PROF];
			}

			$new["prof"] = lookupProfs($thisProf, $emptyProfs, $profs);
		}

		if ($isExtraClass) {
			$new["type"] = "extraEvent";
		} else {
			$new["type"] = "event";
		}


		$add = true;

		if ($isExtraClass) {
			if (!isExtraSubject($thisSubject, $new["weekDay"], $extraEvents, $extraClass, $chosenExtraSubjects)) {
				$add = false;
			}
		}

		if (!validSubject($new["subject"], $ignoredSubjects)) {
			$add = false;
		}

		foreach ($schedule as $key => $existing) {
			if (sameEvent($existing, $new)) {
				$add = false;
				if (containsNewRoom($existing, $new)) {
					$schedule[$key]["room"] .= ", " . $new["room"];
				}
				if (containsNewValidProf($existing, $new, $emptyProfs)) {
					$schedule[$key]["prof"] .= " / " . $new["prof"];
				}
				if (containsNewInfo($existing, $new)) {
					if (!empty($existing['info'])) {
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

	if (empty($schedule) && empty($nextEventDate) && $date > $desiredDateTo && $isCorrectClass) {
		if (!$isExtraClass || ($isExtraClass && isExtraSubject(getSubject($type, $entry[SUBJECT]), formatWeekDay($date), $extraEvents, $extraClass, $chosenExtraSubjects))) {
			$nextEventDate = $date;
		}
	}
}

if (!empty($schedule)) {
	//remove overlapping events
	if ($displayExtraEvents == true) {
		foreach ($schedule as $key => $extraEvent) {
			if ($extraEvent['type'] == "extraEvent") {
				foreach ($schedule as $event) {
					if ($event['type'] == "event" &&
									$extraEvent["date"] == $event["date"] &&
									isBetween($extraEvent["start"], $event["start"], $event["end"]) &&
									isBetween($extraEvent["end"], $event["start"], $event["end"])) {
						unset($schedule[$key]);
					}
				}
			}
		}
	}

	//sort by date
	usort($schedule, 'compareDate');

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
