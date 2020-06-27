<?php
/* Display Schedule */

function printClassDropdown($allowedClasses, $desiredClass, $desiredDate, $tokenEmbed, $enableIDs = true) {
	global $themeColors;
	
	$activeDropdownColor = lookup("activeDropdown", $themeColors);
	$dropdownItemColor = lookup("dropdown-item", $themeColors);
	$codeHighlightColors = lookup("text-secondary", $themeColors);
	
	$key = 1;
	foreach ($allowedClasses as $class) {
		$enableShortcut = $enableIDs === true && $key <= 9;
		
		$classSwitcherClasses = $dropdownItemColor;
		$icon = "fas fa-chalkboard";
		if ($desiredClass == $class) {
			$classSwitcherClasses .= ' active font-weight-bold bg-transparent';
			$classSwitcherClasses .= " " . $activeDropdownColor; //light theme only
			$icon = "fas fa-chalkboard-teacher";
		}
		?>
		<a class="<?php echo $classSwitcherClasses; ?>" href="?class=<?php echo $class; ?>&amp;date=<?php echo $desiredDate . $tokenEmbed; ?>"<?php if($enableShortcut === true) { ?> id="key<?php echo $key; ?>"<?php } ?>>
			<i class="<?php echo $icon; ?>"></i>
			<?php echo $class;

			if($key <= 9) { ?>
				<small class="d-none d-lg-inline"><code class="<?php echo $codeHighlightColors; ?> d-none d-xl-inline">(<?php echo $key; ?>)</code></small>
			<?php } ?>
		</a>
		<?php
		++$key;
	}
}

function printExtraEventDropdown($extraSubjects, $chosenExtraSubjects, $desiredDate, $tokenEmbed) {
	global $themeColors;
	
	$dropdownItemColor = lookup("dropdown-item", $themeColors);
	foreach ($extraSubjects as $extraSubject) {
		$classes = $dropdownItemColor;
		$icon = "fas fa-square";
		$link = strtolower($extraSubject);

		if (!empty($chosenExtraSubjects) && strlen($chosenExtraSubjects[0]) !== 0) {
			if (inArray($extraSubject, $chosenExtraSubjects)) {
				$classes .= ' font-weight-bold';
				$icon = "fas fa-check-square";
				$link = printArray(getArrayWithout($chosenExtraSubjects, $extraSubject), true);
			} else {
				$link = printArray(getArrayWith($chosenExtraSubjects, $extraSubject), true);
			}
		}
		$encodedLink = urlencode($link);
		
		?>
		<a class="<?php echo $classes; ?>" href="?extraSubjects=<?php echo $encodedLink; ?>&amp;date=<?php echo $desiredDate . $tokenEmbed; ?>"><i class="<?php echo $icon; ?>"></i> <?php echo $extraSubject; ?></a>
		<?php 
	}
}


function prepareMsg($sessionName, $msg) {
	if (isset($_SESSION[$sessionName])) {
		if ($_SESSION[$sessionName] === false) {
			$_SESSION["msg"] = $msg;
		}
		unset($_SESSION[$sessionName]);
	}
}

prepareMsg('validToken', "<strong>The setting could not be changed.</strong><br>This link is invalid. Please try again.");
prepareMsg('validDate', "<strong>The date could not be changed.</strong><br>The date must be in the format <strong>YYYY-MM-DD</strong> and between <strong>$minDate</strong> and <strong>$maxDate</strong>.");

function isBreak($currentTime, $thisEnd, $nextStart) {
	return $thisEnd !== $nextStart && isBelowOrAbove($currentTime, $thisEnd, $nextStart);
}

function isNewDate($schedule, $key, $event) {
	return isset($schedule[$key - 1]["date"]) && $schedule[$key - 1]["date"] !== $event["date"];
}

function enableTodayLink($today, $desiredDate, $desiredDateTo) {
	return !isBetween($today, $desiredDate, $desiredDateTo);
}

$enableTodayLink = enableTodayLink($today, $desiredDate, $desiredDateTo);

