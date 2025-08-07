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
		 * Inits the class and its hooks.
		 *
		 * @return void
		 *
		 * @since 1.9.2
		 */
		public static function init() {
			\add_filter( 'cron_schedules', array( __CLASS__, 'recurring_schedules' ), PHP_INT_MAX );
			\add_filter( 'after_setup_theme', array( __CLASS__, 'initialize_hooks' ), 30000 );
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

						$time = strtotime( $parameters['next_run'] . $ve . \get_option( 'gmt_offset' ) . ' HOURS' );
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
