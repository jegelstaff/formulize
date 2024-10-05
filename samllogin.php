<?php

include_once "mainfile.php";

global $xoopsUser;
if(!$xoopsUser) {

require_once XOOPS_ROOT_PATH.'/libraries/php-saml/_toolkit_loader.php';

require_once XOOPS_ROOT_PATH.'/libraries/php-saml/settings.php';

$auth = new OneLogin_Saml2_Auth($settingsInfo);

$auth->login();

} else {
	header('location: '.XOOPS_URL);
}