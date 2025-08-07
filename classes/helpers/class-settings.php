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
use ADVAN\Lists\Table_List;
use ADVAN\Controllers\Slack;
use ADVAN\Lists\Requests_List;
use ADVAN\Controllers\Telegram;
use ADVAN\Controllers\Error_Log;
use ADVAN\Controllers\Slack_API;
use ADVAN\Lists\Transients_List;
use ADVAN\Lists\Views\Crons_View;
use ADVAN\Lists\Views\Table_View;
use ADVAN\Controllers\Telegram_API;
use ADVAN\Lists\Views\Requests_View;
use ADVAN\Settings\Settings_Builder;
use ADVAN\Lists\Views\Transients_View;
use Requests;

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

		public const OPTIONS_VERSION = '15'; // Incremented when the options array changes.

		public const MENU_SLUG = 'advan_logs';

		public const SETTINGS_MENU_SLUG = 'advan_logs_settings';

		public const OPTIONS_PAGE_SLUG = 'analytics-options-page';

		public const SETTINGS_FILE_FIELD = 'aadvana_import_file';

		public const SETTINGS_FILE_UPLOAD_FIELD = 'aadvana_import_upload';

		public const SETTINGS_VERSION = 'aadvana_plugin_version';

		public const PAGE_SLUG = 'wp-control_page_advan_logs_settings';

		public const LIVE_NOTIF_JS_MODULE = 'wp-control-live-notifications-js';

		/**
		 * Holds cache for disabled severity levels
		 *
		 * @var array
		 *
		 * @since 1.1.0
		 */
		private static $disabled_severities = null;

		/**
		 * Holds cache for enabled severity levels
		 *
		 * @var array
		 *
		 * @since 1.1.0
		 */
		private static $enabled_severities = null;

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
		 *
		 * @since 1.2.0
		 */
		private static $settings_page_link = '';

		/**
		 * The link to the WP admin settings page
		 *
		 * @var string
		 *
		 * @since 1.2.0
		 */
		private static $settings_crons_link = '';

		/**
		 * The link to the WP admin settings page
		 *
		 * @var string
		 *
		 * @since 2.1.0
		 */
		private static $settings_table_link = '';

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
		 * The link to the WP admin settings page
		 *
		 * @var string
		 */
		private static $settings_requests_link = '';

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

			if ( self::get_option( 'keep_reading_error_log' ) || ( defined( 'WP_DEBUG' ) && \WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && \WP_DEBUG_LOG ) ) {
				\add_action( 'admin_enqueue_scripts', array( __CLASS__, 'load_custom_wp_admin_style' ) );
			}

			/* Crons start */
			if ( self::get_option( 'cron_module_enabled' ) ) {
				Crons_List::hooks_init();
			}
			/* Crons end */

			/* Transients start */
			if ( self::get_option( 'transients_module_enabled' ) ) {
				Transients_List::hooks_init();
			}
			/* Transients end */

			/* Tables start */
			if ( self::get_option( 'tables_module_enabled' ) ) {
				Table_List::hooks_init();
			}
			/* Tables end */

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
			?>
				<script>
					window.addEventListener("load", () => {

						if ( ( "Notification" in window ) && Notification.permission === "granted" ) {
							// following makes an AJAX call to PHP to get notification every 10 secs
							setInterval(function() { pushNotify(); }, 10000);
						}

						function pushNotify() {
							if (Notification.permission !== "granted")
								Notification.requestPermission();
							else {

								var data = {
									'action': 'aadvana_get_notification_data',
									'_wpnonce': '<?php echo \wp_create_nonce( 'advan-plugin-data', 'advanced-analytics-security' ); ?>',
								};

								jQuery.get({
									url: "<?php echo admin_url( 'admin-ajax.php' ); ?>",
									data,
									success: function(data, textStatus, jqXHR) {
										// if PHP call returns data process it and show notification
										// if nothing returns then it means no notification available for now
										if (jQuery.trim(data.data)) {
											
											notification = createNotification(data.data.title, data.data.icon, data.data.body, data.data.url);

											// closes the web browser notification automatically after 5 secs
											setTimeout(function() {
												notification.close();
											}, 5000);
										}
									},
									error: function(jqXHR, textStatus, errorThrown) { }
								});
							}
						};

						function createNotification(title, icon, body, url) {
							var notification = new Notification(title, {
								icon: icon,
								body: body,
							});
							// url that needs to be opened on clicking the notification
							// finally everything boils down to click and visits right
							notification.onclick = function() {
								window.open(url);
							};
							return notification;
						}
					});
				</script>

			<?php

			// $hook is string value given add_menu_page function.
			if ( ! in_array( $hook, self::get_plugin_page_slugs(), true ) ) {
				return;
			}
			\wp_enqueue_style( 'advan-admin-style', \ADVAN_PLUGIN_ROOT_URL . 'css/admin/style.css', array(), \ADVAN_VERSION, 'all' );

			\wp_enqueue_script(
				'wp-api-fetch'
			);

			?>
			<script>
				window.onload= ( () => {
					jQuery('a.view-source').on('click', function(e) {
						this.href += '&width=' + ( window.innerWidth - 100 ) + '&height=' + ( window.innerHeight - 100 ) ;
					});
				});
			</script>
			<?php
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

			if ( \in_array( $action, array( 'edit_transient', 'edit_cron', 'new_transient', 'new_cron' ), true ) ) {
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
						\wp_json_encode( $settings )
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
				}

				if ( ! isset( self::$current_options['version'] ) || self::OPTIONS_VERSION !== self::$current_options['version'] ) {

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
		 * Returns current option or one stored in the defaults if not present in the current options.
		 *
		 * @param string $option - The name of the option to return value for.
		 *
		 * @return mixed
		 *
		 * @since 2.8.0
		 */
		public static function get_option( string $option ) {

			$current = self::get_current_options();

			if ( ! isset( $current[ $option ] ) ) {
				$current = self::get_default_options();

				if ( ! isset( $current[ $option ] ) ) {
					return null;
				}
			}

			return $current[ $option ];
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
					'menu_admins_only'                => true,
					'live_notifications_admin_bar'    => true,
					'environment_type_admin_bar'      => true,
					'protected_config_source'         => true,
					'keep_reading_error_log'          => false,
					'advana_requests_enable'          => true,
					'advana_http_requests_disable'    => false,
					'advana_rest_requests_disable'    => false,
					'no_rest_api_monitor'             => false,
					'no_wp_die_monitor'               => false,
					'keep_error_log_records_truncate' => 10,
					'plugin_version_switch_count'     => 3,
					'cron_module_enabled'             => true,
					'requests_module_enabled'         => true,
					'transients_module_enabled'       => true,
					'tables_module_enabled'           => true,
					'slack_notifications'             => array(
						'all' => array(
							'channel'    => '',
							'auth_token' => '',
						),
					),
					'telegram_notifications'          => array(
						'all' => array(
							'channel'    => '',
							'auth_token' => '',
						),
					),
					'severities'                      => array(
						'deprecated'     => array(
							'name'    => __( 'Deprecated', '0-day-analytics' ),
							'color'   => '#c4b576',
							'display' => true,
						),
						'error'          => array(
							'name'    => __( 'Error', '0-day-analytics' ),
							'color'   => '#ffb3b3',
							'display' => true,
						),
						'success'        => array(
							'name'    => __( 'Success', '0-day-analytics' ),
							'color'   => '#00ff00',
							'display' => true,
						),
						'info'           => array(
							'name'    => __( 'Info', '0-day-analytics' ),
							'color'   => '#aeaeec',
							'display' => true,
						),
						'notice'         => array(
							'name'    => __( 'Notice', '0-day-analytics' ),
							'color'   => '#feeb8e',
							'display' => true,
						),
						'warning'        => array(
							'name'    => __( 'Warning', '0-day-analytics' ),
							'color'   => '#ffff00',
							'display' => true,
						),
						'fatal'          => array(
							'name'    => __( 'Fatal', '0-day-analytics' ),
							'color'   => '#f09595',
							'display' => true,
						),
						'parse'          => array(
							'name'    => __( 'Parse', '0-day-analytics' ),
							'color'   => '#e3bb8d',
							'display' => true,
						),
						'user'           => array(
							'name'    => __( 'User', '0-day-analytics' ),
							'color'   => '#85b395',
							'display' => true,
						),
						'not set'        => array(
							'name'    => __( 'Not Set', '0-day-analytics' ),
							'color'   => '#7a6f72',
							'display' => true,
						),
						'request'        => array(
							'name'    => __( 'Request', '0-day-analytics' ),
							'color'   => '#759b71',
							'display' => true,
						),
						'rest_no_route'  => array(
							'name'    => __( 'Rest No Route', '0-day-analytics' ),
							'color'   => '#759b71',
							'display' => true,
						),
						'rest_forbidden' => array(
							'name'    => __( 'Rest Forbidden', '0-day-analytics' ),
							'color'   => '#759b71',
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

			if ( self::get_option( 'menu_admins_only' ) && ! \current_user_can( 'manage_options' ) ) {
				return;
			} else {

				$base = 'base';

				$base .= '64_en';

				$base .= 'code';

				self::$hook = \add_menu_page(
					\esc_html__( 'WP Control', '0-day-analytics' ),
					\esc_html__( 'WP Control', '0-day-analytics' ) . self::get_updates_count_html(),
					( ( self::get_option( 'menu_admins_only' ) ) ? 'manage_options' : 'read' ),
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
					\esc_html__( 'WP Control', '0-day-analytics' ),
					\esc_html__( 'Error Log viewer', '0-day-analytics' ),
					( ( self::get_option( 'menu_admins_only' ) ) ? 'manage_options' : 'read' ), // No capability requirement.
					self::MENU_SLUG,
					array( __CLASS__, 'analytics_options_page' ),
					1
				);

				/* Requests start */
				if ( self::get_option( 'requests_module_enabled' ) ) {
					Requests_List::menu_add();
				}
				/* Requests end */

				/* Crons start */
				if ( self::get_option( 'cron_module_enabled' ) ) {
					Crons_List::menu_add();
				}
				/* Crons end */

				/* Transients */
				if ( self::get_option( 'transients_module_enabled' ) ) {
					Transients_List::menu_add();
				}
				/* Transients end */

				/* Table */
				if ( self::get_option( 'tables_module_enabled' ) ) {
					Table_List::menu_add();
				}
				/* Table end */

				if ( ! is_a( WP_Helper::check_debug_status(), '\WP_Error' ) && ! is_a( WP_Helper::check_debug_log_status(), '\WP_Error' ) && self::get_option( 'live_notifications_admin_bar' ) ) {
					\add_action( 'admin_bar_menu', array( __CLASS__, 'live_notifications' ), 1000, 1 );
					\add_action( 'shutdown', array( __CLASS__, 'live_notifications_update' ), \PHP_INT_MAX );
					\add_action(
						'admin_enqueue_scripts',
						function() {
							\wp_enqueue_script(
								self::LIVE_NOTIF_JS_MODULE,
								\ADVAN_PLUGIN_ROOT_URL . 'js/admin/endpoints.js',
								array( 'wp-api-fetch', 'wp-dom-ready', 'wp-i18n' ),
								\ADVAN_VERSION,
								array( 'in_footer' => true )
							);
						}
					);
				}

				\add_action( 'load-' . self::$hook, array( __CLASS__, 'aadvana_common_help' ) );

				$settings_hook = \add_submenu_page(
					self::MENU_SLUG,
					\esc_html__( 'Settings', '0-day-analytics' ),
					\esc_html__( 'Settings', '0-day-analytics' ),
					'manage_options', // No capability requirement.
					self::SETTINGS_MENU_SLUG,
					array( __CLASS__, 'aadvana_show_options' ),
					301
				);

				\add_action( 'load-' . $settings_hook, array( __CLASS__, 'aadvana_settings_help' ) );

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
		 * Add Options Help
		 *
		 * Add help tab to options screen
		 *
		 * @since 1.1.0
		 */
		public static function aadvana_common_help() {

			$screen = WP_Helper::get_wp_screen();

			$suffix = '';

			if ( WP_Helper::is_multisite() ) {
				$suffix = '-network';
			}

			if ( Logs_List::PAGE_SLUG . $suffix === $screen->base || Logs_List::PAGE_SLUG === $screen->base ) {

				$screen->add_help_tab(
					array(
						'id'      => 'advanced-analytics-help-tab',
						'title'   => __( 'Help', '0-day-analytics' ),
						'content' => self::add_help_content_error_log(),
					)
				);
			}

			if ( Transients_List::PAGE_SLUG . $suffix === $screen->base || Transients_List::PAGE_SLUG === $screen->base ) {

				$screen->add_help_tab(
					array(
						'id'      => 'advanced-analytics-help-tab',
						'title'   => __( 'Help', '0-day-analytics' ),
						'content' => Transients_View::add_help_content_transients(),
					)
				);
			}

			if ( Crons_List::PAGE_SLUG . $suffix === $screen->base || Crons_List::PAGE_SLUG === $screen->base ) {

				$screen->add_help_tab(
					array(
						'id'      => 'advanced-analytics-help-tab',
						'title'   => __( 'Help', '0-day-analytics' ),
						'content' => Crons_View::add_help_content_crons(),
					)
				);
			}

			if ( Table_List::PAGE_SLUG . $suffix === $screen->base || Table_List::PAGE_SLUG === $screen->base ) {

				$screen->add_help_tab(
					array(
						'id'      => 'advanced-analytics-help-tab',
						'title'   => __( 'Table Info', '0-day-analytics' ),
						'content' => Table_View::add_config_content_table(),
					)
				);
				$screen->add_help_tab(
					array(
						'id'      => 'advanced-analytics-info-tab',
						'title'   => __( 'Help', '0-day-analytics' ),
						'content' => Table_View::add_help_content_table(),
					)
				);
			}

			if ( Requests_List::PAGE_SLUG . $suffix === $screen->base || Requests_List::PAGE_SLUG === $screen->base ) {

				$screen->add_help_tab(
					array(
						'id'      => 'advanced-analytics-help-tab',
						'title'   => __( 'Requests Table Info', '0-day-analytics' ),
						'content' => Requests_View::add_config_content_table(),
					)
				);
				$screen->add_help_tab(
					array(
						'id'      => 'advanced-analytics-info-tab',
						'title'   => __( 'Help', '0-day-analytics' ),
						'content' => Requests_View::add_help_content_table(),
					)
				);
			}

			$screen->set_help_sidebar( self::add_sidebar_content() );
		}

		/**
		 * Add Options Help
		 *
		 * Add help tab to options screen
		 *
		 * @since 1.1.0
		 */
		public static function aadvana_settings_help() {

			$add_style = '
			<style>
				.' . \esc_attr( self::PAGE_SLUG ) . ' #screen-meta-links {
					z-index: 10;
					position: relative;
				}
			</style>';

			$screen = WP_Helper::get_wp_screen();

			$screen->add_help_tab(
				array(
					'id'      => 'advanced-analytics-help-tab',
					'title'   => __( 'Help', '0-day-analytics' ),
					'content' => $add_style . self::add_help_content(),
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
		 * @since 1.9.8.1
		 */
		public static function add_help_content_error_log() {

			$help_text  = '<p>' . __( 'This screen allows you to see last occurred records (last are first), check their sources, see the code responsible / involved in given error.', '0-day-analytics' ) . '</p>';
			$help_text .= '<p>' . __( 'You can specify how many errors to be shown (up to 100), which columns to see or filter error by severity.', '0-day-analytics' ) . '</p>';
			$help_text .= '<p>' . __( 'You can truncate error log (clear it) or truncate it but leave last records (from settings you can specify how many records you want to be kept).', '0-day-analytics' ) . '</p>';
			$help_text .= '<p>' . __( 'Right under the list, there is a console-like window where you can see the raw error list, everything you select there (with mouse) is automatically copied in you clipboard, so you can use it in chat channel or share it easily.', '0-day-analytics' ) . '</p>';
			$help_text .= '<p>' . __( 'You can see the size of your log file and download it if you need to.', '0-day-analytics' ) . '</p>';

			return $help_text;
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

			$help_text  = '<p>' . __( 'This screen allows you to specify the options for the WP Control plugin.', '0-day-analytics' ) . '</p>';
			$help_text .= '<p>' . __( 'Here adjust the plugin to your specific needs.', '0-day-analytics' ) . '</p>';
			$help_text .= '<p>' . __( 'Remember to click the Save Changes button when on sexttings page for new settings to take effect.', '0-day-analytics' ) . '</p>';

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
			$help_text .= '<p><a href="https://wordpress.org/plugins/0-day-analytics/" target="__blank">' . __( 'Instructions', '0-day-analytics' ) . '</a></p>';
			$help_text .= '<p><a href="https://wordpress.org/support/plugin/0-day-analytics" target="__blank">' . __( 'Support Forum', '0-day-analytics' ) . '</a></p>';

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
				self::$settings_crons_link = \add_query_arg( 'page', Crons_List::CRON_MENU_SLUG, \network_admin_url( 'admin.php' ) );
			}

			return self::$settings_crons_link;
		}

		/**
		 * Returns the link to the WP admin settings page, based on the current WP install
		 *
		 * @return string
		 *
		 * @since 2.1.0
		 */
		public static function get_tables_page_link() {
			if ( '' === self::$settings_table_link ) {
				self::$settings_table_link = \add_query_arg( 'page', Table_List::TABLE_MENU_SLUG, \network_admin_url( 'admin.php' ) );
			}

			return self::$settings_table_link;
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
				self::$settings_transients_link = \add_query_arg( 'page', Transients_List::TRANSIENTS_MENU_SLUG, \network_admin_url( 'admin.php' ) );
			}

			return self::$settings_transients_link;
		}

		/**
		 * Returns the link to the WP admin settings page, based on the current WP install
		 *
		 * @return string
		 *
		 * @since 1.7.4
		 */
		public static function get_requests_page_link() {
			if ( '' === self::$settings_requests_link ) {
				self::$settings_requests_link = \add_query_arg( 'page', Requests_List::REQUESTS_MENU_SLUG, \network_admin_url( 'admin.php' ) );
			}

			return self::$settings_requests_link;
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

				'head-error-log-list'  => esc_html__( 'Error Log', '0-day-analytics' ),

				'error-log-list'       => array(
					'icon'  => 'list-view',
					'title' => esc_html__( 'Error Log Listing', '0-day-analytics' ),
				),

				'head-cron-list'       => esc_html__( 'Cron Log', '0-day-analytics' ),

				'cron-list'            => array(
					'icon'  => 'list-view',
					'title' => esc_html__( 'Cron options', '0-day-analytics' ),
				),

				'head-transients-list' => esc_html__( 'Transients Log', '0-day-analytics' ),

				'transient-list'       => array(
					'icon'  => 'list-view',
					'title' => esc_html__( 'Transient options', '0-day-analytics' ),
				),

				'head-requests-list'   => esc_html__( 'Requests Log', '0-day-analytics' ),

				'request-list'         => array(
					'icon'  => 'list-view',
					'title' => esc_html__( 'Request options', '0-day-analytics' ),
				),

				'head-table-list'      => esc_html__( 'Tables Viewer', '0-day-analytics' ),

				'table-list'           => array(
					'icon'  => 'editor-table',
					'title' => esc_html__( 'Tables options', '0-day-analytics' ),
				),

				'head-notifications'   => esc_html__( 'Notifications', '0-day-analytics' ),

				'notifications'        => array(
					'icon'  => 'bell',
					'title' => esc_html__( 'Notification options', '0-day-analytics' ),
				),

				'head-advanced'        => esc_html__( 'Advanced', '0-day-analytics' ),

				'advanced'             => array(
					'icon'  => 'admin-tools',
					'title' => esc_html__( 'Advanced', '0-day-analytics' ),
				),

				'backup'               => array(
					'icon'  => 'migrate',
					'title' => \esc_html__( 'Export/Import', '0-day-analytics' ),
				),

				'system-info'          => array(
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
			$data = null;

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

			return self::MENU_SLUG === $current_page || self::OPTIONS_PAGE_SLUG === $current_page || Crons_List::CRON_MENU_SLUG === $current_page || Transients_List::TRANSIENTS_MENU_SLUG === $current_page || Table_List::TABLE_MENU_SLUG === $current_page || self::SETTINGS_MENU_SLUG === $current_page || Requests_List::REQUESTS_MENU_SLUG === $current_page;
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

				$admin_bar->add_node(
					array(
						'id'    => 'aadvan-menu',
						'title' => '',
						'href'  => \add_query_arg( 'page', self::MENU_SLUG, \network_admin_url( 'admin.php' ) ),
						'meta'  => array( 'class' => 'aadvan-live-notif-item' ),
					)
				);
			}
		}

		/**
		 * Shows live notifications in the admin bar if there are candidates.
		 *
		 * @return void
		 *
		 * @since 1.1.0
		 */
		public static function live_notifications_update() {
			if ( \current_user_can( 'manage_options' ) && \is_admin() ) {
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
				</style>
				<?php
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
		 * @since 1.9.0 - Added $import parameter to allow importing settings, without interfering with the current options (everything related to wp-config manipulation is not stored in the settings).
		 */
		public static function collect_and_sanitize_options( array $post_array, bool $import = false ): array {
			if ( ! \current_user_can( 'manage_options' ) ) {
				\wp_die( \esc_html__( 'You do not have sufficient permissions to access this page.', '0-day-analytics' ) );
			}

			$advanced_options = array();

			$advanced_options['menu_admins_only'] = ( array_key_exists( 'menu_admins_only', $post_array ) ) ? filter_var( $post_array['menu_admins_only'], FILTER_VALIDATE_BOOLEAN ) : false;

			$advanced_options['live_notifications_admin_bar'] = ( array_key_exists( 'live_notifications_admin_bar', $post_array ) ) ? filter_var( $post_array['live_notifications_admin_bar'], FILTER_VALIDATE_BOOLEAN ) : false;

			$advanced_options['environment_type_admin_bar'] = ( array_key_exists( 'environment_type_admin_bar', $post_array ) ) ? filter_var( $post_array['environment_type_admin_bar'], FILTER_VALIDATE_BOOLEAN ) : false;

			$advanced_options['protected_config_source'] = ( array_key_exists( 'protected_config_source', $post_array ) ) ? filter_var( $post_array['protected_config_source'], FILTER_VALIDATE_BOOLEAN ) : false;

			foreach ( self::get_option( 'severities' ) as $name => $severity ) {
				$advanced_options['severities'][ $name ]['color'] = ( array_key_exists( 'severity_colors_' . $name . '_color', $post_array ) && ! empty( $post_array[ 'severity_colors_' . $name . '_color' ] ) ) ? \sanitize_text_field( $post_array[ 'severity_colors_' . $name . '_color' ] ) : ( ( isset( $post_array['severities'][ $name ]['color'] ) ) ? \sanitize_text_field( $post_array['severities'][ $name ]['color'] ) : $severity['color'] );

				$advanced_options['severities'][ $name ]['display'] = ( array_key_exists( 'severity_show_' . $name . '_display', $post_array ) && ! empty( $post_array[ 'severity_show_' . $name . '_display' ] ) ) ? true : ( ( isset( $post_array['severities'][ $name ]['display'] ) ) ? (bool) $post_array['severities'][ $name ]['display'] : false );

				$advanced_options['severities'][ $name ]['name'] = self::get_option( 'severities' )[ $name ]['name'];
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

			$advanced_options['keep_reading_error_log'] = ( array_key_exists( 'keep_reading_error_log', $post_array ) ) ? filter_var( $post_array['keep_reading_error_log'], \FILTER_VALIDATE_BOOLEAN ) : false;

			$advanced_options['advana_requests_enable'] = ( array_key_exists( 'advana_requests_enable', $post_array ) ) ? filter_var( $post_array['advana_requests_enable'], \FILTER_VALIDATE_BOOLEAN ) : false;

			$advanced_options['advana_http_requests_disable'] = ( array_key_exists( 'advana_http_requests_disable', $post_array ) ) ? filter_var( $post_array['advana_http_requests_disable'], \FILTER_VALIDATE_BOOLEAN ) : false;

			$advanced_options['advana_rest_requests_disable'] = ( array_key_exists( 'advana_rest_requests_disable', $post_array ) ) ? filter_var( $post_array['advana_rest_requests_disable'], \FILTER_VALIDATE_BOOLEAN ) : false;

			$advanced_options['no_rest_api_monitor'] = ( array_key_exists( 'no_rest_api_monitor', $post_array ) ) ? filter_var( $post_array['no_rest_api_monitor'], \FILTER_VALIDATE_BOOLEAN ) : false;

			$advanced_options['no_wp_die_monitor'] = ( array_key_exists( 'no_wp_die_monitor', $post_array ) ) ? filter_var( $post_array['no_wp_die_monitor'], \FILTER_VALIDATE_BOOLEAN ) : false;

			$advanced_options['keep_error_log_records_truncate'] = ( array_key_exists( 'keep_error_log_records_truncate', $post_array ) ) ? filter_var(
				$post_array['keep_error_log_records_truncate'],
				\FILTER_VALIDATE_INT,
				array(
					'options' => array(
						'min_range' => 1,
						'max_range' => 100,
					),
				)
			) : 10;

			$advanced_options['plugin_version_switch_count'] = ( array_key_exists( 'plugin_version_switch_count', $post_array ) ) ? filter_var(
				$post_array['plugin_version_switch_count'],
				\FILTER_VALIDATE_INT,
				array(
					'options' => array(
						'min_range' => 1,
						'max_range' => 10,
					),
				)
			) : 3;

			// Modules start.
			$advanced_options['cron_module_enabled']       = ( array_key_exists( 'cron_module_enabled', $post_array ) ) ? filter_var( $post_array['cron_module_enabled'], \FILTER_VALIDATE_BOOLEAN ) : false;
			$advanced_options['requests_module_enabled']   = ( array_key_exists( 'requests_module_enabled', $post_array ) ) ? filter_var( $post_array['requests_module_enabled'], \FILTER_VALIDATE_BOOLEAN ) : false;
			$advanced_options['transients_module_enabled'] = ( array_key_exists( 'transients_module_enabled', $post_array ) ) ? filter_var( $post_array['transients_module_enabled'], \FILTER_VALIDATE_BOOLEAN ) : false;
			$advanced_options['tables_module_enabled']     = ( array_key_exists( 'tables_module_enabled', $post_array ) ) ? filter_var( $post_array['tables_module_enabled'], \FILTER_VALIDATE_BOOLEAN ) : false;
			// Modules end.

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

					// Clear the flag for keep reading the error log if WP settings are disabled (because at this point they are enabled).
					$advanced_options['keep_reading_error_log'] = false;
				}

				$wp_cron_disable = ( array_key_exists( 'wp_cron_disable', $post_array ) ) ? filter_var( $post_array['wp_cron_disable'], FILTER_VALIDATE_BOOLEAN ) : false;

				Config_Transformer::update( 'constant', 'DISABLE_WP_CRON', $wp_cron_disable, self::$config_args );

				$block_external_requests = ( array_key_exists( 'block_external_requests', $post_array ) ) ? filter_var( $post_array['block_external_requests'], FILTER_VALIDATE_BOOLEAN ) : false;

				Config_Transformer::update( 'constant', 'WP_HTTP_BLOCK_EXTERNAL', $block_external_requests, self::$config_args );

				@clearstatcache( false, File_Helper::get_wp_config_file_path() );
			}

			self::$current_options = $advanced_options;

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
				foreach ( self::get_option( 'severities' ) as $name => $severity ) {
					if ( ! $severity['display'] ) {
						self::$disabled_severities[] = $name;
					}
				}
			}

			return self::$disabled_severities;
		}

		/**
		 * Returns the enabled severities levels and stores them in the internal class cache.
		 *
		 * @return array
		 *
		 * @since 1.1.0
		 */
		public static function get_enabled_severities(): array {
			if ( null === self::$enabled_severities ) {
				self::$enabled_severities = array();
				foreach ( self::get_option( 'severities' ) as $name => $severity ) {
					if ( $severity['display'] ) {
						self::$enabled_severities[] = $name;
					}
				}
			}

			return self::$enabled_severities;
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

			if ( WP_Helper::get_wp_screen() && ( in_array( WP_Helper::get_wp_screen()->base, self::get_plugin_page_slugs(), true ) ) ) {

				$link        = 'https://github.com/sdobreff';
				$footer_link = 'https://wordpress.org/plugins/0-day-analytics/';

				return \sprintf(
				/* translators: This text is prepended by a link to Melapress's website, and appended by a link to Melapress's website. */
					'<a href="%1$s" target="_blank">' . ADVAN_NAME . '</a> ' . __( 'is developed and maintained by', 'wp-security-audit-log' ) . ' <a href="%2$s" target="_blank">Stoil Dobreff</a>.',
					$footer_link,
					$link
				) . '<br><br>' . sprintf(
				/* translators: 1: Plugin Name, 3: Plugin review URL */
					__( 'If you like <strong><ins>%1$s</ins></strong>. please leave us a <a target="_blank" style="color:#f9b918" href="%2$s">★★★★★</a> rating. A huge thank you in advance!', 'error-log-viewer-wp' ),
					\esc_attr( ADVAN_NAME ),
					\esc_url_raw( 'https://wordpress.org/support/view/plugin-reviews/0-day-analytics?filter=5' ),
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

			if ( WP_Helper::get_wp_screen() && ( in_array( WP_Helper::get_wp_screen()->base, self::get_plugin_page_slugs(), true ) ) ) {

				return sprintf(
					'<a href="%s">%s</a> &#8729; <a href="%s">%s</a> &#8729; <a href="%s">%s</a> &#8729; <a href="%s">%s</a> &#8729; <a href="%s">%s</a> &#8729; %s %s',
					self::get_error_log_page_link(),
					__( 'Error Log', 'wp-security-audit-log' ),
					self::get_crons_page_link(),
					__( 'Crons', 'wp-security-audit-log' ),
					self::get_tables_page_link(),
					__( 'Tables', 'wp-security-audit-log' ),
					self::get_transients_page_link(),
					__( 'Transients', 'wp-security-audit-log' ),
					self::get_requests_page_link(),
					__( 'Requests', 'wp-security-audit-log' ),
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

			$suffix = '';

			if ( WP_Helper::is_multisite() ) {
				$suffix = '-network';
			}

			return array_unique(
				array(
					self::PAGE_SLUG . $suffix,
					Logs_List::PAGE_SLUG . $suffix,
					Requests_List::PAGE_SLUG . $suffix,
					Transients_List::PAGE_SLUG . $suffix,
					Crons_List::PAGE_SLUG . $suffix,
					Table_List::PAGE_SLUG . $suffix,
					self::PAGE_SLUG,
					Logs_List::PAGE_SLUG,
					Requests_List::PAGE_SLUG,
					Transients_List::PAGE_SLUG,
					Crons_List::PAGE_SLUG,
					Table_List::PAGE_SLUG,
				)
			);
		}

		/**
		 * Sets severity as enabled
		 *
		 * @param string $severity - The name of the severity to enable.
		 *
		 * @return void
		 *
		 * @since 1.9.5.1
		 */
		public static function enable_severity( string $severity ): void {
			if ( ! isset( self::$current_options['severities'][ $severity ] ) ) {
				return;
			}

			self::$current_options['severities'][ $severity ]['display'] = true;

			self::store_options( self::$current_options );
		}

		/**
		 * Sets severity as disabled
		 *
		 * @param string $severity - The name of the severity to disable.
		 *
		 * @return void
		 *
		 * @since 1.9.5.1
		 */
		public static function disable_severity( string $severity ): void {
			if ( ! isset( self::$current_options['severities'][ $severity ] ) ) {
				return;
			}

			self::$current_options['severities'][ $severity ]['display'] = false;

			self::store_options( self::$current_options );
		}
	}
}
