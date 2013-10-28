<?php
/**
 * @deprecated	Use icms_ipf_export_Handler, instead
 * @todo		Remove in version 1.4
 *
 */
class IcmsPersistableExport extends icms_ipf_export_Handler{
	private $_deprecated;
	public function __construct(&$objectHandler, $criteria=null, $fields=false, $filename=false, $filepath=false, $format='csv', $options=false) {
		parent::getInstance(&$objectHandler, $criteria, $fields, $filename, $filepath, $format, $options);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_ipf_export_Handler', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}
/**
 * @deprecated	Use icms_ipf_export_Renderer, instead
 * @todo		Remove in version 1.4
 *
 */
class IcmsExportRenderer extends icms_ipf_export_Renderer {
	private $_deprecated;
	public function __construct($data, $filename=false, $filepath=false, $format='csv', $options=array('separator'=>';')) {
		parent::__construct($data, $filename, $filepath, $format, $options);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_ipf_export_Renderer', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}
?>