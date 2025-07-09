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
use ADVAN\Lists\Traits\List_Trait;
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

		use List_Trait;

		public const SCREEN_OPTIONS_SLUG = 'advanced_analytics_transients_list';

		public const PAGE_SLUG = 'wp-control_page_advan_transients';

		public const UPDATE_ACTION = 'advan_transients_update';

		public const NEW_ACTION = 'advan_transients_new';

		public const NONCE_NAME = 'advana_transients_manager';

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
		protected static $rows_per_page = 10;

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
			$per_page    = ! empty( $_GET['per_page'] ) ? absint( $_GET['per_page'] ) : self::get_screen_option_per_page();
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

			$hidden = \get_user_option( 'manage' . WP_Helper::get_wp_screen()->id . 'columnshidden', false );
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
				(array) \get_user_option( 'manage' . Settings::get_main_menu_page_hook() . 'columnshidden', false )
			);
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

			$this->items = Transients_Helper::get_transient_items( $args );

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
		public static function parse_args( $args = array() ) {

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
			$count = Transients_Helper::get_transient_items(
				array(
					'count'  => true,
					'search' => $search,
				)
			);

			// Return int.
			return absint( $count );
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

					$edit_url = \remove_query_arg(
						array( 'updated', 'deleted' ),
						\add_query_arg(
							array(
								'action'           => 'edit_transient',
								'trans_id'         => $item['id'],
								self::SEARCH_INPUT => self::escaped_search_input(),
								'_wpnonce'         => $query_args_view_data['_wpnonce'],
							)
						)
					);

					$actions['edit'] = '<a class="aadvana-transient-run" href="' . $edit_url . '">' . \esc_html__( 'Edit', '0-day-analytics' ) . '</a>';

					$core_trans = '';

					if ( in_array( $item['transient_name'], Transients_Helper::WP_CORE_TRANSIENTS ) ) {
						$core_trans = '<span class="dashicons dashicons-wordpress" aria-hidden="true"></span> ';
					} else {
						foreach ( Transients_Helper::WP_CORE_TRANSIENTS as $trans_name ) {
							if ( \str_starts_with( $item['transient_name'], $trans_name ) ) {
								$core_trans = '<span class="dashicons dashicons-wordpress" aria-hidden="true"></span> ';

								break;
							}
						}
					}

					// translators: %s is the transient.
					return '<span>' . $core_trans . '<b title="' . sprintf( \esc_attr__( 'Option ID: %d', '0-day-analytics' ), (int) $item['id'] ) . '">' . $item['transient_name'] . '</b></span>' . self::single_row_actions( $actions );
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
						: __( 'Column "', '0-day-analytics' ) . \esc_html( $column_name ) . __( '" not found', '0-day-analytics' );
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
					self::graceful_exit();
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
			$screen_options = array( 'per_page' => __( 'Number of transients to show', '0-day-analytics' ) );

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
		 * Generates content for a single row of the table.
		 *
		 * @param array $item - The current item.
		 *
		 * @since 1.7.0
		 */
		public function single_row( $item ) {
			if ( 0 === $item['schedule'] ) {
				$classes = ' persistent';
			} else {
				$late = Crons_Helper::is_late( $item );

				if ( $late ) {
					$classes = ' late';
				} else {
					$classes = ' on-time';
				}
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
				\wp_nonce_field( 'bulk-' . $this->_args['plural'] );

				?>
				<script>
					jQuery(document).ready(function(){
						jQuery('.aadvana-transient-delete').on('click', function(e){

							e.preventDefault();
							if ( confirm( '<?php echo \esc_html__( 'You sure you want to delete this transient?', '0-day-analytics' ); ?>' ) ) {
								let that = this;

								jQuery(that).css({
									"pointer-events": "none",
									"cursor": "default"
								});

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
								}, 'json').always(function() {

									jQuery(that).css({
										"pointer-events": "",
										"cursor": ""
									})
								});
							}

						});
					});
				</script>
				<style>
					.wp-control_page_advan_transients .generated-transients .persistent th:nth-child(1) {
						border-left: 7px solid #d2ab0e !important;
					}
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
	}
}
