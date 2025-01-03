<?php
/**
 * Class: File functions helper file.
 *
 * Helper class used for extraction / loading classes.
 *
 * @package advanced-analytics
 */

declare(strict_types=1);

namespace ADVAN\Helpers;

use ADVAN\Controllers\Error_Log;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\ADVAN\Helpers\Ajax_Helper' ) ) {
	/**
	 * Responsible for ajax operations.
	 *
	 * @since latest
	 */
	class Ajax_Helper {

		/**
		 * Inits the class and sets the defaults
		 *
		 * @return void
		 *
		 * @since latest
		 */
		public static function init() {
			if ( \is_admin() && \wp_doing_ajax() ) {

				/**
				 * Truncate file
				 */
				\add_action( 'wp_ajax_advanced_analytics_truncate_log_file', array( __CLASS__, 'truncate_log_file' ) );

				/**
				 * Download file
				 */
				\add_action( 'wp_ajax_advanced_analytics_download_log_file', array( __CLASS__, 'download_log_file' ) );

				/**
				 * Save Options
				 */
				\add_action( 'wp_ajax_aadvana_plugin_data_save', array( __CLASS__, 'save_settings_ajax' ) );

			}
		}

		/**
		 * Truncates the error log file.
		 *
		 * @return void
		 *
		 * @since latest
		 */
		public static function truncate_log_file() {
			// Check nonce.

			if ( \current_user_can( 'manage_options' ) && \check_ajax_referer( 'advan-plugin-data', 'advanced-analytics-security' ) ) {

				Error_Log::clear( Error_Log::autodetect() );
				Log_Line_Parser::delete_last_parsed_timestamp();

				\wp_send_json_success( 2 );

				\wp_die();
			}
		}

		/**
		 * Downloads the error log file.
		 *
		 * @return void
		 *
		 * @since latest
		 */
		public static function download_log_file() {
			// Check nonce.

			if ( \current_user_can( 'manage_options' ) && \check_ajax_referer( 'advan-plugin-data', 'advanced-analytics-security' ) ) {

				echo File_Helper::download( Error_Log::autodetect() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}

			\wp_send_json_success( 2 );

			\wp_die();
		}

		/**
		 * Method responsible for AJAX data saving
		 *
		 * @return void
		 *
		 * @since 3.7.0
		 */
		public static function save_settings_ajax() {

			if ( \current_user_can( 'manage_options' ) && \check_ajax_referer( 'aadvana-plugin-data', 'aadvana-security' ) ) {

				if ( isset( $_POST[ \ADVAN_SETTINGS_NAME ] ) && ! empty( $_POST[ \ADVAN_SETTINGS_NAME ] ) && \is_array( $_POST[ \ADVAN_SETTINGS_NAME ] ) ) {

					$data = array_map( 'sanitize_text_field', \stripslashes_deep( $_POST[ \ADVAN_SETTINGS_NAME ] ) );

					\update_option( \ADVAN_SETTINGS_NAME, Settings::collect_and_sanitize_options( $data ) );

					\wp_send_json_success( 2 );
				}
				\wp_die();
			} else {
				\wp_send_json_success( 0 );
				\wp_die();
			}
		}
	}
}
