<?php
/**
 * Class: File functions helper file.
 *
 * Helper class used for extraction / loading classes.
 *
 * @package advanced-analytics
 *
 * @since 1.1.0
 */

declare(strict_types=1);

namespace ADVAN\Helpers;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\ADVAN\Helpers\File_Helper' ) ) {
	/**
	 * Responsible for file operations.
	 *
	 * @since 1.1.0
	 */
	class File_Helper {

		/**
		 * Keeps the string representation of the last error
		 *
		 * @var string
		 *
		 * @since 1.1.0
		 */
		private static $last_error = '';

		/**
		 * Creates index file in the given directory.
		 *
		 * @param string $path - Path in which index file should be created. If does not exist - the method will try to create it.
		 *
		 * @return boolean
		 *
		 * @since 1.1.0
		 */
		public static function create_index_file( string $path ): bool {
			// Check if directory exists.
			$path = \trailingslashit( $path );

			return self::write_to_file( $path . 'index.php', '<?php /*[' . ADVAN_NAME . ' plugin: This file was auto-generated to prevent directory listing ]*/ exit;' );
		}

		/**
		 * Creates htaccess file in given directory.
		 *
		 * @param string $path - Path in which htaccess file should be created. If does not exist - the method will try to create it.
		 *
		 * @return boolean
		 *
		 * @since 1.1.0
		 */
		public static function create_htaccess_file( string $path ): bool {
			// Check if directory exists.
			$path = trailingslashit( $path );

			return self::write_to_file( $path . '.htaccess', 'Deny from all' );
		}

		/**
		 * Writes content to given file
		 *
		 * @param string  $filename - Full path to the file.
		 * @param string  $content - Content to write into the file.
		 * @param boolean $append - Appends the content to the file if it exists.
		 *
		 * @return boolean
		 *
		 * @since 1.1.0
		 */
		public static function write_to_file( string $filename, string $content, bool $append = false ): bool {
			global $wp_filesystem;

			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();

			$logging_dir = dirname( $filename );

			$result = false;

			if ( ! is_dir( $logging_dir ) ) {
				if ( false === \wp_mkdir_p( $logging_dir ) ) {
					self::$last_error = new \WP_Error(
						'mkdir_failed',
						sprintf(
								/* translators: %s: Directory path. */
							__( 'Unable to create directory %s. Is its parent directory writable by the server?' ),
							esc_html( $result )
						)
					);

					return $result;
				}
			}

			$file_path = $filename;
			if ( ! $wp_filesystem->exists( $file_path ) || $append ) {
				$result = $wp_filesystem->put_contents( $file_path, $content );
			} elseif ( $append ) {
				$existing_content = $wp_filesystem->get_contents( $file_path );
				$result           = $wp_filesystem->put_contents( $file_path, $existing_content . $content );
			}

			if ( false === $result ) {
				self::$last_error = new \WP_Error(
					'write_failed',
					sprintf(
								/* translators: %s: Directory path. */
						__( 'Trying to write to the file %s failed.' ),
						esc_html( $file_path )
					)
				);
			}

			return (bool) $result;
		}

		/**
		 * Getter for the last error variable of the class
		 *
		 * @return string
		 *
		 * @since 1.1.0
		 */
		public static function get_last_error(): string {
			return self::$last_error;
		}

		/**
		 * Returns the file size in human readable format.
		 *
		 * @param string $filename - The name of the file (including path) to check the size of.
		 *
		 * @return string
		 *
		 * @since 1.1.0
		 */
		public static function format_file_size( $filename ): string {

			if ( \is_string( $filename ) && \file_exists( $filename ) ) {

				$size = filesize( $filename );

				return self::show_size( $size );
			}

			return '0 B';
		}

		/**
		 * Shows formatted (human readable) size
		 *
		 * @param int $size - The size (in bytes) to format.
		 *
		 * @return string
		 *
		 * @since 2.1.2
		 */
		public static function show_size( $size ) {

			if ( 0 <= $size ) {

				$units          = array( 'B', 'KB', 'MB', 'GB', 'TB' );
				$formatted_size = $size;

				$units_length = count( $units ) - 1;

				for ( $i = 0; $size >= 1024 && $i < $units_length; $i++ ) {
					$size          /= 1024;
					$formatted_size = round( $size, 2 );
				}

				return $formatted_size . ' ' . $units[ $i ];
			}

			return '0 B';
		}

		/**
		 * Builds and returns download link for log file.
		 *
		 * @return string
		 *
		 * @since 1.1.0
		 */
		public static function download_link(): string {
			$url = \add_query_arg(
				array(
					'action'   => 'advanced_analytics_download_log_file',
					'_wpnonce' => \wp_create_nonce( 'advan-plugin-data', 'advanced-analytics-security' ),
				),
				\admin_url( 'admin-ajax.php' )
			);

			return $url;
		}

		/**
		 * Checks the file and initiates the download
		 *
		 * @param string $file_path - The full path to the file.
		 *
		 * @return void
		 *
		 * @since 1.1.0
		 */
		public static function download( $file_path ) {
			set_time_limit( 0 );
			@ini_set( 'memory_limit', '512M' );
			if ( ! empty( $file_path ) ) {
				$file_info            = pathinfo( $file_path );
				$file_name            = $file_info['basename'];
				$file_extension       = $file_info['extension'];
				$default_content_type = 'application/octet-stream';

				// to find and use specific content type, check out this IANA page : http://www.iana.org/assignments/media-types/media-types.xhtml .
				if ( array_key_exists( $file_extension, self::mime_types() ) ) {
					$content_type = self::mime_types()[ $file_extension ];
				} else {
					$content_type = $default_content_type;
				}
				if ( \file_exists( $file_path ) ) {
					$size   = \filesize( $file_path );
					$offset = 0;
					$length = $size;
					// HEADERS FOR PARTIAL DOWNLOAD FACILITY BEGINS.
					if ( isset( $_SERVER['HTTP_RANGE'] ) ) {
						preg_match( '/bytes=(\d+)-(\d+)?/', $_SERVER['HTTP_RANGE'], $matches ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
						$offset  = intval( $matches[1] );
						$length  = intval( $matches[2] ) - $offset;
						$fhandle = fopen( $file_path, 'r' );
						fseek( $fhandle, $offset ); // seek to the requested offset, this is 0 if it's not a partial content request.
						$data = fread( $fhandle, $length );
						fclose( $fhandle );
						header( 'HTTP/1.1 206 Partial Content' );
						header( 'Content-Range: bytes ' . $offset . '-' . ( $offset + $length ) . '/' . $size );
					}//HEADERS FOR PARTIAL DOWNLOAD FACILITY BEGINS.
					// USUAL HEADERS FOR DOWNLOAD.
					header( 'Content-Disposition: attachment;filename=' . $file_name );
					header( 'Content-Type: ' . $content_type );
					header( 'Accept-Ranges: bytes' );
					header( 'Pragma: public' );
					header( 'Expires: -1' );
					header( 'Cache-Control: no-cache' );
					header( 'Cache-Control: public, must-revalidate, post-check=0, pre-check=0' );
					header( 'Content-Length: ' . filesize( $file_path ) );
					$chunksize = 8 * ( 1024 * 1024 ); // 8MB (highest possible fread length)
					if ( $size > $chunksize ) {
						$handle = fopen( $file_path, 'rb' );
						$buffer = '';
						while ( ! feof( $handle ) && ( connection_status() === CONNECTION_NORMAL ) ) {
							$buffer = fread( $handle, $chunksize );
							print $buffer; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							ob_flush();
							flush();
						}
						if ( connection_status() !== CONNECTION_NORMAL ) {
							echo 'Connection aborted';
						}
						fclose( $handle );
					} else {
						ob_clean();
						flush();
						readfile( $file_path );
					}
				} else {
					echo 'File does not exist!';
				}
			} else {
				echo 'There is no file to download!';
			}
		}

		/**
		 * Function to get correct MIME type for download
		 *
		 * @return array
		 *
		 * @since 1.1.0
		 */
		public static function mime_types(): array {
			/* Just add any required MIME type if you are going to download something not listed here.*/
			$mime_types = array(
				'323'     => 'text/h323',
				'acx'     => 'application/internet-property-stream',
				'ai'      => 'application/postscript',
				'aif'     => 'audio/x-aiff',
				'aifc'    => 'audio/x-aiff',
				'aiff'    => 'audio/x-aiff',
				'asf'     => 'video/x-ms-asf',
				'asr'     => 'video/x-ms-asf',
				'asx'     => 'video/x-ms-asf',
				'au'      => 'audio/basic',
				'avi'     => 'video/x-msvideo',
				'axs'     => 'application/olescript',
				'bas'     => 'text/plain',
				'bcpio'   => 'application/x-bcpio',
				'bin'     => 'application/octet-stream',
				'bmp'     => 'image/bmp',
				'c'       => 'text/plain',
				'cat'     => 'application/vnd.ms-pkiseccat',
				'cdf'     => 'application/x-cdf',
				'cer'     => 'application/x-x509-ca-cert',
				'class'   => 'application/octet-stream',
				'clp'     => 'application/x-msclip',
				'cmx'     => 'image/x-cmx',
				'cod'     => 'image/cis-cod',
				'cpio'    => 'application/x-cpio',
				'crd'     => 'application/x-mscardfile',
				'crl'     => 'application/pkix-crl',
				'crt'     => 'application/x-x509-ca-cert',
				'csh'     => 'application/x-csh',
				'css'     => 'text/css',
				'dcr'     => 'application/x-director',
				'der'     => 'application/x-x509-ca-cert',
				'dir'     => 'application/x-director',
				'dll'     => 'application/x-msdownload',
				'dms'     => 'application/octet-stream',
				'doc'     => 'application/msword',
				'dot'     => 'application/msword',
				'dvi'     => 'application/x-dvi',
				'dxr'     => 'application/x-director',
				'eps'     => 'application/postscript',
				'etx'     => 'text/x-setext',
				'evy'     => 'application/envoy',
				'exe'     => 'application/octet-stream',
				'fif'     => 'application/fractals',
				'flr'     => 'x-world/x-vrml',
				'gif'     => 'image/gif',
				'gtar'    => 'application/x-gtar',
				'gz'      => 'application/x-gzip',
				'h'       => 'text/plain',
				'hdf'     => 'application/x-hdf',
				'hlp'     => 'application/winhlp',
				'hqx'     => 'application/mac-binhex40',
				'hta'     => 'application/hta',
				'htc'     => 'text/x-component',
				'htm'     => 'text/html',
				'html'    => 'text/html',
				'htt'     => 'text/webviewhtml',
				'ico'     => 'image/x-icon',
				'ief'     => 'image/ief',
				'iii'     => 'application/x-iphone',
				'ins'     => 'application/x-internet-signup',
				'isp'     => 'application/x-internet-signup',
				'jfif'    => 'image/pipeg',
				'jpe'     => 'image/jpeg',
				'jpeg'    => 'image/jpeg',
				'jpg'     => 'image/jpeg',
				'js'      => 'application/x-javascript',
				'latex'   => 'application/x-latex',
				'lha'     => 'application/octet-stream',
				'lsf'     => 'video/x-la-asf',
				'lsx'     => 'video/x-la-asf',
				'lzh'     => 'application/octet-stream',
				'm13'     => 'application/x-msmediaview',
				'm14'     => 'application/x-msmediaview',
				'm3u'     => 'audio/x-mpegurl',
				'man'     => 'application/x-troff-man',
				'mdb'     => 'application/x-msaccess',
				'me'      => 'application/x-troff-me',
				'mht'     => 'message/rfc822',
				'mhtml'   => 'message/rfc822',
				'mid'     => 'audio/mid',
				'mny'     => 'application/x-msmoney',
				'mov'     => 'video/quicktime',
				'movie'   => 'video/x-sgi-movie',
				'mp2'     => 'video/mpeg',
				'mp3'     => 'audio/mpeg',
				'mpa'     => 'video/mpeg',
				'mpe'     => 'video/mpeg',
				'mpeg'    => 'video/mpeg',
				'mpg'     => 'video/mpeg',
				'mpp'     => 'application/vnd.ms-project',
				'mpv2'    => 'video/mpeg',
				'ms'      => 'application/x-troff-ms',
				'mvb'     => 'application/x-msmediaview',
				'nws'     => 'message/rfc822',
				'oda'     => 'application/oda',
				'p10'     => 'application/pkcs10',
				'p12'     => 'application/x-pkcs12',
				'p7b'     => 'application/x-pkcs7-certificates',
				'p7c'     => 'application/x-pkcs7-mime',
				'p7m'     => 'application/x-pkcs7-mime',
				'p7r'     => 'application/x-pkcs7-certreqresp',
				'p7s'     => 'application/x-pkcs7-signature',
				'pbm'     => 'image/x-portable-bitmap',
				'pdf'     => 'application/pdf',
				'pfx'     => 'application/x-pkcs12',
				'pgm'     => 'image/x-portable-graymap',
				'pko'     => 'application/ynd.ms-pkipko',
				'pma'     => 'application/x-perfmon',
				'pmc'     => 'application/x-perfmon',
				'pml'     => 'application/x-perfmon',
				'pmr'     => 'application/x-perfmon',
				'pmw'     => 'application/x-perfmon',
				'pnm'     => 'image/x-portable-anymap',
				'pot'     => 'application/vnd.ms-powerpoint',
				'ppm'     => 'image/x-portable-pixmap',
				'pps'     => 'application/vnd.ms-powerpoint',
				'ppt'     => 'application/vnd.ms-powerpoint',
				'prf'     => 'application/pics-rules',
				'ps'      => 'application/postscript',
				'pub'     => 'application/x-mspublisher',
				'qt'      => 'video/quicktime',
				'ra'      => 'audio/x-pn-realaudio',
				'ram'     => 'audio/x-pn-realaudio',
				'ras'     => 'image/x-cmu-raster',
				'rgb'     => 'image/x-rgb',
				'rmi'     => 'audio/mid',
				'roff'    => 'application/x-troff',
				'rtf'     => 'application/rtf',
				'rtx'     => 'text/richtext',
				'scd'     => 'application/x-msschedule',
				'sct'     => 'text/scriptlet',
				'setpay'  => 'application/set-payment-initiation',
				'setreg'  => 'application/set-registration-initiation',
				'sh'      => 'application/x-sh',
				'shar'    => 'application/x-shar',
				'sit'     => 'application/x-stuffit',
				'snd'     => 'audio/basic',
				'spc'     => 'application/x-pkcs7-certificates',
				'spl'     => 'application/futuresplash',
				'src'     => 'application/x-wais-source',
				'sst'     => 'application/vnd.ms-pkicertstore',
				'stl'     => 'application/vnd.ms-pkistl',
				'stm'     => 'text/html',
				'svg'     => 'image/svg+xml',
				'sv4cpio' => 'application/x-sv4cpio',
				'sv4crc'  => 'application/x-sv4crc',
				't'       => 'application/x-troff',
				'tar'     => 'application/x-tar',
				'tcl'     => 'application/x-tcl',
				'tex'     => 'application/x-tex',
				'texi'    => 'application/x-texinfo',
				'texinfo' => 'application/x-texinfo',
				'tgz'     => 'application/x-compressed',
				'tif'     => 'image/tiff',
				'tiff'    => 'image/tiff',
				'tr'      => 'application/x-troff',
				'trm'     => 'application/x-msterminal',
				'tsv'     => 'text/tab-separated-values',
				'txt'     => 'text/plain',
				'uls'     => 'text/iuls',
				'ustar'   => 'application/x-ustar',
				'vcf'     => 'text/x-vcard',
				'vrml'    => 'x-world/x-vrml',
				'wav'     => 'audio/x-wav',
				'wcm'     => 'application/vnd.ms-works',
				'wdb'     => 'application/vnd.ms-works',
				'wks'     => 'application/vnd.ms-works',
				'wmf'     => 'application/x-msmetafile',
				'wps'     => 'application/vnd.ms-works',
				'wri'     => 'application/x-mswrite',
				'wrl'     => 'x-world/x-vrml',
				'wrz'     => 'x-world/x-vrml',
				'xaf'     => 'x-world/x-vrml',
				'xbm'     => 'image/x-xbitmap',
				'xla'     => 'application/vnd.ms-excel',
				'xlc'     => 'application/vnd.ms-excel',
				'xlm'     => 'application/vnd.ms-excel',
				'xls'     => 'application/vnd.ms-excel',
				'xlt'     => 'application/vnd.ms-excel',
				'xlw'     => 'application/vnd.ms-excel',
				'xof'     => 'x-world/x-vrml',
				'xpm'     => 'image/x-xpixmap',
				'xwd'     => 'image/x-xwindowdump',
				'z'       => 'application/x-compress',
				'rar'     => 'application/x-rar-compressed',
				'zip'     => 'application/zip',
			);
			return $mime_types;
		}

		/**
		 * Get full file path to the site's wp-config.php file.
		 *
		 * @since 1.1.0
		 *
		 * @return string Full path to the wp-config.php file or a blank string if modifications for the file are disabled.
		 */
		public static function get_wp_config_file_path() {

			if ( file_exists( ABSPATH . 'wp-config.php' ) ) {

				/** The config file resides in ABSPATH */
				$path = ABSPATH . 'wp-config.php';

			} elseif ( @file_exists( dirname( ABSPATH ) . '/wp-config.php' ) && ! @file_exists( dirname( ABSPATH ) . '/wp-settings.php' ) ) {

				/** The config file resides one level above ABSPATH */
				$path = dirname( ABSPATH ) . '/wp-config.php';

			} else {
				$path = '';
			}

			/**
			 * Gives the ability to manually change the path to the config file.
			 *
			 * @param string - The current value for WP config file path.
			 *
			 * @since 1.1.0
			 */
			$path = \apply_filters( ADVAN_TEXTDOMAIN . 'config_file_path', (string) $path );

			return $path;
		}

		/**
		 * Just returns randomized string.
		 *
		 * @return string
		 *
		 * @since 1.1.0
		 */
		public static function generate_random_file_name() {

			$random_string = uniqid();

			return $random_string;
		}

		/**
		 * Checks if given file is valid PHP file.
		 *
		 * @param string $file_name - The name of the file (including path) to check the size of.
		 *
		 * @return boolean
		 *
		 * @since 1.8.2
		 */
		public static function is_file_valid_php( string $file_name ): bool {

			if ( ! file_exists( $file_name ) ) {
				return false;
			}

			// Define allowed file extensions and MIME types.
			$allowed_types      = array( 'php' );
			$allowed_mime_types = array( 'text/x-php' );

			$finfo     = finfo_open( FILEINFO_MIME_TYPE );
			$mime_type = finfo_file( $finfo, $file_name );
			$extension = strtolower( pathinfo( $file_name, PATHINFO_EXTENSION ) );

			if ( ! in_array( $extension, $allowed_types, true ) || ! in_array( $mime_type, $allowed_mime_types, true ) ) {
				return false;
			}

			return true;
		}

		/**
		 * Checks if the file is writable.
		 *
		 * @param string $file_path - The full path to the file.
		 *
		 * @return boolean
		 *
		 * @since 1.9.2
		 */
		public static function is_writable( string $file_path ): bool {
			// Check if the file exists.
			if ( ! file_exists( $file_path ) ) {
				return false;
			}

			// Check if the file is writable.
			if ( is_writable( $file_path ) ) {
				return true;
			}

			return false;

			/*
			// // If not writable, check if the parent directory is writable.
			// $parent_dir = dirname( $file_path );

			// return is_writable( $parent_dir );
			*/
		}

		/**
		 * Removes empty lines from file using as less memory as possible.
		 *
		 * @param string $file_path - The file (absolute) to remove empty lines from.
		 *
		 * @return bool
		 *
		 * @since 1.9.2
		 */
		public static function remove_empty_lines_low_memory( string $file_path ): bool {
			// Open the input file and a temporary output file.
			$in = @fopen( $file_path, 'r' );
			if ( ! $in ) {
				return false;
			}

			$temp_path = tempnam( sys_get_temp_dir(), 'php_' );
			$out       = @fopen( $temp_path, 'w' );
			if ( ! $out ) {
				fclose( $in );
				return false;
			}

			// Process file line by line.
			while ( ( $line = fgets( $in ) ) !== false ) {
				if ( trim( $line ) !== '' ) {
					fwrite( $out, $line );
				}
			}

			fclose( $in );
			fclose( $out );

			// Replace the original file with the filtered one.
			return rename( $temp_path, $file_path );
		}
	}
}
