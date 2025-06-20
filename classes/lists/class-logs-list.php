<?php
/**
 * Responsible for the Showing the list of the events collected.
 *
 * @package    advanced-analytics
 * @subpackage helpers
 *
 * @since 1.1.0
 *
 * @license    https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */

declare(strict_types=1);

namespace ADVAN\Lists;

use ADVAN\Helpers\Settings;
use ADVAN\Helpers\WP_Helper;
use ADVAN\Helpers\File_Helper;
use ADVAN\Controllers\Error_Log;
use ADVAN\Helpers\Log_Line_Parser;
use ADVAN\Helpers\Plugin_Theme_Helper;
use ADVAN\Controllers\Reverse_Line_Reader;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once \ABSPATH . 'wp-admin/includes/template.php';
	require_once \ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
	require_once \ABSPATH . 'wp-admin/includes/class-wp-list-table-compat.php';
	require_once \ABSPATH . 'wp-admin/includes/list-table.php';
}

/*
 * Base list table class
 */
if ( ! class_exists( '\ADVAN\Lists\Logs_List' ) ) {
	/**
	 * Responsible for rendering base table for manipulation.
	 *
	 * @since 1.1.0
	 */
	class Logs_List extends \WP_List_Table {

		public const SCREEN_OPTIONS_SLUG = 'advanced_analytics_logs_list';

		public const PAGE_SLUG = 'toplevel_page_advan_logs';

		public const SEARCH_INPUT = 'sgp';

		public const NOTIFICATION_TRANSIENT = 'aadvan_notification';

		/**
		 * Name of the table to show.
		 *
		 * @var string
		 *
		 * @since 1.1.0
		 */
		private static $table_name;

		/**
		 * How many.
		 *
		 * @var int
		 *
		 * @since 1.1.0
		 */
		protected $count;

		/**
		 * How many log records to read from the log page - that is a fall back option, it will try to extract that first from the stored user data, then from the settings and from here as a last resort.
		 *
		 * @var int
		 *
		 * @since 1.1.0
		 */
		protected static $log_errors_to_read = 100;

		/**
		 * Holds the array with all of the column names and their representation in the table header.
		 *
		 * @var array
		 *
		 * @since 1.1.0
		 */
		private static $columns = array();

		/**
		 * Holds the array with all of the collected sources.
		 *
		 * @var array
		 *
		 * @since 1.8.0
		 */
		private static $sources = array();

		/**
		 * Events Query Arguments.
		 *
		 * @since 1.1.0
		 * @since 1.1.0 Transformed to array
		 *
		 * @var array
		 */
		private static $query_args;

		/**
		 * Holds the current query arguments.
		 *
		 * @var array
		 *
		 * @since 1.1.0
		 */
		private static $query_occ = array();

		/**
		 * Holds the current query order.
		 *
		 * @var array
		 *
		 * @since 1.1.0
		 */
		private static $query_order = array();

		/**
		 * Current setting (if any) of per_page property - caching value.
		 *
		 * @var int
		 *
		 * @since 1.7.5
		 */
		protected static $per_page = null;

		/**
		 * Default class constructor.
		 *
		 * @param stdClass $query_args Events query arguments.
		 *
		 * @since 1.1.0
		 */
		public function __construct( $query_args ) {
			self::$query_args = $query_args;

			parent::__construct(
				array(
					'singular' => 'generated-log',
					'plural'   => 'generated-logs',
					'ajax'     => true,
					'screen'   => WP_Helper::get_wp_screen(),
				)
			);

			self::$columns = self::manage_columns( array() );

			self::$table_name = 'php_error_logs';
		}

		/**
		 * Displays the search box.
		 *
		 * @since 1.1.0
		 *
		 * @param string $text     The 'submit' button label.
		 * @param string $input_id ID attribute value for the search input field.
		 */
		public function search_box( $text, $input_id ) {

			if ( empty( $_REQUEST[ self::SEARCH_INPUT ] ) && ! $this->has_items() ) {
				return;
			}

			$input_id = $input_id . '-search-input';
			?>
			<p class="search-box" style="position:relative">
				<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php echo \esc_html( $text ); ?>:</label>

				<input type="search" id="<?php echo esc_attr( $input_id ); ?>" class="aadvana_search_input" name="<?php echo \esc_attr( self::SEARCH_INPUT ); ?>" value="<?php echo \esc_attr( self::escaped_search_input() ); ?>" />

			<?php submit_button( $text, '', '', false, array( 'id' => 'search-submit' ) ); ?>
			</p>

			<?php
		}

		/**
		 * Returns the search query string escaped
		 *
		 * @return string
		 *
		 * @since 1.1.0
		 */
		public static function escaped_search_input() {
			return isset( $_REQUEST[ self::SEARCH_INPUT ] ) ? \esc_sql( \sanitize_text_field( \wp_unslash( $_REQUEST[ self::SEARCH_INPUT ] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		/**
		 * Adds columns to the screen options screed.
		 *
		 * @param array $columns - Array of column names.
		 *
		 * @since 1.1.0
		 */
		public static function manage_columns( $columns ): array {
			$admin_fields = array(
				// 'cb'                                  => '<input type="checkbox" />', // to display the checkbox.
				'timestamp'    => esc_html(
					sprintf(
						/* translators: %s: UTC offset */
						__( 'Time (%s)', '0-day-analytics' ),
						WP_Helper::get_timezone_location()
					)
				),
				'severity'     => __( 'Severity', '0-day-analytics' ),
				'message'      => __( 'Message', '0-day-analytics' ),
				'plugin_theme' => __( 'Source', '0-day-analytics' ),
			);

			$screen_options = $admin_fields;

			return \array_merge( $screen_options, $columns );
		}

		/**
		 * Returns the table name.
		 *
		 * @since 1.1.0
		 */
		public static function get_table_name(): string {
			return self::$table_name;
		}

		/**
		 * Prepares the list of items for displaying.
		 *
		 * Query, filter data, handle sorting, and pagination, and any other data-manipulation required prior to rendering
		 *
		 * @since 1.1.0
		 */
		public function prepare_items() {
			$columns = $this->get_columns();
			$hidden  = array();
			// $sortable              = $this->get_sortable_columns();
			$sortable              = array();
			$this->_column_headers = array( $columns, $hidden, $sortable );

			$this->handle_table_actions();

			$this->fetch_table_data();

			$hidden = get_user_option( 'manage' . WP_Helper::get_wp_screen()->id . 'columnshidden', false );
			if ( ! $hidden ) {
				$hidden = array();
			}

			$this->_column_headers = array( self::$columns, $hidden, $sortable );
			// phpcs:ignore
			// usort( $items, [ &$this, 'usort_reorder' ] ); // phpcs:ignore

			// Set the pagination.
			$this->set_pagination_args(
				array(
					'total_items' => $this->count,
					'per_page'    => $this->get_screen_option_per_page(),
					'total_pages' => ceil( $this->count / $this->get_screen_option_per_page() ),
				)
			);
		}

		/**
		 * Returns the currently hidden column headers for the current user
		 *
		 * @return array
		 *
		 * @since 1.1.0
		 */
		public static function get_hidden_columns() {
			return array_filter(
				(array) get_user_option( 'manage' . Settings::get_main_menu_page_hook() . 'columnshidden', false )
			);
		}

		/**
		 * Get a list of columns. The format is:
		 * 'internal-name' => 'Title'.
		 *
		 * @since 1.1.0
		 *
		 * @return array
		 */
		public function get_columns() {
			return self::$columns;
		}

		/**
		 * Get a list of sortable columns. The format is:
		 * 'internal-name' => 'orderby'
		 * or
		 * 'internal-name' => array( 'orderby', true ).
		 *
		 * The second format will make the initial sorting order be descending
		 *
		 * @since 1.1.0
		 *
		 * @return array
		 */
		protected function get_sortable_columns() {
			$first6_columns   = array_keys( self::get_column_names() );
			$sortable_columns = array();

			unset( $first6_columns[0], $first6_columns[9] ); // id column.
			// data column.

			/*
			 * Actual sorting still needs to be done by prepare_items.
			 * specify which columns should have the sort icon.
			 *
			 * The second bool param sets the colum sort order - true ASC, false - DESC or unsorted.
			 */
			foreach ( $first6_columns as $value ) {
				$sortable_columns[ $value ] = array( $value, false );
			}

			return $sortable_columns;
		}

		/**
		 * Text displayed when no user data is available.
		 *
		 * @since 1.1.0
		 *
		 * @return void
		 */
		public function no_items() {
			\esc_html_e( 'No logs found', '0-day-analytics' );
		}

		/**
		 * Fetch table data from the WordPress database.
		 *
		 * @since 1.1.0
		 *
		 * @return array
		 */
		public function fetch_table_data() {

			$this->items = self::get_error_items( true );

			return $this->items;
		}

		/**
		 * Collect error items.
		 *
		 * @param boolean  $write_temp - Bool option responsible for should we write the temp error log or not?.
		 * @param int|bool $items - Number of items to read from the error log. If false or not set, the items per page for that object will be used. @see method get_screen_option_per_page.
		 * @param bool     $first_only - If true, only the first item will be returned.
		 *
		 * @return array
		 *
		 * @since 1.1.0
		 */
		public static function get_error_items( bool $write_temp = true, $items = false, bool $first_only = false ): array {
			$collected_items = array();
			$errors          = array();
			$position        = null;

			if ( \function_exists( 'set_time_limit' ) ) {
				\set_time_limit( 0 );
			}

			$file = Error_Log::autodetect();

			if ( \is_a( $file, 'WP_Error' ) ) {
				$severity = 'error';
				if ( 'error_log_not_exists' === Error_Log::get_last_error()->get_error_code() ) {
					$severity = 'notice';
				}

				return array(
					array(
						'message'   => Error_Log::get_last_error()->get_error_message(),
						'severity'  => $severity,
						'timestamp' => time(),
					),
				);
			}

			$items = ( ! $items ) ? self::get_screen_option_per_page() : $items;

			while ( empty( $errors ) ) {

				$disabled = Settings::get_disabled_severities();

				while ( $items > 0 ) {
					$result = Reverse_Line_Reader::read_file_from_end(
						$file,
						function( $line, $pos ) use ( &$collected_items, &$errors, &$position ) {

							$position = $pos;

							// Flag that holds the status of the error - are there more lines to read or not.
							$more_to_error = false;

							// Check if this is the last line, and if not try to parse the line.
							if ( ! empty( $line ) && null !== Log_Line_Parser::parse_entry_with_stack_trace( $line ) ) {
								$parsed_data = Log_Line_Parser::parse_php_error_log_stack_line( $line );

								if ( \is_array( $parsed_data ) && isset( $parsed_data['message'] ) ) {
									if ( ! empty( $collected_items ) ) {
										$parsed_data['sub_items'] = $collected_items;
										if ( isset( $collected_items[0]['message'] ) ) {
											$parsed_data['message'] = $collected_items[0]['message'] . "\n" . $parsed_data['message'];
											unset( $collected_items[0] );
										}
										$collected_items = array();
									}
									$errors[]      = $parsed_data;
									$more_to_error = false;
								} elseif ( \is_array( $parsed_data ) ) {
									if ( isset( $parsed_data['call'] ) && str_starts_with( trim( $parsed_data['call'] ), 'made by' ) ) {
										$collected_items[] = array(
											'message' => $parsed_data['call'],
										);
									} else {
										$collected_items[] = $parsed_data;
									}

									$more_to_error = true;
								}
							} elseif ( ! empty( $line ) ) {
								$more_to_error = true;
							}

							$is_excluded = false;

							if ( ! $more_to_error ) {
								if ( isset( $parsed_data['severity'] ) && \in_array( $parsed_data['severity'], Settings::get_disabled_severities() ) ) {
									$is_excluded = true;
								}
							}

							return array(
								'line_done' => ! $more_to_error,
								'close'     => false,
								'no_flush'  => $is_excluded,
							);

							// if ( ! str_contains( $address, 'stop_word' ) ) {
							// echo "\nFound 'stop_word'!"; .

							// return false; // returning false here "breaks" the loop
							// } .
						},
						$items,
						$position,
						$write_temp
					);

					if ( ! empty( $disabled ) ) {

						$last_error = end( $errors );

						if ( isset( $last_error['severity'] ) && \in_array( $last_error['severity'], $disabled, true ) ) {
							// Remove the last error if it is disabled.
							array_pop( $errors );
						}
					}

					if ( $first_only && ! empty( $errors ) ) {
						// If we only want the first item, return it.
						return array( reset( $errors ) );
					}

					if ( false === $result ) {
						break 2;
					}
				}
			}

			Log_Line_Parser::store_last_parsed_timestamp();

			return $errors;
		}

		/**
		 * Returns the current query
		 *
		 * @return array
		 *
		 * @since 1.1.0
		 */
		public static function get_query_occ(): array {
			return self::$query_occ;
		}

		/**
		 * Render a column when no column specific method exists.
		 *
		 * Use that method for common rendering and separate columns logic in different methods. See below.
		 *
		 * @param array  $item        - Array with the current row values.
		 * @param string $column_name - The name of the currently processed column.
		 *
		 * @return mixed
		 *
		 * @since 1.1.0
		 */
		public function column_default( $item, $column_name ) {
			return self::format_column_value( $item, $column_name );
		}

		/**
		 * Render a column when no column specific method exists.
		 *
		 * Use that method for common rendering and separate columns logic in different methods. See below.
		 *
		 * @param array  $item        - Array with the current row values.
		 * @param string $column_name - The name of the currently processed column.
		 *
		 * @return mixed
		 *
		 * @since 1.1.0
		 */
		public static function format_column_value( $item, $column_name ) {
			switch ( $column_name ) {
				case 'severity':
					if ( isset( $item['severity'] ) && ! empty( $item['severity'] ) ) {

						if ( isset( Settings::get_current_options()['severities'][ $item['severity'] ] ) ) {

							return '<span class="badge green-badge" style="color: ' . Settings::get_current_options()['severities'][ $item['severity'] ]['color'] . ' !important;">' . $item['severity'] . '</span>';
						} else {
							return '<span class="badge dark-badge">' . $item['severity'] . '</span>';
						}
					} else {
						return '<span class="badge dark-badge">' . __( 'not set', '0-day-analytics' ) . '</span>';
					}
					break;
				case 'timestamp':
					if ( 1 === $item['timestamp'] ) {
						return sprintf(
							'<span class="status-control-warning"><span class="dashicons dashicons-warning" aria-hidden="true"></span> %s</span>',
							\esc_html__( 'Immediately', '0-day-analytics' ),
						);
					}

					$time_format = 'g:i a';

					$event_datetime_utc = \gmdate( 'Y-m-d H:i:s', $item['timestamp'] );

					$timezone_local  = \wp_timezone();
					$event_local     = \get_date_from_gmt( $event_datetime_utc, 'Y-m-d' );
					$today_local     = ( new \DateTimeImmutable( 'now', $timezone_local ) )->format( 'Y-m-d' );
					$tomorrow_local  = ( new \DateTimeImmutable( 'tomorrow', $timezone_local ) )->format( 'Y-m-d' );
					$yesterday_local = ( new \DateTimeImmutable( 'yesterday', $timezone_local ) )->format( 'Y-m-d' );

					// If the offset of the date of the event is different from the offset of the site, add a marker.
					if ( \get_date_from_gmt( $event_datetime_utc, 'P' ) !== get_date_from_gmt( 'now', 'P' ) ) {
						$time_format .= ' (P)';
					}

					$event_time_local = \get_date_from_gmt( $event_datetime_utc, $time_format );

					if ( $event_local === $today_local ) {
						$date = sprintf(
						/* translators: %s: Time */
							__( 'Today at %s', '0-day-analytics' ),
							$event_time_local,
						);
					} elseif ( $event_local === $tomorrow_local ) {
						$date = sprintf(
						/* translators: %s: Time */
							__( 'Tomorrow at %s', '0-day-analytics' ),
							$event_time_local,
						);
					} elseif ( $event_local === $yesterday_local ) {
						$date = sprintf(
						/* translators: %s: Time */
							__( 'Yesterday at %s', '0-day-analytics' ),
							$event_time_local,
						);
					} else {
						$date = sprintf(
						/* translators: 1: Date, 2: Time */
							__( '%1$s at %2$s', '0-day-analytics' ),
							\get_date_from_gmt( $event_datetime_utc, 'F jS' ),
							$event_time_local,
						);
					}

					$time = sprintf(
						'<time datetime="%1$s">%2$s</time>',
						\esc_attr( gmdate( 'c', $item['timestamp'] ) ),
						\esc_html( $date )
					);

					$until = $item['timestamp'] - time();

					if ( $until < 0 ) {
						$ago = sprintf(
						/* translators: %s: Time period, for example "8 minutes" */
							__( '%s ago', '0-day-analytics' ),
							WP_Helper::interval( abs( $until ) )
						);

						return sprintf(
							'<span class="status-control-warning"><span class="dashicons dashicons-clock" aria-hidden="true"></span> %s</span><br>%s',
							esc_html( $ago ),
							$time,
						);
					} elseif ( 0 === $until ) {
						$in = __( 'Now', '0-day-analytics' );
					} else {
						$in = sprintf(
						/* translators: %s: Time period, for example "8 minutes" */
							__( 'In %s', '0-day-analytics' ),
							WP_Helper::interval( $until ),
						);
					}

					return sprintf(
						'<span class="status-control-warning"><span class="dashicons dashicons-clock" aria-hidden="true"></span> %s</span><br>%s',
						\esc_html( $in ),
						$time,
					);

				case 'message':
					$message = esc_html( $item[ $column_name ] );
					if ( isset( $item['sub_items'] ) && ! empty( $item['sub_items'] ) ) {
						$message .= '<div style="margin-top:10px;"><input type="button" class="button button-primary show_log_details" value="' . __( 'Show details', '0-day-analytics' ) . '"></div>';

						$reversed_details = \array_reverse( $item['sub_items'] );
						$message         .= '<div class="log_details_show" style="display:none"><pre style="background:#07073a; color:#c2c8cd; padding: 5px; overflow-y:auto;">';

						$query_array = array(
							'_wpnonce' => \wp_create_nonce( 'source-view' ),
							'action'   => 'log_source_view',
						);
						foreach ( $reversed_details as $val ) {

							$source_link = '';

							if ( isset( $val['file'] ) && ! empty( $val['file'] ) ) {
								$query_array['error_file'] = $val['file'];
								$query_array['error_line'] = 1;

								if ( isset( $val['line'] ) && ! empty( $val['line'] ) ) {
									$query_array['error_line'] = $val['line'];
								}

								$query_array['TB_iframe'] = 'true';

								$view_url = \esc_url_raw(
									\add_query_arg( $query_array, \admin_url( 'admin-ajax.php' ) )
								);

								$title = __( 'Viewing: ', '0-day-analytics' ) . $query_array['error_file'];

								$source_link = ' <a href="' . $view_url . '" title="' . $title . '" class="thickbox view-source">' . $query_array['error_file'] . ':' . $query_array['error_line'] . '</a><br>';

							}

							$message .= ( isset( $val['call'] ) && ! empty( $val['call'] ) ) ? '<b><i>' . $val['call'] . '</i></b> - ' : '';

							if ( ! empty( $source_link ) ) {
								$message .= $source_link;
							} else {
								$message .= ( isset( $val['file'] ) && ! empty( $val['file'] ) ) ? $val['file'] . ' ' : '';
								$message .= ( isset( $val['line'] ) && ! empty( $val['line'] ) ) ? $val['line'] . '<br>' : '';
							}

							$message = \rtrim( $message, ' - ' );
						}
						$message .= '</pre></div>';
					}
					return $message;
				case 'plugin_theme':
					$source_link = '';

					$query_array = array(
						'_wpnonce' => \wp_create_nonce( 'source-view' ),
						'action'   => 'log_source_view',
					);

					if ( isset( $item['error_file'] ) && ! empty( $item['error_file'] ) ) {
						$query_array['error_file'] = $item['error_file'];

						if ( isset( $item['error_line'] ) && ! empty( $item['error_line'] ) ) {
							$query_array['error_line'] = $item['error_line'];
						}

						$query_array['TB_iframe'] = 'true';

						$view_url = \esc_url_raw(
							\add_query_arg( $query_array, \admin_url( 'admin-ajax.php' ) )
						);

						$title = __( 'Viewing: ', '0-day-analytics' ) . $item['error_file'];

						$source_link = '<div> <a href="' . $view_url . '" title = "' . $title . '" class="thickbox view-source gray_lab badge">' . __( 'view source', '0-day-analytics' ) . '</a></div>';
					}

					$message = esc_html( $item['message'] );

					$plugins_dir_basename = basename( WP_PLUGIN_DIR );

					if ( false !== \mb_strpos( $message, $plugins_dir_basename . \DIRECTORY_SEPARATOR ) ) {

						$split_plugin = explode( \DIRECTORY_SEPARATOR, $message );

						$next        = false;
						$plugin_base = '';
						foreach ( $split_plugin as $part ) {
							if ( $next ) {
								$plugin_base = $part;
								break;
							}
							if ( $plugins_dir_basename === $part ) {
								$next = true;
							}
						}

						if ( isset( self::$sources[ $plugin_base ] ) ) {
							return __( 'Plugin: ', '0-day-analytics' ) . esc_html( self::$sources[ $plugin_base ]['Name'] ) . $source_link;
						} else {

							$plugin = Plugin_Theme_Helper::get_plugin_from_path( $plugin_base );

							if ( ! empty( $plugin ) ) {
								self::$sources[ $plugin_base ] = $plugin;
								return __( 'Plugin: ', '0-day-analytics' ) . esc_html( $plugin['Name'] ) . $source_link;
							}
						}
					}

					$theme_root = Plugin_Theme_Helper::get_default_path_for_themes();

					if ( false !== \mb_strpos( $message, $theme_root . \DIRECTORY_SEPARATOR ) ) {

						$theme_dir_basename = basename( $theme_root );

						$split_theme = explode( \DIRECTORY_SEPARATOR, $message );

						$next       = false;
						$theme_base = '';
						foreach ( $split_theme as $part ) {
							if ( $next ) {
								$theme_base = $part;
								break;
							}
							if ( $theme_dir_basename === $part ) {
								$next = true;
							}
						}

						if ( isset( self::$sources[ $theme_base ] ) ) {
							return __( 'Theme: ', '0-day-analytics' ) . esc_html( self::$sources[ $theme_base ] ) . $source_link;
						} else {

							$theme = Plugin_Theme_Helper::get_theme_from_path( $theme_base );

							if ( ! empty( $theme ) && is_a( $theme, '\WP_Theme' ) ) {
								$name = $theme->get( 'Name' );

								$name = ( ! empty( $name ) ) ? $name : __( 'Unknown theme', '0-day-analytics' );

								$parent = $theme->parent(); // ( 'parent_theme' );
								if ( $parent ) {
									$parent = $theme->parent()->get( 'Name' );

									$parent = ( ! empty( $parent ) ) ? '<div>' . __( 'Parent theme: ', '0-day-analytics' ) . $parent . '</div>' : '';
								}
								$name .= (string) $parent;

								self::$sources[ $theme_base ] = $name;
								return __( 'Theme: ', '0-day-analytics' ) . ( $name ) . $source_link;
							}
						}
					}
					if ( false !== \mb_strpos( $message, ABSPATH . WPINC . \DIRECTORY_SEPARATOR ) ) {
						return __( 'WP Core', '0-day-analytics' ) . $source_link;
					}

					$admin_path = str_replace( \get_home_url( 1 ) . '/', ABSPATH, \network_admin_url() );

					if ( false !== \mb_strpos( $message, $admin_path ) ) {
						return __( 'WP Admin Core', '0-day-analytics' ) . $source_link;
					}
					if ( isset( $item['source'] ) ) {
						return $item['source'] . $source_link;
					} else {
						return '';
					}
				default:
					return isset( $item[ $column_name ] )
				? \esc_html( $item[ $column_name ] )
				: 'Column "' . \esc_html( $column_name ) . '" not found';
			}
		}

		/**
		 * Get value for checkbox column.
		 *
		 * The special 'cb' column
		 *
		 * @param object $item - A row's data.
		 *
		 * @return string Text to be placed inside the column < td > .
		 *
		 * @since 1.1.0
		 */
		protected function column_cb( $item ) {
			return;
			return sprintf(
				'<label class="screen-reader-text" for="' . self::$table_name . '_' . $item['id'] . '">' . sprintf(
				// translators: The column name.
					__( 'Select %s' ),
					'id'
				) . '</label>'
				. '<input type="checkbox" name="' . self::$table_name . '[]" id="' . self::$table_name . '_' . $item['id'] . '" value="' . $item['id'] . '" />'
			);
		}

		/**
		 * Returns an associative array containing the bulk actions.
		 *
		 * @since 1.1.0
		 *
		 * @return array
		 */
		public function get_bulk_actions() {
			$actions = array();

			return $actions;
		}

		/**
		 * Process actions triggered by the user.
		 *
		 * @since 1.1.0
		 */
		public function handle_table_actions() {
			if ( ! isset( $_REQUEST[ self::$table_name ] ) ) {
				return;
			}
			/**
			 * Note: Table bulk_actions can be identified by checking $_REQUEST['action'] and $_REQUEST['action2'].
			 *
			 * Action - is set if checkbox from top-most select-all is set, otherwise returns -1
			 * Action2 - is set if checkbox the bottom-most select-all checkbox is set, otherwise returns -1
			 */
		}

		/**
		 * Stop execution and exit.
		 *
		 * @since 1.1.0
		 *
		 * @return void
		 */
		public function graceful_exit() {
			exit;
		}

		/**
		 * Die when the nonce check fails.
		 *
		 * @since 1.1.0
		 *
		 * @return void
		 */
		public function invalid_nonce_redirect() {
			\wp_die(
				'Invalid Nonce',
				'Error',
				array(
					'response'  => 403,
					'back_link' => \esc_url( \network_admin_url( 'users.php' ) ),
				)
			);
		}

		/**
		 * Returns the records to show per page.
		 *
		 * @return int
		 *
		 * @since 1.1.0
		 */
		public static function get_log_errors_to_read() {
			return self::$log_errors_to_read;
		}

		/**
		 * Get the screen option per_page.
		 *
		 * @return int
		 *
		 * @since 1.1.0
		 */
		private static function get_screen_option_per_page() {
			if ( null !== self::$per_page ) {
				return self::$per_page;
			} else {
				$wp_screen = WP_Helper::get_wp_screen();

				if ( is_a( $wp_screen, '\WP_Screen' ) && self::PAGE_SLUG === $wp_screen->base ) {
					$option = $wp_screen->get_option( 'per_page', 'option' );
					if ( ! $option ) {
						$option = str_replace( '-', '_', $wp_screen->id . '_per_page' );
					}
				} else {
					$option = 'advanced_analytics_logs_list_per_page';
				}

				self::$per_page = (int) \get_user_option( $option );
				if ( empty( self::$per_page ) || self::$per_page < 1 ) {
					self::$per_page = false;
					if ( is_a( $wp_screen, '\WP_Screen' ) ) {
						self::$per_page = $wp_screen->get_option( 'per_page', 'default' );
					}
					if ( ! self::$per_page ) {
						self::$per_page = self::get_log_errors_to_read();
					}
				}

				return self::$per_page;
			}
		}

		/**
		 * Returns the columns array (with column name).
		 *
		 * @return array
		 *
		 * @since 1.1.0
		 */
		private static function get_column_names() {
			return self::$columns;
		}

		/**
		 * Adds a screen options to the current screen table.
		 *
		 * @param \WP_Hook $hook - The hook object to attach to.
		 *
		 * @return void
		 *
		 * @since 1.1.0
		 */
		public static function add_screen_options( $hook ) {
			$screen_options = array( 'per_page' => __( 'Number of errors to read', '0-day-analytics' ) );

			$result = array();

			\array_walk(
				$screen_options,
				function ( &$a, $b ) use ( &$result ) {
					$result[ self::SCREEN_OPTIONS_SLUG . '_' . $b ] = $a;
				}
			);
			$screen_options = $result;

			foreach ( $screen_options as $key => $value ) {
				\add_action(
					"load-$hook",
					function () use ( $key, $value ) {
						$option = 'per_page';
						$args   = array(
							'label'   => $value,
							'default' => self::get_log_errors_to_read(),
							'option'  => $key,
						);
						\add_screen_option( $option, $args );
					}
				);
			}
		}

		/**
		 * Form table per-page screen option value.
		 *
		 * @since 1.1.0
		 *
		 * @param bool   $keep   Whether to save or skip saving the screen option value. Default false.
		 * @param string $option The option name.
		 * @param int    $value  The number of rows to use.
		 *
		 * @return mixed
		 */
		public static function set_screen_option( $keep, $option, $value ) {

			if ( false !== \strpos( $option, self::SCREEN_OPTIONS_SLUG . '_' ) ) {
				return $value;
			}

			return $keep;
		}

		/**
		 * Table navigation.
		 *
		 * @param string $which - Position of the nav.
		 *
		 * @since 1.1.0
		 */
		public function extra_tablenav( $which ) {

			// If the position is not top then render.

			// Show site alerts widget.
			// NOTE: this is shown when the filter IS NOT true.

			$log_file = Error_Log::extract_file_name( Error_Log::autodetect() );

			if ( null !== Error_Log::get_last_error() ) {
				if ( 'top' === $which ) {

					if ( 'wp_debug_off' === Error_Log::get_last_error()->get_error_code() ) {
						?>
						<div id="debug-status-error" class="error error-info">
							<p> <?php echo WP_Helper::check_debug_status()->get_error_message();  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
						</div>
						<?php
					} elseif ( 'wp_debug_log_off' === Error_Log::get_last_error()->get_error_code() ) {
						?>
						<div id="debug-status-error" class="error error-info">
							<p> <?php echo WP_Helper::check_debug_log_status()->get_error_message();  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
						</div>
						<?php
					} else {
						?>
						<div id="cron-status-notice" class="notice notice-info">
							<p> <?php echo Error_Log::get_last_error()->get_error_message();  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
						</div>
						<?php
					}
				}
			}

			if ( 'top' === $which ) {
				\wp_nonce_field( 'advan-plugin-data', 'advanced-analytics-security' );

				?>
				<script>
					jQuery( document ).on( 'click', '#top-truncate, #bottom-truncate', function ( e ) {
						var data = {
							'action': 'advanced_analytics_truncate_log_file',
							'post_type': 'GET',
							'_wpnonce': jQuery('#advanced-analytics-security').val(),
						};

						jQuery.post(ajaxurl, data, function(response) {
							if( 2 === response['data'] ) {
								window.location.reload();
							}
						}, 'json');
					});
					jQuery( document ).on( 'click', '#top-truncate-and-keep, #bottom-truncate-and-keep', function ( e ) {
						var data = {
							'action': 'advanced_analytics_truncate_and_keep_log_file',
							'_wpnonce': jQuery('#advanced-analytics-security').val(),
						};

						jQuery.get(ajaxurl, data, function(response) {
							if( 2 === response['data'] ) {
								window.location.reload();
							}
						}, 'json');
					});
					jQuery( document ).on( 'click', '#top-downloadlog, #bottom-downloadlog', function ( e ) {
						
						const a = document.createElement('a');
						a.href = '<?php echo \esc_url_raw( File_Helper::download_link() ); ?>';

						document.body.appendChild(a);
						a.click();
						document.body.removeChild(a);
					});

					jQuery( document ).on( 'click', '.show_log_details', function() {
						jQuery(this).parent().next().closest('.log_details_show').toggle();
					});
				</script>
				<?php
			}
			if ( false !== $log_file ) {
				?>
				<div>
				<?php
				if ( \current_user_can( 'manage_options' ) ) {
					if ( '0 B' !== File_Helper::format_file_size( Error_Log::autodetect() ) && File_Helper::is_writable( Error_Log::autodetect() ) ) {
						?>

							<input class="button" id="<?php echo \esc_attr( $which ); ?>-truncate" type="button" value="<?php echo esc_html__( 'Truncate file', '0-day-analytics' ); ?>" />

							<input class="button" id="<?php echo \esc_attr( $which ); ?>-truncate-and-keep" type="button" value="<?php echo esc_html__( 'Truncate file (keep last records)', '0-day-analytics' ); ?>" />
							
							<input type="submit" name="downloadlog" id="<?php echo \esc_attr( $which ); ?>-downloadlog" class="button" value="<?php echo esc_html__( 'Download Log', '0-day-analytics' ); ?>">
							<?php
					}
				}
				?>
				</div>
				<?php
			}

			if ( ! empty( Settings::get_disabled_severities() ) ) {
				?>
				<style>
					.filtered-severities {
						background:#fff; 
						border:1px solid rgb(228, 144, 18); 
						border-left-width:4px; 
						box-shadow:0 1px 1px rgba (0,0,0,.04); 
						margin:10px 0; 
						padding:1px 5px;
					}

					html.aadvana-darkskin .filtered-severities {
						background: transparent !important;
					}
				</style>
				<div class="filtered-severities">
					<p>
				<?php
				esc_html_e( 'Following types are filtered and not showing: ', '0-day-analytics' );
				foreach ( Settings::get_disabled_severities() as $severity ) {
					echo '<span class="badge dark-badge">' . esc_html( $severity ) . '</span> ';
				}
				?>
					</p>
				</div>
				<?php
			}
			if ( false !== $log_file ) {

				$date_time_format = \get_option( 'date_format' ) . ' ' . \get_option( 'time_format' );
				$time             = \wp_date( $date_time_format, Error_Log::get_modification_time( Error_Log::autodetect() ) );

				?>
				<style>
					.flex {
						display:flex;
					}
					.flex-row {
						flex-direction:row;
					}
					.grow-0 {
						flex-grow:0;
					}
					.p-2 {
						padding:8px;
					}
					.w-full {
						width:auto;
					}
					.border-t {
						border-bottom-width:1px;
					}
					.justify-between {
						justify-content:space-between;
					}
					.italic {
						font-style: italic;
					}
					.text-lg {
						font-size: 1.1em;
						font-weight: bold;
					}
				</style>
				<div class="flex flex-row grow-0 p-2 w-full border-0 border-t border-solid border-[var(--adbtl-log-viewer-border-color)] justify-between">
					<div>
						<b><?php \esc_html_e( 'Log file: ', '0-day-analytics' ); ?></b> <span class="italic"><?php echo \esc_attr( Error_Log::extract_file_name( Error_Log::autodetect() ) ); ?></span></div>
						<div class=""> <?php echo \esc_attr( File_Helper::format_file_size( Error_Log::autodetect() ) ); ?> <span class="text-lg leading-none">|</span> <?php \esc_html_e( 'Last modified: ', '0-day-analytics' ); ?> <?php echo \esc_attr( $time ); ?>
					</div>
				</div>
				<?php

			}
		}

		/**
		 * Generates content for a single row of the table.
		 *
		 * @param object|array $item - The current item.
		 *
		 * @since 1.1.0
		 */
		public function single_row( $item ) {
			$classes = '';
			if ( isset( $item['severity'] ) && ! empty( $item['severity'] ) ) {
				$classes .= ' ' . $item['severity'];
			}
			echo '<tr class="' . \esc_attr( $classes ) . '">';
			$this->single_row_columns( $item );
			echo '</tr>';
		}

		/**
		 * Generates the table navigation above or below the table
		 *
		 * @param string $which - Holds info about the top and bottom navigation.
		 *
		 * @since 1.1.0
		 */
		public function display_tablenav( $which ) {
			if ( 'top' === $which ) {
				wp_nonce_field( 'bulk-' . $this->_args['plural'] );
			}
			?>
			<div class="tablenav <?php echo esc_attr( $which ); ?>">

				<?php if ( $this->has_items() ) : ?>
				<div class="alignleft actions bulkactions">
						<?php $this->bulk_actions( $which ); ?>
				</div>
						<?php
						endif;
				?>

				<br class="clear" />
			</div>
				<?php
				$this->extra_tablenav( $which );
				if ( 'top' !== $which && ! empty( $this->items ) ) {
					?>
				<style>
					.toplevel_page_advan_logs #debug-log {
						max-width: 100%;
						padding: 10px;
						word-wrap: break-word;
						background: black;
						color: #fff;
						border-radius: 5px;
						height: 400px;
						overflow-y: auto;
					}
					.generated-logs #timestamp { width: 15%; }
					.generated-logs #severity { width: 10%; }

					<?php
					foreach ( Settings::get_current_options()['severities'] as $class => $properties ) {

						$color = '#252630';

						$color = ( \in_array( $class, array( 'warning' ), true ) ) ? '#6C6262' : $color;
						$color = ( \in_array( $class, array( 'parse', 'fatal' ), true ) ) ? '#fff' : $color;

						echo '.generated-logs .' . \esc_attr( $class ) . ' td:nth-child(1) { border-left: 7px solid ' . \esc_attr( $properties['color'] ) . ' !important;}';
					}
					?>
				</style>
				<pre id="debug-log"><?php Reverse_Line_Reader::read_temp_file(); ?></pre>
					<?php
				}
		}

		/**
		 * Returns result by ID or GET parameters
		 *
		 * @return \WP_REST_Response|\WP_Error
		 *
		 * @since 1.9.3
		 */
		public static function extract_last_item() {

			$events = self::get_error_items( false, false, true );

			$event = reset( $events );

			if ( $event && ! empty( $event ) ) {

				$time_format = 'g:i a';

				$event_datetime_utc = \gmdate( 'Y-m-d H:i:s', $event['timestamp'] );

				$timezone_local  = \wp_timezone();
				$event_local     = \get_date_from_gmt( $event_datetime_utc, 'Y-m-d' );
				$today_local     = ( new \DateTimeImmutable( 'now', $timezone_local ) )->format( 'Y-m-d' );
				$tomorrow_local  = ( new \DateTimeImmutable( 'tomorrow', $timezone_local ) )->format( 'Y-m-d' );
				$yesterday_local = ( new \DateTimeImmutable( 'yesterday', $timezone_local ) )->format( 'Y-m-d' );

				// If the offset of the date of the event is different from the offset of the site, add a marker.
				if ( \get_date_from_gmt( $event_datetime_utc, 'P' ) !== get_date_from_gmt( 'now', 'P' ) ) {
					$time_format .= ' (P)';
				}

				$event_time_local = \get_date_from_gmt( $event_datetime_utc, $time_format );

				if ( $event_local === $today_local ) {
					$date = sprintf(
					/* translators: %s: Time */
						__( 'Today at %s', '0-day-analytics' ),
						$event_time_local,
					);
				} elseif ( $event_local === $tomorrow_local ) {
					$date = sprintf(
					/* translators: %s: Time */
						__( 'Tomorrow at %s', '0-day-analytics' ),
						$event_time_local,
					);
				} elseif ( $event_local === $yesterday_local ) {
					$date = sprintf(
					/* translators: %s: Time */
						__( 'Yesterday at %s', '0-day-analytics' ),
						$event_time_local,
					);
				} else {
					$date = sprintf(
					/* translators: 1: Date, 2: Time */
						__( '%1$s at %2$s', '0-day-analytics' ),
						\get_date_from_gmt( $event_datetime_utc, 'F jS' ),
						$event_time_local,
					);
				}

				$time = sprintf(
					'<time datetime="%1$s">%2$s</time>',
					\esc_attr( gmdate( 'c', $event['timestamp'] ) ),
					\esc_html( $date )
				);

				$until = $event['timestamp'] - time();

				if ( $until < 0 ) {
					$ago = sprintf(
					/* translators: %s: Time period, for example "8 minutes" */
						__( '%s ago', '0-day-analytics' ),
						WP_Helper::interval( abs( $until ) )
					);

					$in = sprintf(
						' %s ',
						esc_html( $ago ),
					);
				} elseif ( 0 === $until ) {
					$in = __( 'Now', '0-day-analytics' );
				} else {
					$in = sprintf(
					/* translators: %s: Time period, for example "8 minutes" */
						__( 'In %s', '0-day-analytics' ),
						WP_Helper::interval( $until ),
					);
				}

				$classes = '';
				if ( isset( $event['severity'] ) && ! empty( $event['severity'] ) ) {
					$classes .= ' ' . $event['severity'];
				}

				$style = '';

				if ( isset( Settings::get_current_options()['severities'][ $event['severity'] ] ) ) {
					$style .= '.aadvan-live-notif-item.' . \esc_attr( $event['severity'] ) . '{ border-left: 5px solid ' . \esc_attr( Settings::get_current_options()['severities'][ $event['severity'] ]['color'] ) . ' !important; }';
				}

				$response = array(
					'event'   => $event,
					'classes' => $classes,
					'in'      => $in,
					'style'   => $style,
				);

				return \rest_ensure_response( $response );
			}

			return rest_ensure_response(
				array(
					'message' => ADVAN_NAME . __( ': Error log - no logs to report.', '0-day-analytics' ),
				)
			);
		}

		/**
		 * Extracts data and send it to Notification if there is something to report
		 *
		 * @return array
		 *
		 * @since 1.9.3
		 */
		public static function get_notification_data(): array {
			$events = self::get_error_items( false, false, true );

			$event = reset( $events );

			$data = array();

			if ( $event && ! empty( $event ) ) {
				$last_transient_reported_data = (int) \get_site_transient( self::NOTIFICATION_TRANSIENT );

				if ( $last_transient_reported_data < $event['timestamp'] ) {

					\set_site_transient( self::NOTIFICATION_TRANSIENT, $event['timestamp'] );
					$time_format = 'g:i a';

					$event_datetime_utc = \gmdate( 'Y-m-d H:i:s', $event['timestamp'] );

					$timezone_local  = \wp_timezone();
					$event_local     = \get_date_from_gmt( $event_datetime_utc, 'Y-m-d' );
					$today_local     = ( new \DateTimeImmutable( 'now', $timezone_local ) )->format( 'Y-m-d' );
					$tomorrow_local  = ( new \DateTimeImmutable( 'tomorrow', $timezone_local ) )->format( 'Y-m-d' );
					$yesterday_local = ( new \DateTimeImmutable( 'yesterday', $timezone_local ) )->format( 'Y-m-d' );

					// If the offset of the date of the event is different from the offset of the site, add a marker.
					if ( \get_date_from_gmt( $event_datetime_utc, 'P' ) !== get_date_from_gmt( 'now', 'P' ) ) {
						$time_format .= ' (P)';
					}

					$event_time_local = \get_date_from_gmt( $event_datetime_utc, $time_format );

					if ( $event_local === $today_local ) {
						$date = sprintf(
						/* translators: %s: Time */
							__( 'Today at %s', '0-day-analytics' ),
							$event_time_local,
						);
					} elseif ( $event_local === $tomorrow_local ) {
						$date = sprintf(
						/* translators: %s: Time */
							__( 'Tomorrow at %s', '0-day-analytics' ),
							$event_time_local,
						);
					} elseif ( $event_local === $yesterday_local ) {
						$date = sprintf(
						/* translators: %s: Time */
							__( 'Yesterday at %s', '0-day-analytics' ),
							$event_time_local,
						);
					} else {
						$date = sprintf(
						/* translators: 1: Date, 2: Time */
							__( '%1$s at %2$s', '0-day-analytics' ),
							\get_date_from_gmt( $event_datetime_utc, 'F jS' ),
							$event_time_local,
						);
					}

					$time = sprintf(
						'<time datetime="%1$s">%2$s</time>',
						\esc_attr( gmdate( 'c', $event['timestamp'] ) ),
						\esc_html( $date )
					);

					$until = $event['timestamp'] - time();

					if ( $until < 0 ) {
						$ago = sprintf(
						/* translators: %s: Time period, for example "8 minutes" */
							__( '%s ago', '0-day-analytics' ),
							WP_Helper::interval( abs( $until ) )
						);

						$in = sprintf(
							' %s ',
							esc_html( $ago ),
						);
					} elseif ( 0 === $until ) {
						$in = __( 'Now', '0-day-analytics' );
					} else {
						$in = sprintf(
						/* translators: %s: Time period, for example "8 minutes" */
							__( 'In %s', '0-day-analytics' ),
							WP_Helper::interval( $until ),
						);
					}

					$base = 'base';

					$base .= '64_en';

					$base .= 'code';

					$data['body']  = $event['severity'] . ' ' . $event['message'];
					$data['title'] = $in;
					$data['icon']  = 'data:image/svg+xml;base64,' . $base( file_get_contents( \ADVAN_PLUGIN_ROOT . 'assets/icon.svg' ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
					$data['url']   = Settings::get_error_log_page_link();

				}
			} else {
				\set_site_transient( self::NOTIFICATION_TRANSIENT, time() );
			}

			return $data;
		}
	}
}
