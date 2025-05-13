<?php
/**
 * Class: WP Transients Helper class
 *
 * Helper class to manipulate WP crons.
 *
 * @package advanced-analytics
 *
 * @since 1.7.0
 */

declare(strict_types=1);

namespace ADVAN\Helpers;

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
			wp_clear_scheduled_hook( $hook );
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
	}
}
