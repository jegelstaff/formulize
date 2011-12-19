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
/**
 * @package kernel
 * @copyright copyright &copy; 2000 XOOPS.org
 */
/**
 * Class for users
 * @author Kazumi Ono <onokazu@xoops.org>
 * @copyright copyright (c) 2000-2003 XOOPS.org
 * @package kernel
 * @subpackage users 
 */
class XoopsUser extends XoopsObject
{
	/**
	* Array of groups that user belongs to
	* @var array
	* @access private
	*/
	var $_groups = array();
	/**
	* @var bool is the user admin?
	* @access private
	*/
	var $_isAdmin = null;
	/**
	* @var string user's rank
	* @access private
	*/
	var $_rank = null;
	/**
	* @var bool is the user online?
	* @access private
	*/
	var $_isOnline = null;
	
	/**
	* constructor
	* @param array $id Array of key-value-pairs to be assigned to the user. (for backward compatibility only)
	* @param int $id ID of the user to be loaded from the database.
	*/
	function XoopsUser($id = null)
	{
		$this->initVar('uid', XOBJ_DTYPE_INT, null, false);
		$this->initVar('name', XOBJ_DTYPE_TXTBOX, null, false, 60);
		$this->initVar('uname', XOBJ_DTYPE_TXTBOX, null, true, 255);
		$this->initVar('email', XOBJ_DTYPE_TXTBOX, null, true, 60);
		$this->initVar('url', XOBJ_DTYPE_TXTBOX, null, false, 255);
		$this->initVar('user_avatar', XOBJ_DTYPE_TXTBOX, null, false, 30);
		$this->initVar('user_regdate', XOBJ_DTYPE_INT, null, false);
		$this->initVar('user_icq', XOBJ_DTYPE_TXTBOX, null, false, 15);
		$this->initVar('user_from', XOBJ_DTYPE_TXTBOX, null, false, 100);
		$this->initVar('user_sig', XOBJ_DTYPE_TXTAREA, null, false, null);
		$this->initVar('user_viewemail', XOBJ_DTYPE_INT, 0, false);
		$this->initVar('actkey', XOBJ_DTYPE_OTHER, null, false);
		$this->initVar('user_aim', XOBJ_DTYPE_TXTBOX, null, false, 18);
		$this->initVar('user_yim', XOBJ_DTYPE_TXTBOX, null, false, 25);
		$this->initVar('user_msnm', XOBJ_DTYPE_TXTBOX, null, false, 100);
		$this->initVar('pass', XOBJ_DTYPE_TXTBOX, null, false, 255);
		$this->initVar('posts', XOBJ_DTYPE_INT, null, false);
		$this->initVar('attachsig', XOBJ_DTYPE_INT, 0, false);
		$this->initVar('rank', XOBJ_DTYPE_INT, 0, false);
		$this->initVar('level', XOBJ_DTYPE_TXTBOX, 0, false);
		$this->initVar('theme', XOBJ_DTYPE_OTHER, null, false);
		$this->initVar('timezone_offset', XOBJ_DTYPE_OTHER, null, false);
		$this->initVar('last_login', XOBJ_DTYPE_INT, 0, false);
		$this->initVar('umode', XOBJ_DTYPE_OTHER, null, false);
		$this->initVar('uorder', XOBJ_DTYPE_INT, 1, false);
		// RMV-NOTIFY
		$this->initVar('notify_method', XOBJ_DTYPE_OTHER, 1, false);
		$this->initVar('notify_mode', XOBJ_DTYPE_OTHER, 0, false);
		$this->initVar('user_occ', XOBJ_DTYPE_TXTBOX, null, false, 100);
		$this->initVar('bio', XOBJ_DTYPE_TXTAREA, null, false, null);
		$this->initVar('user_intrest', XOBJ_DTYPE_TXTBOX, null, false, 150);
		$this->initVar('user_mailok', XOBJ_DTYPE_INT, 1, false);

		$this->initVar('language', XOBJ_DTYPE_OTHER, null, false);
		$this->initVar('openid', XOBJ_DTYPE_TXTBOX, '', false, 255);
		$this->initVar('salt', XOBJ_DTYPE_TXTBOX, null, false, 255);
			$this->initVar('user_viewoid', XOBJ_DTYPE_INT, 0, false);
		$this->initVar('pass_expired', XOBJ_DTYPE_INT, 0, false);
		$this->initVar('enc_type', XOBJ_DTYPE_INT, 0, false);
		$this->initVar('login_name', XOBJ_DTYPE_TXTBOX, null, true, 255);

		// for backward compatibility
		if(isset($id))
		{
			if(is_array($id))
			{
				$this->assignVars($id);
			}
			else
			{
				$member_handler =& xoops_gethandler('member');
				$user =& $member_handler->getUser($id);
				foreach($user->vars as $k => $v)
				{
					$this->assignVar($k, $v['value']);
				}
			}
		}
	}

