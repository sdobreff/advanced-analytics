<?php
/**
 * Responsible for plugin initialization.
 *
 * @package    advanced-analytics
 * @copyright  %%YEAR%% ADvanced Analytics
 * @license    https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 * @link       https://wordpress.org/plugins/advanced-analytics/
 *
 * @since      1.0.0
 */

declare(strict_types=1);

namespace ADVAN;

use ADVAN\Helpers\Ajax;
use ADVAN\Lists\Logs_List;
use ADVAN\Helpers\Settings;
use ADVAN\Helpers\Ajax_Helper;
use ADVAN\Migration\Migration;
use ADVAN\Controllers\Pointers;
use ADVAN\Helpers\Review_Plugin;
use ADVAN\Helpers\Context_Helper;
use ADVAN\Controllers\Integrations;
use ADVAN\Helpers\WP_Error_Handler;
use ADVAN\Controllers\Footnotes_Formatter;

if ( ! class_exists( '\ADVAN\Advanced_Analytics' ) ) {

	/**
	 * Main plugin class
	 *
	 * @since 2.0.0
	 */
	class Advanced_Analytics {

		// public const REDIRECT_OPTION_NAME = 'aadvana_plugin_do_activation_redirect';

		/**
		 * Inits the class and hooks
		 *
		 * @since 2.0.0
		 */
		public static function init() {
			if ( \is_admin() && ! \wp_doing_ajax() ) {
				Migration::migrate();

				// \add_action( 'admin_init', array( __CLASS__, 'plugin_redirect' ) );

				// \add_action( 'current_screen', array( '\AWEF\Helpers\Upgrade_Notice', 'init' ) );

				// Setup screen options. Needs to be here as admin_init hook it too late.
				\add_filter( 'set-screen-option', array( Logs_List::class, 'set_screen_option' ), 10, 3 );

				\add_filter( 'plugin_action_links', array( __CLASS__, 'add_settings_link' ), 10, 2 );
				// \add_filter( 'plugin_row_meta', array( __CLASS__, 'plugin_meta' ), 10, 2 );

				// Review_Plugin::init();

				// Integrations::init();
				Settings::init();

				// Pointers::init();

				// Hide all unrelated to the plugin notices on the plugin admin pages.
				\add_action( 'admin_print_scripts', array( __CLASS__, 'hide_unrelated_notices' ) );
			} else {
				// Footnotes_Formatter::init();
			}


			// if ( \WP_DEBUG ) {
			// set_error_handler( 'WP_Error_Handler::handle_error' );
			// }
			Ajax_Helper::init();
		}

		/**
		 * Add Settings link to plugin list
		 *
		 * Add a Settings link to the options listed against this plugin
		 *
		 * @param array  $links  Current links.
		 * @param string $file   File in use.
		 *
		 * @return string          Links, now with settings added.
		 *
		 * @since 2.0.0
		 */
		public static function add_settings_link( $links, $file ) {
			if ( ADVAN_PLUGIN_BASENAME === $file ) {
				$settings_link = '<a href="' . Settings::get_settings_page_link() . '">' . __( 'Settings', 'advanced-analytics' ) . '</a>';
				array_unshift( $links, $settings_link );
			}

			return $links;
		}

		/**
		 * Add meta to plugin details
		 *
		 * Add options to plugin meta line
		 *
		 * @param string $links  Current links.
		 * @param string $file   File in use.
		 *
		 * @return string Links, now with settings added.
		 *
		 * @since 2.0.0
		 */
		public static function plugin_meta( $links, $file ) {

			if ( false !== strpos( $file, 'awesome-footnotes.php' ) ) {
				$links = array_merge( $links, array( '<a href="https://wordpress.org/support/plugin/awesome-footnotes">' . __( 'Support', 'advanced-analytics' ) . '</a>' ) );
			}

			return $links;
		}

		/**
		 * Check whether we are on an admin and plugin page.
		 *
		 * @since 2.0.0
		 *
		 * @return bool
		 */
		public static function is_admin_page() {

			return \is_admin() && ( false !== Settings::is_plugin_settings_page() );
		}

		/**
		 * Remove all non-WP Mail SMTP plugin notices from our plugin pages.
		 *
		 * @since 2.0.0
		 */
		public static function hide_unrelated_notices() {
			// Bail if we're not on our screen or page.
			if ( ! self::is_admin_page() ) {
				return;
			}

			self::remove_unrelated_actions( 'user_admin_notices' );
			self::remove_unrelated_actions( 'admin_notices' );
			self::remove_unrelated_actions( 'all_admin_notices' );
			self::remove_unrelated_actions( 'network_admin_notices' );
		}

		/**
		 * Remove all notices from the our plugin pages based on the provided action hook.
		 *
		 * @since 2.0.0
		 *
		 * @param string $action - The name of the action.
		 */
		public static function remove_unrelated_actions( $action ) {
			global $wp_filter;

			if ( empty( $wp_filter[ $action ]->callbacks ) || ! is_array( $wp_filter[ $action ]->callbacks ) ) {
				return;
			}

			foreach ( $wp_filter[ $action ]->callbacks as $priority => $hooks ) {
				foreach ( $hooks as $name => $arr ) {
					if (
					( // Cover object method callback case.
						is_array( $arr['function'] ) &&
						isset( $arr['function'][0] ) &&
						is_object( $arr['function'][0] ) &&
						false !== strpos( ( get_class( $arr['function'][0] ) ), 'ADVAN' )
					) ||
					( // Cover class static method callback case.
						! empty( $name ) &&
						false !== strpos( ( $name ), 'ADVAN' )
					)
					) {
						continue;
					}

					unset( $wp_filter[ $action ]->callbacks[ $priority ][ $name ] );
				}
			}
		}

		/**
		 * Adds a powered-by message in the footer of the page.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function powered_by() {
			if ( Context_Helper::is_front() ) {
				?><!--
				<?php
				printf(
					/* Translators: Plugin link. */
					\esc_html__( 'Proudly powered by %s', 'advanced-analytics' ),
					'<a href="' . esc_url( __( 'https://wordpress.org/plugins/awesome-footnotes/', 'advanced-analytics' ) ) . '" rel="nofollow">' . \esc_attr( ADVAN_NAME ) . '</a>'
				);
				?>
				-->
				<?php
			}
		}

		/**
		 * Registers a plugin redirection on activate setting.
		 *
		 * @return void
		 *
		 * @since 2.4.0
		 */
		public static function plugin_activate() {
			\add_option( self::REDIRECT_OPTION_NAME, true );
		}

		/**
		 * Redirects the plugin to its settings page if it was just activated.
		 *
		 * @return void
		 *
		 * @since 2.4.0
		 */
		public static function plugin_redirect() {
			if ( \get_option( self::REDIRECT_OPTION_NAME, false ) ) {
				\delete_option( self::REDIRECT_OPTION_NAME );
				if ( ! isset( $_REQUEST['activate-multi'] ) ) {
					\wp_safe_redirect( add_query_arg( 'page', Settings::MENU_SLUG, get_admin_url( get_current_blog_id(), 'admin.php' ) ) );
				}
			}
		}


		public static function shutdown() {
			$errfile = 'unknown file';
			$errstr  = 'shutdown';
			$errno   = E_CORE_ERROR;
			$errline = 0;

			$error = error_get_last();

			if ( $error !== null ) {
				$errno   = $error['type'];
				$errfile = $error['file'];
				$errline = $error['line'];
				$errstr  = $error['message'];

				// error_mail(format_error( $errno, $errstr, $errfile, $errline));
			}
		}

	}
}
