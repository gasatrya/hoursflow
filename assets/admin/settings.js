( function () {
	'use strict';

	function updateDay( dayFieldset ) {
		const closedToggle = dayFieldset.querySelector(
			'[data-opennow-closed-toggle]'
		);
		if ( ! closedToggle ) {
			return;
		}

		const timeInputs = dayFieldset.querySelectorAll(
			'[data-opennow-time-input]'
		);
		const isClosed = closedToggle.checked;
		const state = isClosed ? 'closed' : 'open';
		const stateLabelAttribute = isClosed
			? 'data-opennow-closed-label'
			: 'data-opennow-open-label';
		const stateText = dayFieldset.querySelector(
			'[data-opennow-schedule-state-text]'
		);

		for ( let index = 0; index < timeInputs.length; index += 1 ) {
			timeInputs[ index ].disabled = isClosed;
			timeInputs[ index ].required = ! isClosed;
		}

		dayFieldset.classList.toggle(
			'opennow-schedule-day--closed',
			isClosed
		);
		dayFieldset.classList.toggle(
			'opennow-schedule-day--open',
			! isClosed
		);
		dayFieldset.setAttribute( 'data-opennow-schedule-state', state );

		if ( stateText ) {
			stateText.textContent =
				dayFieldset.getAttribute( stateLabelAttribute ) || '';
		}
	}

	function initialize() {
		const dayFieldsets = document.querySelectorAll(
			'[data-opennow-schedule-day]'
		);

		for ( let index = 0; index < dayFieldsets.length; index += 1 ) {
			const dayFieldset = dayFieldsets[ index ];
			const closedToggle = dayFieldset.querySelector(
				'[data-opennow-closed-toggle]'
			);

			updateDay( dayFieldset );
			if ( closedToggle ) {
				closedToggle.addEventListener( 'change', function () {
					updateDay( dayFieldset );
				} );
			}
		}
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', initialize );
	} else {
		initialize();
	}
} )();
