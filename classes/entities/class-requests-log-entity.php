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
			'user_id'        => 'int',
			'runtime'        => 'float',
			'request_status' => 'string',
			'request_group'  => 'string',
			'request_source' => 'string',
			'request_args'   => 'string',
			'response'       => 'string',
			'date_added'     => 'string',
			'requests'       => 'int',
			'trace'          => 'string',
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
			'user_id'        => 0,
			'runtime'        => 0,
			'request_status' => '',
			'request_group'  => '',
			'request_source' => '',
			'request_args'   => '',
			'response'       => '',
			'date_added'     => '',
			'requests'       => 0,
			'trace'          => '',
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
					requests SMALLINT unsigned NOT NULL DEFAULT 0,
					trace MEDIUMTEXT,
				PRIMARY KEY (id),
				KEY `runtime` (`runtime`)
				)
			  ' . $collate . ';';

			return self::maybe_create_table( $table_name, $wp_entity_sql, $connection );
		}

		/**
		 * Returns the table CMS admin fields
		 *
		 * @return array
		 *
		 * @since 2.1.0
		 */
		public static function get_column_names_admin(): array {
			return array(
				'date_added'     => __( 'Date', '0-day-analytics' ),
				'type'           => __( 'Type', '0-day-analytics' ),
				'request_status' => __( 'Status', '0-day-analytics' ),
				'url'            => __( 'URL', '0-day-analytics' ),
				'page_url'       => __( 'Page', '0-day-analytics' ),
				'domain'         => __( 'Domain', '0-day-analytics' ),
				'user_id'        => __( 'User', '0-day-analytics' ),
				'runtime'        => __( 'Runtime', '0-day-analytics' ),
			);
		}
	}
}
