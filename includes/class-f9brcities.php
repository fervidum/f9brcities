<?php
/**
 * F9brcities setup
 *
 * @package f9brcities
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
	protected static $instance = null;

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
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * F9brcities Constructor.
	 */
	public function __construct() {
		$this->filesystem();
		$this->define_constants();
		$this->includes();

		do_action( 'f9brcities_loaded' );
	}

	/**
	 * Define global filesystem.
	 */
	public function filesystem() {
		if ( ! function_exists( 'get_filesystem_method' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		if ( 'direct' === get_filesystem_method() ) {
			$creds = request_filesystem_credentials( site_url() . '/wp-admin/', '', false, false, array() );

			if ( ! WP_Filesystem( $creds ) ) {
				return false;
			}

			global $wp_filesystem;

		}
	}

	/**
	 * Define F9BRCITIES Constants.
	 */
	private function define_constants() {
		global $wp_filesystem;

		$this->define( 'F9BRCITIES_PLUGIN_DIR', plugin_dir_path( F9BRCITIES_PLUGIN_FILE ) );
		$this->define( 'F9BRCITIES_ABSPATH', str_replace( ABSPATH, $wp_filesystem->abspath(), F9BRCITIES_PLUGIN_DIR ) );
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

	/**
	 * Path of a file in plugin.
	 *
	 * @param  string $file Optional. File to search for in the plugin directory.
	 * @return string
	 */
	public function file_path( $file = '' ) {
		$path = untrailingslashit( plugin_dir_path( F9BRCITIES_PLUGIN_FILE ) );
		return $path . '/' . ltrim( $file, '/' );
	}
}
