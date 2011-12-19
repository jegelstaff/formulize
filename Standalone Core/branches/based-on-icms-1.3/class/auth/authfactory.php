<?php
// $Id: authfactory.php 19118 2010-03-27 17:46:23Z skenow $
// authfactory.php - Authentification class factory
/**
 * Authorization classes, factory class file
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	LICENSE.txt
 * @package	Authorization
 * @since	XOOPS
 * @author	http://www.xoops.org The XOOPS Project
 * @author	modified by UnderDog <underdog@impresscms.org>
 * @version	$Id: authfactory.php 19118 2010-03-27 17:46:23Z skenow $
 */

/**
 * Authentification class factory
 *
 * @package     kernel
 * @subpackage  auth
 * @author	    Pierre-Eric MENUET	<pemphp@free.fr>
 * @copyright	copyright (c) 2000-2005 XOOPS.org
 * @deprecated	Use icms_auth_Factory, instead
 * @todo		Remove in version 1.4
 */
class XoopsAuthFactory extends icms_auth_Factory {
	private $_deprecated;
	public function &getAuthConnection($uname) {
		parent::getAuthConnection($uname);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_auth_Factory', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}
