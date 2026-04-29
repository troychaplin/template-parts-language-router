<?php
/**
 * Maintains a render-stack of `core/template-part` slugs being rendered.
 *
 * Used by the router block at PHP render time to auto-detect the slug of the
 * surrounding template part on the frontend (where `do_blocks()` runs and the
 * `pre_render_block` / `render_block` filters fire). The stack is empty
 * inside the editor's REST SSR preview because that context renders the
 * router in isolation, without the parent template-part on the call stack —
 * an explicit `baseSlug` attribute is required there. The editor's JS Edit
 * component performs an equivalent lookup via the block tree.
 *
 * @package Template_Parts_Router
 */

namespace Template_Parts_Router;

/**
 * Class Render_Stack
 */
class Render_Stack extends Plugin_Module {

	/**
	 * Slugs of currently rendering core/template-part blocks, innermost last.
	 *
	 * @var array<int, string>
	 */
	private static array $stack = array();

	/**
	 * Initialize the module.
	 */
	public function init() {
		add_filter( 'pre_render_block', array( $this, 'push' ), 10, 2 );
		add_filter( 'render_block', array( $this, 'pop' ), 10, 2 );
	}

	/**
	 * Push the slug of a `core/template-part` block onto the stack before it
	 * renders.
	 *
	 * @param mixed $pre_render   Existing pre-render value (typically null).
	 * @param array $parsed_block The block being rendered.
	 * @return mixed Unchanged $pre_render value.
	 */
	public function push( $pre_render, $parsed_block ) {
		if ( is_array( $parsed_block ) && 'core/template-part' === ( $parsed_block['blockName'] ?? '' ) ) {
			self::$stack[] = (string) ( $parsed_block['attrs']['slug'] ?? '' );
		}
		return $pre_render;
	}

	/**
	 * Pop the matching slug off the stack after a `core/template-part` block
	 * has rendered.
	 *
	 * @param string $block_content Rendered block HTML.
	 * @param array  $parsed_block  The block that rendered.
	 * @return string Unchanged $block_content.
	 */
	public function pop( $block_content, $parsed_block ) {
		if ( is_array( $parsed_block ) && 'core/template-part' === ( $parsed_block['blockName'] ?? '' ) ) {
			array_pop( self::$stack );
		}
		return $block_content;
	}

	/**
	 * Peek at the slug of the innermost currently rendering template part.
	 *
	 * @return string|null Slug, or null if no template-part is rendering.
	 */
	public static function peek(): ?string {
		$top = end( self::$stack );
		return false === $top ? null : (string) $top;
	}
}
