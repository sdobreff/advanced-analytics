<?php
/**
 * Responsible for the table view
 *
 * @package    advana
 * @subpackage lists
 * @since      1.1
 * @license    https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 * @link       https://wordpress.org/plugins/0-day-analytics/
 */

declare(strict_types=1);

namespace ADVAN\Lists;

use ADVAN\Helpers\WP_Helper;
use ADVAN\Entities\Common_Table;
use ADVAN\Lists\Traits\List_Trait;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/template.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table-compat.php';
	require_once ABSPATH . 'wp-admin/includes/list-table.php';
}

/**
 * Base list table class
 */
if ( ! class_exists( '\ADVAN\Lists\Table_List' ) ) {

	/**
	 * Responsible for rendering base table for manipulation
	 *
	 * @since 2.1.0
	 */
	class Table_List extends \WP_List_Table {

		use List_Trait;

		public const PAGE_SLUG = 'wp-control_page_advan_table';

		public const SCREEN_OPTIONS_SLUG = 'advanced_analytics_table_list';

		public const SEARCH_INPUT = 's';

		/**
		 * The table to show
		 *
		 * @var Common_Table
		 *
		 * @since 2.1.0
		 */
		private static $table;

		/**
		 * How many
		 *
		 * @var int
		 *
		 * @since 2.1.0
		 */
		protected $count;

		/**
		 * How many records to show per page
		 *
		 * @var integer
		 *
		 * @since 2.1.0
		 */
		protected static $rows_per_page = 20;

		/**
		 * Default class constructor
		 *
		 * @param string $table_name - The name of the table to use for the listing.
		 *
		 * @since 2.1.0
		 */
		public function __construct( string $table_name ) {

			$class = Common_Table::class;

			Common_Table::init( $table_name );
			self::$table = $class;

			\add_filter( 'manage_' . WP_Helper::get_wp_screen()->id . '_columns', array( $class, 'manage_columns' ) );

			parent::__construct(
				array(
					'plural'   => $table_name,    // Plural value used for labels and the objects being listed.
					'singular' => $table_name,     // Singular label for an object being listed, e.g. 'post'.
					'ajax'     => false,      // If true, the parent class will call the _js_vars() method in the footer.
				)
			);
		}

		/**
		 * Returns the table name
		 *
		 * @return string
		 *
		 * @since 2.1.0
		 */
		public function get_table_name(): string {
			return self::$table::get_name();
		}

		/**
		 * Prepares the list of items for displaying.
		 *
		 * Query, filter data, handle sorting, and pagination, and any other data-manipulation required prior to rendering
		 *
		 * @since   1.0.0
		 */
		public function prepare_items() {
			$this->handle_table_actions();

			$items = $this->fetch_table_data();

			$columns = self::$table::manage_columns( array() );
			$hidden  = get_user_option( 'manage' . WP_Helper::get_wp_screen()->id . 'columnshidden', false );
			if ( ! $hidden ) {
				$hidden = array();
			}
			$sortable              = $this->get_sortable_columns();
			$this->_column_headers = array( $columns, $hidden, $sortable );
			// phpcs:ignore
			// usort( $items, [ &$this, 'usort_reorder' ] ); // phpcs:ignore

			$this->items = $items;
			// Set the pagination.
			$this->set_pagination_args(
				array(
					'total_items' => $this->count,
					'per_page'    => self::get_screen_option_per_page(),
					'total_pages' => ceil( $this->count / self::get_screen_option_per_page() ),
				)
			);
		}

		/**
		 * Get a list of columns. The format is:
		 * 'internal-name' => 'Title'
		 *
		 * @since 1.0.0
		 *
		 * @return array
		 */
		public function get_columns() {
			return self::$table::manage_columns( array() );
		}

		/**
		 * Get a list of sortable columns. The format is:
		 * 'internal-name' => 'orderby'
		 * or
		 * 'internal-name' => array( 'orderby', true )
		 *
		 * The second format will make the initial sorting order be descending
		 *
		 * @since 1.1.0
		 *
		 * @return array
		 */
		protected function get_sortable_columns() {
			$first6_columns = array_keys( self::$table::get_column_names_admin() );

			/**
			 * Actual sorting still needs to be done by prepare_items.
			 * specify which columns should have the sort icon.
			 *
			 * The second bool param sets the column sort order - true ASC, false - DESC or unsorted.
			 */
			foreach ( $first6_columns as  $value ) {
				$sortable_columns[ $value ] = array( $value, false );
			}

			return $sortable_columns;
		}

		/**
		 * Text displayed when no user data is available
		 *
		 * @since   1.0.0
		 *
		 * @return void
		 */
		public function no_items() {
			\esc_html_e( 'No rows', '0-day-analytics' );
		}

		/**
		 * Fetch table data from the WordPress database.
		 *
		 * @since 1.0.0
		 *
		 * @return  Array
		 */
		public function fetch_table_data() {

			global $wpdb;

			$per_page = self::get_screen_option_per_page();

			$current_page = $this->get_pagenum();
			if ( 1 < $current_page ) {
				$offset = $per_page * ( $current_page - 1 );
			} else {
				$offset = 0;
			}

			$search_string = self::escaped_search_input();

			$search_sql = '';

			if ( '' !== $search_string ) {
				$search_sql = 'AND (' . self::$table::get_real_id_name() . ' LIKE "%' . $wpdb->esc_like( $search_string ) . '%"';
				foreach ( array_keys( self::$table::get_column_names_admin() ) as $value ) {
					$search_sql .= ' OR ' . $value . ' LIKE "%' . esc_sql( $wpdb->esc_like( $search_string ) ) . '%" ';
				}
				$search_sql .= ') ';
			}

			$wpdb_table = $this->get_table_name();

			$orderby = ( isset( $_GET['orderby'] ) && '' != $_GET['orderby'] ) ? \esc_sql( \wp_unslash( $_GET['orderby'] ) ) : self::$table::get_real_id_name();
			$order   = ( isset( $_GET['order'] ) && '' != $_GET['orderby'] ) ? \esc_sql( \wp_unslash( $_GET['order'] ) ) : 'ASC';
			$query   = 'SELECT
				' . implode( ', ', self::$table::get_column_names() ) . '
			  FROM ' . $wpdb_table . '  WHERE 1=1 ' . $search_sql . ' ORDER BY ' . $orderby . ' ' . $order;

			$query .= $wpdb->prepare( ' LIMIT %d OFFSET %d;', $per_page, $offset );

			// query output_type will be an associative array with ARRAY_A.
			// phpcs:ignore
			$query_results = $wpdb->get_results( $query, ARRAY_A );

			// phpcs:ignore
			$this->count = $wpdb->get_var( 'SELECT COUNT(' . self::$table::get_real_id_name() . ') FROM ' . $wpdb_table . '  WHERE 1=1 ' . $search_sql );

			// return result array to prepare_items.
			return $query_results;
		}

		/**
		 * Filter the table data based on the user search key
		 *
		 * @since 1.0.0
		 *
		 * @param array  $table_data - The data from the row.
		 * @param string $search_key - The search key.
		 *
		 * @return array
		 */
		public function filter_table_data( $table_data, $search_key ) {
			$filtered_table_data = array_values(
				array_filter(
					$table_data,
					function( $row ) use ( $search_key ) {
						foreach ( $row as $row_val ) {
							if ( stripos( $row_val, $search_key ) !== false ) {
								return true;
							}
						}
					}
				)
			);

			return $filtered_table_data;
		}

		/**
		 * Render a column when no column specific method exists.
		 *
		 * Use that method for common rendering and separate columns logic in different methods. See below.
		 *
		 * @param array  $item - Array with the current row values.
		 * @param string $column_name - The name of the currently processed column.
		 *
		 * @return mixed
		 */
		public function column_default( $item, $column_name ) {

			switch ( $column_name ) {

				default:
					return $this->common_column_render( $item, $column_name );
			}
		}

		/**
		 * Responsible for common column rendering
		 *
		 * @param array  $item - The current riw with data.
		 * @param string $column_name - The column name.
		 *
		 * @return string
		 *
		 * @since 2.1.0
		 */
		private function common_column_render( array $item, $column_name ): string {
			global $pagenow, $current_screen;

			$admin_page_url = admin_url( 'admin.php' );

			$paged = ( isset( $_GET['paged'] ) ) ? \sanitize_text_field( \wp_unslash( $_GET['paged'] ) ) : 1;

			$search  = ( isset( $_REQUEST['s'] ) ) ? '&s=' . \sanitize_text_field( \wp_unslash( $_REQUEST['s'] ) ) : '';
			$orderby = ( isset( $_REQUEST['orderby'] ) ) ? '&orderby=' . \sanitize_text_field( \wp_unslash( $_REQUEST['orderby'] ) ) : '';
			$order   = ( isset( $_REQUEST['order'] ) ) ? '&order=' . \sanitize_text_field( \wp_unslash( $_REQUEST['order'] ) ) : '';

			$actions = array();
			if ( 'plugin_id' === $column_name ) {
				// row actions to edit record.
				$query_args_view_data = array(
					'page'                           => ( isset( $_REQUEST['page'] ) ) ? \sanitize_text_field( \wp_unslash( $_REQUEST['page'] ) ) : 'wps-proxytron-sites',
					'action'                         => 'view_data',
					self::$table::get_name() . '_id' => absint( $item[ self::$table::get_real_id_name() ] ),
					'_wpnonce'                       => \wp_create_nonce( 'view_data_nonce' ),
					'get_back'                       => urlencode( $pagenow . '?page=' . $current_screen->parent_base . '&paged=' . $paged . $search . $orderby . $order ),
				);
				$view_data_link       = esc_url( add_query_arg( $query_args_view_data, $admin_page_url ) );
				$actions['view_data'] = '<a href="' . $view_data_link . '">' . \esc_html( 'Show Info', 'wps-proxytron' ) . '</a>';
			}

			$row_value = '<strong>' . \esc_html( $item[ $column_name ] ) . '</strong>';

			return $row_value . $this->row_actions( $actions );
		}

		/**
		 * Get value for checkbox column.
		 *
		 * The special 'cb' column
		 *
		 * @param object $item - A row's data.
		 *
		 * @return string Text to be placed inside the column < td > .
		 */
		protected function column_cb( $item ) {
			return sprintf(
				'<label class="screen-reader-text" for="' . self::$table::get_name() . '_' . $item[ self::$table::get_real_id_name() ] . '">' . sprintf(
					// translators: The column name.
					__( 'Select %s' ),
					self::$table::get_real_id_name()
				) . '</label>'
				. '<input type="checkbox" name="' . self::$table::get_name() . '[]" id="' . self::$table::get_name() . '_' . $item[ self::$table::get_real_id_name() ] . '" value="' . $item[ self::$table::get_real_id_name() ] . '" />'
			);
		}

		/**
		 * Returns an associative array containing the bulk actions
		 *
		 * @since    1.0.0
		 *
		 * @return array
		 */
		public function get_bulk_actions() {

			/**
			 * On hitting apply in bulk actions the url paramas are set as
			 * ?action=bulk-download&paged=1&action2=-1
			 *
			 * Action and action2 are set based on the triggers above or below the table
			 */
			$actions = array(
				'delete' => __( 'Delete Records', '0-day-security' ),
			);

			return $actions;
		}

		/**
		 * Process actions triggered by the user
		 *
		 * @since    1.0.0
		 */
		public function handle_table_actions() {

			/**
			 * Note: Table bulk_actions can be identified by checking $_REQUEST['action'] and $_REQUEST['action2']
			 *
			 * Action - is set if checkbox from top-most select-all is set, otherwise returns -1
			 * Action2 - is set if checkbox the bottom-most select-all checkbox is set, otherwise returns -1
			 */

			// check for individual row actions.
			$the_table_action = $this->current_action();

			if ( 'view_data' === $the_table_action ) {

				if ( ! isset( $_REQUEST['_wpnonce'] ) ) {
					$this->graceful_exit();
				}
				$nonce = \sanitize_text_field( \wp_unslash( $_REQUEST['_wpnonce'] ) );
				// verify the nonce.
				if ( ! wp_verify_nonce( $nonce, 'view_data_nonce' ) ) {
					$this->invalid_nonce_redirect();
				} else {
					$this->page_view_data( absint( $_REQUEST[ self::$table::get_name() . '_id' ] ) );
					$this->graceful_exit();
				}
			}

			if ( 'add_data' === $the_table_action ) {

				if ( ! isset( $_REQUEST['_wpnonce'] ) ) {
					$this->graceful_exit();
				}
					$nonce = \sanitize_text_field( \wp_unslash( $_REQUEST['_wpnonce'] ) );

				// verify the nonce.
				if ( ! wp_verify_nonce( $nonce, 'add_' . self::$table::get_name() . '_nonce' ) ) {
					$this->invalid_nonce_redirect();
				} else {
					$this->page_add_data( absint( $_REQUEST[ self::$table::get_name() . '_id' ] ) );
					$this->graceful_exit();
				}
			}

			// check for table bulk actions.
			if ( ( isset( $_REQUEST['action'] ) && 'delete' === $_REQUEST['action'] ) || ( isset( $_REQUEST['action2'] ) && 'delete' === $_REQUEST['action2'] ) ) {

				if ( ! isset( $_REQUEST['_wpnonce'] ) ) {
					$this->graceful_exit();
				}
					$nonce = \sanitize_text_field( \wp_unslash( $_REQUEST['_wpnonce'] ) );
				// verify the nonce.
				/**
				 * Note: the nonce field is set by the parent class
				 * wp_nonce_field( 'bulk-' . $this->_args['plural'] );
				 */
				if ( ! wp_verify_nonce( $nonce, 'bulk-' . $this->_args['plural'] ) ) {
					$this->invalid_nonce_redirect();
				} else {
					foreach ( $_REQUEST[ self::$table::get_name() ] as $id ) {
						self::$table::delete_by_id( (int) $id );
					}
				}
			}
		}

		/**
		 * View a license information.
		 *
		 * @since   1.0.0
		 *
		 * @param int $table_id  - Record ID.
		 */
		public function page_view_data( $table_id ) {

			// Edit_Data::set_table( $this->table );
			// Edit_Data::edit_record( $table_id );
		}

		/**
		 * Die when the nonce check fails.
		 *
		 * @since    1.0.0
		 *
		 * @return void
		 */
		public function invalid_nonce_redirect() {
			wp_die(
				'Invalid Nonce',
				'Error',
				array(
					'response'  => 403,
					'back_link' => esc_url( add_query_arg( array( 'page' => wp_unslash( $_REQUEST['page'] ) ), admin_url( 'users.php' ) ) ),
				)
			);
		}

		/**
		 * Table navigation.
		 *
		 * @param string $which - Position of the nav.
		 *
		 * @since 1.1.0
		 */
		public function extra_tablenav( $which ) {

			?>
			<div class="alignleft actions bulkactions">
			
				<select name="table_filter_<?php echo $which; ?>" class="advan-filter-table">
					<option value=""><?php \esc_html_e( 'Switch table', '0-day-analytics' ); ?></option>

					<?php
					foreach ( Common_Table::get_tables() as $table ) {
						?>
						<option><?php echo $table; ?></option>
						<?php
					}
					?>
					
				</select>
							
			</div>
			<?php
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
		 * Adds a screen options to the current screen table.
		 *
		 * @param \WP_Hook $hook - The hook object to attach to.
		 *
		 * @return void
		 *
		 * @since 1.7.0
		 */
		public static function add_screen_options( $hook ) {
			$screen_options = array( 'per_page' => __( 'Number of rows to show', '0-day-analytics' ) );

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
		 * Adds columns to the screen options screed.
		 *
		 * @param array $columns - Array of column names.
		 *
		 * @since 1.1.0
		 */
		public static function manage_columns( $columns ): array {

			return self::$table::manage_columns( $columns );
		}
	}
}
