<?php
// $Id: authfactory.php 8662 2009-05-01 09:04:30Z pesianstranger $
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
* @version	$Id: authfactory.php 8662 2009-05-01 09:04:30Z pesianstranger $
*/

/**
 * Authentification class factory
 *  
 * @package     kernel
 * @subpackage  auth
 * @author	    Pierre-Eric MENUET	<pemphp@free.fr>
 * @copyright	copyright (c) 2000-2005 XOOPS.org
 */
class XoopsAuthFactory
{

  	/**
  	 * Get a reference to the only instance of authentication class
     * 
     * if the class has not been instantiated yet, this will also take 
     * care of that
     * @param   string $uname Username to get Authentication class for
     * @static
     * @return  object  Reference to the only instance of authentication class
  	 */
  	function &getAuthConnection($uname)
  	{
  		static $auth_instance;
  		if (!isset($auth_instance)) {
      		global $icmsConfigAuth;
  			require_once ICMS_ROOT_PATH.'/class/auth/auth.php';
  			if (empty($icmsConfigAuth['auth_method'])) { // If there is a config error, we use xoops
  				$xoops_auth_method = 'xoops';
  			} else {
  			    $xoops_auth_method = $icmsConfigAuth['auth_method'];

			    // However if auth_method is XOOPS, and openid login is activated and a user is trying to authenticate with his openid

			    /*
			     * @todo we need to add this in the preference
			     */
			    $config_to_enable_openid = true;

			    if ($icmsConfigAuth['auth_method'] == 'xoops' && $config_to_enable_openid && (isset($_REQUEST['openid_identity']) || isset($_SESSION['openid_response']))) {
					$xoops_auth_method = 'openid';
			    }
  			}
  			// Verify if uname allow to bypass LDAP auth 
  			if (in_array($uname, $icmsConfigAuth['ldap_users_bypass'])) $xoops_auth_method = 'xoops';
  			$file = ICMS_ROOT_PATH . '/class/auth/auth_' . $xoops_auth_method . '.php';			
  			require_once $file;
  			$class = 'XoopsAuth' . ucfirst($xoops_auth_method);
  			switch ($xoops_auth_method) {
  				case 'xoops' :
  					$dao =& $GLOBALS['xoopsDB'];
  					break;
  				case 'ldap'  : 
  					$dao = null;
  					break;
  				case 'ads'  : 
  					$dao = null;
  					break;

  			}
  			$auth_instance = new $class($dao);
  		}
  		return $auth_instance;
  	}

}


?>