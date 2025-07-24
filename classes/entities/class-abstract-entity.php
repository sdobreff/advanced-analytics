<?php
/**
 * Entity: Abstract.
 *
 * @package advan
 *
 * @since 2.4.2.1
 */

declare(strict_types=1);

namespace ADVAN\Entities;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\ADVAN\Entities\Abstract_Entity' ) ) {

	/**
	 * Responsible for the common entity operations.
	 */
	abstract class Abstract_Entity {

		/**
		 * Contains the table name.
		 *
		 * @var string
		 *
		 * @since 2.4.2.1
		 */
		private static $table = '';

		/**
		 * Holds the DB connection (for caching purposes)
		 *
		 * @var \wpdb Connection
		 *
		 * @since 2.4.2.1
		 */
		private static $connection = null;

		/**
		 * Keeps the info about the columns of the table - name, type
		 *
		 * @var array
		 *
		 * @since 2.4.2.1
		 */
		private static $fields = array();

		/**
		 * Returns the the table name
		 *
		 * @param \wpdb $connection - \wpdb connection to be used for name extraction.
		 *
		 * @return string
		 *
		 * @since 2.4.2.1
		 */
		public static function get_table_name( $connection = null ): string {
			if ( null !== $connection ) {

				if ( $connection instanceof \wpdb ) {
					return $connection->base_prefix . static::$table;
				}
			}

			return static::get_connection()->base_prefix . static::$table;
		}

		/**
		 * Returns the current connection (used by the plugin)
		 *
		 * @return \wpdb
		 *
		 * @since 2.4.2.1
		 */
		public static function get_connection() {
			if ( null === self::$connection ) {
				global $wpdb;
				self::$connection = $wpdb;
			}

			return self::$connection;
		}

		/**
		 * Sets connection to the given value.
		 *
		 * @param \wpdb $connection - The connection to set to.
		 *
		 * @return void
		 *
		 * @since 2.4.2.1
		 */
		public static function set_connection( $connection ) {
			self::$connection = $connection;
		}

		/**
		 * As this is static class, we need to destroy the connection sometimes.
		 *
		 * @return void
		 *
		 * @since 2.4.2.1
		 */
		public static function destroy_connection() {
			self::$connection = null;
		}

		/**
		 * Checks if the table needs to be recreated / created
		 *
		 * @param string $table_name - The name of the table to check for.
		 * @param string $create_ddl - The create table syntax.
		 * @param \wpdb  $connection - \wpdb connection to be used for name extraction.
		 *
		 * @return bool
		 *
		 * @since 2.4.2.1
		 */
		public static function maybe_create_table( string $table_name, string $create_ddl, $connection = null ): bool {
			if ( null !== $connection ) {
				if ( $connection instanceof \wpdb ) {
					$_wpdb = $connection;
				}
			} else {
				$_wpdb = static::get_connection();
			}

			if ( Common_Table::check_table_exists( $table_name, $_wpdb ) ) {
				// Table exists, so we don't need to create it.
				return true;
			}

			// Didn't find it, so try to create it.
			$_wpdb->query( $create_ddl );

			// We cannot directly tell that whether this succeeded!
			if ( Common_Table::check_table_exists( $table_name, $_wpdb ) ) {
				// Table exists, so we don't need to create it.
				return true;
			}

			return false;
		}

		/**
		 * Checks if the given index exists in the table
		 *
		 * @param string $index - The index to check for (text).
		 *
		 * @return boolean
		 *
		 * @since 2.4.2.1
		 */
		public static function check_index_exists( string $index ): bool {
			$index = \sanitize_text_field( $index );

			$results = static::get_connection()->get_results( 'SHOW INDEX FROM ' . self::get_table_name(), \ARRAY_A );

			foreach ( $results as $row ) {
				if ( isset( $row['Key_name'] ) && $row['Key_name'] === $index ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Checks and returns last mysql error
		 *
		 * @param \wpdb $_wpdb - The Mysql resource class.
		 *
		 * @return integer
		 *
		 * @since 2.4.2.1
		 */
		public static function get_last_sql_error( $_wpdb ): int {
			$code = 0;
			if ( $_wpdb->dbh instanceof \mysqli ) {
				$code = \mysqli_errno( $_wpdb->dbh ); // phpcs:ignore WordPress.DB.RestrictedFunctions.mysql_mysqli_errno
			}

			if ( is_resource( $_wpdb->dbh ) ) {
				// Please do not report this code as a PHP 7 incompatibility. Observe the surrounding logic.
				$code = mysql_errno( $_wpdb->dbh ); // phpcs:ignore WordPress.DB.RestrictedFunctions.mysql_mysql_errno
			}

			return $code;
		}

		/**
		 * Checks if a table exists for a given connection (if no connection is provided - current connection will be used.)
		 *
		 * @param \wpdb $connection  - \wpdb connection to be used for name extraction.
		 *
		 * @return boolean
		 *
		 * @since 2.4.2.1
		 */
		public static function is_installed( $connection = null ): bool {
			return Common_Table::check_table_exists( self::get_table_name( $connection ), $connection );
		}

		/**
		 * Returns records in the table based on condition
		 *
		 * @param string $cond - The condition.
		 * @param array  $args - The arguments (values).
		 * @param \wpdb  $connection - \wpdb connection to be used for name extraction.
		 *
		 * @return int
		 *
		 * @since 2.4.2.1
		 */
		public static function count( $cond = '%d', $args = array( 1 ), $connection = null ) {
			if ( null !== $connection ) {
				if ( $connection instanceof \wpdb ) {
					$_wpdb = $connection;
				}
			} else {
				$_wpdb = static::get_connection();
			}

			$sql = $_wpdb->prepare( 'SELECT COUNT(*) FROM ' . self::get_table_name( $_wpdb ) . ' WHERE ' . $cond, $args );

			$_wpdb->suppress_errors( true );
			$count = (int) $_wpdb->get_var( $sql );
			if ( '' !== $_wpdb->last_error ) {
				if ( 1146 === self::get_last_sql_error( $_wpdb ) ) {
					if ( ( static::class )::create_table( $_wpdb ) ) {
						$count = 0;
					}
				}
			}
			$_wpdb->suppress_errors( false );

			return $count;
		}

		/**
		 * Saves the given data into the table
		 * The data should be in following format:
		 * field name => value
		 *
		 * It checks the given data array against the table fields and determines the types based on that, it stores the values in the table then.
		 *
		 * @param array $data - The data to be saved (check above about the format).
		 *
		 * @return int
		 *
		 * @since 2.4.2.1
		 */
		public static function insert( $data ) {

			$format      = array();
			$insert_data = array();

			foreach ( $data as $key => $val ) {
				if ( isset( ( static::class )::$fields[ $key ] ) ) {
					$insert_data[ $key ] = $val;
					$format[ $key ]      = '%s';
					if ( 'int' === ( static::class )::$fields[ $key ] ) {
						$format[ $key ] = '%d';
					}
					if ( 'float' === ( static::class )::$fields[ $key ] ) {
						$format[ $key ] = '%f';
					}
				}
			}

			if ( ! empty( $format ) ) {
				$_wpdb = static::get_connection();

				$_wpdb->suppress_errors( true );

				$_wpdb->replace( self::get_table_name( $_wpdb ), $insert_data, $format );

				if ( '' !== $_wpdb->last_error ) {
					if ( 1146 === self::get_last_sql_error( $_wpdb ) ) {
						if ( ( static::class )::create_table( $_wpdb ) ) {
							$_wpdb->replace( self::get_table_name( $_wpdb ), $data, $format );
						}
					}
				}

				$_wpdb->suppress_errors( false );

				return $_wpdb->insert_id;
			}

			return 0; // no record is inserted.
		}

		/**
		 * Prepares the data array and the format array based on the existing table fields
		 *
		 * @param array $data - The data to make preparation from.
		 *
		 * @return array
		 *
		 * @since 2.4.2.1
		 */
		public static function prepare_data( array $data ): array {

			$format      = array();
			$insert_data = array();

			foreach ( $data as $key => $val ) {
				if ( isset( ( static::class )::$fields[ $key ] ) ) {
					$insert_data[ $key ] = $val;
					$format[ $key ]      = '%s';
					if ( 'int' === ( static::class )::$fields[ $key ] ) {
						$format[ $key ] = '%d';
					}
					if ( 'float' === ( static::class )::$fields[ $key ] ) {
						$format[ $key ] = '%f';
					}
				}
			}

			return array( $insert_data, $format );
		}

		/**
		 * Load record from DB (Single row).
		 *
		 * @param string $cond - (Optional) Load condition.
		 * @param array  $args - (Optional) Load condition arguments.
		 * @param \wpdb  $connection - \wpdb connection to be used for name extraction.
		 * @param string $extra - The extra SQL string (if needed).
		 *
		 * @return array
		 *
		 * @since 5.0.0
		 */
		public static function load( $cond = 'id=%d', $args = array( 1 ), $connection = null, $extra = '' ) {
			if ( null !== $connection ) {
				if ( $connection instanceof \wpdb ) {
					$_wpdb = $connection;
				}
			} else {
				$_wpdb = static::get_connection();
			}
			$sql = $_wpdb->prepare( 'SELECT * FROM ' . self::get_table_name( $_wpdb ) . ' WHERE ' . $cond, $args );

			if ( ! empty( trim( $extra ) ) ) {
				$sql .= $extra;
			}

			return $_wpdb->get_row( $sql, ARRAY_A );
		}

		/**
		 * Load records from DB (Multi rows).
		 *
		 * @param string $cond Load condition.
		 * @param array  $args (Optional) Load condition arguments.
		 * @param \wpdb  $connection - \wpdb connection to be used for name extraction.
		 * @param string $extra - The extra SQL string (if needed).
		 *
		 * @return array
		 *
		 * @since 5.0.0
		 */
		public static function load_array( $cond, $args = array(), $connection = null, $extra = '' ) {

			if ( null !== $connection ) {
				if ( $connection instanceof \wpdb ) {
					$_wpdb = $connection;
				}
			} else {
				$_wpdb = static::get_connection();
			}

			$sql = $_wpdb->prepare( 'SELECT * FROM ' . self::get_table_name( $_wpdb ) . ' WHERE ' . $cond, $args );

			if ( ! empty( trim( $extra ) ) ) {
				$sql .= $extra;
			}

			$_wpdb->suppress_errors( true );
			$results = $_wpdb->get_results( $sql, ARRAY_A );

			if ( '' !== $_wpdb->last_error ) {
				if ( 1146 === self::get_last_sql_error( $_wpdb ) ) {
					if ( ( static::class )::create_table( $_wpdb ) ) {
						$results = array();
					}
				}
			}
			$_wpdb->suppress_errors( false );

			return $results;
		}

		/**
		 * Delete records in DB matching a query.
		 *
		 * @param string $query Full SQL query.
		 * @param array  $args  (Optional) Query arguments.
		 * @param \wpdb  $connection - \wpdb connection to be used for name extraction.
		 *
		 * @return int|bool
		 *
		 * @since 2.4.2.1
		 */
		public static function delete_query( $query, $args = array(), $connection = null ) {
			if ( null !== $connection ) {
				if ( $connection instanceof \wpdb ) {
					$_wpdb = $connection;
				}
			} else {
				$_wpdb = static::get_connection();
			}

			$sql = count( $args ) ? $_wpdb->prepare( $query, $args ) : $query;

			$_wpdb->suppress_errors( true );
			$res = $_wpdb->query( $sql );
			if ( '' !== $_wpdb->last_error ) {
				if ( 1146 === self::get_last_sql_error( $_wpdb ) ) {
					if ( ( static::class )::create_table( $_wpdb ) ) {
						$res = true;
					}
				}
			}
			$_wpdb->suppress_errors( false );

			return $res;
		}

		/**
		 * Default delete method
		 *
		 * @param integer $id - The real id of the table.
		 * @param \wpdb   $connection - \wpdb connection to be used for name extraction.
		 *
		 * @return int|bool
		 *
		 * @since 2.4.2.1
		 */
		public static function delete_by_id( int $id, $connection = null ) {
			if ( null !== $connection ) {
				if ( $connection instanceof \wpdb ) {
					$_wpdb = $connection;
				}
			} else {
				$_wpdb = static::get_connection();
			}

			$result = $_wpdb->delete(
				self::get_table_name( $_wpdb ),
				array( 'id' => $id ),
				array( '%d' )
			);

			return $result;
		}

		/**
		 * Duplicates table row by its ID
		 *
		 * @param integer $id - The ID of row to duplicate.
		 * @param \wpdb   $connection - The connection which has to be used.
		 *
		 * @return mixed
		 *
		 * @since 5.0.0
		 */
		public static function duplicate_by_id( int $id, $connection ) {
			if ( null !== $connection ) {
				if ( $connection instanceof \wpdb ) {
					$_wpdb = $connection;
				}
			} else {
				$_wpdb = static::get_connection();
			}

			$sql = 'INSERT INTO ' . self::get_table_name( $_wpdb ) . '
				(' . \implode( ',', static::get_duplicate_fields( false ) ) . ')
			SELECT 
				' . \implode( ',', static::get_duplicate_fields( true ) ) . '
			FROM 
				' . self::get_table_name( $_wpdb ) . '
			WHERE 
				id = ' . $id;

			$_wpdb->suppress_errors( true );

			$result = $_wpdb->query(
				$sql
			);

			if ( '' !== $_wpdb->last_error ) {
				if ( 1146 === self::get_last_sql_error( $_wpdb ) ) {
					if ( ( static::class )::create_table( $_wpdb ) ) {

						$result = $_wpdb->query(
							$sql
						);

					}
				}
			}
			$_wpdb->suppress_errors( false );

			return $_wpdb->insert_id;
		}

		/**
		 * Returns array with fields to duplicate, gets rid of id and created_on columns.
		 *
		 * @param bool $duplicate_values - When called for duplication, gives the class ability to set fields that must have specific values in the database.
		 *
		 * @return array
		 *
		 * @since 5.0.0
		 */
		public static function get_duplicate_fields( bool $duplicate_values ): array {
			$fields = self::get_fields();
			unset( $fields['id'] );
			if ( $duplicate_values && isset( $fields['created_on'] ) ) {
				$fields = \array_keys( $fields );
				$time   = \microtime( true );
				$key    = array_search( 'created_on', $fields, true );

				$fields[ $key ] = $time;

				return $fields;
			}

			return array_keys( $fields );
		}

		/**
		 * Default find method
		 *
		 * @param array $data - Must contains formats and data. The array should contain:
		 * 'data' - Associative array of all the fields and values to search for.
		 * 'formats' - array of all the formats for the data we are searching for.
		 *
		 * @return array|bool
		 *
		 * @since 2.4.2.1
		 */
		public static function find( array $data ) {
			/**
			 * \wpdb has very powerful method called process_fields @see \wpdb::process_fields().
			 * Unfortunately this method is not accessible, because it is marked protected. The best solution at the moment is to clone the class, lower the visibility and use the method.
			 *
			 * That of course takes resources so possible solution is to add also caching to this method, so that is marked as todo below.
			 *
			 * TODO: Add caching functionality to the method.
			 */
			// phpcs:disable
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
			// phpcs:enable

			$_wpdb = static::get_connection();

			$where_clause = $wpdb_clone->process_fields(
				self::get_table_name(),
				$data['data'],
				$data['formats']
			);

			$where_data = self::prepare_full_where( $where_clause );

			$conditions = $where_data['conditions'];
			$values     = $where_data['values'];

			$_wpdb->check_current_query = false;

			$sql = $_wpdb->prepare(
				'SELECT * FROM `' . self::get_table_name( $_wpdb ) . '` WHERE ' . $conditions,
				$values
			);

			$_wpdb->suppress_errors( true );

			$result = $_wpdb->get_results(
				$sql,
				ARRAY_A
			);

			if ( '' !== $_wpdb->last_error ) {
				if ( 1146 === self::get_last_sql_error( $_wpdb ) ) {
					if ( ( static::class )::create_table( $_wpdb ) ) {

						$result = array();

					}
				}
			}
			$_wpdb->suppress_errors( false );

			return $result;
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
		 * @since 2.4.2.1
		 */
		public static function prepare_full_where(
			array $where_clause,
			string $condition = ' AND ',
			?string $criteria = ' = ',
			?bool $left_pref = false,
			?bool $right_pref = false
		): array {

			foreach ( $where_clause as $field => $value ) {
				if ( is_null( $value['value'] ) ) {
					$conditions[] = '`' . self::get_table_name() . '` . `' . $field . '` IS null';
					continue;
				}

				if ( \is_array( $value['value'] ) ) {
					$cond_string  = '(`' . self::get_table_name() . '` . `' . $field . '` ';
					$cond_string .= ' BETWEEN ';
					foreach ( $value['value'] as $val ) {
						$cond_string .= $value['format'] . ' ' . $condition . ' ';
						$values[]     =
						( ( $left_pref ) ? ' % ' : '' ) .
						$val .
						( ( $right_pref ) ? ' % ' : '' );
					}
					$cond_string  = rtrim( $cond_string, ' ' . $condition . ' ' ) . ')';
					$conditions[] = $cond_string;

					continue;
				}

				$conditions[] = '`' . self::get_table_name() . '` . `' . $field . '` ' . $criteria . ' ' .
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
		 * Return the table fields and default values
		 *
		 * @return array
		 *
		 * @since 2.4.2.1
		 */
		public static function get_fields_values(): array {
			return ( static::class )::$fields_values;
		}

		/**
		 * Return the table fields
		 *
		 * @return array
		 *
		 * @since 2.4.2.1
		 */
		public static function get_fields(): array {
			return ( static::class )::$fields;
		}

		/**
		 * Creates array with the full filed names (table name included) for the given table
		 *
		 * @param \wpdb $connection - \wpdb connection to be used for name extraction.
		 *
		 * @return array
		 *
		 * @since 2.4.2.1
		 */
		public static function prepare_full_select_statement( $connection = null ): array {
			$full_fields = array();
			foreach ( \array_keys( ( static::class )::$fields ) as $field ) {
				$full_fields[ self::get_table_name( $connection ) . '.' . $field ] = self::get_table_name( $connection ) . $field;
			}

			return $full_fields;
		}


		public static function get_results( string $query, $connection = null ): array {
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
				$query,
				\ARRAY_A
			);

			if ( '' !== $_wpdb->last_error ) {
				if ( 1146 === self::get_last_sql_error( $_wpdb ) ) {
					if ( ( static::class )::create_table( $_wpdb ) ) {

						$results = array();

					}
				}
			}

			$_wpdb->suppress_errors( false );

			return $results;
		}
	}
}
