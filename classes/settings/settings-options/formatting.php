<?php
/**
 * Formatting settings of the plugin
 *
 * @package awe
 *
 * @since 2.0.0
 */

use ADVAN\Helpers\Settings;

	Settings::build_option(
		array(
			'title' => \esc_html__( 'Formatting Settings', 'advanced-analytics' ),
			'id'    => 'formatting-settings-tab',
			'type'  => 'tab-title',
		)
	);

	// Pretty tooltips formatting.
	Settings::build_option(
		array(
			'title' => \esc_html__( 'jQuery pretty tooltip', 'advanced-analytics' ),
			'id'    => 'jquery-pretty-tooltips-format-settings',
			'type'  => 'header',
		)
	);

	Settings::build_option(
		array(
			'name'    => \esc_html__( 'Use jQuery pretty tooltips for showing the footnotes', 'advanced-analytics' ),
			'id'      => 'pretty_tooltips',
			'type'    => 'checkbox',
			'default' => Settings::get_current_options()['pretty_tooltips'],
		)
	);

	// Vanilla tooltips formatting.
	Settings::build_option(
		array(
			'title' => \esc_html__( 'Vanilla JS tooltip (experimetal)', 'advanced-analytics' ),
			'id'    => 'vanilla-js-pretty-tooltips-format-settings',
			'type'  => 'header',
		)
	);

	Settings::build_option(
		array(
			'name'    => \esc_html__( 'Use vanilla JS tooltips for showing the footnotes.', 'advanced-analytics' ),
			'id'      => 'vanilla_js_tooltips',
			'type'    => 'checkbox',
			'hint'    => \esc_html__( 'This feature is still in its early stages, so if you encounter problems, please report them.', 'advanced-analytics' ),
			'default' => Settings::get_current_options()['vanilla_js_tooltips'],
		)
	);

	// Global header and footer settings.
	Settings::build_option(
		array(
			'title' => \esc_html__( ' Global header and footer settings', 'advanced-analytics' ),
			'id'    => 'global-header-footer-format-settings',
			'type'  => 'header',
		)
	);

	Settings::build_option(
		array(
			'name'    => \esc_html__( 'Do not use editor for footer and header', 'advanced-analytics' ),
			'id'      => 'no_editor_header_footer',
			'type'    => 'checkbox',
			'default' => Settings::get_current_options()['no_editor_header_footer'],
			'hint'    => \esc_html__( 'Enable this if you don\'t want to use editors for header and footer.', 'advanced-analytics' ),
		)
	);

	// Header section used.
	Settings::build_option(
		array(
			'title' => \esc_html__( 'Footnote header', 'advanced-analytics' ),
			'id'    => 'markup-format-settings',
			'type'  => 'header',
		)
	);

	Settings::build_option(
		array(
			'name'    => \esc_html__( 'Before footnotes', 'advanced-analytics' ),
			'id'      => 'pre_footnotes',
			'type'    => Settings::get_current_options()['no_editor_header_footer'] ? 'textarea' : 'editor',
			'hint'    => \esc_html__( 'Anything to be displayed before the footnotes at the bottom of the post can go here.', 'advanced-analytics' ),
			'default' => Settings::get_current_options()['pre_footnotes'],
		)
	);

	Settings::build_option(
		array(
			'type' => 'hint',
			'hint' => '<b><i>' . \esc_html__( 'Example:', 'advanced-analytics' ) . '</i></b><div class="foot-header-example">' .
			'<span class="pre-foot-example">' . Settings::get_current_options()['pre_footnotes'] . '</span>' .
			$footnote_example
			. Settings::get_current_options()['post_footnotes']
			. '</div>',
		)
	);

	// Header section used.
	Settings::build_option(
		array(
			'title' => \esc_html__( 'Footnote footer', 'advanced-analytics' ),
			'id'    => 'markup-format-settings',
			'type'  => 'header',
		)
	);

	Settings::build_option(
		array(
			'name'    => \esc_html__( 'After footnotes', 'advanced-analytics' ),
			'id'      => 'post_footnotes',
			'type'    => Settings::get_current_options()['no_editor_header_footer'] ? 'textarea' : 'editor',
			'hint'    => \esc_html__( 'Anything to be displayed after the footnotes at the bottom of the post can go here.', 'advanced-analytics' ),
			'default' => Settings::get_current_options()['post_footnotes'],
		)
	);

	Settings::build_option(
		array(
			'type' => 'hint',
			'hint' => '<b><i>' . \esc_html__( 'Example:', 'advanced-analytics' ) . '</i></b><div class="foot-footer-example">' .
			Settings::get_current_options()['pre_footnotes'] .
			$footnote_example
			. '<span class="post-foot-example">' . Settings::get_current_options()['post_footnotes'] . '</span>'
			. '</div>',
		)
	);
