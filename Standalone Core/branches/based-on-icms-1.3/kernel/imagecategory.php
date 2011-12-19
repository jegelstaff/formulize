<?php
if (!defined('ICMS_ROOT_PATH')) die("ImpressCMS root path not defined");

/**
 *
 * @deprecated	Use icms_image_category_Object, instead
 * @todo		Remove in version 1.4
 *
 */
class XoopsImagecategory extends icms_image_category_Object {
	private $_deprecated;
	public function __construct() {
		parent::__construct();
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_image_category_Object', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}

/**
 *
 * @deprecated	Use icms_image_category_Handler, instead
 * @todo		Remove in version 1.4
 *
 */
class XoopsImagecategoryHandler extends icms_image_category_Handler {
	private $_deprecated;
	public function __construct(&$db) {
		parent::__construct($db);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_image_category_Handler', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}

