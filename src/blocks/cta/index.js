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
		[ 'label', 'action', 'status', 'hideStatus' ].forEach( ( field ) => {
			if ( ! hasOwn( value[ state ], field ) ) {
				return;
			}

			if ( 'hideStatus' === field ) {
				if ( true === value[ state ][ field ] ) {
					stateOverrides[ field ] = true;
				}
				return;
			}

			if ( 'string' === typeof value[ state ][ field ] ) {
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
		if ( 'hideStatus' === field ) {
			nextState[ field ] = true;
		} else {
			nextState[ field ] = 'string' === typeof value ? value : '';
		}
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
	const hideStatusEnabled = hasOverride( stateOverrides, 'hideStatus' );

	return (
		<>
			<ToggleControl
				label={ __( 'Override label', 'hoursflow' ) }
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
					label={ __( 'Label', 'hoursflow' ) }
					help={ __(
						'Enter non-empty plain text. Invalid or empty values fall back to the global label.',
						'hoursflow'
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
				label={ __( 'Override action', 'hoursflow' ) }
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
					label={ __( 'Action', 'hoursflow' ) }
					help={ __(
						'Enter a root-relative URL, HTTPS URL, or tel: action. Invalid or empty values fall back to the global action.',
						'hoursflow'
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
				label={ __( 'Override status', 'hoursflow' ) }
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
					label={ __( 'Status', 'hoursflow' ) }
					help={ __(
						'Enter plain text. A blank value explicitly hides the global status; the Hide status control hides it regardless of its value. Invalid values fall back to the global status.',
						'hoursflow'
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

			<ToggleControl
				label={ __( 'Hide status', 'hoursflow' ) }
				checked={ hideStatusEnabled }
				onChange={ ( enabled ) =>
					updateOverride(
						attributes,
						setAttributes,
						state,
						'hideStatus',
						enabled
					)
				}
			/>
		</>
	);
}

function getServerAttributes( attributes ) {
	const safeAttributes = getAttributes( attributes );
	const serverAttributes = {};

	if ( isObject( safeAttributes.overrides ) ) {
		serverAttributes.overrides = safeAttributes.overrides;
	}
	if ( isObject( safeAttributes.style ) ) {
		serverAttributes.style = safeAttributes.style;
	}
	[ 'backgroundColor', 'fontFamily', 'fontSize', 'textColor' ].forEach(
		( attribute ) => {
			if ( 'string' === typeof safeAttributes[ attribute ] ) {
				serverAttributes[ attribute ] = safeAttributes[ attribute ];
			}
		}
	);

	return serverAttributes;
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
				<PanelBody title={ __( 'Open CTA', 'hoursflow' ) }>
					<CtaOverrideControls
						attributes={ attributes }
						setAttributes={ setAttributes }
						state="open"
					/>
				</PanelBody>
				<PanelBody title={ __( 'Closed CTA', 'hoursflow' ) }>
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
						httpMethod="POST"
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
