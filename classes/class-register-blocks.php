<?php
/**
 * Register blocks from the build directory.
 *
 * @package Template_Parts_Router
 */

namespace Template_Parts_Router;

/**
 * Handles block registration using the blocks manifest.
 */
class Register_Blocks extends Plugin_Module {

	/**
	 * Absolute path to the build directory.
	 *
	 * @var string
	 */
	private string $build_dir;

	/**
	 * Setup the class.
	 *
	 * @param string $build_dir Absolute path to the build directory.
	 */
	public function __construct( string $build_dir ) {
		$this->build_dir = rtrim( $build_dir, '\\/' );
	}

	/**
	 * Initialize the module.
	 */
	public function init() {
		add_action( 'init', array( $this, 'register_blocks' ) );
	}

	/**
	 * Register all blocks found in the manifest.
	 */
	public function register_blocks() {
		$manifest = $this->build_dir . '/blocks-manifest.php';

		if ( ! file_exists( $manifest ) ) {
			return;
		}

		if ( function_exists( 'wp_register_block_types_from_metadata_collection' ) ) {
			wp_register_block_types_from_metadata_collection(
				$this->build_dir . '/blocks',
				$manifest
			);
			return;
		}

		// Fallback for WP < 6.7.
		if ( function_exists( 'wp_register_block_metadata_collection' ) ) {
			wp_register_block_metadata_collection(
				$this->build_dir . '/blocks',
				$manifest
			);
		}

		$manifest_data = require $manifest;

		foreach ( array_keys( $manifest_data ) as $block_type ) {
			register_block_type( $this->build_dir . '/blocks/' . $block_type );
		}
	}
}
