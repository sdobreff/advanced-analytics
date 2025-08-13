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
	 * @since latest
	 */
	class WP_Mail_Log {

		/**
		 * Class cache for the type of the mail message.
		 *
		 * @var integer
		 *
		 * @since latest
		 */
		private static $is_html = 0;

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

				WP_Mail_Entity::insert( $log_entry );
			}
		}

		public static function save_is_html( $content_type ) {

			self::$is_html = ( 'text/html' === $content_type ) ? 1 : 0;

			return $content_type;
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
		 * @param array  $array An array
		 * @param string $separator The character to flatten with
		 * @param string $parent The parent passed to the child (private)
		 *
		 * @return array Flattened array to one level
		 *
		 * @since latest
		 */
		public static function flatten( $array, $separator = '.', $parent = null ) {
			if ( ! is_array( $array ) ) {
				return $array;
			}

			$_flattened = array();

			// Rewrite keys.
			foreach ( $array as $key => $value ) {
				if ( $parent ) {
					$key = $parent . $separator . $key;
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
		 * @return array a single element of the debug_backtrace function
		 */
		private static function get_backtrace( $function_name = 'wp_mail' ): ?array {
			// $backtrace_segment = null;
			// $backtrace         = debug_backtrace();

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
		 * @param array | string $attachments either array of attachment ids or their urls
		 *
		 * @return array [id, url] of attachments
		 */
		protected static function get_attachment_locations( $attachments ): array {
			if ( empty( $attachments ) ) {
				return array();
			}

			if ( is_string( $attachments ) ) {
				$attachments = (array) $attachments;
			}

			$result = array();

			array_walk(
				$attachments,
				function ( &$value ) {
					$value = str_replace( \wp_upload_dir()['basedir'] . '/', '', $value );
				}
			);

			if ( isset( $_POST['attachment_ids'] ) ) {
				$attachment_ids = array_values( array_filter( $_POST['attachment_ids'] ) );
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

			for ( $i = 0, $iMax = count( $attachments ); $i < $iMax; $i++ ) {
				$result[] = array(
					'id'  => $attachment_ids[ $i ],
					'url' => \wp_upload_dir()['url'] . $attachments[ $i ],
				);
			}

			return $result;
		}

		public static function get_attachment_ids_from_url( $urls ) {
			if ( empty( $urls ) ) {
				return array();
			}

			global $wpdb;

			$sql = 'SELECT DISTINCT post_id
                FROM ' . $wpdb->prefix . 'postmeta
				WHERE meta_value LIKE %s';

			if ( is_array( $urls ) && count( $urls ) > 1 ) {
				foreach ( $urls as $url ) {
					// Skip first url as it's covered above.
					if ( $url === $urls[0] ) {
						continue;
					}

					$sql .= ' OR meta_value LIKE %s';
				}
			}

			$sql .= " AND meta_key = '_wp_attached_file'";

			$urls = array_map(
				function ( $url ) {
					return '%' . $url . '%';
				},
				$urls
			);

			$sql     = $wpdb->prepare( $sql, $urls );
			$results = $wpdb->get_results( $sql, ARRAY_N );

			if ( isset( $results[0] ) ) {
				return array_column( $results, 0 );
			}

			return array();
		}
	}
}
