<?php
if (!defined("ICMS_ROOT_PATH")) die("ICMS root path not defined");
/**
 * @deprecated	Use icms_ipf_Tree, instead
 * @todo		Remove in version 1.4
 *
 * @category
 * @package
 * @subpackage
 *
 */
class IcmsPersistableTree extends icms_ipf_Tree{
	private $_deprecated;
	public function __construct(&$objectArr, $myId, $parentId, $rootId = null) {
		parent::__construct($objectArr, $myId, $parentId, $rootId);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_ipf_Tree', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}
?>