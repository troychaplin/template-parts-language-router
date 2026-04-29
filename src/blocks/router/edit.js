/**
 * Edit component for `tp-router/router`.
 *
 * Resolves the surrounding template-part's slug (from a `baseSlug` attribute,
 * falling back to the nearest enclosing core/template-part block in the
 * editor) and renders the language-suffixed variant inline using
 * `useEntityBlockEditor`. Editing the rendered blocks saves to the variant
 * entity — same machinery core/template-part uses.
 */
import './editor.scss';

import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import {
	useBlockProps,
	useInnerBlocksProps,
	InspectorControls,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { PanelBody, TextControl, Placeholder } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import {
	store as coreStore,
	useEntityBlockEditor,
} from '@wordpress/core-data';

const DEFAULT_PREVIEW_LANG = 'en';

function VariantInnerBlocks( { entityId, blockProps } ) {
	const [ innerBlocks, onInput, onChange ] = useEntityBlockEditor(
		'postType',
		'wp_template_part',
		{ id: entityId }
	);

	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		value: innerBlocks ?? [],
		onInput,
		onChange,
		renderAppender: false,
	} );

	return <div { ...innerBlocksProps } />;
}

export default function Edit( { clientId, attributes, setAttributes } ) {
	const { baseSlug } = attributes;
	const [ previewLang, setPreviewLang ] = useState( DEFAULT_PREVIEW_LANG );

	const { detectedSlug, themeSlug } = useSelect(
		( select ) => {
			const { getBlockParents, getBlock } = select( blockEditorStore );
			const parentIds = getBlockParents( clientId );
			let foundSlug = null;
			for ( let i = parentIds.length - 1; i >= 0; i-- ) {
				const block = getBlock( parentIds[ i ] );
				if ( block?.name === 'core/template-part' ) {
					foundSlug = block.attributes?.slug || null;
					break;
				}
			}
			return {
				detectedSlug: foundSlug,
				themeSlug:
					select( coreStore ).getCurrentTheme()?.stylesheet || null,
			};
		},
		[ clientId ]
	);

	const effectiveBase = baseSlug || detectedSlug || '';
	const targetSlug = effectiveBase
		? `${ effectiveBase }-${ previewLang }`
		: '';
	const entityId =
		themeSlug && targetSlug ? `${ themeSlug }//${ targetSlug }` : null;

	const blockProps = useBlockProps();

	const inspector = (
		<InspectorControls>
			<PanelBody title={ __( 'Router', 'tp-router' ) }>
				<TextControl
					label={ __( 'Base slug', 'tp-router' ) }
					value={ baseSlug }
					placeholder={
						detectedSlug || __( 'e.g. footer', 'tp-router' )
					}
					onChange={ ( value ) =>
						setAttributes( { baseSlug: value } )
					}
					help={
						! baseSlug && detectedSlug
							? __(
									'Auto-detected from parent template part. Set explicitly if you need editor preview outside this context.',
									'tp-router'
							  )
							: __(
									'Base slug; the language suffix is appended at render time.',
									'tp-router'
							  )
					}
				/>
				<TextControl
					label={ __( 'Preview language', 'tp-router' ) }
					value={ previewLang }
					onChange={ setPreviewLang }
					help={ __(
						'Editor preview only; the frontend uses the active WPML language.',
						'tp-router'
					) }
				/>
			</PanelBody>
		</InspectorControls>
	);

	// Keep `inspector` at a stable position in the tree across both branches
	// so the InspectorControls portal — and the TextControls inside it —
	// don't unmount when `entityId` resolves on the first keystroke.
	return (
		<>
			{ inspector }
			{ ! entityId ? (
				<div { ...blockProps }>
					<Placeholder
						label={ __(
							'Template Parts Router',
							'tp-router'
						) }
						instructions={
							! effectiveBase
								? __(
										'Set a base slug in the inspector, or place this block inside a Template Part block.',
										'tp-router'
								  )
								: __(
										'Loading theme info…',
										'tp-router'
								  )
						}
					/>
				</div>
			) : (
				<VariantInnerBlocks
					entityId={ entityId }
					blockProps={ blockProps }
				/>
			) }
		</>
	);
}
