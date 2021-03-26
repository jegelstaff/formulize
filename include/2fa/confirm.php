<?php

/* Copyright the Formulize Project - Julian Egelstaff 2021
 *
 * Dialog handler for 2FA confirmation processes
 * handles the confirmation of 2FA metadata by user as part of their user profile
 *
 * If method is 0 (none) then we need to go to the user's saved 2fa details and get a code that way to confirm they can turn off 2FA
 * If the method is 1 (text) then we need a phone number and if not, tell user, if so, get a code that way
 * If the method is 2 (email) then we try to get a code via email
 * If the method is 3 (app) then we provide app setup instructions and ask for code from app
 */

include_once "../../mainfile.php";

icms::$logger->disableLogger();
while(ob_get_level()) {
    ob_end_clean();
}

include "manage.php"; // defines constants

global $xoopsUser;

$codebox = "<br><br>Code: <input type='text' id='dialog-tfacode' value=''>";

$profile_handler = xoops_getmodulehandler('profile', 'profile');
$profile = $profile_handler->get($xoopsUser->getVar('uid'));
$pwChangeMethod = $profile->getVar('2famethod') ? $profile->getVar('2famethod') : TFA_EMAIL;

// if user is changing password
if($_GET['selectedMethod'] == $profile->getVar('2famethod') AND ($_GET['phone'] == preg_replace("/[^0-9]/", '', $profile->getVar('2faphone')) OR $profile->getVar('2famethod') != TFA_SMS)) {
    switch($pwChangeMethod) {
        case TFA_SMS:
            $message = sendCode(); // will return errors
            $method = 'texts';
            break;
        case TFA_APP:
            $message = '';
            $method = 'app';
            break;
        default:
            $message = sendCode(TFA_EMAIL); // will return errors
            $method = 'email';
    }
    $message = $message ? $message : _US_TO_CHANGE_PASS.$method.".$codebox";
// if user is turning on 2FA or changing phone number
} else {
    switch($_GET['method']) {
        case TFA_OFF:
            switch($profile->getVar('2famethod')) {
                case TFA_SMS:
                    $message = sendCode(); // will return errors
                    $method = 'texts';
                    break;
                case TFA_APP:
                    $message = '';
                    $method = 'app';
                    break;
                default:
                    $message = sendCode(TFA_EMAIL); // will return errors
                    $method = 'email';
            }
            $message = $message ? $message : _US_TO_TURN_OFF.$method.".$codebox";
            break;
        case TFA_SMS:
            if($_GET['phone']) {
                $code = sendCode(TFA_SMS, false, $_GET['phone']);
                $message = _US_TURN_ON_PHONE.$codebox;
            } else {
                $message = _US_NO_PHONE_NUMBER;
            }
            break;
        case TFA_EMAIL:
            $message = sendCode(TFA_EMAIL); // will return errors
            $message = $message ? $message : _US_TURN_ON_EMAIL.$codebox;
            break;
        case TFA_APP:
            global $xoopsDB;
            $sql = 'DELETE FROM '.$xoopsDB->prefix('tfa_codes').' WHERE uid = '.intval($xoopsUser->getVar('uid')).' AND method = '.TFA_APP;
            $xoopsDB->queryF($sql);
            $message = sendCode(TFA_APP); // will return instructions
            $message = $message.$codebox;
            break;
    }
}

print "<center>$message</center>";