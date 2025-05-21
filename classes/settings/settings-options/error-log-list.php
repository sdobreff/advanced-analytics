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
use ADVAN\Controllers\Error_Log;
use ADVAN\Helpers\System_Status;

$settings = Settings::get_current_options();

foreach ( $settings['severities'] as $name => $severity ) {
	$settings[ 'severity_colors_' . $name . '_color' ] = $severity['color'];
	$settings[ 'severity_show_' . $name . '_display' ] = $severity['display'];
}

Settings::set_current_options( $settings );

	Settings::build_option(
		array(
			'title' => esc_html__( 'Error Log Options', '0-day-analytics' ),
			'id'    => 'options-settings-tab',
			'type'  => 'tab-title',
		)
	);

	// Debugging options.
	Settings::build_option(
		array(
			'title' => \esc_html__( 'Debugging options', '0-day-analytics' ),
			'id'    => 'debugging-settings-options',
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

		$env_info = System_Status::environment_info();

		Settings::build_option(
			array(
				'name'    => \esc_html__( 'WP Debug Enable', '0-day-analytics' ),
				'id'      => 'wp_debug_enable',
				'type'    => 'checkbox',
				'default' => $env_info['wp_debug_mode'],
			)
		);

		if ( $env_info['wp_debug_mode'] ) {

			Settings::build_option(
				array(
					'name'    => \esc_html__( 'WP Debug Log Enabled', '0-day-analytics' ),
					'id'      => 'wp_debug_log_enable',
					'type'    => 'checkbox',
					'default' => $env_info['wp_debug_log'],
				)
			);

			Settings::build_option(
				array(
					'name'    => \esc_html__( 'WP Debug Display Errors in HTML', '0-day-analytics' ),
					'id'      => 'wp_debug_display_enable',
					'type'    => 'checkbox',
					'default' => $env_info['wp_debug_display'],
				)
			);

			if ( is_writable( \WP_CONTENT_DIR ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable

				$file_name = Error_Log::autodetect();

				if ( \is_a( $file_name, 'WP_Error' ) ) {

					$file_name = '';

				}

				Settings::build_option(
					array(
						'name'    => \esc_html__( 'WP Debug Log File Name', '0-day-analytics' ),
						'id'      => 'wp_debug_log_filename',
						'type'    => 'text',
						'default' => $file_name,
						'pattern' => '([a-zA-Z0-9\/\/\-\.\:])+',
					)
				);

				Settings::build_option(
					array(
						'name'    => \esc_html__( 'SECURITY: Generate WP Debug Log File Name', '0-day-analytics' ),
						'id'      => 'wp_debug_log_file_generate',
						'type'    => 'checkbox',
						'default' => false,
						'hint'    => \esc_html__( 'Check this if you want to generate new randomized filename for storing the error logs. This will always be uncheck if you refresh, check it only if you want new file name to be generated, and press Save Changes button. You are free to set whatever directory and file name you like above, but keep in mind that it needs to be writable from the script otherwise it wont work.', '0-day-analytics' ),
					)
				);
			}
		}
	}

	// Columns of types of errors showing.
	Settings::build_option(
		array(
			'title' => \esc_html__( 'Display these types of errors in the view', '0-day-analytics' ),
			'id'    => 'jquery-pretty-tooltips-format-settings',
			'type'  => 'header',
		)
	);

	foreach ( $settings['severities'] as $name => $severity ) {
		Settings::build_option(
			array(
				'name'    => $severity['name'],
				'id'      => 'severity_show_' . $name . '_display',
				'type'    => 'checkbox',
				'default' => $severity['display'],
			)
		);
	}

	// Error log coloring formatting.
	Settings::build_option(
		array(
			'title' => \esc_html__( 'Error severities coloring', '0-day-analytics' ),
			'id'    => 'jquery-pretty-tooltips-format-settings',
			'type'  => 'header',
		)
	);

	foreach ( $settings['severities'] as $name => $severity ) {

		Settings::build_option(
			array(
				'name'    => $severity['name'],
				'id'      => 'severity_colors_' . $name . '_color',
				'type'    => 'color',
				'default' => Settings::get_current_options(),
			)
		);

	}
