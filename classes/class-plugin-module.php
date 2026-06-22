<?php
/**
 * Base class for plugin modules which can be initialized.
 *
 * @package Template_Parts_Language_Router
 */

namespace Template_Parts_Language_Router;

/**
 * Plugin module extended by other classes.
 */
abstract class Plugin_Module {

	/**
	 * Initialize the module by registering hooks.
	 */
	abstract public function init();
}
