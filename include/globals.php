<?php

if (!empty($timezone)) {
	date_default_timezone_set($timezone);
}

/* Functions */

//function strposa($haystack, $needle, $offset = 0) {
//	if (!is_array($needle)) {
//		$needle = [$needle];
//	}
//	foreach ($needle as $query) {
//		if (!empty($query) && strpos($haystack, $query, $offset) !== false) {
//			return true;
//		}
//	}
//	return false;
//}

function isBetween($x, $min, $max) {
  return ($min <= $x) && ($x <= $max);
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