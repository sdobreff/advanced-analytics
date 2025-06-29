<?php
/**
 * Class: WP Transients Helper class
 *
 * Helper class to manipulate WP transients.
 *
 * @package advanced-analytics
 *
 * @since 1.7.0
 */

declare(strict_types=1);

namespace ADVAN\Helpers;

use ADVAN\Lists\Transients_List;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\ADVAN\Helpers\Transients_Helper' ) ) {
	/**
	 * Responsible for proper context determination.
	 *
	 * @since 1.7.0
	 */
	class Transients_Helper {

		public const WP_CORE_TRANSIENTS = array(
			'update_themes',
			'update_plugins',
			'update_core',
			'theme_roots',
			'poptags_',
			'wp_theme_files_patterns-',
		);

		/**
		 * Deletes a cron event
		 *
		 * @param int $id - The hash of the event to delete.
		 *
		 * @return bool|\WP_Error
		 *
		 * @since 1.7.0
		 */
		public static function delete_transient( int $id ) {

			if ( 0 < $id ) {
				global $wpdb;

				$esc_name = '%' . $wpdb->esc_like( '_transient_' ) . '%';
				$esc_time = '%' . $wpdb->esc_like( '_transient_timeout_' ) . '%';

				$sql = array( 'SELECT' );

				$sql[] = 'option_name';

				$sql[] = "FROM {$wpdb->options} WHERE option_name LIKE %s AND option_name NOT LIKE %s AND option_id = %d";

				$query = implode( ' ', $sql );

				// Prepare.
				$prepared = $wpdb->prepare( $query, $esc_name, $esc_time, $id ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

				$transient = $wpdb->get_var( $prepared, 0 ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
			}

			// Bail if no Transient.
			if ( empty( $transient ) ) {
				return false;
			}

			$transient_name = self::get_transient_name( $transient );

			// Transient type.
			$retval = ( false !== self::is_site_wide( $transient ) )
			? delete_site_transient( $transient_name )
			: delete_transient( $transient_name );

			if ( false === $retval ) {
				return new \WP_Error(
					'transient_not_deleted',
					__( 'Transient is not / can not be deleted.', '0-day-analytics' )
				);
			}

			// Return.
			return $retval;
		}

		/**
		 * Removes all cron events for a specific hook
		 *
		 * @param string $hook The action hook name.
		 *
		 * @since 1.7.0
		 */
		public static function clear_events( $hook ) {
			\wp_clear_scheduled_hook( $hook );
		}

		/**
		 * Is a transient name site-wide?
		 *
		 * @param  string $transient_name - The transient name.
		 *
		 * @return boolean
		 *
		 * @since 1.7.0
		 */
		public static function is_site_wide( $transient_name = '' ): bool {
			return ( false !== strpos( $transient_name, '_site_transient' ) );
		}

		/**
		 * Retrieve the transient name from the transient object
		 *
		 * @param  string $transient - The transient name.
		 *
		 * @return string
		 *
		 * @since 1.7.0
		 */
		public static function get_transient_name( $transient = false ): string {

			// Bail if no Transient.
			if ( empty( $transient ) ) {
				return '';
			}

			// Position.
			$pos = self::is_site_wide( $transient )
			? 16
			: 11;

			return substr( $transient, $pos, strlen( $transient ) );
		}

		/**
		 * Retrieve a transient by its ID
		 *
		 * @param  int $id - The ID of the transient to retrieve.
		 *
		 * @return array
		 *
		 * @since 1.8.5
		 */
		public static function get_transient_by_id( $id = 0 ) {
			global $wpdb;

			$id = \absint( $id );

			// Bail if empty ID.
			if ( empty( $id ) ) {
				return false;
			}

			// Prepare.
			$prepared = $wpdb->prepare( "SELECT * FROM {$wpdb->options} WHERE option_id = %d", $id );

			// Query.
			return $wpdb->get_row( $prepared, ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
		}

		/**
		 * Update an existing transient
		 *
		 * @param  array   $transient - The transient to update.
		 * @param  boolean $site_wide - Is the transient site-wide?.
		 *
		 * @return boolean
		 *
		 * @since 1.8.5
		 */
		public static function update_transient( $transient = '', $site_wide = false ) {

			// Bail if no Transient.
			if ( empty( $transient ) ) {
				return false;
			}

			if ( ! isset( $_POST['value'], $_REQUEST['cron_next_run_custom_date'], $_REQUEST['cron_next_run_custom_time'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				return false;
			}

			// Values.
			$value = \stripslashes( $_POST['value'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			$value = \maybe_unserialize( $value );

			/*
			// $expiration = \absint( \wp_unslash( $_POST['expires'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

			// Subtract now.
			// $expiration = ( $expiration - time() );
			*/

			$current_time = time();

			$date = ( ( isset( $_REQUEST['cron_next_run_custom_date'] ) ) ? \sanitize_text_field( \wp_unslash( $_REQUEST['cron_next_run_custom_date'] ) ) : '' );

			$time = ( ( isset( $_REQUEST['cron_next_run_custom_time'] ) ) ? \sanitize_text_field( \wp_unslash( $_REQUEST['cron_next_run_custom_time'] ) ) : '' );

			$next_run_local = $date . ' ' . $time;

			$next_run_local = strtotime( $next_run_local, $current_time );

			if ( false === $next_run_local ) {
				return new \WP_Error(
					'invalid_timestamp',
					__( 'Invalid timestamp provided.', '0-day-analytics' )
				);
			}

			$expiration = (int) \get_gmt_from_date( \gmdate( 'Y-m-d H:i:s', $next_run_local ), 'U' );

			$expiration = ( $expiration - time() );

			// Transient type.
			$retval = ( false !== $site_wide )
			? \set_site_transient( $transient, $value, $expiration )
			: \set_transient( $transient, $value, $expiration );

			return $retval;
		}

		/**
		 * Creates transient using values in $_POST array
		 *
		 * @param string  $transient - The name of the transient.
		 * @param boolean $site_wide - Is this a site-wide transient or not.
		 *
		 * @return boolean
		 *
		 * @since 1.9.2
		 */
		public static function create_transient( $transient = '', $site_wide = false ) {
			return self::update_transient( $transient, $site_wide );
		}

		/**
		 * Retrieve the human-friendly transient value from the transient object
		 *
		 * @param  string $transient - The transient value.
		 *
		 * @return string/int
		 *
		 * @since 1.7.0
		 */
		public static function get_transient_value( $transient ) {

			// Get the value type.
			$type = self::get_transient_value_type( $transient );

			// Trim value to 100 chars.
			$value = substr( $transient, 0, 100 );

			// Escape & wrap in <code> tag.
			$value = '<code>' . \esc_html( $value ) . '</code>';

			// Return.
			return $value . '<br><span class="transient-type badge">' . esc_html( $type ) . '</span>';
		}

		/**
		 * Try to guess the type of value the Transient is
		 *
		 * @param  mixed $transient - The transient value.
		 *
		 * @return string
		 *
		 * @since 1.7.0
		 */
		private static function get_transient_value_type( $transient ): string {

			// Default type.
			$type = esc_html__( 'unknown', '0-day-analytics' );

			// Try to unserialize.
			$value = maybe_unserialize( $transient );

			// Array.
			if ( is_array( $value ) ) {
				$type = esc_html__( 'array', '0-day-analytics' );

				// Object.
			} elseif ( is_object( $value ) ) {
				$type = esc_html__( 'object', '0-day-analytics' );

				// Serialized array.
			} elseif ( is_serialized( $value ) ) {
				$type = esc_html__( 'serialized', '0-day-analytics' );

				// HTML.
			} elseif ( strip_tags( $value ) !== $value ) {
				$type = esc_html__( 'html', '0-day-analytics' );

				// Scalar.
			} elseif ( is_scalar( $value ) ) {

				if ( is_numeric( $value ) ) {

					// Likely a timestamp.
					if ( 10 === strlen( $value ) ) {
						$type = esc_html__( 'timestamp?', '0-day-analytics' );

						// Likely a boolean.
					} elseif ( in_array( $value, array( '0', '1' ), true ) ) {
						$type = esc_html__( 'boolean?', '0-day-analytics' );

						// Any number.
					} else {
						$type = esc_html__( 'numeric', '0-day-analytics' );
					}

					// JSON.
				} elseif ( is_string( $value ) && is_object( json_decode( $value ) ) ) {

					$type = esc_html__( 'json', '0-day-analytics' );
				} elseif ( is_string( $value ) && in_array( $value, array( 'no', 'yes', 'false', 'true' ), true ) ) {
						$type = esc_html__( 'boolean?', '0-day-analytics' );

					// Scalar.
				} else {
					$type = esc_html__( 'scalar', '0-day-analytics' );
				}

				// Empty.
			} elseif ( empty( $value ) ) {
				$type = esc_html__( 'empty', '0-day-analytics' );
			}

			// Return type.
			return $type;
		}

		/**
		 * Retrieve the expiration timestamp
		 *
		 * @param  string $transient - The transient name.
		 *
		 * @return int
		 *
		 * @since 1.7.0
		 */
		public static function get_transient_expiration_time( $transient ): int {

			// Get the same to use in the option key.
			$name = self::get_transient_name( $transient );

			// Get the value of the timeout.
			$time = self::is_site_wide( $transient )
			? \get_option( "_site_transient_timeout_{$name}" )
			: \get_option( "_transient_timeout_{$name}" );

			// Return the value.
			return (int) $time;
		}

		/**
		 * Collect error items.
		 *
		 * @param  array $args - Array with arguments to use.
		 *
		 * @return array|int
		 *
		 * @since 1.9.0
		 */
		public static function get_transient_items( $args = array() ) {

			global $wpdb;

			// Parse arguments.
			$parsed_args = Transients_List::parse_args( $args );

			// Escape some LIKE parts.
			$esc_name = '%' . $wpdb->esc_like( '_transient_' ) . '%';
			$esc_time = '%' . $wpdb->esc_like( '_transient_timeout_' ) . '%';

			// SELECT.
			$sql = array( 'SELECT' );

			// COUNT.
			if ( ! empty( $parsed_args['count'] ) ) {
				$sql[] = 'count(option_id)';
			} else {
				$sql[] = 'option_id, option_name, option_value, autoload';
			}

			// FROM.
			$sql[] = "FROM {$wpdb->options} WHERE option_name LIKE %s AND option_name NOT LIKE %s";

			// Search.
			if ( ! empty( $parsed_args['search'] ) ) {
				$search = '%' . $wpdb->esc_like( $parsed_args['search'] ) . '%';
				$sql[]  = $wpdb->prepare( 'AND option_name LIKE %s', $search );
			}

			// Limits.
			if ( empty( $parsed_args['count'] ) ) {
				$offset = absint( $parsed_args['offset'] );
				$number = absint( $parsed_args['number'] );

				if ( ! empty( $parsed_args['orderby'] ) && \in_array( $parsed_args['orderby'], array( 'transient_name' ) ) ) {

					$orderby = 'option_name';

					$order = 'DESC';

					if ( ! empty( $parsed_args['order'] ) && \in_array( $parsed_args['order'], array( 'ASC', 'DESC', 'asc', 'desc' ) ) ) {

						$order = $parsed_args['order'];
					}

					$sql[] = $wpdb->prepare(
						'ORDER BY ' . \esc_sql( $orderby ) . ' ' . \esc_sql( $order ) . ' LIMIT %d, %d',
						$offset,
						$number
					);
				} else {
					$sql[] = $wpdb->prepare( 'ORDER BY option_id DESC LIMIT %d, %d', $offset, $number );
				}
			}

			// Combine the SQL parts.
			$query = implode( ' ', $sql );

			// Prepare.
			$prepared = $wpdb->prepare( $query, $esc_name, $esc_time ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			// Query.
			$transients = empty( $parsed_args['count'] )
				? $wpdb->get_results( $prepared, \ARRAY_A ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				: $wpdb->get_var( $prepared, 0 );    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			if ( empty( $parsed_args['count'] ) ) {
				$normalized_data = array();
				foreach ( $transients as $transient ) {
					$normalized_data[] = array(
						'transient_name' => self::get_transient_name( $transient['option_name'] ),
						'value'          => self::get_transient_value( $transient['option_value'] ),
						'schedule'       => self::get_transient_expiration_time( $transient['option_name'] ),
						'id'             => $transient['option_id'],

					);
				}
				$transients = $normalized_data;
			}

			// Return transients.
			return $transients;
		}
	}
}
