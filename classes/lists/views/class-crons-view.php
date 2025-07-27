<?php
/**
 * Class: Responsible for Cron views and operations.
 *
 * Edit and add crons, attach screens.
 *
 * @package advanced-analytics
 *
 * @since 1.9.8.1
 */

declare(strict_types=1);

namespace ADVAN\Lists\Views;

use ADVAN\Lists\Crons_List;
use ADVAN\Helpers\WP_Helper;
use ADVAN\Helpers\Crons_Helper;
use ADVAN\Helpers\Settings;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\ADVAN\Lists\Views\Crons_View' ) ) {
	/**
	 * Responsible for proper context determination.
	 *
	 * @since 1.9.8.1
	 */
	class Crons_View {
				/**
				 * Options Help
				 *
				 * Return help text for options screen
				 *
				 * @return string  Help Text
				 *
				 * @since 1.9.8.1
				 */
		public static function add_help_content_crons() {

			$help_text  = '<p>' . __( 'This screen allows you to see all the crons on your WordPress site.', '0-day-analytics' ) . '</p>';
			$help_text .= '<p>' . __( 'You set which columns to see or filter and search for given cron(s).', '0-day-analytics' ) . '</p>';
			$help_text .= '<p>' . __( 'You can delete, run or edit crons - keep in mind that you may end up editing cron that is no longer available (if the time passes).', '0-day-analytics' ) . '</p>';
			$help_text .= '<p>' . __( 'Bulk operations are supported and you can even add new cron directly from here.', '0-day-analytics' ) . '</p>';

			return $help_text;
		}

		/**
		 * Displays the cron page.
		 *
		 * @return void
		 *
		 * @since 1.1.0
		 */
		public static function analytics_cron_page() {
			\add_thickbox();
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

			$action = ! empty( $_REQUEST['action'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			? sanitize_key( $_REQUEST['action'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			: '';

			if ( ! empty( $action ) && ( 'edit_cron' === $action ) && WP_Helper::verify_admin_nonce( 'bulk-custom-delete' )
						) {
				$cron_hash = ! empty( $_REQUEST['hash'] )
				? \sanitize_text_field( \wp_unslash( $_REQUEST['hash'] ) )
				: false;
				if ( ! $cron_hash ) {
					\wp_die( \esc_html__( 'Invalid cron hash.', '0-day-analytics' ) );
				}
				$cron = Crons_Helper::get_event( $cron_hash );

				if ( $cron ) {
					$next_run_gmt        = gmdate( 'Y-m-d H:i:s', $cron['schedule'] );
					$next_run_date_local = get_date_from_gmt( $next_run_gmt, 'Y-m-d' );
					$next_run_time_local = get_date_from_gmt( $next_run_gmt, 'H:i:s' );
				} else {
					$suggestion          = strtotime( '+1 hour' );
					$next_run_date_local = get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $suggestion ), 'Y-m-d' );
					$next_run_time_local = get_date_from_gmt( gmdate( 'Y-m-d H:\0\0:\0\0', $suggestion ), 'H:i:s' );
				}

				?>
				<div class="wrap">
					<h1 class="wp-heading-inline"><?php \esc_html_e( 'Edit Cron', '0-day-analytics' ); ?></h1>
					<hr class="wp-header-end">

					<?php

					if ( false === $cron ) {
						?>
						<div id="advaa-status-notice" class="notice notice-info">
							<p><?php esc_html_e( 'Cron job does not exists or it has been executed', '0-day-analytics' ); ?></p>
						</div>
						<?php
					} else {

						$arguments = \wp_json_encode( (array) $cron['args'] );

						?>
	
						<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="hash" value="<?php echo esc_attr( $cron_hash ); ?>" />
							<input type="hidden" name="<?php echo \esc_attr( Crons_List::SEARCH_INPUT ); ?>" value="<?php echo esc_attr( Crons_List::escaped_search_input() ); ?>" />
							<input type="hidden" name="action" value="<?php echo \esc_attr( Crons_List::UPDATE_ACTION ); ?>" />
							<?php \wp_nonce_field( Crons_List::NONCE_NAME ); ?>

							<table class="form-table">
								<tbody>
									<tr>
										<th><?php esc_html_e( 'Hook', '0-day-analytics' ); ?></th>
										<td><input type="text" class="large-text code" name="name" value="<?php echo esc_attr( $cron['hook'] ); ?>" /></td>
									</tr>
									<tr>
										<th><?php esc_html_e( 'Next Run', '0-day-analytics' ); ?></th>
										<td>
											<?php
											printf(
												'<input type="date" autocorrect="off" autocapitalize="off" spellcheck="false" name="cron_next_run_custom_date" id="cron_next_run_custom_date" value="%1$s" placeholder="yyyy-mm-dd" pattern="\d{4}-\d{2}-\d{2}" />
												<input type="time" autocorrect="off" autocapitalize="off" spellcheck="false" name="cron_next_run_custom_time" id="cron_next_run_custom_time" value="%2$s" step="1" placeholder="hh:mm:ss" pattern="\d{2}:\d{2}:\d{2}" />',
												esc_attr( $next_run_date_local ),
												esc_attr( $next_run_time_local )
											);
											?>
										</td>
									</tr>
									<tr>
										<th><?php esc_html_e( 'Arguments', '0-day-analytics' ); ?></th>
										<td>
											<textarea class="large-text code" name="cron_args" id="transient-editor" style="height: 302px; padding-left: 35px; max-witdh:100%;"><?php echo esc_textarea( $arguments ); ?></textarea>
											<?php
											printf(
											/* translators: 1, 2, and 3: Example values for an input field. */
												esc_html__( 'Use a JSON encoded array, e.g. %1$s, %2$s, or %3$s', 'wp-crontrol' ),
												'<code>[25]</code>',
												'<code>["asdf"]</code>',
												'<code>["i","want",25,"cakes"]</code>'
											);
											?>
										</td>
									</tr>
									<tr>
										<th><?php esc_html_e( 'Schedule', '0-day-analytics' ); ?></th>
										<td><?php Crons_Helper::schedule_drop_down( $cron['recurrence'] ); ?></td>
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
			} elseif ( ! empty( $action ) && ( 'new_cron' === $action ) && WP_Helper::verify_admin_nonce( 'bulk-custom-delete' )
			) {
				$next_run_gmt        = gmdate( 'Y-m-d H:i:s', time() );
				$next_run_date_local = get_date_from_gmt( $next_run_gmt, 'Y-m-d' );
				$next_run_time_local = get_date_from_gmt( $next_run_gmt, 'H:i:s' );
				?>
				<div class="wrap">
					<h1 class="wp-heading-inline"><?php \esc_html_e( 'New Cron', '0-day-analytics' ); ?></h1>
					<hr class="wp-header-end">

					<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="<?php echo \esc_attr( Crons_List::SEARCH_INPUT ); ?>" value="<?php echo esc_attr( Crons_List::escaped_search_input() ); ?>" />
						<input type="hidden" name="action" value="<?php echo \esc_attr( Crons_List::NEW_ACTION ); ?>" />
						<?php \wp_nonce_field( Crons_List::NONCE_NAME ); ?>

						<table class="form-table">
							<tbody>
								<tr>
									<th><?php esc_html_e( 'Hook', '0-day-analytics' ); ?></th>
									<td><input type="text" class="large-text code" name="name" value="" /></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Next Run', '0-day-analytics' ); ?></th>
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
									<th><?php esc_html_e( 'Arguments', '0-day-analytics' ); ?></th>
									<td>
										<textarea class="large-text code" name="value" id="transient-editor" style="height: 302px; padding-left: 35px; max-witdh:100%;"></textarea>
										<?php
										printf(
										/* translators: 1, 2, and 3: Example values for an input field. */
											esc_html__( 'Use a JSON encoded array, e.g. %1$s, %2$s, or %3$s', 'wp-crontrol' ),
											'<code>[25]</code>',
											'<code>["asdf"]</code>',
											'<code>["i","want",25,"cakes"]</code>'
										);
										?>
									</td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Schedule', '0-day-analytics' ); ?></th>
									<td><?php Crons_Helper::schedule_drop_down(); ?></td>
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
				$events_list = new Crons_List( array() );
				$events_list->prepare_items();
				?>
				<div class="wrap">
					<h1 class="wp-heading-inline"><?php \esc_html_e( 'Cron Jobs', '0-day-analytics' ); ?></h1>
					<?php echo '<a href="' . esc_url( admin_url( 'admin.php?page=' . Settings::CRON_MENU_SLUG . '&action=new_cron&_wpnonce=' . \wp_create_nonce( 'bulk-custom-delete' ) ) ) . '" class="page-title-action">' . \esc_html__( 'Add New Cron', '0-day-analytics' ) . '</a>'; ?>
					<form id="crons-filter" method="get">
					<?php

					$status = Crons_Helper::test_cron_spawn();

					if ( \is_wp_error( $status ) ) {
						if ( 'advana_cron_info' === $status->get_error_code() ) {
							?>
							<div id="advaa-status-notice" class="notice notice-info">
								<p><?php echo $status->get_error_message();  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
							</div>
							<?php
						} else {
							?>
							<div id="advana-status-error" class="notice notice-error">
								<?php
								printf(
									'<p>%1$s</p>',
									sprintf(
										/* translators: %s: Error message text. */
										esc_html__( 'There was a problem spawning a call to the WP-Cron system on your site. This means WP-Cron events on your site may not work. The problem was: %s', '0-day-analytics' ),
										'</p><p><strong>' . esc_html( $status->get_error_message() ) . '</strong>'
									)
								);
								?>
							</div>
							<?php
						}
					}

					$page  = ( isset( $_GET['page'] ) ) ? \sanitize_text_field( \wp_unslash( $_GET['page'] ) ) : 1;
					$paged = ( isset( $_GET['paged'] ) ) ? filter_input( INPUT_GET, 'paged', FILTER_SANITIZE_NUMBER_INT ) : 1;

					printf( '<input type="hidden" name="page" value="%s" />', \esc_attr( $page ) );
					printf( '<input type="hidden" name="paged" value="%d" />', \esc_attr( $paged ) );

					echo '<div style="clear:both; float:right">';
					$events_list->search_box(
						__( 'Search', '0-day-analytics' ),
						strtolower( $events_list::get_table_name() ) . '-find'
					);
					echo '</div>';

					/*
					$status = WP_Helper::check_cron_status();

				if ( \is_wp_error( $status ) ) {
					if ( 'cron_info' === $status->get_error_code() ) {
						?>
							<div id="cron-status-notice" class="notice notice-info">
								<p> <?php echo $status->get_error_message();  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
							</div>
							<?php
					}
				}
				*/
				$events_list->display();

				?>
					</form>
				</div>
				<?php
			}
		}

		/**
		 * Collects all the data from the form and updates the transient.
		 *
		 * @return void
		 *
		 * @since 1.8.5
		 */
		public static function update_cron() {

			// Bail if malformed Transient request.
			if ( empty( $_REQUEST['hash'] ) ) {
				return;
			}

			// Bail if nonce fails.
			if ( empty( $_REQUEST['_wpnonce'] ) || ! WP_Helper::verify_admin_nonce( Crons_List::NONCE_NAME ) ) {
				return;
			}

			// Sanitize transient.
			$cron_hash = \sanitize_key( $_REQUEST['hash'] );

			Crons_Helper::update_cron( $cron_hash );

			\wp_safe_redirect(
				\remove_query_arg(
					array( 'deleted' ),
					add_query_arg(
						array(
							'page'                   => Settings::CRON_MENU_SLUG,
							Crons_List::SEARCH_INPUT => Crons_List::escaped_search_input(),
							'updated'                => true,
						),
						\admin_url( 'admin.php' )
					)
				)
			);
			exit;
		}

		/**
		 * Collects all the data from the form and adds the transient.
		 *
		 * @return void
		 *
		 * @since 1.9.8.1
		 */
		public static function new_cron() {

			// Bail if nonce fails.
			if ( empty( $_REQUEST['_wpnonce'] ) || ! WP_Helper::verify_admin_nonce( Crons_List::NONCE_NAME ) ) {
				return;
			}

			Crons_Helper::add_cron();

			\wp_safe_redirect(
				\remove_query_arg(
					array( 'deleted' ),
					add_query_arg(
						array(
							'page'                   => Settings::CRON_MENU_SLUG,
							Crons_List::SEARCH_INPUT => Crons_List::escaped_search_input(),
							'updated'                => true,
						),
						\admin_url( 'admin.php' )
					)
				)
			);
			exit;
		}
	}
}
