<?php
/**
 * Class: Responsible for Table views and operations.
 *
 * Edit and add table, attach screens.
 *
 * @package advanced-analytics
 *
 * @since 1.9.8.1
 */

declare(strict_types=1);

namespace ADVAN\Lists\Views;

use ADVAN\Lists\Table_List;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\ADVAN\Lists\Views\Table_View' ) ) {
	/**
	 * Responsible for proper context determination.
	 *
	 * @since 1.9.8.1
	 */
	class Table_View {

		/**
		 * Displays the table page.
		 *
		 * @return void
		 *
		 * @since 1.7.0
		 */
		public static function analytics_table_page() {

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

				$table = new Table_List( 'options' );
				$table->prepare_items();
				?>
				<div class="wrap">
					<h1 class="wp-heading-inline"><?php \esc_html_e( 'Table', '0-day-analytics' ); ?></h1>
					
					<hr class="wp-header-end">
					<form id="table-filter" method="get">
					<?php

					$page  = ( isset( $_GET['page'] ) ) ? \sanitize_text_field( \wp_unslash( $_GET['page'] ) ) : 1;
					$paged = ( isset( $_GET['paged'] ) ) ? filter_input( INPUT_GET, 'paged', FILTER_SANITIZE_NUMBER_INT ) : 1;

					printf( '<input type="hidden" name="page" value="%s" />', \esc_attr( $page ) );
					printf( '<input type="hidden" name="paged" value="%d" />', \esc_attr( $paged ) );

					echo '<div style="clear:both; float:right">';
					$table->search_box(
						__( 'Search', '0-day-analytics' ),
						strtolower( $table->get_table_name() ) . '-find'
					);
					echo '</div>';
					$table->display();

					?>
					</form>
				</div>
				<?php
		}

		/**
		 * Options Help
		 *
		 * Return help text for options screen
		 *
		 * @return string  Help Text
		 *
		 * @since 1.9.8.1
		 */
		public static function add_help_content_transients() {

			$help_text  = '<p>' . __( 'This screen allows you to see all the transients on your WordPress site. These are only the ones that are Database based.', '0-day-analytics' ) . '</p>';
			$help_text .= '<p>' . __( 'You can specify how many transients to be shown, which columns to see or filter and search for given transient(s).', '0-day-analytics' ) . '</p>';
			$help_text .= '<p>' . __( 'You can delete or edit transients - keep in mind that you may end up editing transient that is no longer available (if the time passes).', '0-day-analytics' ) . '</p></h4>';
			$help_text .= '<p>' . __( 'Bulk operations are supported and you can even add new transient directly from here.', '0-day-analytics' ) . '</p></h4>';

			return $help_text;
		}
	}
}
