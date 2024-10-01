<?php
/**
 * Reads file in reverse order
 *
 * @package advanced-analytics
 *
 * @since latest
 */

declare(strict_types=1);

namespace ADVAN\Controllers;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\ADVAN\Controllers\Error_Log' ) ) {
	/**
	 * Responsible for operations related to the error log file.
	 *
	 * @since latest
	 */
	class Error_Log {

		/**
		 * Tries to detect the log filename.
		 *
		 * @return string|\WP_Error
		 *
		 * @since latest
		 */
		public static function autodetect() {
			$log_errors            = strtolower( strval( ini_get( 'log_errors' ) ) );
			$error_logging_enabled = ! empty( $log_errors ) && ! in_array( $log_errors, array( 'off', '0', 'false', 'no' ) );
			$log_file              = ini_get( 'error_log' );

			// Check for common problems that could prevent us from displaying the error log.
			if ( ! $error_logging_enabled ) {
				return new \WP_Error(
					'log_errors_off',
					__( 'Error logging is disabled.', 'advanced-analysis' )
				);
			} elseif ( empty( $log_file ) ) {
				return new \WP_Error(
					'error_log_not_set',
					__( 'Error log filename is not set.', 'advanced-analysis' )
				);
			} elseif ( ( strpos( $log_file, '/' ) === false ) && ( strpos( $log_file, '\\' ) === false ) ) {
				return new \WP_Error(
					'error_log_uses_relative_path',
					sprintf(
						// translators: the name of the log file.
						__( 'The current error_log value <code>%s</code> is not supported. Please change it to an absolute path.', 'advanced-analysis' ),
						esc_html( $log_file )
					)
				);
			} elseif ( ! is_readable( $log_file ) ) {
				if ( file_exists( $log_file ) ) {
					return new \WP_Error(
						'error_log_not_accessible',
						sprintf(
							// translators: the name of the log file.
							__( 'The log file <code>%s</code> exists, but is not accessible. Please check file permissions.', 'advanced-analysis' ),
							esc_html( $log_file )
						)
					);
				} else {
					return new \WP_Error(
						'error_log_not_found',
						sprintf(
							// translators: the name of the log file.
							__( 'The log file <code>%s</code> does not exist or is inaccessible.', 'advanced-analysis' ),
							esc_html( $log_file )
						)
					);
				}
			}

			return $log_file;
		}

		/**
		 * Truncates the given file.
		 *
		 * @param string|resource $filename - The name of the file.
		 *
		 * @return void
		 *
		 * @since latest
		 */
		public static function clear( $filename ) {
			if ( $filename = self::extract_file_name( $filename ) ) { // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.Found, Squiz.PHP.DisallowMultipleAssignments.FoundInControlStructure
				$handle = fopen( $filename, 'w' );
				fclose( $handle );
			}
		}

		/**
		 * Returns the file size
		 *
		 * @param string|resource $filename - The name of the file.
		 *
		 * @return int|false
		 *
		 * @since latest
		 */
		public static function get_file_size( $filename ) {
			if ( $filename = self::extract_file_name( $filename ) ) { // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.Found, Squiz.PHP.DisallowMultipleAssignments.FoundInControlStructure
				return filesize( $filename );
			}

			return false;
		}

		/**
		 * Returns the modification time of a file.
		 *
		 * @param string|resource $filename - The name of the file.
		 *
		 * @return int|false
		 *
		 * @since latest
		 */
		public static function get_modification_time( $filename ) {
			if ( $filename = self::extract_file_name( $filename ) ) { // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.Found, Squiz.PHP.DisallowMultipleAssignments.FoundInControlStructure
				return filemtime( $filename );
			}

			return false;
		}

		/**
		 * Tries to extract the string representation of the file. Returns false if it fails or string on success.
		 *
		 * @param string|resource $file - The file to be used as a string representation.
		 *
		 * @return string|bool
		 *
		 * @since latest
		 */
		public static function extract_file_name( $file ) {
			$filename = false;

			if ( \is_resource( $file ) && 'handle' === \get_resource_type( $file ) ) {
				$meta_data = \stream_get_meta_data( $file );
				$filename  = $meta_data['uri'];
			} elseif ( \file_exists( $file ) && \is_readable( $file ) ) {
				$filename = $file;
			}

			return $filename;
		}
	}
}
