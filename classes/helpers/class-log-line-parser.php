<?php
/**
 * Class: Determine the context in which the plugin is executed.
 *
 * Helper class to determine the proper status of the request.
 *
 * @package advanced-analytics
 *
 * @since 2.0.0
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
	 * @since 1.0.0
	 */
	class Log_Line_Parser {

		public const TIMESTAMP_TRANSIENT = 'advan_timestamp';

		/**
		 * Holds the last timestamp read from the log file.
		 *
		 * @var string
		 *
		 * @since latest
		 */
		private static $last_timestamp = null;

		/**
		 * Holds the last parsed timestamp from previous (if any) reading.
		 *
		 * @var string
		 *
		 * @since latest
		 */
		private static $last_parsed_timestamp = null;

		/**
		 * Stores the newest lines read from the log file.
		 *
		 * @var integer
		 *
		 * @since latest
		 */
		private static $newer_lines = 0;

		public static function parse_php_error_log_line( string $line ) {
			$line      = rtrim( $line );
			$timestamp = null;
			$message   = $line;
			$level     = '';
			$context   = null;

			/*
			TODO: Attempt to extract the file name and line number from the message.
			 *
			 * spprintf(&log_buffer, 0, "PHP %s:  %s in %s on line %" PRIu32, error_type_str, buffer, error_filename, error_lineno);
			php_log_err_with_severity(log_buffer, syslog_type_int);

			zend_error_va(severity, (file && ZSTR_LEN(file) > 0) ? ZSTR_VAL(file) : NULL, line,
			"Uncaught %s\n  thrown", ZSTR_VAL(str));
			 */

			// We expect log entries to be structured like this: "[date-and-time] Optional severity: error message".
			$pattern = '/
                ^(?:\[(?P<timestamp>[\w \-+:\/]{6,50}?)\]\ )?
                (?P<message>
                    (?:(?:PHP\ )?(?P<severity>[a-zA-Z][a-zA-Z ]{3,40}?):\ )?
                .+)$
            /x';

			if ( preg_match( $pattern, $line, $matches ) ) {
				$message = $matches['message'];

				if ( ! empty( $matches['timestamp'] ) ) {
					// Attempt to parse the timestamp, if any. Timestamp format can vary by server.
					$parsed_timestamp = strtotime( $matches['timestamp'] );
					if ( ! empty( $parsed_timestamp ) ) {
						$timestamp = $parsed_timestamp;

						self::$last_timestamp = $timestamp;

						if ( (int) self::get_last_parsed_timestamp() < self::$last_timestamp ) {
							++self::$newer_lines;
						}
					}
				}

				if ( ! empty( $matches['severity'] ) ) {
					// Parse the severity level.
					$level = strtolower( trim( $matches['severity'] ) );
				}

				// Does this line contain contextual data for another error?
				$contextPrefix  = '[ELM_context_';
				$trimmedMessage = trim( $message );
				if ( substr( $trimmedMessage, 0, strlen( $contextPrefix ) ) === $contextPrefix ) {
					$context = $this->parseContextLine( $trimmedMessage );
				}
			}

			return array(
				'message'        => $message,
				'timestamp'      => $timestamp,
				'severity'       => $level,
				'isContext'      => ( null !== $context ),
				'contextPayload' => $context,
			);
		}

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
			} else {
				return self::parse_php_error_log_line( (string) $message );
			}
		}

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
		 * @since latest
		 */
		public static function store_last_parsed_timestamp() {
			if ( null !== self::$last_timestamp ) {

				if ( false === self::get_last_parsed_timestamp() ) {
					\set_transient( self::TIMESTAMP_TRANSIENT, self::$last_timestamp - 1, 600 ); // get back 1 second - sometimes there delays.
				}

				if ( 1 <= ( $count = self::get_newer_lines() ) ) { // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.Found
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
		 * @return null|string
		 *
		 * @since latest
		 */
		public static function get_last_parsed_timestamp() {
			if ( null === self::$last_parsed_timestamp ) {
				self::$last_parsed_timestamp = \get_transient( self::TIMESTAMP_TRANSIENT );
				if ( false === self::$last_parsed_timestamp ) {
					self::$last_parsed_timestamp = null;
					return false;
				}
			}

			return self::$last_parsed_timestamp;
		}

		/**
		 * Clears the variable and deletes the transient.
		 *
		 * @return void
		 *
		 * @since latest
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
		 * @since latest
		 */
		public static function get_newer_lines(): int {
			$lines             = (int) self::$newer_lines;
			self::$newer_lines = 0;
			return $lines;
		}
	}
}
