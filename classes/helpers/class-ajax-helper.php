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
use ADVAN\Controllers\Slack;
use ADVAN\Helpers\WP_Helper;
use ADVAN\Controllers\Telegram;
use ADVAN\Controllers\Error_Log;
use ADVAN\Controllers\Slack_API;
use ADVAN\Controllers\Telegram_API;

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

				/**
				 * Delete Cron
				 */
				\add_action( 'wp_ajax_aadvana_delete_transient', array( __CLASS__, 'delete_transient' ) );

				/**
				 * Store Slack API key
				 */
				\add_action( 'wp_ajax_aadvana_store_slack_api_key', array( __CLASS__, 'store_slack_api_key_ajax' ) );

				/**
				 * Store Telegram API key
				 */
				\add_action( 'wp_ajax_aadvana_store_telegram_api_key', array( __CLASS__, 'store_telegram_api_key_ajax' ) );

				/**
				 * Show the code source
				 *
				 * @return void
				 *
				 * @since 1.8.2
				 */
				\add_action( 'wp_ajax_log_source_view', array( __CLASS__, 'ajax_view_source' ) );

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

			WP_Helper::verify_admin_nonce( 'advan-plugin-data', 'advanced-analytics-security' );

			File_Helper::download( Error_Log::autodetect() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

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
		 * Executes transient deletion sequence.
		 *
		 * @return void
		 *
		 * @since 1.7.0
		 */
		public static function delete_transient() {
			WP_Helper::verify_admin_nonce( 'bulk-custom-delete' );

			$id = self::validate_id_param();

			$result = Transients_Helper::delete_transient( $id );

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
		 * @since 1.4.0
		 */
		private static function validate_hash_param(): string {
			if ( ! isset( $_REQUEST['hash'] ) || empty( $_REQUEST['hash'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				\wp_send_json_error( 'Missing or empty hash parameter.', 400 );
				\wp_die();
			}

			return \sanitize_text_field( \wp_unslash( $_REQUEST['hash'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		/**
		 * Validates and retrieves the id parameter from the request.
		 *
		 * @return int The sanitized id.
		 *
		 * @since 1.7.0
		 */
		private static function validate_id_param(): int {
			if ( ! isset( $_REQUEST['id'] ) || empty( $_REQUEST['id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				\wp_send_json_error( 'Missing or empty id parameter.', 400 );
				\wp_die();
			}

			return (int) \sanitize_text_field( \wp_unslash( $_REQUEST['id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		/**
		 * Stores the Slack Credentials key via AJAX request
		 *
		 * @return void
		 *
		 * @since 1.8.0
		 */
		public static function store_slack_api_key_ajax() {

			WP_Helper::verify_admin_nonce( Slack::NONCE_NAME );

			if ( isset( $_REQUEST['slack_auth'] ) && ! empty( $_REQUEST['slack_auth'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$slack_valid =
				Slack_API::verify_slack_token(
					(string) \sanitize_text_field( \wp_unslash( $_REQUEST['slack_auth'] ) ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				);
				if ( $slack_valid ) {
					$options = Slack::get_settings();

					$options['auth_token'] = \sanitize_text_field( \wp_unslash( $_REQUEST['slack_auth'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

					Slack::set_settings( $options );

					\wp_send_json_success();
				}

				\wp_send_json_error( __( 'SLACK: No token provided or the provided one is invalid. Please check and provide the details again.', '0-day-analytics' ) . Slack_API::get_slack_error() );
			}
		}

		/**
		 * Stores the Slack Credentials key via AJAX request
		 *
		 * @return void
		 *
		 * @since 1.8.5
		 */
		public static function store_telegram_api_key_ajax() {
			WP_Helper::verify_admin_nonce( Telegram::NONCE_NAME );

			if ( isset( $_REQUEST['telegram_auth'] ) && ! empty( $_REQUEST['telegram_auth'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$telegram_valid =
				Telegram_API::verify_telegram_token(
					(string) \sanitize_text_field( \wp_unslash( $_REQUEST['telegram_auth'] ) ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				);
				if ( $telegram_valid ) {
					$options = Telegram::get_settings();

					$options['auth_token'] = \sanitize_text_field( \wp_unslash( $_REQUEST['telegram_auth'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

					Telegram::set_settings( $options );

					\wp_send_json_success();
				}

				\wp_send_json_error( __( 'TELEGRAM: No token provided or the provided one is invalid. Please check and provide the details again.', '0-day-analytics' ) . Telegram_API::get_telegram_error() );
			}
		}

		/**
		 * Shows the source code.
		 *
		 * @return void
		 *
		 * @since 1.8.2
		 */
		public static function ajax_view_source() {
			WP_Helper::verify_admin_nonce( 'source-view' );

			if ( ! isset( $_REQUEST['error_file'] ) || empty( $_REQUEST['error_file'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				\wp_send_json_error( \esc_html__( 'File not found.', '0-day-analytics' ), 404 );
				\wp_die();
			}

			$file_name = $_REQUEST['error_file']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			if ( ! File_Helper::is_file_valid_php( $file_name ) ) {
				\wp_send_json_error( \esc_html__( 'File does not exists or it is not valid PHP file.', '0-day-analytics' ), 404 );
				\wp_die();

			}
			// Don't show any configurational files for security reasons.
			if ( Settings::get_current_options()['protected_config_source'] && ( strpos( \basename( $file_name ), 'config' ) !== false || strpos( \basename( $file_name ), 'settings' ) !== false || strpos( \basename( $file_name ), 'wp-load' ) !== false ) ) {
				\wp_send_json_error( \esc_html__( 'File source view is protected. You can change this in Advanced Settings', '0-day-analytics' ), 404 );
				\wp_die();
			}

			$sh_url = ADVAN_PLUGIN_ROOT_URL . 'js/sh/';

			$source = htmlspecialchars( @file_get_contents( $file_name ) ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

			$lines = isset( $_REQUEST['error_line'] ) ? \sanitize_text_field( \wp_unslash( $_REQUEST['error_line'] ) ) : 11; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( \strpos( (string) $lines, '-' ) ) {
				$source_lines = array_map( 'absint', explode( '-', $lines ) );
				$scroll_to    = $source_lines[0] - 10;
				$line         = ' new Array (' . implode( ', ', $source_lines ) . ')';
			} else {
				$line      = absint( $lines );
				$scroll_to = $line - 10;
			}

			?>
				<!DOCTYPE html><html xmlns="http://www.w3.org/1999/xhtml">
				<head>
				<script type="text/javascript" src="<?php echo $sh_url;  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped , WordPress.WP.EnqueuedResources.NonEnqueuedScript ?>scripts/shCore.js"></script>
				<script type="text/javascript" src="<?php echo $sh_url;  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped , WordPress.WP.EnqueuedResources.NonEnqueuedScript ?>scripts/shBrushPhp.js"></script>
				<link href="<?php echo $sh_url;  // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet , WordPress.Security.EscapeOutput.OutputNotEscaped ?>styles/shCore.css" rel="stylesheet" type="text/css" />
				<link href="<?php echo $sh_url;  // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet , WordPress.Security.EscapeOutput.OutputNotEscaped ?>styles/shThemeDefault.css" rel="stylesheet" type="text/css" />
				<style type="text/css" media="all">
					.syntaxhighlighter{
						max-height: 80%;
					}
				</style>
				</head>

				<body>
				<pre class="brush: php; toolbar: false;"><?php echo $source;  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></pre>

				<script type="text/javascript">
					SyntaxHighlighter.defaults["highlight"] = <?php echo $line;  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					//SyntaxHighlighter.defaults["first-line"] = '.$line.';
					SyntaxHighlighter.all();
					function waitUntilRender(){
						linex = document.getElementsByClassName("number<?php echo $scroll_to;  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>");
						if (typeof linex === 'undefined') return;
						linex[0].scrollIntoView();
						clearInterval(intervalID);
					}
					var intervalID  = setInterval(waitUntilRender, 200);

				</script>
				</body></html>
			<?php
			\wp_die();
		}
	}
}