	/**
	* check if the user is a guest user
	*
	* @return bool returns false
	*/
	function isGuest()
	{
		return false;
	}

	/**
	* Updated by Catzwolf 11 Jan 2004
	* find the username for a given ID
	*
	* @param int $userid ID of the user to find
	* @param int $usereal switch for usename or realname
	* @return string name of the user. name for "anonymous" if not found.
	*/
	function getUnameFromId($userid, $usereal = 0)
	{
		$userid = intval($userid);
		$usereal = intval($usereal);
		if($userid > 0)
		{
			$member_handler =& xoops_gethandler('member');
			$user =& $member_handler->getUser($userid);
			if(is_object($user))
			{
				$ts =& MyTextSanitizer::getInstance();
				if($usereal)
				{
					$name = $user->getVar('name');
					if($name != '')
					{
						return $ts->htmlSpecialChars($name);
					}
					else
					{
						return $ts->htmlSpecialChars($user->getVar('uname'));
					}
				}
				else
				{
					return $ts->htmlSpecialChars($user->getVar('uname'));
				}
			}
		}
		return $GLOBALS['xoopsConfig']['anonymous'];
	}

	/**
	* increase the number of posts for the user
	*
	* @deprecated
	*/
	function incrementPost()
	{
		$member_handler =& xoops_gethandler('member');
			return $member_handler->updateUserByField($this, 'posts', $this->getVar('posts') + 1);
	}

	/**
	* set the groups for the user
	*
	* @param array $groupsArr Array of groups that user belongs to
	*/
	function setGroups($groupsArr)
	{
		if(is_array($groupsArr))
		{
			$this->_groups =& $groupsArr;
		}
	}

	/**
	* sends a welcome message to the user which account has just been activated
	*
	* return TRUE if success, FALSE if not
	*/
	function sendWelcomeMessage()
	{
		global $icmsConfig, $icmsConfigUser;

		$myts =& MyTextSanitizer::getInstance();

		if(!$icmsConfigUser['welcome_msg']) {return true;}

		$xoopsMailer =& getMailer();
		$xoopsMailer->useMail();
		$xoopsMailer->setBody($icmsConfigUser['welcome_msg_content']);
		$xoopsMailer->assign('UNAME', $this->getVar('uname'));
		$user_email = $this->getVar('email');
		$xoopsMailer->assign('X_UEMAIL', $user_email);
		$xoopsMailer->setToEmails($user_email);
		$xoopsMailer->setFromEmail($icmsConfig['adminmail']);
		$xoopsMailer->setFromName($icmsConfig['sitename']);
		$xoopsMailer->setSubject(sprintf(_US_YOURREGISTRATION, $myts->stripSlashesGPC($icmsConfig['sitename'])));
		if(!$xoopsMailer->send(true))
		{
			$this->setErrors(_US_WELCOMEMSGFAILED);
			return false;
		}
		else{return true;}
	}

