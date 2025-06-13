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

					$file_name = Error_Log::get_error_log_file();

				}

				Settings::build_option(
					array(
						'name'    => \esc_html__( 'WP Debug Log File Name', '0-day-analytics' ),
						'id'      => 'wp_debug_log_filename',
						'type'    => 'text',
						'default' => $file_name,
						// 'pattern' => '(?:(?:[^<>:\"\|\?\*\n])+)',
						// 'pattern' => '^(?!.*[\\\/]\s+)(?!(?:.*\s|.*\.|\W+)$)(?:[a-zA-Z]:)?(?:(?:[^<>:"\|\?\*\n])+(?:\/\/|\/|\\\\|\\)?)+$',
						'pattern' => '^(?!.*[\\/]\s+)(?!(?:.*\s|.*\.|\W+)$)(?:[a-zA-Z]:)?(?:(?:[^<>:\|\?\*\n])+(?:\/\/|\/|\\|\\\)?)+$',
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

				Settings::build_option(
					array(
						'name'    => \esc_html__( 'WP Script Debug', '0-day-analytics' ),
						'id'      => 'wp_script_debug',
						'type'    => 'checkbox',
						'default' => $env_info['script_debug'],
					)
				);

				Settings::build_option(
					array(
						'name'    => \esc_html__( 'WP Save Queries', '0-day-analytics' ),
						'id'      => 'wp_save_queries',
						'type'    => 'checkbox',
						'default' => $env_info['save_queries'],
					)
				);

				Settings::build_option(
					array(
						'name'    => \esc_html__( 'WP Environment Type', '0-day-analytics' ),
						'id'      => 'wp_environment_type',
						'type'    => 'select',
						'default' => $env_info['wp_environment_type'],
						'options' => array(
							'production'  => \esc_html__( 'Production', '0-day-analytics' ),
							'local'       => \esc_html__( 'Local', '0-day-analytics' ),
							'development' => \esc_html__( 'Development', '0-day-analytics' ),
							'staging'     => \esc_html__( 'Staging', '0-day-analytics' ),

						),
					)
				);

				Settings::build_option(
					array(
						'name'    => \esc_html__( 'WP Development Mode', '0-day-analytics' ),
						'id'      => 'wp_development_mode',
						'type'    => 'select',
						'default' => $env_info['wp_development_mode'],
						'options' => array(
							''       => \esc_html__( 'Disabled', '0-day-analytics' ),
							'core'   => \esc_html__( 'Core', '0-day-analytics' ),
							'plugin' => \esc_html__( 'Plugin', '0-day-analytics' ),
							'theme'  => \esc_html__( 'Theme', '0-day-analytics' ),
							'all'    => \esc_html__( 'Staging', '0-day-analytics' ),

						),
					)
				);
			}
		} else {

			Settings::build_option(
				array(
					'name'    => \esc_html__( 'Keep monitoring', '0-day-analytics' ),
					'id'      => 'keep_reading_error_log',
					'type'    => 'checkbox',
					'hint'    => \esc_html__( 'Check this if you want to keep reading the error log file, even if WP Debug is not enabled. This will allow you to see the errors in the error log list view, if your system keeps logging errors using some other methods (direct php ini_set). Plugin will automatically try to detect that.', '0-day-analytics' ),
					'default' => Settings::get_current_options()['keep_reading_error_log'],
				)
			);
		}
	}

	// Error log file options.
	Settings::build_option(
		array(
			'title' => \esc_html__( 'Error log file options', '0-day-analytics' ),
			'id'    => 'error-log-file-settings',
			'type'  => 'header',
		)
	);

	Settings::build_option(
		array(
			'name'    => \esc_html__( 'How many records to keep when "Truncate file (keep last records)" button is pressed', '0-day-analytics' ),
			'id'      => 'keep_error_log_records_truncate',
			'type'    => 'number',
			'min'     => 1,
			'max'     => 100,
			'hint'    => \esc_html__( 'Set how many records to keep if you want to truncate file (reduce the size) but keep the last records. Maximum allowed number is 100, minimum is 1.', '0-day-analytics' ),
			'default' => Settings::get_current_options()['keep_error_log_records_truncate'],
		)
	);

	Settings::build_option(
		array(
			'name'    => \esc_html__( 'Do not monitor REST API for errors', '0-day-analytics' ),
			'id'      => 'no_rest_api_monitor',
			'type'    => 'checkbox',
			'hint'    => \esc_html__( 'By default, plugin tries to monitor WP REST API for errors and logs problems related to it. Check this if you want to disable this functionality.', '0-day-analytics' ),
			'default' => Settings::get_current_options()['no_rest_api_monitor'],
		)
	);

	// Columns of types of errors showing.
	Settings::build_option(
		array(
			'title' => \esc_html__( 'Display these types of errors in the view', '0-day-analytics' ),
			'id'    => 'errors-view-settings',
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
				'default' => $severity['color'],
			)
		);
	}
