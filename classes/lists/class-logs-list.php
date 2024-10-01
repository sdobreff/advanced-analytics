<?php
/**
 * Responsible for the Showing the list of the events collected.
 *
 * @package    advanced-analytics
 * @subpackage helpers
 *
 * @since 1.0.0
 *
 * @license    https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */

declare(strict_types=1);

namespace ADVAN\Lists;

use WSAL\Helpers\File_Helper;
use ADVAN\Controllers\Error_Log;
use WSAL\Helpers\Settings_Helper;
use ADVAN\Helpers\Log_Line_Parser;
use WSAL\Extensions\Views\Reports;
use ADVAN\Controllers\Reverse_Line_Reader;
use WSAL\Entities\Generated_Reports_Entity;
use WSAL\Helpers\DateTime_Formatter_Helper;

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
	 * @since 5.0.0
	 */
	class Logs_List extends \WP_List_Table {
		public const SCREEN_OPTIONS_SLUG = 'wsal_generated_reports_view';
		public const SEARCH_INPUT        = 'sgp';

		/**
		 * Current screen.
		 *
		 * @var \WP_Screen
		 *
		 * @since 5.0.0
		 */
		protected $wp_screen;

		/**
		 * Name of the table to show.
		 *
		 * @var string
		 *
		 * @since 5.0.0
		 */
		private static $table_name;

		/**
		 * How many.
		 *
		 * @var int
		 *
		 * @since 5.0.0
		 */
		protected $count;

		/**
		 * How many records to show per page - that is a fall back option, it will try to extract that first from the stored user data, then from the settings and from here as a last resort.
		 *
		 * @var int
		 *
		 * @since 5.0.0
		 */
		protected $records_per_page = 10;

		/**
		 * Holds the array with all of the column names and their representation in the table header.
		 *
		 * @var array
		 *
		 * @since 5.0.0
		 */
		private static $columns = array();

		/**
		 * Events Query Arguments.
		 *
		 * @since 5.0.0
		 * @since 5.0.0 Transformed to array
		 *
		 * @var array
		 */
		private static $query_args;

		/**
		 * Holds the DB connection (if it is external), null otherwise.
		 *
		 * @var \wpdb
		 *
		 * @since 5.0.0
		 */
		private static $wsal_db = null;

		/**
		 * Holds the current query arguments.
		 *
		 * @var array
		 *
		 * @since 4.6.1
		 */
		private static $query_occ = array();

		/**
		 * Holds the current query order.
		 *
		 * @var array
		 *
		 * @since 4.6.1
		 */
		private static $query_order = array();

		/**
		 * Default class constructor.
		 *
		 * @param stdClass $query_args Events query arguments.
		 *
		 * @since 5.0.0
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
		 * @since 4.6.1
		 */
		public static function get_wsal_db() {
			return self::$wsal_db;
		}

		/**
		 * Displays the search box.
		 *
		 * @since 5.0.0
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
		 * @since 5.0.0
		 */
		public static function escaped_search_input() {
			return isset( $_REQUEST[ self::SEARCH_INPUT ] ) ? \esc_sql( \sanitize_text_field( \wp_unslash( $_REQUEST[ self::SEARCH_INPUT ] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		/**
		 * Adds columns to the screen options screed.
		 *
		 * @param array $columns - Array of column names.
		 *
		 * @since 5.0.0
		 */
		public static function manage_columns( $columns ): array {
			$admin_fields = array(
				//'cb'                                  => '<input type="checkbox" />', // to display the checkbox.
				'timestamp'               => __( 'Time', 'advanced-analytics' ),
				'severity'                => __( 'Severity', 'advanced-analytics' ),
				'message' => __( 'Message', 'advanced-analytics' ),
			);

			$screen_options = $admin_fields;

			return \array_merge( $screen_options, $columns );
		}

		/**
		 * Returns the table name.
		 *
		 * @since 5.0.0
		 */
		public static function get_table_name(): string {
			return self::$table_name;
		}

		/**
		 * Returns the the wp_screen property.
		 *
		 * @since 5.0.0
		 */
		private function get_wp_screen() {
			if ( empty( $this->wp_screen ) ) {
				$this->wp_screen = get_current_screen();
			}

			return $this->wp_screen;
		}

		/**
		 * Prepares the list of items for displaying.
		 *
		 * Query, filter data, handle sorting, and pagination, and any other data-manipulation required prior to rendering
		 *
		 * @since 5.0.0
		 */
		public function prepare_items() {
			$columns               = $this->get_columns();
			$hidden                = array();
			$sortable              = $this->get_sortable_columns();
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
		 * @since 4.6.1
		 */
		public static function get_hidden_columns() {
			return array_filter(
				(array) get_user_option( 'managetoplevel_page_wsal-auditlogcolumnshidden', false )
			);
		}

		/**
		 * Get a list of columns. The format is:
		 * 'internal-name' => 'Title'.
		 *
		 * @since 5.0.0
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
		 * @since 5.0.0
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
		 * @since 5.0.0
		 *
		 * @return void
		 */
		public function no_items() {
			\esc_html_e( 'No reports found', 'advanced-analytics' );
		}

		/**
		 * Fetch table data from the WordPress database.
		 *
		 * @since 5.0.0
		 *
		 * @return array
		 */
		public function fetch_table_data() {

			$collected_items = [];
			$errors = [];

			Reverse_Line_Reader::read_file_from_end(
				Error_Log::autodetect(),
				function( $line ) use (&$collected_items, &$errors ) {
			
					// Check if this is the last line, and if not try to parse the line.
					if ( null !== Log_Line_Parser::parse_entry_with_stack_trace( $line ) ) {
						$parsed_data = Log_Line_Parser::parse_php_error_log_stack_line( $line );

						if (\is_array($parsed_data) && isset($parsed_data['message'])) {
							if (!empty($collected_items)) {
								$parsed_data['sub_items'] = $collected_items;
								$collected_items = [];
							}
							$errors[] = $parsed_data;
						} elseif (\is_array($parsed_data)) {
							$collected_items[] = $parsed_data;
						}
					}
			
					// if ( ! str_contains( $address, 'stop_word' ) ) {
					// echo "\nFound 'stop_word'!";
			
					// return false; // returning false here "breaks" the loop
					// }
				},
				30
			);

			$this->items = $errors;

			return $this->items;
		}

		/**
		 * Returns the current query
		 *
		 * @return array
		 *
		 * @since 4.6.1
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
		 * @since 5.0.0
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
		 * @since 4.6.1
		 */
		public static function format_column_value( $item, $column_name ) {
			switch ( $column_name ) {
				case 'generated_report_name':
					// row actions to edit record.
					$query_args_view_data = array(
						'page'                   => ( isset( $_REQUEST['page'] ) ) ? \sanitize_text_field( \wp_unslash( $_REQUEST['page'] ) ) : Reports::get_safe_view_name(),
						'action'                 => 'delete',
						'_wpnonce'               => \wp_create_nonce( 'bulk-generated-reports' ),
						self::$table_name . '[]' => absint( $item['id'] ),
					);
					$admin_page_url       = \network_admin_url( 'admin.php' );
					$delete_data_link     = esc_url( add_query_arg( $query_args_view_data, $admin_page_url ) );
					$actions['delete']    = '<a href="' . $delete_data_link . '#wsal-options-tab-saved-reports">' . \esc_html__( 'Delete report', 'advanced-analytics' ) . '</a>';

					if ( \file_exists( Settings_Helper::get_working_dir_path_static( 'reports', true ) . $item['generated_report_file'] . '.csv' ) ) {

						$url                 = add_query_arg(
							array(
								'action' => 'wsal_file_download',
								'f'      => base64_encode( \basename( $item['generated_report_file'] ) . '.csv' ),
								'ctype'  => 1,
								'nonce'  => wp_create_nonce( 'wpsal_reporting_nonce_action' ),
							),
							admin_url( 'admin-ajax.php' )
						);
						$actions['download'] = '<a target="_blank" href="' . $url . '#wsal-options-tab-saved-reports">' . \esc_html__( 'Download CSV report', 'advanced-analytics' ) . '</a> (' . File_Helper::format_file_size( Settings_Helper::get_working_dir_path_static( 'reports', true ) . $item['generated_report_file'] . '.csv' ) . ')';
					}

					if ( \file_exists( Settings_Helper::get_working_dir_path_static( 'reports', true ) . $item['generated_report_file'] . '.html' ) ) {
						$url                      = add_query_arg(
							array(
								'action' => 'wsal_file_download',
								'f'      => base64_encode( \basename( $item['generated_report_file'] ) . '.html' ),
								'ctype'  => 'html',
								'nonce'  => wp_create_nonce( 'wpsal_reporting_nonce_action' ),
							),
							admin_url( 'admin-ajax.php' )
						);
						$actions['download_html'] = '<a target="_blank" href="' . $url . '#wsal-options-tab-saved-reports">' . \esc_html__( 'Download HTML report', 'advanced-analytics' ) . '</a> (' . File_Helper::format_file_size( Settings_Helper::get_working_dir_path_static( 'reports', true ) . $item['generated_report_file'] . '.html' ) . ')';
					}

					if ( -1 === (int) $item['generated_report_number_of_records'] ) {
						if ( \file_exists( Settings_Helper::get_working_dir_path_static( 'reports', true ) . $item['generated_report_file'] . '.json' ) ) {
							$url                      = add_query_arg(
								array(
									'action' => 'wsal_file_download',
									'f'      => base64_encode( \basename( $item['generated_report_file'] ) . '.json' ),
									'ctype'  => 'json',
									'nonce'  => wp_create_nonce( 'wpsal_reporting_nonce_action' ),
								),
								admin_url( 'admin-ajax.php' )
							);
							$actions['download_html'] = '<a target="_blank" href="' . $url . '#wsal-options-tab-saved-reports">' . \esc_html__( 'Download JSON report', 'advanced-analytics' ) . '</a> (' . File_Helper::format_file_size( Settings_Helper::get_working_dir_path_static( 'reports', true ) . $item['generated_report_file'] . '.json' ) . ')';
						}
						if ( \file_exists( Settings_Helper::get_working_dir_path_static( 'reports', true ) . $item['generated_report_file'] . '.pdf' ) ) {
							$url                      = add_query_arg(
								array(
									'action' => 'wsal_file_download',
									'f'      => base64_encode( \basename( $item['generated_report_file'] ) . '.pdf' ),
									'ctype'  => 'pdf',
									'nonce'  => wp_create_nonce( 'wpsal_reporting_nonce_action' ),
								),
								admin_url( 'admin-ajax.php' )
							);
							$actions['download_html'] = '<a target="_blank" href="' . $url . '#wsal-options-tab-saved-reports">' . \esc_html__( 'Download PDF report', 'advanced-analytics' ) . '</a> (' . File_Helper::format_file_size( Settings_Helper::get_working_dir_path_static( 'reports', true ) . $item['generated_report_file'] . '.pdf' ) . ')';
						}
					}

					return '<b>' . $item['generated_report_name'] . '</b>' . ( new \WP_List_Table() )->row_actions( $actions );
				case 'generated_report_tag':
					return $item['generated_report_tag'];
				case 'generated_report_filters_normalized':
					$item['generated_report_filters_normalized'] = \json_decode( $item['generated_report_filters_normalized'], true );
					if ( isset( $item['generated_report_filters_normalized']['custom_title'] ) ) {
						unset( $item['generated_report_filters_normalized']['custom_title'] );
					}
					if ( isset( $item['generated_report_filters_normalized']['comment'] ) ) {
						unset( $item['generated_report_filters_normalized']['comment'] );
					}
					if ( isset( $item['generated_report_filters_normalized']['no_meta'] ) ) {
						unset( $item['generated_report_filters_normalized']['no_meta'] );
					}
					if ( isset( $item['generated_report_filters_normalized']['sql'] ) ) {
						unset( $item['generated_report_filters_normalized']['sql'] );
					}
					if ( isset( $item['generated_report_filters_normalized']['statistic_type'] ) ) {
						unset( $item['generated_report_filters_normalized']['statistic_type'] );
					}

					$body = '';

					$item['generated_report_filters'] = \json_decode( $item['generated_report_filters'], true );

					if ( isset( $item['generated_report_filters']['statistic_report_only_archive'] ) || isset( $item['generated_report_filters']['archive_db_only'] ) ) {

						if ( ( isset( $item['generated_report_filters']['statistic_report_only_archive'] ) && (bool) $item['generated_report_filters']['statistic_report_only_archive'] ) ||
						( isset( $item['generated_report_filters']['use_archive_db'] ) &&
						(bool) $item['generated_report_filters']['use_archive_db'] ) ||
						( isset( $item['generated_report_filters']['archive_db_only'] ) &&
						(bool) $item['generated_report_filters']['archive_db_only'] ) ) {
							if ( ( isset( $item['generated_report_filters']['statistic_report_only_archive'] ) && (bool) $item['generated_report_filters']['statistic_report_only_archive'] ) || ( isset( $item['generated_report_filters']['archive_db_only'] ) && (bool) $item['generated_report_filters']['archive_db_only'] ) ) {
								$body .= '<div><b>' . \esc_html__( 'Archive data only', 'advanced-analytics' ) . '</b></div>';
							} else {
								$body .= '<div><b>' . \esc_html__( 'Archive data included', 'advanced-analytics' ) . '</b></div>';
							}
						}
					} elseif ( Settings_Helper::is_archiving_enabled() ) {
						$body .= '<div><b>' . \esc_html__( 'Archive data is included', 'advanced-analytics' ) . '</b></div>';
					}

					foreach ( $item['generated_report_filters_normalized'] as $criteria_label => $criteria_values ) {

						$body .= '<em>' . $criteria_label . ': </em>';
						$body .= \implode( '<br>', (array) $criteria_values );
						$body .= '<hr />';
					}

					return $body;

				case 'generated_report_finished':
					$finished = '';
					$finished = \esc_html__( 'Report is ready', 'advanced-analytics' );
					if ( 0 === (int) $item['generated_report_finished'] ) {
						$finished = '<span style="color:#b32d2e">' . \esc_html__( 'Still generates', 'advanced-analytics' ) . '</span>';
					}

					return $finished;
				case 'generated_report_number_of_records':
					return ( -1 === (int) $item['generated_report_number_of_records'] ) ? \esc_html__( 'N/A', 'advanced-analytics' ) : $item['generated_report_number_of_records'];
				case 'generated_report_type':
					$type = \esc_html__( 'Statistic', 'advanced-analytics' );
					if ( 999 !== (int) $item['generated_report_format'] ) {
						$type = \esc_html__( 'Generic', 'advanced-analytics' );
					}
					return $type;
				case 'generated_report_username':
					return $item['generated_report_username'];
				case 'created_on':
					return $item['created_on']
						? DateTime_Formatter_Helper::get_formatted_date_time( $item['created_on'], 'datetime', true, true )
						: '<i>' . __( 'Unknown', 'advanced-analytics' ) . '</i>';
				default:
					return isset( $item[ $column_name ] )
						? esc_html( $item[ $column_name ] )
						: 'Column "' . esc_html( $column_name ) . '" not found';
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
		 * @since 5.0.0
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
		 * @since 5.0.0
		 *
		 * @return array
		 */
		public function get_bulk_actions() {
			$actions = array();
			if ( Settings_Helper::current_user_can( 'view' ) ) {
				/**
				 * On hitting apply in bulk actions the url paramas are set as
				 * ?action=bulk-download&paged=1&action2=-1.
				 *
				 * Action and action2 are set based on the triggers above or below the table
				 */
				$actions = array(
					'delete' => __( 'Delete Records', 'advanced-analytics' ),
				);
			}

			return $actions;
		}

		/**
		 * Process actions triggered by the user.
		 *
		 * @since 5.0.0
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
		 * @since 5.0.0
		 *
		 * @return void
		 */
		public function graceful_exit() {
			exit;
		}

		/**
		 * Die when the nonce check fails.
		 *
		 * @since 5.0.0
		 *
		 * @return void
		 */
		public function invalid_nonce_redirect() {
			wp_die(
				'Invalid Nonce',
				'Error',
				array(
					'response'  => 403,
					'back_link' => esc_url( \network_admin_url( 'users.php' ) ),
				)
			);
		}

		/**
		 * Returns the records to show per page.
		 *
		 * @return int
		 *
		 * @since 5.0.0
		 */
		public function get_records_per_page() {
			return $this->records_per_page;
		}

		/**
		 * Get the screen option per_page.
		 *
		 * @return int
		 *
		 * @since 5.0.0
		 */
		private function get_screen_option_per_page() {
			$this->get_wp_screen();
			$option = $this->wp_screen->get_option( 'per_page', 'option' );
			if ( ! $option ) {
				$option = str_replace( '-', '_', "{$this->wp_screen->id}_per_page" );
			}

			$per_page = (int) get_user_option( $option );
			if ( empty( $per_page ) || $per_page < 1 ) {
				$per_page = $this->wp_screen->get_option( 'per_page', 'default' );
				if ( ! $per_page ) {
					$per_page = $this->get_records_per_page();
				}
			}

			return $per_page;
		}

		/**
		 * Returns the columns array (with column name).
		 *
		 * @return array
		 *
		 * @since 5.0.0
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
		 * @since 5.0.0
		 */
		public static function add_screen_options( $hook ) {
			$screen_options = array( 'per_page' => __( 'Records per page', 'advanced-analytics' ) );

			$result = array();

			array_walk(
				$screen_options,
				function ( &$a, $b ) use ( &$result ) {
					$result[ self::SCREEN_OPTIONS_SLUG . '_' . $b ] = $a;
				}
			);
			$screen_options = $result;

			foreach ( $screen_options as $key => $value ) {
				add_action(
					"load-$hook",
					function () use ( $key, $value ) {
						$option = 'per_page';
						$args   = array(
							'label'   => $value,
							'default' => (int) Settings_Helper::get_option_value( 'items-per-page', 10 ),
							'option'  => $key,
						);
						add_screen_option( $option, $args );
					}
				);
			}
		}

		/**
		 * Form table per-page screen option value.
		 *
		 * @since 5.0.0
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
		 */
		public function extra_tablenav( $which ) {
			// If the position is not top then render.

			// Show site alerts widget.
			// NOTE: this is shown when the filter IS NOT true.
		}

		/**
		 * Prints column headers, accounting for hidden and sortable columns.
		 *
		 * @since 3.1.0
		 *
		 * @param bool $with_id Whether to set the ID attribute or not.
		 */
		public function print_column_headers( $with_id = true ) {
			list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();

			$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ); //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$current_url = remove_query_arg( 'paged', $current_url );

			// When users click on a column header to sort by other columns.
			if ( isset( $_GET['orderby'] ) ) {
				$current_orderby = \sanitize_text_field( \wp_unslash( $_GET['orderby'] ) );
				// In the initial view there's no orderby parameter.
			} else {
				$current_orderby = '';
			}

			// Not in the initial view and descending order.
			if ( isset( $_GET['order'] ) && 'desc' === $_GET['order'] ) {
				$current_order = 'desc';
			} else {
				// The initial view is not always 'asc', we'll take care of this below.
				$current_order = 'asc';
			}

			if ( ! empty( $columns['cb'] ) ) {
				static $cb_counter = 1;
				$columns['cb']     = '<label class="label-covers-full-cell" for="cb-select-all-' . $cb_counter . '">' .
				'<span class="screen-reader-text">' .
					/* translators: Hidden accessibility text. */
					__( 'Select All' ) .
				'</span>' .
				'</label>' .
				'<input id="cb-select-all-' . $cb_counter . '" type="checkbox" />';
				++$cb_counter;
			}

			foreach ( $columns as $column_key => $column_display_name ) {
				$class          = array( 'manage-column', "column-$column_key" );
				$aria_sort_attr = '';
				$abbr_attr      = '';
				$order_text     = '';

				if ( in_array( $column_key, $hidden, true ) ) {
					$class[] = 'hidden';
				}

				if ( 'cb' === $column_key ) {
					$class[] = 'check-column';
				} elseif ( in_array( $column_key, array( 'posts', 'comments', 'links' ), true ) ) {
					$class[] = 'num';
				}

				if ( $column_key === $primary ) {
					$class[] = 'column-primary';
				}

				if ( isset( $sortable[ $column_key ] ) ) {
					$orderby       = isset( $sortable[ $column_key ][0] ) ? $sortable[ $column_key ][0] : '';
					$desc_first    = isset( $sortable[ $column_key ][1] ) ? $sortable[ $column_key ][1] : false;
					$abbr          = isset( $sortable[ $column_key ][2] ) ? $sortable[ $column_key ][2] : '';
					$orderby_text  = isset( $sortable[ $column_key ][3] ) ? $sortable[ $column_key ][3] : '';
					$initial_order = isset( $sortable[ $column_key ][4] ) ? $sortable[ $column_key ][4] : '';

					/*
					 * We're in the initial view and there's no $_GET['orderby'] then check if the
					 * initial sorting information is set in the sortable columns and use that.
					 */
					if ( '' === $current_orderby && $initial_order ) {
						// Use the initially sorted column $orderby as current orderby.
						$current_orderby = $orderby;
						// Use the initially sorted column asc/desc order as initial order.
						$current_order = $initial_order;
					}

					/*
					 * True in the initial view when an initial orderby is set via get_sortable_columns()
					 * and true in the sorted views when the actual $_GET['orderby'] is equal to $orderby.
					 */
					if ( $current_orderby === $orderby ) {
						// The sorted column. The `aria-sort` attribute must be set only on the sorted column.
						if ( 'asc' === $current_order ) {
							$order          = 'desc';
							$aria_sort_attr = ' aria-sort="ascending"';
						} else {
							$order          = 'asc';
							$aria_sort_attr = ' aria-sort="descending"';
						}

						$class[] = 'sorted';
						$class[] = $current_order;
					} else {
						// The other sortable columns.
						$order = strtolower( (string) $desc_first );

						if ( ! in_array( $order, array( 'desc', 'asc' ), true ) ) {
							$order = $desc_first ? 'desc' : 'asc';
						}

						$class[] = 'sortable';
						$class[] = 'desc' === $order ? 'asc' : 'desc';

						/* translators: Hidden accessibility text. */
						$asc_text = __( 'Sort ascending.' );
						/* translators: Hidden accessibility text. */
						$desc_text  = __( 'Sort descending.' );
						$order_text = 'asc' === $order ? $asc_text : $desc_text;
					}

					if ( '' !== $order_text ) {
						$order_text = ' <span class="screen-reader-text">' . $order_text . '</span>';
					}

					// Print an 'abbr' attribute if a value is provided via get_sortable_columns().
					$abbr_attr = $abbr ? ' abbr="' . esc_attr( $abbr ) . '"' : '';

					$column_display_name = sprintf(
						'<a href="%1$s#wsal-options-tab-saved-reports">' .
						'<span>%2$s</span>' .
						'<span class="sorting-indicators">' .
							'<span class="sorting-indicator asc" aria-hidden="true"></span>' .
							'<span class="sorting-indicator desc" aria-hidden="true"></span>' .
						'</span>' .
						'%3$s' .
						'</a>',
						esc_url( add_query_arg( compact( 'orderby', 'order' ), $current_url ) ),
						$column_display_name,
						$order_text
					);
				}

				$tag   = ( 'cb' === $column_key ) ? 'td' : 'th';
				$scope = ( 'th' === $tag ) ? 'scope="col"' : '';
				$id    = $with_id ? "id='$column_key'" : '';

				if ( ! empty( $class ) ) {
					$class = "class='" . implode( ' ', $class ) . "'";
				}

				echo "<$tag $scope $id $class $aria_sort_attr $abbr_attr>$column_display_name</$tag>"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}

		/**
		 * Generates the table navigation above or below the table
		 *
		 * @param string $which - Holds info about the top and bottom navigation.
		 *
		 * @since 5.0.0
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
					$this->extra_tablenav( $which );
					?>

				<br class="clear" />
			</div>
			<?php
		}
	}
}
