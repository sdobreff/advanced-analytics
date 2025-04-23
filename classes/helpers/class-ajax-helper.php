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
		 * @since 1.1.0
		 */
		public static function download_log_file() {
			// Check nonce.
			if ( Settings::get_current_options()['menu_admins_only'] && ! \current_user_can( 'manage_options' ) ) {
				\wp_send_json_error( 0 );
			} else {
				if ( \check_ajax_referer( 'advan-plugin-data', 'advanced-analytics-security' ) ) {

					echo File_Helper::download( Error_Log::autodetect() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}

				\wp_send_json_success( 2 );
			}

			\wp_die();
		}

		/**
		 * Method responsible for AJAX data saving
		 *
		 * @return void
		 *
		 * @since 1.1.0
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

		/**
		 * Executes cron deletion sequence.
		 *
		 * @return void
		 *
		 * @since latest
		 */
		public static function delete_cron() {
			if ( \current_user_can( 'manage_options' ) && \check_ajax_referer( 'bulk-custom-delete' ) ) {

				if ( isset( $_REQUEST['hash'] ) && ! empty( $_REQUEST['hash'] ) ) {
					$hash = \sanitize_text_field( \wp_unslash( $_REQUEST['hash'] ) );
				} else {
					\wp_send_json_error( 0 );

					\wp_die();
				}

				$result = Crons_Helper::delete_event( $hash );

				if ( $result && ! \is_wp_error( $result ) ) {
					\wp_send_json_success( 2 );
				} elseif ( \is_wp_error( $result ) ) {
					\wp_send_json_error( $result->get_error_message() );
				} elseif ( false === $result ) {
					\wp_send_json_error( 'Unable to delete cron' );
				}
			} else {
				\wp_send_json_success( 0 );
			}
			\wp_die();
		}

		/**
		 * Executes cron run sequence.
		 *
		 * @return void
		 *
		 * @since latest
		 */
		public static function run_cron() {
			if ( \current_user_can( 'manage_options' ) && \check_ajax_referer( 'bulk-custom-delete' ) ) {

				if ( isset( $_REQUEST['hash'] ) && ! empty( $_REQUEST['hash'] ) ) {
					$hash = \sanitize_text_field( \wp_unslash( $_REQUEST['hash'] ) );
				} else {
					\wp_send_json_error( 0 );

					\wp_die();
				}

				if ( ! defined( 'DOING_CRON' ) ) {
					define( 'DOING_CRON', true );
				}

				Crons_Helper::execute_event( $hash );

				\wp_send_json_success( 2 );

			} else {
				\wp_send_json_success( 0 );
			}
			\wp_die();
		}
	}
}
