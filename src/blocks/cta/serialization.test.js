import {
	createBlock,
	getBlockType,
	parse,
	registerBlockType,
	serialize,
	unregisterBlockType,
} from '@wordpress/blocks';

import metadata from './block.json';

describe( 'OpenNow CTA serialization', () => {
	beforeAll( () => {
		registerBlockType( metadata, {
			attributes: {
				...metadata.attributes,
				backgroundColor: { type: 'string' },
				fontFamily: { type: 'string' },
				fontSize: { type: 'string' },
				style: { type: 'object' },
				textColor: { type: 'string' },
			},
			edit: () => null,
			save: () => null,
		} );
	} );

	afterAll( () => {
		if ( getBlockType( metadata.name ) ) {
			unregisterBlockType( metadata.name );
		}
	} );

	test( 'stores only the dynamic block delimiter', () => {
		const block = createBlock( metadata.name );

		expect( block.attributes ).toEqual( {} );
		expect( serialize( block ) ).toBe( '<!-- wp:opennow/cta /-->' );
	} );

	test( 'serializes sparse overrides including an explicitly blank status and round-trips them', () => {
		const attributes = {
			overrides: {
				open: {
					label: 'Call this block',
					status: '',
				},
			},
		};
		const block = createBlock( metadata.name, attributes );
		const serialized = serialize( block );

		expect( block.attributes ).toEqual( attributes );
		expect( serialized ).toBe(
			'<!-- wp:opennow/cta {"overrides":{"open":{"label":"Call this block","status":""}}} /-->'
		);
		expect( parse( serialized )[ 0 ].attributes ).toEqual( attributes );
	} );

	test( 'serializes color support attributes and round-trips them', () => {
		const attributes = {
			textColor: 'contrast',
			style: {
				color: {
					background: '#123456',
				},
			},
		};
		const block = createBlock( metadata.name, attributes );
		const serialized = serialize( block );

		expect( block.attributes ).toEqual( attributes );
		expect( parse( serialized )[ 0 ].attributes ).toEqual( attributes );
	} );

	test( 'serializes typography support attributes and round-trips them', () => {
		const attributes = {
			fontFamily: 'heading',
			fontSize: 'large',
			style: {
				typography: {
					fontWeight: '700',
					letterSpacing: '0.05em',
					lineHeight: '1.4',
				},
			},
		};
		const block = createBlock( metadata.name, attributes );
		const serialized = serialize( block );

		expect( block.attributes ).toEqual( attributes );
		expect( parse( serialized )[ 0 ].attributes ).toEqual( attributes );
	} );

	test( 'serializes strict per-state hideStatus overrides and round-trips them', () => {
		const attributes = {
			overrides: {
				open: {
					hideStatus: true,
				},
				closed: {
					hideStatus: true,
				},
			},
		};
		const block = createBlock( metadata.name, attributes );
		const serialized = serialize( block );

		expect( block.attributes ).toEqual( attributes );
		expect( serialized ).toBe(
			'<!-- wp:opennow/cta {"overrides":{"open":{"hideStatus":true},"closed":{"hideStatus":true}}} /-->'
		);
		expect( parse( serialized )[ 0 ].attributes ).toEqual( attributes );
	} );
} );
