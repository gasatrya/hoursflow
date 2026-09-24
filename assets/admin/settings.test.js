function scheduleMarkup() {
	return `
		<div id="hoursflow-schedule">
			<fieldset
				class="hoursflow-schedule-day hoursflow-schedule-day--open"
				data-hoursflow-schedule-day="monday"
				data-hoursflow-schedule-state="open"
				data-hoursflow-open-label="Abierto"
				data-hoursflow-closed-label="Cerrado"
			>
				<span data-hoursflow-schedule-state-text="1">Abierto</span>
				<input type="checkbox" data-hoursflow-closed-toggle="1" />
				<input id="monday-opens" data-hoursflow-time-input="1" />
				<input id="monday-closes" data-hoursflow-time-input="1" />
			</fieldset>
			<fieldset
				class="hoursflow-schedule-day hoursflow-schedule-day--closed"
				data-hoursflow-schedule-day="tuesday"
				data-hoursflow-schedule-state="closed"
				data-hoursflow-open-label="Abierto"
				data-hoursflow-closed-label="Cerrado"
			>
				<span data-hoursflow-schedule-state-text="1">Cerrado</span>
				<input type="checkbox" data-hoursflow-closed-toggle="1" checked />
				<input id="tuesday-opens" data-hoursflow-time-input="1" />
				<input id="tuesday-closes" data-hoursflow-time-input="1" />
			</fieldset>
			<input id="outside-time" data-hoursflow-time-input="1" required />
		</div>
	`;
}

function previewMarkup() {
	return `
		<input id="hoursflow-cta-open-label" value="Call now" />
		<input id="hoursflow-cta-open-status" value="Open today" />
		<input id="hoursflow-cta-open-action" value="tel:+123456789" />
		<input id="hoursflow-cta-closed-label" value="Book online" />
		<input id="hoursflow-cta-closed-status" value="Reopens tomorrow" />
		<input id="hoursflow-cta-closed-action" value="/booking/" />
		<input id="hoursflow-appearance-background-color" value="#123456" />
		<input id="hoursflow-appearance-text-color" value="#FEDCBA" />
		<aside
			id="hoursflow-cta-preview"
			data-hoursflow-preview="1"
			data-hoursflow-preview-state="open"
			data-hoursflow-preview-default-background-color="#166534"
			data-hoursflow-preview-default-text-color="#FFFFFF"
		>
			<button type="button" data-hoursflow-preview-state-button="open" aria-pressed="true">Open</button>
			<button type="button" data-hoursflow-preview-state-button="closed" aria-pressed="false">Closed</button>
			<div
				id="hoursflow-cta-preview-content"
				class="hoursflow-cta hoursflow-cta--open"
				data-hoursflow-preview-content="1"
				data-hoursflow-preview-state="open"
			>
				<span data-hoursflow-preview-label="1"></span>
				<span data-hoursflow-preview-status="1"></span>
			</div>
		</aside>
	`;
}

function resetMarkup(
	message = 'Are you sure you want to reset all settings to defaults?',
	includeReset = true
) {
	return `
		<div id="hoursflow-settings-layout">
			<form>
				<button type="submit" data-hoursflow-save="1">Save</button>
				${
					includeReset
						? `<button type="submit" data-hoursflow-reset-confirm="${ message }">Reset</button>`
						: ''
				}
			</form>
		</div>
	`;
}

function loadSettings( markup = scheduleMarkup() ) {
	jest.resetModules();
	document.body.innerHTML = markup;
	require( './settings' );
	document.dispatchEvent( new Event( 'DOMContentLoaded' ) );
}

function dispatchInput( element, eventName = 'input' ) {
	element.dispatchEvent( new Event( eventName, { bubbles: true } ) );
}

function dispatchSubmit( form, submitter ) {
	const event = new window.Event( 'submit', {
		bubbles: true,
		cancelable: true,
	} );
	Object.defineProperty( event, 'submitter', { value: submitter } );
	const dispatched = form.dispatchEvent( event );
	return { event, dispatched };
}

