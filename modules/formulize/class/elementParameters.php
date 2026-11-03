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
 * The parameters of Formulize's functions and methods that take elements, as their docblocks declare them, so that
 * renaming an element can rewrite its handle where code passes it to one of them - see
 * formulizeElementHandleCode::renameStrings().
 *
 * A parameter is declared with a line in the docblock of its function or method, after the @param lines:
 *
 *   @formulizeElement $sortField commas
 *
 * which names the parameter, and then the shape the elements take in it:
 * - one: the argument is one element - display($entry, 'handle')
 * - commas: a string of elements separated by commas - gatherDataset(..., sortField: 'year,price')
 * - list: an array of elements - array('year', 'price')
 * - lists: an array of arrays of elements - array(5 => array('year', 'price')), as gatherDataset's elementHandles
 * - keys: an array whose keys are elements - formulize_writeEntry(array('year' => 1990))
 * - map: an array whose keys and values are both elements - array('year' => 'cloned_year')
 * Only a handle written as a quoted string, at that place in the call, is rewritten. Anything built some other way,
 * such as an array filled in earlier and passed as a variable, may or may not hold the element, so a rename leaves
 * it and says so.
 *
 * A call to a method is recognised by the method's name alone, since which class the object is cannot be known from
 * the code, so only a method whose name no other class in Formulize uses can be declared - the logic test checks.
 *
 * Declare a parameter only when every quoted string passed to it is an element: an element's handle or id, or one
 * of the metadata fields like creation_uid, which are never an element's handle.
 *
 * @package Formulize
 */
class formulizeElementParameters {

	/**
	 * The tag that declares a parameter - see the class.
	 */
	const TAG = '@formulizeElement';

	/**
	 * The shapes a declared parameter can take - see the class.
	 */
	const SHAPES = array('one', 'commas', 'list', 'lists', 'keys', 'map');

	/**
	 * Every declared parameter in Formulize's own code, read from the files the first time they are asked for, or
	 * when they have changed since - see sourceFiles().
	 *
	 * What each file declares is kept in the cache folder with the file's modification time and size, so a file is
	 * only read again when it has changed: reading every file takes most of a second on some servers, and this is
	 * asked on every rename.
	 *
	 * @return array 'function' and 'method' => lowercase name => parameter name => position and shape - see
	 *   index().
	 */
	public static function all() {
		static $parameters = null;
		if($parameters !== null) {
			return $parameters;
		}
		$moduleRoot = XOOPS_ROOT_PATH.'/modules/formulize/';
		$cacheFile = $moduleRoot.'cache/element_parameters.php';
		$cached = is_readable($cacheFile) ? include $cacheFile : array();
		$cached = is_array($cached) ? $cached : array();
		$files = array();
		foreach(self::sourceFiles($moduleRoot) as $path) {
			$name = substr($path, strlen($moduleRoot));
			$stamp = filemtime($path).':'.filesize($path);
			if(isset($cached[$name]) AND $cached[$name]['stamp'] === $stamp) {
				$files[$name] = $cached[$name];
			} elseif(($source = file_get_contents($path)) !== false) {
				$files[$name] = array('stamp' => $stamp, 'declared' => self::declaredIn($source));
			}
		}
		if($files !== $cached AND is_writable(dirname($cacheFile))) {
			// written aside and moved into place, so another request never includes half of it
			$temporaryFile = $cacheFile.'.'.getmypid().'.tmp';
			if(file_put_contents($temporaryFile, '<?php return '.var_export($files, true).';') !== false) {
				rename($temporaryFile, $cacheFile);
			}
		}
		$declarations = array();
		foreach($files as $file) {
			$declarations = array_merge($declarations, $file['declared']);
		}
		$parameters = self::index($declarations);
		return $parameters;
	}

	/**
	 * The files that can declare parameters: the class files, and the function libraries in include.
	 * @param string $moduleRoot The Formulize module's folder, ending in a slash.
	 * @return array The paths.
	 */
	public static function sourceFiles($moduleRoot) {
		return array_merge(
			(array) glob($moduleRoot.'class/*.php'),
			(array) glob($moduleRoot.'include/*.php'),
			(array) glob($moduleRoot.'include/functions/*.php')
		);
	}

