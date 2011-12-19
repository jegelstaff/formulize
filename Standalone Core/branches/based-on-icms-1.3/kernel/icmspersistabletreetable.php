<?php
if (!defined('ICMS_ROOT_PATH')) die("ImpressCMS root path not defined");
/**
 * @deprecated	 Use icms_ipf_view_Table, instead
 * @todo		Remove in version 1.4
 *
 * @category
 * @package
 * @subpackage
 *
 */
class IcmsPersistableTreeTable extends icms_ipf_view_Table {
	private $_deprecated;
	public function __construct(&$objectHandler, $criteria = false, $actions = array('edit', 'delete'), $userSide = false) {
		parent::__construct($objectHandler, $criteria, $actions, $userSide);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_ipf_view_Table', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}

?>