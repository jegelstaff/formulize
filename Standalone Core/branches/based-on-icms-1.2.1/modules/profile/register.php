<?php
/**
 * Extended User Profile
 *
 *
 * @copyright	   The ImpressCMS Project http://www.impresscms.org/
 * @license		 LICENSE.txt
 * @license			GNU General Public License (GPL) http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @package		 modules
 * @since		   1.2
 * @author		  Jan Pedersen
 * @author		  The SmartFactory <www.smartfactory.ca>
 * @author	   		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version		 $Id$
 */

include_once '../../mainfile.php';
$xoopsOption['template_main'] = 'profile_register.html';
include ICMS_ROOT_PATH.'/header.php';
include_once 'include/functions.php';
$myts =& MyTextSanitizer::getInstance();
if ($icmsUser) {
	header('location: userinfo.php?uid='.$icmsUser->getVar('uid'));
}
if (empty($icmsConfigUser['allow_register'])) {
	redirect_header(ICMS_URL.'/', 6, _PROFILE_MA_NOREGISTER);
	exit();
}
if($icmsConfigUser['pass_level']>20){
icms_PasswordMeter();
}

$member_handler =& xoops_gethandler('member');

$template_dir = ICMS_ROOT_PATH.'/language/'.$icmsConfig['language'].'/mail_template';
if (!file_exists($template_dir)) {
	$template_dir = ICMS_ROOT_PATH.'/language/english/mail_template';
}


/**
 * Debugging purpose
 */
$newuser = isset($_SESSION['profile']['uid']) ? $member_handler->getUser($_SESSION['profile']['uid']) : $member_handler->createUser();
//$newuser = $member_handler->createUser();

$profile_handler = icms_getmodulehandler( 'profile', basename( dirname( __FILE__ ) ), 'profile' );
$profile = $profile_handler->get($newuser->getVar('uid'));

$op = !isset($_POST['op']) ? 'register' : $_POST['op'];

$current_step = !isset($_POST['step']) ? 0 : $_POST['step'];
$criteria = new CriteriaCompo();
$criteria->setSort('step_order');
$regstep_handler = icms_getmodulehandler( 'regstep', basename( dirname( __FILE__ ) ), 'profile' );
$steps = $regstep_handler->getObjects($criteria);

$xoopsTpl->assign('categoryPath', $steps[$current_step]->getVar('step_name'));

if (count($steps) == 0) {
	redirect_header(ICMS_URL.'/', 6, _PROFILE_MA_NOSTEPSAVAILABLE);
	exit();
}

