<?php
/**
 * WP Mail log class - captures the requests and fulfills the log table with the results.
 *
 * @package 0-day-analytics
 *
 * @since latest
 */

declare(strict_types=1);

namespace ADVAN\Controllers;

use ADVAN\Helpers\Settings;
use ADVAN\Helpers\Context_Helper;
use ADVAN\Entities\Requests_Log_Entity;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\ADVAN\Controllers\Requests_Log' ) ) {
	/**
	 * Responsible for collecting the requests.
	 *
	 * @since latest
	 */
	class Requests_Log {

		/**
		 * Inits the class.
		 *
		 * @return void
		 *
		 * @since latest
		 */
		public static function init() {
			if ( Settings::get_option( 'wp_mail_module_enabled' ) ) {
				\add_filter( 'wp_mail', array( __CLASS__, 'record_mail' ), PHP_INT_MAX );
				\add_action( 'wp_mail_failed', array( __CLASS__, 'record_error' ), PHP_INT_MAX );
				\add_filter( 'wp_mail_content_type', array( __CLASS__, 'save_is_html' ), PHP_INT_MAX );
			}
		}

		public static function record_mail( $args ) {

			if ( \is_array( $args ) ) {
				$data = array(
					'time'               => time(),
					'email_to'           => GeneralHelper::filterHtml( GeneralHelper::arrayToString( $args['to'] ) ),
					'subject'            => GeneralHelper::filterHtml( $args['subject'] ),
					'message'            => GeneralHelper::filterHtml( $args['message'] ),
					'backtrace_segment'  => json_encode( $this->getBacktrace() ),
					'status'             => 1,
					'attachments'        => json_encode( $this->getAttachmentLocations( $args['attachments'] ) ),
					'additional_headers' => json_encode( $args['headers'] ),
				);
			}
		}

		public static function filter_html( $value ): string {
			$value = htmlspecialchars_decode( (string) $value );
			return wp_kses( $value, self::get_allowed_tags() );
		}

		private static function get_allowed_tags(): array {
			$tags          = wp_kses_allowed_html( 'post' );
			$tags['style'] = array();

			return $tags;
		}
	}
}
