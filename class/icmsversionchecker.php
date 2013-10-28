<?php
if (!defined('ICMS_ROOT_PATH')) die("ImpressCMS root path not defined");

/**
 * IcmsVersionChecker
 *
 * Class used to check if the ImpressCMS install is up to date
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		core
 * @since		1.0
 * @author		marcan <marcan@impresscms.org>
 * @deprecated	Use icms_core_Versionchecker, instead
 * @todo		Remove in version 1.4
 * @version		$Id: icmsversionchecker.php 20370 2010-11-15 20:54:33Z skenow $
 */
class IcmsVersionChecker extends icms_core_Versionchecker {

	private $_deprecated;
	/**
	 * Access the only instance of this class
	 *
	 * @static
	 * @staticvar object
	 *
	 * @return	object
	 *
	 */
	static public function &getInstance() {
		static $instance;
		if (!isset($instance)) {
			$instance = new icms_core_Versionchecker();
		}
		$self->_deprecated = icms_core_Debug::setDeprecated('icms_core_Versionchecker', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return $instance;
	}
}
