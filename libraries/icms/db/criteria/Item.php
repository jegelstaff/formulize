<?php

/**
 * A single criteria for a database query
 *
 * @category	ICMS
 * @package		Database
 * @subpackage	Criteria
 * @version		SVN: $Id: Item.php 20105 2010-09-08 15:39:19Z malanciault $
 */
defined("ICMS_ROOT_PATH") or die("ImpressCMS root path not defined");

/**
 * A single criteria
 *
 * @category	ICMS
 * @package     Database
 * @subpackage  Criteria
 *
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 */
class icms_db_criteria_Item extends icms_db_criteria_Element {

	/**
	 * @var	string
	 */
	private $_prefix;
	private $_function;
	private $_column;
	private $_operator;
	private $_value;

	/**
	 * Constructor
	 *
	 * @param   string  $column
	 * @param   string  $value
	 * @param   string  $operator
	 **/
	public function __construct($column = '', $value='', $operator='=', $prefix = '', $function = '') {
		$this->_prefix = $prefix;
		$this->_function = $function;
		$this->_column = $column;
		$this->_value = $value;
		$this->_operator = $operator;
	}

	/**
	 * Make a sql condition string
	 *
	 * @return  string
	 **/
	public function render() {
		$clause = (!empty($this->_prefix) ? "{$this->_prefix}." : "") . $this->_column;
		if (!empty($this->_function)) {
			$clause = sprintf($this->_function, $clause);
		}
		if (in_array( strtoupper($this->_operator), array('IS NULL', 'IS NOT NULL'))) {
			$clause .= ' ' . $this->_operator;
		} else {
			if ('' === ( $value = trim($this->_value) )) {
				return '';
			}
			if (!in_array(strtoupper($this->_operator), array('IN', 'NOT IN'))) {
				if (( substr($value, 0, 1) != '`' ) && ( substr($value, -1) != '`' )) {
					$value = "'$value'";
				} elseif (!preg_match('/^[a-zA-Z0-9_\.\-`]*$/', $value)) {
					$value = '``';
				}
			}
			$clause .= " {$this->_operator} $value";
		}
		return $clause;
	}

	/**
	 * Generate an LDAP filter from criteria
	 *
	 * @return string
	 * @author Nathan Dial ndial@trillion21.com, improved by Pierre-Eric MENUET pemen@sourceforge.net
	 */
	public function renderLdap() {
		if ($this->_operator == '>') {
			$this->_operator = '>=';
		}
		if ($this->_operator == '<') {
			$this->_operator = '<=';
		}

		if ($this->_operator == '!=' || $this->_operator == '<>') {
			$operator = '=';
			$clause = "(!(" . $this->_column . $operator . $this->_value . "))";
		}
		else {
			if ($this->_operator == 'IN') {
				$newvalue = str_replace(array('(', ')'), '', $this->_value);
				$tab = explode(',', $newvalue);
				foreach ($tab as $uid) {
					$clause .= '(' . $this->_column . '=' . $uid
					.')';
				}
				$clause = '(|' . $clause . ')';
			} else {
				$clause = "(" . $this->_column . $this->_operator . $this->_value . ")";
			}
		}
		return $clause;
	}

	/**
	 * Make a SQL "WHERE" clause
	 *
	 * @return	string
	 */
	public function renderWhere() {
		$cond = $this->render();
		return empty($cond) ? '' : "WHERE $cond";
	}
}

