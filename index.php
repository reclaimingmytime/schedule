<?php
require_once("config.php");

if (!empty($timezone)) {
	date_default_timezone_set($timezone);
}

/* Functions */

function strposa($haystack, $needle, $offset = 0) {
	if (!is_array($needle)) {
		$needle = [$needle];
	}
	foreach ($needle as $query) {
		if (!empty($query) && strpos($haystack, $query, $offset) !== false) {
			return true;
		}
	}
	return false;
}

function equals($x, $y) {
	return $x == $y;
}

function exists($x, $y) {
	return strpos($x, $y) === true;
}

function notExists($x, $y) {
	return strpos($x, $y) === false;
}

function escape($a) {
	return htmlspecialchars($a, ENT_QUOTES);
}

function escapeArray(&$array) {
	array_walk_recursive($array, function(&$item) {
		$item = escape($item);
	});
}

/* API connection */

function retreiveData($api, $cache_file) {
	if (file_exists($cache_file) && (filemtime($cache_file) > (time() - 60 * 30 ))) {
		$file = file_get_contents($cache_file);
	} else {
		$file = file_get_contents($api);
		//refresh cache
		file_put_contents($cache_file, $file, LOCK_EX);
	}

	$calendarJSON = file_get_contents($cache_file);

	if (empty($calendarJSON) || $calendarJSON === false) {
		die("Error connecting to API.");
	}

	$calendarArray = json_decode($calendarJSON, true);

	return $calendarArray[CALENDAR];
}

$calendar = retreiveData($api, $cache_file);

/* Date preparation */

$min = new DateTime($minDate);

function validDate($input) {
	//DateTime even detects 31st Feb and 31st Nov as errors
	$date = DateTime::createFromFormat('Y-m-d', $input);
	$date_errors = DateTime::getLastErrors();

	global $min;
	return $date >= $min && $date_errors['warning_count'] === 0 && $date_errors['error_count'] === 0;
}

function getCustomDate($param, $today) {
	if (isset($_GET[$param]) && validDate($_GET[$param])) {
		return $_GET[$param];
	}
	return $today;
}

function createNewDate($date, $interval = "today") {
	return date("Y-m-d", strtotime($interval, strtotime($date)));
}

$today = date("Y-m-d");
$currentTime = date("H:i");

$desiredDate = getCustomDate("d", $today);

$desiredDateObj = new DateTime($desiredDate);

$desiredDatePretty = $desiredDateObj->format("d.m.y");
$weekDay = $desiredDateObj->format("D");
$displayedDateFull = $weekDay . ", " . $desiredDatePretty;
$displayedDate = $desiredDatePretty;

$nextWeek = createNewDate($desiredDate, "1 week");
$nextDay = createNewDate($desiredDate, "1 day");

$prevDay = createNewDate($desiredDate, "1 day ago");
$prevWeek = createNewDate($desiredDate, "1 week ago");

/* Schedule preparation */

//Room Functions
function prepareRoom($raw, $roomPrefix) {
	return str_replace($roomPrefix, "", $raw);
}

//Prof Functions
function trimPlaceholders($raw, $placeholders) {
	if (in_array($raw, $placeholders)) {
		return "";
	}
	return $raw;
}

function getFullNames($abbr, $profs) {
	if (array_key_exists($abbr, $profs)) {
		return $profs[$abbr];
	}
	return $abbr;
}

