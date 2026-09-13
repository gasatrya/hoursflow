(function () {
    'use strict';

    function updateDay(dayFieldset) {
        var closedToggle = dayFieldset.querySelector('[data-opennow-closed-toggle]');
        var timeInputs = dayFieldset.querySelectorAll('[data-opennow-time-input]');
        var index;
        var isClosed;

        if (!closedToggle) {
            return;
        }

        isClosed = closedToggle.checked;
        for (index = 0; index < timeInputs.length; index += 1) {
            timeInputs[index].disabled = isClosed;
            timeInputs[index].required = !isClosed;
        }
    }

    function initialize() {
        var dayFieldsets = document.querySelectorAll('[data-opennow-schedule-day]');
        var index;
        var closedToggle;

        for (index = 0; index < dayFieldsets.length; index += 1) {
            updateDay(dayFieldsets[index]);
            closedToggle = dayFieldsets[index].querySelector('[data-opennow-closed-toggle]');
            if (closedToggle) {
                closedToggle.addEventListener('change', function () {
                    updateDay(this.closest('[data-opennow-schedule-day]'));
                });
            }
        }
    }

    if ('loading' === document.readyState) {
        document.addEventListener('DOMContentLoaded', initialize);
    } else {
        initialize();
    }
}());