	/**
	* sends a notification to admins to inform them that a new user registered
	* 
	* This method first checks in the preferences if we need to send a notification to admins upon new user
	* registration. If so, it sends the mail.
	*
	* return TRUE if success, FALSE if not
	*/
	function newUserNotifyAdmin()
	{
		global $icmsConfigUser, $icmsConfig;
		
		if($icmsConfigUser['new_user_notify'] == 1 && !empty($icmsConfigUser['new_user_notify_group']))
		{
			$member_handler = xoops_getHandler('member');
			$xoopsMailer =& getMailer();
			$xoopsMailer->useMail();
			$xoopsMailer->setTemplate('newuser_notify.tpl');
			$xoopsMailer->assign('UNAME', $this->getVar('uname'));
			$xoopsMailer->assign('EMAIL', $this->getVar('email'));
			$xoopsMailer->setToGroups($member_handler->getGroup($icmsConfigUser['new_user_notify_group']));
			$xoopsMailer->setFromEmail($icmsConfig['adminmail']);
			$xoopsMailer->setFromName($icmsConfig['sitename']);
			$xoopsMailer->setSubject(sprintf(_US_NEWUSERREGAT,$icmsConfig['sitename']));
			if(!$xoopsMailer->send(true))
			{
				$this->setErrors(_US_NEWUSERNOTIFYADMINFAIL);
				return false;
			}
			else{return true;}
		}
		else{return true;}
	}

	/**
	* get the groups that the user belongs to
	*
	* @return array array of groups
	*/
	function &getGroups()
	{
		if(empty($this->_groups))
		{
			$member_handler =& xoops_gethandler('member');
			$this->_groups =& $member_handler->getGroupsByUser($this->getVar('uid'));
		}
		return $this->_groups;
	}

	/**
	* alias for {@link getGroups()}
	* @see getGroups()
	* @return array array of groups
	* @deprecated
	*/
	function &groups()
	{
		$groups =& $this->getGroups();
		return $groups;
	}

	/**
	* Is the user admin ?
	*
	* This method will return true if this user has admin rights for the specified module.<br />
	* - If you don't specify any module ID, the current module will be checked.<br />
	* - If you set the module_id to -1, it will return true if the user has admin rights for at least one module
	*
	* @param int $module_id check if user is admin of this module
	* @return bool is the user admin of that module?
	*/
	function isAdmin($module_id = null)
	{
		if(is_null($module_id))
		{
			$module_id = isset($GLOBALS['xoopsModule']) ? $GLOBALS['xoopsModule']->getVar('mid', 'n') : 1;
		}
		elseif(intval($module_id) < 1) {$module_id = 0;}

		$moduleperm_handler =& xoops_gethandler('groupperm');
		return $moduleperm_handler->checkRight('module_admin', $module_id, $this->getGroups());
	}

	/**
	* get the user's rank
	* @return array array of rank ID and title
	*/
	function rank()
	{
		if(!isset($this->_rank))
		{
			$this->_rank = xoops_getrank($this->getVar('rank'), $this->getVar('posts'));
		}
		return $this->_rank;
	}

	/**
	* is the user activated?
	* @return bool
	*/
	function isActive()
	{
		if($this->getVar('level') <= 0) {return false;}
		return true;
	}

	/**
	* is the user currently logged in?
	* @return bool
	*/
	function isOnline()
	{
		if(!isset($this->_isOnline))
		{
			$onlinehandler =& xoops_gethandler('online');
			$this->_isOnline = ($onlinehandler->getCount(new Criteria('online_uid', $this->getVar('uid'))) > 0) ? true : false;
		}
		return $this->_isOnline;
	}

	/**#@+
	* specialized wrapper for {@link XoopsObject::getVar()}
	*
	* kept for compatibility reasons.
	*
	* @see XoopsObject::getVar()
	* @deprecated
	*/
	/**
	* get the users UID
	* @return int
	*/
	function uid()
	{
		return $this->getVar('uid');
	}

	/**
	* get the users name
	* @param string $format format for the output, see {@link XoopsObject::getVar()}
	* @return string
	*/
	function name($format='S')
	{
		return $this->getVar('name', $format);
	}

	/**
	* get the user's uname
	* @param string $format format for the output, see {@link XoopsObject::getVar()}
	* @return string
	*/
	function uname($format='S')
	{
		return $this->getVar('uname', $format);
	}

