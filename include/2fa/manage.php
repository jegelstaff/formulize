<?php

/* Copyright the Formulize Project - Julian Egelstaff 2021
 *
 * Manage 2FA codes and infrastructure processes
 */

include_once "../../mainfile.php";
include_once XOOPS_ROOT_PATH."/modules/formulize/include/functions.php";
global $xoopsConfig;
if (file_exists(XOOPS_ROOT_PATH . "/language/".$xoopsConfig['language']."/user.php") ) {
    include_once XOOPS_ROOT_PATH . "/language/".$xoopsConfig['language']."/user.php";
} else {
    XOOPS_ROOT_PATH . "/language/english/user.php";
}

require_once "loader.php";
Loader::register('../../libraries/TwoFactorAuth/','RobThree\\Auth');
use \RobThree\Auth\TwoFactorAuth;

define('TFA_OFF', 0);
define('TFA_EMAIL', 2);
define('TFA_SMS', 1);
define('TFA_APP', 3);

function tfa_formatPhone($phone) {
	// TODO: handle international numbers, extensions, etc. For now just handle basic US 10-digit format and optional leading 1, and format as ###-###-#### or +1-###-###-#### for readability in the UI, but store only digits in the DB for matching and sending
	// possibly take cue from user's timezone?
	$d = preg_replace('/[^0-9]/', '', $phone);
	if(strlen($d) == 10) {
		return substr($d, 0, 3) . '-' . substr($d, 3, 3) . '-' . substr($d, 6);
	} elseif(strlen($d) == 11 && $d[0] == '1') {
		return '+1-' . substr($d, 1, 3) . '-' . substr($d, 4, 3) . '-' . substr($d, 7);
	}
	return $d;
}

/**
 * Build the instruction + code-entry HTML shown inside a 2FA dialog panel.
 * Handles all scenarios: turn_off, change_pass, twophase_confirm, turn_on.
 * The caller is responsible for appending any hidden token inputs after this string.
 *
 * @param string      $scenario       'turn_off'|'change_pass'|'twophase_confirm'|'turn_on'
 * @param string      $method         'app'|'texts'|'email'
 * @param string|null $contactDisplay Formatted, already-HTML-escaped contact address (null for app)
 * @param string      $codebox        HTML for the code input, e.g. "<br><br>Code: <input ...>"
 * @param bool        $error          If true, prepend a bold red invalid-code banner
 */
function tfa_buildDialogMessage($scenario, $method, $contactDisplay, $codebox, $error = false) {
	switch($scenario) {
		case 'turn_off':
			$msg = ($method == 'app') ? _US_TO_TURN_OFF_APP    : sprintf(_US_TO_TURN_OFF,     $contactDisplay);
			break;
		case 'change_pass':
			$msg = ($method == 'app') ? _US_TO_CHANGE_PASS_APP : sprintf(_US_TO_CHANGE_PASS,  $contactDisplay);
			break;
		case 'twophase_confirm':
			$msg = ($method == 'app') ? _US_TO_CONFIRM_CHANGE_APP : sprintf(_US_TO_CONFIRM_CHANGE, $contactDisplay);
			break;
		case 'turn_on':
			$msg = ($method == 'texts') ? sprintf(_US_TURN_ON_PHONE, $contactDisplay) : sprintf(_US_TURN_ON_EMAIL, $contactDisplay);
			break;
		default:
			$msg = '';
	}
	$msg .= $codebox;
	if($error) {
		$msg = "<span style='color:red;font-weight:bold;'>" . _US_2FA_INVALID_CODE . "</span><br><br>" . $msg;
	}
	return $msg;
}

