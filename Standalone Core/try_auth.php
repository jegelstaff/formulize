<?php
/**
* Beginning of authorizing using openID
* @copyright    http://www.impresscms.org/ The ImpressCMS Project
* @license      LICENSE.txt
* @package      Users
* @since        1.1
* @author       marcan <marcan@impresscms.org>
* @author       Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
* @version      $Id: try_auth.php 9346 2009-09-06 16:13:57Z m0nty $
*/
define('ICMS_INCLUDE_OPENID', true);
include_once 'mainfile.php';

$_SESSION['frompage'] = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER']
    : (isset($_ENV['HTTP_REFERER']) ? $_ENV['HTTP_REFERER'] : '');
//SESSION is started automatically by Xoops. -- Natsuhiko
//session_start();

// since we are trying to authenticate with OpenID, let' get rid of any data in $_SESSION['openid_response']
unset($_SESSION['openid_response']);

function getOpenIDURL()
{
    // Render a default page if we got a submission without an openid
    // value.
    if(empty($_GET['openid_identifier']))
    {
        echo $error = 'Expected an OpenID URL.';
        //header("Location: /modules/openid/");
        exit(0);
    }
    return $_GET['openid_identifier'];
}

function run()
{
    $openid = getOpenIDURL();
    $consumer = getConsumer();

    // Begin the OpenID authentication process.
    $auth_request = $consumer->begin($openid);

    // No auth request means we can't begin OpenID.
    if(!$auth_request)
    {
        displayError('Authentication error; not a valid OpenID.');
    }

    $sreg_request = Auth_OpenID_SRegRequest::build(
        // Required
        array('nickname', 'email'),
        // Optional
        array('fullname', 'dob', 'gender', 'postcode', 'country', 'language', 'timezone')
        );

    if($sreg_request)
    {
        $auth_request->addExtension($sreg_request);
    }

    $policy_uris = $_GET['policies'];

    $pape_request = new Auth_OpenID_PAPE_Request($policy_uris);
    if($pape_request)
    {
        $auth_request->addExtension($pape_request);
    }

    // Redirect the user to the OpenID server for authentication.
    // Store the token for this authentication so we can verify the
    // response.

    // For OpenID 1, send a redirect.  For OpenID 2, use a Javascript
    // form to send a POST request to the server.
    if($auth_request->shouldSendRedirect())
    {
        $redirect_url = $auth_request->redirectURL(getTrustRoot(), getReturnTo());

        // If the redirect URL can't be built, display an error
        // message.
        if(Auth_OpenID::isFailure($redirect_url))
        {
            //displayError("Could not redirect to server: " . $redirect_url->message);
        }
         else
        {
            // Send redirect.
            header('Location: '.$redirect_url);
            exit();
        }
    }
    else
    {
        // Generate form markup and render it.
        $form_id = 'openid_message';
        $form_html = $auth_request->formMarkup(getTrustRoot(), getReturnTo(), false, array('id' => $form_id));

        // Display an error if the form markup couldn't be generated;
        // otherwise, render the HTML.
        if(Auth_OpenID::isFailure($form_html))
        {
            displayError('Could not redirect to server: '.$form_html->message);
        }
        else
        {
            $page_contents = array(
                "<html><head><title>",
                "OpenID transaction in progress",
                "</title></head>",
                "<body onload='document.getElementById(\"".$form_id."\").submit()'>",
                $form_html,
                "</body></html>"
                );

            print implode('\n', $page_contents);
        }
    }
}
run();
?>