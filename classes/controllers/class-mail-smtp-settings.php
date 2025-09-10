<?php
/**
 * WP Mail settings class - responsible for SMTP settings for the WordPress PHPMailer.
 *
 * @package 0-day-analytics
 *
 * @since 3.3.0
 */

declare(strict_types=1);

namespace ADVAN\Controllers;

use ADVAN\Helpers\Settings;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\ADVAN\Controllers\Mail_SMTP_Settings' ) ) {
	/**
	 * Responsible for delivering the emails using the SMTP provided settings.
	 *
	 * @since 3.3.0
	 */
	class Mail_SMTP_Settings {

		public const NONCE_NAME = ADVAN_PREFIX . 'mail';

		/**
		 * Inits the class and its hooks.
		 *
		 * @return void
		 *
		 * @since 3.3.0
		 */
		public static function init() {
			\add_action( 'phpmailer_init', array( __CLASS__, 'deliver_email_via_smtp' ), 999999 );
		}

		/**
		 * Sets the properties of the given PHPMailer object based on the settings provided.
		 *
		 * @param \PHPMailer $phpmailer - The PHPMailer class reference to set options to.
		 *
		 * @return void
		 *
		 * @since 3.3.0
		 *
		 * phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		 */
		public static function deliver_email_via_smtp( $phpmailer ) {
			$smtp_host                    = Settings::get_option( 'smtp_host' );
			$smtp_port                    = Settings::get_option( 'smtp_port' );
			$smtp_security                = Settings::get_option( 'encryption_type' );
			$smtp_username                = Settings::get_option( 'smtp_username' );
			$smtp_password                = Settings::get_option( 'smtp_password' );
			$smtp_authentication          = ( Settings::get_option( 'smtp_username' ) ) && ( Settings::get_option( 'smtp_password' ) );
			$smtp_bypass_ssl_verification = Settings::get_option( 'smtp_bypass_ssl_verification' );
			// Do nothing if host or password is empty
			// if ( empty( $smtp_host ) || empty( $smtp_password ) ) {
			// return;
			// }
			// Maybe override FROM email and/or name if the sender is "WordPress <wordpress@sitedomain.com>", the default from WordPress core and not yet overridden by another plugin.
			// $from_name            = $phpmailer->FromName;
			// $from_email_beginning = substr( $phpmailer->From, 0, 9 );
			// Get the first 9 characters of the current FROM email.
			// if ( $smtp_force_from ) {
			// $phpmailer->FromName = $smtp_default_from_name;
			// $phpmailer->From     = $smtp_default_from_email;
			// } else {
			// if ( 'WordPress' === $from_name && ! empty( $smtp_default_from_name ) ) {
			// $phpmailer->FromName = $smtp_default_from_name;
			// }
			// if ( 'WordPress' === $from_email_beginning && ! empty( $smtp_default_from_email ) ) {
			// $phpmailer->From = $smtp_default_from_email;
			// }
			// }

			$from_email = Settings::get_option( 'from_email' );
			if ( ! empty( trim( $from_email ) ) ) {
				$phpmailer->From = $from_email;
			}
			$from_email_name = Settings::get_option( 'from_email_name' );
			if ( ! empty( trim( $from_email_name ) ) ) {
				$phpmailer->FromName = $from_email_name;
			}
			// Only attempt to send via SMTP if all the required info is present. Otherwise, use default PHP Mailer settings as set by wp_mail().
			if ( ! empty( $smtp_host ) && ! empty( $smtp_port ) && ! empty( $smtp_security ) ) {
				// Send using SMTP.
				$phpmailer->isSMTP();

				if ( 'enable' == $smtp_authentication ) {
					$phpmailer->SMTPAuth = true;

				} else {
					$phpmailer->SMTPAuth = false;

				}
				// Set some other defaults.

				$phpmailer->XMailer = ADVAN_NAME . ' v' . ADVAN_VERSION . ' - a WordPress plugin';

				$phpmailer->Host = $smtp_host;

				$phpmailer->Port = $smtp_port;

				$phpmailer->SMTPSecure = $smtp_security;

				if ( $smtp_authentication ) {
					$phpmailer->Username = trim( $smtp_username );

					$phpmailer->Password = trim( $smtp_password );

				}
			}
			// If verification of SSL certificate is bypassed.
			// Reference: https://www.php.net/manual/en/context.ssl.php & https://stackoverflow.com/a/30803024 .
			if ( $smtp_bypass_ssl_verification ) {
				$phpmailer->SMTPOptions = array(
					'ssl' => array(
						'verify_peer'       => false,
						'verify_peer_name'  => false,
						'allow_self_signed' => true,
					),
				);
			}
			// If debug mode is enabled, send debug info (SMTP::DEBUG_CONNECTION) to WordPress debug.log file set in wp-config.php.
			// Reference: https://github.com/PHPMailer/PHPMailer/wiki/SMTP-Debugging
			// if ( $smtp_debug ) {
			// $phpmailer->SMTPDebug = 4;

			// $phpmailer->Debugoutput = 'error_log';

			// }
		}
	}
}
