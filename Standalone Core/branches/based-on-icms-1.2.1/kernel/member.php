<?php
/**
* Manage of members
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	LICENSE.txt
* @package	core
* @since	XOOPS
* @author	http://www.xoops.org The XOOPS Project
* @author	modified by UnderDog <underdog@impresscms.org>
* @version	$Id: member.php 9520 2009-11-11 14:32:52Z pesianstranger $
*/


if (! defined ( 'XOOPS_ROOT_PATH' )) {
    exit ();
}
require_once XOOPS_ROOT_PATH . '/kernel/user.php';
require_once XOOPS_ROOT_PATH . '/kernel/group.php';

/**
* XOOPS member handler class.
* This class provides simple interface (a facade class) for handling groups/users/
* membership data.
*
*
* @author  Kazumi Ono <onokazu@xoops.org>
* @copyright copyright (c) 2000-2003 XOOPS.org
* @package kernel
*/
class XoopsMemberHandler {
    
    /**#@+
    * holds reference to group handler(DAO) class
    * @access private
    */
    var $_gHandler;
    
    /**
    * holds reference to user handler(DAO) class
    */
    var $_uHandler;
    
    /**
    * holds reference to membership handler(DAO) class
    */
    var $_mHandler;
    
    /**
    * holds temporary user objects
    */
    var $_members = array ( );
    /**#@-*/
    
    /**
    * constructor
    *
    */
    function XoopsMemberHandler(&$db) {
        $this->_gHandler = new XoopsGroupHandler ( $db );
        $this->_uHandler = new XoopsUserHandler ( $db );
        $this->_mHandler = new XoopsMembershipHandler ( $db );
    }
    
    /**
    * create a new group
    *
    * @return object XoopsGroup {@link XoopsGroup} reference to the new group
    */
    function &createGroup() {
        $inst = & $this->_gHandler->create ();
        return $inst;
    }
    
    /**
    * create a new user
    *
    * @return object XoopsUser {@link XoopsUser} reference to the new user
    */
    function &createUser() {
        $inst = & $this->_uHandler->create ();
        return $inst;
    }
    
    /**
    * retrieve a group
    *
    * @param int $id ID for the group
    * @return object XoopsGroup {@link XoopsGroup} reference to the group
    */
    function getGroup($id) {
        return $this->_gHandler->get ( $id );
    }
    
    /**
    * retrieve a user
    *
    * @param int $id ID for the user
    * @return object XoopsUser {@link XoopsUser} reference to the user
    */
    function &getUser($id) {
        if (! isset ( $this->_members [$id] )) {
            $this->_members [$id] = & $this->_uHandler->get ( $id );
        }
        return $this->_members [$id];
    }
    
    /**
    * delete a group
    *
    * @param object $group {@link XoopsGroup} reference to the group to delete
    * @return bool FALSE if failed
    */
    function deleteGroup(&$group) {
        $this->_gHandler->delete ( $group );
        $this->_mHandler->deleteAll ( new Criteria ( 'groupid', $group->getVar ( 'groupid' ) ) );
        return true;
    }
    
    /**
    * delete a user
    *
    * @param object $user {@link XoopsUser} reference to the user to delete
    * @return bool FALSE if failed
    */
    function deleteUser(&$user) {
        $this->_uHandler->delete ( $user );
        $this->_mHandler->deleteAll ( new Criteria ( 'uid', $user->getVar ( 'uid' ) ) );
        return true;
    }
    
    /**
    * insert a group into the database
    *
    * @param object $group {@link XoopsGroup} reference to the group to insert
    * @return bool TRUE if already in database and unchanged
    * FALSE on failure
    */
    function insertGroup(&$group) {
        return $this->_gHandler->insert ( $group );
    }
    
    /**
    * insert a user into the database
    *
    * @param object $user {@link XoopsUser} reference to the user to insert
    * @return bool TRUE if already in database and unchanged
    * FALSE on failure
    */
    function insertUser(&$user, $force = false) {
        return $this->_uHandler->insert ( $user, $force );
    }
    
    /**
    * retrieve groups from the database
    *
    * @param object $criteria {@link CriteriaElement}
    * @param bool $id_as_key use the group's ID as key for the array?
    * @return array array of {@link XoopsGroup} objects
    */
    function getGroups($criteria = null, $id_as_key = false) {
        return $this->_gHandler->getObjects ( $criteria, $id_as_key );
    }
    
    /**
    * retrieve users from the database
    *
    * @param object $criteria {@link CriteriaElement}
    * @param bool $id_as_key use the group's ID as key for the array?
    * @return array array of {@link XoopsUser} objects
    */
    function getUsers($criteria = null, $id_as_key = false) {
        return $this->_uHandler->getObjects ( $criteria, $id_as_key );
    }
    
