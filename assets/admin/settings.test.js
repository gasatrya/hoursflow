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

function loadSettings() {
	jest.resetModules();
	document.body.innerHTML = scheduleMarkup();
	require( './settings' );
	document.dispatchEvent( new Event( 'DOMContentLoaded' ) );
}

describe( 'OpenNow settings schedule behavior', () => {
	test( 'initializes each fieldset from its own translated state data', () => {
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
		expect(
			tuesday.querySelector( '[data-opennow-schedule-state-text]' )
				.textContent
		).toBe( 'Cerrado' );

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
		expect( monday.querySelector( '#monday-opens' ).disabled ).toBe( true );
	} );
} );
