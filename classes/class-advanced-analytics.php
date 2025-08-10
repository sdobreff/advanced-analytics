<?php
/**
 * Responsible for plugin initialization.
 *
 * @package    advanced-analytics
 * @copyright  %%YEAR%% WP Control
 * @license    https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 * @link       https://wordpress.org/plugins/advanced-analytics/
 *
 * @since      1.0.0
 */

declare(strict_types=1);

namespace ADVAN;

use ADVAN\Controllers\Cron_Jobs;
use ADVAN\Lists\Logs_List;
use ADVAN\Helpers\Settings;
use ADVAN\Helpers\WP_Helper;
use ADVAN\Helpers\Ajax_Helper;
use ADVAN\Migration\Migration;
use ADVAN\Controllers\Pointers;
use ADVAN\Helpers\Review_Plugin;
use ADVAN\Lists\Transients_List;
use ADVAN\Helpers\Context_Helper;
use ADVAN\Helpers\Upgrade_Notice;
use ADVAN\Controllers\Display_Environment_Type;
use ADVAN\Controllers\Error_Log;
use ADVAN\Lists\Requests_List;
use ADVAN\Lists\Table_List;

if ( ! class_exists( '\ADVAN\Advanced_Analytics' ) ) {

	/**
	 * Main plugin class
	 *
	 * @since 2.0.0
	 */
	class Advanced_Analytics {

		public const REDIRECT_OPTION_NAME = ADVAN_PREFIX . 'plugin_do_activation_redirect';

		/**
		 * Inits the class and hooks
		 *
		 * @since 2.0.0
		 */
		public static function init() {
			if ( \is_admin() && ! \wp_doing_ajax() ) {

				Migration::migrate();

				\add_action( 'admin_init', array( __CLASS__, 'plugin_redirect' ) );

				// Setup screen options. Needs to be here as admin_init hook is too late. Per page set is below.
				\add_filter( 'set-screen-option', array( Logs_List::class, 'set_screen_option' ), 10, 3 );

				\add_filter( 'set-screen-option', array( Transients_List::class, 'set_screen_option' ), 10, 3 );

				\add_filter( 'set-screen-option', array( Requests_List::class, 'set_screen_option' ), 10, 3 );

				\add_filter( 'set-screen-option', array( Table_List::class, 'set_screen_option' ), 10, 3 );

				// End screen options set. per_page.

				\add_filter( 'plugin_action_links', array( __CLASS__, 'add_settings_link' ), 10, 2 );
				\add_filter( 'plugin_row_meta', array( __CLASS__, 'plugin_meta' ), 10, 2 );

				// Review_Plugin::init();

				\add_filter( 'init', array( Settings::class, 'init' ) );

				Pointers::init();

				\add_filter( 'admin_footer_text', array( Settings::class, 'admin_footer_text' ), PHP_INT_MAX );
				\add_filter( 'update_footer', array( Settings::class, 'admin_footer_version_text' ), PHP_INT_MAX );

				// Hide all unrelated to the plugin notices on the plugin admin pages.
				\add_action( 'admin_print_scripts', array( __CLASS__, 'hide_unrelated_notices' ) );

				// Check for urgent upgrades.
				\add_action( 'current_screen', array( Upgrade_Notice::class, 'init' ) );

				if ( Settings::get_option( 'environment_type_admin_bar' ) ) {
					\add_action( 'init', array( Display_Environment_Type::class, 'init' ) );
				}
			}

			// if ( \WP_DEBUG ) {
				// \set_error_handler( '\ADVAN\Helpers\WP_Error_Handler::handle_error' );

				// \register_shutdown_function(
				// function() {
				// $error = error_get_last();
				// if ( $error && 1 === $error['type'] ) {
				// Implement notifications here.

				// Advanced_Analytics::shutdown();
				// }
				// }
				// );
			// }
			if ( \is_admin() ) {
				Ajax_Helper::init();
			}

			// Always execute section.
			Cron_Jobs::init();

			if ( Settings::get_option( 'requests_module_enabled' ) ) {
				Requests_List::init();
			}

			Logs_list::init();
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

				if ( ( Settings::get_option( 'crons_module_enabled' ) ) ) {
					$settings_link = '<a href="' . esc_url( Settings::get_crons_page_link() ) . '">' . \esc_html__( 'Cron Jobs', '0-day-analytics' ) . '</a>';
					array_unshift( $links, $settings_link );
				}
				if ( ( Settings::get_option( 'transients_module_enabled' ) ) ) {
					$settings_link = '<a href="' . esc_url( Settings::get_transients_page_link() ) . '">' . \esc_html__( 'Transients', '0-day-analytics' ) . '</a>';
					array_unshift( $links, $settings_link );
				}
				if ( ( Settings::get_option( 'requests_module_enabled' ) ) ) {
					$settings_link = '<a href="' . esc_url( Settings::get_requests_page_link() ) . '">' . \esc_html__( 'Requests', '0-day-analytics' ) . '</a>';
					array_unshift( $links, $settings_link );
				}
				if ( ( Settings::get_option( 'tables_module_enabled' ) ) ) {
					$settings_link = '<a href="' . esc_url( Settings::get_tables_page_link() ) . '">' . \esc_html__( 'Tables', '0-day-analytics' ) . '</a>';
					array_unshift( $links, $settings_link );
				}
				$settings_link = '<a href="' . esc_url( Settings::get_settings_page_link() ) . '">' . \esc_html__( 'Error Logs', '0-day-analytics' ) . '</a>';
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
		 * @since 1.8.4
		 */
		public static function plugin_meta( $links, $file ) {

			if ( false !== strpos( $file, 'advanced-analytics.php' ) ) {
				$links = array_merge( $links, array( '<a target="_blank" href="https://wordpress.org/support/plugin/0-day-analytics">' . esc_html__( 'Support', '0-day-analytics' ) . '</a>' ) );
			}

			return $links;
		}

		/**
		 * Remove all non-WP Mail SMTP plugin notices from our plugin pages.
		 *
		 * @since 1.8.4
		 */
		public static function hide_unrelated_notices(): void {
			// Bail if we're not on our screen or page.
			if ( ! WP_Helper::is_admin_page() ) {
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
		 * @param string $action - The name of the action.
		 *
		 * @since 1.8.4
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
					esc_html__( 'Proudly powered by %s', '0-day-analytics' ),
					'<a href="' . esc_url( __( 'https://wordpress.org/plugins/awesome-footnotes/', '0-day-analytics' ) ) . '" rel="nofollow">' . esc_attr( ADVAN_NAME ) . '</a>'
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
		 * @since 1.0.0
		 */
		public static function plugin_activate() {
			\add_option( self::REDIRECT_OPTION_NAME, true );
		}

		/**
		 * Redirects the plugin to its settings page if it was just activated.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		public static function plugin_redirect() {
			if ( \get_option( self::REDIRECT_OPTION_NAME, false ) ) {
				\delete_option( self::REDIRECT_OPTION_NAME );
				if ( ! isset( $_REQUEST['activate-multi'] ) ) {
					\wp_safe_redirect( \add_query_arg( 'page', Logs_List::MENU_SLUG, \network_admin_url( \get_current_blog_id(), 'admin.php' ) ) );
				}
			}
		}

		/**
		 * Action for _doing_it_wrong() calls.
		 *
		 * @since 1.9.2.2
		 *
		 * @param string $function_name The function that was called.
		 * @param string $message       A message explaining what has been done incorrectly.
		 * @param string $version       The version of WordPress where the message was added.
		 *
		 * @return void
		 */
		public static function action_doing_it_wrong_run( $function_name, $message, $version ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

			global $wp_filter;

			$function_name = (string) $function_name;
			$message       = (string) $message;

			if ( ! class_exists( '\QM_Collectors', false ) || ! self::is_just_in_time_for_0_day_domain( $function_name, $message ) ) {
				return;
			}

			$qm_collector_doing_it_wrong = \QM_Collectors::get( 'doing_it_wrong' );
			$current_priority            = $wp_filter['doing_it_wrong_run']->current_priority();

			if ( null === $qm_collector_doing_it_wrong || false === $current_priority ) {
				return;
			}

			switch ( $current_priority ) {
				case 0:
					\remove_action( 'doing_it_wrong_run', array( $qm_collector_doing_it_wrong, 'action_doing_it_wrong_run' ) );
					break;

				case 20:
					\add_action( 'doing_it_wrong_run', array( $qm_collector_doing_it_wrong, 'action_doing_it_wrong_run' ), 10, 3 );
					break;

				default:
					break;
			}
		}

		/**
		 * Filter for _doing_it_wrong() calls.
		 *
		 * @since 1.9.2.2
		 *
		 * @param bool|mixed $trigger       Whether to trigger the error for _doing_it_wrong() calls. Default true.
		 * @param string     $function_name The function that was called.
		 * @param string     $message       A message explaining what has been done incorrectly.
		 * @param string     $version       The version of WordPress where the message was added.
		 *
		 * @return bool
		 */
		public static function filter_doing_it_wrong_trigger_error( $trigger, $function_name, $message, $version ): bool { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

			$trigger       = (bool) $trigger;
			$function_name = (string) $function_name;
			$message       = (string) $message;

			return self::is_just_in_time_for_0_day_domain( $function_name, $message ) ? false : $trigger;
		}

		/**
		 * Whether it is the just_in_time_error for 0-Day-related domains.
		 *
		 * @since 1.9.2.2
		 *
		 * @param string $function_name Function name.
		 * @param string $message       Message.
		 *
		 * @return bool
		 */
		public static function is_just_in_time_for_0_day_domain( string $function_name, string $message ): bool {
			return '_load_textdomain_just_in_time' === $function_name && strpos( $message, '<code>' . ADVAN_TEXTDOMAIN ) !== false;
		}
	}
}
