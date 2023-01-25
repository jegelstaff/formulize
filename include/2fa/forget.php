<?php

/* Copyright the Formulize Project - Julian Egelstaff 2021
 *
 * Clear the profile field where the devices are remembered
 */


include_once "../../mainfile.php";
icms::$logger->disableLogger();
while(ob_get_level()) {
    ob_end_clean();
}
global $xoopsUser;
if($xoopsUser) {
    $profile_handler = xoops_getmodulehandler('profile', 'profile');
    $profile = $profile_handler->get($xoopsUser->getVar('uid'));
	$profile->setVar('2fadevices', '');
	if($profile_handler->insert($profile)) {
        print 1;
    }
}