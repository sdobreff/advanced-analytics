<?php
/**
 * WP Mail settings class - responsible for SMTP settings for the WordPress PHPMailer.
 *
 * @package 0-day-analytics
 *
 * @since latest
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
	 * @since latest
	 */
	class Mail_SMTP_Settings {

		/**
		 * Inits the class and its hooks.
		 *
		 * @return void
		 *
		 * @since latest
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
		 * @since latest
		 *
		 * phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		 */
		public static function deliver_email_via_smtp( $phpmailer ) {
			$smtp_host                    = Settings::get_option( 'smtp_host' );
			$smtp_port                    = Settings::get_option( 'smtp_port' );
			$smtp_security                = Settings::get_option( 'encryption_type' );
			$smtp_username                = Settings::get_option( 'smtp_username' );
			$smtp_password                = Settings::get_option( 'smtp_password' );
			$smtp_authentication          = ( Settings::get_option( 'smtp_username' ) ) && ( Settings::get_option( 'smtp_username' ) );
			$smtp_bypass_ssl_verification = Settings::get_option( 'smtp_bypass_ssl_verification' );
			// Do nothing if host or password is empty
			// if ( empty( $smtp_host ) || empty( $smtp_password ) ) {
			// return;
			// }
			// Maybe override FROM email and/or name if the sender is "WordPress <wordpress@sitedomain.com>", the default from WordPress core and not yet overridden by another plugin.
			$from_name            = $phpmailer->FromName;
			$from_email_beginning = substr( $phpmailer->From, 0, 9 );
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
			// 	$phpmailer->SMTPDebug = 4;

			// 	$phpmailer->Debugoutput = 'error_log';

			// }
		}

		/**
		 * Send a test email and use SMTP host if defined in settings
		 *
		 * @since latest
		 */
		public static function send_test_email() {
			if ( isset( $_REQUEST['email_to'] ) && isset( $_REQUEST['nonce'] ) && current_user_can( 'manage_options' ) ) {
				if ( wp_verify_nonce( sanitize_text_field( $_REQUEST['nonce'] ), 'send-test-email-nonce_' . get_current_user_id() ) ) {
					$content       = array(
						array(
							'title' => 'Hey... are you getting this?',
							'body'  => '<p><strong>Looks like you did!</strong></p>',
						),
						array(
							'title' => 'There\'s a message for you...',
							'body'  => '<p><strong>Here it is:</strong></p>',
						),
						array(
							'title' => 'Is it working?',
							'body'  => '<p><strong>Yes, it\'s working!</strong></p>',
						),
						array(
							'title' => 'Hope you\'re getting this...',
							'body'  => '<p><strong>Looks like this was sent out just fine and you got it.</strong></p>',
						),
						array(
							'title' => 'Testing delivery configuration...',
							'body'  => '<p><strong>Everything looks good!</strong></p>',
						),
						array(
							'title' => 'Testing email delivery',
							'body'  => '<p><strong>Looks good!</strong></p>',
						),
						array(
							'title' => 'Config is looking good',
							'body'  => '<p><strong>Seems like everything has been set up properly!</strong></p>',
						),
						array(
							'title' => 'All set up',
							'body'  => '<p><strong>Your configuration is working properly.</strong></p>',
						),
						array(
							'title' => 'Good to go',
							'body'  => '<p><strong>Config is working great.</strong></p>',
						),
						array(
							'title' => 'Good job',
							'body'  => '<p><strong>Everything is set.</strong></p>',
						),
					);
					$random_number = rand( 0, count( $content ) - 1 );
					$to            = $_REQUEST['email_to'];
					$title         = $content[ $random_number ]['title'];
					$body          = $content[ $random_number ]['body'] . '<p>This message was sent from <a href="' . get_bloginfo( 'url' ) . '">' . get_bloginfo( 'url' ) . '</a> on ' . wp_date( 'F j, Y' ) . ' at ' . wp_date( 'H:i:s' ) . ' via ASE.</p>';
					$headers       = array( 'Content-Type: text/html; charset=UTF-8' );
					$success       = wp_mail(
						$to,
						$title,
						$body,
						$headers
					);
					if ( $success ) {
						$response = array(
							'status' => 'success',
						);
					} else {
						$response = array(
							'status' => 'failed',
						);
					}
					echo json_encode( $response );
				}
			}
		}
	}
}
