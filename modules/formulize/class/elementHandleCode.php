<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2007 Freeform Solutions                  ##
###############################################################################
##  This program is free software; you can redistribute it and/or modify     ##
##  it under the terms of the GNU General Public License as published by     ##
##  the Free Software Foundation; either version 2 of the License, or        ##
##  (at your option) any later version.                                      ##
##                                                                           ##
##  This program is distributed in the hope that it will be useful,          ##
##  but WITHOUT ANY WARRANTY; without even the implied warranty of           ##
##  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            ##
##  GNU General Public License for more details.                             ##
###############################################################################
##  Project: Formulize                                                       ##
###############################################################################

if (!defined("XOOPS_ROOT_PATH")) {
    die("XOOPS root path not defined");
}

/**
 * How an element's handle is written in code and templates, and how a rename rewrites it there.
 *
 * Only what can only be the element is rewritten:
 * - a variable Formulize names after the handle, in code that is given one - see variables()
 * - the handle passed as a quoted string to one of Formulize's display functions - display($entry, 'handle')
 * - the handle at the start of a filter term in a quoted string - 'handle/**\/value', or after '][' in one
 * Anything else that names the handle - an array key, a column in a query, the handle inside other text - may or
 * may not be the element, so it is left as it is, and a person is told it is there - see stillNamed().
 *
 * Nothing here reads or writes anything. The callers decide which files and settings to give it, and what kind of
 * code each one is.
 *
 * @package Formulize
 */
class formulizeElementHandleCode {

	/**
	 * The characters that continue a name in PHP, [a-zA-Z0-9_\x80-\xff]. The high bytes matter: they are what let a
	 * variable name hold accented and other non-ASCII characters, and leaving them out means $artifacts_year matches
	 * inside $artifacts_yeare-acute and a rename silently corrupts it. Used without the /u modifier, since that
	 * grammar is defined in bytes, and a multibyte character is a sequence of bytes all inside that range anyway.
	 */
	const NAME_CHARACTERS = 'A-Za-z0-9_\x80-\xff';

	/**
	 * A handle no element can have, for asking what a rename would leave behind without making one: whatever still
	 * names the element's handle after a rename to this is what a real rename would leave for a person to update.
	 */
	const RENAME_CHECK_HANDLE = 'formulize_rename_check_x';

	/**
	 * What the usage report adds to a place that names an element, when renaming the element would leave some of it
	 * for a person to update.
	 */
	const LEFT_BY_A_RENAME = ' - renaming the element would leave this for you to update';

	/**
	 * Formulize's display functions, with the name of the parameter that takes an element. It is the second
	 * parameter of each of them.
	 */
	const DISPLAY_FUNCTIONS = array(
		'display' => 'handle',
		'getvalue' => 'handle',
		'displaytogether' => 'handle',
		'displaypara' => 'handle',
		'displaybr' => 'handle',
		'displaylist' => 'handle',
		'displayrichtext' => 'elementHandle',
		'displayelement' => 'ele',
		'displaycaption' => 'ele',
		'displaydescription' => 'ele',
		'displaybutton' => 'ele',
	);

	/**
	 * The variables Formulize itself sets in the code that gets a variable for each handle: the parameters and
	 * working variables of the function a derived value formula runs in (see formulize_includeDerivedValueFormulas())
	 * and of the save and delete procedures (see formulizeForm), the metadata a formula can name, and $this. An
	 * element given one of these as its handle would share its name with something Formulize needs, so no element
	 * can have one - see reservedNames().
	 */
	const CODE_VARIABLES = array(
		'entry', 'entry_id', 'form_id', 'relationship_id', 'value', 'formulize_invertedDataset',
		'formulize_element_values', 'formulize_element_key', 'formulize_element_value', 'currentValues', 'newEntry',
		'uid', 'proxyid', 'creation_date', 'mod_date', 'creator_email', 'owner_groups',
		'this',
	);

