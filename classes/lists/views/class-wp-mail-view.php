<?php
/**
 * Class: Responsible for WP_Mail views and operations.
 *
 * Edit and add wp-mail, attach screens.
 *
 * @package advanced-analytics
 *
 * @since latest
 */

declare(strict_types=1);

namespace ADVAN\Lists\Views;

use ADVAN\Helpers\Settings;
use ADVAN\Helpers\WP_Helper;
use ADVAN\Lists\WP_Mail_List;
use ADVAN\Entities\Common_Table;
use ADVAN\ControllersApi\Endpoints;
use ADVAN\Entities\WP_Mail_Entity;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\ADVAN\Lists\Views\WP_Mail_View' ) ) {
	/**
	 * Responsible for proper context determination.
	 *
	 * @since latest
	 */
	class WP_Mail_View {

		/**
		 * Displays the wp_mail page.
		 *
		 * @return void
		 *
		 * @since latest
		 */
		public static function analytics_wp_mail_page() {
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
					<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="transient" value="<?php echo esc_attr( $name ); ?>" />
						<input type="hidden" name="<?php echo \esc_attr( WP_Mail_List::SEARCH_INPUT ); ?>" value="<?php echo esc_attr( WP_Mail_List::escaped_search_input() ); ?>" />
						<input type="hidden" name="action" value="<?php echo \esc_attr( WP_Mail_List::UPDATE_ACTION ); ?>" />
						<?php \wp_nonce_field( WP_Mail_List::NONCE_NAME ); ?>

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
				$wp_mail = new WP_Mail_List( '' );
				$wp_mail->prepare_items();
				?>
				<div class="wrap">
					<h1 class="wp-heading-inline"><?php \esc_html_e( 'Mail Logs', '0-day-analytics' ); ?></h1>

					<hr class="wp-header-end">
					<form id="wp-mail-filter" method="get">
					<?php

					$page  = ( isset( $_GET['page'] ) ) ? \sanitize_text_field( \wp_unslash( $_GET['page'] ) ) : 1;
					$paged = ( isset( $_GET['paged'] ) ) ? filter_input( INPUT_GET, 'paged', FILTER_SANITIZE_NUMBER_INT ) : 1;

					printf( '<input type="hidden" name="page" value="%s" />', \esc_attr( $page ) );
					printf( '<input type="hidden" name="paged" value="%d" />', \esc_attr( $paged ) );

					echo '<div style="clear:both; float:right">';
					$wp_mail->search_box(
						__( 'Search', '0-day-analytics' ),
						strtolower( $wp_mail::get_table_name() ) . '-find'
					);
					echo '</div>';
					$wp_mail->display();

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
					html.aadvana-darkskin .wrapper .box {
						background-color: #1d456b !important;
						border: 1px solid #ccc;
					}
					html.aadvana-darkskin .media-frame-content {
						background-color: #1d456b !important;
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
								<h1><?php \esc_html_e( 'Mail details:', '0-day-analytics' ); ?></h1>
							</div>
							<div class="media-frame-content">
								<div class="modal-content-wrap">
									<div class="aadvana-panel-wrapper">
										<div class="aadvana-request-response aadvana-panel-active wrapper">
											<div class="box">
												<div class="flex flex-row grow-0 p-2 w-full border-0 border-t border-solid justify-between">
													<div>
														<h3><?php \esc_html_e( 'Request:', '0-day-analytics' ); ?></h3>
													</div>
													<div class=""><span title="<?php echo __( 'Copy to clipboard', '0-day-analytics' ); ?>" class="dashicons dashicons-clipboard" style="cursor:pointer;" aria-hidden="true"></span> <span title="<?php echo __( 'Share', '0-day-analytics' ); ?>" class="dashicons dashicons-share" style="cursor:pointer;" aria-hidden="true"></span></div>
												</div>
												<div class="http-request-args aadvana-pre-300">

												</div>
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
							let that = this;
							try {
								attResp = wp.apiFetch({
									path: '/<?php echo Endpoints::ENDPOINT_ROOT_NAME; ?>/v1/mail_body/' + id,
									method: 'GET',
									cache: 'no-cache'
								}).then( ( attResp ) => {

									//console.log(attResp);

									jQuery('.media-modal .http-request-args').html(attResp.mail_body);
									
									
								} ).catch(
									( error ) => {
										if (error.message) {
											jQuery(that).closest("tr").after('<tr><td style="overflow:hidden;" colspan="'+(jQuery(that).closest("tr").find("td").length+1)+'"><div class="error" style="background:#fff; color:#000;"> ' + error.message + '</div></td></tr>');
										}
									}
								);
							} catch (error) {
								throw error;
							} finally {
								jQuery(that).css({
									"pointer-events": "",
									"cursor": ""
								})
							}

							jQuery('.media-modal').addClass('open');
							jQuery('.media-modal-backdrop').addClass('open');
						});

						jQuery(document).on('click', '.media-modal-close', function () {
							jQuery('.media-modal .http-request-args').html('');
							jQuery('.media-modal .http-response').html('');
							jQuery('.media-modal').removeClass('open');
							jQuery('.media-modal-backdrop').removeClass('open');
						});

						jQuery( document ).on( 'click', '.dashicons.dashicons-clipboard', function( e ) {

							if ( jQuery(this).parent().parent().next('.aadvana-pre-300').children('pre').length ) {
								let selectedText = jQuery(this).parent().parent().next('.aadvana-pre-300').children('pre').html();

								selectedText = selectedText.replace(/<br\s*\/?>/gim, "\n");
								selectedText = jQuery.parseHTML(selectedText); //parseHTML return HTMLCollection
								selectedText = jQuery(selectedText).text();

								navigator.clipboard.writeText(selectedText);
							}

						});
					
					jQuery( document ).ready( function() {

						if ( navigator.share ) {

							jQuery( document ).on( 'click', '.dashicons.dashicons-share', function( e ) {

								if ( jQuery(this).parent().parent().next('.aadvana-pre-300').children('pre').length ) {
									let selectedText = jQuery(this).parent().parent().next('.aadvana-pre-300').children('pre').html();

									selectedText = selectedText.replace(/<br\s*\/?>/gim, "\n");
									selectedText = jQuery.parseHTML(selectedText); //parseHTML return HTMLCollection
									selectedText = jQuery(selectedText).text();

									const shareData = {
										text: selectedText + '\n\n' + "<?php echo \get_site_url(); ?>",
									};

									try {
										navigator.share(shareData);
									} catch (err) {
										jQuery(this).text( `Error: ${err}` );
									}

								}
							});
							
						} else {
							jQuery( '.dashicons.dashicons-share' ).remove();
						}
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

			$help_text  = '<p>' . __( 'This screen allows you to see all the wp_mail where your WordPress site is currently running.', '0-day-analytics' ) . '</p>';
			$help_text .= '<p>' . __( 'You can specify how many rows to be shown, or filter and search for given value(s).', '0-day-analytics' ) . '</p>';
			$help_text .= '<p>' . __( 'You can delete rows - keep in mind that this operation is destructive and can not be undone - make a backup first.', '0-day-analytics' ) . '</p>';
			$help_text .= '<p>' . __( 'Bulk operations are supported.', '0-day-analytics' ) . '</p>';

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

			Common_Table::init( WP_Mail_Entity::get_table_name() );

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

				if ( isset( $table_info[0]['Create_time'] ) ) {
					?>
					<div><b><?php \esc_html_e( 'Create time: ', '0-day-analytics' ); ?></b> <span class="italic"><?php echo \esc_attr( $table_info[0]['Create_time'] ); ?></span></div>
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
										path: '/<?php echo Endpoints::ENDPOINT_ROOT_NAME; ?>/v1/truncate_table/' + tableName,
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
										path: '/<?php echo Endpoints::ENDPOINT_ROOT_NAME; ?>/v1/drop_table/' + tableName,
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
