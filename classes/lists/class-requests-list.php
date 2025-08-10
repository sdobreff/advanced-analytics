<?php
/**
 * Responsible for the requests view
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
use ADVAN\Lists\Traits\List_Trait;
use ADVAN\ControllersApi\Endpoints;
use ADVAN\Lists\Views\Requests_View;
use ADVAN\Entities\Requests_Log_Entity;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/template.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table-compat.php';
	require_once ABSPATH . 'wp-admin/includes/list-table.php';
}

/**
 * Base list table class
 */
if ( ! class_exists( '\ADVAN\Lists\Requests_List' ) ) {

	/**
	 * Responsible for rendering base table for manipulation
	 *
	 * @since 2.1.0
	 */
	class Requests_List extends \WP_List_Table {

		use List_Trait;

		public const PAGE_SLUG = 'wp-control_page_advan_requests';

		public const SWITCH_ACTION = 'switch_advan_table';

		public const SCREEN_OPTIONS_SLUG = 'advanced_analytics_requests_list';

		public const SEARCH_INPUT = 's';

		public const REQUESTS_MENU_SLUG = 'advan_requests';

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
		 * Holds the prepared options for speeding the proccess
		 *
		 * @var array
		 *
		 * @since 2.1.0
		 */
		protected static $admin_columns = array();

		/**
		 * Default class constructor
		 *
		 * @param string $table_name - The name of the table to use for the listing.
		 *
		 * @since 2.1.0
		 */
		public function __construct( string $table_name = '' ) {

			$class = Common_Table::class;

			Common_Table::init( Requests_Log_Entity::get_table_name() );
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
		 * Inits class hooks. That is called every time - not in some specific environment set.
		 *
		 * @return void
		 *
		 * @since 2.8.2
		 */
		public static function init() {
			\add_filter( 'advan_cron_hooks', array( __CLASS__, 'add_cron_job' ) );
		}

		/**
		 * Adds a cron job for truncating the records in the requests table
		 *
		 * @param array $crons - The array with all the crons associated with the plugin.
		 *
		 * @return array
		 *
		 * @since 2.8.2
		 */
		public static function add_cron_job( $crons ) {
			if ( -1 !== (int) Settings::get_option( 'advana_rest_requests_clear' ) ) {
				$crons[ ADVAN_PREFIX . 'request_table_clear' ] = array(
					'time' => Settings::get_option( 'advana_rest_requests_clear' ),
					'hook' => array( __CLASS__, 'truncate_requests_table' ),
					'args' => array(),
				);
			}

			return $crons;
		}

		/**
		 * Truncates the requests table from CRON job
		 *
		 * @return void
		 *
		 * @since 2.8.2
		 */
		public static function truncate_requests_table() {
			Common_Table::truncate_table( null, Requests_Log_Entity::get_table_name() );
		}

		/**
		 * Adds the module to the main plugin menu
		 *
		 * @return void
		 *
		 * @since 2.8.1
		 */
		public static function menu_add() {
			$requests_hook = \add_submenu_page(
				Logs_List::MENU_SLUG,
				\esc_html__( 'WP Control', '0-day-analytics' ),
				\esc_html__( 'Requests viewer', '0-day-analytics' ),
				( ( Settings::get_option( 'menu_admins_only' ) ) ? 'manage_options' : 'read' ), // No capability requirement.
				self::REQUESTS_MENU_SLUG,
				array( Requests_View::class, 'analytics_requests_page' ),
				3
			);

			self::add_screen_options( $requests_hook );

			\add_filter( 'manage_' . $requests_hook . '_columns', array( self::class, 'manage_columns' ) );

			\add_action( 'load-' . $requests_hook, array( Settings::class, 'aadvana_common_help' ) );
		}

		/**
		 * Returns the table name
		 *
		 * @return string
		 *
		 * @since 2.1.0
		 */
		public static function get_table_name(): string {
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

			$columns = self::manage_columns( array() );
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
			return self::manage_columns( array() );
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
			$first6_columns = array_keys( Requests_Log_Entity::get_column_names_admin() );

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
				$search_sql = 'AND (id LIKE "%' . $wpdb->esc_like( $search_string ) . '%"';
				foreach ( array_keys( Requests_Log_Entity::get_column_names_admin() ) as $value ) {
					$search_sql .= ' OR ' . $value . ' LIKE "%' . esc_sql( $wpdb->esc_like( $search_string ) ) . '%" ';
				}
				$search_sql .= ') ';
			}

			$wpdb_table = $this->get_table_name();

			$orderby = ( isset( $_GET['orderby'] ) && '' != $_GET['orderby'] ) ? \esc_sql( \wp_unslash( $_GET['orderby'] ) ) : 'id';
			$order   = ( isset( $_GET['order'] ) && '' != $_GET['orderby'] ) ? \esc_sql( \wp_unslash( $_GET['order'] ) ) : 'DESC';
			$query   = 'SELECT
				' . implode( ', ', \array_keys( Requests_Log_Entity::get_fields() ) ) . '
			  FROM ' . $wpdb_table . '  WHERE 1=1 ' . $search_sql . ' ORDER BY ' . $orderby . ' ' . $order;

			$query .= $wpdb->prepare( ' LIMIT %d OFFSET %d;', $per_page, $offset );

			// query output_type will be an associative array with ARRAY_A.
			$query_results = Requests_Log_Entity::get_results( $query );

			$this->count = $wpdb->get_var( 'SELECT COUNT(id) FROM ' . $wpdb_table . '  WHERE 1=1 ' . $search_sql );

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
		 *
		 * @since 2.7.0
		 */
		public function column_default( $item, $column_name ) {

			switch ( $column_name ) {

				case 'type':
					return '<span id="advana-request-type-' . $item['id'] . '" class="dark-badge badge">' . \esc_html( $item[ $column_name ] ) . '</span>';

				case 'url':
				case 'page_url':
					$value = \str_replace( array( 'http://', 'https://' ), '', $item[ $column_name ] );
					$value = \str_replace( WP_Helper::get_blog_domain(), '', $value );

					$title = \esc_html( $value );

					$value = substr( $value, 0, 70 );

					// Escape & wrap in <code> tag.
					$value = '<code id="advana-request-' . $column_name . '-' . $item['id'] . '" title="' . $title . '">' . \esc_html( $value ) . '</code>';
					return $value;

				case 'user_id':
					if ( isset( $item[ $column_name ] ) && ! empty( $item[ $column_name ] ) && 0 !== $item[ $column_name ] ) {
						$user = \get_user_by( 'id', $item[ $column_name ] );
						if ( $user ) {
							return '<a href="' . \esc_url( \get_edit_user_link( $user->ID ) ) . '">' . \esc_html( $user->display_name ) . '</a> (' . \esc_html( $user->user_email ) . ')';
						} else {
							return \esc_html__( 'Unknown or deleted user', '0-day-analytics' );
						}
					} else {
						return \esc_html__( 'WP System or Anonymous user', '0-day-analytics' );
					}

				case 'domain':
					// Escape & wrap in <code> tag.
					return '<code id="advana-request-' . $column_name . '-' . $item['id'] . '">' . \esc_html( $item[ $column_name ] ) . '</code>';

				case 'runtime':
					// Escape & wrap in <code> tag.
					return '<code id="advana-request-' . $column_name . '-' . $item['id'] . '">' . \esc_html( \number_format( (float) $item[ $column_name ], 3 ) ) . 's</code>';

				case 'request_status':
					// Escape & wrap in <code> tag.
					$extra_info = '';
					$style      = 'style="color: #00ff00 !important;"';
					if ( 'error' === $item[ $column_name ] ) {
						$extra_info = ' <span class="status-control-error"><span class="dashicons dashicons-warning" aria-hidden="true"></span> ' . \esc_html( $item['response'] ) . '</span>';
						$style      = 'style="color:rgb(235, 131, 55) !important;"';
					}
					return '<code class="badge dark-badge" id="advana-request-' . $column_name . '-' . $item['id'] . '" ' . $style . '>' . \esc_html( $item[ $column_name ] ) . '</code></br>' . $extra_info . self::format_trace( $item['trace'] );

				case 'request_group':
				case 'request_source':
					// Escape & wrap in <code> tag.
					return '<code>' . \esc_html( $item[ $column_name ] ) . '</code>';
				case 'date_added':
					$query_args_view_data             = array();
					$query_args_view_data['_wpnonce'] = \wp_create_nonce( 'bulk-' . $this->_args['plural'] );
					$delete_url                       =
					\add_query_arg(
						array(
							'action'           => 'delete',
							'advan_' . self::$table::get_name() => $item['id'],
							self::SEARCH_INPUT => self::escaped_search_input(),
							'_wpnonce'         => $query_args_view_data['_wpnonce'],
						)
					);

					$actions['delete'] = '<a class="aadvana-transient-delete" href="' . $delete_url . ' "onclick="return confirm(\'' . \esc_html__( 'You sure you want to delete this record?', '0-day-analytics' ) . '\');">' . \esc_html__( 'Delete', '0-day-analytics' ) . '</a>';

					$actions['details'] = '<a href="#" class="aadvan-request-show-details" data-details-id="' . $item['id'] . '">' . \esc_html__( 'Details' ) . '</a>';

					$data  = '<div id="advana-request-details-' . $item['id'] . '" style="display: none;">';
					$data .= '<pre style="overflow-y: hidden;">' . \esc_html( var_export( self::get_formatted_string( $item['request_args'] ), true ) ) . '</pre>';
					$data .= '</div>';
					$data .= '<div id="advana-response-details-' . $item['id'] . '" style="display: none;">';
					$data .= '<pre style="overflow-y: hidden;">' . \esc_html( var_export( self::get_formatted_string( $item['response'] ), true ) ) . '</pre>';
					$data .= '</div>';

					$time_format = 'g:i a';

					$item['date_added'] = (int) $item['date_added'];

					$event_datetime_utc = \gmdate( 'Y-m-d H:i:s', $item['date_added'] );

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
						\esc_attr( gmdate( 'c', $item['date_added'] ) ),
						\esc_html( $date )
					);

					$until = $item['date_added'] - time();

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
						) . $this->row_actions( $actions ) . $data;
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
					) . $this->row_actions( $actions ) . $data;
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
		 * @since 2.1.0
		 */
		protected function column_cb( $item ) {
			return sprintf(
				'<label class="screen-reader-text" for="' . self::$table::get_name() . '_' . $item['id'] . '">' . sprintf(
				// translators: The column name.
					__( 'Select %s' ),
					'id'
				) . '</label>'
				. '<input type="checkbox" name="advan_' . self::$table::get_name() . '[]" id="' . self::$table::get_name() . '_' . $item['id'] . '" value="' . $item['id'] . '" />'
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

			if ( \is_user_logged_in() && \current_user_can( 'manage_options' ) ) {

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
						array( 'delete', '_wpnonce', 'advan_' . self::$table::get_name(), 'action' ),
						\add_query_arg(
							array(
								self::SEARCH_INPUT => self::escaped_search_input(),
								'paged'            => $_REQUEST['paged'] ?? 1,
								'page'             => self::REQUESTS_MENU_SLUG,
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
					.checkbox-wrapper-2 label{
						margin-right: 7px !important;
						cursor: pointer !important;
					}

					.checkbox-wrapper-2 .ikxBAC {
						appearance: none;
						background-color: #dfe1e4;
						border-radius: 72px;
						border-style: none;
						flex-shrink: 0;
						height: 20px;
						margin: 0;
						position: relative;
						width: 30px;
						cursor: pointer !important;
						border: 1px solid #cec6c6;
					}

					.checkbox-wrapper-2 .ikxBAC::before {
						bottom: -6px !important;
						content: "" !important;
						left: -6px !important;
						position: absolute !important;
						right: -6px !important;
						top: -6px !important;
					}

					.checkbox-wrapper-2 .ikxBAC,
					.checkbox-wrapper-2 .ikxBAC::after {
						transition: all 100ms ease-out;
					}

					.checkbox-wrapper-2 .ikxBAC::after {
						background-color: #e68a6e;
						border-radius: 50%;
						content: "";
						height: 14px;
						left: 3px;
						position: absolute;
						top: 3px;
						width: 14px;
					}

					.checkbox-wrapper-2 input[type=checkbox] {
						cursor: default;
					}

					.checkbox-wrapper-2 .ikxBAC:hover {
						background-color: #c9cbcd;
						transition-duration: 0s;
					}

					.checkbox-wrapper-2 .ikxBAC:checked {
						background-color: #d3f9d6;
					}

					html.aadvana-darkskin .checkbox-wrapper-2 .ikxBAC:checked {
						background-color:rgb(27, 27, 28) !important;
					}

					.checkbox-wrapper-2 .ikxBAC:checked::after {
						background-color: #17c622;
						left: 13px;
					}

					.checkbox-wrapper-2 :focus:not(.focus-visible) {
						outline: 0;
					}

					.checkbox-wrapper-2 .ikxBAC:checked:hover {
						background-color: #dfe1e4;
					}

					.tablenav {
						height: auto !important;
					}
				</style>
				<div class="flex flex-row grow-0 p-2 w-full border-0 border-t border-solid justify-between">
					<div class="checkbox-wrapper-2">
					
						<input type="checkbox"  class="sc-gJwTLC ikxBAC requests-monitoring-filter" name="disable_monitoring[]" value="http" id="advana_http_requests_disable" <?php \checked( Settings::get_option( 'advana_http_requests_disable' ), true ); ?>>
							
						<label for="advana_http_requests_disable" class="badge dark-badge">
						<?php \esc_html_e( 'Disable HTTP monitoring', '0-day-analytics' ); ?>
						</label>
					
						<input type="checkbox"  class="sc-gJwTLC ikxBAC requests-monitoring-filter" name="disable_monitoring[]" value="rest" id="advana_rest_requests_disable" <?php \checked( Settings::get_option( 'advana_rest_requests_disable' ), true ); ?>>
							
						<label for="advana_rest_requests_disable" class="badge dark-badge">
						<?php \esc_html_e( 'Disable REST API monitoring', '0-day-analytics' ); ?>
						</label>
						<script>
							let requests_disable = document.getElementsByClassName("requests-monitoring-filter");

							let len = requests_disable.length;

							// call updateCost() function to onclick event on every checkbox
							for (var i = 0; i < len; i++) {
								if (requests_disable[i].type === 'checkbox') {
									requests_disable[i].onclick = setMonitoring;
								}
							}

							async function setMonitoring(e) {

								let monitoringName = e.target.value;
								let monitoringStatus = e.target.checked;
								let attResp;

								try {
									attResp = await wp.apiFetch({
										path: '/<?php echo Endpoints::ENDPOINT_ROOT_NAME; ?>/v1/requests/' + monitoringName + '/' + ( monitoringStatus ? 'enable' : 'disable' ),
										method: 'GET',
										cache: 'no-cache'
									});

									if (attResp.success) {
										
										location.reload();
									} else if (attResp.message) {
										jQuery('#wp-admin-bar-aadvan-menu .ab-item').html('<b><i>' + attResp.message + '</i></b>');
									}

								} catch (error) {
									throw error;
								}
							}

						</script>
					</div>
				</div>

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
		 * Returns translated text for per page option
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

			if ( empty( self::$admin_columns ) ) {

				$admin_columns = Requests_Log_Entity::get_column_names_admin();

				$screen_options = $admin_columns;

				$table_columns = array(
					'cb' => '<input type="checkbox" />', // to display the checkbox.
				);

				self::$admin_columns = \array_merge( $table_columns, $screen_options, $columns );
			}

			return self::$admin_columns;
		}

		/**
		 * Formats the trace from the request log.
		 *
		 * @param string $trace - JSON encoded trace.
		 *
		 * @return string
		 *
		 * @since 2.7.0
		 */
		public static function format_trace( string $trace ): string {

			if ( empty( $trace ) ) {
				return '';
			}

			$trace = \json_decode( $trace, true );

			$defaults = array(
				'line'     => '',
				'file'     => '',
				'class'    => '',
				'function' => '',
			);

			$out = '';

			if ( \is_array( $trace ) && ! empty( $trace ) ) {

				$query_array = array(
					'_wpnonce' => \wp_create_nonce( 'source-view' ),
					'action'   => 'log_source_view',
				);

				$counter = count( $trace ) - 6;
				for ( $i = 1; $i < $counter; $i++ ) {
					$sf    = (object) shortcode_atts( $defaults, $trace[ $i + 6 ] );
					$index = $i - 1;
					$file  = $sf->file;

					$caller = '';
					if ( ! empty( $sf->class ) && ! empty( $sf->function ) ) {
						$caller = $sf->class . '::' . $sf->function . '()';
					} elseif ( ! empty( $sf->function ) ) {
						$caller = $sf->function . '()';
					}

					$source_link = '';

					if ( isset( $file ) && ! empty( $file ) ) {
						$query_array['error_file'] = $file;
						$query_array['error_line'] = 1;

						if ( isset( $sf->line ) && ! empty( $sf->line ) ) {
							$query_array['error_line'] = $sf->line;
						}

						$query_array['TB_iframe'] = 'true';

						$view_url = \esc_url_raw(
							\add_query_arg( $query_array, \admin_url( 'admin-ajax.php' ) )
						);

						$title = __( 'Viewing: ', '0-day-analytics' ) . $query_array['error_file'];

						$source_link = ' <a href="' . $view_url . '" title="' . $title . '" class="thickbox view-source">' . $file . '(' . $sf->line . ')</a>';

					}

					$out .= "#$index {$source_link}: $caller" . '<br>';
				}
			}

			return $out;
		}

		/**
		 * Checks string format and decodes it if it is a valid JSON.
		 *
		 * @param string $string - The string to check and decode.
		 *
		 * @return string
		 *
		 * @since 2.7.0
		 */
		public static function get_formatted_string( $string ) {
			$encoded = json_decode( $string, true, 512, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
			if ( json_last_error() === JSON_ERROR_NONE ) {

				foreach ( $encoded as $key => $value ) {
					if ( ! empty( $value ) && is_string( $value ) && ! is_numeric( $value ) ) {
						$encoded[ $key ] = self::get_formatted_string( $value );
						// if ( is_array( $encoded[ $key ] ) ) {
						// $encoded[ $key ] = array_map( 'htmlspecialchars', $encoded[ $key ] );
						// }
					}
				}

				return $encoded;
			} else {
				return $string;
			}
		}

		/**
		 * Sets the request type status.
		 *
		 * @param \WP_REST_Request $request - The request object.
		 *
		 * @return \WP_REST_Response|\WP_Error
		 *
		 * @since 2.8.0
		 */
		public static function set_request_status( \WP_REST_Request $request ) {
			$request_type = $request->get_param( 'request_type' );
			$status       = $request->get_param( 'status' );

			if ( ! in_array( $request_type, array( 'http', 'rest' ), true ) ) {
				return new \WP_Error(
					'invalid_request_type',
					__( 'Invalid request type name.', '0-day-analytics' ),
					array( 'status' => 400 )
				);
			}

			$request_type = 'advana_' . $request_type . '_requests_disable';

			$settings = Settings::get_current_options();

			if ( 'enable' === $status ) {
				$settings[ $request_type ] = true;
			} elseif ( 'disable' === $status ) {
				$settings[ $request_type ] = false;
			} else {
				return new \WP_Error(
					'invalid_status',
					__( 'Invalid status.', '0-day-analytics' ),
					array( 'status' => 400 )
				);
			}

			Settings::store_options( $settings );
			Settings::set_current_options( $settings );

			return rest_ensure_response(
				array(
					'success' => true,
				)
			);
		}
	}
}
