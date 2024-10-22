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
		public static function read_file_from_end( $file_or_handle, $callback, $max_ines = 0, $pos = null ) {
			if ( null === $pos ) {
				self::$pos = -2;
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
			// Lets check the size and act aproperly.
			if ( null === $pos ) {
				fseek( $handle, 0, SEEK_END );
				$size = ftell( $handle );
				if ( 0 === (int) $size ) {
					fclose( $handle );
					return false;
				}
			}

			while ( true ) {
				fseek( $handle, self::$pos, SEEK_END );
				--self::$pos;
				$char = fgetc( $handle );
				if ( "\n" === $char ) {
					break;
				}
				if ( false === $char ) {
					fseek( $handle, 0 );
					break;
				}
			}
			$line = fgets( $handle );
			self::write_temp_file( $line );
			$result = $callback( $line, $pos );

			if ( false === $result ) {
				fclose( $handle );

				return;
			}
			if ( false === $char ) {
				return;
			}
			if ( $max_ines > 0 ) {
				--$max_ines;
				if ( 0 === $max_ines ) {
					fclose( $handle );

					return;
				}
			}

			self::read_file_from_end( $handle, $callback, $max_ines, self::$pos );
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

				echo fread( self::$temp_handle, fstat( self::$temp_handle )['size'] ); // I am freaking awesome.

				fclose( self::$temp_handle );
			}
		}
	}
}
