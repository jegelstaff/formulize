<?php
/**
 * Handles all tree functions within ImpressCMS
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	LICENSE.txt
 * @package	core
 * @since	XOOPS
 * @author	http://www.xoops.org The XOOPS Project
 * @author	modified by UnderDog <underdog@impresscms.org>
 * @version	$Id: xoopstree.php 20517 2010-12-11 23:39:24Z skenow $
 */

/**
 * Class XoopsTree
 * @package XoopsTree
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @since XOOPS
 * @author Kazumi Ono (AKA onokazu)
 */
class XoopsTree extends icms_view_Tree {
	private $_deprecated;

	public function __construct($table_name, $id_name, $pid_name) {
		parent::__construct($table_name, $id_name, $pid_name);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_view_Tree', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}