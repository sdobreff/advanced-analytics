<?php
/**
 * WP Mail log class - captures the requests and fulfills the log table with the results.
 *
 * @package 0-day-analytics
 *
 * @since 3.0.0
 */

declare(strict_types=1);

namespace ADVAN\Controllers;

use ADVAN\Entities\WP_Mail_Entity;
use ADVAN\Helpers\Settings;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\ADVAN\Controllers\WP_Mail_Log' ) ) {
	/**
	 * Responsible for collecting the amils.
	 *
	 * @since 3.0.0
	 */
	class WP_Mail_Log {

		/**
		 * Class cache for the type of the mail message.
		 *
		 * @var integer
		 *
		 * @since 3.0.0
		 */
		private static $is_html = 0;

		/**
		 * Class cache for the last inserted mail log ID.
		 *
		 * @var integer
		 *
		 * @since 3.0.0
		 */
		private static $last_id = 0;

		/**
		 * Inits the class.
		 *
		 * @return void
		 *
		 * @since 3.0.0
		 */
		public static function init() {
			if ( Settings::get_option( 'wp_mail_module_enabled' ) ) {
				\add_filter( 'wp_mail', array( __CLASS__, 'record_mail' ), PHP_INT_MAX );
				\add_action( 'wp_mail_failed', array( __CLASS__, 'record_error' ), PHP_INT_MAX, 2 );
				\add_filter( 'wp_mail_content_type', array( __CLASS__, 'save_is_html' ), PHP_INT_MAX );

				\add_filter( 'phpmailer_init', array( __CLASS__, 'extract_more_mail_info' ), \PHP_INT_MAX );
			}
		}

		/**
		 * Extracts all of the mail information and stores it in the DB
		 *
		 * @param array $args - Array with all of the mail arguments.
		 *
		 * @return void
		 *
		 * @since 3.0.0
		 */
		public static function record_mail( $args ) {

			if ( \is_array( $args ) ) {
				$log_entry = array(
					'time'               => time(),
					'email_to'           => self::filter_html( self::array_to_string( $args['to'] ) ),
					'subject'            => self::filter_html( $args['subject'] ),
					'message'            => self::filter_html( $args['message'] ),
					'backtrace_segment'  => \wp_json_encode( self::get_backtrace() ),
					'status'             => 1,
					'attachments'        => \wp_json_encode( self::get_attachment_locations( $args['attachments'] ) ),
					'additional_headers' => \wp_json_encode( $args['headers'] ),
					'is_html'            => (int) self::$is_html,
				);

				self::$last_id = WP_Mail_Entity::insert( $log_entry );
			}
		}

		/**
		 * Tries to extract more information from the PHPMailer object.
		 *
		 * @param \PHPMailer $phpmailer - The PHPMailer initialized object from WP.
		 *
		 * @return void
		 *
		 * @since 3.0.1
		 */
		public static function extract_more_mail_info( $phpmailer ) {

			if ( \property_exists( $phpmailer, 'From' ) && ! empty( $phpmailer->From ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$log_entry = WP_Mail_Entity::load( 'id=%d', array( self::$last_id ) );

				$from          = array();
				$from['email'] = $phpmailer->From; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				if ( \property_exists( $phpmailer, 'FromName' ) && ! empty( $phpmailer->FromName ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					$from['name'] = $phpmailer->FromName; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				}

				$log_entry['email_from'] = self::array_to_string( $from );

				WP_Mail_Entity::insert( $log_entry );
			}
		}

		/**
		 * Records the error information for a failed email.
		 *
		 * @param \WP_Error $error - The error triggered.
		 *
		 * @return void
		 *
		 * @since 3.0.0
		 */
		public static function record_error( $error ) {
			$log_entry           = WP_Mail_Entity::load( 'id=%d', array( self::$last_id ) );
			$log_entry['status'] = 0;
			$log_entry['error']  = $error->get_error_message();

			WP_Mail_Entity::insert( $log_entry );
		}

		/**
		 * Stores class constant about the typo of the email - HTML or plain text.
		 *
		 * @param string $content_type - The current content type of the mail.
		 *
		 * @return string
		 *
		 * @since 3.0.0
		 */
		public static function save_is_html( $content_type ) {

			self::$is_html = ( 'text/html' === $content_type ) ? 1 : 0;

			return $content_type;
		}

		/**
		 * Filters HTML content of the mail.
		 *
		 * @param string $value - The mail body.
		 *
		 * @return string
		 *
		 * @since 3.0.0
		 */
		public static function filter_html( $value ): string {

			$value = preg_replace( '~<!--(?!<!)[^\[>].*?-->~s', '', $value );

			$value = htmlspecialchars_decode( (string) $value );

			$string = \wp_kses( $value, self::get_allowed_tags() );

			return $string;
		}

		/**
		 * What tags are allowed in the content of the mail.
		 *
		 * @return array
		 *
		 * @since 3.0.0
		 */
		private static function get_allowed_tags(): array {
			$tags          = wp_kses_allowed_html( 'post' );
			$tags['style'] = array();

			return $tags;
		}

		/**
		 * Converts array to string (inner method)
		 *
		 * @param array  $pieces - The array to convert.
		 * @param string $glue - The glue which to use when converting to string.
		 *
		 * @return string
		 *
		 * @since 3.0.0
		 */
		public static function array_to_string( $pieces, $glue = ', ' ) {
			$result = self::flatten( $pieces );

			if ( is_array( $result ) ) {
				$result = implode( $glue, $pieces );
			}

			return $result;
		}

		/**
		 * Flattens an array to dot notation.
		 *
		 * @param array  $array_to_process - An array.
		 * @param string $separator - The character to flatten with.
		 * @param string $parent_key - The parent passed to the child.
		 *
		 * @return array Flattened array to one level
		 *
		 * @since 3.0.0
		 */
		public static function flatten( $array_to_process, $separator = '.', $parent_key = null ) {
			if ( ! is_array( $array_to_process ) ) {
				return $array_to_process;
			}

			$_flattened = array();

			// Rewrite keys.
			foreach ( $array_to_process as $key => $value ) {
				if ( $parent_key ) {
					$key = $parent_key . $separator . $key;
				}
				$_flattened[ $key ] = self::flatten( $value, $separator, $key );
			}

			// Flatten.
			$flattened = array();
			foreach ( $_flattened as $key => $value ) {
				if ( is_array( $value ) ) {
					$flattened = array_merge( $flattened, $value );
				} else {
					$flattened[ $key ] = $value;
				}
			}

			return $flattened;
		}

		/**
		 * Get the details of the method that originally triggered wp_mail
		 *
		 * @param string $function_name - The name of the function to search for in the backtrace.
		 *
		 * @return array a single element of the debug_backtrace function
		 *
		 * @since 3.0.0
		 */
		private static function get_backtrace( $function_name = 'wp_mail' ): ?array {
			$backtrace_segment = null;

			$backtrace = ( new \Exception( '' ) )->getTrace();

			foreach ( $backtrace as $segment ) {
				if ( $segment['function'] == $function_name ) {
					$backtrace_segment = $segment;
				}
			}

			return $backtrace_segment;
		}

		/**
		 * Convert attachment ids or urls into a format to be usable
		 * by the logs
		 *
		 * @param array | string $attachments either array of attachment ids or their urls.
		 *
		 * @return array [id, url] of attachments
		 *
		 * @since 3.0.0
		 */
		protected static function get_attachment_locations( $attachments ): array {
			if ( empty( $attachments ) ) {
				return array();
			}

			if ( is_string( $attachments ) ) {
				$attachments = (array) $attachments;
			}

			array_walk(
				$attachments,
				function ( &$value ) {
					$value = str_replace( \wp_upload_dir()['basedir'] . '/', '', $value );
				}
			);

			if ( isset( $_POST['attachment_ids'] ) && \is_array( $_POST['attachment_ids'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$attachment_ids = array_map( 'intval', array_values( array_filter( $_POST['attachment_ids'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			} else {
				$attachment_ids = self::get_attachment_ids_from_url( $attachments );

				if ( empty( $attachment_ids ) ) {
					return array(
						array(
							'id' => -1,
						),
					);
				}
			}

			if ( empty( $attachment_ids ) ) {
				return array();
			}

			return $attachment_ids;
		}

		/**
		 * Extracts attachment IDs from the url.
		 *
		 * @param array|string $urls - The URLs to use for IDs extraction.
		 *
		 * @return array
		 *
		 * @since 3.0.0
		 */
		public static function get_attachment_ids_from_url( $urls ): array {
			if ( empty( $urls ) ) {
				return array();
			}

			global $wpdb;

			$attachment_ids = array();

			if ( ! \is_array( $urls ) ) {
				$urls = array( $urls );
			}

			foreach ( $urls as &$url ) {
				$sql = 'SELECT DISTINCT post_id
                FROM ' . $wpdb->prefix . 'postmeta
				WHERE meta_value LIKE %s';

				$sql .= " AND meta_key = '_wp_attached_file'";

				$url = '%' . $url . '%';

				$results = $wpdb->get_results( $wpdb->prepare( $sql, $url ), ARRAY_N ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

				if ( isset( $results[0] ) ) {
					$attachment_ids[] = array(
						'id'        => (int) $results[0][0],
						'url'       => \wp_get_attachment_url( (int) $results[0][0] ),
						'src'       => \wp_mime_type_icon( (int) $results[0][0] ),
						'alt'       => \get_post_meta( (int) $results[0][0], '_wp_attachment_image_alt', true ),
						'mime_type' => \get_post_mime_type( (int) $results[0][0] ),
					);
				} else {
					$attachment_ids[] = array(
						'id'  => -1,
						'url' => $url,
					);
				}
			}
			unset( $url );

			return $attachment_ids;
		}
	}
}
