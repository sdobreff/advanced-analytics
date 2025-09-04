<?php
/**
 * Class: Responsible for Transients views and operations.
 *
 * Edit and add transients, attach screens.
 *
 * @package advanced-analytics
 *
 * @since 1.9.8.1
 */

declare(strict_types=1);

namespace ADVAN\Lists\Views;

use ADVAN\Helpers\WP_Helper;
use ADVAN\Lists\Transients_List;
use ADVAN\Helpers\Transients_Helper;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\ADVAN\Lists\Views\Transients_View' ) ) {
	/**
	 * Responsible for proper context determination.
	 *
	 * @since 1.9.8.1
	 */
	class Transients_View {

		/**
		 * Displays the transients page.
		 *
		 * @return void
		 *
		 * @since 1.7.0
		 */
		public static function analytics_transients_page() {
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

			$action = ! empty( $_REQUEST['action'] )
			? sanitize_key( $_REQUEST['action'] )
			: '';

			if ( ! empty( $action ) && ( 'edit_transient' === $action ) && WP_Helper::verify_admin_nonce( 'bulk-custom-delete' )
			) {
				$transient_id = ! empty( $_REQUEST['trans_id'] )
				? absint( $_REQUEST['trans_id'] )
				: 0;
				$transient    = Transients_Helper::get_transient_by_id( $transient_id );

				if ( null !== $transient ) {

					$name       = Transients_Helper::get_transient_name( $transient['option_name'] );
					$expiration = Transients_Helper::get_transient_expiration_time( $transient['option_name'] );

					if ( 0 !== $expiration ) {

						$next_run_gmt        = gmdate( 'Y-m-d H:i:s', $expiration );
						$next_run_date_local = get_date_from_gmt( $next_run_gmt, 'Y-m-d' );
						$next_run_time_local = get_date_from_gmt( $next_run_gmt, 'H:i:s' );
					}
				}
				?>
				<div class="wrap">
					<h1 class="wp-heading-inline"><?php \esc_html_e( 'Edit Transient', '0-day-analytics' ); ?></h1>
					<hr class="wp-header-end">

					<?php

					if ( null === $transient ) {
						?>
						<div id="advaa-status-notice" class="notice notice-info">
							<p><?php esc_html_e( 'Transient does not exists or it has been expired / removed', '0-day-analytics' ); ?></p>
						</div>
						<?php
					} else {
						?>

					<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="transient" value="<?php echo esc_attr( $name ); ?>" />
						<input type="hidden" name="<?php echo \esc_attr( Transients_List::SEARCH_INPUT ); ?>" value="<?php echo esc_attr( Transients_List::escaped_search_input() ); ?>" />
						<input type="hidden" name="action" value="<?php echo \esc_attr( Transients_List::UPDATE_ACTION ); ?>" />
						<?php \wp_nonce_field( Transients_List::NONCE_NAME ); ?>

						<?php
						if ( in_array( $name, Transients_Helper::WP_CORE_TRANSIENTS ) ) {
							?>
							<div id="advaa-status-notice" class="notice notice-warning">
								<p><?php esc_html_e( 'This is a WP core transient, even if you update it, the new value will be overridden by the core!', '0-day-analytics' ); ?></p>
							</div>
							<?php
						} else {
							foreach ( Transients_Helper::WP_CORE_TRANSIENTS as $trans_name ) {
								if ( \str_starts_with( $name, $trans_name ) ) {
									?>
									<div id="advaa-status-notice" class="notice notice-warning">
										<p><?php esc_html_e( 'This is a WP core transient, even if you update it, the new value will be overridden by the core!', '0-day-analytics' ); ?></p>
									</div>
									<?php
									break;
								}
							}
						}
						?>

						<table class="form-table">
							<tbody>
								<tr>
									<th><?php esc_html_e( 'Option ID', '0-day-analytics' ); ?></th>
									<td><input type="text" disabled class="large-text code" name="name" value="<?php echo esc_attr( $transient['option_id'] ); ?>" /></td>
								</tr>
								<tr>
									<th><?php \esc_html_e( 'Name', '0-day-analytics' ); ?></th>
									<td><input type="text" class="large-text code" name="name" value="<?php echo \esc_attr( Transients_Helper::clear_transient_name( $transient['option_name'] ) ); ?>" /></td>
								</tr>
								<?php
								if ( 0 !== $expiration ) {
									?>
								<tr>
									<th><?php \esc_html_e( 'Expiration', '0-day-analytics' ); ?></th>
									<td>
									<?php
										printf(
											'<input type="date" autocorrect="off" autocapitalize="off" spellcheck="false" name="cron_next_run_custom_date" id="cron_next_run_custom_date" value="%1$s" placeholder="yyyy-mm-dd" pattern="\d{4}-\d{2}-\d{2}" />
											<input type="time" autocorrect="off" autocapitalize="off" spellcheck="false" name="cron_next_run_custom_time" id="cron_next_run_custom_time" value="%2$s" step="1" placeholder="hh:mm:ss" pattern="\d{2}:\d{2}:\d{2}" />',
											\esc_attr( $next_run_date_local ),
											\esc_attr( $next_run_time_local )
										);
									?>
									</td>
								</tr>
									<?php
								} else {

										printf(
											'<input type="hidden" autocorrect="off" autocapitalize="off" spellcheck="false" name="cron_next_run_custom_date" id="cron_next_run_custom_date" value="%1$s"" />
											<input type="hidden" autocorrect="off" autocapitalize="off" spellcheck="false" name="cron_next_run_custom_time" id="cron_next_run_custom_time" value="%2$s"  />',
											'',
											''
										);
								}

								?>
								<tr>
									<th><?php esc_html_e( 'Value', '0-day-analytics' ); ?></th>
									<td>
										<textarea class="large-text code" name="value" id="transient-editor" style="height: 302px; padding-left: 35px; max-witdh:100%;"><?php echo \esc_textarea( $transient['option_value'] ); ?></textarea>
										<?php
										printf(
										/* translators: 1, 2, and 3: Example values for an input field. */
											esc_html__( 'Because of the nature of the transients, if you want to use data structures here they must be in serialized format, e.g. %1$s, %2$s, or %3$s', 'wp-crontrol' ),
											'<code>O:8:"stdClass":100:{s:11:"commerce";...</code>',
											'<code>a:2:{s:7:"version";s:3:"1.2";...</code>',
											'<code>a:0:{}</code>'
										);
										?>
									</td>
								</tr>
							</tbody>
						</table>

						<p class="submit">
							<?php \submit_button( '', 'primary', '', false ); ?>
						</p>
					</form>
						<?php
					}
					?>
				</div>
				<?php
			} elseif ( ! empty( $action ) && ( 'new_transient' === $action ) && WP_Helper::verify_admin_nonce( 'bulk-custom-delete' )
			) {
				$next_run_gmt        = gmdate( 'Y-m-d H:i:s', time() );
				$next_run_date_local = get_date_from_gmt( $next_run_gmt, 'Y-m-d' );
				$next_run_time_local = get_date_from_gmt( $next_run_gmt, 'H:i:s' );
				?>
				<div class="wrap">
					<h1 class="wp-heading-inline"><?php \esc_html_e( 'New Transient', '0-day-analytics' ); ?></h1>
					<hr class="wp-header-end">

					<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="<?php echo \esc_attr( Transients_List::SEARCH_INPUT ); ?>" value="<?php echo esc_attr( Transients_List::escaped_search_input() ); ?>" />
						<input type="hidden" name="action" value="<?php echo \esc_attr( Transients_List::NEW_ACTION ); ?>" />
						<?php \wp_nonce_field( Transients_List::NONCE_NAME ); ?>

						<table class="form-table">
							<tbody>
								<tr>
									<th><?php esc_html_e( 'Name', '0-day-analytics' ); ?></th>
									<td><input type="text" class="large-text code" name="name" value="" /></td>
								</tr>
								<?php
								if ( WP_Helper::is_multisite() ) {
									?>
									<tr>
										<th><?php esc_html_e( 'Side Wide', '0-day-analytics' ); ?></th>
										<td><input type="checkbox" name="side-wide" value="1" /></td>
									</tr>
									<?php
								}
								?>
								<tr>
									<th><?php esc_html_e( 'Expiration', '0-day-analytics' ); ?></th>
									<td>
									<?php
										printf(
											'<input type="date" autocorrect="off" autocapitalize="off" spellcheck="false" name="cron_next_run_custom_date" id="cron_next_run_custom_date" value="%1$s" placeholder="yyyy-mm-dd" pattern="\d{4}-\d{2}-\d{2}" />
											<input type="time" autocorrect="off" autocapitalize="off" spellcheck="false" name="cron_next_run_custom_time" id="cron_next_run_custom_time" value="%2$s" step="1" placeholder="hh:mm:ss" pattern="\d{2}:\d{2}:\d{2}" />',
											\esc_attr( $next_run_date_local ),
											\esc_attr( $next_run_time_local )
										);
									?>
									</td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Value', '0-day-analytics' ); ?></th>
									<td>
										<textarea class="large-text code" name="value" id="transient-editor" style="height: 302px; padding-left: 35px; max-witdh:100%;"></textarea>
									</td>
								</tr>
							</tbody>
						</table>

						<p class="submit">
							<?php \submit_button( '', 'primary', '', false ); ?>
						</p>
					</form>
				</div>
				<?php
			} else {
				$transients = new Transients_List( array() );
				$transients->prepare_items();
				?>
				<div class="wrap">
					<h1 class="wp-heading-inline"><?php \esc_html_e( 'Transients', '0-day-analytics' ); ?></h1>
					<?php echo '<a href="' . \esc_url( \admin_url( 'admin.php?page=' . Transients_List::TRANSIENTS_MENU_SLUG . '&action=new_transient&_wpnonce=' . \wp_create_nonce( 'bulk-custom-delete' ) ) ) . '" class="page-title-action">' . \esc_html__( 'Add New Transient', '0-day-analytics' ) . '</a>'; ?>
					
					<hr class="wp-header-end">

					<h2 class='screen-reader-text'><?php \esc_html_e( 'Filter transients list', '0-day-analytics' ); ?></h2>
					<?php $transients->views(); ?>

					<form id="transients-filter" method="get">
					<?php

					$page  = ( isset( $_GET['page'] ) ) ? \sanitize_text_field( \wp_unslash( $_GET['page'] ) ) : 1;
					$paged = ( isset( $_GET['paged'] ) ) ? filter_input( INPUT_GET, 'paged', FILTER_SANITIZE_NUMBER_INT ) : 1;

					printf( '<input type="hidden" name="page" value="%s" />', \esc_attr( $page ) );
					printf( '<input type="hidden" name="paged" value="%d" />', \esc_attr( $paged ) );

					$transients->search_box(
						__( 'Search', '0-day-analytics' ),
						strtolower( $transients::get_table_name() ) . '-find'
					);

					$transients->display();

					?>
					</form>
				</div>
				<?php
			}
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
			$help_text .= '<p>' . __( 'You can delete or edit transients - keep in mind that you may end up editing transient that is no longer available (if the time passes).', '0-day-analytics' ) . '</p>';
			$help_text .= '<p>' . __( 'Bulk operations are supported and you can even add new transient directly from here.', '0-day-analytics' ) . '</p>';

			return $help_text;
		}

		/**
		 * Collects all the data from the form and updates the transient.
		 *
		 * @return void
		 *
		 * @since 1.8.5
		 */
		public static function update_transient() {

			// Bail if malformed Transient request.
			if ( empty( $_REQUEST['transient'] ) ) {
				return;
			}

			// Bail if nonce fails.
			if ( empty( $_REQUEST['_wpnonce'] ) || ! WP_Helper::verify_admin_nonce( Transients_List::NONCE_NAME ) ) {
				return;
			}

			// Sanitize transient.
			$transient = \sanitize_key( $_REQUEST['transient'] );

			// Site wide.
			$site_wide = ! empty( $_REQUEST['name'] ) && Transients_Helper::is_site_wide( \sanitize_text_field( \wp_unslash( $_REQUEST['name'] ) ) );

			Transients_Helper::update_transient( $transient, $site_wide );

			\wp_safe_redirect(
				\remove_query_arg(
					array( 'deleted' ),
					add_query_arg(
						array(
							'page'                        => Transients_List::TRANSIENTS_MENU_SLUG,
							Transients_List::SEARCH_INPUT => Transients_List::escaped_search_input(),
							'updated'                     => true,
						),
						\admin_url( 'admin.php' )
					)
				)
			);
			exit;
		}

		/**
		 * Collects all the data from the form and creates new transient.
		 *
		 * @return void
		 *
		 * @since 1.9.2
		 */
		public static function new_transient() {

			// Bail if nonce fails.
			if ( empty( $_REQUEST['_wpnonce'] ) || ! WP_Helper::verify_admin_nonce( Transients_List::NONCE_NAME ) ) {
				return;
			}

			// Sanitize transient.
			$transient = ( isset( $_REQUEST['name'] ) ) ? \sanitize_key( $_REQUEST['name'] ) : null;

			// Site wide.
			$site_wide = ! empty( $_REQUEST['side-wide'] ) ? filter_var( \wp_unslash( $_REQUEST['side-wide'] ), FILTER_VALIDATE_BOOLEAN ) : false;

			Transients_Helper::create_transient( $transient, $site_wide );

			\wp_safe_redirect(
				\remove_query_arg(
					array( 'deleted' ),
					\add_query_arg(
						array(
							'page'                        => Transients_List::TRANSIENTS_MENU_SLUG,
							Transients_List::SEARCH_INPUT => Transients_List::escaped_search_input(),
							'updated'                     => true,
						),
						\admin_url( 'admin.php' )
					)
				)
			);
			exit;
		}

		/**
		 * Removes unnecessary arguments if present and reloads.
		 *
		 * @return void
		 *
		 * @since 2.3.0
		 */
		public static function page_load() {
			if ( ! empty( $_GET['_wp_http_referer'] ) ) {
				\wp_redirect(
					\remove_query_arg( array( '_wp_http_referer', 'bulk_action' ), \wp_unslash( $_SERVER['REQUEST_URI'] ) )
				);
				exit;
			}
		}
	}
}
