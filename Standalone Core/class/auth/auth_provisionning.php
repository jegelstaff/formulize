<?php
// $Id: auth_provisionning.php 8768 2009-05-16 22:48:26Z pesianstranger $
/**
* Authorization classes, provisionning class file
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	LICENSE.txt
* @package	Authorization
* @since	XOOPS
* @author	http://www.xoops.org The XOOPS Project
* @author	modified by UnderDog <underdog@impresscms.org>
* @version	$Id: auth_provisionning.php 8768 2009-05-16 22:48:26Z pesianstranger $
*/

/**
 * Authentification provisionning class. This class is responsible to
 * provide synchronisation method to Xoops User Database
 * 
 * @package     kernel
 * @subpackage  auth
 * @author	    Pierre-Eric MENUET	<pemphp@free.fr>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 */
class XoopsAuthProvisionning {

  	var $_auth_instance;

    /**
     * Gets instance of {@link XoopsAuthProvisionning}
  	 * @param   object $auth_instance
  	 * @return  object $provis_instance {@link XoopsAuthProvisionning}
     **/
  	function &getInstance(&$auth_instance)
  	{
  		static $provis_instance;
  		if (!isset($provis_instance)) {
  			$provis_instance = new XoopsAuthProvisionning($auth_instance);
  		}
  		return $provis_instance;
  	}

    /**
     * Authentication Service constructor
     * @param object $auth_instance {@link XoopsAuthProvisionning}
     **/
    function XoopsAuthProvisionning(&$auth_instance) {
        $this->_auth_instance = &$auth_instance;        
        global $icmsConfig, $icmsConfigAuth;
        foreach ($icmsConfigAuth as $key => $val) {
            $this->$key = $val;
        }
        $this->default_TZ = $icmsConfig['default_TZ'];
        $this->theme_set = $icmsConfig['theme_set'];
        $this->com_mode = $icmsConfig['com_mode'];
        $this->com_order = $icmsConfig['com_order'];
    }

    /**
  	 * Return a Xoops User Object 
     * @param   string $uname Username of the user
  	 * @return  mixed XoopsUser {@link XoopsUser} or false if failed
  	 */	
  	function getXoopsUser($uname) {
  		$member_handler =& xoops_gethandler('member');
  		$criteria = new Criteria('uname', $uname);
  		$getuser = $member_handler->getUsers($criteria);
  		if (count($getuser) == 1)
  			return $getuser[0];
  		else return false;
  	}

    /**
  	 * Launch the synchronisation process 
     * @param array $datas Some Data
     * @param string $uname Username of the user
     * @param string $pwd Password of the user
  	 * @return object XoopsUser {@link XoopsUser}
  	 */
  	function sync($datas, $uname, $pwd = null) {
  		$icmsUser = $this->getXoopsUser($uname);		
  		if (!$icmsUser) { // Xoops User Database not exists
  			if ($this->ldap_provisionning) { 
  				$icmsUser = $this->add($datas, $uname, $pwd);
  			} else $this->_auth_instance->setErrors(0, sprintf(_AUTH_LDAP_XOOPS_USER_NOTFOUND, $uname));
  		} else { // Xoops User Database exists
  			if ($this->ldap_provisionning && $this->ldap_provisionning_upd) { 
  				$icmsUser = $this->change($icmsUser, $datas, $uname, $pwd);
  			}
  		}
  		return $icmsUser;
  	}

    /**
  	 * Adds a new user to the system
     * @param array $datas Some Data
     * @param string $uname Username of the user
     * @param string $pwd Password of the user
  	 * @return array $ret
  	 */
  	function add($datas, $uname, $pwd = null) {
  		$ret = false;
  		$member_handler =& xoops_gethandler('member');
          // Create ImpressCMS Database User
  		$newuser = $member_handler->createUser();
          $newuser->setVar('uname', $uname);
          $newuser->setVar('pass', md5(stripslashes($pwd)));
          //$newuser->setVar('name', utf8_decode($datas[$this->ldap_givenname_attr][0]) . ' ' . utf8_decode($datas[$this->ldap_surname_attr][0]));
          //$newuser->setVar('email', $datas[$this->ldap_mail_attr][0]);
          $newuser->setVar('rank', 0);
          $newuser->setVar('level', 1);
          $newuser->setVar('timezone_offset', $this->default_TZ);
          $newuser->setVar('theme', 	$this->theme_set);
          $newuser->setVar('umode', 	$this->com_mode);
          $newuser->setVar('uorder', 	$this->com_order);
          $tab_mapping = explode('|', $this->ldap_field_mapping);
          foreach ($tab_mapping as $mapping) {
      			$fields = explode('=', trim($mapping));
      			if ($fields[0] && $fields[1])
    				$newuser->setVar(trim($fields[0]), utf8_decode($datas[trim($fields[1])][0]));
          }
          if ($member_handler->insertUser($newuser)) {
          	foreach ($this->ldap_provisionning_group as $groupid)
          		$member_handler->addUserToGroup($groupid, $newuser->getVar('uid'));
          	$newuser->unsetNew();
          	return $newuser;
          } else {
      			redirect_header(ICMS_URL.'/user.php', 5, $newuser->getHtmlErrors());
  		}
      	return $ret;
  	}

    /**
  	 * Modify user information
     * @param object {@link XoopsUser} reference to XoopsUser Object
     * @param array $datas Some Data
     * @param string $uname Username of the user
     * @param string $pwd Password of the user
  	 * @return object XoopsUser {@link XoopsUser}
  	 */
  	function change(&$icmsUser, $datas, $uname, $pwd = null) {	
  		$ret = false;
  		$member_handler =& xoops_gethandler('member');
  		$icmsUser->setVar('pass', md5(stripslashes($pwd)));
          $tab_mapping = explode('|', $this->ldap_field_mapping);
          foreach ($tab_mapping as $mapping) {
  			$fields = explode('=', trim($mapping));
  			if ($fields[0] && $fields[1])
  				$icmsUser->setVar(trim($fields[0]), utf8_decode($datas[trim($fields[1])][0]));
          }
  		if ($member_handler->insertUser($icmsUser)) {
          	return $icmsUser;
          } else {
  			redirect_header(ICMS_URL.'/user.php', 5, $icmsUser->getHtmlErrors());
  		}         
      	return $ret;
  	}

    /**
  	 * Modify a user
  	 *
  	 * @return bool
  	 */		
  	function delete() {
  	}

    /**
  	 * Suspend a user
  	 *
  	 * @return bool
  	 */		
  	function suspend() {
  	}

    /**
  	 * Restore a user
  	 *
  	 * @return bool
  	 */		
  	function restore() {
  	}

    /**
  	 * Resets Password for the user
  	 *
  	 * @return bool
  	 */		
  	function resetpwd() {
  	}

} // end class

?>