function prepareProfs($prof, $emptyProfs, $profs) {
	$shortProf = trimPlaceholders($prof, $emptyProfs);
	return getFullNames($shortProf, $profs);
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

//Duplicate check functions
function sameEvent($e, $new) {
	return equals($e["start"], $new["start"]) && equals($e["end"], $new["end"]) && equals($e["subject"], $new["subject"]);
}

function validProf($profs, $emptyProfs) {
	return !in_array($profs, $emptyProfs);
}

function containsNewRoom($e, $new) {
	return !empty($e["room"]) && notExists($e["room"], $new["room"]);
}

function containsNewProf($e, $new) {
	global $emptyProfs;
	return !empty($e["prof"]) && notExists($e["prof"], $new["prof"]) && validProf($new["prof"], $emptyProfs);
}

//Populating schedule array
$schedule = [];

foreach ($calendar as $entry) {
	$date = extractDate($entry[START]);

	if ($date == $desiredDate) {
		$new = [];
		$new["start"] = extractTime($entry[START]);
		$new["end"] = extractTime($entry[END]);
		$new["subject"] = $entry[SUBJECT];
		$new["room"] = prepareRoom($entry[ROOM], $roomPrefix);
		$new["prof"] = prepareProfs($entry[PROF], $emptyProfs, $profs);

		$add = true;

		foreach ($schedule as $key => $existing) {
			if (sameEvent($existing, $new)) {
				$add = false;
				if (containsNewRoom($existing, $new)) {
					$schedule[$key]["room"] .= ", " . $new["room"];
				}
				if (containsNewProf($existing, $new)) {
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

/* Display Schedule */
?>
<!DOCTYPE html>
<html lang="en">
	<head data-nextday="<?php echo $nextDay; ?>" data-prevday="<?php echo $prevDay; ?>" data-nextweek="<?php echo $nextWeek; ?>" data-prevweek="<?php echo $prevWeek; ?>">
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>Calendar for <?php echo $displayedDateFull; ?></title>

		<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">

		<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.2/css/all.css" integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr" crossorigin="anonymous">
	</head>
	<body>
		<div class="container-fluid">
			<header>
				<nav class="navbar navbar-expand navbar-light bg-light mt-3 mb-4">
					<div class="navbar-header  d-none d-sm-block">
						<?php if ($desiredDate !== $today) { ?>
							<a class="navbar-brand" href=".">Schedule</a>
						<?php } else { ?>
							<span class="navbar-brand">Schedule</span>
						<?php } ?>
					</div>
					<ul class="navbar-nav m-auto ml-sm-0">
						<?php if ($desiredDate !== $today) { ?>
						<li class="nav-item mr-3 ml-3"><a class="nav-link" href="?"><i class="fas fa-play"></i> <span class="d-none d-md-inline">Today</span></a></li>
						<?php } else { ?>
						<li class="nav-item mr-3 ml-3 active"><a class="nav-link"><i class="fas fa-play"></i> <span class="d-none d-md-inline">Today</span></a></li>
						<?php } ?>

						<li class="nav-item mr-3"><a class="nav-link" href="?d=<?php echo $nextDay; ?>"><i class="fas fa-forward"></i> <span class="d-none d-md-inline">Next Day</span></a></li>
						<li class="nav-item mr-3"><a class="nav-link" href="?d=<?php echo $nextWeek; ?>"><i class="fas fa-step-forward"></i> <span class="d-none d-md-inline">Next Week</span></a></li>

						<?php if ($prevDay >= createNewDate($minDate)) { ?>
						<li class="nav-item mr-3"><a class="nav-link" href="?d=<?php echo $prevDay; ?>"><i class="fas fa-backward"></i> <span class="d-none d-md-inline">Previous Day</span></a></li>
						<?php }
						if ($prevWeek >= createNewDate($minDate)) {
							?>
							<li class="nav-item"><a class="nav-link" href="?d=<?php echo $prevWeek; ?>"><i class="fas fa-step-backward"></i> <span class="d-none d-md-inline">Previous Week</span></a></li>
						<?php } ?>
					</ul>
				</nav>
			</header>

			<main>
				<ul id="currentDay" class="list-inline text-muted h4">
					<li class="list-inline-item"><i class="fas fa-calendar-alt"></i></li>
					<li class="list-inline-item"><?php echo $weekDay; ?></li>
					<li class="list-inline-item"><?php echo $displayedDate; ?></li>
					<li class="list-inline-item"><?php echo $currentTime; ?></li>
				</ul>

				<?php if (empty($schedule)) { ?>
					<div class="alert alert-secondary mt-4" role="alert">
						No entries have been found for that day.
					</div>
					<?php
				} else {

					foreach ($schedule as $event) {
						$timeRange = $event['start'] . " - " . $event['end'];
						?>
						<div class="card mb-4 mt-4">
							<div class="card-header">
								<i class="fas fa-clock"></i>
								<strong ml-xl><?php echo $timeRange ?></strong>
							</div>

							<div class="card-body">
								<p><strong><?php echo $event['subject']; ?></strong></p>

								<?php if (!empty($event['room'])) { ?>
									<p><?php echo $event['room']; ?></p>
								<?php } ?>

								<p><?php echo $event['prof']; ?></p>
							</div>
						</div>
						<?php
					}
				}
				?>
			</main>
		</div>
		<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
		<script src="js/swipe.min.js"></script>
		<script src="js/main.min.js"></script>
	</body>
</html>
