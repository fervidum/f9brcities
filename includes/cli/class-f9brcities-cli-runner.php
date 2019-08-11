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
		$commands = array(
			'generate',
			'cep-ranges',
			'cities-count',
		);

		foreach ( $commands as $command ) {
			$function = str_replace( '-', '_', $command );
			WP_CLI::add_command( 'brcities ' . $command, array( __CLASS__, $function ) );
		}
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
				printf( "\t%d => __( '%s', 'f9brcities' ),\n", esc_html( absint( $city->code ) ), esc_html( htmlentities2( $city->name ) ) );
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

	/**
	 * Get data of remote or transient.
	 *
	 * @param string $uf State code.
	 * @param string $city City name.
	 */
	public static function get_cep_range_data( $uf, $city ) {
		$city = html_entity_decode( $city );

		$slug = str_replace( '-', '', sanitize_title( $uf . $city ) );

		// Check for transient, if none, grab remote.
		delete_transient( 'ect_remote_' . $slug );
		$tables = get_transient( 'ect_remote_' . $slug );
		if ( false === $tables ) {

			// Get remote body.
			$response = wp_remote_post(
				'http://www.buscacep.correios.com.br/sistemas/buscacep/resultadoBuscaFaixaCEP.cfm',
				array(
					'timeout' => 45,
					'body'    => array(
						'UF'         => $uf,
						'Localidade' => utf8_decode( $city ),
					),
				)
			);

			// Check for error.
			if ( is_wp_error( $response ) ) {
				return;
			}

			// Parse remote body.
			$body = wp_remote_retrieve_body( $response );

			// Check for error.
			if ( is_wp_error( $body ) ) {
				return;
			}

			$tables = join( '', self::extract_tables( $body ) );

			// Store remote tables on body in transient, expire after 24 hours.
			set_transient( 'ect_remote_' . $slug, $tables, 24 * HOUR_IN_SECONDS );
		}

		if ( $tables ) {
			preg_match_all( '#<table[^>]*>.*?</table>#', $tables, $tables );
			$tables = current( $tables );
		} else {
			$tables = array();
		}

		return $tables;
	}

	/**
	 * Cleanup table.
	 *
	 * @param  string $table Table HTML.
	 * @return string
	 */
	private static function clean_table( $table ) {
		$table = preg_replace( '/<\/?b>/', '', $table );
		$table = preg_replace( '/<(\/?\w+)\s[^>]+>/', '<$1>', $table );
		$table = str_replace( '<td> ', '<td>', $table );
		return $table;
	}

	/**
	 * Extract tables.
	 *
	 * @param  string $html Table content.
	 * @return array
	 */
	private static function extract_tables( $html ) {
		$html = preg_replace( '/\r|\n/', '', $html );
		$html = preg_replace( '/(\>)\s*(\<)/m', '$1$2', $html );
		$html = self::clean_table( $html );
		$html = html_entity_decode( $html );
		preg_match_all( '#<table[^>]*>.*?</table>#', $html, $tables );
		return current( $tables );
	}

	/**
	 * Table to array.
	 *
	 * @param  string $table Table content.
	 * @return array
	 */
	private static function table_to_array( $table ) {
		preg_match_all( '/<tr>(.*?)<\/tr>/', $table, $lines );
		if ( count( $lines ) > 1 ) {
			$lines = array_values( array_filter( $lines[1] ) );
		} else {
			$lines = array();
		}
		foreach ( $lines as &$line ) {
			preg_match_all( '/<t\w>(.*?)<\/t\w>/', $line, $cols );
			if ( count( $cols ) > 1 ) {
				$cols = $cols[1];
			} else {
				$cols = array();
			}
			$line = $cols;
		}
		$heading = current( $lines );
		unset( $lines[0] );
		$lines = array_values( $lines );

		$array = array();
		foreach ( $lines as $cols ) {
			$col = array();
			foreach ( $cols as $c => $value ) {
				if ( 'Localidade' === $heading[ $c ] ) {
					$value = utf8_encode( $value );
				}
				$col[ $c ] = array(
					'column' => $heading[ $c ],
					'value'  => $value,
				);
			}
			$array[] = $col;
		}
		return $array;
	}

	/**
	 * Count number of cities.
	 */
	public static function cities_count() {
		global $wp_filesystem;

		$file = 'i18n/cities/br.php';

		if ( ! $wp_filesystem->is_file( F9BRCITIES_ABSPATH . $file ) ) {
			self::generate_cities();
		}

		$cities = apply_filters( 'brcities_cities', include f9brcities()->file_path( $file ) );

		$count = 0;
		foreach ( array_keys( $cities['BR'] ) as $uf ) {
			$count += count( $cities['BR'][ $uf ] );
		}

		WP_CLI::success( sprintf( '%d cities.', $count ) );
	}

	/**
	 * Generates file of brazilian cities cep ranges.
	 */
	public static function cep_ranges() {
		global $wp_filesystem;

		$file = 'i18n/cities/br.php';

		if ( ! $wp_filesystem->is_file( F9BRCITIES_ABSPATH . $file ) ) {
			self::generate_cities();
		}

		$cities = apply_filters( 'brcities_cities', include f9brcities()->file_path( $file ) );

		foreach ( array_keys( $cities['BR'] ) as $uf ) {
			foreach ( $cities['BR'][ $uf ] as $city ) {
				$tables = self::get_cep_range_data( $uf, $city );
				if ( ! is_array( $tables ) ) {
					$tables = array();
				}
				foreach ( $tables as $table ) {
					$array = self::table_to_array( $table );

					if ( 'yes' === get_option( 'brcities_debuger', 'no' ) ) {
						$logger  = wc_get_logger();
						$context = array( 'source' => 'brcities-cep-ranges' );

						$pairs = '';
						foreach ( $array as $item ) {
							foreach ( $item as $attr ) {
								$pairs .= join( ':', array_values( $attr ) ) . "\n";
							}
						}
						$pairs .= "\n";

						$logger->debug( $pairs, $context );
					}
				}
			}
		}

		WP_CLI::success( 'File generated.' );
	}
}