describe( 'HoursFlow settings schedule behavior', () => {
	test( 'initializes each fieldset from its own translated state data without preview markup', () => {
		loadSettings();

		const monday = document.querySelector(
			'[data-hoursflow-schedule-day="monday"]'
		);
		const tuesday = document.querySelector(
			'[data-hoursflow-schedule-day="tuesday"]'
		);

		expect( monday.dataset.hoursflowScheduleState ).toBe( 'open' );
		expect(
			monday.classList.contains( 'hoursflow-schedule-day--open' )
		).toBe( true );
		expect(
			monday.querySelector( '[data-hoursflow-schedule-state-text]' )
				.textContent
		).toBe( 'Abierto' );
		expect( monday.querySelector( '#monday-opens' ).required ).toBe( true );
		expect( monday.querySelector( '#monday-opens' ).disabled ).toBe(
			false
		);
		expect( monday.querySelector( '#monday-closes' ).required ).toBe(
			true
		);

		expect( tuesday.dataset.hoursflowScheduleState ).toBe( 'closed' );
		expect(
			tuesday.classList.contains( 'hoursflow-schedule-day--closed' )
		).toBe( true );
		expect(
			tuesday.querySelector( '[data-hoursflow-schedule-state-text]' )
				.textContent
		).toBe( 'Cerrado' );
		expect( tuesday.querySelector( '#tuesday-opens' ).required ).toBe(
			false
		);
		expect( tuesday.querySelector( '#tuesday-opens' ).disabled ).toBe(
			true
		);
		expect( tuesday.querySelector( '#tuesday-closes' ).required ).toBe(
			false
		);
		expect( document.querySelector( '#outside-time' ).required ).toBe(
			true
		);
		expect( document.querySelector( '#outside-time' ).disabled ).toBe(
			false
		);
	} );

	test( 'toggles only the selected fieldset and updates its state presentation', () => {
		loadSettings();

		const monday = document.querySelector(
			'[data-hoursflow-schedule-day="monday"]'
		);
		const tuesday = document.querySelector(
			'[data-hoursflow-schedule-day="tuesday"]'
		);
		const mondayToggle = monday.querySelector(
			'[data-hoursflow-closed-toggle]'
		);
		const tuesdayToggle = tuesday.querySelector(
			'[data-hoursflow-closed-toggle]'
		);

		mondayToggle.checked = true;
		mondayToggle.dispatchEvent( new Event( 'change' ) );

		expect( monday.dataset.hoursflowScheduleState ).toBe( 'closed' );
		expect(
			monday.classList.contains( 'hoursflow-schedule-day--closed' )
		).toBe( true );
		expect(
			monday.classList.contains( 'hoursflow-schedule-day--open' )
		).toBe( false );
		expect(
			monday.querySelector( '[data-hoursflow-schedule-state-text]' )
				.textContent
		).toBe( 'Cerrado' );
		expect( monday.querySelector( '#monday-opens' ).disabled ).toBe( true );
		expect( monday.querySelector( '#monday-opens' ).required ).toBe(
			false
		);
		expect( monday.querySelector( '#monday-closes' ).disabled ).toBe(
			true
		);
		expect( monday.querySelector( '#monday-closes' ).required ).toBe(
			false
		);

		expect( tuesday.dataset.hoursflowScheduleState ).toBe( 'closed' );
		expect( tuesday.querySelector( '#tuesday-opens' ).disabled ).toBe(
			true
		);
		expect( tuesday.querySelector( '#tuesday-opens' ).required ).toBe(
			false
		);

		tuesdayToggle.checked = false;
		tuesdayToggle.dispatchEvent( new Event( 'change' ) );

		expect( tuesday.dataset.hoursflowScheduleState ).toBe( 'open' );
		expect(
			tuesday.classList.contains( 'hoursflow-schedule-day--open' )
		).toBe( true );
		expect(
			tuesday.querySelector( '[data-hoursflow-schedule-state-text]' )
				.textContent
		).toBe( 'Abierto' );
		expect( tuesday.querySelector( '#tuesday-opens' ).disabled ).toBe(
			false
		);
		expect( tuesday.querySelector( '#tuesday-opens' ).required ).toBe(
			true
		);
		expect( monday.dataset.hoursflowScheduleState ).toBe( 'closed' );
	} );
} );

