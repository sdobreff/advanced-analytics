<?php
/**
 * Responsible for showing the proper environment type set.
 *
 * @package    det
 * @license    GPL v3
 * @copyright  %%YEAR%%
 *
 * @since 1.8.4
 */

declare(strict_types=1);

namespace ADVAN\Controllers;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( '\ADVAN\Controllers\Display_Environment_Type' ) ) {

	/**
	 * Responsible for all the plugin behavior
	 *
	 * @since 1.8.4
	 */
	class Display_Environment_Type {

		const STYLESHEET_HANDLE = 'det-toolbar-styles';

		/**
		 * Tells the plugin to add its hooks on the 'init' action.
		 *
		 * @return void
		 *
		 * @since 1.8.4
		 */
		public static function init() {

			// Bail if we shouldn't display.
			if ( ! self::should_display() ) {
				return;
			}

			// Add an item to the "at a glance" dashboard widget.
			\add_filter( 'dashboard_glance_items', array( __CLASS__, 'add_glance_item' ) );

			// Add an admin bar item if in wp-admin.
			\add_action( 'admin_bar_menu', array( __CLASS__, 'add_toolbar_item' ), 7 );

			// Add styling.
			\add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_styles' ) );
			\add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_styles' ) );
		}

		/**
		 * Make development type i18n.
		 *
		 * @param string $env_type The environment type.
		 *
		 * @return string The translated environment type.
		 *
		 * @since 1.8.4
		 */
		public static function get_env_type_name( $env_type ) {
			$name = '';
			switch ( $env_type ) {
				case 'local':
					$name = __( 'Local', '0-day-analytics' );
					break;
				case 'development':
					$name = __( 'Development', '0-day-analytics' );
					break;
				case 'staging':
					$name = __( 'Staging', '0-day-analytics' );
					break;
				default:
					$name = __( 'Production', '0-day-analytics' );
			}

			/**
			 * Filter the environment type name.
			 *
			 * @param string $name     The translated environment name.
			 * @param string $env_type The environment type key.
			 */
			return \apply_filters( 'display_environment_type_name', $name, $env_type );
		}

		/**
		 * Adds the "at a glance" item.
		 *
		 * @param array $items The current "at a glance" items.
		 *
		 * @return array The updated "at a glance" items array.
		 *
		 * @since 1.8.4
		 */
		public static function add_glance_item( $items ) {
			$env_type      = \wp_get_environment_type();
			$env_type_name = self::get_env_type_name( $env_type );

			if ( ! empty( $env_type ) ) {
				$items[] = '<span class="' . \esc_attr( 'det-env-type det-' . $env_type ) . '" title="' . \esc_attr__( 'Environment Type', '0-day-analytics' ) . '">' . \esc_html( $env_type_name ) . '</span>';
			}

			return $items;
		}

