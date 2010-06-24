<?php
// $Id: auth_ads.php 8662 2009-05-01 09:04:30Z pesianstranger $
// auth_ads.php - Authentification class for Active Directory
/**
* Authorization classes, Active Directory class file
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	LICENSE.txt
* @package	Authorization
* @since	XOOPS
* @author	http://www.xoops.org The XOOPS Project
* @author	modified by UnderDog <underdog@impresscms.org>
* @version	$Id: auth_ads.php 8662 2009-05-01 09:04:30Z pesianstranger $
*/

/**
 * Authentification class for Active Directory
 *  
 * @package     kernel
 * @subpackage  auth
 * @author	    Pierre-Eric MENUET	<pemphp@free.fr>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 */
include_once ICMS_ROOT_PATH . '/class/auth/auth_ldap.php';
 
class XoopsAuthAds extends XoopsAuthLdap {

   	/**
  	 * Authentication Service constructor
  	 */
    function XoopsAuthAds (&$dao) {
  		parent::XoopsAuthLdap($dao);
    }

    /**
  	 *  Authenticate  user again LDAP directory (Bind)
  	 *  2 options : 
  	 * 		Authenticate directly with uname in the DN
  	 * 		Authenticate with manager, search the dn
  	 *
  	 * @param string $uname Username
  	 * @param string $pwd Password
  	 *
  	 * @return bool
  	 */	
    function authenticate($uname, $pwd = null) {
        $authenticated = false;
        if (!extension_loaded('ldap')) {
            $this->setErrors(0, _AUTH_LDAP_EXTENSION_NOT_LOAD);
            return $authenticated;
        }
        $this->_ds = ldap_connect($this->ldap_server, $this->ldap_port);
        if ($this->_ds) {
            ldap_set_option($this->_ds, LDAP_OPT_PROTOCOL_VERSION, $this->ldap_version);
            ldap_set_option($this->_ds, LDAP_OPT_REFERRALS, 0);
            if ($this->ldap_use_TLS) { // We use TLS secure connection
	   			if (!ldap_start_tls($this->_ds))
					$this->setErrors(0, _AUTH_LDAP_START_TLS_FAILED);
            }            
            // If the uid is not in the DN we proceed to a search
            // The uid is not always in the dn
            $userUPN = $this->getUPN($uname);
            if (!$userUPN) return false;
            // We bind as user to test the credentials         
            $authenticated = ldap_bind($this->_ds, $userUPN, $this->cp1252_to_utf8(stripslashes($pwd)));
            if ($authenticated) {
            	// We load the Xoops User database
            	$dn = $this->getUserDN($uname);
            	if ($dn)
            		return $this->loadXoopsUser($dn, $uname, $pwd);
            	else return false;                
            } else $this->setErrors(ldap_errno($this->_ds), ldap_err2str(ldap_errno($this->_ds)) . '(' . $userUPN . ')');
        }
        else {
            $this->setErrors(0, _AUTH_LDAP_SERVER_NOT_FOUND);              
        }
        @ldap_close($this->_ds);
        return $authenticated;
    }

    /**
  	 *  Return the UPN = userPrincipalName (Active Directory)
  	 *  userPrincipalName = guyt@CP.com    Often abbreviated to UPN, and 
  	 *  looks like an email address.  Very useful for logging on especially in 
  	 *  a large Forest.   Note UPN must be unique in the forest.
  	 * 
  	 * @param string $uname Username
  	 * @return userDN or false
  	 */	    
    function getUPN($uname) {
    	$userDN = false;
	    $userDN = $uname."@".$this->ldap_domain_name;
	    return $userDN;
    }
      
} // end class

?>