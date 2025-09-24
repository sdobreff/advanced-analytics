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

use ADVAN\Helpers\Plugin_Theme_Helper;

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
		 * Inner class cache for rendered dorp down with of of the collected data from sites.
		 *
		 * @var string
		 *
		 * @since latest
		 */
		private static $drop_down_sites_rendered = false;

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
			'plugin'         => 'string',
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
			'plugin'         => '',
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
					plugin VARCHAR(200) NOT NULL DEFAULT "",
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
		 * Responsible for adding the plugin column to the table (version 3.7.0).
		 *
		 * @return array|bool
		 *
		 * @since 3.7.0
		 */
		public static function alter_table_370() {
			$sql = 'ALTER TABLE `' . self::get_table_name() . '` ADD `plugin` TEXT DEFAULT "" AFTER `type`;';

			return Common_Table::execute_query( $sql );
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
				'plugin'         => __( 'Plugin Name', '0-day-analytics' ),
				'request_status' => __( 'Status', '0-day-analytics' ),
				'url'            => __( 'URL', '0-day-analytics' ),
				'page_url'       => __( 'Page', '0-day-analytics' ),
				'domain'         => __( 'Domain', '0-day-analytics' ),
				'user_id'        => __( 'User', '0-day-analytics' ),
				'runtime'        => __( 'Runtime', '0-day-analytics' ),
			);
		}

		/**
		 * Generates drop down with all the subsites that have mail logs.
		 *
		 * @param string $selected - The selected (if any) site ID.
		 * @param string $which - Indicates position of the dropdown (top or bottom).
		 *
		 * @return string
		 *
		 * @since 3.6.3
		 */
		public static function get_all_plugins_dropdown( $selected = '', $which = '' ): string {

			if ( false === self::$drop_down_sites_rendered ) {
				$sql = 'SELECT plugin FROM ' . self::get_table_name() . ' GROUP BY plugin ORDER BY plugin DESC';

				$results = self::get_results( $sql );
				$plugins   = array();
				$output  = '';

				if ( $results ) {
					foreach ( $results as $result ) {
						if ( ! isset( $result['plugin'] ) || empty( trim( (string) $result['plugin'] ) ) ) {
							continue;
						}
						$details = Plugin_Theme_Helper::get_plugin_from_path( $result['plugin'] );
						$name    = ( $details ) ? $details['Name'] : \sprintf( /* translators: %s: Site ID */ __( '%s', '0-day-analytics' ), (int) $result['plugin'] );
						$plugins[] = array(
							'id'   => $result['plugin'],
							'name' => $name,
						);
					}
				}

				if ( ! empty( $plugins ) ) {

					$output  = '<select class="plugin_filter" name="plugin_' . \esc_attr( $which ) . '" id="plugin_' . \esc_attr( $which ) . '">';
					$output .= '<option value="-1">' . __( 'All plugins', '0-day-analytics' ) . '</option>';
					foreach ( $plugins as $plugin_info ) {
						if ( isset( $selected ) && ! empty( trim( (string) $selected ) ) && (string) $selected === (string) $plugin_info['id'] ) {
							$output .= '<option value="' . \esc_attr( $plugin_info['id'] ) . '" selected>' . \esc_html( $plugin_info['name'] ) . '</option>';

							continue;
						}
						$output .= '<option value="' . \esc_attr( $plugin_info['id'] ) . '">' . \esc_html( $plugin_info['name'] ) . '</option>';
					}

					$output .= '</select>';
				}
				self::$drop_down_sites_rendered = $output;
			}

			return self::$drop_down_sites_rendered;
		}
	}
}
