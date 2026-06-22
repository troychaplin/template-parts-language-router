import './editor.scss';

import { __, sprintf } from '@wordpress/i18n';
import { useState, useMemo } from '@wordpress/element';
import {
	useBlockProps,
	useInnerBlocksProps,
	InspectorControls,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import {
	PanelBody,
	TextControl,
	RadioControl,
	Placeholder,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import {
	store as coreStore,
	useEntityBlockEditor,
} from '@wordpress/core-data';
import { parse } from '@wordpress/blocks';

const DEFAULT_PREVIEW_LANG = 'en';
const VARIANT_TEMPLATE_PART = 'template-part';
const VARIANT_PATTERN = 'pattern';

/**
 * Editable inline rendering of a template-part variant.
 */
function TemplatePartVariant( { entityId, blockProps } ) {
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

/**
 * Read-only inline rendering of a pattern variant. Patterns are not editable
 * entities; edits should be made to the underlying PHP pattern file.
 */
function PatternVariant( { patternContent, blockProps } ) {
	const blocks = useMemo(
		() => parse( patternContent || '' ),
		[ patternContent ]
	);

	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		value: blocks,
		onInput: () => {},
		onChange: () => {},
		renderAppender: false,
		templateLock: 'all',
	} );

	return <div { ...innerBlocksProps } />;
}

export default function Edit( { clientId, attributes, setAttributes } ) {
	const { baseSlug, variantType = VARIANT_TEMPLATE_PART } = attributes;
	const [ previewLang, setPreviewLang ] = useState( DEFAULT_PREVIEW_LANG );

	const { detectedSlug, themeSlug, patterns } = useSelect(
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
					select( coreStore ).getCurrentTheme()?.stylesheet ||
					null,
				patterns:
					variantType === VARIANT_PATTERN
						? select( coreStore ).getBlockPatterns() || null
						: null,
			};
		},
		[ clientId, variantType ]
	);

	const effectiveBase = baseSlug || detectedSlug || '';
	const variantSlug = effectiveBase
		? `${ effectiveBase }-${ previewLang }`
		: '';
	const entityId =
		themeSlug && variantSlug ? `${ themeSlug }//${ variantSlug }` : null;
	const patternName =
		themeSlug && variantSlug ? `${ themeSlug }/${ variantSlug }` : null;

	const matchedPattern = useMemo( () => {
		if (
			variantType !== VARIANT_PATTERN ||
			! patternName ||
			! patterns
		) {
			return null;
		}
		return patterns.find( ( p ) => p.name === patternName ) || null;
	}, [ variantType, patternName, patterns ] );

	const blockProps = useBlockProps();

	// Inspector — kept at a stable position in the tree across both
	// variant-type branches so the InspectorControls portal (and the inputs
	// inside it) don't unmount on attribute changes.
	const inspector = (
		<InspectorControls>
			<PanelBody title={ __( 'Router', 'tplr' ) }>
				<TextControl
					label={ __( 'Base slug', 'tplr' ) }
					value={ baseSlug }
					placeholder={
						detectedSlug || __( 'e.g. footer', 'tplr' )
					}
					onChange={ ( value ) =>
						setAttributes( { baseSlug: value } )
					}
					help={
						! baseSlug && detectedSlug
							? __(
									'Auto-detected from parent template part. Set explicitly if you need editor preview outside this context.',
									'tplr'
							  )
							: __(
									'Base slug; the language suffix is appended at render time.',
									'tplr'
							  )
					}
				/>
				<RadioControl
					label={ __( 'Variant source', 'tplr' ) }
					selected={ variantType }
					options={ [
						{
							label: __( 'Template part', 'tplr' ),
							value: VARIANT_TEMPLATE_PART,
						},
						{
							label: __( 'Pattern', 'tplr' ),
							value: VARIANT_PATTERN,
						},
					] }
					onChange={ ( value ) =>
						setAttributes( { variantType: value } )
					}
					help={
						variantType === VARIANT_PATTERN
							? __(
									'Pattern variants render read-only in the editor. Edit the pattern PHP file directly to make changes.',
									'tplr'
							  )
							: __(
									'Template part variants are inline-editable in the editor.',
									'tplr'
							  )
					}
				/>
				<TextControl
					label={ __( 'Preview language', 'tplr' ) }
					value={ previewLang }
					onChange={ setPreviewLang }
					help={ __(
						'Editor preview only; the frontend uses the active WPML language.',
						'tplr'
					) }
				/>
			</PanelBody>
		</InspectorControls>
	);

	let body;
	if ( ! effectiveBase || ! themeSlug ) {
		body = (
			<div { ...blockProps }>
				<Placeholder
					label={ __( 'Template Parts Language Router', 'tplr' ) }
					instructions={
						! effectiveBase
							? __(
									'Set a base slug in the inspector, or place this block inside a Template Part block.',
									'tplr'
							  )
							: __( 'Loading theme info…', 'tplr' )
					}
				/>
			</div>
		);
	} else if ( variantType === VARIANT_PATTERN ) {
		if ( ! patterns ) {
			body = (
				<div { ...blockProps }>
					<Placeholder
						label={ __(
							'Template Parts Language Router',
							'tplr'
						) }
						instructions={ __(
							'Loading patterns…',
							'tplr'
						) }
					/>
				</div>
			);
		} else if ( ! matchedPattern ) {
			body = (
				<div { ...blockProps }>
					<Placeholder
						label={ __(
							'Template Parts Language Router',
							'tplr'
						) }
						instructions={ sprintf(
							/* translators: %s: pattern slug, e.g. "my-theme/footer-fr" */
							__(
								'Pattern "%s" not found. Add a patterns/<name>.php file in your theme with that Slug header.',
								'tplr'
							),
							patternName
						) }
					/>
				</div>
			);
		} else {
			body = (
				<PatternVariant
					patternContent={ matchedPattern.content }
					blockProps={ blockProps }
				/>
			);
		}
	} else {
		body = (
			<TemplatePartVariant
				entityId={ entityId }
				blockProps={ blockProps }
			/>
		);
	}

	return (
		<>
			{ inspector }
			{ body }
		</>
	);
}
