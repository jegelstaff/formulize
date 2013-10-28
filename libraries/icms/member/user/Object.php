<?php
/**
 * Manage users
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @category	ICMS
 * @package		Member
 * @subpackage	User
 * @version		SVN: $Id: Object.php 21105 2011-03-19 00:39:26Z m0nty_ $
 */

defined('ICMS_ROOT_PATH') or exit();
/**
 * Class for users
 * @author		Kazumi Ono <onokazu@xoops.org>
 * @category	ICMS
 * @package		Member
 * @subpackage	User
 */
class icms_member_user_Object extends icms_core_Object {
	/**
	 * Array of groups that user belongs to
	 * @var array
	 */
	private $_groups = array();
	/**
	 * @var bool is the user admin?
	 */
	static private $_isAdmin = array();
	/**
	 * @var string user's rank
	 */
	private $_rank = null;
	/**
	 * @var bool is the user online?
	 */
	private $_isOnline = null;

	/**
	 * constructor
	 * @param array $id Array of key-value-pairs to be assigned to the user. (for backward compatibility only)
	 * @param int $id ID of the user to be loaded from the database.
	 */
	public function __construct($id = null) {
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
		if (isset($id)) {
			if (is_array($id)) {
				$this->assignVars($id);
			} else {
				$member_handler = icms::handler('icms_member');
				$user =& $member_handler->getUser($id);
				foreach ($user->vars as $k => $v) {
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
	public function isGuest() {
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
	static public function getUnameFromId($userid, $usereal = 0) {
		$userid = (int) $userid;
		$usereal = (int) $usereal;
		if ($userid > 0) {
			$member_handler = icms::handler('icms_member');
			$user =& $member_handler->getUser($userid);
			if (is_object($user)) {
				if ($usereal) {
					$name = $user->getVar('name');
					if ($name != '') {
						return icms_core_DataFilter::htmlSpecialChars($name);
					} else {
						return icms_core_DataFilter::htmlSpecialChars($user->getVar('uname'));
					}
				} else {
					return icms_core_DataFilter::htmlSpecialChars($user->getVar('uname'));
				}
			}
		}
		return $GLOBALS['icmsConfig']['anonymous'];
	}

	/**
	 * increase the number of posts for the user
	 *
	 * @deprecated	Use the handler, instead
	 * @todo		Remove in version 1.4 - there are no occurrences in the core
	 */
	public function incrementPost() {
		icms_core_Debug::setDeprecated('icms_member_Handler->updateUserByField', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		$member_handler = icms::handler('icms_member');
		return $member_handler->updateUserByField($this, 'posts', $this->getVar('posts') + 1);
	}

	/**
	 * set the groups for the user
	 *
	 * @param array $groupsArr Array of groups that user belongs to
	 */
	public function setGroups($groupsArr) {
		if (is_array($groupsArr)) {
			$this->_groups =& $groupsArr;
		}
	}

	/**
	 * sends a welcome message to the user which account has just been activated
	 *
	 * return TRUE if success, FALSE if not
	 */
	public function sendWelcomeMessage() {
		global $icmsConfig, $icmsConfigUser;

		if (!$icmsConfigUser['welcome_msg']) return true;

		$xoopsMailer = new icms_messaging_Handler();
		$xoopsMailer->useMail();
		$xoopsMailer->setBody($icmsConfigUser['welcome_msg_content']);
		$xoopsMailer->assign('UNAME', $this->getVar('uname'));
		$user_email = $this->getVar('email');
		$xoopsMailer->assign('X_UEMAIL', $user_email);
		$xoopsMailer->setToEmails($user_email);
		$xoopsMailer->setFromEmail($icmsConfig['adminmail']);
		$xoopsMailer->setFromName($icmsConfig['sitename']);
		$xoopsMailer->setSubject(sprintf(_US_YOURREGISTRATION, icms_core_DataFilter::stripSlashesGPC($icmsConfig['sitename'])));
		if (!$xoopsMailer->send(true)) {
			$this->setErrors(_US_WELCOMEMSGFAILED);
			return false;
		} else {
			return true;
		}
	}

	/**
	 * sends a notification to admins to inform them that a new user registered
	 *
	 * This method first checks in the preferences if we need to send a notification to admins upon new user
	 * registration. If so, it sends the mail.
	 *
	 * return TRUE if success, FALSE if not
	 */
	public function newUserNotifyAdmin() {
		global $icmsConfigUser, $icmsConfig;

		if ($icmsConfigUser['new_user_notify'] == 1 && !empty($icmsConfigUser['new_user_notify_group'])) {
			$member_handler = icms::handler('icms_member');
			$xoopsMailer = new icms_messaging_Handler();
			$xoopsMailer->useMail();
			$xoopsMailer->setTemplate('newuser_notify.tpl');
			$xoopsMailer->assign('UNAME', $this->getVar('uname'));
			$xoopsMailer->assign('EMAIL', $this->getVar('email'));
			$xoopsMailer->setToGroups($member_handler->getGroup($icmsConfigUser['new_user_notify_group']));
			$xoopsMailer->setFromEmail($icmsConfig['adminmail']);
			$xoopsMailer->setFromName($icmsConfig['sitename']);
			$xoopsMailer->setSubject(sprintf(_US_NEWUSERREGAT, $icmsConfig['sitename']));
			if (!$xoopsMailer->send(true)) {
				$this->setErrors(_US_NEWUSERNOTIFYADMINFAIL);
				return false;
			} else {
				return true;
			}
		} else {
			return true;
		}
	}

	/**
	 * get the groups that the user belongs to
	 *
	 * @return array array of groups
	 */
	public function &getGroups() {
		if (empty($this->_groups)) {
			$member_handler = icms::handler('icms_member');
			$this->_groups =& $member_handler->getGroupsByUser($this->getVar('uid'));
		}
		return $this->_groups;
	}

	/**
	 * alias for {@link getGroups()}
	 * @see getGroups()
	 * @return array array of groups
	 * @deprecated	Use getGroups(), instead
	 * @todo		Remove in version 1.4 - no occurrences in the core
	 */
	public function &groups() {
		icms_core_Debug::setDeprecated('$this->getGroups', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
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
	 * @staticvar array $buffer result buffer
	 * @return bool is the user admin of that module?
	 */
	public function isAdmin($module_id = null) {
		static $buffer = array();
		if (is_null($module_id)) {
			$module_id = isset($GLOBALS['xoopsModule']) ? $GLOBALS['xoopsModule']->getVar('mid', 'n') : 1;
		} elseif((int) $module_id < 1) {$module_id = 0;}

		if (!isset($buffer[$module_id])) {
			$moduleperm_handler = icms::handler('icms_member_groupperm');
			$buffer[$module_id] = $moduleperm_handler->checkRight('module_admin', $module_id, $this->getGroups());
		}
		return $buffer[$module_id];
	}

	/**
	 * get the user's rank
	 * @return array array of rank ID and title
	 */
	public function rank() {
		if (!isset($this->_rank)) {
			$this->_rank = icms_getModuleHandler("userrank", "system")->getRank($this->getVar('rank'), $this->getVar('posts'));
		}
		return $this->_rank;
	}

	/**
	 * is the user activated?
	 * @return bool
	 */
	public function isActive() {
		if ($this->getVar('level') <= 0) {return false;}
		return true;
	}

	/**
	 * is the user currently logged in?
	 * @return bool
	 */
	public function isOnline() {
		if (!isset($this->_isOnline)) {
			$onlinehandler = icms::handler('icms_core_Online');
			$this->_isOnline =
				($onlinehandler->getCount(new icms_db_criteria_Item('online_uid', $this->getVar('uid'))) > 0)
				? true
				: false;
		}
		return $this->_isOnline;
	}

	/**#@+
	 * specialized wrapper for {@link icms_core_Object::getVar()}
	 *
	 * kept for compatibility reasons.
	 *
	 * @see icms_core_Object::getVar()
	 * @deprecated	Use user->getVar, instead
	 * @todo		Remove in version 1.4
	 */
	/**
	 * get the users UID
	 * @return int
	 * all instances have been removed from the core
	 */
	public function uid() {
		icms_core_Debug::setDeprecated('$this->getVar("uid")', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return $this->getVar('uid');
	}

	/**
	 * get the users name
	 * @param string $format format for the output, see {@link icms_core_Object::getVar()}
	 * @return string
	 * No occurrences found in the core
	 */
	function name($format='S')
	{
		icms_core_Debug::setDeprecated('$this->getVar("name", $format)', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return $this->getVar('name', $format);
	}

	/**
	 * get the user's uname
	 * @param string $format format for the output, see {@link icms_core_Object::getVar()}
	 * @return string
	 * All occurrences removed from the core
	 */
	function uname($format='S')
	{
		icms_core_Debug::setDeprecated('$this->getVar("uname", $format)', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return $this->getVar('uname', $format);
	}

	/**
	 * get the user's login_name
	 * @param string $format format for the output, see {@link icms_core_Object::getVar()}
	 * @return string
	 * no occurrences found in the core
	 */
	function login_name($format='S')
	{
		icms_core_Debug::setDeprecated('$this->getVar("login_name", $format)', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return $this->getVar('login_name', $format);
	}

	/**
	 * get the user's email
	 *
	 * @param string $format format for the output, see {@link icms_core_Object::getVar()}
	 * @return string
	 * removed all occurrences in the core
	 */
	function email($format='S')
	{
		icms_core_Debug::setDeprecated('$this->getVar("email", $format)', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return $this->getVar('email', $format);
	}
	/* no occurrences found in the core */
	function url($format='S')
	{
		icms_core_Debug::setDeprecated('$this->getVar("url", $format)', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return $this->getVar('url', $format);
	}
	/* no occurrences found in the core */
	function user_avatar($format='S')
	{
		icms_core_Debug::setDeprecated('$this->getVar("user_avatar")', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return $this->getVar('user_avatar');
	}
	/* no occurrences found in the core */
	function user_regdate()
	{
		icms_core_Debug::setDeprecated('$this->getVar("user_regdate")', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return $this->getVar('user_regdate');
	}
	/* no occurrences found in the core */
	function user_icq($format='S')
	{
		icms_core_Debug::setDeprecated('$this->getVar("user_icq")', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return $this->getVar('user_icq', $format);
	}
	/* no occurrences found in the core */
	function user_from($format='S')
	{
		icms_core_Debug::setDeprecated('$this->getVar("user_from")', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return $this->getVar('user_from', $format);
	}
	/* no occurrences found in the core */
	function user_sig($format='S')
	{
		icms_core_Debug::setDeprecated('$this->getVar("user_sig", $format)', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return $this->getVar('user_sig', $format);
	}
	/* all occurrences replaced in the core */
	function user_viewemail()
	{
		icms_core_Debug::setDeprecated('$this->getVar("user_viewemail")', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return $this->getVar('user_viewemail');
	}
	/* no occurrences found in the core */
	function actkey()
	{
		icms_core_Debug::setDeprecated('$this->getVar("actkey")', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return $this->getVar('actkey');
	}
	/* no occurrences found in the core */
	function user_aim($format='S')
	{
		icms_core_Debug::setDeprecated('$this->getVar("user_aim", $format)', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return $this->getVar('user_aim', $format);
	}
	/* no occurrences found in the core */
	function user_yim($format='S')
	{
		icms_core_Debug::setDeprecated('$this->getVar("user_yim", $format)', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return $this->getVar('user_yim', $format);
	}
	/* no occurrences found in the core */
	function user_msnm($format='S')
	{
		icms_core_Debug::setDeprecated('$this->getVar("user_msnm", $format)', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return $this->getVar('user_msnm', $format);
	}
	/* no occurrences found in the core */
	function pass()
	{
		icms_core_Debug::setDeprecated('$this->getVar("pass")', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return $this->getVar('pass');
	}
	/* no occurrences found in the core */
	function posts()
	{
		icms_core_Debug::setDeprecated('$this->getVar("posts")', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return $this->getVar('posts');
	}
	/* no occurrences found in the core */
	function attachsig()
	{
		icms_core_Debug::setDeprecated('$this->getVar("attachsig")', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return $this->getVar("attachsig");
	}
	/* no occurrences found in the core */
	function level()
	{
		icms_core_Debug::setDeprecated('$this->getVar("level")', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return $this->getVar('level');
	}
	/* all occurrences replaced in the core */
	function theme()
	{
		icms_core_Debug::setDeprecated('$this->getVar("theme")', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return $this->getVar('theme');
	}
	/* no occurrences found in the core */
	function timezone()
	{
		icms_core_Debug::setDeprecated('$this->getVar("timezone_offset")', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return $this->getVar('timezone_offset');
	}
	/* no occurrences found in the core */
	function umode()
	{
		icms_core_Debug::setDeprecated('$this->getVar("umode")', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return $this->getVar('umode');
	}
	/* no occurrences found in the core */
	function uorder()
	{
		icms_core_Debug::setDeprecated('$this->getVar("uorder")', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return $this->getVar('uorder');
	}
	// RMV-NOTIFY
	/* all occurrences replaced in the core */
	function notify_method()
	{
		icms_core_Debug::setDeprecated('$this->getVar("notify_method")', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return $this->getVar('notify_method');
	}
	/* no occurrences found in the core */
	function notify_mode()
	{
		icms_core_Debug::setDeprecated('$this->getVar("notify_mode")', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return $this->getVar('notify_mode');
	}
	/* no occurrences found in the core */
	function user_occ($format='S')
	{
		icms_core_Debug::setDeprecated('$this->getVar("user_occ")', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return $this->getVar('user_occ', $format);
	}
	/* no occurrences found in the core */
	function bio($format='S')
	{
		icms_core_Debug::setDeprecated('$this->getVar("bio", $format)', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return $this->getVar('bio', $format);
	}
	/* no occurrences found in the core */
	function user_intrest($format='S')
	{
		icms_core_Debug::setDeprecated('$this->getVar("user_intrest", $format)', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return $this->getVar('user_intrest', $format);
	}
	/* no occurrences found in the core */
	function last_login()
	{
		icms_core_Debug::setDeprecated('$this->getVar("last_login")', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return $this->getVar('last_login');
	}
	/* all occurrences replaced in the core */
	function language()
	{
		icms_core_Debug::setDeprecated('$this->getVar("language")', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return $this->getVar('language');
	}
	/* no occurrences found in the core */
	function openid()
	{
		icms_core_Debug::setDeprecated('$this->getVar("openid")', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return $this->getVar('openid');
	}
	/* no occurrences found in the core */
	function salt()
	{
		icms_core_Debug::setDeprecated('$this->getVar("salt")', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return $this->getVar('salt');
	}
	/* no occurrences found in the core */
	function pass_expired()
	{
		icms_core_Debug::setDeprecated('$this->getVar("pass_expired")', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return $this->getVar('pass_expired');
	}
	/* no occurrences found in the core */
	function enc_type()
	{
		icms_core_Debug::setDeprecated('$this->getVar("enc_type")', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return $this->getVar('enc_type');
	}
	/* all occurrences replaced in the core */
	function user_viewoid()
	{
		icms_core_Debug::setDeprecated('$this->getVar("user_viewoid")', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
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
	public function gravatar($rating = false, $size = false, $default = false, $border = false, $overwrite = false) {
		if (!$overwrite && is_file(ICMS_UPLOAD_PATH . '/' . $this->getVar('user_avatar')) && $this->getVar('user_avatar') != 'blank.gif') {
			return ICMS_UPLOAD_URL . '/' . $this->getVar('user_avatar');
		}
		$ret = "http://www.gravatar.com/avatar/" . md5(strtolower($this->getVar('email', 'E'))) . "?d=identicon";
		if ($rating && $rating != '') {$ret .= "&amp;rating=" . $rating;}
		if ($size && $size != '') {$ret .="&amp;size=" . $size;}
		if ($default && $default != '') {$ret .= "&amp;default=" . urlencode($default);}
		if ($border && $border != '') {$ret .= "&amp;border=" . $border;}
		return $ret;
	}
}
