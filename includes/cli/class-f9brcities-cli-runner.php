<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * F9BRCITIES to WC CLI Bridge.
 *
 * Register functions as CLI commands.
 *
 * @version 1.0.0
 * @package F9brcities
 */
class F9BRCITIES_CLI_Runner {

	/**
	 * Register's function as commands once WP and F9BRCITIES have all loaded.
	 */
	public static function after_wp_load() {
		self::register_commands();
	}

	/**
	 * Generates command information and tells WP CLI.
	 */
	private static function register_commands() {
		WP_CLI::add_command( 'brcities generate', array( __CLASS__, 'generate' ) );
	}

	/**
	 * Generates file of brazilian cities.
	 */
	public static function generate() {

		// Check for transient, if none, grab remote JS file.
		if ( false === ( $js = get_transient( 'ibge_remote_js' ) ) ) {

			// Get remote JS file.
			$response = wp_remote_get( 'https://cidades.ibge.gov.br/dist/main-client.js' );

			// Check for error.
			if ( is_wp_error( $response ) ) {
				return;
			}

			// Parse remote JS file.
			$data = wp_remote_retrieve_body( $response );

			// Check for error.
			if ( is_wp_error( $data ) ) {
				return;
			}

			// Store remote JS file in transient, expire after 24 hours.
			set_transient( 'ibge_remote_js', $data, 24 * HOUR_IN_SECONDS );

		}

		error_log( "test\n", 3, dirname( ABSPATH ) . '/brcities.log' );
		preg_match( '/i\.municipios=\[([^\]]+)]/', $js, $matches );

		if ( count( $matches ) > 1 ) {
			preg_match_all( '/{[^}]+}/', $matches[1], $matches );
			$json_items = $matches[0];

			$brcities = array();

			foreach ( $json_items as $json_item ) {
				$json_item = preg_replace( '/([a-zA-Z]+):/', '"$1":', $json_item );
				$city = (object) json_decode( $json_item );
				$brcities[ $city->codigoUf ][ $city->codigo ] = $city->nome;
			}
		}

		ob_start();
		?>
<?php echo chr( 60 ); ?>?php
/**
 * Brazillian cities
 *
 * @author      Fervidum
 * @category    i18n
 * @package     F9brcities/i18n
 * @version     1.0.0
 */

global $cities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
<?php
		foreach ( $brcities as $state_cod => $state_cities ) {
			printf( "\n\$cities['BR']['%s'] = array(\n", $state_cod );

			foreach ( $state_cities as $city_cod => $city ) {
				printf( "\t\t'%s' => __( '%s', 'f9brcities' ),\n", $city_cod, htmlentities2( $city ) );
			}
			if ( $state_cities !== end( $brcities ) ) {
				echo '),';
			} else {
				echo ");\n";
			}
		}

		$path = F9BRCITIES_ABSPATH . 'includes/i18n/cities/';
		if ( ! mkdir( $path, 0755, true ) ) {
			WP_CLI::error( 'Failed to create folder.' );
		}

		$file = F9BRCITIES_ABSPATH . 'includes/i18n/cities/br.php';
		file_put_contents( $file, ob_get_clean() );

		WP_CLI::success( 'File generated.' );
	}
}