	/**
	* get the user's login_name
	* @param string $format format for the output, see {@link XoopsObject::getVar()}
	* @return string
	*/
	function login_name($format='S')
	{
		return $this->getVar('login_name', $format);
	}

	/**
	* get the user's email
	*
	* @param string $format format for the output, see {@link XoopsObject::getVar()}
	* @return string
	*/
	function email($format='S')
	{
		return $this->getVar('email', $format);
	}
	function url($format='S')
	{
		return $this->getVar('url', $format);
	}
	function user_avatar($format='S')
	{
		return $this->getVar('user_avatar');
	}
	function user_regdate()
	{
		return $this->getVar('user_regdate');
	}
	function user_icq($format='S')
	{
		return $this->getVar('user_icq', $format);
	}
	function user_from($format='S')
	{
		return $this->getVar('user_from', $format);
	}
	function user_sig($format='S')
	{
		return $this->getVar('user_sig', $format);
	}
	function user_viewemail()
	{
		return $this->getVar('user_viewemail');
	}
	function actkey()
	{
		return $this->getVar('actkey');
	}
	function user_aim($format='S')
	{
		return $this->getVar('user_aim', $format);
	}
	function user_yim($format='S')
	{
		return $this->getVar('user_yim', $format);
	}
	function user_msnm($format='S')
	{
		return $this->getVar('user_msnm', $format);
	}
	function pass()
	{
		return $this->getVar('pass');
	}
	function posts()
	{
		return $this->getVar('posts');
	}
	function attachsig()
	{
		return $this->getVar("attachsig");
	}
	function level()
	{
		return $this->getVar('level');
	}
	function theme()
	{
		return $this->getVar('theme');
	}
	function timezone()
	{
		return $this->getVar('timezone_offset');
	}
	function umode()
	{
		return $this->getVar('umode');
	}
	function uorder()
	{
		return $this->getVar('uorder');
	}
	// RMV-NOTIFY
	function notify_method()
	{
		return $this->getVar('notify_method');
	}
	function notify_mode()
	{
		return $this->getVar('notify_mode');
	}
	function user_occ($format='S')
	{
		return $this->getVar('user_occ', $format);
	}
	function bio($format='S')
	{
		return $this->getVar('bio', $format);
	}
	function user_intrest($format='S')
	{
		return $this->getVar('user_intrest', $format);
	}
	function last_login()
	{
		return $this->getVar('last_login');
	}
	function language()
	{
		return $this->getVar('language');
	}
	function openid()
	{
		return $this->getVar('openid');
	}	
	function salt()
	{
		return $this->getVar('salt');
	}
	function pass_expired()
	{
		return $this->getVar('pass_expired');
	}
	function enc_type()
	{
		return $this->getVar('enc_type');
	}
	function user_viewoid()
	{
		return $this->getVar('user_viewoid');
	}

	/**
	* Gravatar plugin for ImpressCMS
	* @author TheRplima
	*
	* @param string $rating
	* @param integer $size (size in pixels of the image. Accept values between 1 to 80. Default 80)
	* @param string $default (url of default avatar. Will be used if no gravatar are found)
	* @param string $border (hexadecimal color)
	*
	* @return string (gravatar or ImpressCMS avatar)
	*/
	function gravatar($rating = false, $size = false, $default = false, $border = false, $overwrite = false)
	{
		if(!$overwrite && file_exists(XOOPS_UPLOAD_PATH.'/'.$this->getVar('user_avatar')) && $this->getVar('user_avatar') != 'blank.gif')
		{
			return XOOPS_UPLOAD_URL.'/'.$this->getVar('user_avatar');
		}
		$ret = "http://www.gravatar.com/avatar/".md5(strtolower($this->getVar('email', 'E')))."?d=identicon";
		if($rating && $rating != ''){$ret .= "&amp;rating=".$rating;}
		if($size && $size != ''){$ret .="&amp;size=".$size;}
		if($default && $default != ''){$ret .= "&amp;default=".urlencode($default);}
		if($border && $border != ''){$ret .= "&amp;border=".$border;}
		return $ret;
	}
	
}

