<?php
/**
 * Entity: Requests.
 *
 * @package advan
 *
 * @since 2.4.2.1
 */

declare(strict_types=1);

namespace ADVAN\Entities;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\ADVAN\Entities\Requests_Log_Entity' ) ) {
	/**
	 * Responsible for the events metadata.
	 */
	class Requests_Log_Entity extends Abstract_Entity {
		/**
		 * Contains the table name.
		 *
		 * @var string
		 *
		 * @since 2.4.2.1
		 */
		protected static $table = ADVAN_PREFIX . 'requests_log';

		/**
		 * Keeps the info about the columns of the table - name, type.
		 *
		 * @var array
		 *
		 * @since 2.4.2.1
		 */
		protected static $fields = array(
			'id'             => 'int',
			'type'           => 'string',
			'url'            => 'string',
			'page_url'       => 'string',
			'domain'         => 'string',
			'runtime'        => 'float',
			'request_status' => 'string',
			'request_group'  => 'string',
			'request_source' => 'string',
			'request_args'   => 'string',
			'response'       => 'string',
			'date_added'     => 'string',
		);

		/**
		 * Holds all the default values for the columns.
		 *
		 * @var array
		 *
		 * @since 2.4.2.1
		 */
		protected static $fields_values = array(
			'id'             => 0,
			'type'           => '',
			'url'            => '',
			'page_url'       => '',
			'domain'         => '',
			'runtime'        => 0,
			'request_status' => '',
			'request_group'  => '',
			'request_source' => '',
			'request_args'   => '',
			'response'       => '',
			'date_added'     => '',
		);

		/**
		 * Creates table functionality.
		 *
		 * @param \wpdb $connection - \wpdb connection to be used for name extraction.
		 *
		 * @since 2.4.2.1
		 */
		public static function create_table( $connection = null ): bool {
			if ( null !== $connection ) {
				if ( $connection instanceof \wpdb ) {
					$collate = $connection->get_charset_collate();

				}
			} else {
				$collate = self::get_connection()->get_charset_collate();
			}
			$table_name    = self::get_table_name( $connection );
			$wp_entity_sql = '
				CREATE TABLE `' . $table_name . '` (
					id BIGINT unsigned not null auto_increment,
					type VARCHAR(20) NOT NULL DEFAULT "",
					url TEXT(2048),
					page_url TEXT(2048),
					user_id BIGINT unsigned NOT NULL DEFAULT 0,
					domain TEXT(255),
					runtime DECIMAL(10,3),
					request_status VARCHAR(20),
					request_group VARCHAR(20),
					request_source VARCHAR(255),
					request_args MEDIUMTEXT,
					response MEDIUMTEXT,            
					date_added DOUBLE NOT NULL DEFAULT 0,
				PRIMARY KEY (id),
				KEY `runtime` (`runtime`)
				)
			  ' . $collate . ';';

			return self::maybe_create_table( $table_name, $wp_entity_sql, $connection );
		}
	}
}
