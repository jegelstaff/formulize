<?php
/* -----------------------------------------------------
// OpenID RP Module for Xoops
//  by Nat Sakimura
//  (c) 2008 by Nat Sakimura (=nat), JanRain
//  License: GPL
//
// occommon.php is the file for shared functions.
// This file is based on JanRain's Library example.
-------------------------------------------------------- */
$path_extra = dirname(__FILE__);
$path_extra2 = ICMS_LIBRARIES_PATH . "/phpopenid/";
$path = ini_get('include_path');
$path = $path_extra2 . PATH_SEPARATOR . $path_extra . PATH_SEPARATOR . $path;

ini_set('include_path', $path);

function displayError($message) {
	icms_core_Message::warning($message, '', TRUE);
}

function doIncludes() {
    /**
     * Require the OpenID consumer code.
     */
    require_once "Auth/OpenID/Consumer.php";

    /**
     * Require the "file store" module, which we'll need to store
     * OpenID information.
     */
    require_once "Auth/OpenID/FileStore.php";

    /**
     * Require the Simple Registration extension API.
     */
    require_once "Auth/OpenID/SReg.php";

    /**
     * Require the PAPE extension module.
     */
    require_once "Auth/OpenID/PAPE.php";
}

doIncludes();
//require_once('header.php');

global $pape_policy_uris;
$pape_policy_uris = array(
			  PAPE_AUTH_MULTI_FACTOR_PHYSICAL,
			  PAPE_AUTH_MULTI_FACTOR,
			  PAPE_AUTH_PHISHING_RESISTANT
			  );

function &getStore() {
    /**
     * This is where the example will store its OpenID information.
     * You should change this path if you want the example store to be
     * created elsewhere.  After you're done playing with the example
     * script, you'll have to remove this directory manually.
     */
    //$store_path = "/tmp/_php_consumer_test";
    $store_path = ICMS_TRUST_PATH . "/_php_consumer";

    if (!file_exists($store_path) 
    	&& !mkdir($store_path)
    ) {
        print "Could not create the FileStore directory."
        	. " Please check the effective permissions.";
        exit(0);
    }

    $return = new Auth_OpenID_FileStore($store_path);
    return $return;
}

function &getConsumer() {
    /**
     * Create a consumer object using the store object created
     * earlier.
     */
    $store = getStore();
    $return = new Auth_OpenID_Consumer($store);
    return $return;
}

function getScheme() {
    $scheme = 'http';
    if (isset($_SERVER['HTTPS']) and $_SERVER['HTTPS'] == 'on') {
        $scheme .= 's';
    }
    return $scheme;
}

/**
 * OpenID needs a target URI to return its response
 */
function getReturnTo() {
	return ICMS_URL . "/finish_auth.php";
}

function getTrustRoot() {
	$directory = dirname($_SERVER['PHP_SELF']); 
	$directory = "/" ? "" : $directory;
	return sprintf("%s://%s:%s%s/",
                   getScheme(), $_SERVER['SERVER_NAME'],
                   $_SERVER['SERVER_PORT'],
                   $directory
                   );
}


/**
 * This is where the example will store its OpenID information.  You
 * should change this path if you want the example store to be created
 * elsewhere.  After you're done playing with the example script,
 * you'll have to remove this directory manually.
 */

$store_path = ICMS_TRUST_PATH . "/_php_consumer";
if (!file_exists($store_path) 
	&& !mkdir($store_path)
) {
    print "Could not create the FileStore directory." 
    	. " Please check the effective permissions.";
    exit(0);
}

$store = new Auth_OpenID_FileStore($store_path);

/**
 * Create a consumer object using the store object created earlier.
 */
$consumer = new Auth_OpenID_Consumer($store);

/**
 * Sanitization Functions
 */
function quote_smart($value) {
    // Stripslashes
    if (get_magic_quotes_gpc()) {
        $value = stripslashes($value);
    }
    // Escape non-numeric string
    if (!is_numeric($value)) {
        $value = "'" . icms::$xoopsDB->escape($value) . "'";
    }
    return $value;
}


function alphaonly($str) {
	$str2 = preg_replace("/[^a-zA-Z0-9=@\/\.]/",'_',$str);
	return $str2;
}

