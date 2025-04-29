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

use ADVAN\Helpers\Settings;
use ADVAN\Helpers\WP_Helper;
use ADVAN\Controllers\Error_Log;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\ADVAN\Helpers\Ajax_Helper' ) ) {
	/**
	 * Responsible for ajax operations.
	 *
	 * @since 1.1.0
	 */
	class Ajax_Helper {

		/**
		 * Inits the class and sets the defaults
		 *
		 * @return void
		 *
		 * @since 1.1.0
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

				/**
				 * Delete Cron
				 */
				\add_action( 'wp_ajax_aadvana_delete_cron', array( __CLASS__, 'delete_cron' ) );

				/**
				 * Run Cron
				 */
				\add_action( 'wp_ajax_aadvana_run_cron', array( __CLASS__, 'run_cron' ) );

			}
		}

		/**
		 * Truncates the error log file.
		 *
		 * @return void
		 *
		 * @since 1.1.0
		 */
		public static function truncate_log_file() {
			WP_Helper::verify_admin_nonce( 'advan-plugin-data', 'advanced-analytics-security' );

			Error_Log::clear( Error_Log::autodetect() );
			Log_Line_Parser::delete_last_parsed_timestamp();

			\wp_send_json_success( 2 );
		}

		/**
		 * Downloads the error log file.
		 *
		 * @return void
		 *
		 * @since 1.1.0
		 */
		public static function download_log_file() {
			if ( Settings::get_current_options()['menu_admins_only'] && ! \current_user_can( 'manage_options' ) ) {
				\wp_send_json_error( 'Insufficient permissions.', 403 );
			}

			WP_Helper::verify_admin_nonce( 'advan-plugin-data', 'advanced-analytics-security' );

			echo File_Helper::download( Error_Log::autodetect() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			\wp_send_json_success( 2 );
		}

		/**
		 * Method responsible for AJAX data saving
		 *
		 * @return void
		 *
		 * @since 1.1.0
		 */
		public static function save_settings_ajax() {

			WP_Helper::verify_admin_nonce( 'aadvana-plugin-data', 'aadvana-security' );

			if ( isset( $_POST[ \ADVAN_SETTINGS_NAME ] ) && ! empty( $_POST[ \ADVAN_SETTINGS_NAME ] ) && \is_array( $_POST[ \ADVAN_SETTINGS_NAME ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing

				$data = array_map( 'sanitize_text_field', \stripslashes_deep( $_POST[ \ADVAN_SETTINGS_NAME ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

				\update_option( \ADVAN_SETTINGS_NAME, Settings::collect_and_sanitize_options( $data ) );

				\wp_send_json_success( 2 );
			}
				\wp_die();
		}

		/**
		 * Executes cron deletion sequence.
		 *
		 * @return void
		 *
		 * @since 1.3.0
		 */
		public static function delete_cron() {
			WP_Helper::verify_admin_nonce( 'bulk-custom-delete' );

			$hash = self::validate_hash_param();

			$result = Crons_Helper::delete_event( $hash );

			if ( $result && ! \is_wp_error( $result ) ) {
				\wp_send_json_success( 2 );
			} elseif ( \is_wp_error( $result ) ) {
				\wp_send_json_error( $result->get_error_message(), 500 );
			} else {
				\wp_send_json_error( 'Unable to delete cron.', 500 );
			}
		}

		/**
		 * Executes cron run sequence.
		 *
		 * @return void
		 *
		 * @since 1.3.0
		 */
		public static function run_cron() {
			WP_Helper::verify_admin_nonce( 'bulk-custom-delete' );

			$hash = self::validate_hash_param();

			if ( ! defined( 'DOING_CRON' ) ) {
				define( 'DOING_CRON', true );
			}

			Crons_Helper::execute_event( $hash );

			\wp_send_json_success( 2 );
		}

		/**
		 * Validates and retrieves the hash parameter from the request.
		 *
		 * @return string The sanitized hash.
		 *
		 * @since latest
		 */
		private static function validate_hash_param(): string {
			if ( ! isset( $_REQUEST['hash'] ) || empty( $_REQUEST['hash'] ) ) {
				\wp_send_json_error( 'Missing or empty hash parameter.', 400 );
				\wp_die();
			}

			return \sanitize_text_field( \wp_unslash( $_REQUEST['hash'] ) );
		}
	}
}
