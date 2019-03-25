<?php
/* Display Schedule */

function printClassDropdown($allowedClasses, $desiredClass, $desiredDate, $tokenEmbed, $ids = false) {
  $keyCode = 48;
  $i = 0;
	foreach ($allowedClasses as $class) { ?>
		<a class="dropdown-item<?php if ($desiredClass == $class) echo " active"; ?>" href="?class=<?php echo $class; ?>&amp;date=<?php echo $desiredDate . $tokenEmbed; ?>"<?php echo ($ids === true && $i <= 9 ? " id=\"keyCode$keyCode\"" : '') ?>><i class="fas fa-folder-open"></i> <?php echo $class . ($ids === true && $i <= 9 ? " <small class=\"text-muted\">($i)</small>" : ''); ?></a>
		<?php
    $keyCode++;
    $i++;
	}
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
			.dropdown-toggle {
					outline: none;
			}
			.btn:focus {
					box-shadow: none;
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
						<a class="navbar-brand<?php echo ($desiredDate == $today) ? ' active' : ''; ?>" href=".">Schedule</a>
					</div>

					<ul class="navbar-nav m-auto ml-sm-0">
						<li class="nav-item mr-4 ml-3<?php echo ($desiredDate == $today) ? ' active' : ''; ?>">
              <a class="nav-link" id="today" href="."><i class="fas fa-play"></i> <span class="d-none d-lg-inline">Today <small>(Enter)</small></span></a>
						</li>

						<li class="nav-item mr-4">
              <a class="nav-link" id="nextDay" href="?date=<?php echo $nextDay; ?>"><i class="fas fa-forward"></i> <span class="d-none d-lg-inline">Next Day <small>(D)</small></span></a>
						</li>
						<li class="nav-item mr-4">
							<a class="nav-link" id="nextWeek" href="?date=<?php echo $nextWeek; ?>"><i class="fas fa-step-forward"></i> <span class="d-none d-lg-inline">Next Week <small>(W)</small></span></a>
						</li>

						<?php if ($prevDay !== "none") { ?>
							<li class="nav-item mr-4">
								<a class="nav-link" id="prevDay" href="?date=<?php echo $prevDay; ?>"><i class="fas fa-backward"></i> <span class="d-none d-lg-inline">Previous Day <small>(A)</small></span></a>
							</li>
						<?php }
						if ($prevWeek !== "none") { ?>
							<li class="nav-item mr-4">
								<a class="nav-link" id="prevWeek" href="?date=<?php echo $prevWeek; ?>"><i class="fas fa-step-backward"></i> <span class="d-none d-lg-inline">Previous Week <small>(S)</small></span></a>
							</li>
						<?php } ?>

						<?php if(!empty($allowedClasses)) { ?>
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
			
			<div class="row">
				<div class="col-xl-6">
					<main>
						<?php if (isset($_SESSION['validToken']) && $_SESSION['validToken'] === false) { ?>
							<div class="alert alert-danger alert-dismissible fade show" role="alert">
								<strong>The class could not be changed.</strong><br>
								This link is invalid. Please try again.
								<button type="button" class="close" data-dismiss="alert" aria-label="Close">
									<span aria-hidden="true">&times;</span>
								</button>
							</div>
						<?php }
						unset($_SESSION['validToken']);
						?>
						<ul class="list-inline text-muted h4 pb-1">
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
								<?php foreach ($schedule as $event) {
									$timeRange = $event['start'] . " - " . $event['end'];
									$headerClasses = onGoingEvent($event) ? ' bg-dark text-light' : '';
								?>
								<div class="card mt-3">
									<div class="card-header<?php echo $headerClasses; ?>">
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
                        <li class="list-inline-item text-secondary"><?php echo $event['prof']; ?></li>
                      <?php } ?>
										</ul>
									</div>
								</div>
								<?php }
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
						</main>
					</div>
				</div>

			<footer class="text-center my-4">
				<?php if (!empty($allowedClasses)) { ?>
					<div class="d-block d-sm-none dropup d-inline">
						<a class="btn btn-white text-muted dropdown-toggle" href="#" role="button" id="classFooterButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
							<i class="fas fa-folder"></i> <?php echo $desiredClass; ?>
						</a>
						<div class="dropdown-menu" id="classFooterMenu" aria-labelledby="classFooterButton">
							<?php printClassDropdown($allowedClasses, $desiredClass, $desiredDate, $tokenEmbed); ?>
						</div>
					</div>
				<?php } ?>
        <div class="d-block d-sm-none mt-2">
          <small class="text-muted">Navigate by swiping left and right.</small>
        </div>
			</footer>

		</div>

		<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js" integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha256-ZvOgfh+ptkpoa2Y4HkRY28ir89u/+VRyDE7sB7hEEcI=" crossorigin="anonymous"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha256-CjSoeELFOcH0/uxWu6mC/Vlrc1AARqbm/jiiImDGV3s=" crossorigin="anonymous"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.touchswipe/1.6.19/jquery.touchSwipe.min.js" integrity="sha256-ns1OeEP3SedE9Theqmu444I44sikbp1O+bF/6BNUUy0=" crossorigin="anonymous"></script>
		<script src="js/main.min.js"></script>
	</body>
</html>
