<?php

/* Copyright the Formulize Project - Julian Egelstaff 2021
 *
 * Dialog handler for 2FA login challenge processes
 * return challenge ui if user needs it, return nothing if not
 */

include_once "../../mainfile.php";

icms::$logger->disableLogger();
while(ob_get_level()) {
    ob_end_clean();
}

include "manage.php"; // defines constants

$accountIdentifier = '';

$user = false;
if(isset($_GET['a']) AND $GLOBALS['xoopsSecurity']->check(true, $_GET['token'])) {
    include_once XOOPS_ROOT_PATH.'/modules/formulize/include/functions.php';
    global $xoopsDB;
    $sql = 'SELECT uid FROM '.$xoopsDB->prefix('users').' WHERE login_name = "'.formulize_db_escape($_GET['a']).'" OR email = "'.formulize_db_escape($_GET['a']).'" LIMIT 0,1';
    $res = $xoopsDB->query($sql);
    if($xoopsDB->getRowsNum($res)==1) {
        $row = $xoopsDB->fetchRow($res);
        $uid = $row[0];
        $member_handler = icms::handler('icms_member');
        $user = $member_handler->getUser($uid);
        $accountIdentifier = "<input type='hidden' id='dialog-tfaaccountidentifier' value='".strip_tags(htmlspecialchars($_GET['a'], ENT_QUOTES))."'></input>";
    }
} else {
    $icmsAuth =& icms_auth_Factory::getAuthConnection(icms_core_DataFilter::addSlashes($uname));
    $user = $icmsAuth->authenticate(trim($_GET['u']), trim($_GET['p']));
}
if($user) {
    $method = user2FAMethod($user);
    $userRemembersDevice = userRemembersDevice($user);
    $rememberDeviceHTML = "<br><input type='checkbox' id='dialog-tfaremember'> <label for='dialog-tfaremember'>"._US_DONT_ASK_AGAIN."</label>";
    if(isset($_GET['a'])) { // if the user is trying to recover account info, use email as fall back method, and ignore if they remember the device
        $method = $method ? $method : TFA_EMAIL;
        $userRemembersDevice = false;
        $rememberDeviceHTML = "";
    }
	if($method AND $userRemembersDevice == false) {
        $codebox = "<br><br>"._US_2FA_CODE."<input type='text' id='dialog-tfacode' value=''>".$rememberDeviceHTML;
        switch($method) {
            case TFA_SMS:
                $message = sendCode(TFA_SMS, $user->getVar('uid')); // will return errors
                $method = 'texts';
                break;
            case TFA_APP:
                $message = '';
                $method = 'app';
                break;
            default:
                $message = sendCode(TFA_EMAIL, $user->getVar('uid')); // will return errors
                $method = 'email';
        }
        $message = $message ? $message : _US_ENTER_CODE.$method.".$codebox";
        print "<center>$message</center>$accountIdentifier";
	}
}