	/**
	 * The names no element handle can be, because Formulize keeps them for its own variables in code - see
	 * CODE_VARIABLES, and formulizeTemplateScreenHandler::RESERVED_TEMPLATE_VARIABLES for the template screens'.
	 * @param array $alsoReserved Other names the caller knows are kept.
	 * @return array
	 */
	public static function reservedNames($alsoReserved = array()) {
		return array_values(array_unique(array_merge(self::CODE_VARIABLES, $alsoReserved)));
	}

	/**
	 * The regular expression for a variable named after a handle, with an optional prefix in front of it - $handle,
	 * or $quickSearchhandle in a list screen's template. The prefix is the first group, so a replacement can keep it.
	 * @param string $handle The handle.
	 * @param array $prefixes The prefixes to match, '' for none.
	 * @return string
	 */
	public static function variablePattern($handle, $prefixes = array('')) {
		$quotedPrefixes = array_map(function($prefix) { return preg_quote($prefix, '/'); }, $prefixes);
		return '/\$('.implode('|', $quotedPrefixes).')'.preg_quote($handle, '/').'(?!['.self::NAME_CHARACTERS.'])/';
	}

	/**
	 * The regular expression for a {handle} token, which Formulize swaps for the element's value.
	 * @param string $handle The handle.
	 * @return string
	 */
	public static function tokenPattern($handle) {
		return '/\{'.preg_quote($handle, '/').'\}/';
	}

	/**
	 * The regular expression that finds the handle named in code or a template, for a person to look at: as a
	 * $handle variable, a {handle} token, or a string of its own - 'handle', "handle", or `handle` as a column in a
	 * query - and with $sql, where the code is a query, the bare handle as a column name. Wider than what a rename
	 * rewrites, since a string or a word that matches a handle is not always one, but a person reading it can tell.
	 * @param string $handle The handle.
	 * @param bool $sql Whether the code is a query.
	 * @return string
	 */
	public static function mentionPattern($handle, $sql = false) {
		$quoted = preg_quote($handle, '/');
		$patterns = array(
			'\$'.$quoted.'(?!['.self::NAME_CHARACTERS.'])',
			'\{'.$quoted.'\}',
			'([\'"`])'.$quoted.'\1',
		);
		if($sql) {
			$patterns[] = '(?<!['.self::NAME_CHARACTERS.'$])'.$quoted.'(?!['.self::NAME_CHARACTERS.'])';
		}
		return '/(?:'.implode('|', $patterns).')/'; // non-capturing, so the quote is group 1 for the \1 above
	}

	/**
	 * Does the text still name the handle, in any way mentionPattern() finds?
	 * @param string $text
	 * @param string $handle
	 * @param bool $sql Whether the text is a query.
	 * @return bool
	 */
	public static function stillNamed($text, $handle, $sql = false) {
		return is_string($text) AND preg_match(self::mentionPattern($handle, $sql), $text) === 1;
	}

	/**
	 * Rename the variables named after a handle, in texts that share one set of variables - one formula, or a screen's
	 * code and template together - so that $oldhandle becomes $newhandle.
	 *
	 * A variable already called $newhandle is the author's own, since no element had that handle until now, so it is
	 * moved out of the way first, to the first $newhandle_1, $newhandle_2... that none of the texts uses and that is
	 * not a name Formulize sets itself in this code. Otherwise the two would become one variable.
	 *
	 * @param array $texts key => text, all sharing their variables.
	 * @param string $oldHandle
	 * @param string $newHandle
	 * @param array $prefixes What comes before the handle in the variable's name, '' for none - see variablePattern().
	 * @param array $unavailable Names the variable moved out of the way must not take: every element handle, which
	 *        is or can become a variable here, and the reserved names.
	 * @return array The texts, rewritten.
	 */
	public static function renameVariables($texts, $oldHandle, $newHandle, $prefixes = array(''), $unavailable = array()) {
		$oldPattern = self::variablePattern($oldHandle, $prefixes);
		$usesOld = false;
		foreach($texts as $text) {
			if(preg_match($oldPattern, $text)) {
				$usesOld = true;
				break;
			}
		}
		if(!$usesOld) {
			return $texts;
		}
		$inUse = function($name) use ($texts, $prefixes) {
			$pattern = self::variablePattern($name, $prefixes);
			foreach($texts as $text) {
				if(preg_match($pattern, $text)) {
					return true;
				}
			}
			return false;
		};
		if($inUse($newHandle)) {
			$suffix = 1;
			while($inUse($newHandle.'_'.$suffix) OR in_array($newHandle.'_'.$suffix, $unavailable, true)) {
				$suffix++;
			}
			$texts = self::replaceVariables($texts, $newHandle, $newHandle.'_'.$suffix, $prefixes);
		}
		return self::replaceVariables($texts, $oldHandle, $newHandle, $prefixes);
	}

