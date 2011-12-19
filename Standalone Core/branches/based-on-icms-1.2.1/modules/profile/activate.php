<?php
/**
 * Extended User Profile
 *
 *
 * @copyright       The ImpressCMS Project http://www.impresscms.org/
 * @license         LICENSE.txt
 * @license			GNU General Public License (GPL) http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @package         modules
 * @since           1.2
 * @author          Jan Pedersen
 * @author          The SmartFactory <www.smartfactory.ca>
 * @author	   		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version         $Id$
 */

//$xoopsOption['pagetype'] = "user";
include "../../mainfile.php";
include ICMS_ROOT_PATH.'/header.php';

if (isset($_REQUEST['op']) && $_REQUEST['op'] == "actv") {
icms_loadLanguageFile('core', 'user');
    $id = intval($_GET['id']);
    $actkey = trim($_GET['actkey']);
    if (empty($id)) {
        redirect_header(ICMS_URL,1,'');
        exit();
    }
    $member_handler =& xoops_gethandler('member');
    $thisuser =& $member_handler->getUser($id);
    if (!is_object($thisuser)) {
        exit();
    }
    if ($thisuser->getVar('actkey') != $actkey) {
        redirect_header(ICMS_URL.'/',5,_PROFILE_MA_ACTKEYNOT);
    } else {
        if ($thisuser->getVar('level') > 0 ) {
            redirect_header(ICMS_URL.'/modules/'.basename( dirname( __FILE__ ) ).'/index.php',5,_PROFILE_MA_ACONTACT);
        } else {
            if (false != $member_handler->activateUser($thisuser)) {
                $config_handler =& xoops_gethandler('config');
                if ($icmsModuleConfig['activation_type'] == 2) {
                    $myts =& MyTextSanitizer::getInstance();
                    $xoopsMailer =& getMailer();
                    $xoopsMailer->useMail();
                    $xoopsMailer->setTemplateDir(ICMS_ROOT_PATH."/modules/".basename( dirname( __FILE__ ) )."/language/".$icmsConfig['language']."/mail_template");
                    $xoopsMailer->setTemplate('activated.tpl');
                    $xoopsMailer->assign('SITENAME', $icmsConfig['sitename']);
                    $xoopsMailer->assign('ADMINMAIL', $icmsConfig['adminmail']);
                    $xoopsMailer->assign('SITEURL', ICMS_URL."/");
                    $xoopsMailer->setToUsers($thisuser);
                    $xoopsMailer->setFromEmail($icmsConfig['adminmail']);
                    $xoopsMailer->setFromName($icmsConfig['sitename']);
                    $xoopsMailer->setSubject(sprintf(_PROFILE_MA_YOURACCOUNT,$icmsConfig['sitename']));
                    include ICMS_ROOT_PATH.'/header.php';
                    if ( !$xoopsMailer->send() ) {
                        printf(_PROFILE_MA_ACTVMAILNG, $thisuser->getVar('uname'));
                    } else {
                        printf(_PROFILE_MA_ACTVMAILOK, $thisuser->getVar('uname'));
                    }
                    include ICMS_ROOT_PATH.'/footer.php';
                } else {
                    redirect_header(ICMS_URL.'/user.php',5,_PROFILE_MA_ACTLOGIN);
                }
            } else {
                redirect_header(ICMS_URL.'/index.php',5,'Activation failed!');
            }
        }
    }
    exit();
}
elseif (!isset($_REQUEST['submit']) || !isset($_REQUEST['email']) || trim($_REQUEST['email']) == "") {
    include_once(ICMS_ROOT_PATH."/class/xoopsformloader.php");
    $form = new XoopsThemeForm('', 'form', 'activate.php');
    $form->addElement(new XoopsFormText(_PROFILE_MA_EMAIL, 'email', 25, 255));
    $form->addElement(new XoopsFormButton('', 'submit', _SUBMIT, 'submit'));
    $form->display();
}else{
    $myts =& MyTextSanitizer::getInstance();
    $member_handler =& xoops_gethandler('member');
    $getuser =& $member_handler->getUsers(new Criteria('email', $myts->addSlashes(trim($_REQUEST['email']))));
    if (count($getuser) == 0) {
        redirect_header(ICMS_URL, 2, _PROFILE_MA_SORRYNOTFOUND);
    }
    if($getuser[0]->isActive()){
        redirect_header(ICMS_URL, 2, sprintf(_PROFILE_MA_USERALREADYACTIVE, $getuser[0]->getVar('email')));
    }
    if($getuser[0]->isDisabled()){
        redirect_header(ICMS_URL, 2, sprintf(_PROFILE_MA_USERDISABLED, $getuser[0]->getVar('email')));
    }
    $xoopsMailer =& getMailer();
    $xoopsMailer->useMail();
    $xoopsMailer->setTemplate('register.tpl');
    $xoopsMailer->setTemplateDir(ICMS_ROOT_PATH."/modules/".$icmsModule->getVar('dirname')."/language/".$icmsConfig['language']."/mail_template/");
    $xoopsMailer->assign('SITENAME', $icmsConfig['sitename']);
    $xoopsMailer->assign('ADMINMAIL',
    $icmsConfig['adminmail']);
    $xoopsMailer->assign('SITEURL', ICMS_URL."/");
    $xoopsMailer->setToUsers($getuser[0]);
    $xoopsMailer->setFromEmail($icmsConfig['adminmail']);
    $xoopsMailer->setFromName($icmsConfig['sitename']);
    $xoopsMailer->setSubject($xoopsMailer->setSubject(sprintf(_PROFILE_MA_USERKEYFOR, $getuser[0]->getVar('uname'))));
    if ( !$xoopsMailer->send() ) {
        echo _PROFILE_MA_YOURREGMAILNG;
    } else {
        echo _PROFILE_MA_YOURREGISTERED;
    }
}
include ICMS_ROOT_PATH.'/footer.php';
?>