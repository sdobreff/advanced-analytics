<?php
/**
 * Advanced settings of the plugin
 *
 * @package awe
 *
 * @since 2.0.0
 */

use ADVAN\Helpers\Settings;

Settings::build_option(
	array(
		'title' => esc_html__( 'Advanced Settings', 'advanced-analytics' ),
		'id'    => 'advanced-settings-tab',
		'type'  => 'tab-title',
	)
);

Settings::build_option(
	array(
		'type'  => 'header',
		'id'    => 'advanced-settings',
		'title' => esc_html__( 'Advanced Settings', 'advanced-analytics' ),
	)
);

	Settings::build_option(
		array(
			'name'    => \esc_html__( 'Show plugin menu to admins only', 'advanced-analytics' ),
			'id'      => 'menu_admins_only',
			'type'    => 'checkbox',
			'default' => Settings::get_current_options()['menu_admins_only'],
		)
	);

	// Reset the settings options.
	Settings::build_option(
		array(
			'type'  => 'header',
			'id'    => 'reset-all-settings',
			'title' => esc_html__( 'Reset All Settings', 'advanced-analytics' ),
		)
	);

	Settings::build_option(
		array(
			'title' => esc_html__( 'Markup', 'advanced-analytics' ),
			'id'    => 'reset-settings-hint',
			'type'  => 'hint',
			'hint'  => esc_html__( 'This is destructive operation, which can not be undone! You may want to export your current settings first.', 'advanced-analytics' ),
		)
	);

	?>

	<div class="option-item">
		<a id="aadvana-reset-settings" class="aadvana-primary-button button button-primary button-hero aadvana-button-red" href="<?php print \esc_url( \wp_nonce_url( \admin_url( 'admin.php?page=' . self::MENU_SLUG . '&reset-settings' ), 'reset-plugin-settings', 'reset_nonce' ) ); ?>" data-message="<?php esc_html_e( 'This action can not be undone. Clicking "OK" will reset your plugin options to the default installation. Click "Cancel" to stop this operation.', 'advanced-analytics' ); ?>"><?php esc_html_e( 'Reset All Settings', 'advanced-analytics' ); ?></a>
	</div>

