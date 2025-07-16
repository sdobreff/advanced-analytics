<?php
/**
 * Pointers class - showing the pointers where necessary.
 *
 * @package awesome-footnotes
 *
 * @since 1.7.5
 */

declare(strict_types=1);

namespace ADVAN\Controllers;

use ADVAN\Lists\Logs_List;
use ADVAN\Helpers\WP_Helper;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\ADVAN\Controllers\Pointers' ) ) {
	/**
	 * Responsible for showing the pointers.
	 *
	 * @since 1.7.5
	 */
	class Pointers {

		public const POINTER_ADMIN_MENU_NAME = 'aadvana-admin-menu';

		/**
		 * Inits the class and sets the hooks
		 *
		 * @return void
		 *
		 * @since 1.7.5
		 */
		public static function init() {

			if ( ! self::is_dismissed( self::POINTER_ADMIN_MENU_NAME ) ) {
				\add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );
			}
		}

		/**
		 * Adds the necessary scripts to the queue
		 *
		 * @return void
		 *
		 * @since 1.7.5
		 */
		public static function admin_enqueue_scripts() {
			// Using Pointers.
			\wp_enqueue_style( 'wp-pointer' );
			\wp_enqueue_script( 'wp-pointer' );

			// Register our action.
			\add_action( 'admin_print_footer_scripts', array( __CLASS__, 'print_footer_scripts' ) );
		}

		/**
		 * Prints out the JS needed to show the pointer.
		 *
		 * @return void
		 *
		 * @since 1.7.5
		 */
		public static function print_footer_scripts() {

			$suffix = '';

			if ( WP_Helper::is_multisite() ) {
				$suffix = '-network';
			}

			$element_id = Logs_List::PAGE_SLUG . $suffix;
			?>
			<script>
				jQuery(
					function() {
						var { __ } = wp.i18n;
						jQuery('#<?php echo \esc_attr( $element_id ); ?>').pointer( 
							{
								content:
									"<h3>" + __( 'WP Control', '0-day-analytics' ) + "<\/h3>" +
									"<h4>" + __( 'Here is the home for your', '0-day-analytics' ) + "<\/h4>" +
									"<p>" + __( ' - Error Logs', '0-day-analytics' ) + "</p>" +
									"<p>" + __( ' - Cron Jobs', '0-day-analytics' ) + "</p>" +
									"<p>" + __( ' - Transients', '0-day-analytics' ) + "</p>",


								position:
									{
										edge:  'left',
										align: 'left'
									},

								pointerClass:
									'wp-pointer arrow-top',

								pointerWidth: 420,
								
								close: function() {
									jQuery.post(
										ajaxurl,
										{
											pointer: '<?php echo \esc_attr( self::POINTER_ADMIN_MENU_NAME ); ?>',
											action: 'dismiss-wp-pointer',
										}
									);
								},

							}
						).pointer('open');
					}
				);
			</script>
			<?php
		}

		/**
		 * Checks if the user already dismissed the message
		 *
		 * @param string $pointer - Name of the pointer to check.
		 *
		 * @return boolean
		 *
		 * @since 1.7.5
		 */
		public static function is_dismissed( string $pointer ): bool {

			$dismissed = \array_filter( explode( ',', (string) \get_user_meta( \get_current_user_id(), 'dismissed_wp_pointers', true ) ) );

			return \in_array( $pointer, (array) $dismissed, true );
		}
	}
}
