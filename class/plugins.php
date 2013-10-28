<?php
/**
 *
 * Class To load plugins for modules.
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		core
 * @since		1.2
 * @author		ImpressCMS
 * @author	   Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version		$Id$
 */

/**
 * @deprecated	Use icms_plugins_Object, instead
 * @todo		Remove in 1.4
 *
 */
class IcmsPlugins extends icms_plugins_Object {

	private $_deprecated;

	function IcmsPlugins(&$array) {
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_plugins_Object', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		parent::__construct($array);
	}
}

/**
 * @deprecated	Use icms_plugins_Handler, instead
 * @todo		Remove in 1.4
 *
 */
class IcmsPluginsHandler extends icms_plugins_Handler {	
	private $_deprecated;
	
	public function __construct() {
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_plugins_Handler', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return new parent; 
	}
}