    /**
    * get a list of groupnames and their IDs
    *
    * @param object $criteria {@link CriteriaElement} object
    * @return array associative array of group-IDs and names
    */
    function getGroupList($criteria = null) {
        $groups = $this->_gHandler->getObjects ( $criteria, true );
        $ret = array ( );
        foreach ( array_keys ( $groups ) as $i ) {
            $ret [$i] = $groups [$i]->getVar ( 'name' );
        }
        return $ret;
    }
    
    /**
    * get a list of usernames and their IDs
    *
    * @param object $criteria {@link CriteriaElement} object
    * @return array associative array of user-IDs and names
    */
    function getUserList($criteria = null) {
        $users = $this->_uHandler->getObjects ( $criteria, true );
        $ret = array ( );
        foreach ( array_keys ( $users ) as $i ) {
            $ret [$i] = $users [$i]->getVar ( 'uname' );
        }
        return $ret;
    }
    
    /**
    * add a user to a group
    *
    * @param int $group_id ID of the group
    * @param int $user_id ID of the user
    * @return object XoopsMembership {@link XoopsMembership}
    */
    function addUserToGroup($group_id, $user_id) {
        $mship = & $this->_mHandler->create ();
        $mship->setVar ( 'groupid', $group_id );
        $mship->setVar ( 'uid', $user_id );
        return $this->_mHandler->insert ( $mship );
    }
    
    /**
    * remove a list of users from a group
    *
    * @param int $group_id ID of the group
    * @param array $user_ids array of user-IDs
    * @return bool success?
    */
    function removeUsersFromGroup($group_id, $user_ids = array()) {
        $criteria = new CriteriaCompo ( );
        $criteria->add ( new Criteria ( 'groupid', $group_id ) );
        $criteria2 = new CriteriaCompo ( );
        foreach ( $user_ids as $uid ) {
            $criteria2->add ( new Criteria ( 'uid', $uid ), 'OR' );
        }
        $criteria->add ( $criteria2 );
        return $this->_mHandler->deleteAll ( $criteria );
    }
    
    /**
    * get a list of users belonging to a group
    *
    * @param int $group_id ID of the group
    * @param bool $asobject return the users as objects?
    * @param int $limit number of users to return
    * @param int $start index of the first user to return
    * @return array Array of {@link XoopsUser} objects (if $asobject is TRUE)
    * or of associative arrays matching the record structure in the database.
    */
    function getUsersByGroup($group_id, $asobject = false, $limit = 0, $start = 0) {
        $user_ids = $this->_mHandler->getUsersByGroup ( $group_id, $limit, $start );
        if (! $asobject) {
            return $user_ids;
        } else {
            $ret = array ( );
            foreach ( $user_ids as $u_id ) {
                $user = & $this->getUser ( $u_id );
                if (is_object ( $user )) {
                    $ret [] = & $user;
                }
                unset ( $user );
            }
            return $ret;
        }
    }
    
    /**
    * get a list of groups that a user is member of
    *
    * @param int $user_id ID of the user
    * @param bool $asobject return groups as {@link XoopsGroup} objects or arrays?
    * @return array array of objects or arrays
    */
    function getGroupsByUser($user_id, $asobject = false) {
        $group_ids = $this->_mHandler->getGroupsByUser ( $user_id );
        if (! $asobject) {
            return $group_ids;
        } else {
            foreach ( $group_ids as $g_id ) {
                $ret [] = & $this->getGroup ( $g_id );
            }
            return $ret;
        }
    }
    
