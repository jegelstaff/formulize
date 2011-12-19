<?php
/**
* Manage of users
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	LICENSE.txt
* @package	core
* @since	XOOPS
* @author	http://www.xoops.org The XOOPS Project
* @author	modified by UnderDog <underdog@impresscms.org>
* @version	$Id: user.php 9520 2009-11-11 14:32:52Z pesianstranger $
*/

if(!defined('ICMS_ROOT_PATH')) {exit();}

class XoopsUser extends icms_member_user_Object {
	private $_deprecated;
	public function __construct(&$id) {
		parent::__construct($id);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_member_user_Object', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}

class XoopsGuestUser extends XoopsUser {
	private $_deprecated;
	public function __construct(&$id) {
		parent::__construct($id);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_member_user_Object', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}

class XoopsUserHandler extends icms_member_user_Handler {
	private $_deprecated;
	public function __construct(&$db) {
		parent::__construct($db);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_member_user_Handler', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}

