<?php
require_once("config.php");
if(!isset($icaltoken) || $_GET['icaltoken'] != $icaltoken)) {
    echo "No permission";
    die;
}

require_once("include/globals.php");

$realTimezone = $timezone;
$timezone = "UTC";
require_once("include/data-acquisition.php");

$fullCalendar = true;
require_once("include/data-processing.php");

/* Define and output ical */
$ical = "BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Schedule//EN";

foreach($schedule as $event) {
    $ical .= "
BEGIN:VEVENT
UID:" . md5(uniqid(mt_rand(), true)) . "
DTSTAMP:" . gmdate("Ymd") . "T" . gmdate("His") . "Z
DTSTART;TZID=" . $realTimezone . ":" . date("Ymd\THis\Z", strtotime($event['startDateTime'])) .  "
DTEND;TZID=" . $realTimezone . ":" . date("Ymd\THis\Z", strtotime($event['endDateTime'])) . "
SUMMARY:" . $event['subject'] . ", " . $event['room'] . "
END:VEVENT";
}

header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename=schedule.ics');

echo $ical . "
END:VCALENDAR";
