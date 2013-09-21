<?php
/**
 * Manage of private messages
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	LICENSE.txt
 * @package	core
 * @since	XOOPS
 * @author	http://www.xoops.org The XOOPS Project
 * @author	modified by UnderDog <underdog@impresscms.org>
 * @version	$Id: privmessage.php 19431 2010-06-16 20:46:34Z david-sf $
 */

if (!defined('ICMS_ROOT_PATH')) die("ImpressCMS root path not defined");

/**
 * @package     kernel
 *
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 */

/**
 * A handler for Private Messages
 *
 * @package		kernel
 *
 * @author		Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 The XOOPS Project (http://www.xoops.org)
 *
 * @version		$Revision: 1102 $ - $Date: 2007-10-18 22:55:52 -0400 (jeu., 18 oct. 2007) $
 * @deprecated	Use icms_data_privmessage_Object, instead
 * @todo		Remove in version 1.4
 */
class XoopsPrivmessage extends icms_data_privmessage_Object
{

	private $_deprecated;
	/**
	 * constructor
	 **/
	function XoopsPrivmessage()
	{
		parent::__construct();
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_data_privmessage_Object', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}

/**
 * XOOPS private message handler class.
 *
 * This class is responsible for providing data access mechanisms to the data source
 * of XOOPS private message class objects.
 *
 * @package		kernel
 *
 * @author		Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 The XOOPS Project (http://www.xoops.org)
 *
 * @version		$Revision: 1102 $ - $Date: 2007-10-18 22:55:52 -0400 (jeu., 18 oct. 2007) $
 * @deprecated	Use icms_data_privmessage_Handler, instead
 * @todo		Remove in version 1.4
 *
 */
class XoopsPrivmessageHandler extends icms_data_privmessage_Handler
{
	private $_deprecated;
	public function __construct(&$db) {
		parent::__construct($db);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_data_privmessage_Handler', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}
