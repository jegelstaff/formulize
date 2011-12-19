<?php
/**
 * Authorization classes, factory class file
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @category	ICMS
 * @package		Auth
 * @author		modified by UnderDog <underdog@impresscms.org>
 * @version		SVN: $Id: Factory.php 20426 2010-11-20 22:28:44Z phoenyx $
 */

/**
 * Authentification class factory
 *
 * @category	ICMS
 * @package     Auth
 * @author	    Pierre-Eric MENUET	<pemphp@free.fr>
 */
class icms_auth_Factory {

	/**
	 * Get a reference to the only instance of authentication class
	 *
	 * if the class has not been instantiated yet, this will also take
	 * care of that
	 * @param   string $uname Username to get Authentication class for
	 * @static
	 * @return  object  Reference to the only instance of authentication class
	 */
	static public function &getAuthConnection($uname) {
		static $auth_instance;
		if (isset($auth_instance)) {
			return $auth_instance;
		} else {
			global $icmsConfigAuth;

			if (empty($icmsConfigAuth['auth_method'])) {
				// If there is a config error, we use xoops
				$auth_method = 'xoops';
			} else {
				$auth_method = $icmsConfigAuth['auth_method'];

				// However if auth_method is XOOPS, and openid login is activated and a user is trying to authenticate with his openid

				/*
				 * @todo we need to add this in the preference
				 */
				$config_to_enable_openid = true;

				if ($icmsConfigAuth['auth_method'] == 'xoops' && $config_to_enable_openid && (isset($_REQUEST['openid_identity']) || isset($_SESSION['openid_response']))) {
					$auth_method = 'openid';
				}
			}
			// Verify if uname allow to bypass LDAP auth
			if (in_array($uname, $icmsConfigAuth['ldap_users_bypass'])) $auth_method = 'xoops';
			/* with autoloading in ImpressCMS 1.3, requiring the file is not necessary */
			$class = 'icms_auth_' . ucfirst($auth_method);
			switch ($auth_method) {
				case 'xoops' :
					$dao =& icms::$xoopsDB;
					break;

				case 'ldap'  :
					$dao = null;
					break;

				case 'ads'  :
					$dao = null;
					break;

				default:
					break;
			}
			$auth_instance = new $class($dao);
			return $auth_instance;
		}
	}
}

