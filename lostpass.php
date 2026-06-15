<?php

/* Copyright the Formulize Project - Julian Egelstaff 2021
 *
 * Give the user a UI for changing their password, if their code is valid
 */


include "mainfile.php";

$pageStartTime = microtime(true);

if(!$GLOBALS['xoopsSecurity']->check(true, $_GET['token'])) {
    include_once XOOPS_ROOT_PATH.'/modules/formulize/include/functions.php';
    redirect_header(XOOPS_URL, 5, trans("[en]Please try again. Do not click 'Back' in your browser.[/en][fr]Veuillez réessayer. Ne cliquez pas sur 'Retour' dans votre navigateur.[/fr]"));
    exit();
}

global $xoopsDB, $icmsConfigUser;
include_once XOOPS_ROOT_PATH.'/modules/formulize/include/functions.php';
include_once XOOPS_ROOT_PATH.'/include/2fa/manage.php';
include_once XOOPS_ROOT_PATH.'/modules/formulize/include/writeToFormulizeLog.php';
$member_handler = xoops_gethandler('member');

// Look up the account WITHOUT revealing whether it exists. A zero- or multiple-match renders the
// same page as a successful single match, so account existence is not disclosed.
$sql = "SELECT uid, login_name FROM ".$xoopsDB->prefix('users')." WHERE login_name = '".formulize_db_escape($_GET['a'])."' OR email = '".formulize_db_escape($_GET['a'])."'";
$res = $xoopsDB->query($sql);
$uid = 0;
$userObject = null;
if($xoopsDB->getRowsNum($res) == 1) {
	$row = $xoopsDB->fetchArray($res);
	$uid = $row['uid'];
	$userObject = $member_handler->getUser($uid);
}

include "header.php";

$errorMessage = '';

// If a code + matching passwords were submitted for a real account, validate and update the password.
if(isset($_POST['code']) AND
   isset($_POST['pass1']) AND
   isset($_POST['pass2']) AND
   $_POST['pass1'] == $_POST['pass2'] AND
   strlen($_POST['pass1']) >= $icmsConfigUser['minpass']) {
    if($uid AND validateCode($_POST['code'], $uid)) {
        $icmspass = new icms_core_Password();
        $salt = $icmspass->createSalt();
        $userObject->setVar('salt', $salt);
        $pass1 = $icmspass->encryptPass($_POST['pass1'], $salt);
        $userObject->setVar('pass', $pass1);
        if(!$member_handler->insertUser($userObject, true)) {
            exit("Error: could not save new password to the database.");
        }
        // Invalidate any existing authenticated sessions for this account, so a pre-existing
        // (possibly attacker) session can't outlive the password change. The session table has no
        // uid column - the uid lives inside the serialized sess_data - so we match it there. The DB
        // does the filtering in a single query (no PHP-side iteration), so this stays cheap even
        // with many sessions. The two LIKE patterns cover the 'php' and 'php_serialize' session
        // serialization handlers; the trailing ';' prevents matching e.g. uid 50 when targeting 5.
        $uidInt = intval($uid);
        $sessTable = $xoopsDB->prefix('session');
        $xoopsDB->queryF("DELETE FROM $sessTable WHERE sess_data LIKE '%xoopsUserId|i:$uidInt;%' OR sess_data LIKE '%s:11:\"xoopsUserId\";i:$uidInt;%'");
        $online_handler = icms::handler('icms_core_Online');
        $online_handler->destroy($uidInt);
        redirect_header(ICMS_URL, 5, _US_LOGIN_WITH_NEW_PW);
        exit();
    } else {
        // Wrong code, OR an unknown/spoofed account ($uid=0). Give method-tailored retry guidance plus
        // attempts-remaining. The originally-sent code is still valid (validateCode only clears it on
        // success), so an inline retry handles the common typo case. To preserve the indistinguishable
        // design, the no-account case mirrors a typical real first failure (email method, one attempt
        // used) so a single probe can't tell real from fake. NB: repeated probing can still enumerate,
        // and can lock a real account's reset - that DoS is inherent to having a rate limit at all.
        if($uid) {
            $invalidIsApp = (user2FAMethod($userObject) == TFA_APP);
            $attemptsSoFar = 1; // default if no live code row is found (shouldn't happen on a real wrong guess)
            $attRes = $xoopsDB->query('SELECT method, attempts FROM '.$xoopsDB->prefix('tfa_codes').' WHERE uid = '.intval($uid));
            while($attRow = $xoopsDB->fetchArray($attRes)) {
                if(($attRow['method'] == TFA_APP) == $invalidIsApp) { $attemptsSoFar = intval($attRow['attempts']); break; }
            }
        } else {
            $invalidIsApp = false; // unknown account: present the common email-method case
            $attemptsSoFar = 1;    // mirror a real first failure so a single probe is indistinguishable
        }
        $triesRemaining = max(0, TFA_MAX_ATTEMPTS - $attemptsSoFar);
        $startOverLink = "<a href='".XOOPS_URL."'>"._US_RESET_PW_START_OVER."</a>";
        if($triesRemaining <= 0) {
            $guidance = $invalidIsApp ? _US_2FA_LOCKED_OUT_APP : sprintf(_US_2FA_LOCKED_OUT_SENT, $startOverLink);
        } else {
            $guidance = ($invalidIsApp ? _US_RESET_PW_RETRY_APP : sprintf(_US_RESET_PW_RETRY_OR_RESTART, $startOverLink))
                        ." ".sprintf(_US_2FA_ATTEMPTS_REMAINING, $triesRemaining);
        }
        $errorMessage = "<p><b>"._US_INVALID_CODE."</b></p><p>".$guidance."</p><br>";
    }
}

