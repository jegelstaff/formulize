<?php

/* Copyright the Formulize Project - Julian Egelstaff 2021
 *
 * Dialog handler for 2FA login challenge processes
 * return challenge ui if user needs it, return nothing if not
 */

$xoopsOption['ignore_closed_site'] = true; // challenge endpoint must be reachable when site is closed
include_once "../../mainfile.php";

icms::$logger->disableLogger();
while(ob_get_level()) {
    ob_end_clean();
}

include "manage.php"; // defines constants

$icmsAuth =& icms_auth_Factory::getAuthConnection(icms_core_DataFilter::addSlashes(trim($_POST['u'])));
$user = $icmsAuth->authenticate(trim($_POST['u']), trim($_POST['p']));

if($user AND
   $method = user2FAMethod($user) AND
   userRemembersDevice($user) == false) {
    $rememberLabel = sprintf(_US_REMEMBER_DEVICE_FOR, tfa_formatWindow(tfa_rememberDeviceDays()));
    $codebox = "<br><br>"._US_2FA_CODE."<input type='text' id='dialog-tfacode' value=''><br><br><input type='checkbox' id='dialog-tfaremember'> <label for='dialog-tfaremember'>".$rememberLabel."</label>";
    switch($method) {
        case TFA_SMS:
            $message = sendCode(TFA_SMS, $user->getVar('uid')); // will return errors
            $profile_handler = xoops_getmodulehandler('profile', 'profile');
            $profile = $profile_handler->get($user->getVar('uid'));
            $loginTokenContact = preg_replace('/[^0-9]/', '', $profile->getVar('2faphone'));
            $method = 'texts';
            break;
        case TFA_APP:
            $message = '';
            $loginTokenContact = 'authenticator-app';
            $method = 'app';
            break;
        default:
            $message = sendCode(TFA_EMAIL, $user->getVar('uid')); // will return errors
            $loginTokenContact = $user->getVar('email');
            $method = 'email';
    }
    $loginToken = icms::$security->createToken(300, $loginTokenContact);
    $message = $message ? $message : _US_ENTER_CODE.$method.".$codebox";
    $tokenInput = "<input type='hidden' class='tfa-login-token' value='" . htmlspecialchars($loginToken, ENT_QUOTES) . "'>";
    print "<center>$message</center>" . $tokenInput;
}
