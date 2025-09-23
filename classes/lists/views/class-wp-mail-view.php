<?php
/**
 * Class: Responsible for WP_Mail views and operations.
 *
 * Edit and add wp-mail, attach screens.
 *
 * @package advanced-analytics
 *
 * @since 3.0.0
 */

declare(strict_types=1);

namespace ADVAN\Lists\Views;

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
	 * @since 3.0.0
	 */
	class WP_Mail_View {

		/**
		 * Displays the wp_mail page.
		 *
		 * @return void
		 *
		 * @since 3.0.0
		 */
		public static function analytics_wp_mail_page() {
			\add_thickbox();
			\wp_enqueue_style( 'media-views' );
			\wp_enqueue_script( 'wp-api-fetch' );
			\wp_enqueue_media();
			?>
			<script>
				if( 'undefined' != typeof localStorage ){
					var skin = localStorage.getItem('aadvana-backend-skin');
					if( skin == 'dark' ){

						var element = document.getElementsByTagName("html")[0];
						element.classList.add("aadvana-darkskin");
					}
				}
				jQuery(document).ready(function($){

					var mediaUploader;

					$('#upload-button').click(function(e) {
						e.preventDefault();
						// If the uploader object has already been created, reopen the dialog
						if (mediaUploader) {
						mediaUploader.open();
						return;
						}
						// Extend the wp.media object
						mediaUploader =  wp.media({
						title: 'Add Attachments',
						button: {
						text: 'Choose Files'
						}, multiple: true });

						// When a file is selected, grab the URL and set it as the text field's value
						mediaUploader.on('select', function() {
							//media_uploader.on("insert", function(){

							
							var length = mediaUploader.state().get("selection").length;
							var files = mediaUploader.state().get("selection").models

							var arr_file_url = []
							for(var iii = 0; iii < length; iii++)
							{
								//var image_url = files[iii].changed.url;
								arr_file_url.push( files[iii].changed.url ); 
								$('#attachment-container').append( '<a href="' + files[iii].changed.url + '" target="_blank">' + files[iii].changed.title + ' (' + files[iii].changed.url + ')' + '</a><br/>' )
								//var image_caption = files[iii].changed.caption;
								//var image_title = files[iii].changed.title;
							}
							//console.log( arr_file_url );
							var prev_attachments = $("#attachments").val();
							if(jQuery.trim(prev_attachments).length > 0) {
								$('#attachments').val( prev_attachments + ',' + arr_file_url.join() );
							}
							else {
								$('#attachments').val( arr_file_url.join() );
							}
							
						//});
							/*
							console.log(mediaUploader.state().get('selection'));
							attachment = mediaUploader.state().get('selection').first().toJSON();
							$('#attachments').val(attachment.url);
							*/
						});
						// Open the uploader dialog
						mediaUploader.open();
					});

				});
			</script>
			<?php

			$action = ! empty( $_REQUEST['action'] )
			? \sanitize_key( $_REQUEST['action'] )
			: '';

			if ( ! empty( $action ) && ( 'new_mail' === $action ) && WP_Helper::verify_admin_nonce( 'bulk-custom-delete' )
			) {
				
				?>
				<div class="wrap">
					<h1 class="wp-heading-inline"><?php \esc_html_e( 'Compose mail', '0-day-analytics' ); ?></h1>
					<hr class="wp-header-end">

					<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="<?php echo \esc_attr( WP_Mail_List::SEARCH_INPUT ); ?>" value="<?php echo esc_attr( WP_Mail_List::escaped_search_input() ); ?>" />
						<input type="hidden" name="action" value="<?php echo \esc_attr( WP_Mail_List::NEW_ACTION ); ?>" />
						<?php \wp_nonce_field( WP_Mail_List::NONCE_NAME ); ?>

						<table  class="form-table">
							<!--<tr>
								<th scope="row">
									<label for="from"><?php /*esc_html_e( 'From (Optional)', '0-day-analytics' ); */ ?></label>
								</th>

								<td> 
									<input type="text" id="from" name="from" value="" placeholder="<?php /*esc_attr_e( 'name@yourdomain.com (Optional)', '0-day-analytics' ); */ ?>" tabindex="1" class="regular-text">
									<p class="description"><strong>
									<?php
									/*
															esc_html_e(
										'Make sure you are setting a from address is hosted in your domain; otherwise, Your Composed email may be considered spam. For example, You should write the from email address like john@yourdomain.com.
									',
										'0-day-analytics'
									);
									*/
									?>
																	</strong></p>
								</td>  
							</tr>-->
							<tr> 
								<th scope="row">  
									<label for="to"><?php \esc_html_e( 'To', '0-day-analytics' ); ?></label> 
								</th>
								<td> 
									<input type="email" id="to" name="to" value="" placeholder="<?php \esc_attr_e( 'To', '0-day-analytics' ); ?>" tabindex="2" class="regular-text" required pattern="([a-zA-Z0-9\._\%\+\-]+@[a-zA-Z0-9\.\-]+\.[a-zA-Z]{2,20}[,]{0,}){0,}">
								</td>  
							</tr>
							<tr> 
								<th scope="row">  
									<label for="subject"><?php \esc_html_e( 'Subject', '0-day-analytics' ); ?></label> 
								</th>
								<td>
									<input type="text" id="subject" name="subject" value="" placeholder="<?php \esc_attr_e( 'Subject', '0-day-analytics' ); ?>" tabindex="3"  class="regular-text"  required>
								</td>
							</tr>     
							<tr> 
								<th scope="row">  
									<label for="upload-button"><?php \esc_html_e( 'Attachments', '0-day-analytics' ); ?></label> 
								</th>
								<td>
								
									<input type="hidden" name="attachments" id="attachments" value="" class="regular-text" >
									<input id="upload-button" type="button" class="button" value="<?php esc_attr_e( 'Attach Files', '0-day-analytics' ); ?>" tabindex="4" />
									<div id="attachment-container">
									</div>
								</td>
							</tr>     
							<tr> 
								<th scope="row">  
									<label for="message"><?php \esc_html_e( 'Message', '0-day-analytics' ); ?></label> 
								</th>
								<td>
									<?php
									$args = array(
										'textarea_name' => 'message',
										'wpautop'       => false, /*'textarea_rows' => '22',*/
										'media_buttons' => true,
										'tabindex'      => 4,
									);
									\wp_editor( '', 'message', $args );
									?>
								</td>
							</tr>
						</table>

						<p class="submit">
							<?php \submit_button( 'Send mail', 'primary', '', false ); ?>
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
				<?php echo '<a href="' . \esc_url( \admin_url( 'admin.php?page=' . WP_Mail_List::WP_MAIL_MENU_SLUG . '&action=new_mail&_wpnonce=' . \wp_create_nonce( 'bulk-custom-delete' ) ) ) . '" class="page-title-action">' . \esc_html__( 'Compose mail', '0-day-analytics' ) . '</a>'; ?>
					<hr class="wp-header-end">

					<h2 class='screen-reader-text'><?php \esc_html_e( 'Filter mail list', '0-day-analytics' ); ?></h2>
					<?php $wp_mail->views(); ?>

					<form id="wp-mail-filter" method="get">
					<?php

					$page  = ( isset( $_GET['page'] ) ) ? \sanitize_text_field( \wp_unslash( $_GET['page'] ) ) : 1;
					$paged = ( isset( $_GET['paged'] ) ) ? filter_input( INPUT_GET, 'paged', FILTER_SANITIZE_NUMBER_INT ) : 1;

					printf( '<input type="hidden" name="page" value="%s" />', \esc_attr( $page ) );
					printf( '<input type="hidden" name="paged" value="%d" />', \esc_attr( $paged ) );

					$wp_mail->search_box(
						__( 'Search', '0-day-analytics' ),
						strtolower( $wp_mail::get_table_name() ) . '-find'
					);
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
						jQuery('.media-modal .http-request-args').html('<?php \esc_html_e( 'Loading please wait...', '0-day-analytics' ); ?>');
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
		}

		/**
		 * Collects all the data from the form and creates new transient.
		 *
		 * @return void
		 *
		 * @since 1.9.2
		 */
		public static function new_mail() {

			// Bail if nonce fails.
			if ( empty( $_REQUEST['_wpnonce'] ) || ! WP_Helper::verify_admin_nonce( WP_Mail_List::NONCE_NAME ) ) {
				return;
			}

			if ( isset( $_POST['to'] ) ) {
				$to = \sanitize_text_field( $_POST['to'] );
			}
			if ( isset( $_POST['subject'] ) ) {
				$subject = \sanitize_text_field( $_POST['subject'] );
			}
			if ( isset( $_POST['message'] ) ) {
				// message may be content of html tags.
				$message = \wp_kses_post( $_POST['message'] );

				if ( empty( $message ) ) {
					$message = ' ';
				}

				\add_filter(
					'wp_mail_content_type',
					function () {
						return 'text/html';
					}
				);
			}

			$arr_attachments = array();
			if ( isset( $_POST['attachments'] ) && ! empty( $_POST['attachments'] ) ) {
				$arr_attachments_url = explode( ',', $_POST['attachments'] );
				$arr_attachments_url = array_map( 'sanitize_text_field', $arr_attachments_url );
				$arr_attachments     = array();
				foreach ( $arr_attachments_url as $attach_url ) {
					$arr_attachments[] = str_replace( WP_CONTENT_URL, WP_CONTENT_DIR, $attach_url );
				}
			}

			$header = '';
			/*
			if ( ! empty( $_POST['from'] ) && filter_var( $_POST['from'], FILTER_VALIDATE_EMAIL ) ) {
			$header = 'From:' . sanitize_text_field( $_POST['from'] ) . "\r\n";
			}*/
			$ret_mail = \wp_mail( $to, $subject, $message, $header, $arr_attachments );

			\wp_safe_redirect(
				\remove_query_arg(
					array( 'deleted' ),
					\add_query_arg(
						array(
							'page'                     => WP_Mail_List::WP_MAIL_MENU_SLUG,
							WP_Mail_List::SEARCH_INPUT => WP_Mail_List::escaped_search_input(),
							'updated'                  => true,
						),
						\admin_url( 'admin.php' )
					)
				)
			);
			exit;
		}

		/**
		 * Options Help
		 *
		 * Return help text for options screen
		 *
		 * @return string  Help Text
		 *
		 * @since 3.0.0
		 */
		public static function add_help_content_table() {

			$help_text  = '<p>' . __( 'This screen allows you to see all the mails sent from your WordPress site.', '0-day-analytics' ) . '</p>';
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
		 * @since 3.0.0
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
		 * @since 3.0.0
		 */
		public static function page_load() {
			if ( ! empty( $_GET['_wp_http_referer'] ) ) {
				\wp_redirect(
					\remove_query_arg( array( '_wp_http_referer', 'bulk_action' ), \wp_unslash( $_SERVER['REQUEST_URI'] ) )
				);
				exit;
			}
		}

		/**
		 * Responsible for filtering table by site ID.
		 *
		 * @return void
		 *
		 * @since 2.1.0
		 */
		public static function site_id_filter_action() {

			if ( isset( $_REQUEST['site_id_top'] ) || isset( $_REQUEST['site_id_filter_bottom'] ) ) {

				if ( \check_admin_referer( WP_Mail_List::SITE_ID_FILTER_ACTION, WP_Mail_List::SITE_ID_FILTER_ACTION . 'nonce' ) ) {
					$id = $_REQUEST['site_id_top']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash

					\wp_safe_redirect(
						\remove_query_arg(
							array( 'deleted' ),
							\add_query_arg(
								array(
									'page'       => WP_Mail_List::WP_MAIL_MENU_SLUG,
									WP_Mail_List::SEARCH_INPUT => WP_Mail_List::escaped_search_input(),
									'site_id' => rawurlencode( $id ),
								),
								\admin_url( 'admin.php' )
							)
						)
					);
					exit;
				}
			}
		}
	}
}
