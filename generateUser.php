<?php

include "mainfile.php";
include_once XOOPS_ROOT_PATH."/modules/formulize/include/common.php";
include_once XOOPS_ROOT_PATH."/modules/formulize/integration_api.php";
Formulize::init();

$user_data = array(
    'uid'                => 0,
    'uname'                => 'jeremy123',
    'login_name'        => 'Jeremy12353',
    'name'                => 'j',
    'pass'                => '123',
    'email'                => 'my email',
    'timezone_offset'    => 45,
    'language'            => 'en',
    'user_avatar'        => 'blank.gif',
    'theme'                => 'impresstheme',
    'level'                => 1
);
$user = new FormulizeUser($user_data);

Formulize::createUser($user);
