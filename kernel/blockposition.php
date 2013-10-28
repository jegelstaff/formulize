<?php
defined('ICMS_ROOT_PATH') or die('ImpressCMS root path not defined');
/**
 * @deprecated	Use icms_view_block_position_Object, instead
 * @todo		Remove in version 1.4
 */
class IcmsBlockposition extends icms_view_block_position_Object {
	private $_deprecated;
	public function __construct(&$handler) {
		parent::__construct($handler);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_view_block_position_Object', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}
/**
 * @deprecated	Use icms_view_block_position_Handler, instead
 * @todo		Remove in version 1.4
 *
 */
class IcmsBlockpositionHandler extends icms_view_block_position_Handler {
	private $_deprecated;
	public function __construct(&$db) {
		parent::__construct($db);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_view_block_position_Handler', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}