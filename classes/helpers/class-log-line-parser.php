<?php
/**
 * Class: Reads line of the PHP error log and parses it.
 *
 * Helper class to properly extract and determine an error from error log.
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

if ( ! class_exists( '\ADVAN\Helpers\Log_Line_Parser' ) ) {
	/**
	 * Responsible for parsing lines of logs.
	 *
	 * @since 1.1.0
	 */
	class Log_Line_Parser {

		public const TIMESTAMP_TRANSIENT = 'advan_timestamp';
		public const LINES_TRANSIENT     = 'advan_newer_lines';

		/**
		 * Holds the last timestamp read from the log file.
		 *
		 * @var string
		 *
		 * @since 1.1.0
		 */
		private static $last_timestamp = null;

		/**
		 * Holds the last parsed timestamp from previous (if any) reading.
		 *
		 * @var string
		 *
		 * @since 1.1.0
		 */
		private static $last_parsed_timestamp = null;

		/**
		 * Stores the newest lines read from the log file.
		 *
		 * @var integer
		 *
		 * @since 1.1.0
		 */
		private static $newer_lines = 0;

		/**
		 * Parses a line from the PHP error log.
		 *
		 * @param string $line - The line to parse.
		 *
		 * @return array An associative array containing the parsed data.
		 *
		 * @since 1.1.0
		 */
		public static function parse_php_error_log_line( string $line ) {
			$line       = rtrim( $line );
			$timestamp  = null;
			$message    = $line;
			$source     = '';
			$level      = '';
			$context    = null;
			$error_line = null;
			$error_file = null;

			// We expect log entries to be structured like this: "[date-and-time] Optional severity: error message".
			$pattern = '/
                ^(?:\[(?P<timestamp>[\w \-+:\/]{6,50}?)\]\ )?
                (?P<message>
                    (?:(?:(?P<source>PHP|WordPress\ database)\ )?(?P<severity>[a-zA-Z][a-zA-Z ]{3,40}?):?\ )?
                .+)$
            /x';

			if ( preg_match( $pattern, $line, $matches ) ) {
				$message = $matches['message'];

				if ( ! empty( $matches['timestamp'] ) ) {
					// Attempt to parse the timestamp, if any. Timestamp format can vary by server.
					$parsed_timestamp = strtotime( $matches['timestamp'] );
					if ( ! empty( $parsed_timestamp ) ) {
						$timestamp = $parsed_timestamp;

						if ( $timestamp > self::$last_timestamp ) {
							self::$last_timestamp = $timestamp;
						}

						if ( (int) self::get_last_parsed_timestamp() < $timestamp ) {
							++self::$newer_lines;
						}
					}
				}

				if ( ! empty( $matches['severity'] ) && ! empty( $matches['source'] ) ) {
					// Parse the severity level.
					$level = strtolower( trim( $matches['severity'] ) );
				}

				if ( ! empty( $matches['source'] ) ) {
					// Parse the severity level.
					$source = strtolower( trim( $matches['source'] ) );
				}

				/*
				Attempt to extract the file name and line number from the message.
				*
				* spprintf(&log_buffer, 0, "PHP %s:  %s in %s on line %" PRIu32, error_type_str, buffer, error_filename, error_lineno);
				php_log_err_with_severity(log_buffer, syslog_type_int);

				zend_error_va(severity, (file && ZSTR_LEN(file) > 0) ? ZSTR_VAL(file) : NULL, line,
				"Uncaught %s\n  thrown", ZSTR_VAL(str));
				*/
				if ( preg_match( '/(?:in\s+([^\s]+(?:[\\\\\/][^\s]+)*)\s+on\s+line\s+(\d+)|([^\s]+(?:[\\\\\/][^\s:]+)*)[:](\d+))/', $message, $matches ) ) {

					$error_file = $matches[1] ? $matches[1] : $matches[3];

					/**
					 * If file is outside web root or somewhere where restrictions are in place, that will trigger warnings in PHP, so lets suppress it.
					 */
					if ( @\is_file( $error_file ) ) {
						// If the file exists, we can use it.

						$error_line = $matches[2] ? $matches[2] : $matches[4];
					} else {
						$error_file = null;
					}
				}

				// Does this line contain contextual data for another error?
				$context_prefix  = '[ELM_context_';
				$trimmed_message = trim( $message );
				if ( substr( $trimmed_message, 0, strlen( $context_prefix ) ) === $context_prefix ) {
					$context = self::parse_context_line( $trimmed_message );
				}
			}

			return array(
				'message'        => $message,
				'timestamp'      => $timestamp,
				'severity'       => $level,
				'source'         => $source,
				'isContext'      => ( null !== $context ),
				'contextPayload' => $context,
				'error_line'     => $error_line,
				'error_file'     => $error_file,
			);
		}

		/**
		 * Parses the context line from the log message.
		 *
		 * @param string $message - The log message.
		 *
		 * @return array|null - The parsed context or null if not found.
		 *
		 * @since 1.1.0
		 */
		private static function parse_context_line( $message ) {
			if ( ! preg_match( '@^\[(ELM_context_\d{1,8}?)\]@', $message, $matches ) ) {
				return null;
			}

			$end_tag          = '[/' . $matches[1] . ']';
			$end_tag_position = strrpos( $message, $end_tag );
			if ( false === $end_tag_position ) {
				return null;
			}

			$serialized_context = substr(
				$message,
				strlen( $matches[0] ),
				$end_tag_position - strlen( $matches[0] )
			);
			$context            = @json_decode( $serialized_context, true ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

			if ( ! is_array( $context ) ) {
				return null;
			}

			if ( ! isset( $context['parentEntryPosition'] ) ) {
				$context['parentEntryPosition'] = 'next';
			}
			return $context;
		}

		/**
		 * Parses a line from the PHP error log with stack trace.
		 *
		 * @param string $message    - The line to parse.
		 * @param bool   $is_last_line - Whether this is the last line of a stack trace.
		 *
		 * @return array An associative array containing the parsed data.
		 *
		 * @since 1.1.0
		 */
		public static function parse_php_error_log_stack_line( $message, $is_last_line = false ) {
			// It's usually "#123 C:\path\to\plugin.php(456): functionCallHere()".
			// The last line of a very long entry can be truncated.
			if ( preg_match(
				'@^\#(?P<index>\d++)\s  # Stack frame index.
			(?:
			    (?P<source>
			        \[internal\sfunction\]
			        | 
			        (?P<file>
			             (?:phar://)?          # PHAR archive prefix (optional).
			             (?:[a-zA-Z]:)?        # Drive letter (optional).
			             ([^:?*<>{}]+)?           # File path.
			        ) \((?P<line>\d{1,6})?\)    # Line number.
			    ):
			    | (?P<main>{main})\s*?$
			)@x',
				$message,
				$matches
			) ) {
				$item = array();

				if ( ! empty( $matches['source'] ) && ! empty( $matches[0] ) ) {
					$item['call'] = ltrim( substr( $message, strlen( $matches[0] ) ) );
				} elseif ( ! empty( $matches['main'] ) ) {
					$item['call'] = $matches['main'];
				}

				if ( ! empty( $matches['file'] ) ) {
					$item['file'] = $matches['file'];
				} elseif ( ! empty( $matches['source'] ) ) {
					$item['file'] = $matches['source'];
				}

				if ( ! empty( $matches['line'] ) ) {
					$item['line'] = $matches['line'];
				} elseif ( empty( $matches['main'] ) ) {
					// Line is missing from log (unknown reason).
					$item['line'] = 'Line Unknown';
				}

				return $item;
			} elseif (
			// Simplified parsing for truncated stack trace entries.
			$is_last_line
			&& preg_match( '@^\#(?P<index>\d++)\s@', $message, $matches )
			&& preg_match( '@\son\sline\s\d++$@', $message )
			) {
				$item = array( 'call' => trim( substr( $message, strlen( $matches[0] ) ) ) );
				return $item;
			} elseif ( false === \str_starts_with( $message, '[' ) ) {
				// Some modules are writing in error log with new lines - this logic tries to cover that case.
				$item = array( 'call' => trim( $message ) . "\n" );
				return $item;
			} else {
				return self::parse_php_error_log_line( (string) $message );
			}
		}

		/**
		 * Parses a line from the PHP error log with stack trace.
		 *
		 * @param string $line - The line to parse.
		 *
		 * @return bool|null
		 *
		 * @since 1.1.0
		 */
		public static function parse_entry_with_stack_trace( string $line ) {
			if ( false !== \strpos( $line, 'throw' ) || false !== \strpos( $line, 'Stack trace' ) ) {
				return \null;
			}

			return true;
		}

		/**
		 * Stores the last known Timestamp as transient
		 *
		 * @return void
		 *
		 * @since 1.1.0
		 */
		public static function store_last_parsed_timestamp() {
			if ( null !== self::$last_timestamp ) {

				if ( false === self::get_last_parsed_timestamp() || self::$last_timestamp > (int) self::get_last_parsed_timestamp() ) {
					self::$last_parsed_timestamp = self::$last_timestamp;
					\set_transient( self::TIMESTAMP_TRANSIENT, self::$last_timestamp, 600 );
				}

				if ( 1 <= ( $count = self::get_lines_to_show_interface() ) ) { // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.Found, Squiz.PHP.DisallowMultipleAssignments.FoundInControlStructure
					?>
					<script>
						if (jQuery('#advan-errors-menu .update-count').length) {
							jQuery('#advan-errors-menu').show();
							jQuery('#advan-errors-menu .update-count').html('<?php echo \esc_attr( \number_format_i18n( $count ) ); ?>');
						}
					</script>
					<?php
				}
			}
		}

		/**
		 * Returns the last known Timestamp transient.
		 *
		 * @return bool|string
		 *
		 * @since 1.1.0
		 */
		public static function get_last_parsed_timestamp() {
			if ( null === self::$last_parsed_timestamp ) {
				self::$last_parsed_timestamp = \get_transient( self::TIMESTAMP_TRANSIENT );
				if ( false === self::$last_parsed_timestamp ) {

					return self::$last_parsed_timestamp;
				}
			}

			return self::$last_parsed_timestamp;
		}

		/**
		 * Clears the variable and deletes the transient.
		 *
		 * @return void
		 *
		 * @since 1.1.0
		 */
		public static function delete_last_parsed_timestamp() {
			self::$last_parsed_timestamp = null;

			\delete_transient( self::TIMESTAMP_TRANSIENT );
		}

		/**
		 * Returns newer errors from last log parsing.
		 *
		 * @return int
		 *
		 * @since 1.1.0
		 */
		public static function get_newer_lines(): int {
			$lines             = (int) self::$newer_lines;
			self::$newer_lines = 0;

			return $lines;
		}

		/**
		 * Checks newer lines stored in the class var, if negative - returns the lines parsed from the log file, last time the log was parsed. If empty - extracts the data from transient, otherwise stores the newly lines in the transient and returns them
		 *
		 * @return int
		 *
		 * @since 1.7.5
		 */
		public static function get_lines_to_show_interface(): int {
			$lines = self::get_newer_lines();

			if ( 0 >= $lines ) {
				$lines = (int) \get_transient( self::LINES_TRANSIENT );
			} else {
				\set_transient( self::LINES_TRANSIENT, $lines, 600 );
			}

			return $lines;
		}
	}
}
