<?php
if (!file_exists("config.php")) {
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
	} else {
		return $cookie;
	}
}

/* API connection */
function validClass($class, $allowedClasses) {
	return !empty($class) && in_array($class, $allowedClasses);
}

function getClass($defaultClass, $allowedClasses) {
	$classGET = getParameter("class");
	$classCookie = getCookie("class");
	
	$class = getInput($classGET, $classCookie);

	if (validClass($class, $allowedClasses)) {
		if (isDifferent($classGET, $classCookie)) {
			writeCookie("class", $class, "1 year");
		}
		return $class;
	}
	//use cookie as fallback
	if(validClass($classCookie, $allowedClasses)) {
		return $classCookie;
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

if (!isset($allowedClasses)) {
	$allowedClasses = [];
}

if (!isset($defaultClass) || !isset($api)) {
	die('Empty or invalid API. Please specify $api and $defaultClass in your config file in the following format:<br><b>$api</b> = https://example.com/api.json?class=<b>$defaultClass</b>');
}

$desiredClass = getClass($defaultClass, $allowedClasses);
$desiredAPI = getAPIUrl($api, $desiredClass, $defaultClass);

$folder = "cache/";
createCache($folder);
$cache_file = $folder . $desiredClass . ".json";
$calendar = retreiveData($desiredAPI, $cache_file);

/* Date preparation */

if (empty($minDate)) {
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

function hasExcludedWeekends() {
	global $excludeWeekends;
	return isset($excludeWeekends) && $excludeWeekends === true;
}

$today = date("Y-m-d");
$currentTime = date("H:i");

$desiredDate = getCustomDate("date", $today);

$weekBump = false;
if (hasExcludedWeekends() && isWeekend($desiredDate)) {
	$desiredDate = createNewDate($desiredDate, "1 weekday");
	$weekBump = true;
}
if (hasExcludedWeekends() && isWeekend($today)) {
	$today = createNewDate($today, "1 weekday");
	if ($today == $desiredDate) {
		$weekBump = true;
	}
}

$desiredDateObj = new DateTime($desiredDate);

$desiredDatePretty = $desiredDateObj->format("d.m.y");
$weekDay = $desiredDateObj->format("D");
$displayedDateFull = $weekDay . ", " . $desiredDatePretty;
$displayedDate = $desiredDatePretty;

if (hasExcludedWeekends()) {
	$weekDayString = "weekday";
} else {
	$weekDayString = "day";
}

$nextWeek = createNewDate($desiredDate, "1 week");
$nextDay = createNewDate($desiredDate, "1 $weekDayString");

$prevDay = createNewDate($desiredDate, "1 $weekDayString ago");
$prevWeek = createNewDate($desiredDate, "1 week ago");

if ($prevWeek < createNewDate($minDate)) {
	$prevWeek = "none";
}

if ($prevDay < createNewDate($minDate)) {
	$prevDay = "none";
}

/* Schedule preparation */

//General Functions
function lookup($room, $rooms) {
	if (array_key_exists($room, $rooms)) {
		return $rooms[$room];
	}
	return $room;
}

//Room Functions
if (!isset($roomPrefix)) {
	$roomPrefix = "";
}

function trimRoom($raw, $roomPrefix) {
	return !empty($roomPrefix) ? str_replace($roomPrefix, "", $raw) : $raw;
}

//Prof Functions
function trimPlaceholders($raw, $placeholders) {
	if (in_array($raw, $placeholders)) {
		return "";
	}
	return $raw;
}

if (!isset($profs)) {
	$profs = [];
}

if (!isset($emptyProfs)) {
	$emptyProfs = [];
}

function lookupProfs($prof, $emptyProfs, $profs) {
	$realProf = trimPlaceholders($prof, $emptyProfs);
	return lookup($realProf, $profs);
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
function ensureDefined($constant) {
	if (!defined($constant)) {
		die("Undefined constant $constant. Please define in config file.");
	}
}

function ensureAllDefined($constants) {
	foreach ($constants as $constant) {
		ensureDefined($constant);
	}
}

ensureAllDefined(['SUBJECT', 'START', 'END', 'ROOM', 'PROF']);

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
		$new["subject"] = !empty($subjects) ? lookup($entry[SUBJECT], $subjects) : $entry[SUBJECT];

		$shortRoom = !empty($roomPrefix) ? trimRoom($entry[ROOM], $roomPrefix) : $entry[ROOM];
		$new["room"] = lookup($shortRoom, $rooms);

		$new["prof"] = !empty($emptyProfs) && !empty($profs) ? lookupProfs($entry[PROF], $emptyProfs, $profs) : $entry[PROF];

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
	global $weekBump;

	return $desiredDate == $today && isBetween(createTime($currentTime), createTime($event['start']), createTime($event['end'])) && $weekBump === false;
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
							<li class="nav-item mr-4 ml-3"><a class="nav-link" href="."><i class="fas fa-play"></i> <span class="d-none d-lg-inline">Today</span></a></li>
						<?php } else { ?>
							<li class="nav-item mr-4 ml-3 active"><a class="nav-link"><i class="fas fa-play"></i> <span class="d-none d-lg-inline">Today</span></a></li>
<?php } ?>

						<li class="nav-item mr-4"><a class="nav-link" href="?date=<?php echo $nextDay; ?>"><i class="fas fa-forward"></i> <span class="d-none d-lg-inline">Next Day</span></a></li>
						<li class="nav-item mr-4"><a class="nav-link" href="?date=<?php echo $nextWeek; ?>"><i class="fas fa-step-forward"></i> <span class="d-none d-lg-inline">Next Week</span></a></li>

						<?php if ($prevDay !== "none") { ?>
							<li class="nav-item mr-4"><a class="nav-link" href="?date=<?php echo $prevDay; ?>"><i class="fas fa-backward"></i> <span class="d-none d-lg-inline">Previous Day</span></a></li>
						<?php } if ($prevWeek !== "none") { ?>
							<li class="nav-item mr-4"><a class="nav-link" href="?date=<?php echo $prevWeek; ?>"><i class="fas fa-step-backward"></i> <span class="d-none d-lg-inline">Previous Week</span></a></li>
						<?php } ?>

<?php if (!empty($allowedClasses)) { ?>
							<li class="nav-item d-none d-sm-inline-block dropdown">
								<a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
									<i class="fas fa-folder"></i> <span class="d-none d-lg-inline"><?php echo $desiredClass; ?></span>
								</a>
								<div class="dropdown-menu" aria-labelledby="navbarDropdown">
									<?php foreach ($allowedClasses as $class) { ?>
										<a class="dropdown-item<?php if ($desiredClass == $class) echo " active"; ?>" href="?class=<?php echo $class; ?>&amp;date=<?php echo $desiredDate; ?>"><i class="fas fa-folder-open"></i> <?php echo $class; ?></a>
	<?php } ?>
								</div>
							</li>
<?php } ?>
					</ul>
				</nav>
			</header>

			<main>
				<ul class="list-inline text-muted h4">
					<li class="list-inline-item"><i class="fas fa-calendar-alt"></i></li>
					<li class="list-inline-item"><?php echo $weekDay; ?></li>
					<li class="list-inline-item"><?php echo $displayedDate; ?></li>
					<li class="list-inline-item currentTime"><?php echo $currentTime; ?></li>
				</ul>

<?php if (empty($schedule)) { ?>
					<div class="alert alert-secondary mt-4" role="alert">
						No entries have been found for that day.
					</div>
					<?php } else {
					?>
					<div class="row">
						<?php
						foreach ($schedule as $event) {
							$timeRange = $event['start'] . " - " . $event['end'];
							$headerClasses = onGoingEvent($event) ? ' bg-dark text-light' : '';
							?>

							<div class="col-12 pb-1">
								<div class="card mt-3">
									<div class="card-header<?php echo $headerClasses; ?>">
										<i class="fas fa-clock"></i>
										<strong><?php echo $timeRange ?></strong>
									</div>

									<div class="card-body pt-3 pb-1">
										<ul class="list-inline">
											<?php if (!empty($event['subject'])) { ?>
												<li class="list-inline-item pr-3 font-weight-bold"><?php echo $event['subject']; ?></li>
											<?php } ?>
											<?php if (!empty($event['room'])) { ?>
												<li class="list-inline-item pr-3"><?php echo $event['room']; ?></li>
											<?php } ?>

											<?php if (!empty($event['prof'])) { ?>
												<li class="list-inline-item text-secondary"><?php echo $event['prof']; ?></li>
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
							<i class="fas fa-folder"></i> <?php echo $desiredClass; ?>
						</a>
						<div class="dropdown-menu" aria-labelledby="classLink">
							<?php foreach ($allowedClasses as $class) { ?>
								<a class="dropdown-item<?php if ($desiredClass == $class) echo " active"; ?>" href="?class=<?php echo $class; ?>&amp;date=<?php echo $desiredDate; ?>"><i class="fas fa-folder-open"></i> <?php echo $class; ?></a>
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
