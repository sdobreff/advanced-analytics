<?php
/**
 * Slack main class
 *
 * @package advanced-analytics
 *
 * @since 1.8.0
 */

declare(strict_types=1);

namespace ADVAN\Controllers;

use ADVAN\Helpers\Settings;
use ADVAN\Controllers\Slack_API;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Slack settings class
 */
if ( ! class_exists( 'ADVAN\Controllers\Slack' ) ) {

	/**
	 * Responsible for setting different Slack settings
	 *
	 * @since 1.8.0
	 */
	class Slack {

		public const NONCE_NAME = ADVAN_PREFIX . 'slack';

		/**
		 * Slack Account AUTH
		 *
		 * @var string
		 *
		 * @since 1.8.0
		 */
		protected static $auth_key = null;

		/**
		 * All the extension settings and their values
		 *
		 * @var array
		 *
		 * @since 1.8.0
		 */
		private static $settings = null;

		/**
		 * Internal class cache for status of the slack
		 *
		 * @var bool
		 *
		 * @since 1.8.0
		 */
		private static $is_set = null;

		/**
		 * Checks if the method is whole set.
		 *
		 * @return boolean
		 *
		 * @since 1.8.0
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
		 * @since 1.8.0
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
		 * @since 1.8.0
		 */
		public static function get_settings(): array {
			if ( null === self::$settings ) {
				self::$settings = Settings::get_current_options()['slack_notifications']['all'];
			}

			return self::$settings;
		}

		/**
		 * Returns the Slack stored settings
		 *
		 * @param array $options - Array with the current Slack settings.
		 *
		 * @return void
		 *
		 * @since 1.8.0
		 */
		public static function set_settings( array $options ): void {
			// Sanitize each setting value.
			$sanitized_options = array_map( 'sanitize_text_field', $options );

			$current_options = Settings::get_current_options();

			$current_options['slack_notifications']['all'] = $sanitized_options;

			Settings::store_options( $current_options );
		}

		/**
		 * Returns the currently stored Slack AUTH key or false if there is none.
		 *
		 * @return bool|string
		 *
		 * @since 1.8.0
		 */
		public static function get_slack_auth_key() {
			if ( null === self::$auth_key ) {
				self::$auth_key = false;

				if ( isset( self::get_settings()['auth_token'] ) ) {
					self::$auth_key = self::get_settings()['auth_token'];
				}
			}

			return self::$auth_key;
		}
		/**
		 * Returns the currently stored Slack AUTH key or false if there is none.
		 *
		 * @return bool|string
		 *
		 * @since 1.8.0
		 */
		public static function get_slack_channel() {
			return self::get_settings()['channel'];
		}

		/**
		 * Extracts the setting value
		 *
		 * @param string $setting - The name of the setting which value needs to be extracted.
		 *
		 * @return mixed
		 *
		 * @since 1.8.0
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
