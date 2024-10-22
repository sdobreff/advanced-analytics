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

				\wp_send_json_success( 2 );

				\wp_die();
			}
		}
	}
}
