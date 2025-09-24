<?php
/**
 * 0-day Analytics
 *
 * Do WP Control
 *
 * @package   0-day-analytics
 * @author    sdobreff
 * @copyright Copyright (C) 2025-%%YEAR%%, WP Control
 * @license   GPL v3
 * @link      https://wordpress.org/plugins/0-day-analytics/
 *
 * Plugin Name:     WP Control
 * Description:     Take full control of error log, crons, transients, plugins, requests, mails and DB tables.
 * Version:         3.6.4
 * Author:          Stoil Dobrev
 * Author URI:      https://github.com/sdobreff/
 * Text Domain:     0-day-analytics
 * License:         GPL v3
 * License URI:     http://www.gnu.org/licenses/gpl-3.0.txt
 * Requires PHP:    7.4
 * Network:         true
 */

use ADVAN\Helpers\Settings;
use ADVAN\Advanced_Analytics;
use ADVAN\ControllersApi\Endpoints;
use ADVAN\Helpers\WP_Error_Handler;
use ADVAN\Helpers\WP_Helper;
use ADVAN\Controllers\Requests_Log;
use ADVAN\Controllers\WP_Mail_Log;
use ADVAN\Controllers\Mail_SMTP_Settings;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // More secure than die().
}

// Constants.
if ( ! defined( 'ADVAN_VERSION' ) ) {
	define( 'ADVAN_VERSION', '3.6.4' );
	define( 'ADVAN_TEXTDOMAIN', '0-day-analytics' );
	define( 'ADVAN_NAME', 'WP Control' );
	define( 'ADVAN_PLUGIN_ROOT', \plugin_dir_path( __FILE__ ) );
	define( 'ADVAN_PLUGIN_ROOT_URL', \plugin_dir_url( __FILE__ ) );
	define( 'ADVAN_PLUGIN_BASENAME', \plugin_basename( __FILE__ ) );
	define( 'ADVAN_PLUGIN_ABSOLUTE', __FILE__ );
	define( 'ADVAN_MIN_PHP_VERSION', '7.4' );
	define( 'ADVAN_WP_VERSION', '6.0' );
	define( 'ADVAN_SETTINGS_NAME', '0-day-analytics_options' );
	define( 'ADVAN_PREFIX', 'aadvana_' );
}

if ( version_compare( PHP_VERSION, ADVAN_MIN_PHP_VERSION, '<=' ) ) {
	\add_action(
		'admin_init',
		static function () {
			\deactivate_plugins( \plugin_basename( __FILE__ ) );
		}
	);
	\add_action(
		'admin_notices',
		static function () {
			echo \wp_kses_post(
				sprintf(
					'<div class="notice notice-error"><p>%s</p></div>',
					sprintf(
						// translators: the minimum version of the PHP required by the plugin.
						__(
							'"%1$s" requires PHP %2$s or newer. Plugin is automatically deactivated.',
							'0-day-analytics'
						),
						ADVAN_NAME,
						ADVAN_MIN_PHP_VERSION
					)
				)
			);
		}
	);

	// Return early to prevent loading the plugin.
	return;
}

require_once ADVAN_PLUGIN_ROOT . 'vendor/autoload.php';

// Query monitor grabs handler and cancel the process of error capturing, disable it.
if ( ! defined( 'QM_DISABLE_ERROR_HANDLER' ) ) {
	define( 'QM_DISABLE_ERROR_HANDLER', true );
}

\register_shutdown_function( array( WP_Error_Handler::class, 'shutdown' ) );
\set_exception_handler( array( WP_Error_Handler::class, 'exception_handler' ) );

\add_action( 'doing_it_wrong_trigger_error', array( WP_Error_Handler::class, 'trigger_error' ), 0, 4 );
\add_action( 'doing_it_wrong_run', array( Advanced_Analytics::class, 'action_doing_it_wrong_run' ), 0, 3 );
\add_action( 'doing_it_wrong_run', array( Advanced_Analytics::class, 'action_doing_it_wrong_run' ), 20, 3 );

\add_action( 'doing_it_wrong_run', array( WP_Error_Handler::class, 'action_doing_it_wrong_run' ), 99, 3 );

// All deprecated error following their own idea of what to pass and how to pass it. That list covers the most common ones.
\add_action( 'deprecated_function_run', array( WP_Error_Handler::class, 'deprecated_error' ), 0, 3 );
\add_action( 'deprecated_constructor', array( WP_Error_Handler::class, 'deprecated_error' ), 0, 3 );
\add_action( 'deprecated_class_run', array( WP_Error_Handler::class, 'deprecated_error' ), 0, 3 );
\add_action( 'deprecated_file_included', array( WP_Error_Handler::class, 'deprecated_error' ), 0, 4 );
\add_action( 'deprecated_hook_run', array( WP_Error_Handler::class, 'deprecated_error' ), 0, 3 );
\add_action( 'deprecated_argument_run', array( WP_Error_Handler::class, 'deprecated_error' ), 0, 3 );

\add_action( 'http_api_debug', array( WP_Error_Handler::class, 'capture_request' ), 0, 5 );

