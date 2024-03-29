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
	$roomPrefix = [""];
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
	foreach ($ignoredSubjects as $ignored) {
		if(startsWith($subject, $ignored)) {
			return false;
		}
	}
	return true;
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

function formatSubject($thisSubject, $subjects, $subjectRemoveAt) {
	$subject = !empty($subjects) ? lookup($thisSubject, $subjects) : $thisSubject;
	if(isset($subjectRemoveAt) && contains($subject, $subjectRemoveAt)) {
		$subject = substr_replace($subject, "", strpos($subject, $subjectRemoveAt)); //remove starting at
		$subject = removeFromString(", ", $subject);
	}
	return $subject;
}

//Populating schedule array
$schedule = [];

$desiredDateTo = $desiredDate;
if ($weekOverview === true) {
	$desiredDateTo = getDateFromInterval($desiredDate, "+5 days");
	$displayedDateFull .= " - " . formatFullReadableDate($desiredDateTo);
}

foreach ($calendar as $entry) {
	$date = extractApiDate($type, $entry[START]);
	$subject = formatSubject($entry[SUBJECT], $subjects, $subjectRemoveAt);

	$isCorrectClass = true;
	$isExtraClass = false;
	if (isset($desiredClass)) {
		/* See if key in array contains value */
		$attendeesArray = array_filter($entry, function($key) {
			return strpos($key, 'ATTENDEE;CN=') === 0;
		}, ARRAY_FILTER_USE_KEY);
		$attendeesRaw = array_keys($attendeesArray);
		$attendees = str_replace("ATTENDEE;CN=", "", $attendeesRaw);

		if (!in_array($desiredClass, $attendees)) {
			$isCorrectClass = false;
			if ($displayExtraEvents == true) {
				foreach ($extraEvents as $extraClass => $value) {
					if (in_array($extraClass, $attendees)) {
						$isCorrectClass = true;
						$isExtraClass = true;
						break;
					}
				}
			}
		}
	}

	if (isset($fullCalendar) || isBetween($date, $desiredDate, $desiredDateTo) && $isCorrectClass) {
		$new = [];

		$new["date"] = formatReadableDate($date);
		$new["weekDay"] = formatWeekDay($date);

		$new["start"] = extractApiTime($type, $entry[START]);
		$new["end"] = extractApiTime($type, $entry[END]);
		$new["startDateTime"] = extractApiDateTime($type, $entry[START]);
		$new["endDateTime"] = extractApiDateTime($type, $entry[END]);

		$thisSubject = $entry[SUBJECT];
		$new["subject"] = $subject;


		if (!isset($excludedRoomSubjects) || isset($excludedRoomSubjects) && !in_array($new["subject"], $excludedRoomSubjects)) {
			$shortRoom = !empty($roomPrefix) ? trimRoom($entry[ROOM], $roomPrefix) : $entry[ROOM];
			$descriptiveRoom = !empty($rooms) ? lookup($shortRoom, $rooms) : $shortRoom;
			$tidyRoom = !empty($roomDelimiter) ? str_replace($roomDelimiter, ", ", $descriptiveRoom) : $descriptiveRoom;
			$new["room"] = removeFromString($emptyRoom, $tidyRoom);
		}

		if (defined('INFO')) {
			$rawInfo = isset ($entry[INFO]) && stringRange($entry[INFO], INFOSECTION[0], INFOSECTION[1]);
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

	if (empty($schedule) && $isCorrectClass && validSubject($subject, $ignoredSubjects) &&
					$date > $desiredDateTo && //"next event" = later than desired date
					(empty($nextEventDate) ||
					isset($nextEventDate) && $date < $nextEventDate)) {
		if (!$isExtraClass || ($isExtraClass && isExtraSubject($entry[SUBJECT], formatWeekDay($date), $extraEvents, $extraClass, $chosenExtraSubjects))) {
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
	
	$addedDates = [];
	foreach ($schedule as $key => $event) {
		/* Sort rooms */
		if (!empty($schedule[$key]["room"])) {
			$schedule[$key]["room"] = sortRooms($schedule[$key]["room"]);
		}

		/* Merge events */
		if (isset($schedule[$key - 1]) && splitupEvent($schedule[$key - 1], $schedule[$key])) {
			$prevEvent = $schedule[$key - 1];

			$mergedStart = min($prevEvent["startDateTime"], $event["startDateTime"]);
			$mergedEnd = max($prevEvent["endDateTime"], $event["endDateTime"]);

			$schedule[$key - 1]["start"] = dateTimeToHourMin($mergedStart);
			$schedule[$key - 1]["end"] = dateTimeToHourMin($mergedEnd);

			$schedule[$key - 1]["startDateTime"] = $mergedStart;
			$schedule[$key - 1]["endDateTime"] = $mergedEnd;
			unset($schedule[$key]);
		}
		
		foreach ($schedule as $key => $event) {
			if(!in_array($event["date"], $addedDates)) {
				$addedDates[] = $event["date"];
			}
		}
	}
	
	if(!isset($fullCalendar) && $weekOverview == true) {
		$period = new DatePeriod(
						new DateTime($desiredDate),
						DateInterval::createFromDateString('1 day'),
						new DateTime($desiredDateTo)
		);
		
		foreach ($period as $dt) {
			if(!in_array($dt->format("d.m.y"), $addedDates)) {
				$mockEvent["date"] = $dt->format("d.m.y");
				$mockEvent["weekDay"] = $dt->format("D");

				$mockEvent["startDateTime"] = $dt->format("M j, Y H:i:s");
				$mockEvent["endDateTime"] = $dt->format("M j, Y H:i:s");
				$mockEvent["start"] = "00:00";
				$mockEvent["end"] = "00:00";

				$mockEvent["type"] = "empty";

				$schedule[] = $mockEvent;
			}
		}
	}
	

	//sort by date
	usort($schedule, 'compareStartDateTime');
	usort($schedule, 'compareEndDateTime');
	
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
