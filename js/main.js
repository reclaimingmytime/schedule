/* Swipe */

$(function () {
	$("html").swipe({
		swipe: function (event, direction, distance, duration, fingerCount, fingerData) {
			if (direction == "left") {
				window.location.href = "?d=" + $('head').data('nextday');
			}
			if (direction == "right") {
				if ($('head').data('prevday') !== "none") {
					window.location.href = "?d=" + $('head').data('prevday');
				}
			}
		},
		threshold: 10
	});

	$(document).keydown(function (e) {
		switch (e.which) {
			// case 37: // left
			case 65: // A
				if ($('head').data('prevday') !== "none") {
					window.location.href = "?d=" + $('head').data('prevday');
				}
				break;

			// case 39: // right
			case 68: // D
				window.location.href = "?d=" + $('head').data('nextday');
				break;

			// case 38: // up
			case 87: // W
				window.location.href = "?d=" + $('head').data('nextweek');
				break;

				// case 40: // down
			case 83: // S
				if ($('head').data('prevweek') !== "none") {
					window.location.href = "?d=" + $('head').data('prevweek');
				}
				break;

			case 13: // enter
				if ($('head').data('today') !== $('head').data('desireddate')) {
					window.location.href = ".";
				}
				break;

			default:
				return; // exit this handler for other keys
		}
		e.preventDefault(); // prevent the default action (scroll / move caret)
	});
});