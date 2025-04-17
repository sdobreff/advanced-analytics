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
use ADVAN\Helpers\File_Helper;
use ADVAN\Controllers\Error_Log;
use ADVAN\Helpers\Log_Line_Parser;
use ADVAN\Helpers\Plugin_Theme_Helper;
use ADVAN\Controllers\Reverse_Line_Reader;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/template.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table-compat.php';
	require_once ABSPATH . 'wp-admin/includes/list-table.php';
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

		/**
		 * Current screen.
		 *
		 * @var \WP_Screen
		 *
		 * @since 1.1.0
		 */
		protected static $wp_screen;

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
		 * Events Query Arguments.
		 *
		 * @since 1.1.0
		 * @since 1.1.0 Transformed to array
		 *
		 * @var array
		 */
		private static $query_args;

		/**
		 * Holds the DB connection (if it is external), null otherwise.
		 *
		 * @var \wpdb
		 *
		 * @since 1.1.0
		 */
		private static $wsal_db = null;

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
		 * Holds the read lines from error log.
		 *
		 * @var array
		 *
		 * @since 1.1.0
		 */
		private static $read_items = array();

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
					'screen'   => $this->get_wp_screen(),
				)
			);

			self::$columns = self::manage_columns( array() );

			self::$wsal_db = null;

			self::$table_name = 'php_error_logs';
		}

		/**
		 * Returns the current wsal_db connection.
		 *
		 * @return \wpdb
		 *
		 * @since 1.1.0
		 */
		public static function get_wsal_db() {
			return self::$wsal_db;
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

				<input type="search" id="<?php echo esc_attr( $input_id ); ?>" class="wsal_search_input" name="<?php echo \esc_attr( self::SEARCH_INPUT ); ?>" value="<?php echo \esc_attr( self::escaped_search_input() ); ?>" />

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
				'timestamp'    => __( 'Time', '0-day-analytics' ),
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
		 * Returns the the wp_screen property.
		 *
		 * @since 1.1.0
		 */
		private static function get_wp_screen() {
			if ( empty( self::$wp_screen ) ) {
				self::$wp_screen = \get_current_screen();
			}

			return self::$wp_screen;
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

			$hidden = get_user_option( 'manage' . $this->get_wp_screen()->id . 'columnshidden', false );
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
			\esc_html_e( 'No reports found', '0-day-analytics' );
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
		 * @param boolean $write_temp - Bool option responsible for should we write the temp error log or not?.
		 * @param int     $items - Number of items to read from the error log. If false or not set, the items per page for that object will be used. @see method get_screen_option_per_page.
		 *
		 * @return array
		 *
		 * @since 1.1.0
		 */
		public static function get_error_items( bool $write_temp = true, $items = false ): array {

			// if ( empty( self::$read_items ) ) { .
				$collected_items = array();
				$errors          = array();
				$position        = null;

			if ( \function_exists( 'set_time_limit' ) ) {
				\set_time_limit( 0 );
			}

			$file = Error_Log::autodetect();

			if ( \is_a( $file, 'WP_Error' ) ) {
				return array(
					array(
						'message'   => Error_Log::get_last_error(),
						'timestamp' => time(),
					),
				);
			}

			while ( empty( $errors ) ) {
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
								$errors[] = $parsed_data;
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
						} elseif (!empty($line)){
							$more_to_error = true;
						}

						return ['line_done' => !$more_to_error, 'close'=> false];

						// if ( ! str_contains( $address, 'stop_word' ) ) {
						// echo "\nFound 'stop_word'!"; .

						// return false; // returning false here "breaks" the loop
						// } .
					},
					( ! $items ) ? self::get_screen_option_per_page() : $items,
					$position,
					$write_temp
				);

				if ( false === $result ) {
					break;
				}
			}

			$disabled = Settings::get_disabled_severities();
			if ( ! empty( $disabled ) ) {
				foreach ( $disabled as $severity ) {
					foreach ( $errors as $key => $error ) {
						if ( isset( $error['severity'] ) && $error['severity'] === $severity ) {
							unset( $errors[ $key ] );
						}
					}
				}
			}
				self::$read_items = $errors;
			// }

			Log_Line_Parser::store_last_parsed_timestamp();

			return self::$read_items;
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
				case 'timestamp':
					$date_time_format = \get_option( 'date_format' ) . ' ' . \get_option( 'time_format' );
					$time             = \wp_date( $date_time_format, $item['timestamp'] );

					return $time;
				case 'message':
					$message = esc_html( $item[ $column_name ] );
					if ( isset( $item['sub_items'] ) && ! empty( $item['sub_items'] ) ) {
						$message .= '<div style="margin-top:10px;"><input type="button" class="button button-primary show_log_details" value="' . __( 'Show details', '0-day-analytics' ) . '"></div>';

						$reversed_details = \array_reverse( $item['sub_items'] );
						$message         .= '<div class="log_details_show" style="display:none"><pre style="background:#07073a; color:#c2c8cd; padding: 5px; overflow-y:auto;">';
						foreach ( $reversed_details as $key => $val ) {
							$message .= ( isset( $val['call'] ) && ! empty( $val['call'] ) ) ? '<b><i>' . $val['call'] . '</i></b> - ' : '';
							$message .= ( isset( $val['file'] ) && ! empty( $val['file'] ) ) ? $val['file'] . ' ' : '';
							$message .= ( isset( $val['line'] ) && ! empty( $val['line'] ) ) ? $val['line'] . '<br>' : '';

							$message = \rtrim( $message, ' - ' );
						}
						$message .= '</pre></div>';
					}
					return $message;
				case 'plugin_theme':
					if ( isset( $item['source'] ) ) {
						return $item['source'];
					} else {
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

							$plugin = Plugin_Theme_Helper::get_plugin_from_path( $plugin_base );

							if ( ! empty( $plugin ) ) {
								return esc_html( $plugin['Name'] );
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

							$theme = Plugin_Theme_Helper::get_theme_from_path( $theme_base );

							if ( ! empty( $theme ) && is_a( $theme, '\WP_Theme' ) ) {
								$name = $theme->get( 'Name' );

								$name = ( ! empty( $name ) ) ? $name : __( 'Unknown thenme', '0-day-analytics' );

								$parent = $theme->parent(); // ( 'parent_theme' );
								if ( $parent ) {
									$parent = $theme->parent()->get( 'Name' );

									$parent = ( ! empty( $parent ) ) ? '<div>' . __( 'Parent thenme: ', '0-day-analytics' ) . $parent . '</div>' : '';
								}
								$name .= (string) $parent;

								return ( $name );
							}
						}

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

			// check for individual row actions.
			$the_table_action = $this->current_action();

			// check for table bulk actions.
			if ( ( ( isset( $_REQUEST['action'] ) && 'delete' === $_REQUEST['action'] ) || ( isset( $_REQUEST['action2'] ) && 'delete' === $_REQUEST['action2'] ) ) && Settings_Helper::current_user_can( 'view' ) ) {
				if ( ! isset( $_REQUEST['_wpnonce'] ) ) {
					$this->graceful_exit();
				}
				$nonce = \sanitize_text_field( \wp_unslash( $_REQUEST['_wpnonce'] ) );
				// verify the nonce.
				/**
				 * Note: the nonce field is set by the parent class
				 * wp_nonce_field( 'bulk-' . $this->_args['plural'] );.
				 */
				if ( ! wp_verify_nonce( $nonce, 'bulk-' . $this->_args['plural'] ) ) {
					$this->invalid_nonce_redirect();
				} elseif ( isset( $_REQUEST[ self::$table_name ] ) && \is_array( $_REQUEST[ self::$table_name ] ) ) {
					foreach ( \wp_unslash( $_REQUEST[ self::$table_name ] ) as $id ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
						self::$table::delete_by_id( (int) $id, self::$wsal_db );
					}
				}
				?>
				<script>
					jQuery('body').addClass('has-overlay');
					window.location = "<?php echo \remove_query_arg( array( 'action', '_wpnonce', self::$table_name, '_wp_http_referer', 'action2' ) ) . '#wsal-options-tab-saved-reports'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>";
				</script>
				<?php
			}
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
			self::get_wp_screen();

			if ( self::PAGE_SLUG === self::$wp_screen->base ) {
				$option = self::$wp_screen->get_option( 'per_page', 'option' );
				if ( ! $option ) {
					$option = str_replace( '-', '_', self::$wp_screen->id . '_per_page' );
				}
			} else {
				$option = 'advanced_analytics_logs_list_per_page';
			}

			$per_page = (int) \get_user_option( $option );
			if ( empty( $per_page ) || $per_page < 1 ) {
				$per_page = self::$wp_screen->get_option( 'per_page', 'default' );
				if ( ! $per_page ) {
					$per_page = self::get_log_errors_to_read();
				}
			}

			return $per_page;
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

				echo '<div><b style="color: red">' . \esc_html__( 'Log file Problem: ', '0-day-analytics' ) . '</b> ' . \esc_attr( Error_Log::get_last_error() ) . '</div>';
			}

			if ( false !== $log_file ) {

				$date_time_format = \get_option( 'date_format' ) . ' ' . \get_option( 'time_format' );
				$time             = \wp_date( $date_time_format, Error_Log::get_modification_time( Error_Log::autodetect() ) );

				echo '<div><b>' . \esc_html__( 'Log file: ', '0-day-analytics' ) . '</b> ' . \esc_attr( Error_Log::extract_file_name( Error_Log::autodetect() ) ) . '</div>';
				echo '<div><b>' . \esc_html__( 'File size: ', '0-day-analytics' ) . '</b> ' . \esc_attr( File_Helper::format_file_size( Error_Log::autodetect() ) ) . '</div>';
				echo '<div><b>' . \esc_html__( 'Last modified: ', '0-day-analytics' ) . '</b> ' . \esc_attr( $time ) . '</div>';
			} else {
				echo '<div><b>' . \esc_html__( 'No log file detected', '0-day-analytics' ) . '</b></div>';
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
					jQuery( document ).on( 'click', '#top-downloadlog, #bottom-downloadlog', function ( e ) {
						
						const a = document.createElement('a');
						a.href = '<?php echo File_Helper::download_link(); ?>';

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
						if ( null === Error_Log::get_last_error() ) {
							?>
						

							<input class="button button-primary" id="<?php echo \esc_attr( $which ); ?>-truncate" type="button" value="<?php echo esc_html__( 'Truncate file', '0-day-analytics' ); ?>" />
							<?php
						}
						?>
					<input type="submit" name="downloadlog" id="<?php echo \esc_attr( $which ); ?>-downloadlog" class="button button-primary" value="<?php echo esc_html__( 'Download Log', '0-day-analytics' ); ?>">
						<?php
					}
					?>
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
						max-width: 95%;
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

						echo '.generated-logs .' . \esc_attr( $class ) . '{ background: ' . \esc_attr( $properties['color'] ) . ' !important;}';
						echo '#the-list .' . \esc_attr( $class ) . ' td { color: ' . \esc_attr( $color ) . ' !important;}';
						echo '#the-list td { color: #fff !important; }';
						echo '#the-list tr { background: #1d456b;}';

					}
					?>
				</style>
				<pre id="debug-log"><?php Reverse_Line_Reader::read_temp_file(); ?></pre>
					<?php
			}
		}
	}
}
