<?php

/* Copyright the Formulize Project - Julian Egelstaff 2021
 *
 * Give the user a UI for changing their password, if their code is valid
 */


include "mainfile.php";

if(!$GLOBALS['xoopsSecurity']->check(true, $_GET['token'])) {
    include_once XOOPS_ROOT_PATH.'/modules/formulize/include/functions.php';
    redirect_header(XOOPS_URL, 5, trans("[en]Please try again. Do not click 'Back' in your browser.[/en][fr]Veuillez réessayer. Ne cliquez pas sur 'Retour' dans votre navigateur.[/fr]"));
    exit();
}

global $xoopsDB, $icmsConfigUser;
include_once XOOPS_ROOT_PATH.'/modules/formulize/include/functions.php';
include_once XOOPS_ROOT_PATH.'/include/2fa/manage.php';
$member_handler = xoops_gethandler('member');

// check if the account id is at least valid...
$sql = 'SELECT uid, login_name FROM '.$xoopsDB->prefix('users').' WHERE login_name = "'.formulize_db_escape($_GET['a']).'" OR email = "'.formulize_db_escape($_GET['a']).'"';
$res = $xoopsDB->query($sql);
$rowsFound = $xoopsDB->getRowsNum($res);
if($rowsFound != 1 ) { // if account identifier does not identify one account
	include "header.php";
	if($rowsFound == 0) {
    print "<h2>".trans("[en]There is no account with that username or email address.[/en][fr]Il n'y a pas de compte avec ce nom d'utilisateur/trice ou cette adresse e-mail.[/fr]")."</h2>";
	} elseif($rowsFound > 1) {
		print "<h2>".trans("[en]There is more than one account with that username or email address. Please contact a webmaster for help.[/en][fr]Il y a plus d'un compte avec ce nom d'utilisateur/trice ou cette adresse e-mail. Contactez un webmaster pour obtenir de l'aide, s'il vous plaît.[/fr]")."</h2>";
	}
	include "footer.php";
	exit();
}

$row = $xoopsDB->fetchArray($res);
$uid = $row['uid'];
$login_name = $row['login_name'];
$userObject = $member_handler->getUser($uid);

include "header.php";

// check if the submitted code is valid for the identified user
// if so, then go ahead and update that user's password
if(isset($_POST['code']) AND
   isset($_POST['pass1']) AND
   isset($_POST['pass2']) AND
   $_POST['pass1'] == $_POST['pass2'] AND
   strlen($_POST['pass1']) >= $icmsConfigUser['minpass']) {
    if(validateCode($_POST['code'], $uid)) {
        $icmspass = new icms_core_Password();
        $salt = $icmspass->createSalt();
        $userObject->setVar('salt', $salt);
        $enc_type = $icmsConfigUser['enc_type'];
        $pass1 = $icmspass->encryptPass($_POST['pass1'], $salt, $enc_type);
        $userObject->setVar('pass', $pass1);
        if(!$member_handler->insertUser($userObject, true)) {
            exit("Error: could not save new password to the database.");
        }
        redirect_header(ICMS_URL, 5, _US_LOGIN_WITH_NEW_PW);
        exit();
    } else {
        $errorMessage =  "<p><b>"._US_INVALID_CODE."</b></p><br>";
    }
}

$method = user2FAMethod($userObject);
$method = $method ? $method : TFA_EMAIL;
switch($method) {
    case TFA_SMS:
        $errorMessage .= sendCode(TFA_SMS, $uid); // will return errors
        $method = 'texts';
        break;
    case TFA_APP:
        $method = 'app';
        break;
    default:
				// verify that user has email address!
				if($userObject->getVar('email')=='') {
					print "
<div style='padding: 2em;'>
<h1>"._US_RESET_PW_NO_EMAIL."</h1>
<p>"._US_RESET_PW_CONTACT_ADMIN."</p>
</div>";
					include 'footer.php';
					exit();
				} else {
        	$errorMessage .= sendCode(TFA_EMAIL, $uid); // will return errors
        	$method = 'email';
				}
}
print "
<div style='padding: 2em;'>
<h1>"._US_RESET_PW_FOR.strip_tags(htmlspecialchars($row['login_name'], ENT_QUOTES))."</h1>$errorMessage
<form id='pwchange' action='".XOOPS_URL."/lostpass.php?a=".urlencode(strip_tags(htmlspecialchars($_GET['a'], ENT_QUOTES)))."&token=".urlencode($GLOBALS['xoopsSecurity']->createToken())."' method='post'>
<p><b>"._US_TO_CHANGE_PASS.$method.":</b><br><input type='text' autocomplete='one-time-code' inputmode='numeric' name='code' value='' /></p><br>
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
