<?php
/**
 * Criteria Base Class for composing Where clauses in SQL Queries
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @category	ICMS
 * @package		Database
 * @subpackage	Criteria
 * @author		modified by UnderDog <underdog@impresscms.org>
 * @version		SVN: $Id: Element.php 11989 2012-08-30 08:09:00Z qm-b $
 */
defined("ICMS_ROOT_PATH") or die("ImpressCMS root path not defined");

/**
 * A criteria (grammar?) for a database query.
 *
 * Abstract base class should never be instantiated directly.
 *
 * @abstract
 * @category	ICMS
 * @package     Database
 * @subpackage  Criteria
 *
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 */
abstract class icms_db_criteria_Element {
	/**
	 * Sort order
	 * @var	string
	 */
	public $order = 'ASC';

	/**
	 * @var	string
	 */
	public $sort = '';

	/**
	 * Number of records to retrieve
	 * @var	int
	 */
	public $limit = 0;

	/**
	 * Offset of first record
	 * @var	int
	 */
	public $start = 0;

	/**
	 * @var	string
	 */
	public $groupby = '';

	/**
	 * Constructor
	 **/
	public function __construct(){}

	/**
	 * Render the criteria element
	 */
	abstract public function render();

	/**#@+
	 * Accessor
	 */
	/**
	 * @param	string  $sort
	 */
	public function setSort($sort) {
		$this->sort = $sort;
	}

	/**
	 * @return	string
	 */
	public function getSort() {
		return $this->sort;
	}

	/**
	 * @param	string  $order
	 */
	public function setOrder($order) {
		if ('DESC' == strtoupper($order)) {
			$this->order = 'DESC';
		}
	}

	/**
	 * @return	string
	 */
	public function getOrder() {
		return $this->order;
	}

	/**
	 * @param	int $limit
	 */
	public function setLimit($limit=0) {
		$this->limit = (int) ($limit);
	}

	/**
	 * @return	int
	 */
	public function getLimit() {
		return $this->limit;
	}

	/**
	 * @param	int $start
	 */
	public function setStart($start=0) {
		$this->start = (int) ($start);
	}

	/**
	 * @return	int
	 */
	public function getStart() {
		return $this->start;
	}

	/**
	 * @param	string  $group
	 */
	public function setGroupby($group) {
		$this->groupby = $group;
	}

	/**
	 * @return	string
	 */
	public function getGroupby() {
		return ' GROUP BY ' . $this->groupby;
	}
	/**#@-*/
}

