<?php
/**
 * Entity: WP_Mail.
 *
 * @package advan
 *
 * @since 3.0.0
 */

declare(strict_types=1);

namespace ADVAN\Entities;

use ADVAN\Helpers\WP_Helper;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\ADVAN\Entities\WP_Mail_Entity' ) ) {
	/**
	 * Responsible for the mail metadata.
	 */
	class WP_Mail_Entity extends Abstract_Entity {
		/**
		 * Contains the table name.
		 *
		 * @var string
		 *
		 * @since 3.0.0
		 */
		protected static $table = ADVAN_PREFIX . 'wp_mail_log';

		/**
		 * Keeps the info about the columns of the table - name, type.
		 *
		 * @var array
		 *
		 * @since 3.0.0
		 */
		protected static $fields = array(
			'id'                 => 'int',
			'blog_id'            => 'int',
			'time'               => 'string',
			'email_to'           => 'string',
			'email_from'         => 'string',
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
		 * @since 3.0.0
		 */
		protected static $fields_values = array(
			'id'                 => 0,
			'blog_id'            => 0,
			'time'               => '',
			'email_to'           => '',
			'email_from'         => '',
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
		 * @since 3.0.0
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
					blog_id int NOT NULL,
					time DOUBLE NOT NULL DEFAULT 0,
					email_to TEXT DEFAULT NULL,
					email_from TEXT DEFAULT NULL,
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
		 * Responsible for adding the from email column to the table (version 3.0.1).
		 *
		 * @return array|bool
		 *
		 * @since 3.0.1
		 */
		public static function alter_table_301() {
			$sql = 'ALTER TABLE `' . self::get_table_name() . '` ADD `email_from` TEXT DEFAULT NULL AFTER `email_to`;';

			return Common_Table::execute_query( $sql );
		}

		/**
		 * Alters the table to add the blog_id for more precise logging in multisite setups.
		 *
		 * @return array|bool
		 *
		 * @since 3.6.3
		 */
		public static function alter_table_363() {
			$sql = 'ALTER TABLE `' . self::get_table_name() . '` ADD `blog_id` INT NOT NULL AFTER `id`';

			// Extend our logging logic to capture get_current_blog_id() / get_site_url() and store it in a new column in the log table.

			return Common_Table::execute_query( $sql );
		}

		/**
		 * Returns the table CMS admin fields
		 *
		 * @return array
		 *
		 * @since 3.0.0
		 */
		public static function get_column_names_admin(): array {
			$columns = array(
				'time'              => __( 'Date', '0-day-analytics' ),
				'email_to'          => __( 'To', '0-day-analytics' ),
				'email_from'        => __( 'From', '0-day-analytics' ),
				'subject'           => __( 'Subject', '0-day-analytics' ),
				'is_html'           => __( 'Is HTML', '0-day-analytics' ),
				'attachments'       => __( 'Attachments', '0-day-analytics' ),
				'backtrace_segment' => __( 'Source', '0-day-analytics' ),
			);

			if ( WP_Helper::is_multisite() ) {
				$columns['blog_id'] = __( 'From Blog', '0-day-analytics' );
			}

			return $columns;
		}

		/**
		 * Generates drop down with all the subsites that have mail logs.
		 *
		 * @param string $selected - The selected (if any) site ID.
		 * @param string $which - Indicates postion of the dropdown (top or bottom).
		 *
		 * @return string
		 *
		 * @since 3.6.3
		 */
		public static function get_all_sites_dropdown( $selected = '', $which = '' ): string {
			$sql = 'SELECT blog_id FROM ' . self::get_table_name() . ' GROUP BY blog_id ORDER BY blog_id DESC';

			$results = self::get_results( $sql );
			$sites   = array();
			$output  = '';

			if ( $results ) {
				foreach ( $results as $result ) {
					$details = \get_blog_details( array( 'blog_id' => $result['blog_id'] ) );
					$name    = ( $details ) ? $details->blogname : \sprintf( /* translators: %s: Site ID */ __( 'Site %s', '0-day-analytics' ), (int) $result['blog_id'] );
					$sites[] = array(
						'id'   => $result['blog_id'],
						'name' => $name,
					);
				}
			}

			if ( ! empty( $sites ) ) {

				$output  = '<select class="site_id_filter" name="site_id_' . \esc_attr( $which ) . '" id="site_id_' . \esc_attr( $which ) . '">';
				$output .= '<option value="-1">' . __( 'All sites', '0-day-analytics' ) . '</option>';
				foreach ( $sites as $site_info ) {
					if ( isset( $selected ) && ! empty( trim( (string) $selected ) ) && (int) $selected === (int) $site_info['id'] ) {
						$output .= '<option value="' . \esc_attr( $site_info['id'] ) . '" selected>' . \esc_html( $site_info['name'] ) . '</option>';

						continue;
					}
					$output .= '<option value="' . \esc_attr( $site_info['id'] ) . '">' . \esc_html( $site_info['name'] ) . '</option>';
				}

				$output .= '</select>';
			}

			return $output;
		}
	}
}
