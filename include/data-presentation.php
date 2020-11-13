<?php
/* Display Schedule */

function printClassDropdown($allowedClasses, $desiredClass, $desiredDate, $tokenEmbed, $enableIDs = true) {
	global $themeColors;
	global $classPrefix;
	
	$activeDropdownColor = lookup("activeDropdown", $themeColors);
	$dropdownItemColor = lookup("dropdown-item", $themeColors);
	$codeHighlightColors = lookup("text-secondary", $themeColors);
	
	$key = 1;
	foreach ($allowedClasses as $class) {
		$enableShortcut = $enableIDs === true && $key <= 9;
		$shortClass = isset($classPrefix) ? removeFromString($classPrefix, $class) : $classPrefix;
		
		$classSwitcherClasses = $dropdownItemColor;
		$icon = "fas fa-chalkboard";
		if ($desiredClass == $shortClass) {
			$classSwitcherClasses .= ' active font-weight-bold bg-transparent';
			$classSwitcherClasses .= " $activeDropdownColor"; //light theme only
			$icon = "fas fa-chalkboard-teacher";
		}
		?>
		<a class="<?= $classSwitcherClasses; ?>" href="?class=<?= $class; ?>&amp;date=<?= $desiredDate . $tokenEmbed; ?>"<?php if($enableShortcut === true) { ?> id="classKey<?= $key; ?>"<?php } ?>>
			<i class="<?= $icon; ?>"></i>
			<?= $shortClass;

			if($enableShortcut) { ?>
				<small class="d-none d-lg-inline"><code class="<?= $codeHighlightColors; ?> d-none d-xl-inline">(<?= $key; ?>)</code></small>
			<?php } ?>
		</a>
		<?php
		++$key;
	}
}

function printExtraEventDropdown($extraSubjects, $chosenExtraSubjects, $desiredDate, $tokenEmbed, $enableIDs = true) {
	global $themeColors;
	
	$dropdownItemColor = lookup("dropdown-item", $themeColors);
	$key = 1;
	foreach ($extraSubjects as $extraSubject) {
		$enableShortcut = $enableIDs === true && $key <= 9;
		
		$classes = $dropdownItemColor;
		$icon = "fas fa-square";
		$link = strtolower($extraSubject);
		
		$codeHighlightColors = lookup("text-secondary", $themeColors);

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
		<a class="<?= $classes; ?>" href="?extraSubjects=<?= $encodedLink; ?>&amp;date=<?= $desiredDate . $tokenEmbed; ?>"<?php if($enableShortcut === true) { ?> id="eventsKey<?= $key; ?>"<?php } ?>><i class="<?= $icon; ?>"></i> <?= $extraSubject; ?>
		
		<?php 
		if($enableShortcut) { ?>
			<small class="d-none d-lg-inline"><code class="<?= $codeHighlightColors; ?> d-none d-xl-inline">(<?= $key; ?>)</code></small>
		<?php } ?>
		</a>
		<?php
		++$key;
	}
}

