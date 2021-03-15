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
include "manage.php"; // defines constants

global $xoopsUser;

$codebox = "<br><br>Code: <input type='text' id='dialog-tfacode' value=''>";

switch($_GET['method']) {
    case TFA_OFF:
        $profile_handler = xoops_getmodulehandler('profile', 'profile');
		$profile = $profile_handler->get($xoopsUser->getVar('uid'));
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
				$message = sendCode(); // will return errors
                $method = 'email';
        }
        $message = $message ? $message : "To turn off Two-Factor Authentication, you need to enter the code from your ".$method.".$codebox";
        break;
    case TFA_SMS:
        if($_GET['phone']) {
            $code = sendCode(TFA_SMS, false, $_GET['phone']);
            $message = "To turn on Two-Factor Authentication, you need to enter the code we texted to your phone.$codebox";
        } else {
            $message = "You have not entered a phone number. Please click Cancel and enter a phone number.";
        }
        break;
    case TFA_EMAIL:
        $message = sendCode(TFA_EMAIL); // will return errors
        $message = $message ? $message : "To turn on Two-Factor Authentication, you need to enter the code we emailed you.$codebox";
        break;
    case TFA_APP:
		global $xoopsDB;
		$sql = 'DELETE FROM '.$xoopsDB->prefix('tfa_codes').' WHERE uid = '.intval($xoopsUser->getVar('uid')).' AND method = '.TFA_APP;
		$xoopsDB->queryF($sql);
        $message = sendCode(TFA_APP); // will return instructions
        $message = $message.$codebox;
        break;
}

print "<center>$message</center>";