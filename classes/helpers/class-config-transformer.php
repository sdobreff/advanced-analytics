<?php
/**
 * Class: Determine the context in which the plugin is executed.
 *
 * Helper class to determine the proper status of the request.
 *
 * @package advanced-analytics
 *
 * @since 1.1.0
 *
 * This file originates from here - https://github.com/wp-cli/wp-config-transformer but like everything else that comes from the core team it requires lots of work and should follow the standards, so it is transformed
 */

declare(strict_types=1);

namespace ADVAN\Helpers;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\ADVAN\Helpers\Config_Transformer' ) ) {
	/**
	 * Transforms a wp-config.php file.
	 *
	 * @since 1.1.0
	 */
	class Config_Transformer {
		/**
		 * Append to end of file
		 *
		 * @since 1.1.0
		 */
		const ANCHOR_EOF = 'EOF';

		/**
		 * Path to the wp-config.php file.
		 *
		 * @var string
		 *
		 * @since 1.1.0
		 */
		private static $wp_config_path = null;

		/**
		 * Original source of the wp-config.php file.
		 *
		 * @var string
		 *
		 * @since 1.1.0
		 */
		private static $wp_config_src = null;

		/**
		 * Array of parsed configs.
		 *
		 * @var array
		 *
		 * @since 1.1.0
		 */
		private static $wp_configs = array();

		/**
		 * Instantiates the class with a valid wp-config.php.
		 *
		 * @throws \Exception If the wp-config.php file is missing.
		 * @throws \Exception If the wp-config.php file is not writable.
		 *
		 * @param string $wp_config_path Path to a wp-config.php file.
		 * @param bool   $read_only If the config is set to read-only.
		 *
		 * @return \WP_Error|void
		 *
		 * @since 1.1.0
		 */
		public static function init( $wp_config_path = '', $read_only = false ) {

			if ( empty( $wp_config_path ) ) {
				$wp_config_path = File_Helper::get_wp_config_file_path();
			}

			$basename = basename( $wp_config_path );

			if ( ! file_exists( $wp_config_path ) ) {
				return new \WP_Error(
					'wp_debug_off',
					__( "{$basename} does not exist.", '0-day-analytics' ) // phpcs:ignore WordPress.WP.I18n.InterpolatedVariableText
				);
				// throw new \Exception( "{$basename} does not exist." );
			}

			if ( ! $read_only && ! is_writable( $wp_config_path ) ) {
				return new \WP_Error(
					'wp_debug_off',
					__( "{$basename} is not writable.", '0-day-analytics' ) // phpcs:ignore WordPress.WP.I18n.InterpolatedVariableText
				);
				// throw new \Exception( "{$basename} is not writable." );
			}

			self::$wp_config_path = $wp_config_path;
		}

		/**
		 * Performs internal check and inits the class with defaults from the pluigin.
		 *
		 * @return void
		 *
		 * @since 1.1.0
		 */
		private static function auto_init() {
			if ( \is_null( self::$wp_config_path ) ) {
				self::init();
			}
		}

		/**
		 * Checks if a config exists in the wp-config.php file.
		 *
		 * @throws \Exception If the wp-config.php file is empty.
		 * @throws \Exception If the requested config type is invalid.
		 *
		 * @param string $type Config type (constant or variable).
		 * @param string $name Config name.
		 *
		 * @return bool
		 *
		 * @since 1.1.0
		 */
		public static function exists( $type, $name ) {

			self::auto_init();

			$wp_config_src = file_get_contents( self::$wp_config_path );

			if ( ! trim( $wp_config_src ) ) {
				throw new \Exception( 'Config file is empty.' );
			}
			// Normalize the newline to prevent an issue coming from OSX.
			self::$wp_config_src = str_replace( array( "\r\n", "\n\r", "\r" ), "\n", $wp_config_src );
			self::$wp_configs    = self::parse_wp_config( self::$wp_config_src );

			if ( ! isset( self::$wp_configs[ $type ] ) ) {
				throw new \Exception( "Config type '{$type}' does not exist." );
			}

			return isset( self::$wp_configs[ $type ][ $name ] );
		}

		/**
		 * Get the value of a config in the wp-config.php file.
		 *
		 * @throws \Exception If the wp-config.php file is empty.
		 * @throws \Exception If the requested config type is invalid.
		 *
		 * @param string $type Config type (constant or variable).
		 * @param string $name Config name.
		 *
		 * @return string|null
		 *
		 * @since 1.1.0
		 */
		public static function get_value( $type, $name ) {
			self::auto_init();

			$wp_config_src = file_get_contents( self::$wp_config_path );

			if ( ! trim( $wp_config_src ) ) {
				throw new \Exception( 'Config file is empty.' );
			}

			self::$wp_config_src = $wp_config_src;
			self::$wp_configs    = self::parse_wp_config( self::$wp_config_src );

			if ( ! isset( self::$wp_configs[ $type ] ) ) {
				throw new \Exception( "Config type '{$type}' does not exist." );
			}

			return self::$wp_configs[ $type ][ $name ]['value'];
		}

		/**
		 * Adds a config to the wp-config.php file.
		 *
		 * @throws \Exception If the config value provided is not a string.
		 * @throws \Exception If the config placement anchor could not be located.
		 *
		 * @param string $type    Config type (constant or variable).
		 * @param string $name    Config name.
		 * @param string $value   Config value.
		 * @param array  $options (optional) Array of special behavior options.
		 *
		 * @return bool
		 *
		 * @since 1.1.0
		 */
		public static function add( $type, $name, $value, array $options = array() ) {
			self::auto_init();

			if ( ! is_string( $value ) ) {
				throw new \Exception( 'Config value must be a string.' );
			}

			if ( self::exists( $type, $name ) ) {
				return false;
			}

			$defaults = array(
				'raw'       => false, // Display value in raw format without quotes.
				'anchor'    => "/* That's all, stop editing!", // Config placement anchor string.
				'separator' => PHP_EOL, // Separator between config definition and anchor string.
				'placement' => 'before', // Config placement direction (insert before or after).
			);

			list( $raw, $anchor, $separator, $placement ) = array_values( array_merge( $defaults, $options ) );

			$raw       = (bool) $raw;
			$anchor    = (string) $anchor;
			$separator = (string) $separator;
			$placement = (string) $placement;

			if ( self::ANCHOR_EOF === $anchor ) {
				$contents = self::$wp_config_src . self::normalize( $type, $name, self::format_value( $value, $raw ) );
			} else {
				if ( false === strpos( self::$wp_config_src, $anchor ) ) {
					throw new \Exception( 'Unable to locate placement anchor.' );
				}

				$new_src  = self::normalize( $type, $name, self::format_value( $value, $raw ) );
				$new_src  = ( 'after' === $placement ) ? $anchor . $separator . $new_src : $new_src . $separator . $anchor;
				$contents = str_replace( $anchor, $new_src, self::$wp_config_src );
			}

			return self::save( $contents );
		}

		/**
		 * Updates an existing config in the wp-config.php file.
		 *
		 * @throws \Exception If the config value provided is not a string.
		 *
		 * @param string $type    Config type (constant or variable).
		 * @param string $name    Config name.
		 * @param string $value   Config value.
		 * @param array  $options (optional) Array of special behavior options.
		 *
		 * @return bool
		 *
		 * @since 1.1.0
		 */
		public static function update( $type, $name, $value, array $options = array() ) {
			self::auto_init();

			$value = var_export( $value, true );

			if ( ! is_string( $value ) ) {
				throw new \Exception( 'Config value must be a string.' );
			}

			$defaults = array(
				'add'       => true, // Add the config if missing.
				'raw'       => false, // Display value in raw format without quotes.
				'normalize' => false, // Normalize config output using WP Coding Standards.
			);

			list( $add, $raw, $normalize ) = array_values( array_merge( $defaults, $options ) );

			$add       = (bool) $add;
			$raw       = (bool) $raw;
			$normalize = (bool) $normalize;

			if ( ! self::exists( $type, $name ) ) {
				return ( $add ) ? self::add( $type, $name, $value, $options ) : false;
			}

			$old_src   = self::$wp_configs[ $type ][ $name ]['src'];
			$old_value = self::$wp_configs[ $type ][ $name ]['value'];
			$new_value = self::format_value( $value, $raw );

			if ( $normalize ) {
				$new_src = self::normalize( $type, $name, $new_value );
			} else {
				$new_parts    = self::$wp_configs[ $type ][ $name ]['parts'];
				$new_parts[1] = str_replace( $old_value, $new_value, $new_parts[1] ); // Only edit the value part.
				$new_src      = implode( '', $new_parts );
			}

			$contents = preg_replace(
				sprintf( '/(?<=^|;|<\?php\s|<\?\s)(\s*?)%s/m', preg_quote( trim( $old_src ), '/' ) ),
				'$1' . str_replace( '$', '\$', trim( $new_src ) ),
				self::$wp_config_src
			);

			return self::save( $contents );
		}

		/**
		 * Removes a config from the wp-config.php file.
		 *
		 * @param string $type Config type (constant or variable).
		 * @param string $name Config name.
		 *
		 * @return bool
		 *
		 * @since 1.1.0
		 */
		public static function remove( $type, $name ) {
			self::auto_init();

			if ( ! self::exists( $type, $name ) ) {
				return false;
			}

			$pattern  = sprintf( '/(?<=^|;|<\?php\s|<\?\s)%s\s*(\S|$)/m', preg_quote( self::$wp_configs[ $type ][ $name ]['src'], '/' ) );
			$contents = preg_replace( $pattern, '$1', self::$wp_config_src );

			return self::save( $contents );
		}

		/**
		 * Applies formatting to a config value.
		 *
		 * @throws \Exception When a raw value is requested for an empty string.
		 *
		 * @param string $value Config value.
		 * @param bool   $raw   Display value in raw format without quotes.
		 *
		 * @return mixed
		 *
		 * @since 1.1.0
		 */
		protected static function format_value( $value, $raw ) {
			if ( $raw && '' === trim( $value ) ) {
				throw new \Exception( 'Raw value for empty string not supported.' );
			}

			return ( $raw ) ? $value : var_export( $value, true );
		}

		/**
		 * Normalizes the source output for a name/value pair.
		 *
		 * @throws \Exception If the requested config type does not support normalization.
		 *
		 * @param string $type  Config type (constant or variable).
		 * @param string $name  Config name.
		 * @param mixed  $value Config value.
		 *
		 * @return string
		 *
		 * @since 1.1.0
		 */
		protected static function normalize( $type, $name, $value ) {
			if ( 'constant' === $type ) {
				$placeholder = "define( '%s', %s );";
			} elseif ( 'variable' === $type ) {
				$placeholder = '$%s = %s;';
			} else {
				throw new \Exception( "Unable to normalize config type '{$type}'." );
			}

			return sprintf( $placeholder, $name, $value );
		}

		/**
		 * Parses the source of a wp-config.php file.
		 *
		 * @param string $src Config file source.
		 *
		 * @return array
		 *
		 * @since 1.1.0
		 */
		protected static function parse_wp_config( $src ) {
			self::auto_init();

			$configs             = array();
			$configs['constant'] = array();
			$configs['variable'] = array();

			// Strip comments.
			foreach ( token_get_all( $src ) as $token ) {
				if ( in_array( $token[0], array( T_COMMENT, T_DOC_COMMENT ), true ) ) {
					if ( '//' === $token[1] ) {
						// For empty line comments, actually remove empty line comments instead of all double-slashes.
						// See: https://github.com/wp-cli/wp-config-transformer/issues/47 .
						$src = preg_replace( '/' . preg_quote( '//', '/' ) . '$/m', '', $src );
					} else {
						$src = str_replace( $token[1], '', $src );
					}
				}
			}

			preg_match_all( '/(?<=^|;|<\?php\s|<\?\s)(\h*define\s*\(\s*[\'"](\w*?)[\'"]\s*)(,\s*(\'\'|""|\'.*?[^\\\\]\'|".*?[^\\\\]"|.*?)\s*)((?:,\s*(?:true|false)\s*)?\)\s*;)/ims', $src, $constants );
			preg_match_all( '/(?<=^|;|<\?php\s|<\?\s)(\h*\$(\w+)\s*=)(\s*(\'\'|""|\'.*?[^\\\\]\'|".*?[^\\\\]"|.*?)\s*;)/ims', $src, $variables );

			if ( ! empty( $constants[0] ) && ! empty( $constants[1] ) && ! empty( $constants[2] ) && ! empty( $constants[3] ) && ! empty( $constants[4] ) && ! empty( $constants[5] ) ) {
				foreach ( $constants[2] as $index => $name ) {
					$configs['constant'][ $name ] = array(
						'src'   => $constants[0][ $index ],
						'value' => $constants[4][ $index ],
						'parts' => array(
							$constants[1][ $index ],
							$constants[3][ $index ],
							$constants[5][ $index ],
						),
					);
				}
			}

			if ( ! empty( $variables[0] ) && ! empty( $variables[1] ) && ! empty( $variables[2] ) && ! empty( $variables[3] ) && ! empty( $variables[4] ) ) {
				// Remove duplicate(s), last definition wins.
				$variables[2] = array_reverse( array_unique( array_reverse( $variables[2], true ) ), true );
				foreach ( $variables[2] as $index => $name ) {
					$configs['variable'][ $name ] = array(
						'src'   => $variables[0][ $index ],
						'value' => $variables[4][ $index ],
						'parts' => array(
							$variables[1][ $index ],
							$variables[3][ $index ],
						),
					);
				}
			}

			return $configs;
		}

		/**
		 * Saves new contents to the wp-config.php file.
		 *
		 * @throws \Exception If the config file content provided is empty.
		 * @throws \Exception If there is a failure when saving the wp-config.php file.
		 *
		 * @param string $contents New config contents.
		 *
		 * @return bool
		 *
		 * @since 1.1.0
		 */
		protected static function save( $contents ) {
			self::auto_init();

			if ( ! trim( $contents ) ) {
				throw new \Exception( 'Cannot save the config file with empty contents.' );
			}

			if ( $contents === self::$wp_config_src ) {
				return false;
			}

			$result = file_put_contents( self::$wp_config_path, $contents, LOCK_EX );

			if ( false === $result ) {
				throw new \Exception( 'Failed to update the config file.' );
			}

			return true;
		}
	}
}
