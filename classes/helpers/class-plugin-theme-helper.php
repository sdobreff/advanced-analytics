<?php
/**
 * Class: Plugins and themes helper class.
 *
 * @package advanced-analytics
 *
 * @since 2.0.0
 */

declare(strict_types=1);

namespace ADVAN\Helpers;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\ADVAN\Helpers\Plugin_Theme_Helper' ) ) {
	/**
	 * Responsible for plugin and themes.
	 *
	 * @since 1.0.0
	 */
	class Plugin_Theme_Helper {

		/**
		 * Holds cache of the plugins installed
		 *
		 * @var array
		 *
		 * @since latest
		 */
		private static $plugins = array();

		/**
		 * Caches the theme directory name
		 *
		 * @var string
		 *
		 * @since latest
		 */
		private static $theme_path = '';

		/**
		 * Fulfills the inner array of plugins with data if not already loaded.
		 *
		 * @return array
		 *
		 * @since latest
		 */
		public static function get_plugins(): array {
			if ( empty( self::$plugins ) ) {
				self::$plugins = \get_plugins();
			}

			return self::$plugins;
		}

		/**
		 * Extracts plugin from just given path (the one after the (default) plugin/<directory>) and searches that against the plugins array stored in given WP
		 *
		 * @param string $path_name - The name of the directory where the plugin is stored - no triling slash - this method will add one to he string.
		 *
		 * @return array
		 *
		 * @since latest
		 */
		public static function get_plugin_from_path( string $path_name ): array {
			foreach ( self::get_plugins() as $path => $plugin ) {
				if ( \str_starts_with( $path, $path_name . \DIRECTORY_SEPARATOR ) ) {
					return $plugin;
				}
			}

			return array();
		}

		public static function get_theme_from_path( $path_name ): array {
			// About to be implemented.
			wp_get_themes();
			\WP_Theme::get_theme_root();
		}

		public static function get_default_path_for_themes(): string {

			if ( empty( self::$theme_path ) ) {
				global $wp_theme_directories;

				$stylesheet = \get_stylesheet();
				self::$theme_path = \get_raw_theme_root( $stylesheet );
				if ( false === self::$theme_path ) {
					self::$theme_path = WP_CONTENT_DIR . \DIRECTORY_SEPARATOR . 'themes';
				} elseif ( ! in_array( self::$theme_path, (array) $wp_theme_directories, true ) ) {
					self::$theme_path = WP_CONTENT_DIR . self::$theme_path;
				}

				return self::$theme_path;
			}
		}

		/**
		 * Checks if given plugin is active or not.
		 *
		 * @param string $plugin_slug - The plugin slug to check for.
		 *
		 * @return boolean
		 *
		 * @since latest
		 */
		public static function is_plugin_active( string $plugin_slug ): bool {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';

			if ( is_plugin_active( $plugin_slug ) ) {
				return true;
			}

			return false;
		}
	}
}
