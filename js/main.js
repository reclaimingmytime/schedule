$(function () {
  // Redirect
  function redirect(url) {
    window.location.href = url;
  }
  function redirectToHref(selector) {
    redirect($(selector).attr('href'));
  }
  function clickID(id) {
    document.getElementById(id).click();
  }
  // Detect "active"
  $.fn.isActive = function () {
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
    return notNone($('head').data(data));
  }
  //Time
  function pad(str, max) {
    str = str.toString();
    return str.length < max ? pad("0" + str, max) : str;
  }

  /* Swipe */
  $("html").swipe({
    swipe: function (event, direction, distance, duration, fingerCount, fingerData) {
      // fingerCount 0: No touchscreen detected
      if (direction === "left" && (fingerCount === 1 || fingerCount === 0)) {
        redirectToHref('#nextDay');
      }
      if (direction === "left" && fingerCount === 2) {
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
          redirectToHref('#nextDay');
          break;

        case 87: // W
          redirectToHref('#nextWeek');
          break;

        case 83: // S
          if (dataNotNone('prevweek')) {
            redirectToHref('#prevWeek');
          }
          break;

        case 13: // enter
          if ($('head').data('today') !== $('head').data('desireddate')) {
            redirectToHref('#today');
          }
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
          var keyCodeElement = '#keyCode' + e.keyCode;
          if ($(keyCodeElement).length && !$(keyCodeElement).isActive()) {
            redirectToHref(keyCodeElement);
          }
          if($(keyCodeElement).hasClass('active') && $(keyCodeElement).isVisible()) {
            clickID('classNavButton'); //close menu if link is active
          }
          break;
        
        case 67: //C
          clickID('classNavButton');

        default:
          return; // exit this handler for other keys
      }
      e.preventDefault(); // prevent the default action (scroll / move caret)
    }
  });

  /* Time */
  function updateEvents(time) {
    $("div[data-start]").val(function(){ //for-each all divs with data-start
      var start = $(this).data('start');
      var end = $(this).data('end');

      if(start <= time && time <= end) {
        $(this).addClass('bg-dark text-light');
      } else if($(this).hasClass('bg-dark')) {
        $(this).removeClass('bg-dark text-light');
      }

    });
  }

  var displayed = $('.currentTime').text();
  function updateTime() {
    var dt = new Date();
    var time = pad(dt.getHours(), 2) + ":" + pad(dt.getMinutes(), 2);

    if (displayed !== time) {
      displayed = time;
      $('.currentTime').html(time);
      if($("head").data('highlightevents') === true) {
        updateEvents(time);
      }
    }
    
  }
  setInterval(function () {
    updateTime();
  }, 5000);
  
  updateTime();

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
