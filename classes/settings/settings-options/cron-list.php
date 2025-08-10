<?php
/**
 * Option settings of the plugin
 *
 * @package awe
 *
 * @since 2.0.0
 */

use ADVAN\Helpers\Settings;
use ADVAN\Helpers\File_Helper;
use ADVAN\Helpers\System_Status;

	Settings::build_option(
		array(
			'title' => esc_html__( 'Cron Options', '0-day-analytics' ),
			'id'    => 'options-settings-tab',
			'type'  => 'tab-title',
		)
	);

	// Cron options.
	Settings::build_option(
		array(
			'title' => \esc_html__( 'Cron options', '0-day-analytics' ),
			'id'    => 'cron-settings-options',
			'type'  => 'header',
		)
	);

	Settings::build_option(
		array(
			'name'    => \esc_html__( 'Enable cron module', '0-day-analytics' ),
			'id'      => 'cron_module_enabled',
			'type'    => 'checkbox',
			'hint'    => \esc_html__( 'If you disable this, the entire plugin cron module will be disabled. The rest of the settings are global for your WP and they are separate from the module, so you can still change them. This applies only for the plugin module (if you are not using it and don\'t want it to take unnecessarily resources, your WP will continue working as before.', '0-day-analytics' ),
			'default' => Settings::get_option( 'cron_module_enabled' ),
		)
	);

	if ( ! is_writable( File_Helper::get_wp_config_file_path() ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable
		Settings::build_option(
			array(
				'text' => \esc_html__( 'WP Config is not writable - you can not make changes from here', '0-day-analytics' ),
				'id'   => 'wp_config_not_writable',
				'type' => 'error',
			)
		);
	} else {

		Settings::build_option(
			array(
				'name'    => \esc_html__( 'Global WP Cron disabled', '0-day-analytics' ),
				'id'      => 'wp_cron_disable',
				'type'    => 'checkbox',
				'default' => System_Status::environment_info()['wp_cron_disable'],
			)
		);

	}