/**
* Class that represents a guest user
* @author Kazumi Ono <onokazu@xoops.org>
* @copyright copyright (c) 2000-2003 XOOPS.org
* @package kernel
*/
class XoopsGuestUser extends XoopsUser
{
	/**
	* check if the user is a guest user
	*
	* @return bool returns true
	*
	*/
	function isGuest()
	{
		return true;
	}
}

/**
* XOOPS user handler class.
* This class is responsible for providing data access mechanisms to the data source
* of XOOPS user class objects.
*
* @author  Kazumi Ono <onokazu@xoops.org>
* @copyright copyright (c) 2000-2003 XOOPS.org
* @package kernel
*/
class XoopsUserHandler extends XoopsObjectHandler
{
	/**
	* create a new user
	*
	* @param bool $isNew flag the new objects as "new"?
	* @return object XoopsUser
	*/
	function &create($isNew = true)
	{
		$user = new XoopsUser();
		if($isNew)
		{
			$user->setNew();
		}
		return $user;
	}

	/**
	* retrieve a user from ID
	*
	* @param int $id UID of the user
	* @return mixed reference to the {@link XoopsUser} object, FALSE if failed
	*/
	function &get($id)
	{
		$id = intval($id);
		$user = false;
		if($id > 0)
		{
			$sql = "SELECT * FROM ".$this->db->prefix('users')." WHERE uid='".$id."'";
			if(!$result = $this->db->query($sql)) {return $user;}
			$numrows = $this->db->getRowsNum($result);
			if($numrows == 1)
			{
				$user = new XoopsUser();
				$user->assignVars($this->db->fetchArray($result));
			}
		}
		return $user;
	}

