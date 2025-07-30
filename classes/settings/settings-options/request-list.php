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
			'name'    => \esc_html__( 'Enable requests logging', '0-day-analytics' ),
			'id'      => 'advana_requests_disable',
			'type'    => 'checkbox',
			'toggle'  => '#advana_http_requests_disable-item, #advana_rest_requests_disable-item',
			'default' => ! Settings::get_current_options()['advana_requests_disable'],
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
