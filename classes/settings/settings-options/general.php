<?php
/**
 * General settings of the plugin
 *
 * @package awe
 *
 * 2.0.0
 */

use ADVAN\Helpers\Settings;
use AWEF\Controllers\Footnotes_Formatter;

	Settings::build_option(
		array(
			'title' => esc_html__( 'General Settings', '0-day-analytics' ),
			'id'    => 'general-settings-tab',
			'type'  => 'tab-title',
		)
	);

	// Markup used.
	Settings::build_option(
		array(
			'title' => esc_html__( 'Markup', '0-day-analytics' ),
			'id'    => 'markup-format-settings',
			'type'  => 'header',
		)
	);

	Settings::build_option(
		array(
			'title' => esc_html__( 'Markup', '0-day-analytics' ),
			'id'    => 'markup-format-settings',
			'type'  => 'hint',
			'hint'  => esc_html__( 'How the markup should be represented in the documents', '0-day-analytics' ) . '<div>' . esc_html__( 'Changing the following settings will change functionality in a way which may stop footnotes from displaying correctly. For footnotes to work as expected after updating these settings, you will need to manually update all existing posts with footnotes.', '0-day-analytics' ) . '</div>',
		)
	);

	Settings::build_option(
		array(
			'name'    => esc_html__( 'Open footnote tag', '0-day-analytics' ),
			'id'      => 'footnotes_open',
			'type'    => 'text',
			'default' => Settings::get_current_options()['footnotes_open'],
		)
	);

	Settings::build_option(
		array(
			'name'    => esc_html__( 'Close footnote tag', '0-day-analytics' ),
			'id'      => 'footnotes_close',
			'type'    => 'text',
			'default' => Settings::get_current_options()['footnotes_close'],
		)
	);

	Settings::build_option(
		array(
			'type' => 'hint',
			'hint' => '<b><i>' . esc_html__( 'Example:', '0-day-analytics' ) . '</i></b><div>' . esc_html__( '"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.', '0-day-analytics' ) . Settings::get_current_options()['footnotes_open'] . '<b>' . esc_html__( 'Text of your footnote goes between these tags.', '0-day-analytics' ) . Settings::get_current_options()['footnotes_close']
			. '</b>"</div>',
		)
	);

	// Identifier settings begin.
	Settings::build_option(
		array(
			'title' => esc_html__( 'Identifier', '0-day-analytics' ),
			'id'    => 'identifier-format-settings',
			'type'  => 'header',
		)
	);

	Settings::build_option(
		array(
			'name'    => esc_html__( 'Type', '0-day-analytics' ),
			'id'      => 'list_style_type',
			'type'    => 'radio',
			'hint'    => esc_html__( 'How the footnotes will be represented', '0-day-analytics' ),
			'toggle'  => array(
				''       => '',
				'symbol' => '#list_style_symbol-item',
			),
			'options' => Footnotes_Formatter::get_styles(),
		)
	);

	Settings::build_option(
		array(
			'name'    => esc_html__( 'Symbol', '0-day-analytics' ),
			'id'      => 'list_style_symbol',
			'class'   => 'list_style_type',
			'type'    => 'text',
			'default' => Settings::get_default_options()['list_style_symbol'],
			'hint'    => esc_html__( 'Preview: ', '0-day-analytics' ) .
			'<b>' . html_entity_decode( Settings::get_current_options()['list_style_symbol'] ) . '</b>',
		)
	);

	Footnotes_Formatter::insert_styles();

	?>
	<style>
		.symbol-example ol.footnotes > li::marker {
			font-weight: bold;
		}
		.backlink-example ol.footnotes > li > span.footnote-back-link-wrapper {
			font-weight: bold;
		}
		.pre-show .pre-demo {
			font-weight: bold;
		}
		.post-show .post-demo {
			font-weight: bold;
		}
		.foot-header-example .pre-foot-example {
			font-weight: bold;;
		}
		.foot-footer-example .post-foot-example {
			font-weight: bold;;
		}
	</style>
	<?php

	$before_position = Settings::get_current_options()['position_before_footnote'];
	$back_link_title = Settings::get_current_options()['back_link_title'];

	if ( false !== \mb_strpos( $back_link_title, '###' ) ) {

		$text_pos = \strpos( $back_link_title, '###' );
		if ( false !== $text_pos ) {
			$back_link_title = \substr_replace( $back_link_title, (string) 1, $text_pos, \mb_strlen( '###' ) );
		}
	}

	$back_link = Settings::get_current_options()['pre_backlink'] . '<a href="#" class="footnote-link footnote-back-link" title="' . \esc_attr( $back_link_title ) . '" aria-label="' . esc_attr( $back_link_title ) . '" onclick="return false">' . Settings::get_current_options()['backlink'] . '</a>' . Settings::get_current_options()['post_backlink'] . '</span>';

	ob_start();
	?>
	<ol class="footnotes awepost_0">
		<li id="footnote_0_1" class="footnote">
			<span class="symbol"><?php echo html_entity_decode( Settings::get_current_options()['list_style_symbol'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>

			<?php echo ( ( $before_position ) ? $back_link : '' ); ?> <?php esc_html_e( 'First footnote', '0-day-analytics' ); ?> <?php echo ( ( ! $before_position ) ? $back_link : '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</li>
		<li id="footnote_1_1" class="footnote">
			<span class="symbol"><?php echo str_repeat( html_entity_decode( Settings::get_current_options()['list_style_symbol'] ), 2 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>


			<?php echo ( ( $before_position ) ? $back_link : '' ); ?> <?php esc_html_e( 'Second footnote', '0-day-analytics' ); ?> <?php echo ( ( ! $before_position ) ? $back_link : '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</li>
		<li id="footnote_2_1" class="footnote">
			<span class="symbol"><?php echo str_repeat( html_entity_decode( Settings::get_current_options()['list_style_symbol'] ), 3 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>


			
			<?php echo ( ( $before_position ) ? $back_link : '' ); ?> <?php esc_html_e( 'Third footnote', '0-day-analytics' ); ?> <?php echo ( ( ! $before_position ) ? $back_link : '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</li>
	</ol>
<?php
	$footnote_example = ob_get_clean();
	Settings::build_option(
		array(
			'type' => 'hint',
			'hint' => '<b><i>' . esc_html__( 'Example:', '0-day-analytics' ) . '</i></b><div class="symbol-example">' .
			$footnote_example
			. '</div>',
		)
	);

	Settings::build_option(
		array(
			'name' => esc_html__( 'Show identifier as superscript', '0-day-analytics' ),
			'id'   => 'superscript',
			'type' => 'checkbox',
		)
	);

	$id_replace = '<a href="#" class="footnote-link footnote-identifier-link" title="Lorem ipsum dolor sit" onclick="return false">1</a>';
	if ( Settings::get_current_options()['superscript'] ) {
		$id_replace = '<sup>' . $id_replace . '</sup>';
	}

	Settings::build_option(
		array(
			'type' => 'hint',
			'hint' => '<b><i>' . esc_html__( 'Example:', '0-day-analytics' ) . '</i></b><div>' . esc_html__( '"Lorem ipsum dolor sit amet', '0-day-analytics' ) . '<b>' . $id_replace . '</b>' . esc_html__( ', consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.', '0-day-analytics' ) . '"</div>',
		)
	);

	// Before identifiers.

	$id_replace = '<span class="pre-demo">' . Settings::get_current_options()['pre_identifier'] . '</span><a href="#" class="footnote-link footnote-identifier-link" title="Lorem ipsum dolor sit" onclick="return false"><span class="pre-demo">' . Settings::get_current_options()['inner_pre_identifier'] . '</span>1<span class="post-demo">' . Settings::get_current_options()['inner_post_identifier'] . '</span></a><span class="post-demo">' . Settings::get_current_options()['post_identifier'] . '</span>';
	if ( Settings::get_current_options()['superscript'] ) {
		$id_replace = '<sup>' . $id_replace . '</sup>';
	}

	Settings::build_option(
		array(
			'title' => esc_html__( 'Before identifiers', '0-day-analytics' ),
			'id'    => 'before-identifier-format-settings',
			'type'  => 'header',
		)
	);

	Settings::build_option(
		array(
			'name'    => esc_html__( 'Pre identifier', '0-day-analytics' ),
			'id'      => 'pre_identifier',
			'type'    => 'text',
			'default' => Settings::get_current_options()['pre_identifier'],
		)
	);

	Settings::build_option(
		array(
			'name'    => esc_html__( 'Inner pre identifier', '0-day-analytics' ),
			'id'      => 'inner_pre_identifier',
			'type'    => 'text',
			'default' => Settings::get_current_options()['inner_pre_identifier'],
		)
	);

	Settings::build_option(
		array(
			'type' => 'hint',
			'hint' => '<b><i>' . esc_html__( 'Example:', '0-day-analytics' ) . '</i></b><div class="pre-show">"' . esc_html__( 'Lorem ipsum dolor sit amet', '0-day-analytics' ) . '<b>' . $id_replace . '</b>' . esc_html__( ', consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.', '0-day-analytics' ) . '"</div>',
		)
	);

	// After identifiers.
	Settings::build_option(
		array(
			'title' => esc_html__( 'Post identifiers', '0-day-analytics' ),
			'id'    => 'post-identifier-format-settings',
			'type'  => 'header',
		)
	);

	Settings::build_option(
		array(
			'name'    => esc_html__( 'Post identifier', '0-day-analytics' ),
			'id'      => 'post_identifier',
			'type'    => 'text',
			'default' => Settings::get_current_options()['post_identifier'],
		)
	);

	Settings::build_option(
		array(
			'name'    => esc_html__( 'Inner post identifier', '0-day-analytics' ),
			'id'      => 'inner_post_identifier',
			'type'    => 'text',
			'default' => Settings::get_current_options()['inner_post_identifier'],
		)
	);

	Settings::build_option(
		array(
			'type' => 'hint',
			'hint' => '<b><i>' . esc_html__( 'Example:', '0-day-analytics' ) . '</i></b><div class="post-show">"' . esc_html__( 'Lorem ipsum dolor sit amet', '0-day-analytics' ) . '<b>' . $id_replace . '</b>' . esc_html__( ', consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.', '0-day-analytics' ) . '"</div>',
		)
	);

	// Back link.
	Settings::build_option(
		array(
			'title' => esc_html__( 'Back link', '0-day-analytics' ),
			'id'    => 'before-identifier-format-settings',
			'type'  => 'header',
		)
	);

	Settings::build_option(
		array(
			'name'    => esc_html__( 'Open back link tag', '0-day-analytics' ),
			'id'      => 'pre_backlink',
			'type'    => 'text',
			'default' => Settings::get_current_options()['pre_backlink'],
		)
	);

	Settings::build_option(
		array(
			'name'    => esc_html__( 'Close back link tag', '0-day-analytics' ),
			'id'      => 'post_backlink',
			'type'    => 'text',
			'default' => Settings::get_current_options()['post_backlink'],
		)
	);

	Settings::build_option(
		array(
			'name'    => esc_html__( 'Back link symbol', '0-day-analytics' ),
			'id'      => 'backlink',
			'type'    => 'text',
			'default' => Settings::get_current_options()['backlink'],
		)
	);

	Settings::build_option(
		array(
			'name' => esc_html__( 'Show backlink in the beginning of the footnote', '0-day-analytics' ),
			'id'   => 'position_before_footnote',
			'type' => 'checkbox',
		)
	);

	Settings::build_option(
		array(
			'type' => 'hint',
			'hint' => '<b><i>' . esc_html__( 'Example:', '0-day-analytics' ) . '</i></b><div class="backlink-example">' .
			$footnote_example
			. '</div>',
		)
	);

	// Back link title.
	Settings::build_option(
		array(
			'title' => esc_html__( 'Back link title', '0-day-analytics' ),
			'id'    => 'backlink-general-settings',
			'type'  => 'header',
		)
	);

	Settings::build_option(
		array(
			'name'    => esc_html__( 'Title to show on the backlinks', '0-day-analytics' ),
			'id'      => 'back_link_title',
			'type'    => 'text',
			'default' => Settings::get_current_options()['back_link_title'],
			'hint'    => '<b><i>' . esc_html__( 'Options:', '0-day-analytics' ) . '</i></b><div class="post-show">' . esc_html__( 'Add "###" (without the quotes) to include the footnote number.', '0-day-analytics' ) . '</div>',
		)
	);
