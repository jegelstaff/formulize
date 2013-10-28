<?php
defined('ICMS_ROOT_PATH') or die('ImpressCMS root path not defined');
/**
 * @deprecated	Use icms_view_block_Object, instead
 * @todo		Remove in version 1.4
 *
 */
class XoopsBlock extends icms_view_block_Object {
	private $_deprecated;
	public function __construct(&$handler) {
		parent::__construct($handler);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_core_Block', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}
/**
 * @deprecated	Use icms_view_block_Handler, instead
 * @todo		Remove in version 1.4
 *
 */
class XoopsBlockHandler extends icms_view_block_Handler {
	private $_deprecated;
	public function __construct(&$db) {
		parent::__construct($db);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_core_BlockHandler', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}
