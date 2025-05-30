<?php
/**
 * Option settings of the plugin
 *
 * @package awe
 *
 * @since 1.8.0
 */

use ADVAN\Helpers\Settings;
use ADVAN\Controllers\Slack;
use ADVAN\Controllers\Telegram;
use ADVAN\Controllers\Telegram_API;

$settings = Settings::get_current_options();

$settings['notification_default_slack_channel']    = $settings['slack_notifications']['all']['channel'];
$settings['notification_default_telegram_channel'] = $settings['telegram_notifications']['all']['channel'];

Settings::set_current_options( $settings );

	Settings::build_option(
		array(
			'title' => esc_html__( 'Notifications', '0-day-analytics' ),
			'id'    => 'options-settings-tab',
			'type'  => 'tab-title',
		)
	);

	// Slack options.
	Settings::build_option(
		array(
			'title' => \esc_html__( 'Slack options', '0-day-analytics' ),
			'id'    => 'slack-settings-options',
			'type'  => 'header',
		)
	);

	if ( Slack::is_set() ) {

		Settings::build_option(
			array(
				'name'        => esc_html__( 'Default channel name', '0-day-analytics' ),
				'type'        => 'text',
				'id'          => 'notification_default_slack_channel',
				'hint'        => esc_html__( 'By default Slack messages will be sent to this channel.', '0-day-analytics' ),
				'placeholder' => esc_html__( 'Channel name', '0-day-analytics' ),
			)
		);
	} else {
		Settings::build_option(
			array(
				'id'   => 'notification_default_slack_channel',
				'type' => 'error',
				'text' => '<span class="extra-text">' . esc_html__(
					'In order to send notifications via Slack messages please configure the Slack Bot token below.',
					'0-day-analytics'
				) . '</span>',
			)
		);
	}

	// Slack settings part start.
	Settings::build_option(
		array(
			'title' => esc_html__( 'Slack account', '0-day-analytics' ),
			'id'    => 'slack-notification-settings',
			'type'  => 'header',
			'hint'  => esc_html__( 'Refer to the ', '0-day-analytics' ) . '<a href="https://api.slack.com/quickstart" rel="nofollow" target="_blank">' . esc_html__( 'Slack integration documentation', '0-day-analytics' ) . '</a> ' . esc_html__( 'for a complete guide on how to set up an account and configure the integration.', '0-day-analytics' ),
		)
	);

	if ( Slack::is_set() ) {
		Settings::build_option(
			array(
				'type' => 'message',
				'text' => esc_html__( 'Your Slack token is set - if you want to remove it - type', '0-day-analytics' ) . ' <strong>REMOVE</strong>' . esc_html__( ' in the edit field below', '0-day-analytics' ),
			)
		);
	}

	Settings::build_option(
		array(
			'name' => esc_html__( 'Bot token: ', '0-day-analytics' ),
			'id'   => 'slack_notification_auth_token',
			'type' => 'text',
			'hint' => esc_html__( 'Get your bot token from the application from the "OAuth & Permissions" section. Type ', '0-day-analytics' ) . 'REMOVE' . esc_html__( ' if you want to remove the token from settings', '0-day-analytics' ),
		)
	);

	Settings::build_option(
		array(
			'id'      => 'slack_notification_nonce',
			'type'    => 'hidden',
			'default' => \wp_create_nonce( Slack::NONCE_NAME ),
		)
	);

	if ( ! Slack::is_set() ) {

		Settings::build_option(
			array(
				'add_label' => true,
				'id'        => 'slack_notification_store_settings_ajax',
				'type'      => 'button',
				'default'   => esc_html__( 'Save Slack settings', '0-day-analytics' ),
			)
		);
	}
	// Slack settings part end.

	// Telegram options.
	Settings::build_option(
		array(
			'title' => \esc_html__( 'Telegram options', '0-day-analytics' ),
			'id'    => 'telegram-settings-options',
			'type'  => 'header',
		)
	);

	if ( Telegram::is_set() ) {

		Settings::build_option(
			array(
				'name'        => esc_html__( 'Default channel ID', '0-day-analytics' ),
				'type'        => 'text',
				'hint'        => esc_html__( 'There you must provide the channel ID.', '0-day-analytics' ),
				'id'          => 'notification_default_telegram_channel',
				'hint'        => esc_html__( 'By default Telegram messages will be sent to this channel.', '0-day-analytics' ),
				'placeholder' => esc_html__( 'Channel name', '0-day-analytics' ),
			)
		);
	} else {
		Settings::build_option(
			array(
				'id'   => 'notification_default_telegram_channel',
				'type' => 'error',
				'text' => '<span class="extra-text">' . esc_html__(
					'In order to send notifications via Telegram messages please configure the Telegram Bot token below.',
					'0-day-analytics'
				) . '</span>',
			)
		);
	}

	// Telegram settings part start.
	Settings::build_option(
		array(
			'title' => esc_html__( 'Telegram account', '0-day-analytics' ),
			'id'    => 'telegram-notification-settings',
			'type'  => 'header',
			'hint'  => esc_html__( 'Refer to the ', '0-day-analytics' ) . '<a href="https://core.telegram.org/bots#how-do-i-create-a-bot" rel="nofollow" target="_blank">' . esc_html__( 'Telegram integration documentation', '0-day-analytics' ) . '</a> ' . esc_html__( 'for a complete guide on how to set up an account and configure the integration.', '0-day-analytics' ),
		)
	);

	if ( Telegram::is_set() ) {
		Settings::build_option(
			array(
				'type' => 'message',
				'text' => esc_html__( 'Your Telegram token is set - if you want to remove it - type', '0-day-analytics' ) . ' <strong>REMOVE</strong>' . esc_html__( ' in the edit field below', '0-day-analytics' ),
			)
		);
	}

	Settings::build_option(
		array(
			'name' => esc_html__( 'Bot token: ', '0-day-analytics' ),
			'id'   => 'telegram_notification_auth_token',
			'type' => 'text',
			'hint' => esc_html__( 'Get your bot token from the application from the "OAuth & Permissions" section. Type ', '0-day-analytics' ) . 'REMOVE' . esc_html__( ' if you want to remove the token from settings', '0-day-analytics' ),
		)
	);

	Settings::build_option(
		array(
			'id'      => 'telegram_notification_nonce',
			'type'    => 'hidden',
			'default' => \wp_create_nonce( Telegram::NONCE_NAME ),
		)
	);

	if ( ! Telegram::is_set() ) {


		Settings::build_option(
			array(
				'add_label' => true,
				'id'        => 'telegram_notification_store_settings_ajax',
				'type'      => 'button',
				'default'   => esc_html__( 'Save Telegram settings', '0-day-analytics' ),
			)
		);
	}
	// Telegram settings part end.
