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
			'id'            => 'int',
			'occurrence_id' => 'int',
			'name'          => 'string',
			'value'         => 'string',
		);

		/**
		 * Holds all the default values for the columns.
		 *
		 * @var array
		 *
		 * @since 2.4.2.1
		 */
		protected static $fields_values = array(
			'id'            => 0,
			'occurrence_id' => 0,
			'name'          => '',
			'value'         => '',
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
					`id` bigint NOT NULL AUTO_INCREMENT,
					`occurrence_id` bigint(20) NOT NULL,
					`name` varchar(60) NOT NULL,
					`value` longtext NOT NULL,
				PRIMARY KEY (`id`),
				KEY `occurrence_name` (`occurrence_id`,`name`),
				KEY `name_value` (`name`,`value`(64))
				)
			  ' . $collate . ';';

			return self::maybe_create_table( $table_name, $wp_entity_sql, $connection );
		}
	}
}
