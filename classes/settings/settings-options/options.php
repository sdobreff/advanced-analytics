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
			'title' => esc_html__( 'Options', '0-day-analytics' ),
			'id'    => 'options-settings-tab',
			'type'  => 'tab-title',
		)
	);

	// Markup used.
	Settings::build_option(
		array(
			'title' => esc_html__( 'Suppress footnotes', '0-day-analytics' ),
			'id'    => 'markup-format-settings',
			'type'  => 'header',
		)
	);

	Settings::build_option(
		array(
			'name'    => esc_html__( 'Do not autodisplay in posts', '0-day-analytics' ),
			'id'      => 'no_display_post',
			'type'    => 'checkbox',
			'default' => Settings::get_current_options()['no_display_post'],
			'hint'    => esc_html__( 'Use this option if you want to display footnotes on separate place other than below the post (default). To achieve that you have to either use a shortcode ([awef_show_footnotes]), or direct PHP call (Footnotes_Formatter::show_footnotes();).', '0-day-analytics' ),
		)
	);

	Settings::build_option(
		array(
			'name'    => esc_html__( 'On the home page', '0-day-analytics' ),
			'id'      => 'no_display_home',
			'type'    => 'checkbox',
			'default' => Settings::get_current_options()['no_display_home'],
		)
	);

	Settings::build_option(
		array(
			'name'    => esc_html__( 'When displaying a preview', '0-day-analytics' ),
			'id'      => 'no_display_preview',
			'type'    => 'checkbox',
			'default' => Settings::get_current_options()['no_display_preview'],
		)
	);

	Settings::build_option(
		array(
			'name'    => esc_html__( 'In search results', '0-day-analytics' ),
			'id'      => 'no_display_search',
			'type'    => 'checkbox',
			'default' => Settings::get_current_options()['no_display_search'],
		)
	);

	Settings::build_option(
		array(
			'name'    => esc_html__( 'In the feed (RSS, Atom, etc.)', '0-day-analytics' ),
			'id'      => 'no_display_feed',
			'type'    => 'checkbox',
			'default' => Settings::get_current_options()['no_display_feed'],
		)
	);

	Settings::build_option(
		array(
			'name'    => esc_html__( 'In any kind of archive', '0-day-analytics' ),
			'id'      => 'no_display_archive',
			'type'    => 'checkbox',
			'default' => Settings::get_current_options()['no_display_archive'],
		)
	);

	Settings::build_option(
		array(
			'name'    => esc_html__( 'In category archives', '0-day-analytics' ),
			'id'      => 'no_display_category',
			'type'    => 'checkbox',
			'default' => Settings::get_current_options()['no_display_category'],
		)
	);

	Settings::build_option(
		array(
			'name'    => esc_html__( 'in date-based archives', '0-day-analytics' ),
			'id'      => 'no_display_date',
			'type'    => 'checkbox',
			'default' => Settings::get_current_options()['no_display_date'],
		)
	);

	// Priority.
	Settings::build_option(
		array(
			'title' => esc_html__( 'Priority', '0-day-analytics' ),
			'id'    => 'priority-format-settings',
			'type'  => 'header',
		)
	);

	Settings::build_option(
		array(
			'name'    => esc_html__( 'Plugin priority', '0-day-analytics' ),
			'id'      => 'priority',
			'type'    => 'number',
			'default' => Settings::get_current_options()['priority'],
		)
	);

	// Combine footnotes.
	Settings::build_option(
		array(
			'title' => esc_html__( 'Combine footnotes', '0-day-analytics' ),
			'id'    => 'priority-format-settings',
			'type'  => 'header',
		)
	);

	Settings::build_option(
		array(
			'name'    => esc_html__( 'Combine identical footnotes', '0-day-analytics' ),
			'id'      => 'combine_identical_notes',
			'type'    => 'checkbox',
			'default' => Settings::get_current_options()['combine_identical_notes'],
		)
	);

	// Custom CSS.
	Settings::build_option(
		array(
			'title' => esc_html__( 'Styling (CSS)', '0-day-analytics' ),
			'id'    => 'markup-format-settings',
			'type'  => 'header',
		)
	);

	Settings::build_option(
		array(
			'name'    => esc_html__( 'CSS footnotes', '0-day-analytics' ),
			'id'      => 'css_footnotes',
			'type'    => 'textarea',
			'hint'    => esc_html__( 'You can change the footnotes styling from here or leave it empty if you are using your own.', '0-day-analytics' ),
			'default' => Settings::get_current_options()['css_footnotes'],
		)
	);

	Settings::build_option(
		array(
			'type' => 'hint',
			'hint' => '<b><i>' . esc_html__( 'Example:', '0-day-analytics' ) . '</i></b><div class="symbol-example">' .
			$footnote_example
			. '</div>',
		)
	);
