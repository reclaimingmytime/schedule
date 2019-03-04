<?php
if(!file_exists("config.php")) {
	die("config.php missing. Please use config.default.php as a template (if available).");
}
require_once("config.php");

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

function getClassInput() {
	$classGET = !empty($_GET['c']) ? $_GET['c'] : '';
	$classCookie = !empty($_COOKIE['c']) ? $_COOKIE['c'] : '';

	if(!empty($classGET) && $classCookie !== $classGET) {
		return $classGET;
	} else {
		return $classCookie;
	}
}

function getClass($defaultClass, $allowedClasses) {
	$class = getClassInput();
	if(!empty($class) && in_array($class, $allowedClasses)) {
		$expTime = new DateTime("1 year");
		$exp = $expTime->getTimestamp();

		setcookie("c", $class, $exp, '/', null, false, true);
		return $class;
	}
	return $defaultClass;
}

function getAPIUrl($api, $replace, $default) {
	return str_replace($default, $replace, $api);
}

function createCache($folder) {
	if (!is_writable($folder)) { //checks both, exists & writable
		$errorMsg = 'Insufficient permission to create files and folders. Please give the parent directory sufficient permissions (at least chmod 700).';
		
		if (!file_exists($folder)) {
			try {
				mkdir($folder, 0700, true);
			} catch (Exception $ex) {
				die($errorMsg);
			}
		} 
		if (!is_writable($folder)) {
			die($errorMsg);
		}
	}
}

function retreiveData($api, $cache_file) {
	if (is_writable($cache_file) && (filemtime($cache_file) > (time() - 60 * 30 ))) {
		$file = file_get_contents($cache_file);
	} else {
		try {
			$file = file_get_contents($api);
		} catch (Exception $ex) {
			die("Unable to reach API.");
		}
		//refresh cache
		file_put_contents($cache_file, $file, LOCK_EX);
	}

	$calendarJSON = file_get_contents($cache_file);

	if (empty($calendarJSON) || $calendarJSON === false) {
		die("Error connecting to API.");
	}

	$calendarArray = json_decode($calendarJSON, true);

	return defined('CALENDAR') ? $calendarArray[CALENDAR] : $calendarArray;
}

if(!isset($allowedClasses)) {
	$allowedClasses = [];
}

if(!isset($defaultClass) || !isset($api)) {
	die('Empty or invalid API. Please specify $api and $defaultClass in your config file in the following format:<br><b>$api</b> = https://example.com/api.json?class=<b>$defaultClass</b>');
}

$class = getClass($defaultClass, $allowedClasses);
$desiredAPI = getAPIUrl($api, $class, $defaultClass);

$folder = "cache/";
createCache($folder);
$cache_file = $folder . $class . ".json";
$calendar = retreiveData($desiredAPI, $cache_file);

/* Date preparation */

if(empty($minDate)) {
	$minDate = date("d.m.Y", 0);
}

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

function createTime($input) {
	return DateTime::createFromFormat('H:i', $input);
}

function excludedWeekends() {
	global $excludeWeekends;
	return isset($excludeWeekends) && $excludeWeekends === true;
}

$today = date("Y-m-d");
$currentTime = date("H:i");

$desiredDate = getCustomDate("d", $today);

$weekBump = false;
if(excludedWeekends() && isWeekend($desiredDate)) {
	$desiredDate = createNewDate($desiredDate, "1 weekday");
	$weekBump = true;
}
if(excludedWeekends() && isWeekend($today)) {
	$today = createNewDate($today, "1 weekday");
	if($today == $desiredDate) {
		$weekBump = true;
	}
}

$desiredDateObj = new DateTime($desiredDate);

$desiredDatePretty = $desiredDateObj->format("d.m.y");
$weekDay = $desiredDateObj->format("D");
$displayedDateFull = $weekDay . ", " . $desiredDatePretty;
$displayedDate = $desiredDatePretty;

if(excludedWeekends()) {
	$weekDayString = "weekday";
} else {
	$weekDayString = "day";
}

$nextWeek = createNewDate($desiredDate, "1 week");
$nextDay = createNewDate($desiredDate, "1 $weekDayString");

$prevDay = createNewDate($desiredDate, "1 $weekDayString ago");
$prevWeek = createNewDate($desiredDate, "1 week ago");

if($prevWeek < createNewDate($minDate)) {
	$prevWeek = "none";
}

if($prevDay < createNewDate($minDate)) {
	$prevDay = "none";
}

