<?php
//  ------------------------------------------------------------------------ //
//                XOOPS - PHP Content Management System                      //
//           Registration Codes Module - Freeform Solutions                  //
//                    Copyright (c) 2000 XOOPS.org                           //
//                       <http://www.xoops.org/>                             //
//  ------------------------------------------------------------------------ //
//  This program is free software; you can redistribute it and/or modify     //
//  it under the terms of the GNU General Public License as published by     //
//  the Free Software Foundation; either version 2 of the License, or        //
//  (at your option) any later version.                                      //
//                                                                           //
//  You may not change or alter any portion of this comment or credits       //
//  of supporting developers from this source code or any supporting         //
//  source code which is considered copyrighted (c) material of the          //
//  original comment or credit authors.                                      //
//                                                                           //
//  This program is distributed in the hope that it will be useful,          //
//  but WITHOUT ANY WARRANTY; without even the implied warranty of           //
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            //
//  GNU General Public License for more details.                             //
//                                                                           //
//  You should have received a copy of the GNU General Public License        //
//  along with this program; if not, write to the Free Software              //
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA //
//  ------------------------------------------------------------------------ //

function validateRegCode($code) {
	if($code == "nocode") {
		global $xoopsConfigUser;
		if(empty($xoopsConfigUser['allow_register'])) {
			return false;
		} else {
			return true; 
		}
	}
  	global $xoopsDB, $xoopsConfig;
	$stop = "";
	$code = str_replace("'", "", $code);
	$code = str_replace('"', '', $code);
	$reggroupsq = "SELECT COUNT(*) FROM " . $xoopsDB->prefix("reg_codes") . " WHERE reg_codes_code=\"" . mysql_real_escape_string($code) . "\" AND reg_codes_expiry>" . date('Y-m-d') . " AND (reg_codes_curuses < reg_codes_maxuses OR reg_codes_maxuses = 0)";
	$resreggroupsq = $xoopsDB->query($reggroupsq);
	list($regcodesfound) = $xoopsDB->fetchRow($resreggroupsq);
	if($regcodesfound == 0) {
		return false;
	} else {
		return true;
	}
}



function processCode($regcode, $newid, $return_groups_only = false) {

	$return_groups = array();
	if($code != "nocode") {
		$member_handler =& xoops_gethandler('member');
		$reggroupsq = "SELECT reg_codes_groups, reg_codes_redirect, reg_codes_curuses FROM " . XOOPS_DB_PREFIX . "_reg_codes WHERE reg_codes_code=\"" . mysql_real_escape_string($regcode) . "\"";
		$resreggroupsq = mysql_query($reggroupsq);
		$rowreggroupsq = mysql_fetch_row($resreggroupsq);
		$flatgrouplist = $rowreggroupsq[0];
		$redirect = $rowreggroupsq[1];
		$ccuruses = $rowreggroupsq[2];
		$flatgrouparray = explode("&8(%$", $flatgrouplist);
		foreach($flatgrouparray as $agid) {
			if($agid === "" OR $agid == XOOPS_GROUP_USERS) { continue; } // ignore null values and the Registered users group
			if(!$return_groups_only) {
				if(!$member_handler->addUserToGroup($agid, $newid)) {
					echo _US_REGISTERNG;
					include 'footer.php';
					exit();
				}
			} else {
				$return_groups[] = $agid;
			}
		}
		if($return_groups_only) {
			$return_groups[] = XOOPS_GROUP_USERS;
			return $return_groups;
		}
		// increment the current uses ...
		$ccuruses++;
		$curuinc = "UPDATE " . XOOPS_DB_PREFIX . "_reg_codes SET reg_codes_curuses=\"$ccuruses\" WHERE reg_codes_code=\"$regcode\"";
		$rescui = mysql_query($curuinc);
	} else { // nocode
		if($return_groups_only) {
			$return_groups[] = XOOPS_GROUP_USERS;
			return $return_groups;
		} else {
			$redirect = XOOPS_URL;
		}
	}
	return $redirect;
}

