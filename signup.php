<?php
/**
 * Self-service account signup.
 *
 * A public visitor creates their own account here. The form is the Formulize System Users form —
 * the exact same form (and the same save pipeline in readelements.php) used by Edit Account and the
 * webmaster Users list — so there is one code path and one set of rules for account data everywhere.
 *
 * Flow:
 *   1. (optional) The visitor supplies an invitation token, either in the URL (?token=XXXX) or by
 *      typing it into the "invitation code" box. A valid token is remembered in the session and,
 *      when the account is created, adds membership in the group(s) the token grants.
 *   2. The visitor fills in the account form (first/last name, username, password, and email and/or
 *      phone). Submitting it runs the standard save pipeline, which creates the user — but INACTIVE
 *      (level 0) because this is a self-registration (see formulizeUserAccountElementHandler).
 *   3. Everyone is added to Registered Users; token groups are added on top. A confirmation code is
 *      sent by email (or SMS, if that is how they can be reached), reusing the 2FA code machinery.
 *   4. The visitor enters the code (op=confirm). On success the account is activated and they are
 *      logged in automatically — possession of the code proves control of the contact method, the
 *      same standard used for a password reset.
 *
 * Self-registration is gated throughout by formulize_selfRegistrationActive(), which requires the
 * $GLOBALS['formulize_selfRegistration'] flag set below, no logged-in user, and the site's
 * allow_register policy. That single gate is what lets an anonymous visitor create a new entry on
 * the otherwise locked-down System Users form.
 *
 * @package Formulize
 */

$xoopsOption['pagetype'] = 'user';
include 'mainfile.php';

global $xoopsUser, $icmsConfig, $icmsConfigUser, $xoopsDB;

// Core user-language constants (_US_*) used in the redirects and confirmation messages below.
icms_loadLanguageFile('core', 'user');

// Already logged in? Nothing to sign up for.
if (is_object($xoopsUser)) {
	redirect_header(XOOPS_URL, 3, _US_ALREADY_LOGED_IN);
	exit();
}

// Respect the site policy switch for self-registration (ImpressCMS "Allow new user registration").
if (empty($icmsConfigUser['allow_register'])) {
	redirect_header(XOOPS_URL, 5, _US_NOREGISTER);
	exit();
}

include_once XOOPS_ROOT_PATH . '/modules/formulize/include/common.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/include/functions.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/include/usersAndGroups.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/class/token.php';
include_once XOOPS_ROOT_PATH . '/include/2fa/manage.php'; // sendCode(), validateCode(), TFA_* constants

// Mark the self-registration context. Every relaxed permission check downstream funnels through
// formulize_selfRegistrationActive(), which additionally re-checks "no logged-in user".
$GLOBALS['formulize_selfRegistration'] = true;

$op = isset($_REQUEST['op']) ? $_REQUEST['op'] : '';

// ---------------------------------------------------------------------------
// PHASE 2 — CONFIRM: verify the emailed/texted code, then activate + log in.
// ---------------------------------------------------------------------------
if ($op == 'confirm') {
	formulize_signupRenderConfirm();
	exit();
}

// ---------------------------------------------------------------------------
// PHASE 1 — SIGNUP: accept a token, render the account form, and (on submit)
// create the account, assign groups, send a code, and hand off to confirm.
// ---------------------------------------------------------------------------

// Accept an invitation token from the URL or the invitation-code box, remembering it in session.
$tokenNotice = formulize_signupAcceptToken();

// Is a valid invitation token currently accepted (held in session, still valid)?
$tokenHandler = xoops_getmodulehandler('token', 'formulize');
$hasValidToken = !empty($_SESSION['formulize_signup_token']) && $tokenHandler->get($_SESSION['formulize_signup_token']);

// "Require account tokens for public sign-ups?" (Formulize preference). When on, no account may be
// created without a valid token: we neither process a submission nor render the account form until a
// valid token is accepted. Missing config (older installs) reads as off.
$config_handler = xoops_gethandler('config');
$formulizeConfig = $config_handler->getConfigsByCat(0, getFormulizeModId());
$requireToken = !empty($formulizeConfig['requireTokenForSignup']);
$tokenGateBlocks = ($requireToken && !$hasValidToken);