// For a real account, send a verification code over whatever channel we can actually reach the user
// on. Preference: their configured 2FA method (if deliverable), then email, then SMS (if a phone is
// on file), then an authenticator app. Send failures are logged, not shown, so we don't reveal
// whether/how a code could be delivered. Only on the FIRST load (a GET with no code submitted yet):
// a failed code submission must NOT silently resend a code; the user gets a "start over" link instead.
if($uid AND !isset($_POST['code'])) {
    $resetMethod = user2FAMethod($userObject); // configured method (TFA_APP/SMS/EMAIL), or false
    $hasEmail = trim($userObject->getVar('email')) != '';
    $lp_profile_handler = xoops_getmodulehandler('profile', 'profile');
    $lp_profile = $lp_profile_handler->get($uid);
    // We can only text the user if the SMS provider is actually configured AND they have a number.
    $smsConfigured = (defined('SMS_ACCOUNT_SID') AND SMS_ACCOUNT_SID AND defined('SMS_AUTH_TOKEN') AND SMS_AUTH_TOKEN AND defined('SMS_FROM_NUMBER') AND SMS_FROM_NUMBER);
    $canText = ($smsConfigured AND $lp_profile AND preg_replace('/[^0-9]/', '', $lp_profile->getVar('2faphone')) != '');

    if($resetMethod == TFA_APP) {
        // app-based: the user reads the code from their authenticator; nothing to send
    } elseif($resetMethod == TFA_SMS AND $canText) {
        // text the configured phone
    } elseif($resetMethod == TFA_EMAIL AND $hasEmail) {
        // email the configured address
    } elseif($hasEmail) {
        $resetMethod = TFA_EMAIL; // fall back to email
    } elseif($canText) {
        $resetMethod = TFA_SMS;   // fall back to texting
    } else {
        $resetMethod = false;     // no email, no usable phone, no app: nothing we can do
    }

    if($resetMethod === false) {
        // There is genuinely no way to verify this person online (no contact info on file at all).
        // This is essentially a user-configuration error; show a generic apology, not the specifics.
        print "
<div style='padding: 2em;'>
<h1>"._US_RESET_PW_NO_CONTACT."</h1>
<p>"._US_RESET_PW_CONTACT_ADMIN."</p>
</div>";
        include 'footer.php';
        exit();
    }

    $sendError = false;
    if($resetMethod == TFA_SMS) {
        $sendError = sendCode(TFA_SMS, $uid);
    } elseif($resetMethod == TFA_EMAIL) {
        $sendError = sendCode(TFA_EMAIL, $uid);
    }
    if(!empty($sendError)) {
        writeToFormulizeLog(array(
            'formulize_event' => 'lostpass-send-code-error',
            'user_id' => intval($uid),
            'error' => is_array($sendError) ? implode('; ', $sendError) : $sendError
        ));
    }
}

// Normalize response time so a real account (which sends a code, taking time) can't be told apart
// from a non-existent one by how long the page takes. Pad to a per-request randomized floor.
$elapsed = microtime(true) - $pageStartTime;
$targetSeconds = 2.0 + (random_int(0, 700) / 1000); // ~2.0-2.7s, varies each request
if($elapsed < $targetSeconds) {
    usleep((int)(($targetSeconds - $elapsed) * 1000000));
}

print "
<div style='padding: 2em;'>
<h1>"._US_LOSTPASS_TITLE."</h1>$errorMessage
<form id='pwchange' action='".XOOPS_URL."/lostpass.php?a=".urlencode(strip_tags(htmlspecialchars($_GET['a'], ENT_QUOTES)))."&token=".urlencode($GLOBALS['xoopsSecurity']->createToken())."' method='post'>
<p><b>"._US_LOSTPASS_ENTER_CODE."</b><br><input type='text' autocomplete='one-time-code' inputmode='numeric' name='code' value='' /></p><br>
<p>"._US_NEW_PASSWORD."<br><input type='password' autocomplete='new-password' name='pass1' value='' /></p><br>
<p>"._US_CONFIRM_PASSWORD."<br><input type='password' autocomplete='new-password' name='pass2' value='' /></p><br>
<input type='submit' name='submit' value='"._US_RESET_PW_BUTTON."'>
</form>
</div>
<script type='text/javascript'>
jQuery(document).ready(function() {
    jQuery('#pwchange').submit(function() {
        if(jQuery('input[name=\"pass1\"]').val() != jQuery('input[name=\"pass2\"]').val()) {
            alert(\""._US_PASSWORDS_DONT_MATCH."\");
            return false;
        }
        if(jQuery('input[name=\"pass1\"]').val().length < ".$icmsConfigUser['minpass'].") {
            alert(\"".sprintf(_US_PASSWORD_TOO_SHORT, $icmsConfigUser['minpass'])."\");
            return false;
        }
    });
});
</script>
";
include "footer.php";
