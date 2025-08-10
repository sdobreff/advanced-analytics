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

foreach ( $settings['severities'] as $name => $severity ) {
	$settings[ 'severity_colors_' . $name . '_color' ] = $severity['color'];
	$settings[ 'severity_show_' . $name . '_display' ] = $severity['display'];
}

Settings::set_current_options( $settings );

	Settings::build_option(
		array(
			'title' => esc_html__( 'Request Options', '0-day-analytics' ),
			'id'    => 'options-settings-tab',
			'type'  => 'tab-title',
		)
	);

	// Cron options.
	Settings::build_option(
		array(
			'title' => \esc_html__( 'Request options', '0-day-analytics' ),
			'id'    => 'cron-settings-options',
			'type'  => 'header',
		)
	);

	Settings::build_option(
		array(
			'name'    => \esc_html__( 'Enable requests module', '0-day-analytics' ),
			'id'      => 'requests_module_enabled',
			'type'    => 'checkbox',
			'hint'    => \esc_html__( 'If you disable this, the entire plugin requests module will be disabled. This applies only for the plugin module (if you are not using it and don\'t want it to take unnecessarily resources, your WP will continue working as before.', '0-day-analytics' ),
			'toggle'  => '#advana_requests_settings-item',
			'default' => Settings::get_option( 'requests_module_enabled' ),
		)
	);
	?>
<div id="advana_requests_settings-item">
	<?php
	Settings::build_option(
		array(
			'name'    => \esc_html__( 'Enable requests logging', '0-day-analytics' ),
			'id'      => 'advana_requests_enable',
			'type'    => 'checkbox',
			'toggle'  => '#advana_http_requests_disable-item, #advana_rest_requests_disable-item',
			'default' => Settings::get_current_options()['advana_requests_enable'],
		)
	);

	Settings::build_option(
		array(
			'name'    => \esc_html__( 'Disable HTTP Rrequests logging', '0-day-analytics' ),
			'id'      => 'advana_http_requests_disable',
			'type'    => 'checkbox',

			'default' => Settings::get_current_options()['advana_http_requests_disable'],
		)
	);

	Settings::build_option(
		array(
			'name'    => \esc_html__( 'Disable REST API Rrequests logging', '0-day-analytics' ),
			'id'      => 'advana_rest_requests_disable',
			'type'    => 'checkbox',

			'default' => Settings::get_current_options()['advana_rest_requests_disable'],
		)
	);

	$schedules = \wp_get_schedules();
	$options   = array(
		'-1' => esc_html__( 'Never', 'wp-security-audit-log' ),
	);
	foreach ( $schedules as $schedule => $text ) {
		$options[ $schedule ] = $text['display'];
	}

	Settings::build_option(
		array(
			'name'    => \esc_html__( 'Clear requests table every', '0-day-analytics' ),
			'id'      => 'advana_rest_requests_clear',
			'type'    => 'select',
			'options' => $options,
			'default' => Settings::get_option( 'advana_rest_requests_clear' ),
		)
	);
	?>
</div>
