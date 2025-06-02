<?php
/**
 * Class: Determine the context in which the plugin is executed.
 *
 * Helper class to determine the proper status of the request.
 *
 * @package advanced-analytics
 *
 * @since 1.1.0
 */

declare(strict_types=1);

namespace ADVAN\Helpers;

use ADVAN\Lists\Logs_List;
use ADVAN\Lists\Crons_List;
use ADVAN\Controllers\Slack;
use ADVAN\Controllers\Telegram;
use ADVAN\Controllers\Error_Log;
use ADVAN\Controllers\Slack_API;
use ADVAN\Lists\Transients_List;
use ADVAN\Controllers\Telegram_API;
use ADVAN\Settings\Settings_Builder;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\ADVAN\Helpers\Settings' ) ) {
	/**
	 * Responsible for proper context determination.
	 *
	 * @since 1.1.0
	 */
	class Settings {

		public const OPTIONS_VERSION = '7'; // Incremented when the options array changes.

		public const MENU_SLUG = 'advan_logs';

		public const SETTINGS_MENU_SLUG = 'advan_logs_settings';

		public const CRON_MENU_SLUG = 'advan_cron_jobs';

		public const TRANSIENTS_MENU_SLUG = 'advan_transients';

		public const OPTIONS_PAGE_SLUG = 'analytics-options-page';

		public const SETTINGS_FILE_FIELD = 'aadvana_import_file';

		public const SETTINGS_FILE_UPLOAD_FIELD = 'aadvana_import_upload';

		public const SETTINGS_VERSION = 'aadvana_plugin_version';

		public const PAGE_SLUG = 'wp-control_page_advan_logs_settings';

		/**
		 * Holds cache for disabled severity levels
		 *
		 * @var array
		 *
		 * @since 1.1.0
		 */
		private static $disabled_severities = null;

		/**
		 * Default wp_config.php writer configs
		 *
		 * @var array
		 *
		 * @since 1.1.0
		 */
		private static $config_args = array(
			'normalize' => true,
			'raw'       => true,
			'add'       => true,
		);

		/**
		 * Array with the current options
		 *
		 * @var array
		 *
		 * @since 1.1.0
		 */
		private static $current_options = array();

		/**
		 * The name of the hook for the menu.
		 *
		 * @var string
		 *
		 * @since 1.1.0
		 */
		private static $hook = null;

		/**
		 * Array with the default options
		 *
		 * @var array
		 *
		 * @since 1.1.0
		 */
		private static $default_options = array();

		/**
		 * The link to the WP admin settings page
		 *
		 * @var string
		 */
		private static $settings_page_link = '';

		/**
		 * The link to the WP admin settings page
		 *
		 * @var string
		 */
		private static $settings_crons_link = '';

		/**
		 * The link to the WP admin settings page
		 *
		 * @var string
		 */
		private static $settings_error_logs_link = '';

		/**
		 * The link to the WP admin settings page
		 *
		 * @var string
		 */
		private static $settings_transients_link = '';

		/**
		 * The current version of the plugin
		 *
		 * @var string
		 */
		private static $current_version = '';

		/**
		 * Inits the class.
		 *
		 * @return void
		 *
		 * @since 1.1.0
		 */
		public static function init() {

			self::get_current_options();

			// Hook me up.
			\add_action( 'admin_menu', array( __CLASS__, 'add_options_page' ) ); // Insert the Admin panel.
			if ( \is_multisite() ) {
				\add_action( 'network_admin_menu', array( __CLASS__, 'add_options_page' ) ); // Insert the Admin on multisite install panel.
			}

			\add_action( 'admin_enqueue_scripts', array( __CLASS__, 'load_custom_wp_admin_style' ) );

			\add_action( 'admin_print_styles-' . Transients_List::PAGE_SLUG, array( __CLASS__, 'print_styles' ) );
			\add_action( 'admin_print_styles-' . Crons_List::PAGE_SLUG, array( __CLASS__, 'print_styles' ) );

			\add_action( 'admin_post_' . Transients_List::UPDATE_ACTION, array( __CLASS__, 'update_transient' ) );
			\add_action( 'admin_post_' . Crons_List::UPDATE_ACTION, array( __CLASS__, 'update_cron' ) );

			/**
			 * Draws the save button in the settings
			 */
			\add_action( 'aadvana_settings_save_button', array( __CLASS__, 'save_button' ) );
		}

		/**
		 * Enqueue the custom admin style.
		 *
		 * @param string $hook - The current admin page.
		 *
		 * @return void
		 *
		 * @since 1.7.4
		 */
		public static function load_custom_wp_admin_style( $hook ) {
			// $hook is string value given add_menu_page function.
			if ( Logs_List::PAGE_SLUG !== $hook && Crons_List::PAGE_SLUG !== $hook && Transients_List::PAGE_SLUG !== $hook ) {
				return;
			}
			\wp_enqueue_style( 'advan-admin-style', \ADVAN_PLUGIN_ROOT_URL . 'css/admin/style.css', array(), \ADVAN_VERSION, 'all' );
		}

		/**
		 * Responsible for printing the styles for the CodeMirror editor.
		 *
		 * @return void
		 *
		 * @since 1.8.5
		 */
		public static function print_styles() {
			$action = ! empty( $_REQUEST['action'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			? sanitize_key( $_REQUEST['action'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			: '';

			if ( \in_array( $action, array( 'edit_transient', 'edit_cron' ), true ) ) {
				// Try to enqueue the code editor.
				$settings = \wp_enqueue_code_editor(
					array(
						'type'       => 'text/plain',
						'codemirror' => array(
							'indentUnit' => 4,
							'tabSize'    => 4,
						),
					)
				);

				// Bail if user disabled CodeMirror.
				if ( false === $settings ) {
					return;
				}

				// Target the textarea.
				\wp_add_inline_script(
					'code-editor',
					sprintf(
						'jQuery( function() { wp.codeEditor.initialize( "transient-editor", %s ); } );',
						wp_json_encode( $settings )
					)
				);

				// Custom styling.
				\wp_add_inline_style(
					'code-editor',
					'.CodeMirror-wrap {
                    width: 99%;
                    border: 1px solid #8c8f94;
                    border-radius: 3px;
                    overflow: hidden;
                }
                .CodeMirror-gutters {
                    background: transparent;
                }'
				);
			}
		}

		/**
		 * Returns the current options.
		 * Fills the current options array with values if empty.
		 *
		 * @return array
		 *
		 * @since 1.1.0
		 */
		public static function get_current_options(): array {
			if ( empty( self::$current_options ) ) {

				// Get the current settings or setup some defaults if needed.
				self::$current_options = \get_option( ADVAN_SETTINGS_NAME );
				if ( ! self::$current_options ) {

					self::$current_options = self::get_default_options();
					self::store_options( self::$current_options );
				} elseif ( ! isset( self::$current_options['version'] ) || self::OPTIONS_VERSION !== self::$current_options['version'] ) {

					// Set any unset options.
					foreach ( self::get_default_options() as $key => $value ) {
						if ( ! isset( self::$current_options[ $key ] ) ) {
							self::$current_options[ $key ] = $value;
						}
					}
					self::$current_options['version'] = self::OPTIONS_VERSION;
					self::store_options( self::$current_options );
				}
			}

			return self::$current_options;
		}

		/**
		 * Stores the options in the database
		 *
		 * @param array $options - The array with the options to store.
		 *
		 * @return void
		 *
		 * @since 1.1.0
		 */
		public static function store_options( array $options ): void {
			\update_option( ADVAN_SETTINGS_NAME, $options );
		}

		/**
		 * Returns the default plugin options
		 *
		 * @return array
		 *
		 * @since 1.1.0
		 */
		public static function get_default_options(): array {

			if ( empty( self::$default_options ) ) {
				// Define default options.
				self::$default_options = array(
					'menu_admins_only'             => true,
					'live_notifications_admin_bar' => true,
					'environment_type_admin_bar'   => true,
					'slack_notifications'          => array(
						'all' => array(
							'channel'    => '',
							'auth_token' => '',
						),
					),
					'telegram_notifications'       => array(
						'all' => array(
							'channel'    => '',
							'auth_token' => '',
						),
					),
					'severities'                   => array(
						'deprecated' => array(
							'name'    => __( 'Deprecated', '0-day-analytics' ),
							'color'   => '#c4b576',
							'display' => true,
						),
						'error'      => array(
							'name'    => __( 'Error', '0-day-analytics' ),
							'color'   => '#ffb3b3',
							'display' => true,
						),
						'success'    => array(
							'name'    => __( 'Success', '0-day-analytics' ),
							'color'   => '#00ff00',
							'display' => true,
						),
						'info'       => array(
							'name'    => __( 'Info', '0-day-analytics' ),
							'color'   => '#0000ff',
							'display' => true,
						),
						'notice'     => array(
							'name'    => __( 'Notice', '0-day-analytics' ),
							'color'   => '#feeb8e',
							'display' => true,
						),
						'warning'    => array(
							'name'    => __( 'Warning', '0-day-analytics' ),
							'color'   => '#ffff00',
							'display' => true,
						),
						'fatal'      => array(
							'name'    => __( 'Fatal', '0-day-analytics' ),
							'color'   => '#b92a2a',
							'display' => true,
						),
						'parse'      => array(
							'name'    => __( 'Parse', '0-day-analytics' ),
							'color'   => '#b9762a',
							'display' => true,
						),
						'user'       => array(
							'name'    => __( 'User', '0-day-analytics' ),
							'color'   => '#0d4c24',
							'display' => true,
						),
					),
				);
			}

			return self::$default_options;
		}

		/**
		 * Returns the stored main menu hook
		 *
		 * @return string
		 *
		 * @since 1.1.0
		 */
		public static function get_main_menu_page_hook() {
			return self::$hook;
		}

		/**
		 * Add to Admin
		 *
		 * Add the options page to the admin menu
		 *
		 * @since 1.1.0
		 */
		public static function add_options_page() {

			if ( self::get_current_options()['menu_admins_only'] && ! \current_user_can( 'manage_options' ) ) {
				return;
			} else {

				$base = 'base';

				$base .= '64_en';

				$base .= 'code';

				self::$hook = \add_menu_page(
					\esc_html__( 'Advanced Analytics', '0-day-analytics' ),
					\esc_html__( 'WP Control', '0-day-analytics' ) . self::get_updates_count_html(),
					( ( self::get_current_options()['menu_admins_only'] ) ? 'manage_options' : 'read' ),
					self::MENU_SLUG,
					array( __CLASS__, 'analytics_options_page' ),
					'data:image/svg+xml;base64,' . $base( file_get_contents( \ADVAN_PLUGIN_ROOT . 'assets/icon.svg' ) ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
					3
				);

				\add_filter( 'manage_' . self::$hook . '_columns', array( Logs_List::class, 'manage_columns' ) );

				Logs_List::add_screen_options( self::$hook );

				\register_setting(
					\ADVAN_SETTINGS_NAME,
					\ADVAN_SETTINGS_NAME,
					array(
						self::class,
						'collect_and_sanitize_options',
					)
				);

				\add_submenu_page(
					self::MENU_SLUG,
					\esc_html__( 'Advanced Analytics', '0-day-analytics' ),
					\esc_html__( 'Error Log viewer', '0-day-analytics' ),
					( ( self::get_current_options()['menu_admins_only'] ) ? 'manage_options' : 'read' ), // No capability requirement.
					self::MENU_SLUG,
					array( __CLASS__, 'analytics_options_page' ),
					1
				);

				/* Crons */
				$cron_hook = \add_submenu_page(
					self::MENU_SLUG,
					\esc_html__( 'Advanced Analytics', '0-day-analytics' ),
					\esc_html__( 'Cron viewer', '0-day-analytics' ),
					( ( self::get_current_options()['menu_admins_only'] ) ? 'manage_options' : 'read' ), // No capability requirement.
					self::CRON_MENU_SLUG,
					array( __CLASS__, 'analytics_cron_page' ),
					1
				);

				Crons_List::add_screen_options( $cron_hook );

				\add_filter( 'manage_' . $cron_hook . '_columns', array( Crons_List::class, 'manage_columns' ) );

				/* Crons end */

				/* Transients */
				$transients_hook = \add_submenu_page(
					self::MENU_SLUG,
					\esc_html__( 'Advanced Analytics', '0-day-analytics' ),
					\esc_html__( 'Transients viewer', '0-day-analytics' ),
					( ( self::get_current_options()['menu_admins_only'] ) ? 'manage_options' : 'read' ), // No capability requirement.
					self::TRANSIENTS_MENU_SLUG,
					array( __CLASS__, 'analytics_transients_page' ),
					2
				);

				Transients_List::add_screen_options( $transients_hook );

				\add_filter( 'manage_' . $transients_hook . '_columns', array( Transients_List::class, 'manage_columns' ) );

				/* Transients end */

				if ( ! is_a( WP_Helper::check_debug_status(), '\WP_Error' ) && ! is_a( WP_Helper::check_debug_log_status(), '\WP_Error' ) && self::get_current_options()['live_notifications_admin_bar'] ) {
					\add_action( 'admin_bar_menu', array( __CLASS__, 'live_notifications' ), 1000, 1 );
				}

				\add_action( 'load-' . self::$hook, array( __CLASS__, 'aadvana_help' ) );

				\add_submenu_page(
					self::MENU_SLUG,
					\esc_html__( 'Settings', '0-day-analytics' ),
					\esc_html__( 'Settings', '0-day-analytics' ),
					'manage_options', // No capability requirement.
					self::SETTINGS_MENU_SLUG,
					array( __CLASS__, 'aadvana_show_options' ),
					301
				);

				if ( ! self::is_plugin_settings_page() ) {
					return;
				}

				// Reset settings.
				if ( isset( $_REQUEST['reset-settings'] ) && \check_admin_referer( 'reset-plugin-settings', 'reset_nonce' ) ) {

					\delete_option( ADVAN_SETTINGS_NAME );

					// Redirect to the plugin settings page.
					\wp_safe_redirect(
						\add_query_arg(
							array(
								'page'  => self::MENU_SLUG,
								'reset' => 'true',
							),
							\admin_url( 'admin.php' )
						)
					);
					exit;
				} elseif ( isset( $_REQUEST['export-settings'] ) && \check_admin_referer( 'export-plugin-settings', 'export_nonce' ) ) { // Export Settings.

					global $wpdb;

					$stored_options = $wpdb->get_results(
						$wpdb->prepare( 'SELECT option_name, option_value FROM ' . $wpdb->options . ' WHERE option_name = %s', \ADVAN_SETTINGS_NAME ),
						ARRAY_A
					);

					header( 'Cache-Control: public, must-revalidate' );
					header( 'Pragma: hack' );
					header( 'Content-Type: text/plain' );
					header( 'Content-Disposition: attachment; filename="' . ADVAN_TEXTDOMAIN . '-options-' . gmdate( 'dMy' ) . '.dat"' );

					echo \wp_json_encode( unserialize( $stored_options[0]['option_value'], array( 'allowed_classes' => false ) ) );
					die();
				} elseif ( isset( $_FILES[ self::SETTINGS_FILE_FIELD ] ) && \check_admin_referer( 'aadvana-plugin-data', 'aadvana-security' ) ) { // Import the settings.
					if ( isset( $_FILES ) &&
					isset( $_FILES[ self::SETTINGS_FILE_FIELD ] ) &&
					isset( $_FILES[ self::SETTINGS_FILE_FIELD ]['error'] ) &&
					! $_FILES[ self::SETTINGS_FILE_FIELD ]['error'] > 0 &&
					isset( $_FILES[ self::SETTINGS_FILE_FIELD ]['tmp_name'] ) ) {
						global $wp_filesystem;

						if ( null === $wp_filesystem ) {
							\WP_Filesystem();
						}

						if ( $wp_filesystem->exists( \sanitize_text_field( \wp_unslash( $_FILES[ self::SETTINGS_FILE_FIELD ]['tmp_name'] ) ) ) ) {
							$options = json_decode( $wp_filesystem->get_contents( \sanitize_text_field( \wp_unslash( $_FILES[ self::SETTINGS_FILE_FIELD ]['tmp_name'] ) ) ), true );
						}

						if ( ! empty( $options ) && is_array( $options ) ) {
							\remove_filter( 'sanitize_option_' . ADVAN_SETTINGS_NAME, array( self::class, 'collect_and_sanitize_options' ) );
							\update_option( ADVAN_SETTINGS_NAME, self::collect_and_sanitize_options( $options, true ) );
						}
					}

					\wp_safe_redirect(
						\add_query_arg(
							array(
								'page'   => self::MENU_SLUG,
								'import' => 'true',
							),
							\admin_url( 'admin.php' )
						)
					);
					exit;
				}
			}
		}

		/**
		 * Return the updates count markup.
		 *
		 * @return string Updates count markup, empty string if no updates available.
		 *
		 * @since 1.1.0
		 */
		public static function get_updates_count_html(): string {

			$count_html = sprintf(
				' <span id="advan-errors-menu" style="display:none" class="update-plugins"><span class="update-count">%s</span></span>',
				''
				// \number_format_i18n( $count )
			);

			return $count_html;
		}

		/**
		 * Options Page
		 *
		 * Get the options and display the page
		 *
		 * @since 1.1.0
		 */
		public static function analytics_options_page() {
			self::render();
		}

		/**
		 * Displays the settings page.
		 *
		 * @return void
		 *
		 * @since 1.1.0
		 */
		public static function render() {

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
			$events_list = new Logs_List( array() );
			$events_list->prepare_items();
			?>
			<div class="wrap">
			<h1 class="wp-heading-inline"><?php \esc_html_e( 'Error logs', '0-day-analytics' ); ?></h1>
			<form id="error-logs-filter" method="get">
				<?php
				$events_list->display();
				?>
				</form>
			</div>
			<?php
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

				$arguments = \wp_json_encode( (array) $cron['args'] );

				?>
				<div class="wrap">
					<h1 class="wp-heading-inline"><?php \esc_html_e( 'Edit Cron', '0-day-analytics' ); ?></h1>
					<hr class="wp-header-end">
	
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
				</div>
				<?php
			} else {
				$events_list = new Crons_List( array() );
				$events_list->prepare_items();
				?>
				<div class="wrap">
					<h1 class="wp-heading-inline"><?php \esc_html_e( 'Cron Jobs', '0-day-analytics' ); ?></h1>
					<form id="crons-filter" method="get">
					<?php

					$status = Crons_Helper::test_cron_spawn();

					if ( \is_wp_error( $status ) ) {
						if ( 'advana_cron_info' === $status->get_error_code() ) {
							?>
							<div id="advaa-status-notice" class="notice notice-info">
								<p><?php echo esc_html( $status->get_error_message() ); ?></p>
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
										esc_html__( 'There was a problem spawning a call to the WP-Cron system on your site. This means WP-Cron events on your site may not work. The problem was: %s', 'w0-day-analytics' ),
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
							'page'                        => self::TRANSIENTS_MENU_SLUG,
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
							'page'                   => self::CRON_MENU_SLUG,
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
		 * Displays the transients page.
		 *
		 * @return void
		 *
		 * @since 1.7.0
		 */
		public static function analytics_transients_page() {

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

				$next_run_gmt        = gmdate( 'Y-m-d H:i:s', $expiration );
				$next_run_date_local = get_date_from_gmt( $next_run_gmt, 'Y-m-d' );
				$next_run_time_local = get_date_from_gmt( $next_run_gmt, 'H:i:s' );

				?>
				<div class="wrap">
					<h1 class="wp-heading-inline"><?php \esc_html_e( 'Edit Transient', '0-day-analytics' ); ?></h1>
					<hr class="wp-header-end">

					<form method="post" action="<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="transient" value="<?php echo esc_attr( $name ); ?>" />
						<input type="hidden" name="<?php echo \esc_attr( Transients_List::SEARCH_INPUT ); ?>" value="<?php echo esc_attr( Transients_List::escaped_search_input() ); ?>" />
						<input type="hidden" name="action" value="<?php echo \esc_attr( Transients_List::UPDATE_ACTION ); ?>" />
						<?php \wp_nonce_field( Transients_List::NONCE_NAME ); ?>

						<table class="form-table">
							<tbody>
								<tr>
									<th><?php esc_html_e( 'Option ID', '0-day-analytics' ); ?></th>
									<td><input type="text" disabled class="large-text code" name="name" value="<?php echo esc_attr( $transient['option_id'] ); ?>" /></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Name', '0-day-analytics' ); ?></th>
									<td><input type="text" class="large-text code" name="name" value="<?php echo esc_attr( $transient['option_name'] ); ?>" /></td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Expiration', '0-day-analytics' ); ?></th>
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
									<th><?php esc_html_e( 'Value', '0-day-analytics' ); ?></th>
									<td>
										<textarea class="large-text code" name="value" id="transient-editor" style="height: 302px; padding-left: 35px; max-witdh:100%;"><?php echo esc_textarea( $transient['option_value'] ); ?></textarea>
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
					<form id="transients-filter" method="get">
					<?php

					$page  = ( isset( $_GET['page'] ) ) ? \sanitize_text_field( \wp_unslash( $_GET['page'] ) ) : 1;
					$paged = ( isset( $_GET['paged'] ) ) ? filter_input( INPUT_GET, 'paged', FILTER_SANITIZE_NUMBER_INT ) : 1;

					printf( '<input type="hidden" name="page" value="%s" />', \esc_attr( $page ) );
					printf( '<input type="hidden" name="paged" value="%d" />', \esc_attr( $paged ) );

					echo '<div style="clear:both; float:right">';
					$transients->search_box(
						__( 'Search', '0-day-analytics' ),
						strtolower( $transients::get_table_name() ) . '-find'
					);
					echo '</div>';
					$transients->display();

					?>
					</form>
				</div>
				<?php
			}
		}

		/**
		 * Add Options Help
		 *
		 * Add help tab to options screen
		 *
		 * @since 1.1.0
		 */
		public static function aadvana_help() {

			$screen = \get_current_screen();

			if ( $screen->id !== self::$hook ) {
				return; }

			$screen->add_help_tab(
				array(
					'id'      => 'advanced-analytics-help-tab',
					'title'   => __( 'Help', '0-day-analytics' ),
					'content' => self::add_help_content(),
				)
			);

			$screen->set_help_sidebar( self::add_sidebar_content() );
		}

		/**
		 * Options Help
		 *
		 * Return help text for options screen
		 *
		 * @return string  Help Text
		 *
		 * @since 1.1.0
		 */
		public static function add_help_content() {

			$help_text  = '<p>' . __( 'This screen allows you to specify the options for the Advanced Analytics plugin.', '0-day-analytics' ) . '</p>';
			$help_text .= '<p>' . __( 'Here you can set how many errors to be shown (maximum is 100), create new debug log, or enebale / disable WP logging..', '0-day-analytics' ) . '</p>';
			$help_text .= '<p>' . __( 'Remember to click the Save Changes button when on sexttings page for new settings to take effect.', '0-day-analytics' ) . '</p></h4>';

			return $help_text;
		}

		/**
		 * Options Help Sidebar
		 *
		 * Add a links sidebar to the options help
		 *
		 * @return string  Help Text
		 *
		 * @since 1.1.0
		 */
		public static function add_sidebar_content() {

			$help_text  = '<p><strong>' . __( 'For more information:', '0-day-analytics' ) . '</strong></p>';
			$help_text .= '<p><a href="https://wordpress.org/plugins/0-day-analytics/">' . __( 'Instructions', '0-day-analytics' ) . '</a></p>';
			$help_text .= '<p><a href="https://wordpress.org/support/plugin/0-day-analytics">' . __( 'Support Forum', '0-day-analytics' ) . '</a></p></h4>';

			return $help_text;
		}

		/**
		 * Returns the link to the WP admin settings page, based on the current WP install
		 *
		 * @return string
		 *
		 * @since 1.1.0
		 */
		public static function get_settings_page_link() {
			if ( '' === self::$settings_page_link ) {
				self::$settings_page_link = \add_query_arg( 'page', self::MENU_SLUG, \network_admin_url( 'admin.php' ) );
			}

			return self::$settings_page_link;
		}

		/**
		 * Returns the link to the WP admin settings page, based on the current WP install
		 *
		 * @return string
		 *
		 * @since 1.7.4
		 */
		public static function get_crons_page_link() {
			if ( '' === self::$settings_crons_link ) {
				self::$settings_crons_link = \add_query_arg( 'page', self::CRON_MENU_SLUG, \network_admin_url( 'admin.php' ) );
			}

			return self::$settings_crons_link;
		}

		/**
		 * Returns the link to the WP admin settings page, based on the current WP install
		 *
		 * @return string
		 *
		 * @since 1.7.5
		 */
		public static function get_error_log_page_link() {
			if ( '' === self::$settings_error_logs_link ) {
				self::$settings_error_logs_link = \add_query_arg( 'page', self::MENU_SLUG, \network_admin_url( 'admin.php' ) );
			}

			return self::$settings_error_logs_link;
		}

		/**
		 * Returns the link to the WP admin settings page, based on the current WP install
		 *
		 * @return string
		 *
		 * @since 1.7.4
		 */
		public static function get_transients_page_link() {
			if ( '' === self::$settings_transients_link ) {
				self::$settings_transients_link = \add_query_arg( 'page', self::TRANSIENTS_MENU_SLUG, \network_admin_url( 'admin.php' ) );
			}

			return self::$settings_transients_link;
		}

		/**
		 * Shows the save button in the settings
		 *
		 * @return void
		 *
		 * @since 1.1.0
		 */
		public static function save_button() {

			?>
			<div class="aadvana-panel-submit">
				<button name="<?php echo \esc_attr( \ADVAN_SETTINGS_NAME ); ?>[save_button]" class="aadvana-save-button aadvana-primary-button button button-primary button-hero"
						type="submit"><?php esc_html_e( 'Save Changes', '0-day-analytics' ); ?></button>
			</div>
			<?php
		}

		/**
		 * The Settings Panel UI
		 *
		 * @return void
		 *
		 * @since 1.1.0
		 */
		public static function aadvana_show_options() {

			\wp_enqueue_script( 'aadvana-admin-scripts', \ADVAN_PLUGIN_ROOT_URL . 'js/admin/aadvana-settings.js', array( 'jquery', 'jquery-ui-sortable', 'jquery-ui-draggable', 'wp-color-picker', 'jquery-ui-autocomplete' ), \ADVAN_VERSION, false );

			\wp_enqueue_style( 'advan-admin-style', \ADVAN_PLUGIN_ROOT_URL . 'css/admin/style.css', array(), \ADVAN_VERSION, 'all' );
			\wp_enqueue_media();

			$settings_tabs = self::build_options_tabs();

			?>

			<div id="aadvana-page-overlay"></div>

			<div id="aadvana-saving-settings">
				<svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
					<circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none" />
					<path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8" />
					<path class="checkmark__error_1" d="M38 38 L16 16 Z" />
					<path class="checkmark__error_2" d="M16 38 38 16 Z" />
				</svg>
			</div>

			<div class="aadvana-panel wrap">

				<div class="aadvana-panel-tabs">
					<div class="aadvana-logo">
						<svg fill="currentColor" height="800px" width="800px" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"  viewBox="0 0 512.001 512.001" xml:space="preserve">
							<g>
								<g>
								<path d="M484.312,86.624H19.688C8.812,86.624,0,95.436,0,106.312v291.376c0,10.876,8.812,19.688,19.688,19.688h464.624
			c10.876,0,19.688-8.812,19.688-19.688V106.312C504,95.436,495.188,86.624,484.312,86.624z M330.56,149.624h71.068V189H330.56
			c-10.884,0-19.736-8.804-19.736-19.688C310.824,158.428,319.676,149.624,330.56,149.624z M330.56,208.688h27.752v39.376H330.56
			c-10.884,0-19.736-8.804-19.736-19.688C310.824,217.492,319.676,208.688,330.56,208.688z M330.56,263.812h55.316v39.376H330.56
			c-10.884,0-19.736-8.804-19.736-19.688C310.824,272.616,319.676,263.812,330.56,263.812z M149.164,362.156
			c-51.984,0-94.276-42.296-94.276-94.276c0-51.98,42.524-94.272,94.508-94.272c2.172,0,4.168,1.764,4.168,3.936v86.272h85.94
			c2.172,0,3.936,1.828,3.936,4C243.44,319.8,201.148,362.156,149.164,362.156z M262.916,248.064H172.58
			c-2.172,0-3.264-1.42-3.264-3.596v-90.34c0-2.172,1.424-3.936,3.6-3.936c51.98,0,94.104,42.12,94.104,94.1
			C267.02,246.472,265.088,248.064,262.916,248.064z M334.688,358.312h-4.128c-10.884,0-19.736-8.804-19.736-19.688
			c0-10.884,8.852-19.688,19.736-19.688h4.128V358.312z M428.904,358.312h-66.656v-39.376H428.9
			c10.884,0,19.736,8.804,19.736,19.688C448.636,349.508,439.784,358.312,428.904,358.312z M428.904,303.188H413.44v-39.376h15.464
			c10.884,0,19.736,8.804,19.736,19.688C448.64,294.384,439.784,303.188,428.904,303.188z M428.904,248.064h-43.028v-39.376h43.028
			c10.884,0,19.736,8.804,19.736,19.688C448.64,239.26,439.784,248.064,428.904,248.064z M429.188,189.272v-39.46
			c11.812,0.028,19.688,8.864,19.688,19.732S441,189.248,429.188,189.272z"/>
								</g>
							</g>
						</svg>
					</div>

					<ul>
					<?php
					foreach ( $settings_tabs as $tab => $settings ) {

						if ( ! empty( $settings['title'] ) ) {
							$icon  = $settings['icon'];
							$title = $settings['title'];
							?>

							<li class="aadvana-tabs aadvana-options-tab-<?php echo \esc_attr( $tab ); ?>">
								<a href="#aadvana-options-tab-<?php echo \esc_attr( $tab ); ?>">
									<span class="dashicons-before dashicons-<?php echo \esc_html( $icon ); ?> aadvana-icon-menu"></span>
								<?php echo \esc_html( $title ); ?>
								</a>
							</li>
						<?php } else { ?>
							<li class="aadvana-tab-menu-head"><?php echo $settings; ?></li>
							<?php
						}
					}

					?>
					</ul>
					<div class="clear"></div>
				</div> <!-- .aadvana-panel-tabs -->

				<div class="aadvana-panel-content">

					<form method="post" name="aadvana_form" id="aadvana_form" enctype="multipart/form-data">

						<div class="aadvana-tab-head">
							<div id="aadvana-options-search-wrap">
								<input id="aadvana-panel-search" type="text" placeholder="<?php esc_html_e( 'Search', '0-day-analytics' ); ?>">
								<div id="aadvana-search-list-wrap" class="has-custom-scroll">
									<ul id="aadvana-search-list"></ul>
								</div>
							</div>

							<div class="awefpanel-head-elements">

							<?php \do_action( 'aadvana_settings_save_button' ); ?>

							
								<ul>
									<li>
										<div id="awefpanel-darkskin-wrap">
											<span class="darkskin-label"><svg height="512" viewBox="0 0 512 512" width="512" xmlns="http://www.w3.org/2000/svg"><title/><line style="fill:none;stroke:#000;stroke-linecap:round;stroke-miterlimit:10;stroke-width:32px" x1="256" x2="256" y1="48" y2="96"/><line style="fill:none;stroke:#000;stroke-linecap:round;stroke-miterlimit:10;stroke-width:32px" x1="256" x2="256" y1="416" y2="464"/><line style="fill:none;stroke:#000;stroke-linecap:round;stroke-miterlimit:10;stroke-width:32px" x1="403.08" x2="369.14" y1="108.92" y2="142.86"/><line style="fill:none;stroke:#000;stroke-linecap:round;stroke-miterlimit:10;stroke-width:32px" x1="142.86" x2="108.92" y1="369.14" y2="403.08"/><line style="fill:none;stroke:#000;stroke-linecap:round;stroke-miterlimit:10;stroke-width:32px" x1="464" x2="416" y1="256" y2="256"/><line style="fill:none;stroke:#000;stroke-linecap:round;stroke-miterlimit:10;stroke-width:32px" x1="96" x2="48" y1="256" y2="256"/><line style="fill:none;stroke:#000;stroke-linecap:round;stroke-miterlimit:10;stroke-width:32px" x1="403.08" x2="369.14" y1="403.08" y2="369.14"/><line style="fill:none;stroke:#000;stroke-linecap:round;stroke-miterlimit:10;stroke-width:32px" x1="142.86" x2="108.92" y1="142.86" y2="108.92"/><circle cx="256" cy="256" r="80" style="fill:none;stroke:#000;stroke-linecap:round;stroke-miterlimit:10;stroke-width:32px"/></svg></span>
											<input id="awefpanel-darkskin" class="aadvana-js-switch" type="checkbox" value="true">
											<span class="darkskin-label"><svg height="512" viewBox="0 0 512 512" width="512" xmlns="http://www.w3.org/2000/svg"><title/><path d="M160,136c0-30.62,4.51-61.61,16-88C99.57,81.27,48,159.32,48,248c0,119.29,96.71,216,216,216,88.68,0,166.73-51.57,200-128-26.39,11.49-57.38,16-88,16C256.71,352,160,255.29,160,136Z" style="fill:none;stroke:#000;stroke-linecap:round;stroke-linejoin:round;stroke-width:32px"/></svg></span>
											<script>
												if( 'undefined' != typeof localStorage ){
													var skin = localStorage.getItem('aadvana-backend-skin');
													if( skin == 'dark' ){
														document.getElementById('awefpanel-darkskin').setAttribute('checked', 'checked');

														var element = document.getElementsByTagName("html")[0];
														element.classList.add("aadvana-darkskin");
													}
												}
											</script>
										</div>
									</li>

								</ul>
							</div>
						</div>

						<?php
						foreach ( $settings_tabs as $tab => $settings ) {
							if ( ! empty( $settings['title'] ) ) {
								?>
						<!-- <?php echo \esc_attr( $tab ); ?> Settings -->
						<div id="aadvana-options-tab-<?php echo \esc_attr( $tab ); ?>" class="tabs-wrap">

								<?php
								include_once \ADVAN_PLUGIN_ROOT . 'classes/settings/settings-options/' . $tab . '.php';

								\do_action( 'aadvana_plugin_options_tab_' . $tab );
								?>

						</div>
								<?php
							}
						}
						?>

						<?php \wp_nonce_field( 'aadvana-plugin-data', 'aadvana-security' ); ?>
						<input type="hidden" name="action" value="aadvana_plugin_data_save" />

						<div class="aadvana-footer">

						<?php \do_action( 'aadvana_settings_save_button' ); ?>
						</div>
					</form>

				</div><!-- .aadvana-panel-content -->
				<div class="clear"></div>

			</div><!-- .aadvana-panel -->

						<?php
		}

		/**
		 * The settings panel option tabs.
		 *
		 * @return array
		 *
		 * @since 1.1.0
		 */
		public static function build_options_tabs(): array {

			$settings_tabs = array(

				// 'head-general' => esc_html__( 'General Settings', '0-day-analytics' ),

				// 'general'      => array(
				// 'icon'  => 'admin-generic',
				// 'title' => esc_html__( 'General', '0-day-analytics' ),
				// ),

				// 'head-global'  => esc_html__( 'Global Settings', '0-day-analytics' ),

				// 'backup'       => array(
				// 'icon'  => 'migrate',
				// 'title' => esc_html__( 'Export/Import', '0-day-analytics' ),
				// ),

				'head-error-log-list' => esc_html__( 'Error Log', '0-day-analytics' ),

				'error-log-list'      => array(
					'icon'  => 'list-view',
					'title' => esc_html__( 'Error Log Listing', '0-day-analytics' ),
				),

				'head-cron-list'      => esc_html__( 'Cron Log', '0-day-analytics' ),

				'cron-list'           => array(
					'icon'  => 'list-view',
					'title' => esc_html__( 'Cron options', '0-day-analytics' ),
				),

				'head-notifications'  => esc_html__( 'Notifications', '0-day-analytics' ),

				'notifications'       => array(
					'icon'  => 'bell',
					'title' => esc_html__( 'Notification options', '0-day-analytics' ),
				),

				'head-advanced'       => esc_html__( 'Advanced', '0-day-analytics' ),

				'advanced'            => array(
					'icon'  => 'admin-tools',
					'title' => esc_html__( 'Advanced', '0-day-analytics' ),
				),

				'backup'              => array(
					'icon'  => 'migrate',
					'title' => \esc_html__( 'Export/Import', '0-day-analytics' ),
				),

				'system-info'         => array(
					'icon'  => 'wordpress-alt',
					'title' => esc_html__( 'System Info', '0-day-analytics' ),
				),
			);

			return $settings_tabs;
		}

		/**
		 * Creates an option and draws it
		 *
		 * @param array $value - The array with option data.
		 *
		 * @return void
		 *
		 * @since 1.1.0
		 */
		public static function build_option( array $value ) {
			$data = false;

			if ( empty( $value['id'] ) ) {
				$value['id'] = ' ';
			}

			if ( isset( self::get_current_options()[ $value['id'] ] ) ) {
				$data = self::get_current_options()[ $value['id'] ];
			}

			Settings_Builder::create( $value, \ADVAN_SETTINGS_NAME . '[' . $value['id'] . ']', $data );
		}

		/**
		 * Setter method for the current options
		 *
		 * @param array $options - Array with the options to store.
		 *
		 * @return array
		 *
		 * @since 1.1.0
		 */
		public static function set_current_options( array $options ) {
			return self::$current_options = $options; // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.Found
		}

		/**
		 * Checks if current page is plugin settings page
		 *
		 * @return boolean
		 *
		 * @since 1.1.0
		 */
		public static function is_plugin_settings_page() {

			$current_page = ! empty( $_REQUEST['page'] ) ? \sanitize_text_field( \wp_unslash( $_REQUEST['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			return self::MENU_SLUG === $current_page || self::OPTIONS_PAGE_SLUG === $current_page || self::CRON_MENU_SLUG === $current_page || self::TRANSIENTS_MENU_SLUG === $current_page || self::SETTINGS_MENU_SLUG === $current_page;
		}

		/**
		 * Extracts the current version of the plugin
		 *
		 * @return string
		 *
		 * @since 1.1.0
		 */
		public static function get_version(): string {
			if ( empty( self::$current_version ) ) {
				self::$current_version = (string) \get_option( self::SETTINGS_VERSION, '' );
			}

			if ( empty( self::$current_version ) ) {
				self::$current_version = '0.0.0';
			}

			return self::$current_version;
		}

		/**
		 * Stores the current version of the plugin into the global options table
		 *
		 * @return void
		 *
		 * @since 1.1.0
		 */
		public static function store_version(): void {
			\update_option( self::SETTINGS_VERSION, \ADVAN_VERSION );
		}

		/**
		 * Shows live notifications in the admin bar if there are candidates.
		 *
		 * @param \WP_Admin_Bar $admin_bar - Current admin bar object.
		 *
		 * @return void
		 *
		 * @since 1.1.0
		 */
		public static function live_notifications( $admin_bar ) {
			if ( \current_user_can( 'manage_options' ) && \is_admin() ) {

				$events = Logs_List::get_error_items( false, false, true );

				$event = reset( $events );

				if ( $event && ! empty( $event ) ) {

					?>
					<style>
						#wp-admin-bar-aadvan-menu {
							overflow: auto;
							overflow-x: hidden;
							text-overflow: ellipsis;
							max-width: 50%;
							height: 30px;
							width: 400px;
						}
						/* #wpadminbar:not(.mobile) .ab-top-menu > li#wp-admin-bar-aadvan-menu:hover > .ab-item {
							background: #d7dce0;
							color: #42425d !important;
						} */
						<?php
						foreach ( self::get_current_options()['severities'] as $class => $properties ) {
							echo '.aadvan-live-notif-item.' . \esc_attr( $class ) . '{ border-left: 5px solid ' . \esc_attr( $properties['color'] ) . ' !important; }';
							// echo '.aadvan-live-notif-item.' . \esc_attr( $class ) . ' a { color: '.$properties['color'].' !important; }';
						}
						?>
					</style>
					<?php

					$time_format = 'g:i a';

					$event_datetime_utc = \gmdate( 'Y-m-d H:i:s', $event['timestamp'] );

					$timezone_local  = \wp_timezone();
					$event_local     = \get_date_from_gmt( $event_datetime_utc, 'Y-m-d' );
					$today_local     = ( new \DateTimeImmutable( 'now', $timezone_local ) )->format( 'Y-m-d' );
					$tomorrow_local  = ( new \DateTimeImmutable( 'tomorrow', $timezone_local ) )->format( 'Y-m-d' );
					$yesterday_local = ( new \DateTimeImmutable( 'yesterday', $timezone_local ) )->format( 'Y-m-d' );

					// If the offset of the date of the event is different from the offset of the site, add a marker.
					if ( \get_date_from_gmt( $event_datetime_utc, 'P' ) !== get_date_from_gmt( 'now', 'P' ) ) {
						$time_format .= ' (P)';
					}

					$event_time_local = \get_date_from_gmt( $event_datetime_utc, $time_format );

					if ( $event_local === $today_local ) {
						$date = sprintf(
							/* translators: %s: Time */
							__( 'Today at %s', '0-day-analytics' ),
							$event_time_local,
						);
					} elseif ( $event_local === $tomorrow_local ) {
						$date = sprintf(
							/* translators: %s: Time */
							__( 'Tomorrow at %s', '0-day-analytics' ),
							$event_time_local,
						);
					} elseif ( $event_local === $yesterday_local ) {
						$date = sprintf(
							/* translators: %s: Time */
							__( 'Yesterday at %s', '0-day-analytics' ),
							$event_time_local,
						);
					} else {
						$date = sprintf(
							/* translators: 1: Date, 2: Time */
							__( '%1$s at %2$s', '0-day-analytics' ),
							\get_date_from_gmt( $event_datetime_utc, 'F jS' ),
							$event_time_local,
						);
					}

					$time = sprintf(
						'<time datetime="%1$s">%2$s</time>',
						\esc_attr( gmdate( 'c', $event['timestamp'] ) ),
						\esc_html( $date )
					);

					$until = $event['timestamp'] - time();

					if ( $until < 0 ) {
						$ago = sprintf(
						/* translators: %s: Time period, for example "8 minutes" */
							__( '%s ago', '0-day-analytics' ),
							WP_Helper::interval( abs( $until ) )
						);

						$in = sprintf(
							' %s ',
							esc_html( $ago ),
						);
					} elseif ( 0 === $until ) {
						$in = __( 'Now', '0-day-analytics' );
					} else {
						$in = sprintf(
							/* translators: %s: Time period, for example "8 minutes" */
							__( 'In %s', '0-day-analytics' ),
							WP_Helper::interval( $until ),
						);
					}

					$classes = '';
					if ( isset( $event['severity'] ) && ! empty( $event['severity'] ) ) {
						$classes .= ' ' . $event['severity'];
					}
					$admin_bar->add_node(
						array(
							'id'    => 'aadvan-menu',
							'title' => ( ( ! empty( $in ) ) ? '<b><i>' . $in . '</i></b> : ' : '' ) . ( ( ! empty( $event['severity'] ) ) ? $event['severity'] . ' : ' : '' ) . $event['message'],
							'href'  => \add_query_arg( 'page', self::MENU_SLUG, \network_admin_url( 'admin.php' ) ),
							'meta'  => array( 'class' => 'aadvan-live-notif-item' . $classes ),
						)
					);
				}
			}
		}

		/**
		 * Collects the passed options, validates them and stores them.
		 *
		 * @param array $post_array - The collected settings array.
		 * @param bool  $import - The settings store comes from the imported file.
		 *
		 * @return array
		 *
		 * @since 1.1.0
		 * @since latest - Added $import parameter to allow importing settings, without interfering with the current options (everything related to wp-config manipulation is not stored in the settings).
		 */
		public static function collect_and_sanitize_options( array $post_array, bool $import = false ): array {
			if ( ! \current_user_can( 'manage_options' ) ) {
				\wp_die( \esc_html__( 'You do not have sufficient permissions to access this page.', '0-day-analytics' ) );
			}

			$advanced_options = array();

			$advanced_options['menu_admins_only'] = ( array_key_exists( 'menu_admins_only', $post_array ) ) ? filter_var( $post_array['menu_admins_only'], FILTER_VALIDATE_BOOLEAN ) : false;

			$advanced_options['live_notifications_admin_bar'] = ( array_key_exists( 'live_notifications_admin_bar', $post_array ) ) ? filter_var( $post_array['live_notifications_admin_bar'], FILTER_VALIDATE_BOOLEAN ) : false;

			$advanced_options['environment_type_admin_bar'] = ( array_key_exists( 'environment_type_admin_bar', $post_array ) ) ? filter_var( $post_array['environment_type_admin_bar'], FILTER_VALIDATE_BOOLEAN ) : false;

			foreach ( self::get_current_options()['severities'] as $name => $severity ) {
				$advanced_options['severities'][ $name ]['color'] = ( array_key_exists( 'severity_colors_' . $name . '_color', $post_array ) && ! empty( $post_array[ 'severity_colors_' . $name . '_color' ] ) ) ? \sanitize_text_field( $post_array[ 'severity_colors_' . $name . '_color' ] ) : ( ( isset( $post_array['severities'][ $name ]['color'] ) ) ? \sanitize_text_field( $post_array['severities'][ $name ]['color'] ) : $severity['color'] );

				$advanced_options['severities'][ $name ]['display'] = ( array_key_exists( 'severity_show_' . $name . '_display', $post_array ) && ! empty( $post_array[ 'severity_show_' . $name . '_display' ] ) ) ? true : ( ( isset( $post_array['severities'][ $name ]['display'] ) ) ? (bool) $post_array['severities'][ $name ]['display'] : false );

				$advanced_options['severities'][ $name ]['name'] = self::get_current_options()['severities'][ $name ]['name'];
			}

			$advanced_options['slack_notifications']['all'] = array();

			if ( array_key_exists( 'slack_notification_auth_token', $post_array ) ) {

				$slack_token = ( array_key_exists( 'slack_notification_auth_token', $post_array ) && ! empty( $post_array['slack_notification_auth_token'] ) ) ? \sanitize_text_field( \wp_unslash( $post_array['slack_notification_auth_token'] ) ) : '';

				if ( ! empty( $slack_token ) ) {

					if ( 'REMOVE' === $slack_token ) {
						$advanced_options['slack_notifications']['all']['auth_token'] = '';
					} elseif ( Slack_API::verify_slack_token( $slack_token ) ) {
						$advanced_options['slack_notifications']['all']['auth_token'] = $slack_token;
					}
				} elseif ( Slack::is_set() ) {
					$advanced_options['slack_notifications']['all']['auth_token'] = Slack::get_slack_auth_key();
				}
			} elseif ( Slack::is_set() ) {
				$advanced_options['slack_notifications']['all']['auth_token'] = Slack::get_slack_auth_key();
			}

			$advanced_options['slack_notifications']['all']['channel'] = ( array_key_exists( 'notification_default_slack_channel', $post_array ) ) ? \sanitize_text_field( \wp_unslash( $post_array['notification_default_slack_channel'] ) ) : '';

			$advanced_options['telegram_notifications']['all'] = array();

			if ( array_key_exists( 'telegram_notification_auth_token', $post_array ) ) {

				$telegram_token = ( array_key_exists( 'telegram_notification_auth_token', $post_array ) && ! empty( $post_array['telegram_notification_auth_token'] ) ) ? \sanitize_text_field( \wp_unslash( $post_array['telegram_notification_auth_token'] ) ) : '';

				if ( ! empty( $telegram_token ) ) {

					if ( 'REMOVE' === $telegram_token ) {
						$advanced_options['telegram_notifications']['all']['auth_token'] = '';
					} elseif ( Telegram_API::verify_telegram_token( $telegram_token ) ) {
						$advanced_options['telegram_notifications']['all']['auth_token'] = $telegram_token;
					}
				} elseif ( Telegram::is_set() ) {
					$advanced_options['telegram_notifications']['all']['auth_token'] = Telegram::get_telegram_auth_key();
				}
			} elseif ( Telegram::is_set() ) {
				$advanced_options['telegram_notifications']['all']['auth_token'] = Telegram::get_telegram_auth_key();
			}

			$advanced_options['telegram_notifications']['all']['channel'] = ( array_key_exists( 'notification_default_telegram_channel', $post_array ) ) ? \sanitize_text_field( \wp_unslash( $post_array['notification_default_telegram_channel'] ) ) : '';

			self::$current_options = $advanced_options;

			if ( ! $import && ! is_a( Config_Transformer::init(), '\WP_Error' ) ) {

				$wp_debug_enable = ( array_key_exists( 'wp_debug_enable', $post_array ) ) ? filter_var( $post_array['wp_debug_enable'], FILTER_VALIDATE_BOOLEAN ) : false;

				Config_Transformer::update( 'constant', 'WP_DEBUG', $wp_debug_enable, self::$config_args );

				$wp_debug_display_enable = ( array_key_exists( 'wp_debug_display_enable', $post_array ) ) ? filter_var( $post_array['wp_debug_display_enable'], FILTER_VALIDATE_BOOLEAN ) : false;

				Config_Transformer::update( 'constant', 'WP_DEBUG_DISPLAY', $wp_debug_display_enable, self::$config_args );

				$wp_script_debug = ( array_key_exists( 'wp_script_debug', $post_array ) ) ? filter_var( $post_array['wp_script_debug'], FILTER_VALIDATE_BOOLEAN ) : false;

				Config_Transformer::update( 'constant', 'SCRIPT_DEBUG', $wp_script_debug, self::$config_args );

				$wp_save_queries = ( array_key_exists( 'wp_save_queries', $post_array ) ) ? filter_var( $post_array['wp_save_queries'], FILTER_VALIDATE_BOOLEAN ) : false;

				Config_Transformer::update( 'constant', 'SAVEQUERIES', $wp_save_queries, self::$config_args );

				$wp_environment_type = ( array_key_exists( 'wp_environment_type', $post_array ) ) ? \sanitize_text_field( \wp_unslash( $post_array['wp_environment_type'] ) ) : false;

				if ( false !== $wp_environment_type && ! in_array( $wp_environment_type, array( 'production', 'development', 'staging', 'local' ), true ) ) {
					$wp_environment_type = 'production';
				}

				Config_Transformer::update( 'constant', 'WP_ENVIRONMENT_TYPE', $wp_environment_type, self::$config_args );

				$wp_development_mode = ( array_key_exists( 'wp_development_mode', $post_array ) ) ? \sanitize_text_field( \wp_unslash( $post_array['wp_development_mode'] ) ) : false;

				if ( false !== $wp_development_mode && ! in_array( $wp_development_mode, array( '', 'all', 'core', 'plugin', 'theme' ), true ) ) {
					$wp_development_mode = '';
				}

				Config_Transformer::update( 'constant', 'WP_DEVELOPMENT_MODE', $wp_development_mode, self::$config_args );

				$wp_debug_log_enable = ( array_key_exists( 'wp_debug_log_enable', $post_array ) ) ? filter_var( $post_array['wp_debug_log_enable'], FILTER_VALIDATE_BOOLEAN ) : false;

				Config_Transformer::update( 'constant', 'WP_DEBUG_LOG', $wp_debug_log_enable, self::$config_args );

				if ( $wp_debug_log_enable ) {
					$wp_debug_log_generate = ( array_key_exists( 'wp_debug_log_file_generate', $post_array ) ) ? filter_var( $post_array['wp_debug_log_file_generate'], FILTER_VALIDATE_BOOLEAN ) : false;

					$wp_debug_log_filename = ( array_key_exists( 'wp_debug_log_filename', $post_array ) ) ? \sanitize_text_field( $post_array['wp_debug_log_filename'] ) : '';

					if ( ! empty( $wp_debug_log_filename ) && Error_Log::autodetect() !== $wp_debug_log_filename ) {

						if ( \is_writable( \dirname( $wp_debug_log_filename ) ) ) {
							// $file_name = \dirname( $wp_debug_log_filename ) . \DIRECTORY_SEPARATOR . 'debug_' . File_Helper::generate_random_file_name() . '.log';

							Config_Transformer::update( 'constant', 'WP_DEBUG_LOG', $wp_debug_log_filename, self::$config_args );
							// } elseif ( \is_string( Error_Log::autodetect() ) ) {
							// Config_Transformer::update( 'constant', 'WP_DEBUG_LOG', Error_Log::autodetect(), self::$config_args );
						}
						// } elseif ( \is_string( Error_Log::autodetect() ) ) {
						// Config_Transformer::update( 'constant', 'WP_DEBUG_LOG', Error_Log::autodetect(), self::$config_args );
					}

					if ( $wp_debug_log_generate ) {
						$file_name = \WP_CONTENT_DIR . \DIRECTORY_SEPARATOR . 'debug_' . File_Helper::generate_random_file_name() . '.log';

						Config_Transformer::update( 'constant', 'WP_DEBUG_LOG', $file_name, self::$config_args );
					}
				}

				$wp_cron_disable = ( array_key_exists( 'wp_cron_disable', $post_array ) ) ? filter_var( $post_array['wp_cron_disable'], FILTER_VALIDATE_BOOLEAN ) : false;

				Config_Transformer::update( 'constant', 'DISABLE_WP_CRON', $wp_cron_disable, self::$config_args );
			}

			return $advanced_options;
		}

		/**
		 * Returns the disabled severities levels and stores them in the internal class cache.
		 *
		 * @return array
		 *
		 * @since 1.1.0
		 */
		public static function get_disabled_severities(): array {
			if ( null === self::$disabled_severities ) {
				self::$disabled_severities = array();
				foreach ( self::get_current_options()['severities'] as $name => $severity ) {
					if ( ! $severity['display'] ) {
						self::$disabled_severities[] = $name;
					}
				}
			}

			return self::$disabled_severities;
		}


		/**
		 * Modifies the admin footer text.
		 *
		 * @param   string $text The current admin footer text.
		 * @return  string
		 *
		 * @since 1.7.5
		 */
		public static function admin_footer_text( $text ) {

			global $current_screen;

			if ( isset( $current_screen ) && ( in_array( $current_screen->base, self::get_plugin_page_slugs(), true ) ) ) {
				$our_footer = '';

				$link        = 'https://github.com/sdobreff';
				$footer_link = 'https://wordpress.org/plugins/0-day-analytics/';

				return $our_footer . \sprintf(
				/* translators: This text is prepended by a link to Melapress's website, and appended by a link to Melapress's website. */
					'<a href="%1$s" target="_blank">' . ADVAN_NAME . '</a> ' . __( 'is developed and maintained by', 'wp-security-audit-log' ) . ' <a href="%2$s" target="_blank">Stoil Dobreff</a>.',
					$footer_link,
					$link
				);
			}

			return $text;
		}

		/**
		 * Modifies the admin footer version text.
		 *
		 * @param   string $text The current admin footer version text.
		 *
		 * @return  string
		 *
		 * @since 1.7.5
		 */
		public static function admin_footer_version_text( $text ) {

			global $current_screen;

			if ( isset( $current_screen ) && ( in_array( $current_screen->base, self::get_plugin_page_slugs(), true ) ) ) {

				return sprintf(
					'<a href="%s">%s</a> &#8729; <a href="%s">%s</a> &#8729; <a href="%s">%s</a> &#8729; %s %s',
					self::get_error_log_page_link(),
					__( 'Error Log', 'wp-security-audit-log' ),
					self::get_crons_page_link(),
					__( 'Crons', 'wp-security-audit-log' ),
					self::get_transients_page_link(),
					__( 'Transients', 'wp-security-audit-log' ),
					__( 'Version ', 'wp-security-audit-log' ),
					ADVAN_VERSION
				);
			}

			return $text;
		}

		/**
		 * Returns all fo the plugin admin pages slugs.
		 *
		 * @return array
		 *
		 * @since 1.7.5
		 */
		private static function get_plugin_page_slugs(): array {
			return array(
				self::PAGE_SLUG,
				Logs_List::PAGE_SLUG,
				Transients_List::PAGE_SLUG,
				Crons_List::PAGE_SLUG,
			);
		}
	}
}
