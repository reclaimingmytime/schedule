<?php

if (!empty($timezone)) {
	date_default_timezone_set($timezone);
}

/* Conditions */

function isBetween($x, $min, $max) {
	return ($min <= $x) && ($x <= $max);
}

function isBelowOrAbove($x, $min, $max) {
	return ($min < $x) && ($x < $max);
}

function contains($haystack, $needle) {
	return strpos($haystack, $needle) !== false;
}

function notContains($haystack, $needle) {
	return strpos($haystack, $needle) === false;
}

function isDifferent($x, $y) {
	return !empty($x) && $x !== $y;
}

function containsAllValues($needle, $haystack) {
	return !array_diff($needle, $haystack);
}

function arrayContains($array, $string) {
	return in_array(strtolower($string), $array) || in_array(strtoupper($string), $array);
}

function lookup($key, $array) {
	if(array_key_exists($key, $array)) {
		return $array[$key];
	}
	return $key;
}

function compareDate($a, $b) {
		return new DateTime($a['date']) <=> new DateTime($b['date']);
	}

/* Various Functions */

function removeLineBreaks($string) {
	return preg_replace("/\r|\n/", "", $string);
}

function stringRange($rawString, $startString, $endString) {
	$string = removeLineBreaks($rawString);
	$r = explode($startString, $string);

	if (isset($r[1])){
			if($endString === false) return $r[1];

		$r = explode($endString, $r[1]);
		return $r[0];
	}
	return '';
}

function printBoolean($x) {
	return $x === true ? "true" : "false";
}

function escape($a) {
	return htmlspecialchars($a, ENT_QUOTES);
}

/* Array Functions */

function escapeArray(&$array) {
	array_walk_recursive($array, function(&$item) {
		$item = escape($item);
	});
}

function lowercaseArray(&$array) {
	return array_map('strtolower', $array);
}

function uppercaseArray(&$array) {
	return array_map('strtoupper', $array);
}

function printArray($array, $lowercase = false) {
	if (empty($array))
		return "none";

	$output = (is_array($array)) ? implode(",", $array) : $array;
	if ($lowercase === true)
		return strtolower($output);
	return $output;
}

function prettyPrintArray($array) {
	return (is_array($array)) ? implode(", ", $array) : $array;
}

function getArray($string, $unique = false, $uppercase = false, $lowercase = false) {
	if (strtolower($string) === "none")
		return [];

	if ($uppercase === true)
		$string = strtoupper($string);
	if ($lowercase === true)
		$string = strtolower($string);

	if (contains($string, ",")) {
		$array = explode(",", $string);
		if (arrayContains($array, "none"))
			return [];
		if ($unique === true)
			return array_unique($array);
		return $array;
	}

	return (array) $string;
}

function getArrayWith($array, $string) {
	if (!is_array($array))
		return $array . $string;

	$array[] = $string;
	return $array;
}

function getArrayWithout($array, $string) {
	if (($key = array_search($string, $array)) !== false) {
		unset($array[$key]);
	}
	return $array;
}

/* Cookie, Get and Post */

function writeCookie($name, $val, $time = "1 year") {
	$expTime = new DateTime($time);
	$exp = $expTime->getTimestamp();

	setcookie($name, $val, $exp, '/', null, false, true);
}

function getToCookie($name, $value, $token) {
	if (validToken(getParameter("token"), $token)) {
		writeCookie($name, $value, "1 year");
	}
}

function getParameter($string) {
	return !empty($_GET[$string]) ? $_GET[$string] : '';
}

function getCookie($string) {
	return !empty($_COOKIE[$string]) ? $_COOKIE[$string] : '';
}

function getInput($get, $cookie) {
	if (!empty($get) && $get !== $cookie) {
		return $get;
	}
	return $cookie;
}

/* Redirect */

function redirect($path = '.') {
	header('Location:' . $path);
	die();
}

function redirectToDate($desiredDate, $today) {
	if ($desiredDate == $today) {
		redirect(".");
	} else {
		redirect("?date=" . $desiredDate);
	}
}

/* Time */

function getDateFromInterval($date, $interval = "today") {
	return date("Y-m-d", strtotime($interval, strtotime($date)));
}

function formatTime($time, $interval = "now") {
	return date("H:i", strtotime($interval, strtotime($time)));
}

function createTime($input) {
	return DateTime::createFromFormat('H:i', $input);
}

function formatWeekDay($date) {
	$object = new DateTime($date);
	return $object->format("D");
}

function formatReadableDate($date) {
	$object = new DateTime($date);
	return $object->format("d.m.y");
}

function readableToIsoDate($date) {
	$object = DateTime::createFromFormat('d.m.y', $date);
	return $object->format("Y-m-d");
}

function createJsTime($time) {
	return date("M j, Y H:i:s", strtotime($time));
}

/* CSRF Token */
if (!isset($_SESSION['token'])) {
	$_SESSION['token'] = bin2hex(random_bytes(16));
}
$token = $_SESSION['token'];
$tokenEmbed = '&amp;token=' . $token;

function validToken($input, $token) {
	$_SESSION['validToken'] = $input === $token;
	return $_SESSION['validToken'];
}
