<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2011 Freeform Solutions                  ##
###############################################################################
##  This program is free software; you can redistribute it and/or modify     ##
##  it under the terms of the GNU General Public License as published by     ##
##  the Free Software Foundation; either version 2 of the License, or        ##
##  (at your option) any later version.                                      ##
##                                                                           ##
##  You may not change or alter any portion of this comment or credits       ##
##  of supporting developers from this source code or any supporting         ##
##  source code which is considered copyrighted (c) material of the          ##
##  original comment or credit authors.                                      ##
##                                                                           ##
##  This program is distributed in the hope that it will be useful,          ##
##  but WITHOUT ANY WARRANTY; without even the implied warranty of           ##
##  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            ##
##  GNU General Public License for more details.                             ##
##                                                                           ##
##  You should have received a copy of the GNU General Public License        ##
##  along with this program; if not, write to the Free Software              ##
##  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA ##
###############################################################################
##  Author of this file: Freeform Solutions                                  ##
##  Project: Formulize                                                       ##
###############################################################################

require_once XOOPS_ROOT_PATH . "/modules/formulize/class/elements.php"; // you need to make sure the base element class has been read in first!
require_once XOOPS_ROOT_PATH . "/modules/formulize/class/userAccountElement.php";

class formulizeUserAccount2FAElement extends formulizeUserAccountElement {

    function __construct() {
		parent::__construct();
		$this->name = "User Account Two-Factor Authentication Settings";
		$this->userProperty = "profile:2famethod"; // 2FA method is stored in user profile, not base user object :/
    }

}

#[AllowDynamicProperties]
class formulizeUserAccount2FAElementHandler extends formulizeUserAccountElementHandler {

	function create() {
		return new formulizeUserAccount2FAElement();
	}

	protected function getOptions() {
		include_once XOOPS_ROOT_PATH . '/include/2fa/manage.php';
		return array(
			TFA_OFF   => _NONE,
			TFA_EMAIL => _formulize_USERACCOUNT_2FAOPTION_EMAIL,
			TFA_SMS   => _formulize_USERACCOUNT_2FAOPTION_SMS,
			TFA_APP   => _formulize_USERACCOUNT_2FAOPTION_AUTHAPP,
		);
	}

	function prepareDataForDataset($value, $handle, $entry_id) {
		$options = $this->getOptions();
		return isset($options[$value]) ? $options[$value] : $value;
	}

