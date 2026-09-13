import { useBlockProps } from '@wordpress/block-editor';
import { registerBlockType } from '@wordpress/blocks';
import { Placeholder } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import metadata from './block.json';
import { Edit, Save } from './index';

jest.mock(
	'@wordpress/block-editor',
	() => ( {
		useBlockProps: jest.fn( () => ( {
			className: 'wp-block-opennow-cta',
		} ) ),
	} ),
	{ virtual: true }
);
jest.mock(
	'@wordpress/blocks',
	() => ( {
		registerBlockType: jest.fn(),
	} ),
	{ virtual: true }
);
jest.mock(
	'@wordpress/components',
	() => ( {
		Placeholder: jest.fn(),
	} ),
	{ virtual: true }
);
jest.mock(
	'@wordpress/i18n',
	() => ( {
		__: jest.fn( ( text ) => text ),
	} ),
	{ virtual: true }
);

describe( 'OpenNow CTA block', () => {
	test( 'registers metadata with an editor and null serializer', () => {
		expect( registerBlockType ).toHaveBeenCalledTimes( 1 );
		expect( registerBlockType ).toHaveBeenCalledWith( metadata, {
			edit: Edit,
			save: Save,
		} );
		expect( Save() ).toBeNull();
	} );

	test( 'uses block props and a translated global-state placeholder', () => {
		const element = Edit();
		const placeholder = element.props.children;

		expect( useBlockProps ).toHaveBeenCalledWith();
		expect( element.type ).toBe( 'div' );
		expect( placeholder.type ).toBe( Placeholder );
		expect( placeholder.props.label ).toBe( 'OpenNow CTA' );
		expect( placeholder.props.instructions ).toContain(
			'global OpenNow configuration'
		);
		expect( placeholder.props.instructions ).toContain(
			'The output uses the global OpenNow configuration'
		);
		expect( placeholder.props.instructions ).toContain(
			'current business state'
		);
		expect( __ ).toHaveBeenCalledWith( 'OpenNow CTA', 'opennow' );
		expect( __ ).toHaveBeenCalledWith(
			'The output uses the global OpenNow configuration and the current business state.',
			'opennow'
		);
	} );

	test( 'has no serialized attributes or presentation supports', () => {
		expect( metadata ).not.toHaveProperty( 'attributes' );
		expect( metadata.supports ).toEqual( {
			html: false,
			customClassName: false,
		} );
		expect( Save() ).toBeNull();
	} );
} );
