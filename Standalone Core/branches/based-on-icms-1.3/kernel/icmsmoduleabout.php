<?php
/**
 * @deprecated	Use icms_ipf_About, instead
 * @todo		Remove in version 1.4
 *
 */
class IcmsModuleAbout extends icms_ipf_About {
	private $_deprecated;
	public function __construct($aboutTitle = _MODABOUT_ABOUT) {
		parent::__construct($aboutTitle);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_ipf_About', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}

?>