// Resolve the System Users form + its default form screen (same resolution edituser.php uses).
$fid = ensureUsersTableForm();
$form_handler = xoops_getmodulehandler('forms', 'formulize');
$formObject = $fid ? $form_handler->get($fid) : false;
$sid = $formObject ? intval($formObject->getVar('defaultform')) : 0;
if (!$sid) {
	redirect_header(XOOPS_URL, 5, _US_NOREGISTER);
	exit();
}

// Process any submitted account form BEFORE rendering (save-before-display), exactly like
// edituser.php / users.php. If the account form was submitted, this creates the (inactive) user.
// When a required token is missing we skip this entirely, so an account can never be created by
// forging a POST past the on-screen token gate.
$newUserTableUserIds = array();
if (!$tokenGateBlocks) {
	include_once XOOPS_ROOT_PATH . '/modules/formulize/include/readelements.php';
}

// Did the save just create a new system user? If so, finish signup: assign groups, send a
// confirmation code, and redirect to the confirm step. $newUserTableUserIds is populated by
// readelements.php when the System Users form creates a user.
if (!empty($newUserTableUserIds[$fid])) {
	$newUid = intval($newUserTableUserIds[$fid]);
	formulize_signupAssignGroups($newUid);

	$maskedContact = '';
	$method = formulize_signupChooseConfirmMethod($newUid, $maskedContact);
	if ($method === false) {
		// No email and no reachable phone: we cannot verify this person online. The account exists
		// but stays inactive; a webmaster can help. This should be unreachable because the form
		// requires an email and/or phone, but we handle it rather than send them into a dead end.
		include XOOPS_ROOT_PATH . '/header.php';
		echo "<div class='formulize-signup'><h1>" . _formulize_SIGNUP_TITLE . "</h1><p>" .
			_formulize_SIGNUP_NO_CONTACT . "</p></div>";
		include XOOPS_ROOT_PATH . '/footer.php';
		exit();
	}

	sendCode($method, $newUid); // errors are non-fatal; the confirm step offers a resend
	$_SESSION['formulize_signup_pending_uid'] = $newUid;
	$_SESSION['formulize_signup_confirm_method'] = $method;
	$_SESSION['formulize_signup_confirm_contact'] = $maskedContact;
	redirect_header(XOOPS_URL . '/signup.php?op=confirm', 0, _formulize_SIGNUP_SENDING);
	exit();
}

// Otherwise: render the signup page (token box + account form).
include_once XOOPS_ROOT_PATH . '/header.php';

global $xoTheme;
if ($xoTheme) {
	$cssVersion = formulize_get_file_version('/modules/formulize/templates/css/formulize.css');
	$jsVersion = formulize_get_file_version('/modules/formulize/libraries/formulize.js');
	$xoTheme->addStylesheet("/modules/formulize/templates/css/formulize.css?v=" . $cssVersion);
	$xoTheme->addScript("/modules/formulize/libraries/formulize.js?v=" . $jsVersion);
}

echo "<div class='formulize-signup'>";
echo "<h1>" . _formulize_SIGNUP_TITLE . "</h1>";

// Invitation-code UI. If a token has already been accepted (from the URL or a prior submit) we show
// a confirmation of it instead of the box. When a token is required, the box is presented as required.
echo formulize_signupTokenBox($tokenNotice, $requireToken);

// When a token is required but none is accepted yet, do not render the account form at all — there
// is nothing to submit until a valid token is entered above.
if ($tokenGateBlocks) {
	echo "<p class='formulize-signup-token-required'>" . _formulize_SIGNUP_TOKEN_REQUIRED . "</p>";
	echo "</div>";
	include XOOPS_ROOT_PATH . '/footer.php';
	exit();
}

// Trim the System Users form to just the fields a new signer-upper needs (first/last name, username,
// password, email, phone). Admin-only fields (group membership, status, masquerade) are already
// hidden from anonymous viewers; this additionally drops timezone and 2FA setup, which have sensible
// defaults and can be configured later under Edit Account.
$screen_handler = xoops_getmodulehandler('screen', 'formulize');
$thisScreen = $screen_handler->get($sid);
$type_handler = xoops_getmodulehandler($thisScreen->getVar('type') . 'Screen', 'formulize');
$thisScreen = $type_handler->get($sid);

