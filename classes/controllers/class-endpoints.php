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

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Endpoints registering
 */
if ( ! class_exists( '\ADVAN\Controllers\Api\Endpoints' ) ) {

	/**
	 * Creates and registers all the API endpoints for the proxytron
	 *
	 * @since 1.9.2
	 */
	class Endpoints {
		const NAMESPACE = 'wp-control/v1';

		/**
		 * All of the endpoints supported by the plugin.
		 *
		 * @var array
		 *
		 * @since 1.9.2
		 */
		public static $endpoints = array(
			'site' => array(
				'class'     => __CLASS__,
				'endpoints' => array(
					array(
						/**
						 * Extracts record from the database.
						 * Expected result:
						 *
						 * [
						 *  {
						 *      "id": "338",
						 *      "plugin_id": "8257",
						 *      "site_id": "9901982",
						 *      "site_url": "https:\/\/www.scanbit.se",
						 *      "license_id": "966913",
						 *      "user_count": "80",
						 *      "quota": "-1",
						 *      "type": "",
						 *      "created": "2022-07-10 23:05:58",
						 *      "last_changed": "0000-00-00 00:00:00",
						 *      "last_contacted": "2022-07-21 23:59:59"
						 *  }
						 *  ]
						 */
						'customers/(?P<plugin_id>\d+)/(?P<site_id>\d+)/(?P<license_id>\d+)' => array(
							'methods'          => array(
								'method'   => \WP_REST_Server::READABLE,
								'callback' => 'get_action',
							),
							'args'             => array(
								'plugin_id'  => array(
									'required'    => true,
									'type'        => 'integer',
									'description' => 'Plugin ID',
									'minimum'     => 1,
								),
								'site_id'    => array(
									'required'    => true,
									'type'        => 'integer',
									'description' => 'Site ID',
									'minimum'     => 1,
								),
								'license_id' => array(
									'required'    => true,
									'type'        => 'integer',
									'description' => 'License ID',
									'minimum'     => 1,
								),
							),
							'checkPermissions' => array(
								__CLASS__,
								'authorize_request',
							),
							'showInIndex'      => false,
						),
					),
				),
			),
		);

		/**
		 * Inits all the endpoints from given structure
		 *
		 * @return void
		 *
		 * @since 1.9.2
		 */
		public static function init() {
			foreach ( self::$endpoints as $settings ) {
				$class = $settings['class'];

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
							self::NAMESPACE,
							'/' . $route,
							array(
								array(
									'methods'             => $endpoint['methods']['method'],
									'callback'            => array( $class, $endpoint['methods']['callback'] ),
									'args'                => $args,
									'permission_callback' => $check_permissions,
									'show_in_index'       => $show_in_index,
								),
							),
							false
						);
					}
				}
			}
		}
	}
}
