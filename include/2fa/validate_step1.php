<?php

/* Copyright the Formulize Project - Julian Egelstaff 2026
 *
 * AJAX endpoint for two-phase 2FA verification of contact changes.
 * Called after the user enters their OLD contact code (phase 1).
 * Validates the old code, mints a server-side step-1 token,
 * sends a code to the NEW contact, and returns phase 2 dialog HTML.
 * On failure, returns an error message so the dialog stays open for retry.
 */

include_once "../../mainfile.php";

icms::$logger->disableLogger();
while(ob_get_level()) {
    ob_end_clean();
}

include "manage.php"; // defines constants, validateCode(), sendCode(), generateCode()

global $xoopsUser;
if(!$xoopsUser) {
    print '<center><span style="color:red;">Not logged in.</span></center>';
    exit;
}

$uid           = intval($xoopsUser->getVar('uid'));
$submittedCode = isset($_GET['code']) ? trim($_GET['code']) : '';
$confirmToken  = isset($_GET['confirm_token']) ? trim($_GET['confirm_token']) : '';
$newMethod     = intval(isset($_GET['new_method']) ? $_GET['new_method'] : TFA_EMAIL);
$newPhone      = preg_replace('/[^0-9]/', '', isset($_GET['new_phone']) ? $_GET['new_phone'] : '');
$newEmail      = isset($_GET['new_email']) ? trim($_GET['new_email']) : '';

$codebox = "<br><br>Code: <input type='text' id='dialog-tfacode' value=''>";

// Validate the confirm token issued by confirm.php for this session,
// bound to the old contact where the phase-1 code was sent.
$profile_handler_s1 = xoops_getmodulehandler('profile', 'profile');
$profile_s1 = $profile_handler_s1->get($uid);
$oldMethod_s1 = intval($profile_s1 ? $profile_s1->getVar('2famethod') : TFA_EMAIL);
if($oldMethod_s1 == TFA_APP) {
    $oldContact_s1 = 'authenticator-app';
} elseif($oldMethod_s1 == TFA_SMS) {
    $oldContact_s1 = preg_replace('/[^0-9]/', '', $profile_s1->getVar('2faphone'));
} else {
    $oldContact_s1 = $xoopsUser->getVar('email');
}
if(!icms::$security->validateToken($confirmToken, true, $oldContact_s1)) {
    print '<center><span style="color:red;">' . _US_2FA_INVALID_CODE . '</span></center>';
    exit;
}

if(!validateCode($submittedCode, $uid)) {
    if($oldMethod_s1 == TFA_APP) {
        $errMethod  = 'app';
        $errContact = null;
    } elseif($oldMethod_s1 == TFA_SMS) {
        $errMethod  = 'texts';
        $errContact = htmlspecialchars(tfa_formatPhone($oldContact_s1));
    } else {
        $errMethod  = 'email';
        $errContact = htmlspecialchars($oldContact_s1);
    }
    $errMsg = tfa_buildDialogMessage('twophase_confirm', $errMethod, $errContact, $codebox, true);
    // Mint a fresh confirm token — the one submitted with this request was consumed above
    $retryToken = icms::$security->createToken(300, $oldContact_s1);
    $errMsg .= "<input type='hidden' class='tfa-confirm-token' value='" . htmlspecialchars($retryToken, ENT_QUOTES) . "'>";
    print "<center>$errMsg</center>";
    exit;
}

// Phase 1 validated — mint a short-lived server-side token bound to the new contact.
// Using the new contact as the token name means the final POST can only validate
// if it submits the same contact value — a different submitted email/phone won't match.
if($newMethod == TFA_APP) {
    $newContactId = 'authenticator-app';
} elseif($newMethod == TFA_SMS && $newPhone) {
    $newContactId = $newPhone;
} else {
    $newContactId = $newEmail;
}
$step1Token = icms::$security->createToken(300, $newContactId);

// Send a new code to the NEW contact
$codeError      = '';
$contactType    = 'email';
$appSetupMessage = '';
switch($newMethod) {
    case TFA_APP:
        // Delete any existing app secret so generateCode() creates a fresh one
        global $xoopsDB;
        $sql = 'DELETE FROM '.$xoopsDB->prefix('tfa_codes').' WHERE uid = '.$uid.' AND method = '.TFA_APP;
        $xoopsDB->queryF($sql);
        $appSetupMessage = sendCode(TFA_APP, $uid);
        $contactType = 'app';
        break;
    case TFA_SMS:
        if($newPhone) {
            $codeError   = sendCode(TFA_SMS, $uid, $newPhone);
            $contactType = 'phone';
        } else {
            // No phone — fall back to email
            $codeError   = sendCode(TFA_EMAIL, $uid, null, $newEmail ?: null);
            $contactType = 'email';
        }
        break;
    case TFA_EMAIL:
    case TFA_OFF:
    default:
        $codeError   = sendCode(TFA_EMAIL, $uid, null, $newEmail ?: null);
        $contactType = 'email';
        break;
}

if($codeError) {
    print '<center><span style="color:red;">' . htmlspecialchars($codeError) . '</span></center>';
    exit;
}

// Return phase 2 dialog HTML.
// The hidden .tfa-step1token input carries the minted token back to the browser.
// JS reads it to detect that phase 1 succeeded, stores it, and transitions to phase 2.
if($contactType == 'app') {
    print "<center>"
        . "<input type='hidden' class='tfa-step1token' value='" . htmlspecialchars($step1Token, ENT_QUOTES) . "'>"
        . $appSetupMessage
        . $codebox
        . "</center>";
} else {
    $displayNewContact = ($contactType == 'phone')
        ? htmlspecialchars(tfa_formatPhone($newPhone))
        : htmlspecialchars($newEmail);
    $phase2Message = sprintf(_US_2FA_NOW_ENTER_CODE, $displayNewContact);
    print "<center>"
        . "<input type='hidden' class='tfa-step1token' value='" . htmlspecialchars($step1Token, ENT_QUOTES) . "'>"
        . $phase2Message
        . $codebox
        . "</center>";
}
