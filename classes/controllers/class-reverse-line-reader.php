<?php
/**
 * Reads file in reverse order
 *
 * @package advanced-analytics
 *
 * @since 1.1.1
 */

declare(strict_types=1);

namespace ADVAN\Controllers;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\ADVAN\Controllers\Reverse_Line_Reader' ) ) {
	/**
	 * Responsible for reding lines from the end of file.
	 *
	 * @since 1.1.1
	 */
	class Reverse_Line_Reader {
		const BUFFER_SIZE = 16384;
		const SEPARATOR   = PHP_EOL;

		/**
		 * Keeps track of of the current position in the file.
		 *
		 * @var array
		 *
		 * @since 1.1.1
		 */
		private static $buffer = array( '' );

		/**
		 * Holds the value of the buffer size.
		 *
		 * @var int
		 *
		 * @since 1.1.1
		 */
		private static $buffer_size = self::BUFFER_SIZE;

		/**
		 * The file size.
		 *
		 * @var int
		 *
		 * @since 1.1.1
		 */
		private static $file_size = 0;

		/**
		 * Keeps track of of the current position in the file.
		 *
		 * @var int
		 *
		 * @since 1.1.1
		 */
		private static $pos = null;

		/**
		 * Stores the temp file handle for showing the truncated error log.
		 *
		 * @var handle
		 *
		 * @since 1.1.1
		 */
		private static $temp_handle = null;

		/**
		 * Stores the memory file handle for showing the truncated error log.
		 *
		 * @var handle
		 *
		 * @since 1.1.1
		 */
		private static $memory_handle = null;

		/**
		 * Stores the error log file handle for reading the error log.
		 *
		 * @var handle
		 *
		 * @since latest
		 */
		private static $error_log_handle = null;

		/**
		 * Reads lines from given file reversed order.
		 *
		 * @param string|resource $file_or_handle - The file or handle to read from.
		 * @param function        $callback - The function to call back when result is returned.
		 * @param integer         $max_lines - Maximum number of lines to read.
		 * @param int|null        $pos - The current position to start reading from.
		 * @param bool            $temp_writer - Whether to write the error log to a temporary file or not.
		 *
		 * @return void|bool
		 *
		 * @since 1.1.1
		 */
		public static function read_file_from_end( $file_or_handle, $callback, &$max_lines = 0, $pos = null, bool $temp_writer = true ) {
			if ( \is_a( $file_or_handle, 'WP_Error' ) ) {
				return $file_or_handle;
			}

			if ( null === $pos ) {
				self::$pos    = -1;
				self::$buffer = array( '' );
			}
			if ( null === self::$error_log_handle && \is_string( $file_or_handle ) ) {
				if ( \file_exists( $file_or_handle ) && \is_readable( $file_or_handle ) ) {
					self::$error_log_handle = fopen( $file_or_handle, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
				} else {
					$max_lines = 0;

					self::reset_class_globals();

					return false;
				}
			} elseif ( null === self::$error_log_handle && \is_resource( $file_or_handle ) && ( 'handle' === \get_resource_type( $file_or_handle ) || 'stream' === get_resource_type( $file_or_handle ) ) ) {
				self::$error_log_handle = $file_or_handle;
			} elseif ( null === self::$error_log_handle ) {
				$max_lines = 0;

				self::reset_class_globals();

				return false;
			}
			// Lets check the size and act appropriately.
			if ( null === $pos ) {
				fseek( self::$error_log_handle, 0, SEEK_END );
				$size = ftell( self::$error_log_handle );
				if ( 0 === (int) $size ) {
					self::reset_class_globals();

					$max_lines = 0;

					return false;
				} elseif ( self::$buffer_size >= (int) $size ) {
					// self::$pos is holding negative values - so sum.
					self::$buffer_size = ( (int) $size ) + self::$pos;
				}

				self::$file_size = - (int) $size;
			}

			$line = self::readline();

			if ( null === $line ) {
				self::reset_class_globals();

				$max_lines = 0;

				return false;
			}

			if ( $temp_writer ) {
				self::write_memory_file( $line . self::SEPARATOR );
			}
			$result = $callback( $line, self::$pos );

			if ( true === $result['close'] ) {
				self::reset_class_globals();

				$max_lines = 0;

				return false;
			}
			if ( $max_lines > 0 ) {
				if ( $result['line_done'] && ! $result['no_flush'] ) {
					if ( $temp_writer ) {
						self::flush_memory_file_to_temp();
					}
					--$max_lines;
				}
				if ( $result['line_done'] && $result['no_flush'] ) {

					if ( null !== self::$memory_handle ) {
						\fclose( self::$memory_handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

						self::$memory_handle = null;
					}
				}
				if ( 0 === $max_lines ) {
					self::reset_class_globals();

					$max_lines = 0;

					return false;
				}
			}

			// self::read_file_from_end( $handle, $callback, $max_lines, self::$pos, $temp_writer );
		}

		/**
		 * Reads buffer from the end of the file backwards to the beginning.
		 *
		 * @param int $size - The buffer size to read.
		 *
		 * @return string|false
		 *
		 * @since 1.1.1
		 */
		public static function read( int $size ) {
			self::$pos -= $size;
			if ( 0 === self::$pos ) {
				fseek( self::$error_log_handle, 0 );
			} else {
				if ( self::$pos < self::$file_size ) {
					$size      = abs( abs( self::$pos ) - abs( self::$file_size ) - self::$buffer_size );
					self::$pos = self::$file_size;
				}
				fseek( self::$error_log_handle, self::$pos, SEEK_END );
			}
			$read_string = fread( self::$error_log_handle, $size ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread

			return $read_string;
		}

		/**
		 * Reads line from file
		 *
		 * @return string
		 *
		 * @since 1.1.1
		 */
		public static function readline() {
			$buffer =& self::$buffer;
			while ( true ) {
				if ( 0 === self::$pos || self::$pos <= self::$file_size ) {

					if ( self::$pos < self::$file_size ) {

						self::$buffer_size = abs( ( self::$file_size - -self::$buffer_size ) + 1 );
						self::$pos         = self::$buffer_size;
						$buffer            = explode( self::SEPARATOR, self::read( self::$buffer_size, self::$error_log_handle ) . ( ( isset( $buffer[0] ) ) ? $buffer[0] : '' ) );

						self::$pos = 0;

						return array_pop( $buffer );
					}

					return array_pop( $buffer );
				}
				if ( count( $buffer ) > 1 ) {
					return array_pop( $buffer );
				}
				$buffer = explode( self::SEPARATOR, self::read( self::$buffer_size, self::$error_log_handle ) . ( ( isset( $buffer[0] ) ) ? $buffer[0] : '' ) );
			}
		}

		/**
		 * Writes temporary file used lated on to show the content of the error log (in reverse order and truncated to the last couple of errors)
		 *
		 * @param string $line - The line to be written.
		 *
		 * @return void
		 *
		 * @since 1.1.1
		 */
		public static function write_temp_file( string $line ) {
			if ( null === self::$temp_handle ) {
				self::$temp_handle = fopen( 'php://temp', 'w+' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
			}

			fwrite( self::$temp_handle, $line ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
		}

		/**
		 * Writes memory file used lated on to show the content of the error log (in reverse order and truncated to the last couple of errors)
		 *
		 * @param string $line - The line to be written.
		 *
		 * @return void
		 *
		 * @since 1.1.1
		 */
		public static function write_memory_file( string $line ) {
			if ( null === self::$memory_handle ) {
				self::$memory_handle = fopen( 'php://memory', 'w+' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
			}

			fwrite( self::$memory_handle, $line ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
		}

		/**
		 * Reads the contents of the temp file and returns the contents.
		 *
		 * @return void
		 *
		 * @since 1.1.1
		 */
		public static function read_temp_file() {
			if ( \is_resource( self::$temp_handle ) && ( 'handle' === get_resource_type( self::$temp_handle ) || 'stream' === get_resource_type( self::$temp_handle ) ) ) {
				rewind( self::$temp_handle ); // resets the position of pointer.

				echo fread( self::$temp_handle, fstat( self::$temp_handle )['size'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread, WordPress.Security.EscapeOutput.OutputNotEscaped

				fclose( self::$temp_handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			}
		}

		/**
		 * Reads the contents of the memory file and returns the contents.
		 *
		 * @return void
		 *
		 * @since 1.1.1
		 */
		public static function read_memory_file() {
			if ( \is_resource( self::$memory_handle ) && ( 'handle' === get_resource_type( self::$memory_handle ) || 'stream' === get_resource_type( self::$memory_handle ) ) ) {
				rewind( self::$memory_handle ); // resets the position of pointer.

				echo fread( self::$memory_handle, fstat( self::$memory_handle )['size'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread, WordPress.Security.EscapeOutput.OutputNotEscaped

				fclose( self::$memory_handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			}

			self::close_streams();
		}

		/**
		 * Writes the contents of the memory file to a temporary file in reverse order.
		 *
		 * @return void
		 *
		 * @since 1.5.0
		 */
		public static function flush_memory_file_to_temp() {
			if ( \is_resource( self::$memory_handle ) && ( 'handle' === get_resource_type( self::$memory_handle ) || 'stream' === get_resource_type( self::$memory_handle ) ) ) {

				$line = '';
				for ( $x_pos = 0; fseek( self::$memory_handle, $x_pos, SEEK_END ) !== -1; $x_pos-- ) {
					$char = fgetc( self::$memory_handle );

					if ( PHP_EOL === $char ) {
						self::write_temp_file( $line . PHP_EOL );
						$line = '';
						continue;
					} else {
						$line = $char . $line;
					}
				}
				if ( ! empty( $line ) ) {
					self::write_temp_file( $line . PHP_EOL );
				}
				fclose( self::$memory_handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

				self::$memory_handle = null;
			}
		}

		/**
		 * Closes the streams.
		 *
		 * @return void
		 *
		 * @since latest
		 */
		public static function close_streams() {
			if ( \is_resource( self::$temp_handle ) && ( 'handle' === get_resource_type( self::$temp_handle ) || 'stream' === get_resource_type( self::$temp_handle ) ) ) {
				fclose( self::$temp_handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

				self::$temp_handle = null;
			}
			if ( \is_resource( self::$memory_handle ) && ( 'handle' === get_resource_type( self::$memory_handle ) || 'stream' === get_resource_type( self::$memory_handle ) ) ) {
				fclose( self::$memory_handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

				self::$memory_handle = null;
			}
		}

		/**
		 * Resets the class globals.
		 *
		 * @return void
		 *
		 * @since latest
		 */
		public static function reset_class_globals() {
			if ( \is_resource( self::$error_log_handle ) && ( 'handle' === get_resource_type( self::$error_log_handle ) || 'stream' === get_resource_type( self::$error_log_handle ) ) ) {
				\fclose( self::$error_log_handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			}

			self::$buffer_size      = self::BUFFER_SIZE;
			self::$buffer           = array( '' );
			self::$pos              = null;
			self::$error_log_handle = null;
		}
	}
}
