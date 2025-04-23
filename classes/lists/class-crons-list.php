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
use ADVAN\Helpers\File_Helper;
use ADVAN\Controllers\Error_Log;
use ADVAN\Helpers\Log_Line_Parser;
use ADVAN\Helpers\Plugin_Theme_Helper;
use ADVAN\Controllers\Reverse_Line_Reader;
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

		public const PAGE_SLUG = 'toplevel_page_advan_crons';

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
					'screen'   => $this->get_wp_screen(),
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
				'cb'         => '<input type="checkbox" />', // to display the checkbox.
				'hook'       => __( 'Hook', '0-day-analytics' ),
				'schedule'   => __( 'Time', '0-day-analytics' ),
				'recurrence' => __( 'Interval', '0-day-analytics' ),
				'args'       => __( 'Args', '0-day-analytics' ),
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

			$this->items = self::get_cron_items( true );

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
		public static function get_cron_items( bool $write_temp = true, $items = false ): array {

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
				case 'hook':
					$query_args_view_data['hash']     = $item['hash'];
					$query_args_view_data['_wpnonce'] = \wp_create_nonce( 'bulk-custom-delete' );

					$actions['delete'] = '<a class="aadvana-cron-delete" href="#" data-nonce="' . $query_args_view_data['_wpnonce'] . '" data-hash="' . $query_args_view_data['hash'] . '">' . \esc_html__( 'Delete', '0-day-analytics' ) . '</a>';


					$actions['run'] = '<a class="aadvana-cron-run" href="#" data-nonce="' . $query_args_view_data['_wpnonce'] . '" data-hash="' . $query_args_view_data['hash'] . '">' . \esc_html__( 'Run', '0-day-analytics' ) . '</a>';

					return '<span>' . $item['hook'] . '</span>' . self::single_row_actions( $actions );
				case 'recurrence':
					return ( ! empty( $item['recurrence'] ) ? $item['recurrence'] : __( 'once', '0-day-analytics' ) );
				case 'args':
					return ( ! empty( $item['args'] ) ? \print_r( $item['args'], true ) : __( 'NO', '0-day-analytics' ) );
				case 'schedule':
					$date_time_format = \get_option( 'date_format' ) . ' ' . \get_option( 'time_format' );
					$time             = \wp_date( $date_time_format, $item['schedule'] );

					return $time;
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
				$option = 'advanced_analytics_crons_list_per_page';
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
							}, 'json');

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
									
								} else {
									jQuery(that).closest("tr").after('<tr><td style="overflow:hidden;" colspan="'+(jQuery(that).closest("tr").find("td").length+1)+'"><div class="error" style="background:#fff; color:#000;"> ' + response['data'] + '</div></td></tr>');
								}
							}, 'json');

						});
					});
				</script>
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
						echo '#the-list td { color: #333 !important; }';
						echo '#the-list tr { background: #fff;}';

					}
					?>
				</style>
				<pre id="debug-log"><?php Reverse_Line_Reader::read_temp_file(); ?></pre>
					<?php
			}
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


	}
}