	/**
	 * The parameters declared in some PHP source, read the way PHP reads it, so a function's docblock, name and
	 * parameters are what they are, and a method is told from a function by being in a class.
	 * @param string $source
	 * @return array One for each declaration: kind ('function' or 'method'), name, class (for a method), parameter,
	 *   position (from 0), shape, and problem - why it is not a declaration, if it is not one: a shape that does not
	 *   exist, or a parameter the function does not have. A declaration with a problem declares nothing.
	 */
	public static function declaredIn($source) {
		if(strpos($source, self::TAG) === false) {
			return array();
		}
		$tokens = token_get_all($source);
		$count = count($tokens);
		$declarations = array();
		$ignorable = array(T_WHITESPACE, T_COMMENT);
		$modifiers = array(T_PUBLIC, T_PROTECTED, T_PRIVATE, T_STATIC, T_ABSTRACT, T_FINAL);
		$braceDepth = 0;
		$classes = array(); // brace depth inside each class being read => its name
		$classComing = null;
		for($i = 0; $i < $count; $i++) {
			$id = is_array($tokens[$i]) ? $tokens[$i][0] : $tokens[$i];
			if($id === '{' OR $id === T_CURLY_OPEN OR $id === T_DOLLAR_OPEN_CURLY_BRACES) {
				$braceDepth++;
				if($classComing !== null) {
					$classes[$braceDepth] = $classComing;
					$classComing = null;
				}
			} elseif($id === '}') {
				unset($classes[$braceDepth]);
				$braceDepth--;
			} elseif(in_array($id, array(T_CLASS, T_TRAIT, T_INTERFACE), true) AND !self::follows($tokens, $i, T_DOUBLE_COLON)) {
				$next = self::nextSignificant($tokens, $i);
				$classComing = ($next !== null AND is_array($tokens[$next]) AND $tokens[$next][0] === T_STRING) ? $tokens[$next][1] : 'class@anonymous';
			} elseif($id === T_FUNCTION) {
				// the docblock, before any modifiers
				$doc = null;
				for($b = $i - 1; $b >= 0; $b--) {
					$backId = is_array($tokens[$b]) ? $tokens[$b][0] : $tokens[$b];
					if(in_array($backId, $ignorable, true) OR in_array($backId, $modifiers, true)) {
						continue;
					}
					$doc = $backId === T_DOC_COMMENT ? $tokens[$b][1] : null;
					break;
				}
				if($doc === null OR strpos($doc, self::TAG) === false) {
					continue;
				}
				$n = self::nextSignificant($tokens, $i);
				if($n !== null AND (is_array($tokens[$n]) ? $tokens[$n][1] : $tokens[$n]) === '&') { // a token of its own since PHP 8.1
					$n = self::nextSignificant($tokens, $n);
				}
				if($n === null OR !is_array($tokens[$n]) OR $tokens[$n][0] !== T_STRING) {
					continue; // a closure
				}
				$name = $tokens[$n][1];
				$isMethod = isset($classes[$braceDepth]);
				$parameterNames = self::parameterNames($tokens, $n);
				preg_match_all('/'.preg_quote(self::TAG, '/').'(?![A-Za-z0-9_])[ \t]*([^\r\n]*)/', $doc, $tags);
				foreach($tags[1] as $tag) {
					$declaration = array(
						'kind' => $isMethod ? 'method' : 'function',
						'name' => $name,
						'class' => $isMethod ? $classes[$braceDepth] : '',
						'parameter' => '',
						'position' => null,
						'shape' => '',
						'problem' => '',
					);
					if(!preg_match('/^\$?([A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*)\s+([A-Za-z]+)/', trim(rtrim(trim($tag), '*/')), $parts)) {
						$declaration['problem'] = 'it does not name a parameter and a shape';
					} else {
						$declaration['parameter'] = $parts[1];
						$declaration['shape'] = $parts[2];
						$position = array_search($parts[1], $parameterNames, true);
						if(!in_array($parts[2], self::SHAPES, true)) {
							$declaration['problem'] = "'".$parts[2]."' is not a shape - it can be ".implode(', ', self::SHAPES);
						} elseif($position === false) {
							$declaration['problem'] = "there is no parameter called ".$parts[1];
						} else {
							$declaration['position'] = $position;
						}
					}
					$declarations[] = $declaration;
				}
			}
		}
		return $declarations;
	}

	/**
	 * The declarations that have no problem, by kind and lowercase name, which is how PHP matches a call to a function
	 * or a method.
	 * @param array $declarations From declaredIn().
	 * @return array 'function' and 'method' => lowercase name => parameter name => array(position, shape).
	 */
	public static function index($declarations) {
		$parameters = array('function' => array(), 'method' => array());
		foreach($declarations as $declaration) {
			if($declaration['problem'] === '') {
				$parameters[$declaration['kind']][strtolower($declaration['name'])][$declaration['parameter']] = array(
					'position' => $declaration['position'],
					'shape' => $declaration['shape'],
				);
			}
		}
		return $parameters;
	}

	/**
	 * The names of a function's parameters, in order, without the $.
	 * @param array $tokens From token_get_all().
	 * @param int $nameIndex Where the function's name is.
	 * @return array
	 */
	private static function parameterNames($tokens, $nameIndex) {
		$names = array();
		$open = self::nextSignificant($tokens, $nameIndex);
		if($open === null OR $tokens[$open] !== '(') {
			return $names;
		}
		$depth = 0;
		$named = false; // whether the parameter being read has its name yet - a default value can hold variables too
		for($i = $open; $i < count($tokens); $i++) {
			$id = is_array($tokens[$i]) ? $tokens[$i][0] : $tokens[$i];
			if(in_array($id, array('(', '[', '{', T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES), true)) {
				$depth++;
			} elseif(in_array($id, array(')', ']', '}'), true)) {
				$depth--;
				if($depth == 0) {
					break;
				}
			} elseif($depth == 1 AND $id === ',') {
				$named = false;
			} elseif($depth == 1 AND $id === T_VARIABLE AND !$named) {
				$names[] = substr($tokens[$i][1], 1);
				$named = true;
			}
		}
		return $names;
	}

	private static function nextSignificant($tokens, $i) {
		for($i++; $i < count($tokens); $i++) {
			if(!is_array($tokens[$i]) OR !in_array($tokens[$i][0], array(T_WHITESPACE, T_COMMENT, T_DOC_COMMENT), true)) {
				return $i;
			}
		}
		return null;
	}

	private static function follows($tokens, $i, $id) {
		for($i--; $i >= 0; $i--) {
			if(!is_array($tokens[$i]) OR !in_array($tokens[$i][0], array(T_WHITESPACE, T_COMMENT, T_DOC_COMMENT), true)) {
				return (is_array($tokens[$i]) ? $tokens[$i][0] : $tokens[$i]) === $id;
			}
		}
		return false;
	}
}
