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

/**
* Get {@link XoopsThemeForm} for registering new users
*
* @param object $user {@link XoopsUser} to register
* @param int $step Which step we are at
* @param ProfileRegstep $next_step
*
* @return object
*/
function getRegisterForm(&$user, $profile, $next_step = 0, $step) {
    $action = $_SERVER['REQUEST_URI'];
    global $icmsConfigUser;
    include_once ICMS_ROOT_PATH."/class/xoopsformloader.php";
	include_once ICMS_ROOT_PATH."/modules/".basename(  dirname(  dirname( __FILE__ ) ) )."/class/forms/profile_form.php";
    $reg_form = new ProfileForm($step->getVar('step_name'), "regform", $action, "post");

    if ($step->getVar('step_intro') != "") {
        $reg_form->addElement(new XoopsFormLabel('', $step->getVar('step_intro')));
    }

    if ($next_step == 0) {
        $uname_size = $icmsConfigUser['maxuname'] < 75 ? $icmsConfigUser['maxuname'] : 75;

        $elements[0][] = array('element' => new XoopsFormText(_PROFILE_MA_USERLOGINNAME, "login_name", $uname_size, 75, $user->getVar('login_name', 'e')), 'required' => true);
        $weights[0][] = 0;

        $elements[0][] = array('element' => new XoopsFormText(_PROFILE_MA_USERNAME, "uname", $uname_size, 75, $user->getVar('uname', 'e')), 'required' => true);
        $weights[0][] = 0;

        $elements[0][] = array('element' => new XoopsFormText(_PROFILE_MA_EMAIL, "email", $uname_size, 60, $user->getVar('email', 'e')), 'required' => true);
        $weights[0][] = 0;

        $elements[0][] = array('element' => new XoopsFormPassword(_PROFILE_MA_PASSWORD, "pass", 10, 32, "", false, ($icmsConfigUser['pass_level']?'password_adv':'')), 'required' => true);
        $weights[0][] = 0;
        
        $elements[0][] = array('element' => new XoopsFormPassword(_PROFILE_MA_VERIFYPASS, "vpass", 10, 32, ""), 'required' => true);
        $weights[0][] = 0;
    }

    // Dynamic fields
    $profile_handler =& icms_getmodulehandler( 'profile', basename(  dirname(  dirname( __FILE__ ) ) ), 'profile' );
    // Get fields
    $fields =& $profile_handler->loadFields();

    foreach (array_keys($fields) as $i) {
// MPB ADD - START
// Set field persistance - load profile with session vars
        $fieldname = $fields[$i]->getVar('field_name');
        if (!empty($_SESSION['profile'][$fieldname]) && $value = $_SESSION['profile'][$fieldname]) {
            $profile->setVar($fieldname,$value);
        }
// MPB ADD - END
        if ($fields[$i]->getVar('step_id') == $step->getVar('step_id')) {
            $fieldinfo['element'] = $fields[$i]->getEditElement($user, $profile);
            $fieldinfo['required'] = $fields[$i]->getVar('field_required');

            $key = $fields[$i]->getVar('catid');
            $elements[$key][] = $fieldinfo;
            $weights[$key][] = $fields[$i]->getVar('field_weight');
        }
    }
    ksort($elements);

    // Get categories
    $cat_handler = icms_getmodulehandler( 'category', basename(  dirname(  dirname( __FILE__ ) ) ), 'profile' );
    $categories = $cat_handler->getObjects(null, true, false);

    foreach (array_keys($elements) as $k) {
        array_multisort($weights[$k], SORT_ASC, array_keys($elements[$k]), SORT_ASC, $elements[$k]);
        $title = isset($categories[$k]) ? $categories[$k]['cat_title'] : _PROFILE_MA_DEFAULT;
        $desc = isset($categories[$k]) ? $categories[$k]['cat_description'] : "";
          $reg_form->insertBreak($title, 'head');
        $reg_form->addElement(new XoopsFormLabel($title, $desc), false);
        foreach (array_keys($elements[$k]) as $i) {
            $reg_form->addElement($elements[$k][$i]['element'], $elements[$k][$i]['required']);
        }
    }
    //end of Dynamic User fields

    if ($next_step == 0 && $icmsConfigUser['reg_dispdsclmr'] != 0 && $icmsConfigUser['reg_disclaimer'] != '') {
        $disc_tray = new XoopsFormElementTray(_PROFILE_MA_DISCLAIMER, '<br />');
        $disc_text = new XoopsFormLabel("", "<div id=\"disclaimer\">".$GLOBALS["myts"]->displayTarea($icmsConfigUser['reg_disclaimer'],1)."</div>");
        $disc_tray->addElement($disc_text);
        $session_agreement = empty($_SESSION['profile']['agree_disc']) ? '':$_SESSION['profile']['agree_disc'];
        $agree_chk = new XoopsFormCheckBox('', 'agree_disc', $session_agreement);
        $agree_chk->addOption(1, _PROFILE_MA_IAGREE);
        $disc_tray->addElement($agree_chk);
        $reg_form->addElement($disc_tray);
    }
	if ($next_step == 0 && $icmsConfigUser['use_captcha'] == 1) {
	$reg_form->addElement(new IcmsFormCaptcha(_SECURITYIMAGE_GETCODE, "scode"));
	}
    $reg_form->addElement(new XoopsFormHidden("op", "step"));
    $reg_form->addElement(new XoopsFormHidden("step", $next_step));
    $reg_form->addElement(new XoopsFormButton("", "submit", _PROFILE_MA_SUBMIT, "submit"));
    //var_dump($reg_form);
    return $reg_form;
}

