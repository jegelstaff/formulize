<?php
/**
 * Database Base Class
 *
 * Defines abstract database wrapper class
 *
 * @copyright	The XOOPS Project <http://www.xoops.org/>
 * @copyright	XOOPS_copyrights.txt
 * @copyright	The ImpressCMS Project <http://www.impresscms.org/>
 * @license	LICENSE.txt
 * @package	database
 * @since	XOOPS
 * @version	$Id: database.php 20119 2010-09-09 17:55:46Z phoenyx $
 * @author	The XOOPS Project Community <http://www.xoops.org>
 * @author      Kazumi Ono  <onokazu@xoops.org>
 * @author	modified by UnderDog <underdog@impresscms.org>
 * @author	Gustavo Alejandro Pilla (aka nekro) <nekro@impresscms.org> <gpilla@nubee.com.ar>
 */

/**
 * Abstract base class for Database access classes
 *
 * @abstract
 *
 * @package database
 * @subpackage  main
 *
 * @author      Gustavo Pilla  (aka nekro) <nekro@impresscms.org>
 * @copyright   copyright (c) 2000-2003 XOOPS.org
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 */
abstract class IcmsDatabase extends icms_db_legacy_Database{
	private $_errors;
	public function __construct() {
		parent::__construct();
		$this->_errors = icms_core_Debug::setDeprecated('icms_db_legacy_database', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}

/**
 * Abstract base class for Database access classes
 *
 * @abstract
 *
 * @package database
 * @subpackage  main
 * @since XOOPS
 *
 * @author      Kazumi Ono  <onokazu@xoops.org>
 * @copyright   copyright (c) 2000-2003 XOOPS.org
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 *
 * @deprecated Use IcmsDatabase instead
 * @todo Remove this from the core in version 1.4
 */
abstract class XoopsDatabase extends IcmsDatabase { /* For Backwards compatibility */ }

/**
 * Only for backward compatibility
 *
 * @package database
 * @subpackage  main
 * @since XOOPS
 *
 * @author      Kazumi Ono  <onokazu@xoops.org>
 * @copyright   copyright (c) 2000-2003 XOOPS.org
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 *
 * @deprecated Use icms_db_legacy_Factory instead
 * @todo		Remove this in version 1.4?
 */
class Database {
	static public function &getInstance() {
		return icms_db_legacy_Factory::instance();
	}
}