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
	 * @since latest
	 */
	class WP_Helper {

		/**
		 * Holds the file components array.
		 *
		 * @var array<string, QM_Component>
		 *
		 * @since latest
		 */
		protected static $file_components = array();

		/**
		 * Holds the file components array.
		 *
		 * @var array<string, string|null>
		 *
		 * @since latest
		 */
		private static $file_dirs = array();

		/**
		 * Holds the abs path of the WordPress installation.
		 *
		 * @var string|null
		 *
		 * @since latest
		 */
		private static $abspath = null;

		/**
		 * Holds the content path of the WordPress installation.
		 *
		 * @var string|null
		 *
		 * @since latest
		 */
		private static $contentpath = null;

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
		 * @since latest
		 */
		public static function verify_admin_nonce( string $action, string $nonce_name = '_wpnonce' ): void {
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
		 * @since latest
		 */
		public static function get_cron_callbacks( $name ) {
			global $wp_filter;

			$actions = array();

			if ( isset( $wp_filter[ $name ] ) ) {
				// See http://core.trac.wordpress.org/ticket/17817.
				$action = $wp_filter[ $name ];

				foreach ( $action as $priority => $callbacks ) {
					foreach ( $callbacks as $callback ) {
						$callback = self::populate_callback( $callback );

						if ( __NAMESPACE__ . '\\pauser()' === $callback['name'] ) {
							continue;
						}

						$actions[] = array(
							'priority' => $priority,
							'callback' => $callback,
						);
					}
				}
			}

			return $actions;
		}

		/**
		 * Tries to extract as much information as possible from a callback.
		 *
		 * @param array<string, mixed> $callback - The callback to populate.
		 *
		 * @return array<string, mixed>
		 * @phpstan-return array{
		 *   name?: string,
		 *   file?: string|false,
		 *   line?: string|false,
		 *   error?: WP_Error,
		 *   component?: QM_Component,
		 * }
		 *
		 * @since latest
		 */
		public static function populate_callback( array $callback ) {

			if ( is_string( $callback['function'] ) && ( false !== strpos( $callback['function'], '::' ) ) ) {
				$callback['function'] = explode( '::', $callback['function'] );
			}

			if ( isset( $callback['class'] ) ) {
				$callback['function'] = array(
					$callback['class'],
					$callback['function'],
				);
			}

			try {

				if ( is_array( $callback['function'] ) ) {
					if ( is_object( $callback['function'][0] ) ) {
						$class  = get_class( $callback['function'][0] );
						$access = '->';
					} else {
						$class  = $callback['function'][0];
						$access = '::';
					}

					$callback['name'] = self::shorten_fqn( $class . $access . $callback['function'][1] ) . '()';
					$ref              = new \ReflectionMethod( $class, $callback['function'][1] );
				} elseif ( is_object( $callback['function'] ) ) {
					if ( $callback['function'] instanceof \Closure ) {
						$ref      = new \ReflectionFunction( $callback['function'] );
						$filename = $ref->getFileName();

						if ( $filename ) {
							$file = self::standard_dir( $filename, '' );
							if ( 0 === strpos( $file, '/' ) ) {
								$file = basename( $filename );
							}
							$callback['name'] = sprintf(
							/* translators: A closure is an anonymous PHP function. 1: Line number, 2: File name */
								__( 'Closure on line %1$d of %2$s', '0-day-analytics' ),
								$ref->getStartLine(),
								$file
							);
						} else {
							/* translators: A closure is an anonymous PHP function */
							$callback['name'] = __( 'Unknown closure', '0-day-analytics' );
						}
					} else {
						// the object should have a __invoke() method.
						$class            = get_class( $callback['function'] );
						$callback['name'] = self::shorten_fqn( $class ) . '->__invoke()';
						$ref              = new \ReflectionMethod( $class, '__invoke' );
					}
				} else {
					$callback['name'] = self::shorten_fqn( $callback['function'] ) . '()';
					$ref              = new \ReflectionFunction( $callback['function'] );
				}

				$callback['file'] = $ref->getFileName();
				$callback['line'] = $ref->getStartLine();

				$name = trim( $ref->getName() );

				if ( '__lambda_func' === $name || 0 === strpos( $name, 'lambda_' ) ) {
					if ( $callback['file'] && preg_match( '|(?P<file>.*)\((?P<line>[0-9]+)\)|', $callback['file'], $matches ) ) {
						$callback['file'] = $matches['file'];
						$callback['line'] = $matches['line'];
						$file             = trim( self::standard_dir( $callback['file'], '' ), '/' );
						/* translators: 1: Line number, 2: File name */
						$callback['name'] = sprintf( __( 'Anonymous function on line %1$d of %2$s', '0-day-analytics' ), $callback['line'], $file );
					} else {
						unset( $callback['line'], $callback['file'] );
						$callback['name']  = $name . '()';
						$callback['error'] = new \WP_Error( 'unknown_lambda', __( 'Unable to determine source of lambda function', '0-day-analytics' ) );
					}
				}

				if ( ! empty( $callback['file'] ) ) {
					$callback['component'] = self::get_file_component( $callback['file'] );
				} else {
					$callback['component']            = array();
					$callback['component']['type']    = 'php';
					$callback['component']['name']    = 'PHP';
					$callback['component']['context'] = '';
				}
			} catch ( \ReflectionException $e ) {

				$callback['error'] = new \WP_Error( 'reflection_exception', $e->getMessage() );

			}

			unset( $callback['function'], $callback['class'] );

			return $callback;
		}


		/**
		 * Converts and extracts standardized patch from directory
		 *
		 * @param string $dir - The directory to check.
		 * @param string $path_replace - The path to replace with.
		 *
		 * @return string
		 *
		 * @since latest
		 */
		public static function standard_dir( $dir, $path_replace = null ): string {

			$dir = self::normalize_path( $dir );

			if ( is_string( $path_replace ) ) {
				if ( ! self::$abspath ) {
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
		 * @since latest
		 */
		public static function normalize_path( $path ) {
			if ( function_exists( 'wp_normalize_path' ) ) {
				$path = \wp_normalize_path( $path );
			} else {
				$path = str_replace( '\\', '/', $path );
				$path = str_replace( '//', '/', $path );
			}

			return $path;
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
		 * @since latest
		 */
		public static function shorten_fqn( $fqn ) {
			if ( substr_count( $fqn, '\\' ) < 3 ) {
				return $fqn;
			}

			return preg_replace_callback(
				'#\\\\[a-zA-Z0-9_\\\\]{4,}\\\\#',
				function( array $matches ) {
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
		 * @return QM_Component An object representing the component.
		 *
		 * @since latest
		 */
		public static function get_file_component( $file ) {
			$file = self::standard_dir( $file );
			$type = '';

			if ( isset( self::$file_components[ $file ] ) ) {
				return self::$file_components[ $file ];
			}

			foreach ( self::get_file_dirs() as $type => $dir ) {
				// this slash makes paths such as plugins-mu match mu-plugin not plugin.
				if ( $dir && ( 0 === strpos( $file, trailingslashit( $dir ) ) ) ) {
					break;
				}
			}

			$context = $type;

			switch ( $type ) {
				case 'altis-vendor':
					$plug = str_replace( \Altis\ROOT_DIR . '/vendor/', '', $file );
					$plug = explode( '/', $plug, 3 );
					$plug = $plug[0] . '/' . $plug[1];
					/* translators: %s: Dependency name */
					$name = sprintf( __( 'Dependency: %s', '0-day-analytics' ), $plug );
					break;
				case 'plugin':
				case 'mu-plugin':
				case 'mu-vendor':
					$plug = str_replace( '/vendor/', '/', $file );
					$plug = plugin_basename( $plug );
					if ( strpos( $plug, '/' ) ) {
						$plug = explode( '/', $plug );
						$plug = reset( $plug );
					} else {
						$plug = basename( $plug );
					}
					if ( 'plugin' !== $type ) {
						/* translators: %s: Plugin name */
						$name = sprintf( __( 'MU Plugin: %s', '0-day-analytics' ), $plug );
					} else {
						/* translators: %s: Plugin name */
						$name = sprintf( __( 'Plugin: %s', '0-day-analytics' ), $plug );
					}
					$context = $plug;
					break;
				case 'go-plugin':
				case 'vip-plugin':
				case 'vip-client-mu-plugin':
					$plug = str_replace( self::$file_dirs[ $type ], '', $file );
					$plug = trim( $plug, '/' );
					if ( strpos( $plug, '/' ) ) {
						$plug = explode( '/', $plug );
						$plug = reset( $plug );
					} else {
						$plug = basename( $plug );
					}
					if ( 'vip-client-mu-plugin' === $type ) {
						/* translators: %s: Plugin name */
						$name = sprintf( __( 'VIP Client MU Plugin: %s', '0-day-analytics' ), $plug );
					} else {
						/* translators: %s: Plugin name */
						$name = sprintf( __( 'VIP Plugin: %s', '0-day-analytics' ), $plug );
					}
					$context = $plug;
					break;
				case 'stylesheet':
					if ( is_child_theme() ) {
						$name = __( 'Child Theme', '0-day-analytics' );
					} else {
						$name = __( 'Theme', '0-day-analytics' );
					}
					$type = 'theme';
					break;
				case 'template':
					$name = __( 'Parent Theme', '0-day-analytics' );
					$type = 'theme';
					break;
				case 'other':
					// Anything else that's within the content directory should appear as
					// `wp-content/{dir}` or `wp-content/{file}`.
					$name    = self::standard_dir( $file );
					$name    = str_replace( dirname( self::$file_dirs['other'] ), '', $name );
					$parts   = explode( '/', trim( $name, '/' ) );
					$name    = $parts[0] . '/' . $parts[1];
					$context = $file;
					break;
				case 'core':
					$name = __( 'WordPress Core', '0-day-analytics' );
					break;
				case 'unknown':
				default:
					$name = __( 'Unknown', '0-day-analytics' );
					break;
			}

			if ( 'other' === $type ) {
				if ( ! function_exists( '_get_dropins' ) ) {
					require_once trailingslashit( constant( 'ABSPATH' ) ) . 'wp-admin/includes/plugin.php';
				}

				/** @var array<int, string> $dropins */
				$dropins = array_keys( _get_dropins() );

				foreach ( $dropins as $dropin ) {
					$dropin_path = trailingslashit( constant( 'WP_CONTENT_DIR' ) ) . $dropin;

					if ( $file !== $dropin_path ) {
						continue;
					}

					$type = 'dropin';
					/* translators: %s: Drop-in plugin file name */
					$name = sprintf( __( 'Drop-in: %s', '0-day-analytics' ), pathinfo( $dropin, PATHINFO_BASENAME ) );
				}
			}

			$component            = array();
			$component['type']    = $type;
			$component['name']    = $name;
			$component['context'] = $context;

			self::$file_components[ $file ] = $component;

			return self::$file_components[ $file ];
		}

		/**
		 * @return array<string, string|null>
		 *
		 * @since latest
		 */
		public static function get_file_dirs() {
			if ( empty( self::$file_dirs ) ) {

				self::$file_dirs['plugin']     = WP_PLUGIN_DIR;
				self::$file_dirs['mu-vendor']  = WPMU_PLUGIN_DIR . '/vendor';
				self::$file_dirs['go-plugin']  = WPMU_PLUGIN_DIR . '/shared-plugins';
				self::$file_dirs['mu-plugin']  = WPMU_PLUGIN_DIR;
				self::$file_dirs['vip-plugin'] = get_theme_root() . '/vip/plugins';

				if ( defined( 'WPCOM_VIP_CLIENT_MU_PLUGIN_DIR' ) ) {
					self::$file_dirs['vip-client-mu-plugin'] = WPCOM_VIP_CLIENT_MU_PLUGIN_DIR;
				}

				if ( defined( '\Altis\ROOT_DIR' ) ) {
					self::$file_dirs['altis-vendor'] = \Altis\ROOT_DIR . '/vendor';
				}

				self::$file_dirs['theme']      = null;
				self::$file_dirs['stylesheet'] = get_stylesheet_directory();
				self::$file_dirs['template']   = get_template_directory();
				self::$file_dirs['other']      = WP_CONTENT_DIR;
				self::$file_dirs['core']       = ABSPATH;
				self::$file_dirs['unknown']    = null;

				foreach ( self::$file_dirs as $type => $dir ) {
					if ( null === $dir ) {
						continue;
					}

					self::$file_dirs[ $type ] = self::standard_dir( $dir );
				}
			}

			return self::$file_dirs;
		}
	}
}