$element_handler = xoops_getmodulehandler('elements', 'formulize');
$keepSuffixes = array('firstname', 'lastname', 'username', 'email', 'phone', 'password');
$orderedIds = array();
foreach ($keepSuffixes as $suffix) {
	if ($ele = $element_handler->get('formulize_user_account_' . $suffix . '_' . $fid)) {
		$orderedIds[] = intval($ele->getVar('ele_id'));
	}
}
$thisScreen->setVar('pages', serialize(array(0 => $orderedIds)));
$thisScreen->setVar('pagetitles', serialize(array(0 => _formulize_SIGNUP_ACCOUNT_DETAILS)));
$thisScreen->setVar('showpagetitles', 1);
$thisScreen->setVar('navstyle', 3);        // buttons only
$thisScreen->setVar('showpageindicator', 2); // off
$thisScreen->setVar('showpageselector', 2); // off

// One button, labelled for signing up.
$thisScreen->setVar('buttontext', array(
	'thankyoulinktext' => '',
	'leaveButtonText' => '',
	'prevButtonText' => '',
	'saveButtonText' => _formulize_SIGNUP_CREATE_BUTTON,
	'nextButtonText' => '',
	'finishButtonText' => _formulize_SIGNUP_CREATE_BUTTON,
	'printableViewButtonText' => '',
	'closeButtonText' => ''
));

// Render a NEW (blank) entry on the System Users form.
$type_handler->render($thisScreen, '', '');

echo "</div>";

include XOOPS_ROOT_PATH . '/footer.php';


// ===========================================================================
// Helpers
// ===========================================================================

/**
 * Accept an invitation token from the URL (?token=) or the invitation-code box, validating it and
 * remembering the sanitized key in the session. Also handles a request to remove a stored token.
 *
 * @return string A human-readable notice about the token (accepted / invalid / empty), or ''.
 */
function formulize_signupAcceptToken() {
	// Remove a previously accepted token if asked.
	if (isset($_POST['signup_token_remove'])) {
		unset($_SESSION['formulize_signup_token']);
		return '';
	}

	$candidate = '';
	$fromUser = false;
	if (isset($_GET['token'])) {
		$candidate = $_GET['token'];
	} elseif (isset($_POST['signup_token'])) {
		$candidate = $_POST['signup_token'];
		$fromUser = true;
	}

	$candidate = preg_replace('/[^A-Za-z0-9]/', '', $candidate);
	if ($candidate === '') {
		// An empty submission from the box is a gentle "no code entered"; a missing URL/POST is silent.
		return ($fromUser) ? _formulize_SIGNUP_TOKEN_EMPTY : '';
	}

	$tokenHandler = xoops_getmodulehandler('token', 'formulize');
	if ($tokenHandler->get($candidate)) {
		$_SESSION['formulize_signup_token'] = $candidate;
		return '';
	}

	// Invalid or expired token. Do not persist it.
	unset($_SESSION['formulize_signup_token']);
	return _formulize_SIGNUP_TOKEN_INVALID;
}

/**
 * Build the invitation-code UI: a confirmation line if a token is already accepted, otherwise a box
 * to enter one. Rendered as its own small form so it does not interfere with the account form.
 *
 * @param string $notice   A notice from formulize_signupAcceptToken() to display, or ''.
 * @param bool   $required Whether a token is mandatory (changes the prompt wording).
 * @return string HTML
 */
