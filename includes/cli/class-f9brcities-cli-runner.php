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
	 * UFs code and abbr.
	 *
	 * @var array
	 */
	public static $ufs = array();

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
	 * Get js data of file or transient.
	 */
	public static function get_js_data() {

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

		return $js;
	}

	/**
	 * Set array state of code state and abbr.
	 */
	private static function set_ufs() {

		preg_match( '/i\.ufs=\[([^\]]+)]/', self::get_js_data(), $matches );
	}

	/**
	 * Split false json objects.
	 */
	private static function json_items( $var ) {

		$output = array();

		if ( false !== ( $group = self::get_group( $var ) ) ) {
			preg_match_all( '/{[^}]+}/', $group, $matches );
			$output = $matches[0];
		}

		return $output;
	}

	/**
	 * Split false objects json.
	 */
	private static function get_group( $var ) {

		preg_match( "/i\.$var=\[([^\]]+)]/", self::get_js_data(), $matches );

		$output = false;

		if ( count( $matches ) > 1 ) {
			$output = $matches[1];
		}
		return $output;
	}

	/**
	 * Turn false json to array.
	 */
	private static function json_to_aray( $false_json ) {
		$real_json = preg_replace( '/([a-zA-Z]+):/', '"$1":', $false_json );
		$output = (object) json_decode( $real_json );
		return $output;
	}

	/**
	 * Turn false json to array.
	 */
	private static function get_uf( $uf_code ) {
		if ( empty( self::$ufs ) ) {
			if ( false !== ( $items = self::json_items( 'ufs' ) ) ) {
				$ufs = array();
				foreach ( $items as $json_item ) {
					$uf = self::json_to_aray( $json_item );
					self::$ufs[ $uf->codigo ] = $uf->sigla;
				}
			}
		}
		return self::$ufs[ $uf_code ];
	}

	/**
	 * Generates file of brazilian cities.
	 */
	public static function generate() {

		if ( false !== ( $items = self::json_items( 'municipios' ) ) ) {

			$brcities = array();

			foreach ( $items as $json_item ) {
				$city = self::json_to_aray( $json_item );
				$uf = self::get_uf( $city->codigoUf );
				$brcities[ $uf ][ $city->codigo ] = $city->nome;
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
		if ( ! file_exists ( $path ) ) {
			if ( ! mkdir( $path, 0755, true ) ) {
				WP_CLI::error( 'Failed to create folder.' );
			}
		}

		$file = F9BRCITIES_ABSPATH . 'includes/i18n/cities/br.php';
		file_put_contents( $file, ob_get_clean() );

		die;//WP_CLI::success( 'File generated.' );
	}
}
