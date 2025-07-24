<?php
/**
 * Responsible for the entities functionalities
 *
 * @package    advan
 * @subpackage entities
 * @since      1.1
 * @license    https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 * @link       https://wordpress.org/plugins/0-day-analytics/
 */

declare(strict_types=1);

namespace ADVAN\Entities;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Sites table class
 */
if ( ! class_exists( '\ADVAN\Entities\Common_Table' ) ) {

	/**
	 * Base class for all the entities
	 *
	 * @since 2.1.0
	 */
	class Common_Table {

		/**
		 * All MySQL integer types.
		 *
		 * @property const array Holds the MySql integer data types
		 */
		public const INT_TYPES = array(
			'TINYINT'   => 'TINYINT',
			'SMALLINT'  => 'SMALLINT',
			'MEDIUMINT' => 'MEDIUMINT',
			'INT'       => 'INT',
			'BIGINT'    => 'BIGINT',
			'BIT'       => 'BIT',
		);

		/**
		 * All MySQL float types.
		 *
		 * @property const array Holds the MySql float data types
		 */
		public const FLOAT_TYPES = array(
			'DECIMAL' => 'DECIMAL',
			'FLOAT'   => 'FLOAT',
			'DOUBLE'  => 'DOUBLE',
		);

		/**
		 * Name of the table ID
		 *
		 * @var string
		 *
		 * @since 2.1.0
		 */
		private static $id = '';

		/**
		 * Name of the real table ID
		 *
		 * @var string
		 *
		 * @since 2.1.0
		 */
		private static $real_id = '';

		/**
		 * The name of the table
		 *
		 * @var string
		 *
		 * @since 2.1.0
		 */
		protected static $table_name = '';

		/**
		 * Stores info for the table columns - that is extracted from the MySQL server.
		 *
		 * @var array
		 *
		 * @since 2.1.0
		 */
		protected static $columns_info = array();

		/**
		 * Class cache that holds all of the tables in schema.
		 *
		 * @var array
		 *
		 * @since 2.2.0
		 */
		private static $tables = array();

		/**
		 * Class cache that holds all core tables of WP.
		 *
		 * @var array
		 *
		 * @since 2.2.0
		 */
		private static $core_tables = array();

		/**
		 * Holds the prepared options for speeding the proccess
		 *
		 * @var array
		 *
		 * @since 2.1.0
		 */
		protected static $admin_columns = array();

		/**
		 * Class cache keeps the size of the table
		 *
		 * @var int
		 *
		 * @since 2.1.2
		 */
		private static $table_size = null;

		/**
		 * Inner class cache to store the table status
		 *
		 * @var array
		 *
		 * @since 2.4.1
		 */
		private static $table_stat = array();

		/**
		 * Inits the class and sets the vars
		 *
		 * @param string $table_name - The name of the table to use.
		 *
		 * @return void
		 *
		 * @since 2.1.0
		 */
		public static function init( string $table_name ) {
			self::$table_name = $table_name;
		}

		/**
		 * Returns the name of the table
		 *
		 * @return string
		 *
		 * @since 2.1.0
		 */
		public static function get_name(): string {

			return static::$table_name;
		}

		/**
		 * Checks if give table exists
		 *
		 * @param string $table_name - The table to check for. If empty checks for the current table.
		 *
		 * @return boolean
		 *
		 * @since 2.1.0
		 */
		public static function check_table_exists( string $table_name = '', $connection = null ): bool {
			if ( null !== $connection ) {
				if ( $connection instanceof \wpdb ) {
					$_wpdb = $connection;
				}
			} else {
				global $wpdb;

				$_wpdb = $wpdb;
			}

			if ( '' === $table_name ) {
				$table_name = static::get_name();
			}

			foreach ( $_wpdb->get_col( 'SHOW TABLES', 0 ) as $table ) {
				if ( $table === $table_name ) {
					return true;
				}
			}
			return false;
		}

		/**
		 * Executes query.
		 *
		 * Important - query string is not checked nor validated, the calling script is responsible for that.
		 *
		 * @param string $query - The query which needs to be executed.
		 * @param \wpdb  $connection - \wpdb connection to be used for name extraction.
		 *
		 * @return array
		 *
		 * @since 2.1.0
		 */
		public static function execute_query( string $query, $connection = null ) {
			if ( null !== $connection ) {
				if ( $connection instanceof \wpdb ) {
					$_wpdb = $connection;
				}
			} else {
				global $wpdb;
				$_wpdb = $wpdb;
			}

			return $_wpdb->query( $query );
		}

		/**
		 * Calls for the create table syntax and executes the query.
		 *
		 * @return void
		 *
		 * @since 2.1.0
		 */
		public static function create_table() {
			// self::execute_query( static::get_create_table_sql() );
		}

		/**
		 * Drop the table from the DB.
		 *
		 * @param \WP_REST_Request $request - The request object.
		 * @param string           $table_name - The name of the table, if one is not provided, the default will be used.
		 * @param \wpdb            $connection - \wpdb connection to be used for name extraction.
		 *
		 * @return \WP_REST_Response|\WP_Error
		 *
		 * @since 2.1.0
		 */
		public static function drop_table( ?\WP_REST_Request $request = null, string $table_name = '', $connection = null ) {

			if ( null !== $connection ) {
				if ( $connection instanceof \wpdb ) {
					$_wpdb = $connection;
				}
			} else {
				global $wpdb;
				$_wpdb = $wpdb;
			}

			if ( null !== $request ) {
				$table_name = $request->get_param( 'table_name' );
			}

			if ( '' === $table_name ) {
				$table_name = static::get_name();
			}

			if ( ! \in_array( $table_name, self::get_wp_core_tables(), true )
			&& \in_array( $table_name, self::get_tables($_wpdb), true ) ) {

				self::execute_query( 'DROP TABLE IF EXISTS ' . $table_name, $_wpdb );
			} elseif ( null !== $request ) {
				return new \WP_Error(
					'core_table',
					__( 'Can not delete core table.', '0-day-analytics' ),
					array( 'status' => 400 )
				);
			}

			if ( null !== $request ) {
				return rest_ensure_response(
					array(
						'success' => true,
					)
				);
			}
		}

		/**
		 * Truncates the table.
		 *
		 * @param \WP_REST_Request $request - The request object.
		 * @param string           $table_name - The name of the table, if one is not provided, the default will be used.
		 * @param \wpdb            $connection - \wpdb connection to be used for name extraction.
		 *
		 * @return \WP_REST_Response|\WP_Error
		 *
		 * @since 2.4.1
		 */
		public static function truncate_table( ?\WP_REST_Request $request = null, string $table_name = '', $connection = null ) {
			if ( null !== $connection ) {
				if ( $connection instanceof \wpdb ) {
					$_wpdb = $connection;
				}
			} else {
				global $wpdb;
				$_wpdb = $wpdb;
			}

			if ( null !== $request ) {
				$table_name = $request->get_param( 'table_name' );
			}

			if ( '' === $table_name ) {
				$table_name = static::get_name();
			}

			if ( \in_array( $table_name, self::get_tables($_wpdb), true ) ) {

				self::execute_query( 'TRUNCATE TABLE ' . $table_name, $_wpdb );
			} elseif ( null !== $request ) {
				return new \WP_Error(
					'truncate_table',
					__( 'Can not truncate table.', '0-day-analytics' ),
					array( 'status' => 400 )
				);
			}

			if ( null !== $request ) {
				return rest_ensure_response(
					array(
						'success' => true,
					)
				);
			}
		}

		/**
		 * Returns the name of the id column for the table
		 *
		 * @return string
		 *
		 * @since 2.1.0
		 */
		public static function get_id_name(): string {
			return static::$id;
		}

		/**
		 * Returns the name of the real id column for the table - some times there are different ids - for internal use and for global use.
		 *
		 * @return string
		 *
		 * @since 2.1.0
		 */
		public static function get_real_id_name(): string {
			if ( empty( self::$real_id ) ) {

				global $wpdb;

				$sql = 'SHOW KEYS FROM ' . self::get_name() . " WHERE Key_name = 'PRIMARY'";

				$result = $wpdb->get_results(
					$sql,
					ARRAY_A
				);

				if ( \is_array( $result ) && ! empty( $result ) && isset( $result[0]['Column_name'] ) ) {
					static::$real_id = $result[0]['Column_name'];
				}
			}

			return static::$real_id;
		}

		/**
		 * Checks for given column existence using custom connection.
		 *
		 * @param string       $col_name - The name of the column.
		 * @param string       $col_type - Type of the column.
		 * @param string       $table_name - The name of the table.
		 * @param boolean|null $is_null - Is it null.
		 * @param mixed        $key - Is it key.
		 * @param mixed        $default - The default value of the column.
		 * @param mixed        $extra - Extra parameters.
		 *
		 * @return boolean - True if the column exists and all given parameters are the same, false otherwise.
		 *
		 * @since 2.1.0
		 */
		public static function check_column(
			string $col_name,
			string $col_type,
			string $table_name = '',
			?bool $is_null = null,
			$key = null,
			$default = null,
			$extra = null ): bool {

			global $wpdb;

			if ( '' === $table_name ) {
				$table_name = static::get_name();
			}

			$diffs   = 0;
			$results = $wpdb->get_results( "DESC $table_name" ); // phpcs:ignore

			foreach ( $results as $row ) {

				if ( $row->Field === $col_name ) { // phpcs:ignore

					// Got our column, check the params.
					if ( ( null !== $col_type ) && ( strtolower( str_replace( ' ', '', $row->Type ) ) !== strtolower( str_replace( ' ', '', $col_type ) ) ) ) { // phpcs:ignore
						++$diffs;
					}
					if ( ( null !== $is_null ) && ( $row->Null !== $is_null ) ) { // phpcs:ignore
						++$diffs;
					}
					if ( ( null !== $key ) && ( $row->Key !== $key ) ) { // phpcs:ignore
						++$diffs;
					}
					if ( ( null !== $default ) && ( $row->Default !== $default ) ) { // phpcs:ignore
						++$diffs;
					}
					if ( ( null !== $extra ) && ( $row->Extra !== $extra ) ) { // phpcs:ignore
						++$diffs;
					}

					if ( $diffs > 0 ) {
						return false;
					}

					return true;
				} // End if found our column.
			}

			return false;
		}

		/**
		 * Getter for the collected table column info
		 *
		 * @return array
		 */
		public static function get_columns_info(): array {
			global $wpdb;

			$query = $wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$wpdb->esc_like( self::get_name() )
			);

			if ( $wpdb->get_var( $query ) == self::get_name() ) {
				static::$columns_info = $wpdb->get_results(
					'DESC ' .
					self::get_name(),
					ARRAY_A
				);
			}

			return static::$columns_info;
		}

		/**
		 * Default find method
		 *
		 * @param array $data Must contains formats and data.
		 *
		 * @return int bool
		 */
		public static function find( array $data ) {
			global $wpdb;

			/**
			 * \WPDB has very powerful method called process_fields @see \WPDB::process_fields().
			 * Unfortunately this method is not accessible, because it is marked protected. The best solution at the moment is to clone the class, lower the visibility and use the method.
			 *
			 * That of course takes resources so possible solution is to add also caching to this method, so that is marked as todo below.
			 *
			 * TODO: Add caching functionality to the method.
			 */
			$wpdb_clone = new class() extends \wpdb {

				public function __construct() {
					$dbuser     = defined( 'DB_USER' ) ? DB_USER : '';
					$dbpassword = defined( 'DB_PASSWORD' ) ? DB_PASSWORD : '';
					$dbname     = defined( 'DB_NAME' ) ? DB_NAME : '';
					$dbhost     = defined( 'DB_HOST' ) ? DB_HOST : '';

					parent::__construct( $dbuser, $dbpassword, $dbname, $dbhost );
				}

				public function process_fields( $name, $data, $formats ) {
					return parent::process_fields( $name, $data, $formats );
				}

			};

			$where_clause = $wpdb_clone->process_fields(
				self::get_name(),
				$data['data'],
				$data['formats']
			);

			$where_data = self::prepare_full_where( $where_clause );

			$conditions = $where_data['conditions'];
			$values     = $where_data['values'];

			$wpdb->check_current_query = false;

			$result = $wpdb->get_results(
				$wpdb->prepare(
			// phpcs:disable
			'SELECT * from `' . self::get_name() . '` WHERE ' . $conditions,
			$values
			// phpcs:enable
				),
				ARRAY_A
			);

			self::check_error();

			return $result;
		}

		/**
		 * Checks for errors and throws exception if any
		 *
		 * @throws \Exception Last wpdb error.
		 *
		 * @return void
		 */
		protected static function check_error() {
			global $wpdb;
			if ( '' !== $wpdb->last_error ) {
				throw new \Exception( 'Error with query - check the logs' );
			}
		}

		/**
		 * Prepares full where clause
		 *
		 * @param array        $where_clause - Array prepared based on fields and values from the WP_DB.
		 * @param string       $condition - The where clause condition - default AND.
		 * @param string|null  $criteria - The criteria to check for.
		 * @param boolean|null $left_pref - For any starting value - partial where clause.
		 * @param boolean|null $right_pref - For any ending value - partial where clause.
		 *
		 * @return array
		 *
		 * @since 2.1.0
		 */
		public static function prepare_full_where( array $where_clause,
			string $condition = ' AND ',
			?string $criteria = ' = ',
			?bool $left_pref = false,
			?bool $right_pref = false
			): array {

			foreach ( $where_clause as $field => $value ) {
				if ( is_null( $value['value'] ) ) {
					$conditions[] = '`' . self::get_name() . '` . `' . $field . '` IS null';
					continue;
				}

				$conditions[] = '`' . self::get_name() . '` . `' . $field . '` ' . $criteria . ' ' .
				$value['format'];
				$values[]     =
				( ( $left_pref ) ? ' % ' : '' ) .
				$value['value'] .
				( ( $right_pref ) ? ' % ' : '' );
			}

			$conditions = implode( ' ' . $condition . ' ', $conditions );

			return array(
				'conditions' => $conditions,
				'values'     => $values,
			);
		}

		/**
		 * Collects table column names
		 *
		 * @return array
		 *
		 * @since 2.4.1
		 */
		public static function get_column_names(): array {
			$array = array_column( self::get_columns_info(), 'Field' );
			return array_combine( $array, $array );
		}

		/**
		 * Returns the class shortname
		 *
		 * @return string
		 *
		 * @since 2.4.1
		 */
		public static function get_entity_name(): string {
			return ( new \ReflectionClass( get_called_class() ) )->getShortName();
		}

		/**
		 * Default delete method
		 *
		 * @param integer $id - The real id of the table.
		 *
		 * @return int|bool
		 *
		 * @since 2.4.1
		 */
		public static function delete_by_id( int $id ) {
			global $wpdb;

			$result = $wpdb->delete(
				self::get_name(),
				array( self::get_real_id_name() => $id ),
				array( '%d' )
			);

			self::check_error();

			return $result;
		}

		/**
		 * Default delete method for API
		 *
		 * @param array $data - Array with key and value pair.
		 *
		 * @return int bool
		 *
		 * @since 2.4.1
		 */
		public static function delete_data( array $data ) {
			global $wpdb;

			if ( isset( $data['data'][ self::get_real_id_name() ] ) ) {
				return self::delete_by_id( (int) $data['data'][ self::get_real_id_name() ] );
			} else {

				$where        = $data['data'];
				$where_format = $data['formats'];

				$result = $wpdb->delete( self::get_name(), $where, $where_format );

				self::check_error();

				return $result;
			}
		}

		/**
		 * Default update method
		 *
		 * @param array $data - Array with key and value pair.
		 *
		 * @return int bool
		 *
		 * @since 2.4.1
		 */
		public static function update_data( array $data ) {
			global $wpdb;

			$where_clause = self::extract_where( $data );

			$where        = $where_clause['where'];
			$where_format = $where_clause['whereFormat'];

			$result = $wpdb->update( self::get_name(), $data['data'], $where, $data['formats'], $where_format );

			self::check_error();

			return $result;
		}

		/**
		 * Default insert method
		 *
		 * @param array $data - Array with key and value pair.
		 *
		 * @return int|bool
		 *
		 * @since 2.4.1
		 */
		public static function insert_data( array $data ) {
			global $wpdb;

			$wpdb->insert(
				self::get_name(),
				$data['data'],
				$data['formats']
			);

			self::check_error();

			return $wpdb->insert_id;
		}

		/**
		 * Backups the table.
		 * Format is the table name + yearmonthday-hour:minute:second
		 *
		 * @return void
		 *
		 * @since 2.1.0
		 */
		public static function backup_table() {
			global $wpdb;

			$new_table = self::get_name() . gmdate( 'Ymd-His' );

			$sql = "CREATE TABLE `$new_table` LIKE " . self::get_name();

			$wpdb->query( $sql ); // phpcs:ignore -- no need of placheholders - that is safe

			$sql = "INSERT INTO `$new_table` SELECT * FROM " . self::get_name();

			$wpdb->query( $sql ); // phpcs:ignore -- no need of placheholders - that is safe
		}

		/**
		 * Extracts array with where values / formats from prepared data array
		 *
		 * @param array $data - The array to extract the data from.
		 *
		 * @return array
		 *
		 * @since 2.4.1
		 */
		protected static function extract_where( array &$data ): array {
			$where[ self::get_real_id_name() ] = $data['data'][ self::get_real_id_name() ];
			unset( $data['data'][ self::get_real_id_name() ] );
			$where_format[ self::get_real_id_name() ] = $data['formats'][ self::get_real_id_name() ];
			unset( $data['formats'][ self::get_real_id_name() ] );

			return array(
				'where'       => $where,
				'whereFormat' => $where_format,
			);
		}

		/**
		 * Returns the table CMS admin fields
		 *
		 * @return array
		 *
		 * @since 2.1.0
		 */
		public static function get_column_names_admin(): array {
			return self::get_column_names();
		}

		/**
		 * Adds columns to the screen options screed
		 *
		 * @param array $columns - Array of column names.
		 *
		 * @return array
		 *
		 * @since 2.1.0
		 */
		public static function manage_columns( $columns ): array {
			if ( empty( self::$admin_columns ) ) {
				$screen_options = self::get_column_names();

				$table_columns = array(
					'cb' => '<input type="checkbox" />', // to display the checkbox.
				);

				self::$admin_columns = \array_merge( $table_columns, $screen_options, $columns );
			}

			return self::$admin_columns;
		}

		/**
		 * Returns a list with all available tables.
		 *
		 * @param \wpdb $connection - \wpdb connection to be used for name extraction.
		 *
		 * @return array
		 *
		 * @since 2.1.0
		 */
		public static function get_tables( $connection = null ): array {

			if ( empty( self::$tables ) ) {
				if ( null !== $connection ) {
					if ( $connection instanceof \wpdb ) {
						$_wpdb = $connection;
					}
				} else {
					global $wpdb;
					$_wpdb = $wpdb;
				}

				$_wpdb->suppress_errors( true );
				$results = $_wpdb->get_results(
					// $wpdb->prepare(
						// 'SELECT table_name FROM information_schema.tables WHERE table_schema = %s;',
					'SHOW TABLES;',
					// $wpdb->dbname
					// ),
					\ARRAY_A
				);

				if ( '' !== $_wpdb->last_error || null === $results ) {

					self::$tables = self::get_wp_core_tables();

				} else {
					foreach ( $results as $table ) {
						self::$tables[] = reset( $table );
					}
				}

				$_wpdb->suppress_errors( false );
			}

			return self::$tables;
		}

		/**
		 * Returns all of the core WP tables.
		 *
		 * @return array
		 *
		 * @since 2.2.0
		 */
		public static function get_wp_core_tables() {
			if ( empty( self::$core_tables ) ) {
				global $wpdb;

				self::$core_tables = $wpdb->tables( 'all' );
			}

			return self::$core_tables;
		}

		/**
		 * Returns the table size in Megabyte format
		 *
		 * @return int
		 *
		 * @since 2.1.2
		 */
		public static function get_table_size() {
			if ( null === self::$table_size ) {
				global $wpdb;

				$sql = "SELECT 
				ROUND(((data_length + index_length)), 2) AS `Size (B)`
			FROM
				information_schema.TABLES
			WHERE
				table_schema = '" . $wpdb->dbname . "'
				AND table_name = '" . self::get_name() . "';";

				$wpdb->suppress_errors( true );
				$results = $wpdb->get_var( $sql );

				if ( '' !== $wpdb->last_error || null === $results ) {

					$results = array();

				}

				$wpdb->suppress_errors( false );

				if ( $results ) {
					self::$table_size = $results;
				} else {
					self::$table_size = 0;
				}
			}

			return self::$table_size;
		}

		/**
		 * Extracts table information and returns it
		 *
		 * @return array
		 *
		 * @since 2.3.0
		 */
		public static function get_table_status(): array {

			if ( empty( self::$table_stat ) ) {
				global $wpdb;

				$sql = 'SHOW TABLE STATUS FROM `' . $wpdb->dbname . '` LIKE \'' . self::get_name() . '\'; ';

				$wpdb->suppress_errors( true );

				$results = $wpdb->get_results( $sql, \ARRAY_A );

				if ( '' !== $wpdb->last_error || null === $results ) {

					$results = array();

				}

				$wpdb->suppress_errors( false );

				self::$table_stat = $results;
			}

			return self::$table_stat;
		}

		/**
		 * Returns the default table to show if none selected.
		 *
		 * @return string
		 *
		 * @since 2.4.1
		 */
		public static function get_default_table(): string {
			global $wpdb;

			return $wpdb->prefix . 'options';
		}
	}
}