/**
* Get {@link XoopsSimpleForm} for finishing registration
*
* @param object $user {@link XoopsUser} object to finish registering
* @param string $vpass Password verification field
* @param mixed $action URL to submit to or false for $_SERVER['REQUEST_URI']
*
* @return object
*/
function getFinishForm(&$user, $vpass, $action = false) {
    if ($action === false) {
        $action = $_SERVER['REQUEST_URI'];
    }
    include_once ICMS_ROOT_PATH."/class/xoopsformloader.php";

    $form = new XoopsSimpleForm("", "userinfo", $action, "post");
    $profile = $user->getProfile();
    $array = array_merge(array_keys($user->getVars()), array_keys($profile->getVars()));
    foreach ($array as $field) {
        $value = $user->getVar($field, 'e');
        if (is_array($value)) {
            foreach ($value as $thisvalue) {
                $form->addElement(new XoopsFormHidden($field."[]", $thisvalue));
            }
        }
        else {
            $form->addElement(new XoopsFormHidden($field, $value));
        }
    }
    $form->setExtra("", true);
    $myts =& MyTextSanitizer::getInstance();
    $form->addElement(new XoopsFormHidden('vpass', $myts->htmlSpecialChars($vpass)));
    $form->addElement(new XoopsFormHidden('op', 'finish'));
    $form->addElement(new XoopsFormButton('', 'submit', _PROFILE_MA_FINISH, 'submit'));
    return $form;
}

