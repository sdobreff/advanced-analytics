<?php
/**
 * Advanced analysis
 *
 * Do WP Advanced Analysis
 *
 * @package   advanced-analysis
 * @author    sdobreff
 * @copyright Copyright (C) 2023-%%YEAR%%, Advanced analysis
 * @license   GPL v3
 * @link      https://wordpress.org/plugins/advanced-analysis/
 *
 * Plugin Name:     Advanced analysis
 * Description:     Allows admins to do WP analytics.
 * Version:         1.0.0
 * Author:          Stoil Dobrev
 * Author URI:      https://github.com/sdobreff/
 * Text Domain:     advanced-analysis
 * License:         GPL v3
 * License URI:     http://www.gnu.org/licenses/gpl-3.0.txt
 * Requires PHP:    7.4
 */

use ADVAN\Advanced_Analytics;
use ADVAN\Controllers\Error_Log;
use ADVAN\Helpers\Context_Helper;
use ADVAN\Helpers\Log_Line_Parser;
use ADVAN\Controllers\Reverse_Line_Reader;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'We\'re sorry, but you can not directly access this file.' );
}

define( 'ADVAN_VERSION', '1.0.0' );
define( 'ADVAN_TEXTDOMAIN', 'advanced-analysis' );
define( 'ADVAN_NAME', 'Advanced Analysis' );
define( 'ADVAN_PLUGIN_ROOT', \plugin_dir_path( __FILE__ ) );
define( 'ADVAN_PLUGIN_ROOT_URL', \plugin_dir_url( __FILE__ ) );
define( 'ADVAN_PLUGIN_BASENAME', \plugin_basename( __FILE__ ) );
define( 'ADVAN_PLUGIN_ABSOLUTE', __FILE__ );
define( 'ADVAN_MIN_PHP_VERSION', '7.4' );
define( 'ADVAN_WP_VERSION', '6.0' );
define( 'ADVAN_SETTINGS_NAME', 'advanced-analysis_options' );

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
							'awesome-footnotes'
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
							'awesome-footnotes'
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
/*
Reverse_Line_Reader::read_file_from_end(
	Error_Log::autodetect(),
	function( $line ) {
		// echo $line;

		// Check if this is the last line, and if not try to parse the line.
		if ( null !== Log_Line_Parser::parse_entry_with_stack_trace( $line ) ) {
			print_r( Log_Line_Parser::parse_php_error_log_stack_line( $line ) );
		}

		// if ( ! str_contains( $address, 'stop_word' ) ) {
		// echo "\nFound 'stop_word'!";

		// return false; // returning false here "breaks" the loop
		// }
	},
	30
);
*/
if ( ! Context_Helper::is_installing() ) {
	// \register_activation_hook( ADVAN_PLUGIN_ABSOLUTE, array( '\ADVAN\Advanced_Analytics', 'plugin_activate' ) );
	\add_action( 'plugins_loaded', array( Advanced_Analytics::class, 'init' ) );
}
