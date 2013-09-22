<?php
/**
 * This class is responsible for some members functions
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @category	ICMS
 * @package		Ipf
 * @subpackage	Member
 * @since		1.2
 * @author		Original idea by Jan Keller Pedersen <mithrandir@xoops.org> - IDG Danmark A/S <www.idg.dk>
 * @author		marcan <marcan@impresscms.org>
 * @author	    Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version		SVN: $Id: Handler.php 10868 2010-12-11 12:02:57Z phoenyx $
 */

defined('ICMS_ROOT_PATH') or die('ImpressCMS root path not defined');

/**
 * Member handler class.
 * This class provides simple interface (a facade class) for handling groups/users/
 * membership data.
 *
 * @category	ICMS
 * @package		Ipf
 * @subpackage	Member
 */
class icms_ipf_member_Handler extends icms_member_Handler {

	/**
	 * constructor
	 *
	 */
	public function __construct(&$db) {
		parent::__construct($db);
		$this->_uHandler = new icms_member_user_Handler($db);
	}

	// This function here below needs some changes to work under 1.2 Final. it's temporarily disabled.
	/*	function addAndActivateUser(&$userObj, $groups=false, $notifyUser=true, &$password=false)
	 {
	 $email = $userObj->getVar('email');
	 if (!$userObj->getVar('email') || $email == '') {
	 $userObj->setErrors(_CO_ICMS_USER_NEED_EMAIL);
	 return false;
	 }

		$password = $userObj->getVar('pass');
		// randomly generating the password if not already set
		if (strlen($password) == 0) {
		$password = substr(md5(uniqid(mt_rand(), 1)), 0, 6);

		}
		$userObj->setVar('pass', md5($password));

		// if no username is set, let's generate one
		$unamecount = 20;
		$uname = $userObj->getVar('uname');
		if (!$uname || $uname == '') {
		$usernames = $this->genUserNames($email, $unamecount);
		$newuser = false;
		$i = 0;
		while ($newuser == false) {
		$crit = new icms_db_criteria_Item('uname', $usernames[$i]);
		$count = $this->getUserCount($crit);
		if ($count == 0) {
		$newuser = true;
		} else {
		//Move to next username
		$i++;
		if ($i == $unamecount) {
		//Get next batch of usernames to try, reset counter
		$usernames = $this->genUserNames($email, $unamecount);
		$i = 0;
		}
		}

		}
		}

		global $icmsConfig, $icmsConfigUser;

		switch ($icmsConfigUser['activation_type']) {
		case 0 :
		$level = 0;
		$mailtemplate = 'smartmail_activate_user.tpl';
		$aInfoMessages[] = sprintf(_NL_MA_NEW_USER_NEED_ACT, $user_email);
		break;
		case 1 :
		$level = 1;
		$mailtemplate = 'smartmail_auto_activate_user.tpl';
		$aInfoMessages[] = sprintf(_NL_MA_NEW_USER_AUTO_ACT, $user_email);
		break;
		case 2 :
		default :
		$level = 0;
		$mailtemplate = 'smartmail_admin_activate_user.tpl';
		$aInfoMessages[] = sprintf(_NL_MA_NEW_USER_ADMIN_ACT, $user_email);
		}

		$userObj->setVar('uname',$usernames[$i]);
		$userObj->setVar('user_avatar','blank.gif');
		$userObj->setVar('user_regdate', time());
		$userObj->setVar('timezone_offset', $icmsConfig['default_TZ']);
		$actkey = substr(md5(uniqid(mt_rand(), 1)), 0, 8);
		$userObj->setVar('actkey', $actkey);
		$userObj->setVar('email',$email);
		$userObj->setVar('notify_method', 2);
		$userObj->setVar('level', $userObj);

		if ($this->insertUser($userObj)) {

		// if $groups=false, Add the user to Registered Users group
		if (!$groups) {
		$this->addUserToGroup(ICMS_GROUP_USERS, $userObj->getVar('uid'));
		} else {
		foreach ($groups as $groupid) {
		$this->addUserToGroup($groupid, $userObj->getVar('uid'));
		}
		}
		} else {
		return false;
		}

		if ($notifyUser) {
		// send some notifications
		$xoopsMailer =  new icms_messaging_Handler();
		$xoopsMailer->useMail();
		$xoopsMailer->setTemplateDir(ICMS_ROOT_PATH . 'language/' . $icmsConfig['language'] . '/mail_template');
		$xoopsMailer->setTemplate('smartobject_notify_user_added_by_admin.tpl');
		$xoopsMailer->assign('XOOPS_USER_PASSWORD', $password);
		$xoopsMailer->assign('SITENAME', $icmsConfig['sitename']);
		$xoopsMailer->assign('ADMINMAIL', $icmsConfig['adminmail']);
		$xoopsMailer->assign('SITEURL', ICMS_URL . "/");
		$xoopsMailer->assign('NAME', $userObj->getVar('name'));
		$xoopsMailer->assign('UNAME', $userObj->getVar('uname'));
		$xoopsMailer->setToUsers($userObj);
		$xoopsMailer->setFromEmail($icmsConfig['adminmail']);
		$xoopsMailer->setFromName($icmsConfig['sitename']);
		$xoopsMailer->setSubject(sprintf(_CO_ICMS_NEW_USER_NOTIFICATION_SUBJECT, $icmsConfig['sitename']));

		if (!$xoopsMailer->send(true)) {
		/**
		* @todo trap error if email was not sent
		*	/
		$xoopsMailer->getErrors(true);
		}
		}

		return true;
		}
		*/
	// This function here below needs some changes to work under 1.2 Final. it's temporarily disabled.

