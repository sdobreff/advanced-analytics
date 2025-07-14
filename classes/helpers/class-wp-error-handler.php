<?php
/**
 * Class: System status info collector.
 *
 * Helper class to determine the proper status of the request.
 *
 * @package advanced-analytics
 *
 * @since 1.1.0
 */

declare(strict_types=1);

namespace ADVAN\Helpers;

use ADVAN\Controllers\Slack;
use ADVAN\Advanced_Analytics;
use ADVAN\Controllers\Telegram;
use ADVAN\Controllers\Slack_API;
use ADVAN\Controllers\Telegram_API;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\ADVAN\Helpers\WP_Error_Handler' ) ) {
	/**
	 * Class: WP_Error_Handler
	 *
	 * Helper class to handle errors and exceptions.
	 *
	 * @since 1.1.0
	 */
	class WP_Error_Handler {

		/**
		 * Stores the original wp_die handles.
		 *
		 * @var callable|null
		 *
		 * @since 1.9.5
		 */
		public static $original_wp_die_handler = null;

		/**
		 * WP wp_die handler callback.
		 *
		 * @param callable $handler - The handler to call, this method will store the original and call it later on.
		 *
		 * @return callable
		 *
		 * @since 1.9.5
		 */
		public static function wp_die_handler( $handler ) {
			self::$original_wp_die_handler = $handler;

			return array( __CLASS__, 'wp_die_handler_callback' );
		}

		/**
		 * Custom wp_die handler callback
		 *
		 * @param string $message - The message to display.
		 * @param string $title - The title of the error.
		 * @param array  $args - Additional arguments for the error.
		 *
		 * @return callable|null
		 *
		 * @since 1.9.5
		 */
		public static function wp_die_handler_callback( $message, $title = '', $args = array() ) {

			if ( ! empty( $message ) ) {
				list( $get_message, $get_title, $parsed_args ) = _wp_die_process_input( $message, $title, $args );

				if ( is_string( $get_message ) && ! empty( $get_message ) ) {
					if ( ! empty( $parsed_args['additional_errors'] ) ) {
						$get_message = array_merge(
							array( $get_message ),
							\wp_list_pluck( $parsed_args['additional_errors'], 'message' )
						);

						$get_message = implode( ', ', $get_message );

					}
					$get_message = str_replace( array( "\n", "\r" ), ' ', $get_message );

					self::handle_error( \E_USER_NOTICE, \esc_html( $get_message ), '', '', null, 2, 0 );
				}
			}

			// If the original handler is set, call it.
			if ( self::$original_wp_die_handler ) {
				return call_user_func( self::$original_wp_die_handler, $message, $title, $args );
			}
		}

		/**
		 * Catches errors which come from the doing_it_wrong() function, WP core does not provide much information about what is really going on and where, this method adds some more information to the error log.
		 *
		 * @param bool   $errno - Whether to trigger the error for _doing_it_wrong() calls. Default true.
		 * @param string $errstr - The WP error string (message).
		 * @param string $errfile - The name of the function that triggered the error (this is the WP function which is not called right, not the real function that actually called it).

		 * @param string $errline - Since which WP version given error was added.
		 * @param int    $errcontext - The number of the error (type of the error - that probably never get set by WP and always falls to the default which is E_USER_NOTICE).
		 *
		 * @return bool
		 *
		 * @since 1.1.1
		 */
		public static function handle_error( $errno, $errstr, $errfile, $errline, $errcontext = null, int $remove_from = 1, int $count_to = 1 ) {
			/*
			// if ( ! ( error_reporting(E_ERROR) & $errno ) ) {
			// This error code is not included in error_reporting, so let it fall.
			// through to the standard PHP error handler.
			// return false;
			// }
			*/

			// These are default values for a single trace.
			// To prevent errors when a trace ommits some values.
			$defaults = array(
				'line'     => '',
				'file'     => '',
				'class'    => '',
				'function' => '',
			);
			// $errfile  = self::clean_file_path( $errfile );
			$php_error_name = self::error_code_to_string( $errno );
			$out            = "PHP $php_error_name: $errstr" . PHP_EOL . 'Stack trace:' . PHP_EOL;

			$trace      = debug_backtrace();
			$main_shown = false;

			$thrown_file = '';
			$thrown_line = '';

			// skip current function and require() in /index.php .
			$counter = count( $trace ) - $count_to;
			for ( $i = $remove_from; $i < $counter; $i++ ) {
				$sf    = (object) shortcode_atts( $defaults, $trace[ $i ] );
				$index = $i - 1;
				$file  = $sf->file;
				// $file  = self::clean_file_path( $sf->file );

				if ( $remove_from === $i ) {
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
				$out .= '#' . ( ++$index ) . ' {main}' . PHP_EOL;
			}
			$out .= '  thrown in ' . $thrown_file . ' on line ' . $thrown_line;
			if ( WP_DEBUG_LOG ) {
				error_log( $out );
			}

			return true;
		}

		/**
		 * Catches errors which come from the doing_it_wrong() function, WP core does not provide much information about what is really going on and where, this method adds some more information to the error log.
		 *
		 * @param bool   $status - Whether to trigger the error for _doing_it_wrong() calls. Default true.
		 * @param string $function_name - The name of the function that triggered the error (this is the WP function which is not called right, not the real function that actually called it).
		 * @param string $errstr - The WP error string (message).
		 * @param string $version - Since which WP version given error was added.
		 * @param int    $errno - The number of the error (type of the error - that probably never get set by WP and always falls to the default which is E_USER_NOTICE).
		 *
		 * @return bool
		 *
		 * @since 1.1.1
		 */
		public static function trigger_error( $status, string $function_name, $errstr, $version, $errno = E_USER_NOTICE ) {

			if ( false === $status ) {
				return $status;
			}

			if ( Advanced_Analytics::is_just_in_time_for_0_day_domain( $function_name, $errstr ) ) {
				// This error code is not included in error_reporting, so let it fall.
				// through to the standard PHP error handler.
				return false;
			}

			$defaults = array(
				'line'     => '',
				'file'     => '',
				'class'    => '',
				'function' => '',
			);

			$php_error_name = self::error_code_to_string( $errno );
			$out            = "PHP $php_error_name: $errstr" . PHP_EOL . 'Stack trace:' . PHP_EOL;

			$trace      = debug_backtrace();
			$main_shown = false;

			$thrown_file = '';
			$thrown_line = '';

			$args = '';

			// skip current function and require() in /index.php .
			$counter = count( $trace ) - 3;
			for ( $i = 1; $i < $counter; $i++ ) {
				$sf    = (object) shortcode_atts( $defaults, $trace[ $i + 3 ] );
				$index = $i - 1;
				$file  = $sf->file;
				// $file  = self::clean_file_path( $sf->file );

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

				if ( ! $main_shown && isset( $trace[ $i + 3 ]['args'] ) && ! empty( $trace[ $i + 3 ]['args'] ) ) {
					$args = ' Arguments ' . \htmlentities( \json_encode( $trace[ $i + 3 ]['args'] ) );
				} else {
					$args = '';
				}

				$out .= "#$index $file({$sf->line}): $caller $args" . PHP_EOL;

			}
			if ( ! $main_shown ) {
				$out .= '#' . ( ++$index ) . ' {main}' . PHP_EOL;
			}
			$out .= '  thrown in ' . $thrown_file . ' on line ' . $thrown_line;
			if ( WP_DEBUG_LOG ) {
				error_log( $out );
			}

			return $status;
		}

		/**
		 * Catches errors which come from the doing_it_wrong() function, WP core does not provide much information about what is really going on and where, this method adds some more information to the error log.
		 *
		 * @param string $deprecated_name - Name of the deprecated.
		 * @param string $replacement - What can be used as replacement.
		 * @param string $version - Since which WP version given error was added.
		 *
		 * @return void
		 *
		 * @since 1.1.1
		 */
		public static function deprecated_error( string $deprecated_name, $replacement, $version ) {

			$defaults = array(
				'line'     => '',
				'file'     => '',
				'class'    => '',
				'function' => '',
			);

			$php_error_name = 'DEPRECATED';
			$out            = "PHP $php_error_name: $deprecated_name is deprecated" . PHP_EOL . 'Stack trace:' . PHP_EOL;

			$trace      = debug_backtrace();
			$main_shown = false;

			$thrown_file = '';
			$thrown_line = '';

			$args = '';

			// skip current function and require() in /index.php .
			$counter = count( $trace ) - 3;
			for ( $i = 1; $i < $counter; $i++ ) {
				$sf    = (object) shortcode_atts( $defaults, $trace[ $i + 3 ] );
				$index = $i - 1;
				$file  = $sf->file;
				// $file  = self::clean_file_path( $sf->file );

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

				if ( ! $main_shown && isset( $trace[ $i + 3 ]['args'] ) && ! empty( $trace[ $i + 3 ]['args'] ) ) {
					$args = ' Arguments ' . \htmlentities( \json_encode( $trace[ $i + 3 ]['args'] ) );
				} else {
					$args = '';
				}

				$out .= "#$index $file({$sf->line}): $caller $args" . PHP_EOL;

			}
			if ( ! $main_shown ) {
				$out .= '#' . ( ++$index ) . ' {main}' . PHP_EOL;
			}
			$out .= '  thrown in ' . $thrown_file . ' on line ' . $thrown_line;
			if ( WP_DEBUG_LOG ) {
				error_log( $out );
			}
		}

		/**
		 * Removes root path of WordPress from a given directory.
		 *
		 * @param string $path - The path string to strip from.
		 *
		 * @return string
		 *
		 * @since 1.1.0
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
		 * @since 1.1.0
		 */
		public static function error_code_to_string( $code ) {
			$errors = array(
				1     => 'ERROR', // E_ERROR.
				2     => 'WARNING', // E_WARNING.
				4     => 'PARSE', // E_PARSE.
				8     => 'NOTICE', // E_NOTICE.
				16    => 'CORE ERROR', // E_CORE_ERROR.
				32    => 'CORE WARNING', // E_CORE_WARNING.
				64    => 'COMPILE ERROR', // E_COMPILE_ERROR.
				128   => 'COMPILE WARNING', // E_COMPILE_WARNING.
				256   => 'USER ERROR', // E_USER_ERROR.
				512   => 'USER WARNING', // E_USER_WARNING.
				1024  => 'USER NOTICE', // E_USER_NOTICE.
				2048  => 'STRICT', // E_STRICT.
				4096  => 'RECOVERABLE ERROR', // E_RECOVERABLE_ERROR.
				8192  => 'DEPRECATED', // E_DEPRECATED.
				16384 => 'USER DEPRECATED', // E_USER_DEPRECATED.
				32767 => 'ALL ERRORS', // E_ALL.
			);
			if ( isset( $errors[ $code ] ) ) {
				return $errors[ $code ];
			} else {
				return 'UNKNOWN ERROR';
			}
		}

		/**
		 * Log REST API errors
		 *
		 * @param WP_REST_Response $result  Result that will be sent to the client.
		 * @param WP_REST_Server   $server  The API server instance.
		 * @param WP_REST_Request  $request The request used to generate the response.
		 *
		 * @since 1.9.3
		 */
		public static function log_rest_api_errors( $result, $server, $request ) {
			if ( $result->is_error() ) {
				error_log(
					sprintf(
						'REST API request: %s:',
						$request->get_route(),
					) . \PHP_EOL .
					var_export( $request->get_params(), true )
				);
				error_log(
					sprintf(
						'REST API %s: %s.',
						$result->get_data()['code'],
						$result->get_data()['message'],
					) . \PHP_EOL .
					var_export( $result->get_data(), true )
				);
			}

			return $result;
		}

		/**
		 * Shutdown function to handle errors.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public static function shutdown() {

			$error = error_get_last();

			if ( null !== $error && ( \in_array( $error['type'], array( 1, 4 ) ) ) ) {
				$errno   = $error['type'];
				$errfile = $error['file'];
				$errline = $error['line'];
				$errstr  = $error['message'];

				if ( Slack::is_set() ) {
					// Send error to Slack.
					Slack_API::send_slack_message_via_api( null, null, ( WP_Helper::get_blog_domain() . "\n" . self::error_code_to_string( $errno ) . ' ' . $errstr . ' ' . $errfile . ' ' . $errline ) );
				}

				if ( Telegram::is_set() ) {
					// Send error to \Telegram.
					Telegram_API::send_telegram_message_via_api( null, null, ( WP_Helper::get_blog_domain() . "\n" . self::error_code_to_string( $errno ) . ' ' . $errstr . ' ' . $errfile . ' ' . $errline ) );
				}
			}
		}

		/**
		 * Uncaught error handler.
		 *
		 * @param Throwable $e - The error or exception.
		 *
		 * @return void
		 *
		 * @since 1.8.4
		 */
		public static function exception_handler( $e ) {
			$error = 'Uncaught Error';

			if ( $e instanceof \Exception ) {
				$error = 'Uncaught Exception';
			}

			if ( Slack::is_set() ) {
				// Send error to Slack.
				Slack_API::send_slack_message_via_api( null, null, ( WP_Helper::get_blog_domain() . "\n" . $error . ' ' . $e->getMessage() . ' ' . $e->getFile() . ' ' . $e->getLine() ) );
			}

			if ( Telegram::is_set() ) {
				// Send error to \Telegram.
				Telegram_API::send_telegram_message_via_api( null, null, ( WP_Helper::get_blog_domain() . "\n" . $error . ' ' . $e->getMessage() . ' ' . $e->getFile() . ' ' . $e->getLine() ) );
			}

			$main_shown = false;

			$out = sprintf(
				'PHP FATAL: %s in %s on line %d',
				$e->getMessage(),
				$e->getFile(),
				$e->getLine(),
			);

			$out .= PHP_EOL . 'Stack trace:' . PHP_EOL;

			$defaults = array(
				'line'     => '',
				'file'     => '',
				'class'    => '',
				'function' => '',
			);

			$counter = count( $e->getTrace() );
			for ( $i = 0; $i < $counter; $i++ ) {
				$sf    = (object) shortcode_atts( $defaults, $e->getTrace()[ $i ] );
				$index = $i;
				$file  = $sf->file;
				// $file  = self::clean_file_path( $sf->file );

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

				if ( ! $main_shown && isset( $trace[ $i + 3 ]['args'] ) && ! empty( $trace[ $i + 3 ]['args'] ) ) {
					$args = ' Arguments ' . \htmlentities( \json_encode( $trace[ $i + 3 ]['args'] ) );
				} else {
					$args = '';
				}

				$out .= "#$index $file({$sf->line}): $caller $args" . PHP_EOL;

			}
			if ( ! $main_shown ) {
				$out .= '#' . ( ++$index ) . ' {main}' . PHP_EOL;
			}
			$out .= '  thrown in ' . $thrown_file . ' on line ' . $thrown_line;

			if ( WP_DEBUG_LOG ) {
				\error_log( $out );
			}

			// $message = sprintf(
			// '%s in %s on line %d trace: %s',
			// $e->getMessage(),
			// $e->getFile(),
			// $e->getLine(),
			// $e->getTrace()
			// );

			// \error_log( $message );
		}

		/**
		 * Triggers on mail error
		 *
		 * @param \WP_error $wp_error - The actual error.
		 *
		 * @return void
		 *
		 * @since 2.1.0
		 */
		public static function on_mail_error( $wp_error ) {

			if ( \is_wp_error( $wp_error ) ) {
				self::log_wp_error( $wp_error );
			}
		}

		/**
		 * Logs error of type WP_Error in the error log
		 *
		 * @param \WP_Error $error - The error to log.
		 *
		 * @return void
		 *
		 * @since 2.1.0
		 */
		public static function log_wp_error( \WP_Error $error ) {
			$error_data = $error->get_all_error_data();
			\array_walk_recursive(
				$error_data,
				function( &$leaf ) {
					if ( is_string( $leaf ) ) {
						$leaf = \esc_html( $leaf );
					}
				}
			);
			error_log(
				sprintf(
					'WP_Error error: %s: %s.',
					$error->get_error_code(),
					$error->get_error_message(),
				) . \PHP_EOL .
				var_export( $error_data, true ),
			);
		}
	}
}
