<?php
/**
 * Controller: Cron Jobs.
 *
 * @since 1.9.2
 *
 * @package   advan
 * @subpackage helpers
 */

declare(strict_types=1);

namespace ADVAN\Controllers;

use ADVAN\Helpers\Crons_Helper;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\ADVAN\Controllers\Cron_Jobs' ) ) {
	/**
	 * Provides cron jobs functionality for the plugin.
	 *
	 * @since 1.9.2
	 */
	class Cron_Jobs {

		public const CRON_JOBS_NAMES = array();

		/**
		 * The name of the option where plugin stores the cron jobs names (related to the plugin itself).
		 *
		 * @since 1.9.2
		 */
		public const CRON_JOBS_SETTINGS_NAME = 'cron_jobs_options';
		/**
		 * Inits the class and its hooks.
		 *
		 * @return void
		 *
		 * @since 1.9.2
		 */
		public static function init() {
			// Add custom schedules for WSAL early otherwise they won't work.
			\add_filter( 'cron_schedules', array( __CLASS__, 'recurring_schedules' ), PHP_INT_MAX );
			\add_filter( 'after_setup_theme', array( __CLASS__, 'initialize_hooks' ), 30000 );
		}

		/**
		 * Adds cron jobs stored in the globals settings (options table).
		 *
		 * @param array $crons - The list of cron jobs to add.
		 *
		 * @since 1.9.2
		 */
		public static function settings_hooks( array $crons ): array {
			// $available_cron_jobs = Settings_Helper::get_option_value( self::CRON_JOBS_SETTINGS_NAME, array() );

			// if ( ! empty( $available_cron_jobs ) ) {
			// $crons = array_merge( $crons, $available_cron_jobs );
			// }

			// return $crons;
		}

		/**
		 * Extend WP cron time intervals for scheduling.
		 *
		 * @param array $schedules - Array of schedules.
		 *
		 * @return array
		 *
		 * @since 1.9.2
		 */
		public static function recurring_schedules( $schedules ) {
			$schedules = array();

			return $schedules;
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
		public static function store_cron_option( array $cron_job ) {
			// if ( empty( $cron_job ) || 1 < count( $cron_job ) ) {
			// throw new \InvalidArgumentException( __( 'Only one cron at a time', 'wp-security-audit-log' ) );
			// }

			// $keys = array(
			// 'time',
			// 'hook',
			// 'args',
			// );
			// if ( count( $keys ) === count(
			// array_filter(
			// array_keys( \reset( $cron_job ) ),
			// function ( $key ) use ( $keys ) {
			// return in_array( $key, $keys, true );
			// }
			// )
			// ) ) {
			// $available_cron_jobs = Settings_Helper::get_option_value( self::CRON_JOBS_SETTINGS_NAME, array() );

			// $available_cron_jobs = array_merge( $available_cron_jobs, $cron_job );

			// Settings_Helper::set_option_value( self::CRON_JOBS_SETTINGS_NAME, $available_cron_jobs );
			// } else {
			// throw new \InvalidArgumentException( __( 'Invalid cron job format', 'wp-security-audit-log' ) );
			// }
		}

		/**
		 * Unset cron job from global settings.
		 *
		 * @param string $cron_name - The name of the cron job to remove.
		 *
		 * @return void
		 *
		 * @since 1.9.2
		 */
		public static function remove_cron_option( string $cron_name ) {
			// $available_cron_jobs = Settings_Helper::get_option_value( self::CRON_JOBS_SETTINGS_NAME, array() );

			// if ( isset( $available_cron_jobs[ $cron_name ] ) ) {
			// Crons_Helper::unschedule_event( $cron_name, $available_cron_jobs[ $cron_name ]['args'] );

			// unset( $available_cron_jobs[ $cron_name ] );

			// Settings_Helper::set_option_value( self::CRON_JOBS_SETTINGS_NAME, $available_cron_jobs );
			// }
		}

		/**
		 * Initializes the plugin cron jobs.
		 *
		 * @return void
		 *
		 * @since 1.9.2
		 */
		public static function initialize_hooks() {
			$hooks_array = self::CRON_JOBS_NAMES;

			/**
			 * Gives an option to add hooks which must be enabled.
			 *
			 * @var array - The current hooks.
			 *
			 * @since 1.9.2
			 */
			$hooks_array = \apply_filters( 'advan_cron_hooks', $hooks_array );

			foreach ( $hooks_array as $name => $parameters ) {
				if ( ! Crons_Helper::is_scheduled( $name, ( isset( $parameters['args'] ) ) ? $parameters['args'] : array() ) ) {
					$time = time();

					if ( isset( $parameters['next_run'] ) ) {
						$ve = \get_option( 'gmt_offset' ) > 0 ? ' -' : ' +';

						$time = strtotime( $parameters['next_run'] . $ve . get_option( 'gmt_offset' ) . ' HOURS' );
					}

					Crons_Helper::schedule_event(
						$name,
						$time,
						( isset( $parameters['time'] ) ) ? $parameters['time'] : null,
						( isset( $parameters['args'] ) ) ? $parameters['args'] : array()
					);
				}

				\add_action( $name, $parameters['hook'] );
			}
		}
	}
}
