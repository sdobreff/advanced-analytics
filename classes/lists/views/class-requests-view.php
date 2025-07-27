<?php
/**
 * Class: Responsible for Requests views and operations.
 *
 * Edit and add requests, attach screens.
 *
 * @package advanced-analytics
 *
 * @since latest
 */

declare(strict_types=1);

namespace ADVAN\Lists\Views;

use ADVAN\Helpers\Settings;
use ADVAN\Helpers\WP_Helper;
use ADVAN\Lists\Requests_List;
use ADVAN\Entities\Common_Table;
use ADVAN\Entities\Requests_Log_Entity;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\ADVAN\Lists\Views\Requests_View' ) ) {
	/**
	 * Responsible for proper context determination.
	 *
	 * @since latest
	 */
	class Requests_View {

		/**
		 * Displays the requests page.
		 *
		 * @return void
		 *
		 * @since latest
		 */
		public static function analytics_requests_page() {
			\add_thickbox();
			\wp_enqueue_style( 'media-views' );
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
				$name         = Transients_Helper::get_transient_name( $transient['option_name'] );
				$expiration   = Transients_Helper::get_transient_expiration_time( $transient['option_name'] );

				if ( 0 !== $expiration ) {

					$next_run_gmt        = gmdate( 'Y-m-d H:i:s', $expiration );
					$next_run_date_local = get_date_from_gmt( $next_run_gmt, 'Y-m-d' );
					$next_run_time_local = get_date_from_gmt( $next_run_gmt, 'H:i:s' );
				}

				?>
				<div class="wrap">
					<h1 class="wp-heading-inline"><?php \esc_html_e( 'Edit Transient', '0-day-analytics' ); ?></h1>
					<hr class="wp-header-end">
					<?php
					if ( Settings::get_current_options()['advana_requests_disable'] ) {
						?>
					<div id="advana-status-error" class="notice notice-error">
						<?php
						printf(
							'<p>%1$s</p>',
							sprintf(
								/* translators: %s: Link to requests settings. */
								esc_html__( 'The requests logging is disabled. To enable it go to : %s', '0-day-analytics' ),
								'<a href="' . \add_query_arg( array( 'page' => Settings::SETTINGS_MENU_SLUG ), network_admin_url( 'admin.php' ) ) . '#aadvana-options-tab-request-list">' . __( 'here', '0-day-analytics' ) . '</a>',
							)
						);
						?>
					</div>
						<?php
					}
					?>
					<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="transient" value="<?php echo esc_attr( $name ); ?>" />
						<input type="hidden" name="<?php echo \esc_attr( Requests_List::SEARCH_INPUT ); ?>" value="<?php echo esc_attr( Requests_List::escaped_search_input() ); ?>" />
						<input type="hidden" name="action" value="<?php echo \esc_attr( Requests_List::UPDATE_ACTION ); ?>" />
						<?php \wp_nonce_field( Requests_List::NONCE_NAME ); ?>

						<?php
						if ( in_array( $name, Requests_Helper::WP_CORE_TRANSIENTS ) ) {
							?>
							<div id="advaa-status-notice" class="notice notice-warning">
								<p><?php esc_html_e( 'This is a WP core transient, even if you update it, the new value will be overridden by the core!', '0-day-analytics' ); ?></p>
							</div>
							<?php
						} else {
							foreach ( Requests_Helper::WP_CORE_TRANSIENTS as $trans_name ) {
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
									<td><input type="text" class="large-text code" name="name" value="<?php echo \esc_attr( Requests_Helper::clear_transient_name( $transient['option_name'] ) ); ?>" /></td>
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
				</div>
				<?php
			} else {
				$requests = new Requests_List( '' );
				$requests->prepare_items();
				?>
				<div class="wrap">
					<h1 class="wp-heading-inline"><?php \esc_html_e( 'Requests', '0-day-analytics' ); ?></h1>

					<hr class="wp-header-end">
					<?php
					if ( Settings::get_current_options()['advana_requests_disable'] ) {
						?>
					<div id="advana-status-error" class="notice notice-error">
						<?php
						printf(
							'<p>%1$s</p>',
							sprintf(
								/* translators: %s: Link to requests settings. */
								esc_html__( 'The requests logging is disabled. To enable it go to : %s', '0-day-analytics' ),
								'<a href="' . \add_query_arg( array( 'page' => Settings::SETTINGS_MENU_SLUG ), network_admin_url( 'admin.php' ) ) . '#aadvana-options-tab-request-list">' . __( 'here', '0-day-analytics' ) . '</a>',
							)
						);
						?>
					</div>
						<?php
					}
					?>

					<form id="requests-filter" method="get">
					<?php

					$page  = ( isset( $_GET['page'] ) ) ? \sanitize_text_field( \wp_unslash( $_GET['page'] ) ) : 1;
					$paged = ( isset( $_GET['paged'] ) ) ? filter_input( INPUT_GET, 'paged', FILTER_SANITIZE_NUMBER_INT ) : 1;

					printf( '<input type="hidden" name="page" value="%s" />', \esc_attr( $page ) );
					printf( '<input type="hidden" name="paged" value="%d" />', \esc_attr( $paged ) );

					echo '<div style="clear:both; float:right">';
					$requests->search_box(
						__( 'Search', '0-day-analytics' ),
						strtolower( $requests::get_table_name() ) . '-find'
					);
					echo '</div>';
					$requests->display();

					?>
					</form>
				</div>
				<style>
					/* modal */
					.media-modal,
					.media-modal-backdrop {
						display: none;
					}

					.media-modal.open,
					.media-modal-backdrop.open {
						display: block;
					}

					#aadvana-modal.aadvana-modal .media-frame-title,
					#aadvana-modal.aadvana-modal .media-frame-content {
						left: 0;
					}

					.media-frame-router {
						left: 10px;
					}
					#aadvana-modal.aadvana-modal
					.media-frame-content {
						top: 48px;
						bottom: 0;
						overflow: auto;
					}

					.button-link.media-modal-close {
						cursor: pointer;
						text-decoration: none;
					}

					.aadvana-modal-buttons{
						position: absolute;
						top: 0;
						right: 0;
					}
					.aadvana-modal-buttons .media-modal-close{
						position: relative;
						width: auto;
						padding: 0 .5rem;
					}

					.media-modal-close.prev .media-modal-icon::before {
						content: "\f342";
					}

					.media-modal-close.next .media-modal-icon::before {
						content: "\f346";
					}

					.modal-content-wrap {
						padding: 16px;
					}

					/* tab and panel */
					.aadvana-modal .nav-tab-active{
						border-bottom: solid 1px white;
						background-color: white;
					}
					.aadvana-panel-active{
						display:block;
						margin: 1rem 0;
					}

					.wrapper {
						text-align: center;
					}
					.wrapper .box{
						text-align: left;
						background-color: #f4f5f6;
						padding: .5rem;
						border-radius: .5rem;
						margin-bottom: 1rem;
						display: inline-block;
						vertical-align: top;
						width: 48%;
						box-sizing: border-box;
					}
					@media screen and (max-width: 782px) {

						.wrapper .box{
							display: block;
							width: auto;
						}

					}

				</style>

				<div id="aadvana-modal" class="media-modal aadvana-modal">
					<div class="aadvana-modal-buttons">
						<button class="button-link media-modal-close"><span class="media-modal-icon"></span></button>
					</div>
					<div class="media-modal-content">
						<div class="media-frame">
							<div class="media-frame-title">
								<h1><?php \esc_html_e( 'Request details:', '0-day-analytics' ); ?></h1>
							</div>
							<div class="media-frame-content">
								<div class="modal-content-wrap">
									<p>
										<b><?php \esc_html_e( 'Request: ', '0-day-analytics' ); ?> </b><span class="http-request-type"></span> | <span class="http-request-status"></span> | <span class="http-request-runtime"></span> | <?php \esc_html_e( 'Domain: ', '0-day-analytics' ); ?><span class="http-request-domain"></span>
									</p>
									<p>
										<b><?php \esc_html_e( 'Page:', '0-day-analytics' ); ?>:</b> 
										<span class="http-request-page"></span><br>
										<b><?php \esc_html_e( 'Request URL:', '0-day-analytics' ); ?>:</b> <span class="http-request-url"></span>
									</p>
									<div class="aadvana-panel-wrapper">
										<div class="aadvana-request-response aadvana-panel-active wrapper">
											<div class="box">
												<h3><?php \esc_html_e( 'Request:', '0-day-analytics' ); ?></h3>
												<div class="http-request-args aadvana-pre-300"></div>
											</div>
											<div class="box">
												<h3><?php \esc_html_e( 'Response:', '0-day-analytics' ); ?></h3>
												<div class="http-response aadvana-pre-300"></div>
											</div>						
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="media-modal-backdrop"></div>

					<script>

						jQuery(document).on('click', '.aadvan-request-show-details', function( e ) {
							e.preventDefault();
							let id = jQuery( this ).data( 'details-id' );
							jQuery('.http-request-args').html( jQuery('#advana-request-details-' + id ).html() );
							jQuery('.http-response').html( jQuery('#advana-response-details-' + id ).html() );

							jQuery('.http-request-status').html( jQuery('#advana-request-request_status-' + id ).clone() );
							jQuery('.http-request-runtime').html( jQuery('#advana-request-runtime-' + id ).clone() );
							jQuery('.http-request-type').html( jQuery('#advana-request-type-' + id ).clone() );
							jQuery('.http-request-domain').html( jQuery('#advana-request-domain-' + id ).clone() );
							jQuery('.http-request-page').html( jQuery('#advana-request-page_url-' + id ).clone() );
							jQuery('.http-request-url').html( jQuery('#advana-request-url-' + id ).clone() );

							jQuery('.media-modal').addClass('open');
							jQuery('.media-modal-backdrop').addClass('open');
						});

						jQuery(document).on('click', '.media-modal-close', function () {
							jQuery('.media-modal .http-request-args').html('');
							jQuery('.media-modal .http-response').html('');
							jQuery('.media-modal').removeClass('open');
							jQuery('.media-modal-backdrop').removeClass('open');
						});
					</script>
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
		 * @since latest
		 */
		public static function add_help_content_table() {

			$help_text  = '<p>' . __( 'This screen allows you to see all the requests where your WordPress site is currently running.', '0-day-analytics' ) . '</p>';
			$help_text .= '<p>' . __( 'You can specify how many rows to be shown, or filter and search for given value(s).', '0-day-analytics' ) . '</p>';
			$help_text .= '<p>' . __( 'You can delete rows - keep in mind that this operation is destructive and can not be undone - make a backup first.', '0-day-analytics' ) . '</p>';
			$help_text .= '<p>' . __( 'Bulk operations are supported.', '0-day-analytics' ) . '</p>';
			$help_text .= '<p>' . __( 'Use the drop-down to select different table.', '0-day-analytics' ) . '</p>';

			return $help_text;
		}

		/**
		 * Options Help
		 *
		 * Return help text for options screen
		 *
		 * @return string  Help Text
		 *
		 * @since latest
		 */
		public static function add_config_content_table() {

			Common_Table::init( Requests_Log_Entity::get_table_name() );

			$table_info = Common_Table::get_table_status();
			$help_text  = '';
			if ( ! empty( $table_info ) && isset( $table_info[0] ) ) {

				\ob_start();

				if ( isset( $table_info[0]['Name'] ) ) {
					?>
					<div><b><?php \esc_html_e( 'Name: ', '0-day-analytics' ); ?></b> <span class="italic"><?php echo \esc_attr( $table_info[0]['Name'] ); ?></span></div>
					<?php
				}

				if ( isset( $table_info[0]['Engine'] ) ) {
					?>
					<div><b><?php \esc_html_e( 'Engine: ', '0-day-analytics' ); ?></b> <span class="italic"><?php echo \esc_attr( $table_info[0]['Engine'] ); ?></span></div>
					<?php
				}

				if ( isset( $table_info[0]['Version'] ) ) {
					?>
					<div><b><?php \esc_html_e( 'Version: ', '0-day-analytics' ); ?></b> <span class="italic"><?php echo \esc_attr( $table_info[0]['Version'] ); ?></span></div>
					<?php
				}

				if ( isset( $table_info[0]['Row_format'] ) ) {
					?>
					<div><b><?php \esc_html_e( 'Row format: ', '0-day-analytics' ); ?></b> <span class="italic"><?php echo \esc_attr( $table_info[0]['Row_format'] ); ?></span></div>
					<?php
				}

				if ( isset( $table_info[0]['Rows'] ) ) {
					?>
					<div><b><?php \esc_html_e( 'Rows: ', '0-day-analytics' ); ?></b> <span class="italic"><?php echo \esc_attr( $table_info[0]['Rows'] ); ?></span></div>
					<?php
				}

				if ( isset( $table_info[0]['Avg_row_length'] ) ) {
					?>
					<div><b><?php \esc_html_e( 'Avg row length: ', '0-day-analytics' ); ?></b> <span class="italic"><?php echo \esc_attr( $table_info[0]['Avg_row_length'] ); ?></span></div>
					<?php
				}

				if ( isset( $table_info[0]['Data_length'] ) ) {
					?>
					<div><b><?php \esc_html_e( 'Data length: ', '0-day-analytics' ); ?></b> <span class="italic"><?php echo \esc_attr( $table_info[0]['Data_length'] ); ?></span></div>
					<?php
				}

				if ( isset( $table_info[0]['Index_length'] ) ) {
					?>
					<div><b><?php \esc_html_e( 'Index length: ', '0-day-analytics' ); ?></b> <span class="italic"><?php echo \esc_attr( $table_info[0]['Index_length'] ); ?></span></div>
					<?php
				}

				if ( isset( $table_info[0]['Data_free'] ) ) {
					?>
					<div><b><?php \esc_html_e( 'Data free: ', '0-day-analytics' ); ?></b> <span class="italic"><?php echo \esc_attr( $table_info[0]['Data_free'] ); ?></span></div>
					<?php
				}

				if ( isset( $table_info[0]['Auto_increment'] ) ) {
					?>
					<div> <b><?php \esc_html_e( 'Auto increment: ', '0-day-analytics' ); ?></b> <span class="italic"><?php echo \esc_attr( $table_info[0]['Auto_increment'] ); ?></span></div>
					<?php
				}

				if ( isset( $table_info[0]['Create_time'] ) ) {
					?>
					<div><b><?php \esc_html_e( 'Create time: ', '0-day-analytics' ); ?></b> <span class="italic"><?php echo \esc_attr( $table_info[0]['Create_time'] ); ?></span></div>
					<?php
				}

				if ( isset( $table_info[0]['Update_time'] ) ) {
					?>
					<div><b><?php \esc_html_e( 'Update time: ', '0-day-analytics' ); ?></b> <span class="italic"><?php echo \esc_attr( $table_info[0]['Update_time'] ); ?></span></div>
					<?php
				}

				if ( isset( $table_info[0]['Check_time'] ) ) {
					?>
					<div><b><?php \esc_html_e( 'Check time: ', '0-day-analytics' ); ?></b> <span class="italic"><?php echo \esc_attr( $table_info[0]['Check_time'] ); ?></span></div>
					<?php
				}

				if ( isset( $table_info[0]['Collation'] ) ) {
					?>
					<div><b><?php \esc_html_e( 'Collation: ', '0-day-analytics' ); ?></b> <span class="italic"><?php echo \esc_attr( $table_info[0]['Collation'] ); ?></span></div>
					<?php
				}
				?>
				<input type="button" name="truncate_action" id="truncate_table" class="button action" data-table-name="<?php echo \esc_attr( $table_info[0]['Name'] ); ?>" value="<?php \esc_html_e( 'Truncate Table', '0-day-analytics' ); ?>">

					<script>
						let action_truncate = document.getElementById("truncate_table");

						action_truncate.onclick = tableTruncate;

						async function tableTruncate(e) {

							if ( confirm( '<?php echo \esc_html__( 'You sure you want to truncate this table? That operation is destructive', '0-day-analytics' ); ?>' ) ) {
								let tableName = e.target.getAttribute('data-table-name');

								let attResp;

								try {
									attResp = await wp.apiFetch({
										path: '/wp-control/v1/truncate_table/' + tableName,
										method: 'DELETE',
										cache: 'no-cache'
									});

									if (attResp.success) {
										
										location.reload();
									} else if (attResp.message) {
										jQuery('#wp-admin-bar-aadvan-menu .ab-item').html('<b><i>' + attResp.message + '</i></b>');
									}

								} catch (error) {
									throw error;
								}
							}
						}

					</script>
					<?php

					if ( ! \in_array( $table_info[0]['Name'], Common_Table::get_wp_core_tables() ) ) {
						?>
					<input type="button" name="drop_action" id="drop_table" class="button action" data-table-name="<?php echo \esc_attr( $table_info[0]['Name'] ); ?>" value="<?php \esc_html_e( 'Drop Table', '0-day-analytics' ); ?>">

					<script>
						let action_drop = document.getElementById("drop_table");

						action_drop.onclick = tableDrop;

						async function tableDrop(e) {

							if ( confirm( '<?php echo \esc_html__( 'You sure you want to delete this table? That operation is destructive', '0-day-analytics' ); ?>' ) ) {
								let tableName = e.target.getAttribute('data-table-name');

								let attResp;

								try {
									attResp = await wp.apiFetch({
										path: '/wp-control/v1/drop_table/' + tableName,
										method: 'DELETE',
										cache: 'no-cache'
									});

									if (attResp.success) {
										
										location.reload();
									} else if (attResp.message) {
										jQuery('#wp-admin-bar-aadvan-menu .ab-item').html('<b><i>' + attResp.message + '</i></b>');
									}

								} catch (error) {
									throw error;
								}
							}
						}

					</script>
						<?php
					}

					$help_text = \ob_get_clean();
			}

			return $help_text;
		}

		/**
		 * Removes unnecessary arguments if present and reloads.
		 *
		 * @return void
		 *
		 * @since latest
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
