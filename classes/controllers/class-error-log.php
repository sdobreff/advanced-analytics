<?php
/**
 * Responsible for operations related to the error log file.
 *
 * @package advanced-analytics
 *
 * @since 1.1.0
 */

declare(strict_types=1);

namespace ADVAN\Controllers;

use ADVAN\Lists\Logs_List;
use ADVAN\Helpers\Settings;
use ADVAN\Helpers\File_Helper;
use ADVAN\Helpers\Log_Line_Parser;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\ADVAN\Controllers\Error_Log' ) ) {
	/**
	 * Responsible for operations related to the error log file.
	 *
	 * @since 1.1.0
	 */
	class Error_Log {

		/**
		 * Path to the error log file.
		 *
		 * @var string|null
		 *
		 * @since 1.1.0
		 */
		private static $log_file = null;

		/**
		 * Stores last error (if exists).
		 *
		 * @var string|null
		 *
		 * @since 1.1.0
		 */
		private static $last_error = null;

		/**
		 * Tries to detect the log filename.
		 *
		 * @return string|\WP_Error
		 *
		 * @since 1.1.0
		 */
		public static function autodetect() {
			if ( null === self::$log_file ) {
				$log_errors            = \strtolower( \strval( \ini_get( 'log_errors' ) ) );
				$error_logging_enabled = ! empty( $log_errors ) && ! \in_array( $log_errors, array( 'off', '0', 'false', 'no' ), true );
				self::$log_file        = \ini_get( 'error_log' );

				/**
				 * If the user has enabled the option to keep the error log, we will not check for the WP Debug and WP Debug Log.
				 */
				if ( ! Settings::get_option( 'keep_reading_error_log' ) ) {

					// First check if the WP Debug is enabled.
					if ( ! \defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
						self::$last_error = new \WP_Error(
							'wp_debug_off',
							__( 'WP Debug is disabled.', '0-day-analytics' )
						);
						return self::$last_error;
					}

					// Second check if the WP Debug Log is enabled.
					if ( ! \defined( 'WP_DEBUG_LOG' ) || ! WP_DEBUG_LOG ) {
						self::$last_error = new \WP_Error(
							'wp_debug_log_off',
							__( 'WP Debug Log is disabled.', '0-day-analytics' )
						);
						return self::$last_error;
					}
				}

				// Check for common problems that could prevent us from displaying the error log.
				if ( ! $error_logging_enabled ) {
					self::$last_error = new \WP_Error(
						'log_errors_off',
						__( 'Error logging is disabled.', '0-day-analytics' )
					);
					return self::$last_error;
				} elseif ( empty( self::$log_file ) ) {
					self::$last_error = new \WP_Error(
						'error_log_not_set',
						__( 'Error log filename is not set.', '0-day-analytics' )
					);
					return self::$last_error;
				} elseif ( ( strpos( self::$log_file, '/' ) === false ) && ( strpos( self::$log_file, '\\' ) === false ) ) {
					self::$last_error = new \WP_Error(
						'error_log_uses_relative_path',
						sprintf(
						// translators: the name of the log file.
							__( 'The current error_log value <code>%s</code> is not supported. Please change it to an absolute path.', '0-day-analytics' ),
							esc_html( self::$log_file )
						)
					);
					return self::$last_error;
				} elseif ( ! file_exists( self::$log_file ) ) {

					self::$last_error = new \WP_Error(
						'error_log_not_exists',
						sprintf(
						// translators: the name of the log file.
							__( 'The log file <code>%s</code> does not exists.', '0-day-analytics' ),
							esc_html( self::$log_file )
						)
					);
					return self::$last_error;
				} elseif ( ! is_writable( self::$log_file ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable
					self::$last_error = new \WP_Error(
						'error_log_not_writable',
						sprintf(
						// translators: the name of the log file.
							__( 'The log file <code>%s</code> exists, but is not writable. Please check file permissions.', '0-day-analytics' ),
							esc_html( self::$log_file )
						)
					);
					return self::$last_error;
				} elseif ( file_exists( self::$log_file ) && ! is_readable( self::$log_file ) ) {
					self::$last_error = new \WP_Error(
						'error_log_not_accessible',
						sprintf(
						// translators: the name of the log file.
							__( 'The log file <code>%s</code> exists, but is not accessible. Please check file permissions.', '0-day-analytics' ),
							esc_html( self::$log_file )
						)
					);
					return self::$last_error;
				}
			}

			return self::$log_file;
		}

		/**
		 * Truncates the given file.
		 *
		 * @param string|resource $filename - The name of the file.
		 *
		 * @return void
		 *
		 * @since 1.1.0
		 */
		public static function clear( $filename ) {
			$filename = self::extract_file_name( $filename );
			if ( $filename && \is_writable( $filename ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable
				$handle = \fopen( $filename, 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen

				if ( false !== $handle ) {
					\fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
				}
			}
		}

		/**
		 * Returns the file size.
		 *
		 * @param string|resource $filename - The name of the file.
		 *
		 * @return int|false
		 *
		 * @since 1.1.0
		 */
		public static function get_file_size( $filename ) {
			$filename = self::extract_file_name( $filename );
			return $filename ? filesize( $filename ) : false;
		}

		/**
		 * Returns the modification time of a file.
		 *
		 * @param string|resource $filename - The name of the file.
		 *
		 * @return int|false
		 *
		 * @since 1.1.0
		 */
		public static function get_modification_time( $filename ) {
			$filename = self::extract_file_name( $filename );
			return $filename ? filemtime( $filename ) : false;
		}

		/**
		 * Tries to extract the string representation of the file. Returns false if it fails or string on success.
		 *
		 * @param string|resource $file - The file to be used as a string representation.
		 *
		 * @return string|bool
		 *
		 * @since 1.1.0
		 */
		public static function extract_file_name( $file ) {
			$filename = false;

			if ( \is_resource( $file ) && 'handle' === \get_resource_type( $file ) ) {
				$meta_data = \stream_get_meta_data( $file );
				$filename  = $meta_data['uri'];
			} elseif ( \is_string( $file ) && \file_exists( $file ) && \is_readable( $file ) ) {
				$filename = $file;
			}

			return $filename;
		}

		/**
		 * Returns last stored error (if exists) or null.
		 *
		 * @return \WP_Error|null
		 *
		 * @since 1.1.0
		 */
		public static function get_last_error() {
			return self::$last_error;
		}

		/**
		 * Suppress error logging.
		 *
		 * @return void
		 *
		 * @since 1.9.2
		 */
		public static function suppress_error_logging() {

			if ( null === self::$log_file ) {
				self::autodetect();
			}
			ini_set( 'log_errors', false ); // phpcs:ignore WordPress.PHP.IniSet.log_errors_Disallowed
		}

		/**
		 * Enables error logging.
		 *
		 * @return void
		 *
		 * @since 1.9.2
		 */
		public static function enable_error_logging() {
			ini_set( 'log_errors', 1 ); // phpcs:ignore WordPress.PHP.IniSet.log_errors_Disallowed
			ini_set( 'error_log', self::$log_file ); // phpcs:ignore WordPress.PHP.IniSet.Risky
		}

		/**
		 * Returns the stored value in the internal class var (name of the error log file). Return empty string if that variable is null.
		 *
		 * @return string
		 *
		 * @since 1.9.5
		 */
		public static function get_error_log_file(): string {

			return (string) self::$log_file;
		}

		/**
		 * Truncates error log file but keeps the last (settings) errors.
		 *
		 * @return void
		 *
		 * @since 2.8.2
		 */
		public static function truncate_and_keep_errors() {

			self::suppress_error_logging();

			$file_and_path = self::autodetect();

			$dirname = pathinfo( $file_and_path, PATHINFO_DIRNAME );
			$dirname = realpath( $dirname );

			$temp_file = File_Helper::generate_random_file_name() . '.log';

			$new_log_file = \trailingslashit( $dirname ) . $temp_file;

			Reverse_Line_Reader::set_temp_handle_from_file_path( $new_log_file );

			$items = Logs_List::get_error_items( true, Settings::get_option( 'keep_error_log_records_truncate' ) );

			self::clear( $file_and_path );
			Log_Line_Parser::delete_last_parsed_timestamp();

			File_Helper::remove_empty_lines_low_memory( $new_log_file );

			rename( $new_log_file, $file_and_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename

			Reverse_Line_Reader::set_temp_handle_from_file_path( $new_log_file );

			$items = Logs_List::get_error_items( true, Settings::get_option( 'keep_error_log_records_truncate' ) );

			self::clear( $file_and_path );
			Log_Line_Parser::delete_last_parsed_timestamp();

			File_Helper::remove_empty_lines_low_memory( $new_log_file );

			rename( $new_log_file, $file_and_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename

			self::enable_error_logging();
		}
	}
}
