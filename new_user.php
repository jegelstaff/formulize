<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
$xoopsOption['template_main'] = 'profile_register.html';
include "mainfile.php";

include "header.php";
include ICMS_ROOT_PATH .'/include/registerform.php';
include_once ICMS_ROOT_PATH .'/include/functions.php';
include_once ICMS_ROOT_PATH .'/modules/profile/include/forms.php';
include_once ICMS_ROOT_PATH . '/modules/profile/language/english/main.php';


$member_handler = icms::handler('icms_member');
$newuser = isset($_SESSION['profile']['uid']) ? $member_handler->getUser($_SESSION['profile']['uid']) : $member_handler->createUser();

$profile_handler = icms_getmodulehandler('profile', 'profile', 'profile');
$profile = $profile_handler->get($newuser->getVar('uid'));


$criteria = new icms_db_criteria_Compo();
$criteria->setSort('step_order');

$regstep_handler = icms_getmodulehandler('regstep', 'profile', 'profile');
$steps = $regstep_handler->getObjects($criteria);
if (count($steps) == 0) redirect_header(ICMS_URL.'/', 6, _MD_PROFILE_NOSTEPSAVAILABLE);

//set some of the inputs with the info we get from google
$newuser->setVar('login_name', $_SESSION['name']);
$newuser->setVar('uname', $_SESSION['name']);
$newuser->setVar('email', $_SESSION['email']);
$reg_form = getRegisterForm($newuser, $profile, 0, $steps[0]);

$reg_form->assign($icmsTpl);

include "footer.php";