	/**
	* insert a new user in the database
	*
	* @param object $user reference to the {@link XoopsUser} object
	* @param bool $force
	* @return bool FALSE if failed, TRUE if already present and unchanged or successful
	*/
	function insert(&$user, $force = false)
	{
		/**
		* @TODO: Change to if (!(class_exists($this->className) && $obj instanceof $this->className)) when going fully PHP5
		*/
		if(!is_a($user, 'xoopsuser')) {return false;}
		if(!$user->isDirty()) {return true;}
		if(!$user->cleanVars()) {return false;}
		foreach($user->cleanVars as $k => $v) {${$k} = $v;}

		// RMV-NOTIFY
		if($user->isNew())
		{
			$uid = $this->db->genId($this->db->prefix('users').'_uid_seq');
			$sql = sprintf("INSERT INTO %s (uid, uname, name, email, url, user_avatar, user_regdate, user_icq, user_from, user_sig, user_viewemail, actkey, user_aim, user_yim, user_msnm, pass, posts, attachsig, rank, level, theme, timezone_offset, last_login, umode, uorder, notify_method, notify_mode, user_occ, bio, user_intrest, user_mailok, language, openid, salt, user_viewoid, pass_expired, enc_type, login_name) VALUES ('%u', %s, %s, %s, %s, %s, '%u', %s, %s, %s, '%u', %s, %s, %s, %s, %s, '%u', '%u', '%u', '%u', %s, %s, '%u', %s, '%u', '%u', '%u', %s, %s, %s, '%u', %s, %s, %s, '%u', '%u', '%u', %s)", $this->db->prefix('users'), intval($uid), $this->db->quoteString($uname), $this->db->quoteString($name), $this->db->quoteString($email), $this->db->quoteString($url), $this->db->quoteString($user_avatar), time(), $this->db->quoteString($user_icq), $this->db->quoteString($user_from), $this->db->quoteString($user_sig), intval($user_viewemail), $this->db->quoteString($actkey), $this->db->quoteString($user_aim), $this->db->quoteString($user_yim), $this->db->quoteString($user_msnm), $this->db->quoteString($pass), intval($posts), intval($attachsig), intval($rank), intval($level), $this->db->quoteString($theme), $this->db->quoteString(floatval($timezone_offset)), 0, $this->db->quoteString($umode), intval($uorder), intval($notify_method), intval($notify_mode), $this->db->quoteString($user_occ), $this->db->quoteString($bio), $this->db->quoteString($user_intrest), intval($user_mailok), $this->db->quoteString($language), $this->db->quoteString($openid), $this->db->quoteString($salt), intval($user_viewoid), intval($pass_expired), intval($enc_type), $this->db->quoteString($login_name));
		}
		else
		{
			$sql = sprintf("UPDATE %s SET uname = %s, name = %s, email = %s, url = %s, user_avatar = %s, user_icq = %s, user_from = %s, user_sig = %s, user_viewemail = '%u', user_aim = %s, user_yim = %s, user_msnm = %s, posts = %d, pass = %s, attachsig = '%u', rank = '%u', level= '%s', theme = %s, timezone_offset = %s, umode = %s, last_login = '%u', uorder = '%u', notify_method = '%u', notify_mode = '%u', user_occ = %s, bio = %s, user_intrest = %s, user_mailok = '%u', language = %s, openid = %s, salt = %s, user_viewoid = '%u', pass_expired = '%u', enc_type = '%u', login_name = %s WHERE uid = '%u'", $this->db->prefix('users'), $this->db->quoteString($uname), $this->db->quoteString($name), $this->db->quoteString($email), $this->db->quoteString($url), $this->db->quoteString($user_avatar), $this->db->quoteString($user_icq), $this->db->quoteString($user_from), $this->db->quoteString($user_sig), $user_viewemail, $this->db->quoteString($user_aim), $this->db->quoteString($user_yim), $this->db->quoteString($user_msnm), intval($posts), $this->db->quoteString($pass), intval($attachsig), intval($rank), intval($level), $this->db->quoteString($theme), $this->db->quoteString(floatval($timezone_offset)), $this->db->quoteString($umode), intval($last_login), intval($uorder), intval($notify_method), intval($notify_mode), $this->db->quoteString($user_occ), $this->db->quoteString($bio), $this->db->quoteString($user_intrest), intval($user_mailok), $this->db->quoteString($language), $this->db->quoteString($openid), $this->db->quoteString($salt), intval($user_viewoid), intval($pass_expired), intval($enc_type), $this->db->quoteString($login_name), intval($uid));
		}
		if(false != $force)
		{
			$result = $this->db->queryF($sql);
		}
		else
		{
			$result = $this->db->query($sql);
		}
		if(!$result) {return false;}
		if($user->isNew())
		{
			$uid = $this->db->getInsertId();
			$user->assignVar('uid', $uid);
		}
		return true;
	}

	/**
	* delete a user from the database
	*
	* @param object $user reference to the user to delete
	* @param bool $force
	* @return bool FALSE if failed.
	*/
	function delete(&$user, $force = false)
	{
		/**
		* @TODO: Change to if (!(class_exists($this->className) && $obj instanceof $this->className)) when going fully PHP5
		*/
		if(!is_a($user, 'xoopsuser')) {return false;}
		$pass = substr ( md5 ( time () ), 0, 8 );
		$salt = substr ( md5 ( time () * 2 ), 0, 12 );
		$sql = sprintf("UPDATE %s SET level = '-1', pass = '%s', salt = '%s' WHERE uid = '%u'", $this->db->prefix('users'), $pass, $salt, intval($user->getVar('uid')));
		if(false != $force)
		{
			$result = $this->db->queryF($sql);
		}
		else {$result = $this->db->query($sql);}
		if(!$result) {return false;}
		return true;
	}

