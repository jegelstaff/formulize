<?php

/*
 * ESAT Remote Plugin for Brightspace
 */

require_once "../../mainfile.php";
require_once '../brightspace/OAuth1p0.php';
require_once '../brightspace/libsrc/D2LAppContextFactory.php';
require_once '../brightspace/libsrc/D2LHostSpec.php';
require_once '../brightspace/config.php';

error_reporting(E_ALL);
ini_set('display_errors',0);

// Construct our URL that will be used for auth redirect
if(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
  $scheme = 'https';
} else {
  $scheme = 'http';
}

$serverPort = $_SERVER['SERVER_PORT'];
$port = "";
if(($scheme == 'http' && $serverPort != 80) || ($scheme == "https" && $serverPort != 443)) {
    $port = ":$serverPort";
}

$myUrl = $scheme . '://' . $_SERVER["SERVER_NAME"] . $port . $_SERVER["REQUEST_URI"];

// we have credentials from Brightspace token check, then we should be able to get lmsUrl from Session
if(isset($_GET['x_a']) AND isset($_GET['x_b'])) {
    
    $lmsUrl = parse_url($_SESSION['lmsUrl']);
    
// we don't have credentials yet, so let's validate via OAuth and go ask for credentials
} else {
    
    // Find our URL
    $url = 'http';
    if ( !empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] !== 'off' ) {
        $url .= 's';
    }
    
    $serverPort = $_SERVER['SERVER_PORT'];
    $port = "";
    if ( ( $url == 'http' && $serverPort != 80 ) || ( $url == "https" && $serverPort != 443 ) ) {
        $port = ":$serverPort";
    }
    
    $url .= '://' . $_SERVER["SERVER_NAME"] . $port . $_SERVER["REQUEST_URI"];
    
    if ( !OAuth1p0::CheckSignatureForFormUrlEncoded( $url, 'POST', $_POST, $config['secret'] ) ){
        exit( "Invalid OAuth signature\n" );
    }
    
    if ( empty( $_POST[ 'user_id' ] ) ) {
        exit( "Missing user_id parameter" );
    }
    
    // Get user info from the LTI launch
    $userId = $_POST[ 'user_id' ];
    
    // D2L LTI launch sends user_id as <LMS installation code>_<LMS user ID>
    $userId = substr( $userId, strpos( $userId, '_') + 1 );
    
    $orgId = $_POST[ 'context_id' ];
    $name = $_POST[ 'lis_person_name_full' ];
    $email = $_POST[ 'lis_person_contact_email_primary' ];
    
    $returnUrl = parse_url( $_POST[ 'launch_presentation_return_url' ] );
    
    $queryParams = array();
    parse_str( $returnUrl[ 'query' ], $queryParams );
    
    // we get the lms info from lis_outcome_service_url
    $lmsUrl = parse_url($_POST['lis_outcome_service_url']);
    if($lmsUrl['port'] == '') {
      $lmsUrl['port'] = $lmsUrl['scheme'] == 'http' ? 80 : 443;
    }
    
    // Record the user info
    $_SESSION['returnUrl'] = $_POST['launch_presentation_return_url'];
    $_SESSION['orgId'] = $_POST['context_id'];
    $_SESSION['lmsUrl'] = $lmsUrl['scheme'].'://'.$lmsUrl['host'].':'.$lmsUrl['port'];
    $_SESSION['lmsUserId'] = $userId;
    $_SESSION['name'] = $name;
    //$_SESSION['email'] = 'bgabriel@ilns';
    $_SESSION['email'] = $email;
    $_SESSION['ou'] = $queryParams['ou'];
    $_SESSION['parentNode'] = $queryParams['parentNode'];
    $_SESSION['context_title'] = $_REQUEST['context_title'];
    $_SESSION['context_label'] = $_REQUEST['context_label'];
    
}

// Create auth security context and user context
$authContextFactory = new D2LAppContextFactory();
$authContext = $authContextFactory->createSecurityContext($config['appId'], $config['appKey']);
$hostSpec = new D2LHostSpec($lmsUrl['host'], $lmsUrl['port'], $lmsUrl['scheme']);
$opContext = $authContext->createUserContextFromHostSpec($hostSpec, null, null, $myUrl);

if($opContext != null) {
  // We have everything we need to create user context.  Go to the main page after saving user API key and ID.
  $_SESSION['apiUserId'] = $opContext->getUserId();
  $_SESSION['apiUserKey'] = $opContext->getUserKey();
  //print_r($_SESSION);
  //exit('heading to controller');
  header("Location: /libraries/brightspace/controller.php");
} else {
  // Do the LMS auth.
  $url = $authContext->createUrlForAuthenticationFromHostSpec($hostSpec, $myUrl);
  //print_r($_SESSION);
  //exit('requesting token');
  header("Location: $url");
}