// code borrowed from include/checklogin.php
function loginUser($uname, $pass) {
  		global $xoopsConfig, $myts;
			$member_handler =& xoops_gethandler('member');
			//$user =& $member_handler->loginUser(addslashes($myts->stripSlashesGPC($uname)), addslashes($myts->stripSlashesGPC($pass)));
      include_once XOOPS_ROOT_PATH.'/class/auth/authfactory.php';
      include_once XOOPS_ROOT_PATH.'/language/'.$xoopsConfig['language'].'/auth.php';
      $xoopsAuth =& XoopsAuthFactory::getAuthConnection($myts->addSlashes($uname));
      $user = $xoopsAuth->authenticate($myts->addSlashes($uname), $myts->addSlashes($pass));

			if (false != $user) {
				if (0 == $user->getVar('level')) {
					redirect_header(XOOPS_URL.'/index.php', 5, _US_NOACTTPADM);
					exit();
				}
				if ($xoopsConfig['closesite'] == 1) {
					$allowed = false;
					foreach ($user->getGroups() as $group) {
						if (in_array($group, $xoopsConfig['closesite_okgrp']) || XOOPS_GROUP_ADMIN == $group) {
							$allowed = true;
							break;
						}
					}
					if (!$allowed) {
						redirect_header(XOOPS_URL.'/index.php', 1, _NOPERM);
						exit();
					}
				}
				$user->setVar('last_login', time());
				$member_handler->insertUser($user); // updates user with latest login time and other info
				$GLOBALS['xoopsUser'] = $user;
				$token = $_SESSION['XOOPS_TOKEN_SESSION']; // got to save the token for the current session and then add it to the new logged in session we're about to create, so the form can be accepted properly and not trip up the security check
        session_regenerate_id(true);
				$_SESSION = array();
				$_SESSION['xoopsUserId'] = $user->getVar('uid');
				$_SESSION['XOOPS_TOKEN_SESSION'] = $token;
				$_SESSION['xoopsUserGroups'] = $user->getGroups();
				if ($xoopsConfig['use_mysession'] && $xoopsConfig['session_name'] != '') {
					setcookie($xoopsConfig['session_name'], session_id(), time()+(60 * $xoopsConfig['session_expire']), '/',  '', 0);
				}
				$user_theme = $user->getVar('theme');
				if (in_array($user_theme, $xoopsConfig['theme_set_allowed'])) {
					$_SESSION['xoopsUserTheme'] = $user_theme;
				}
				$notification_handler =& xoops_gethandler('notification');
				$notification_handler->doLoginMaintenance($user->getVar('uid'));
        
			} else {
				exit("Error: failed to login.");
			}
}

function saveProfileInfo($newid, $redirect, $groups="") {

			global $xoopsConfig;
			include_once XOOPS_ROOT_PATH . "/modules/formulize/include/formread.php";
			include_once XOOPS_ROOT_PATH . "/modules/formulize/language/".$xoopsConfig['language']."/main.php";
			$formulize_mgr =& xoops_getmodulehandler('elements', 'formulize');
			$module_handler =& xoops_gethandler('module');
			$config_handler =& xoops_gethandler('config');
		  $formulizeModule =& $module_handler->getByDirname("formulize");
			$formulizeConfig =& $config_handler->getConfigsByCat(0, $formulizeModule->getVar('mid'));
			$fid = $formulizeConfig['profileForm'];
			$entries[$fid][0] = "";
			$owner = $newid;
			$uid = $newid; 
			if(!$groups) { $groups = $_SESSION['xoopsUserGroups']; }
			$owner_groups = $groups;
			handleSubmission($formulize_mgr, $entries, $uid, $owner, $fid, $owner_groups, $groups, "new");
      if(isset($GLOBALS['reg_codes_xoopsMailerToSend'])) {
        $GLOBALS['reg_codes_xoopsMailerToSend']->send();
      }
			if($redirect) {
				redirect_header($redirect, 4, _formulize_ACTCREATED); // changed redirect text
				exit();
			} 
}

