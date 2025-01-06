<?php
/**
 * Option settings of the plugin
 *
 * @package awe
 *
 * @since 2.0.0
 */

use ADVAN\Helpers\Settings;

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

	// Pretty tooltips formatting.
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
