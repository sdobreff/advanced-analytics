<?php
/**
 * Class: WordPress function helper class .
 *
 * Helper class to ease the work with WP functions.
 *
 * @package advanced-analytics
 *
 * @since 1.1.0
 */

declare(strict_types=1);

namespace ADVAN\Helpers;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\ADVAN\Helpers\WP_Helper' ) ) {
	/**
	 * Class: WP_Helper
	 *
	 * Helper class to handle errors and exceptions.
	 *
	 * @since 1.4.0
	 */
	class WP_Helper {

		/**
		 * Holds the file components array.
		 *
		 * @var array
		 *
		 * @since 1.4.0
		 */
		protected static $file_components = array();

		/**
		 * Holds the file components array.
		 *
		 * @var array
		 *
		 * @since 1.4.0
		 */
		private static $file_dirs = array();

		/**
		 * Holds the abs path of the WordPress installation.
		 *
		 * @var string|null
		 *
		 * @since 1.4.0
		 */
		private static $abspath = null;

		/**
		 * Holds the admin path of the WordPress installation.
		 *
		 * @var string|null
		 *
		 * @since 1.8.0
		 */
		private static $admin_path = null;

		/**
		 * Holds the content path of the WordPress installation.
		 *
		 * @var string|null
		 *
		 * @since 1.4.0
		 */
		private static $contentpath = null;

		/**
		 * Keeps the value of the multisite install of the WP.
		 *
		 * @var bool
		 *
		 * @since 1.8.2
		 */
		private static $is_multisite = null;

		/**
		 * Current screen.
		 *
		 * @var \WP_Screen
		 *
		 * @since 1.7.0
		 */
		protected static $wp_screen;

		/**
		 * Verifies the nonce and user capability.
		 *
		 * @param string $action Nonce action.
		 * @param string $nonce_name Nonce name.
		 *
		 * @return void
		 *
		 * @throws \Exception If the nonce is invalid or the user does not have the required capability.
		 *
		 * @since 1.4.0
		 */
		public static function verify_admin_nonce( string $action, string $nonce_name = '_wpnonce' ): void {
			if ( Settings::get_current_options()['menu_admins_only'] && ! \current_user_can( 'manage_options' ) ) {
				\wp_send_json_error( 'Insufficient permissions.', 403 );
				\wp_die();
			}

			if ( ! \current_user_can( 'manage_options' ) || ! \check_ajax_referer( $action, $nonce_name, false ) ) {
				\wp_send_json_error( 'Insufficient permissions or invalid nonce.', 403 );
				\wp_die();
			}
		}

		/**
		 * Returns an array of the callback functions that are attached to the given hook name.
		 *
		 * @param string $name The hook name.
		 * @return array<int,array<string,mixed>> Array of callbacks attached to the hook.
		 * @phpstan-return array<int,array{
		 *   priority: int,
		 *   callback: array<string,mixed>,
		 * }>
		 *
		 * @since 1.4.0
		 */
		public static function get_cron_callbacks( $name ) {
			global $wp_filter;

			if ( ! isset( $wp_filter[ $name ] ) ) {
				return array();
			}

			$actions = array();
			foreach ( $wp_filter[ $name ] as $priority => $callbacks ) {
				foreach ( $callbacks as $callback ) {
					$callback = self::populate_callback( $callback['function'] );

					if ( __NAMESPACE__ . '\\pauser()' === $callback['name'] ) {
						continue;
					}

					$actions[] = array(
						'priority' => $priority,
						'callback' => $callback,
					);
				}
			}

			return $actions;
		}

		/**
		 * Tries to extract as much information as possible from a callback.
		 *
		 * @param array|string $callback - The callback to populate.
		 *
		 * @return array
		 * @phpstan-return array{
		 *   name?: string,
		 *   file?: string|false,
		 *   line?: string|false,
		 *   error?: WP_Error,
		 *   component?: QM_Component,
		 * }
		 *
		 * @since 1.4.0
		 */
		public static function populate_callback( $callback ): array {
			$result = array();

			try {
				if ( is_array( $callback ) ) {
					if ( is_object( $callback[0] ) ) {
						$class  = get_class( $callback[0] );
						$access = '->';
					} else {
						$class  = $callback[0];
						$access = '::';
					}

					$result['name'] = self::shorten_fqn( $class . $access . $callback[1] ) . '()';
					$ref            = new \ReflectionMethod( $class, $callback[1] );
				} elseif ( is_object( $callback ) && method_exists( $callback, '__invoke' ) ) {
					$class          = get_class( $callback );
					$result['name'] = self::shorten_fqn( $class ) . '->__invoke()';
					$ref            = new \ReflectionMethod( $class, '__invoke' );
				} else {
					$function_name  = is_string( $callback ) ? $callback : spl_object_hash( $callback );
					$result['name'] = self::shorten_fqn( $function_name ) . '()';
					try {
						$ref = new \ReflectionFunction( $callback );
						// Class as string ?
					} catch ( \ReflectionException $e ) {
						if ( PHP_VERSION_ID >= 80400 ) {
							$ref = \ReflectionMethod::createFromMethodName( $callback );
						} else {
							$ref = new \ReflectionMethod( $callback );
						}
					}
				}

				$result['file'] = $ref->getFileName();
				$result['line'] = $ref->getStartLine();

				$name = trim( $ref->getName() );

				if ( '__lambda_func' === $name || 0 === strpos( $name, 'lambda_' ) ) {
					if ( $result['file'] && preg_match( '|(?P<file>.*)\((?P<line>[0-9]+)\)|', $result['file'], $matches ) ) {
						$result['file'] = $matches['file'];
						$result['line'] = $matches['line'];
						$file           = trim( self::standard_dir( $result['file'], '' ), '/' );
						$result['name'] = sprintf(
							// translators: %1$d is the line number, %2$s is the file name.
							__( 'Anonymous function on line %1$d of %2$s', '0-day-analytics' ),
							$result['line'],
							$file
						);
					} else {
						unset( $result['line'], $result['file'] );
						$result['name']  = $name . '()';
						$result['error'] = new \WP_Error( 'unknown_lambda', __( 'Unable to determine source of lambda function', '0-day-analytics' ) );
					}
				}

				if ( ! empty( $result['file'] ) ) {
					$result['component'] = self::get_file_component( $result['file'] );
				} else {
					$result['component'] = array(
						'type'    => 'php',
						'name'    => 'PHP',
						'context' => '',
					);
				}
			} catch ( \ReflectionException $e ) {
				$result['error'] = new \WP_Error( 'reflection_exception', $e->getMessage() );
			}

			return $result;
		}

		/**
		 * Converts and extracts standardized patch from directory
		 *
		 * @param string $dir - The directory to check.
		 * @param string $path_replace - The path to replace with.
		 *
		 * @return string
		 *
		 * @since 1.4.0
		 */
		public static function standard_dir( string $dir, ?string $path_replace = null ): string {
			$dir = self::normalize_path( $dir );

			if ( is_string( $path_replace ) ) {
				if ( null === self::$abspath ) {
					self::$abspath     = self::normalize_path( ABSPATH );
					self::$contentpath = self::normalize_path( dirname( WP_CONTENT_DIR ) . '/' );
				}
				$dir = str_replace(
					array(
						self::$abspath,
						self::$contentpath,
					),
					$path_replace,
					$dir
				);
			}

			return $dir;
		}

		/**
		 * Normalizes the given path string
		 *
		 * @param string $path - Path to normalize.
		 *
		 * @return string
		 *
		 * @since 1.4.0
		 */
		public static function normalize_path( string $path ): string {
			if ( function_exists( 'wp_normalize_path' ) ) {
				return \wp_normalize_path( $path );
			}

			$path = str_replace( '\\', '/', $path );

			return str_replace( '//', '/', $path );
		}

		/**
		 * Shortens a fully qualified name to reduce the length of the names of long namespaced symbols.
		 *
		 * This initialises portions that do not form the first or last portion of the name. For example:
		 *
		 *     Inpsyde\Wonolog\HookListener\HookListenersRegistry->hook_callback()
		 *
		 * becomes:
		 *
		 *     Inpsyde\W\H\HookListenersRegistry->hook_callback()
		 *
		 * @param string $fqn A fully qualified name.
		 *
		 * @return string A shortened version of the name.
		 *
		 * @since 1.4.0
		 */
		public static function shorten_fqn( string $fqn ): string {
			if ( substr_count( $fqn, '\\' ) < 3 ) {
				return $fqn;
			}

			return preg_replace_callback(
				'#\\\\[a-zA-Z0-9_\\\\]{4,}\\\\#',
				static function ( array $matches ): string {
					preg_match_all( '#\\\\([a-zA-Z0-9_])#', $matches[0], $m );

					return '\\' . implode( '\\', $m[1] ) . '\\';
				},
				$fqn
			);
		}

		/**
		 * Attempts to determine the component responsible for a given file name.
		 *
		 * @param string $file An absolute file path.
		 *
		 * @return array An array representing the component.
		 *
		 * @since 1.4.0
		 */
		public static function get_file_component( string $file ): array {
			$file = self::standard_dir( $file );

			if ( isset( self::$file_components[ $file ] ) ) {
				return self::$file_components[ $file ];
			}

			$type = '';
			foreach ( self::get_file_dirs() as $current_type => $dir ) {
				if ( $dir && ( 0 === strpos( $file, trailingslashit( $dir ) ) ) ) {
					$type = $current_type;
					break;
				}
			}

			$context = $type;

			switch ( $type ) {
				case 'altis-vendor':
					$name = self::get_altis_vendor_name( $file );
					break;

				case 'plugin':
				case 'mu-plugin':
				case 'mu-vendor':
					$name = self::get_plugin_name( $file, $type );
					break;

				case 'go-plugin':
				case 'vip-plugin':
				case 'vip-client-mu-plugin':
					$name = self::get_vip_plugin_name( $file, $type );
					break;

				case 'stylesheet':
					$name = is_child_theme() ? __( 'Child Theme', '0-day-analytics' ) : __( 'Theme', '0-day-analytics' );
					break;

				case 'template':
					$name = __( 'Parent Theme', '0-day-analytics' );
					break;

				case 'other':
					$name = self::get_other_name( $file );
					break;

				case 'core':
					$name = __( 'WordPress Core', '0-day-analytics' );
					break;

				default:
					$name = __( 'Unknown', '0-day-analytics' );
					break;
			}

			/*
				$name = match ($type) {
					'altis-vendor' => self::get_altis_vendor_name( $file ),
					'plugin', 'mu-plugin', 'mu-vendor' => self::get_plugin_name( $file, $type ),
					'go-plugin', 'vip-plugin', 'vip-client-mu-plugin' => self::get_vip_plugin_name( $file, $type ),
					'stylesheet' => is_child_theme() ? __( 'Child Theme', '0-day-analytics' ) : __( 'Theme', '0-day-analytics' ),
					'template' => __( 'Parent Theme', '0-day-analytics' ),
					'other' => self::get_other_name( $file ),
					'core' => __( 'WordPress Core', '0-day-analytics' ),
					default => __( 'Unknown', '0-day-analytics' ),
				};
			}
			*/

			if ( 'stylesheet' === $type || 'template' === $type ) {
				$type = 'theme';
			}

			if ( 'other' === $type ) {
				if ( ! function_exists( '_get_dropins' ) ) {
					require_once trailingslashit( constant( 'ABSPATH' ) ) . 'wp-admin/includes/plugin.php';
				}

				$dropins = array_keys( _get_dropins() );

				foreach ( $dropins as $dropin ) {
					$dropin_path = trailingslashit( constant( 'WP_CONTENT_DIR' ) ) . $dropin;

					if ( $file !== $dropin_path ) {
						continue;
					}

					$type = 'dropin';
					$name = sprintf(
						// translators: %s is the drop-in file name.
						__( 'Drop-in: %s', '0-day-analytics' ),
						pathinfo( $dropin, PATHINFO_BASENAME )
					);
					break;
				}
			}

			$component = array(
				'type'    => $type,
				'name'    => $name,
				'context' => $context,
			);

			self::$file_components[ $file ] = $component;

			return $component;
		}

		/**
		 * Get the name of the altis vendor.
		 *
		 * @param string $file The file path.
		 *
		 * @return string The name of the altis vendor.
		 *
		 * @since 1.4.0
		 */
		private static function get_altis_vendor_name( string $file ): string {
			$plug = str_replace( \Altis\ROOT_DIR . '/vendor/', '', $file );
			$plug = explode( '/', $plug, 3 );
			$plug = $plug[0] . '/' . $plug[1];

			return sprintf(
				// translators: %s is the altis vendor name.
				__( 'Dependency: %s', '0-day-analytics' ),
				$plug
			);
		}

		/**
		 * Get the name of the plugin.
		 *
		 * @param string $file The file path.
		 * @param string $type The type of the plugin.
		 *
		 * @return string The name of the plugin.
		 *
		 * @since 1.4.0
		 */
		private static function get_plugin_name( string $file, string $type ): string {
			$file = str_replace( '/vendor/', '/', $file );
			$plug = plugin_basename( $file );
			if ( strpos( $plug, '/' ) ) {
				$plug = explode( '/', $plug )[0];
			} else {
				$plug = basename( $plug );
			}

			$name = sprintf(
				// translators: %s is the plugin name.
				( 'plugin' !== $type ? __( 'MU Plugin: %s', '0-day-analytics' ) : __( 'Plugin: %s', '0-day-analytics' ) ),
				$plug
			);

			return $name;
		}

		/**
		 * Get the name of the VIP plugin.
		 *
		 * @param string $file The file path.
		 * @param string $type The type of the plugin.
		 *
		 * @return string The name of the VIP plugin.
		 *
		 * @since 1.4.0
		 */
		private static function get_vip_plugin_name( string $file, string $type ): string {
			$plug = str_replace( self::$file_dirs[ $type ], '', $file );
			$plug = trim( $plug, '/' );
			if ( strpos( $plug, '/' ) ) {
				$plug = explode( '/', $plug )[0];
			} else {
				$plug = basename( $plug );
			}

			$name = sprintf(
				(
					'vip-client-mu-plugin' === $type ?
					// translators: %s is the VIP plugin name.
						__( 'VIP Client MU Plugin: %s', '0-day-analytics' ) :
						// translators: %s is the VIP plugin name.
						__( 'VIP Plugin: %s', '0-day-analytics' )
				),
				$plug
			);

			return $name;
		}

		/**
		 * Get the name of the other file.
		 *
		 * @param string $file The file path.
		 *
		 * @return string The name of the other file.
		 *
		 * @since 1.4.0
		 */
		private static function get_other_name( string $file ): string {
			$name  = self::standard_dir( $file );
			$name  = str_replace( dirname( self::$file_dirs['other'] ), '', $name );
			$parts = explode( '/', trim( $name, '/' ) );
			$name  = $parts[0] . '/' . $parts[1];

			return $name;
		}

		/**
		 * Get the file directories.
		 *
		 * @return array The file directories.
		 *
		 * @since 1.4.0
		 */
		public static function get_file_dirs(): array {
			if ( ! empty( self::$file_dirs ) ) {
				return self::$file_dirs;
			}

			self::$file_dirs = array(
				'plugin'               => WP_PLUGIN_DIR,
				'mu-vendor'            => WPMU_PLUGIN_DIR . '/vendor',
				'go-plugin'            => WPMU_PLUGIN_DIR . '/shared-plugins',
				'mu-plugin'            => WPMU_PLUGIN_DIR,
				'vip-plugin'           => get_theme_root() . '/vip/plugins',
				'vip-client-mu-plugin' => defined( 'WPCOM_VIP_CLIENT_MU_PLUGIN_DIR' ) ? \WPCOM_VIP_CLIENT_MU_PLUGIN_DIR : null,
				'altis-vendor'         => defined( '\Altis\ROOT_DIR' ) ? \Altis\ROOT_DIR . '/vendor' : null,
				'theme'                => null,
				'stylesheet'           => get_stylesheet_directory(),
				'template'             => get_template_directory(),
				'other'                => WP_CONTENT_DIR,
				'core'                 => ABSPATH,
				'unknown'              => null,
			);

			foreach ( self::$file_dirs as $type => &$dir ) {
				if ( null !== $dir ) {
					$dir = self::standard_dir( $dir );
				}
			}

			return self::$file_dirs;
		}

		/**
		 * Get the name of the time offset setting.
		 *
		 * @return string The UTC offset.
		 *
		 * @since 1.4.0
		 */
		public static function get_timezone_location(): string {

			$timezone_string = \get_option( 'timezone_string', '' );
			$gmt_offset      = \get_option( 'gmt_offset', 0 );

			if ( 'UTC' === $timezone_string || ( empty( $gmt_offset ) && empty( $timezone_string ) ) ) {
				return 'UTC';
			}

			if ( '' === $timezone_string ) {
				return self::get_utc_offset();
			}

			$parts = explode( '/', $timezone_string );

			return str_replace( '_', ' ', end( $parts ) );
		}


		/**
		 * Converts a period of time in seconds into a human-readable format representing the interval.
		 *
		 * Intervals less than an hour are displayed in minutes, and intervals less than a minute are
		 * displayed in seconds. All intervals are displayed in the two largest units.
		 *
		 * The `$accurate` parameter can be used to display an interval of less than an hour in minutes and seconds.
		 *
		 * @param  int|float $since    A period of time in seconds.
		 * @param  bool      $accurate Whether to display the interval in minutes and seconds.
		 *
		 * @return string An interval represented as a string.
		 *
		 * @since 1.4.0
		 */
		public static function interval( $since, bool $accurate = false ) {
			// Array of time period chunks.
			$chunks = array(
				/* translators: %s: The number of years in an interval of time. */
				array( YEAR_IN_SECONDS, _n_noop( '%s year', '%s years', '0-day-analytics' ) ),
				/* translators: %s: The number of months in an interval of time. */
				array( MONTH_IN_SECONDS, _n_noop( '%s month', '%s months', '0-day-analytics' ) ),
				/* translators: %s: The number of weeks in an interval of time. */
				array( WEEK_IN_SECONDS, _n_noop( '%s week', '%s weeks', '0-day-analytics' ) ),
				/* translators: %s: The number of days in an interval of time. */
				array( DAY_IN_SECONDS, _n_noop( '%s day', '%s days', '0-day-analytics' ) ),
				/* translators: %s: The number of hours in an interval of time. */
				array( HOUR_IN_SECONDS, _n_noop( '%s hour', '%s hours', '0-day-analytics' ) ),
				/* translators: %s: The number of minutes in an interval of time. */
				array( MINUTE_IN_SECONDS, _n_noop( '%s minute', '%s minutes', '0-day-analytics' ) ),
				/* translators: %s: The number of seconds in an interval of time. */
				array( 1, _n_noop( '%s second', '%s seconds', '0-day-analytics' ) ),
			);

			if ( $since <= 0 ) {
				return __( 'now', '0-day-analytics' );
			}

			if ( ( ! $accurate ) && ( $since >= MINUTE_IN_SECONDS ) && ( $since < HOUR_IN_SECONDS ) ) {
				$num = intval( floor( $since / MINUTE_IN_SECONDS ) );
				return sprintf(
					/* translators: %s: The number of minutes in an interval of time. */
					_n( '%s minute', '%s minutes', $num, '0-day-analytics' ),
					$num
				);
			}

			/**
			 * We only want to output two chunks of time here, eg:
			 * x years, xx months
			 * x days, xx hours
			 * so there's only two bits of calculation below:
			 */

			// Step one: the first chunk.
			foreach ( array_keys( $chunks ) as $i ) {
				$seconds = $chunks[ $i ][0];
				$name    = $chunks[ $i ][1];

				// Finding the biggest chunk (if the chunk fits, break).
				$count = (int) floor( $since / $seconds );
				if ( $count ) {
					break;
				}
			}

			// Set output var.
			$output = sprintf( translate_nooped_plural( $name, $count, '0-day-analytics' ), $count );

			// Step two: the second chunk.
			if ( $i + 1 < count( $chunks ) ) {
				$seconds2 = $chunks[ $i + 1 ][0];
				$name2    = $chunks[ $i + 1 ][1];
				$count2   = (int) floor( ( $since - ( $seconds * $count ) ) / $seconds2 );
				if ( $count2 ) {
					// Add to output var.
					$output .= ' ' . sprintf( translate_nooped_plural( $name2, $count2, '0-day-analytics' ), $count2 );
				}
			}

			return $output;
		}

		/**
		 * Returns a display value for a UTC offset.
		 *
		 * Examples:
		 *   - UTC
		 *   - UTC+4
		 *   - UTC-6
		 *
		 * @return string The UTC offset display value.
		 *
		 * @since 1.4.0
		 */
		public static function get_utc_offset() {
			$offset = get_option( 'gmt_offset', 0 );

			if ( empty( $offset ) ) {
				return 'UTC';
			}

			if ( 0 <= $offset ) {
				$formatted_offset = '+' . (string) $offset;
			} else {
				$formatted_offset = (string) $offset;
			}

			$formatted_offset = str_replace(
				array( '.25', '.5', '.75' ),
				array( ':15', ':30', ':45' ),
				$formatted_offset
			);

			return 'UTC' . $formatted_offset;
		}

		/**
		 * Checks global WP_Cron constant and its status - returns error if disabled, nothing otherwise.
		 *
		 * @return \WP_Error|null
		 *
		 * @since 1.7.4
		 */
		public static function check_cron_status() {

			if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) {
				return new \WP_Error(
					'cron_info',
					sprintf(
					/* translators: %s: The name of the PHP constant that is set. %s The url to the cron settings */
						__( 'The %1$s constant is set to true. WP-Cron spawning is disabled. Try to enable it in settings - %2$s', '0-day-analytics' ),
						'DISABLE_WP_CRON',
						'<a href="' . \add_query_arg( array( 'page' => Settings::SETTINGS_MENU_SLUG ), network_admin_url( 'admin.php' ) ) . '#aadvana-options-tab-cron-list">' . __( 'here', '0-day-analytics' ) . '</a>',
					)
				);
			}
		}

		/**
		 * Checks global WP_Debug constant and its status - returns error if disabled, nothing otherwise.
		 *
		 * @return \WP_Error|null
		 *
		 * @since 1.7.4
		 */
		public static function check_debug_status() {

			if ( ! defined( 'WP_DEBUG' ) || ! \WP_DEBUG ) {
				return new \WP_Error(
					'debug_off',
					sprintf(
					/* translators: %s: The name of the PHP constant that is set. %s The url to the cron settings */
						__( 'The %1$s constant is not set or it is set to false. WP Debug is disabled. Try to enable it in settings - %2$s', '0-day-analytics' ),
						'WP_DEBUG',
						'<a href="' . \add_query_arg( array( 'page' => Settings::SETTINGS_MENU_SLUG ), network_admin_url( 'admin.php' ) ) . '#aadvana-options-tab-error-log-list">' . __( 'here', '0-day-analytics' ) . '</a>',
					)
				);
			}
		}

		/**
		 * Checks global WP_Debug_Log constant and its status - returns error if disabled, nothing otherwise.
		 *
		 * @return \WP_Error|null
		 *
		 * @since 1.7.4
		 */
		public static function check_debug_log_status() {

			if ( ! defined( 'WP_DEBUG_LOG' ) || ! \WP_DEBUG_LOG ) {
				return new \WP_Error(
					'debug_log_off',
					sprintf(
					/* translators: %s: The name of the PHP constant that is set. %s The url to the cron settings */
						__( 'The %1$s constant is not set or it is set to false. WP Debug Log is disabled. Try to enable it in settings - %2$s', '0-day-analytics' ),
						'WP_DEBUG_LOG',
						'<a href="' . \add_query_arg( array( 'page' => Settings::SETTINGS_MENU_SLUG ), network_admin_url( 'admin.php' ) ) . '#aadvana-options-tab-error-log-list">' . __( 'here', '0-day-analytics' ) . '</a>',
					)
				);
			}
		}

		/**
		 * Extracts the admin path of the WordPress installation.
		 *
		 * @return string
		 *
		 * @since 1.8.0
		 */
		public static function get_admin_path(): string {
			if ( null === self::$admin_path ) {
				self::$admin_path = (string) str_replace( \get_home_url( 1 ) . '/', ABSPATH, \network_admin_url() );
			}

			return self::$admin_path;
		}

		/**
		 * Get the blog URL.
		 *
		 * @since 1.8.2
		 *
		 * @return string
		 */
		public static function get_blog_domain(): string {
			if ( self::is_multisite() ) {
				$blog_id     = function_exists( 'get_current_blog_id' ) ? \get_current_blog_id() : 0;
				$blog_domain = \get_blog_option( $blog_id, 'home' );
			} else {
				$blog_domain = \get_option( 'home' );
			}

			// Replace protocols.
			return str_replace( array( 'http://', 'https://' ), '', $blog_domain );
		}

		/**
		 * Check is this is a multisite setup.
		 *
		 * @return bool
		 *
		 * @since 1.8.2
		 */
		public static function is_multisite() {
			if ( null === self::$is_multisite ) {
				self::$is_multisite = function_exists( 'is_multisite' ) && is_multisite();
			}

			return self::$is_multisite;
		}

		/**
		 * Returns the the wp_screen property.
		 *
		 * @return \WP_Screen|null
		 *
		 * @since 1.8.4
		 */
		public static function get_wp_screen() {
			if ( empty( self::$wp_screen ) ) {
				self::$wp_screen = \get_current_screen();
			}

			return self::$wp_screen;
		}

		/**
		 * More unified time formatter for list items table view showing.
		 *
		 * @param array  $item - Item data.
		 * @param string $label_string  - Label string to show in the badge.
		 *
		 * @return string
		 *
		 * @since 1.8.4
		 */
		public static function time_formatter( array $item, string $label_string ): string {

			if ( ! isset( $item['schedule'] ) && isset( $item['timestamp'] ) ) {
				$item['schedule'] = $item['timestamp'];
			}

			if ( 1 === $item['schedule'] ) {
				return sprintf(
					'<span class="status-control-warning"><span class="dashicons dashicons-warning" aria-hidden="true"></span> %s</span>',
					\esc_html__( 'Immediately', '0-day-analytics' ),
				);
			}

					$time_format = 'g:i a';

					$event_datetime_utc = \gmdate( 'Y-m-d H:i:s', $item['schedule'] );

					$timezone_local  = \wp_timezone();
					$event_local     = \get_date_from_gmt( $event_datetime_utc, 'Y-m-d' );
					$today_local     = ( new \DateTimeImmutable( 'now', $timezone_local ) )->format( 'Y-m-d' );
					$tomorrow_local  = ( new \DateTimeImmutable( 'tomorrow', $timezone_local ) )->format( 'Y-m-d' );
					$yesterday_local = ( new \DateTimeImmutable( 'yesterday', $timezone_local ) )->format( 'Y-m-d' );

					// If the offset of the date of the event is different from the offset of the site, add a marker.
			if ( \get_date_from_gmt( $event_datetime_utc, 'P' ) !== get_date_from_gmt( 'now', 'P' ) ) {
				$time_format .= ' (P)';
			}

					$event_time_local = \get_date_from_gmt( $event_datetime_utc, $time_format );

			if ( $event_local === $today_local ) {
				$date = sprintf(
				/* translators: %s: Time */
					__( 'Today at %s', '0-day-analytics' ),
					$event_time_local,
				);
			} elseif ( $event_local === $tomorrow_local ) {
				$date = sprintf(
				/* translators: %s: Time */
					__( 'Tomorrow at %s', '0-day-analytics' ),
					$event_time_local,
				);
			} elseif ( $event_local === $yesterday_local ) {
				$date = sprintf(
				/* translators: %s: Time */
					__( 'Yesterday at %s', '0-day-analytics' ),
					$event_time_local,
				);
			} else {
				$date = sprintf(
				/* translators: 1: Date, 2: Time */
					__( '%1$s at %2$s', '0-day-analytics' ),
					\get_date_from_gmt( $event_datetime_utc, 'F jS' ),
					$event_time_local,
				);
			}

					$time = sprintf(
						'<time datetime="%1$s">%2$s</time>',
						\esc_attr( gmdate( 'c', $item['schedule'] ) ),
						\esc_html( $date )
					);

					$until = $item['schedule'] - time();
					$late  = Crons_Helper::is_late( $item );

			if ( $late ) {
				// Show a warning for events that are late.
				$ago = sprintf(
				/* translators: %s: Time period, for example "8 minutes" */
					__( '%s ago', '0-day-analytics' ),
					self::interval( abs( $until ) )
				);

				return sprintf(
					'<span class="badge red-badge status-control-warning"><span class="dashicons dashicons-warning" aria-hidden="true"></span> %s</span><br>%s',
					esc_html( $ago ),
					$time,
				) . '<br><span class="badge red-badge">' . $label_string . '</span>';
			}

			if ( $until <= 0 ) {
				$in = __( 'Now', '0-day-analytics' );
			} else {
				$in = sprintf(
				/* translators: %s: Time period, for example "8 minutes" */
					__( 'In %s', '0-day-analytics' ),
					self::interval( $until ),
				);
			}

					return sprintf(
						'%s<br><span class="badge green-badge"><span class="dashicons dashicons-clock" aria-hidden="true"></span> %s</span>',
						\esc_html( $in ),
						$time,
					);
		}

	}
}