/**
* Get {@link XoopsThemeForm} for editing a user
*
* @param object $user {@link XoopsUser} to edit
*
* @return object
*/
function getUserForm(&$user, $profile = false, $action = false) {
    global $icmsConfig, $icmsModule, $icmsUser, $icmsConfigUser, $icmsConfigAuth;
    if ($action === false) {
        $action = $_SERVER['REQUEST_URI'];
    }
    include_once ICMS_ROOT_PATH."/class/xoopsformloader.php";
    $title = $user->isNew() ? _PROFILE_AM_ADDUSER : _PROFILE_MA_EDITPROFILE;

    $form = new XoopsThemeForm($title, 'userinfo', $action, 'post', true);

    $profile_handler =& icms_getmodulehandler( 'profile', basename(  dirname(  dirname( __FILE__ ) ) ), 'profile' );
    // Dynamic fields
    if (!$profile) {
        $profile_handler = icms_getmodulehandler( 'profile', basename(  dirname(  dirname( __FILE__ ) ) ), 'profile' );
        $profile = $profile_handler->get($user->getVar('uid'));
    }
    // Get fields
    $fields =& $profile_handler->loadFields();
    // Get ids of fields that can be edited
    $gperm_handler =& xoops_gethandler('groupperm');
    $editable_fields =& $gperm_handler->getItemIds('profile_edit', $icmsUser->getGroups(), $icmsModule->getVar('mid'));

    $email_tray = new XoopsFormElementTray(_PROFILE_MA_EMAIL, '<br />');
    if ($user->isNew() || $icmsUser->isAdmin()) {
        $elements[0][] = array('element' => new XoopsFormText(_PROFILE_MA_USERLOGINNAME, 'login_name', 25, 75, $user->getVar('login_name', 'e')), 'required' => 1);
        $weights[0][] = 0;
        $elements[0][] = array('element' => new XoopsFormText(_PROFILE_MA_USERNAME, 'uname', 25, 75, $user->getVar('uname', 'e')), 'required' => 1);
        $weights[0][] = 0;
        $elements[0][] = array('element' => new XoopsFormText(_PROFILE_MA_EMAIL, 'email', 30, 60, $user->getVar('email')), 'required' => 1);
        $weights[0][] = 0;
    } else {
        $elements[0][] = array('element' => new XoopsFormLabel(_PROFILE_MA_USERLOGINNAME, $user->getVar('login_name', 'e')), 'required' => 0);
        $weights[0][] = 0;
        if ($icmsConfigUser['allow_chguname'] == 1) {
            $elements[0][] = array('element' => new XoopsFormText(_PROFILE_MA_USERNAME, 'uname', 25, 75, $user->getVar('uname', 'e')), 'required' => 1);
        } else {
            $elements[0][] = array('element' => new XoopsFormLabel(_PROFILE_MA_USERNAME, $user->getVar('uname')), 'required' => 0);
        }
	$weights[0][] = 0;
        if ($icmsConfigUser['allow_chgmail'] == 1) {
		$elements[0][] = array('element' => new XoopsFormText(_PROFILE_MA_EMAIL, 'email', 30, 60, $user->getVar('email')), 'required' => 1);
	} else {
		$elements[0][] = array('element' => new XoopsFormLabel(_PROFILE_MA_EMAIL, $user->getVar('email')), 'required' => 0);
	}
	$weights[0][] = 0;
    }

    if ($icmsConfigAuth['auth_openid'] == 1) {
        $openid_tray = new XoopsFormElementTray(_PROFILE_MA_OPENID, '<br />');
        $openid_tray->addElement(new XoopsFormText('', 'openid', 30, 255, $user->getVar('openid')));
        $openid_checkbox = new XoopsFormCheckbox('', 'user_viewoid', $user->getVar('user_viewoid'));
        $openid_checkbox->addOption('1', _PROFILE_MA_OPENID_VIEW);
        $openid_tray->addElement($openid_checkbox);
        $elements[0][] = array('element' => $openid_tray, 'required' => 0);
        $weights[0][] = 0;
    }

    if ($icmsUser->isAdmin() && $user->getVar('uid') != $icmsUser->getVar('uid')) {
        //If the user is an admin and is editing someone else
        $pwd_text = new XoopsFormPassword('', 'password', 10, 32, "", false, ($icmsConfigUser['pass_level']?'password_adv':''));
        $pwd_text2 = new XoopsFormPassword('', 'vpass', 10, 32);
        $pwd_tray = new XoopsFormElementTray(_PROFILE_MA_PASSWORD.'<br />'._PROFILE_MA_TYPEPASSTWICE);
        $pwd_tray->addElement($pwd_text, $user->isNew());
        $pwd_tray->addElement($pwd_text2, $user->isNew());
        $elements[0][] = array('element' => $pwd_tray, 'required' => 1); //cannot set an element tray required
        $weights[0][] = 0;

        $level_radio = new XoopsFormRadio(_PROFILE_MA_ACTIVEUSER, 'level', $user->getVar('level'));
        $level_radio->addOption(1, _PROFILE_MA_ACTIVE);
        $level_radio->addOption(0, _PROFILE_MA_INACTIVE);
        $level_radio->addOption(-1, _PROFILE_MA_DISABLED);
        $elements[0][] = array('element' => $level_radio, 'required' => 0);
        $weights[0][] = 0;
    }

    $elements[0][] = array('element' => new XoopsFormHidden('uid', $user->getVar('uid')), 'required' => 0);
    $weights[0][] = 0;
    $elements[0][] = array('element' => new XoopsFormHidden('op', 'save'), 'required' => 0);
    $weights[0][] = 0;

    $profile_cat_handler =& icms_getmodulehandler( 'category', basename(  dirname(  dirname( __FILE__ ) ) ), 'profile' );
    /* @var $profile_cat_handler ProfileCategoryHandler */

    $categories =& $profile_cat_handler->getObjects(null, true, false);

    foreach (array_keys($fields) as $i) {
        if (in_array($fields[$i]->getVar('fieldid'), $editable_fields)) {
			if ($fields[$i]->getVar('field_edit') == 1) {
				$fieldinfo['element'] = $fields[$i]->getEditElement($user, $profile);
	            $fieldinfo['required'] = $fields[$i]->getVar('field_required');
	
	            $key = $fields[$i]->getVar('catid');
	            $elements[$key][] = $fieldinfo;
	            $weights[$key][] = $fields[$i]->getVar('field_weight');
	
	            // Image upload
	            if ($fields[$i]->getVar('field_type') == "image") {
	                $form->setExtra('enctype="multipart/form-data"');
	            }
            }
        }
    }

    if ($icmsUser && $icmsUser->isAdmin()) {
        if (@!include_once(ICMS_ROOT_PATH."/modules/".basename(  dirname(  dirname( __FILE__ ) ) )."/language/".$icmsConfig['language']."/admin.php")) {
            include_once(ICMS_ROOT_PATH."/modules/".basename(  dirname(  dirname( __FILE__ ) ) )."/language/english/admin.php");
        }
        $gperm_handler =& xoops_gethandler('groupperm');
        //If user has admin rights on groups
        include_once ICMS_ROOT_PATH."/modules/system/constants.php";
        if ($gperm_handler->checkRight("system_admin", XOOPS_SYSTEM_GROUP, $icmsUser->getGroups(), 1)) {
            //add group selection
            $group_select = new XoopsFormSelectGroup(_PROFILE_AM_GROUP, 'groups', false, $user->getGroups(), 5, true);
            $elements[0][] = array('element' => $group_select, 'required' => 0);
            $weights[0][] = 15000;
        }
    }

    ksort($elements);
    foreach (array_keys($elements) as $k) {
        array_multisort($weights[$k], SORT_ASC, array_keys($elements[$k]), SORT_ASC, $elements[$k]);
        $title = isset($categories[$k]) ? $categories[$k]['cat_title'] : _PROFILE_MA_DEFAULT;
        $desc = isset($categories[$k]) ? $categories[$k]['cat_description'] : "";
        $form->addElement(new XoopsFormLabel($title, $desc), false);
        foreach (array_keys($elements[$k]) as $i) {
            $form->addElement($elements[$k][$i]['element'], $elements[$k][$i]['required']);
        }
    }

    $form->addElement(new XoopsFormButton('', 'submit', _PROFILE_MA_SAVECHANGES, 'submit'));
    return $form;
}
?>