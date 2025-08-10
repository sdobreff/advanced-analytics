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

use ADVAN\Lists\Logs_List;
use ADVAN\Helpers\Settings;
use ADVAN\Helpers\WP_Helper;
use ADVAN\Helpers\File_Helper;
use ADVAN\Entities\Common_Table;
use ADVAN\Lists\Views\Table_View;
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

		public const SWITCH_ACTION = 'switch_advan_table';

		public const SCREEN_OPTIONS_SLUG = 'advanced_analytics_table_list';

		public const SEARCH_INPUT = 's';

		public const TABLE_MENU_SLUG = 'advan_table';

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

			// \add_filter( 'manage_' . WP_Helper::get_wp_screen()->id . '_columns', array( $class, 'manage_columns' ) );

			parent::__construct(
				array(
					'plural'   => $table_name,    // Plural value used for labels and the objects being listed.
					'singular' => $table_name,     // Singular label for an object being listed, e.g. 'post'.
					'ajax'     => false,      // If true, the parent class will call the _js_vars() method in the footer.
				)
			);
		}

		/**
		 * Inits the module hooks.
		 *
		 * @return void
		 *
		 * @since 2.8.1
		 */
		public static function hooks_init() {
			\add_action( 'admin_post_' . self::SWITCH_ACTION, array( Table_View::class, 'switch_action' ) );
			\add_action( 'load-' . self::PAGE_SLUG, array( Table_View::class, 'page_load' ) );
		}

		/**
		 * Adds the module to the main plugin menu
		 *
		 * @return void
		 *
		 * @since 2.8.1
		 */
		public static function menu_add() {

			$table_hook = \add_submenu_page(
				Logs_List::MENU_SLUG,
				\esc_html__( 'WP Control', '0-day-analytics' ),
				\esc_html__( 'Table viewer', '0-day-analytics' ),
				( ( Settings::get_option( 'menu_admins_only' ) ) ? 'manage_options' : 'read' ), // No capability requirement.
				self::TABLE_MENU_SLUG,
				array( Table_View::class, 'analytics_table_page' ),
				4
			);

				self::add_screen_options( $table_hook );

				// \add_filter( 'manage_' . $table_hook . '_columns', array( Table_List::class, 'manage_columns' ) );

				\add_action( 'load-' . $table_hook, array( Settings::class, 'aadvana_common_help' ) );
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
			$hidden  = \get_user_option( 'manage' . WP_Helper::get_wp_screen()->id . 'columnshidden', false );
			if ( ! $hidden ) {
				$hidden = array();
			}
			$sortable              = $this->get_sortable_columns();
			$this->_column_headers = array( $columns, $hidden, $sortable );

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
			$query_results = $wpdb->get_results( $query, ARRAY_A );

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

			if ( $column_name === self::$table::get_real_id_name() ) {
				$query_args_view_data = array();

				$query_args_view_data['_wpnonce'] = \wp_create_nonce( 'bulk-' . $this->_args['plural'] );

				$delete_url =
					\add_query_arg(
						array(
							'action'           => 'delete',
							'advan_' . self::$table::get_name() => $item[ self::$table::get_real_id_name() ],
							self::SEARCH_INPUT => self::escaped_search_input(),
							'_wpnonce'         => $query_args_view_data['_wpnonce'],
						)
					);

				$actions['delete'] = '<a class="aadvana-transient-delete" href="' . $delete_url . ' "onclick="return confirm(\'' . \esc_html__( 'You sure you want to delete this record?', '0-day-analytics' ) . '\');">' . \esc_html__( 'Delete', '0-day-analytics' ) . '</a>';

				$row_value = \esc_html( $item[ $column_name ] ) . $this->row_actions( $actions );

			} else {
				$row_value = \esc_html( $item[ $column_name ] );
			}

			return $row_value;
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
		 * @since 2.1.0
		 */
		protected function column_cb( $item ) {
			return sprintf(
				'<label class="screen-reader-text" for="' . self::$table::get_name() . '_' . $item[ self::$table::get_real_id_name() ] . '">' . sprintf(
					// translators: The column name.
					__( 'Select %s' ),
					self::$table::get_real_id_name()
				) . '</label>'
				. '<input type="checkbox" name="advan_' . self::$table::get_name() . '[]" id="' . self::$table::get_name() . '_' . $item[ self::$table::get_real_id_name() ] . '" value="' . $item[ self::$table::get_real_id_name() ] . '" />'
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
				'delete' => __( 'Delete Records', '0-day-analytics' ),
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

			if ( is_user_logged_in() && current_user_can( 'manage_options' ) ) {

				// check for individual row actions.
				$the_table_action = $this->current_action();

				if ( 'view_data' === $the_table_action ) {

					if ( ! isset( $_REQUEST['_wpnonce'] ) ) {
						self::graceful_exit();
					}
					$nonce = \sanitize_text_field( \wp_unslash( $_REQUEST['_wpnonce'] ) );
					// verify the nonce.
					if ( ! \wp_verify_nonce( $nonce, 'view_data_nonce' ) ) {
						$this->invalid_nonce_redirect();
					} elseif ( isset( $_REQUEST[ self::$table::get_name() . '_id' ] ) ) {
						$this->page_view_data( absint( $_REQUEST[ self::$table::get_name() . '_id' ] ) );
						self::graceful_exit();
					}
				}

				if ( 'add_data' === $the_table_action ) {

					if ( ! isset( $_REQUEST['_wpnonce'] ) ) {
						self::graceful_exit();
					}
					$nonce = \sanitize_text_field( \wp_unslash( $_REQUEST['_wpnonce'] ) );

					// verify the nonce.
					if ( ! \wp_verify_nonce( $nonce, 'add_' . self::$table::get_name() . '_nonce' ) ) {
						$this->invalid_nonce_redirect();
					} else {
						$this->page_add_data( absint( $_REQUEST[ self::$table::get_name() . '_id' ] ) );
						self::graceful_exit();
					}
				}

				// check for table bulk actions.
				if ( ( isset( $_REQUEST['action'] ) && 'delete' === $_REQUEST['action'] ) || ( isset( $_REQUEST['action2'] ) && 'delete' === $_REQUEST['action2'] ) ) {

					if ( ! isset( $_REQUEST['_wpnonce'] ) ) {
						self::graceful_exit();
					}
					$nonce = \sanitize_text_field( \wp_unslash( $_REQUEST['_wpnonce'] ) );
					// verify the nonce.
					/**
					 * Note: the nonce field is set by the parent class
					 * wp_nonce_field( 'bulk-' . $this->_args['plural'] );
					 */
					if ( ! wp_verify_nonce( $nonce, 'bulk-' . $this->_args['plural'] ) ) {
						$this->invalid_nonce_redirect();
					} elseif ( isset( $_REQUEST[ 'advan_' . self::$table::get_name() ] ) ) {
						foreach ( (array) $_REQUEST[ 'advan_' . self::$table::get_name() ] as $id ) {
							self::$table::delete_by_id( (int) $id );
						}
					}

					$redirect =
						\remove_query_arg(
							array( 'delete', '_wpnonce', 'advan_' . self::$table::get_name() ),
							\add_query_arg(
								array(
									self::SEARCH_INPUT => self::escaped_search_input(),
									'paged'            => $_REQUEST['paged'] ?? 1,
									'page'             => self::TABLE_MENU_SLUG,
									'show_table'       => self::$table::get_name(),
								),
								\admin_url( 'admin.php' )
							)
						);

					?>
					<script>
						window.location.href = '<?php echo $redirect; ?>';
					</script>
					<?php
					exit;
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
			\wp_die(
				'Invalid Nonce',
				'Error',
				array(
					'response'  => 403,
					'back_link' => esc_url(
						\network_admin_url()
					),
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
				
				<select id="table_filter_<?php echo \esc_attr( $which ); ?>" class="table_filter" name="table_filter_<?php echo \esc_attr( $which ); ?>" class="advan-filter-table" style="font-family: dashicons;">
					<?php
					foreach ( Common_Table::get_tables() as $table ) {
						$selected = '';
						if ( self::$table::get_name() === $table ) {
							$selected = ' selected="selected"';
						}
						$core_table = '';
						if ( in_array( $table, Common_Table::get_wp_core_tables(), true ) ) {
							$core_table = ' ';
						}
						?>
						<option <?php echo $selected; ?> value="<?php echo \esc_attr( $table ); ?>" style="font-family: dashicons;"><?php echo $core_table . \esc_html( $table );  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></option>
						<?php
					}
					?>
					
				</select>
				
			</div>

			<?php
			// if ( 'top' === $which ) {
			global $wpdb;
			?>
						<script>
							jQuery('form .table_filter').on('change', function(e) {
								jQuery('form .table_filter').val(jQuery(this).val());
								jQuery( this ).closest( 'form' ).attr( 'action', '<?php echo \esc_url( \admin_url( 'admin-post.php' ) ); ?>').append('<input type="hidden" name="action" value="<?php echo \esc_attr( self::SWITCH_ACTION ); ?>">').append('<?php \wp_nonce_field( self::SWITCH_ACTION, self::SWITCH_ACTION . 'nonce' ); ?>').submit();
							});
						</script>
						<?php
						if ( 'top' === $which ) {
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
					#wpwrap {
						overflow-x: hidden !important;
					}
					.wp-list-table {
						white-space: nowrap;
						display: block;
						overflow-x: auto;
					}
					/* .wp-list-table {
						display: block;
						overflow-x: auto;
						white-space: nowrap;
					}
					.wp-list-table tbody {
						display: table;
						width: 100%;
					}
					.wp-list-table thead {
						position: sticky;
						z-index: 2;
						top: 0;
					} */

				</style>
						<?php } ?>
				<div class="flex flex-row grow-0 p-2 w-full border-0 border-t border-solid justify-between">
					<div class=""> <?php \esc_html_e( 'Size: ', '0-day-analytics' ); ?> <?php echo \esc_attr( File_Helper::show_size( Common_Table::get_table_size() ) ); ?>

					<?php
					$table_info = Common_Table::get_table_status();
					if ( ! empty( $table_info ) && isset( $table_info[0] ) ) {

						if ( isset( $table_info[0]['Engine'] ) ) {
							?>
							| <b><?php \esc_html_e( 'Engine: ', '0-day-analytics' ); ?></b> <span class="italic"><?php echo \esc_attr( $table_info[0]['Engine'] ); ?></span>
							<?php
						}

						if ( isset( $table_info[0]['Auto_increment'] ) ) {
							?>
							| <b><?php \esc_html_e( 'Auto increment: ', '0-day-analytics' ); ?></b> <span class="italic"><?php echo \esc_attr( $table_info[0]['Auto_increment'] ); ?></span>
							<?php
						}

						if ( isset( $table_info[0]['Collation'] ) ) {
							?>
							| <b><?php \esc_html_e( 'Collation: ', '0-day-analytics' ); ?></b> <span class="italic"><?php echo \esc_attr( $table_info[0]['Collation'] ); ?></span>
							<?php
						}

						if ( isset( $table_info[0]['Create_time'] ) ) {
							?>
							| <b><?php \esc_html_e( 'Create time : ', '0-day-analytics' ); ?></b> <span class="italic"><?php echo \esc_attr( $table_info[0]['Create_time'] ); ?></span>
							<?php
						}

						if ( isset( $table_info[0]['Update_time'] ) ) {
							?>
							| <b><?php \esc_html_e( 'Update time : ', '0-day-analytics' ); ?></b> <span class="italic"><?php echo \esc_attr( $table_info[0]['Update_time'] ); ?></span>
							<?php
						}
					}
					?>
					</div>
					<div>
						<b><?php \esc_html_e( 'Schema: ', '0-day-analytics' ); ?></b> <span class="italic"><?php echo \esc_attr( $wpdb->dbname ); ?></span> | <b><?php \esc_html_e( 'Tables: ', '0-day-analytics' ); ?></b><span class="italic"><?php echo \esc_attr( count( Common_Table::get_tables() ) ); ?></span>
					</div>
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
		 * Returns translatet text for per page option
		 *
		 * @return string
		 *
		 * @since 2.3.0
		 */
		private static function get_screen_per_page_title(): string {
			return __( 'Number of rows to show', '0-day-analytics' );
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