    function icms_getLoginFromUserEmail($email = '')
    {
        $db = Database::getInstance();
        include_once ICMS_ROOT_PATH.'/class/database/databaseupdater.php';
        $table = new IcmsDatabasetable('users');

        if($email !== '')
        {
            if($table->fieldExists('loginname'))
            {
                $sql = $db->query("SELECT loginname, email FROM ".$db->prefix('users')." WHERE email =
                    '".@htmlspecialchars($email, ENT_QUOTES, _CHARSET)."'");
            }
            elseif($table->fieldExists('login_name'))
            {
                $sql = $db->query("SELECT login_name, email FROM ".$db->prefix('users')." WHERE email =
                '".@htmlspecialchars($email, ENT_QUOTES, _CHARSET)."'");
            }
            list($uname, $email) = $db->fetchRow($sql);
        }
        else
        {
            redirect_header('user.php',2,_US_SORRYNOTFOUND);
        }
        return $uname;
    }

    /**
    * log in a user
    * @param string $uname username as entered in the login form
    * @param string $pwd password entered in the login form
    * @return object XoopsUser {@link XoopsUser} reference to the logged in user. FALSE if failed to log in
    */
    function loginUser($uname, $pwd)
    {
        include_once ICMS_ROOT_PATH.'/class/icms_Password.php';
        $icmspass = new icms_Password();

        if(strstr($uname, '@'))
        {
            $uname = $this->icms_getLoginFromUserEmail($uname);
        }

        $is_expired = $icmspass->icms_passExpired($uname);
        if($is_expired == 1)
        {
            redirect_header(ICMS_URL.'/user.php?op=resetpass&uname='.$uname, 5, _US_PASSEXPIRED, false);
        }
        $salt = $icmspass->icms_getUserSaltFromUname($uname);
        $pwd = $icmspass->icms_encryptPass($pwd, $salt);
        include_once ICMS_ROOT_PATH.'/class/database/databaseupdater.php';
        $table = new IcmsDatabasetable('users');
        if($table->fieldExists('loginname'))
        {
            $criteria = new CriteriaCompo(new Criteria('loginname', $uname));
        }
        elseif($table->fieldExists('login_name'))
        {
            $criteria = new CriteriaCompo(new Criteria('login_name', $uname));
        }
        else
        {
            $criteria = new CriteriaCompo(new Criteria('uname', $uname));
        }
        $criteria->add(new Criteria('pass', $pwd));
        $user = $this->_uHandler->getObjects($criteria, false);
        if(!$user || count($user) != 1)
        {
            $user = false;
            return $user;
        }
        return $user[0];
    }
    
    /**
    * logs in a user with an md5 encrypted password
    *
    * @param string $uname username
    * @param string $md5pwd password encrypted with md5
    * @return object XoopsUser {@link XoopsUser} reference to the logged in user. FALSE if failed to log in
    */
/*	function &loginUserMd5($uname, $md5pwd) {
        include_once ICMS_ROOT_PATH . '/class/database/databaseupdater.php';
        $table = new IcmsDatabasetable('users');
        if ($table->fieldExists('loginname')) {
        $criteria = new CriteriaCompo ( new Criteria ( 'loginname', $uname ) );
        }elseif ($table->fieldExists('login_name')) {
        $criteria = new CriteriaCompo ( new Criteria ( 'login_name', $uname ) );
        }else{
        $criteria = new CriteriaCompo ( new Criteria ( 'uname', $uname ) );
        }
        $criteria->add ( new Criteria ( 'pass', $md5pwd ) );
        $user = $this->_uHandler->getObjects ( $criteria, false );
        if (! $user || count ( $user ) != 1) {
            $user = false;
            return $user;
        }
        return $user [0];
    } */
    
    /**
    * count users matching certain conditions
    *
    * @param object $criteria {@link CriteriaElement} object
    * @return int
    */
    function getUserCount($criteria = null) {
        return $this->_uHandler->getCount ( $criteria );
    }
    
    /**
    * count users belonging to a group
    *
    * @param int $group_id ID of the group
    * @return int
    */
    function getUserCountByGroup($group_id) {
        return $this->_mHandler->getCount ( new Criteria ( 'groupid', $group_id ) );
    }
    
    /**
    * updates a single field in a users record
    *
    * @param object $user {@link XoopsUser} reference to the {@link XoopsUser} object
    * @param string $fieldName name of the field to update
    * @param string $fieldValue updated value for the field
    * @return bool TRUE if success or unchanged, FALSE on failure
    */
    function updateUserByField(&$user, $fieldName, $fieldValue) {
        $user->setVar ( $fieldName, $fieldValue );
        return $this->insertUser ( $user );
    }
    
    /**
    * updates a single field in a users record
    *
    * @param string $fieldName name of the field to update
    * @param string $fieldValue updated value for the field
    * @param object $criteria {@link CriteriaElement} object
    * @return bool TRUE if success or unchanged, FALSE on failure
    */
    function updateUsersByField($fieldName, $fieldValue, $criteria = null) {
        return $this->_uHandler->updateAll ( $fieldName, $fieldValue, $criteria );
    }
    
    /**
    * activate a user
    *
    * @param object $user {@link XoopsUser} reference to the object
    * @return bool successful?
    */
    function activateUser(&$user) {
        if ($user->getVar ( 'level' ) != 0) {
            return true;
        }
        $user->setVar ( 'level', 1 );
        return $this->_uHandler->insert ( $user, true );
    }
    
    /**
    * Get a list of users belonging to certain groups and matching criteria
    * Temporary solution
    *
    * @param int $groups IDs of groups
    * @param object $criteria {@link CriteriaElement} object
    * @param bool $asobject return the users as objects?
    * @param bool $id_as_key use the UID as key for the array if $asobject is TRUE
    * @return array Array of {@link XoopsUser} objects (if $asobject is TRUE)
    * or of associative arrays matching the record structure in the database.
    */
    function getUsersByGroupLink($groups, $criteria = null, $asobject = false, $id_as_key = false) {
        $ret = array ( );
        
        $select = $asobject ? "u.*" : "u.uid";
        $sql [] = "	SELECT DISTINCT {$select} " . "	FROM " . $this->_uHandler->db->prefix ( "users" ) . " AS u" . "	
LEFT JOIN " . $this->_mHandler->db->prefix ( "groups_users_link" ) . " AS m ON m.uid = u.uid" . "	WHERE 1 = '1'";
        if (! empty ( $groups )) {
            $sql [] = "m.groupid IN (" . implode ( ", ", $groups ) . ")";
        }
        $limit = $start = 0;
        if (isset ( $criteria ) && is_subclass_of ( $criteria, 'criteriaelement' )) {
            $sql_criteria = $criteria->render ();
            if ($criteria->getSort () != '') {
                $sql_criteria .= ' ORDER BY ' . $criteria->getSort () . ' ' . $criteria->getOrder ();
            }
            $limit = $criteria->getLimit ();
            $start = $criteria->getStart ();
            if ($sql_criteria) {
                $sql [] = $sql_criteria;
            }
        }
        $sql_string = implode ( " AND ", array_filter ( $sql ) );
        if (! $result = $this->_uHandler->db->query ( $sql_string, $limit, $start )) {
            return $ret;
        }
        while ( $myrow = $this->_uHandler->db->fetchArray ( $result ) ) {
            if ($asobject) {
                $user = new XoopsUser ( );
                $user->assignVars ( $myrow );
                if (! $id_as_key) {
                    $ret [] = & $user;
                } else {
                    $ret [$myrow ['uid']] = & $user;
                }
                unset ( $user );
            } else {
                $ret [] = $myrow ['uid'];
            }
        }
        return $ret;
    }

    /**
    * Get count of users belonging to certain groups and matching criteria
    * Temporary solution
    *
    * @param int $groups IDs of groups
    * @return int count of users
    */
    function getUserCountByGroupLink($groups, $criteria = null) {
        $ret = 0;
        
        $sql [] = "	SELECT COUNT(DISTINCT u.uid) " . "	FROM " . $this->_uHandler->db->prefix ( "users" ) . " AS u" . "	
LEFT JOIN " . $this->_mHandler->db->prefix ( "groups_users_link" ) . " AS m ON m.uid = u.uid" . "	WHERE 1 = '1'";
        if (! empty ( $groups )) {
            $sql [] = "m.groupid IN (" . implode ( ", ", $groups ) . ")";
        }
        if (isset ( $criteria ) && is_subclass_of ( $criteria, 'criteriaelement' )) {
            $sql [] = $criteria->render ();
        }
        $sql_string = implode ( " AND ", array_filter ( $sql ) );
        if (! $result = $this->_uHandler->db->query ( $sql_string )) {
            return $ret;
        }
        list ( $ret ) = $this->_uHandler->db->fetchRow ( $result );
        return $ret;
    }

    /**
    * Gets the usergroup with the most rights for a specific userid
    *
    * @param int  $uid  the userid to get the usergroup for
    *
    * @return int  the best usergroup belonging to the userid
    */
    function getUserBestGroup($uid) {
        $ret = XOOPS_GROUP_ANONYMOUS;
        $uid = intval ( $uid );
        $gperms = array ( );
        if ($uid <= 0) {
            return $ret;
        }
        
        $groups = $this->getGroupsByUser ( $uid );
        if (in_array ( XOOPS_GROUP_ADMIN, $groups )) {
            $ret = XOOPS_GROUP_ADMIN;
        } else {
            foreach ( $groups as $group ) {
                $sql = 'SELECT COUNT(gperm_id) as total FROM ' . $this->_uHandler->db->prefix ( "group_permission" ) . '
WHERE gperm_groupid=' . $group;
                if (! $result = $this->_uHandler->db->query ( $sql )) {
                    return $ret;
                }
                list($t) = $this->_uHandler->db->fetchRow ( $result );
                $gperms [$group] = $t;
            }
            foreach ( $gperms as $key => $val ) {
                if ($val == max ( $gperms )){
                    $ret = $key;
                    break;
                }
            }
        }
        
        return $ret;
    }

}

?>