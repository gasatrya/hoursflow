import {
	createBlock,
	getBlockType,
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
} );
