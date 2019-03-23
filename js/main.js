$(function () {
  function redirect(url) {
    window.location.href = url;
  }
  
  /* Swipe */
  $("html").swipe({
    swipe: function (event, direction, distance, duration, fingerCount, fingerData) {
      // fingerCount 0: No touchscreen detected
      if (direction == "left" && (fingerCount == 1 || fingerCount == 0)) {
        redirect($('#nextDay').attr('href'));
      }
      if (direction == "left" && fingerCount == 2) {
        redirect($('#nextWeek').attr('href'));
      }

      if (direction == "right" && (fingerCount == 1 || fingerCount == 0) && $('head').data('prevday') !== "none") {
        if ($('head').data('prevday') !== "none") {
          redirect($('#prevDay').attr('href'));
        }
      }
      if (direction == "right" && fingerCount == 2 && $('head').data('prevweek') !== "none") {
        redirect($('#prevWeek').attr('href'));
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
            redirect($('#prevDay').attr('href'));
          }
          break;

        case 68: // D
          redirect($('#nextDay').attr('href'));
          break;

        case 87: // W
          redirect($('#nextWeek').attr('href'));
          break;

        case 83: // S
          if ($('head').data('prevweek') !== "none") {
            redirect($('#prevWeek').attr('href'));
          }
          break;

        case 13: // enter
          if ($('head').data('today') !== $('head').data('desireddate')) {
            redirect($('#today').attr('href'));
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

  /* Automatic Scroll */
  $("#infoBtn").click(function () {
    if (!$('#weekendNotice').hasClass('show')) {
      $([document.documentElement, document.body]).animate({
        scrollTop: $("footer").offset().top //cannot scroll to hidden weekendNotice directly
      }, 250);
    }
  });
});
