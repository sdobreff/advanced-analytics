<?php
/**
 * Option settings of the plugin
 *
 * @package awe
 *
 * @since 3.0.0
 */

use ADVAN\Helpers\Settings;

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
		)
	);

	Settings::build_option(
		array(
			'name' => esc_html__( 'Host: ', '0-day-analytics' ),
			'id'   => 'slack_notification_auth_token',
			'type' => 'text',
			'hint' => esc_html__( 'Get your bot token from the application from the "OAuth & Permissions" section. Type ', '0-day-analytics' ) . 'REMOVE' . esc_html__( ' if you want to remove the token from settings', '0-day-analytics' ),
		)
	);

	Settings::build_option(
		array(
			'name'    => \esc_html__( 'Port', '0-day-analytics' ),
			'id'      => 'keep_error_log_records_truncate',
			'type'    => 'number',
			'min'     => 1,
			'max'     => 100,
			'hint'    => \esc_html__( 'Set how many records to keep if you want to truncate file (reduce the size) but keep the last records. Maximum allowed number is 100, minimum is 1.', '0-day-analytics' ),
			'default' => Settings::get_current_options()['keep_error_log_records_truncate'],
		)
	);

	// ADD checkbox options - none, SSL, TLS.
	Settings::build_option(
		array(
			'name'    => \esc_html( 'Encryption', '0-day-analytics' ),
			'id'      => 'encryption_type',
			'type'    => 'radio',
			'options' => array(
				'none' => esc_html__( 'None', '0-day-analytics' ),
				'ssl'  => esc_html__( 'SSL', '0-day-analytics' ),
				'tls'  => esc_html__( 'TLS', '0-day-analytics' ),
			),
			'hint'    => \esc_html( 'Select the encryption type to use for SMTP connection.', '0-day-analytics' ),
			'default' => Settings::get_option( 'encryption_type' ),
		)
	);

	Settings::build_option(
		array(
			'name' => esc_html__( 'Username: ', '0-day-analytics' ),
			'id'   => 'slack_notification_auth_token',
			'type' => 'text',
			'hint' => esc_html__( 'Get your bot token from the application from the "OAuth & Permissions" section. Type ', '0-day-analytics' ) . 'REMOVE' . esc_html__( ' if you want to remove the token from settings', '0-day-analytics' ),
		)
	);

	Settings::build_option(
		array(
			'name' => esc_html__( 'Password: ', '0-day-analytics' ),
			'id'   => 'slack_notification_auth_token',
			'type' => 'text',
			'hint' => esc_html__( 'Get your bot token from the application from the "OAuth & Permissions" section. Type ', '0-day-analytics' ) . 'REMOVE' . esc_html__( ' if you want to remove the token from settings', '0-day-analytics' ),
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
