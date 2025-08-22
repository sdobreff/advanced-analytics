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

use ADVAN\Helpers\Settings;
use ADVAN\Lists\Table_List;
use ADVAN\Entities\Common_Table;
use ADVAN\ControllersApi\Endpoints;

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
			\add_thickbox();
			\wp_enqueue_style( 'media-views' );
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

			$table_name = Common_Table::get_default_table();

			if ( isset( $_REQUEST['show_table'] ) ) {
				if ( \in_array( $_REQUEST['show_table'], Common_Table::get_tables() ) ) {
					$table_name = $_REQUEST['show_table']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
				}
			}

			$table = new Table_List( $table_name );
			$table->prepare_items();
			$core_table = '';
			if ( in_array( $table_name, Common_Table::get_wp_core_tables(), true ) ) {
				$core_table = ' ( <span class="dashicons dashicons-wordpress" aria-hidden="true" style="vertical-align: middle;"></span> ) ';
			}
			?>
				<div class="wrap">
					<h1 class="wp-heading-inline"><?php \esc_html_e( 'Table: ', '0-day-analytics' ); ?><?php echo $core_table . \esc_html( $table_name );  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h1>
					
					<hr class="wp-header-end">
					<form id="table-filter" method="get">
					<?php

					$page  = ( isset( $_GET['page'] ) ) ? \sanitize_text_field( \wp_unslash( $_GET['page'] ) ) : 1;
					$paged = ( isset( $_GET['paged'] ) ) ? filter_input( INPUT_GET, 'paged', FILTER_SANITIZE_NUMBER_INT ) : 1;

					printf( '<input type="hidden" name="page" value="%s" />', \esc_attr( $page ) );
					printf( '<input type="hidden" name="paged" value="%d" />', \esc_attr( $paged ) );

					printf( '<input type="hidden" name="show_table" value="%s" />', \esc_attr( $table_name ) );

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
						box-sizing: border-box;
					}
					html.aadvana-darkskin .wrapper .box {
						background-color: #1d456b !important;
						border: 1px solid #ccc;
					}
					html.aadvana-darkskin .media-frame-content {
						background-color: #1d456b !important;
					}

					.wrapper #attachments {
						width: 10%;
					}
					.wrapper #mail-body {
						width: 70%;
					}
					@media screen and (max-width: 782px) {

						.wrapper .box{
							display: block;
							width: auto;
						}

						.wrapper #attachments, .wrapper #mail-body {
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
									<p>
										<b><?php \esc_html_e( 'To', '0-day-analytics' ); ?>:</b> 
										<span class="http-mail-to"></span><br>
										<b><?php \esc_html_e( 'From', '0-day-analytics' ); ?>:</b> 
										<span class="http-mail-from"></span><br>
										<b><?php \esc_html_e( 'Subject', '0-day-analytics' ); ?>:</b> <span class="http-mail-subject"></span><br>
										<b><?php \esc_html_e( 'Additional headers', '0-day-analytics' ); ?>:</b> <span class="http-mail-headers"></span>
									</p>
									<div class="aadvana-panel-wrapper">
										<div class="aadvana-request-response aadvana-panel-active wrapper">
											<div class="box" id="mail-body">
												<div class="flex flex-row grow-0 p-2 w-full border-0 border-t border-solid justify-between">
													<div>
														<h3><?php \esc_html_e( 'Mail body:', '0-day-analytics' ); ?></h3>
													</div>
													<div class=""><span title="<?php echo __( 'Copy to clipboard (as raw HTML)', '0-day-analytics' ); ?>" class="dashicons dashicons-clipboard" style="cursor:pointer;font-family: dashicons !important;" aria-hidden="true"></span> <span title="<?php esc_html_e( 'Share', '0-day-analytics' ); ?>" class="dashicons dashicons-share" style="cursor:pointer;font-family: dashicons !important;" aria-hidden="true"></span></div>
												</div>
												<div class="http-request-args aadvana-pre-300" style="background: #fff;color:#000;">
													<?php
													\esc_html_e( 'Loading please wait...', '0-day-analytics' );
													?>
														
												</div>
											</div>
											<div class="box" id="attachments" style="display:none;">
												<div>
													<h3><?php \esc_html_e( 'Attachments:', '0-day-analytics' ); ?></h3>
													</div>
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

					jQuery(document).on('click', '.aadvana-tablerow-view', function( e ) {
						e.preventDefault();
						let id = jQuery( this ).data( 'details-id' );
						let that = this;
						try {
							attResp = wp.apiFetch({
								path: '/<?php echo Endpoints::ENDPOINT_ROOT_NAME; ?>/v1/get_table_record/<?php echo $table_name ?>/' + id + '/',
								method: 'GET',
								cache: 'no-cache'
							}).then( ( attResp ) => {

								jQuery('.media-modal .http-request-args').html(attResp.mail_body);
								jQuery('.media-modal .http-mail-to').html(attResp.email_to);
								jQuery('.media-modal .http-mail-from').html(attResp.email_from);
								jQuery('.media-modal .http-mail-subject').html(attResp.subject);
								jQuery('.media-modal .http-mail-headers').html(attResp.additional_headers);

								if ( attResp.attachments ) {
									jQuery('.media-modal #attachments').show();
									jQuery('.media-modal .http-response').html(attResp.attachments);
								}

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
						jQuery('.media-modal .http-request-args').html('<?php \esc_html_e( 'Loading please wait...', '0-day-analytics' );?>');
						jQuery('.media-modal .http-mail-to').html('');
						jQuery('.media-modal .http-mail-from').html('');
						jQuery('.media-modal .http-mail-subject').html('');
						jQuery('.media-modal .http-mail-headers').html('');
						jQuery('.media-modal #attachments').hide();
						jQuery('.media-modal .http-response').html('');
						jQuery('.media-modal').removeClass('open');
						jQuery('.media-modal-backdrop').removeClass('open');
					});

					jQuery( document ).on( 'click', '.dashicons.dashicons-clipboard', function( e ) {

						if ( jQuery(this).parent().parent().next('.aadvana-pre-300') ) {
							let selectedText = jQuery(this).parent().parent().next('.aadvana-pre-300').html();

							console.log(jQuery(this).parent().parent().next('.aadvana-pre-300').html())

							// selectedText = selectedText.replace(/<br\s*\/?>/gim, "\n");
							// selectedText = jQuery.parseHTML(selectedText); //parseHTML return HTMLCollection
							// selectedText = jQuery(selectedText).text();

							navigator.clipboard.writeText(selectedText);
						}

					});
				
					jQuery( document ).ready( function() {

						if ( navigator.share ) {

							jQuery( document ).on( 'click', '.dashicons.dashicons-share', function( e ) {

								if ( jQuery(this).parent().parent().next('.aadvana-pre-300') ) {
									let selectedText = jQuery(this).parent().parent().next('.aadvana-pre-300').html();

									// selectedText = selectedText.replace(/<br\s*\/?>/gim, "\n");
									// selectedText = jQuery.parseHTML(selectedText); //parseHTML return HTMLCollection
									// selectedText = jQuery(selectedText).text();

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

		/**
		 * Options Help
		 *
		 * Return help text for options screen
		 *
		 * @return string  Help Text
		 *
		 * @since 1.9.8.1
		 */
		public static function add_help_content_table() {

			$help_text  = '<p>' . __( 'This screen allows you to see all the tables in your Database where your WordPress site is currently running.', '0-day-analytics' ) . '</p>';
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
		 * @since 2.4.1
		 */
		public static function add_config_content_table() {

			if ( '' === Common_Table::get_name() ) {
				$table_name = Common_Table::get_default_table();
				if ( isset( $_REQUEST['show_table'] ) ) {
					if ( \in_array( $_REQUEST['show_table'], Common_Table::get_tables() ) ) {
						$table_name = $_REQUEST['show_table']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
					}
				}

				Common_Table::init( $table_name );
			}

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
										
										location.href= '<?php echo Settings::get_tables_page_link(); ?>';
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
		 * Responsible for switching the table of the view.
		 *
		 * @return void
		 *
		 * @since 2.1.0
		 */
		public static function switch_action() {

			if ( isset( $_REQUEST['table_filter_top'] ) || isset( $_REQUEST['table_filter_bottom'] ) ) {

				if ( \check_admin_referer( Table_List::SWITCH_ACTION, Table_List::SWITCH_ACTION . 'nonce' ) && \in_array( $_REQUEST['table_filter_top'], Common_Table::get_tables(), true ) ) {
					$table = $_REQUEST['table_filter_top']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash

					\wp_safe_redirect(
						\remove_query_arg(
							array( 'deleted' ),
							\add_query_arg(
								array(
									'page'       => Table_List::TABLE_MENU_SLUG,
									Table_List::SEARCH_INPUT => Table_List::escaped_search_input(),
									'show_table' => rawurlencode( $table ),
								),
								\admin_url( 'admin.php' )
							)
						)
					);
					exit;
				}
			}
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
					\remove_query_arg( array( '_wp_http_referer' ), \wp_unslash( $_SERVER['REQUEST_URI'] ) )
				);
				exit;
			}
		}
	}
}