function formulize_signupTokenBox($notice, $required = false) {
	$html = "<div class='formulize-signup-token' style='margin:1em 0; padding:1em; border:1px solid #ddd; border-radius:8px;'>";

	if (!empty($_SESSION['formulize_signup_token'])) {
		$groupNames = formulize_signupTokenGroupNames($_SESSION['formulize_signup_token']);
		$html .= "<form method='post' action='" . XOOPS_URL . "/signup.php'>";
		$html .= "<strong>" . _formulize_SIGNUP_TOKEN_ACCEPTED . "</strong>";
		if ($groupNames) {
			$html .= " " . sprintf(_formulize_SIGNUP_TOKEN_GRANTS, htmlspecialchars($groupNames, ENT_QUOTES));
		}
		$html .= " <button type='submit' name='signup_token_remove' value='1' style='margin-left:1em;'>" .
			_formulize_SIGNUP_TOKEN_REMOVE . "</button>";
		$html .= "</form>";
	} else {
		if ($notice) {
			$html .= "<p style='color:#cc0000;'>" . htmlspecialchars($notice, ENT_QUOTES) . "</p>";
		}
		$prompt = $required ? _formulize_SIGNUP_TOKEN_PROMPT_REQUIRED : _formulize_SIGNUP_TOKEN_PROMPT;
		$html .= "<form method='post' action='" . XOOPS_URL . "/signup.php'>";
		$html .= "<label for='signup_token'>" . $prompt . "</label><br>";
		$html .= "<input type='text' id='signup_token' name='signup_token' value='' size='30'> ";
		$html .= "<button type='submit'>" . _formulize_SIGNUP_TOKEN_APPLY . "</button>";
		$html .= "</form>";
	}

	$html .= "</div>";
	return $html;
}

/**
 * Resolve the comma-separated group names a token grants, for display. Registered Users is implicit
 * for everyone and is excluded from the token's own group list by the admin UI, so it is not shown.
 *
 * @param string $tokenKey Sanitized token key
 * @return string Comma-separated group names, or ''
 */
function formulize_signupTokenGroupNames($tokenKey) {
	$tokenHandler = xoops_getmodulehandler('token', 'formulize');
	if (!$token = $tokenHandler->get($tokenKey)) {
		return '';
	}
	$member_handler = xoops_gethandler('member');
	$names = array();
	foreach (array_filter(array_map('intval', explode(' ', trim($token->getVar('groups'))))) as $gid) {
		if ($group = $member_handler->getGroup($gid)) {
			$names[] = $group->getVar('name');
		}
	}
	return implode(', ', $names);
}

/**
 * Assign group memberships to a newly created self-registered user: always Registered Users, plus
 * any groups granted by an accepted token. The token is consumed here (one use spent). For safety a
 * self-signup token can never grant the Anonymous or Webmasters groups.
 *
 * @param int $uid The new user's id
 * @return void
 */
function formulize_signupAssignGroups($uid) {
	$member_handler = xoops_gethandler('member');
	$member_handler->addUserToGroup(XOOPS_GROUP_USERS, $uid);

	if (empty($_SESSION['formulize_signup_token'])) {
		return;
	}
	$tokenKey = $_SESSION['formulize_signup_token'];
	unset($_SESSION['formulize_signup_token']); // consume from the session regardless of outcome

	$tokenHandler = xoops_getmodulehandler('token', 'formulize');
	if (!$token = $tokenHandler->get($tokenKey)) {
		return; // token expired between acceptance and account creation — Registered Users only
	}
	if (!$tokenHandler->incrementUses($token)) {
		return; // token was exhausted — do not grant its groups
	}

	// The manage-tokens UI already excludes the webmasters, anonymous and template groups from what a
	// token can grant. Re-enforce that here so a hand-crafted or pre-existing token can never assign
	// them either: webmasters (site takeover) and anonymous are hard-refused, and template groups —
	// which hold no direct memberships — are skipped.
	$validGroupIds = array_keys($member_handler->getGroups(id_as_key: true));
	$templateGroupIds = formulize_signupTemplateGroupIds();
	foreach (array_filter(array_map('intval', explode(' ', trim($token->getVar('groups'))))) as $gid) {
		if ($gid
			&& $gid != XOOPS_GROUP_ANONYMOUS
			&& $gid != XOOPS_GROUP_ADMIN
			&& !in_array($gid, $templateGroupIds)
			&& in_array($gid, $validGroupIds)) {
			$member_handler->addUserToGroup($gid, $uid);
		}
	}
}

