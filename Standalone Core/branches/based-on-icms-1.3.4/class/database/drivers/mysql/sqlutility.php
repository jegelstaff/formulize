<?php
/**
 * @package database
 * @subpackage  mysql
 * @since XOOPS
 * @version $Id: sqlutility.php 10624 2010-09-09 17:55:46Z phoenyx $
 *
 * @author      Kazumi Ono  <onokazu@xoops.org>
 * @copyright   copyright (c) 2000-2003 XOOPS.org
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 */

/**
 * Provide some utility methods for databases
 *
 * @package     database
 * @subpackage  mysql
 * @since XOOPS
 *
 * @author      Kazumi Ono  <onokazu@xoops.org>
 * @copyright   copyright (c) 2000-2003 XOOPS.org
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 */
class SqlUtility extends icms_db_legacy_mysql_Utility {
	private $_errors;
	public function __construct() {
		parent::__construct();
		$this->_errors = icms_core_Debug::setDeprecated('icms_db_legacy_mysql_Utility', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}

?>