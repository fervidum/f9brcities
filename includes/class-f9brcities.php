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

	/**
	 * F9brcities Constructor.
	 */
	public function __construct() {
		$this->define_constants();
		$this->includes();

		do_action( 'f9brcities_loaded' );
	}

	/**
	 * Define F9BRCITIES Constants.
	 */
	private function define_constants() {
		$this->define( 'F9BRCITIES_ABSPATH', dirname( F9BRCITIES_PLUGIN_FILE ) . '/' );
	}

	/**
	 * Define constant if not already set.
	 *
	 * @param string      $name  Constant name.
	 * @param string|bool $value Constant value.
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 */
	public function includes() {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			include_once F9BRCITIES_ABSPATH . 'includes/class-f9brcities-cli.php';
		}
	}
}
