<?php
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

function containsNewRoom($existing, $new) {
	return !empty($existing["room"]) && notExists($existing["room"], $new["room"]);
}

function containsNewProf($existing, $new, $emptyProfs) {
	return !empty($existing["prof"]) && notExists($existing["prof"], $new["prof"]) && validProf($new["prof"], $emptyProfs);
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
				if (containsNewProf($existing, $new, $emptyProfs)) {
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

function timeIsBetween($time, $start, $end) {
  return isBetween(createTime($time), createTime($start), createTime($end));
}

function onGoingEvent($event, $currentTime) {
  return timeIsBetween($currentTime, $event['start'], $event['end']);
}