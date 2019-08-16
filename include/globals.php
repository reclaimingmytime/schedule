<?php

if (!empty($timezone)) {
	date_default_timezone_set($timezone);
}

/* Functions */

function stringRange($string, $startString, $endString) {
	$r = explode($startString, $string);
	if (isset($r[1])){
			if($endString === false) return $r[1];
			
			$r = explode($endString, $r[1]);
			return $r[0];
	}
	return '';
}

function isBetween($x, $min, $max) {
	return ($min <= $x) && ($x <= $max);
}

function isBelowOrAbove($x, $min, $max) {
	return ($min < $x) && ($x < $max);
}

function equals($x, $y) {
	return $x === $y;
}

function contains($x, $y) {
	return strpos($x, $y) !== false;
}

function notContains($x, $y) {
	return strpos($x, $y) === false;
}

function isTrue($x) {
	return (bool)$x === true;
}

function isFalse($x) {
	return $x === false;
}

function printBoolean($x) {
	return $x === true ? "true" : "false";
}

function escape($a) {
	return htmlspecialchars($a, ENT_QUOTES);
}

function escapeArray(&$array) {
	array_walk_recursive($array, function(&$item) {
		$item = escape($item);
	});
}

function printArray($rawInput, $lowercase = false) {
	$input = $lowercase === true ? strtolower($rawInput) : $rawInput;
	return is_array($input) ? implode(",", $input) : $input;
}

function getArrayWith($array, $string) {
	if(!is_array($array)) return $array . $string;
	
	$array[] = $string;
	return $array;
}

function getArrayWithout($array, $string) {
	if (($key = array_search($string, $array)) !== false) {
		unset($array[$key]);
	}
	return $array;
}

function writeCookie($name, $val, $time) {
	$expTime = new DateTime($time);
	$exp = $expTime->getTimestamp();

	setcookie($name, $val, $exp, '/', null, false, true);
}

function isDifferent($x, $y) {
	return !empty($x) && $x !== $y;
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

/* CSRF Token */
if(!isset($_SESSION['token'])) {
	$_SESSION['token'] = bin2hex(random_bytes(16));
}
$token = $_SESSION['token'];
$tokenEmbed = '&amp;token=' . $token;

function validToken($input, $token) {
	if($input !== $token) {
		$_SESSION['validToken'] = false;
		return false;
	} else {
		$_SESSION['validToken'] = true;
	}
	return true;
}