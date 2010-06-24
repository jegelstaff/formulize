<?php
/**
 * Extended User Profile
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license	LICENSE.txt
 * @license	GNU General Public License (GPL) http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @package	modules
 * @since	1.2
 * @author	Jan Pedersen
 * @author	The SmartFactory <www.smartfactory.ca>
 * @author	Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version	$Id$
 */

include_once("admin_header.php");
xoops_cp_header();

$op = isset($_REQUEST['op']) ? trim($_REQUEST['op']) : 'list';
if($op == 'editordelete') $op = isset($_REQUEST['delete'])?'delete':'edit';
$adminMenuIncluded = false;
/* @var $handler XoopsMemberHandler */
$handler =& xoops_gethandler('member');
global $icmsConfigUser;
switch($op) {
	default:
	case 'list':
		icms_adminMenu(1, '');
		$adminMenuIncluded = true;
		include_once(ICMS_ROOT_PATH.'/class/xoopsformloader.php');
		$form = new XoopsThemeForm(_PROFILE_AM_EDITUSER, 'form', 'user.php');
		$form->addElement(new XoopsFormSelectUser(_PROFILE_AM_SELECTUSER, 'id'));
		$form->addElement(new XoopsFormHidden('op', 'editordelete'));
		$button_tray = new XoopsFormElementTray('');
		$button_tray->addElement(new XoopsFormButton('', 'edit', _EDIT, 'submit'));
		$button_tray->addElement(new XoopsFormButton('', 'delete', _DELETE, 'submit'));
		$form->addElement($button_tray);
		//$form->addElement(new XoopsFormButton('', 'submit', _SUBMIT, 'submit'));
		$form->display();
		echo "<br />\n";
		$member_handler =& xoops_gethandler('member');
		$user_count = $member_handler->getUserCount(new Criteria('level', '-1'));
			if(count($user_count)>1){
				$form = new XoopsThemeForm(_PROFILE_AM_REMOVEDUSERS, 'form', 'user.php');
				$form->addElement(new XoopsFormSelectUser(_PROFILE_AM_SELECTUSER, 'id', false, false, false, false, true, true));
				$form->addElement(new XoopsFormHidden('op', 'editordelete'));
				$button_tray = new XoopsFormElementTray('');
				$button_tray->addElement(new XoopsFormButton('', 'edit', _EDIT, 'submit'));
				$form->addElement($button_tray);
				$form->display();
				echo "<br />\n";
			}
	
	case 'new':
		if (@!include_once(ICMS_ROOT_PATH.'/modules/'.basename(  dirname(  dirname( __FILE__ ) ) ).'/language/'.$icmsConfig['language'].'/main.php')) {
			include_once(ICMS_ROOT_PATH.'/modules/'.basename(  dirname(  dirname( __FILE__ ) ) ).'/language/english/main.php');
		}
		if (!$adminMenuIncluded) icms_adminMenu(1, '');
		include_once('../include/forms.php');
		$obj =& $handler->createUser();
		$obj->setGroups(array(ICMS_GROUP_USERS));
		$form =& getUserForm($obj, false, false, true);
		$form->display();
		break;

	case 'edit':
		if (@!include_once(ICMS_ROOT_PATH.'/modules/'.basename(  dirname(  dirname( __FILE__ ) ) ).'/language/'.$icmsConfig['language'].'/main.php')) {
			include_once(ICMS_ROOT_PATH.'/modules/'.basename(  dirname(  dirname( __FILE__ ) ) ).'/language/english/main.php');
		}
		$obj =& $handler->getUser(intval($_REQUEST['id']));
		if (in_array(ICMS_GROUP_ADMIN, $obj->getGroups()) && !in_array(ICMS_GROUP_ADMIN, $icmsUser->getGroups())) {
			// If not webmaster trying to edit a webmaster - disallow
			redirect_header('user.php', 3, _PROFILE_AM_CANNOTEDITWEBMASTERS);
		}
		icms_adminMenu(1, '');
		include_once('../include/forms.php');
		$form =& getUserForm($obj, false, false, true);
		$form->display();
		break;

	case 'save':
		if (@!include_once(ICMS_ROOT_PATH.'/modules/'.basename(  dirname(  dirname( __FILE__ ) ) ).'/language/'.$icmsConfig['language'].'/main.php')) {
			include_once(ICMS_ROOT_PATH.'/modules/'.basename(  dirname(  dirname( __FILE__ ) ) ).'/language/english/main.php');
		}
		if (!$GLOBALS['xoopsSecurity']->check()) {
			redirect_header('user.php',3,_PROFILE_MA_NOEDITRIGHT.'<br />'.implode('<br />', $GLOBALS['xoopsSecurity']->getErrors()));
			exit;
		}
		$uid = 0;
		if (!empty($_POST['uid'])) {
			$uid = intval($_POST['uid']);
			$user =& $handler->getUser($uid);
		}
		else {
			$user =& $handler->createUser();
			$user->setVar('user_regdate', time());
			$user->setVar('user_avatar', 'blank.gif');
			$user->setVar('uorder', $icmsConfig['com_order']);
			$user->setVar('umode', $icmsConfig['com_mode']);
		}
		$errors = array();
		$myts =& MyTextSanitizer::getInstance();
		$user->setVar('email', trim($_POST['email']));
		if ($user->getVar('uid') != $icmsUser->getVar('uid')) {
			$password = '';
			if (!empty($_POST['password'])) {
				$password = $myts->stripSlashesGPC(trim($_POST['password']));
			}
			if ($password != '') {
				if (strlen($password) < $icmsConfigUser['minpass']) {
					$errors[] = sprintf(_PROFILE_MA_PWDTOOSHORT,$icmsConfigUser['minpass']);
				}
				$vpass = '';
				if (!empty($_POST['vpass'])) {
					$vpass = $myts->stripSlashesGPC(trim($_POST['vpass']));
				}
				if ($password != $vpass) {
					$errors[] = _PROFILE_MA_PASSNOTSAME;
				}
				include_once ICMS_ROOT_PATH.'/class/icms_Password.php';
				$icmspass = new icms_Password();
				$salt = $icmspass->icms_createSalt();
				$pass = $icmspass->icms_encryptPass($password, $salt);
				$user->setVar('pass', $pass);
				$user->setVar('pass_expired', 0);
				$user->setVar('enc_type', $icmsConfigUser['enc_type']);
				$user->setVar('salt', $salt);
			}elseif ($user->isNew()) {
				$errors[] = _PROFILE_MA_NOPASSWORD;
			}
			$user->setVar('level', intval($_POST['level']));
		}
		$user->setVar('uname', trim($_POST['uname']));
		$user->setVar('login_name', trim($_POST['login_name']));
		if ($icmsConfigAuth['auth_openid'] == 1) {
			$user->setVar('openid', trim($_POST['openid']));
			$user->setVar('user_viewoid', isset($_POST['user_viewoid']) ? intval($_POST['user_viewoid']) : 0);
		}
		include_once('../include/functions.php');
		$stop = userCheck($user);
		if ($stop != '') {
			$errors[] = $stop;
		}

		// Dynamic fields
		$profile_handler =& icms_getmodulehandler( 'profile', basename(  dirname(  dirname( __FILE__ ) ) ), 'profile' );
		// Get fields
		$fields =& $profile_handler->loadFields();
		// Get ids of fields that can be edited
		$gperm_handler =& xoops_gethandler('groupperm');
		$editable_fields =& $gperm_handler->getItemIds('profile_edit', $icmsUser->getGroups(), $icmsModule->getVar('mid'));

		$profile = $profile_handler->get($user->getVar('uid'));

		foreach (array_keys($fields) as $i) {
			$fieldname = $fields[$i]->getVar('field_name');
			if (in_array($fields[$i]->getVar('fieldid'), $editable_fields) && ($fields[$i]->getvar('field_type') == 'image' || isset($_REQUEST[$fieldname]))) {
				if (in_array($fieldname, $profile_handler->getUserVars())) {
					$value = $fields[$i]->getValueForSave(trim($_REQUEST[$fieldname]), $user->getVar($fieldname, 'n'));
					$user->setVar($fieldname, $value);
				}
				else {
					$value = $fields[$i]->getValueForSave((isset($_REQUEST[$fieldname]) ? trim($_REQUEST[$fieldname]) : ''), $profile->getVar($fieldname, 'n'));
					$profile->setVar($fieldname, $value);
				}
			}
		}

		$new_groups = isset($_POST['groups']) ? $_POST['groups'] : array();
		//$user->setGroups($new_groups);

		if (count($errors) == 0) {
			if ($handler->insertUser($user)) {
				$profile->setVar('profileid', $user->getVar('uid'));
				$profile_handler->insert($profile);

				include_once(ICMS_ROOT_PATH.'/modules/system/constants.php');
				if ($gperm_handler->checkRight('system_admin', XOOPS_SYSTEM_GROUP, $icmsUser->getGroups(), 1)) {
					//Update group memberships
					$cur_groups = $user->getGroups();

					$added_groups = array_diff($new_groups, $cur_groups);
					$removed_groups = array_diff($cur_groups, $new_groups);

					if (count($added_groups) > 0) {
						foreach ($added_groups as $groupid) {
							$handler->addUserToGroup($groupid, $user->getVar('uid'));
						}
					}
					if (count($removed_groups) > 0) {
						foreach ($removed_groups as $groupid) {
							$handler->removeUsersFromGroup($groupid, array($user->getVar('uid')));
						}
					}
				}
				if ($user->isNew()) {
					redirect_header('user.php', 2, _PROFILE_AM_USERCREATED, false);
				}
				else {
					redirect_header('user.php', 2, _PROFILE_MA_PROFUPDATED, false);
				}
			}
		}
		else {
			foreach ($errors as $err) {
				$user->setErrors($err);
			}
		}
		$user->setGroups($new_groups);

		icms_adminMenu(1, '');
		include_once('../include/forms.php');
		echo $user->getHtmlErrors();
		$form =& getUserForm($user, $profile);
		$form->display();
		break;

	case 'delete':
		if ($_REQUEST['id'] == $icmsUser->getVar('uid')) {
			redirect_header('user.php', 2, _PROFILE_AM_CANNOTDELETESELF);
		}
		$obj =& $handler->getUser($_REQUEST['id']);
		if (isset($_REQUEST['ok']) && $_REQUEST['ok'] == 1) {
			if (!$GLOBALS['xoopsSecurity']->check()) {
				redirect_header('user.php', 3, implode(',', $GLOBALS['xoopsSecurity']->getErrors()), false);
			}
			$profile_handler = icms_getmodulehandler( 'profile', basename(  dirname(  dirname( __FILE__ ) ) ), 'profile' );
			$profile = $profile_handler->get($obj->getVar('uid'));
			if ($profile->isNew() || $profile_handler->delete($profile)) {
				if ($handler->deleteUser($obj)) {
					redirect_header('user.php', 3, sprintf(_PROFILE_AM_DELETEDSUCCESS, $obj->getVar('uname').' ('.$obj->getVar('email').')'), false);
				} else {
					icms_adminMenu(1, '');
					echo $obj->getHtmlErrors();
				}
			} else {
				icms_adminMenu(1, '');
				echo $profile->getHtmlErrors();
			}
		} else {
			xoops_confirm(array('ok' => 1, 'id' => intval($_REQUEST['id']), 'op' => 'delete'), $_SERVER['REQUEST_URI'], sprintf(_PROFILE_AM_RUSUREDEL, $obj->getVar('uname').' ('.$obj->getVar('email').')'));
		}
		break;
}

xoops_cp_footer();
?>