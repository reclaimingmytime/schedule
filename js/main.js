$(function () {
	//Elements
	const head = $('head');

	const A_KEY = 65;
	const C_KEY = 67;
	const D_KEY = 68;
	const S_KEY = 83;
	const T_KEY = 84;
	const W_KEY = 87;
	const X_KEY = 88;
	const ENTER_KEY = 13;
	const ZERO_KEY = 13;
	const ONE_KEY = 49;
	const TWO_KEY = 50;
	const THREE_KEY = 51;
	const FOUR_KEY = 52;
	const FIVE_KEY = 53;
	const SIX_KEY = 54;
	const SEVEN_KEY = 55;
	const EIGHT_KEY = 56;
	const NINE_KEY = 57;

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
	//Other
	$.fn.capitalizeFirstLetter = function () {
		return this.charAt(0).toUpperCase() + this.slice(1);
	};

	//Navigation
	function redirectToNextDay() {
		if (dataNotNone('nextday')) {
			redirectToHref('#nextDay');
		} else if (head.data('weekoverview') === true) {
			redirectToHref('#nextWeek');
		}
	}
	function redirectToNextWeek() {
		if (dataNotNone('nextweek')) {
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
	function redirectToPrevWeek() {
		if (dataNotNone('prevweek')) {
			redirectToHref('#prevWeek');
		}
	}
	function redirectToToday() {
		if (head.data('enabletodaylink') === true) {
			redirectToHref('#today');
		}
	}

	/* Swipe */
	// fingerCount 0: No touchscreen detected

	function hasTouch() {
		try {
			document.createEvent("TouchEvent");
			return true;
		} catch (e) {
			return false;
		}
	}

	if (hasTouch() == true) {
		$("html").swipe({
			swipeLeft: function (event, direction, distance, duration, fingerCount) {
				if (fingerCount === 1 || fingerCount === 0) {
					redirectToNextDay();
				}
				if (fingerCount === 2) {
					redirectToNextWeek();
				}
			},
			swipeRight: function (event, direction, distance, duration, fingerCount) {
				// fingerCount 0: No touchscreen detected
				if (fingerCount === 1 || fingerCount === 0) {
					redirectToPrevDay();
				}
				if (fingerCount === 2) {
					redirectToPrevWeek();
				}
			},
			fingers: 'all',
			threshold: '125'
		});
	}

	/* Keyboard navigation */
	$(document).keydown(function (e) {
		if (!e.ctrlKey && !e.metaKey && !e.shiftKey) {
			switch (e.which) {
				case A_KEY:
					redirectToPrevDay();
					break;

				case D_KEY:
					redirectToNextDay();
					break;

				case W_KEY:
					redirectToNextWeek();
					break;

				case S_KEY:
					redirectToPrevWeek();
					break;

				case ENTER_KEY:
					redirectToToday();
					break;

				case T_KEY:
					redirectToHref('#overviewType');
					break;

				case ONE_KEY:
				case TWO_KEY:
				case THREE_KEY:
				case FOUR_KEY:
				case FIVE_KEY:
				case SIX_KEY:
				case SEVEN_KEY:
				case EIGHT_KEY:
				case NINE_KEY:
					const keyCodeElement = '#keyCode' + e.keyCode;
					if ($(keyCodeElement).length && !$(keyCodeElement).hasActiveClass()) {
						redirectToHref(keyCodeElement);
					}
					if ($(keyCodeElement).hasActiveClass() && $(keyCodeElement).isVisible()) {
						clickIDIfExists('classNavButton'); //close menu when selecting link with active class
					}
					break;

				case C_KEY:
					clickIDIfExists('classNavButton');
					break;

				case X_KEY:
					clickIDIfExists('extraEventsButton');
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
	function millisecondsToMins(time) {
		return Math.ceil(time / 60000);
	}
	function removeHours(minutes) {
		return minutes % 60;
	}
	function removeMinutes(minutes) {
		return Math.floor(minutes / 60);
	}

	function computeRemainingMilliseconds(destination, timeMilliseconds) {
		var countDownDate = new Date(destination).getTime();
		return countDownDate - timeMilliseconds;
	}
	
	function prettyPrintMinutes(minutes) {
		if (minutes < 60) {
			return minutes + " m";
		}
		var m = removeHours(minutes);
		var h = removeMinutes(minutes);
		return h + " h " + m + " m";
	}

	function formatRemainingTime(minutes) {
		return prettyPrintMinutes(minutes) + " remaining";
	}

	function displayRemainingTime(card, timeRemaining) {
		var cardFooter = card.find('.card-footer');
		cardFooter.find('.timeRemaining').html(timeRemaining);
		cardFooter.removeClass('d-none');
	}
	function hideRemainingTime(card) {
		card.find('.card-footer').addClass('d-none');
	}

	function updateRemainingTime(endDateTime, timeMilliseconds, card) {
		var remaining = computeRemainingMilliseconds(endDateTime, timeMilliseconds);

		var minutesRemaining = millisecondsToMins(remaining);
		if (minutesRemaining >= 0) {
			var timeRemaining = formatRemainingTime(minutesRemaining);
			displayRemainingTime(card, timeRemaining);
		}
	}

	function swapClasses(element, toBeRemoved, toBeAdded) {
		if (toBeRemoved !== undefined) {
			$(element).removeClass(toBeRemoved);
		}
		if (toBeAdded !== undefined) {
			$(element).addClass(toBeAdded);
		}
	}

	function highlightEvent(element, time, timeMilliseconds, start, end, endDateTime, highlight, normal) {
		var card = $(element).closest('.card');

		if (isBetween(time, start, end)) {
			updateRemainingTime(endDateTime, timeMilliseconds, card);
			swapClasses(element, normal, highlight);
		} else {
			hideRemainingTime(card);
			swapClasses(element, highlight, normal);
		}
	}

	const highlightClasses = head.data('highlightclasses');
	const extraClasses = head.data('extraclasses');

	function updateEvents(time, timeMilliseconds) {
		$("div[data-start].today").val(function () { //for-each all divs with data-start and class today
			const thisType = $(this).data('type');

			const thisStart = $(this).data('start');
			const thisEnd = $(this).data('end');
			const endDateTime = $(this).data('enddatetime');

			if (thisType == "event") {
				highlightEvent(this, time, timeMilliseconds,
								thisStart, thisEnd, endDateTime,
								highlightClasses, undefined);
			} else if (thisType == "extraEvent") {
				highlightEvent(this, time, timeMilliseconds,
								thisStart, thisEnd, endDateTime,
								highlightClasses, extraClasses);
			} else if (thisType == "break") {
				highlightEvent(this, time, timeMilliseconds,
								thisStart, thisEnd, endDateTime,
								undefined, "d-none");
			}
		});
	}

	const clock = $('.currentTime');
	var displayed = clock.text(); //cannot use let here, eslint forbids it in the global scope.

	function updateTime() {
		const dt = new Date();
//		const dt = new Date("Oct 1, 2019 10:22:59");
		const time = formatTime(dt.getHours(), dt.getMinutes());
		const timeMilliseconds = dt.getTime();

		if (displayed !== time) {
			displayed = time;
			clock.html(time);
			if (head.data('highlightevents') === true) {
				updateEvents(time, timeMilliseconds);
			}
		}

	}
	updateTime();
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

	/* Service Worker */
	if ('serviceWorker' in navigator && head.data('hasmanifest') === true) {
		navigator.serviceWorker.register('serviceworker.min.js').
						then(function (registration) {
							/* console.log('ServiceWorker registration successful with scope: ',
							 registration.scope); */
						}).catch(function (err) {
			/* console.log('ServiceWorker registration failed: ', err); */
		});
	}

});
