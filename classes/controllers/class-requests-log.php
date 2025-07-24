<?php
/**
 * Requests log class - showing the pointers where necessary.
 *
 * @package 0-day-analytics
 *
 * @since 2.7.0
 */

declare(strict_types=1);

namespace ADVAN\Controllers;

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
	 * @since 2.7.0
	 */
	class Requests_Log {

		/**
		 * Class cache for the requests count.
		 *
		 * @var integer
		 *
		 * @since 2.7.0
		 */
		private static $requests = 0;

		/**
		 * Class cache for the last inserted request ID.
		 *
		 * @var integer
		 *
		 * @since 2.7.0
		 */
		private static $last_id = 0;

		/**
		 * Class cache for the extracted page URL.
		 *
		 * @var string
		 *
		 * @since 2.7.0
		 */
		private static $page_url = '';

		/**
		 * Class cache for the collected trace.
		 *
		 * @var string
		 *
		 * @since 2.7.0
		 */
		private static $trace = '';

		/**
		 * Inits the class.
		 *
		 * @return void
		 *
		 * @since 2.7.0
		 */
		public static function init() {
			\add_filter( 'pre_http_request', array( __CLASS__, 'pre_http_request' ), 0, 3 );
			\add_action( 'http_api_debug', array( __CLASS__, 'capture_request' ), 10, 5 );
		}

		/**
		 * Fires after an HTTP API response is received and before the response is returned.
		 *
		 * @param array|WP_Error $response    HTTP response or WP_Error object.
		 * @param string         $context     Context under which the hook is fired.
		 * @param string         $class       HTTP transport used.
		 * @param array          $parsed_args HTTP request arguments.
		 * @param string         $url         The request URL.
		 *
		 * @since 2.7.0
		 */
		public static function capture_request( $response, $context, $class, $parsed_args, $url ) {

			static $user_id = null;

			// Check if the response is an error.
			if ( \is_wp_error( $response ) ) {
				$status = 'error';
			} else {
				$status = 'success';
			}

			++self::$requests;

			if ( null === $user_id && \is_user_logged_in() ) {
				$user_id = \get_current_user_id();
			} else {
				$user_id = 0;
			}

			// Prepare the log entry.
			$log_entry = array(
				'url'            => $url,
				'page_url'       => self::page_url(),
				'type'           => self::current_page_type(),
				'domain'         => \wp_parse_url( $url, PHP_URL_HOST ),
				'user_id'        => $user_id,
				'runtime'        => microtime( true ) - ( ( $_SERVER['REQUEST_TIME_FLOAT'] ) ?? 0 ),
				'request_status' => $status,
				'request_group'  => isset( $parsed_args['group'] ) ? $parsed_args['group'] : '',
				'request_source' => isset( $parsed_args['source'] ) ? $parsed_args['source'] : '',
				'request_args'   => \wp_json_encode( $parsed_args ),
				'response'       => \is_wp_error( $response ) ? $response->get_error_message() : \wp_json_encode( $response ),
				'date_added'     => time(),
				'requests'       => self::$requests,
				'trace'          => self::get_trace(),
			);

			if ( isset( self::$last_id ) && self::$last_id > 0 ) {
				$log_entry ['id'] = self::$last_id;
			}

			// Save the log entry to the database.
			self::$last_id = Requests_Log_Entity::insert( $log_entry );
		}

		/**
		 * Collects and returns the trace of the current request in JSON format.
		 *
		 * @return string
		 *
		 * @since 2.7.0
		 */
		public static function get_trace(): string {
			if ( empty( self::$trace ) ) {
				$trace = new \Exception( '' )->getTrace();

				self::$trace = \json_encode( $trace, );
			}

			return self::$trace;
		}

		/**
		 * Fires before the actual request - start our timer.
		 *
		 * @param false|array|WP_Error $response    A preemptive return value of an HTTP request. Default false.
		 * @param array                $parsed_args HTTP request arguments.
		 * @param string               $url         The request URL.
		 *
		 * @since 2.7.0
		 */
		public static function pre_http_request( $response, $parsed_args, $url ) {
			// Start the timer.
			$_SERVER['REQUEST_TIME_FLOAT'] = microtime( true );

			return $response;
		}

		/**
		 * Return current page type.
		 * Id adding new page type update self::$page_types array with new page type group
		 *
		 * @return string cron|ajax|rest_api|xmlrpc|login|admin|frontend
		 *
		 * @since 2.7.0
		 */
		public static function current_page_type() {

			static $return;

			if ( is_null( $return ) ) {
				if ( is_null( $return ) && Context_Helper::is_cron() ) {
					$return = 'cron';
				}

				if ( is_null( $return ) && Context_Helper::is_ajax() ) {
					$return = 'ajax';
				}

				// Is REST API endpoint.
				if ( is_null( $return ) && Context_Helper::is_rest() ) {
					$return = 'rest_api';
				}

				if ( is_null( $return ) && Context_Helper::is_xml_rpc() ) {
					$return = 'xmlrpc';
				}

				if ( is_null( $return ) && Context_Helper::is_wp_cli() ) {
					$return = 'wp-cli';
				}

				if ( is_null( $return ) && Context_Helper::is_login() ) {
					$return = 'login';
				}

				if ( is_null( $return ) && Context_Helper::is_front() ) {
					$return = 'frontend';
				}

				if ( is_null( $return ) && Context_Helper::is_admin() ) {
					$return = 'admin';
				}

				if ( is_null( $return ) && Context_Helper::is_core() ) {
					$return = 'core';
				}

				if ( is_null( $return ) && Context_Helper::is_installing() ) {
					$return = 'installing';
				}

				if ( is_null( $return ) && Context_Helper::is_wp_activate() ) {
					$return = 'activate';
				}
				if ( is_null( $return ) && Context_Helper::is_undetermined() ) {
					$return = 'undetermined';
				}
			}

			// Certain or fallback type.
			return $return;
		}

		/**
		 * Collects the given page URL.
		 *
		 * @return string
		 *
		 * @since 2.7.0
		 */
		public static function page_url(): string {

			if ( ! empty( self::$page_url ) ) {
				return self::$page_url;
			}

			if ( isset( $_SERVER['HTTP_HOST'] ) && isset( $_SERVER['REQUEST_URI'] ) ) {
				$host           = \sanitize_text_field( $_SERVER['HTTP_HOST'] );
				$uri            = \sanitize_text_field( $_SERVER['REQUEST_URI'] );
				self::$page_url = ( \is_ssl() ? 'https://' : 'http://' ) . $host . $uri;
			} else {
				// use WordPress functions.
				global $wp;
				self::$page_url = \home_url( \add_query_arg( array(), $wp->request ) );
			}

			return self::$page_url;
		}
	}
}