foreach ($steps as $step) {
	$xoopsTpl->append('steps', $step->toArray());
}
switch ( $op ) {
	case 'step':
		//Dynamic fields
		// Get fields
		$fields =& $profile_handler->loadFields();
		if (count($fields) > 0) {
			foreach (array_keys($fields) as $i) {
				$fieldname = $fields[$i]->getVar('field_name');
				if (isset($_POST[$fieldname])) {
					if(!empty($_SESSION['profile'][$fieldname])){
						if ('date' == $fields[$i]->getVar('field_type') || 'longdate' == $fields[$i]->getVar('field_type')) {
							// change text time back to unix timestamp
							$_SESSION['profile'][$fieldname] = trim(strtotime($_POST[$fieldname]));
						}elseif('datetime' == $fields[$i]->getVar('field_type')){
							// change text datetime back to unix timestamp
							$_SESSION['profile'][$fieldname] = trim(strtotime($_POST[$fieldname]['date']) + $_POST[$fieldname]['time']);
						}else{
							$_SESSION['profile'][$fieldname] = trim($_POST[$fieldname]);
						}
					}
				}
			}
		}
		// if first step was previous step, check user data as they will always be at first step
		if ($current_step == 0) {
			include_once ICMS_ROOT_PATH.'/class/icms_Password.php';
			$icmspass = new icms_Password();
			$newuser->setVar('login_name', isset($_POST['login_name']) ? trim($_POST['login_name']) : '');
			$newuser->setVar('uname', isset($_POST['uname']) ? trim($_POST['uname']) : '');
			$newuser->setVar('email', isset($_POST['email']) ? trim($_POST['email']) : '');
			$vpass = isset($_POST['vpass']) ? $myts->stripSlashesGPC($_POST['vpass']) : '';
			$agree_disc = (isset($_POST['agree_disc']) && intval($_POST['agree_disc'])) ? 1 : 0;
			$salt = $icmspass->icms_createSalt();
			$pass = $icmspass->icms_encryptPass(trim($_POST['pass']), $salt);
			$newuser->setVar('pass', isset($_POST['pass']) ? $pass : '');
			$newuser->setVar('enc_type', $icmsConfigUser['enc_type']);
			$newuser->setVar('salt', $salt);
			$newuser->setVar('user_avatar', 'blank.gif');
			$newuser->setVar('uorder', $icmsConfig['com_order']);
			$newuser->setVar('umode', $icmsConfig['com_mode']);

			$stop = '';
			if ($icmsConfigUser['use_captcha'] == 1) {
				include_once (ICMS_ROOT_PATH ."/class/captcha/captcha.php");
				include_once(ICMS_ROOT_PATH ."/class/xoopsformloader.php");
				$icmsCaptcha = IcmsCaptcha::instance();
				if(! $icmsCaptcha->verify() ) {
					$stop .= $icmsCaptcha->getMessage().'<br />';
				}
			}
			if ($icmsConfigUser['reg_dispdsclmr'] != 0 && $icmsConfigUser['reg_disclaimer'] != '') {
				if (empty($agree_disc)) {
					$stop .= _PROFILE_MA_UNEEDAGREE.'<br />';
				}
			}
			if (!empty($icmsConfigUser['minpass']) && strlen(trim($_POST['pass'])) < $icmsConfigUser['minpass']) {
				$stop .= sprintf(_PROFILE_MA_PWDTOOSHORT,$icmsConfigUser['minpass']).'<br />';
			}
			$stop .= userCheck($newuser);
			if (empty($stop)) {
				$_SESSION['profile']['login_name'] = $newuser->getVar('login_name', 'n');
				$_SESSION['profile']['uname'] = $newuser->getVar('uname', 'n');
				$_SESSION['profile']['email'] = $newuser->getVar('email', 'n');
				$_SESSION['profile']['pass'] = $newuser->getVar('pass', 'n');
				$_SESSION['profile']['actkey'] = substr(xoops_makepass(), 0, 8);
			}
		}
		// Set vars
		$uservars = $profile_handler->getUserVars();
		foreach ($_SESSION['profile'] as $field => $value) {
			if (in_array($field, $uservars)) {
				$newuser->setVar($field, $value);
			}
			else {
				$profile->setVar($field, $value);
			}
		}
		if (empty($stop)) {
			// If save after previous step, save the user
			$save = false;
			for ($i = 0; $i <= $current_step; $i++) {
				if ($steps[$i]->getVar('step_save')) {
					$save = true;
					break;
				}
			}
			if ($save) {
				if (!$member_handler->insertUser($newuser)) {
					$stop .= _PROFILE_MA_REGISTERNG.'<br />';
					$stop .= implode('<br />', $newuser->getErrors());
					$xoopsTpl->assign('stop', $stop);
				}
				else{
					$_SESSION['profile']['uid'] = $newuser->getVar('uid');
					$profile->setVar('profileid', $newuser->getVar('uid'));
					$profile_handler->insert($profile);
					if ($newuser->isNew() ) {
						$xoopsTpl->append('confirm', postSaveProcess($newuser) );
					}
				}
			}
			if (isset($steps[$current_step+1])) {
				$xoopsTpl->assign('categoryPath', $steps[$current_step+1]->getVar('step_name'));
			}
			if (!empty($stop) || isset($steps[$current_step+1])) {
				// There are errors or we can proceed to next step
				$next_step = (empty($stop) ? $current_step+1 : $current_step);
				include_once 'include/forms.php';
				// Set field persistance - load newuser with session vars
				$uservars = $profile_handler->getUserVars();
				foreach ($_SESSION['profile'] as $field => $value) {
					if (in_array($field, $uservars)) $newuser->setVar($field, $value);
				}
				$reg_form =& getRegisterForm($newuser, $profile, $next_step, $steps[$next_step] );
				assign($reg_form, $xoopsTpl);
				$xoopsTpl->assign('current_step', $next_step);
				$xoopsTpl->assign('stop', $stop);
			}
			else {
				// No errors and no more steps, finish
				$xoopsTpl->append('confirm', _PROFILE_MA_REGISTER_FINISH);
			}
		}else{
			include_once 'include/forms.php';
			// Set field persistance - load newuser with session vars
			$uservars = $profile_handler->getUserVars();
			foreach ($_SESSION['profile'] as $field => $value) {
				if (in_array($field, $uservars)) $newuser->setVar($field, $value);
			}
			$reg_form =& getRegisterForm($newuser, $profile, $current_step, $steps[$current_step]);
			assign($reg_form, $xoopsTpl);
			$xoopsTpl->assign('stop', $stop);
			$xoopsTpl->assign('current_step', $current_step);
		}
		break;

	case 'register':
	default:
		include_once 'include/forms.php';
		$reg_form =& getRegisterForm($newuser, $profile, 0, $steps[0]);
		assign($reg_form, $xoopsTpl);
		$xoopsTpl->assign('current_step', 0);
		break;
}

