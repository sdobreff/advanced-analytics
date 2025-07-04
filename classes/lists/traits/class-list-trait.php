<?php
/**
 * Responsible for the plugin wizard ordering
 *
 * @package    advana
 * @subpackage traits
 * @copyright  %%YEAR%% Melapress
 * @license    https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 * @link       https://wordpress.org/plugins/wp-2fa/
 */

namespace ADVAN\Lists\Traits;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

if ( ! class_exists( '\ADVAN\Lists\List_Trait' ) ) {
	/**
	 * Responsible for the list logs show
	 *
	 * @since latest
	 */
	trait List_Trait {

		/**
		 * Holds the array with all of the column names and their representation in the table header.
		 *
		 * @var array
		 *
		 * @since 1.7.0
		 */
		private static $columns = array();

		/**
		 * Returns the search query string escaped
		 *
		 * @return string
		 *
		 * @since 1.1.0
		 */
		public static function escaped_search_input() {
			return isset( $_REQUEST[ static::SEARCH_INPUT ] ) ? \esc_sql( \sanitize_text_field( \wp_unslash( $_REQUEST[ static::SEARCH_INPUT ] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		/**
		 * Get a list of columns. The format is:
		 * 'internal-name' => 'Title'.
		 *
		 * @since 1.7.0
		 *
		 * @return array
		 */
		public function get_columns() {
			return static::$columns;
		}

		/**
		 * Stop execution and exit.
		 *
		 * @since 1.7.0
		 *
		 * @return void
		 */
		public function graceful_exit() {
			exit;
		}

		/**
		 * Form table per-page screen option value.
		 *
		 * @since 1.1.0
		 *
		 * @param bool   $keep   Whether to save or skip saving the screen option value. Default false.
		 * @param string $option The option name.
		 * @param int    $value  The number of rows to use.
		 *
		 * @return mixed
		 */
		public static function set_screen_option( $keep, $option, $value ) {

			if ( false !== \strpos( $option, static::SCREEN_OPTIONS_SLUG . '_' ) ) {
				return $value;
			}

			return $keep;
		}

		/**
		 * Returns the columns array (with column name).
		 *
		 * @return array
		 *
		 * @since 1.7.0
		 */
		private static function get_column_names() {
			return self::$columns;
		}
	}
}