	/**
	* retrieve users from the database
	*
	* @param object $criteria {@link CriteriaElement} conditions to be met
	* @param bool $id_as_key use the UID as key for the array?
	* @return array array of {@link XoopsUser} objects
	*/
	function getObjects($criteria = null, $id_as_key = false)
	{
		$ret = array();
		$limit = $start = 0;
		$sql = "SELECT * FROM ".$this->db->prefix('users');
		if(isset($criteria) && is_subclass_of($criteria, 'criteriaelement'))
		{
			$sql .= " ".$criteria->renderWhere();
			if($criteria->getSort() != '') {$sql .= " ORDER BY ".$criteria->getSort()." ".$criteria->getOrder();}
			$limit = $criteria->getLimit();
		$start = $criteria->getStart();
		}
		$result = $this->db->query($sql, $limit, $start);
		if(!$result) {return $ret;}
		while($myrow = $this->db->fetchArray($result))
		{
			$user = new XoopsUser();
			$user->assignVars($myrow);
			if(!$id_as_key)
			{
				$ret[] =& $user;
			}
			else {$ret[$myrow['uid']] =& $user;}
			unset($user);
		}
		return $ret;
	}

	/**
	* count users matching a condition
	*
	* @param object $criteria {@link CriteriaElement} to match
	* @return int count of users
	*/
	function getCount($criteria = null)
	{
		$sql = 'SELECT COUNT(*) FROM '.$this->db->prefix('users');
		if(isset($criteria) && is_subclass_of($criteria, 'criteriaelement')) {$sql .= ' '.$criteria->renderWhere();}
		$result = $this->db->query($sql);
		if(!$result) {return 0;}
		list($count) = $this->db->fetchRow($result);
		return $count;
	}

	/**
	* delete users matching a set of conditions
	*
	* @param object $criteria {@link CriteriaElement}
	* @return bool FALSE if deletion failed
	*/
	function deleteAll($criteria = null)
	{
		$pass = substr ( md5 ( time () ), 0, 8 );
		$salt = substr ( md5 ( time () * 2 ), 0, 12 );
		$sql = sprintf("UPDATE %s SET level= '-1', pass = %s, salt = %s", $this->db->prefix('users'), $pass, $salt);
		if(isset($criteria) && is_subclass_of($criteria, 'criteriaelement')) {$sql .= " ".$criteria->renderWhere();}
			if(!$result = $this->db->query($sql)) {return false;}
		return true;
	}

	/**
	* Change a value for users with a certain criteria
	*
	* @param   string  $fieldname  Name of the field
	* @param   string  $fieldvalue Value to write
	* @param   object  $criteria   {@link CriteriaElement}
	*
	* @return  bool
	**/
	function updateAll($fieldname, $fieldvalue, $criteria = null)
	{
		$set_clause = is_numeric($fieldvalue) ? $fieldname.' = '.$fieldvalue : $fieldname.' = '.$this->db->quoteString($fieldvalue);
		$sql = 'UPDATE '.$this->db->prefix('users').' SET '.$set_clause;
		if(isset($criteria) && is_subclass_of($criteria, 'criteriaelement')) {$sql .= ' '.$criteria->renderWhere();}
		if(!$result = $this->db->query($sql)) {return false;}
		return true;
	}

