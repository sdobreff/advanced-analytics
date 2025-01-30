<?php
/**
 * Option settings of the plugin
 *
 * @package awe
 *
 * @since 2.0.0
 */

use ADVAN\Helpers\File_Helper;
use ADVAN\Helpers\Settings;
use ADVAN\Helpers\System_Status;

$settings = Settings::get_current_options();

foreach ( $settings['severity_colors'] as $name => $severity ) {
	$settings[ 'severity_colors_' . $name . '_color' ] = $severity['color'];
}

Settings::set_current_options( $settings );

	Settings::build_option(
		array(
			'title' => esc_html__( 'Error Log Options', 'advanced-analytics' ),
			'id'    => 'options-settings-tab',
			'type'  => 'tab-title',
		)
	);

	// Debugging options.
	Settings::build_option(
		array(
			'title' => \esc_html__( 'Debugging options', 'advanced-analytics' ),
			'id'    => 'jquery-pretty-tooltips-format-settings',
			'type'  => 'header',
		)
	);

	if ( ! is_writable( File_Helper::get_wp_config_file_path() ) ) {
		Settings::build_option(
			array(
				'text' => \esc_html__( 'WP Config is not writable - you can not make changes from here', 'advanced-analytics' ),
				'id'   => 'wp_config_not_writable',
				'type' => 'error',
			)
		);
	} else {

		$env_info = System_Status::environment_info();

		Settings::build_option(
			array(
				'name'     => \esc_html__( 'WP Debug Enable', 'advanced-analytics' ),
				'id'       => 'wp_debug_enable',
				'type'     => 'checkbox',
				'default'  => $env_info['wp_debug_mode'],
			)
		);

		Settings::build_option(
			array(
				'name'     => \esc_html__( 'WP Debug Display Errors in HTML', 'advanced-analytics' ),
				'id'       => 'wp_debug_display_enable',
				'type'     => 'checkbox',
				'default'  => $env_info['wp_debug_display'],
			)
		);
	}

	// Error log coloring formatting.
	Settings::build_option(
		array(
			'title' => \esc_html__( 'Error severities coloring', 'advanced-analytics' ),
			'id'    => 'jquery-pretty-tooltips-format-settings',
			'type'  => 'header',
		)
	);

	Settings::build_option(
		array(
			'name'    => \esc_html__( 'Error', 'advanced-analytics' ),
			'id'      => 'severity_colors_error_color',
			'type'    => 'color',
			'default' => Settings::get_current_options(),
		)
	);

	Settings::build_option(
		array(
			'name'    => \esc_html__( 'Deprecated', 'advanced-analytics' ),
			'id'      => 'severity_colors_deprecated_color',
			'type'    => 'color',
			'default' => Settings::get_current_options(),
		)
	);

	Settings::build_option(
		array(
			'name'    => \esc_html__( 'Success', 'advanced-analytics' ),
			'id'      => 'severity_colors_success_color',
			'type'    => 'color',
			'default' => Settings::get_current_options(),
		)
	);

	Settings::build_option(
		array(
			'name'    => \esc_html__( 'Info', 'advanced-analytics' ),
			'id'      => 'severity_colors_info_color',
			'type'    => 'color',
			'default' => Settings::get_current_options(),
		)
	);

	Settings::build_option(
		array(
			'name'    => \esc_html__( 'Notice', 'advanced-analytics' ),
			'id'      => 'severity_colors_notice_color',
			'type'    => 'color',
			'default' => Settings::get_current_options(),
		)
	);

	Settings::build_option(
		array(
			'name'    => \esc_html__( 'Warning', 'advanced-analytics' ),
			'id'      => 'severity_colors_warning_color',
			'type'    => 'color',
			'default' => Settings::get_current_options(),
		)
	);
