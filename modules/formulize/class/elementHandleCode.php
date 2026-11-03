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
 * - a variable Formulize names after the handle, in code that is given one - see renameVariables()
 * - the handle passed as a quoted string to a parameter of Formulize's that takes elements - display($entry,
 *   'handle'), or a key in formulize_writeEntry(array('handle' => 1)) - see formulizeElementParameters
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
	 * What a rename adds to a file it could not write, so it still names the old handle wherever it did before.
	 */
	const NOT_WRITTEN = ' - the web server cannot write to it, so none of it was updated';

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
	 * Rewrite the handle where PHP code passes it as a string that can only mean the element: at a parameter of
	 * Formulize's that takes elements, in the shape that parameter takes them - see formulizeElementParameters - and
	 * in a filter term in a quoted string, where it comes first or straight after '][', followed by /**\/ -
	 * 'handle/**\/value/**\/=' and the like.
	 *
	 * The code is read the way PHP reads it, so a string, a comment or a call is seen for what it is: a function
	 * being declared is not a call, a call inside an argument is found as well as the outer one, and 'artifacts_'.$x
	 * or "artifacts_$x" is not a handle at all. Text outside <?php ?> is left alone.
	 *
	 * @param string $code
	 * @param string $oldHandle
	 * @param string $newHandle
	 * @param array $parameters The parameters that take elements - see formulizeElementParameters::all().
	 * @param bool $isCode Whether the text is PHP throughout, without an opening tag - a formula, a procedure, code
	 *        kept in the database. Otherwise only what is inside <?php ?> is PHP.
	 * @return string
	 */
	public static function renameStrings($code, $oldHandle, $newHandle, $parameters, $isCode = true) {
		// A number given as an element is read as the element's id, everywhere an element can be given that way - see
		// formulizeElementsHandler::get() and prepareElementMetaData() - so for an element whose old handle is a number,
		// such a string is some element's id and never this element's handle.
		if(!is_string($code) OR $oldHandle === '' OR is_numeric($oldHandle) OR strpos($code, $oldHandle) === false) {
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
		foreach(self::elementArgumentStrings($tokens, $parameters) as $index => $separatedByCommas) {
			$text = $tokens[$index][1];
			$elements = $separatedByCommas ? explode(',', substr($text, 1, -1)) : array(substr($text, 1, -1));
			foreach($elements as $e => $element) {
				if(trim($element) === $oldHandle) {
					$elements[$e] = str_replace($oldHandle, $newHandle, $element); // keeping the spaces around it
				}
			}
			$tokens[$index][1] = $text[0].implode(',', $elements).$text[0];
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
	 * The quoted strings that are elements given to a parameter that takes them, in a call to a function or method
	 * that has one - see formulizeElementParameters. A method is known by its name alone, since which class an object
	 * is cannot be read from the code.
	 * @param array $tokens (id, text) pairs, from token_get_all().
	 * @param array $parameters See renameStrings().
	 * @return array index of each string's token => whether it is elements separated by commas, rather than one.
	 */
	private static function elementArgumentStrings($tokens, $parameters) {
		$ignorable = array(T_WHITESPACE, T_COMMENT, T_DOC_COMMENT);
		$significant = array();
		foreach($tokens as $index => $token) {
			if(!in_array($token[0], $ignorable, true)) {
				$significant[] = $index;
			}
		}
		$calledOn = array(T_OBJECT_OPERATOR, T_DOUBLE_COLON);
		if(defined('T_NULLSAFE_OBJECT_OPERATOR')) {
			$calledOn[] = T_NULLSAFE_OBJECT_OPERATOR;
		}
		$names = array(T_STRING);
		if(defined('T_NAME_FULLY_QUALIFIED')) {
			$names[] = T_NAME_FULLY_QUALIFIED;
		}
		$found = array();
		$count = count($significant);
		for($s = 0; $s < $count - 1; $s++) {
			$token = $tokens[$significant[$s]];
			if(!in_array($token[0], $names, true) OR $tokens[$significant[$s + 1]][0] !== '(') {
				continue;
			}
			$before = $s > 0 ? $tokens[$significant[$s - 1]][0] : null;
			if($before === T_FUNCTION OR $before === T_NEW
				OR ($s > 1 AND $tokens[$significant[$s - 1]][1] === '&' AND $tokens[$significant[$s - 2]][0] === T_FUNCTION)) {
				continue; // a function being declared, or a class being made
			}
			$kind = in_array($before, $calledOn, true) ? 'method' : 'function';
			$name = strtolower(ltrim($token[1], '\\'));
			if(!isset($parameters[$kind][$name])) {
				continue;
			}
			// the significant tokens of each argument, read to the closing bracket
			$arguments = self::separated($tokens, array_slice($significant, $s + 1), ',');
			// by name - display($entry, handle: 'x') - or else by position, which only the arguments before the first
			// one given by name, or spread out of an array, have
			$byName = array();
			$byPosition = array();
			foreach($arguments as $position => $argument) {
				if(count($argument) > 2 AND $tokens[$argument[0]][0] === T_STRING AND $tokens[$argument[1]][0] === ':') {
					$byName[$tokens[$argument[0]][1]] = array_slice($argument, 2);
				} elseif($argument AND $tokens[$argument[0]][0] === T_ELLIPSIS) {
					break;
				} elseif(!$byName) {
					$byPosition[$position] = $argument;
				}
			}
			foreach($parameters[$kind][$name] as $parameter => $declared) {
				$argument = $byName[$parameter] ?? ($byPosition[$declared['position']] ?? null);
				if($argument) {
					$found = $found + self::stringsInShape($tokens, $argument, $declared['shape']);
				}
			}
		}
		return $found;
	}

	/**
	 * The quoted strings in an argument that are elements, given the shape the parameter takes them in - see
	 * formulizeElementParameters. Only a string standing on its own is one, at a place the shape says an element is.
	 * @param array $tokens
	 * @param array $argument The indexes of the argument's significant tokens.
	 * @param string $shape
	 * @return array See elementArgumentStrings().
	 */
	private static function stringsInShape($tokens, $argument, $shape) {
		$found = array();
		if($shape == 'one' OR $shape == 'commas') {
			if(count($argument) == 1 AND $tokens[$argument[0]][0] === T_CONSTANT_ENCAPSED_STRING) {
				$found[$argument[0]] = ($shape == 'commas');
			}
			return $found;
		}
		foreach(self::arrayItems($tokens, $argument) as list($key, $value)) {
			if($shape == 'keys' OR $shape == 'map') {
				$found = $found + self::stringsInShape($tokens, (array) $key, 'one');
			}
			if($shape == 'list' OR $shape == 'map') {
				$found = $found + self::stringsInShape($tokens, $value, 'one');
			} elseif($shape == 'lists') {
				$found = $found + self::stringsInShape($tokens, $value, 'list');
			}
		}
		return $found;
	}

	/**
	 * The items of an argument that is an array written out - array(...) or [...] - and nothing else.
	 * @param array $tokens
	 * @param array $argument The indexes of the argument's significant tokens.
	 * @return array array(key, value) for each item, as the indexes of their significant tokens, the key null where
	 *   the item has none.
	 */
	private static function arrayItems($tokens, $argument) {
		if($argument AND $tokens[$argument[0]][0] === T_ARRAY) {
			array_shift($argument);
		}
		if(!$argument OR !in_array($tokens[$argument[0]][0], array('(', '['), true)) {
			return array();
		}
		$items = self::separated($tokens, $argument, ',', true, true);
		if($items === null) {
			return array();
		}
		$keysAndValues = array();
		foreach($items as $item) {
			$parts = self::separated($tokens, $item, T_DOUBLE_ARROW, false);
			if(count($parts) == 2) {
				$keysAndValues[] = array($parts[0], $parts[1]);
			} elseif($item) {
				$keysAndValues[] = array(null, $item);
			}
		}
		return $keysAndValues;
	}

	/**
	 * Split significant tokens at a separator that is not inside brackets of their own.
	 * @param array $tokens
	 * @param array $indexes The indexes of the significant tokens to split.
	 * @param mixed $separator The separator's token id.
	 * @param bool $bracketed Whether the indexes start with an opening bracket, and what is split is what is inside it,
	 *        up to the bracket that closes it. Otherwise they are split as they are.
	 * @param bool $wholly For bracketed tokens, whether the closing bracket must be the last of them.
	 * @return array|null The parts, each the indexes of its tokens, or null if the closing bracket had to be the last
	 *         and was not.
	 */
	private static function separated($tokens, $indexes, $separator, $bracketed = true, $wholly = false) {
		$opening = array('(', '[', '{', T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES);
		$closing = array(')', ']', '}');
		$parts = array(array());
		$depth = $bracketed ? 0 : 1;
		$last = count($indexes) - 1;
		foreach(array_values($indexes) as $i => $index) {
			$id = $tokens[$index][0];
			if(in_array($id, $opening, true)) {
				$depth++;
				if($depth == 1) {
					continue;
				}
			} elseif(in_array($id, $closing, true)) {
				$depth--;
				if($depth == 0) {
					return (!$wholly OR $i == $last) ? $parts : null;
				}
			} elseif($id === $separator AND $depth == 1) {
				$parts[] = array();
				continue;
			}
			$parts[count($parts) - 1][] = $index;
		}
		return ($bracketed AND $wholly) ? null : $parts;
	}
}
