<?php
/* Time */
//$timezone = 'Europe/London';

//$minDate = "01.01.2000";
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

$profs = [
	"doe" => "John Doe",
];

$roomPrefix = 'Room-';

$rooms = [
		"001" => "Entrance Hall"
];
