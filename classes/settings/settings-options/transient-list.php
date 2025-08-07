<?php
/**
 * Option settings of the plugin
 *
 * @package awe
 *
 * @since 2.0.0
 */

use ADVAN\Helpers\Settings;

	Settings::build_option(
		array(
			'title' => esc_html__( 'Transients Options', '0-day-analytics' ),
			'id'    => 'options-settings-tab',
			'type'  => 'tab-title',
		)
	);

	// Cron options.
	Settings::build_option(
		array(
			'title' => \esc_html__( 'Transients options', '0-day-analytics' ),
			'id'    => 'transients-settings-options',
			'type'  => 'header',
		)
	);

	Settings::build_option(
		array(
			'name'    => \esc_html__( 'Enable transients module', '0-day-analytics' ),
			'id'      => 'transients_module_enabled',
			'type'    => 'checkbox',
			'hint'    => \esc_html__( 'If you disable this, the entire plugin transients module will be disabled. This applies only for the plugin module (if you are not using it and don\'t want it to take unnecessarily resources, your WP will continue working as before.', '0-day-analytics' ),
			'default' => Settings::get_option( 'transients_module_enabled' ),
		)
	);
