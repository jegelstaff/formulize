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

$icmsAuth =& icms_auth_Factory::getAuthConnection(icms_core_DataFilter::addSlashes($uname));
$user = $icmsAuth->authenticate(trim($_GET['u']), trim($_GET['p']));

if($user AND
   $method = user2FAMethod($user) AND
   userRemembersDevice($user) == false) {
    $codebox = "<br><br>"._US_2FA_CODE."<input type='text' id='dialog-tfacode' value=''><br><input type='checkbox' id='dialog-tfaremember'> <label for='dialog-tfaremember'>"._US_DONT_ASK_AGAIN."</label>";
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
    print "<center>$message</center>";
}
