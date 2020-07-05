<?php

/*get the aid and include custom_code if exists
 *
 *Added By Jinfu Jan 2015
 */
$application_handler = xoops_getmodulehandler('applications','formulize');
$apps = $application_handler->getAllApplications();

foreach($apps as $appObject){
   $aid=$appObject->getVar('appid');
   if(file_exists(XOOPS_ROOT_PATH.'/modules/formulize/custom_code/application_custom_code_'.$aid.'.php')) {
       include_once(XOOPS_ROOT_PATH.'/modules/formulize/custom_code/application_custom_code_'.$aid.'.php');
   }
}