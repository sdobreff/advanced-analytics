<?php
/**
 * Class: Plugins and themes helper class.
 *
 * @package advanced-analytics
 *
 * @since 1.1.0
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
	 * @since 1.1.0
	 */
	class Plugin_Theme_Helper {

		/**
		 * Holds cache of the plugins installed
		 *
		 * @var array
		 *
		 * @since 1.1.0
		 */
		private static $plugins = array();

		/**
		 * Holds cache of the plugins base (directory of the plugins) installed
		 *
		 * @var array
		 *
		 * @since 2.8.2
		 */
		private static $plugins_bases = array();

		/**
		 * Holds cache of the themes installed
		 *
		 * @var array
		 *
		 * @since 1.1.0
		 */
		private static $themes = array();

		/**
		 * Caches the theme directory name
		 *
		 * @var string
		 *
		 * @since 1.1.0
		 */
		private static $theme_path = '';

		/**
		 * Class cache for checked paths and their belonging to plugins.
		 *
		 * @var array
		 *
		 * @since 2.8.2
		 */
		private static $paths_to_plugins = array();

		/**
		 * Fulfills the inner array of plugins with data if not already loaded.
		 *
		 * @return array
		 *
		 * @since 1.1.0
		 */
		public static function get_plugins(): array {
			if ( empty( self::$plugins ) ) {
				self::$plugins = \get_plugins();
			}

			return self::$plugins;
		}

		/**
		 * Fulfills the inner array of plugins with data if not already loaded.
		 *
		 * @return array
		 *
		 * @since 2.8.2
		 */
		public static function get_plugins_bases(): array {
			if ( empty( self::$plugins_bases ) ) {
				$plugins = self::get_plugins();

				foreach ( array_keys( $plugins ) as $plugin ) {
					$split_plugin = explode( \DIRECTORY_SEPARATOR, $plugin );

					if ( ! empty( $split_plugin ) ) {
						self::$plugins_bases[ $split_plugin[0] ] = $split_plugin[0];
					}
				}
			}

			return self::$plugins_bases;
		}

		/**
		 * Extracts plugin from just given path (the one after the (default) plugin/<directory>) and searches that against the plugins array stored in given WP
		 *
		 * @param string $path_name - The name of the directory where the plugin is stored - no trailing slash - this method will add one to he string.
		 *
		 * @return array
		 *
		 * @since 1.1.0
		 */
		public static function get_plugin_from_path( string $path_name ): array {
			if ( isset( self::$paths_to_plugins[ $path_name ] ) ) {
				return self::$paths_to_plugins[ $path_name ];
			}
			foreach ( self::get_plugins() as $path => $plugin ) {
				if ( \str_starts_with( $path, $path_name . \DIRECTORY_SEPARATOR ) ) {
					self::$paths_to_plugins[ $path_name ] = $plugin;
					return $plugin;
				}
			}

			return array();
		}

		/**
		 * Gets all themes and starts checking against the given possible path
		 *
		 * @param string $path_name - The path to check for.
		 *
		 * @return \WP_Theme|false
		 *
		 * @since 1.1.0
		 */
		public static function get_theme_from_path( string $path_name ) {

			// About to be implemented.

			if ( empty( self::$themes ) ) {
				self::$themes = \wp_get_themes();
			}
			foreach ( self::$themes as $theme ) {
				// $path = $theme->get_theme_root();
				$template = $theme->get_template();
				// $theme_root   = get_theme_root( $template );
				// $template_dir = "$theme_root/$template";
				if ( \mb_strtolower( $template ) === \mb_strtolower( $path_name ) ) {
					return $theme;
				}
			}

			return false;
			// \WP_Theme::get_theme_root();
		}

		/**
		 * Tries to find the path root for the theme (not exactly possible as they might be everywhere but it gets the current theme and extracts the path from there)
		 *
		 * @return string
		 *
		 * @since 1.1.0
		 */
		public static function get_default_path_for_themes(): string {

			if ( empty( self::$theme_path ) ) {
				global $wp_theme_directories;

				$stylesheet       = \get_stylesheet();
				self::$theme_path = \get_raw_theme_root( $stylesheet );
				if ( false === self::$theme_path ) {
					self::$theme_path = WP_CONTENT_DIR . \DIRECTORY_SEPARATOR . 'themes';
				} elseif ( ! in_array( self::$theme_path, (array) $wp_theme_directories, true ) ) {
					self::$theme_path = WP_CONTENT_DIR . self::$theme_path;
				}
			}

			return (string) self::$theme_path;
		}

		/**
		 * Checks if given plugin is active or not.
		 *
		 * @param string $plugin_slug - The plugin slug to check for.
		 *
		 * @return boolean
		 *
		 * @since 1.1.0
		 */
		public static function is_plugin_active( string $plugin_slug ): bool {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';

			if ( \is_plugin_active( $plugin_slug ) ) {
				return true;
			}

			return false;
		}
	}
}
