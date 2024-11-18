<?php
/**
 * Class: System status info collector.
 *
 * Helper class to determine the proper status of the request.
 *
 * @package advanced-analytics
 *
 * @since latest
 */

declare(strict_types=1);

namespace ADVAN\Helpers;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\ADVAN\Helpers\WP_Error_Handler' ) ) {
	class WP_Error_Handler {
		public static function handle_error( $errno, $errstr, $errfile, $errline, $errcontext = null ) {
			if ( ! ( error_reporting() & $errno ) ) {
				// This error code is not included in error_reporting, so let it fall.
				// through to the standard PHP error handler.
				return false;
			}

			// These are default values for a single trace.
			// To prevent errors when a trace ommits some values.
			$defaults = array(
				'line'     => '',
				'file'     => '',
				'class'    => '',
				'function' => '',
			);
			// $errfile  = self::clean_file_path( $errfile );
			$errname  = self::error_code_to_string( $errno );
			$out      = "$errname ($errno): $errstr" . PHP_EOL . 'Stack trace:' . PHP_EOL;

			$trace      = debug_backtrace();
			$main_shown = false;

			$thrown_file = '';
			$thrown_line = '';

			// skip current function and require() in /index.php .
			$counter = count( $trace ) - 1;
			for ( $i = 1; $i < $counter; $i++ ) {
				$sf    = (object) shortcode_atts( $defaults, $trace[ $i ] );
				$index = $i - 1;
				$file  = $sf->file;
				//$file  = self::clean_file_path( $sf->file );

				if ( 1 === $i ) {
					$thrown_file = $file;
					$thrown_line = $sf->line;
				}

				$caller = '';
				if ( ! empty( $sf->class ) && ! empty( $sf->function ) ) {
					$caller = $sf->class . '::' . $sf->function . '()';
				} elseif ( ! empty( $sf->function ) ) {
					$caller = $sf->function . '()';
				} else {
					$main_shown = true;
					$caller     = '{main}';
				}

				$out .= "#$index $file({$sf->line}): $caller" . PHP_EOL;

			}
			if ( ! $main_shown ) {
				$out .= '#' . ( $index++ ) . ' {main}' . PHP_EOL;
			}
			$out .= '  thrown in ' . $thrown_file . ' on line ' . $thrown_line;
			if ( WP_DEBUG_DISPLAY ) {
				echo nl2br( $out );
			}
			if ( WP_DEBUG_LOG ) {
				error_log( $out );
			}

			return true;
		}

		public static function trigger_error($status, string $function_name, $errstr, $version, $errno = E_USER_NOTICE) {
			$defaults = array(
				'line'     => '',
				'file'     => '',
				'class'    => '',
				'function' => '',
			);

			$errname  = self::error_code_to_string( $errno );
			$out      = "PHP $errname: $errstr" . PHP_EOL . 'Stack trace:' . PHP_EOL;

			$trace      = debug_backtrace();
			$main_shown = false;

			$thrown_file = '';
			$thrown_line = '';

			// skip current function and require() in /index.php .
			$counter = count( $trace ) - 3;
			for ( $i = 1; $i < $counter; $i++ ) {
				$sf    = (object) shortcode_atts( $defaults, $trace[ $i + 3 ] );
				$index = $i - 1;
				$file  = $sf->file;
				//$file  = self::clean_file_path( $sf->file );

				if ( 1 === $i ) {
					$thrown_file = $file;
					$thrown_line = $sf->line;
				}

				$caller = '';
				if ( ! empty( $sf->class ) && ! empty( $sf->function ) ) {
					$caller = $sf->class . '::' . $sf->function . '()';
				} elseif ( ! empty( $sf->function ) ) {
					$caller = $sf->function . '()';
				} else {
					$main_shown = true;
					$caller     = '{main}';
				}

				$out .= "#$index $file({$sf->line}): $caller" . PHP_EOL;

			}
			if ( ! $main_shown ) {
				$out .= '#' . ( $index++ ) . ' {main}' . PHP_EOL;
			}
			$out .= '  thrown in ' . $thrown_file . ' on line ' . $thrown_line;
			if ( WP_DEBUG_DISPLAY ) {
				echo nl2br( $out );
			}
			if ( WP_DEBUG_LOG ) {
				error_log( $out );
			}

			return false;
		}

		/**
		 * Removes root path of WordPress from a given directory.
		 *
		 * @param string $path - The path string to strip from.
		 *
		 * @return string
		 *
		 * @since latest
		 */
		private static function clean_file_path( $path ) {
			return str_replace( ABSPATH, '/', $path );
		}

		/**
		 * Gets the equivalent error code in string
		 *
		 * @param int $code - The code of the error.
		 *
		 * @return string
		 *
		 * @since latest
		 */
		private static function error_code_to_string( $code ) {
			$errors = array(
				1     => 'ERROR',
				2     => 'WARNING',
				4     => 'PARSE',
				8     => 'NOTICE',
				16    => 'CORE ERROR',
				32    => 'CORE WARNING',
				64    => 'COMPILE ERROR',
				128   => 'COMPILE WARNING',
				256   => 'USER ERROR',
				512   => 'USER WARNING',
				1024  => 'USER NOTICE',
				2048  => 'STRICT',
				4096  => 'RECOVERABLE ERROR',
				8192  => 'DEPRECATED',
				16384 => 'USER DEPRECATED',
			);
			if ( isset( $errors[ $code ] ) ) {
				return $errors[ $code ];
			} else {
				return 'UNKNOWN ERROR';
			}
		}
	}
}
