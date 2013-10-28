<?php
/**
 * XOOPS authentification class
 * Authorization classes, xoops authorization class file
 * @copyright    http://www.xoops.org/ The XOOPS Project
 * @copyright    XOOPS_copyrights.txt
 * @copyright    http://www.impresscms.org/ The ImpressCMS Project
 * @license      LICENSE.txt
 * @package      Authorization
 * @since        XOOPS
 * @author       http://www.xoops.org The XOOPS Project
 * @author       modified by UnderDog <underdog@impresscms.org>
 * @version      $Id: auth_xoops.php 19787 2010-07-13 15:37:14Z skenow $
 */
/**
 * Authentification class for Native XOOPS
 * @package      kernel
 * @subpackage   auth
 * @author       Pierre-Eric MENUET <pemphp@free.fr>
 * @copyright    copyright (c) 2000-2003 XOOPS.org
 */
class XoopsAuthXoops extends icms_auth_Xoops
{
	private $_deprecated;
	public function __construct() {
		parent::__construct();
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_auth_Xoops', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}
?>