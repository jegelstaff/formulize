<?php
/**
 * Extended User Profile
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		LICENSE.txt
 * @license		GNU General Public License (GPL) http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @package		modules
 * @since		1.2
 * @author		Jan Pedersen
 * @author		The SmartFactory <www.smartfactory.ca>
 * @author		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version		$Id: edituser.php 21139 2011-03-20 20:58:11Z m0nty_ $
 */

include '../../mainfile.php';

if (!is_object(icms::$user)) redirect_header(ICMS_URL, 3, _NOPERM);

// initialize $op variable
$op = isset($_REQUEST['op']) ? $_REQUEST['op'] : 'editprofile';

switch ($op) {
	case 'save':
		if (!icms::$security->check()) redirect_header(ICMS_URL."/modules/".basename(dirname(__FILE__)), 3, _NOPERM."<br />".implode('<br />', icms::$security->getErrors()));

		$uid = 0;
		if (!empty($_POST['uid'])) $uid = (int)$_POST['uid'];
		if (empty($uid) || (icms::$user->getVar('uid') != $uid && !icms::$user->isAdmin())) redirect_header(ICMS_URL, 3, _MD_PROFILE_NOEDITRIGHT);

		$login_name = isset($_POST['login_name']) ? trim($_POST['login_name']) : '';
		$uname = isset($_POST['uname']) ? trim($_POST['uname']) : '';
		$email = isset($_POST['email']) ? trim($_POST['email']) : '';
		$pass = isset($_POST['password']) ? icms_core_DataFilter::stripSlashesGPC($_POST['password']) : '';
		$vpass = isset($_POST['vpass']) ? icms_core_DataFilter::stripSlashesGPC($_POST['vpass']) : '';

		icms_loadLanguageFile('core', 'user');
		$user_handler = icms::handler('icms_member_user');
		if (icms::$user->isAdmin()) {
			$stop = $user_handler->userCheck($login_name, $uname, $email, ($pass == '') ? false : $pass, $vpass, $uid);
		} elseif ($icmsConfigUser['allow_chguname'] == 1) {
			// a normal user can only change his username on this screen (and only if this is allowed in the settings)
			$stop = $user_handler->userCheck(false, $uname, false, false, false, $uid);
		}

		if (!empty($stop)) redirect_header(icms_getPreviousPage('edituser.php?uid='.$uid), 3, $stop);

		$member_handler = icms::handler('icms_member');
		$edituser = $member_handler->getUser($uid);
		if (icms::$user->isAdmin()) {
			$edituser->setVar('login_name', $login_name);
			$edituser->setVar('uname', $uname);
			$edituser->setVar('email', $email);

			if ($edituser->getVar('uid') != icms::$user->getVar('uid')) {
				if ($pass != '') {
					$icmspass = new icms_core_Password();
					$salt = icms_core_Password::createSalt();
					$pass = $icmspass->encryptPass($pass, $salt, $icmsConfigUser['enc_type']);
					$edituser->setVar('pass', $pass);
					$edituser->setVar('pass_expired', 0);
					$edituser->setVar('enc_type', $icmsConfigUser['enc_type']);
					$edituser->setVar('salt', $salt);
				}
				$edituser->setVar('level', (int)$_POST['level']);
			}
		} else {
			if ($icmsConfigUser['allow_chguname'] == 1) $edituser->setVar('uname', $uname);
		}
		if ($icmsConfigAuth['auth_openid'] == 1) {
			$edituser->setVar('openid', icms_core_DataFilter::stripSlashesGPC(trim($_POST['openid'])));
			$edituser->setVar('user_viewoid', isset($_POST['user_viewoid']) ? (int)$_POST['user_viewoid'] : 0);
		}

		// ALTERED BY FREEFORM SOLUTIONS TO SUPPORT USERS CHANGING THEIR OWN PASSWORDS FROM A SINGLE PROFILE PAGE
		// A REPEAT OF THE CODE BLOCK JUST ABOVE, TO HANDLE THE CASE WHERE THE USER IS UPDATING THEIR OWN PASSWORD
		if ($pass != '' AND $edituser->getVar('uid') == icms::$user->getVar('uid')) {
			$icmspass = new icms_core_Password();
			$salt = icms_core_Password::createSalt();
			$pass = $icmspass->encryptPass($pass, $salt, $icmsConfigUser['enc_type']);
			$edituser->setVar('pass', $pass);
			$edituser->setVar('pass_expired', 0);
			$edituser->setVar('enc_type', $icmsConfigUser['enc_type']);
			$edituser->setVar('salt', $salt);
		}

		// Dynamic fields
		$profile_handler = icms_getmodulehandler('profile', basename(dirname(__FILE__)), 'profile');
		// Get fields
		$fields = $profile_handler->loadFields();
		// Get ids of all available fields for the user groups icms::$user is a member of.
		$editable_fields = icms::handler('icms_member_groupperm')->getItemIds('profile_edit', icms::$user->getGroups(), icms::$module->getVar('mid'));

		$profile = $profile_handler->get($edituser->getVar('uid'));

		foreach (array_keys($fields) as $i) {
			if ($fields[$i]->getVar('field_edit') == 1) {
				$fieldname = $fields[$i]->getVar('field_name');
				if (in_array($fields[$i]->getVar('fieldid'), $editable_fields) && ($fields[$i]->getvar('field_type') == "image" || isset($_REQUEST[$fieldname]))) {
					if (in_array($fieldname, $profile_handler->getUserVars())) {
						$value = $fields[$i]->getValueForSave($_REQUEST[$fieldname], $edituser->getVar($fieldname, 'n'));
						$edituser->setVar($fieldname, $value);
					}
					else {
						$value = $fields[$i]->getValueForSave((isset($_REQUEST[$fieldname]) ? $_REQUEST[$fieldname] : ""), $profile->getVar($fieldname, 'n'));
						$profile->setVar($fieldname, $value);
					}
				}
			}
		}
		if (!$member_handler->insertUser($edituser)) {
			include ICMS_ROOT_PATH.'/header.php';
			include_once 'include/forms.php';
			echo '<a href="'.ICMS_URL.'/modules/'.basename( dirname( __FILE__ ) ).'/userinfo.php?uid='.$edituser->getVar('uid').'">'. _MD_PROFILE_PROFILE .'</a>&nbsp;<span style="font-weight:bold;">&raquo;&raquo;</span>&nbsp;'. _MD_PROFILE_EDITPROFILE .'<br /><br />';
			$form =& getUserForm($edituser, $profile);
			echo $edituser->getHtmlErrors();
			$form->display();
		} else {
			$profile->setVar('profileid', $edituser->getVar('uid'));
			$profile_handler->insert($profile);
			unset($_SESSION['xoopsUserTheme']);
			redirect_header(ICMS_URL.'/modules/'.basename( dirname( __FILE__ ) ).'/userinfo.php?uid='.$uid, 2, _MD_PROFILE_PROFUPDATED);
		}		
		break;
	case 'delete':
		if (!icms::$user || $icmsConfigUser['self_delete'] != 1) redirect_header(ICMS_URL, 3, _MD_PROFILE_NOPERMISS);
		// users in the webmasters group may not be deleted
		$groups = icms::$user->getGroups();
		if (in_array(ICMS_GROUP_ADMIN, $groups)) redirect_header(ICMS_URL, 3, _MD_PROFILE_ADMINNO);

		$ok = !isset($_POST['ok']) ? 0 : (int)$_POST['ok'];
		if ($ok != 1) {
			include ICMS_ROOT_PATH.'/header.php';
			icms_core_Message::confirm(array('op' => 'delete', 'ok' => 1), ICMS_URL.'/modules/'.basename(dirname(__FILE__)).'/edituser.php', _MD_PROFILE_SURETODEL.'<br/>'._MD_PROFILE_REMOVEINFO);
			include ICMS_ROOT_PATH.'/footer.php';
		} else {
			$del_uid = (int) icms::$user->getVar("uid");
			if (false != icms::handler('icms_member')->deleteUser(icms::$user)) {
				icms::handler('icms_core_Online')->destroy($del_uid);
				xoops_notification_deletebyuser($del_uid);

				//logout user
				$_SESSION = array();
				session_destroy();
				if ($icmsConfig['use_mysession'] && $icmsConfig['session_name'] != '') setcookie($icmsConfig['session_name'], '', time()- 3600, '/',  '', 0);
				redirect_header(ICMS_URL, 3, _MD_PROFILE_BEENDELED);
			}
			redirect_header(ICMS_URL, 3, _MD_PROFILE_NOPERMISS);
		}
		break;
	case 'avatarform':
		include ICMS_ROOT_PATH.'/header.php';
		echo '<a href="'.ICMS_URL.'/modules/'.basename( dirname( __FILE__ ) ).'/userinfo.php?uid='.icms::$user->getVar('uid').'">'. _MD_PROFILE_PROFILE .'</a>&nbsp;<span style="font-weight:bold;">&raquo;&raquo;</span>&nbsp;'. _MD_PROFILE_UPLOADMYAVATAR .'<br /><br />';
		$oldavatar = icms::$user->getVar('user_avatar');
		if (!empty($oldavatar) && $oldavatar != 'blank.gif') {
			echo '<div style="text-align:center;"><h4 style="color:#ff0000; font-weight:bold;">'._MD_PROFILE_OLDDELETED.'</h4>';
			echo '<img src="'.ICMS_UPLOAD_URL.'/'.$oldavatar.'" alt="" /></div>';
		}
		if ($icmsConfigUser['avatar_allow_upload'] == 1 && icms::$user->getVar('posts') >= $icmsConfigUser['avatar_minposts']) {
			$form = new icms_form_Theme(_MD_PROFILE_UPLOADMYAVATAR, 'uploadavatar', ICMS_URL.'/modules/'.basename( dirname( __FILE__ ) ).'/edituser.php', 'post', true);
			$form->setExtra('enctype="multipart/form-data"');
			$form->addElement(new icms_form_elements_Label(_MD_PROFILE_MAXPIXEL, $icmsConfigUser['avatar_width'].' x '.$icmsConfigUser['avatar_height']));
			$form->addElement(new icms_form_elements_Label(_MD_PROFILE_MAXIMGSZ, $icmsConfigUser['avatar_maxsize']));
			$form->addElement(new icms_form_elements_File(_MD_PROFILE_SELFILE, 'avatarfile', $icmsConfigUser['avatar_maxsize']), true);
			$form->addElement(new icms_form_elements_Hidden('op', 'avatarupload'));
			$form->addElement(new icms_form_elements_Hidden('uid', icms::$user->getVar('uid')));
			$form->addElement(new icms_form_elements_Button('', 'submit', _SUBMIT, 'submit'));
			$form->display();
		}
		$form2 = new icms_form_Theme(_MD_PROFILE_CHOOSEAVT, 'uploadavatar', ICMS_URL.'/modules/'.basename( dirname( __FILE__ ) ).'/edituser.php', 'post', true);
		$avatar_select = new icms_form_elements_Select('', 'user_avatar', icms::$user->getVar('user_avatar'));
		$avatar_select->addOptionArray(icms::handler('icms_data_avatar')->getList('S'));
		$avatar_select->setExtra("onchange='showImgSelected(\"avatar\", \"user_avatar\", \"uploads\", \"\", \"".ICMS_URL."\")'");
		$avatar_tray = new icms_form_elements_Tray(_MD_PROFILE_AVATAR, '&nbsp;');
		$avatar_tray->addElement($avatar_select);
		$avatar_tray->addElement(new icms_form_elements_Label('', "<img src='".ICMS_UPLOAD_URL."/".icms::$user->getVar("user_avatar", "E")."' name='avatar' id='avatar' alt='' /> <a href=\"javascript:openWithSelfMain('".ICMS_URL."/misc.php?action=showpopups&amp;type=avatars','avatars',600,400);\">"._LIST."</a>"));
		$form2->addElement($avatar_tray);
		$form2->addElement(new icms_form_elements_Hidden('uid', icms::$user->getVar('uid')));
		$form2->addElement(new icms_form_elements_Hidden('op', 'avatarchoose'));
		$form2->addElement(new icms_form_elements_Button('', 'submit2', _SUBMIT, 'submit'));
		$form2->display();
		break;
	case 'avatarupload':
		if (!icms::$security->check()) {
			redirect_header('index.php',3,_MD_PROFILE_NOEDITRIGHT."<br />".implode('<br />', icms::$security->getErrors()));
			exit;
		}
		$uid = 0;
		if (!empty($_POST['uid'])) {
			$uid = (int)$_POST['uid'];
		}
		if (empty($uid) || icms::$user->getVar('uid') != $uid ) {
			redirect_header('index.php',3,_MD_PROFILE_NOEDITRIGHT);
		}
		if ($icmsConfigUser['avatar_allow_upload'] == 1 && icms::$user->getVar('posts') >= $icmsConfigUser['avatar_minposts']) {
			$uploader = new icms_file_MediaUploadHandler(ICMS_UPLOAD_PATH, array('image/gif', 'image/jpeg', 'image/pjpeg', 'image/x-png', 'image/png'), $icmsConfigUser['avatar_maxsize'], $icmsConfigUser['avatar_width'], $icmsConfigUser['avatar_height']);
			if ($uploader->fetchMedia($_POST['xoops_upload_file'][0])) {
				$uploader->setPrefix('cavt');
				if ($uploader->upload()) {
					$avt_handler = icms::handler('icms_data_avatar');
					$avatar = $avt_handler->create();
					$avatar->setVar('avatar_file', $uploader->getSavedFileName());
					$avatar->setVar('avatar_name', icms::$user->getVar('uname'));
					$avatar->setVar('avatar_mimetype', $uploader->getMediaType());
					$avatar->setVar('avatar_display', 1);
					$avatar->setVar('avatar_type', 'C');
					if (!$avt_handler->insert($avatar)) {
						@unlink($uploader->getSavedDestination());
					} else {
						$oldavatar = icms::$user->getVar('user_avatar');
						if (!empty($oldavatar) && $oldavatar != 'blank.gif' && !preg_match("/^savt/", strtolower($oldavatar))) {
							$avatars = $avt_handler->getObjects(new icms_db_criteria_Item('avatar_file', $oldavatar));
							$avt_handler->delete($avatars[0]);
							$oldavatar_path = str_replace("\\", "/", realpath(ICMS_UPLOAD_PATH.'/'.$oldavatar));
							if (0 === strpos($oldavatar_path, ICMS_UPLOAD_PATH) && is_file($oldavatar_path)) {
								unlink($oldavatar_path);
							}
						}
						$sql = sprintf("UPDATE %s SET user_avatar = %s WHERE uid = %u", icms::$xoopsDB->prefix('users'), icms::$xoopsDB->quoteString($uploader->getSavedFileName()), icms::$user->getVar('uid'));
						icms::$xoopsDB->query($sql);
						$avt_handler->addUser($avatar->getVar('avatar_id'), icms::$user->getVar('uid'));
						redirect_header('userinfo.php?t='.time().'&amp;uid='.icms::$user->getVar('uid'),0, _MD_PROFILE_PROFUPDATED);
					}
				}
			}
			include ICMS_ROOT_PATH.'/header.php';
			echo $uploader->getErrors();
		}
		break;
	case 'avatarchoose':
		if (!icms::$security->check()) {
			redirect_header('index.php',3,_MD_PROFILE_NOEDITRIGHT."<br />".implode('<br />', icms::$security->getErrors()));
			exit;
		}
		$uid = 0;
		if (!empty($_POST['uid'])) {
			$uid = (int)$_POST['uid'];
		}
		if (empty($uid) || icms::$user->getVar('uid') != $uid ) {
			redirect_header('index.php', 3, _MD_PROFILE_NOEDITRIGHT);
		}
		$user_avatar = '';
		if (!empty($_POST['user_avatar'])) {
			$user_avatar = trim($_POST['user_avatar']);
		}
		$user_avatarpath = str_replace("\\", "/", realpath(ICMS_UPLOAD_PATH.'/'.$user_avatar));
		if (0 === strpos($user_avatarpath, ICMS_UPLOAD_PATH) && is_file($user_avatarpath)) {
			$oldavatar = icms::$user->getVar('user_avatar');
			icms::$user->setVar('user_avatar', $user_avatar);
			if (!icms::handler('icms_member')->insertUser(icms::$user)) {
				include ICMS_ROOT_PATH.'/header.php';
				echo icms::$user->getHtmlErrors();
				include ICMS_ROOT_PATH.'/footer.php';
				exit();
			}
			$avt_handler = icms::handler('icms_data_avatar');
			if ($oldavatar && $oldavatar != 'blank.gif' && !preg_match("/^savt/", strtolower($oldavatar))) {
				$avatars = $avt_handler->getObjects(new icms_db_criteria_Item('avatar_file', $oldavatar));
				if (is_object($avatars[0])) {
					$avt_handler->delete($avatars[0]);
				}
				$oldavatar_path = str_replace("\\", "/", realpath(ICMS_UPLOAD_PATH.'/'.$oldavatar));
				if (0 === strpos($oldavatar_path, ICMS_UPLOAD_PATH) && is_file($oldavatar_path)) {
					unlink($oldavatar_path);
				}
			}
			if ($user_avatar != 'blank.gif') {
				$avatars = $avt_handler->getObjects(new icms_db_criteria_Item('avatar_file', $user_avatar));
				if (is_object($avatars[0])) {
					$avt_handler->addUser($avatars[0]->getVar('avatar_id'), icms::$user->getVar('uid'));
				}
			}
		}
		redirect_header('userinfo.php?uid='.$uid, 0, _MD_PROFILE_PROFUPDATED);
		break;
	case 'editprofile':
	default:
		include_once ICMS_ROOT_PATH.'/header.php';
		include_once 'include/forms.php';
		$uid = (isset($_GET['uid'])) ? (int)$_GET['uid'] : icms::$user->getVar('uid');
		$thisUser = icms::handler('icms_member')->getUser($uid);
		if ($uid != icms::$user->getVar('uid') && !icms::$user->isAdmin()) redirect_header(ICMS_URL, 3, _NOPERM);
		$form = getUserForm($thisUser);
		$form->display();
		break;
}

include ICMS_ROOT_PATH.'/footer.php';