$(function () {
	//Elements
	const head = $('head');

	// Redirect
	function redirect(url) {
		window.location.href = url;
	}
	function redirectToHref(selector) {
		const target = $(selector).attr('href');
		if (target.length) {
			redirect(target);
		}
	}
	function clickID(id) {
		document.getElementById(id).click();
	}
	function clickIDIfExists(id) {
		if ($("#" + id).length) {
			clickID(id);
		}
	}
	// Detect "active"
	$.fn.hasActiveClass = function () {
		return this.hasClass('active');
	};
	$.fn.isVisible = function () {
		return this.is(':visible');
	};
	// Detect "none"
	function notNone(val) {
		return val !== "none";
	}
	function dataNotNone(data) {
		const attr = head.data(data);
		return attr !== undefined && notNone(attr);
	}
	//Time
	function pad(str, max) {
		str = str.toString();
		return str.length < max ? pad("0" + str, max) : str;
	}
	function isBetween(x, min, max) {
		return (min <= x) && (x <= max);
	}

	//Navigation
	function redirectToNextDay() {
		if (dataNotNone('nextday')) {
			redirectToHref('#nextDay');
		} else if (head.data('weekoverview') === true) {
			redirectToHref('#nextWeek');
		}
	}
	function redirectToPrevDay() {
		if (dataNotNone('prevday')) {
			redirectToHref('#prevDay');
		} else if (head.data('weekoverview') === true) {
			redirectToHref('#prevWeek');
		}
	}

	/* Swipe */
	// fingerCount 0: No touchscreen detected
	$("html").swipe({
		swipeLeft: function (event, direction, distance, duration, fingerCount) {
			if (fingerCount === 1 || fingerCount === 0) {
				redirectToNextDay();
			}
			if (fingerCount === 2 && dataNotNone('nextweek')) {
				redirectToHref('#nextWeek');
			}
		},
		swipeRight: function (event, direction, distance, duration, fingerCount) {
			// fingerCount 0: No touchscreen detected
			if (fingerCount === 1 || fingerCount === 0) {
				redirectToPrevDay();
			}
			if (fingerCount === 2 && dataNotNone('prevweek')) {
				redirectToHref('#prevWeek');
			}
		},
		fingers: 'all',
		threshold: '125'
	});

	/* Keyboard navigation */
	$(document).keydown(function (e) {
		if (!e.ctrlKey && !e.metaKey && !e.shiftKey) {
			switch (e.which) {
				case 65: // A
					redirectToPrevDay();
					break;

				case 68: // D
					redirectToNextDay();
					break;

				case 87: // W
					if (dataNotNone('nextweek')) {
						redirectToHref('#nextWeek');
					}
					break;

				case 83: // S
					if (dataNotNone('prevweek')) {
						redirectToHref('#prevWeek');
					}
					break;

				case 13: // enter
					if (head.data('enabletodaylink') === true) {
						redirectToHref('#today');
					}
					break;

				case 84: // T
					redirectToHref('#overviewType');
					break;

					//1-9
				case 49:
				case 50:
				case 51:
				case 52:
				case 53:
				case 54:
				case 55:
				case 56:
				case 57:
					const keyCodeElement = '#keyCode' + e.keyCode;
					if ($(keyCodeElement).length && !$(keyCodeElement).hasActiveClass()) {
						redirectToHref(keyCodeElement);
					}
					if ($(keyCodeElement).hasActiveClass() && $(keyCodeElement).isVisible()) {
						clickIDIfExists('classNavButton'); //close menu when selecting link with active class
					}
					break;

				case 67: //C
					clickIDIfExists('classNavButton');
					break;

				default:
					return; // exit this handler for other keys
			}
			e.preventDefault(); // prevent the default action (scroll / move caret)
		}
	});

	/* Time */
	function formatTime(min, hours) {
		return pad(min, 2) + ":" + pad(hours, 2);
	}

	const highlightClasses = head.data('highlightclasses');
	function updateEvents(time) {
		$("div[data-start].today").val(function () { //for-each all divs with data-start
			const thisType = $(this).data('type');

			const thisStart = $(this).data('start');
			const thisEnd = $(this).data('end');

			if (thisType == "event") {
				if (isBetween(time, thisStart, thisEnd)) {
					$(this).addClass(highlightClasses);
				} else {
					$(this).removeClass(highlightClasses);
				}
			} else if (thisType == "break") {
				if (isBetween(time, thisStart, thisEnd)) {
					$(this).removeClass("d-none");
				} else {
					$(this).addClass("d-none");
				}
			}
		});
	}

	const clock = $('.currentTime');
	var displayed = clock.text(); //cannot use let here, eslint forbids it in the global scope.

	function updateTime() {
		const dt = new Date();
		const time = formatTime(dt.getHours(), dt.getMinutes());

		if (displayed !== time) {
			displayed = time;
			clock.html(time);
			if (head.data('highlightevents') === true) {
				updateEvents(time);
			}
		}

	}
	setInterval(function () {
		updateTime();
	}, 5000);

	/* Automatic Scroll */
	$("#infoBtn").click(function () {
		if (!$('#weekendNotice').hasClass('show')) {
			$([document.documentElement, document.body]).animate({
				scrollTop: $("footer").offset().top //cannot scroll to hidden weekendNotice directly
			}, 250);
		}
	});

	/* Tooltip */
	$('[data-toggle="tooltip"]').tooltip();
});
