<?php
defined('ICMS_ROOT_PATH') or die('ImpressCMS root path not defined');
/**
 *
 * @deprecated	Use icms_data_avatar_Object, instead
 * @todo		Remove in version 1.4
 *
 */
class XoopsAvatar extends icms_data_avatar_Object {
	private $_deprecated;
	public function __construct() {
		parent::__construct();
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_data_avatar_Object', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}

/**
 * @deprecated	Use icms_data_avatar_Handler, instead
 * @todo		Remove in version 1.4
 */
class XoopsAvatarHandler extends icms_data_avatar_Handler {
	private $_deprecated;
	public function __construct(&$db) {
		parent::__construct($db);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_data_avatar_Handler', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}