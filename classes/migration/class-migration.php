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
	}
}
