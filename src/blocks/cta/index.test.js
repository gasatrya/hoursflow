import { useBlockProps } from '@wordpress/block-editor';
import { registerBlockType } from '@wordpress/blocks';
import {
	Disabled,
	PanelBody,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import { __ } from '@wordpress/i18n';

import metadata from './block.json';
import { Edit, Save } from './index';

jest.mock(
	'@wordpress/block-editor',
	() => ( {
		InspectorControls: jest.fn(),
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
		Disabled: jest.fn(),
		PanelBody: jest.fn(),
		TextControl: jest.fn(),
		ToggleControl: jest.fn(),
	} ),
	{ virtual: true }
);
jest.mock(
	'@wordpress/server-side-render',
	() => ( {
		__esModule: true,
		default: jest.fn(),
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
jest.mock( '../../../assets/public/cta.css', () => ( {} ), {
	virtual: true,
} );
jest.mock( './editor.scss', () => ( {} ), { virtual: true } );

function childrenOf( element ) {
	return Array.isArray( element.props.children )
		? element.props.children
		: [ element.props.children ];
}

function panelsFrom( element ) {
	const inspector = childrenOf( element )[ 0 ];
	return childrenOf( inspector );
}

function controlsFrom( panel ) {
	const controls = panel.props.children;
	return controls.type( controls.props ).props.children;
}

describe( 'OpenNow CTA block', () => {
	test( 'registers metadata with an editor and null serializer', () => {
		expect( registerBlockType ).toHaveBeenCalledTimes( 1 );
		expect( registerBlockType ).toHaveBeenCalledWith( metadata, {
			edit: Edit,
			save: Save,
		} );
		expect( Save() ).toBeNull();
	} );

	test( 'renders the current server output with block attributes', () => {
		const attributes = {
			overrides: {
				open: {
					label: 'Custom label',
				},
			},
		};
		const element = Edit( { attributes, setAttributes: jest.fn() } );
		const wrapper = childrenOf( element )[ 1 ];
		const previewGuard = wrapper.props.children;
		const preview = previewGuard.props.children;

		expect( previewGuard.type ).toBe( Disabled );
		expect( preview.type ).toBe( ServerSideRender );
		expect( preview.props.block ).toBe( 'opennow/cta' );
		expect( preview.props.attributes ).toEqual( attributes );
		expect( preview.props.httpMethod ).toBe( 'POST' );
		expect( useBlockProps ).toHaveBeenCalledWith();
	} );

	test( 'passes color and typography support attributes to the server preview', () => {
		const attributes = {
			fontFamily: 'heading',
			fontSize: 'large',
			textColor: 'contrast',
			style: {
				color: {
					background: '#123456',
				},
				typography: {
					fontWeight: '700',
					letterSpacing: '0.05em',
					lineHeight: '1.4',
				},
			},
		};
		const element = Edit( { attributes, setAttributes: jest.fn() } );
		const preview =
			childrenOf( element )[ 1 ].props.children.props.children;

		expect( preview.props.attributes ).toEqual( attributes );
	} );

	test( 'groups translated open and closed field controls in inspector panels', () => {
		const element = Edit( { attributes: {}, setAttributes: jest.fn() } );
		const panels = panelsFrom( element );

		expect( panels ).toHaveLength( 2 );
		expect( panels[ 0 ].type ).toBe( PanelBody );
		expect( panels[ 1 ].type ).toBe( PanelBody );
		expect( panels[ 0 ].props.title ).toBe( 'Open CTA' );
		expect( panels[ 1 ].props.title ).toBe( 'Closed CTA' );

		const openControls = controlsFrom( panels[ 0 ] ).filter( Boolean );
		expect(
			openControls.filter( ( control ) => control.type === ToggleControl )
		).toHaveLength( 4 );
		expect(
			openControls.filter( ( control ) => control.type === TextControl )
		).toHaveLength( 0 );
		expect( __ ).toHaveBeenCalledWith( 'Override label', 'opennow' );
		expect( __ ).toHaveBeenCalledWith( 'Override action', 'opennow' );
		expect( __ ).toHaveBeenCalledWith( 'Override status', 'opennow' );
		expect( __ ).toHaveBeenCalledWith( 'Hide status', 'opennow' );
	} );

	test( 'provides translated guidance for each editable override field', () => {
		const element = Edit( {
			attributes: {
				overrides: {
					open: {
						label: '',
						action: '',
						status: '',
					},
				},
			},
			setAttributes: jest.fn(),
		} );
		const textControls = controlsFrom( panelsFrom( element )[ 0 ] ).filter(
			( control ) => control && control.type === TextControl
		);

		expect( textControls.map( ( control ) => control.props.help ) ).toEqual(
			[
				'Enter non-empty plain text. Invalid or empty values fall back to the global label.',
				'Enter a root-relative URL, HTTPS URL, or tel: action. Invalid or empty values fall back to the global action.',
				'Enter plain text. A blank value explicitly hides the global status; the Hide status control hides it regardless of its value. Invalid values fall back to the global status.',
			]
		);
		expect( __ ).toHaveBeenCalledWith(
			'Enter non-empty plain text. Invalid or empty values fall back to the global label.',
			'opennow'
		);
		expect( __ ).toHaveBeenCalledWith(
			'Enter a root-relative URL, HTTPS URL, or tel: action. Invalid or empty values fall back to the global action.',
			'opennow'
		);
		expect( __ ).toHaveBeenCalledWith(
			'Enter plain text. A blank value explicitly hides the global status; the Hide status control hides it regardless of its value. Invalid values fall back to the global status.',
			'opennow'
		);
	} );

	test( 'toggle and text updates add, preserve, and remove sparse fields immutably', () => {
		const attributes = {
			overrides: {
				open: {
					label: 'Custom label',
					status: '',
				},
			},
		};
		const setAttributes = jest.fn();
		const element = Edit( { attributes, setAttributes } );
		const controls = controlsFrom( panelsFrom( element )[ 0 ] ).filter(
			Boolean
		);
		const toggles = controls.filter(
			( control ) => control.type === ToggleControl
		);
		const textControls = controls.filter(
			( control ) => control.type === TextControl
		);

		expect( toggles.map( ( control ) => control.props.checked ) ).toEqual( [
			true,
			false,
			true,
			false,
		] );
		expect(
			textControls.map( ( control ) => control.props.value )
		).toEqual( [ 'Custom label', '' ] );

		textControls[ 1 ].props.onChange( '' );
		expect( setAttributes ).toHaveBeenLastCalledWith( {
			overrides: {
				open: {
					label: 'Custom label',
					status: '',
				},
			},
		} );

		toggles[ 1 ].props.onChange( true );
		expect( setAttributes ).toHaveBeenLastCalledWith( {
			overrides: {
				open: {
					label: 'Custom label',
					status: '',
					action: '',
				},
			},
		} );

		toggles[ 0 ].props.onChange( false );
		expect( setAttributes ).toHaveBeenLastCalledWith( {
			overrides: {
				open: {
					status: '',
				},
			},
		} );
	} );

	test( 'hide status toggle stores true and removes only that field', () => {
		const attributes = {
			overrides: {
				open: {
					label: 'Custom label',
					status: 'Custom status',
				},
			},
		};
		const enableSetAttributes = jest.fn();
		const enabledElement = Edit( {
			attributes,
			setAttributes: enableSetAttributes,
		} );
		const enabledControls = controlsFrom(
			panelsFrom( enabledElement )[ 0 ]
		).filter( Boolean );
		const hideStatusToggle = enabledControls.find(
			( control ) =>
				control.type === ToggleControl &&
				control.props.label === 'Hide status'
		);

		expect( hideStatusToggle.props.checked ).toBe( false );
		hideStatusToggle.props.onChange( true );
		expect( enableSetAttributes ).toHaveBeenCalledWith( {
			overrides: {
				open: {
					label: 'Custom label',
					status: 'Custom status',
					hideStatus: true,
				},
			},
		} );

		const disableSetAttributes = jest.fn();
		const disabledElement = Edit( {
			attributes: {
				overrides: {
					open: {
						label: 'Custom label',
						status: 'Custom status',
						hideStatus: true,
					},
				},
			},
			setAttributes: disableSetAttributes,
		} );
		const disabledControls = controlsFrom(
			panelsFrom( disabledElement )[ 0 ]
		).filter( Boolean );
		disabledControls
			.find(
				( control ) =>
					control.type === ToggleControl &&
					control.props.label === 'Hide status'
			)
			.props.onChange( false );

		expect( disableSetAttributes ).toHaveBeenCalledWith( {
			overrides: {
				open: {
					label: 'Custom label',
					status: 'Custom status',
				},
			},
		} );
	} );

	test( 'prunes an empty state and overrides object after hiding is disabled', () => {
		const setAttributes = jest.fn();
		const element = Edit( {
			attributes: { overrides: { closed: { hideStatus: true } } },
			setAttributes,
		} );
		const controls = controlsFrom( panelsFrom( element )[ 1 ] ).filter(
			Boolean
		);
		const hideStatusToggle = controls.find(
			( control ) =>
				control.type === ToggleControl &&
				control.props.label === 'Hide status'
		);

		hideStatusToggle.props.onChange( false );

		expect( setAttributes ).toHaveBeenCalledWith( {
			overrides: undefined,
		} );
	} );

	test( 'prunes an empty state and overrides object after the last field is disabled', () => {
		const setAttributes = jest.fn();
		const element = Edit( {
			attributes: { overrides: { closed: { status: '' } } },
			setAttributes,
		} );
		const controls = controlsFrom( panelsFrom( element )[ 1 ] ).filter(
			Boolean
		);
		const statusToggle = controls.find(
			( control ) =>
				control.type === ToggleControl && control.props.checked === true
		);

		statusToggle.props.onChange( false );

		expect( setAttributes ).toHaveBeenCalledWith( {
			overrides: undefined,
		} );
	} );

	test( 'canonicalizes malformed attributes on update without changing preview input', () => {
		const malformed = {
			overrides: {
				open: {
					label: 'Keep open',
					action: 42,
					hideStatus: '1',
					unknown: 'drop this field',
				},
				closed: 'not an object',
				unknown: { status: 'drop this state' },
			},
		};
		const setAttributes = jest.fn();
		expect( () => Edit( null ) ).not.toThrow();

		const element = Edit( { attributes: malformed, setAttributes } );
		const preview =
			childrenOf( element )[ 1 ].props.children.props.children;
		expect( preview.props.attributes ).toEqual( {
			overrides: malformed.overrides,
		} );
		expect( preview.props.attributes.overrides ).toBe(
			malformed.overrides
		);
		expect( malformed ).toEqual( {
			overrides: {
				open: {
					label: 'Keep open',
					action: 42,
					hideStatus: '1',
					unknown: 'drop this field',
				},
				closed: 'not an object',
				unknown: { status: 'drop this state' },
			},
		} );

		const openControls = controlsFrom( panelsFrom( element )[ 0 ] ).filter(
			Boolean
		);
		expect(
			openControls
				.filter( ( control ) => control.type === ToggleControl )
				.map( ( control ) => control.props.checked )
		).toEqual( [ true, false, false, false ] );

		openControls
			.filter( ( control ) => control.type === ToggleControl )[ 1 ]
			.props.onChange( true );
		expect( setAttributes ).toHaveBeenCalledWith( {
			overrides: {
				open: {
					label: 'Keep open',
					action: '',
				},
			},
		} );
	} );

	test( 'preserves valid sibling-state overrides during canonical updates', () => {
		const attributes = {
			overrides: {
				open: {
					label: 'Open label',
					status: 123,
					unknown: 'discard',
				},
				closed: {
					label: 'Closed label',
					action: '/closed/',
					status: '',
				},
				unknown: {
					label: 'discard',
				},
			},
		};
		const setAttributes = jest.fn();
		const element = Edit( { attributes, setAttributes } );
		const openControls = controlsFrom( panelsFrom( element )[ 0 ] ).filter(
			Boolean
		);
		const labelControl = openControls.find(
			( control ) =>
				control.type === TextControl &&
				control.props.value === 'Open label'
		);

		labelControl.props.onChange( 'Updated open label' );

		expect( setAttributes ).toHaveBeenCalledWith( {
			overrides: {
				open: { label: 'Updated open label' },
				closed: {
					label: 'Closed label',
					action: '/closed/',
					status: '',
				},
			},
		} );
	} );

	test( 'has the expected metadata attributes and dynamic serializer', () => {
		expect( metadata.attributes ).toEqual( {
			overrides: { type: 'object' },
		} );
		expect( metadata.editorStyle ).toBe( 'file:./index.css' );
		expect( metadata.supports.color ).toEqual( {
			text: true,
			background: true,
			__experimentalSkipSerialization: true,
			__experimentalDefaultControls: {
				text: true,
				background: true,
			},
		} );
		expect( metadata.supports.typography ).toEqual( {
			fontSize: true,
			lineHeight: true,
			__experimentalFontFamily: true,
			__experimentalFontWeight: true,
			__experimentalFontStyle: true,
			__experimentalTextTransform: true,
			__experimentalLetterSpacing: true,
			__experimentalDefaultControls: {
				fontSize: true,
			},
		} );
		expect( Save() ).toBeNull();
	} );
} );