describe( 'HoursFlow reset confirmation', () => {
	test( 'prevents reset submission when confirmation is declined', () => {
		const originalConfirm = window.confirm;
		window.confirm = jest.fn( () => false );
		try {
			loadSettings( resetMarkup() );
			const form = document.querySelector(
				'#hoursflow-settings-layout form'
			);
			const button = form.querySelector(
				'[data-hoursflow-reset-confirm]'
			);
			const result = dispatchSubmit( form, button );

			expect( button.type ).toBe( 'submit' );
			expect( button.hasAttribute( 'onclick' ) ).toBe( false );
			expect( window.confirm ).toHaveBeenCalledWith(
				'Are you sure you want to reset all settings to defaults?'
			);
			expect( result.event.defaultPrevented ).toBe( true );
			expect( result.dispatched ).toBe( false );
		} finally {
			window.confirm = originalConfirm;
		}
	} );

	test( 'allows reset submission when confirmation is accepted', () => {
		const originalConfirm = window.confirm;
		window.confirm = jest.fn( () => true );
		try {
			loadSettings( resetMarkup() );
			const form = document.querySelector(
				'#hoursflow-settings-layout form'
			);
			const button = form.querySelector(
				'[data-hoursflow-reset-confirm]'
			);
			const result = dispatchSubmit( form, button );

			expect( window.confirm ).toHaveBeenCalledWith(
				'Are you sure you want to reset all settings to defaults?'
			);
			expect( result.event.defaultPrevented ).toBe( false );
			expect( result.dispatched ).toBe( true );
		} finally {
			window.confirm = originalConfirm;
		}
	} );

	test( 'does not prompt when another submitter saves the form', () => {
		const originalConfirm = window.confirm;
		window.confirm = jest.fn( () => false );
		try {
			loadSettings( resetMarkup() );
			const form = document.querySelector(
				'#hoursflow-settings-layout form'
			);
			const saveButton = form.querySelector( '[data-hoursflow-save]' );
			const result = dispatchSubmit( form, saveButton );

			expect( window.confirm ).not.toHaveBeenCalled();
			expect( result.event.defaultPrevented ).toBe( false );
			expect( result.dispatched ).toBe( true );
		} finally {
			window.confirm = originalConfirm;
		}
	} );

	test( 'does not prompt when the reset button is absent', () => {
		const originalConfirm = window.confirm;
		window.confirm = jest.fn();
		try {
			loadSettings( resetMarkup( undefined, false ) );
			const form = document.querySelector(
				'#hoursflow-settings-layout form'
			);
			const saveButton = form.querySelector( '[data-hoursflow-save]' );
			const result = dispatchSubmit( form, saveButton );

			expect( window.confirm ).not.toHaveBeenCalled();
			expect( result.event.defaultPrevented ).toBe( false );
			expect( result.dispatched ).toBe( true );
		} finally {
			window.confirm = originalConfirm;
		}
	} );

	test( 'does not prompt when the reset message is blank', () => {
		const originalConfirm = window.confirm;
		window.confirm = jest.fn();
		try {
			loadSettings( resetMarkup( '   ' ) );
			const form = document.querySelector(
				'#hoursflow-settings-layout form'
			);
			const button = form.querySelector(
				'[data-hoursflow-reset-confirm]'
			);
			const result = dispatchSubmit( form, button );

			expect( window.confirm ).not.toHaveBeenCalled();
			expect( result.event.defaultPrevented ).toBe( false );
			expect( result.dispatched ).toBe( true );
		} finally {
			window.confirm = originalConfirm;
		}
	} );

	test( 'does not prompt when window.confirm is unavailable', () => {
		const originalConfirm = window.confirm;
		window.confirm = undefined;
		try {
			loadSettings( resetMarkup() );
			const form = document.querySelector(
				'#hoursflow-settings-layout form'
			);
			const button = form.querySelector(
				'[data-hoursflow-reset-confirm]'
			);
			const result = dispatchSubmit( form, button );

			expect( result.event.defaultPrevented ).toBe( false );
			expect( result.dispatched ).toBe( true );
		} finally {
			window.confirm = originalConfirm;
		}
	} );
} );

