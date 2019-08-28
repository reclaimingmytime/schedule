<?php
/* Time */
//$timezone = 'Europe/London';

//$minDate = "01.01.2000";
//$maxDate = "01.01.2029";
$excludeWeekends = false;

/* API connection */
$defaultClass = "CLASS1";
$allowedClasses = ['CLASS1', 'CLASS2'];

$api = "https://example.com/api.json?class=$defaultClass";

/* Handling Data */
define('CALENDAR', 'cal');

define('START', 'Start');
define('END', 'End');
define('SUBJECT', 'Subject');
define('ROOM', 'Room');
define('PROF', 'Professor');

/* Displaying Data */
$subjects = [
	"brk" => "Break"
];

$emptyProfs = ['-'];

$displayProfs = true;
$profs = [
	"doe" => "John Doe",
];

$roomPrefix = 'Room-';

$rooms = [
		"001" => "Entrance Hall"
];

/* Icons */
$manifest = [
		"name" => "Schedule",
		"short_name" => "Schedule",
		/* "icons" => [
				[
						"src" => "img/icon-192.png",
						"sizes" => "192x192",
						"type" => "image/png",
				],
		], */
		"theme_color" => "#ffffff",
		"background_color" => "#ffffff",
		"start_url" => "/",
		"display" => "standalone",
];
/*
 $touchIconPath = "img/apple-touch-icon.png";
 $favicons = [
		[
				"src" => "img/icon-32.png",
				"sizes" => "32x32",
				"type" => "image/png",
		]
];
*/