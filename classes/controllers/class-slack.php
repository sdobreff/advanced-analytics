<?php
/**
 * Slack main class
 *
 * @package advanced-analytics
 *
 * @since latest
 */

declare(strict_types=1);

namespace ADVAN\Controllers;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Slack settings class
 */
if ( ! class_exists( 'ADVAN\Controllers\Slack' ) ) {

	/**
	 * Responsible for setting different 2FA Slack settings
	 *
	 * @since latest
	 */
	class Slack {

		public const SETTINGS_NAME = WSAL_PREFIX . 'slack';

		public const NONCE_NAME = WSAL_PREFIX . 'slack';

		public const POLICY_SETTINGS_NAME = 'enable_slack';

		/**
		 * Slack Account AUTH
		 *
		 * @var string
		 *
		 * @since latest
		 */
		protected static $auth_key = null;

		/**
		 * All the extension settings and their values
		 *
		 * @var array
		 *
		 * @since latest
		 */
		private static $settings = null;

		/**
		 * Internal class cache for status of the slack
		 *
		 * @var bool
		 *
		 * @since latest
		 */
		private static $is_set = null;

		/**
		 * Inits the class hooks
		 *
		 * @return void
		 *
		 * @since latest
		 */
		public static function init() {

			// AJAX calls part.
			\add_action( 'wp_ajax_wsal_store_slack_api_key', array( __CLASS__, 'store_slack_api_key_ajax' ) );

			self::$settings = Notifications::get_global_notifications_setting();
		}

		/**
		 * Stores the Slack Credentials key via AJAX request
		 *
		 * @return void
		 *
		 * @since latest
		 */
		public static function store_slack_api_key_ajax() {
			if ( \wp_doing_ajax() ) {
				if ( isset( $_REQUEST['_wpnonce'] ) ) {
					$nonce_check = \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_REQUEST['_wpnonce'] ) ), self::NONCE_NAME );
					if ( ! $nonce_check ) {
						\wp_send_json_error( new \WP_Error( 500, \esc_html__( 'Nonce checking failed', '0-day-analytics' ) ), 400 );
					}
				} else {
					\wp_send_json_error( new \WP_Error( 500, \esc_html__( 'Nonce is not provided', '0-day-analytics' ) ), 400 );
				}
			} else {
				\wp_send_json_error( new \WP_Error( 500, \esc_html__( 'Not allowed', '0-day-analytics' ) ), 400 );
			}

			if ( \current_user_can( 'manage_options' ) ) {

				if ( isset( $_REQUEST['slack_auth'] ) && ! empty( $_REQUEST['slack_auth'] ) ) {
					$slack_valid =
					Slack_API::verify_slack_token(
						(string) \sanitize_text_field( \wp_unslash( $_REQUEST['slack_auth'] ) ),
					);
					if ( $slack_valid ) {
						$options = Notifications::get_global_notifications_setting();

						$options['slack_notification_auth_token'] = \sanitize_text_field( \wp_unslash( $_REQUEST['slack_auth'] ) );

						Notifications::set_global_notifications_setting( $options );

						\wp_send_json_success();
					}

					\wp_send_json_error( __( 'SLACK: No token provided or the provided one is invalid. Please check and provide the details again.', '0-day-analytics' ) . Slack_API::get_slack_error() );
				}
			}
			\wp_send_json_error( new \WP_Error( 500, \esc_html__( 'Not allowed', '0-day-analytics' ) ), 400 );
		}

		/**
		 * Checks if the method is whole set.
		 *
		 * @return boolean
		 *
		 * @since latest
		 */
		public static function is_set() {
			if ( null === self::$is_set ) {
				self::$is_set = false;

				$auth = self::get_slack_auth_key();

				if ( $auth ) {

					if ( Slack_API::verify_slack_token( $auth ) ) {
						self::$is_set = true;

						return true;
					}
				}
			}

			return self::$is_set;
		}

		/**
		 * Adds JS setting to the first time wizard set up page - adds Slack key store key - the key can be stored from the wizard directly.
		 *
		 * @param array $settings - Array with the current wizard JS settings.
		 *
		 * @return array
		 *
		 * @since latest
		 */
		public static function js_wizard_settings( array $settings ): array {
			$settings['storeKey'] = true;

			return $settings;
		}

		/**
		 * Returns the Slack stored settings
		 *
		 * @return array
		 *
		 * @since latest
		 */
		public static function get_settings(): array {
			if ( null === self::$settings ) {
				self::$settings = Notifications::get_global_notifications_setting();
			}

			return self::$settings;
		}

		/**
		 * Returns the currently stored Slack AUTH key or false if there is none.
		 *
		 * @return bool|string
		 *
		 * @since latest
		 */
		public static function get_slack_auth_key() {
			if ( null === self::$auth_key ) {
				self::$auth_key = false;

				if ( isset( self::get_settings()['slack_notification_auth_token'] ) ) {
					self::$auth_key = self::get_settings()['slack_notification_auth_token'];
				}
			}

			return self::$auth_key;
		}

		/**
		 * Extracts the setting value
		 *
		 * @param string $setting - The name of the setting which value needs to be extracted.
		 *
		 * @return mixed
		 *
		 * @since latest
		 */
		public static function get_slack_setting( string $setting ) {
			if ( ! isset( $setting ) ) {
				return '';
			}

			if ( isset( self::get_settings()[ $setting ] ) ) {
				return self::get_settings()[ $setting ];
			}

			return '';
		}
	}
}