/**
 * Return the ids of all template groups (is_group_template = 1). Users are never direct members of
 * these — only the per-entry groups derived from them (via entries-are-groups forms) are — so a
 * signup token must never assign one.
 *
 * @return int[]
 */
function formulize_signupTemplateGroupIds() {
	global $xoopsDB;
	$ids = array();
	if ($res = $xoopsDB->query("SELECT groupid FROM " . $xoopsDB->prefix('groups') . " WHERE is_group_template = 1")) {
		while ($row = $xoopsDB->fetchArray($res)) {
			$ids[] = intval($row['groupid']);
		}
	}
	return $ids;
}

/**
 * Choose how to deliver the confirmation code to a new account: their configured 2FA method if it is
 * deliverable, then email, then SMS. Sets $maskedContact to a partially-obscured display string.
 *
 * @param int    $uid           The new user's id
 * @param string $maskedContact (out) A safe-to-display, partially masked contact string
 * @return int|false A TFA_* method constant, or false if there is no way to reach the user
 */
function formulize_signupChooseConfirmMethod($uid, &$maskedContact) {
	$member_handler = xoops_gethandler('member');
	$user = $member_handler->getUser($uid);
	$profile_handler = xoops_getmodulehandler('profile', 'profile');
	$profile = $profile_handler->get($uid);

	$email = $user ? trim($user->getVar('email')) : '';
	$phone = $profile ? preg_replace('/[^0-9]/', '', $profile->getVar('2faphone')) : '';
	$smsConfigured = (defined('SMS_ACCOUNT_SID') && SMS_ACCOUNT_SID && defined('SMS_AUTH_TOKEN') && SMS_AUTH_TOKEN && defined('SMS_FROM_NUMBER') && SMS_FROM_NUMBER);
	$canText = ($smsConfigured && $phone !== '');
	$chosen = $profile ? intval($profile->getVar('2famethod')) : 0;

	if ($chosen == TFA_SMS && $canText) {
		$maskedContact = '•••' . substr($phone, -4);
		return TFA_SMS;
	}
	if ($chosen == TFA_EMAIL && $email) {
		$maskedContact = formulize_signupMaskEmail($email);
		return TFA_EMAIL;
	}
	if ($email) {
		$maskedContact = formulize_signupMaskEmail($email);
		return TFA_EMAIL;
	}
	if ($canText) {
		$maskedContact = '•••' . substr($phone, -4);
		return TFA_SMS;
	}
	return false;
}

/**
 * Partially mask an email address for display, e.g. "jane@example.com" -> "j•••@example.com".
 *
 * @param string $email
 * @return string
 */
function formulize_signupMaskEmail($email) {
	$parts = explode('@', $email);
	if (count($parts) != 2 || $parts[0] === '') {
		return $email;
	}
	return substr($parts[0], 0, 1) . '•••@' . $parts[1];
}

/**
 * Render (and process) the confirmation step: the visitor enters the code we sent, and on success
 * their account is activated and they are logged in. On a wrong code we redisplay with an error.
 *
 * @return void
 */