// function creates a member when passed the following params:
//$user_viewemail
//$uname
//$name
//$email
//$pass
//$timezone_offset
//$user_mailok (optional)
//returns the uid of the new user
function createMember($params) {
  
		foreach($params as $k=>$v) {
			${$k} = $v;
		}
		global $xoopsConfigUser, $xoopsConfig, $config_handler;
		$member_handler =& xoops_gethandler('member');
		$newuser =& $member_handler->createUser();
		$newuser->setVar('user_viewemail',$user_viewemail, true);
	
		$module_handler =& xoops_gethandler('module');
		$regcodesModule =& $module_handler->getByDirname("reg_codes");
		$regcodesConfig =& $config_handler->getConfigsByCat(0, $regcodesModule->getVar('mid'));
		if ($regcodesConfig['email_as_username'] == 0) {
			$newuser->setVar('uname', $uname, true);
		}
		else {
			$newuser->setVar('uname', $email, true);
		}
		$newuser->setVar('name', $name, true);
		$newuser->setVar('email', $email, true);
		if ($url != '') {
			$newuser->setVar('url', formatURL($url), true);
		}
    
		$newuser->setVar('user_avatar','blank.gif', true);
		$actkey = substr(md5(uniqid(mt_rand(), 1)), 0, 8);
		$newuser->setVar('actkey', $actkey, true);
		$newuser->setVar('pass', md5($pass), true);
		$newuser->setVar('timezone_offset', $timezone_offset, true);
		$newuser->setVar('user_regdate', time(), true);
		$newuser->setVar('uorder',$xoopsConfig['com_order'], true);
		$newuser->setVar('umode',$xoopsConfig['com_mode'], true);
		$newuser->setVar('user_mailok',$user_mailok, true);
		// setup default notification method
		include_once XOOPS_ROOT_PATH . '/include/notification_constants.php';
		if($regcodesConfig['notification_default'] == 1) {
			$newuser->setVar('notify_method', XOOPS_NOTIFICATION_METHOD_EMAIL);
		} else {
			$newuser->setVar('notify_method', XOOPS_NOTIFICATION_METHOD_PM);
		}
//		all users are activated automatically, unless the code requires approval by certain groups
		if(!$approval) {
			$newuser->setVar('level', 1, true);
		}
    
		if (!$member_handler->insertUser($newuser)) {
			echo _US_REGISTERNG;
			include 'footer.php';
			exit();
		}
    
		$newid = $newuser->getVar('uid');
		if (!$member_handler->addUserToGroup(XOOPS_GROUP_USERS, $newid)) {
			echo _US_REGISTERNG;
			include 'footer.php';
			exit();
		}

		// THIS IS THE SIMPLE NOTIFICATION MESSAGE, NOT THE MESSAGE WITH THE APPROVAL KEY.  approval key is sent by notifyAdmin, based on setting in the regcode and in XOOPS
		if ($xoopsConfigUser['new_user_notify'] == 1 && !empty($xoopsConfigUser['new_user_notify_group'])) {
			$xoopsMailer =& getMailer();
			$xoopsMailer->useMail();
			$xoopsMailer->setToGroups($member_handler->getGroup($xoopsConfigUser['new_user_notify_group']));
			$xoopsMailer->setFromEmail($xoopsConfig['adminmail']);
			$xoopsMailer->setFromName($xoopsConfig['sitename']);
			$xoopsMailer->setSubject(sprintf(_US_NEWUSERREGAT,$xoopsConfig['sitename']));
			$xoopsMailer->setBody(sprintf(_US_HASJUSTREG, $uname));
			$GLOBALS['reg_codes_xoopsMailerToSend'] = $xoopsMailer; // in some rare cases, sending the mailer here can cause login problems later, so we store the mailer object and send it later if it exists.  notifyAdmin, confirmUser and SaveProfileInfo are the three possible points where it can be sent (based on logic in the main register.php file that will direct the flow of code in those directions)
		}

		// SEND AN ACKNOWLEDGEMENT MESSSAGE TO THE USER IF NECESSARY
		sendAcknowledgement($newuser, $regcode);

	$GLOBALS['userprofile_uid'] = $newid;
	return array(0=>$newid, 1=>$actkey);
}

