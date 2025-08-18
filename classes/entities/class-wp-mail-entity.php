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

if ( ! class_exists( '\ADVAN\Entities\WP_Mail_Entity' ) ) {
	/**
	 * Responsible for the events metadata.
	 */
	class WP_Mail_Entity extends Abstract_Entity {
		/**
		 * Contains the table name.
		 *
		 * @var string
		 *
		 * @since latest
		 */
		protected static $table = ADVAN_PREFIX . 'wp_mail_log';

		/**
		 * Keeps the info about the columns of the table - name, type.
		 *
		 * @var array
		 *
		 * @since latest
		 */
		protected static $fields = array(
			'id'                 => 'int',
			'time'               => 'string',
			'email_to'           => 'string',
			'subject'            => 'string',
			'message'            => 'string',
			'backtrace_segment'  => 'string',
			'status'             => 'int',
			'is_html'            => 'int',
			'error'              => 'string',
			'attachments'        => 'string',
			'additional_headers' => 'string',
		);

		/**
		 * Holds all the default values for the columns.
		 *
		 * @var array
		 *
		 * @since latest
		 */
		protected static $fields_values = array(
			'id'                 => 0,
			'time'               => '',
			'email_to'           => '',
			'subject'            => '',
			'message'            => '',
			'backtrace_segment'  => '',
			'status'             => 0,
			'is_html'            => 0,
			'error'              => '',
			'attachments'        => '',
			'additional_headers' => '',
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
					is_html BOOL DEFAULT 1 NOT NULL,
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
				'time'              => __( 'Date', '0-day-analytics' ),
				'email_to'          => __( 'To', '0-day-analytics' ),
				'subject'           => __( 'Subject', '0-day-analytics' ),
				'backtrace_segment' => __( 'Source', '0-day-analytics' ),
			);
		}
	}
}
