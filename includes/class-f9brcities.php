<?php
/**
 * F9brcities setup
 *
 * @package  WooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main F9brcities Class.
 *
 * @class F9brcities
 */
final class F9brcities {

	/**
	 * F9brcities version.
	 *
	 * @var string
	 */
	public $version = '1.0.0';

	/**
	 * The single instance of the class.
	 *
	 * @var F9brcities
	 */
	protected static $_instance = null;

	/**
	 * Main F9brcities Instance.
	 *
	 * Ensures only one instance of F9brcities is loaded or can be loaded.
	 *
	 * @static
	 * @see f9brcities()
	 * @return F9brcities - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
}