// FUNCTION CHECKS TO SEE IF THERE'S A MAIL TEMPLATE ASSOCIATED WITH THIS CODE, AND IF SO, SENDS THE MESSAGE
function sendAcknowledgement($user, $regcode) {
	global $xoopsConfig;
	if(file_exists(XOOPS_ROOT_PATH."/language/".$xoopsConfig['language']."/mail_template/$regcode.tpl")) {
		include_once XOOPS_ROOT_PATH . "/modules/reg_codes/language/".$xoopsConfig['language']."/templates.php";
		$xoopsMailer =& getMailer();
		$xoopsMailer->useMail();
		$xoopsMailer->assign('USERNAME', $user->getVar('uname'));
		$xoopsMailer->assign('USEREMAIL', $user->getVar('email'));
		$xoopsMailer->assign('SITENAME', $xoopsConfig['sitename']);
		$xoopsMailer->assign('ADMINMAIL', $xoopsConfig['adminmail']);
		$xoopsMailer->assign('SITEURL', XOOPS_URL."/");
		$xoopsMailer->assign('FULLNAME', $user->getVar('name'));
		$xoopsMailer->setTemplate("$regcode.tpl");
		$xoopsMailer->setFromEmail($xoopsConfig['adminmail']);
		$xoopsMailer->setFromName($xoopsConfig['sitename']);
		$xoopsMailer->setSubject(_AM_REG_CODES_LANG_WELCOMESUBJECT);
		$xoopsMailer->setToEmails($user->getVar('email'));
		if ( !$xoopsMailer->send() ) {
			echo _US_YOURREGMAILNG;
			exit();
		}
	}
}

// FUNCTION DETERMINEs WHAT APPROVALS ARE NECESSARY, IF ANY
function checkApproval($regcode) {

	$member_handler =& xoops_gethandler('member');
	if($regcode == "nocode") {
		global $xoopsConfigUser;
		if ($xoopsConfigUser['activation_type'] == 1) { // no approval required
			return false; 
		} elseif ($xoopsConfigUser['activation_type'] == 0) { // user must activate themselves
			return "user";
		} else { //  only other condition is if($xoopsConfigUser['activation_type'] == 2) which is admin approval, and we want any case that doesn't match for some reason to also include admin approval since that's the strictest option.
			$activation_group = $member_handler->getGroup($xoopsConfigUser['activation_group']);
			if(!is_object($activation_group)) { $activation_group = XOOPS_GROUP_ADMIN; }
			return array($activation_group->getVar('groupid')=>$activation_group);
		}
	} else {
		$reggroupsq = "SELECT reg_codes_approval FROM " . XOOPS_DB_PREFIX . "_reg_codes WHERE reg_codes_code=\"" . mysql_real_escape_string($regcode) . "\"";
		$resreggroupsq = mysql_query($reggroupsq);
		$rowreggroupsq = mysql_fetch_row($resreggroupsq);
		$approval_groups = explode(",",trim($rowreggroupsq[0],","));
		foreach($approval_groups as $thisgroup) {
			if($thisgroup) {
				$approval_group[$thisgroup] = $member_handler->getGroup($thisgroup); 
			}
		}
		if(count($approval_group) == 0) { return false; }
		return $approval_group;
	}
}

