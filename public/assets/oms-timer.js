/* ==========================================================================
   OMS – task countdown
   --------------------------------------------------------------------------
   Any element marked with data-oms-countdown="<unix timestamp>" ticks down to
   that deadline. Past the deadline it counts up and turns red; inside the last
   30 minutes it turns amber.

     <span class="oms-timer" data-oms-countdown="1785000000">–</span>
   ========================================================================== */

(function () {
    'use strict';

    var WARNING_SECONDS = 30 * 60;

    function format(totalSeconds) {
        var hours   = Math.floor(totalSeconds / 3600);
        var minutes = Math.floor((totalSeconds % 3600) / 60);
        var seconds = Math.floor(totalSeconds % 60);

        return hours + 'h ' + String(minutes).padStart(2, '0') + 'm ' + String(seconds).padStart(2, '0') + 's';
    }

    function tick() {
        Array.prototype.forEach.call(document.querySelectorAll('[data-oms-countdown]'), function (element) {
            var deadline  = parseInt(element.getAttribute('data-oms-countdown'), 10) * 1000;
            var remaining = Math.round((deadline - Date.now()) / 1000);
            var overdue   = remaining < 0;

            element.textContent = (overdue ? '+' : '') + format(Math.abs(remaining));
            element.classList.toggle('oms-timer--danger', overdue);
            element.classList.toggle('oms-timer--warn', !overdue && remaining <= WARNING_SECONDS);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (!document.querySelector('[data-oms-countdown]')) return;

        tick();
        setInterval(tick, 1000);
    });
})();
