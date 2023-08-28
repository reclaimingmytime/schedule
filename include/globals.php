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

function startsWith($haystack, $needle) {
	return substr($haystack, 0, strlen($needle)) === $needle;
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

function removeFromString($search, $subject) {
  return str_replace($search, "", $subject);
}

function lookup($key, $array) {
	if(isset($array[$key])) {
		return $array[$key];
	}
	return $key;
}

function mergeDimension($arr) {
	return array_merge(...array_values($arr));
}

function compareStartDateTime($a, $b) {
	return new DateTime($a['startDateTime']) <=> new DateTime($b['startDateTime']);
}

function compareEndDateTime($a, $b) {
	return new DateTime($a['endDateTime']) <=> new DateTime($b['endDateTime']);
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

function inArray($needle, $haystack) { //Case-insensitive
	return in_array(strtolower($needle), array_map('strtolower', $haystack));
}

function arraySearch($needle, $haystack) { //Case-insensitive
	return array_search(strtolower($needle), array_map('strtolower', $haystack));
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
	if (($key = arraySearch($string, $array)) !== false) {
		unset($array[$key]);
	}
	return $array;
}

/* Cookie, Get and Post */

function writeCookie($name, $val, $time = "1 year") {
	$expTime = new DateTime($time);
	$exp = $expTime->getTimestamp();

	setcookie($name, $val, $exp, '/', "", false, true);
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

function formatFullReadableDate($desiredDate) {
	return formatWeekDay($desiredDate) . ", " . formatReadableDate($desiredDate);
}

function readableToIsoDate($date) {
	$object = DateTime::createFromFormat('d.m.y', $date);
	return $object->format("Y-m-d");
}

function getLastMonday($date) {
	return getDateFromInterval($date, "last monday");
}

function isSameWeek($date1, $date2) {
	return (new DateTime($date1))->format("W Y") == (new DateTime($date2))->format("W Y");
}

function createJsTime($time) {
	return date("M j, Y H:i:s", strtotime($time));
}

function dateTimeToHourMin($time) {
	return DateTime::createFromFormat('M j, Y H:i:s', $time)->format("H:i");
}

/* Colors */
$colors = [
		"light" => [
				"bg-hex" => "#ffffff",
				"body" => "bg-white text-body",
				"navbar" => "navbar navbar-light bg-light",
				"highlightClasses" => "bg-dark text-light",
				"activeDropdown" => "text-body",
		],
		"dark" => [
				"bg-hex" => "#343a40",
				"body" => "bg-dark text-light",
				
				"text-muted" => "text-white-50",
				"text-secondary" => "text-white-50",
				"text-dark" => "text-light",
				"card" => "card text-light bg-secondary",
				"navbar" => "navbar navbar-dark bg-dark",
				
				"dropdown" => "dropdown bg-dark",
				"dropdown-menu" => "dropdown-menu dropdown-menu-dark",
				"highlightClasses" => "bg-dark text-light",
				"activeDropdown" => "",
		],
];

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
