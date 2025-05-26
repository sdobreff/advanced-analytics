<?php
/**
 * Class: WP Crons Helper class
 *
 * Helper class to manipulate WP crons.
 *
 * @package advanced-analytics
 *
 * @since 1.1.0
 */

declare(strict_types=1);

namespace ADVAN\Helpers;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\ADVAN\Helpers\Crons_Helper' ) ) {
	/**
	 * Responsible for proper context determination.
	 *
	 * @since 1.1.0
	 */
	class Crons_Helper {

		/**
		 * Hold all cron events collected and formatted for inner use.
		 *
		 * @var array
		 *
		 * @since 1.3.0
		 */
		private static $events = null;

		/**
		 * Schedules a new cron event
		 *
		 * @param string   $hook The action hook name.
		 * @param string   $recurrence 'hourly', 'twicedaily', 'daily', or custom interval.
		 * @param int|null $first_run Unix timestamp (null = current time).
		 * @param array    $args Arguments to pass to the hook.
		 *
		 * @since 1.3.0
		 */
		public static function schedule_event( $hook, $recurrence, $first_run = null, $args = array() ) {
			if ( ! \wp_next_scheduled( $hook, $args ) ) {
				$timestamp = $first_run ? $first_run : time();
				\wp_schedule_event( $timestamp, $recurrence, $hook, $args );
			}
		}

		/**
		 * Unschedules a cron event
		 *
		 * @param string $hook The action hook name.
		 * @param array  $args Arguments used when scheduling.
		 *
		 * @return bool|\WP_Error
		 *
		 * @since 1.3.0
		 */
		public static function unschedule_event( $hook, $args = array() ) {
			$timestamp = \wp_next_scheduled( $hook, $args );
			if ( $timestamp ) {
				return \wp_unschedule_event( $timestamp, $hook, $args );
			}

			return false;
		}

		/**
		 * Checks if a cron event is scheduled
		 *
		 * @param string $hook The action hook name.
		 * @param array  $args Arguments used when scheduling.
		 *
		 * @return bool
		 *
		 * @since 1.3.0
		 */
		public static function is_scheduled( $hook, $args = array() ) {
			return \wp_next_scheduled( $hook, $args ) !== false;
		}

		/**
		 * Immediately runs a cron event
		 *
		 * @param array $event - The action hook.
		 *
		 * @since 1.3.0
		 */
		public static function run_event( $event ) {
			\do_action_ref_array( $event['hook'], $event['args'] );
		}

		/**
		 * Deletes a cron event
		 *
		 * @param string $hash - The hash of the event to delete.
		 *
		 * @return bool|\WP_Error
		 *
		 * @since 1.3.0
		 */
		public static function delete_event( string $hash ) {
			$events = self::get_events();
			if ( isset( $events[ $hash ] ) ) {
				$event = $events[ $hash ];

				self::clear_inner_events();

				return self::unschedule_event( $event['hook'], $event['args'] );
			}

			return false;
		}

		/**
		 * Executes a cron event
		 *
		 * @param string $hash - The hash of the event to execute.
		 *
		 * @return bool|\WP_Error
		 *
		 * @since 1.3.0
		 */
		public static function execute_event( string $hash ) {
			$events = self::get_events();
			if ( isset( $events[ $hash ] ) ) {
				$event = $events[ $hash ];

				return self::run_event( $event );
			}

			return false;
		}

		/**
		 * Removes all cron events for a specific hook
		 *
		 * @param string $hook The action hook name.
		 *
		 * @since 1.3.0
		 */
		public static function clear_events( $hook ) {
			\wp_clear_scheduled_hook( $hook );
		}

		/**
		 * Collects all cron events and formats them for inner use.
		 *
		 * @return array
		 *
		 * @since 1.3.0
		 */
		public static function get_events() {
			if ( null === self::$events ) {

				$crons = _get_cron_array();

				if ( $crons && is_array( $crons ) ) {
					if ( null === self::$events ) {
						self::$events = array();
					}
					foreach ( $crons as $timestamp => $cron ) {
						if ( ! is_array( $cron ) ) {
							continue;
						}
						foreach ( $cron as $hook => $events ) {
							foreach ( $events as $event ) {

								$cron_item = array();

								$cron_item['hook']     = \esc_html( $hook );
								$cron_item['schedule'] = $timestamp;
								if ( isset( $event['schedule'] ) ) {
									$cron_item['recurrence'] = \esc_html( $event['schedule'] );
								}
								if ( isset( $event['args'] ) ) {
									$cron_item['args'] = $event['args'];
								}

								$cron_item['hash'] = substr( md5( $cron_item['hook'] . $cron_item['recurrence'] . $cron_item['schedule'] . \wp_json_encode( $event['args'] ) ), 0, 8 );
							}
							self::$events[ $cron_item['hash'] ] = $cron_item;
						}
					}
				}
			}

			return self::$events;
		}

		/**
		 * Clears the inner events cache.
		 *
		 * @since 1.3.0
		 */
		private static function clear_inner_events(): void {
			self::$events = null;
		}

		/**
		 * Determines whether an event is late.
		 *
		 * An event which has missed its schedule by more than 10 minutes is considered late.
		 *
		 * @param array $event The event.
		 *
		 * @return bool Whether the event is late.
		 *
		 * @since 1.4.0
		 */
		public static function is_late( array $event ) {
			if ( ! isset( $item['schedule'] ) && isset( $item['timestamp'] ) ) {
				$item['schedule'] = $item['timestamp'];
			}

			if ( ! isset( $item['schedule'] ) ) {
				return false;
			}

			$until = $event['schedule'] - time();

			return ( $until < ( 0 - ( 10 * MINUTE_IN_SECONDS ) ) );
		}
	}
}