/* Schedule preparation */

//Room Functions
if(!isset($roomPrefix)) {
	$roomPrefix = "";
}

function prepareRoom($raw, $roomPrefix) {
	return !empty($roomPrefix) ? str_replace($roomPrefix, "", $raw) : $raw;
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

if(!isset($profs)) {
	$profs = [];
}

if(!isset($emptyProfs)) {
	$emptyProfs = [];
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

function isWeekend($date) {
	$weekDay = date('w', strtotime($date));
	return ($weekDay == 0 || $weekDay == 6);
}

//Ensure defined constants
function ensureDefined($const) {
	if(!defined($const)) {
		die("Undefined constant $const. Please define in config file.");
	}
}
ensureDefined("SUBJECT");
ensureDefined("START");
ensureDefined("ROOM");
ensureDefined("PROF");

//Duplicate check
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

		if(defined('SUBJECT')) {
			$new["subject"] = $entry[SUBJECT];
		}
		
		if(defined('ROOM')) {
			$new["room"] = prepareRoom($entry[ROOM], $roomPrefix);
		}
		
		if(defined('PROF')) {
			$new["prof"] = prepareProfs($entry[PROF], $emptyProfs, $profs);
		}
		

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
function onGoingEvent($event) {
	global $desiredDate;
	global $today;
	global $currentTime;
	
	return $desiredDate == $today && isBetween(createTime($currentTime), createTime($event['start']), createTime($event['end']));
}
?>
<!DOCTYPE html>
<html lang="en">
	<head data-desireddate="<?php echo $desiredDate; ?>" data-today="<?php echo $today; ?>" data-nextday="<?php echo $nextDay; ?>" data-prevday="<?php echo $prevDay; ?>" data-nextweek="<?php echo $nextWeek; ?>" data-prevweek="<?php echo $prevWeek; ?>">
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>Calendar for <?php echo $displayedDateFull; ?></title>

		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha256-YLGeXaapI0/5IgZopewRJcFXomhRMlYYjugPLSyNjTY=" crossorigin="anonymous">
		<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.2/css/all.css" integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr" crossorigin="anonymous">
		
		<style>
			#navbarDropdown {
				outline: none;
			}
			.active {
				pointer-events: none;
			}
			.dropdown-item.active {
				font-weight: bold;
				color: #212529;
				background-color: transparent;
			}
		</style>
	</head>
	<body>
		<div class="container-fluid">
			<header>
				<nav class="navbar navbar-expand navbar-light bg-light mt-3 mb-4">
					<div class="navbar-header d-none d-sm-block">
						
						<?php if ($desiredDate !== $today) { ?>
							<a class="navbar-brand" href="?">Schedule</a>
						<?php } else { ?>
							<span class="navbar-brand">Schedule</span>
						<?php } ?>
					</div>
					<ul class="navbar-nav m-auto ml-sm-0">
						<?php if ($desiredDate !== $today) { ?>
							<li class="nav-item mr-4 ml-3"><a class="nav-link" href="."><i class="fas fa-play"></i> <span class="d-none d-md-inline">Today</span></a></li>
						<?php } else { ?>
							<li class="nav-item mr-4 ml-3 active"><a class="nav-link"><i class="fas fa-play"></i> <span class="d-none d-md-inline">Today</span></a></li>
						<?php } ?>

						<li class="nav-item mr-4"><a class="nav-link" href="?d=<?php echo $nextDay; ?>"><i class="fas fa-forward"></i> <span class="d-none d-md-inline">Next Day</span></a></li>
						<li class="nav-item mr-4"><a class="nav-link" href="?d=<?php echo $nextWeek; ?>"><i class="fas fa-step-forward"></i> <span class="d-none d-md-inline">Next Week</span></a></li>

						<?php if ($prevDay !== "none") { ?>
							<li class="nav-item mr-4"><a class="nav-link" href="?d=<?php echo $prevDay; ?>"><i class="fas fa-backward"></i> <span class="d-none d-md-inline">Previous Day</span></a></li>
						<?php } if ($prevWeek !== "none") { ?>
							<li class="nav-item mr-4"><a class="nav-link" href="?d=<?php echo $prevWeek; ?>"><i class="fas fa-step-backward"></i> <span class="d-none d-md-inline">Previous Week</span></a></li>
						<?php } ?>
							
						<?php if(!empty($allowedClasses)) { ?>
						<li class="nav-item d-none d-sm-inline-block dropdown">
							<a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
								<i class="fas fa-folder"></i> <span class="d-none d-md-inline"><?php echo $class; ?></span>
							</a>
							<div class="dropdown-menu" aria-labelledby="navbarDropdown">
								<?php foreach($allowedClasses as $c) { ?>
								<a class="dropdown-item<?php if($class == $c) echo " active";?>" href="?c=<?php echo $c; ?>&amp;d=<?php echo $desiredDate; ?>"><i class="fas fa-folder-open"></i> <?php echo $c; ?></a>
								<?php } ?>
							</div>
						</li>
						<?php } ?>
					</ul>
				</nav>
			</header>
			
			<main>
				<ul class="list-inline text-muted h4 py-2">
					<li class="list-inline-item"><i class="fas fa-calendar-alt"></i></li>
					<li class="list-inline-item"><?php echo $weekDay; ?></li>
					<li class="list-inline-item"><?php echo $displayedDate; ?></li>
					<li class="list-inline-item currentTime"><?php echo $currentTime; ?></li>
				</ul>

				<?php if (empty($schedule)) { ?>
					<div class="alert alert-secondary mt-4" role="alert">
						No entries have been found for that day.
					</div>
					<?php
				} else { ?>
					<div class="row">
						<?php foreach ($schedule as $event) {
							$timeRange = $event['start'] . " - " . $event['end'];
							$headerClasses = onGoingEvent($event) ? ' bg-dark text-light' : '';
						?>
						
						<div class="col-md-4 col-xl-3 pr-md-4 pr-xl-5 pb-4 pb-xl-5">
							<div class="card h-100 mt-3">
								<div class="card-header<?php echo $headerClasses; ?>">
									<i class="fas fa-clock"></i>
									<strong><?php echo $timeRange ?></strong>
								</div>

								<div class="card-body">
											<?php if (!empty($event['subject'])) { ?>
												<p class="font-weight-bold"><?php echo $event['subject']; ?></p>
											<?php } ?>

											<ul class="list-inline">
												<?php if (!empty($event['room'])) { ?>
													<li class="list-inline-item pr-3"><?php echo $event['room']; ?></li>
												<?php } ?>
													
												<?php if (!empty($event['prof'])) { ?>
													<li class="list-inline-item"><?php echo $event['prof']; ?></li>
												<?php } ?>
											</ul>
								</div>
							</div>
						</div>

						<?php } ?>
						</div>
					<?php } ?>

				<?php if (isset($weekBump) && $weekBump === true) { ?>
					<p class="text-center text-sm-left mt-4">
						<a class="btn btn-outline-secondary" data-toggle="collapse" href="#collapseExample" role="button" aria-expanded="false" aria-controls="collapseExample">
							Info
						</a>
					</p>
					<div class="collapse" id="collapseExample">
						<div class="card card-body pb-1">
							<p>Weekends have been excluded from the schedule. You are now viewing the next week day.</p>
						</div>
					</div>
				<?php } ?>
			</main>
			
			<footer class="text-center my-4">
				<?php if (!empty($allowedClasses)) { ?>
					<div class="d-inline-block d-sm-none dropup d-inline">
						<a class="btn btn-white text-muted dropdown-toggle" href="#" role="button" id="classLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
							<i class="fas fa-folder"></i> <?php echo $class; ?>
						</a>
						<div class="dropdown-menu" aria-labelledby="classLink">
							<?php foreach ($allowedClasses as $c) { ?>
								<a class="dropdown-item<?php if ($class == $c) echo " active"; ?>" href="?c=<?php echo $c; ?>&amp;d=<?php echo $desiredDate; ?>"><i class="fas fa-folder-open"></i> <?php echo $c; ?></a>
							<?php } ?>
						</div>
					</div>
				<?php } ?>
				<a href="#" class="btn btn-white text-muted top d-none" role="button" aria-pressed="true"><i class="fas fa-angle-up"></i> Top</a>
			</footer>

		</div>
		
		<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js" integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha256-CjSoeELFOcH0/uxWu6mC/Vlrc1AARqbm/jiiImDGV3s=" crossorigin="anonymous"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.touchswipe/1.6.19/jquery.touchSwipe.min.js" integrity="sha256-ns1OeEP3SedE9Theqmu444I44sikbp1O+bF/6BNUUy0=" crossorigin="anonymous"></script>
		<script src="js/main.min.js"></script>
	</body>
</html>
