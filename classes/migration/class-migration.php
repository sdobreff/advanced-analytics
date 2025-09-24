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

use ADVAN\Entities\Common_Table;
use ADVAN\Helpers\Settings;
use ADVAN\Entities\WP_Mail_Entity;
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

		/**
		 * Migrates the plugin up-to version 1.9.6
		 *
		 * @return void
		 *
		 * @since 1.9.6
		 */
		public static function migrate_up_to_196() {

			$settings = Settings::get_current_options();
			if ( isset( $settings['severities']['user'] ) && isset( $settings['severities']['user']['color'] ) && '#0d4c24' === $settings['severities']['user']['color'] ) {
				$settings['severities']['user']['color'] = '#85b395';
			}
			if ( isset( $settings['severities']['info'] ) && isset( $settings['severities']['info']['color'] ) && '#0000ff' === $settings['severities']['info']['color'] ) {
				$settings['severities']['info']['color'] = '#aeaeec';
			}
			if ( isset( $settings['severities']['fatal'] ) && isset( $settings['severities']['fatal']['color'] ) && '#b92a2a' === $settings['severities']['fatal']['color'] ) {
				$settings['severities']['fatal']['color'] = '#f09595';
			}
			if ( isset( $settings['severities']['parse'] ) && isset( $settings['severities']['parse']['color'] ) && '#b9762a' === $settings['severities']['parse']['color'] ) {
				$settings['severities']['parse']['color'] = '#e3bb8d';
			}

			Settings::store_options( $settings );
			Settings::set_current_options( $settings );
		}

		/**
		 * Migrates the plugin up-to version 2.8.1
		 *
		 * @return void
		 *
		 * @since 2.8.1
		 */
		public static function migrate_up_to_281() {
			$settings = Settings::get_current_options();

			$defaults = Settings::get_default_options()['severities'];

			foreach ( $defaults as $name => $default ) {

				if ( ! isset( $settings['severities'][ $name ] ) ) {
					$settings['severities'][ $name ] = $default;
				}
			}

			Settings::store_options( $settings );
			Settings::set_current_options( $settings );
		}

		/**
		 * Migrates the plugin up-to version 3.0.1
		 *
		 * @return void
		 *
		 * @since 3.0.1
		 */
		public static function migrate_up_to_301() {
			if ( \class_exists( '\ADVAN\Entities\WP_Mail_Entity' ) ) {
				if ( Common_Table::check_table_exists( WP_Mail_Entity::get_table_name() ) && ! Common_Table::check_column( 'email_from', 'text', WP_Mail_Entity::get_table_name() ) ) {
					WP_Mail_Entity::alter_table_301();
				}
			}
		}

		/**
		 * Migrates the plugin up-to version 3.6.3
		 *
		 * @return void
		 *
		 * @since 3.6.3
		 */
		public static function migrate_up_to_363() {
			if ( \class_exists( '\ADVAN\Entities\WP_Mail_Entity' ) ) {
				if ( Common_Table::check_table_exists( WP_Mail_Entity::get_table_name() ) && ! Common_Table::check_column( 'blog_id', 'int', WP_Mail_Entity::get_table_name() ) ) {
					WP_Mail_Entity::alter_table_363();
				}
			}
		}
	}
}
