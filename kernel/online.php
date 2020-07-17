<?php
/**
 * Manage of online users
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	LICENSE.txt
 * @package	core
 * @since	XOOPS
 * @author	http://www.xoops.org The XOOPS Project
 * @author	modified by UnderDog <underdog@impresscms.org>
 * @version	$Id: online.php 19118 2010-03-27 17:46:23Z skenow $
 */
/**
 * @package     kernel
 *
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 */

/**
 * A handler for "Who is Online?" information
 *
 * @package     kernel
 *
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 * @deprecated	Use icms_core_OnlineHandler, instead
 * @todo		Remove in version 1.4
 */
class XoopsOnlineHandler extends icms_core_OnlineHandler
{

	private $_deprecated;

	/**
	 * Constructor
	 *
	 * @param	object  &$db    {@link XoopsHandlerFactory}
	 */
	public function __construct(&$db) {
		parent::__construct($db);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_core_OnlineHandler', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}

}

