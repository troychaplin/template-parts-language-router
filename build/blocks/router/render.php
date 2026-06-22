<?php
/**
 * Template Parts Language Router — server render.
 *
 * Resolves the base slug from the block's `baseSlug` attribute, falling back
 * to the nearest enclosing core/template-part being rendered (tracked by
 * Render_Stack), then renders `{baseSlug}-{language}` via
 * `block_template_part()`. The language is resolved by tp_router_get_current_language()
 * (WPML → Polylang → WordPress locale, with a tp_router/current_language override filter).
 *
 * @package Template_Parts_Language_Router
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner content (unused — block has no inner HTML).
 * @var WP_Block $block      Block instance (unused).
 */

$base_slug = '' !== ( $attributes['baseSlug'] ?? '' )
	? (string) $attributes['baseSlug']
	: (string) ( \Template_Parts_Language_Router\Render_Stack::peek() ?? '' );

if ( '' === $base_slug ) {
	return;
}

$lang = tp_router_get_current_language();

$variant_slug = "{$base_slug}-{$lang}";
$variant_type = (string) ( $attributes['variantType'] ?? 'template-part' );

if ( 'pattern' === $variant_type ) {
	$pattern_slug = get_stylesheet() . '/' . $variant_slug;
	$registry     = \WP_Block_Patterns_Registry::get_instance();

	if ( $registry->is_registered( $pattern_slug ) ) {
		$pattern = $registry->get_registered( $pattern_slug );
		echo do_blocks( $pattern['content'] );
	}

	return;
}

block_template_part( $variant_slug );
