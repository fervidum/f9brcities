<?php
/**
 * F9brcities cli runner
 *
 * @package f9brcities
 */

defined( 'ABSPATH' ) || exit;

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
		$js = get_transient( 'ibge_remote_js' );
		if ( false === $js ) {

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
	 *
	 * @param string $var Variable.
	 */
	private static function json_items( $var ) {

		$output = array();

		$group = self::get_group( $var );
		if ( false !== $group ) {
			preg_match_all( '/{[^}]+}/', $group, $matches );
			$output = $matches[0];
		}

		return $output;
	}

	/**
	 * Split false objects json.
	 *
	 * @param string $var Variable.
	 */
	private static function get_group( $var ) {

		preg_match( "/\.$var\s*=\s*\[([^\]]+)]/im", self::get_js_data(), $matches );

		$output = false;

		if ( count( $matches ) > 1 ) {
			$output = $matches[1];
		}
		return $output;
	}

	/**
	 * Turn false json to array.
	 *
	 * @param string $false_json False json.
	 */
	private static function json_to_aray( $false_json ) {
		$real_json = preg_replace( '/([a-zA-Z]+):/', '"$1":', $false_json );
		$output    = (object) json_decode( $real_json );
		return $output;
	}

	/**
	 * Turn false json to array.
	 *
	 * @param string $uf_code UF code.
	 */
	private static function get_uf( $uf_code ) {
		if ( empty( self::$ufs ) ) {
			$items = self::json_items( 'ufs' );
			if ( false !== $items ) {
				$ufs = array();
				foreach ( $items as $json_item ) {
					$uf                       = self::json_to_aray( $json_item );
					self::$ufs[ $uf->codigo ] = $uf->sigla;
				}
			}
		}
		return self::$ufs[ $uf_code ];
	}

	/**
	 * Generates file of brazilian states and cities.
	 */
	public static function generate() {
		self::generate_states();
		self::generate_cities();

		WP_CLI::success( 'Files generated.' );
	}

	/**
	 * Generates file of brazilian states.
	 */
	public static function generate_states() {
		global $wp_filesystem;

		$items = self::json_items( 'ufs' );

		if ( false !== $items ) {

			$brstates = array();

			foreach ( $items as $json_item ) {
				$state = self::json_to_aray( $json_item );

				$brstates[ sanitize_title( $state->nome ) ] = (object) array(
					'abbr' => $state->sigla,
					'name' => $state->nome,
				);
			}
			ksort( $brstates );
		}

		ob_start();
		echo '<?php
/**
 * Brazillian states
 *
 * @package F9brcities/i18n
 * @version 1.0.0
 */

global $state;

defined( \'ABSPATH\' ) || exit;
';

		printf( "\n\$states['BR'] = array(\n" );
		foreach ( $brstates as $state ) {
			printf( "\t'%s' => __( '%s', 'f9brcities' ),\n", esc_html( $state->abbr ), esc_html( htmlentities2( $state->name ) ) );
		}
		echo ");\n";

		$path = F9BRCITIES_ABSPATH . 'i18n/';

		if ( ! $wp_filesystem->is_dir( $path ) ) {
			$wp_filesystem->mkdir( $path );
		}

		$path = $path . 'states/';

		if ( ! $wp_filesystem->is_dir( $path ) ) {
			$wp_filesystem->mkdir( $path );
		}

		$file = $path . 'br.php';
		$wp_filesystem->put_contents(
			$file,
			ob_get_clean()
		);
	}

	/**
	 * Generates file of brazilian cities.
	 */
	public static function generate_cities() {
		global $wp_filesystem;

		$items = self::json_items( 'municipios' );
		if ( false !== $items ) {

			$brcities = array();

			foreach ( $items as $json_item ) {
				$city = self::json_to_aray( $json_item );
				$uf   = self::get_uf( $city->codigoUf ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

				$brcities[ $uf ][ sanitize_title( $city->nome ) ] = (object) array(
					'code' => $city->codigo,
					'name' => $city->nome,
				);
			}
			ksort( $brcities[ $uf ] );
		}
		ksort( $brcities );

		ob_start();
		echo '<?php
/**
 * Brazillian cities
 *
 * @package F9brcities/i18n
 * @version 1.0.0
 */

global $cities;

defined( \'ABSPATH\' ) || exit;
';

		foreach ( $brcities as $state_cod => $state_cities ) {
			printf( "\n\$cities['BR']['%s'] = array(\n", esc_html( $state_cod ) );

			foreach ( $state_cities as $city ) {
				printf( "\t'%s' => __( '%s', 'f9brcities' ),\n", esc_html( $city->code ), esc_html( htmlentities2( $city->name ) ) );
			}
			if ( end( $brcities ) !== $state_cities ) {
				echo ');';
			} else {
				echo ");\n";
			}
		}

		$path = F9BRCITIES_ABSPATH . 'i18n/';

		if ( ! $wp_filesystem->is_dir( $path ) ) {
			$wp_filesystem->mkdir( $path );
		}

		$path = $path . 'cities/';

		if ( ! $wp_filesystem->is_dir( $path ) ) {
			$wp_filesystem->mkdir( $path );
		}

		$file = $path . 'br.php';
		$wp_filesystem->put_contents(
			$file,
			ob_get_clean()
		);
	}
}
