<?php
if (!defined('ICMS_ROOT_PATH')) die("ImpressCMS root path not defined");

/**
 *
 * @deprecated	Use icms_image_Object, instead
 * @todo		Remove this in version 1.4
 *
 */
class XoopsImage extends icms_image_Object {
	private $_deprecated;
	public function __construct() {
		parent::__construct();
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_image_Object', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}

/**
 *
 * @deprecated	Use icms_image_Handler, instead
 * @todo		Remove in version 1.4
 *
 */
class XoopsImageHandler extends icms_image_Handler {
	private $_deprecated;
	public function __construct(&$db) {
		parent::__construct($db);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_image_Handler', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}

