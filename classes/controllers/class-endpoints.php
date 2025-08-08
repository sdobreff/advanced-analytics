<?php
/**
 * Responsible for the API endpoints
 *
 * @package    advanced-analytics
 * @subpackage endpoints
 * @since 1.9.2
 * @copyright  2025 Stoil Dobrev
 * @license    https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 * @link       https://wordpress.org/plugins/wp-2fa/
 */

declare(strict_types=1);

namespace ADVAN\ControllersApi;

use ADVAN\Lists\Logs_List;
use ADVAN\Lists\Requests_List;
use ADVAN\Entities\Common_Table;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Endpoints registering
 */
if ( ! class_exists( '\ADVAN\Controllers\Api\Endpoints' ) ) {

	/**
	 * Creates and registers all the API endpoints for the plugin
	 *
	 * @since 1.9.2
	 */
	class Endpoints {

		public const ENDPOINT_ROOT_NAME = '0-day';

		/**
		 * All of the endpoints supported by the plugin.
		 *
		 * @var array
		 *
		 * @since 1.9.2
		 */
		public static $endpoints = array(
			self::class => array(
				'live'            => array(
					'class'     => Logs_List::class,
					'namespace' => self::ENDPOINT_ROOT_NAME . '/v1',

					'endpoints' => array(
						array(
							'get_last_item' => array(
								'methods'          => array(
									'method'   => \WP_REST_Server::READABLE,
									'callback' => 'extract_last_item',
								),
								'checkPermissions' => array( __CLASS__, 'check_permissions' ),
								'showInIndex'      => false,
							),
						),
					),
				),
				'severity'        => array(
					'class'     => Logs_List::class,
					'namespace' => self::ENDPOINT_ROOT_NAME . '/v1',

					'endpoints' => array(
						array(
							'(?P<severity_name>\w+)/(?P<status>\w+)/' => array(
								'methods'          => array(
									'method'   => \WP_REST_Server::READABLE,
									'callback' => 'set_severity_status',
								),
								'args'             => array(
									'severity_name' => array(
										'required'    => true,
										'type'        => 'string',
										'description' => 'Severity name',
									),
									'status'        => array(
										'required'    => true,
										'type'        => 'string',
										'description' => 'Severity status',
									),
								),
								'checkPermissions' => array( __CLASS__, 'check_permissions' ),
								'showInIndex'      => false,
							),
						),
					),
				),
				'single_severity' => array(
					'class'     => Logs_List::class,
					'namespace' => self::ENDPOINT_ROOT_NAME . '/v1',

					'endpoints' => array(
						array(
							'(?P<severity_name>\w+)/' => array(
								'methods'          => array(
									'method'   => \WP_REST_Server::READABLE,
									'callback' => 'set_single_severity',
								),
								'args'             => array(
									'severity_name' => array(
										'required'    => true,
										'type'        => 'string',
										'description' => 'Severity name',
									),
								),
								'checkPermissions' => array( __CLASS__, 'check_permissions' ),
								'showInIndex'      => false,
							),
						),
					),
				),
				'requests'        => array(
					'class'     => Requests_List::class,
					'namespace' => self::ENDPOINT_ROOT_NAME . '/v1',

					'endpoints' => array(
						array(
							'(?P<request_type>\w+)/(?P<status>\w+)/' => array(
								'methods'          => array(
									'method'   => \WP_REST_Server::READABLE,
									'callback' => 'set_request_status',
								),
								'args'             => array(
									'request_type' => array(
										'required'    => true,
										'type'        => 'string',
										'description' => 'Request type',
									),
									'status'       => array(
										'required'    => true,
										'type'        => 'string',
										'description' => 'Severity status',
									),
								),
								'checkPermissions' => array( __CLASS__, 'check_permissions' ),
								'showInIndex'      => false,
							),
						),
					),
				),
				'drop_table'      => array(
					'class'     => Common_Table::class,
					'namespace' => self::ENDPOINT_ROOT_NAME . '/v1',

					'endpoints' => array(
						array(
							'(?P<table_name>\w+)/' => array(
								'methods'          => array(
									'method'   => \WP_REST_Server::DELETABLE,
									'callback' => 'drop_table',
								),
								'args'             => array(
									'table_name' => array(
										'required'    => true,
										'type'        => 'string',
										'pattern'     => '\w+',
										'description' => 'Table name',
									),
								),
								'checkPermissions' => array( __CLASS__, 'check_permissions' ),
								'showInIndex'      => false,
							),
						),
					),
				),
				'truncate_table'  => array(
					'class'     => Common_Table::class,
					'namespace' => self::ENDPOINT_ROOT_NAME . '/v1',

					'endpoints' => array(
						array(
							'(?P<table_name>\w+)/' => array(
								'methods'          => array(
									'method'   => \WP_REST_Server::DELETABLE,
									'callback' => 'truncate_table',
								),
								'args'             => array(
									'table_name' => array(
										'required'    => true,
										'type'        => 'string',
										'pattern'     => '\w+',
										'description' => 'Table name',
									),
								),
								'checkPermissions' => array( __CLASS__, 'check_permissions' ),
								'showInIndex'      => false,
							),
						),
					),
				),
			),
		);

		/**
		 * Inits the class
		 *
		 * @return void
		 *
		 * @since 1.9.2
		 */
		public static function init() {

			\add_action( 'rest_api_init', array( __CLASS__, 'init_endpoints' ) );

			// $api_classes = Classes_Helper::get_classes_by_namespace( 'WP2FA\Admin\Controllers\API' );

			// if ( \is_array( $api_classes ) && ! empty( $api_classes ) ) {
			// foreach ( $api_classes as $class ) {
			// if ( \method_exists( $class, 'init' ) ) {
			// $class::init();
			// }
			// }
			// }
		}

		/**
		 * Inits all the endpoints from given structure
		 *
		 * @return void
		 *
		 * @since 1.9.2
		 */
		public static function init_endpoints() {
			foreach ( self::$endpoints as $endpoint_provider ) {
				foreach ( $endpoint_provider as $root_endpoint => $settings ) {
					$class     = $settings['class'];
					$namespace = $settings['namespace'];

					foreach ( $settings['endpoints'] as $routes ) {
						foreach ( $routes as $route => $endpoint ) {
							$args = array();
							if ( isset( $endpoint['args'] ) ) {
								$args = $endpoint['args'];
							}
							$check_permissions = array();
							if ( isset( $endpoint['checkPermissions'] ) ) {
								$check_permissions = $endpoint['checkPermissions'];
							}
							$show_in_index = $endpoint['showInIndex'];
							\register_rest_route(
								$namespace,
								'/' . $root_endpoint . '/' . $route,
								array(
									array(
										'methods'       => $endpoint['methods']['method'],
										'callback'      => array( $class, $endpoint['methods']['callback'] ),
										'args'          => $args,
										'permission_callback' => $check_permissions,
										'show_in_index' => $show_in_index,
									),
								),
								false
							);
						}
					}
				}
			}
		}

		/**
		 * Global method to check permissions for API endpoints - this one checks if the user has admin capability.
		 *
		 * @return bool
		 *
		 * @since 1.9.2
		 */
		public static function check_permissions() {
			return \current_user_can( 'manage_options' );
		}
	}
}
