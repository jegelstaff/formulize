<?php
/**
 * ICMS Preload Handler
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	LICENSE.txt
 * @package	IcmsPersistableObject
 * @since	1.1
 * @author		marcan <marcan@impresscms.org>
 * @author	    Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version	$Id: icmspreloadhandler.php 19421 2010-06-14 07:28:37Z david-sf $
 */

defined('ICMS_ROOT_PATH') or die("ImpressCMS root path not defined");

/**
 * IcmsPreloadHandler
 *
 * Class handling preload events automatically detect from the files in ICMS_PRELOAD_PATH
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		core
 * @since		1.1
 * @author		marcan <marcan@impresscms.org>
 * @version		$Id: icmspreloadhandler.php 19421 2010-06-14 07:28:37Z david-sf $
 * @deprecated	Relocated to icms_preload_handler
 */
class IcmsPreloadHandler extends icms_preload_Handler {
	private $_deprecated;
	/**
	 * Constructor
	 *
	 * @return	void
	 */
	function __construct() {
		parent::__construct();
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_preload_Handler', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));

	}
}

/**
 * IcmsPreloadItem
 *
 * Class which is extended by any preload item. This class is empty for now but is there for
 * extended future purposes
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		libraries
 * @since		1.1
 * @author		marcan <marcan@impresscms.org>
 * @version		$Id: icmspreloadhandler.php 19421 2010-06-14 07:28:37Z david-sf $
 * @deprecated	Use icms_preload_Item, instead
 * @todo		Remove in version 1.4
 */
class IcmsPreloadItem  extends icms_preload_Item {
	function IcmsPreloadItem() {}
}