// Need to add deprecated_argument_run as it is bit different than the others.
if ( ! Settings::get_option( 'no_wp_die_monitor' ) ) {
	\add_filter( 'wp_die_ajax_handler', array( WP_Error_Handler::class, 'wp_die_handler' ), PHP_INT_MAX );
	\add_filter( 'wp_die_json_handler', array( WP_Error_Handler::class, 'wp_die_handler' ), PHP_INT_MAX );
	\add_filter( 'wp_die_jsonp_handler', array( WP_Error_Handler::class, 'wp_die_handler' ), PHP_INT_MAX );
	\add_filter( 'wp_die_xmlrpc_handler', array( WP_Error_Handler::class, 'wp_die_handler' ), PHP_INT_MAX );
	\add_filter( 'wp_die_xml_handler', array( WP_Error_Handler::class, 'wp_die_handler' ), PHP_INT_MAX );
	\add_filter( 'wp_die_handler', array( WP_Error_Handler::class, 'wp_die_handler' ), PHP_INT_MAX );
}

\register_activation_hook( ADVAN_PLUGIN_ABSOLUTE, array( Advanced_Analytics::class, 'plugin_activate' ) );

\add_action( 'plugins_loaded', array( Advanced_Analytics::class, 'init' ) );

if ( ! Settings::get_option( 'no_rest_api_monitor' ) ) {
	\add_filter( 'rest_post_dispatch', array( WP_Error_Handler::class, 'log_rest_api_errors' ), 10, 3 );
}

\add_action( 'wp_mail_failed', array( WP_Error_Handler::class, 'on_mail_error' ), -1 );

\add_action( 'plugin_loaded', 'advana_remove_plugins' );
\add_action( 'network_plugin_loaded', 'advana_remove_plugins' );

if ( ! WP_Helper::is_multisite() && \wp_is_recovery_mode() ) {
	\add_action(
		'plugin_loaded',
		function() {
			$paused_plugins = \wp_paused_plugins()->get_all();
			if ( empty( $paused_plugins ) ) {
				$storage = new \WP_Paused_Extensions_Storage( 'plugin' );
				foreach ( \wp_get_active_and_valid_plugins() as $plugin ) {
					$plugin_base         = \plugin_basename( $plugin );
					list( $plugin_file ) = explode( '/', $plugin_base );
					if ( ADVAN_PLUGIN_BASENAME !== $plugin_base ) {
						$storage->set(
							$plugin_file,
							array(
								'type'    => __( 'Check WP Control plugin, find fatal errors and fix them.', '0-day-analytics' ),
								'line'    => 0,
								'file'    => 0,
								'message' => '',
							)
						);
					}
					// $GLOBALS['_paused_plugins'][ $plugin_file ] = ['type'=>'Advanced'];
				}

				global $wp_theme_directories;

				$paused_themes = \wp_paused_themes()->get_all();

				if ( ! empty( $wp_theme_directories ) && empty( $paused_themes ) ) {

					$storage = new \WP_Paused_Extensions_Storage( 'theme' );

					// foreach ( $wp_theme_directories as $theme_directory ) {
					// $theme_directory = \wp_normalize_path( $theme_directory );

					// $stylesheet       = \get_stylesheet();

					// $parts = explode( '/', $theme_directory );

						$storage->set(
							\get_stylesheet(),
							array(
								'type'    => __( 'Check WP Control plugin, find fatal errors and fix them.', '0-day-analytics' ),
								'line'    => 0,
								'file'    => 0,
								'message' => '',
							)
						);
					// }
				}

				$redirect_to = 'wp-login.php?action=entered_recovery_mode';
				if ( isset( $_SERVER['REQUEST_URI'] ) ) {
					$redirect_to = \wp_unslash( $_SERVER['REQUEST_URI'] );
				}
				if ( ! function_exists( 'wp_redirect' ) ) {
					require_once ABSPATH . WPINC . '/pluggable.php';
				}
				\wp_redirect( $redirect_to );

				exit;
			}
		}
	);
}

\add_filter( 'det_display_environment_type', '__return_false' );

Requests_Log::init();
WP_Mail_Log::init();
Endpoints::init();
Mail_SMTP_Settings::init();

if ( ! function_exists( 'advana_remove_plugins' ) ) {

	/**
	 * Deactivating plugins which interfere with proper working.
	 *
	 * @param string $plugin - Currently activated plugin.
	 *
	 * @return void
	 *
	 * @since 1.9.8.1
	 */
	function advana_remove_plugins( $plugin ) {
		/**
		 * Because of its extremely poor implementation, the log-iq plugin must be deactivated as it interferes very badly with the proper WP work.
		 */
		if ( false !== strpos( $plugin, 'log-iq' . DIRECTORY_SEPARATOR ) ) {
			\deactivate_plugins( $plugin, true, null );
		}
	}
}

// Polyfill for str_starts_with (PHP < 8.0).
if ( ! function_exists( 'str_starts_with' ) ) {
	/**
	 * PHP lower than 8 is missing that function but it required in the newer versions of our plugin.
	 *
	 * @param string $haystack - The string to search in.
	 * @param string $needle - The needle to search for.
	 *
	 * @return bool
	 *
	 * @since 1.1.1
	 */
	function str_starts_with( $haystack, $needle ): bool {
		if ( '' === $needle ) {
			return true;
		}

		return 0 === strpos( $haystack, $needle );
	}
}

// Offer our own error logging if there is no way to enable WP_DEBUG and nothing else works.
if ( Settings::get_option( 'plugin_debug_enable' ) ) {
	if ( function_exists( 'error_reporting' ) ) {
		error_reporting( E_ALL );
	}

	ini_set( 'display_errors', 0 );

	$log_path = WP_CONTENT_DIR . '/debug.log';

	if ( $log_path ) {
		ini_set( 'log_errors', 1 );
		ini_set( 'error_log', $log_path );
	}
}
