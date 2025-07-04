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

use ADVAN\Entities\Common_Table;

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

		/**
		 * Current screen
		 *
		 * @var [type]
		 *
		 * @since 2.1.0
		 */
		protected $wp_screen;

		/**
		 * The table to show
		 *
		 * @var \WpWhiteSecurity\Proxytron\Entities\Abstract_Entity
		 *
		 * @since 2.1.0
		 */
		protected $table;

		/**
		 * Name of the table to show
		 *
		 * @var string
		 *
		 * @since 2.1.0
		 */
		protected $table_name;

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
		protected $records_per_page = 20;

		/**
		 * Default class constructor
		 *
		 * @param string $table_name - The name of the table to use for the listing.
		 *
		 * @since 2.1.0
		 */
		public function __construct( string $table_name ) {

			parent::__construct(
				array(
					'plural'   => $table_name,    // Plural value used for labels and the objects being listed.
					'singular' => $table_name,     // Singular label for an object being listed, e.g. 'post'.
					'ajax'     => false,      // If true, the parent class will call the _js_vars() method in the footer.
				)
			);

			$class = Common_Table::class;

			Common_Table::init( $table_name );

			$this->table_name = $class::get_name();
			$this->table      = $class;

			$screen = $this->get_wp_screen();

			add_filter( 'manage_' . $screen->id . '_columns', array( $class, 'manage_columns' ) );
		}

		/**
		 * Returns the table name
		 *
		 * @return string
		 *
		 * @since 2.1.0
		 */
		public function get_table_name(): string {
			return $this->table_name;
		}

		/**
		 * Get the wp_screen property.
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
		 * @since   1.0.0
		 */
		public function prepare_items() {
			$this->handle_table_actions();

			$items = $this->fetch_table_data();

			$columns = $this->table::manage_columns( array() );
			$hidden  = get_user_option( 'manage' . $this->get_wp_screen()->id . 'columnshidden', false );
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
					'per_page'    => $this->get_screen_option_per_page(),
					'total_pages' => ceil( $this->count / $this->get_screen_option_per_page() ),
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
			return $this->table::manage_columns( array() );
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
			$first6_columns = array_keys( $this->table::get_column_names_admin() );

			/**
			 * Actual sorting still needs to be done by prepare_items.
			 * specify which columns should have the sort icon.
			 *
			 * The second bool param sets the colum sort order - true ASC, false - DESC or unsorted.
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
		 * @return string
		 */
		public function no_items() {
			return 'No records available.';
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

			$per_page = $this->get_screen_option_per_page();

			$current_page = $this->get_pagenum();
			if ( 1 < $current_page ) {
				$offset = $per_page * ( $current_page - 1 );
			} else {
				$offset = 0;
			}

			$search_string = ( isset( $_REQUEST['s'] ) ? \esc_sql( \wp_unslash( $_REQUEST['s'] ) ) : '' );

			$search_sql = '';

			if ( '' !== $search_string ) {
				$search_sql = 'AND (' . $this->table::get_real_id_name() . ' LIKE "%' . $wpdb->esc_like( $search_string ) . '%"';
				foreach ( array_keys( $this->table::get_column_names_admin() ) as $value ) {
					$search_sql .= ' OR ' . $value . ' LIKE "%' . esc_sql( $wpdb->esc_like( $search_string ) ) . '%" ';
				}
				$search_sql .= ') ';
			}

			$wpdb_table = $this->get_table_name();

			$orderby = ( isset( $_GET['orderby'] ) && '' != $_GET['orderby'] ) ? \esc_sql( \wp_unslash( $_GET['orderby'] ) ) : $this->table::get_real_id_name();
			$order   = ( isset( $_GET['order'] ) && '' != $_GET['orderby'] ) ? \esc_sql( \wp_unslash( $_GET['order'] ) ) : 'ASC';
			$query   = 'SELECT
				' . implode( ', ', $this->table::get_column_names() ) . '
			  FROM ' . $wpdb_table . '  WHERE 1=1 ' . $search_sql . ' ORDER BY ' . $orderby . ' ' . $order;

			$query .= $wpdb->prepare( ' LIMIT %d OFFSET %d;', $per_page, $offset );

			// query output_type will be an associative array with ARRAY_A.
			// phpcs:ignore
			$query_results = $wpdb->get_results( $query, ARRAY_A );

			// phpcs:ignore
			$this->count = $wpdb->get_var( 'SELECT COUNT(' . $this->table::get_real_id_name() . ') FROM ' . $wpdb_table . '  WHERE 1=1 ' . $search_sql );

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
				case 'type':
					return $item[ $column_name ];
				default:
					return $this->common_column_render( $item, $column_name );
			}
		}

		/**
		 * That column logic is different
		 * In order to safe space and keep things fast, we are using CHAR(0) to store data in it
		 * Which means that NULL in that column means paid account and empty string ('') means trail account
		 *
		 * @param array $item - Array with the current row values.
		 *
		 * @return void
		 *
		 * @since 2.1.0
		 */
		protected function column_type( $item ) {
			echo ( null === $item['type'] ) ? 'Paid' : 'Trial';
		}

		/**
		 * In that column we show the number of user's quotas current license has. Important - not site but license
		 * If that has value of -1 - that means unlimited quota.
		 *
		 * @param array $item - Array with the current row values.
		 *
		 * @return void
		 *
		 * @since 2.1.0
		 */
		protected function column_quota( $item ) {
			echo ( -1 === (int) $item['quota'] ) ? \esc_html( 'Unlimited', 'wps-proxytron' ) : \esc_html( $item['quota'], 'wps-proxytron' );
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
					'page'                    => ( isset( $_REQUEST['page'] ) ) ? \sanitize_text_field( \wp_unslash( $_REQUEST['page'] ) ) : 'wps-proxytron-sites',
					'action'                  => 'view_data',
					$this->table_name . '_id' => absint( $item[ $this->table::get_real_id_name() ] ),
					'_wpnonce'                => \wp_create_nonce( 'view_data_nonce' ),
					'get_back'                => urlencode( $pagenow . '?page=' . $current_screen->parent_base . '&paged=' . $paged . $search . $orderby . $order ),
				);
				$view_data_link       = esc_url( add_query_arg( $query_args_view_data, $admin_page_url ) );
				$actions['view_data'] = '<a href="' . $view_data_link . '">' . \esc_html( 'Show Info', 'wps-proxytron' ) . '</a>';
			}

			$row_value = '<strong>' . $item[ $column_name ] . '</strong>';

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
				'<label class="screen-reader-text" for="' . $this->table_name . '_' . $item[ $this->table::get_real_id_name() ] . '">' . sprintf(
					// translators: The column name.
					__( 'Select %s' ),
					$this->table::get_real_id_name()
				) . '</label>'
				. '<input type="checkbox" name="' . $this->table_name . '[]" id="' . $this->table_name . '_' . $item[ $this->table::get_real_id_name() ] . '" value="' . $item[ $this->table::get_real_id_name() ] . '" />'
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
				'delete' => 'Delete Records',
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
					$this->page_view_data( absint( $_REQUEST[ $this->table_name . '_id' ] ) );
					$this->graceful_exit();
				}
			}

			if ( 'add_data' === $the_table_action ) {

				if ( ! isset( $_REQUEST['_wpnonce'] ) ) {
					$this->graceful_exit();
				}
					$nonce = \sanitize_text_field( \wp_unslash( $_REQUEST['_wpnonce'] ) );

				// verify the nonce.
				if ( ! wp_verify_nonce( $nonce, 'add_' . $this->table_name . '_nonce' ) ) {
					$this->invalid_nonce_redirect();
				} else {
					$this->page_add_data( absint( $_REQUEST[ $this->table_name . '_id' ] ) );
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
					foreach ( $_REQUEST[ $this->table_name ] as $id ) {
						$this->table::delete_by_id( (int) $id );
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
		 * Stop execution and exit
		 *
		 * @since    1.0.0
		 *
		 * @return void
		 */
		public function graceful_exit() {
			exit;
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
		 * Returns the records to show per page.
		 *
		 * @return int
		 *
		 * @since 2.1.0
		 */
		public function get_records_per_page() {
			return $this->records_per_page;
		}

		/**
		 * Get the screen option per_page.
		 *
		 * @return int
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
	}
}
