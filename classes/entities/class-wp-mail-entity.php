<?php
/**
 * Entity: WP_Mail.
 *
 * @package advan
 *
 * @since latest
 */

declare(strict_types=1);

namespace ADVAN\Entities;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\ADVAN\Entities\WP_Mail_entity' ) ) {
	/**
	 * Responsible for the events metadata.
	 */
	class WP_Mail_entity extends Abstract_Entity {
		/**
		 * Contains the table name.
		 *
		 * @var string
		 *
		 * @since latest
		 */
		protected static $table = ADVAN_PREFIX . 'wp-mail-log';

		/**
		 * Keeps the info about the columns of the table - name, type.
		 *
		 * @var array
		 *
		 * @since latest
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
		 * @since latest
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
		 * @since latest
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
					id BIGINT NOT NULL AUTO_INCREMENT,
					time DOUBLE NOT NULL DEFAULT 0,
					email_to TEXT DEFAULT NULL,
					subject TEXT DEFAULT NULL,
					message MEDIUMTEXT DEFAULT NULL,
					backtrace_segment MEDIUMTEXT NOT NULL,
					status BOOL DEFAULT 1 NOT NULL,
					error TEXT DEFAULT NULL,
					attachments MEDIUMTEXT DEFAULT NULL,
					additional_headers TEXT DEFAULT NULL,
				PRIMARY KEY (id)
				)
			  ' . $collate . ';';

			return self::maybe_create_table( $table_name, $wp_entity_sql, $connection );
		}

		/**
		 * Returns the table CMS admin fields
		 *
		 * @return array
		 *
		 * @since latest
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
