<?php
/**
 * Class: Responsible for Error Logs List views and operations.
 *
 * View logs, attach screens.
 *
 * @package advanced-analytics
 *
 * @since 2.8.2
 */

declare(strict_types=1);

namespace ADVAN\Lists\Views;

use ADVAN\Lists\Logs_List;
use ADVAN\Helpers\WP_Helper;
use ADVAN\Helpers\Plugin_Theme_Helper;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\ADVAN\Lists\Views\Logs_List_View' ) ) {
	/**
	 * Responsible for proper context determination.
	 *
	 * @since 2.8.2
	 */
	class Logs_List_View {

		/**
		 * Displays the settings page.
		 *
		 * @return void
		 *
		 * @since 1.1.0
		 */
		public static function render() {
			\add_thickbox();
			\wp_enqueue_script( 'wp-api-fetch' );
			?>
			<script>
				if( 'undefined' != typeof localStorage ){
					var skin = localStorage.getItem('aadvana-backend-skin');
					if( skin == 'dark' ){

						var element = document.getElementsByTagName("html")[0];
						element.classList.add("aadvana-darkskin");
					}
				}
			</script>
			<?php

			$plugin_filter = $_REQUEST['plugin_filter'] ?? ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			$plugin_filter = \sanitize_text_field( \wp_unslash( $plugin_filter ) );
			$events_list   = new Logs_List(
				array(
					'plugin_filter' => $plugin_filter,
				)
			);
			$events_list->prepare_items();
			?>
			<div class="wrap">
				<h1 class="wp-heading-inline"><?php \esc_html_e( 'Error logs', '0-day-analytics' ); ?></h1>
				<hr class="wp-header-end">
				<form id="error-logs-filter" method="get">
					<input type="hidden" name="page" value="<?php echo \esc_attr( Logs_List::MENU_SLUG ); ?>" />
					<input type="hidden" name="action" value="" />
					<?php
					$events_list->search_box(
						__( 'Search', '0-day-analytics' ),
						strtolower( $events_list::get_table_name() ) . '-find'
					);
					$events_list->display();
					?>
				</form>
			</div>
			<?php
		}

		/**
		 * Removes unnecessary arguments if present and reloads.
		 *
		 * @return void
		 *
		 * @since 2.8.2
		 */
		public static function page_load() {
			if ( ! empty( $_GET['single_severity_filter_top'] ) ) {
				WP_Helper::verify_admin_nonce( 'advan-plugin-data', 'advanced-analytics-security' );

				if ( isset( $_REQUEST['plugin_filter'] ) && ! empty( $_REQUEST['plugin_filter'] ) && -1 !== (int) $_REQUEST['plugin_filter'] ) {
					if ( ! \in_array( $_REQUEST['plugin_filter'], ( Plugin_Theme_Helper::get_plugins_bases() ) ) ) {
						\wp_redirect(
							\remove_query_arg( array( 'severity_filter', 'bulk_action', 'single_severity_filter_top', 'filter_action', 'plugin_filter' ), \wp_unslash( $_SERVER['REQUEST_URI'] ) )
						);
						exit;
					}
				}

				\wp_redirect(
					\remove_query_arg( array( 'severity_filter', 'bulk_action', 'single_severity_filter_top', 'filter_action' ), \wp_unslash( $_SERVER['REQUEST_URI'] ) )
				);
				exit;
			}
		}
	}
}
