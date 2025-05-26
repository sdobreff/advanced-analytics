<?php
/**
 * Responsible for the Showing the list of the transients.
 *
 * @package    advanced-analytics
 * @subpackage helpers
 *
 * @since 1.7.0
 *
 * @license    https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */

declare(strict_types=1);

namespace ADVAN\Lists;

use ADVAN\Helpers\Settings;
use ADVAN\Helpers\WP_Helper;
use ADVAN\Helpers\Crons_Helper;
use ADVAN\Helpers\Transients_Helper;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/template.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table-compat.php';
	require_once ABSPATH . 'wp-admin/includes/list-table.php';
}

/*
 * Base list table class
 */
if ( ! class_exists( '\ADVAN\Lists\Transients_List' ) ) {
	/**
	 * Responsible for rendering base table for manipulation.
	 *
	 * @since 1.7.0
	 */
	class Transients_List extends \WP_List_Table {

		public const SCREEN_OPTIONS_SLUG = 'advanced_analytics_transients_list';

		public const PAGE_SLUG = 'wp-control_page_advan_transients';

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
		 * Name of the table to show.
		 *
		 * @var string
		 *
		 * @since 1.7.0
		 */
		private static $table_name;

		/**
		 * How many.
		 *
		 * @var int
		 *
		 * @since 1.7.0
		 */
		protected $count;

		/**
		 * How many log records to read from the log page - that is a fall back option, it will try to extract that first from the stored user data, then from the settings and from here as a last resort.
		 *
		 * @var int
		 *
		 * @since 1.7.0
		 */
		protected static $transients_per_page = 10;

		/**
		 * Current setting (if any) of per_page property - caching value.
		 *
		 * @var int
		 *
		 * @since 1.7.5
		 */
		protected static $per_page = null;

		/**
		 * Holds the array with all of the column names and their representation in the table header.
		 *
		 * @var array
		 *
		 * @since 1.7.0
		 */
		private static $columns = array();

		/**
		 * Events Query Arguments.
		 *
		 * @since 1.7.0
		 * @since 1.7.0 Transformed to array
		 *
		 * @var array
		 */
		private static $query_args;

		/**
		 * Holds the current query arguments.
		 *
		 * @var array
		 *
		 * @since 1.7.0
		 */
		private static $query_occ = array();

		/**
		 * Holds the current query order.
		 *
		 * @var array
		 *
		 * @since 1.7.0
		 */
		private static $query_order = array();

		/**
		 * Holds the read lines from error log.
		 *
		 * @var array
		 *
		 * @since 1.7.0
		 */
		private static $read_items = null;

		/**
		 * Default class constructor.
		 *
		 * @param stdClass $query_args Events query arguments.
		 *
		 * @since 1.7.0
		 */
		public function __construct( $query_args ) {
			self::$query_args = $query_args;

			parent::__construct(
				array(
					'singular' => 'generated-transient',
					'plural'   => 'generated-transients',
					'ajax'     => true,
					'screen'   => WP_Helper::get_wp_screen(),
				)
			);

			self::$columns = self::manage_columns( array() );

			self::$table_name = 'advanced_transients';
		}

		/**
		 * Displays the search box.
		 *
		 * @since 1.7.0
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
		 * @since 1.7.0
		 */
		public static function escaped_search_input() {
			return isset( $_REQUEST[ self::SEARCH_INPUT ] ) ? \esc_sql( \sanitize_text_field( \wp_unslash( $_REQUEST[ self::SEARCH_INPUT ] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		/**
		 * Adds columns to the screen options screed.
		 *
		 * @param array $columns - Array of column names.
		 *
		 * @since 1.7.0
		 */
		public static function manage_columns( $columns ): array {
			$admin_fields = array(
				'cb'             => '<input type="checkbox" />', // to display the checkbox.
				'transient_name' => __( 'Name', '0-day-analytics' ),
				'schedule'       => esc_html(
					sprintf(
						/* translators: %s: UTC offset */
						__( 'Expiration (%s)', '0-day-analytics' ),
						WP_Helper::get_timezone_location()
					),
				),
				'value'          => __( 'Value', '0-day-analytics' ),
			);

			$screen_options = $admin_fields;

			return \array_merge( $screen_options, $columns );
		}

		/**
		 * Returns the table name.
		 *
		 * @since 1.7.0
		 */
		public static function get_table_name(): string {
			return self::$table_name;
		}

		/**
		 * Prepares the list of items for displaying.
		 *
		 * Query, filter data, handle sorting, and pagination, and any other data-manipulation required prior to rendering
		 *
		 * @since 1.7.0
		 */
		public function prepare_items() {
			$columns               = $this->get_columns();
			$hidden                = array();
			$sortable              = $this->get_sortable_columns();
			$this->_column_headers = array( $columns, $hidden, $sortable );

			// Vars.
			$search      = self::escaped_search_input();
			$per_page    = ! empty( $_GET['per_page'] ) ? absint( $_GET['per_page'] ) : $this->get_screen_option_per_page();
			$orderby     = ! empty( $_GET['orderby'] ) ? \sanitize_text_field( \wp_unslash( $_GET['orderby'] ) ) : '';
			$order       = ! empty( $_GET['order'] ) ? \sanitize_text_field( \wp_unslash( $_GET['order'] ) ) : 'DESC';
			$page        = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
			$offset      = $per_page * ( $page - 1 );
			$pages       = ceil( $this->count / $per_page );
			$one_page    = ( 1 === $pages ) ? 'one-page' : '';
			$this->count = self::get_total_transients( $search );

			$this->handle_table_actions();

			$this->fetch_table_data(
				array(
					'search'  => $search,
					'offset'  => $offset,
					'number'  => $per_page,
					'orderby' => $orderby,
					'order'   => $order,
				)
			);

			$hidden = get_user_option( 'manage' . WP_Helper::get_wp_screen()->id . 'columnshidden', false );
			if ( ! $hidden ) {
				$hidden = array();
			}

			$this->_column_headers = array( self::$columns, $hidden, $sortable );

			// Set the pagination.
			$this->set_pagination_args(
				array(
					'total_items' => $this->count,
					'per_page'    => $per_page,
					'total_pages' => ceil( $this->count / $per_page ),
				)
			);
		}

		/**
		 * Returns the currently hidden column headers for the current user
		 *
		 * @return array
		 *
		 * @since 1.7.0
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
		 * @since 1.7.0
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
		 * @since 1.7.0
		 *
		 * @return array
		 */
		protected function get_sortable_columns() {
			return array(
				// 'transient_name' => array( 'transient_name', false ),
				// 'schedule'       => array( 'schedule', false, null, null, 'asc' ),
			);
		}

		/**
		 * Text displayed when no user data is available.
		 *
		 * @since 1.7.0
		 *
		 * @return void
		 */
		public function no_items() {
			\esc_html_e( 'No transients found', '0-day-analytics' );
		}

		/**
		 * Fetch table data from the WordPress database.
		 *
		 * @param array $args - The arguments collected / passed.
		 *
		 * @since 1.7.0
		 *
		 * @return array
		 */
		public function fetch_table_data( array $args = array() ) {

			$this->items = self::get_transient_items( $args );

			return $this->items;
		}

		/**
		 * Parse the query arguments
		 *
		 * @param  array $args - Array to parse.
		 *
		 * @return array
		 *
		 * @since 1.7.0
		 */
		private static function parse_args( $args = array() ) {

			// Parse.
			$parsed_args = \wp_parse_args(
				$args,
				array(
					'offset' => 0,
					'number' => self::get_screen_option_per_page(),
					'search' => '',
					'count'  => false,
				)
			);

			// Return.
			return $parsed_args;
		}

		/**
		 * Collect error items.
		 *
		 * @param  array $args - Array with arguments to use.
		 *
		 * @return array|int
		 */
		public static function get_transient_items( $args = array() ) {

			global $wpdb;

			// Parse arguments.
			$parsed_args = self::parse_args( $args );

			// Escape some LIKE parts.
			$esc_name = '%' . $wpdb->esc_like( '_transient_' ) . '%';
			$esc_time = '%' . $wpdb->esc_like( '_transient_timeout_' ) . '%';

			// SELECT.
			$sql = array( 'SELECT' );

			// COUNT.
			if ( ! empty( $parsed_args['count'] ) ) {
				$sql[] = 'count(option_id)';
			} else {
				$sql[] = 'option_id, option_name, option_value, autoload';
			}

			// FROM.
			$sql[] = "FROM {$wpdb->options} WHERE option_name LIKE %s AND option_name NOT LIKE %s";

			// Search.
			if ( ! empty( $parsed_args['search'] ) ) {
				$search = '%' . $wpdb->esc_like( $parsed_args['search'] ) . '%';
				$sql[]  = $wpdb->prepare( 'AND option_name LIKE %s', $search );
			}

			// Limits.
			if ( empty( $parsed_args['count'] ) ) {
				$offset = absint( $parsed_args['offset'] );
				$number = absint( $parsed_args['number'] );

				if ( ! empty( $parsed_args['orderby'] ) && \in_array( $parsed_args['orderby'], array( 'transient_name' ) ) ) {

					$orderby = 'option_name';

					$order = 'DESC';

					if ( ! empty( $parsed_args['order'] ) && \in_array( $parsed_args['order'], array( 'ASC', 'DESC', 'asc', 'desc' ) ) ) {

						$order = $parsed_args['order'];
					}

					$sql[] = $wpdb->prepare(
						'ORDER BY ' . \esc_sql( $orderby ) . ' ' . \esc_sql( $order ) . ' LIMIT %d, %d',
						$offset,
						$number
					);
				} else {
					$sql[] = $wpdb->prepare( 'ORDER BY option_id DESC LIMIT %d, %d', $offset, $number );
				}
			}

			// Combine the SQL parts.
			$query = implode( ' ', $sql );

			// Prepare.
			$prepared = $wpdb->prepare( $query, $esc_name, $esc_time ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			// Query.
			$transients = empty( $parsed_args['count'] )
				? $wpdb->get_results( $prepared, \ARRAY_A ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				: $wpdb->get_var( $prepared, 0 );    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			if ( empty( $parsed_args['count'] ) ) {
				$normalized_data = array();
				foreach ( $transients as $transient ) {
					$normalized_data[] = array(
						'transient_name' => Transients_Helper::get_transient_name( $transient['option_name'] ),
						'value'          => self::get_transient_value( $transient['option_value'] ),
						'schedule'       => self::get_transient_expiration_time( $transient['option_name'] ),
						'id'             => $transient['option_id'],

					);
				}
				$transients = $normalized_data;
			}

			// Return transients.
			return $transients;
		}

		/**
		 * Retrieve the total number transients in the database
		 *
		 * If a search is performed, it returns the number of found results
		 *
		 * @param  string $search - Search string.
		 * @return int
		 *
		 * @since 1.7.0
		 */
		private static function get_total_transients( $search = '' ) {

			// Query.
			$count = self::get_transient_items(
				array(
					'count'  => true,
					'search' => $search,
				)
			);

			// Return int.
			return absint( $count );
		}

		/**
		 * Returns the current query
		 *
		 * @return array
		 *
		 * @since 1.7.0
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
		 * @since 1.7.0
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
		 * @since 1.7.0
		 */
		public static function format_column_value( $item, $column_name ) {
			switch ( $column_name ) {
				case 'transient_name':
					$query_args_view_data['hash']     = $item['id'];
					$query_args_view_data['_wpnonce'] = \wp_create_nonce( 'bulk-custom-delete' );

					$actions['delete'] = '<a class="aadvana-transient-delete" href="#" data-nonce="' . $query_args_view_data['_wpnonce'] . '" data-id="' . $query_args_view_data['hash'] . '">' . \esc_html__( 'Delete', '0-day-analytics' ) . '</a>';

					// $actions['run'] = '<a class="aadvana-transient-run" href="#" data-nonce="' . $query_args_view_data['_wpnonce'] . '" data-hash="' . $query_args_view_data['hash'] . '">' . \esc_html__( 'Run', '0-day-analytics' ) . '</a>';

					return '<span><b>' . $item['transient_name'] . '</b></span>' . self::single_row_actions( $actions );
				case 'schedule':
					if ( 0 === $item['schedule'] ) {
						return '&mdash;<br><span class="badge">' . esc_html__( 'Persistent', '0-day-analytics' ) . '</span>';
					}

					return WP_Helper::time_formatter( $item, \esc_html__( 'Expired', '0-day-analytics' ) );
				case 'value':
					return $item['value'];
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
		 * @since 1.7.0
		 */
		protected function column_cb( $item ) {
			return sprintf(
				'<label class="screen-reader-text" for="' . $item['id'] . '">' . sprintf(
					// translators: The column name.
					__( 'Select %s' ),
					'id'
				) . '</label>'
				. '<input type="checkbox" name="' . self::$table_name . '[]" id="' . $item['id'] . '" value="' . $item['id'] . '" />'
			);
		}

		/**
		 * Returns an associative array containing the bulk actions.
		 *
		 * @since 1.7.0
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
				// 'run'    => __( 'Run', '0-day-security' ),
			);

			return $actions;
		}

		/**
		 * Process actions triggered by the user.
		 *
		 * @since 1.7.0
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
							// Delete the transient.
							Transients_Helper::delete_transient( (int) $id );
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
							// Delete the transient.
							// Crons_Helper::execute_event( $id );
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
		 * @since 1.7.0
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
		 * @since 1.7.0
		 */
		public static function get_default_per_page() {
			return self::$transients_per_page;
		}

		/**
		 * Get the screen option per_page.
		 *
		 * @return int
		 *
		 * @since 1.7.0
		 */
		private static function get_screen_option_per_page() {

			if ( null !== self::$per_page ) {
				return self::$per_page;
			} else {
				$wp_screen = WP_Helper::get_wp_screen();

				if ( self::PAGE_SLUG === $wp_screen->base ) {
					$option = $wp_screen->get_option( 'per_page', 'option' );
					if ( ! $option ) {
						$option = str_replace( '-', '_', $wp_screen->id . '_per_page' );
					}
				} else {
					$option = 'advanced_analytics_transients_list_per_page';
				}

				self::$per_page = (int) \get_user_option( $option );
				if ( empty( self::$per_page ) || self::$per_page < 1 ) {
					self::$per_page = $wp_screen->get_option( 'per_page', 'default' );
					if ( ! self::$per_page ) {
						self::$per_page = self::get_default_per_page();
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
		 * @since 1.7.0
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
		 * @since 1.7.0
		 */
		public static function add_screen_options( $hook ) {
			$screen_options = array( 'per_page' => __( 'Number of transients to read', '0-day-analytics' ) );

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
							'default' => self::get_default_per_page(),
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
		 * @since 1.7.0
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
		 * Generates content for a single row of the table.
		 *
		 * @param object|array $item - The current item.
		 *
		 * @since 1.7.0
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
		 * @since 1.7.0
		 */
		public function display_tablenav( $which ) {
			if ( 'top' === $which ) {
				wp_nonce_field( 'bulk-' . $this->_args['plural'] );

				?>
				<script>
					jQuery(document).ready(function(){
						jQuery('.aadvana-transient-delete').on('click', function(e){

							e.preventDefault();

							let that = this;

							var data = {
								'action': 'aadvana_delete_transient',
								'post_type': 'GET',
								'_wpnonce': jQuery(this).data('nonce'),
								'id': jQuery(this).data('id'),
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
							}, 'json');

						});
					});
				</script>
				<style>
					.wp-control_page_advan_transients .generated-transients .late th:nth-child(1) {
						border-left: 7px solid #dd9192 !important;
					}
					.wp-control_page_advan_transients .generated-transients .on-time th:nth-child(1) {
						border-left: 7px solid rgb(49, 179, 45) !important;
					}
				</style>
				<?php
			}
			?>
			<div class="tablenav <?php echo esc_attr( $which ); ?>">

			<?php if ( $this->has_items() ) { ?>
				<div class="alignleft actions bulkactions">
					<?php $this->bulk_actions( $which ); ?>
				</div>
				<?php
			}
			$this->extra_tablenav( $which );
			$this->pagination( $which );

			?>

				<br class="clear" />
			</div>
			<?php
				$this->extra_tablenav( $which );
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
		 * Retrieve the human-friendly transient value from the transient object
		 *
		 * @param  string $transient - The transient value.
		 *
		 * @return string/int
		 *
		 * @since 1.7.0
		 */
		private static function get_transient_value( $transient ) {

			// Get the value type.
			$type = self::get_transient_value_type( $transient );

			// Trim value to 100 chars.
			$value = substr( $transient, 0, 100 );

			// Escape & wrap in <code> tag.
			$value = '<code>' . \esc_html( $value ) . '</code>';

			// Return.
			return $value . '<br><span class="transient-type badge">' . esc_html( $type ) . '</span>';
		}

		/**
		 * Retrieve the expiration timestamp
		 *
		 * @param  string $transient - The transient name.
		 *
		 * @return int
		 *
		 * @since 1.7.0
		 */
		private static function get_transient_expiration_time( $transient ): int {

			// Get the same to use in the option key.
			$name = Transients_Helper::get_transient_name( $transient );

			// Get the value of the timeout.
			$time = Transients_Helper::is_site_wide( $transient )
			? \get_option( "_site_transient_timeout_{$name}" )
			: \get_option( "_transient_timeout_{$name}" );

			// Return the value.
			return (int) $time;
		}

		/**
		 * Try to guess the type of value the Transient is
		 *
		 * @param  mixed $transient - The transient value.
		 *
		 * @return string
		 *
		 * @since 1.7.0
		 */
		private static function get_transient_value_type( $transient ): string {

			// Default type.
			$type = esc_html__( 'unknown', '0-day-analytics' );

			// Try to unserialize.
			$value = maybe_unserialize( $transient );

			// Array.
			if ( is_array( $value ) ) {
				$type = esc_html__( 'array', '0-day-analytics' );

				// Object.
			} elseif ( is_object( $value ) ) {
				$type = esc_html__( 'object', '0-day-analytics' );

				// Serialized array.
			} elseif ( is_serialized( $value ) ) {
				$type = esc_html__( 'serialized', '0-day-analytics' );

				// HTML.
			} elseif ( strip_tags( $value ) !== $value ) {
				$type = esc_html__( 'html', '0-day-analytics' );

				// Scalar.
			} elseif ( is_scalar( $value ) ) {

				if ( is_numeric( $value ) ) {

					// Likely a timestamp.
					if ( 10 === strlen( $value ) ) {
						$type = esc_html__( 'timestamp?', '0-day-analytics' );

						// Likely a boolean.
					} elseif ( in_array( $value, array( '0', '1' ), true ) ) {
						$type = esc_html__( 'boolean?', '0-day-analytics' );

						// Any number.
					} else {
						$type = esc_html__( 'numeric', '0-day-analytics' );
					}

					// JSON.
				} elseif ( is_string( $value ) && is_object( json_decode( $value ) ) ) {

					$type = esc_html__( 'json', '0-day-analytics' );
				} elseif ( is_string( $value ) && in_array( $value, array( 'no', 'yes', 'false', 'true' ), true ) ) {
						$type = esc_html__( 'boolean?', '0-day-analytics' );

					// Scalar.
				} else {
					$type = esc_html__( 'scalar', '0-day-analytics' );
				}

				// Empty.
			} elseif ( empty( $value ) ) {
				$type = esc_html__( 'empty', '0-day-analytics' );
			}

			// Return type.
			return $type;
		}
	}
}
