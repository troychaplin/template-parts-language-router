<?php
/**
 * Template Parts Router — server render.
 *
 * Resolves the base slug from the block's `baseSlug` attribute, falling back
 * to the nearest enclosing core/template-part being rendered (tracked by
 * Render_Stack), then renders `{baseSlug}-{language}` via
 * `block_template_part()`. The language is taken from
 * `apply_filters( 'wpml_current_language', 'en' )`.
 *
 * @package Template_Parts_Router
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner content (unused — block has no inner HTML).
 * @var WP_Block $block      Block instance (unused).
 */

$base_slug = '' !== ( $attributes['baseSlug'] ?? '' )
	? (string) $attributes['baseSlug']
	: (string) ( \Template_Parts_Router\Render_Stack::peek() ?? '' );

if ( '' === $base_slug ) {
	return;
}

$lang = (string) apply_filters( 'wpml_current_language', 'en' );
if ( '' === $lang ) {
	$lang = 'en';
}

block_template_part( "{$base_slug}-{$lang}" );
