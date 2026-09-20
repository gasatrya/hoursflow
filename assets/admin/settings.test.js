function scheduleMarkup() {
	return `
		<div id="opennow-schedule">
			<fieldset
				class="opennow-schedule-day opennow-schedule-day--open"
				data-opennow-schedule-day="monday"
				data-opennow-schedule-state="open"
				data-opennow-open-label="Abierto"
				data-opennow-closed-label="Cerrado"
			>
				<span data-opennow-schedule-state-text="1">Abierto</span>
				<input type="checkbox" data-opennow-closed-toggle="1" />
				<input id="monday-opens" data-opennow-time-input="1" />
				<input id="monday-closes" data-opennow-time-input="1" />
			</fieldset>
			<fieldset
				class="opennow-schedule-day opennow-schedule-day--closed"
				data-opennow-schedule-day="tuesday"
				data-opennow-schedule-state="closed"
				data-opennow-open-label="Abierto"
				data-opennow-closed-label="Cerrado"
			>
				<span data-opennow-schedule-state-text="1">Cerrado</span>
				<input type="checkbox" data-opennow-closed-toggle="1" checked />
				<input id="tuesday-opens" data-opennow-time-input="1" />
				<input id="tuesday-closes" data-opennow-time-input="1" />
			</fieldset>
			<input id="outside-time" data-opennow-time-input="1" required />
		</div>
	`;
}

