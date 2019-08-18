<?php
/* Display Schedule */

function printClassDropdown($allowedClasses, $desiredClass, $desiredDate, $tokenEmbed, $showIDs = false) {
	$i = 1;
	foreach ($allowedClasses as $class) {
		$keyCode = ($showIDs === true && $i <= 9) ? $i + 48 : null;
		
		$classSwitcherClasses = "dropdown-item";
		if ($desiredClass == $class) {
			$classSwitcherClasses .= ' active font-weight-bold text-body bg-transparent';
		}
		?>
		<a class="<?php echo $classSwitcherClasses; ?>" href="?class=<?php echo $class; ?>&amp;date=<?php echo $desiredDate . $tokenEmbed; ?>"<?php echo !empty($keyCode) ? ' id="keyCode' . $keyCode . '"' : ''; ?>>
			<i class="fas fa-folder-open"></i>
			<?php echo $class;

			if(!empty($keyCode)) { ?>
				<small class="d-none d-lg-inline"><code class="text-secondary">(<?php echo $i; ?>)</code></small>
			<?php } ?>
		</a>
		<?php
		++$i;
	}
}

function printExtraEventDropdown($extraSubjects, $chosenExtraSubjects, $desiredDate, $tokenEmbed) {
	foreach ($extraSubjects as $extraSubject) {
		$icon = "fas fa-square";
		$link = strtolower($extraSubject);

		if (!empty($chosenExtraSubjects) && strlen($chosenExtraSubjects[0]) !== 0) {
			if (in_array($extraSubject, $chosenExtraSubjects)) {
				$icon = "fas fa-check-square";
				$link = printArray(getArrayWithout($chosenExtraSubjects, $extraSubject), true);
			} else {
				$link = printArray(getArrayWith($chosenExtraSubjects, $extraSubject), true);
			}
		}
		
		echo '<a class="dropdown-item" href="?extraSubjects=' . $link . '&amp;date=' . $desiredDate . $tokenEmbed . '"><i class="' . $icon . '"></i> ' . $extraSubject . '</a>';
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

$highlightEvents = isFalse($weekBump) ? true : false;

$highlightClasses = 'bg-dark text-light';

if(!isset($extraEventsText)) {
	$extraEventsText = "Extra Events";
}
?>
<!DOCTYPE html>
<html class="h-100" lang="en">
	<head data-desireddate="<?php echo $desiredDate; ?>" data-today="<?php echo $today; ?>" data-nextday="<?php echo $nextDay; ?>" data-prevday="<?php echo $prevDay; ?>" data-nextweek="<?php echo $nextWeek; ?>" data-prevweek="<?php echo $prevWeek; ?>" data-weekoverview="<?php echo $weekOverview === true ? "true" : "false"; ?>" data-highlightevents="<?php echo $highlightEvents ? 'true' : 'false'; ?>" data-highlightclasses="<?php echo $highlightClasses; ?>" data-enabletodaylink="<?php echo printBoolean($enableTodayLink);?>">
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>Calendar for <?php echo $displayedDateFull; ?></title>

		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha256-YLGeXaapI0/5IgZopewRJcFXomhRMlYYjugPLSyNjTY=" crossorigin="anonymous">
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0-12/css/all.min.css" integrity="sha256-cC4ByuxbguozEVx8jcKy94MFiGvxN9GwjCqZ8f3+yBk=" crossorigin="anonymous">

		<style>
		.dropdown-toggle {
			outline: none;
		}
		.active {
			pointer-events: none;
		}
		</style>
	</head>
	<body>
		<div class="container-fluid">
			<header>
				<nav class="navbar navbar-expand navbar-light bg-light mt-3 mb-4">
					<div class="navbar-header d-none d-sm-block mr-3">
						<a class="navbar-brand<?php echo (!$enableTodayLink) ? ' active' : ''; ?>" href="."><i class="fas fa-clock"></i> <span class="currentTime"><?php echo $currentTime; ?></span></a>
					</div>

					<ul class="navbar-nav m-auto ml-sm-0">
						
						<?php if ($prevWeek !== "none") { ?>
							<li class="nav-item mr-4">
								<a class="nav-link" id="prevWeek" href="?date=<?php echo $prevWeek; ?>"><i class="fas fa-step-backward"></i> <span class="d-none d-lg-inline">Previous Week <small><code class="text-secondary">(S)</code></small></span></a>
							</li>
						<?php } ?>

						<?php if ($prevDay !== "none") { ?>
							<li class="nav-item mr-4">
								<a class="nav-link" id="prevDay" href="?date=<?php echo $prevDay; ?>"><i class="fas fa-backward"></i> <span class="d-none d-lg-inline">Previous Day <small><code class="text-secondary">(A)</code></small></span></a>
							</li>
						<?php } ?>
						
						<li class="nav-item mr-4<?php echo (!$enableTodayLink) ? ' active' : ''; ?>">
							<a class="nav-link" id="today" href="."><i class="fas fa-play"></i> <span class="d-none d-lg-inline">Today <small><code class="text-secondary">(Enter)</code></small></span></a>
						</li>
						
						<?php if ($nextDay !== "none") { ?>
						<li class="nav-item mr-4">
							<a class="nav-link" id="nextDay" href="?date=<?php echo $nextDay; ?>"><i class="fas fa-forward"></i> <span class="d-none d-lg-inline">Next Day <small><code class="text-secondary">(D)</code></small></span></a>
						</li>
						<?php } ?>

						<?php if ($nextWeek !== "none") { ?>
						<li class="nav-item mr-4">
							<a class="nav-link" id="nextWeek" href="?date=<?php echo $nextWeek; ?>"><i class="fas fa-step-forward"></i> <span class="d-none d-lg-inline">Next Week <small><code class="text-secondary">(W)</code></small></span></a>
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
							<a class="nav-link" id="overviewType" href="?<?php echo $desiredDateMidWeek !== $today ? 'date=' . $desiredDateMidWeek . '&' : ''; ?>overview=<?php echo $overviewType . $tokenEmbed; ?>"><i class="<?php echo $icon; ?>"></i> <span class="d-none d-lg-inline"><?php echo $text;?> <small><code class="text-secondary">(T)</code></small></span></a>
						</li>
						
						<?php if(!empty($allowedClasses) && !empty($desiredClass)) { ?>
							<li class="nav-item mr-4 d-none d-sm-inline-block dropdown">
								<a class="nav-link dropdown-toggle" href="#" id="classNavButton" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
									<i class="fas fa-folder"></i> <span class="d-none d-lg-inline"><?php echo $desiredClass; ?> <small><code class="text-secondary">(C)</code></small></span>
								</a>
								<div class="dropdown-menu" id="classNavMenu" aria-labelledby="classNavButton">
									<?php printClassDropdown($allowedClasses, $desiredClass, $desiredDate, $tokenEmbed, true); ?>
								</div>
							</li>
						<?php } ?>
						
						<?php if(!empty($extraSubjects)) { ?>
							<li class="nav-item mr-4 d-none d-sm-inline-block dropdown">
								<a class="nav-link dropdown-toggle" href="#" id="extraEventsButton" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
									<i class="fas fa-folder"></i> <span class="d-none d-lg-inline"><?php echo $extraEventsText; ?> <small><code class="text-secondary">(X)</code></small></span>
								</a>
								<div class="dropdown-menu" id="extraEventsMenu" aria-labelledby="extraEventsButton">
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
						<div class="col-xl-6">
							<div class="alert alert-danger alert-dismissible fade show" role="alert">
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
				
				<div class="row">
					<div class="col-xl-<?php echo ($weekOverview === true) ? '2' : '6'; ?>">
							<?php
							if(!empty($schedule[0])) {
								$firstEventWeekDay = $schedule[0]["weekDay"];
								$firstEventDate = $schedule[0]["date"];
							} else {
								$firstEventWeekDay = $weekDay;
								$firstEventDate = $displayedDate;
							}
							?>
							<div<?php echo !isToday($firstEventDate, $today) ? ' class="text-secondary"' : ''; ?>>
								<h1 class="h4 pb-1 d-inline">
									<span class="mr-1"><i class="fas fa-calendar-alt"></i></span>
									<span class="mr-1"><?php echo $firstEventWeekDay; ?></span>
									<span class="mr-1"><?php echo $firstEventDate; ?></span>
								</h1>
								<span class="h4 float-right d-sm-none">
									<i class="fas fa-clock"></i> <span class="currentTime"><?php echo $currentTime; ?></span>
								</span>
							</div>

							<?php if (empty($schedule)) { ?>
								<div class="alert alert-secondary mt-3" role="alert">
									No entries have been found for that day.
								</div>
								<?php
							} else {
									foreach ($schedule as $key => $event) {
										
										if(isset($schedule[$key + 1])) {
											$thisEnd = $event["end"];
											$nextEvent = $schedule[$key + 1];
											$nextStart = $nextEvent["start"];
										} else {
											$nextEvent = null; //prevent $nextEvent from previous loop persiting
										}
										
										if(isNewDate($schedule, $key, $event)) { ?>
									</div>
									<div class="col-xl-2 mt-4 mt-xl-0">
										<span class="<?php echo !isToday($event['date'], $today) ? 'text-secondary ' : ''; ?>h4 pb-1">
											<span class="mr-1"><i class="fas fa-calendar-alt"></i></span>
											<span class="mr-1"><?php echo $event["weekDay"]; ?></span>
											<span class="mr-1"><?php echo $event["date"]; ?></span>
										</span>
									<?php }
									$timeRange = $event['start'] . " - " . $event['end'];
									$headerClasses = "";
									if(isTrue($highlightEvents) && onGoingEvent($event, $currentTime, $today)) {
										$headerClasses .= ' ' . $highlightClasses;
									} else if(isTrue($event['extra'])) {
										$headerClasses .= ' bg-info text-light';
									}
									if((isToday($event['date'], $today))) {
										$headerClasses .= ' today';
									}
									?>
									<div class="card my-3">
										<div class="card-header<?php echo $headerClasses; ?>" data-start="<?php echo $event['start'];?>" data-end="<?php echo $event['end'];?>" data-type="event">
											<i class="fas fa-clock"></i>
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
													<li class="list-inline-item pr-3 text-secondary"><?php echo $event['prof']; ?></li>
												<?php }
												if (!empty($event['info'])) { ?>
													<li class="list-inline-item font-italic"><?php echo $event['info']; ?></li>
												<?php } ?>
											</ul>
										</div>
									</div>
									<?php
									if(isset($nextEvent) && isTrue($highlightEvents) && isToday($nextEvent['date'], $today)) {
										$breakStart = formatTime($thisEnd, "+1 minute");
										$breakEnd = formatTime($nextStart, "-1 minute");
										?>
										<div class="card mt-3<?php echo !isBreak($currentTime, $thisEnd, $nextStart) ? ' d-none' : '' ?> today" data-start="<?php echo $breakStart;?>" data-end="<?php echo $breakEnd;?>" data-type="break">
											<div class="card-header <?php echo $highlightClasses; ?>">
												<i class="fas fa-pause"></i> <strong class="text-center">Break</strong>
											</div>
										</div>
										<?php
									}
								}
							} ?>

						</div>
					</div>
				<?php if (isset($weekBump) && $weekBump === true) { ?>
					<p class="text-center text-sm-left mt-4">
						<a class="btn btn-outline-secondary" data-toggle="collapse" href="#weekendNotice" id="infoBtn" role="button" aria-expanded="false" aria-controls="weekendNotice">
							Info
						</a>
					</p>
					<div class="collapse" id="weekendNotice">
						<div class="card card-body pb-1">
							<p>Weekends have been excluded from the schedule. You are now viewing the next week.</p>
						</div>
					</div>
				<?php } ?>
			</main>

			<footer class="text-center my-4">
				<?php /* Class Dropdown */ ?>
				<?php if(!empty($allowedClasses) && !empty($desiredClass)) { ?>
					<div class="d-block d-sm-none dropup d-inline">
						<a class="btn btn-white shadow-none text-secondary dropdown-toggle" href="#" role="button" id="classFooterButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
							<i class="fas fa-folder"></i> <?php echo $desiredClass; ?>
						</a>
						<div class="dropdown-menu" id="classFooterMenu" aria-labelledby="classFooterButton">
							<?php printClassDropdown($allowedClasses, $desiredClass, $desiredDate, $tokenEmbed); ?>
						</div>
					</div>
				<?php } ?>
				
				<?php if(!empty($extraSubjects)) { ?>
					<div class="d-block d-sm-none dropup d-inline">
						<a class="btn btn-white shadow-none text-secondary dropdown-toggle" href="#" role="button" id="extraEventsButtonFooter" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
							<i class="fas fa-folder"></i> <?php echo $extraEventsText; ?>
						</a>
						<div class="dropdown-menu" id="extraEventsMenuFooter" aria-labelledby="extraEventsButtonFooter">
							<?php printExtraEventDropdown($extraSubjects, $chosenExtraSubjects, $desiredDate, $tokenEmbed); ?>
						</div>
					</div>
				<?php } ?>
				
				<div class="d-block d-sm-none mt-2">
					<span class="text-muted" <?php if($weekOverview === false) { ?>data-toggle="tooltip" data-placement="bottom" title="One-finger swipes change the day. Two-finger swipes change the week." <?php } ?>>
						<small>Navigate by swiping left and right. <?php if($weekOverview === false) { ?><i class="fas fa-info-circle"></i><?php } ?></small>
					</span>
				</div>
			</footer>

		</div>

		<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha256-ZvOgfh+ptkpoa2Y4HkRY28ir89u/+VRyDE7sB7hEEcI=" crossorigin="anonymous"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha256-CjSoeELFOcH0/uxWu6mC/Vlrc1AARqbm/jiiImDGV3s=" crossorigin="anonymous"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.touchswipe/1.6.19/jquery.touchSwipe.min.js" integrity="sha256-ns1OeEP3SedE9Theqmu444I44sikbp1O+bF/6BNUUy0=" crossorigin="anonymous"></script>
		<script src="js/main.min.js"></script>
	</body>
</html>