	private static function replaceVariables($texts, $from, $to, $prefixes) {
		$pattern = self::variablePattern($from, $prefixes);
		foreach($texts as $key => $text) {
			$rewritten = preg_replace_callback($pattern, function($match) use ($to) {
				return '$'.$match[1].$to;
			}, $text);
			if($rewritten !== null) {
				$texts[$key] = $rewritten;
			}
		}
		return $texts;
	}

	/**
	 * Rewrite {oldhandle} tokens to {newhandle}.
	 * @param string $text
	 * @param string $oldHandle
	 * @param string $newHandle
	 * @return string
	 */
	public static function renameTokens($text, $oldHandle, $newHandle) {
		$rewritten = preg_replace(self::tokenPattern($oldHandle), '{'.str_replace(array('\\', '$'), array('\\\\', '\\$'), $newHandle).'}', $text);
		return $rewritten === null ? $text : $rewritten;
	}

	/**
	 * Rewrite the handle where PHP code passes it as a string that can only mean the element: the element argument
	 * of one of the DISPLAY_FUNCTIONS, written as a quoted string on its own, and a filter term in a quoted string,
	 * where it comes first or straight after '][', followed by /**\/ - 'handle/**\/value/**\/=' and the like.
	 *
	 * The code is read the way PHP reads it, so a string, a comment or a call is seen for what it is: a method
	 * called display() is not the function, a call inside the first argument is found as well as the outer one, and
	 * 'artifacts_'.$x or "artifacts_$x" is not a handle at all. Text outside <?php ?> is left alone.
	 *
	 * @param string $code
	 * @param string $oldHandle
	 * @param string $newHandle
	 * @param bool $isCode Whether the text is PHP throughout, without an opening tag - a formula, a procedure, code
	 *        kept in the database. Otherwise only what is inside <?php ?> is PHP.
	 * @return string
	 */
	public static function renameStrings($code, $oldHandle, $newHandle, $isCode = true) {
		if(!is_string($code) OR $oldHandle === '' OR strpos($code, $oldHandle) === false) {
			return $code;
		}
		$hasOpeningTag = strpos($code, '<?') !== false;
		if(!$hasOpeningTag AND !$isCode) {
			return $code;
		}
		$opening = $hasOpeningTag ? '' : '<?php ';
		$tokens = array();
		foreach(token_get_all($opening.$code) as $token) {
			$tokens[] = is_array($token) ? array($token[0], $token[1]) : array($token, $token);
		}
		foreach(self::displayFunctionElementArguments($tokens) as $index) {
			$text = $tokens[$index][1];
			if(substr($text, 1, -1) === $oldHandle) {
				$tokens[$index][1] = $text[0].$newHandle.$text[0];
			}
		}
		$quotedHandle = preg_quote($oldHandle, '/');
		$renameTerms = function($text, $startsTheString) use ($quotedHandle, $newHandle) {
			$before = $startsTheString ? '(^|\]\[)' : '(\]\[)';
			$rewritten = preg_replace_callback('/'.$before.$quotedHandle.'(?=\/\*\*\/)/', function($match) use ($newHandle) {
				return $match[1].$newHandle;
			}, $text);
			return $rewritten === null ? $text : $rewritten;
		};
		foreach($tokens as $index => $token) {
			if($token[0] === T_CONSTANT_ENCAPSED_STRING) {
				$tokens[$index][1] = $token[1][0].$renameTerms(substr($token[1], 1, -1), true).substr($token[1], -1);
			} elseif($token[0] === T_ENCAPSED_AND_WHITESPACE) {
				// a piece of a string with variables in it, which only starts the string when it follows the quote
				$startsTheString = ($index > 0 && in_array($tokens[$index - 1][0], array('"', T_START_HEREDOC), true));
				$tokens[$index][1] = $renameTerms($token[1], $startsTheString);
			}
		}
		$rewritten = '';
		foreach($tokens as $token) {
			$rewritten .= $token[1];
		}
		return substr($rewritten, strlen($opening));
	}

