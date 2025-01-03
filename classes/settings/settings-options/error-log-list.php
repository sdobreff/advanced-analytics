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
			'title' => esc_html__( 'Error Log Options', 'advanced-analytics' ),
			'id'    => 'options-settings-tab',
			'type'  => 'tab-title',
		)
	);