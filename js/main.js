$(function () {

	/* Swipe */
	$("html").swipe({
		swipe: function (event, direction, distance, duration, fingerCount, fingerData) {
			// fingerCount 0: No touchscreen detected
			if (direction == "left" && (fingerCount == 1 || fingerCount == 0)) {
				window.location.href = "?date=" + $('head').data('nextday');
			}
			if (direction == "left" && fingerCount == 2) {
				window.location.href = "?date=" + $('head').data('nextweek');
			}

			if (direction == "right" && (fingerCount == 1 || fingerCount == 0) && $('head').data('prevday') !== "none") {
				if ($('head').data('prevday') !== "none") {
					window.location.href = "?date=" + $('head').data('prevday');
				}
			}
			if (direction == "right" && fingerCount == 2 && $('head').data('prevweek') !== "none") {
				window.location.href = "?date=" + $('head').data('prevweek');
			}
		},
		fingers: 'all',
		threshold: 50
	});

	/* Keyboard navigation */
	$(document).keydown(function (e) {
		if (!e.ctrlKey && !e.metaKey && !e.shiftKey) {
			switch (e.which) {
				case 65: // A
					if ($('head').data('prevday') !== "none") {
						window.location.href = "?date=" + $('head').data('prevday');
					}
					break;

				case 68: // D
					window.location.href = "?date=" + $('head').data('nextday');
					break;

				case 87: // W
					window.location.href = "?date=" + $('head').data('nextweek');
					break;

				case 83: // S
					if ($('head').data('prevweek') !== "none") {
						window.location.href = "?date=" + $('head').data('prevweek');
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
		}
	});

	/* Time */
	function pad(str, max) {
		str = str.toString();
		return str.length < max ? pad("0" + str, max) : str;
	}

	var displayed = $('.currentTime').text();
	function updateTime() {
		var dt = new Date();
		var time = pad(dt.getHours(), 2) + ":" + pad(dt.getMinutes(), 2);

		if (displayed !== time) {
			displayed = time;
			$('.currentTime').html(time);
		}
	}
	setInterval(function () {
		updateTime();
	}, 5000);

	/* Scroll to top */
	$('.top').on('click', function () {
		$('html, body').animate({
			scrollTop: 0
		}, 400);
		return false;
	});
	if (($(document).height() > $(window).height())) { //if content is scrollable
		$('.top').removeClass('d-none').addClass('d-inline-block');
	}
});
