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
		for ( let index = 0; index < timeInputs.length; index += 1 ) {
			timeInputs[ index ].disabled = isClosed;
			timeInputs[ index ].required = ! isClosed;
		}
	}

	function initialize() {
		const dayFieldsets = document.querySelectorAll(
			'[data-opennow-schedule-day]'
		);

		for ( let index = 0; index < dayFieldsets.length; index += 1 ) {
			updateDay( dayFieldsets[ index ] );
			const closedToggle = dayFieldsets[ index ].querySelector(
				'[data-opennow-closed-toggle]'
			);
			if ( closedToggle ) {
				closedToggle.addEventListener( 'change', function () {
					updateDay( this.closest( '[data-opennow-schedule-day]' ) );
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
