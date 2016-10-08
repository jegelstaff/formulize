<?php

###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2005 Freeform Solutions                  ##
###############################################################################
##                    XOOPS - PHP Content Management System                  ##
##                       Copyright (c) 2000 XOOPS.org                        ##
##                          <http://www.xoops.org/>                          ##
###############################################################################
##  This program is free software; you can redistribute it and/or modify     ##
##  it under the terms of the GNU General Public License as published by     ##
##  the Free Software Foundation; either version 2 of the License, or        ##
##  (at your option) any later version.                                      ##
##                                                                           ##
##  You may not change or alter any portion of this comment or credits       ##
##  of supporting developers from this source code or any supporting         ##
##  source code which is considered copyrighted (c) material of the          ##
##  original comment or credit authors.                                      ##
##                                                                           ##
##  This program is distributed in the hope that it will be useful,          ##
##  but WITHOUT ANY WARRANTY; without even the implied warranty of           ##
##  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            ##
##  GNU General Public License for more details.                             ##
##                                                                           ##
##  You should have received a copy of the GNU General Public License        ##
##  along with this program; if not, write to the Free Software              ##
##  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA ##
###############################################################################
##  Author of this file: Freeform Solutions 		                         ##
##  Project: Formulize                                                       ##
###############################################################################

// read data out of the notification cache if this is called by a direct URL request, that includes the GET param 'readFormulizeNotificationCache'
if(isset($_GET['readFormulizeNotificationCache'])) {
    include '../../mainfile.php';
    $notFile = fopen(XOOPS_ROOT_PATH."/cache/formulizeNotifications","r");
    $notData = file($notFile);
    fclose($notFile);
    foreach($notData as $thisNot) {
        $thisNot = explode("19690509",$thisNot);
        $event = unserialize($thisNot[0]);
        $extra_tags = unserialize($thisNot[0]);
        $fid = unserialize($thisNot[0]);
        $uids_to_notify = unserialize($thisNot[0]);
        $mid = unserialize($thisNot[0]);
        $omit_user = unserialize($thisNot[0]);
        $subject = unserialize($thisNot[0]);
        $template = unserialize($thisNot[0]);
        $GLOBALS['formulize_notification_email'] = unserialize($thisNot[0]);
        formulize_notify($event, $extra_tags, $fid, $uids_to_notify, $mid, $omit_user, $subject, $template);
    }
    unlink(XOOPS_ROOT_PATH."/cache/formulizeNotifications");
}

// send the notifications
function formulize_notify($event, $extra_tags, $fid, $uids_to_notify, $mid, $omit_user, $subject="", $template="") {
    
    $notification_handler = xoops_gethandler('notification');
    $module_handler = xoops_gethandler('module');
    $formulizeModule = $module_handler->getByDirname("formulize");
    $not_config = $formulizeModule->getInfo('notification');
    
    if($subject OR $template) {
    
        switch ($event) {
            case "new_entry":
                $evid = 1;
                break;
            case "update_entry":
                $evid = 2;
                break;
            case "delete_entry":
                $evid = 3;
                break;
        }
        $oldsubject = $not_config['event'][$evid]['mail_subject'];
        $oldtemp = $not_config['event'][$evid]['mail_template'];
        // rewrite the notification with the subject and template we want, then reset
        $GLOBALS['formulize_notificationTemplateOverride'] = $template == "" ? $not_config['event'][$evid]['mail_template'] : $template;
        $GLOBALS['formulize_notificationSubjectOverride'] = $subject == "" ? $not_config['event'][$evid]['mail_subject'] : trans($subject);
        $not_config['event'][$evid]['mail_template'] = $template == "" ? $not_config['event'][$evid]['mail_template'] : $template;
        $not_config['event'][$evid]['mail_subject'] = $subject == "" ? $not_config['event'][$evid]['mail_subject'] : trans($subject);
        // loop through the variables and do replacements in the subject, if any
        if (strstr($not_config['event'][$evid]['mail_subject'], "{ELEMENT")) {
            foreach ($extra_tags as $tag=>$value) {
                str_replace("{".$tag."}",$value, $not_config['event'][$evid]['mail_subject']);
                str_replace("{".$tag."}",$value, $GLOBALS['formulize_notificationSubjectOverride']);
            }
        }
        $mailSubject = $not_config['event'][$evid]['mail_subject'];
        $mailTemplate = $not_config['event'][$evid]['mail_template'];
        
    } else {
        $mailSubject = "";
        $mailTemplate = "";
    }
    
    // trigger the event
    if (in_array(-1, $uids_to_notify)) {
        sendNotificationToEmail($GLOBALS['formulize_notification_email'], $event, $extra_tags, $mailSubject, $mailTemplate);
        unset( $uids_to_notify[array_search(-1, $uids_to_notify)]); // now remove the special flag before triggering the event
    }
    $notification_handler->triggerEvent("form", $fid, $event, $extra_tags, $uids_to_notify, $mid, $omit_user);
    
    if($subject OR $template) {
        $not_config['event'][$evid]['mail_subject'] = $oldsubject;
        $not_config['event'][$evid]['mail_template'] = $oldtemp;
        unset($GLOBALS['formulize_notificationTemplateOverride']);
        unset($GLOBALS['formulize_notificationSubjectOverride']);
    }
    
}