$extraClasses = 'bg-info text-light';

$highlightEvents = !$weekBump;
$highlightClasses = lookup('highlightClasses', $themeColors);

if(!isset($extraEventsText)) {
	$extraEventsText = "Extra Events"; //TODO Starting with PHP 7.4: replace if statement with $var ??= "default"
}
if(!isset($extraEventsIcon)) {
	$extraEventsIcon = "fas fa-folder";
}
$hasManifest = isset($manifest) && !empty($manifest);
?>
<!DOCTYPE html>
<html class="h-100" lang="en">
	<head data-desireddate="<?php echo $desiredDate; ?>" data-today="<?php echo $today; ?>" data-nextday="<?php echo $nextDay; ?>" data-prevday="<?php echo $prevDay; ?>" data-nextweek="<?php echo $nextWeek; ?>" data-prevweek="<?php echo $prevWeek; ?>" data-weekoverview="<?php echo printBoolean($weekOverview); ?>" data-highlightevents="<?php echo printBoolean($highlightEvents); ?>" data-highlightclasses="<?php echo $highlightClasses; ?>" data-extraclasses="<?php echo $extraClasses; ?>" data-enabletodaylink="<?php echo printBoolean($enableTodayLink);?>" data-hasmanifest="<?php echo printBoolean($hasManifest); ?>">
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>Schedule for <?php echo $displayedDateFull; ?></title>

		<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.0/dist/css/bootstrap.min.css" integrity="sha256-aAr2Zpq8MZ+YA/D6JtRD3xtrwpEz2IqOS+pWD/7XKIw=" crossorigin="anonymous">
		<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.13.0/css/all.min.css" integrity="sha256-h20CPZ0QyXlBuAw7A+KluUYx/3pK+c7lYEpqLTlxjYQ=" crossorigin="anonymous">
		<?php if($hasManifest == true) { ?>
			<link rel="manifest" href="site.webmanifest.php" crossorigin="use-credentials">	
		<?php } ?>
		<?php if(!empty($touchIconPath)) { ?>
			<link rel="apple-touch-icon" href="<?php echo $touchIconPath; ?>">	
		<?php } ?>
		<?php if (isset($manifest["icons"])) {
			foreach ($manifest["icons"] as $icon) {
				?>
				<link rel="icon" type="<?php echo $icon["type"]; ?>" sizes="<?php echo $icon["sizes"]; ?>" href="<?php echo $icon["src"]; ?>">
			<?php }
		} ?>

		<style>
		.dropdown-toggle {
			outline: none;
		}
		.active {
			pointer-events: none;
		}
		</style>
	</head>
	<body class="mb-4 <?php echo lookup("body", $themeColors); ?>">
		<div class="container-fluid">
			<header>
				<nav class="<?php echo lookup("navbar", $themeColors); ?> navbar-expand mt-3 mb-4">
					<div class="navbar-header d-none d-sm-block mr-3">
						<a class="navbar-brand<?php echo (!$enableTodayLink) ? ' active' : ''; ?>" href="."><i class="fas fa-clock"></i> <span class="currentTime"><?php echo $currentTime; ?></span></a>
					</div>

					<ul class="navbar-nav m-auto ml-sm-0">
						
						<?php if ($prevWeek !== "none") { ?>
							<li class="nav-item mr-4">
								<a class="nav-link" id="prevWeek" href="?date=<?php echo $prevWeek; ?>"><i class="fas fa-angle-double-left"></i> <span class="d-none d-lg-inline">Previous Week <small><code class="<?php echo lookup("text-secondary", $themeColors); ?> d-none d-xl-inline">(<?php echo ($weekOverview === false) ? "S" : "A"; ?>)</code></small></span></a>
							</li>
						<?php } ?>

						<?php if ($prevDay !== "none") { ?>
							<li class="nav-item mr-4">
								<a class="nav-link" id="prevDay" href="?date=<?php echo $prevDay; ?>"><i class="fas fa-angle-left"></i> <span class="d-none d-lg-inline">Previous Day <small><code class="<?php echo lookup("text-secondary", $themeColors); ?> d-none d-xl-inline">(A)</code></small></span></a>
							</li>
						<?php } ?>
						
						<li class="nav-item mr-4<?php echo (!$enableTodayLink) ? ' active' : ''; ?>">
							<a class="nav-link" id="today" href="."><i class="fas fa-home"></i> <span class="d-none d-lg-inline">Today <small><code class="<?php echo lookup("text-secondary", $themeColors); ?> d-none d-xl-inline">(Enter)</code></small></span></a>
						</li>
						
						<?php if ($nextDay !== "none") { ?>
						<li class="nav-item mr-4">
							<a class="nav-link" id="nextDay" href="?date=<?php echo $nextDay; ?>"><i class="fas fa-angle-right"></i> <span class="d-none d-lg-inline">Next Day <small><code class="<?php echo lookup("text-secondary", $themeColors); ?> d-none d-xl-inline">(D)</code></small></span></a>
						</li>
						<?php } ?>

						<?php if ($nextWeek !== "none") { ?>
						<li class="nav-item mr-4">
							<a class="nav-link" id="nextWeek" href="?date=<?php echo $nextWeek; ?>"><i class="fas fa-angle-double-right"></i> <span class="d-none d-lg-inline">Next Week <small><code class="<?php echo lookup("text-secondary", $themeColors); ?> d-none d-xl-inline">(<?php echo ($weekOverview === false) ? "W" : "D"; ?>)</code></small></span></a>
						</li>
						<?php } ?>

						<?php if ($weekOverview === true) {
							$overviewType = "day";
							$icon = "fas fa-calendar-day";
							$text = "Day";
						} else {
							$overviewType = "week";
							$icon = "fas fa-calendar-week";
							$text = "Week";
						}  ?>
						<li class="nav-item mr-4">
							<a class="nav-link" id="overviewType" href="?<?php echo $desiredDateMidWeek !== $today ? 'date=' . $desiredDateMidWeek . '&' : ''; ?>overview=<?php echo $overviewType . $tokenEmbed; ?>"><i class="<?php echo $icon; ?>"></i> <span class="d-none d-lg-inline"><?php echo $text;?> <small><code class="<?php echo lookup("text-secondary", $themeColors); ?> d-none d-xl-inline">(T)</code></small></span></a>
						</li>
						
						<?php if(!empty($allowedClasses) && !empty($desiredClass) && $weekOverview == true) { ?>
							<li class="nav-item mr-3 d-none d-sm-inline-block <?php echo lookup("dropdown", $themeColors); ?>">
								<a class="nav-link dropdown-toggle" href="#" id="classNavButton" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
									<i class="fas fa-chalkboard-teacher"></i> <span class="d-none d-lg-inline"><?php echo $desiredClass; ?> <small><code class="<?php echo lookup("text-secondary", $themeColors); ?> d-none d-xl-inline">(C)</code></small></span>
								</a>
								<div class="<?php echo lookup("dropdown-menu", $themeColors); ?>" id="classNavMenu" aria-labelledby="classNavButton">
									<?php printClassDropdown($allowedClasses, $desiredClass, $desiredDate, $tokenEmbed, false); ?>
								</div>
							</li>
						<?php } ?>
						
						<?php if(!empty($extraSubjects) && $weekOverview == true) { ?>
							<li class="nav-item mr-3 d-none d-sm-inline-block dropdown">
								<a class="nav-link dropdown-toggle" href="#" id="extraEventsButton" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
									<i class="<?php echo $extraEventsIcon; ?>"></i> <span class="d-none d-lg-inline"><?php echo $extraEventsText; ?> <small><code class="<?php echo lookup("text-secondary", $themeColors); ?> d-none d-xl-inline">(X)</code></small></span>
								</a>
								<div class="<?php echo lookup("dropdown-menu", $themeColors); ?>" id="extraEventsMenu" aria-labelledby="extraEventsButton">
									<?php	printExtraEventDropdown($extraSubjects, $chosenExtraSubjects, $desiredDate, $tokenEmbed); ?>
								</div>
							</li>
						<?php } ?>
					</ul>
				</nav>
			</header>

			<main>
				<?php if (!empty($_SESSION['msg'])) { ?>
					<div class="row">
						<div class="col-xl-4">
							<div class="alert alert-danger alert-dismissible fade show" role="alert">
								<i class="fas fa-exclamation-circle"></i>
								<?php echo $_SESSION["msg"]; ?>
								<button type="button" class="close" data-dismiss="alert" aria-label="Close">
									<span aria-hidden="true">&times;</span>
								</button>
							</div>
						</div>
					</div>
				<?php
				}
				unset($_SESSION['msg']);
				?>
				
				<div class="row row-cols-1 row-cols-xl-6 row-cols-lg-5">
					<div class="col">
							<?php
							if(!empty($schedule[0])) {
								$firstEventWeekDay = $schedule[0]["weekDay"];
								$firstEventDate = $schedule[0]["date"];
							} else {
								$firstEventWeekDay = $weekDay;
								$firstEventDate = $displayedDate;
							}
							?>
							<div<?php echo !isToday($firstEventDate, $today) ? ' class="' . lookup("text-secondary", $themeColors) . '"' : ''; ?>>
								<span class="h4 float-right d-sm-none">
									<i class="fas fa-clock"></i> <span class="currentTime"><?php echo $currentTime; ?></span>
								</span>
								<h1 class="h4 pb-1 d-inline">
									<i class="fas fa-calendar-alt mr-1"></i>
									<?php if(empty($schedule) && $weekOverview == true) { ?>Week of <?php } ?>
									<span class="mr-1"><?php echo $firstEventWeekDay . " " . $firstEventDate; ?></span>
								</h1>
							</div>
							
							<?php if(empty($calendar)) { ?>
								<div class="alert alert-warning mt-3" role="alert">
									<i class="fas fa-exclamation-circle"></i> No events exist for this class yet. Please check back later.
								</div>
							<?php } else if (empty($schedule)) { ?>
								<div class="alert alert-info mt-3" role="alert">
									<i class="fas fa-info-circle"></i> No events <?php echo $weekOverview === true ? "in that week" : "on that day"; ?>.
								</div>
								<?php if(!empty($nextEventDate)) {?>
									<div class="text-center">
										<a class="btn btn-success text-light" href="?date=<?php echo $nextEventDate; ?>"><i class="fas fa-angle-double-right"></i> Go to next event on <?php echo formatReadableDate($nextEventDate); ?></a>
									</div>
								<?php }
								
							} else {
									foreach ($schedule as $key => $event) {
										
										if(isset($schedule[$key + 1])) {
											$thisEnd = $event["end"];
											$nextEvent = $schedule[$key + 1];
											$nextStart = $nextEvent["start"];
										} else {
											$nextEvent = null; //prevent $nextEvent from previous loop persiting
										}
										
										
										if(isNewDate($schedule, $key, $event)) {
											$prevEventDate = DateTime::createFromFormat('d.m.y', $schedule[$key - 1]["date"]);
											$nextEventDate = DateTime::createFromFormat('d.m.y', $event["date"]);
											
												?>
										</div>
										<div class="col mt-4 mt-lg-0">
											<span class="<?php echo ($nextEventDate->format("Y-m-d") == $today) ? '' : lookup("text-secondary", $themeColors) . ' '; ?>h4 pb-1">
												<span class="mr-1"><i class="fas fa-calendar-alt"></i></span>
												<span class="mr-1"><?php echo $nextEventDate->format("D"); ?></span>
												<span class="mr-1"><?php echo $nextEventDate->format("d.m.y"); ?></span>
											</span>
									
									<?php }
									
									if($event["type"] == "empty") { ?>
											<div class="alert alert-info mt-3" role="alert">
													<i class="fas fa-info-circle"></i> No events.
											</div>
										<?php
										continue;
									}
									
									$timeRange = $event['start'] . " - " . $event['end'];
									
									$headerClasses = 'card-header';
									if($highlightEvents == true && onGoingEvent($event, $currentTime, $today)) {
										$headerClasses .= ' ' . $highlightClasses;
									} else if($event['type'] == 'extraEvent') {
										$headerClasses .= ' ' . $extraClasses;
									}
									if(isToday($event['date'], $today) && $highlightEvents == true) {
										$headerClasses .= ' today';
									}
									
									$clockIcon = "fas fa-clock";
									if($event['type'] == 'extraEvent' && !empty($extraEventIcon)) {
										$clockIcon = $extraEventIcon;
									}
									?>
									<div class="<?php echo lookup("card", $themeColors); ?> my-3">
										<div class="<?php echo $headerClasses; ?>"
												 data-start="<?php echo $event['start'];?>" 
												 data-end="<?php echo $event['end'];?>"
												 data-type="<?php echo $event['type']; ?>"
												 data-enddatetime="<?php echo $event['endDateTime']; ?>">
											<i class="<?php echo $clockIcon; ?>"></i>
											<strong><?php echo $timeRange ?></strong>
										</div>

										<div class="card-body pt-3 pb-1">
											<ul class="list-inline">
												<?php if (!empty($event['subject'])) { ?>
													<li class="list-inline-item pr-3 font-weight-bold"><?php echo $event['subject']; ?></li>
												<?php }
												if (!empty($event['room'])) { ?>
													<li class="list-inline-item pr-3"><?php echo $event['room']; ?></li>
												<?php }
												if (!empty($event['prof'])) { ?>
													<li class="list-inline-item pr-3 <?php echo lookup("text-secondary", $themeColors); ?>">
														<?php
														if(strlen($event['prof']) <= 50) {
															echo $event['prof'];
														} else { ?>
															<span data-toggle="tooltip" data-placement="bottom" title="<?php echo $event['prof']; ?>">
																<i class="fas fa-user-tie"></i>
															</span>
														<?php } ?>
													</li>
												<?php }
												if (!empty($event['info'])) { ?>
													<li class="list-inline-item font-italic"><?php echo $event['info']; ?></li>
												<?php } ?>
											</ul>
										</div>
										<?php if(isToday($event['date'], $today) && $highlightEvents == true) { ?>
											<div class="card-footer <?php echo lookup("text-muted", $themeColors); echo !onGoingEvent($event, $currentTime, $today) ? ' d-none' : ''; ?>">
												<i class="fas fa-business-time"></i> <span class="timeRemaining"></span>
											</div>
										<?php } ?>
									</div>
									<?php
									if(isset($nextEvent)
													&& $highlightEvents == true
													&& isToday($event['date'], $today)
													&& isToday($nextEvent['date'], $today)) {
										$breakStart = formatTime($thisEnd, "+1 minute");
										$breakEnd = formatTime($nextStart, "-1 minute");
										?>
										<div class="<?php echo lookup("card", $themeColors); ?> mt-3<?php echo !isBreak($currentTime, $thisEnd, $nextStart) ? ' d-none' : '' ?> today"
												 data-start="<?php echo $breakStart;?>"
												 data-end="<?php echo $breakEnd;?>"
												 data-enddatetime="<?php echo createJsTime($nextStart);?>"
												 data-type="break">
											<div class="card-header <?php echo $highlightClasses; ?>">
												<i class="fas fa-pause"></i> <strong>Break until <?php echo $nextStart; ?></strong>
											</div>
											<div class="card-footer <?php echo lookup("text-muted", $themeColors); ?>">
												<i class="fas fa-business-time"></i> <span class="timeRemaining"></span>
											</div>
										</div>
										<?php
									}
								}
							} ?>

						</div>
					</div>
			</main>

			<footer class="text-center my-4">
				<?php if (isset($weekBump) && $weekBump === true) { ?>
				<div class="d-block my-3">
					<span class="<?php echo lookup("text-muted", $themeColors); ?>" data-toggle="tooltip" data-placement="bottom" title="Weekends are not part of the schedule. You are now viewing the next week.">
						<small>Weekend skipped. <i class="fas fa-info-circle"></i></small>
					</span>
				</div>
				<?php } ?>
				
				<?php /* Class Dropdown */ ?>
				<?php if(!empty($allowedClasses) && !empty($desiredClass)) { ?>
					<div class="d-block <?php echo $weekOverview == true ? "d-sm-none " : "" ?>dropup d-inline">
						<a class="btn btn-white shadow-none <?php echo lookup("text-secondary", $themeColors); ?> dropdown-toggle" href="#" role="button" id="classFooterButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
							<i class="fas fa-chalkboard-teacher"></i> <?php echo $desiredClass; ?>
							<small><code class="<?php echo lookup("text-secondary", $themeColors); ?> d-none d-xl-inline">(C)</code></small>
						</a>
						<div class="<?php echo lookup("dropdown-menu", $themeColors); ?>" id="classFooterMenu" aria-labelledby="classFooterButton">
							<?php printClassDropdown($allowedClasses, $desiredClass, $desiredDate, $tokenEmbed); ?>
						</div>
					</div>
				<?php } ?>
				
				<?php /* Extra Subjects */ ?>
				<?php if(!empty($extraSubjects)) { ?>
					<div class="d-block <?php echo $weekOverview == true ? "d-sm-none " : "" ?>dropup d-inline">
						<a class="btn btn-white shadow-none <?php echo lookup("text-secondary", $themeColors); ?> dropdown-toggle" href="#" role="button" id="extraEventsFooterButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
							<i class="<?php echo $extraEventsIcon; ?>"></i> <?php echo $extraEventsText; ?>
							<small><code class="<?php echo lookup("text-secondary", $themeColors); ?> d-none d-xl-inline">(X)</code></small>
						</a>
						<div class="<?php echo lookup("dropdown-menu", $themeColors); ?>" id="extraEventsMenuFooter" aria-labelledby="extraEventsFooterButton">
							<?php printExtraEventDropdown($extraSubjects, $chosenExtraSubjects, $desiredDate, $tokenEmbed); ?>
						</div>
					</div>
				<?php } ?>
				
				<?php /* Theme Switcher */ ?>
				<div class="d-block">
					<a href="?theme=<?php echo $theme == "dark" ? "light" : "dark";?>&date=<?php echo $desiredDate . $tokenEmbed; ?>" class="btn btn-white <?php echo lookup("text-secondary", $themeColors); ?>" id="themeSwitcher" role="button"><i class="fas fa-<?php echo $theme == "dark" ? "check-square" : "square"; ?>"></i> Dark Theme <small><code class="<?php echo lookup("text-secondary", $themeColors); ?> d-none d-xl-inline">(E)</code></small>
