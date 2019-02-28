<?php
/* Time */
//$timezone = 'Europe/London';

$minDate = "01.01.2000";

/* API connection */
$cache_file = "mock-api.json";

$defaultClass = "CLASS1";
$allowedClasses = ['CLASS1', 'CLASS2'];
$api = 'https://example.com/api/';

/* Handling Data */
define('CALENDAR', 'cal');

define('START', 'Start');
define('END', 'End');
define('SUBJECT', 'Subject');
define('ROOM', 'Room');
define('PROF', 'Professor');

$emptyProfs = ['-'];

$profs = [
		"js" => "John Doe",
];

$roomPrefix = 'Room-';