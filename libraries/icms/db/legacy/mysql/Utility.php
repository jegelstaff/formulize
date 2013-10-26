<?php
/**
 * Legacy MySQL utilities
 *
 * @category	ICMS
 * @package		Database
 * @subpackage  Legacy
 * @author      Kazumi Ono  <onokazu@xoops.org>
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @version		SVN: $Id: Utility.php 20145 2010-09-16 02:46:12Z skenow $
 */

/**
 * Provide some utility methods for databases
 *
 * @category	ICMS
 * @package		Database
 * @subpackage	Legacy
 * @author      Kazumi Ono  <onokazu@xoops.org>
 */
class icms_db_legacy_mysql_Utility {

	/**
	 * Creates a new utility object
	 */
	public function __construct(){
	}

	/**
	 * Function from phpMyAdmin (http://phpwizard.net/projects/phpMyAdmin/)
	 *
	 * Removes comment and splits large sql files into individual queries
	 *
	 * Last revision: September 23, 2001 - gandon
	 *
	 * @param   array    the split sql commands
	 * @param   string   the sql commands
	 * @return  boolean  always true
	 */
	public function splitMySqlFile(&$ret, $sql) {
		$sql               = trim($sql);
		$sql_len           = strlen($sql);
		$char              = '';
		$string_start      = '';
		$in_string         = FALSE;

		for ($i = 0; $i < $sql_len; ++$i) {
			$char = $sql[$i];

			// We are in a string, check for not escaped end of
			// strings except for backquotes that can't be escaped
			if ($in_string) {
				for (;;) {
					$i = strpos($sql, $string_start, $i);
					// No end of string found -> add the current
					// substring to the returned array
					if (!$i) {
						$ret[] = $sql;
						return TRUE;
					} elseif ($string_start == '`' || $sql[$i-1] != '\\') {
					// Backquotes or no backslashes before
					// quotes: it's indeed the end of the
					// string -> exit the loop
						$string_start      = '';
						$in_string         = FALSE;
						break;
					} else {
					// one or more Backslashes before the presumed
					// end of string...
						// first checks for escaped backslashes
						$j                     = 2;
						$escaped_backslash     = FALSE;
						while ($i-$j > 0 && $sql[$i-$j] == '\\') {
							$escaped_backslash = !$escaped_backslash;
							$j++;
						}
						// ... if escaped backslashes: it's really the
						// end of the string -> exit the loop
						if ($escaped_backslash) {
							$string_start  = '';
							$in_string     = FALSE;
							break;
						} else {
							$i++;
						}
					}
				}
			} elseif ($char == ';') {
			// We are not in a string, first check for delimiter...
				// if delimiter found, add the parsed part to the returned array
				$ret[]    = substr($sql, 0, $i);
				$sql      = ltrim(substr($sql, min($i + 1, $sql_len)));
				$sql_len  = strlen($sql);
				if ($sql_len) {
					$i      = -1;
				} else {
					// The submited statement(s) end(s) here
					return TRUE;
				}
			} elseif (($char == '"') || ($char == '\'') || ($char == '`')) {
			// ... then check for start of a string,...
				$in_string    = TRUE;
				$string_start = $char;
			} elseif ($char == '#' || ($char == ' ' && $i > 1 && $sql[$i-2] . $sql[$i-1] == '--')) {
			// for start of a comment (and remove this comment if found)...
				// starting position of the comment depends on the comment type
				$start_of_comment = (($sql[$i] == '#') ? $i : $i-2);
				// if no "\n" exits in the remaining string, checks for "\r"
				// (Mac eol style)
				$end_of_comment   = (strpos(' ' . $sql, "\012", $i+2))
					? strpos(' ' . $sql, "\012", $i+2)
					: strpos(' ' . $sql, "\015", $i+2);
				if (!$end_of_comment) {
					// no eol found after '#', add the parsed part to the returned
					// array and exit
					// RMV fix for comments at end of file
					$last = trim(substr($sql, 0, $i-1));
					if (!empty($last)) {
						$ret[] = $last;
					}
					return TRUE;
				} else {
					$sql     = substr($sql, 0, $start_of_comment) . ltrim(substr($sql, $end_of_comment));
					$sql_len = strlen($sql);
					$i--;
				}
			}
		}

		// add any rest to the returned array
		if (!empty($sql) && trim($sql) != '') {
			$ret[] = $sql;
		}
		return TRUE;
	}

