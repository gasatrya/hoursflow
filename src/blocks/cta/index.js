import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { Placeholder } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import metadata from './block.json';

export function Edit() {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<Placeholder
				label={ __( 'OpenNow CTA', 'opennow' ) }
				instructions={ __(
					'The output uses the global OpenNow configuration and the current business state.',
					'opennow'
				) }
			/>
		</div>
	);
}

export function Save() {
	return null;
}

registerBlockType( metadata, {
	edit: Edit,
	save: Save,
} );