describe( 'HoursFlow live CTA preview', () => {
	test( 'initializes the open state from current fields and persisted colors', () => {
		loadSettings( previewMarkup() );

		const preview = document.querySelector( '[data-hoursflow-preview]' );
		const content = preview.querySelector(
			'[data-hoursflow-preview-content]'
		);

		expect( preview.dataset.hoursflowPreviewState ).toBe( 'open' );
		expect( content.dataset.hoursflowPreviewState ).toBe( 'open' );
		expect( content.classList.contains( 'hoursflow-cta--open' ) ).toBe(
			true
		);
		expect(
			content.querySelector( '[data-hoursflow-preview-label]' )
				.textContent
		).toBe( 'Call now' );
		expect(
			content.querySelector( '[data-hoursflow-preview-status]' )
				.textContent
		).toBe( 'Open today' );
		expect(
			content.querySelector( '[data-hoursflow-preview-status]' ).hidden
		).toBe( false );
		expect(
			content.style.getPropertyValue( '--hoursflow-cta-background-color' )
		).toBe( '#123456' );
		expect(
			content.style.getPropertyValue( '--hoursflow-cta-text-color' )
		).toBe( '#FEDCBA' );
	} );

	test( 'switches between current open and closed values with an announced pressed state', () => {
		loadSettings( previewMarkup() );

		const preview = document.querySelector( '[data-hoursflow-preview]' );
		const content = preview.querySelector(
			'[data-hoursflow-preview-content]'
		);
		const openButton = preview.querySelector(
			'[data-hoursflow-preview-state-button="open"]'
		);
		const closedButton = preview.querySelector(
			'[data-hoursflow-preview-state-button="closed"]'
		);

		closedButton.click();

		expect( openButton.getAttribute( 'aria-pressed' ) ).toBe( 'false' );
		expect( closedButton.getAttribute( 'aria-pressed' ) ).toBe( 'true' );
		expect( content.classList.contains( 'hoursflow-cta--closed' ) ).toBe(
			true
		);
		expect( content.classList.contains( 'hoursflow-cta--open' ) ).toBe(
			false
		);
		expect(
			content.querySelector( '[data-hoursflow-preview-label]' )
				.textContent
		).toBe( 'Book online' );
		expect(
			content.querySelector( '[data-hoursflow-preview-status]' )
				.textContent
		).toBe( 'Reopens tomorrow' );

		openButton.click();
		expect( openButton.getAttribute( 'aria-pressed' ) ).toBe( 'true' );
		expect( closedButton.getAttribute( 'aria-pressed' ) ).toBe( 'false' );
		expect(
			content.querySelector( '[data-hoursflow-preview-label]' )
				.textContent
		).toBe( 'Call now' );
	} );

	test( 'updates selected copy immediately and defers unselected copy until switching', () => {
		loadSettings( previewMarkup() );

		const label = document.querySelector(
			'[data-hoursflow-preview-label]'
		);
		const status = document.querySelector(
			'[data-hoursflow-preview-status]'
		);
		const openLabel = document.getElementById( 'hoursflow-cta-open-label' );
		const openStatus = document.getElementById(
			'hoursflow-cta-open-status'
		);
		const closedLabel = document.getElementById(
			'hoursflow-cta-closed-label'
		);

		openLabel.value = 'Phone us';
		dispatchInput( openLabel );
		expect( label.textContent ).toBe( 'Phone us' );

		openStatus.value = '   ';
		dispatchInput( openStatus, 'change' );
		expect( status.textContent ).toBe( '   ' );
		expect( status.hidden ).toBe( true );

		openStatus.value = 'Open late';
		dispatchInput( openStatus );
		expect( status.textContent ).toBe( 'Open late' );
		expect( status.hidden ).toBe( false );

		closedLabel.value = 'Reserve later';
		dispatchInput( closedLabel );
		expect( label.textContent ).toBe( 'Phone us' );

		document
			.querySelector( '[data-hoursflow-preview-state-button="closed"]' )
			.click();
		expect( label.textContent ).toBe( 'Reserve later' );
	} );

	test( 'uses canonical defaults for blank or malformed colors and accepts later valid colors', () => {
		loadSettings( previewMarkup() );

		const content = document.querySelector(
			'[data-hoursflow-preview-content]'
		);
		const background = document.getElementById(
			'hoursflow-appearance-background-color'
		);
		const text = document.getElementById(
			'hoursflow-appearance-text-color'
		);

		background.value = '';
		text.value = 'not-a-color';
		dispatchInput( background );
		dispatchInput( text, 'change' );

		expect(
			content.style.getPropertyValue( '--hoursflow-cta-background-color' )
		).toBe( '#166534' );
		expect(
			content.style.getPropertyValue( '--hoursflow-cta-text-color' )
		).toBe( '#FFFFFF' );

		background.value = '#ABCDEF';
		text.value = '#010203';
		dispatchInput( background );
		dispatchInput( text );
		expect(
			content.style.getPropertyValue( '--hoursflow-cta-background-color' )
		).toBe( '#ABCDEF' );
		expect(
			content.style.getPropertyValue( '--hoursflow-cta-text-color' )
		).toBe( '#010203' );
	} );

	test( 'treats administrator copy as text and never creates actionable preview markup', () => {
		loadSettings( previewMarkup() );

		const unsafeCopy = '<img src=x onerror="alert(1)">Call';
		const openLabel = document.getElementById( 'hoursflow-cta-open-label' );
		const label = document.querySelector(
			'[data-hoursflow-preview-label]'
		);
		const preview = document.querySelector( '[data-hoursflow-preview]' );

		openLabel.value = unsafeCopy;
		dispatchInput( openLabel );

		expect( label.textContent ).toBe( unsafeCopy );
		expect( label.querySelector( 'img' ) ).toBeNull();
		expect( preview.querySelector( 'a' ) ).toBeNull();
		expect( preview.querySelector( '[href]' ) ).toBeNull();
		expect( preview.textContent ).not.toContain( 'tel:+123456789' );
	} );

	test( 'tolerates missing preview children and source fields', () => {
		expect( () => {
			loadSettings( `
				<div data-hoursflow-preview data-hoursflow-preview-state="closed">
					<button data-hoursflow-preview-state-button="closed">Closed</button>
				</div>
			` );
			document
				.querySelector( '[data-hoursflow-preview-state-button]' )
				.click();
		} ).not.toThrow();
	} );

	test( 'keeps preview state changes isolated from weekly schedule controls', () => {
		loadSettings( scheduleMarkup() + previewMarkup() );

		const monday = document.querySelector(
			'[data-hoursflow-schedule-day="monday"]'
		);
		const toggle = monday.querySelector( '[data-hoursflow-closed-toggle]' );
		const opens = monday.querySelector( '#monday-opens' );
		const stateText = monday.querySelector(
			'[data-hoursflow-schedule-state-text]'
		);

		document
			.querySelector( '[data-hoursflow-preview-state-button="closed"]' )
			.click();

		expect( toggle.checked ).toBe( false );
		expect( opens.disabled ).toBe( false );
		expect( opens.required ).toBe( true );
		expect( monday.dataset.hoursflowScheduleState ).toBe( 'open' );
		expect( stateText.textContent ).toBe( 'Abierto' );
	} );
} );
