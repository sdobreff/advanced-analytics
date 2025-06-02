<?php
/**
 * Telegram API class
 *
 * @package advanced-analytics
 *
 * @since 1.8.5
 */

declare(strict_types=1);

namespace ADVAN\Controllers;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/*
 * Api class for Telegram
 */
if ( ! class_exists( '\ADVAN\Controllers\Telegram_API' ) ) {
	/**
	 * Responsible for communication with the Telegram API.
	 *
	 * @since 1.8.5
	 */
	class Telegram_API {

		/**
		 * Error message
		 *
		 * @var string
		 *
		 * @since 1.8.5
		 */
		public static $error = null;

		/**
		 * Response valid message
		 *
		 * @var string
		 *
		 * @since 1.8.5
		 */
		public static $valid_message = null;

		/**
		 * Send Telegram message to a specific channel.
		 *
		 * @param string $bot_token - API Auth token to use.
		 * @param string $channel_id - The ID of the channel.
		 * @param string $text   - Text body to send.
		 *
		 * @since 1.8.5
		 */
		public static function send_telegram_message_via_api( ?string $bot_token, ?string $channel_id, string $text ) {

			if ( empty( $bot_token ) ) {
				$bot_token = Telegram::get_telegram_auth_key();
			}

			if ( empty( $channel_id ) ) {
				$channel_id = Telegram::get_telegram_channel();
			}

			$parse_mode = 'Markdown';

			$url = sprintf(
				'https://api.telegram.org/bot%s/sendMessage',
				urlencode( $bot_token )
			);

			$args = array(
				'body'    => array(
					'chat_id'                  => $channel_id,
					'text'                     => \wp_kses_post( $text ),
					'parse_mode'               => in_array( $parse_mode, array( 'Markdown', 'HTML' ), true ) ? $parse_mode : '',
					'disable_web_page_preview' => true,
				),
				'timeout' => 15,
			);

			$response = \wp_remote_post( esc_url_raw( $url ), $args );

			if ( \is_wp_error( $response ) ) {
				self::$error = $response->get_error_message();

				return false;
			} else {
				return true;
			}
		}

		/**
		 * Verify the Telegram token.
		 *
		 * @param string $token - The token to verify.
		 *
		 * @return bool
		 *
		 * @since 1.8.5
		 */
		public static function verify_telegram_token( $token ) {
			if ( empty( $token ) ) {
				return new \WP_Error( 'telegram_config', 'Bot token not configured' );
			}

			$api_url = sprintf(
				'https://api.telegram.org/bot%s/getMe',
				urlencode( $token )
			);

			$response = \wp_remote_get( \esc_url_raw( $api_url ), array( 'timeout' => 15 ) );

			if ( \is_wp_error( $response ) ) {
				self::$error = $response->get_error_message();

				return false;
			} else {
				$response_data = json_decode( wp_remote_retrieve_body( $response ), true );
				if ( ! empty( $response_data['ok'] ) && ! empty( $response_data['result']['is_bot'] ) && true === $response_data['result']['is_bot'] ) {
					self::$valid_message = $response_data;
					return true;
				} else {
					self::$error = $response_data['description'] ?? __( 'Unknown Telegram API error', '0-day-analytics' );

					return false;
				}
			}

			self::$error = 'Unknown error';

			return false;
		}

		/**
		 * Returns the error stored from Telegram.
		 *
		 * @since 1.8.5
		 */
		public static function get_telegram_error(): string {
			$error = self::$error;
			if ( \is_array( self::$error ) ) {
				$error = print_r( self::$error, true );
			}

			return (string) $error;
		}

		/**
		 * Inline message button.
		 *
		 * @param string $text - The message text to send.
		 * @param string $button_text - The button libel text.
		 * @param string $url - The url to open when the button is clicked.
		 *
		 * @return bool
		 *
		 * @since 1.8.5
		 */
		public static function send_with_button( $text, $button_text, $url ) {
			$args['body']['reply_markup'] = json_encode(
				array(
					'inline_keyboard' => array(
						array(
							array(
								'text' => sanitize_text_field( $button_text ),
								'url'  => esc_url( $url ),
							),
						),
					),
				)
			);

			return self::send_telegram_message_via_api( null, null, $text );
		}
	}
}