function tfaDialogButtonStyles($buttonIds) {
	$ids = (array)$buttonIds;
	$selectors     = '#' . implode(', #', $ids);
	$textSelectors = '#' . implode(' .ui-button-text, #', $ids) . ' .ui-button-text';
	return "<style>
$selectors {
	background: var(--button-color, #0d7bbf) !important;
	color: white !important;
	font-weight: 600 !important;
	font-size: 0.9rem !important;
	line-height: 1.3 !important;
	padding: 0 !important;
	border: 0 !important;
	border-radius: 10px !important;
	min-width: 200px !important;
	box-shadow: 0px 4px 10px 0px rgba(16, 156, 241, 0.24) !important;
	height: auto !important;
	width: auto !important;
}
$textSelectors {
	padding: 12px 30px !important;
	line-height: 1.3 !important;
}
</style>";
}

function validateCode($code, $uid=false) {
    // check if the user has a code on file
    // if not and we ignore when DB is empty, return true
    // Otherwise, need the right code
    // clear code for this user, unless the user is using app method, then we must keep it for next time
    global $xoopsUser, $xoopsDB, $icmsConfig;
    if(!$uid AND !$xoopsUser) {
        exit('No known user to check 2FA code for!');
    }
    $uid = $uid ? $uid : $xoopsUser->getVar('uid');
    //$sql = 'SELECT method, AES_DECRYPT(code, UNHEX(SHA2("'.XOOPS_DB_PASS.XOOPS_DB_PREFIX.'",512))) as code FROM '.$xoopsDB->prefix('tfa_codes').' WHERE uid = '.intval($uid);
    $sql = 'SELECT method, code FROM '.$xoopsDB->prefix('tfa_codes').' WHERE uid = '.intval($uid);
    $res = $xoopsDB->query($sql);
    while($data = $xoopsDB->fetchArray($res)) {
        if($data['method'] == TFA_APP) {
			$tfa = new TwoFactorAuth(trans($icmsConfig['sitename']));
			if($tfa->verifyCode($data['code'], $code)) {
				return true;
			}
        } else {
            if($data['code'] == trim($code)) {
                $sql = 'DELETE FROM '.$xoopsDB->prefix('tfa_codes').' WHERE uid = '.intval($uid);
                $xoopsDB->queryF($sql);
                return true;
            }
        }
    }
    return false;
}

function generateCode($method, $uid) {
    // store code for this user to be checked when validating
    // replace an existing code unless the existing code has TFA_APP method, and current method is TFA_APP. In that case, we leave TFA_APP alone, since we need it to validate codes
    global $xoopsDB, $icmsConfig;
    if(!$uid) {
        exit('No user to generate 2FA code for!');
    }
    $sql = 'DELETE FROM '.$xoopsDB->prefix('tfa_codes').' WHERE uid = '.intval($uid).' AND method != '.TFA_APP;
    $xoopsDB->queryF($sql);
    if($method == TFA_APP) {
        $sql = 'SELECT * FROM '.$xoopsDB->prefix('tfa_codes').' WHERE uid = '.intval($uid).' AND method = '.TFA_APP;
        $res = $xoopsDB->query($sql);
        if($xoopsDB->getRowsNum($res)==0) { // only generate if there isn't one already, and in this case pass back the necessary stuff for making the QR code to initialize the app
			$tfa = new TwoFactorAuth(trans($icmsConfig['sitename']));
            $secret = $tfa->createSecret(160);
			//$sql = 'INSERT INTO '.$xoopsDB->prefix('tfa_codes').' (uid, code, method) VALUES ('.intval($uid).', AES_ENCRYPT("'.$secret.'", UNHEX(SHA2("'.XOOPS_DB_PASS.XOOPS_DB_PREFIX.'",512))), '.intval($method).')';
            $sql = 'INSERT INTO '.$xoopsDB->prefix('tfa_codes').' (uid, code, method) VALUES ('.intval($uid).', "'.$secret.'", '.intval($method).')';
			$xoopsDB->queryF($sql);
			return $secret;
        }
		return '';
    } else {
        $code = random_int(111111,999999);
        //$sql = 'INSERT INTO '.$xoopsDB->prefix('tfa_codes').' (uid, code, method) VALUES ('.intval($uid).', AES_ENCRYPT("'.$code.'", UNHEX(SHA2("'.XOOPS_DB_PASS.XOOPS_DB_PREFIX.'",512))), '.intval($method).')';
        $sql = 'INSERT INTO '.$xoopsDB->prefix('tfa_codes').' (uid, code, method) VALUES ('.intval($uid).', "'.$code.'", '.intval($method).')';
        $xoopsDB->queryF($sql);
        return $code;
    }
}

// returns any errors so they can be displayed
function sendCode($method=null, $uid=false, $phone_override=null, $email_override=null) {
    global $xoopsUser, $icmsConfig;
    if(!$uid AND !$xoopsUser) {
        exit('No known user to send 2FA code to!');
    }
    $uid = $uid ? $uid : $xoopsUser->getVar('uid');

    $profile_handler = xoops_getmodulehandler('profile', 'profile');
	$profile = $profile_handler->get($uid);
    if(!$method) {
        $method = intval($profile->getVar('2famethod'));
    }

    $code = generateCode($method, $uid);

    switch($method) {
        case TFA_EMAIL:
            $member_handler = xoops_gethandler('member');
						$userObject = $member_handler->getUser($uid);
            $email = $userObject->getVar('email');
            $xoopsMailer = new icms_messaging_Handler();
            $xoopsMailer->useMail();
            if($email_override) {
							if(filter_var($email_override, FILTER_VALIDATE_EMAIL)) {
                $xoopsMailer->setToEmails($email_override);
							} else {
								throw new Exception('Invalid email address provided for 2FA code: ' . htmlspecialchars($email_override));
							}
            } elseif(!$email AND $xoopsUser AND isset($_GET['email']) AND filter_var($_GET['email'], FILTER_VALIDATE_EMAIL)) {
                $xoopsMailer->setToEmails($_GET['email']);
            } else {
                $xoopsMailer->setToUsers($userObject);
            }
						$xoopsMailer->setTemplate('2fa.tpl');
						$xoopsMailer->assign('SITENAME', $icmsConfig['sitename']);
						$xoopsMailer->assign('ADMINMAIL', $icmsConfig['adminmail']);
						$xoopsMailer->assign('SITEURL', ICMS_URL . '/');
						$xoopsMailer->assign('IP', $_SERVER['REMOTE_ADDR']);
						$xoopsMailer->assign('CODE', $code);
						$xoopsMailer->setFromEmail($icmsConfig['adminmail']);
						$xoopsMailer->setFromName($icmsConfig['sitename']);
						$xoopsMailer->setSubject(sprintf(_US_EMAIL_SUBJECT, $code));
						if (!$xoopsMailer->send()) {
								return $xoopsMailer->getErrors();
						}
						return false; // no errors
            break;
        case TFA_SMS:
            $phone = $phone_override ? $phone_override : $profile->getVar('2faphone');
						// Use SMS handler directly
						require_once ICMS_ROOT_PATH . '/libraries/icms/messaging/SmsHandler.php';
						$smsHandler = new icms_messaging_SmsHandler();
						$body = sprintf(_US_SMS_TEXT, $code, trans($icmsConfig['sitename']), $_SERVER['REMOTE_ADDR'], $icmsConfig['adminmail']);
						return $smsHandler->send($phone, $body);
            break;
        case TFA_APP:
						$instructions = '';
						if($code) { // code only set if a new secret was created, ie: we're initializing with user
							$member_handler = xoops_gethandler('member');
							$userObject = $member_handler->getUser($uid);
							$tfa = new TwoFactorAuth(trans($icmsConfig['sitename']));
							$qr = '<img src="'.$tfa->getQRCodeImageAsDataUri($userObject->getVar('login_name'), $code).'">';
							$secret = chunk_split($code, 4, ' ');
							$instructions = _US_SCAN_THIS_CODE.' <br>'.$qr.'<br><br>'._US_ENTER_THIS_MANUALLY.'<br>'.$secret.'<br><br>'._US_ONCE_DONE_ENTER_CODE;
						}
            return $instructions; // when passed back to confirmation dialog, we use it to generate the QR code, etc
    }
}

// returns 2FA method for the current user
// will default to email if the user must use 2FA but they don't have a method set
function user2FAMethod($user=null) {

    // check if 2FA is on
    $config_handler = icms::handler('icms_config');
	$criteria = new Criteria('conf_name', 'auth_2fa');
	if($auth_2fa = $config_handler->getConfigs($criteria)) {
        $auth_2fa = $auth_2fa[0];
        $auth_2fa = $auth_2fa->getConfValueForOutput();
    }
    if($auth_2fa == false) {
        return false;
    }

    // if 2FA is on, return the user's method if any, or email if they have no method but are in a group that must use 2FA
	if(!$user) {
		global $xoopsUser;
		$user = $xoopsUser;
	}
	$profile_handler = xoops_getmodulehandler('profile', 'profile');
	$profile = $profile_handler->get($user->getVar('uid'));
	if($profile->getVar('2famethod')) {
		return $profile->getVar('2famethod');
	}
	$criteria = new Criteria('conf_name', 'auth_2fa_groups');
	$auth_2fa_groups = $config_handler->getConfigs($criteria);
	$auth_2fa_groups = $auth_2fa_groups[0];
	$auth_2fa_groups = $auth_2fa_groups->getConfValueForOutput();
	if(array_intersect($user->getGroups(), $auth_2fa_groups)) {
		return TFA_EMAIL;
	}
	return false;
}

// takes the id of the containing div of the login form
function tfaLoginJS($id) {
	global $xoopsConfig;
	if ( file_exists(XOOPS_ROOT_PATH."/modules/formulize/images/working-".$xoopsConfig['language'].".gif") ) {
		$workingMessageGif = "<img src=\"" . XOOPS_URL . "/modules/formulize/images/working-" . $xoopsConfig['language'] . ".gif\">";
	} else {
		$workingMessageGif = "<img src=\"" . XOOPS_URL . "/modules/formulize/images/working-english.gif\">";
	}
    if(file_exists(XOOPS_ROOT_PATH.'/modules/system/language/'.$xoopsConfig['language'].'/blocks.php')) {
        require_once XOOPS_ROOT_PATH.'/modules/system/language/'.$xoopsConfig['language'].'/blocks.php';
    } elseif(file_exists(XOOPS_ROOT_PATH.'/modules/system/language/english/blocks.php')) {
        require_once XOOPS_ROOT_PATH.'/modules/system/language/english/blocks.php';
    }
	static $counter = 0;
	$counter++;
	$js = "
	<div id='tfadialog-$id'><center>".$workingMessageGif."</center></div>
    <div id='tfalostpassdialog-$id'><center>".$workingMessageGif."</center></div>"
	. tfaDialogButtonStyles(array("tfa-login-ok-$counter", "tfa-login-cancel-$counter", "tfa-lostpass-ok-$counter", "tfa-lostpass-cancel-$counter")) .
	"
	<script type='text/javascript'>
	var tfadialog$counter;
	jQuery('document').ready(function() {
		tfadialog$counter = jQuery('#tfadialog-$id').dialog({
			autoOpen: false,
			modal: true,
			title: '"._US_2FA."',
			width: 'auto',
			position: { my: 'center center', at: 'center center', of: window },
			buttons: [
                { text: 'OK', icon: 'ui-icon-check', click: function() {
						close2FADialog(jQuery(this), '$id');
					}
				},
				{ text: 'Cancel', icon: 'ui-icon-close', click: function() {
						jQuery( this ).dialog( 'close' );
						jQuery( this ).html('<center>".$workingMessageGif."</center>');
					}
				}
			],
			open: function() {
				jQuery(this).css('overflow-y', 'auto !important');
				jQuery(this).closest('.ui-dialog').css('opacity', 0);
				var btns = jQuery(this).closest('.ui-dialog').find('.ui-dialog-buttonpane button');
				btns.eq(0).attr('id', 'tfa-login-ok-$counter');
				btns.eq(1).attr('id', 'tfa-login-cancel-$counter');
			}
		});

		jQuery('#tfadialog-$id').keypress(function(e) {
			if (e.keyCode == jQuery.ui.keyCode.ENTER) {
				close2FADialog(tfadialog$counter, '$id');
			}
		});

		jQuery('#".$id." form').on('submit', function(event) {
			var tfacode = jQuery('#tfacode').val();
			if(!tfacode) {
				event.preventDefault();
				jQuery.ajax({
					async: false,
					type: 'GET',
					url: '".XOOPS_URL."/include/2fa/challenge.php?u='+encodeURIComponent(jQuery('#$id input[name=\"uname\"]').val())+'&p='+encodeURIComponent(jQuery('#$id input[name=\"pass\"]').val()),
					success: function(data) {
						if(data) {
							tfadialog$counter.html(data);
							tfadialog$counter.dialog('open');
							tfadialog$counter.closest('.ui-dialog').fadeTo(300, 1, function() { jQuery('#dialog-tfacode').focus(); });
						} else {
							jQuery('input[name=\"tfacode\"]').each(function() {
								jQuery(this).val('050969');
							});
							jQuery('#".$id." form').submit();
						}
					}
				});
			}
			return true;
		});

        tfalostpassdialog$counter = jQuery('#tfalostpassdialog-$id').dialog({
			autoOpen: false,
			modal: true,
			title: '"._MB_SYSTEM_LPASS."',
			width: '40%',
			position: { my: 'center center', at: 'center center', of: window },
			buttons: [
				{ text: 'OK', icon: 'ui-icon-check', click: function() {
						close2FALostPassDialog(jQuery(this), '$id');
					}
				},
				{ text: 'Cancel', icon: 'ui-icon-close', click: function() {
						jQuery( this ).dialog( 'close' );
						jQuery( this ).html('<center>".$workingMessageGif."</center>');
					}
				}
			],
			open: function() {
				jQuery(this).css('overflow-y', 'auto !important');
				jQuery(this).closest('.ui-dialog').css('opacity', 0);
				var btns = jQuery(this).closest('.ui-dialog').find('.ui-dialog-buttonpane button');
				btns.eq(0).attr('id', 'tfa-lostpass-ok-$counter');
				btns.eq(1).attr('id', 'tfa-lostpass-cancel-$counter');
			}
		});

        jQuery('#tfalostpassdialog-$id').keypress(function(e) {
			if (e.keyCode == jQuery.ui.keyCode.ENTER) {
				close2FALostPassDialog(tfalostpassdialog$counter, '$id');
			}
		});

        jQuery('#lostpass').click(function() {
            event.preventDefault();
            tfalostpassdialog$counter.html('<center>"._US_USERNAME_OR_EMAIL."<input type=\"text\" id=\"dialog-tfalostaccount\" value=\"\"></center>');
            tfalostpassdialog$counter.dialog('open');
            tfalostpassdialog$counter.closest('.ui-dialog').fadeTo(300, 1, function() { jQuery('#dialog-tfalostaccount').focus(); });
        });

	});
	</script>
	";

	if($counter == 1) {
		$js .= "<script type=\"text/javascript\">

		function close2FADialog(dialog, id) {
			var code = jQuery('#dialog-tfacode').val();
			var token = jQuery('.tfa-login-token', dialog).val();
			var remember = jQuery('#dialog-tfaremember').is(':checked');
			dialog.dialog( 'close' );
			dialog.html('<center>".$workingMessageGif."</center>');
			if(code) {
				jQuery('input[name=\"tfacode\"]').val(code);
				if(remember) { jQuery('input[name=\"tfaremember\"]').val(1); }
				if(token) { jQuery('input[name=\"tfa_login_token\"]').val(token); }
				jQuery('#'+id+' form').submit();
			}
		}

        function close2FALostPassDialog(dialog, id) {
            var account = jQuery('#dialog-tfalostaccount').val();
            if(account) {
				window.location = '".XOOPS_URL."/lostpass.php?a='+encodeURIComponent(account)+'&token='+encodeURIComponent('".$GLOBALS['xoopsSecurity']->createToken()."');
            } else {
                dialog.dialog( 'close' );
				dialog.html('<center>".$workingMessageGif."</center>');
            }
		}

		</script>
		";
	}

	return $js;

}

function getDeviceFingerprint() {
	return md5($_SERVER['HTTP_USER_AGENT'].$_SERVER['REMOTE_ADDR']);
}

function rememberDevice($user=null) {
	if(!$user) {
		global $xoopsUser;
		$user = $xoopsUser;
	}
	if($user) {
		$fingerprint = getDeviceFingerprint();
		$profile_handler = xoops_getmodulehandler('profile', 'profile');
		$profile = $profile_handler->get($user->getVar('uid'));
		$devices = unserialize($profile->getVar('2fadevices','n')); // 'n' necessary to do no htmlspecialchars magic or anything on the value, just return raw
		$devices = is_array($devices) ? $devices : array();
		$devices[$fingerprint] = true;
		$profile->setVar('2fadevices', serialize($devices));
		$profile_handler->insert($profile);
	}
}

function userRemembersDevice($user=null) {
	if(!$user) {
		global $xoopsUser;
		$user = $xoopsUser;
	}
	if($user) {
		$fingerprint = getDeviceFingerprint();
		$profile_handler = xoops_getmodulehandler('profile', 'profile');
		$profile = $profile_handler->get($user->getVar('uid'));
		$devices = unserialize($profile->getVar('2fadevices','n')); // 'n' necessary to do no htmlspecialchars magic or anything on the value, just return raw
		if(isset($devices[$fingerprint])) {
			return true;
		}
	}
	return false;
}
