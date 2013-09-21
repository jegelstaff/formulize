<?php
if (!defined('ICMS_ROOT_PATH')) die("ImpressCMS root path not defined");
/**
 * @deprecated	Use icms_ipf_view_Table
 * @todo		Remove in version 1.4
 *
 * @category
 * @package
 * @subpackage
 */
class IcmsPersistableColumn extends icms_ipf_view_Column {
	private $_deprecated;
	public function __construct($keyname, $align = _GLOBAL_LEFT, $width = false, $customMethodForValue = false, $param = false, $customCaption = false, $sortable = true) {
		parent::__construct($keyname, $align, $width, $customMethodForValue, $param, $customCaption, $sortable);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_ipf_view_Column', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}
/**
 * @deprecated	Use icms_ipf_view_Table
 * @todo		Remove in version 1.4
 *
 * @category
 * @package
 * @subpackage
 */
class IcmsPersistableTable extends icms_ipf_view_Table {
	private $_deprecated;
	public function __construct(&$objectHandler, $criteria = false, $actions = array('edit', 'delete'), $userSide = false) {
		parent::__construct($objectHandler, $criteria, $actions, $userSide);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_ipf_view_Table', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}

?>