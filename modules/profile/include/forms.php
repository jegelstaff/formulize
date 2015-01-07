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
 * @version	$Id: forms.php 22408 2011-08-26 18:45:39Z phoenyx $
 */

/**
* Get {@link icms_form_Theme} for registering new users
*
* @param object $user {@link icms_member_user_Object} to register
* @param int $step Which step we are at
* @param ProfileRegstep $next_step
*
* @return object
*/
function &getRegisterForm(&$user, $profile, $next_step = 0, $step) {
    $action = $_SERVER['REQUEST_URI'];
    global $icmsConfigUser;
	$reg_form = new icms_form_Theme($step->getVar('step_name'), "regform", $action, "post");

    if ($step->getVar('step_intro') != "") $reg_form->addElement(new icms_form_elements_Label('', $step->getVar('step_intro')));
    if ($next_step == 0) {
		icms_loadLanguageFile('core', 'user');
        $uname_size = $icmsConfigUser['maxuname'] < 75 ? $icmsConfigUser['maxuname'] : 75;

        $elements[0][] = array('element' => new icms_form_elements_Text(_US_LOGIN_NAME, "login_name", $uname_size, 75, $user->getVar('login_name', 'e')), 'required' => true);
        $weights[0][] = 0;
        $elements[0][] = array('element' => new icms_form_elements_Text(_US_NICKNAME, "uname", $uname_size, 75, $user->getVar('uname', 'e')), 'required' => true);
        $weights[0][] = 0;
        $elements[0][] = array('element' => new icms_form_elements_Password(_MD_PROFILE_PASSWORD, "pass", 10, 32, "", false, ($icmsConfigUser['pass_level']?'password_adv':'')), 'required' => true);
        $weights[0][] = 0;
        $elements[0][] = array('element' => new icms_form_elements_Password(_MD_PROFILE_VERIFYPASS, "vpass", 10, 32, ""), 'required' => true);
        $weights[0][] = 0;
    }

    // Dynamic fields
    $profile_handler = icms_getmodulehandler('profile', basename(dirname(dirname(__FILE__))), 'profile');
    // Get fields
    $fields = $profile_handler->loadFields();

    foreach (array_keys($fields) as $i) {
	// Set field persistance - load profile with session vars
        $fieldname = $fields[$i]->getVar('field_name');
        if (!empty($_SESSION['profile'][$fieldname]) && $value = $_SESSION['profile'][$fieldname]) {
            $profile->setVar($fieldname,$value);
        }

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
    $cat_handler = icms_getmodulehandler('category', basename(dirname(dirname(__FILE__))), 'profile');
    $categories = $cat_handler->getObjects(null, true, false);

    foreach (array_keys($elements) as $k) {
        array_multisort($weights[$k], SORT_ASC, array_keys($elements[$k]), SORT_ASC, $elements[$k]);
        $title = isset($categories[$k]) ? $categories[$k]['cat_title'] : _MD_PROFILE_DEFAULT;
        $desc = isset($categories[$k]) ? $categories[$k]['cat_description'] : "";

        $reg_form->addElement(new icms_form_elements_Label($title, $desc), false);
        foreach (array_keys($elements[$k]) as $i) {
            $reg_form->addElement($elements[$k][$i]['element'], $elements[$k][$i]['required']);
        }
    }
    //end of Dynamic User fields

    if ($next_step == 0 && $icmsConfigUser['reg_dispdsclmr'] != 0 && $icmsConfigUser['reg_disclaimer'] != '') {
        $disc_tray = new icms_form_elements_Tray(_MD_PROFILE_DISCLAIMER, '<br />');
        $disc_text = new icms_form_elements_Label("", "<div id=\"disclaimer\">".icms_core_DataFilter::checkVar($icmsConfigUser['reg_disclaimer'], 'html', 'output')."</div>");
        $disc_tray->addElement($disc_text);
        $session_agreement = empty($_SESSION['profile']['agree_disc']) ? '':$_SESSION['profile']['agree_disc'];
        $agree_chk = new icms_form_elements_Checkbox('', 'agree_disc', $session_agreement);
        $agree_chk->addOption(1, _MD_PROFILE_IAGREE);
        $disc_tray->addElement($agree_chk);
        $reg_form->addElement($disc_tray);
    }
	if ($next_step == 0 && $icmsConfigUser['use_captcha'] == 1) {
		$reg_form->addElement(new icms_form_elements_Captcha(_SECURITYIMAGE_GETCODE, "scode"));
	}
    $reg_form->addElement(new icms_form_elements_Hidden("op", "step"));
    $reg_form->addElement(new icms_form_elements_Hidden("step", $next_step));
    $reg_form->addElement(new icms_form_elements_Button("", "submit", _MD_PROFILE_SUBMIT, "submit"));

    return $reg_form;
}

/**
* Get {@link icms_form_Simple} for finishing registration
*
* @param object $user {@link icms_member_user_Object} object to finish registering
* @param string $vpass Password verification field
* @param mixed $action URL to submit to or false for $_SERVER['REQUEST_URI']
*
* @return object
*/
function getFinishForm(&$user, $vpass, $action = false) {
    if ($action === false) {
        $action = $_SERVER['REQUEST_URI'];
    }

    $form = new icms_form_Simple("", "userinfo", $action, "post");
    $profile = $user->getProfile();
    $array = array_merge(array_keys($user->getVars()), array_keys($profile->getVars()));
    foreach ($array as $field) {
        $value = $user->getVar($field, 'e');
        if (is_array($value)) {
            foreach ($value as $thisvalue) {
                $form->addElement(new icms_form_elements_Hidden($field."[]", $thisvalue));
            }
        }
        else {
            $form->addElement(new icms_form_elements_Hidden($field, $value));
        }
    }
    $form->setExtra("", true);
    $form->addElement(new icms_form_elements_Hidden('vpass', icms_core_DataFilter::htmlSpecialChars($vpass)));
    $form->addElement(new icms_form_elements_Hidden('op', 'finish'));
    $form->addElement(new icms_form_elements_Button('', 'submit', _MD_PROFILE_FINISH, 'submit'));
    return $form;
}

/**
* Get {@link icms_form_Theme} for editing a user
*
* @param object $user {@link icms_member_user_Object} to edit
*
* @return object
*/
function getUserForm(&$user, $profile = false, $action = false) {
    global $icmsConfig, $icmsConfigUser, $icmsConfigAuth;
    if ($action === false) {
        $action = $_SERVER['REQUEST_URI'];
    }
    $title = $user->isNew() ? _AM_PROFILE_ADDUSER : _MD_PROFILE_EDITPROFILE;
	icms_loadLanguageFile('core', 'user');
    $form = new icms_form_Theme($title, 'userinfo', $action, 'post', true);

    $profile_handler = icms_getModuleHandler('profile', basename(dirname(dirname(__FILE__))), 'profile');
    // Dynamic fields
    if (!$profile) {
        $profile_handler = icms_getModuleHandler('profile', basename(dirname(dirname(__FILE__))), 'profile');
        $profile = $profile_handler->get($user->getVar('uid'));
    }
    // Get fields
    $fields = $profile_handler->loadFields();
    // Get ids of fields that can be edited
    $gperm_handler = icms::handler('icms_member_groupperm');
    $editable_fields = $gperm_handler->getItemIds('profile_edit', icms::$user->getGroups(), icms::$module->getVar('mid'));

    $email_tray = new icms_form_elements_Tray(_MD_PROFILE_EMAIL, '<br />');
    if ($user->isNew() || icms::$user->isAdmin()) {
        $elements[0][] = array('element' => new icms_form_elements_Text(_US_LOGIN_NAME, 'login_name', 25, 75, $user->getVar('login_name', 'e')), 'required' => 1);
        $weights[0][] = 0;
        $elements[0][] = array('element' => new icms_form_elements_Text(_US_NICKNAME, 'uname', 25, 75, $user->getVar('uname', 'e')), 'required' => 1);
        $weights[0][] = 0;
        $elements[0][] = array('element' => new icms_form_elements_Text(_MD_PROFILE_EMAIL, 'email', 30, 60, $user->getVar('email')), 'required' => 1);
        $weights[0][] = 0;
    } else {
        $elements[0][] = array('element' => new icms_form_elements_Label(_US_LOGIN_NAME, $user->getVar('login_name', 'e')), 'required' => 0);
        $weights[0][] = 0;
        if ($icmsConfigUser['allow_chguname'] == 1) {
            $elements[0][] = array('element' => new icms_form_elements_Text(_US_NICKNAME, 'uname', 25, 75, $user->getVar('uname', 'e')), 'required' => 1);
        } else {
            $elements[0][] = array('element' => new icms_form_elements_Label(_US_NICKNAME, $user->getVar('uname')), 'required' => 0);
        }
		$weights[0][] = 0;
		$elements[0][] = array('element' => new icms_form_elements_Label(_MD_PROFILE_EMAIL, $user->getVar('email')), 'required' => 0);
		$weights[0][] = 0;
    }

    if ($icmsConfigAuth['auth_openid'] == 1) {
        $openid_tray = new icms_form_elements_Tray(_MD_PROFILE_OPENID, '<br />');
        $openid_tray->addElement(new icms_form_elements_Text('', 'openid', 30, 255, $user->getVar('openid')));
        $openid_checkbox = new icms_form_elements_Checkbox('', 'user_viewoid', $user->getVar('user_viewoid'));
        $openid_checkbox->addOption('1', _MD_PROFILE_OPENID_VIEW);
        $openid_tray->addElement($openid_checkbox);
        $elements[0][] = array('element' => $openid_tray, 'required' => 0);
        $weights[0][] = 0;
    }

    if ((icms::$user->isAdmin() && $user->getVar('uid') != icms::$user->getVar('uid')) OR  $user->getVar('uid') == icms::$user->getVar('uid')) { // ALTERED BY FREEFORM SOLUTIONS TO ALLOW USERS TO CHANGE THEIR OWN PASSWORDS
        //If the user is an admin and is editing someone else
        $pwd_text = new icms_form_elements_Password('', 'password', 10, 32, "", false, ($icmsConfigUser['pass_level']?'password_adv':''));
        $pwd_text2 = new icms_form_elements_Password('', 'vpass', 10, 32);
        $pwd_tray = new icms_form_elements_Tray(_MD_PROFILE_PASSWORD.'<br />'._MD_PROFILE_TYPEPASSTWICE);
        $pwd_tray->addElement($pwd_text, $user->isNew());
        $pwd_tray->addElement($pwd_text2, $user->isNew());
        $elements[0][] = array('element' => $pwd_tray, 'required' => 1); //cannot set an element tray required
        $weights[0][] = 0;

		if (icms::$user->isAdmin() && $user->getVar('uid') != icms::$user->getVar('uid')) { // ALTERED BY FREEFORM SOLUTIONS SO THAT USERS CAN'T ALTER THEIR OWN LEVEL
            $level_radio = new icms_form_elements_Radio(_MD_PROFILE_ACTIVEUSER, 'level', $user->getVar('level'));
            $level_radio->addOption(1, _MD_PROFILE_ACTIVE);
            $level_radio->addOption(0, _MD_PROFILE_INACTIVE);
            $level_radio->addOption(-1, _MD_PROFILE_DISABLED);
            $elements[0][] = array('element' => $level_radio, 'required' => 0);
            $weights[0][] = 0;
		}
    }

    $elements[0][] = array('element' => new icms_form_elements_Hidden('uid', $user->getVar('uid')), 'required' => 0);
    $weights[0][] = 0;
    $elements[0][] = array('element' => new icms_form_elements_Hidden('op', 'save'), 'required' => 0);
    $weights[0][] = 0;

    $profile_cat_handler = icms_getmodulehandler('category', basename(dirname(dirname(__FILE__))), 'profile');
    $categories = $profile_cat_handler->getObjects(null, true, false);

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

    if (icms::$user && icms::$user->isAdmin()) {
		icms_loadLanguageFile(basename(dirname(dirname(__FILE__))), 'admin');
        //If user has admin rights on groups
        include_once ICMS_ROOT_PATH."/modules/system/constants.php";
        if ($gperm_handler->checkRight("system_admin", XOOPS_SYSTEM_GROUP, icms::$user->getGroups(), 1)) {
            //add group selection
            $group_select = new icms_form_elements_select_Group(_AM_PROFILE_GROUP, 'groups', false, $user->getGroups(), 15, true); // UPDATED BY FREEFORM SOLUTIONS - MAKE LIST TALLER
            $elements[0][] = array('element' => $group_select, 'required' => 0);
            $weights[0][] = 15000;
        }
    }

    ksort($elements);
    foreach (array_keys($elements) as $k) {
        array_multisort($weights[$k], SORT_ASC, array_keys($elements[$k]), SORT_ASC, $elements[$k]);
        $title = isset($categories[$k]) ? $categories[$k]['cat_title'] : _MD_PROFILE_DEFAULT;
        $desc = isset($categories[$k]) ? $categories[$k]['cat_description'] : "";
        $form->addElement(new icms_form_elements_Label($title, $desc), false);
        foreach (array_keys($elements[$k]) as $i) {
            $form->addElement($elements[$k][$i]['element'], $elements[$k][$i]['required']);
        }
    }

    $form->addElement(new icms_form_elements_Button('', 'submit', _MD_PROFILE_SAVECHANGES, 'submit'));
    return $form;
}