function previewMarkup() {
	return `
		<input id="opennow-cta-open-label" value="Call now" />
		<input id="opennow-cta-open-status" value="Open today" />
		<input id="opennow-cta-open-action" value="tel:+123456789" />
		<input id="opennow-cta-closed-label" value="Book online" />
		<input id="opennow-cta-closed-status" value="Reopens tomorrow" />
		<input id="opennow-cta-closed-action" value="/booking/" />
		<input id="opennow-appearance-background-color" value="#123456" />
		<input id="opennow-appearance-text-color" value="#FEDCBA" />
		<aside
			id="opennow-cta-preview"
			data-opennow-preview="1"
			data-opennow-preview-state="open"
			data-opennow-preview-default-background-color="#166534"
			data-opennow-preview-default-text-color="#FFFFFF"
		>
			<button type="button" data-opennow-preview-state-button="open" aria-pressed="true">Open</button>
			<button type="button" data-opennow-preview-state-button="closed" aria-pressed="false">Closed</button>
			<div
				id="opennow-cta-preview-content"
				class="opennow-cta opennow-cta--open"
				data-opennow-preview-content="1"
				data-opennow-preview-state="open"
			>
				<span data-opennow-preview-label="1"></span>
				<span data-opennow-preview-status="1"></span>
			</div>
		</aside>
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

describe( 'OpenNow settings schedule behavior', () => {
	test( 'initializes each fieldset from its own translated state data without preview markup', () => {
		loadSettings();

		const monday = document.querySelector(
			'[data-opennow-schedule-day="monday"]'
		);
		const tuesday = document.querySelector(
			'[data-opennow-schedule-day="tuesday"]'
		);

		expect( monday.dataset.opennowScheduleState ).toBe( 'open' );
		expect(
			monday.classList.contains( 'opennow-schedule-day--open' )
		).toBe( true );
		expect(
			monday.querySelector( '[data-opennow-schedule-state-text]' )
				.textContent
		).toBe( 'Abierto' );
		expect( monday.querySelector( '#monday-opens' ).required ).toBe( true );
		expect( monday.querySelector( '#monday-opens' ).disabled ).toBe(
			false
		);
		expect( monday.querySelector( '#monday-closes' ).required ).toBe(
			true
		);

		expect( tuesday.dataset.opennowScheduleState ).toBe( 'closed' );
		expect(
			tuesday.classList.contains( 'opennow-schedule-day--closed' )
		).toBe( true );
		expect(
			tuesday.querySelector( '[data-opennow-schedule-state-text]' )
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
			'[data-opennow-schedule-day="monday"]'
		);
		const tuesday = document.querySelector(
			'[data-opennow-schedule-day="tuesday"]'
		);
		const mondayToggle = monday.querySelector(
			'[data-opennow-closed-toggle]'
		);
		const tuesdayToggle = tuesday.querySelector(
			'[data-opennow-closed-toggle]'
		);

		mondayToggle.checked = true;
		mondayToggle.dispatchEvent( new Event( 'change' ) );

		expect( monday.dataset.opennowScheduleState ).toBe( 'closed' );
		expect(
			monday.classList.contains( 'opennow-schedule-day--closed' )
		).toBe( true );
		expect(
			monday.classList.contains( 'opennow-schedule-day--open' )
		).toBe( false );
		expect(
			monday.querySelector( '[data-opennow-schedule-state-text]' )
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

		expect( tuesday.dataset.opennowScheduleState ).toBe( 'closed' );
		expect( tuesday.querySelector( '#tuesday-opens' ).disabled ).toBe(
			true
		);
		expect( tuesday.querySelector( '#tuesday-opens' ).required ).toBe(
			false
		);

		tuesdayToggle.checked = false;
		tuesdayToggle.dispatchEvent( new Event( 'change' ) );

		expect( tuesday.dataset.opennowScheduleState ).toBe( 'open' );
		expect(
			tuesday.classList.contains( 'opennow-schedule-day--open' )
		).toBe( true );
		expect(
			tuesday.querySelector( '[data-opennow-schedule-state-text]' )
				.textContent
		).toBe( 'Abierto' );
		expect( tuesday.querySelector( '#tuesday-opens' ).disabled ).toBe(
			false
		);
		expect( tuesday.querySelector( '#tuesday-opens' ).required ).toBe(
			true
		);
		expect( monday.dataset.opennowScheduleState ).toBe( 'closed' );
	} );
} );

describe( 'OpenNow live CTA preview', () => {
	test( 'initializes the open state from current fields and persisted colors', () => {
		loadSettings( previewMarkup() );

		const preview = document.querySelector( '[data-opennow-preview]' );
		const content = preview.querySelector(
			'[data-opennow-preview-content]'
		);

		expect( preview.dataset.opennowPreviewState ).toBe( 'open' );
		expect( content.dataset.opennowPreviewState ).toBe( 'open' );
		expect( content.classList.contains( 'opennow-cta--open' ) ).toBe(
			true
		);
		expect(
			content.querySelector( '[data-opennow-preview-label]' ).textContent
		).toBe( 'Call now' );
		expect(
			content.querySelector( '[data-opennow-preview-status]' ).textContent
		).toBe( 'Open today' );
		expect(
			content.querySelector( '[data-opennow-preview-status]' ).hidden
		).toBe( false );
		expect(
			content.style.getPropertyValue( '--opennow-cta-background-color' )
		).toBe( '#123456' );
		expect(
			content.style.getPropertyValue( '--opennow-cta-text-color' )
		).toBe( '#FEDCBA' );
	} );

	test( 'switches between current open and closed values with an announced pressed state', () => {
		loadSettings( previewMarkup() );

		const preview = document.querySelector( '[data-opennow-preview]' );
		const content = preview.querySelector(
			'[data-opennow-preview-content]'
		);
		const openButton = preview.querySelector(
			'[data-opennow-preview-state-button="open"]'
		);
		const closedButton = preview.querySelector(
			'[data-opennow-preview-state-button="closed"]'
		);

		closedButton.click();

		expect( openButton.getAttribute( 'aria-pressed' ) ).toBe( 'false' );
		expect( closedButton.getAttribute( 'aria-pressed' ) ).toBe( 'true' );
		expect( content.classList.contains( 'opennow-cta--closed' ) ).toBe(
			true
		);
		expect( content.classList.contains( 'opennow-cta--open' ) ).toBe(
			false
		);
		expect(
			content.querySelector( '[data-opennow-preview-label]' ).textContent
		).toBe( 'Book online' );
		expect(
			content.querySelector( '[data-opennow-preview-status]' ).textContent
		).toBe( 'Reopens tomorrow' );

		openButton.click();
		expect( openButton.getAttribute( 'aria-pressed' ) ).toBe( 'true' );
		expect( closedButton.getAttribute( 'aria-pressed' ) ).toBe( 'false' );
		expect(
			content.querySelector( '[data-opennow-preview-label]' ).textContent
		).toBe( 'Call now' );
	} );

	test( 'updates selected copy immediately and defers unselected copy until switching', () => {
		loadSettings( previewMarkup() );

		const label = document.querySelector( '[data-opennow-preview-label]' );
		const status = document.querySelector(
			'[data-opennow-preview-status]'
		);
		const openLabel = document.getElementById( 'opennow-cta-open-label' );
		const openStatus = document.getElementById( 'opennow-cta-open-status' );
		const closedLabel = document.getElementById(
			'opennow-cta-closed-label'
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
			.querySelector( '[data-opennow-preview-state-button="closed"]' )
			.click();
		expect( label.textContent ).toBe( 'Reserve later' );
	} );

	test( 'uses canonical defaults for blank or malformed colors and accepts later valid colors', () => {
		loadSettings( previewMarkup() );

		const content = document.querySelector(
			'[data-opennow-preview-content]'
		);
		const background = document.getElementById(
			'opennow-appearance-background-color'
		);
		const text = document.getElementById( 'opennow-appearance-text-color' );

		background.value = '';
		text.value = 'not-a-color';
		dispatchInput( background );
		dispatchInput( text, 'change' );

		expect(
			content.style.getPropertyValue( '--opennow-cta-background-color' )
		).toBe( '#166534' );
		expect(
			content.style.getPropertyValue( '--opennow-cta-text-color' )
		).toBe( '#FFFFFF' );

		background.value = '#ABCDEF';
		text.value = '#010203';
		dispatchInput( background );
		dispatchInput( text );
		expect(
			content.style.getPropertyValue( '--opennow-cta-background-color' )
		).toBe( '#ABCDEF' );
		expect(
			content.style.getPropertyValue( '--opennow-cta-text-color' )
		).toBe( '#010203' );
	} );

	test( 'treats administrator copy as text and never creates actionable preview markup', () => {
		loadSettings( previewMarkup() );

		const unsafeCopy = '<img src=x onerror="alert(1)">Call';
		const openLabel = document.getElementById( 'opennow-cta-open-label' );
		const label = document.querySelector( '[data-opennow-preview-label]' );
		const preview = document.querySelector( '[data-opennow-preview]' );

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
				<div data-opennow-preview data-opennow-preview-state="closed">
					<button data-opennow-preview-state-button="closed">Closed</button>
				</div>
			` );
			document
				.querySelector( '[data-opennow-preview-state-button]' )
				.click();
		} ).not.toThrow();
	} );

	test( 'keeps preview state changes isolated from weekly schedule controls', () => {
		loadSettings( scheduleMarkup() + previewMarkup() );

		const monday = document.querySelector(
			'[data-opennow-schedule-day="monday"]'
		);
		const toggle = monday.querySelector( '[data-opennow-closed-toggle]' );
		const opens = monday.querySelector( '#monday-opens' );
		const stateText = monday.querySelector(
			'[data-opennow-schedule-state-text]'
		);

		document
			.querySelector( '[data-opennow-preview-state-button="closed"]' )
			.click();

		expect( toggle.checked ).toBe( false );
		expect( opens.disabled ).toBe( false );
		expect( opens.required ).toBe( true );
		expect( monday.dataset.opennowScheduleState ).toBe( 'open' );
		expect( stateText.textContent ).toBe( 'Abierto' );
	} );
} );
