$(function () {
	$("html").swipe({
		swipe: function (event, direction, distance, duration, fingerCount, fingerData) {
			if (direction == "left") {
				window.location.href = "?d=" + $('head').data('nextday');
			}
			if (direction == "right") {
				if($('head').data('prevday') !== "none") {
					window.location.href = "?d=" + $('head').data('prevday');
				}
			}
		},
		threshold: 75
	});

	$(document).keydown(function (e) {
		switch (e.which) {
			case 37: // left
				if($('head').data('prevday') !== "none") {
					window.location.href = "?d=" + $('head').data('prevday');
				}
				break;

			case 39: // right
				window.location.href = "?d=" + $('head').data('nextday');
				break;

			case 38: // up
				if($('head').data('prevweek') !== "none") {
					window.location.href = "?d=" + $('head').data('prevweek');
				}
				break;

			case 40: // down
				window.location.href = "?d=" + $('head').data('nextweek');
				break;

			case 13: // enter
				window.location.href = ".";
				break;

			default:
				return; // exit this handler for other keys
		}
		e.preventDefault(); // prevent the default action (scroll / move caret)
	});
});

