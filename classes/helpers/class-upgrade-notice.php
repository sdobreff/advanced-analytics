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
			global $current_screen;

			if ( ! isset( $current_screen ) ) {
				return;
			}

			if ( 'plugins' === $current_screen->id ) {
				\add_action( 'in_plugin_update_message-' . \ADVAN_PLUGIN_BASENAME, array( __CLASS__, 'prefix_plugin_update_message' ), 10, 2 );
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
	}
}
