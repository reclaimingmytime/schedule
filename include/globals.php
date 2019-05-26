<?php

if (!empty($timezone)) {
	date_default_timezone_set($timezone);
}

/* Functions */

function stringPart($string, $startString) {
	$pos = strpos($string, $startString);
	if($pos == 0) {
		return;
	}
	$start = $pos + strlen($startString);
	return substr($string, $start);
}

function stringRange($string, $startString, $endString) {
	if($endString == false) {
		return stringPart($string, $startString);
	}
	
	$start = strpos($string, $startString) + strlen($startString); //select start point and don't include start text
	$end = strpos($string, $endString); //select end point by selecting next delimiter
	
	return substr($string, $start, $end - $start);
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

function exists($x, $y) {
	return strpos($x, $y) === true;
}

function notExists($x, $y) {
	return strpos($x, $y) === false;
}

function isTrue($x) {
	return $x === true;
}

function isFalse($x) {
	return $x === false;
}

function escape($a) {
	return htmlspecialchars($a, ENT_QUOTES);
}

function escapeArray(&$array) {
	array_walk_recursive($array, function(&$item) {
		$item = escape($item);
	});
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

/* Time */

function formatIsoDate($date, $interval = "today") {
	return date("Y-m-d", strtotime($interval, strtotime($date)));
}

function createTime($input) {
	return DateTime::createFromFormat('H:i', $input);
}

function createTimeString($input) {
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