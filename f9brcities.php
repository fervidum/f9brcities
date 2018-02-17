<?php
/**
 * Plugin Name: F9brcities
 * Plugin URI: https://fervidum.github.io/f9brcities/
 * Description: Brazilian cities for various purposes in WordPress.
 * Version: 1.0.0
 * Author: Fervidum
 * Author URI: https://fervidum.github.io/
 *
 * Text Domain: f9brcities
 * Domain Path: /i18n/languages/
 *
 * @package F9brcities
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define F9BRCITIES_PLUGIN_FILE.
if ( ! defined( 'F9BRCITIES_PLUGIN_FILE' ) ) {
	define( 'F9BRCITIES_PLUGIN_FILE', __FILE__ );
}

// Include the main F9brcities class.
if ( ! class_exists( 'F9brcities' ) ) {
	include_once dirname( __FILE__ ) . '/includes/class-f9brcities.php';
}

/**
 * Main instance of F9brcities.
 *
 * Returns the main instance of f9brcities to prevent the need to use globals.
 *
 * @return F9brcities
 */
function f9brcities() {
	return F9brcities::instance();
}

f9brcities();
