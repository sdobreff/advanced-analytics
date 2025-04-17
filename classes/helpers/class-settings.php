<?php
/**
 * Class: Determine the context in which the plugin is executed.
 *
 * Helper class to determine the proper status of the request.
 *
 * @package advanced-analytics
 *
 * @since 1.0.0
 */

declare(strict_types=1);

namespace ADVAN\Helpers;

use ADVAN\Lists\Logs_List;
use ADVAN\Controllers\Error_Log;
use ADVAN\Settings\Settings_Builder;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\ADVAN\Helpers\Settings' ) ) {
	/**
	 * Responsible for proper context determination.
	 *
	 * @since 2.0.0
	 */
	class Settings {

		public const OPTIONS_VERSION = '3'; // Incremented when the options array changes.

		public const MENU_SLUG = 'advan_logs';

		public const SETTINGS_MENU_SLUG = 'advan_logs_settings';

		public const OPTIONS_PAGE_SLUG = 'analytics-options-page';

		public const SETTINGS_FILE_FIELD = 'aadvana_import_file';

		public const SETTINGS_FILE_UPLOAD_FIELD = 'aadvana_import_upload';

		public const SETTINGS_VERSION = 'aadvana_plugin_version';

		/**
		 * Holds cache for disabled severity levels
		 *
		 * @var array
		 *
		 * @since 
		 */
		private static $disabled_severities = null;

		/**
		 * Default wp_config.php writer configs
		 *
		 * @var array
		 *
		 * @since 
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
		 * @since 2.0.0
		 */
		private static $current_options = array();

		/**
		 * The name of the hook for the menu.
		 *
		 * @var string
		 *
		 * @since 
		 */
		private static $hook = null;

		/**
		 * Array with the default options
		 *
		 * @var array
		 *
		 * @since 2.0.0
		 */
		private static $default_options = array();

		/**
		 * The link to the WP admin settings page
		 *
		 * @var string
		 */
		private static $settings_page_link = '';

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
		 * @since 2.0.0
		 */
		public static function init() {

			self::get_current_options();

			// Hook me up.
			\add_action( 'admin_menu', array( __CLASS__, 'add_options_page' ) ); // Insert the Admin panel.
			if ( \is_multisite() ) {
				\add_action( 'network_admin_menu', array( __CLASS__, 'add_options_page' ) ); // Insert the Admin on multisite install panel.
			}

			/**
			 * Draws the save button in the settings
			 */
			\add_action( 'aadvana_settings_save_button', array( __CLASS__, 'save_button' ) );
		}

		/**
		 * Returns the current options.
		 * Fills the current options array with values if empty.
		 *
		 * @return array
		 *
		 * @since 2.0.0
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
		 * @since 
		 */
		public static function store_options( array $options ): void {
			\update_option( ADVAN_SETTINGS_NAME, $options );
		}

		/**
		 * Returns the default plugin options
		 *
		 * @return array
		 *
		 * @since 2.0.0
		 */
		public static function get_default_options(): array {

			if ( empty( self::$default_options ) ) {
				// Define default options.
				self::$default_options = array(
					'menu_admins_only' => true,
					'severities'       => array(
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
		 * @since 
		 */
		public static function get_main_menu_page_hook() {
			return self::$hook;
		}

		/**
		 * Add to Admin
		 *
		 * Add the options page to the admin menu
		 *
		 * @since 2.0.0
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
					\esc_html__( 'Analyze', '0-day-analytics' ) . self::get_updates_count_html(),
					( ( self::get_current_options()['menu_admins_only'] ) ? 'manage_options' : 'read' ),
					self::MENU_SLUG,
					array( __CLASS__, 'analytics_options_page' ),
					'data:image/svg+xml;base64,' . $base( file_get_contents( \ADVAN_PLUGIN_ROOT . 'assets/icon.svg' ) ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
					30
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
					\esc_html__( 'Log viewer', '0-day-analytics' ),
					( ( self::get_current_options()['menu_admins_only'] ) ? 'manage_options' : 'read' ), // No capability requirement.
					self::MENU_SLUG,
					array( __CLASS__, 'analytics_options_page' ),
					1
				);

				\add_action( 'admin_bar_menu', array( __CLASS__, 'live_notifications' ), 1000, 1 );
				\add_action( 'wp_ajax_wsal_adminbar_events_refresh', array( __CLASS__, 'wsal_adminbar_events_refresh__premium_only' ) );

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
						$wpdb->prepare( 'SELECT option_name, option_value FROM ' . $wpdb->options . ' WHERE option_name = %s', \ADVAN_SETTINGS_NAME )
					);

					header( 'Cache-Control: public, must-revalidate' );
					header( 'Pragma: hack' );
					header( 'Content-Type: text/plain' );
					header( 'Content-Disposition: attachment; filename="' . ADVAN_TEXTDOMAIN . '-options-' . gmdate( 'dMy' ) . '.dat"' );
					echo \wp_json_encode( unserialize( $stored_options[0]->option_value ), array( 'allowed_classes' => false ) );
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
							\update_option( ADVAN_SETTINGS_NAME, self::collect_and_sanitize_options( $options ) );
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
		 * @since 
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
		 * @since 2.0.0
		 */
		public static function analytics_options_page() {
			self::render();
		}

		/**
		 * Displays the settings page.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function render() {

			\wp_enqueue_style( 'advan-admin-style', \ADVAN_PLUGIN_ROOT_URL . 'css/admin/style.css', array(), \ADVAN_VERSION, 'all' );
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
			</script>
			<?php
			$events_list = new Logs_List( array() );
			$events_list->prepare_items();

			$events_list->display();

			// self::aadvana_show_options();
		}

		/**
		 * Add Options Help
		 *
		 * Add help tab to options screen
		 *
		 * @since 2.0.0
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
		 * @since 2.0.0
		 */
		public static function add_help_content() {

			$help_text  = '<p>' . __( 'This screen allows you to specify the default options for the Awesome Footnotes plugin.', '0-day-analytics' ) . '</p>';
			$help_text .= '<p>' . __( 'The identifier is what appears when a footnote is inserted into your page contents. The back-link appear after each footnote, linking back to the identifier.', '0-day-analytics' ) . '</p>';
			$help_text .= '<p>' . __( 'Remember to click the Save Changes button at the bottom of the screen for new settings to take effect.', '0-day-analytics' ) . '</p></h4>';

			return $help_text;
		}

		/**
		 * Options Help Sidebar
		 *
		 * Add a links sidebar to the options help
		 *
		 * @return string  Help Text
		 *
		 * @since 2.0.0
		 */
		public static function add_sidebar_content() {

			$help_text  = '<p><strong>' . __( 'For more information:', '0-day-analytics' ) . '</strong></p>';
			$help_text .= '<p><a href="https://wordpress.org/plugins/awesome-footnotes/">' . __( 'Instructions', '0-day-analytics' ) . '</a></p>';
			$help_text .= '<p><a href="https://wordpress.org/support/plugin/awesome-footnotes">' . __( 'Support Forum', '0-day-analytics' ) . '</a></p></h4>';

			return $help_text;
		}

		/**
		 * Returns the link to the WP admin settings page, based on the current WP install
		 *
		 * @return string
		 *
		 * @since 1.6.0
		 */
		public static function get_settings_page_link() {
			if ( '' === self::$settings_page_link ) {
				self::$settings_page_link = \add_query_arg( 'page', self::MENU_SLUG, \network_admin_url( 'admin.php' ) );
			}

			return self::$settings_page_link;
		}

		/**
		 * Shows the save button in the settings
		 *
		 * @return void
		 *
		 * @since 2.0.0
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
		 * @since 2.0.0
		 */
		public static function aadvana_show_options() {

			\wp_enqueue_script( 'aadvana-admin-scripts', \ADVAN_PLUGIN_ROOT_URL . 'js/admin/aadvana-settings.js', array( 'jquery', 'jquery-ui-sortable', 'jquery-ui-draggable', 'wp-color-picker', 'jquery-ui-autocomplete' ), \ADVAN_VERSION, false );

			\wp_enqueue_style( 'advan-admin-style', \ADVAN_PLUGIN_ROOT_URL . 'css/admin/style.css', array(), \ADVAN_VERSION, 'all' );
			\wp_enqueue_media();

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

				'head-error-log-list' => esc_html__( 'Error Log', 'awesome-footnotes' ),

				'error-log-list'      => array(
					'icon'  => 'list-view',
					'title' => esc_html__( 'Error Log Listing', '0-day-analytics' ),
				),

				'head-advanced'       => esc_html__( 'Advanced', 'awesome-footnotes' ),

				'advanced'            => array(
					'icon'  => 'admin-tools',
					'title' => esc_html__( 'Advanced', 'awesome-footnotes' ),
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
		 * @since 2.0.0
		 */
		public static function build_options_tabs(): array {

			$settings_tabs = array(

				'general'       => array(
					'icon'  => 'admin-generic',
					'title' => \esc_html__( 'General', '0-day-analytics' ),
				),

				'logo'          => array(
					'icon'  => 'lightbulb',
					'title' => \esc_html__( 'Logo', '0-day-analytics' ),
				),

				'posts'         => array(
					'icon'  => 'media-text',
					'title' => \esc_html__( 'Article types', '0-day-analytics' ),
				),

				'footer'        => array(
					'icon'  => 'editor-insertmore',
					'title' => \esc_html__( 'Footer', '0-day-analytics' ),
				),

				'seo'           => array(
					'icon'  => 'google',
					'title' => \esc_html__( 'SEO', '0-day-analytics' ),
				),

				'optimization'  => array(
					'icon'  => 'dashboard',
					'title' => \esc_html__( 'Optimization', '0-day-analytics' ),
				),

				'miscellaneous' => array(
					'icon'  => 'shortcode',
					'title' => \esc_html__( 'Miscellaneous', '0-day-analytics' ),
				),
			);

			$settings_tabs['backup'] = array(
				'icon'  => 'migrate',
				'title' => \esc_html__( 'Export/Import', '0-day-analytics' ),
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
		 * @since 2.0.0
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
		 * @since 
		 */
		public static function set_current_options( array $options ) {
			return self::$current_options = $options; // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.Found
		}

		/**
		 * Checks if current page is plugin settings page
		 *
		 * @return boolean
		 *
		 * @since 2.0.0
		 */
		public static function is_plugin_settings_page() {

			$current_page = ! empty( $_REQUEST['page'] ) ? \sanitize_text_field( \wp_unslash( $_REQUEST['page'] ) ) : '';

			return self::MENU_SLUG === $current_page || self::OPTIONS_PAGE_SLUG === $current_page || self::SETTINGS_MENU_SLUG === $current_page;
		}

		/**
		 * Extracts the current version of the plugin
		 *
		 * @return string
		 *
		 * @since 2.0.0
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
		 * @since 2.0.0
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
		 * @since 
		 */
		public static function live_notifications( $admin_bar ) {
			if ( \current_user_can( 'manage_options' ) && \is_admin() ) {

				$logs = Logs_List::get_error_items( false );

				$event = ( isset( $logs[0] ) ) ? $logs[0] : null;

				if ( $event && ! empty( $event ) ) {

					?>
					<style>
						#wp-admin-bar-aadvan-menu {
							overflow: auto;
							text-overflow: ellipsis;
							max-width: 50%;
							height: 30px;
							width: 400px;
						}
						#wpadminbar:not(.mobile) .ab-top-menu > li#wp-admin-bar-aadvan-menu:hover > .ab-item {
							background: #d7dce0;
							color: #42425d !important;
						}
						<?php
						foreach ( self::get_current_options()['severities'] as $class => $properties ) {
							echo '.aadvan-live-notif-item.' . \esc_attr( $class ) . '{ background: ' . \esc_attr( $properties['color'] ) . ' !important; }';
							echo '.aadvan-live-notif-item.' . \esc_attr( $class )  . ' a { color: #42425d !important; }';
						}
						?>
					</style>
					<?php

					$date_time_format = \get_option( 'date_format' ) . ' ' . \get_option( 'time_format' );
					$time             = \wp_date( $date_time_format, $event['timestamp'] );

					$classes = '';
					if ( isset( $event['severity'] ) && ! empty( $event['severity'] ) ) {
						$classes .= ' ' . $event['severity'];
					}
					$admin_bar->add_node(
						array(
							'id'    => 'aadvan-menu',
							'title' => ( ( ! empty( $time ) ) ? '<b><i>' . $time . '</i></b>' . ' : ' : '' ) . ( ( ! empty( $event['severity'] ) ) ? $event['severity'] . ' : ' : '' ) . $event['message'],
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
		 *
		 * @return array
		 *
		 * @since 2.0.0
		 */
		public static function collect_and_sanitize_options( array $post_array ): array {
			if ( ! \current_user_can( 'manage_options' ) ) {
				\wp_die( \esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-footnotes' ) );
			}

			$advanced_options = array();

			$advanced_options['menu_admins_only'] = ( array_key_exists( 'menu_admins_only', $post_array ) ) ? filter_var( $post_array['menu_admins_only'], FILTER_VALIDATE_BOOLEAN ) : false;

			foreach ( self::get_current_options()['severities'] as $name => $severity ) {
				$advanced_options['severities'][ $name ]['color']   = ( array_key_exists( 'severity_colors_' . $name . '_color', $post_array ) && ! empty( $post_array[ 'severity_colors_' . $name . '_color' ] ) ) ? \sanitize_text_field( $post_array[ 'severity_colors_' . $name . '_color' ] ) : ( ( isset( $advanced_options['severities'][ $name ]['color'] ) ) ? $advanced_options['severities'][ $name ]['color'] : $severity['color'] );
				$advanced_options['severities'][ $name ]['display'] = ( array_key_exists( 'severity_show_' . $name . '_display', $post_array ) && ! empty( $post_array[ 'severity_show_' . $name . '_display' ] ) ) ? true : false;

				$advanced_options['severities'][ $name ]['name'] = self::get_current_options()['severities'][ $name ]['name'];
			}

			self::$current_options = $advanced_options;

			$wp_debug_enable = ( array_key_exists( 'wp_debug_enable', $post_array ) ) ? filter_var( $post_array['wp_debug_enable'], FILTER_VALIDATE_BOOLEAN ) : false;

			Config_Transformer::update( 'constant', 'WP_DEBUG', $wp_debug_enable, self::$config_args );

			$wp_debug_display_enable = ( array_key_exists( 'wp_debug_display_enable', $post_array ) ) ? filter_var( $post_array['wp_debug_display_enable'], FILTER_VALIDATE_BOOLEAN ) : false;

			Config_Transformer::update( 'constant', 'WP_DEBUG_DISPLAY', $wp_debug_display_enable, self::$config_args );

			$wp_debug_log_enable = ( array_key_exists( 'wp_debug_log_enable', $post_array ) ) ? filter_var( $post_array['wp_debug_log_enable'], FILTER_VALIDATE_BOOLEAN ) : false;

			Config_Transformer::update( 'constant', 'WP_DEBUG_LOG', $wp_debug_log_enable, self::$config_args );

			if ( $wp_debug_log_enable ) {
				$wp_debug_log_generate = ( array_key_exists( 'wp_debug_log_file_generate', $post_array ) ) ? filter_var( $post_array['wp_debug_log_file_generate'], FILTER_VALIDATE_BOOLEAN ) : false;

				$wp_debug_log_filename = ( array_key_exists( 'wp_debug_log_filename', $post_array ) ) ? \sanitize_text_field( \wp_unslash( $post_array['wp_debug_log_filename'] ) ) : '';

				if ( ! empty( $wp_debug_log_filename ) && Error_Log::autodetect() !== $wp_debug_log_filename ) {

					if ( is_writable( \dirname( $wp_debug_log_filename ) ) ) {
						$file_name = \dirname( $wp_debug_log_filename ) . \DIRECTORY_SEPARATOR . 'debug_' . File_Helper::generate_random_file_name() . '.log';

						Config_Transformer::update( 'constant', 'WP_DEBUG_LOG', $file_name, self::$config_args );
					} elseif ( \is_string( Error_Log::autodetect() ) ) {
						Config_Transformer::update( 'constant', 'WP_DEBUG_LOG', Error_Log::autodetect(), self::$config_args );
					}
				} elseif ( \is_string( Error_Log::autodetect() ) ) {
					Config_Transformer::update( 'constant', 'WP_DEBUG_LOG', Error_Log::autodetect(), self::$config_args );
				}

				if ( $wp_debug_log_generate ) {
					$file_name = \WP_CONTENT_DIR . \DIRECTORY_SEPARATOR . 'debug_' . File_Helper::generate_random_file_name() . '.log';

					Config_Transformer::update( 'constant', 'WP_DEBUG_LOG', $file_name, self::$config_args );
				}
			}

			return $advanced_options;
		}

		/**
		 * Returns the disabled severities levels and stores them in the internal class cache.
		 *
		 * @return array
		 *
		 * @since 
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
	}
}
