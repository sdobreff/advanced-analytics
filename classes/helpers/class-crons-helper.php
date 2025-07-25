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

		public const TRANSIENT_NAME = 'advana-cron-test-ok';

		public const WP_CORE_CRONS = array(
			'recovery_mode_clean_expired_keys',
			'wp_delete_temp_updater_backups',
			'wp_privacy_delete_old_export_files',
			'wp_update_user_counts',
			'wp_version_check',
			'wp_update_plugins',
			'wp_update_themes',
			'wp_scheduled_delete',
			'delete_expired_transients',
			'wp_scheduled_auto_draft_delete',
			'wp_site_health_scheduled_check',
		);

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
				$timestamp = $first_run ? (int) $first_run : time();
				\wp_schedule_event( $timestamp, $recurrence, $hook, $args );
			}
		}

		/**
		 * Unschedule a cron event
		 *
		 * @param string $hook The action hook name.
		 * @param array  $args Arguments used when scheduling.
		 *
		 * @return bool|\WP_Error
		 *
		 * @since 1.3.0
		 */
		public static function unschedule_event( $hook, $args = array() ) {

			if ( self::is_scheduled( $hook, $args ) ) {
				$timestamp = \wp_next_scheduled( $hook, $args );
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
		public static function is_scheduled( $hook, $args = array() ): bool {
			return \wp_next_scheduled( $hook, $args ) !== false;
		}

		/**
		 * Immediately runs a cron event
		 *
		 * @param array $event - The action hook.
		 *
		 * @return bool
		 *
		 * @since 1.3.0
		 */
		public static function run_event( $event ) {
			\do_action_ref_array( $event['hook'], $event['args'] );

			return true;
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

				// When collecting cron event data we are encoding the hook name, so we need to decode it here. For whatever reason there is no specific name convention for crons and some plugins use HTML entities in the hook names.
				// This is not a good practice, but we need to handle it.
				return self::unschedule_event( \html_entity_decode( $event['hook'] ), $event['args'] );
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
		 * @since 1.8.5
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
		 * @param array $item - The item array which will be used for checking the time.
		 *
		 * @return bool Whether the event is late.
		 *
		 * @since 1.4.0
		 */
		public static function is_late( array $item ) {
			if ( ! isset( $item['schedule'] ) && isset( $item['timestamp'] ) ) {
				$item['schedule'] = $item['timestamp'];
			}

			if ( ! isset( $item['schedule'] ) ) {
				return false;
			}

			$until = $item['schedule'] - time();

			return ( $until < ( 0 - ( 10 * MINUTE_IN_SECONDS ) ) );
		}

		/**
		 * Shows select drop-down for cron schedules.
		 *
		 * @param boolean $current - Currently selected schedule.
		 *
		 * @return void
		 *
		 * @since 1.8.5
		 */
		public static function schedule_drop_down( $current = false ) {
			$schedules = \wp_get_schedules();
			uasort( $schedules, array( Crons_List::class, 'sort_schedules' ) );
			?>
			<select class="postform" name="cron_schedule" id="cron_schedule" required>
			<option <?php selected( $current, '_oneoff' ); ?> value="_oneoff"><?php esc_html_e( 'Non-repeating', '0-day-analytics' ); ?></option>
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
		 * @since 1.8.5
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
						\add_query_arg(
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
					__( 'Invalid timestamp provided.', '0-day-analytics' )
				);
			}

			$next_run_utc = (int) \get_gmt_from_date( \gmdate( 'Y-m-d H:i:s', $next_run_local ), 'U' );

			$schedule = ( isset( $_REQUEST['cron_schedule'] ) ? \sanitize_text_field( \wp_unslash( $_REQUEST['cron_schedule'] ) ) : '' );

			if ( '_oneoff' === $schedule ) {
				$schedule = '';
			} elseif ( ! isset( \wp_get_schedules()[ $schedule ] ) ) {
				return new \WP_Error(
					'invalid_schedule',
					__( 'Invalid schedule provided.', '0-day-analytics' )
				);
			}

			$args = ( isset( $_REQUEST['cron_args'] ) ? \sanitize_textarea_field( \wp_unslash( $_REQUEST['cron_args'] ) ) : '' );

			$args = \json_decode( $args, true );

			if ( empty( $args ) || ! is_array( $args ) ) {
				$args = array();
			}

			$new_hook_name = ( isset( $_REQUEST['name'] ) ) ? $_REQUEST['name'] : '';
			if ( empty( $new_hook_name ) ) {
				$new_hook_name = $cron['hook'];
			}

			if ( '_oneoff' === $schedule || '' === $schedule ) {
				$result = \wp_schedule_single_event( $next_run_utc, $new_hook_name, $args, true );
			} else {
				$result = \wp_schedule_event( $next_run_utc, $schedule, $new_hook_name, $args, true );
			}
			if ( \is_wp_error( $result ) ) {
				return new \WP_Error(
					'invalid_cron_parameters',
					__( 'Cron job can not be added.', '0-day-analytics' )
				);
			}
		}

		/**
		 * Adds a cron event.
		 *
		 * @return void|\WP_Error
		 *
		 * @since 1.8.5
		 */
		public static function add_cron() {

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

			$next_run_utc = (int) \get_gmt_from_date( \gmdate( 'Y-m-d H:i:s', $next_run_local ), 'U' );

			$schedule = ( isset( $_REQUEST['cron_schedule'] ) ? \sanitize_text_field( \wp_unslash( $_REQUEST['cron_schedule'] ) ) : '' );

			if ( '_oneoff' === $schedule ) {
				$schedule = '';
			} elseif ( ! isset( \wp_get_schedules()[ $schedule ] ) ) {
				return new \WP_Error(
					'invalid_schedule',
					__( 'Invalid schedule provided.', '0-day-analytics' )
				);
			}

			$args = ( isset( $_REQUEST['cron_args'] ) ? \sanitize_textarea_field( \wp_unslash( $_REQUEST['cron_args'] ) ) : '' );

			$args = \json_decode( $args, true );

			if ( empty( $args ) || ! is_array( $args ) ) {
				$args = array();
			}

			$new_hook_name = ( isset( $_REQUEST['name'] ) ) ? $_REQUEST['name'] : '';
			if ( empty( $new_hook_name ) ) {
				$new_hook_name = 'unnamed_hook_' . uniqid();
			}

			if ( '_oneoff' === $schedule || '' === $schedule ) {
				$result = \wp_schedule_single_event( $next_run_utc, $new_hook_name, $args, true );
			} else {
				$result = \wp_schedule_event( $next_run_utc, $schedule, $new_hook_name, $args, true );
			}
			if ( \is_wp_error( $result ) ) {
				return new \WP_Error(
					'invalid_cron_parameters',
					__( 'Cron job can not be added.', '0-day-analytics' )
				);
			}
		}

		/**
		 * Adds a cron job to the stored ones.
		 *
		 * @param array $cron_job - Array with the cron job information. Every cron job information includes 'time', 'hook', 'args', if it is a recurring one - and 'next_run'.
		 *
		 * Example:
		 * 'hook_name'   => array(
		 *      'time'     => 'monthly',
		 *      'hook'     => array( __CLASS_TO_CALL__, 'method_to_call' ),
		 *      'args'     => array(),
		 *      'next_run' => '00:00 first day of next month',
		 *  )
		 * .
		 *
		 * @return void
		 *
		 * @throws \InvalidArgumentException When cron job information passed not contains required keys.
		 *
		 * @since 1.9.2
		 */
		public static function add_cron_from_array( array $cron_job ) {
			
		}

		/**
		 * Tests the proper spawning of the WP Cron
		 *
		 * @param boolean $cache - Flag - should only use the cached results from previous calls.
		 *
		 * @return bool|\WP_Error
		 *
		 * @since 1.8.5
		 */
		public static function test_cron_spawn( $cache = true ) {
			global $wp_version;

			$cron_runner_plugins = array(
				'\HM\Cavalcade\Plugin\Job'         => 'Cavalcade',
				'\Automattic\WP\Cron_Control\Main' => 'Cron Control',
			);

			foreach ( $cron_runner_plugins as $class => $plugin ) {
				if ( class_exists( $class ) ) {
					return new \WP_Error(
						'advana_cron_info',
						sprintf(
						/* translators: %s: The name of the plugin that controls the running of cron events. */
							__( 'WP-Cron spawning is being managed by the %s plugin.', '0-day-analytics' ),
							$plugin
						)
					);
				}
			}

			if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) {
				return WP_Helper::check_cron_status();
			}

			if ( defined( 'ALTERNATE_WP_CRON' ) && ALTERNATE_WP_CRON ) {
				return new \WP_Error(
					'advana_cron_info',
					sprintf(
					/* translators: %s: The name of the PHP constant that is set. */
						__( 'The %s constant is set to true.', '0-day-analytics' ),
						'ALTERNATE_WP_CRON'
					)
				);
			}

			$cached_status = \get_transient( self::TRANSIENT_NAME );

			if ( $cache && $cached_status ) {
				return true;
			}

			$sslverify     = version_compare( $wp_version, '4.0', '<' );
			$doing_wp_cron = sprintf( '%.22F', microtime( true ) );

			$cron_request = \apply_filters(
				'cron_request',
				array(
					'url'  => \add_query_arg( 'doing_wp_cron', $doing_wp_cron, site_url( 'wp-cron.php' ) ),
					'key'  => $doing_wp_cron,
					'args' => array(
						'timeout'   => 3,
						'blocking'  => true,
						'sslverify' => apply_filters( 'https_local_ssl_verify', $sslverify ),
					),
				),
				$doing_wp_cron
			);

			$cron_request['args']['blocking'] = true;

			$result = \wp_remote_post( $cron_request['url'], $cron_request['args'] );

			if ( \is_wp_error( $result ) ) {
				return $result;
			} elseif ( \wp_remote_retrieve_response_code( $result ) >= 300 ) {
				return new \WP_Error(
					'unexpected_http_response_code',
					sprintf(
					/* translators: %s: The HTTP response code. */
						__( 'Unexpected HTTP response code: %s', '0-day-analytics' ),
						intval( \wp_remote_retrieve_response_code( $result ) )
					)
				);
			} else {
				\set_transient( self::TRANSIENT_NAME, 1, 3600 );
				return true;
			}
		}

		/**
		 * Returns an array of the callback functions that are attached to the given hook name.
		 *
		 * @param string $name The hook name.
		 * @return array<int,array<string,mixed>> Array of callbacks attached to the hook.
		 * @phpstan-return array<int,array{
		 *   priority: int,
		 *   callback: array<string,mixed>,
		 * }>
		 *
		 * @since 1.4.0
		 */
		public static function get_cron_callbacks( $name ) {
			global $wp_filter;

			if ( $wp_filter && ! isset( $wp_filter[ $name ] ) ) {
				return array();
			}

			$actions = array();
			foreach ( $wp_filter[ $name ] as $priority => $callbacks ) {
				foreach ( $callbacks as $callback ) {
					$callback = self::populate_callback( $callback['function'] );

					if ( __NAMESPACE__ . '\\pauser()' === $callback['name'] ) {
						continue;
					}

					$actions[] = array(
						'priority' => $priority,
						'callback' => $callback,
					);
				}
			}

			return $actions;
		}

		/**
		 * Tries to extract as much information as possible from a callback.
		 *
		 * @param array|string $callback - The callback to populate.
		 *
		 * @return array
		 * @phpstan-return array{
		 *   name?: string,
		 *   file?: string|false,
		 *   line?: string|false,
		 *   error?: WP_Error,
		 *   component?: QM_Component,
		 * }
		 *
		 * @since 1.4.0
		 */
		private static function populate_callback( $callback ): array {
			$result = array();

			try {
				if ( is_array( $callback ) ) {
					if ( is_object( $callback[0] ) ) {
						$class  = get_class( $callback[0] );
						$access = '->';
					} else {
						$class  = $callback[0];
						$access = '::';
					}

					$result['name'] = WP_Helper::shorten_fqn( $class . $access . $callback[1] ) . '()';
					$ref            = new \ReflectionMethod( $class, $callback[1] );
				} elseif ( is_object( $callback ) && method_exists( $callback, '__invoke' ) ) {
					$class          = get_class( $callback );
					$result['name'] = WP_Helper::shorten_fqn( $class ) . '->__invoke()';
					$ref            = new \ReflectionMethod( $class, '__invoke' );
				} else {
					$function_name  = is_string( $callback ) ? $callback : spl_object_hash( $callback );
					$result['name'] = WP_Helper::shorten_fqn( $function_name ) . '()';
					try {
						$ref = new \ReflectionFunction( $callback );
						// Class as string ?
					} catch ( \ReflectionException $e ) {
						if ( PHP_VERSION_ID >= 80400 ) {
							$ref = \ReflectionMethod::createFromMethodName( $callback );
						} else {
							$ref = new \ReflectionMethod( $callback );
						}
					}
				}

				$result['file'] = $ref->getFileName();
				$result['line'] = $ref->getStartLine();

				$name = trim( $ref->getName() );

				if ( '__lambda_func' === $name || 0 === strpos( $name, 'lambda_' ) ) {
					if ( $result['file'] && preg_match( '|(?P<file>.*)\((?P<line>[0-9]+)\)|', $result['file'], $matches ) ) {
						$result['file'] = $matches['file'];
						$result['line'] = $matches['line'];
						$file           = trim( WP_Helper::standard_dir( $result['file'], '' ), '/' );
						$result['name'] = sprintf(
							// translators: %1$d is the line number, %2$s is the file name.
							__( 'Anonymous function on line %1$d of %2$s', '0-day-analytics' ),
							$result['line'],
							$file
						);
					} else {
						unset( $result['line'], $result['file'] );
						$result['name']  = $name . '()';
						$result['error'] = new \WP_Error( 'unknown_lambda', __( 'Unable to determine source of lambda function', '0-day-analytics' ) );
					}
				}

				if ( ! empty( $result['file'] ) ) {
					$result['component'] = WP_Helper::get_file_component( $result['file'] );
				} else {
					$result['component'] = array(
						'type'    => 'php',
						'name'    => 'PHP',
						'context' => '',
					);
				}
			} catch ( \ReflectionException $e ) {
				$result['error'] = new \WP_Error( 'reflection_exception', $e->getMessage() );
			}

			return $result;
		}
	}
}

