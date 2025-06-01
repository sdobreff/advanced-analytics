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

use ADVAN\Lists\Crons_List;
use ADVAN\Helpers\Settings;

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
		 * Returns a cron event by its hash
		 *
		 * @param string $hash - The hash of the event to retrieve.
		 *
		 * @return array|bool
		 *
		 * @since latest
		 */
		public static function get_event( string $hash ) {
			$events = self::get_events();
			if ( isset( $events[ $hash ] ) ) {
				return $events[ $hash ];
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

		/**
		 * Shows select drop-down for cron schedules.
		 *
		 * @param boolean $current - Currently selected schedule.
		 *
		 * @return void
		 *
		 * @since latest
		 */
		public static function schedule_drop_down( $current = false ) {
			$schedules = \wp_get_schedules();
			uasort( $schedules, array( Crons_List::class, 'sort_schedules' ) );
			?>
			<select class="postform" name="cron_schedule" id="cron_schedule" required>
			<option <?php selected( $current, '_oneoff' ); ?> value="_oneoff"><?php esc_html_e( 'Non-repeating', 'wp-crontrol' ); ?></option>
				<?php foreach ( $schedules as $sched_name => $sched_data ) { ?>
				<option <?php selected( $current, $sched_name ); ?> value="<?php echo esc_attr( $sched_name ); ?>">
					<?php
					printf(
						'%s (%s)',
						esc_html( isset( $sched_data['display'] ) ? $sched_data['display'] : $sched_data['name'] ),
						esc_html( $sched_name )
					);
					?>
				</option>
			<?php } ?>
			</select>
			<?php
		}

		/**
		 * Updates a cron event by its hash.
		 *
		 * @param string $hash - The hash of the event to update.
		 *
		 * @return void|\WP_Error
		 *
		 * @since latest
		 */
		public static function update_cron( string $hash ) {
			$cron = self::get_event( $hash );
			if ( ! $cron ) {
				return false;
			}

			$deleted = self::delete_event( $hash );

			if ( ! $deleted || \is_wp_error( $deleted ) ) {

				\wp_safe_redirect(
					\remove_query_arg(
						array( 'deleted' ),
						add_query_arg(
							array(
								'page'                   => Settings::CRON_MENU_SLUG,
								Crons_List::SEARCH_INPUT => Crons_List::escaped_search_input(),
								'updated'                => false,
								'cron_name'              => rawurlencode( $cron['hook'] ),
							),
							\admin_url( 'admin.php' )
						)
					)
				);
				exit;
			}

			$current_time = time();

			$date = ( ( isset( $_REQUEST['cron_next_run_custom_date'] ) ) ? \sanitize_text_field( \wp_unslash( $_REQUEST['cron_next_run_custom_date'] ) ) : '' );

			$time = ( ( isset( $_REQUEST['cron_next_run_custom_time'] ) ) ? \sanitize_text_field( \wp_unslash( $_REQUEST['cron_next_run_custom_time'] ) ) : '' );

			$next_run_local = $date . ' ' . $time;

			$next_run_local = strtotime( $next_run_local, $current_time );

			if ( false === $next_run_local ) {
				return new \WP_Error(
					'invalid_timestamp',
					__( 'Invalid timestamp provided.', 'wp-crontrol' )
				);
			}

			$next_run_utc = (int) \get_gmt_from_date( \gmdate( 'Y-m-d H:i:s', $next_run_local ), 'U' );

			$schedule = ( isset( $_REQUEST['cron_schedule'] ) ? \sanitize_text_field( \wp_unslash( $_REQUEST['cron_schedule'] ) ) : '' );

			if ( '_oneoff' === $schedule ) {
				$schedule = '';
			} elseif ( ! isset( \wp_get_schedules()[ $schedule ] ) ) {
				return new \WP_Error(
					'invalid_schedule',
					__( 'Invalid schedule provided.', 'wp-crontrol' )
				);
			}

			$args = ( isset( $_REQUEST['cron_args'] ) ? \sanitize_textarea_field( \wp_unslash( $_REQUEST['cron_args'] ) ) : '' );

			$args = \json_decode( $args, true );

			if ( empty( $args ) || ! is_array( $args ) ) {
				$args = array();
			}

			if ( '_oneoff' === $schedule || '' === $schedule ) {
				$result = wp_schedule_single_event( $next_run_utc, $cron['hook'], $args, true );
			} else {
				$result = wp_schedule_event( $next_run_utc, $schedule, $cron['hook'], $args, true );
			}

			if ( \is_wp_error( $result ) ) {
				return new \WP_Error(
					'invalid_cron_parameters',
					__( 'Cron job can not be added.', 'wp-crontrol' )
				);
			}
		}
	}
}