	/**
	 * Render the 2FA settings field as a radio group plus a confirmation dialog.
	 *
	 * Returns false when 2FA is not enabled site-wide. When the element is editable,
	 * a jQuery UI dialog is appended for the one- or two-phase confirmation flow.
	 *
	 * @param mixed  $ele_value  Current 2FA method value (TFA_OFF, TFA_EMAIL, TFA_SMS, TFA_APP)
	 * @param string $caption    Field caption
	 * @param string $markupName HTML input name
	 * @param bool   $isDisabled Whether the field is read-only
	 * @param object $element    The element object
	 * @param mixed  $entry_id   Entry ID
	 * @param mixed  $screen     Screen object (unused)
	 * @param mixed  $owner      Owner context (unused)
	 * @return XoopsFormElement|false
	 */
	function render($ele_value, $caption, $markupName, $isDisabled, $element, $entry_id, $screen, $owner) {
		include_once XOOPS_ROOT_PATH . '/include/2fa/manage.php';
		// check if 2FA is turned on for the site, and return false if it is not
		$is2faEnabled = false;
		$config_handler = icms::handler('icms_config');
		$criteria = new Criteria('conf_name', 'auth_2fa');
		if($auth_2fa = $config_handler->getConfigs($criteria)) {
			$auth_2fa = $auth_2fa[0];
			$is2faEnabled = $auth_2fa->getConfValueForOutput();
		}
		if(!$is2faEnabled) {
			return false;
		}

		$options = $this->getOptions(); // all 4 options; TFA_OFF is first

		global $xoopsUser;
		$userMustUse2FA = false;
		// get the user associated with this entry
		$fid = $element->getVar('fid');
		$form_handler = xoops_getmodulehandler('forms', 'formulize');
		$formObj = $form_handler->get($fid);
		$entryUserId = $formObj ? $formObj->getSystemUserIdFromEntry($entry_id) : 0;
		if($entryUserId) {
			$userMustUse2FA = true;
			$member_handler = xoops_gethandler('member');
			if($entryUserObject = $member_handler->getUser($entryUserId)) {
				$criteria_groups = new Criteria('conf_name', 'auth_2fa_groups');
				if($auth_2fa_groups_cfg = $config_handler->getConfigs($criteria_groups)) {
					$auth_2fa_groups = $auth_2fa_groups_cfg[0]->getConfValueForOutput();
					if(!array_intersect($entryUserObject->getGroups(), (array)$auth_2fa_groups)) {
						$userMustUse2FA = false;
					}
				}
			}
		}
		if($userMustUse2FA) {
			unset($options[TFA_OFF]);
		}

		// When an admin edits another user's entry, the Authenticator App option must be
		// disabled: only the account holder can scan the QR code on their own device.
		// If TFA_APP is already the user's current method, a hidden input placed before
		// the radio group preserves it on save when the admin leaves it unchanged.
		// (Browser POST order means an enabled radio selected after it wins, so the admin
		// can still switch the user away from TFA_APP to another method.)
		$adminEditingOtherUser = $entryUserId && $xoopsUser && intval($entryUserId) !== intval($xoopsUser->getVar('uid'));
		$disabledOptions2fa = $adminEditingOtherUser ? array(TFA_APP) : array();
		$tfaAppHidden = ($adminEditingOtherUser && $ele_value == TFA_APP)
			? "<input type='hidden' name='" . htmlspecialchars($markupName, ENT_QUOTES) . "' value='" . intval(TFA_APP) . "'>"
			: '';

		// Use the parent method to render the radio buttons (handles disabled state automatically)
		$radioFormElement = $this->renderUserAccountRadioButtons($options, $ele_value, $caption, $markupName, $isDisabled, $disabledOptions2fa);
		$radioHtml = $tfaAppHidden . $radioFormElement->render();

		// Add the confirmation dialog UI when the element is editable
		$dialogHtml = '';
		if(!$isDisabled) {
			global $xoopsConfig, $xoopsUser;
			if(file_exists(XOOPS_ROOT_PATH.'/modules/formulize/images/working-'.$xoopsConfig['language'].'.gif')) {
				$workingGif = '<img src="'.XOOPS_URL.'/modules/formulize/images/working-'.$xoopsConfig['language'].'.gif">';
			} else {
				$workingGif = '<img src="'.XOOPS_URL.'/modules/formulize/images/working-english.gif">';
			}

			// Safe JS identifier derived from the markup name (e.g. de_5_3_12)
			$safeId = preg_replace('/[^a-zA-Z0-9_]/', '_', $markupName);
		// Compute auto-reopen vars when a 2FA validation failed in this request
		$autoReopenScript = '';
		if(formulizeUserAccountElementHandler::$tfaValidationError) {
			$fid_eo = $element->getVar('id_form');
			$eh_eo = xoops_getmodulehandler('elements', 'formulize');
			$ph_eo = $eh_eo->get('formulize_user_account_phone_'.$fid_eo);
			$em_eo = $eh_eo->get('formulize_user_account_email_'.$fid_eo);
			$pfh_eo = xoops_getmodulehandler('profile', 'profile');
			$pf_eo = $pfh_eo->get($xoopsUser->getVar('uid'));
			$mh_eo = xoops_gethandler('member');
			$uo_eo = $mh_eo->getUser($xoopsUser->getVar('uid'));
			$cm_eo = intval($pf_eo->getVar('2famethod'));
			$cp_eo = preg_replace('/[^0-9]/', '', $pf_eo->getVar('2faphone') ?? '');
			$ce_eo = $uo_eo ? $uo_eo->getVar('email') : '';
			$psel_eo = $ph_eo ? 'de_'.$fid_eo.'_'.$entry_id.'_'.$ph_eo->getVar('ele_id') : '';
			$esel_eo = $em_eo ? 'de_'.$fid_eo.'_'.$entry_id.'_'.$em_eo->getVar('ele_id') : '';
			$autoReopenScript = "<script type='text/javascript'>
"
				."var tfa_auto_reopen=true,tfa_auto_safeId=".json_encode($safeId)
				.",tfa_auto_markupName=".json_encode($markupName)
				.",tfa_auto_phoneSelector=".json_encode($psel_eo)
				.",tfa_auto_emailSelector=".json_encode($esel_eo)
				.",tfa_auto_currentMethod=".intval($cm_eo)
				.",tfa_auto_currentPhone=".json_encode($cp_eo)
				.",tfa_auto_currentEmail=".json_encode($ce_eo)
				.",tfa_auto_TFA_EMAIL=".TFA_EMAIL
				.",tfa_auto_TFA_SMS=".TFA_SMS
				.",tfa_auto_TFA_OFF=".TFA_OFF
				.",tfa_auto_singlephaseBaseUrl=".json_encode(XOOPS_URL.TFA_CONFIRM_URL_PATH)
				.";
</script>";
		}

			// Dialog title — use the existing _US_2FA constant if loaded, else a plain fallback
			$dialogTitle = defined('_US_2FA') ? addslashes(_US_2FA) : 'Two-Factor Authentication';

			$dialogHtml = "
		<div id='tfa-dialog-{$safeId}'></div>
		<div id='tfa-loading-{$safeId}' style='display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:1100;'><center>{$workingGif}</center></div>
		<input type='hidden' name='formulize_tfa_code' id='tfa-code-{$safeId}' value=''>
		<input type='hidden' name='formulize_tfa_step1token' id='tfa-step1token-{$safeId}' value=''>
	<input type='hidden' name='tfa_confirm_token' id='tfa-confirm-token-{$safeId}' value=''>"
		. tfaDialogButtonStyles(array("tfa-btn-ok-{$safeId}", "tfa-btn-cancel-{$safeId}")) .
		"
		<script type='text/javascript'>
	function tfa_recentreDialog_{$safeId}() {
		var dlgWrap = jQuery('#tfa-dialog-{$safeId}').closest('.ui-dialog');
		setTimeout(function() {
			dlgWrap.css({
				position: 'fixed',
				top: '50%',
				left: '50%',
				marginLeft: '0',
				marginTop: '0',
				transform: 'translate(-50%, -50%)'
			});
		}, 0);
	}

	function tfa_showDialog_{$safeId}() {
		jQuery('#tfa-loading-{$safeId}').hide();
		jQuery('#tfa-dialog-{$safeId}').closest('.ui-dialog').fadeTo(300, 1, function() {
			jQuery('#tfa-dialog-{$safeId} #dialog-tfacode').focus();
		});
	}

		jQuery(document).ready(function() {
			window['tfa_form_{$safeId}'] = jQuery('input[name=\"{$markupName}\"]').first().closest('form');

			function tfa_submitAfterCode_{$safeId}() {
				if(typeof validateAndSubmit === 'function') {
					validateAndSubmit();
				} else {
					window['tfa_form_{$safeId}'][0].submit();
				}
			}

			function tfa_doStep1Ajax_{$safeId}(\$dlg) {
				var code = jQuery('#dialog-tfacode', \$dlg).val();
				if(!code) return;
				jQuery('#tfa-dialog-{$safeId}').closest('.ui-dialog').fadeTo(150, 0);
				jQuery('#tfa-loading-{$safeId}').show();
				jQuery.post(
					'".XOOPS_URL.TFA_VALIDATE_STEP1_URL_PATH."',
					{
						code: code,
						new_method: \$dlg.data('tfa-new-method'),
						new_phone:  \$dlg.data('tfa-new-phone'),
						new_email:  \$dlg.data('tfa-new-email'),
						confirm_token: \$dlg.data('tfa-phase1-token') || ''
					},
					function(response) {
						\$dlg.html(response);
						var step1Token = jQuery('.tfa-step1token', \$dlg).val();
						if(step1Token) {
							\$dlg.data('tfa-phase', 2);
							window['tfa_form_{$safeId}'].find('input[name=\"formulize_tfa_step1token\"]').val(step1Token);
						}
						// On error retry, validate_step1.php returns a fresh confirm token â store it
						var ct = jQuery('.tfa-confirm-token', \$dlg).val();
						if(ct) { \$dlg.data('tfa-phase1-token', ct); }
						tfa_recentreDialog_{$safeId}();
						tfa_showDialog_{$safeId}();
					}
				);
			}

			var tfa_dialog_{$safeId} = jQuery('#tfa-dialog-{$safeId}').dialog({
				autoOpen: false,
				modal: true,
				title: '{$dialogTitle}',
				width: 'auto',
				position: { my: 'center center', at: 'center center', of: window },
				buttons: [
					{ text: 'OK', icon: 'ui-icon-check', click: function() {
						var \$dlg = jQuery(this);
						if(\$dlg.data('tfa-phase') == 1) {
							tfa_doStep1Ajax_{$safeId}(\$dlg);
						} else {
							var code = jQuery('#dialog-tfacode', \$dlg).val();
							\$dlg.dialog('close');
							\$dlg.data('tfa-phase', 0);
							if(code) {
								window['tfa_form_{$safeId}'].find('input[name=\"formulize_tfa_code\"]').val(code);
								tfa_submitAfterCode_{$safeId}();
							}
						}
					}},
					{ text: 'Cancel', icon: 'ui-icon-close', click: function() {
						jQuery(this).dialog('close');
						jQuery(this).data('tfa-phase', 0);
						window['tfa_form_{$safeId}'].find('input[name=\"formulize_tfa_step1token\"]').val('');
						window['tfa_form_{$safeId}'].find('input[name=\"tfa_confirm_token\"]').val('');
					}}
				],
				open: function() {
					jQuery(this).css('overflow-y', 'auto !important');
					jQuery(this).closest('.ui-dialog').css('opacity', 0);
					tfa_recentreDialog_{$safeId}();
					var tfa_btns = jQuery(this).closest('.ui-dialog').find('.ui-dialog-buttonpane button');
					tfa_btns.eq(0).attr('id', 'tfa-btn-ok-{$safeId}');
					tfa_btns.eq(1).attr('id', 'tfa-btn-cancel-{$safeId}');
				}
			});

			jQuery('#tfa-dialog-{$safeId}').keypress(function(e) {
				if(e.keyCode == jQuery.ui.keyCode.ENTER) {
					var \$dlg = jQuery(this);
					if(\$dlg.data('tfa-phase') == 1) {
						tfa_doStep1Ajax_{$safeId}(\$dlg);
					} else {
						var code = jQuery('#dialog-tfacode', \$dlg).val();
						tfa_dialog_{$safeId}.dialog('close');
						tfa_dialog_{$safeId}.data('tfa-phase', 0);
						if(code) {
							window['tfa_form_{$safeId}'].find('input[name=\"formulize_tfa_code\"]').val(code);
							tfa_submitAfterCode_{$safeId}();
						}
					}
				}
			});

		// Auto-reopen with error message if previous submission had invalid 2FA code/token
		if(typeof tfa_auto_reopen !== 'undefined' && tfa_auto_reopen) {
			setTimeout(function() {
				var tfa_dlg_ro = jQuery('#tfa-dialog-' + tfa_auto_safeId);
				var tfa_newMethod_ro = parseInt(jQuery('input[name=\"' + tfa_auto_markupName + '\"]:checked').val()) || 0;
				var tfa_newPhone_ro  = tfa_auto_phoneSelector ? jQuery('[name=\"' + tfa_auto_phoneSelector + '\"]').val().replace(/[^0-9]/g,'') : '';
				var tfa_newEmail_ro  = tfa_auto_emailSelector ? (jQuery('[name=\"' + tfa_auto_emailSelector + '\"]').length > 0 ? jQuery('[name=\"' + tfa_auto_emailSelector + '\"]').val() : null) : null;
				var tfa_needsTwoPhase_ro = (
					((tfa_auto_currentMethod == tfa_auto_TFA_EMAIL || tfa_auto_currentMethod == tfa_auto_TFA_OFF) && (tfa_newMethod_ro == tfa_auto_TFA_EMAIL || tfa_newMethod_ro == tfa_auto_TFA_OFF) && tfa_newEmail_ro !== null && tfa_newEmail_ro != tfa_auto_currentEmail && tfa_auto_currentEmail != '') ||
					(tfa_auto_currentMethod == tfa_auto_TFA_SMS   && tfa_newMethod_ro == tfa_auto_TFA_SMS   && tfa_newPhone_ro != tfa_auto_currentPhone && tfa_auto_currentPhone != '') ||
					(tfa_auto_currentMethod != tfa_auto_TFA_OFF   && tfa_newMethod_ro != tfa_auto_TFA_OFF   && tfa_newMethod_ro != tfa_auto_currentMethod)
				);
				tfa_dlg_ro.dialog('open');
				jQuery('#tfa-loading-' + tfa_auto_safeId).show();
				if(tfa_needsTwoPhase_ro) {
					tfa_dlg_ro.data('tfa-phase', 1);
					tfa_dlg_ro.data('tfa-new-method', tfa_newMethod_ro);
					tfa_dlg_ro.data('tfa-new-phone', tfa_newPhone_ro);
					tfa_dlg_ro.data('tfa-new-email', tfa_newEmail_ro || '');
					tfa_dlg_ro.load(tfa_auto_singlephaseBaseUrl, { method: tfa_auto_currentMethod, phone: tfa_auto_currentPhone, selectedMethod: tfa_auto_currentMethod, email: tfa_auto_currentEmail, twophase: 1, error: 1 }, function() { var ct = jQuery('.tfa-confirm-token', tfa_dlg_ro).val(); tfa_dlg_ro.data('tfa-phase1-token', ct || ''); window['tfa_recentreDialog_'+tfa_auto_safeId](); window['tfa_showDialog_'+tfa_auto_safeId](); });
				} else {
					tfa_dlg_ro.data('tfa-phase', 0);
					tfa_dlg_ro.load(tfa_auto_singlephaseBaseUrl, { method: tfa_newMethod_ro, phone: tfa_newPhone_ro, email: (tfa_newEmail_ro || ''), selectedMethod: tfa_newMethod_ro, error: 1 }, function() { var ct = jQuery('.tfa-confirm-token', tfa_dlg_ro).val(); if(ct) { window['tfa_form_' + tfa_auto_safeId].find('input[name=\"tfa_confirm_token\"]').val(ct); } window['tfa_recentreDialog_'+tfa_auto_safeId](); window['tfa_showDialog_'+tfa_auto_safeId](); });
				}
			}, 400);
		}
		});
		</script>" . $autoReopenScript;
		}

		return new XoopsFormLabel($caption, $radioHtml . $dialogHtml, $markupName);
	}