	/**
	 * Generates an array of usernames
	 *
	 * @param string $email email of user
	 * @param string $name name of user
	 * @param int $count number of names to generate
	 * @return array $names
	 * @author xHelp Team
	 */
	public function genUserNames($email, $count=20) {
		$name = substr($email, 0, strpos($email, "@")); //Take the email adress without domain as username

		$names = array();
		$userid   = explode('@',$email);

		$basename = '';
		$hasbasename = false;
		$emailname = $userid[0];

		$names[] = $emailname;

		if (strlen($name) > 0) {
			$name = explode(' ', trim($name));
			if (count($name) > 1) {
				$basename = strtolower(substr($name[0], 0, 1) . $name[count($name) - 1]);
			} else {
				$basename = strtolower($name[0]);
			}
			$basename = icms_core_DataFilter::icms_substr($basename, 0, 60, '');
			//Prevent Duplication of Email Username and Name
			if (!in_array($basename, $names)) {
				$names[] = $basename;
				$hasbasename = true;
			}
		}

		$i = count($names);
		$onbasename = 1;
		while ($i < $count) {
			$num = $this->genRandNumber();
			if ($onbasename < 0 && $hasbasename) {
				$names[] = icms_core_DataFilter::icms_substr($basename, 0, 58, '').$num;

			} else {
				$names[] = icms_core_DataFilter::icms_substr($emailname, 0, 58, ''). $num;
			}
			$i = count($names);
			$onbasename = ~ $onbasename;
			$num = '';
		}

		return $names;

	}

	/**
	 * Creates a random number with a specified number of $digits
	 *
	 * @param int $digits number of digits
	 * @return return int random number
	 * @author xHelp Team
	 */
	public function genRandNumber($digits = 2) {
		$this->initRand();
		$tmp = array();

		for ($i = 0; $i < $digits; $i++) {
			$tmp[$i] = (rand()%9);
		}
		return implode('', $tmp);
	}

	/**
	 * Gives the random number generator a seed to start from
	 *
	 * @return void
	 *
	 * @access public
	 */
	public function initRand() {
		static $randCalled = FALSE;
		if (!$randCalled) {
			srand((double) microtime() * 1000000);
			$randCalled = TRUE;
		}
	}
}

