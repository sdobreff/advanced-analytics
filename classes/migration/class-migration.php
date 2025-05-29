<?php
/**
 * Responsible for plugin updates.
 *
 * @package    advanced-analytics
 * @subpackage migration
 * @copyright  %%YEAR%%
 * @license    https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */

declare(strict_types=1);

namespace ADVAN\Migration;

use ADVAN\Helpers\Settings;
use ADVAN\Migration\Abstract_Migration;


defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Migration class
 */
if ( ! class_exists( '\ADVAN\Migration\Migration' ) ) {

	/**
	 * Put all you migration methods here
	 *
	 * @since 1.0.1
	 */
	class Migration extends Abstract_Migration {

		/**
		 * Migrates the plugin up-to version 1.0.1
		 *
		 * @return void
		 *
		 * @since 1.0.1
		 */
		public static function migrate_up_to_101() {
			$settings = Settings::get_current_options();

			$defs = array();

			$defaults = Settings::get_default_options()['severities'];

			foreach ( $defaults as $name => $default ) {
				$defs[ $name ] = $default['name'];
			}

			if ( isset( $settings['severity_colors'] ) && isset( $settings['severity_show'] ) ) {
				$settings['severities'] = \array_merge_recursive(
					$settings['severity_colors'],
					$settings['severity_show'],
					$defs
				);

				unset( $settings['severity_colors'] );
				unset( $settings['severity_show'] );
			}

			if ( isset( $settings['severity_colors'] ) ) {
				$settings['severities'] = \array_merge_recursive( $settings['severity_colors'], $defs );
				unset( $settings['severity_colors'] );
			}

			if ( isset( $settings['severity_show'] ) ) {
				$settings['severities'] = \array_merge_recursive( $settings['severity_show'], $defs );
				unset( $settings['severity_show'] );
			}

			Settings::store_options( $settings );
			Settings::set_current_options( $settings );
		}

		/**
		 * Migrates the plugin up-to version 1.8.2
		 *
		 * @return void
		 *
		 * @since 1.8.2
		 */
		public static function migrate_up_to_182() {
			$settings = Settings::get_current_options();

			if ( ! isset( $settings['live_notifications_admin_bar'] ) ) {
				$settings['live_notifications_admin_bar'] = true;
			}

			Settings::store_options( $settings );
			Settings::set_current_options( $settings );
		}

		/**
		 * Migrates the plugin up-to version 1.8.4
		 *
		 * @return void
		 *
		 * @since 1.8.4
		 */
		public static function migrate_up_to_184() {
			$settings = Settings::get_current_options();

			if ( ! isset( $settings['environment_type_admin_bar'] ) ) {
				$settings['environment_type_admin_bar'] = true;
			}

			$settings['severities']['user'] = array(
				'name'    => __( 'User', '0-day-analytics' ),
				'color'   => '#0d4c24',
				'display' => true,
			);

			Settings::store_options( $settings );
			Settings::set_current_options( $settings );
		}
	}
}
