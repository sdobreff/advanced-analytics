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
