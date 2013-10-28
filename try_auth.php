<?php
/**
 * Beginning of authorizing using openID
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @package		Auth
 * @subpackage	Openid
 * @since		1.1
 * @author		marcan <marcan@impresscms.org>
 * @author		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version		SVN: $Id: try_auth.php 21731 2011-06-10 21:36:28Z skenow $
 */
/**
 * Has this file been included?
 * @var boolean
 */
define('ICMS_INCLUDE_OPENID', TRUE);
/**
 * mainfile.php starts the boot process
 */
include_once 'mainfile.php';

$_SESSION['frompage'] = isset($_SERVER['HTTP_REFERER'])
	? $_SERVER['HTTP_REFERER']
	: (isset($_ENV['HTTP_REFERER']) ? $_ENV['HTTP_REFERER'] : '');

// since we are trying to authenticate with OpenID, let's get rid of any data in $_SESSION['openid_response']
unset($_SESSION['openid_response']);

function getOpenIDURL() {
	// Render a default page if we got a submission without an openid value.
	if (empty($_GET['openid_identifier'])) {
		echo $error = _CORE_OID_URL_EXPECTED;
		exit(0);
	}
	$oid_uri = filter_var($_GET['openid_identifier'], FILTER_SANITIZE_URL);
	return $oid_uri;
}

function run() {
	$openid = getOpenIDURL();
	$consumer = getConsumer();

	// Begin the OpenID authentication process.
	$auth_request = $consumer->begin($openid);

	// No auth request means we can't begin OpenID.
	if (!$auth_request) {
		displayError(_CORE_OID_URL_INVALID);
	}

	$sreg_request = Auth_OpenID_SRegRequest::build(
		// Required
		array('nickname', 'email'),
		// Optional
		array('fullname', 'dob', 'gender', 'postcode', 'country', 'language', 'timezone')
	);

	if ($sreg_request) {
		$auth_request->addExtension($sreg_request);
	}

	$policy_uris = isset($_GET['policies']) ? filter_var($_GET['policies'], FILTER_SANITIZE_URL) : NULL;

	$pape_request = new Auth_OpenID_PAPE_Request($policy_uris);
	if ($pape_request) {
		$auth_request->addExtension($pape_request);
	}

	// Redirect the user to the OpenID server for authentication.
	// Store the token for this authentication so we can verify the
	// response.

	// For OpenID 1, send a redirect.  For OpenID 2, use a Javascript
	// form to send a POST request to the server.
	if ($auth_request->shouldSendRedirect()) {
		$redirect_url = $auth_request->redirectURL(getTrustRoot(), getReturnTo());

		// If the redirect URL can't be built, display an error
		// message.
		if (Auth_OpenID::isFailure($redirect_url)) {
			//displayError("Could not redirect to server: " . $redirect_url->message);
		} else {
			// Send redirect.
			header('Location: ' . $redirect_url);
			exit();
		}
	} else {
		// Generate form markup and render it.
		$form_id = 'openid_message';
		$form_html = $auth_request->formMarkup(getTrustRoot(), getReturnTo(), FALSE, array('id' => $form_id));

		// Display an error if the form markup couldn't be generated;
		// otherwise, render the HTML.
		if (Auth_OpenID::isFailure($form_html)) {
			displayError(sprintf(_CORE_OID_REDIRECT_FAILED, $form_html->message));
		} else {
			$page_contents = array(
				"<html><head><title>",
				_CORE_OID_INPROGRESS,
				"</title></head>",
				"<body onload='document.getElementById(\"" . $form_id . "\").submit()'>",
				$form_html,
				"</body></html>"
			);
			print implode("\n", $page_contents);
		}
	}
}
run();
