<?php
/**
* Shows all tree structures within ImpressCMS (including breadcrumbs)
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	LICENSE.txt
* @package	core
* @since	XOOPS
* @author	http://www.xoops.org The XOOPS Project
* @author	modified by UnderDog <underdog@impresscms.org>
* @version	$Id: tree.php 20762 2011-02-05 17:25:14Z skenow $
*/

/**
 * A tree structures with {@link XoopsObject}s as nodes
 * @deprecated	Use icms_ipf_Tree, instead
 * @todo		Remove in version 1.4
 *
 * @package		kernel
 * @subpackage	core
 *
 * @author		Kazumi Ono 	<onokazu@xoops.org>
 * @copyright	(c) 2000-2003 The Xoops Project - www.xoops.org
 */
class XoopsObjectTree extends icms_ipf_Tree {
	
	private $_deprecated;
	
	public function __construct(& $objectArr, $myId, $parentId, $rootId = null) {
		parent::__construct($objectArr, $myId, $parentId, $rootId);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_ipf_Tree', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}