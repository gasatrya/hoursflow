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
} );
