<?php
/**
 * Plugin Name:       Template Parts Language Router
 * Description:       A plugin to route template parts to the appropriate template file based on the current context. Supports WPML, Polylang, and WordPress locale.
 * Requires at least: 6.6
 * Requires PHP:      7.0
 * Version:           0.1.0
 * Author:            Troy Chaplin
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       tplr
 *
 * @package Template_Parts_Language_Router
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Composer autoload.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

if ( ! class_exists( Template_Parts_Language_Router\Plugin_Module::class ) ) {
	add_action(
		'admin_notices',
		function () {
			echo '<div class="notice notice-error"><p>';
			echo esc_html__( 'Template Parts Language Router: Composer autoload not found. Run `composer install`.', 'tplr' );
			echo '</p></div>';
		}
	);
	return;
}

/**
 * Resolves the current language for template part routing.
 *
 * Priority: tp_router/current_language filter → WPML → Polylang → WordPress locale.
 * Hook `tp_router/current_language` and return a language code string to override
 * detection entirely (useful for custom multilingual setups or testing).
 *
 * @return string Two-letter (or longer) language code, e.g. "en", "fr".
 */
function tp_router_get_current_language(): string {
	$lang = apply_filters( 'tp_router/current_language', null );
	if ( $lang ) {
		return (string) $lang;
	}

	if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
		return (string) apply_filters( 'wpml_current_language', 'en' );
	}

	if ( function_exists( 'pll_current_language' ) ) {
		return pll_current_language() ?: 'en';
	}

	return substr( get_locale(), 0, 2 );
}

// Instantiate modules.
$tp_router_modules = array(
	new Template_Parts_Language_Router\Render_Stack(),
	new Template_Parts_Language_Router\Register_Blocks( __DIR__ . '/build' ),
);

foreach ( $tp_router_modules as $tp_router_module ) {
	$tp_router_module->init();
}
