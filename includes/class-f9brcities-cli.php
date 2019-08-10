<?php
/**
 * F9brcities cli
 *
 * @package f9brcities
 */

defined( 'ABSPATH' ) || exit;

/**
 * Enables F9brcities, via the the command line.
 *
 * @version 1.0.0
 * @package F9brcities
 * @author  F9brcities
 */
class F9brcities_CLI {
	/**
	 * Load required files and hooks to make the CLI work.
	 */
	public function __construct() {
		$this->includes();
		$this->hooks();
	}

	/**
	 * Load command files.
	 */
	private function includes() {
		require_once __DIR__ . '/cli/class-f9brcities-cli-runner.php';
	}

	/**
	 * Sets up and hooks WP CLI to our CLI code.
	 */
	private function hooks() {
		WP_CLI::add_hook( 'after_wp_load', 'F9BRCITIES_CLI_Runner::after_wp_load' );
	}
}

new F9brcities_CLI();
