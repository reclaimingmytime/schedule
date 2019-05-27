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

	/* Swipe */
	$("body").swipe({
		swipe: function (event, direction, distance, duration, fingerCount) {
			// fingerCount 0: No touchscreen detected
			if (direction === "left" && (fingerCount === 1 || fingerCount === 0) && dataNotNone('nextday')) {
				redirectToHref('#nextDay');
			}
			if (direction === "left" && fingerCount === 2 && dataNotNone('nextweek')) {
				redirectToHref('#nextWeek');
			}

			if (direction === "right" && (fingerCount === 1 || fingerCount === 0) && dataNotNone('prevday')) {
				redirectToHref('#prevDay');
			}
			if (direction === "right" && fingerCount === 2 && dataNotNone('prevweek')) {
				redirectToHref('#prevWeek');
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
					if (dataNotNone('prevday')) {
						redirectToHref('#prevDay');
					}
					break;

				case 68: // D
					if (dataNotNone('nextday')) {
						redirectToHref('#nextDay');
					}
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
					if (head.data('today') !== head.data('desireddate')) {
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
			} else if(thisType == "break") {
				if (isBetween(time, thisStart, thisEnd)) {
					$(this).removeClass("d-none");
				} else {
					$(this).addClass("d-none");
				}
			}

			/* Future feature: Dynamically disable and enable break
			 const nextEvent = $(this).parent().nextUntil(".event").last().next().find(".card-header");
			 //Debug: $("#event0 .card-header").parent().nextUntil(".event").last().next().find(".card-header").data("start")
			 const nextStart = nextEvent.data("start");
			 const nextBreak = $(this).parent().nextUntil(".break").last().next();
			 //			console.log("nextEvent: " + nextEvent.data("start") + " - " + nextEvent.data("end"));
			 
			 //			if(thisEnd !== nextStart && isBetween(time, thisEnd, thisStart) && nextBreak.hasClass("d-none")) {
			 //				nextBreak.removeClass("d-none");
			 //			} else if(nextBreak.not(".d-none")) {
			 //				nextBreak.addClass("d-none");	
			 //			} */

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
