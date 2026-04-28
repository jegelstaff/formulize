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
if(!$xoopsUser) {
    exit;
}

$codebox = "<br><br>Code: <input type='text' id='dialog-tfacode' value=''>";

$profile_handler = xoops_getmodulehandler('profile', 'profile');
$profile = $profile_handler->get($xoopsUser->getVar('uid'));
$pwChangeMethod = $profile->getVar('2famethod') ? $profile->getVar('2famethod') : TFA_EMAIL;

$sendError = null;
$scenario = null;
$newPhoneForTurnOn = null;
$newEmailForTurnOn = null;
$method = null;

// if user is changing password
if(intval($_GET['selectedMethod']) == intval($profile->getVar('2famethod')) AND ($_GET['phone'] == preg_replace("/[^0-9]/", '', $profile->getVar('2faphone')) OR $profile->getVar('2famethod') != TFA_SMS)) {
    switch($pwChangeMethod) {
        case TFA_SMS:
            $sendError = sendCode(); // will return errors
            $method = 'texts';
            break;
        case TFA_APP:
            $method = 'app';
            break;
        default:
            $sendError = sendCode(TFA_EMAIL); // will return errors
            $method = 'email';
    }
    $scenario = !empty($_GET['twophase']) ? 'twophase_confirm' : 'change_pass';
// if user is turning on 2FA or changing phone number
} else {
    switch($_GET['method']) {
        case TFA_OFF:
            switch($profile->getVar('2famethod')) {
                case TFA_SMS:
                    $sendError = sendCode(); // will return errors
                    $method = 'texts';
                    break;
                case TFA_APP:
                    $method = 'app';
                    break;
                default:
                    $sendError = sendCode(TFA_EMAIL); // will return errors
                    $method = 'email';
            }
            $scenario = 'turn_off';
            break;
        case TFA_SMS:
            if($_GET['phone']) {
                $newPhoneForTurnOn = $_GET['phone'];
                sendCode(TFA_SMS, false, $newPhoneForTurnOn);
                $method = 'texts';
                $scenario = 'turn_on';
            } else {
                $message = _US_NO_PHONE_NUMBER;
            }
            break;
        case TFA_EMAIL:
            $newEmailForTurnOn = isset($_GET['email']) ? trim($_GET['email']) : '';
            $sendError = sendCode(TFA_EMAIL, false, null, $newEmailForTurnOn ?: null); // will return errors
            $method = 'email';
            $scenario = 'turn_on';
            break;
        case TFA_APP:
            global $xoopsDB;
            $sql = 'DELETE FROM '.$xoopsDB->prefix('tfa_codes').' WHERE uid = '.intval($xoopsUser->getVar('uid')).' AND method = '.TFA_APP;
            $xoopsDB->queryF($sql);
            $message = sendCode(TFA_APP); // will return instructions
            $message = $message.$codebox;
            $method = 'app';
            $scenario = 'turn_on';
            break;
    }
}

// Build the final message if not already set by a special case (no-phone, app-turn-on)
if(!isset($message)) {
    if($sendError) {
        $message = $sendError;
    } else {
        // Determine actual contact for display
        if($method == 'app') {
            $contactDisplay = null;
        } elseif($method == 'texts') {
            $rawPhone = $newPhoneForTurnOn ?: $profile->getVar('2faphone');
            $contactDisplay = htmlspecialchars(tfa_formatPhone($rawPhone));
        } else {
            if($newEmailForTurnOn) {
                $contactDisplay = htmlspecialchars($newEmailForTurnOn);
            } else {
                $member_handler_msg = xoops_gethandler('member');
                $userObjMsg = $member_handler_msg->getUser($xoopsUser->getVar('uid'));
                $contactDisplay = htmlspecialchars($userObjMsg->getVar('email'));
            }
        }

        $message = tfa_buildDialogMessage($scenario, $method, $contactDisplay, $codebox);
    }
}

// Mint a token bound to the specific contact the code was sent to.
// The final POST (or validate_step1.php for two-phase flows) must validate with the
// same contact, so altering the submitted email/phone will cause token validation to fail.
if($method == 'app') {
    $tokenContact = 'authenticator-app';
} elseif($method == 'texts') {
    // Sent to new phone (changing-phone path) or stored phone (password-change path)
    $tokenContact = preg_replace('/[^0-9]/', '',
        (!empty($_GET['phone']) && intval($_GET['method']) == TFA_SMS)
            ? $_GET['phone']
            : $profile->getVar('2faphone')
    );
} else {
    // email — sent to new email (turn-on path) or stored email (all other paths)
    if($newEmailForTurnOn) {
        $tokenContact = $newEmailForTurnOn;
    } else {
        $member_handler = xoops_gethandler('member');
        $userObj = $member_handler->getUser($xoopsUser->getVar('uid'));
        $tokenContact = $userObj->getVar('email');
    }
}
$confirmToken = icms::$security->createToken(300, $tokenContact);
$message .= "<input type='hidden' class='tfa-confirm-token' value='"
          . htmlspecialchars($confirmToken, ENT_QUOTES) . "'>";

if(!empty($_GET['error'])) {
    $message = "<span style='color:red;font-weight:bold;'>" . _US_2FA_INVALID_CODE . "</span><br><br>" . $message;
}

print "<center>$message</center>";
