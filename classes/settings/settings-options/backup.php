<?php
/**
 * Import/Export settings of the plugin
 *
 * @package awe
 *
 * @since 1.1.0
 */

use ADVAN\Lists\Logs_List;
use ADVAN\Helpers\Settings;

	Settings::build_option(
		array(
			'title' => esc_html__( 'Export/Import Plugin Options', '0-day-analytics' ),
			'id'    => 'export-settings-tab',
			'type'  => 'tab-title',
		)
	);

	if ( isset( $_REQUEST['import'] ) ) {

		Settings::build_option(
			array(
				'text' => esc_html__( 'The plugin options have been imported successfully.', '0-day-analytics' ),
				'type' => 'message',
			)
		);
	}

	Settings::build_option(
		array(
			'title' => esc_html__( 'Export', '0-day-analytics' ),
			'id'    => 'export-settings',
			'type'  => 'header',
		)
	);

	?>

<div class="option-item">

	<p><?php esc_html_e( 'When you click the button below the plugin will create a .dat file for you to save to your computer.', '0-day-analytics' ); ?>
	</p>
	<p><?php esc_html_e( 'Once you’ve saved the download file, you can use the Import function in another WordPress installation to import the plugin options from this site.', '0-day-analytics' ); ?>
	</p>

	<p><a class="aadvana-primary-button button button-primary button-hero"
			href="
			<?php
			print \esc_url(
				\wp_nonce_url(
					\admin_url( 'admin.php?page=' . Logs_List::MENU_SLUG . '&export-settings' ),
					'export-plugin-settings',
					'export_nonce'
				)
			);
			?>
				"><?php esc_html_e( 'Download Export File', '0-day-analytics' ); ?></a>
	</p>
</div>

<?php

	Settings::build_option(
		array(
			'title' => \esc_html__( 'Import', '0-day-analytics' ),
			'id'    => 'import-settings',
			'type'  => 'header',
		)
	);

	?>

<div class="option-item">

	<p><?php \esc_html_e( 'Upload your .dat plugin options file and we will import the options into this site.', '0-day-analytics' ); ?>
	</p>
	<p><?php \esc_html_e( 'Choose a (.dat) file to upload, then click Upload file and import.', '0-day-analytics' ); ?></p>

	<p>
		<label for="upload"><?php \esc_html_e( 'Choose a file from your computer:', '0-day-analytics' ); ?></label>
		<input type="file" name="<?php echo \esc_attr( Settings::SETTINGS_FILE_FIELD ); ?>" id="aadvana-import-file" />
	</p>

	<p>
		<input type="submit" name="<?php echo \esc_attr( Settings::SETTINGS_FILE_UPLOAD_FIELD ); ?>" id="aadvana-import-upload" class="button-primary"
			value="<?php \esc_html_e( 'Upload file and import', '0-day-analytics' ); ?>" />
	</p>
</div>
