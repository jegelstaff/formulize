<?php
/**
 * Criteria Base Class for composing Where clauses in SQL Queries
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	LICENSE.txt
 * @package	core
 * @since	XOOPS
 * @author	http://www.xoops.org The XOOPS Project
 * @author	modified by UnderDog <underdog@impresscms.org>
 * @version	$Id: criteria.php 19118 2010-03-27 17:46:23Z skenow $
 * @deprecated
 * @todo	Remove completely in version 1.4
 */

/**
 *
 *
 * @package     kernel
 * @subpackage  database
 *
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 */

/**
 * A criteria (grammar?) for a database query.
 *
 * Abstract base class should never be instantiated directly.
 *
 * @abstract
 *
 * @package     kernel
 * @subpackage  database
 *
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 * @deprecated	Use icms_db_criteria_Element, instead
 * @todo		Remove in version 1.4
 */
abstract class CriteriaElement extends icms_db_criteria_Element
{
	public function __construct($column = '', $value='', $operator='=', $prefix = '', $function = '') {
	}
}

/**
 * Collection of multiple {@link CriteriaElement}s
 *
 * @package     kernel
 * @subpackage  database
 *
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 * @deprecated	Use icms_db_criteria_Compo, instead
 * @todo		Remove in version 1.4
 */
class CriteriaCompo extends icms_db_criteria_Compo
{
	private $_errors;
	public function __construct($ele=null, $condition='AND') {
		parent::__construct($ele, $condition);
		$this->_errors = icms_core_Debug::setDeprecated('icms_db_criteria_Compo', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}

}

/**
 * A single criteria
 *
 * @package     kernel
 * @subpackage  database
 *
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 * @deprecated	Use icms_db_criteria_Item, instead
 * @todo		Remove in version 1.4
 */
class Criteria extends icms_db_criteria_Item
{
	private $_errors;
	public function __construct($column, $value='', $operator='=', $prefix = '', $function = '') {
		parent::__construct($column, $value, $operator, $prefix, $function);
		$this->_errors = icms_core_Debug::setDeprecated('icms_db_criteria_Item', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}

