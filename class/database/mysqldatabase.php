<?php

/**
 * Connections to database
 *
 * This file is responsible for:
 *               -connections to database
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		database
 * @since		XOOPS
 * @author		http://www.xoops.org The XOOPS Project
 * @author		modified by stranger <www.impresscms.org>
 * @version		$Id: mysqldatabase.php 20426 2010-11-20 22:28:44Z phoenyx $
 */

/**
 * Old mysqldatabase.php
 *
 * This file is for backward compatibility only, for module not using the icms::$xoopsDB and calling
 * this file directly
 *
 * @package ImpressCMS
 */

defined("ICMS_ROOT_PATH") or die("ImpressCMS root path not defined");

/**
 * @package     kernel
 * @subpackage  database
 *
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 */

/**
 * connection to a mysql database
 *
 * @abstract
 *
 * @author      Kazumi Ono  <onokazu@xoops.org>
 * @copyright   copyright (c) 2000-2003 XOOPS.org
 *
 * @package     kernel
 * @subpackage  database
 */
class XoopsMySQLDatabase extends icms_db_legacy_mysql_Database {
	private $_errors;
	public function __construct() {
		parent::__construct();
		$this->_errors = icms_core_Debug::setDeprecated('icms_db_legacy_mysql_Database', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}

/**
 * Safe Connection to a MySQL database.
 *
 *
 * @author Kazumi Ono <onokazu@xoops.org>
 * @copyright copyright (c) 2000-2003 XOOPS.org
 *
 * @package kernel
 * @subpackage database
 */
class XoopsMySQLDatabaseSafe extends icms_db_legacy_mysql_Safe {
	private $_errors;
	public function __construct() {
		parent::__construct();
		$this->_errors = icms_core_Debug::setDeprecated('icms_db_legacy_mysql_Safe', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}

/**
 * Read-Only connection to a MySQL database.
 *
 * This class allows only SELECT queries to be performed through its
 * {@link query()} method for security reasons.
 *
 *
 * @author Kazumi Ono <onokazu@xoops.org>
 * @copyright copyright (c) 2000-2003 XOOPS.org
 *
 * @package kernel
 * @subpackage database
 */
class XoopsMySQLDatabaseProxy extends icms_db_legacy_mysql_Proxy {
	private $_errors;
	public function __construct() {
		parent::__construct();
		$this->_errors = icms_core_Debug::setDeprecated('icms_db_legacy_mysql_Proxy', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}
?>