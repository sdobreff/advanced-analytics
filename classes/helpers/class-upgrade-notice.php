<?php
/**
 * Class: Determine the context in which the plugin is executed.
 *
 * Helper class to determine the proper status of the request.
 *
 * @package awesome-footnotes
 *
 * @since 1.9.2
 */

declare(strict_types=1);

namespace ADVAN\Helpers;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Upgrade notice class
 */
if ( ! class_exists( '\ADVAN\Helpers\Upgrade_Notice' ) ) {
	/**
	 * Utility class for showing the upgrade notice in the plugins page.
	 *
	 * @package awe
	 *
	 * @since 1.9.2
	 */
	class Upgrade_Notice {
		/**
		 * Inits the upgrade notice hooks.
		 *
		 * @return void
		 *
		 * @since 1.9.2
		 */
		public static function init() {

			if ( ! WP_Helper::get_wp_screen() ) {
				return;
			}

			if ( 'plugins' === WP_Helper::get_wp_screen()->id ) {
				\add_action( 'in_plugin_update_message-' . \ADVAN_PLUGIN_BASENAME, array( __CLASS__, 'prefix_plugin_update_message' ), 10, 2 );
				\add_action( 'after_plugin_row_meta', array( __CLASS__, 'after_plugin_row_meta' ), 10, 2 );

				if ( \current_user_can( 'activate_plugins' ) || \current_user_can( 'delete_plugins' ) ) {
					\add_action( 'admin_enqueue_scripts', array( __CLASS__, 'load_custom_wp_admin_style' ) );
				}
			}
		}

		/**
		 * Shows the message for the upgrading footnotes
		 *
		 * @param array  $data - Array with the data.
		 * @param object $response - The response.
		 *
		 * @return void
		 *
		 * @since 1.9.2
		 */
		public static function prefix_plugin_update_message( $data, $response ) {

			$current_version_parts = explode( '.', \ADVAN_VERSION );
			$new_version_parts     = explode( '.', $response->new_version );

			// If user has already moved to the minor version, we don't need to flag up anything.
			if ( version_compare( $current_version_parts[0] . '.' . $current_version_parts[1] . '.' . $current_version_parts[2], $new_version_parts[0] . '.' . $new_version_parts[1] . '.' . $new_version_parts[2], '>' ) ) {
				return;
			}

			$upgrade_notice = self::get_upgrade_notice( $response->new_version );

			if ( isset( $upgrade_notice ) && ! empty( $upgrade_notice ) ) {
				printf(
					'</p><div class="update-message">%s</div><p class="dummy">',
					$upgrade_notice // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				);
			}
		}

		/**
		 * Get the upgrade notice from WordPress.org.
		 *
		 * @param  string $version - Plugin new version.
		 *
		 * @return string
		 *
		 * @since 1.9.2
		 */
		private static function get_upgrade_notice( $version ) {
			$transient_name = 'advan_upgrade_notice_' . $version;
			$upgrade_notice = \get_transient( $transient_name );

			if ( false === $upgrade_notice || empty( $upgrade_notice ) ) {
				$response = \wp_safe_remote_get( 'https://plugins.svn.wordpress.org/0-day-analytics/trunk/readme.txt' );

				if ( ! \is_wp_error( $response ) && ! empty( $response['body'] ) ) {
					$upgrade_notice = self::parse_update_notice( $response['body'], $version );
					\set_transient( $transient_name, $upgrade_notice, \DAY_IN_SECONDS );
				}
			}

			return $upgrade_notice;
		}

		/**
		 * Parse update notice from readme file.
		 *
		 * @param  string $content - Plugin readme file content.
		 * @param  string $new_version - Plugin new version.
		 * @return string
		 *
		 * @example readme.txt :
		 * ...
		 * == Upgrade Notice ==
		 *
		 * = Version =
		 * Description
		 * ...
		 *
		 * @since 1.9.2
		 */
		private static function parse_update_notice( $content, $new_version ) {
			$version_parts     = explode( '.', $new_version );
			$check_for_notices = array(

				$version_parts[0] . '.' . $version_parts[1] . '.' . $version_parts[2], // Patch.
			);

			$notice_regexp  = '~==\s*Upgrade Notice\s*==\s*=\s*(.*)\s*=(.*)(=\s*' . preg_quote( $new_version ) . '\s*=|$)~Uis';
			$upgrade_notice = '';

			$style = '';

			foreach ( $check_for_notices as $check_version ) {
				if ( version_compare( \ADVAN_VERSION, $check_version, '>' ) ) {
					continue;
				}

				$matches = null;
				if ( preg_match( $notice_regexp, $content, $matches ) ) {
					$notices = (array) preg_split( '~[\r\n]+~', trim( $matches[2] ) );

					if ( version_compare( trim( $matches[1] ), $check_version, '=' ) ) {
						$style           = '<style>
							.advan_plugin_upgrade_notice {
								font-weight: normal;
								background: #fff8e5 !important;
								border-left: none !important;
								border-top: 1px solid #ffb900;
								padding: 9px 0 20px 32px !important;
								margin: 0 -12px 0 -20px !important;
							}
							p.advan_plugin_upgrade_notice::before {
									content: "\f348" !important;
									display: inline-block;
									font: 400 18px/1 dashicons;
									speak: never;
									margin: 0 8px 0 -2px;
									vertical-align: top;
							}
							.dummy {
								display: none;
							}
							.update-message {
								margin: 9px !important;
							}
						</style>';
						$upgrade_notice .= '<p class="advan_plugin_upgrade_notice">';

						foreach ( $notices as $line ) {
							$upgrade_notice .= preg_replace( '~\[([^\]]*)\]\(([^\)]*)\)~', '<a href="${2}">${1}</a>', $line ) . '<br>';
						}

						$upgrade_notice .= '</p>';

						break;
					}
					continue;
				}
			}

			return $style . ( $upgrade_notice );
		}

		/**
		 * Adds the file path after the plugin meta.
		 *
		 * @param string $plugin_file Refer to {@see 'plugin_row_meta'} filter.
		 * @param array  $plugin_data Refer to {@see 'plugin_row_meta'} filter.
		 *
		 * @return void
		 *
		 * @since 1.9.7
		 */
		public static function after_plugin_row_meta( $plugin_file, $plugin_data ) {
			if ( \current_user_can( 'activate_plugins' ) || \current_user_can( 'delete_plugins' ) ) {
				if ( isset( $plugin_data['slug'] ) && ! empty( $plugin_data['slug'] ) ) {
					echo '<div style="margin-top:10px;"><input type="button" class="button button-primary switch_plugin_version" data-plugin-slug="' . \esc_attr( $plugin_data['slug'] ) . '" value="' . \esc_html__( 'Switch plugin version', '0-day-analytics' ) . '"><select id="' . \esc_attr( ADVAN_PREFIX.$plugin_data['slug'] ) . '" style="display:none"></select><input id="'.ADVAN_PREFIX.'switch_plugin_to_version_' . \esc_attr( $plugin_data['slug'] ) . '" style="display:none" type="button" class="button button-primary switch_to_plugin_version" data-plugin-slug="' . \esc_attr( $plugin_data['slug'] ) . '" value="' . \esc_html__( 'Switch version', '0-day-analytics' ) . '"></div>';
				}
			}
			echo '<div style="margin-top:10px;">' . \esc_attr( \trailingslashit( WP_PLUGIN_DIR ) ) . '<b>' . \esc_attr( $plugin_file ) . '</b></div>';
		}

		/**
		 * Extracts the plugin versions from WordPress.org.
		 *
		 * @param string $plugin_slug - The plugin slug to collect versions for.
		 *
		 * @return \WP_Error|string
		 *
		 * @since 1.9.7
		 */
		public static function extract_plugin_versions( string $plugin_slug ) {

			if ( ! function_exists( 'plugins_api' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
			}

			$plugin_information = \plugins_api(
				'plugin_information',
				array(
					'slug'   => esc_html( $plugin_slug ),
					'fields' => array(
						'version'           => true,
						'versions'          => true,
						'contributors'      => false,
						'short_description' => false,
						'description'       => false,
						'sections'          => false,
						'screenshots'       => false,
						'tags'              => false,
						'donate_link'       => false,
						'ratings'           => false,
					),
				)
			);

			if ( \is_wp_error( $plugin_information ) ) {
				if ( 'plugins_api_failed' === $plugin_information->get_error_code() ) {
					return new \WP_Error( 'plugins_api_failed', __( 'Plugin API information could not be retrieved.', '0-day-analytics' ) );
				}

				return $plugin_information;
			}

			$rollback_versions = array();

			if ( ! empty( $plugin_information->versions ) || is_array( $plugin_information->versions ) ) {

				$versions = $plugin_information->versions;

				usort( $versions, 'version_compare' );

				$versions = array_slice( $versions, -( Settings::get_option( 'plugin_version_switch_count' ) ), Settings::get_option( 'plugin_version_switch_count' ), true );

				foreach ( $plugin_information->versions as $version => $download_link ) {
					$key = array_search( $download_link, $versions );
					if ( false !== $key && 'trunk' !== $version && ! empty( $version ) ) {
						$rollback_versions[] = $version;
					}
				}
			}
			$versions = $rollback_versions;

			$result = null;

			if ( is_array( $versions ) && ! array_key_exists( 'errors', $versions ) ) {
				$result = true;
			}

			if ( is_object( $versions ) && property_exists( $versions, 'error_data' ) && ! empty( $versions->error_data ) ) {
				$result = false;
			}

			if ( ! $result ) {
				return new \WP_Error( 'plugin_versions_not_found', __( 'No Versions Found', '0-day-analytics' ) );
			}

			$versions = $versions ? $versions : array();

			if ( ! empty( $versions ) ) {
				if ( ! array_key_exists( 'errors', $versions ) ) {
					$option_html = "<option value='' >" . esc_html__( 'Select Version', '0-day-analytic' ) . '</option>';
					foreach ( $versions as $version ) {
						$option_html .= "<option value='{$version}'>$version</option>";
					}
					return $option_html;
				} else {
					return new \WP_Error( 'plugin_not_found', __( 'Plugin is not found in WordPress ORG', '0-day-analytics' ) );

				}
			} else {
				return new \WP_Error( 'plugin_version_not_found', __( 'No Version Found', '0-day-analytics' ) );
			}
		}

		/**
		 * Switch to selected plugin version.
		 *
		 * @param string $plugin_slug - The plugin slug.
		 * @param string $version - The version to switch to.
		 *
		 * @return \WP_Error
		 *
		 * @since 1.9.7
		 */
		public static function version_switch( $plugin_slug, $version ) {

			if ( empty( $version ) ) {
				return new \WP_Error( 'plugin_version_invalid', __( 'Error occurred, The version selected is invalid. Try selecting different version.', '0-day-analytics' ) );
			}

			return self::upgrade( $plugin_slug, $version );
		}

		/**
		 * Upgrades the plugin to a specific version.
		 *
		 * @param string $plugin_name - The plugin slug.
		 * @param string $version - The version to switch to.
		 *
		 * @return bool|\WP_Error
		 *
		 * @since 1.9.7
		 */
		public static function upgrade( string $plugin_name, $version ) {

			$update_plugins = \get_site_transient( 'update_plugins' );
			if ( ! is_object( $update_plugins ) ) {
				$update_plugins = new \stdClass();
			}

			$plugin_info              = new \stdClass();
			$plugin_info->new_version = $version;
			$plugin_info->slug        = $plugin_name;
			$plugin_info->package     = sprintf( 'https://downloads.wordpress.org/plugin/%s.%s.zip', $plugin_name, $version );

			$update_plugins->response[ $plugin_name ] = $plugin_info;

			\set_site_transient( 'update_plugins', $update_plugins );

			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

			$title = __( 'Version Switching : - ', '0-day-analytics' );
			$title = $title . str_replace( '-', ' ', $plugin_name ) . ' v' . $version;

			$upgrader_args = array(
				'url'    => 'update.php?action=upgrade-plugin&plugin=' . rawurlencode( $plugin_name ),
				'plugin' => $plugin_name,
				'nonce'  => 'upgrade-plugin_' . $plugin_name,
				'title'  => $title,
			);

			$upgrader = new \Plugin_Upgrader( new \Plugin_Upgrader_Skin( $upgrader_args ) );

			return $upgrader->upgrade( $plugin_name );
		}

		/**
		 * Enqueue the custom admin style.
		 *
		 * @param string $hook - The current admin page.
		 *
		 * @return void
		 *
		 * @since 1.9.7
		 */
		public static function load_custom_wp_admin_style( $hook ) {
			?>
				<script>
					window.addEventListener("load", () => {
						jQuery( '.switch_plugin_version' ).on( 'click', function(e) {
							var data = {
								'action': '<?php echo ADVAN_PREFIX; ?>extract_plugin_versions',
								'_wpnonce': '<?php echo \wp_create_nonce( 'advan-plugin-data', 'advanced-analytics-security' );  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>',
								'plugin_slug': jQuery(this).data('plugin-slug')
							};

							let that = this;

							jQuery( that ).addClass( 'disabled' );

							jQuery.get({
								url: "<?php echo \esc_url( \admin_url( 'admin-ajax.php' ) ); ?>",
								data,
								success: function(data, textStatus, jqXHR) {
									// if PHP call returns data process it and show notification
									// if nothing returns then it means no notification available for now
									if (jQuery.trim(data.data)) {
										
										jQuery('#<?php echo \esc_attr( ADVAN_PREFIX ); ?>' + jQuery(that).data('plugin-slug')).html(data.data).show();
										jQuery('#<?php echo \esc_attr( ADVAN_PREFIX ); ?>switch_plugin_to_version_' + jQuery(that).data('plugin-slug')).html(data.data).show();
										that.remove();
									}
								},
								error: function(jqXHR, textStatus, errorThrown) { 
									jQuery( that ).removeClass( 'disabled' );
								}
							});
						});

						jQuery( '.switch_to_plugin_version' ).on( 'click', function(e) {
							let that = this;

							var selectedVersion = jQuery('#<?php echo \esc_attr( ADVAN_PREFIX ); ?>' + jQuery(that).data('plugin-slug')).find(":selected").val();

							if ( ! selectedVersion ) {
								alert('<?php echo esc_js( __( 'Please select a version to switch.', '0-day-analytics' ) ); ?>');
								return;
							}

							jQuery( that ).addClass( 'disabled' );
							jQuery('#<?php echo \esc_attr( ADVAN_PREFIX ); ?>' + jQuery(that).data('plugin-slug')).addClass( 'disabled' );
					
							var data = {
								'action': '<?php echo \esc_attr( ADVAN_PREFIX ); ?>switch_plugin_version',
								'_wpnonce': '<?php echo \wp_create_nonce( 'advan-plugin-data', 'advanced-analytics-security' );  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>',
								'plugin_slug': jQuery(this).data('plugin-slug'),
								'version': selectedVersion
							};

							jQuery.get({
								url: "<?php echo \esc_url( \admin_url( 'admin-ajax.php' ) ); ?>",
								data,
								success: function(data, textStatus, jqXHR) {
									location.reload();
								},
								error: function(jqXHR, textStatus, errorThrown) { 
									jQuery( that ).removeClass( 'disabled' );
									jQuery('#<?php echo \esc_attr( ADVAN_PREFIX ); ?>' + jQuery(that).data('plugin-slug')).removeClass( 'disabled' );
								}
							});
						});
					});
				</script>

			<?php
		}
	}
}
