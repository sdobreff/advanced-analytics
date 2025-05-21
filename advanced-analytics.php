<?php
/**
 * 0-day Analytics
 *
 * Do WP Advanced Analysis
 *
 * @package   0-day-analytics
 * @author    sdobreff
 * @copyright Copyright (C) 2023-%%YEAR%%, Advanced analytics
 * @license   GPL v3
 * @link      https://wordpress.org/plugins/0-day-analytics/
 *
 * Plugin Name:     Advanced Analytics
 * Description:     Provides WordPress analytics with a focus on performance and security.
 * Version:         1.8.0
 * Author:          Stoil Dobrev
 * Author URI:      https://github.com/sdobreff/
 * Text Domain:     0-day-analytics
 * License:         GPL v3
 * License URI:     http://www.gnu.org/licenses/gpl-3.0.txt
 * Requires PHP:    7.4
 * Network:         true
 */

use ADVAN\Advanced_Analytics;
use ADVAN\Helpers\Context_Helper;
use ADVAN\Helpers\WP_Error_Handler;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // More secure than die().
}

// Constants.
define( 'ADVAN_VERSION', '1.8.0' );
define( 'ADVAN_TEXTDOMAIN', '0-day-analytics' );
define( 'ADVAN_NAME', 'Advanced Analysis' );
define( 'ADVAN_PLUGIN_ROOT', \plugin_dir_path( __FILE__ ) );
define( 'ADVAN_PLUGIN_ROOT_URL', \plugin_dir_url( __FILE__ ) );
define( 'ADVAN_PLUGIN_BASENAME', \plugin_basename( __FILE__ ) );
define( 'ADVAN_PLUGIN_ABSOLUTE', __FILE__ );
define( 'ADVAN_MIN_PHP_VERSION', '7.4' );
define( 'ADVAN_WP_VERSION', '6.0' );
define( 'ADVAN_SETTINGS_NAME', '0-day-analytics_options' );
define( 'ADVAN_PREFIX', 'aadvana_' );

if ( version_compare( PHP_VERSION, ADVAN_MIN_PHP_VERSION, '<=' ) ) {
	\add_action(
		'admin_init',
		static function () {
			\deactivate_plugins( plugin_basename( __FILE__ ) );
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

// Check mbstring extension.
if ( ! extension_loaded( 'mbstring' ) ) {
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
						// translators: the mbstring extensions is required by the plugin.
						__(
							'"%1$s" requires multi byte string extension loaded. Plugin is automatically deactivated.',
							'0-day-analytics'
						)
					)
				)
			);
		}
	);

	// Return early to prevent loading the plugin.
	return;
}

$plugin_name_libraries = require ADVAN_PLUGIN_ROOT . 'vendor/autoload.php';

if ( ! Context_Helper::is_installing() ) {
	\add_action( 'doing_it_wrong_trigger_error', array( WP_Error_Handler::class, 'trigger_error' ), 10, 4 );

	// All deprecated error following their own idea of what to pass and how to pass it. That list covers the most common ones.
	\add_action( 'deprecated_function_run', array( WP_Error_Handler::class, 'deprecated_error' ), 10, 3 );
	\add_action( 'deprecated_constructor_run', array( WP_Error_Handler::class, 'deprecated_error' ), 10, 3 );
	\add_action( 'deprecated_class_run', array( WP_Error_Handler::class, 'deprecated_error' ), 10, 3 );
	\add_action( 'deprecated_file_included', array( WP_Error_Handler::class, 'deprecated_error' ), 10, 3 );
	\add_action( 'deprecated_hook_run', array( WP_Error_Handler::class, 'deprecated_error' ), 10, 3 );

	// Need to add deprecated_argument_run as it is bit different than the others.

	\register_activation_hook( ADVAN_PLUGIN_ABSOLUTE, array( Advanced_Analytics::class, 'plugin_activate' ) );
	\add_action( 'plugins_loaded', array( Advanced_Analytics::class, 'init' ) );
}

register_shutdown_function( array( Advanced_Analytics::class, 'shutdown' ) );

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