	/**
	 * Generate JS that opens the 2FA confirmation dialog when sensitive settings change.
	 *
	 * Only emitted when the user is editing their own existing entry and 2FA is enabled
	 * site-wide. Opens a one- or two-phase dialog and halts the save until a valid code
	 * is entered.
	 *
	 * @param string    $caption    Field caption (unused)
	 * @param string    $markupName HTML input name for the 2FA method radio group
	 * @param object    $element    The element object
	 * @param int|mixed $entry_id   Entry ID
	 * @return array Array of JavaScript statement strings
	 */
	function generateValidationCode($caption, $markupName, $element, $entry_id) {
		// Only relevant when 2FA is enabled globally
		$config_handler = icms::handler('icms_config');
		$criteria = new Criteria('conf_name', 'auth_2fa');
		$auth_2fa = $config_handler->getConfigs($criteria);
		if(!$auth_2fa || !$auth_2fa[0]->getConfValueForOutput()) {
			return array();
		}

		// Only show the dialog when a user is editing their own existing entry
		global $xoopsUser;
		if(!$xoopsUser || !is_numeric($entry_id)) {
			return array();
		}

		$fid = $element->getVar('id_form');
		$form_handler = xoops_getmodulehandler('forms', 'formulize');
		$formObj = $form_handler->get($fid);
		$entryUserId = $formObj ? $formObj->getSystemUserIdFromEntry($entry_id) : 0;
		if(!$entryUserId || $entryUserId != intval($xoopsUser->getVar('uid'))) {
			return array(); // admin editing another user, or a brand-new entry
		}

		// Load the currently-saved 2FA values for comparison
		include_once XOOPS_ROOT_PATH . '/include/2fa/manage.php';
		$member_handler = xoops_gethandler('member');
		$profile_handler = xoops_getmodulehandler('profile', 'profile');
		$profile = $profile_handler->get($xoopsUser->getVar('uid'));
		$userObj  = $member_handler->getUser($xoopsUser->getVar('uid'));
		$currentMethod = intval($profile->getVar('2famethod'));
		$currentPhone  = preg_replace('/[^0-9]/', '', $profile->getVar('2faphone') ?? '');
		$currentEmail  = $userObj ? $userObj->getVar('email') : '';

		// Locate sibling elements in the same form so we can read their submitted values in JS
		$element_handler = xoops_getmodulehandler('elements', 'formulize');
		$phoneElement = $element_handler->get('formulize_user_account_phone_'.$fid);
		$emailElement = $element_handler->get('formulize_user_account_email_'.$fid);
		$passElement  = $element_handler->get('formulize_user_account_password_'.$fid);

		$phoneMarkupName = $phoneElement ? 'de_'.$fid.'_'.$entry_id.'_'.$phoneElement->getVar('ele_id') : '';
		$emailMarkupName = $emailElement ? 'de_'.$fid.'_'.$entry_id.'_'.$emailElement->getVar('ele_id') : '';
		$passMarkupName  = $passElement  ? 'de_'.$fid.'_'.$entry_id.'_'.$passElement->getVar('ele_id')  : '';

		$safeId = preg_replace('/[^a-zA-Z0-9_]/', '_', $markupName);
		$currentEmailJs = json_encode($currentEmail);

		$currentPhoneJs  = json_encode($currentPhone);
		$currentMethodJs = intval($currentMethod);

		$js = array();
		$js[] = "var tfa_newMethod = parseInt(jQuery('input[name=\"{$markupName}\"]:checked').val()) || 0;";
		$js[] = $phoneMarkupName
			? "var tfa_newPhone = jQuery('[name=\"{$phoneMarkupName}\"]').val().replace(/[^0-9]/g,'');"
			: "var tfa_newPhone = '';";
		$js[] = $emailMarkupName
			? "var tfa_newEmail = jQuery('[name=\"{$emailMarkupName}\"]').length > 0 ? jQuery('[name=\"{$emailMarkupName}\"]').val() : null;"
			: "var tfa_newEmail = null;";
		$js[] = $passMarkupName
			? "var tfa_hasPass = jQuery('[name=\"{$passMarkupName}\"]').val() != '';"
			: "var tfa_hasPass = false;";
		$js[] = "var tfa_code = (myform && myform.elements['formulize_tfa_code']) ? myform.elements['formulize_tfa_code'].value : '';";
		// Two-phase is needed in three cases:
		// 1. Staying on email, email is changing (verify old email, then new email). Email is the
		//    default 2FA contact when no method is set, so TFA_OFF is treated as email-ish here.
		// 2. Method unchanged = SMS, phone is changing (verify old phone, then new phone).
		// 3. Method was active (not off) and is changing to a different active method
		//    (verify old contact via old method, then verify new contact via new method).
		$js[] = "var tfa_needsTwoPhase = (";
		$js[] = "    (({$currentMethodJs} == ".TFA_EMAIL." || {$currentMethodJs} == ".TFA_OFF.") && (tfa_newMethod == ".TFA_EMAIL." || tfa_newMethod == ".TFA_OFF.") && tfa_newEmail !== null && tfa_newEmail != {$currentEmailJs} && {$currentEmailJs} != '') ||";
		$js[] = "    ({$currentMethodJs} == ".TFA_SMS." && tfa_newMethod == ".TFA_SMS." && tfa_newPhone != {$currentPhoneJs} && {$currentPhoneJs} != '') ||";
		$js[] = "    ({$currentMethodJs} != ".TFA_OFF." && tfa_newMethod != ".TFA_OFF." && tfa_newMethod != {$currentMethodJs})";
		$js[] = ");";
		$js[] = "if(!tfa_code && (";
		$js[] = "    tfa_newMethod != {$currentMethod} ||";
		$js[] = "    (tfa_newMethod == ".TFA_SMS." && tfa_newPhone != {$currentPhoneJs}) ||";
		$js[] = "    (tfa_newMethod == ".TFA_EMAIL." && tfa_newEmail !== null && tfa_newEmail != {$currentEmailJs}) ||";
		$js[] = "    (tfa_newMethod == ".TFA_OFF." && tfa_newEmail !== null && tfa_newEmail != {$currentEmailJs}) ||";
		$js[] = "    tfa_hasPass";
		$js[] = ")) {";
		$js[] = "    var tfa_dlg = jQuery('#tfa-dialog-{$safeId}');";
		$js[] = "    tfa_dlg.dialog('open');";
		$js[] = "    jQuery('#tfa-loading-{$safeId}').show();";
		$js[] = "    if(tfa_needsTwoPhase) {";
		$js[] = "        tfa_dlg.data('tfa-phase', 1);";
		$js[] = "        tfa_dlg.data('tfa-new-method', tfa_newMethod);";
		$js[] = "        tfa_dlg.data('tfa-new-phone', tfa_newPhone);";
		$js[] = "        tfa_dlg.data('tfa-new-email', tfa_newEmail || '');";
		$js[] = "        tfa_dlg.load('".XOOPS_URL.TFA_CONFIRM_URL_PATH."', { method: {$currentMethodJs}, phone: {$currentPhoneJs}, selectedMethod: {$currentMethodJs}, email: {$currentEmailJs}, twophase: 1 }, function() { var ct = jQuery('.tfa-confirm-token', tfa_dlg).val(); tfa_dlg.data('tfa-phase1-token', ct || ''); tfa_recentreDialog_{$safeId}(); tfa_showDialog_{$safeId}(); });";
		$js[] = "    } else {";
		$js[] = "        tfa_dlg.data('tfa-phase', 0);";
		$js[] = "        tfa_dlg.load('".XOOPS_URL.TFA_CONFIRM_URL_PATH."', { method: tfa_newMethod, phone: tfa_newPhone, email: (tfa_newEmail || ''), selectedMethod: tfa_newMethod }, function() { var ct = jQuery('.tfa-confirm-token', tfa_dlg).val(); if(ct) { window['tfa_form_{$safeId}'].find('input[name=\"tfa_confirm_token\"]').val(ct); } tfa_recentreDialog_{$safeId}(); tfa_showDialog_{$safeId}(); });";
		$js[] = "    }";
		$js[] = "    return false;";
		$js[] = "}";

		return $js;
	}

