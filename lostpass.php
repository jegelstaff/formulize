<?php

/* Copyright the Formulize Project - Julian Egelstaff 2021
 *
 * Give the user a UI for changing their password, if their code is valid
 */


include "mainfile.php";

// check if the account id is at least valid...
include_once XOOPS_ROOT_PATH.'/modules/formulize/include/functions.php';
global $xoopsDB, $icmsConfigUser;
$sql = 'SELECT uid, login_name FROM '.$xoopsDB->prefix('users').' WHERE login_name = "'.formulize_db_escape($_GET['a']).'" OR email = "'.formulize_db_escape($_GET['a']).'" LIMIT 0,1';
$res = $xoopsDB->query($sql);
if($xoopsDB->getRowsNum($res)==1) { // if account identifier returns us one account
    $row = $xoopsDB->fetchArray($res);
    $uid = $row['uid'];
    // if no password submitted yet, we're setting up form
    if(!isset($_POST['pass1'])) {
        // if request is valid
        if($GLOBALS['xoopsSecurity']->check(true, $_GET['token'])) {
            include_once XOOPS_ROOT_PATH.'/include/2fa/manage.php';
            if(validateCode($_GET['c'], $uid)) { // if the code they entered was valid, then show them pw reset form
                include "header.php";
                print "
                <div style='padding: 2em;'>
                <h1>"._US_RESET_PW_FOR.strip_tags(htmlspecialchars($row['login_name'], ENT_QUOTES))."</h1>
                <form id='pwchange' action='".XOOPS_URL."/lostpass.php' method='post'>
                <p>"._US_NEW_PASSWORD."<br><input type='password' name='pass1' value='' /></p><br>
                <p>"._US_CONFIRM_PASSWORD."<br><input type='password' name='pass2' value='' /></p><br>
                <input type='hidden' name='token' value='".$GLOBALS['xoopsSecurity']->createToken()."' />
                <input type='hidden' name='a' value='".strip_tags(htmlspecialchars($_GET['a'], ENT_QUOTES))."' />
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
            }
        }
    } elseif($_POST['pass1'] AND $_POST['pass2'] AND $_POST['pass1'] == $_POST['pass2'] AND strlen($_POST['pass1']) >= $icmsConfigUser['minpass'] AND $GLOBALS['xoopsSecurity']->check(true, $_POST['token'])) { // user has submitted the form, valid password, valid token, so process it
        $member_handler = xoops_gethandler('member');
        $icmspass = new icms_core_Password();
        $salt = $icmspass->createSalt();
        $userObject = $member_handler->getUser($uid);
        $userObject->setVar('salt', $salt);
        global $icmsConfigUser;
        $enc_type = $icmsConfigUser['enc_type'];
        $pass1 = $icmspass->encryptPass($_POST['pass1'], $salt, $enc_type);
        $userObject->setVar('pass', $pass1);
        if(!$member_handler->insertUser($userObject, true)) {
            exit("Error: could not save new password to the database.");
        }
        redirect_header(ICMS_URL, 5, _US_LOGIN_WITH_NEW_PW);
    } 
}