// FUNCTION MODIFIED TO INCLUDE CHECKING OF REGISTRATION CODE
function userCheck($uname, $email, $pass, $vpass, $regcode)
{
	global $xoopsConfigUser;
	$xoopsDB =& Database::getInstance();
	$myts =& MyTextSanitizer::getInstance();
	$stop = '';

	// CHECK REGISTRATION CODE
	if(validateRegCode($regcode) != true) {
		global $xoopsConfig;
		include XOOPS_ROOT_PATH . "/modules/reg_codes/language/".$xoopsConfig['language']."/modinfo.php";
		$stop .= "ERROR: " . _REGFORM_REGCODES_INVALID.'<br />';
	}
	// END CHECK FOR CODE

	if (!checkEmail($email)) {
		$stop .= _US_INVALIDMAIL.'<br />';
	}
	foreach ($xoopsConfigUser['bad_emails'] as $be) {
		if (!empty($be) && preg_match("/".$be."/i", $email)) {
			$stop .= _US_INVALIDMAIL.'<br />';
			break;
		}
	}
	if (strrpos($email,' ') > 0) {
		$stop .= _US_EMAILNOSPACES.'<br />';
	}
	$uname = xoops_trim($uname);
	switch ($xoopsConfigUser['uname_test_level']) {
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
	if (empty($uname) || preg_match($restriction, $uname)) {
		$stop .= _US_INVALIDNICKNAME."<br />";
	}
	if (strlen($uname) > $xoopsConfigUser['maxuname']) {
		$stop .= sprintf(_US_NICKNAMETOOLONG, $xoopsConfigUser['maxuname'])."<br />";
	}
	if (strlen($uname) < $xoopsConfigUser['minuname']) {
		$stop .= sprintf(_US_NICKNAMETOOSHORT, $xoopsConfigUser['minuname'])."<br />";
	}
	foreach ($xoopsConfigUser['bad_unames'] as $bu) {
		if (!empty($bu) && preg_match("/".$bu."/i", $uname)) {
			$stop .= _US_NAMERESERVED."<br />";
			break;
		}
	}
	if (strrpos($uname, ' ') > 0) {
		$stop .= _US_NICKNAMENOSPACES."<br />";
	}
	$sql = sprintf('SELECT COUNT(*) FROM %s WHERE uname = %s', $xoopsDB->prefix('users'), $xoopsDB->quoteString(addslashes($uname)));
	$result = $xoopsDB->query($sql);
	list($count) = $xoopsDB->fetchRow($result);
	if ($count > 0) {
		$stop .= _US_NICKNAMETAKEN."<br />";
	}
	$count = 0;
	if ( $email ) {
		$sql = sprintf('SELECT COUNT(*) FROM %s WHERE email = %s', $xoopsDB->prefix('users'), $xoopsDB->quoteString(addslashes($email)));
		$result = $xoopsDB->query($sql);
		list($count) = $xoopsDB->fetchRow($result);
		if ( $count > 0 ) {
			$stop .= _US_EMAILTAKEN."<br />";
		}
	}
	if ( !isset($pass) || $pass == '' || !isset($vpass) || $vpass == '' ) {
		$stop .= _US_ENTERPWD.'<br />';
	}
	if ( (isset($pass)) && ($pass != $vpass) ) {
		$stop .= "ERROR: " . _US_PASSNOTSAME.'<br />'; // 'Error' added to front to match other messages
	} elseif ( ($pass != '') && (strlen($pass) < $xoopsConfigUser['minpass']) ) {
		$stop .= sprintf(_US_PWDTOOSHORT,$xoopsConfigUser['minpass'])."<br />";
	}
	return $stop;
}

// THIS FUNCTION NOTIFIES THE NECESSARY ADMIN GROUPS THAT SOMEONE HAS APPLIED TO REGISTER
// This function also notifies users if no approval groups are invoked
function notifyAdmin($name, $email, $newid, $actkey, $approval_groups) {

	global $xoopsConfigUser, $xoopsConfig;

	if($approval_groups == "user") { // if no approval is required, but user must confirm account -- only happens with "nocode" if that account activation setting is in effect
		$xoopsMailer =& getMailer();
		$xoopsMailer->useMail();
		$xoopsMailer->setTemplate('register.tpl');
		$xoopsMailer->assign('SITENAME', $xoopsConfig['sitename']);
		$xoopsMailer->assign('ADMINMAIL', $xoopsConfig['adminmail']);
		$xoopsMailer->assign('SITEURL', XOOPS_URL."/");
		$xoopsMailer->setToUsers(new XoopsUser($newid));
		$xoopsMailer->setFromEmail($xoopsConfig['adminmail']);
		$xoopsMailer->setFromName($xoopsConfig['sitename']);
		$xoopsMailer->setSubject(sprintf(_US_USERKEYFOR, $name));
		if ( !$xoopsMailer->send() ) {
			echo _US_YOURREGMAILNG;
		} else {
			redirect_header(XOOPS_URL, 8, _US_YOURREGISTERED);
		}
	} elseif(is_array($approval_groups)) {
		$xoopsMailer =& getMailer();
		$xoopsMailer->useMail();
		$xoopsMailer->setTemplate('adminactivate.tpl');
		$xoopsMailer->assign('USERNAME', $name);
		$xoopsMailer->assign('USEREMAIL', $email);
		$xoopsMailer->assign('USERACTLINK', XOOPS_URL.'/user.php?op=actv&id='.$newid.'&actkey='.$actkey);
		$xoopsMailer->assign('SITENAME', $xoopsConfig['sitename']);
		$xoopsMailer->assign('ADMINMAIL', $xoopsConfig['adminmail']);
		$xoopsMailer->assign('SITEURL', XOOPS_URL."/");
		$member_handler =& xoops_gethandler('member');
	//	$xoopsMailer->setToGroups($member_handler->getGroup($xoopsConfigUser['activation_group']));
		$activation_group = $member_handler->getGroup($xoopsConfigUser['activation_group']);
		if(is_object($activation_group)) {
			if(!in_array($activation_group->getVar('groupid'), array_keys($approval_groups))) { $approval_groups[] = $activation_group; }
		}
		$xoopsMailer->setToGroups($approval_groups);
		$xoopsMailer->setFromEmail($xoopsConfig['adminmail']);
		$xoopsMailer->setFromName($xoopsConfig['sitename']);
		$xoopsMailer->setSubject(sprintf(_US_USERKEYFOR, $name));
		if ( !$xoopsMailer->send() ) {
			echo _US_YOURREGMAILNG;
			exit();
		} else {
      if(isset($GLOBALS['reg_codes_xoopsMailerToSend'])) {
        $GLOBALS['reg_codes_xoopsMailerToSend']->send();
      }
			redirect_header(XOOPS_URL, 8, _US_YOURREGISTERED2); 
		}
	}
}

// This function confirms the users email address //nmc 2007.03.20
function confirmUser($name, $email, $newid, $actkey, $regcode ) {

	global $xoopsConfigUser, $xoopsConfig, $xoopsDB ;

	$xoopsMailer =& getMailer();
	$xoopsMailer->useMail();
	$xoopsMailer->setTemplate('confirmuser.tpl');
	$xoopsMailer->assign('SITENAME', $xoopsConfig['sitename']);
	$xoopsMailer->assign('ADMINMAIL', $xoopsConfig['adminmail']);
	$xoopsMailer->assign('USERCONFLINK', XOOPS_URL.'/register.php?op=conf&ckey='.urlencode($actkey));
	$xoopsMailer->assign('SITEURL', XOOPS_URL."/");
	$xoopsMailer->setToUsers(new XoopsUser($newid));
	$xoopsMailer->setFromEmail($xoopsConfig['adminmail']);
	$xoopsMailer->setFromName($xoopsConfig['sitename']);
	$xoopsMailer->setSubject(sprintf(_US_REGPROCESSSUBJ, $name));
	if ( !$xoopsMailer->send() ) {
		echo _US_REGPROCESSFAILED;
		exit();
	} else {
		$sql = sprintf("INSERT INTO %s (reg_codes_conf_id, reg_codes_conf_actkey, reg_codes_conf_name, reg_codes_conf_email, reg_codes_conf_reg_code) VALUES (%u,'%s','%s','%s','%s')",
						$xoopsDB->prefix('reg_codes_confirm_user'), 
						$newid, 
						mysql_real_escape_string($actkey),
						mysql_real_escape_string($name),
						mysql_real_escape_string($email),
						mysql_real_escape_string($regcode));
		//echo $sql;
		if(!$res = $xoopsDB->query($sql)) {
			echo "Error with this SQL: $sql<br>";
			}
		else {
      if(isset($GLOBALS['reg_codes_xoopsMailerToSend'])) {
        $GLOBALS['reg_codes_xoopsMailerToSend']->send();
      }
			redirect_header(XOOPS_URL, 15, _US_REGPROCESSBEGUN);
		}
	}
}


?>