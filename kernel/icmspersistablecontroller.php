<?php
if (!defined('ICMS_ROOT_PATH')) die("ImpressCMS root path not defined");
/**
 * @deprecated	Use icms_ipf_Controller, instead
 * @todo		Remove in version 1.4
 *
 */
class IcmsPersistableController extends icms_ipf_Controller{
	private $_deprecated;
	public function __construct($handler) {
		parent::__construct($handler);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_ipf_Controller', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}

?>