	/**
	 *  Validates username, email address and password entries during registration
	 *  Username is validated for uniqueness and length, password is validated for length and strictness,
	 *  email is validated as a proper email address pattern  
	 *  
	 *  @param string $uname User display name entered by the user
	 *  @param string $login_name Username entered by the user
	 *  @param string $email Email address entered by the user   
	 *  @param string $pass Password entered by the user
	 *  @param string $vpass Password verification entered by the user
	 *  @return string of errors encountered while validating the user information, will be blank if successful 
	 */	 
	function userCheck($login_name, $uname, $email, $pass, $vpass)
	{
		global $icmsConfigUser, $xoopsDB;
		$myts =& MyTextSanitizer::getInstance();
		include_once ICMS_ROOT_PATH . '/kernel/icmsstopspammer.php';
		$stop = '';
		if (!checkEmail($email)) {
			$stop .= _US_INVALIDMAIL.'<br />';
		}
		foreach ($icmsConfigUser['bad_emails'] as $be) {
			if (!empty($be) && preg_match('/'.$be.'/i', $email)) {
				$stop .= _US_INVALIDMAIL.'<br />';
				break;
			}
		}
		if (strrpos($email,' ') > 0) {
			$stop .= _US_EMAILNOSPACES.'<br />';
		}
		$login_name = xoops_trim($login_name);
		switch ($icmsConfigUser['uname_test_level']) {
		case 0:
			// strict
			$restriction = '/[^a-zA-Z0-9\_\-]/';
			break;
		case 1:
			// medium
			$restriction = '/[^a-zA-Z0-9\_\-\<\>\,\.\$\%\#\@\!\\\'\"]/';
			break;
		case 2:
			// loose
			$restriction = '/[\000-\040]/';
			break;
		}
		$icmsStopSpammers = new IcmsStopSpammer();
		if ($icmsStopSpammers->badUsername($uname)) {
			$stop .= _US_INVALIDNICKNAME . '<br />';
		}
		if ($icmsStopSpammers->badEmail($email)) {
			$stop .= _US_INVALIDMAIL . '<br />';
		}
		if ($icmsStopSpammers->badIP($_SERVER['REMOTE_ADDR'])) {
			$stop .= _US_INVALIDIP . '<br />';
		}
		if (empty($login_name) || preg_match($restriction, $login_name)) {
			$stop .= _US_INVALIDNICKNAME.'<br />';
		}
		if (strlen($login_name) > $icmsConfigUser['maxuname']) {
			$stop .= sprintf(_US_NICKNAMETOOLONG, $icmsConfigUser['maxuname']).'<br />';
		}
		if (strlen($login_name) < $icmsConfigUser['minuname']) {
			$stop .= sprintf(_US_NICKNAMETOOSHORT, $icmsConfigUser['minuname']).'<br />';
		}
		foreach ($icmsConfigUser['bad_unames'] as $bu) {
			if (!empty($bu) && preg_match('/'.$bu.'/i', $login_name)) {
				$stop .= _US_NAMERESERVED.'<br />';
				break;
			}
		}
		if (strrpos($login_name, ' ') > 0) {
			$stop .= _US_NICKNAMENOSPACES.'<br />';
		}
		$sql = sprintf('SELECT COUNT(*) FROM %s WHERE login_name = %s', $xoopsDB->prefix('users'), $xoopsDB->quoteString(addslashes($login_name)));
		$result = $xoopsDB->query($sql);
		list($count) = $xoopsDB->fetchRow($result);
		if ($count > 0) {
			$stop .= _US_LOGINNAMETAKEN.'<br />';
		}
		$count = 0;
		if ( $uname ) {
			$sql = sprintf('SELECT COUNT(*) FROM %s WHERE uname = %s', $xoopsDB->prefix('users'), $xoopsDB->quoteString(addslashes($uname)));
			$result = $xoopsDB->query($sql);
			list($count) = $xoopsDB->fetchRow($result);
			if ( $count > 0 ) {
				$stop .= _US_NICKNAMETAKEN.'<br />';
			}
		}
		$count = 0;
		if ( $email ) {
			$sql = sprintf('SELECT COUNT(*) FROM %s WHERE email = %s', $xoopsDB->prefix('users'), $xoopsDB->quoteString(addslashes($email)));
			$result = $xoopsDB->query($sql);
			list($count) = $xoopsDB->fetchRow($result);
			if ( $count > 0 ) {
				$stop .= _US_EMAILTAKEN.'<br />';
			}
		}
		if ( !isset($pass) || $pass == '' || !isset($vpass) || $vpass == '' ) {
			$stop .= _US_ENTERPWD.'<br />';
		}
		if ( (isset($pass)) && ($pass != $vpass) ) {
			$stop .= _US_PASSNOTSAME.'<br />';
		} elseif ( ($pass != '') && (strlen($pass) < $icmsConfigUser['minpass']) ) {
			$stop .= sprintf(_US_PWDTOOSHORT,$icmsConfigUser['minpass']).'<br />';
		}
		if((isset($pass)) && (isset($login_name)))
		{
			if($pass == $login_name || $pass == icms_utf8_strrev($login_name, true) || strripos($pass, $login_name) === true)
			{
				$stop .= _US_BADPWD.'<br />';
			}
		}
		return $stop;
	}
}
?>