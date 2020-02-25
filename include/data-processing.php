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

function sortRooms($rooms) {
	$array = explode(', ', $rooms);
	natsort($array);
	return implode(', ', $array);
}

//Prof Functions
function trimPlaceholders($raw, $placeholders) {
	$processed = $raw;
	foreach ($placeholders as $placeholder) {
		$processed = removeFromString($placeholder, $processed);
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
	if (!empty($profs) && !empty($prof)) {
    foreach ($profs as $profInitial => $profName) {
      $prof = str_replace($profInitial, $profName, $prof);
    }
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
					$e["subject"] == $new["subject"] &&
					$e["start"] == $new["start"] &&
					$e["end"] == $new["end"];
}
function isSameProf($displayProfs, $existing, $new) {
	return $displayProfs == true ? $existing["prof"] == $new["prof"] : true;
}
function splitupEvent($prev, $current) {
	global $displayProfs;
	return $prev["date"] == $current["date"] &&
					$prev["subject"] == $current["subject"] &&
					$prev["info"] == $current["info"] &&
					$prev["room"] == $current["room"] &&
					isSameProf($displayProfs, $prev, $current) && 
					($prev["start"] != $current["start"] || $prev["end"] != $current["end"]);
}

function validProf($profs, $emptyProfs) {
	return !in_array($profs, $emptyProfs);
}

function validSubject($subject, $ignoredSubjects) {
	return !in_array($subject, $ignoredSubjects);
}

function containsNewRoom($existing, $new) {
	return !empty($new["room"]) && notContains($existing["room"], $new["room"]);
}

function containsNewValidProf($existing, $new, $emptyProfs) {
	return !empty($new["prof"]) &&
					notContains($existing["prof"], $new["prof"]) &&
					validProf($new["prof"], $emptyProfs);
}

function containsNewInfo($existing, $new) {
	return !empty($new["info"]) && notContains($existing["info"], $new["info"]);
}

function isExtraSubject($subject, $weekDay, $extraEvents, $extraClass, $chosenExtraSubjects) {
	return isset($extraEvents[$extraClass][$weekDay]) && inArray(strtoupper($subject), $extraEvents[$extraClass][$weekDay]) && inArray(strtoupper($subject), $chosenExtraSubjects);
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
			if (!isExtraSubject($thisSubject, $new["weekDay"], $extraEvents, $extraClass, $chosenExtraSubjects) &&
							!isExtraSubject($new["info"], $new["weekDay"], $extraEvents, $extraClass, $chosenExtraSubjects)) {
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
					if(!empty($existing["room"])) {
						$schedule[$key]["room"] .= ", ";
					}
					$schedule[$key]["room"] .= $new["room"];
				}
				if (containsNewValidProf($existing, $new, $emptyProfs)) {
					if(!empty($existing["prof"])) {
						$schedule[$key]["prof"] .= " / ";
					}
					$schedule[$key]["prof"] .= $new["prof"];
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

	if (empty($schedule) && $isCorrectClass &&
					$date > $desiredDateTo && //"next event" = later than desired date
					(empty($nextEventDate) ||
					isset($nextEventDate) && $date < $nextEventDate)) {
		if (!$isExtraClass || ($isExtraClass && isExtraSubject(getSubject($type, $entry[SUBJECT]), formatWeekDay($date), $extraEvents, $extraClass, $chosenExtraSubjects))) {
			$nextEventDate = $date;
		}
	}
}

if (!empty($schedule)) {
	/* //remove overlapping events // might be intended
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
	} */
	
	foreach ($schedule as $key => $event) {
		/* Sort rooms */
		if(!empty($schedule[$key]["room"])) {
			$schedule[$key]["room"] = sortRooms($schedule[$key]["room"]);
		}
		
		/* Merge events */
		if (isset($schedule[$key - 1]) && splitupEvent($schedule[$key - 1], $schedule[$key])) {
			$prevEvent = $schedule[$key - 1];
			
			$schedule[$key - 1]["start"] = min($prevEvent["start"], $event["start"]);
			$schedule[$key - 1]["end"] = max($prevEvent["end"], $event["end"]);
			unset($schedule[$key]);
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
