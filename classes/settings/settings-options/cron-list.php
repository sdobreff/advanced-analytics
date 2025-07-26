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

$settings = Settings::get_current_options();

foreach ( $settings['severities'] as $name => $severity ) {
	$settings[ 'severity_colors_' . $name . '_color' ] = $severity['color'];
	$settings[ 'severity_show_' . $name . '_display' ] = $severity['display'];
}

Settings::set_current_options( $settings );

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
				'name'    => \esc_html__( 'WP Cron disabled', '0-day-analytics' ),
				'id'      => 'wp_cron_disable',
				'type'    => 'checkbox',
				'default' => System_Status::environment_info()['wp_cron_disable'],
			)
		);

	}
