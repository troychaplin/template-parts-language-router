<?php
/**
 * Plugin Name:       Template Parts Router
 * Description:       A plugin to route template parts to the appropriate template file based on the current context.
 * Requires at least: 6.6
 * Requires PHP:      7.0
 * Version:           0.1.0
 * Author:            Troy Chaplin
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       tp-router
 *
 * @package Template_Parts_Router
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Composer autoload.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

if ( ! class_exists( Template_Parts_Router\Plugin_Module::class ) ) {
	add_action(
		'admin_notices',
		function () {
			echo '<div class="notice notice-error"><p>';
			echo esc_html__( 'Template Parts Router: Composer autoload not found. Run `composer install`.', 'tp-router' );
			echo '</p></div>';
		}
	);
	return;
}

// Instantiate modules.
$tp_router_modules = array(
	new Template_Parts_Router\Render_Stack(),
	new Template_Parts_Router\Register_Blocks( __DIR__ . '/build' ),
);

foreach ( $tp_router_modules as $tp_router_module ) {
	$tp_router_module->init();
}
