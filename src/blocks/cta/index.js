import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
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
import '../../../assets/public/cta.css';
import './editor.scss';

function isObject( value ) {
	return (
		null !== value && 'object' === typeof value && ! Array.isArray( value )
	);
}

function hasOwn( object, property ) {
	return Object.prototype.hasOwnProperty.call( object, property );
}

function canonicalizeOverrides( value ) {
	if ( ! isObject( value ) ) {
		return {};
	}

	const canonical = {};
	[ 'open', 'closed' ].forEach( ( state ) => {
		if ( ! hasOwn( value, state ) || ! isObject( value[ state ] ) ) {
			return;
		}

		const stateOverrides = {};
		[ 'label', 'action', 'status' ].forEach( ( field ) => {
			if (
				hasOwn( value[ state ], field ) &&
				'string' === typeof value[ state ][ field ]
			) {
				stateOverrides[ field ] = value[ state ][ field ];
			}
		} );

		if ( Object.keys( stateOverrides ).length > 0 ) {
			canonical[ state ] = stateOverrides;
		}
	} );

	return canonical;
}

function getAttributes( attributes ) {
	return isObject( attributes ) ? attributes : {};
}

function getOverrides( attributes ) {
	return canonicalizeOverrides( getAttributes( attributes ).overrides );
}

function getStateOverrides( attributes, state ) {
	const candidate = getOverrides( attributes )[ state ];

	return isObject( candidate ) ? candidate : {};
}

function hasOverride( stateOverrides, field ) {
	return hasOwn( stateOverrides, field );
}

function getOverrideValue( stateOverrides, field ) {
	return 'string' === typeof stateOverrides[ field ]
		? stateOverrides[ field ]
		: '';
}

function updateOverride(
	attributes,
	setAttributes,
	state,
	field,
	enabled,
	value = ''
) {
	if ( 'function' !== typeof setAttributes ) {
		return;
	}

	const nextOverrides = { ...getOverrides( attributes ) };
	const currentState = nextOverrides[ state ];

	if ( enabled ) {
		const nextState = isObject( currentState ) ? { ...currentState } : {};
		nextState[ field ] = 'string' === typeof value ? value : '';
		nextOverrides[ state ] = nextState;
	} else if ( isObject( currentState ) ) {
		const nextState = { ...currentState };
		delete nextState[ field ];
		if ( Object.keys( nextState ).length > 0 ) {
			nextOverrides[ state ] = nextState;
		} else {
			delete nextOverrides[ state ];
		}
	} else {
		delete nextOverrides[ state ];
	}

	setAttributes( {
		overrides:
			Object.keys( nextOverrides ).length > 0 ? nextOverrides : undefined,
	} );
}

function CtaOverrideControls( { attributes, setAttributes, state } ) {
	const stateOverrides = getStateOverrides( attributes, state );
	const labelEnabled = hasOverride( stateOverrides, 'label' );
	const actionEnabled = hasOverride( stateOverrides, 'action' );
	const statusEnabled = hasOverride( stateOverrides, 'status' );

	return (
		<>
			<ToggleControl
				label={ __( 'Override label', 'opennow' ) }
				checked={ labelEnabled }
				onChange={ ( enabled ) =>
					updateOverride(
						attributes,
						setAttributes,
						state,
						'label',
						enabled
					)
				}
			/>
			{ labelEnabled && (
				<TextControl
					label={ __( 'Label', 'opennow' ) }
					help={ __(
						'Enter non-empty plain text. Invalid or empty values fall back to the global label.',
						'opennow'
					) }
					value={ getOverrideValue( stateOverrides, 'label' ) }
					onChange={ ( value ) =>
						updateOverride(
							attributes,
							setAttributes,
							state,
							'label',
							true,
							value
						)
					}
				/>
			) }

			<ToggleControl
				label={ __( 'Override action', 'opennow' ) }
				checked={ actionEnabled }
				onChange={ ( enabled ) =>
					updateOverride(
						attributes,
						setAttributes,
						state,
						'action',
						enabled
					)
				}
			/>
			{ actionEnabled && (
				<TextControl
					label={ __( 'Action', 'opennow' ) }
					help={ __(
						'Enter a root-relative URL, HTTPS URL, or tel: action. Invalid or empty values fall back to the global action.',
						'opennow'
					) }
					value={ getOverrideValue( stateOverrides, 'action' ) }
					onChange={ ( value ) =>
						updateOverride(
							attributes,
							setAttributes,
							state,
							'action',
							true,
							value
						)
					}
				/>
			) }

			<ToggleControl
				label={ __( 'Override status', 'opennow' ) }
				checked={ statusEnabled }
				onChange={ ( enabled ) =>
					updateOverride(
						attributes,
						setAttributes,
						state,
						'status',
						enabled
					)
				}
			/>
			{ statusEnabled && (
				<TextControl
					label={ __( 'Status', 'opennow' ) }
					help={ __(
						'Enter plain text. A blank value explicitly hides the global status; invalid values fall back to the global status.',
						'opennow'
					) }
					value={ getOverrideValue( stateOverrides, 'status' ) }
					onChange={ ( value ) =>
						updateOverride(
							attributes,
							setAttributes,
							state,
							'status',
							true,
							value
						)
					}
				/>
			) }
		</>
	);
}

function getServerAttributes( attributes ) {
	const safeAttributes = getAttributes( attributes );

	if ( ! isObject( safeAttributes.overrides ) ) {
		return {};
	}

	return { overrides: safeAttributes.overrides };
}

export function Edit( props = {} ) {
	const safeProps = isObject( props ) ? props : {};
	const attributes = safeProps.attributes;
	const setAttributes = safeProps.setAttributes;
	const blockProps = useBlockProps();
	const serverAttributes = getServerAttributes( attributes );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Open CTA', 'opennow' ) }>
					<CtaOverrideControls
						attributes={ attributes }
						setAttributes={ setAttributes }
						state="open"
					/>
				</PanelBody>
				<PanelBody title={ __( 'Closed CTA', 'opennow' ) }>
					<CtaOverrideControls
						attributes={ attributes }
						setAttributes={ setAttributes }
						state="closed"
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<Disabled>
					<ServerSideRender
						block={ metadata.name }
						attributes={ serverAttributes }
					/>
				</Disabled>
			</div>
		</>
	);
}

export function Save() {
	return null;
}

registerBlockType( metadata, {
	edit: Edit,
	save: Save,
} );
