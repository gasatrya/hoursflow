( function () {
	'use strict';

	const DEFAULT_BACKGROUND_COLOR = '#166534';
	const DEFAULT_TEXT_COLOR = '#FFFFFF';
	const COLOR_PATTERN = /^#[0-9A-Fa-f]{6}$/;

	function updateDay( dayFieldset ) {
		const closedToggle = dayFieldset.querySelector(
			'[data-hoursflow-closed-toggle]'
		);
		if ( ! closedToggle ) {
			return;
		}

		const timeInputs = dayFieldset.querySelectorAll(
			'[data-hoursflow-time-input]'
		);
		const isClosed = closedToggle.checked;
		const state = isClosed ? 'closed' : 'open';
		const stateLabelAttribute = isClosed
			? 'data-hoursflow-closed-label'
			: 'data-hoursflow-open-label';
		const stateText = dayFieldset.querySelector(
			'[data-hoursflow-schedule-state-text]'
		);

		for ( let index = 0; index < timeInputs.length; index += 1 ) {
			timeInputs[ index ].disabled = isClosed;
			timeInputs[ index ].required = ! isClosed;
		}

		dayFieldset.classList.toggle(
			'hoursflow-schedule-day--closed',
			isClosed
		);
		dayFieldset.classList.toggle(
			'hoursflow-schedule-day--open',
			! isClosed
		);
		dayFieldset.setAttribute( 'data-hoursflow-schedule-state', state );

		if ( stateText ) {
			stateText.textContent =
				dayFieldset.getAttribute( stateLabelAttribute ) || '';
		}
	}

	function initializeSchedule() {
		const dayFieldsets = document.querySelectorAll(
			'[data-hoursflow-schedule-day]'
		);

		for ( let index = 0; index < dayFieldsets.length; index += 1 ) {
			const dayFieldset = dayFieldsets[ index ];
			const closedToggle = dayFieldset.querySelector(
				'[data-hoursflow-closed-toggle]'
			);

			updateDay( dayFieldset );
			if ( closedToggle ) {
				closedToggle.addEventListener( 'change', function () {
					updateDay( dayFieldset );
				} );
			}
		}
	}

	function initializeResetConfirmation() {
		const form = document.querySelector(
			'#hoursflow-settings-layout form'
		);
		if ( ! form || 'function' !== typeof form.addEventListener ) {
			return;
		}

		const resetButton = form.querySelector(
			'[data-hoursflow-reset-confirm]'
		);
		if ( ! resetButton ) {
			return;
		}

		const message =
			resetButton.getAttribute( 'data-hoursflow-reset-confirm' ) || '';
		if ( '' === message.trim() || 'function' !== typeof window.confirm ) {
			return;
		}

		form.addEventListener( 'submit', function ( event ) {
			if ( event.submitter !== resetButton ) {
				return;
			}

			// eslint-disable-next-line no-alert
			if ( ! window.confirm( message ) ) {
				event.preventDefault();
			}
		} );
	}

	function normalizeState( state ) {
		return 'closed' === state ? 'closed' : 'open';
	}

	function getPreviewFieldValue( fieldId ) {
		const field = document.getElementById( fieldId );
		if ( ! field || 'string' !== typeof field.value ) {
			return null;
		}

		return field.value;
	}

	function getPreviewState( preview ) {
		return normalizeState(
			preview.getAttribute( 'data-hoursflow-preview-state' )
		);
	}

	function getPreviewDefaultColor( preview, attribute, fallback ) {
		const value = preview.getAttribute( attribute );
		return COLOR_PATTERN.test( value || '' ) ? value : fallback;
	}

	function getPreviewColor( fieldId, fallback ) {
		const value = getPreviewFieldValue( fieldId );
		return COLOR_PATTERN.test( value || '' ) ? value : fallback;
	}

	function updatePreviewColors( preview ) {
		const content = preview.querySelector(
			'[data-hoursflow-preview-content]'
		);
		if (
			! content ||
			! content.style ||
			'function' !== typeof content.style.setProperty
		) {
			return;
		}

		const backgroundDefault = getPreviewDefaultColor(
			preview,
			'data-hoursflow-preview-default-background-color',
			DEFAULT_BACKGROUND_COLOR
		);
		const textDefault = getPreviewDefaultColor(
			preview,
			'data-hoursflow-preview-default-text-color',
			DEFAULT_TEXT_COLOR
		);
		const background = getPreviewColor(
			'hoursflow-appearance-background-color',
			backgroundDefault
		);
		const text = getPreviewColor(
			'hoursflow-appearance-text-color',
			textDefault
		);

		content.style.setProperty(
			'--hoursflow-cta-background-color',
			background
		);
		content.style.setProperty( '--hoursflow-cta-text-color', text );
	}

	function updatePreviewContent( preview, state ) {
		const content = preview.querySelector(
			'[data-hoursflow-preview-content]'
		);
		if ( ! content ) {
			return;
		}

		const label = getPreviewFieldValue(
			'hoursflow-cta-' + state + '-label'
		);
		const status = getPreviewFieldValue(
			'hoursflow-cta-' + state + '-status'
		);
		const labelElement = content.querySelector(
			'[data-hoursflow-preview-label]'
		);
		const statusElement = content.querySelector(
			'[data-hoursflow-preview-status]'
		);

		if ( labelElement && null !== label ) {
			labelElement.textContent = label;
		}
		if ( statusElement && null !== status ) {
			statusElement.textContent = status;
			statusElement.hidden = '' === status.trim();
		}
	}

	function updatePreviewState( preview, state ) {
		const selectedState = normalizeState( state );
		const buttons = preview.querySelectorAll(
			'[data-hoursflow-preview-state-button]'
		);
		const content = preview.querySelector(
			'[data-hoursflow-preview-content]'
		);

		preview.setAttribute( 'data-hoursflow-preview-state', selectedState );
		if ( content ) {
			content.classList.toggle(
				'hoursflow-cta--open',
				'open' === selectedState
			);
			content.classList.toggle(
				'hoursflow-cta--closed',
				'closed' === selectedState
			);
			content.setAttribute(
				'data-hoursflow-preview-state',
				selectedState
			);
		}

		for ( let index = 0; index < buttons.length; index += 1 ) {
			const button = buttons[ index ];
			const buttonState = normalizeState(
				button.getAttribute( 'data-hoursflow-preview-state-button' )
			);
			button.setAttribute(
				'aria-pressed',
				buttonState === selectedState ? 'true' : 'false'
			);
		}

		updatePreviewContent( preview, selectedState );
	}

	function bindPreviewField( preview, fieldId, state, kind ) {
		const field = document.getElementById( fieldId );
		if ( ! field || 'function' !== typeof field.addEventListener ) {
			return;
		}

		const update = function () {
			if ( 'color' === kind ) {
				updatePreviewColors( preview );
				return;
			}
			if ( getPreviewState( preview ) === state ) {
				updatePreviewContent( preview, state );
			}
		};

		field.addEventListener( 'input', update );
		field.addEventListener( 'change', update );
	}

	function initializePreview() {
		const preview =
			document.querySelector( '[data-hoursflow-preview]' ) ||
			document.getElementById( 'hoursflow-cta-preview' );
		if ( ! preview ) {
			return;
		}

		const buttons = preview.querySelectorAll(
			'[data-hoursflow-preview-state-button]'
		);
		for ( let index = 0; index < buttons.length; index += 1 ) {
			const button = buttons[ index ];
			button.addEventListener( 'click', function () {
				updatePreviewState(
					preview,
					button.getAttribute( 'data-hoursflow-preview-state-button' )
				);
			} );
		}

		updatePreviewState( preview, 'open' );
		updatePreviewColors( preview );

		for ( const state of [ 'open', 'closed' ] ) {
			bindPreviewField(
				preview,
				'hoursflow-cta-' + state + '-label',
				state,
				'text'
			);
			bindPreviewField(
				preview,
				'hoursflow-cta-' + state + '-status',
				state,
				'text'
			);
		}
		bindPreviewField(
			preview,
			'hoursflow-appearance-background-color',
			'open',
			'color'
		);
		bindPreviewField(
			preview,
			'hoursflow-appearance-text-color',
			'open',
			'color'
		);
	}

	function initialize() {
		initializeSchedule();
		initializePreview();
		initializeResetConfirmation();
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', initialize );
	} else {
		initialize();
	}
} )();
