<?php
if (!defined('ICMS_ROOT_PATH')) die("ImpressCMS root path not defined");
/**
 * @deprecated	Use icms_member_group_Object, instead
 * @todo		Remove in version 1.4
 *
 */
class XoopsGroup extends icms_member_group_Object {
	private $_deprecated;
	public function __construct() {
		parent::getInstance();
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_member_group_Object', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}
/**
 * @deprecated	Use icms_member_group_Handler, instead
 * @todo		Remove in version 1.4
 *
 */
class XoopsGroupHandler extends icms_member_group_Handler {
	private $_deprecated;
	public function __construct(&$db) {
		parent::__construct($db);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_member_group_Handler', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}
/**
 * @deprecated	Use icms_member_group_membership_Object, instead
 * @todo		Remove in version 1.4
 *
 */
class XoopsMembership extends icms_member_group_membership_Object {
	private $_deprecated;
	public function __construct() {
		parent::__construct();
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_member_group_membership_Object', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}
/**
 * @deprecated	Use icms_member_group_membership_Handler, instead
 * @todo		Remove in version 1.4
 *
 */
class XoopsMembershipHandler extends icms_member_group_membership_Handler {
	private $_deprecated;
	public function __construct(&$db) {
		parent::__construct($db);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_member_group_membership_Handler', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}