</a>
				</div>
				
				<?php /* Swipe Hints */ ?>
				<div class="d-block d-sm-none mt-2">
					<span class="<?php echo lookup("text-muted", $themeColors); ?>" <?php if($weekOverview === false) { ?>data-toggle="tooltip" data-placement="bottom" title="One-finger swipes change the day. Two-finger swipes change the week." <?php } ?>>
						<small>Navigate by swiping left and right.<?php if($weekOverview === false) { ?> <i class="fas fa-info-circle"></i><?php } ?></small>
					</span>
				</div>
			</footer>

		</div>

		<script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
		<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha256-x3YZWtRjM8bJqf48dFAv/qmgL68SI4jqNWeSLMZaMGA=" crossorigin="anonymous"></script> <?php /* use UMD version of popper.js */ ?>
		<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.0/dist/js/bootstrap.min.js" integrity="sha256-OFRAJNoaD8L3Br5lglV7VyLRf0itmoBzWUoM+Sji4/8=" crossorigin="anonymous"></script>
		<script src="https://cdn.jsdelivr.net/npm/jquery-touchswipe@1.6.19/jquery.touchSwipe.min.js" integrity="sha256-ns1OeEP3SedE9Theqmu444I44sikbp1O+bF/6BNUUy0=" crossorigin="anonymous"></script>
		<script src="js/main.min.js"></script>
	</body>
</html>
