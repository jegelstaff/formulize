<?php

/* Copyright the Formulize Project - Julian Egelstaff 2021
 *
 * Dialog handler for 2FA login challenge processes
 * return challenge ui if user needs it, return nothing if not
 */

include_once "../../mainfile.php";
include "manage.php"; // defines constants

icms::$logger->disableLogger();

while(ob_get_level()) {
    ob_end_clean();
}

$member_handler = icms::handler('icms_member');

$icmsAuth =& icms_auth_Factory::getAuthConnection(icms_core_DataFilter::addSlashes($uname));
$user = $icmsAuth->authenticate(trim($_GET['u']), trim($_GET['p']));
if($user) {
	if($method = user2FAMethod($user)) {
        if(userRemembersDevice($user) == false) {
            $codebox = "<br><br>Code: <input type='text' id='dialog-tfacode' value=''><br><input type='checkbox' id='dialog-tfaremember'> <label for='dialog-tfaremember'>Don't ask again on this device</label>";
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
            $message = $message ? $message : "Enter the Two-Factor Authentication Code from your ".$method.".$codebox";
            print "<center>$message</center>";
        }
	}
}