	/**
	 * The tokens that are the element argument of a call to one of the DISPLAY_FUNCTIONS, where it is a quoted string
	 * and nothing else.
	 * @param array $tokens (id, text) pairs, from token_get_all().
	 * @return array Their indexes.
	 */
	private static function displayFunctionElementArguments($tokens) {
		$ignorable = array(T_WHITESPACE, T_COMMENT, T_DOC_COMMENT);
		$significant = array();
		foreach($tokens as $index => $token) {
			if(!in_array($token[0], $ignorable, true)) {
				$significant[] = $index;
			}
		}
		$notTheFunction = array(T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION, T_NEW);
		if(defined('T_NULLSAFE_OBJECT_OPERATOR')) {
			$notTheFunction[] = T_NULLSAFE_OBJECT_OPERATOR;
		}
		$names = array(T_STRING);
		if(defined('T_NAME_FULLY_QUALIFIED')) {
			$names[] = T_NAME_FULLY_QUALIFIED;
		}
		$opening = array('(', '[', '{', T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES);
		$closing = array(')', ']', '}');
		$found = array();
		$count = count($significant);
		for($s = 0; $s < $count - 1; $s++) {
			$token = $tokens[$significant[$s]];
			if(!in_array($token[0], $names, true) OR $tokens[$significant[$s + 1]][0] !== '(') {
				continue;
			}
			$function = strtolower(ltrim($token[1], '\\'));
			if(!isset(self::DISPLAY_FUNCTIONS[$function])
				OR ($s > 0 AND in_array($tokens[$significant[$s - 1]][0], $notTheFunction, true))) {
				continue;
			}
			// the significant tokens of each argument, read to the closing bracket
			$arguments = array(array());
			$depth = 0;
			for($a = $s + 1; $a < $count; $a++) {
				$id = $tokens[$significant[$a]][0];
				if(in_array($id, $opening, true)) {
					$depth++;
					if($depth == 1) {
						continue;
					}
				} elseif(in_array($id, $closing, true)) {
					$depth--;
					if($depth == 0) {
						break;
					}
				} elseif($id === ',' AND $depth == 1) {
					$arguments[] = array();
					continue;
				}
				$arguments[count($arguments) - 1][] = $significant[$a];
			}
			// by name - display($entry, handle: 'x') - or else second
			$parameter = self::DISPLAY_FUNCTIONS[$function];
			$element = null;
			foreach($arguments as $position => $argument) {
				if(count($argument) > 2 AND $tokens[$argument[0]][0] === T_STRING AND $tokens[$argument[1]][0] === ':') {
					if($tokens[$argument[0]][1] === $parameter) {
						$element = array_slice($argument, 2);
					}
				} elseif($position == 1) {
					$element = $argument;
				}
			}
			if($element AND count($element) == 1 AND $tokens[$element[0]][0] === T_CONSTANT_ENCAPSED_STRING) {
				$found[] = $element[0];
			}
		}
		return $found;
	}
}
