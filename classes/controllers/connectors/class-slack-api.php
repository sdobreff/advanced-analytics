<?php
/**
 * Slack API class
 *
 * @package advanced-analytics
 *
 * @since 1.8.0
 */

declare(strict_types=1);

namespace ADVAN\Controllers;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/*
 * Api class for slack
 */
if ( ! class_exists( '\ADVAN\Controllers\Slack_API' ) ) {
	/**
	 * Responsible for communication with the Slack API.
	 *
	 * @since 1.8.0
	 */
	class Slack_API {

		/**
		 * Error message
		 *
		 * @var string
		 *
		 * @since 1.8.0
		 */
		public static $error = null;

		/**
		 * Response valid message
		 *
		 * @var string
		 *
		 * @since 1.8.0
		 */
		public static $valid_message = null;

		/**
		 * Send Slack message to a specific channel.
		 *
		 * @param string $bot_token - API Auth token to use.
		 * @param string $channel_name   - The name of the channel.
		 * @param string $text   - Text body to send.
		 *
		 * @since 1.8.0
		 */
		public static function send_slack_message_via_api( ?string $bot_token, ?string $channel_name, string $text ) {

			if ( empty( $bot_token ) ) {
				$bot_token = Slack::get_slack_auth_key();
			}

			if ( empty( $channel_name ) ) {
				$channel_name = Slack::get_slack_channel();
			}

			$url  = 'https://slack.com/api/chat.postMessage';
			$data = array(
				'channel' => $channel_name,
				'text'    => ':warning: ' . ( $text ),
				'unfurl_links' => false,
				'unfurl_media' => false,
			);

			$headers = array(
				'Content-Type'  => 'application/json; charset=utf-8',
				'Authorization' => 'Bearer ' . $bot_token,
			);

			$args = array(
				'method'  => 'POST',
				'headers' => $headers,
				'body'    => json_encode( $data ),
			);

			$response = \wp_remote_post( \esc_url_raw( $url ), $args );

			if ( \is_wp_error( $response ) ) {
				self::$error = $response->get_error_message();

				return false;
			} else {
				return true;
			}
		}

		/**
		 * Verify the Slack token.
		 *
		 * @param string $token - The token to verify.
		 *
		 * @return bool
		 *
		 * @since 1.8.0
		 */
		public static function verify_slack_token( $token ) {
			if ( empty( $token ) ) {
				return new \WP_Error( 'slack_config', 'Bot token not configured' );
			}

			$url     = 'https://slack.com/api/auth.test';
			$headers = array(
				'Content-Type'  => 'application/x-www-form-urlencoded',
				'Authorization' => 'Bearer ' . $token,
			);

			$args = array(
				'method'  => 'POST',
				'headers' => $headers,
			);

			$response = \wp_remote_post( \esc_url_raw( $url ), $args );

			if ( \is_wp_error( $response ) ) {
				self::$error = $response->get_error_message();

				return false;
			} else {
				$response_data = json_decode( $response['body'], true );
				if ( $response_data['ok'] ) {
					self::$valid_message = $response_data;

					return true;
				} else {
					self::$error = $response_data['error'];

					return false;
				}
			}

			self::$error = 'Unknown error';

			return false;
		}

		/**
		 * Returns the error stored from Slack.
		 *
		 * @since 1.8.0
		 */
		public static function get_slack_error(): string {
			$error = self::$error;
			if ( \is_array( self::$error ) ) {
				$error = print_r( self::$error, true );
			}

			return (string) $error;
		}
	}
}
