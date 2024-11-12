<?php
/**
 * Reads file in reverse order
 *
 * @package advanced-analytics
 *
 * @since latest
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
	 * @since latest
	 */
	class Reverse_Line_Reader {
		const BUFFER_SIZE = 4096;
		const SEPARATOR   = PHP_EOL;

		/**
		 * Keeps track of of the current position in the file.
		 *
		 * @var array
		 *
		 * @since latest
		 */
		private static $buffer = array( '' );

		/**
		 * Holds the value of the buffer size.
		 *
		 * @var int
		 *
		 * @since latest
		 */
		private static $buffer_size = self::BUFFER_SIZE;

		/**
		 * The file size.
		 *
		 * @var int
		 *
		 * @since latest
		 */
		private static $file_size = 0;

		/**
		 * Keeps track of of the current position in the file.
		 *
		 * @var int
		 *
		 * @since latest
		 */
		private static $pos = null;

		/**
		 * Stores the temp file handle for showing the truncated error log.
		 *
		 * @var handle
		 *
		 * @since latest
		 */
		private static $temp_handle = null;

		/**
		 * Reads lines from given file reversed order.
		 *
		 * @param string|resource $file_or_handle - The file or handle to read from.
		 * @param function        $callback - The function to call back when result is returned.
		 * @param integer         $max_ines - Maximum number of lines to read.
		 * @param int|null        $pos - The current position to start reading from.
		 *
		 * @return void|bool
		 *
		 * @since latest
		 */
		public static function read_file_from_end( $file_or_handle, $callback, $max_ines = 0, $pos = null, bool $temp_writer = true ) {
			if ( null === $pos ) {
				self::$pos    = -1;
				self::$buffer = array( '' );
			}
			if ( \is_string( $file_or_handle ) ) {
				if ( \file_exists( $file_or_handle ) && \is_readable( $file_or_handle ) ) {
					$handle = fopen( $file_or_handle, 'r' );
				} else {
					return false;
				}
			} elseif ( \is_resource( $file_or_handle ) && ( 'handle' === get_resource_type( $file_or_handle ) || 'stream' === get_resource_type( $file_or_handle ) ) ) {
				$handle = $file_or_handle;
			} else {
				return false;
			}
			// Lets check the size and act appropriately.
			if ( null === $pos ) {
				fseek( $handle, 0, SEEK_END );
				$size = ftell( $handle );
				if ( 0 === (int) $size ) {
					fclose( $handle );
					return false;
				} elseif ( self::$buffer_size >= (int) $size ) {
					// self::$pos is holding negative values - so sum.
					self::$buffer_size = ( (int) $size ) + self::$pos;
				}

				self::$file_size = - (int) $size;
			}

			// while ( true ) {
			// fseek( $handle, self::$pos, SEEK_END );
			// --self::$pos;
			// $char = fgetc( $handle );
			// if ( "\n" === $char ) {
			// break;
			// }
			// if ( false === $char ) {
			// fseek( $handle, 0 );
			// break;
			// }
			// }

			// $line = fgets( $handle );

			$line = self::readline( $handle );

			if ( null === $line ) {
				fclose( $handle );

				return;
			}

			/*
			New shit
			while ( ( $buffer = fgets( $fp, 4096 ) ) !== false ) {
				echo $buffer, PHP_EOL;
			}

			fseek( $handle, self::$pos - 4096, SEEK_END );

			$line = fgets( $handle, 4096 );

			self::$pos -= \mb_strlen( (string) $line );
			*/

			if ( $temp_writer ) {
				self::write_temp_file( $line . self::SEPARATOR );
			}
			$result = $callback( $line, $pos );

			if ( false === $result ) {
				\fclose( $handle );

				return;
			}
			// if ( false === $char ) {
			// return;
			// }
			if ( $max_ines > 0 ) {
				--$max_ines;
				if ( 0 === $max_ines ) {
					\fclose( $handle );

					return;
				}
			}

			self::read_file_from_end( $handle, $callback, $max_ines, self::$pos, $temp_writer );
		}

		/**
		 * Reads buffer from the end of the file backwards to the beginning.
		 *
		 * @param int      $size - The buffer size to read.
		 * @param resource $file_or_handle - The resource to read from.
		 *
		 * @return string|false
		 *
		 * @since latest
		 */
		public static function read( int $size, &$file_or_handle ) {
			self::$pos -= $size;
			if ( 0 === self::$pos ) {
				fseek( $file_or_handle, 0 );
			} else {
				fseek( $file_or_handle, self::$pos, SEEK_END );
			}
			$read_string = fread( $file_or_handle, $size );

			return $read_string;
		}

		/**
		 * Reads line from file
		 *
		 * @param resource $file_or_handle - The file handle to read from.
		 *
		 * @return string
		 *
		 * @since latest
		 */
		public static function readline( &$file_or_handle ) {
			$buffer =& self::$buffer;
			while ( true ) {
				if ( 0 === self::$pos || self::$pos <= self::$file_size ) {

					if ( self::$pos < self::$file_size ) {

						self::$buffer_size = abs( self::$file_size - -self::$buffer_size );
						self::$pos         = self::$buffer_size;
						$buffer            = explode( self::SEPARATOR, self::read( self::$buffer_size, $file_or_handle ) . ( ( isset( $buffer[0] ) ) ? $buffer[0] : '' ) );

						self::$pos = 0;

						return array_pop( $buffer );
					}

					return array_pop( $buffer );
				}
				if ( count( $buffer ) > 1 ) {
					return array_pop( $buffer );
				}
				$buffer = explode( self::SEPARATOR, self::read( self::$buffer_size, $file_or_handle ) . ( ( isset( $buffer[0] ) ) ? $buffer[0] : '' ) );
			}
		}

		/**
		 * Writes temporary file used lated on to show the content of the error log (in reverse order and truncated to the last couple of errors)
		 *
		 * @param string $line - The line to be written.
		 *
		 * @return void
		 *
		 * @since latest
		 */
		public static function write_temp_file( string $line ) {
			if ( null === self::$temp_handle ) {
				self::$temp_handle = fopen( 'php://temp', 'w+' );
			}

			fwrite( self::$temp_handle, $line );
		}

		/**
		 * Reads the contents of the temp file and returns the contents.
		 *
		 * @return void
		 *
		 * @since latest
		 */
		public static function read_temp_file() {
			if ( \is_resource( self::$temp_handle ) && ( 'handle' === get_resource_type( self::$temp_handle ) || 'stream' === get_resource_type( self::$temp_handle ) ) ) {
				rewind( self::$temp_handle ); // resets the position of pointer.

				echo fread( self::$temp_handle, fstat( self::$temp_handle )['size'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread, WordPress.Security.EscapeOutput.OutputNotEscaped

				fclose( self::$temp_handle );
			}
		}
	}
}
