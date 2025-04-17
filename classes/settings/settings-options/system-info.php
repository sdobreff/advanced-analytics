<?php
/**
 * System info section of the plugin settings
 *
 * @package awe
 *
 * 2.0.0
 */

use ADVAN\Helpers\Settings;
use ADVAN\Helpers\System_Status;

Settings::build_option(
	array(
		'title' => esc_html__( 'System Info', '0-day-analytics' ),
		'id'    => 'advanced-settings-tab',
		'type'  => 'tab-title',
	)
);

Settings::build_option(
	array(
		'type'  => 'header',
		'id'    => 'advanced-settings',
		'title' => esc_html__( 'Environment Information', '0-day-analytics' ),
	)
);

System_Status::print_environment_info();
System_Status::print_plugins_info();
System_Status::print_theme_info();
System_Status::print_report();
