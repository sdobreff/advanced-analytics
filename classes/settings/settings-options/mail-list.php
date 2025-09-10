<?php
/**
 * Option settings of the plugin
 *
 * @package awe
 *
 * @since 3.0.0
 */

use ADVAN\Helpers\Settings;
use ADVAN\Controllers\Mail_SMTP_Settings;

	Settings::build_option(
		array(
			'title' => esc_html__( 'Mail viewer Options', '0-day-analytics' ),
			'id'    => 'options-settings-tab',
			'type'  => 'tab-title',
		)
	);

	// Cron options.
	Settings::build_option(
		array(
			'title' => \esc_html__( 'Mail list options', '0-day-analytics' ),
			'id'    => 'table-settings-options',
			'type'  => 'header',
		)
	);

	Settings::build_option(
		array(
			'name'    => \esc_html__( 'Enable mails module', '0-day-analytics' ),
			'id'      => 'wp_mail_module_enabled',
			'type'    => 'checkbox',
			'hint'    => \esc_html__( 'If you disable this, the entire plugin mails module will be disabled. This applies only for the plugin module (if you are not using it and don\'t want it to take unnecessarily resources, your WP will continue working as before.', '0-day-analytics' ),
			'default' => Settings::get_option( 'wp_mail_module_enabled' ),
			'toggle'  => '#advana_maillist_settings-item',
		)
	);
?>
<div id="advana_maillist_settings-item">
	<?php

	$schedules = \wp_get_schedules();
	$options   = array(
		'-1' => esc_html__( 'Never', 'wp-security-audit-log' ),
	);
	foreach ( $schedules as $schedule => $text ) {
		$options[ $schedule ] = $text['display'];
	}

	Settings::build_option(
		array(
			'name'    => \esc_html__( 'Clear mails log table every', '0-day-analytics' ),
			'id'      => 'advana_mail_logging_clear',
			'type'    => 'select',
			'options' => $options,
			'default' => Settings::get_option( 'advana_mail_logging_clear' ),
		)
	);
	?>
</div>
<?php
	// Mail SMTP options.
	Settings::build_option(
		array(
			'title' => \esc_html__( 'External SMTP provider', '0-day-analytics' ),
			'id'    => 'table-settings-options',
			'type'  => 'header',
		)
	);

	Settings::build_option(
		array(
			'name'    => \esc_html__( 'Host: ', '0-day-analytics' ),
			'id'      => 'smtp_host',
			'type'    => 'text',
			'hint'    => \esc_html__( 'Here you can provide your custom SMTP host', '0-day-analytics' ),
			'default' => Settings::get_option( 'smtp_host' ),
		)
	);

	Settings::build_option(
		array(
			'name'    => \esc_html__( 'Port', '0-day-analytics' ),
			'id'      => 'smtp_port',
			'type'    => 'number',
			'min'     => 1,
			'max'     => 65535,
			'hint'    => \esc_html__( 'Set the port of you custom SMTP provider', '0-day-analytics' ),
			'default' => Settings::get_option( 'smtp_port' ),
		)
	);

	// ADD checkbox options - none, SSL, TLS.
	Settings::build_option(
		array(
			'name'    => \esc_html__( 'Encryption', '0-day-analytics' ),
			'id'      => 'encryption_type',
			'type'    => 'radio',
			'options' => array(
				'none' => \esc_html__( 'None', '0-day-analytics' ),
				'ssl'  => \esc_html__( 'SSL', '0-day-analytics' ),
				'tls'  => \esc_html__( 'TLS', '0-day-analytics' ),
			),
			'hint'    => \esc_html( 'Select the encryption type to use for SMTP connection.', '0-day-analytics' ),
			'default' => Settings::get_option( 'encryption_type' ),
		)
	);

	Settings::build_option(
		array(
			'name'    => \esc_html__( 'Username: ', '0-day-analytics' ),
			'id'      => 'smtp_username',
			'type'    => 'text',
			'hint'    => \esc_html__( 'Provide the username for your custom SMTP provider.', '0-day-analytics' ),
			'default' => Settings::get_option( 'smtp_username' ),
		)
	);

	Settings::build_option(
		array(
			'name'     => \esc_html__( 'Password: ', '0-day-analytics' ),
			'id'       => 'smtp_password',
			'type'     => 'text',
			'validate' => 'password',
			'hint'     => \esc_html__( 'Provide the password (if any) for your custom SMTP provider', '0-day-analytics' ),
			'default'  => Settings::get_option( 'smtp_password' ),
		)
	);

	Settings::build_option(
		array(
			'name'    => \esc_html( 'Bypass SSL verification', '0-day-analytics' ),
			'id'      => 'bypass_ssl_verification',
			'type'    => 'checkbox',
			'hint'    => \esc_html( 'If enabled the plugin will bypass SSL certificate. This would be insecure if mail is delivered across the internet but could help in certain local and/or containerized WordPress scenarios.', '0-day-analytics' ),
			'default' => Settings::get_option( 'bypass_ssl_verification' ),
		)
	);

	Settings::build_option(
		array(
			'title' => \esc_html__( 'Test mail delivery', '0-day-analytics' ),
			'id'    => 'mail-settings-options-test',
			'type'  => 'header',
		)
	);

	Settings::build_option(
		array(
			'name'     => \esc_html__( 'Mail address to send test mail to', '0-day-analytics' ),
			'id'       => 'test_mail_address',
			'type'     => 'text',
			'validate' => 'email',
			'required' => true,
			'pattern'  => '([a-zA-Z0-9\._\%\+\-]+@[a-zA-Z0-9\.\-]+\.[a-zA-Z]{2,20}[,]{0,}){0,}',
		)
	);

	Settings::build_option(
		array(
			'add_label' => true,
			'id'        => 'mail_send_test_ajax',
			'type'      => 'button',
			'default'   => \esc_html__( 'Send test mail', '0-day-analytics' ),
		)
	);

	Settings::build_option(
		array(
			'id'      => 'mail_test_nonce',
			'type'    => 'hidden',
			'default' => \wp_create_nonce( Mail_SMTP_Settings::NONCE_NAME ),
		)
	);