	/**
	 * Convert a human-readable 2FA method label to its numeric constant for database comparison.
	 *
	 * @param mixed  $value        Label as typed by the user (e.g. "Email", "SMS")
	 * @param object $element      The element object (unused)
	 * @param bool   $partialMatch True for partial/LIKE matching, false for exact match
	 * @return int|array|mixed Matching constant(s), or the original value if no match found
	 */
	function prepareLiteralTextForDB($value, $element, $partialMatch = false) {
		$options = $this->getOptions();
		$matchingKeys = array();
		foreach ($options as $key => $label) {
			$matches = $partialMatch
				? (stripos($label, $value) !== false)
				: (strcasecmp($label, $value) === 0);
			if ($matches) {
				$matchingKeys[] = $key;
			}
		}
		if (empty($matchingKeys)) {
			return $value;
		}
		return count($matchingKeys) === 1 ? $matchingKeys[0] : $matchingKeys;
	}

	/**
	 * Build a WHERE clause fragment to search by 2FA method value.
	 *
	 * Resolves numeric method constants from $term and produces an EXISTS subquery against
	 * the profile_profile table; TFA_OFF (0) also matches rows where 2famethod IS NULL.
	 *
	 * @param mixed  $term       Numeric method constant(s) (already resolved via prepareLiteralTextForDB)
	 * @param string $operator   SQL operator; 'NOT LIKE'/'!=' produces a NOT EXISTS clause
	 * @param string $quotes     Ignored
	 * @param string $likebits   Ignored
	 * @param int    $fid        Form ID (unused)
	 * @param string $tableAlias Alias for the users table in the outer query
	 * @return string SQL WHERE clause fragment
	 */
	function buildSearchWhereClause($term, $operator, $quotes, $likebits, $fid, $tableAlias = 'main') {
		global $xoopsDB;
		$isNegative = (trim($operator) === 'NOT LIKE' || trim($operator) === '!=');
		$options = $this->getOptions();

		$terms = is_array($term) ? $term : array($term);
		$matchingKeys = array();
		foreach ($terms as $t) {
			if (is_numeric($t)) {
				$k = intval($t);
				if (array_key_exists($k, $options)) {
					$matchingKeys[] = $k;
				}
			}
		}

		if (empty($matchingKeys)) {
			return $isNegative ? '1=1' : '1=0';
		}

		$safeKeys = implode(',', $matchingKeys);

		if ($isNegative) {
			return "NOT EXISTS("
				. "SELECT 1 FROM " . $xoopsDB->prefix('profile_profile') . " AS pp"
				. " WHERE pp.profileid = {$tableAlias}.uid"
				. " AND pp.`2famethod` IN ($safeKeys)"
				. ")";
		}

		$nullClause = in_array(0, $matchingKeys) ? " OR pp.`2famethod` IS NULL" : "";
		return "EXISTS("
			. "SELECT 1 FROM " . $xoopsDB->prefix('profile_profile') . " AS pp"
			. " WHERE pp.profileid = {$tableAlias}.uid"
			. " AND (pp.`2famethod` IN ($safeKeys)$nullClause)"
			. ")";
	}

}
