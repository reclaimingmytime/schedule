<?php
/* Display Schedule */

function printClassDropdown($allowedClasses, $desiredClass, $desiredDate, $tokenEmbed, $showIDs = false) {
	$i = 1;
	foreach ($allowedClasses as $class) {
		$keyCode = $showIDs === true && $i <= 9 ? $i + 48 : null;
		?>
		<a class="dropdown-item<?php if ($desiredClass == $class) echo ' active font-weight-bold text-body bg-transparent'; ?>" href="?class=<?php echo $class; ?>&amp;date=<?php echo $desiredDate . $tokenEmbed; ?>"<?php echo !empty($keyCode) ? ' id="keyCode' . $keyCode . '"' : ''; ?>>
			<i class="fas fa-folder-open"></i>
			<?php echo $class;

			if(!empty($keyCode)) { ?>
				<small class="text-muted d-none d-lg-inline">(<?php echo $i; ?>)</small>
			<?php } ?>
		</a>
		<?php
		++$i;
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

prepareMsg('validToken', "<strong>The class could not be changed.</strong><br>This link is invalid. Please try again.");
prepareMsg('validDate', "<strong>The date could not be changed.</strong><br>The date must be in the format <strong>YYYY-MM-DD</strong> and between <strong>$minDate</strong> and <strong>$maxDate</strong>.");

function isBreak($currentTime, $thisEnd, $nextStart) {
	return $nextEvent !== $nextStart && isBelowOrAbove($currentTime, $thisEnd, $nextStart);
}

$highlightEvents = equals($desiredDate, $today) && isFalse($weekBump) ? true : false;
$highlightClasses = 'bg-dark text-light';
?>
<!DOCTYPE html>
<html lang="en">
	<head data-desireddate="<?php echo $desiredDate; ?>" data-today="<?php echo $today; ?>" data-nextday="<?php echo $nextDay; ?>" data-prevday="<?php echo $prevDay; ?>" data-nextweek="<?php echo $nextWeek; ?>" data-prevweek="<?php echo $prevWeek; ?>" data-highlightevents="<?php echo $highlightEvents ? 'true' : 'false'; ?>" data-highlightclasses="<?php echo $highlightClasses; ?>">
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>Calendar for <?php echo $displayedDateFull; ?></title>

		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha256-YLGeXaapI0/5IgZopewRJcFXomhRMlYYjugPLSyNjTY=" crossorigin="anonymous">
		<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.8.2/css/all.css" integrity="sha384-oS3vJWv+0UjzBfQzYUhtDYW+Pj2yciDJxpsK1OYPAYjqT085Qq/1cq5FLXAZQ7Ay" crossorigin="anonymous">

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
					<div class="navbar-header d-none d-sm-block">
						<a class="navbar-brand<?php echo ($desiredDate == $today) ? ' active' : ''; ?>" href=".">Schedule</a>
					</div>

					<ul class="navbar-nav m-auto ml-sm-0">
						<li class="nav-item mr-4 ml-3<?php echo ($desiredDate == $today) ? ' active' : ''; ?>">
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

						<?php if ($prevDay !== "none") { ?>
							<li class="nav-item mr-4">
								<a class="nav-link" id="prevDay" href="?date=<?php echo $prevDay; ?>"><i class="fas fa-backward"></i> <span class="d-none d-lg-inline">Previous Day <small><code class="text-secondary">(A)</code></small></span></a>
							</li>
						<?php }
						if ($prevWeek !== "none") { ?>
							<li class="nav-item mr-4">
								<a class="nav-link" id="prevWeek" href="?date=<?php echo $prevWeek; ?>"><i class="fas fa-step-backward"></i> <span class="d-none d-lg-inline">Previous Week <small><code class="text-secondary">(S)</code></small></span></a>
							</li>
						<?php } ?>

						<?php if(!empty($allowedClasses) && !empty($desiredClass)) { ?>
						<li class="nav-item d-none d-sm-inline-block dropdown">
							<a class="nav-link dropdown-toggle" href="#" id="classNavButton" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
								<i class="fas fa-folder"></i> <span class="d-none d-lg-inline"><?php echo $desiredClass; ?> <small>(C)</small></span>
							</a>
							<div class="dropdown-menu" id="classNavMenu" aria-labelledby="classNavButton">
								<?php printClassDropdown($allowedClasses, $desiredClass, $desiredDate, $tokenEmbed, true); ?>
							</div>
						</li>
						<?php } ?>
					</ul>
				</nav>
			</header>

			<main>
				<div class="row">
					<div class="col-xl-6">
						<?php if (!empty($_SESSION['msg'])) { ?>
							<div class="alert alert-danger alert-dismissible fade show" role="alert">
								<?php echo $_SESSION["msg"]; ?>
								<button type="button" class="close" data-dismiss="alert" aria-label="Close">
									<span aria-hidden="true">&times;</span>
								</button>
							</div>
						<?php }
						unset($_SESSION['msg']);
						?>
						<h1 class="text-muted h4 pb-1">
							<span class="mr-2"><i class="fas fa-calendar-alt"></i></span>
							<span class="mr-2"><?php echo $weekDay; ?></span>
							<span class="mr-2"><?php echo $displayedDate; ?></span>
							<span class="mr-2 currentTime"><?php echo $currentTime; ?></span>
						</h1>

						<?php if (empty($schedule)) { ?>
							<div class="alert alert-secondary mt-3" role="alert">
								No entries have been found for that day.
							</div>
							<?php
						} else {
								foreach ($schedule as $key => $event) {
									$timeRange = $event['start'] . " - " . $event['end'];
									$headerClasses = isTrue($highlightEvents) && onGoingEvent($event, $currentTime) ? ' ' . $highlightClasses : '';
									?>
									<div class="card mt-3">
										<div class="card-header<?php echo $headerClasses; ?>" data-start="<?php echo $event['start'];?>" data-end="<?php echo $event['end'];?>">
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
													<li class="list-inline-item text-secondary pr-3"><?php echo $event['prof']; ?></li>
												<?php }
												if (!empty($event['info'])) { ?>
													<li class="list-inline-item text-secondary"><?php echo $event['info']; ?></li>
												<?php } ?>
											</ul>
										</div>
									</div>
									<?php
									if($highlightEvents && isset($schedule[$key + 1])) {
										$nextEvent = $schedule[$key + 1];
										$thisEnd = $event["end"];
										$nextStart = $nextEvent["start"];
										?>
										<div class="card mt-3<?php echo !isBreak($currentTime, $thisEnd, $nextStart) ? ' d-none' : '' ?>">
											<div class="card-body <?php echo $highlightClasses; ?>">
												<i class="fas fa-pause"></i> <strong class="text-center">Break</strong>
											</div>
										</div>
										<?php
									}
								}
							} ?>

						<?php if (isset($weekBump) && $weekBump === true) { ?>
							<p class="text-center text-sm-left mt-4">
								<a class="btn btn-outline-secondary" data-toggle="collapse" href="#weekendNotice" id="infoBtn" role="button" aria-expanded="false" aria-controls="weekendNotice">
									Info
								</a>
							</p>
							<div class="collapse" id="weekendNotice">
								<div class="card card-body pb-1">
									<p>Weekends have been excluded from the schedule. You are now viewing the next week day.</p>
								</div>
							</div>
						<?php } ?>
						</div>
					</div>
				</main>

			<footer class="text-center my-4">
				<?php if(!empty($allowedClasses) && !empty($desiredClass)) { ?>
					<div class="d-block d-sm-none dropup d-inline">
						<a class="btn btn-white shadow-none text-muted dropdown-toggle" href="#" role="button" id="classFooterButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
							<i class="fas fa-folder"></i> <?php echo $desiredClass; ?>
						</a>
						<div class="dropdown-menu" id="classFooterMenu" aria-labelledby="classFooterButton">
							<?php printClassDropdown($allowedClasses, $desiredClass, $desiredDate, $tokenEmbed); ?>
						</div>
					</div>
				<?php } ?>
				<div class="d-block d-sm-none mt-2">
					<span class="text-muted" data-toggle="tooltip" data-placement="bottom" title="One-finger swipes change the day. Two-finger swipes change the week.">
						<small>Navigate by swiping left and right. <i class="fas fa-info-circle"></i></small>
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
