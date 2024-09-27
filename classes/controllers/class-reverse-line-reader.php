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
			$line   = fgets( $handle );
			$result = $callback( $line );

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
	}
}
