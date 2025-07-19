<?php
/**
 * Advanced settings of the plugin
 *
 * @package awe
 *
 * @since 1.1.0
 */

use ADVAN\Helpers\Settings;

Settings::build_option(
	array(
		'title' => esc_html__( 'Advanced Settings', '0-day-analytics' ),
		'id'    => 'advanced-settings-tab',
		'type'  => 'tab-title',
	)
);

Settings::build_option(
	array(
		'type'  => 'header',
		'id'    => 'advanced-settings',
		'title' => esc_html__( 'Advanced Settings', '0-day-analytics' ),
	)
);

	Settings::build_option(
		array(
			'name'    => \esc_html__( 'Show plugin menu to admins only', '0-day-analytics' ),
			'id'      => 'menu_admins_only',
			'type'    => 'checkbox',
			'default' => Settings::get_current_options()['menu_admins_only'],
		)
	);

	Settings::build_option(
		array(
			'name'    => \esc_html__( 'Show live notifications in admin bar', '0-day-analytics' ),
			'id'      => 'live_notifications_admin_bar',
			'type'    => 'checkbox',
			'default' => Settings::get_current_options()['live_notifications_admin_bar'],
		)
	);

	Settings::build_option(
		array(
			'name'    => \esc_html__( 'Show WP environment type in admin bar', '0-day-analytics' ),
			'id'      => 'environment_type_admin_bar',
			'type'    => 'checkbox',
			'default' => Settings::get_current_options()['environment_type_admin_bar'],
		)
	);

	Settings::build_option(
		array(
			'name'    => \esc_html__( 'Do not show the source of the config and settings files', '0-day-analytics' ),
			'id'      => 'protected_config_source',
			'type'    => 'checkbox',
			'default' => Settings::get_current_options()['protected_config_source'],
		)
	);

	Settings::build_option(
		array(
			'name'    => \esc_html__( 'Block all external requests', '0-day-analytics' ),
			'id'      => 'block_external_requests',
			'type'    => 'checkbox',
			'default' => $env_info['block_external_requests'],
		)
	);

	Settings::build_option(
		array(
			'name'    => \esc_html__( 'How many versions to show in options for plugin version switch', '0-day-analytics' ),
			'id'      => 'plugin_version_switch_count',
			'type'    => 'number',
			'min'     => 1,
			'max'     => 10,
			'hint'    => \esc_html__( 'Set how many version to show in the drop down to choose from when Plugin version switching. Maximum allowed number is 10, minimum is 1.', '0-day-analytics' ),
			'default' => Settings::get_current_options()['plugin_version_switch_count'],
		)
	);

	// Reset the settings options.
	Settings::build_option(
		array(
			'type'  => 'header',
			'id'    => 'reset-all-settings',
			'title' => esc_html__( 'Reset All Settings', '0-day-analytics' ),
		)
	);

	Settings::build_option(
		array(
			'title' => esc_html__( 'Markup', '0-day-analytics' ),
			'id'    => 'reset-settings-hint',
			'type'  => 'hint',
			'hint'  => esc_html__( 'This is destructive operation, which can not be undone! You may want to export your current settings first.', '0-day-analytics' ),
		)
	);

	?>

	<div class="option-item">
		<a id="aadvana-reset-settings" class="aadvana-primary-button button button-primary button-hero aadvana-button-red" href="<?php print \esc_url( \wp_nonce_url( \admin_url( 'admin.php?page=' . self::MENU_SLUG . '&reset-settings' ), 'reset-plugin-settings', 'reset_nonce' ) ); ?>" data-message="<?php esc_html_e( 'This action can not be undone. Clicking "OK" will reset your plugin options to the default installation. Click "Cancel" to stop this operation.', '0-day-analytics' ); ?>"><?php esc_html_e( 'Reset All Settings', '0-day-analytics' ); ?></a>
	</div>

