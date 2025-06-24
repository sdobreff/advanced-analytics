<?php
/**
 * Responsible for the Showing the list of the crons.
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
use ADVAN\Helpers\Crons_Helper;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/template.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table-compat.php';
	require_once ABSPATH . 'wp-admin/includes/list-table.php';
}

/*
 * Base list table class
 */
if ( ! class_exists( '\ADVAN\Lists\Crons_List' ) ) {
	/**
	 * Responsible for rendering base table for manipulation.
	 *
	 * @since 1.1.0
	 */
	class Crons_List extends \WP_List_Table {

		public const SCREEN_OPTIONS_SLUG = 'advanced_analytics_crons_list';

		public const PAGE_SLUG = 'wp-control_page_advan_cron_jobs';

		public const UPDATE_ACTION = 'advan_crons_update';

		public const NONCE_NAME = 'advana_crons_manager';

		public const SEARCH_INPUT = 'sgp';

		/**
		 * Format for the file link.
		 *
		 * @var string|false|null
		 *
		 * @since 1.4.0
		 */
		private static $file_link_format = null;

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
		private static $read_items = null;

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
					'singular' => 'generated-cron',
					'plural'   => 'generated-crons',
					'ajax'     => true,
					'screen'   => WP_Helper::get_wp_screen(),
				)
			);

			self::$columns = self::manage_columns( array() );

			self::$table_name = 'advanced_crons';
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

			if ( empty( $_REQUEST[ self::SEARCH_INPUT ] ) && ! self::are_there_items() ) {
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
				'cb'         => '<input type="checkbox" />', // to display the checkbox.
				'hook'       => __( 'Hook', '0-day-analytics' ),
				'schedule'   => esc_html(
					sprintf(
						/* translators: %s: UTC offset */
						__( 'Next Run (%s)', '0-day-analytics' ),
						WP_Helper::get_timezone_location()
					),
				),
				'recurrence' => __( 'Interval', '0-day-analytics' ),
				'args'       => __( 'Args', '0-day-analytics' ),
				'actions'    => __( 'Actions', '0-day-analytics' ),
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
			$columns               = $this->get_columns();
			$hidden                = array();
			$sortable              = $this->get_sortable_columns();
			$this->_column_headers = array( $columns, $hidden, $sortable );

			$this->handle_table_actions();

			$this->fetch_table_data();

			$hidden = \get_user_option( 'manage' . WP_Helper::get_wp_screen()->id . 'columnshidden', false );
			if ( ! $hidden ) {
				$hidden = array();
			}

			$this->_column_headers = array( self::$columns, $hidden, $sortable );
			// phpcs:ignore
			// usort( $items, [ &$this, 'usort_reorder' ] ); // phpcs:ignore

			// Set the pagination.
			// $this->set_pagination_args(
			// array(
			// 'total_items' => $this->count,
			// 'per_page'    => $this->get_screen_option_per_page(),
			// 'total_pages' => ceil( $this->count / $this->get_screen_option_per_page() ),
			// )
			// );
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
			return array(
				'hook'       => array( 'hook', false ),
				'schedule'   => array( 'schedule', false, null, null, 'asc' ),
				'recurrence' => array( 'recurrence', false ),
			);
		}

		/**
		 * Text displayed when no user data is available.
		 *
		 * @since 1.1.0
		 *
		 * @return void
		 */
		public function no_items() {
			\esc_html_e( 'No crons found', '0-day-analytics' );
		}

		/**
		 * Fetch table data from the WordPress database.
		 *
		 * @since 1.1.0
		 *
		 * @return array
		 */
		public function fetch_table_data() {

			$this->items = self::get_cron_items();

			return $this->items;
		}

		/**
		 * Collect error items.
		 *
		 * @return array
		 *
		 * @since 1.1.0
		 */
		public static function get_cron_items(): array {

			if ( null === self::$read_items ) {

				self::$read_items = Crons_Helper::get_events();

				// $crons = _get_cron_array();

				// if ( $crons && is_array( $crons ) ) {
				// if ( null === self::$read_items ) {
				// self::$read_items = array();
				// }
				// foreach ( $crons as $timestamp => $cron ) {
				// if ( ! is_array( $cron ) ) {
				// continue;
				// }
				// foreach ( $cron as $hook => $events ) {
				// foreach ( $events as $event ) {

				// $cron_item = array();

				// $cron_item['hook']     = \esc_html( $hook );
				// $cron_item['schedule'] = $timestamp;
				// if ( isset( $event['schedule'] ) ) {
				// $cron_item['recurrence'] = \esc_html( $event['schedule'] );
				// }
				// if ( isset( $event['args'] ) ) {
				// $cron_item['args'] = print_r( $event['args'], true );
				// }

				// $cron_item['hash'] = substr( md5( $cron_item['hook'] . $cron_item['recurrence'] . $cron_item['schedule'] . serialize( $event['args'] ) ), 0, 8 );
				// }
				// self::$read_items[] = $cron_item;
				// }
				// }
				// }
			}

			if ( ! empty( $_REQUEST[ self::SEARCH_INPUT ] ) && is_string( $_REQUEST[ self::SEARCH_INPUT ] ) ) {
				$s = sanitize_text_field( wp_unslash( $_REQUEST[ self::SEARCH_INPUT ] ) );

				self::$read_items = array_filter(
					self::$read_items,
					function ( $event ) use ( $s ) {
						return ( false !== strpos( $event['hook'], $s ) );
					}
				);
			}

			if ( null !== self::$read_items ) {
				uasort( self::$read_items, array( __CLASS__, 'uasort_order_events' ) );
			}

			return self::$read_items ?? array();
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
				case 'hook':
					$query_args_view_data['hash']     = $item['hash'];
					$query_args_view_data['_wpnonce'] = \wp_create_nonce( 'bulk-custom-delete' );

					$actions['delete'] = '<a class="aadvana-cron-delete" href="#" data-nonce="' . $query_args_view_data['_wpnonce'] . '" data-hash="' . $query_args_view_data['hash'] . '">' . \esc_html__( 'Delete', '0-day-analytics' ) . '</a>';

					$actions['run'] = '<a class="aadvana-cron-run" href="#" data-nonce="' . $query_args_view_data['_wpnonce'] . '" data-hash="' . $query_args_view_data['hash'] . '">' . \esc_html__( 'Run', '0-day-analytics' ) . '</a>';

					$edit_url = \remove_query_arg(
						array( 'updated', 'deleted' ),
						\add_query_arg(
							array(
								'action'           => 'edit_cron',
								'hash'             => $item['hash'],
								self::SEARCH_INPUT => self::escaped_search_input(),
								'_wpnonce'         => $query_args_view_data['_wpnonce'],
							)
						)
					);

					$actions['edit'] = '<a class="aadvana-transient-run" href="' . $edit_url . '">' . \esc_html__( 'Edit', '0-day-analytics' ) . '</a>';

					return '<span><b>' . $item['hook'] . '</b></span>' . self::single_row_actions( $actions );
				case 'recurrence':
					return ( ! empty( $item['recurrence'] ) ? $item['recurrence'] : __( 'once', '0-day-analytics' ) );
				case 'args':
					return ( ! empty( $item['args'] ) ? \print_r( $item['args'], true ) : __( 'NO', '0-day-analytics' ) );
				case 'schedule':
					return WP_Helper::time_formatter( $item, esc_html__( 'overdue', '0-day-analytics' ) );
				case 'actions':
					$hook_callbacks = Crons_Helper::get_cron_callbacks( $item['hook'] );

					if ( ! empty( $hook_callbacks ) ) {
						$callbacks = array();

						foreach ( $hook_callbacks as $callback ) {
							if ( \key_exists( 'error', $callback['callback'] ) ) {
								if ( \is_a( $callback['callback']['error'], '\WP_Error' ) ) {
									$callbacks[] = '<span style="color: #b32d2e; background:#ffd6d6;padding:3px;">' . esc_html__( 'Error occurred with cron callback', '0-day-analytics' ) . ' - ' . $callback['callback']['error']->get_error_message() . '</span>';
								} else {
									$callbacks[] = '<span style="color: #b32d2e; background:#ffd6d6;padding:3px;">' . esc_html__( 'Unknown error occurred', '0-day-analytics' ) . '</span>';
								}
							} else {
								$callbacks[] = self::output_filename(
									$callback['callback']['name'],
									$callback['callback']['file'],
									$callback['callback']['line']
								);
							}

							if ( isset( $callback['callback']['component'] ) && ! empty( $callback['callback']['component'] ) && isset( $callback['callback']['component']['name'] ) && ! empty( $callback['callback']['component']['name'] ) ) {
								$callbacks[] = '<span class="status-crontrol-info"><span class="dashicons dashicons-info" aria-hidden="true"></span> ' . esc_html( $callback['callback']['component']['name'] ) . '</span>';
							}
						}

						if ( 'action_scheduler_run_queue' === $item['hook'] ) {
							$callbacks[] = '';
							$callbacks[] = sprintf(
								'<span class="status-crontrol-info"><span class="dashicons dashicons-info" aria-hidden="true"></span> <a href="%s">%s</a></span>',
								admin_url( 'tools.php?page=action-scheduler' ),
								esc_html__( 'View the scheduled actions here &raquo;', '0-day-analytics' )
							);
						}

						return implode( '<br>', $callbacks ); // WPCS:: XSS ok.
					}

					return '';
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
			return sprintf(
				'<label class="screen-reader-text" for="' . $item['hash'] . '">' . sprintf(
					// translators: The column name.
					__( 'Select %s' ),
					'id'
				) . '</label>'
				. '<input type="checkbox" name="' . self::$table_name . '[]" id="' . $item['hash'] . '" value="' . $item['hash'] . '" />'
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
			/**
			 * On hitting apply in bulk actions the url paramas are set as
			 * ?action=bulk-download&paged=1&action2=-1.
			 *
			 * Action and action2 are set based on the triggers above or below the table
			 */
			$actions = array(
				'delete' => __( 'Delete', '0-day-security' ),
				'run'    => __( 'Run', '0-day-security' ),
			);

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
			if ( ( ( isset( $_REQUEST['action'] ) && 'delete' === $_REQUEST['action'] ) || ( isset( $_REQUEST['action2'] ) && 'delete' === $_REQUEST['action2'] ) ) ) {
				if ( ! isset( $_REQUEST['_wpnonce'] ) ) {
					$this->graceful_exit();
				}
				/**
				 * Note: the nonce field is set by the parent class
				 * wp_nonce_field( 'bulk-' . $this->_args['plural'] );.
				 */
				WP_Helper::verify_admin_nonce( 'bulk-' . $this->_args['plural'] );

				if ( isset( $_REQUEST[ self::$table_name ] ) && \is_array( $_REQUEST[ self::$table_name ] ) ) {
					foreach ( \wp_unslash( $_REQUEST[ self::$table_name ] ) as $id ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
						$id = \sanitize_text_field( $id );
						if ( ! empty( $id ) ) {
							// Delete the cron.
							Crons_Helper::delete_event( $id );
						}
					}
				}
				?>
				<script>
					jQuery('body').addClass('has-overlay');
					
				</script>
				<?php
			}
			if ( ( ( isset( $_REQUEST['action'] ) && 'run' === $_REQUEST['action'] ) || ( isset( $_REQUEST['action2'] ) && 'run' === $_REQUEST['action2'] ) ) ) {
				if ( ! isset( $_REQUEST['_wpnonce'] ) ) {
					$this->graceful_exit();
				}
				/**
				 * Note: the nonce field is set by the parent class
				 * wp_nonce_field( 'bulk-' . $this->_args['plural'] );.
				 */
				WP_Helper::verify_admin_nonce( 'bulk-' . $this->_args['plural'] );

				if ( isset( $_REQUEST[ self::$table_name ] ) && \is_array( $_REQUEST[ self::$table_name ] ) ) {
					foreach ( \wp_unslash( $_REQUEST[ self::$table_name ] ) as $id ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
						$id = \sanitize_text_field( $id );
						if ( ! empty( $id ) ) {
							// Delete the cron.
							Crons_Helper::execute_event( $id );
						}
					}
				}
				?>
				<script>
					jQuery('body').addClass('has-overlay');
					
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
			$wp_screen = WP_Helper::get_wp_screen();

			if ( self::PAGE_SLUG === $wp_screen->base ) {
				$option = $wp_screen->get_option( 'per_page', 'option' );
				if ( ! $option ) {
					$option = str_replace( '-', '_', $wp_screen->id . '_per_page' );
				}
			} else {
				$option = 'advanced_analytics_crons_list_per_page';
			}

			$per_page = (int) \get_user_option( $option );
			if ( empty( $per_page ) || $per_page < 1 ) {
				$per_page = $wp_screen->get_option( 'per_page', 'default' );
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
			return;
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
		}

		/**
		 * Generates content for a single row of the table.
		 *
		 * @param object|array $item - The current item.
		 *
		 * @since 1.1.0
		 */
		public function single_row( $item ) {
			$late = Crons_Helper::is_late( $item );

			if ( $late ) {
				$classes = ' late';
			} else {
				$classes = ' on-time';
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

				?>
				<script>
					jQuery(document).ready(function(){
						jQuery('.aadvana-cron-delete').on('click', function(e){

							e.preventDefault();

							let that = this;

							var data = {
								'action': 'aadvana_delete_cron',
								'post_type': 'GET',
								'_wpnonce': jQuery(this).data('nonce'),
								'hash': jQuery(this).data('hash'),
							};

							jQuery.get(ajaxurl, data, function(response) {

								if ( 2 === response['data'] || 0 === response['data'] ) {
									jQuery(that).closest("tr").animate({
										opacity: 0
									}, 1000, function() {
										jQuery(that).closest("tr").remove();
									});
								} else {
									jQuery(that).closest("tr").after('<tr><td style="overflow:hidden;" colspan="'+(jQuery(that).closest("tr").find("td").length+1)+'"><div class="error" style="background:#fff; color:#000;"> ' + response['data'] + '</div></td></tr>');
								}
							}, 'json').fail(function(xhr, status, error) {
								if ( xhr.responseJSON && xhr.responseJSON.data ) {
									errorMessage = xhr.responseJSON.data;
									jQuery(that).closest("tr").after('<tr><td style="overflow:hidden;" colspan="'+(jQuery(that).closest("tr").find("td").length+1)+'"><div class="error" style="background:#fff; color:#000;"> ' + errorMessage + '</div></td></tr>');
								}
							});

						});
						jQuery('.aadvana-cron-run').on('click', function(e){

							e.preventDefault();

							let that = this;

							var data = {
								'action': 'aadvana_run_cron',
								'post_type': 'GET',
								'_wpnonce': jQuery(this).data('nonce'),
								'hash': jQuery(this).data('hash'),
							};

							jQuery.get(ajaxurl, data, function(response) {
								if ( 2 === response['data'] || 0 === response['data'] ) {

										let success = '<?php echo \esc_html__( 'Successfully run', '0-day-analytics' ); ?>';
										let dynRun = jQuery(that).closest("tr").after('<tr><td style="overflow:hidden;" colspan="'+(jQuery(that).closest("tr").find("td").length+1)+'"><div class="updated" style="background:#fff; color:#000;"> ' + success + '</div></td></tr>');
										dynRun.next('tr').fadeOut( 5000, function() {
											dynRun.next('tr').remove();
										});
									
								} else {
									let dynRun = jQuery(that).closest("tr").after('<tr><td style="overflow:hidden;" colspan="'+(jQuery(that).closest("tr").find("td").length+1)+'"><div class="error" style="background:#fff; color:#000;"> ' + response['data'] + '</div></td></tr>');
									dynRun.next('tr').fadeOut( 5000, function() {
										dynRun.next('tr').remove();
									});
								}
							}, 'json').fail(function(xhr, status, error) {
								if ( xhr.responseJSON && xhr.responseJSON.data ) {
									errorMessage = xhr.responseJSON.data;
									jQuery(that).closest("tr").after('<tr><td style="overflow:hidden;" colspan="'+(jQuery(that).closest("tr").find("td").length+1)+'"><div class="error" style="background:#fff; color:#000;"> ' + errorMessage + '</div></td></tr>');
								}
							});

						});
					});
				</script>
				<style>
					.wp-control_page_advan_cron_jobs .generated-crons .late th:nth-child(1) {
						border-left: 7px solid #dd9192 !important;
					}
					.wp-control_page_advan_cron_jobs .generated-crons .on-time th:nth-child(1) {
						border-left: 7px solid rgb(49, 179, 45) !important;
					}
				</style>
				<?php
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

			if ( 'bottom' === $which ) {
				$schedules = \wp_get_schedules();
				uasort( $schedules, array( __CLASS__, 'sort_schedules' ) );
				?>
				<h2><?php esc_html_e( 'Available schedules', '0-day-analytics' ); ?></h2>
				<table class="widefat striped" style="width:auto">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Frequency', '0-day-analytics' ); ?></th>
							<th><?php esc_html_e( 'ID', '0-day-analytics' ); ?></th>
							<th><?php esc_html_e( 'Interval (seconds)', '0-day-analytics' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $schedules as $schedule_id => $schedule ) { ?>
							<tr>
								<td><?php echo esc_html( $schedule['display'] ); ?></td>
								<td><code><?php echo esc_html( $schedule_id ); ?></code></td>
								<td><?php echo esc_html( $schedule['interval'] ); ?></td>
							</tr>
						<?php } ?>
					</tbody>
				</table>
				<?php
			}
		}

		/**
		 * Responsible for sorting the schedule intervals
		 *
		 * @param int $a - Timestamp.
		 * @param int $b - Timestamp.
		 *
		 * @return int
		 *
		 * @since 1.7.4
		 */
		public static function sort_schedules( $a, $b ) {
			if ( $a['interval'] == $b['interval'] ) {
				return strcmp( $a['display'], $b['display'] );
			}
			if ( $a['interval'] ) {
				if ( $b['interval'] ) {
					return $a['interval'] - $b['interval'];
				}
				return -1;
			}
			if ( $b['interval'] ) {
				return 1;
			}
			return 0;
		}

		/**
		 * Generates the required HTML for a list of row action links.
		 *
		 * @since 1.3.0
		 *
		 * @param string[] $actions        An array of action links.
		 * @param bool     $always_visible Whether the actions should be always visible.
		 * @return string The HTML for the row actions.
		 */
		protected static function single_row_actions( $actions, $always_visible = false ) {
			$action_count = count( $actions );

			if ( ! $action_count ) {
				return '';
			}

			$mode = \get_user_setting( 'posts_list_mode', 'list' );

			if ( 'excerpt' === $mode ) {
				$always_visible = true;
			}

			$output = '<div class="' . ( $always_visible ? 'row-actions visible' : 'row-actions' ) . '">';

			$i = 0;

			foreach ( $actions as $action => $link ) {
				++$i;

				$separator = ( $i < $action_count ) ? ' | ' : '';

				$output .= "<span class='$action'>{$link}{$separator}</span>";
			}

			$output .= '</div>';

			$output .= '<button type="button" class="toggle-row"><span class="screen-reader-text">' .
			/* translators: Hidden accessibility text. */
			__( 'Show more details' ) .
			'</span></button>';

			return $output;
		}


		/**
		 * Checks and returns if there are items to show.
		 *
		 * @return bool
		 *
		 * @since 1.4.0
		 */
		public static function are_there_items(): bool {
			return ( isset( self::$read_items ) && ! empty( self::$read_items ) );
		}

		/**
		 * Returns a file path, name, and line number, or a clickable link to the file. Safe for output.
		 *
		 * @link https://querymonitor.com/help/clickable-stack-traces-and-function-names/
		 *
		 * @param  string $text        The display text, such as a function name or file name.
		 * @param  string $file        The full file path and name.
		 * @param  int    $line        Optional. A line number, if appropriate.
		 * @param  bool   $is_filename Optional. Is the text a plain file name? Default false.
		 * @return string The fully formatted file link or file name, safe for output.
		 */
		public static function output_filename( $text, $file, $line = 0, $is_filename = false ) {
			if ( empty( $file ) ) {
				if ( $is_filename ) {
					return esc_html( $text );
				} else {
					return '<code>' . esc_html( $text ) . '</code>';
				}
			}

			$link_line = $line ? $line : 1;

			$source_link = '';

			$query_array = array(
				'_wpnonce' => \wp_create_nonce( 'source-view' ),
				'action'   => 'log_source_view',
			);

			if ( isset( $file ) && ! empty( $file ) ) {
				$query_array['error_file'] = $file;

				if ( isset( $link_line ) && ! empty( $link_line ) ) {
					$query_array['error_line'] = $link_line;
				}

				$query_array['TB_iframe'] = 'true';

				$view_url = \esc_url_raw(
					\add_query_arg( $query_array, \admin_url( 'admin-ajax.php' ) )
				);

				$title = __( 'Viewing: ', '0-day-analytics' ) . $query_array['error_file'];

				$source_link = '<div> <a href="' . $view_url . '" title="' . $title . '" class="thickbox view-source gray_lab badge">' . __( 'view source', '0-day-analytics' ) . '</a></div>';

			}

			if ( ! self::has_clickable_links() ) {
				$fallback = WP_Helper::standard_dir( $file, '' );
				if ( $line ) {
					$fallback .= ':' . $line;
				}
				if ( $is_filename ) {
					$return = esc_html( $text );
				} else {
					$return = '<code>' . esc_html( $text ) . '</code>';
				}
				if ( $fallback !== $text ) {
					$return .= '<br><span>' . esc_html( $fallback ) . '</span>' . $source_link;
				}
				return $return;
			}

			$map = self::get_file_path_map();

			if ( ! empty( $map ) ) {
				foreach ( $map as $from => $to ) {
					$file = str_replace( $from, $to, $file );
				}
			}

			$link_format = self::get_file_link_format();
			$link        = sprintf( $link_format, rawurlencode( $file ), intval( $link_line ) );

			if ( $is_filename ) {
				$format = '<a href="%1$s">%2$s%3$s</a>';
			} else {
				$format = '<a href="%1$s"><code>%2$s</code>%3$s</a>';
			}

			return sprintf(
				$format,
				esc_attr( $link ),
				esc_html( $text ),
				( 'edit' )
			);
		}

		/**
		 * Returns file path map
		 *
		 * @return array<string, string>
		 *
		 * @since 1.4.0
		 */
		public static function get_file_path_map() {
			$map = array();

			$host_path = getenv( 'HOST_PATH' );

			if ( ! empty( $host_path ) ) {
				$source         = rtrim( ABSPATH, DIRECTORY_SEPARATOR );
				$replacement    = rtrim( $host_path, DIRECTORY_SEPARATOR );
				$map[ $source ] = $replacement;
			}

			return $map;
		}

		/**
		 * Returns the extracted file format.
		 *
		 * @return string|false
		 *
		 * @since 1.4.0
		 */
		public static function get_file_link_format() {
			if ( ! isset( self::$file_link_format ) ) {
				$format = ini_get( 'xdebug.file_link_format' );

				if ( empty( $format ) ) {
					self::$file_link_format = false;
				} else {
					self::$file_link_format = str_replace( array( '%f', '%l' ), array( '%1$s', '%2$d' ), $format );
				}
			}

			return self::$file_link_format;
		}

		/**
		 * Check if there are clickable links in the file formatter.
		 *
		 * @return bool
		 *
		 * @since 1.4.0
		 */
		public static function has_clickable_links(): bool {
			return ( false !== self::get_file_link_format() );
		}

		/**
		 * Returns an array of CSS class names for the table.
		 *
		 * @return array<int,string> Array of class names.
		 *
		 * @since 1.4.0
		 */
		protected function get_table_classes() {
			return array( 'widefat', 'striped', 'table-view-list', $this->_args['plural'] );
		}

		/**
		 * Sorts the events by the selected column.
		 *
		 * @param array $a - First item to compare.
		 * @param array $b - Second item to compare.
		 *
		 * @return int
		 *
		 * @since 1.4.0
		 */
		private static function uasort_order_events( $a, $b ) {
			$orderby = ( ! empty( $_GET['orderby'] ) && is_string( $_GET['orderby'] ) ) ? sanitize_text_field( \wp_unslash( $_GET['orderby'] ) ) : 'crontrol_next';
			$order   = ( ! empty( $_GET['order'] ) && is_string( $_GET['order'] ) ) ? sanitize_text_field( \wp_unslash( $_GET['order'] ) ) : 'asc';
			$compare = 0;

			switch ( $orderby ) {
				case 'hook':
					if ( 'asc' === $order ) {
						$compare = strcmp( $a['hook'], $b['hook'] );
					} else {
						$compare = strcmp( $b['hook'], $a['hook'] );
					}
					break;
				case 'recurrence':
					if ( 'asc' === $order ) {
						$compare = ( $a['recurrence'] ?? 0 ) <=> ( $b['recurrence'] ?? 0 );
					} else {
						$compare = ( $b['recurrence'] ?? 0 ) <=> ( $a['recurrence'] ?? 0 );
					}
					break;
				default:
					if ( 'asc' === $order ) {
						$compare = $a['schedule'] <=> $b['schedule'];
					} else {
						$compare = $b['schedule'] <=> $a['schedule'];
					}
					break;
			}

			return $compare;
		}
	}
}