function printDateNavLi($id, $date, $icon, $text, $hotKey, $liClasses = null) {
	global $themeColors;
	?>
		
	<li class="nav-item mr-4<?php if (isset($liClasses)) echo ' ' . $liClasses; ?>">
		<a class="nav-link<?php if($date == "none") echo " disabled"; ?>" id="<?= $id; ?>" href="?date=<?= $date; ?>">
			<i class="<?= $icon; ?>"></i> <span class="d-none d-lg-inline"><?= $text;?> <small><code class="<?= lookup("text-secondary", $themeColors); ?> d-none d-xl-inline">(<?= $hotKey; ?>)</code></small></span>
		</a>
	</li>
<?php }

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
	<head data-nextday="<?= $nextDay; ?>"
				data-prevday="<?= $prevDay; ?>"
				data-nextweek="<?= $nextWeek; ?>"
				data-prevweek="<?= $prevWeek; ?>"
				
				data-weekoverview="<?= printBoolean($weekOverview); ?>"
				data-highlightevents="<?= printBoolean($highlightEvents); ?>"
				data-highlightclasses="<?= $highlightClasses; ?>"
				
				data-extraclasses="<?= $extraClasses; ?>"
				data-enabletodaylink="<?= printBoolean($enableTodayLink);?>"
				data-hasmanifest="<?= printBoolean($hasManifest); ?>"
				data-pickedtheme="<?= printBoolean($pickedTheme); ?>">
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="theme-color" content="<?= lookup("bg-hex", $themeColors); ?>">
		<title>Schedule for <?= $displayedDateFull; ?></title>

		<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/css/bootstrap.min.css" integrity="sha256-Ww++W3rXBfapN8SZitAvc9jw2Xb+Ixt0rvDsmWmQyTo=" crossorigin="anonymous">
		<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.14.0/css/all.min.css" integrity="sha256-FMvZuGapsJLjouA6k7Eo2lusoAX9i0ShlWFG6qt7SLc=" crossorigin="anonymous">
		<?php if($hasManifest == true) { ?>
			<link rel="manifest" href="site.webmanifest.php" crossorigin="use-credentials">	
		<?php } ?>
		<?php if(!empty($touchIconPath)) { ?>
			<link rel="apple-touch-icon" href="<?= $touchIconPath; ?>">	
		<?php } ?>
		<?php if (isset($manifest["icons"])) {
			foreach ($manifest["icons"] as $icon) {
				?>
				<link rel="icon" type="<?= $icon["type"]; ?>" sizes="<?= $icon["sizes"]; ?>" href="<?= $icon["src"]; ?>">
			<?php }
		} ?>

		<style>
		html {
			scroll-behavior: smooth;
		}
		.dropdown-toggle {
			outline: none;
		}
		.active {
			pointer-events: none;
		}
		</style>
	</head>
	<body class="mb-4 <?= lookup("body", $themeColors); ?>">
		<div class="container-fluid">
			<header>
				<nav class="<?= lookup("navbar", $themeColors); ?> navbar-expand mt-3">
					<div class="navbar-header d-none d-sm-block mr-3">
						<a class="navbar-brand<?php if (!$enableTodayLink) echo ' active'; ?>" href="."><i class="fas fa-clock"></i> <span class="currentTime"><?= $currentTime; ?></span></a>
					</div>

					<ul class="navbar-nav m-auto ml-sm-0">
						
						<?php
            $moveLeftKeys = 'A/<i class="fas fa-caret-square-left"></i>';
            $moveRightKeys = 'D/<i class="fas fa-caret-square-right"></i>';

            printDateNavLi("prevWeek", $prevWeek, "fas fa-angle-double-left", "Previous Week", $weekOverview === false ? "S" : $moveLeftKeys);
						if($weekOverview === false) printDateNavLi("prevDay", $prevDay, "fas fa-angle-left", "Previous Day", $moveLeftKeys);
						printDateNavLi("today", $today, "fas fa-home", "Today", "Enter", !$enableTodayLink ? 'active' : null);
            if($weekOverview === false) printDateNavLi("nextDay", $nextDay, "fas fa-angle-right", "Next Day", $moveRightKeys);
						printDateNavLi("nextWeek", $nextWeek, "fas fa-angle-double-right", "Next Week", $weekOverview === false ? "W" : $moveRightKeys);

						if ($weekOverview === true) {
							$overviewType = "day";
							$icon = "fas fa-calendar-day";
							$text = "Day";
						} else {
							$overviewType = "week";
							$icon = "fas fa-calendar-week";
							$text = "Week";
						}  ?>
						<li class="nav-item mr-4">
							<a class="nav-link" id="overviewType" href="?<?php if($desiredDateMidWeek !== $today) echo 'date=' . $desiredDateMidWeek . '&'; ?>overview=<?= $overviewType . $tokenEmbed; ?>"><i class="<?= $icon; ?>"></i> <span class="d-none d-lg-inline"><?= $text;?> <small><code class="<?= lookup("text-secondary", $themeColors); ?> d-none d-xl-inline">(T)</code></small></span></a>
						</li>
						
						<?php if(!empty($allowedClasses) && !empty($desiredClass) && $weekOverview == true) {
							$desiredClassShort = isset($classPrefix) ? removeFromString($classPrefix, $desiredClass) : $classPrefix;
							?>
							<li class="nav-item mr-3 d-none d-sm-inline-block <?= lookup("dropdown", $themeColors); ?>">
								<a class="nav-link dropdown-toggle" href="#" id="classNavButton" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
									<i class="fas fa-chalkboard-teacher"></i> <span class="d-none d-lg-inline"><?= $desiredClassShort; ?> <small><code class="<?= lookup("text-secondary", $themeColors); ?> d-none d-xl-inline">(C)</code></small></span>
								</a>
								<div class="<?= lookup("dropdown-menu", $themeColors); ?>" id="classNavMenu" aria-labelledby="classNavButton">
									<?php printClassDropdown($allowedClasses, $desiredClassShort, $desiredDate, $tokenEmbed, true); ?>
								</div>
							</li>
						<?php } ?>
						
						<?php if(!empty($extraSubjects) && $weekOverview == true) { ?>
							<li class="nav-item mr-3 d-none d-sm-inline-block dropdown">
								<a class="nav-link dropdown-toggle" href="#" id="extraEventsButton" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
									<i class="<?= $extraEventsIcon; ?>"></i> <span class="d-none d-lg-inline"><?= $extraEventsText; ?> <small><code class="<?= lookup("text-secondary", $themeColors); ?> d-none d-xl-inline">(X)</code></small></span>
								</a>
								<div class="<?= lookup("dropdown-menu", $themeColors); ?>" id="extraEventsMenu" aria-labelledby="extraEventsButton">
									<?php	printExtraEventDropdown($extraSubjects, $chosenExtraSubjects, $desiredDate, $tokenEmbed, true); ?>
								</div>
							</li>
						<?php } ?>
					</ul>
				</nav>
				<?php if(!empty($schedule) && isset($period)) { ?>
					<nav class="nav nav-pills nav-fill mt-3 d-lg-none">
					<?php
					foreach ($period as $dt) {
						$thisWkDay = $dt->format("D");
						$thisDay = $dt->format("Y-m-d");
						?>
						<a class="nav-link <?= lookup('text-dark', $themeColors); ?><?php if($thisDay == $today) echo " font-weight-bold" ?>" href="#<?= strtolower($thisWkDay); ?>"><?= $thisWkDay; ?></a>
					<?php } ?>
					</nav>
				<?php } ?>
			</header>

			<main class="mt-3">
				<?php if (!empty($_SESSION['msg'])) { ?>
					<div class="row">
						<div class="col-xl-4">
							<div class="alert alert-danger alert-dismissible fade show" role="alert">
								<i class="fas fa-exclamation-circle"></i>
								<?= $_SESSION["msg"]; ?>
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
							<div<?php if (!isToday($firstEventDate, $today)) echo ' class="' . lookup("text-secondary", $themeColors) . '"'; ?>>
								<span class="h4 float-right d-sm-none">
									<i class="fas fa-clock"></i> <span class="currentTime"><?= $currentTime; ?></span>
								</span>
								<h1 class="h4 pb-1 d-inline" id="<?= strtolower($weekDay); ?>">
									<i class="fas fa-calendar-alt mr-1"></i>
									<?php if(empty($schedule) && $weekOverview == true) { ?>Week of <?php } ?>
									<span class="mr-1"><?= $firstEventWeekDay . " " . $firstEventDate; ?></span>
								</h1>
							</div>
							
							<?php if(empty($calendar)) { ?>
								<div class="alert alert-warning mt-3" role="alert">
									<i class="fas fa-exclamation-circle"></i> No events exist for this class yet. Please check back later.
								</div>
							<?php } else if (empty($schedule)) { ?>
									<div class="alert alert-info mt-3" role="alert">
									<i class="fas fa-info-circle"></i> No events
										<?php 
										if (empty($nextEventDate)) {
											echo "the following weeks";
										} else {
											if ($weekOverview === true) {
												echo "in that week";
											} else {
												echo "on that day";
											}
										}
										?>.
									</div>
								<?php if(!empty($nextEventDate)) {?>
									<div class="text-center">
										<a class="btn btn-success text-light" href="?date=<?= $nextEventDate; ?>"><i class="fas fa-angle-double-right"></i> Go to next event on <?= formatReadableDate($nextEventDate); ?></a>
									</div>
								<?php } else { ?>
									<div class="text-center">
											<a class="btn btn-success text-light" href="."><i class="fas fa-angle-double-left"></i> Back to today</a>
									</div>
								<?php } ?>
								
							<?php } else {
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
										<span class="<?= ($nextEventDate->format("Y-m-d") == $today) ? '' : lookup("text-secondary", $themeColors) . ' '; ?>h4 pb-1" id="<?= strtolower($nextEventDate->format("D")); ?>">
											<span class="mr-1"><i class="fas fa-calendar-alt"></i></span>
											<span class="mr-1"><?= $nextEventDate->format("D"); ?></span>
											<span class="mr-1"><?= $nextEventDate->format("d.m.y"); ?></span>
										</span>

								<?php }

								if($event["type"] == "empty") { ?>
										<div class="alert alert-info mt-3" role="alert">
												<i class="fas fa-info-circle"></i> No events
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
								<div class="<?= lookup("card", $themeColors); ?> my-3">
									<div class="<?= $headerClasses; ?>"
											 data-start="<?= $event['start'];?>" 
											 data-end="<?= $event['end'];?>"
											 data-type="<?= $event['type']; ?>"
											 data-enddatetime="<?= $event['endDateTime']; ?>">
										<i class="<?= $clockIcon; ?>"></i>
										<strong><?= $timeRange ?></strong>
									</div>

									<div class="card-body pt-3 pb-1">
										<ul class="list-inline">
											<?php if (!empty($event['subject'])) { ?>
												<li class="list-inline-item pr-3 font-weight-bold"><?= $event['subject']; ?></li>
											<?php }
											if (!empty($event['room'])) { ?>
												<li class="list-inline-item pr-3"><?= $event['room']; ?></li>
											<?php }
											if (!empty($event['prof'])) { ?>
												<li class="list-inline-item pr-3 <?= lookup("text-secondary", $themeColors); ?>">
													<?php
													if(strlen($event['prof']) <= 50) {
														echo $event['prof'];
													} else { ?>
														<span data-toggle="tooltip" data-placement="bottom" title="<?= $event['prof']; ?>">
															<i class="fas fa-user-tie"></i>
														</span>
													<?php } ?>
												</li>
											<?php }
											if (!empty($event['info'])) { ?>
												<li class="list-inline-item font-italic"><?= $event['info']; ?></li>
											<?php } ?>
										</ul>
									</div>
									<?php if(isToday($event['date'], $today) && $highlightEvents == true) { ?>
										<div class="card-footer <?= lookup("text-muted", $themeColors); if (!onGoingEvent($event, $currentTime, $today)) echo ' d-none'; ?>">
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
									<div class="<?= lookup("card", $themeColors); ?> mt-3<?php if (!isBreak($currentTime, $thisEnd, $nextStart)) echo ' d-none'; ?> today"
											 data-start="<?= $breakStart;?>"
											 data-end="<?= $breakEnd;?>"
											 data-enddatetime="<?= createJsTime($nextStart);?>"
											 data-type="break">
										<div class="card-header <?= $highlightClasses; ?>">
											<i class="fas fa-pause"></i> <strong>Break until <?= $nextStart; ?></strong>
										</div>
										<div class="card-footer <?= lookup("text-muted", $themeColors); ?>">
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
					<span class="<?= lookup("text-muted", $themeColors); ?>" data-toggle="tooltip" data-placement="bottom" title="Weekends are not part of the schedule. You are now viewing the next week.">
						<small>Weekend skipped. <i class="fas fa-info-circle"></i></small>
					</span>
				</div>
				<?php } ?>
				
				<?php /* Class Dropdown */ ?>
				<?php if(!empty($allowedClasses) && !empty($desiredClass)) { ?>
					<div class="d-block <?php if ($weekOverview == true) echo "d-sm-none "; ?>dropup d-inline">
						<a class="btn btn-white shadow-none <?= lookup("text-secondary", $themeColors); ?> dropdown-toggle" href="#" role="button" id="classFooterButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
							<i class="fas fa-chalkboard-teacher"></i> <?= $desiredClass; ?>
							<small><code class="<?= lookup("text-secondary", $themeColors); ?> d-none d-xl-inline">(C)</code></small>
						</a>
						<div class="<?= lookup("dropdown-menu", $themeColors); ?>" id="classFooterMenu" aria-labelledby="classFooterButton">
							<?php printClassDropdown($allowedClasses, $desiredClass, $desiredDate, $tokenEmbed, false); ?>
						</div>
					</div>
				<?php } ?>
				
				<?php /* Extra Subjects */ ?>
				<?php if(!empty($extraSubjects)) { ?>
					<div class="d-block <?php if ($weekOverview == true) echo "d-sm-none "; ?>dropup d-inline">
						<a class="btn btn-white shadow-none <?= lookup("text-secondary", $themeColors); ?> dropdown-toggle" href="#" role="button" id="extraEventsFooterButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
							<i class="<?= $extraEventsIcon; ?>"></i> <?= $extraEventsText; ?>
							<small><code class="<?= lookup("text-secondary", $themeColors); ?> d-none d-xl-inline">(X)</code></small>
						</a>
						<div class="<?= lookup("dropdown-menu", $themeColors); ?>" id="extraEventsMenuFooter" aria-labelledby="extraEventsFooterButton">
							<?php printExtraEventDropdown($extraSubjects, $chosenExtraSubjects, $desiredDate, $tokenEmbed, false); ?>
						</div>
					</div>
				<?php } ?>
				
				<?php /* Theme Switcher */ ?>
				<div class="d-block">
					<a href="?theme=<?= $theme == "dark" ? "light" : "dark";?>&date=<?= $desiredDate . $tokenEmbed; ?>" class="btn btn-white <?= lookup("text-secondary", $themeColors); ?>" id="themeSwitcher" role="button"><i class="fas fa-<?= $theme == "dark" ? "check-square" : "square"; ?>"></i> Dark Theme <small><code class="<?= lookup("text-secondary", $themeColors); ?> d-none d-xl-inline">(E)</code></small>
</a>
				</div>
				
				<?php /* Swipe Hints */ ?>
				<div class="d-block d-sm-none mt-2">
					<span class="<?= lookup("text-muted", $themeColors); ?>" <?php if($weekOverview === false) { ?>data-toggle="tooltip" data-placement="bottom" title="One-finger swipes change the day. Two-finger swipes change the week." <?php } ?>>
						<small>Navigate by swiping left and right.<?php if($weekOverview === false) { ?> <i class="fas fa-info-circle"></i><?php } ?></small>
					</span>
				</div>
			</footer>

		</div>

		<script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
		<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js" integrity="sha256-/ijcOLwFf26xEYAjW75FizKVo5tnTYiQddPZoLUHHZ8=" crossorigin="anonymous"></script> <?php /* use UMD version of popper.js */ ?>
		<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.min.js" integrity="sha256-ecWZ3XYM7AwWIaGvSdmipJ2l1F4bN9RXW6zgpeAiZYI=" crossorigin="anonymous"></script>
		<script src="https://cdn.jsdelivr.net/npm/jquery-touchswipe@1.6.19/jquery.touchSwipe.min.js" integrity="sha256-ns1OeEP3SedE9Theqmu444I44sikbp1O+bF/6BNUUy0=" crossorigin="anonymous"></script>
		<!--<script src="js/main.min.js"></script>-->
		<script src="js/main.js"></script>
	</body>
</html>