	/**
	 * Function from phpMyAdmin (http://phpwizard.net/projects/phpMyAdmin/)
	 *
	 * Removes comment and splits large sql files into individual queries
	 *
	 * Last revision: September 23, 2001 - gandon
	 *
	 * @param   array    the split sql commands
	 * @param   string   the sql commands
	 * @return  boolean  always true
	 */
	public function splitSqlFile(&$ret, $sql) {
		$sql               = trim($sql);
		$sql_len           = strlen($sql);
		$char              = '';
		$string_start      = '';
		$in_string         = FALSE;

		for ($i = 0; $i < $sql_len; ++$i) {
			$char = $sql[$i];

			// We are in a string, check for not escaped end of
			// strings except for backquotes that can't be escaped
			if ($in_string) {
				for (;;) {
					$i         = strpos($sql, $string_start, $i);
					// No end of string found -> add the current
					// substring to the returned array
					if (!$i) {
						$ret[] = $sql;
						return TRUE;
					} elseif ($string_start == '`' || $sql[$i-1] != '\\') {
					// Backquotes or no backslashes before
					// quotes: it's indeed the end of the
					// string -> exit the loop
						$string_start      = '';
						$in_string         = FALSE;
						break;
					} else {
					// one or more Backslashes before the presumed
					// end of string...
						// first checks for escaped backslashes
						$j                     = 2;
						$escaped_backslash     = FALSE;
						while ($i-$j > 0 && $sql[$i-$j] == '\\') {
							$escaped_backslash = !$escaped_backslash;
							$j++;
						}
						// ... if escaped backslashes: it's really the
						// end of the string -> exit the loop
						if ($escaped_backslash) {
							$string_start  = '';
							$in_string     = FALSE;
							break;
						} else {
						// ... else loop
							$i++;
						}
					}
				}
			} elseif ($char == ';') {
			// We are not in a string, first check for delimiter...
				// if delimiter found, add the parsed part to the returned array
				$ret[]    = substr($sql, 0, $i);
				$sql      = ltrim(substr($sql, min($i + 1, $sql_len)));
				$sql_len  = strlen($sql);
				if ($sql_len) {
					$i      = -1;
				} else {
					// The submited statement(s) end(s) here
					return TRUE;
				}
			} elseif (($char == '"') || ($char == '\'') || ($char == '`')) {
			// ... then check for start of a string,...
				$in_string    = TRUE;
				$string_start = $char;
			} elseif ($char == '#' || ($char == ' ' && $i > 1 && $sql[$i-2] . $sql[$i-1] == '--')) {
			// for start of a comment (and remove this comment if found)...
				// starting position of the comment depends on the comment type
				$start_of_comment = (($sql[$i] == '#') ? $i : $i-2);
				// if no "\n" exits in the remaining string, checks for "\r"
				// (Mac eol style)
				$end_of_comment   = (strpos(' ' . $sql, "\012", $i+2))
					? strpos(' ' . $sql, "\012", $i+2)
					: strpos(' ' . $sql, "\015", $i+2);
				if (!$end_of_comment) {
					// no eol found after '#', add the parsed part to the returned
					// array and exit
					// RMV fix for comments at end of file
					$last = trim(substr($sql, 0, $i-1));
					if (!empty($last)) {
						$ret[] = $last;
					}
					return TRUE;
				} else {
					$sql     = substr($sql, 0, $start_of_comment) . ltrim(substr($sql, $end_of_comment));
					$sql_len = strlen($sql);
					$i--;
				}
			}
		}

		// add any rest to the returned array
		if (!empty($sql) && trim($sql) != '') {
			$ret[] = $sql;
		}
		return TRUE;
	}

	/**
	 * add a prefix.'_' to all tablenames in a query
	 *
	 * @param   string  $query  valid SQL query string
	 * @param   string  $prefix prefix to add to all table names
	 * @return  mixed   FALSE on failure
	 */
	public function prefixQuery($query, $prefix) {
		$pattern = "/^(INSERT INTO|CREATE TABLE|ALTER TABLE|UPDATE)(\s)+([`]?)([^`\s]+)\\3(\s)+/siU";
		$pattern2 = "/^(DROP TABLE)(\s)+([`]?)([^`\s]+)\\3(\s)?$/siU";
		if (preg_match($pattern, $query, $matches) || preg_match($pattern2, $query, $matches)) {
			$replace = "\\1 " . $prefix . "_\\4\\5";
			$matches[0] = preg_replace($pattern, $replace, $query);
			return $matches;
		}
		return FALSE;
	}
}

