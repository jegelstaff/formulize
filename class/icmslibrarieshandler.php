<?php
/**
 * Handles all functions related to 3rd party libraries within ImpressCMS
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	LICENSE.txt
 * @package	core
 * @package		libraries
 * @since		  1.1
 * @author		  marcan <marcan@impresscms.org>
 * @author	modified by UnderDog <underdog@impresscms.org>
 * @version	$Id: icmslibrarieshandler.php 20509 2010-12-11 12:02:57Z phoenyx $
 */

if (!defined('ICMS_ROOT_PATH')) {
	die("ImpressCMS root path not defined");
}

/**
 * IcmsLibrariesHandler
 * @deprecated	This isn't found anywhere in the current core, but use icms_preload_LibrariesHandler, instead
 * @todo		Remove in 1.4
 *
 * Class handling third party libraries within ImpressCMS
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		libraries
 * @since		  1.1
 * @author		  marcan <marcan@impresscms.org>
 * @version		$Id: icmslibrarieshandler.php 20509 2010-12-11 12:02:57Z phoenyx $
 */
class IcmsLibrariesHandler extends icms_preload_LibrariesHandler {
	private $_deprecated;
	
	public function __construct() {
		parent::__construct();
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_preload_LibrariesHandler', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}