		/**
		 * Adds an admin bar item.
		 *
		 * @param \admin_bar $admin_bar The WordPress toolbar.
		 *
		 * @return void
		 *
		 * @since 1.8.4
		 */
		public static function add_toolbar_item( $admin_bar ) {
			$env_type      = \wp_get_environment_type();
			$env_type_name = self::get_env_type_name( $env_type );

			if ( ! empty( $env_type ) ) {
				$admin_bar->add_menu(
					array(
						'id'     => 'det_env_type',
						'parent' => 'top-secondary',
						'title'  => '<span class="ab-icon" aria-hidden="true"></span><span class="ab-label">' . \esc_html( $env_type_name ) . '</span>',
						'meta'   => array(
							'class' => 'det-' . \sanitize_title( $env_type ),
						),
					)
				);

				$admin_bar->add_node(
					array(
						'id'     => 'det-wp-debug',
						'title'  => self::show_label_value( 'WP_DEBUG', ( WP_DEBUG ? 'true' : 'false' ) ),
						'parent' => 'det_env_type',
					)
				);

				$admin_bar->add_node(
					array(
						'id'     => 'det-wp-debug-log',
						'title'  => self::show_label_value( 'WP_DEBUG_LOG', ( WP_DEBUG_LOG ? 'true' : 'false' ) ),
						'parent' => 'det_env_type',
						'meta'   => array(
							'title' => WP_DEBUG_LOG,
						),
					)
				);

				$admin_bar->add_node(
					array(
						'id'     => 'det-wp-debug-display',
						'title'  => self::show_label_value( 'WP_DEBUG_DISPLAY', ( WP_DEBUG_DISPLAY ? 'true' : 'false' ) ),
						'parent' => 'det_env_type',
					)
				);

				$wp_development_mode = ( function_exists( 'wp_get_development_mode' ) ? \wp_get_development_mode() : null );

				if ( null !== $wp_development_mode ) {
					if ( empty( $wp_development_mode ) ) {
						$wp_development_mode = 'false';
					}
					$admin_bar->add_node(
						array(
							'id'     => 'det-wp-development-mode',
							'title'  => self::show_label_value( 'WP_DEVELOPMENT_MODE', $wp_development_mode ),
							'parent' => 'det_env_type',
						)
					);
				}

				$admin_bar->add_node(
					array(
						'id'     => 'det-script-display',
						'title'  => self::show_label_value( 'SCRIPT_DEBUG', ( SCRIPT_DEBUG ? 'true' : 'false' ) ),
						'parent' => 'det_env_type',
					)
				);

				$admin_bar->add_node(
					array(
						'id'     => 'det-savequeries',
						'title'  => self::show_label_value( 'SAVEQUERIES', ( defined( 'SAVEQUERIES' ) && SAVEQUERIES ? 'true' : 'false' ) ),
						'parent' => 'det_env_type',
					)
				);

				$admin_bar->add_node(
					array(
						'id'     => 'det-wp',
						'title'  => self::show_label_value( 'WP', \get_bloginfo( 'version', 'display' ) ),
						'parent' => 'det_env_type',
					)
				);

				$admin_bar->add_node(
					array(
						'id'     => 'det-php',
						'title'  => self::show_label_value( 'PHP', phpversion() ),
						'parent' => 'det_env_type',
					)
				);
			}
		}

		/**
		 * Html_label_value
		 *
		 * @param  string $label Text to display as label.
		 * @param  string $value Text to display as value.
		 *
		 * @return string        HTML to display label and value.
		 *
		 * @since 1.8.4
		 */
		private static function show_label_value( $label, $value ): string {
			$html  = '';
			$html .= '<span class="ei-label">' . $label . '</span>';
			$html .= '<span class="ei-value">' . $value . '</span>';

			return $html;
		}

		/**
		 * Determine whether or not to display the environment type.
		 *
		 * @return bool Whether the plugin should display anything.
		 *
		 * @since 1.8.4
		 */
		protected static function should_display() {
			// By default, we don't display anything.
			$display = false;

			// If the function doesn't exist, the plugin absolutely cannot function.
			if ( ! function_exists( 'wp_get_environment_type' ) ) {
				return false;
			}

			// If the admin bar is not showing there is no place to display the environment type.
			if ( ! \is_admin_bar_showing() ) {
				return false;
			}

			if ( is_admin() ) {
				// Display in wp-admin for any role above subscriber.
				if ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) {
					$display = true;
				}
			} elseif ( \is_user_logged_in() && \current_user_can( 'manage_options' ) ) {
				// Display on the front-end only if user has the manage_options capability.
				$display = true;
			}

			/**
			 * Filter whether or not the environment type should be displayed.
			 *
			 * Allows you to perform checks like user capabilities or is_admin()
			 * and return true to display the environment type, or false to not.
			 *
			 * @since 1.2
			 *
			 * @param boolean $display Whether the environment type should be displayed.
			 */
			$display = (bool) apply_filters( 'aadvan_display_environment_type', $display );

			return $display;
		}

		/**
		 * Enqueues the CSS styles necessary to display the environment type.
		 *
		 * @since 1.8.4
		 */
		public static function enqueue_styles() {
			\wp_enqueue_style(
				self::STYLESHEET_HANDLE,
				ADVAN_PLUGIN_ROOT_URL . 'css/admin.css',
				array(),
				ADVAN_VERSION
			);
		}
	}
}