$xoopsTpl->assign('module_home', _PROFILE_MA_REGISTER);
include 'footer.php';
function postSaveProcess($newuser) {
	global $icmsConfigUser, $icmsConfig, $template_dir;
	$newid = $newuser->getVar('uid');
	$member_handler = xoops_gethandler('member');
	if (!$member_handler->addUserToGroup(ICMS_GROUP_USERS, $newid)) {
		return _PROFILE_MA_REGISTERNG;
	}
	if ($icmsConfigUser['new_user_notify'] == 1 && !empty($icmsConfigUser['new_user_notify_group'])) {
		$xoopsMailer =& getMailer();
		$xoopsMailer->useMail();
		$member_handler =& xoops_gethandler('member');
		$xoopsMailer->setToGroups($member_handler->getGroup($icmsConfigUser['new_user_notify_group']));
		$xoopsMailer->setFromEmail($icmsConfig['adminmail']);
		$xoopsMailer->setFromName($icmsConfig['sitename']);
		$xoopsMailer->setSubject(sprintf(_PROFILE_MA_NEWUSERREGAT,$icmsConfig['sitename']));
		$xoopsMailer->setBody(sprintf(_PROFILE_MA_HASJUSTREG, $newuser->getVar('uname')));
		//xoops_debug('sending email');
		$xoopsMailer->send(true);
	   // xoops_debug($xoopsMailer->getErrors(true));
	}
	if ($icmsConfigUser['activation_type'] == 1) {
		return '';
	}
	if ($icmsConfigUser['activation_type'] == 0) {
		$xoopsMailer =& getMailer();
		$xoopsMailer->useMail();
		$xoopsMailer->setTemplate('register.tpl');
		$xoopsMailer->setTemplateDir($template_dir);
		$xoopsMailer->assign('X_SITENAME', $icmsConfig['sitename']);
		$xoopsMailer->assign('X_ADMINMAIL', $icmsConfig['adminmail']);
		$xoopsMailer->assign('X_SITEURL', ICMS_URL.'/');
		$xoopsMailer->assign('X_USERPASSWORD', $_POST['vpass']);
		$xoopsMailer->assign('X_USERLOGINNAME', $_POST['login_name']);
		$xoopsMailer->setToUsers(new XoopsUser($newid));
		$xoopsMailer->setFromEmail($icmsConfig['adminmail']);
		$xoopsMailer->setFromName($icmsConfig['sitename']);
		$xoopsMailer->setSubject(sprintf(_PROFILE_MA_USERKEYFOR, $newuser->getVar('uname')));

		//xoops_debug('sending email');
		if ( !$xoopsMailer->send(true) ) {
			//xoops_debug($xoopsMailer->getErrors(true));
			return _PROFILE_MA_YOURREGMAILNG;
		} else {
			return _PROFILE_MA_YOURREGISTERED;
		}
	} elseif ($icmsConfigUser['activation_type'] == 2) {
		$xoopsMailer =& getMailer();
		$xoopsMailer->useMail();
		$xoopsMailer->setTemplate('adminactivate.tpl');
		$xoopsMailer->setTemplateDir($template_dir);
		$xoopsMailer->assign('USERNAME', $newuser->getVar('uname'));
		$xoopsMailer->assign('USERLOGINNAME', $newuser->getVar('login_name'));
		$xoopsMailer->assign('USEREMAIL', $newuser->getVar('email'));
		$actkey = ICMS_URL.'/user.php?op=actv&id='.$newid.'&actkey='.$newuser->getVar('actkey');
		$xoopsMailer->assign('USERACTLINK', $actkey);

		$xoopsMailer->assign('SITENAME', $icmsConfig['sitename']);
		$xoopsMailer->assign('ADMINMAIL', $icmsConfig['adminmail']);
		$xoopsMailer->assign('SITEURL', ICMS_URL.'/');
		$member_handler =& xoops_gethandler('member');
		$xoopsMailer->setToGroups($member_handler->getGroup($icmsConfigUser['activation_group']));
		$xoopsMailer->setFromEmail($icmsConfig['adminmail']);
		$xoopsMailer->setFromName($icmsConfig['sitename']);
		$xoopsMailer->setSubject(sprintf(_PROFILE_MA_USERKEYFOR, $newuser->getVar('uname')));
	   // xoops_debug('sending email');
		if ( !$xoopsMailer->send(true) ) {
			xoops_debug($xoopsMailer->getErrors(true));
			return _PROFILE_MA_YOURREGMAILNG;
		} else {
			return _PROFILE_MA_YOURREGISTERED2;
		}
	}

	return '';
}

function assign($form, &$tpl) {
	$i = 0;
	$req_elements = $form->getRequired();
	$required = array();
	foreach ( $req_elements as $elt ) {
		if ($elt->getName() != '') {
			$required[] = $elt->getName();
		}
	}
	$elements = array();
	foreach ( $form->getElements() as $ele ) {
		if (is_a($ele, 'XoopsFormElement') ) {
			$n = ($ele->getName() != '') ? $ele->getName() : $i;
			$elements[$n]['name']	  = $ele->getName();
			$elements[$n]['caption']  = $ele->getCaption();
			$elements[$n]['body']	  = $ele->render();
			$elements[$n]['hidden']	  = $ele->isHidden();
			$elements[$n]['required'] = in_array($n, $required);
			if ($ele->getDescription() != '') {
				$elements[$n]['description']  = $ele->getDescription();
			}
		}
		$i++;
	}

	$js = $form->renderValidationJS();//var_dump($form->getName());exit;
	$tpl->assign($form->getName(), array('title' => $form->getTitle(), 'name' => $form->getName(), 'action' => $form->getAction(),  'method' => $form->getMethod(), 'extra' => 'onsubmit="return xoopsFormValidate_'.$form->getName().'(this);"'.$form->getExtra(), 'javascript' => $js, 'elements' => $elements));

}
?>