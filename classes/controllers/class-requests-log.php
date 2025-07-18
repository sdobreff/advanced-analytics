<?php
/**
 * Requests log class - showing the pointers where necessary.
 *
 * @package awesome-footnotes
 *
 * @since latest
 */

declare(strict_types=1);

namespace ADVAN\Controllers;

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
			\apply_filters( 'pre_http_request', array( __CLASS__, 'pre_http_request' ), 0, 3 );
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
		 * @since latest
		 */
		public static function capture_request( $response, $context, $class, $parsed_args, $url ) {
			// Check if the response is an error.
			if ( is_wp_error( $response ) ) {
				$status = 'error';
			} else {
				$status = 'success';
			}

			// Prepare the log entry.
			$log_entry = array(
				'url'            => $url,
				'domain'         => \wp_parse_url( $url, PHP_URL_HOST ),
				'runtime'        => microtime( true ) - ( ( $_SERVER['REQUEST_TIME_FLOAT'] ) ?? 0 ),
				'request_status' => $status,
				'request_group'  => isset( $parsed_args['group'] ) ? $parsed_args['group'] : '',
				'request_source' => isset( $parsed_args['source'] ) ? $parsed_args['source'] : '',
				'request_args'   => \wp_json_encode( $parsed_args ),
				'response'       => \is_wp_error( $response ) ? $response->get_error_message() : \wp_json_encode( $response ),
				'date_added'     => current_time( 'mysql' ),
			);

			// Save the log entry to the database.
			Requests_Log_Entity::insert( $log_entry );
		}

		/**
		 * Fires before the actual request - start our timer.
		 *
		 * @param false|array|WP_Error $response    A preemptive return value of an HTTP request. Default false.
		 * @param array                $parsed_args HTTP request arguments.
		 * @param string               $url         The request URL.
		 *
		 * @since latest
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
		 * @since latest
		 */
		public static function current_page_type() {

			if ( is_null( $return ) ) {
				if ( is_null( $return ) && ( ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() ) || ( defined( 'DOING_CRON' ) && DOING_CRON ) ) ) {
					$return = 'cron';
				}

				if ( is_null( $return ) && wp_doing_ajax() ) {
					$return = 'ajax';
				}

				// is REST API endpoint
				if ( is_null( $return ) && ( ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ! empty( $GLOBALS['wp']->query_vars['rest_route'] ) ) ) {
					$return = 'rest_api';
				}

				if ( is_null( $return ) && ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) ) {
					$return = 'xmlrpc';
				}

				if ( is_null( $return ) && self::is_login_page() ) {
					$return = 'login';
				}
			}

			// certain or fallback type
			return is_null( $return ) ? ( is_admin() ? 'admin' : 'frontend' ) : $return;
		}
	}
}
