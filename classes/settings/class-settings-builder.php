<?php
/**
 * Class: Determine the context in which the plugin is executed.
 *
 * Helper class to determine the proper status of the request.
 *
 * @package awesome-footnotes
 *
 * @since 1.1.0
 */

// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped

declare(strict_types=1);

namespace ADVAN\Settings;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Upgrade notice class
 */
if ( ! class_exists( '\ADVAN\Settings\Settings_Builder' ) ) {
	/**
	 * Utility class for showing the upgrade notice in the plugins page.
	 *
	 * @package awe
	 *
	 * @since 1.1.0
	 */
	class Settings_Builder {
		/**
		 * The inner item id of the setting
		 *
		 * @var string
		 *
		 * @since 1.1.0
		 */
		public static $item_id;

		/**
		 * Additional attributes of the item.
		 *
		 * @var string
		 *
		 * @since 1.1.0
		 */
		public static $item_id_attr;

		/**
		 * Item wrapper - the id of the setting + '_item' suffix
		 *
		 * @var string
		 *
		 * @since 1.1.0
		 */
		public static $item_id_wrap;

		/**
		 * The name attribute of the element
		 *
		 * @var string
		 *
		 * @since 1.1.0
		 */
		public static $name_attr;

		/**
		 * Placeholder for the setting
		 *
		 * @var string
		 *
		 * @since 1.1.0
		 */
		public static $placeholder_attr;

		/**
		 * Custom class for the setting
		 *
		 * @var string
		 *
		 * @since 1.1.0
		 */
		public static $custom_class;

		/**
		 * The setting value
		 *
		 * @var mixed
		 *
		 * @since 1.1.0
		 */
		public static $current_value;

		/**
		 * The type of the setting
		 *
		 * @var string
		 *
		 * @since 1.1.0
		 */
		public static $option_type;

		/**
		 * The name of the setting
		 *
		 * @var string
		 *
		 * @since 1.1.0
		 */
		public static $option_name;

		/**
		 * Array with the setting settings
		 *
		 * @var array
		 *
		 * @since 1.1.0
		 */
		public static $settings;

		/**
		 * Integer - minimum value
		 *
		 * @var int
		 *
		 * @since 1.1.0
		 */
		public static $min;

		/**
		 * Integer - maximum value
		 *
		 * @var int
		 *
		 * @since 1.1.0
		 */
		public static $max;

		/**
		 * Integer - step value
		 *
		 * @var int
		 *
		 * @since 1.1.0
		 */
		public static $step;

		/**
		 * Holds the type of the edit HTML field
		 *
		 * @var string
		 *
		 * @since 1.1.0
		 */
		public static $edit_type;

		/**
		 * Holds the validation pattern for the text field
		 *
		 * @var string
		 *
		 * @since 1.1.0
		 */
		public static $validate_pattern;

		/**
		 * Holds the maximum characters for the text field
		 *
		 * @var string
		 *
		 * @since 1.1.0
		 */
		public static $max_chars;

		/**
		 * Holds the title attribute for the text field
		 *
		 * @var string
		 *
		 * @since 1.1.0
		 */
		public static $title_attr;

		/**
		 * The given field is required
		 *
		 * @var string
		 *
		 * @since 1.1.0
		 */
		public static $required;

		/**
		 * Builds the element
		 *
		 * @param array  $settings - Array with settings.
		 * @param string $option_name - Name of the option.
		 * @param mixed  $data - The data to show.
		 *
		 * @return void
		 *
		 * @since 1.1.0
		 */
		public static function create( $settings, $option_name, $data ) {

			self::$item_id          = null;
			self::$item_id_attr     = null;
			self::$item_id_wrap     = null;
			self::$name_attr        = null;
			self::$placeholder_attr = null;
			self::$custom_class     = null;
			self::$current_value    = null;
			self::$option_type      = null;
			self::$option_name      = null;
			self::$settings         = null;
			self::$edit_type        = null;
			self::$validate_pattern = null;
			self::$max_chars        = null;
			self::$min              = null;
			self::$max              = null;
			self::$step             = null;

			self::prepare_data( $settings, $option_name, $data );

			if ( empty( self::$option_type ) ) {
				return;
			}

			// Options Without Labels.
			$with_label = false;

			switch ( self::$option_type ) {
				case 'tab-title':
					self::tab_title();
					break;

				case 'header':
						self::section_head();
					break;

				case 'message':
				case 'success':
				case 'error':
						self::notice_message();
					break;

				case 'hidden':
						self::hidden();
					break;

				case 'html':
						self::html();
					break;

				default:
					$with_label = true;
					break;
			}

			// Options With Label.
			if ( $with_label ) {

				/** Option Start */
				self::option_head();

				/** The Option */
				switch ( self::$option_type ) {
					case 'text':
						self::text();
						break;

					case 'button':
						self::button();
						break;

					case 'arrayText':
						self::text_array();
						break;

					case 'number':
						self::number();
						break;

					case 'radio':
						self::radio();
						break;

					case 'checkbox':
						self::checkbox();
						break;

					case 'select-multiple':
						self::multiple_select();
						break;

					case 'textarea':
						self::textarea();
						break;

					case 'color':
						self::color();
						break;

					case 'posts':
						self::posts();
						break;

					case 'post':
						self::post();
						break;

					case 'editor':
						self::editor();
						break;

					case 'fonts':
						self::fonts();
						break;

					case 'upload':
						self::upload();
						break;

					case 'upload-font':
						self::upload_font();
						break;

					case 'typography':
						self::typography();
						break;

					case 'background':
						self::background();
						break;

					case 'select':
						self::select();
						break;

					case 'visual':
						self::visual();
						break;

					case 'gallery':
						self::gallery();
						break;

					case 'icon':
						self::icon();
						break;

					default:
						break;
				}

				/** Option END */
				if ( 'upload' !== self::$option_type ) {
					self::hint();
				}

				echo '</div>';

			}
		}

		/**
		 * HTML code
		 *
		 * @since 1.1.0
		 */
		private static function html() {

			if ( ! empty( self::$settings['content'] ) ) {
				echo self::$settings['content'];
			}
		}

		/**
		 * Setting Description
		 *
		 * @since 1.1.0
		 */
		private static function hint() {

			if ( ! empty( self::$settings['hint'] ) ) {
				?>
				<span class="extra-text">
				<?php echo self::$settings['hint']; ?>
				</span>
				<?php
			}
		}

		/**
		 * Upload
		 *
		 * @since 1.1.0
		 */
		private static function upload() {

			$upload_button = ! empty( self::$settings['custom_text'] ) ? self::$settings['custom_text'] : esc_html__( 'Upload', '0-day-analytics' );
			$image_preview = ! empty( self::$current_value ) ? self::$current_value : '/framework/admin/assets/images/empty.png';
			$hide_preview  = ! empty( self::$current_value ) ? '' : 'style="display:none"';
			?>

			<div class="image-preview-wrapper">
				<input <?php echo self::$item_id_attr; ?> <?php echo self::$name_attr; ?> class="aadvana-img-path" type="text"
					value="<?php echo esc_attr( self::$current_value ); ?>" <?php echo self::$placeholder_attr; ?>>
				<input id="<?php echo 'upload_' . self::$item_id . '_button'; ?>" type="button" class="aadvana-upload-img button"
					value="<?php echo $upload_button; ?>">

						<?php self::hint(); ?>
			</div>

			<div id="<?php echo self::$item_id . '-preview'; ?>" class="img-preview" <?php echo $hide_preview; ?>>
				<img src="<?php echo $image_preview; ?>" alt="">
				<a class="del-img"></a>
			</div>
			<div class="clear"></div>
			<?php
		}

		/**
		 * Upload Font
		 *
		 * @since 1.1.0
		 */
		private static function upload_font() {

			$upload_button = ! empty( self::$settings['custom_text'] ) ? self::$settings['custom_text'] : esc_html__( 'Upload', '0-day-analytics' );
			?>

			<div class="image-preview-wrapper">
				<input <?php echo self::$item_id_attr; ?> <?php echo self::$name_attr; ?> class="aadvana-font-path" type="text"
					value="<?php echo esc_attr( self::$current_value ); ?>" <?php echo self::$placeholder_attr; ?>>
				<input id="<?php echo 'upload_' . self::$item_id . '_button'; ?>" type="button" class="aadvana-upload-font button"
					value="<?php echo $upload_button; ?>">

						<?php self::hint(); ?>
			</div>
			<?php
		}

		/**
		 * Text
		 *
		 * @since 1.1.0
		 */
		private static function text() {
			$type_attr  = 'type="text"';
			$pattern    = '';
			$step       = '';
			$max_chars  = '';
			$title_attr = '';

			if ( ! empty( self::$edit_type ) ) {
				$type_attr = ' type="' . self::$edit_type . '"';
			}
			if ( ! empty( self::$title_attr ) ) {
				$title_attr = ' title="' . self::$title_attr . '"';
			}
			if ( ! empty( self::$validate_pattern ) ) {
				$pattern = ' pattern="' . self::$validate_pattern . '"';
			}
			if ( ! empty( self::$step ) ) {
				$step = ' step="' . self::$step . '"';
			}
			if ( ! empty( self::$max_chars ) ) {
				$max_chars = ' maxlength="' . self::$max_chars . '"';
			}
			?>
			<input <?php echo self::$item_id_attr; ?> <?php echo $title_attr; ?> <?php echo self::$name_attr; ?> <?php echo $type_attr; ?>	value="<?php echo esc_attr( self::$current_value ); ?>" <?php echo self::$placeholder_attr; ?><?php echo $pattern; ?><?php echo $max_chars; ?><?php echo ( ( self::$required ) ? ' required' : '' ); ?><?php echo $step; ?>>
			<?php
		}

		/**
		 * Button
		 *
		 * @since 5.0.0
		 */
		private static function button() {
			$type_attr = 'type="button"';
			$pattern   = '';
			$step      = '';
			$max_chars = '';
			if ( ! empty( self::$edit_type ) ) {
				$type_attr = ' type="' . self::$edit_type . '"';
			}
			if ( ! empty( self::$validate_pattern ) ) {
				$pattern = ' pattern="' . self::$validate_pattern . '"';
			}
			if ( ! empty( self::$step ) ) {
				$step = ' step="' . self::$step . '"';
			}
			if ( ! empty( self::$max_chars ) ) {
				$max_chars = ' maxlength="' . self::$max_chars . '"';
			}
			?>
			<input class="aadvana-primary-button button" <?php echo self::$item_id_attr; ?> <?php echo self::$name_attr; ?> <?php echo $type_attr; ?> value="<?php echo esc_attr( self::$current_value ); ?>" <?php echo self::$placeholder_attr; ?><?php echo $pattern; ?><?php echo $max_chars; ?><?php echo ( ( self::$required ) ? ' required' : '' ); ?><?php echo $step; ?>>
			<?php
		}

		/**
		 * Text Array
		 *
		 * @since 1.1.0
		 */
		private static function text_array() {

			$key           = self::$settings['key'];
			$single_name   = self::$option_name . '[' . $key . ']';
			$current_value = ! empty( self::$current_value[ $key ] ) ? self::$current_value[ $key ] : '';

			?>
			<input name="<?php echo $single_name; ?>" type="text" value="<?php echo $current_value; ?>"
			<?php echo self::$placeholder_attr; ?>>
			<?php
		}

		/**
		 * Checkbox
		 *
		 * @since 1.1.0
		 */
		private static function checkbox() {

			$checked = checked( self::$current_value, true, false );

			$toggle_data  = ! empty( self::$settings['toggle'] ) ? 'data-aadvana-toggle="' . self::$settings['toggle'] . '"' : '';
			$toggle_class = ! empty( self::$settings['toggle'] ) ? 'aadvana-toggle-option' : '';

			?>
				<input <?php echo self::$item_id_attr; ?> <?php echo self::$name_attr; ?>
					class="aadvana-js-switch <?php echo $toggle_class; ?>" <?php echo $toggle_data; ?> type="checkbox" value="true"
							<?php echo $checked; ?>>
							<?php
		}

		/**
		 * Radio
		 *
		 * @since 1.1.0
		 */
		private static function radio() {

			?>
			<div class="option-contents">
			<?php
					$i = 0;
			foreach ( self::$settings['options'] as $option_key => $option ) {
				++$i;

				$checked = '';
				if ( ( ! empty( self::$current_value ) && self::$current_value === $option_key ) || ( empty( self::$current_value ) && 1 === $i ) ) {
					$checked = 'checked="checked"';
				}

				?>
								<label>
									<input <?php echo self::$name_attr; ?> <?php echo $checked; ?> type="radio" value="<?php echo $option_key; ?>">
							<?php echo $option; ?>
								</label>
								<?php
			}

			?>
							</div>
							<div class="clear"></div>

			<?php
			if ( empty( self::$settings['toggle'] ) ) {
				return;
			}
			?>

							<script>
							jQuery(document).ready(function() {
								jQuery('.<?php echo esc_js( self::$item_id ); ?>-options').hide();
					<?php
					if ( isset( self::$settings['toggle'][ self::$current_value ] ) ) { // For the option that doesn't have sub option such as the Logo > Title option.
						if ( ! empty( self::$settings['toggle'][ self::$current_value ] ) ) {
							?>
								jQuery('<?php echo esc_js( self::$settings['toggle'][ self::$current_value ] ); ?>').show();
								<?php
						}
					} elseif ( is_array( self::$settings['toggle'] ) ) {
						$first_elem = reset( self::$settings['toggle'] )
						?>
								jQuery('<?php echo esc_js( $first_elem ); ?>').show();
							<?php
					}
					?>

								jQuery("input[name='<?php echo esc_js( self::$option_name ); ?>']").change(function() {
									selected_val = jQuery(this).val();
									jQuery('.<?php echo esc_js( self::$item_id ); ?>-options').slideUp('fast');
						<?php
						foreach ( self::$settings['toggle'] as $tg_item_name => $tg_item_id ) {
							if ( ! empty( $tg_item_id ) ) {
								?>

									if (selected_val == '<?php echo esc_js( $tg_item_name ); ?>') {
										jQuery('<?php echo esc_js( $tg_item_id ); ?>').slideDown('fast');
									}
												<?php
							}
						}
						?>
								});
							});
							</script>
			<?php
		}

		/**
		 * Multiple Select
		 *
		 * @since 1.1.0
		 */
		private static function multiple_select() {
			?>
				<select name="<?php echo self::$option_name . '[]'; ?>" <?php echo self::$item_id_attr; ?> multiple="multiple">

							<?php

							$data = maybe_unserialize( self::$current_value );

							$i = 0;
							foreach ( self::$settings['options'] as $option_key => $option ) {
								$selected = '';
								if ( ( ! empty( $data ) && ! is_array( $data ) && $data === $option_key ) || ( ! empty( $data ) && is_array( $data ) && in_array( $option_key, $data, true ) ) || ( empty( $data ) && 1 === $i ) ) {
									$selected = 'selected="selected"';
								}

								?>
					<option value="<?php echo $option_key; ?>" <?php echo $selected; ?>><?php echo $option; ?></option>
								<?php
							}
							?>
				</select>
			<?php
		}

		/**
		 * Textarea
		 *
		 * @since 1.1.0
		 */
		private static function textarea() {
			?>
			<textarea <?php echo self::$item_id_attr; ?> <?php echo self::$name_attr; ?>
				rows="3"><?php echo esc_textarea( self::$current_value ); ?></textarea>
			<?php
		}

		/**
		 * Color
		 *
		 * @since 1.1.0
		 */
		private static function color() {

			$custom_class = ! empty( self::$settings['color_class'] ) ? self::$settings['color_class'] : 'figaroColorSelector';

			?>

			<div class="aadvana-custom-color-picker">
				<input class="<?php echo $custom_class; ?>" <?php echo self::$item_id_attr; ?> <?php echo self::$name_attr; ?>
					type="text" value="<?php echo self::$current_value; ?>"
					data-palette="#000000, #9b59b6, #3498db, #2ecc71, #f1c40f, #34495e, #e74c3c"
					style="width:80px;">
			</div>
			<?php
		}

		/**
		 * Posts selector
		 *
		 * @since 1.1.0
		 */
		private static function posts() {
			?>
			<div class="aadvana-custom-posts-selector">
				<div style="width:99%;max-width:25em; float:left;">
					<select name="<?php echo self::$option_name . '[]'; ?>" <?php echo self::$item_id_attr; ?> multiple="multiple"
						style="width:100%">
						<?php
						$data = maybe_unserialize( self::$current_value );

						if ( ! is_null( $data ) ) {
							$args = array(
								'post_type' => 'any',
								'post__in'  => $data,
								'orderby'   => 'post__in',
							);
							// The Query.
							$query = new \WP_Query( $args );
							$posts = $query->posts;
							foreach ( $posts as $post ) {
								?>
						<option value="<?php echo $post->ID; ?>" selected="selected">
								<?php echo esc_html( $post->post_title ); ?>
						</option>
								<?php
							}
						}
						?>
					</select>
				</div>
			</div>
			<?php
		}

		/**
		 * Post
		 *
		 * @since 1.1.0
		 */
		private static function post() {
			?>
			<div class="aadvana-custom-post-selector">
				<input type="text" value="<?php echo esc_attr( get_the_title( self::$current_value ) ); ?>"
						<?php echo self::$placeholder_attr; ?>>
				<input <?php echo self::$item_id_attr; ?> <?php echo self::$name_attr; ?> type="hidden"
					value="<?php echo esc_attr( self::$current_value ); ?>" <?php echo self::$placeholder_attr; ?>>
			</div>
			<?php
		}

		/**
		 * Editor
		 *
		 * @since 1.1.0
		 */
		private static function editor() {

			// Settings.
			$settings                  = ! empty( self::$settings['editor'] ) ? self::$settings['editor'] : array(
				'editor_height' => '400px',
				'media_buttons' => false,
			);
			$settings['textarea_name'] = self::$option_name;

			self::$current_value = ! empty( self::$settings['kses'] ) ? wp_kses_stripslashes( stripslashes( self::$current_value ) ) : self::$current_value;

			if ( null === self::$current_value ) {
				self::$current_value = '';
			}

			\wp_editor(
				self::$current_value,
				self::$item_id,
				$settings
			);
		}

		/**
		 * Fonts
		 *
		 * @since 1.1.0
		 */
		private static function fonts() {
			?>
			<input <?php echo self::$name_attr; ?> <?php echo self::$item_id_attr; ?> class="aadvana-select-font" type="text" value="<?php echo esc_attr( self::$current_value ); ?>">
			<?php
		}

		/**
		 * Tab Title
		 *
		 * @since 1.1.0
		 */
		private static function tab_title() {
			?>
				<h2>
					<?php

					echo \esc_html( self::$settings['title'] );
					?>
				</h2>
			<?php
		}

		/**
		 * Notice Message
		 *
		 * @since 1.1.0
		 */
		private static function notice_message() {

			self::$custom_class .= ' aadvana-message-hint';

			if ( 'error' === self::$option_type ) {
				self::$custom_class .= ' aadvana-message-error';
			} elseif ( 'success' === self::$option_type ) {
				self::$custom_class .= ' aadvana-message-success';
			}

			?>
			<p <?php echo self::$item_id_wrap; ?> class="<?php echo self::$custom_class; ?>">
						<?php echo self::$settings['text']; ?>
			</p>
			<?php
		}

		/**
		 * Hidden
		 *
		 * @since 1.1.0
		 */
		private static function hidden() {
			?>
			<input <?php echo self::$item_id_attr; ?> <?php echo self::$name_attr; ?> type="hidden" value="<?php echo esc_attr( self::$current_value ); ?>">
			<?php
		}

		/**
		 * Number
		 *
		 * @since 1.1.0
		 */
		private static function number() {

			$min = ! empty( self::$min ) ? self::$min : -1000;
			$max = ! empty( self::$max ) ? self::$max : 1000000;

			?>
			<input style="width:100px" min="<?php echo $min; ?>" max="<?php echo $max; ?>" <?php echo self::$item_id_attr; ?> <?php echo self::$name_attr; ?> type="number" value="<?php echo esc_attr( self::$current_value ); ?>" <?php echo self::$placeholder_attr; ?>>
			<?php
		}

		/**
		 * Section Head
		 *
		 * @since 1.1.0
		 */
		private static function section_head() {
			?>

			<h3 <?php echo self::$item_id_attr; ?> class="aadvana-section-title <?php echo self::$custom_class; ?>">
			<?php

			echo self::$settings['title'];

			if ( ! empty( self::$settings['id'] ) ) {
				\do_action( 'awef_admin_after_head_title', self::$settings['id'] );
			}
			echo self::hint();
			?>
			</h3>

			<?php
		}

		/**
		 * Option Head
		 *
		 * @since 1.1.0
		 */
		private static function option_head() {
			// Everything is ok with not closed div - dont worry about it.
			?>
			<div <?php echo self::$item_id_wrap; ?> class="option-item <?php echo self::$custom_class; ?>">

			<?php

			if ( ! empty( self::$settings['pre_text'] ) ) {
				?>
				<div class="aadvana-option-pre-label"><?php echo self::$settings['pre_text']; ?></div>
				<div class="clear"></div>
				<?php
			}

			if ( ! empty( self::$settings['name'] ) ) {
				?>
				<span class="aadvana-label"><?php echo self::$settings['name']; ?></span>
				<?php
			}
		}

		/**
		 * Visual
		 *
		 * @since 1.1.0
		 */
		private static function visual() {
			?>
			<ul id="awef_<?php echo self::$item_id; ?>" class="aadvana-options">

			<?php

				$i = 0;

				$images_path = ! isset( self::$settings['external_images'] ) ? '/framework/admin/assets/images/' : '';

			foreach ( self::$settings['options'] as $option_key => $option ) {
				++$i;

				$checked = '';
				if ( ( ! empty( self::$current_value ) && self::$current_value === $option_key ) || ( empty( self::$current_value ) && 1 === $i ) ) {
					$checked = 'checked="checked"';
				}

				?>
					<li class="visual-option-<?php echo $option_key; ?>">
						<input <?php echo self::$name_attr; ?> type="radio" value="<?php echo $option_key; ?>"
						<?php echo $checked; ?>>
						<a class="checkbox-select" href="#">

						<?php

						if ( is_array( $option ) ) {
							foreach ( $option as $description => $img_data ) {

								if ( is_array( $img_data ) ) {

									$img_value = reset( $img_data );
									$key       = key( $img_data );
									unset( $img_data[ $key ] );

									$data_attr = '';
									if ( ! empty( $img_data ) && is_array( $img_data ) ) {
										foreach ( $img_data as $data_name => $data_value ) {
											$data_attr = ' data-' . $data_name . '="' . $data_value . '"';
										}
									}
									?>
							<img class="<?php echo $key; ?>" <?php echo $data_attr; ?>
								src="<?php echo $images_path . $img_value; ?>" alt="">
										<?php
								} else {
									?>
							<img src="<?php echo $images_path . $img_data; ?>" alt="">
										<?php
								}

								if ( ! empty( $description ) ) {
									?>
							<span><?php echo $description; ?></span>
										<?php
								}
							}
						} else {
							?>
							<img src="<?php echo $images_path . $option; ?>" alt="">
								<?php
						}
						?>
						</a>
					</li>
					<?php
			}
			?>
			</ul>
			<?php

			if ( empty( self::$settings['toggle'] ) ) {
				return;
			}
			?>

			<script>
			jQuery(document).ready(function() {
				jQuery('.<?php echo esc_js( self::$item_id ); ?>-options').hide();
				<?php
				if ( ! empty( self::$settings['toggle'][ self::$current_value ] ) ) {
					?>
				jQuery('<?php echo esc_js( self::$settings['toggle'][ self::$current_value ] ); ?>').show();
					<?php
				} elseif ( is_array( self::$settings['toggle'] ) ) {
					$first_elem = reset( self::$settings['toggle'] )
					?>
				jQuery('<?php echo esc_js( $first_elem ); ?>').show();
					<?php
				}
				?>

				jQuery(document).on('click', '#awef_<?php echo esc_js( self::$item_id ); ?> a', function() {
					selected_val = jQuery(this).parent().find('input').val();
					jQuery('.<?php echo esc_js( self::$item_id ); ?>-options').hide();
					<?php
					foreach ( self::$settings['toggle'] as $tg_item_name => $tg_item_id ) {
						if ( ! empty( $tg_item_id ) ) {
							?>
					if (selected_val == '<?php echo esc_js( $tg_item_name ); ?>') {
						jQuery('<?php echo esc_js( $tg_item_id ); ?>').slideDown('fast');

						// CodeMirror
						jQuery('<?php echo esc_js( $tg_item_id ); ?>').find('.CodeMirror').each(
							function(i,
								el) {
								el.CodeMirror.refresh();
							});
					}
							<?php
						}
					}
					?>
				});
			});
			</script>
			<?php
		}

		/**
		 * Gallery
		 *
		 * @since 1.1.0
		 */
		private static function gallery() {
			?>

			<input id="<?php echo esc_attr( self::$item_id ); ?>-upload" type="button"
			class="aadvana-upload-image aadvana-primary-buttonton button button-primary button-large"
			value="<?php esc_html_e( 'Add Image', '0-day-analytics' ); ?>">

			<ul id="<?php echo esc_attr( self::$item_id ); ?>-gallery-items" class="aadvana-gallery-items">
				<?php

						$counter = 0;

				if ( self::$current_value ) {

					$gallery = maybe_unserialize( self::$current_value );

					if ( is_array( $gallery ) ) {
						foreach ( $gallery as $slide ) {

							++$counter;
							?>

				<li id="listItem_<?php echo esc_attr( $counter ); ?>" class="ui-state-default">
					<div class="gallery-img img-preview">
							<?php echo wp_get_attachment_image( $slide['id'], 'thumbnail' ); ?>
						<input id="awef_post_gallery[<?php echo esc_attr( $counter ); ?>][id]"
							name="awef_post_gallery[<?php echo esc_attr( $counter ); ?>][id]"
							value="<?php echo esc_attr( $slide['id'] ); ?>" type="hidden" />
						<a class="del-img-all"></a>
					</div>
				</li>

							<?php
						}
					}
				}
				?>
			</ul>
			<script>
			var nextImgCell = <?php echo esc_js( $counter + 1 ); ?>;

			jQuery(document).ready(function() {
				jQuery(function() {
					jQuery("#<?php echo esc_attr( self::$item_id ); ?>-gallery-items").sortable({
						placeholder: "aadvana-state-highlight"
					});
				});

				// Uploading files
				var awef_slider_uploader;

				jQuery(document).on('click', '#<?php echo esc_attr( self::$item_id ); ?>-upload', function(
					event) {
					event.preventDefault();
					awef_slider_uploader = wp.media.frames.awef_slider_uploader = wp.media({
						title: '<?php esc_html_e( 'Add Image', '0-day-analytics' ); ?>',
						library: {
							type: 'image'
						},
						button: {
							text: '<?php esc_html_e( 'Select', '0-day-analytics' ); ?>'
						},
						multiple: true,
					});

					awef_slider_uploader.on('select', function() {
						var selection = awef_slider_uploader.state().get('selection');
						selection.map(function(attachment) {
							attachment = attachment.toJSON();
							jQuery(
									'#<?php echo esc_attr( self::$item_id ); ?>-gallery-items'
								)
								.append('\
										<li id="listItem_' + nextImgCell + '" class="ui-state-default">\
											<div class="gallery-img img-preview">\
												<img src="' + attachment.url + '" alt=""><input id="awef_post_gallery[' +
									nextImgCell + '][id]" name="awef_post_gallery[' +
									nextImgCell + '][id]" value="' + attachment.id + '" type="hidden">\
												<a class="del-img-all"></a>\
											</div>\
										</li>\
									');

							nextImgCell++;
						});
					});

					awef_slider_uploader.open();
				});
			});
			</script>
			<?php
		}

		/**
		 * Icon
		 *
		 * @since 1.1.0
		 */
		private static function icon() {
			?>
			<input <?php echo self::$item_id_attr; ?> <?php echo self::$name_attr; ?> type="hidden"
				value="<?php echo esc_attr( self::$current_value ); ?>">
			<div class="icon-picker-wrapper">
				<div id="preview-edit-icon-<?php echo esc_attr( self::$item_id ); ?>"
					data-target="#<?php echo esc_attr( self::$item_id ); ?>"
					class="button icon-picker fa <?php echo esc_attr( self::$current_value ); ?>"></div>
			</div>
			<?php
		}

		/**
		 * Select
		 *
		 * @since 1.1.0
		 */
		private static function select() {
			?>
			<div class="aadvana-custom-select">
				<select <?php echo self::$item_id_attr; ?> <?php echo self::$name_attr; ?>>
					<?php
					$i = 0;
					if ( ! empty( self::$settings['options'] ) && is_array( self::$settings['options'] ) ) {
						foreach ( self::$settings['options'] as $option_key => $option ) {
							++$i;

							$selected = '';
							if ( ( ! empty( self::$current_value ) && self::$current_value === $option_key ) || ( empty( self::$current_value ) && 1 === $i ) ) {
								$selected = 'selected="selected"';
							}
							?>

					<option value="<?php echo $option_key; ?>" <?php echo $selected; ?>><?php echo $option; ?></option>

							<?php
						}
					}
					?>
				</select>
			</div>

			<?php

			if ( ! empty( self::$settings['toggle'] ) ) {
				?>
			<script>
			jQuery(document).ready(function() {
				jQuery('.<?php echo esc_js( self::$item_id ); ?>-options').hide();

					<?php
					if ( ! empty( self::$settings['toggle'][ self::$current_value ] ) ) {
						?>
				jQuery('<?php echo esc_js( self::$settings['toggle'][ self::$current_value ] ); ?>').show();
						<?php
					} elseif ( is_array( self::$settings['toggle'] ) ) {
						$first_elem = reset( self::$settings['toggle'] )
						?>
				jQuery('<?php echo esc_js( $first_elem ); ?>').show();
						<?php
					}
					?>

				jQuery("select[name='<?php echo esc_js( self::$option_name ); ?>']").change(function() {
					selected_val = jQuery(this).val();
					jQuery('.<?php echo esc_js( self::$item_id ); ?>-options').slideUp('fast');

					<?php
					foreach ( self::$settings['toggle'] as $tg_item_name => $tg_item_id ) {
						if ( ! empty( $tg_item_id ) ) {
							?>
					if (selected_val == '<?php echo esc_js( $tg_item_name ); ?>') {
						jQuery('<?php echo esc_js( $tg_item_id ); ?>').slideDown('fast');
					}
							<?php
						}
					}

					?>
				});
			});
			</script>
				<?php
			}
		}

		/**
		 * Background
		 *
		 * @since 1.1.0
		 */
		private static function background() {

			$current_value = maybe_unserialize( self::$current_value );
			?>

			<input id="<?php echo esc_attr( self::$item_id ); ?>-img" class="aadvana-img-paaadvana-igaro-background-path"
			type="text" size="56" name="<?php echo esc_attr( self::$option_name ); ?>[img]" value="
									<?php
									if ( ! empty( $current_value['img'] ) ) {
										echo esc_attr( $current_value['img'] );}
									?>
			">
			<input id="upload_<?php echo esc_attr( self::$item_id ); ?>_button" type="button" class="button"
			value="<?php esc_html_e( 'Upload', '0-day-analytics' ); ?>">

			<div class="aadvana-background-options">

				<select name="<?php echo esc_attr( self::$option_name ); ?>[repeat]"
					id="<?php echo esc_attr( self::$item_id ); ?>[repeat]">
					<option value=""></option>
					<option value="no-repeat" 
					<?php
					if ( ! empty( $current_value['repeat'] ) ) {
						selected( $current_value['repeat'], 'no-repeat' );}
					?>
					><?php esc_html_e( 'no-repeat', '0-day-analytics' ); ?></option>
					<option value="repeat" 
					<?php
					if ( ! empty( $current_value['repeat'] ) ) {
						selected( $current_value['repeat'], 'repeat' );}
					?>
					><?php esc_html_e( 'Tile', '0-day-analytics' ); ?></option>
					<option value="repeat-x" 
					<?php
					if ( ! empty( $current_value['repeat'] ) ) {
						selected( $current_value['repeat'], 'repeat-x' );}
					?>
					><?php esc_html_e( 'Tile Horizontally', '0-day-analytics' ); ?></option>
					<option value="repeat-y" 
					<?php
					if ( ! empty( $current_value['repeat'] ) ) {
						selected( $current_value['repeat'], 'repeat-y' );}
					?>
					><?php esc_html_e( 'Tile Vertically', '0-day-analytics' ); ?></option>
				</select>

				<select name="<?php echo esc_attr( self::$option_name ); ?>[attachment]"
					id="<?php echo esc_attr( self::$item_id ); ?>[attachment]">
					<option value=""></option>
					<option value="fixed" 
					<?php
					if ( ! empty( $current_value['attachment'] ) ) {
						selected( $current_value['attachment'], 'fixed' );}
					?>
					><?php esc_html_e( 'Fixed', '0-day-analytics' ); ?></option>
					<option value="scroll" 
					<?php
					if ( ! empty( $current_value['attachment'] ) ) {
						selected( $current_value['attachment'], 'scroll' );}
					?>
					><?php esc_html_e( 'Scroll', '0-day-analytics' ); ?></option>
					<option value="cover" 
					<?php
					if ( ! empty( $current_value['attachment'] ) ) {
						selected( $current_value['attachment'], 'cover' );}
					?>
					><?php esc_html_e( 'Cover', '0-day-analytics' ); ?></option>
				</select>

				<select name="<?php echo esc_attr( self::$option_name ); ?>[hor]"
					id="<?php echo esc_attr( self::$item_id ); ?>[hor]">
					<option value=""></option>
					<option value="left" 
					<?php
					if ( ! empty( $current_value['hor'] ) ) {
						selected( $current_value['hor'], 'left' );}
					?>
					><?php esc_html_e( 'Left', '0-day-analytics' ); ?></option>
					<option value="right" 
					<?php
					if ( ! empty( $current_value['hor'] ) ) {
						selected( $current_value['hor'], 'right' );}
					?>
					><?php esc_html_e( 'Right', '0-day-analytics' ); ?></option>
					<option value="center" 
					<?php
					if ( ! empty( $current_value['hor'] ) ) {
						selected( $current_value['hor'], 'center' );}
					?>
					><?php esc_html_e( 'Center', '0-day-analytics' ); ?></option>
				</select>

				<select name="<?php echo esc_attr( self::$option_name ); ?>[ver]"
					id="<?php echo esc_attr( self::$item_id ); ?>[ver]">
					<option value=""></option>
					<option value="top" 
					<?php
					if ( ! empty( $current_value['ver'] ) ) {
						selected( $current_value['ver'], 'top' );}
					?>
					><?php esc_html_e( 'Top', '0-day-analytics' ); ?></option>
					<option value="bottom" 
					<?php
					if ( ! empty( $current_value['ver'] ) ) {
						selected( $current_value['ver'], 'bottom' );}
					?>
					><?php esc_html_e( 'Bottom', '0-day-analytics' ); ?></option>
					<option value="center" 
					<?php
					if ( ! empty( $current_value['ver'] ) ) {
						selected( $current_value['ver'], 'center' );}
					?>
					><?php esc_html_e( 'Center', '0-day-analytics' ); ?></option>
				</select>
			</div>

			<div id="<?php echo esc_attr( self::$item_id ); ?>-preview" class="img-preview" 
								<?php
								if ( empty( $current_value['img'] ) ) {
									echo 'style="display:none;"';}
								?>
								>
				<img src="
					<?php
					if ( ! empty( $current_value['img'] ) ) {
						echo esc_attr( $current_value['img'] );
					} else {
						echo '/framework/admin/assets/images/empty.png';
					}
					?>
					" alt="">
				<a class="del-img" title="<?php esc_html_e( 'Remove', '0-day-analytics' ); ?>"></a>
			</div>

			<?php
		}

		/**
		 * Typography
		 *
		 * @since 1.1.0
		 */
		private static function typography() {

			$current_value = wp_parse_args(
				self::$current_value,
				array(
					'size'        => '',
					'line_height' => '',
					'weight'      => '',
					'transform'   => '',
				)
			);

			?>

			<div class="aadvana-custom-select typography-custom-select">
				<select name="<?php echo esc_attr( self::$option_name ); ?>[size]"
					id="<?php echo esc_attr( self::$settings['id'] ); ?>[size]">

					<option <?php selected( $current_value['size'], '' ); ?> <?php disabled( 1, 1 ); ?>>
						<?php esc_html_e( 'Font Size in Pixels', '0-day-analytics' ); ?></option>
					<option value=""><?php esc_html_e( 'Default', '0-day-analytics' ); ?></option>
					<?php for ( $i = 8; $i < 61; $i++ ) { ?>
					<option value="<?php echo ( $i ); ?>" <?php selected( $current_value['size'], $i ); ?>>
						<?php echo ( $i ); ?></option>
					<?php } ?>
				</select>
			</div>

			<div class="aadvana-custom-select typography-custom-select">
				<select name="<?php echo esc_attr( self::$option_name ); ?>[line_height]"
					id="<?php echo esc_attr( self::$settings['id'] ); ?>[line_height]">

					<option <?php selected( $current_value['line_height'], '' ); ?> <?php disabled( 1, 1 ); ?>>
						<?php esc_html_e( 'Line Height', '0-day-analytics' ); ?></option>
					<option value=""><?php esc_html_e( 'Default', '0-day-analytics' ); ?></option>

					<?php
					for ( $i = 10; $i <= 60; $i += 2.5 ) {
						$line_height = $i / 10;
						?>
					<option value="<?php echo ( $line_height ); ?>"
						<?php selected( $current_value['line_height'], $line_height ); ?>>
						<?php echo ( $line_height ); ?>
					</option>
					<?php } ?>
				</select>
			</div>

			<div class="aadvana-custom-select typography-custom-select">
				<select name="<?php echo esc_attr( self::$option_name ); ?>[weight]"
					id="<?php echo esc_attr( self::$settings['id'] ); ?>[weight]">
					<option <?php selected( $current_value['weight'], '' ); ?> <?php disabled( 1, 1 ); ?>>
						<?php esc_html_e( 'Font Weight', '0-day-analytics' ); ?></option>
					<option value=""><?php esc_html_e( 'Default', '0-day-analytics' ); ?></option>
					<option value="100" <?php selected( $current_value['weight'], 100 ); ?>>
						<?php esc_html_e( 'Thin 100', '0-day-analytics' ); ?></option>
					<option value="200" <?php selected( $current_value['weight'], 200 ); ?>>
						<?php esc_html_e( 'Extra 200 Light', '0-day-analytics' ); ?></option>
					<option value="300" <?php selected( $current_value['weight'], 300 ); ?>>
						<?php esc_html_e( 'Light 300', '0-day-analytics' ); ?></option>
					<option value="400" <?php selected( $current_value['weight'], 400 ); ?>>
						<?php esc_html_e( 'Regular 400', '0-day-analytics' ); ?></option>
					<option value="500" <?php selected( $current_value['weight'], 500 ); ?>>
						<?php esc_html_e( 'Medium 500', '0-day-analytics' ); ?></option>
					<option value="600" <?php selected( $current_value['weight'], 600 ); ?>>
						<?php esc_html_e( 'Semi 600 Bold', '0-day-analytics' ); ?></option>
					<option value="700" <?php selected( $current_value['weight'], 700 ); ?>>
						<?php esc_html_e( 'Bold 700', '0-day-analytics' ); ?></option>
					<option value="800" <?php selected( $current_value['weight'], 800 ); ?>>
						<?php esc_html_e( 'Extra 800 Bold', '0-day-analytics' ); ?></option>
					<option value="900" <?php selected( $current_value['weight'], 900 ); ?>>
						<?php esc_html_e( 'Black 900', '0-day-analytics' ); ?></option>
				</select>
			</div>

			<div class="aadvana-custom-select typography-custom-select">
				<select name="<?php echo esc_attr( self::$option_name ); ?>[transform]"
					id="<?php echo esc_attr( self::$settings['id'] ); ?>[transform]">

					<option <?php selected( $current_value['transform'], '' ); ?> <?php disabled( 1, 1 ); ?>>
						<?php esc_html_e( 'Capitalization', '0-day-analytics' ); ?></option>
					<option value=""><?php esc_html_e( 'Default', '0-day-analytics' ); ?></option>
					<option value="uppercase" <?php selected( $current_value['transform'], 'uppercase' ); ?>>
						<?php esc_html_e( 'UPPERCASE', '0-day-analytics' ); ?></option>
					<option value="capitalize" <?php selected( $current_value['transform'], 'capitalize' ); ?>>
						<?php esc_html_e( 'Capitalize', '0-day-analytics' ); ?></option>
					<option value="lowercase" <?php selected( $current_value['transform'], 'lowercase' ); ?>>
						<?php esc_html_e( 'lowercase', '0-day-analytics' ); ?></option>
				</select>
			</div>
			<?php
		}

		/**
		 * Prepare Data
		 *
		 * @param array  $settings - Array with settings.
		 * @param string $option_name - Name of the option.
		 * @param mixed  $data - The data to show.
		 *
		 * @since 1.1.0
		 */
		private static function prepare_data( $settings, $option_name, $data ) {

			// Default Settings.
			$settings = \wp_parse_args(
				$settings,
				array(
					'id'    => '',
					'class' => '',
				)
			);

			self::$settings    = $settings;
			self::$option_name = $option_name;

			extract( $settings ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract

			self::$option_type = ! empty( $type ) ? $type : false;
			self::$required    = ! empty( $required ) ? $required : false;

			self::$min = ! empty( $min ) ? $min : null;
			self::$max = ! empty( $max ) ? $max : null;

			self::$step = ! empty( $step ) ? $step : null;

			if ( 'text' === self::$option_type ) {
				self::$edit_type        = ! empty( $validate ) ? $validate : false;
				self::$validate_pattern = ! empty( $pattern ) ? $pattern : false;
				self::$max_chars        = ! empty( $max_chars ) ? $max_chars : false;
				self::$title_attr       = ! empty( $title_attr ) ? $title_attr : false;
			}

			// ID.
			self::$item_id .= ! empty( $prefix ) ? $prefix . '-' : '';
			self::$item_id .= ! empty( $id ) ? $id : '';

			if ( ! empty( self::$item_id ) && ' ' !== self::$item_id ) {

				self::$item_id = ( 'arrayText' === $type ) ? self::$item_id . '-' . $key : self::$item_id;

				self::$item_id_attr = 'id="' . self::$item_id . '"';
				self::$item_id_wrap = 'id="' . self::$item_id . '-item"';
			}

			// Class.
			self::$custom_class = ! empty( $class ) ? ' ' . $class . '-options' : '';

			// Name.
			self::$name_attr = 'name="' . $option_name . '"';

			// Placeholder.
			self::$placeholder_attr = ! empty( $placeholder ) ? 'placeholder="' . $placeholder . '"' : '';

			// Get the option stored data.
			if ( ! empty( $data ) ) {
				self::$current_value = $data;
			} elseif ( ! empty( $default ) ) {
				self::$current_value = $default;
			}
		}
	}
}