function formulize_signupRenderConfirm() {
	global $icmsConfig;

	$pendingUid = isset($_SESSION['formulize_signup_pending_uid']) ? intval($_SESSION['formulize_signup_pending_uid']) : 0;
	if (!$pendingUid) {
		redirect_header(XOOPS_URL . '/signup.php', 4, _formulize_SIGNUP_SESSION_LOST);
		exit();
	}

	$member_handler = xoops_gethandler('member');
	$user = $member_handler->getUser($pendingUid);
	if (!$user) {
		formulize_signupClearSession();
		redirect_header(XOOPS_URL . '/signup.php', 4, _formulize_SIGNUP_SESSION_LOST);
		exit();
	}
	if (intval($user->getVar('level')) > 0) {
		// Already activated (e.g. a duplicate confirm) — just send them to log in.
		formulize_signupClearSession();
		redirect_header(XOOPS_URL . '/user.php', 4, _formulize_SIGNUP_ALREADY_ACTIVE);
		exit();
	}

	$method = isset($_SESSION['formulize_signup_confirm_method']) ? intval($_SESSION['formulize_signup_confirm_method']) : TFA_EMAIL;
	$contact = isset($_SESSION['formulize_signup_confirm_contact']) ? $_SESSION['formulize_signup_confirm_contact'] : '';

	// Resend requested.
	if (isset($_GET['resend'])) {
		sendCode($method, $pendingUid);
		redirect_header(XOOPS_URL . '/signup.php?op=confirm', 2, _formulize_SIGNUP_RESENT);
		exit();
	}

	$errorMessage = '';
	if (isset($_POST['confirm_code'])) {
		if (validateCode(trim($_POST['confirm_code']), $pendingUid)) {
			// Activate the account and log the user in — possession of the code proves control of
			// their contact method (same standard as a password reset).
			$user->setVar('level', 1);
			$member_handler->insertUser($user, true);
			formulize_signupLoginUser($user);
			formulize_signupClearSession();
			redirect_header(XOOPS_URL . '/index.php', 3, sprintf(_US_LOGGINGU, $user->getVar('uname')), false);
			exit();
		}
		$errorMessage = _formulize_SIGNUP_BAD_CODE;
	}

	include XOOPS_ROOT_PATH . '/header.php';

	$resendLink = "<a href='" . XOOPS_URL . "/signup.php?op=confirm&resend=1'>" . _formulize_SIGNUP_RESEND . "</a>";
	echo "<div class='formulize-signup'>";
	echo "<h1>" . _formulize_SIGNUP_CONFIRM_TITLE . "</h1>";
	if ($errorMessage) {
		echo "<p style='color:#cc0000; font-weight:bold;'>" . $errorMessage . "</p>";
	}
	echo "<p>" . sprintf(_formulize_SIGNUP_CONFIRM_INSTRUCTIONS, htmlspecialchars($contact, ENT_QUOTES)) . "</p>";
	echo "<form method='post' action='" . XOOPS_URL . "/signup.php?op=confirm'>";
	echo "<p><input type='text' name='confirm_code' value='' autocomplete='one-time-code' inputmode='numeric'> ";
	echo "<button type='submit'>" . _formulize_SIGNUP_CONFIRM_BUTTON . "</button></p>";
	echo "</form>";
	echo "<p>" . sprintf(_formulize_SIGNUP_RESEND_PROMPT, $resendLink) . "</p>";
	echo "</div>";

	include XOOPS_ROOT_PATH . '/footer.php';
}

/**
 * Establish an authenticated session for the given (already activated) user. Mirrors the essential
 * session setup performed by include/checklogin.php after a successful password login.
 *
 * @param object $user The user object to log in
 * @return void
 */
function formulize_signupLoginUser($user) {
	global $icmsConfig;
	$member_handler = xoops_gethandler('member');
	$user->setVar('last_login', time());
	$member_handler->insertUser($user, true);

	session_regenerate_id(true);
	$_SESSION = array();
	$_SESSION['xoopsUserId'] = $user->getVar('uid');
	$_SESSION['xoopsUserGroups'] = $user->getGroups();
	$_SESSION['xoopsUserLastLogin'] = $user->getVar('last_login');

	if ($icmsConfig['use_mysession'] && $icmsConfig['session_name'] != '') {
		$session_secure = substr(ICMS_URL, 0, 5) == 'https';
		setcookie($icmsConfig['session_name'], session_id(), array(
			'expires' => time() + (60 * $icmsConfig['session_expire']),
			'path' => '/',
			'domain' => '',
			'secure' => $session_secure,
			'httponly' => true,
			'samesite' => icms_core_Session::cookieSameSite($session_secure)
		));
	}
	$user_theme = $user->getVar('theme');
	if (in_array($user_theme, $icmsConfig['theme_set_allowed'])) {
		$_SESSION['xoopsUserTheme'] = $user_theme;
	}
}

/**
 * Clear all transient signup state from the session.
 *
 * @return void
 */
function formulize_signupClearSession() {
	unset($_SESSION['formulize_signup_pending_uid']);
	unset($_SESSION['formulize_signup_confirm_method']);
	unset($_SESSION['formulize_signup_confirm_contact']);
	unset($_SESSION['formulize_signup_token']);
}
