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

// read data out of the notification cache if this is called by a direct request rather than included within Formulize
// record which row we're reading and if we get to the end, unlink the file
// if for some reason we haven't unlinked the file, then pick up reading the file from where we left off last time.

$startTime = microtime(TRUE);
$maxExec = 60; // max seconds the script has to execute in. Based on the lowest time limit the script is operating under, could be fastcgi limit, php limit, something else...we could set this with config option in xoopsVersion.php if we want to get fancy and give the user control

if(!defined("XOOPS_MAINFILE_INCLUDED")) {

    include '../../mainfile.php';
    if(file_exists(XOOPS_ROOT_PATH."/modules/formulize/cache/formulizeNotifications.txt")) {
        
        // check if there's a sending operation going on, but ignore if the lock is really old
        if($lockTime = floatval(file_get_contents(XOOPS_ROOT_PATH."/modules/formulize/cache/formulizeNotificationsSending.lock"))) {
            if($startTime - $lockTime < $maxExec * 2) {
                exit();
            }
        }

        // no lock, so away we go
        file_put_contents(XOOPS_ROOT_PATH."/modules/formulize/cache/formulizeNotificationsSending.lock", "$startTime");
        include_once XOOPS_ROOT_PATH."/modules/formulize/include/functions.php";

        // if the queue is not fully sent, we'll truncate to the appropriate point, and start from there...
        if($start = intval(file_get_contents(XOOPS_ROOT_PATH."/modules/formulize/cache/formulizeNotificationsIndex.txt"))) {
            $notFile = fopen(XOOPS_ROOT_PATH."/modules/formulize/cache/formulizeNotifications.txt","a");
            formulize_getLock($notFile);
            $notData = formulize_readNotifications();
            ftruncate($notFile, 0); // erase the file contents, we're going to rewrite them now starting with the next record to send...
            $i = $start;
            while(isset($notData[$i])) {
                fwrite($notFile, trim($notData[$i])."19690509\r\n");
                $i++;
            }
            file_put_contents(XOOPS_ROOT_PATH."/modules/formulize/cache/formulizeNotificationsIndex.txt", "0"); // reset the counter since we've removed the lines that were already sent
            $start=0;
            fclose($notFile);
        }

        // read the queue
        $notData = formulize_readNotifications();
        $i = $start; // should always be zero by this point, since if we were going to start above zero, then we would have shrunk the file and reset counter
        while(isset($notData[$i]) AND formulize_notifyStillTime($startTime, $maxExec)) { 
            if(trim($notData[$i])) {
                list(
                    $event,
                    $extra_tags,
                    $fid,
                    $uids_to_notify,
                    $mid,
                    $omit_user,
                    $subject,
                    $template,
                    $GLOBALS['formulize_notification_email']) = unserialize(trim($notData[$i]));
                formulize_notify($event, $extra_tags, $fid, $uids_to_notify, $mid, $omit_user, $subject, $template);
            }
            $i++;
            // save the next row number so we know where to pickup next time if we timeout or stop or whatever.
            file_put_contents(XOOPS_ROOT_PATH."/modules/formulize/cache/formulizeNotificationsIndex.txt", "$i"); 
        }
        
        // check if in fact we've sent everything that is now in the cache file, and if so, unlink the file...
        // someone else could have added to the cache while we were doing this sending operation.
        if(!isset($notData[$i])) {
            if(count(formulize_readNotifications()) <= $i) {
                unlink(XOOPS_ROOT_PATH."/modules/formulize/cache/formulizeNotifications.txt");
                file_put_contents(XOOPS_ROOT_PATH."/modules/formulize/cache/formulizeNotificationsIndex.txt", "0");
            }
        }
        
        // remove the lock
        unlink(XOOPS_ROOT_PATH."/modules/formulize/cache/formulizeNotificationsSending.lock");
    }
}

// read the notification file and return an array with all the items in the queue
function formulize_readNotifications() {
    $notData = trim(file_get_contents(XOOPS_ROOT_PATH."/modules/formulize/cache/formulizeNotifications.txt"));
    if($notData) {
        return explode("19690509",$notData);
    } else {
        return array();
    }
}

// check how much time has elapsed since we started the script, and since the last message was sent
// figure out if we have enough time left to send another message.
// SAME IN DIGEST.PHP
function formulize_notifyStillTime($startTime, $maxExec) {
    
    static $prevTimes = array();
    static $durations = array();
    static $iteration = 0;
    
    // if we've done this before, calculate the duration since the last time
    $curTime = microtime(TRUE);
    if($iteration) {
        $durations[$iteration] = $curTime - $prevTimes[$iteration];
    }
    $iteration++;
    $prevTimes[$iteration] = $curTime;
    // if we've got a duration, then work out if we've got a chance of going around one more time before the maxExec time is reached
    if(isset($durations[$iteration-1])) {
        $go = false;
        $remainingTime = $maxExec - ($curTime - $startTime);
        // if we've got at least double the prior duration remaining, and double the average duration remaining, then go
        if($remainingTime > ($durations[$iteration-1]*2) AND $remainingTime > ((array_sum($durations)/count($durations))*2)) {
            $go = true;
        }
    } else {
        $go = true;
    }
    return $go;
}

// send the notifications
function formulize_notify($event, $extra_tags, $fid, $uids_to_notify, $mid, $omit_user, $subject="", $template="") {
    
    $notification_handler = xoops_gethandler('notification');
    $module_handler = xoops_gethandler('module');
    $formulizeModule = $module_handler->getByDirname("formulize");
    $not_config = $formulizeModule->getInfo('notification');
    $form_handler = xoops_getmodulehandler('forms', 'formulize');
    $formObject = $form_handler->get($fid);
    $sendDigests = $formObject->getVar('send_digests');
    
    if($subject OR $template) {
    
        switch ($event) {
            case "new_entry":
                $evid = 1;
                break;
            case "update_entry":
                $evid = 2;
                // validate any revision data we might have, and only proceed if there's a difference
                $differenceFound = false;
                foreach($extra_tags as $key=>$value) {
                    if(substr($key, 0, 8)=="ELEMENT_") {
                        if(isset($extra_tags['REVISION_'.$key])) {
                            $revisionsOn = true;
                            if($extra_tags['REVISION_'.$key] != $value) {
                                $differenceFound = true;
                                break;
                            }
                        } else {
                            $revisionsOn = false;
                            break;
                        }
                    }
                }
                if($revisionsOn AND !$differenceFound) {
                    return;
                }
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
                $not_config['event'][$evid]['mail_subject'] = str_replace("{".$tag."}",$value, $not_config['event'][$evid]['mail_subject']);
                $GLOBALS['formulize_notificationSubjectOverride'] = str_replace("{".$tag."}",$value, $GLOBALS['formulize_notificationSubjectOverride']);
            }
        }
        $mailSubject = $not_config['event'][$evid]['mail_subject'];
        $mailTemplate = $not_config['event'][$evid]['mail_template'];
        
    } else {
        $mailSubject = "";
        $mailTemplate = "";
    }
    
    // IF WE'RE SENDING DIGESTS, THE STORE THE MESSAGE DATA ORGANIZED BY USER/EMAIL IN A NEW QUEUE, ELSE SEND THE NOTIFICATION
    if($sendDigests) {
        if (in_array(-1, $uids_to_notify)) {
            foreach(explode(",",$GLOBALS['formulize_notification_email']) as $email) {
                formulize_saveDigestData($email, $fid, $event, $extra_tags, $mailSubject, $mailTemplate);
            }
            unset( $uids_to_notify[array_search(-1, $uids_to_notify)]); // now remove the special flag that indicates we're sending to direct e-mails
        }
        if(count($uids_to_notify)>0) {
            $member_handler = xoops_gethandler('member');
            foreach($uids_to_notify as $uid) {
                if($userObject = $member_handler->getUser($uid)) {
                    formulize_saveDigestData($userObject->getVar('email'), $fid, $event, $extra_tags, $mailSubject, $mailTemplate);
                }
            }
        }
    } else {
        // trigger the event
        if (in_array(-1, $uids_to_notify)) {
            sendNotificationToEmail($GLOBALS['formulize_notification_email'], $event, $extra_tags, $mailSubject, $mailTemplate);
            unset( $uids_to_notify[array_search(-1, $uids_to_notify)]); // now remove the special flag before triggering the event
        }
        if(count($uids_to_notify)>0) {
            $notification_handler->triggerEvent("form", $fid, $event, $extra_tags, $uids_to_notify, $mid, $omit_user);
        }
    }
        
    if($subject OR $template) {
        $not_config['event'][$evid]['mail_subject'] = $oldsubject;
        $not_config['event'][$evid]['mail_template'] = $oldtemp;
        unset($GLOBALS['formulize_notificationTemplateOverride']);
        unset($GLOBALS['formulize_notificationSubjectOverride']);
    }
    
}

// save digestData to the database, so we can call it up later when everything is finished
function formulize_saveDigestData($email, $fid, $event, $extra_tags, $mailSubject, $mailTemplate) {
    global $xoopsDB;
    $sql = "INSERT INTO ".$xoopsDB->prefix("formulize_digest_data")." (email, fid, event, extra_tags, mailSubject, mailTemplate) VALUES ('".formulize_db_escape($email)."', ".intval($fid).", '".formulize_db_escape($event)."', '".formulize_db_escape(serialize($extra_tags))."', '".formulize_db_escape($mailSubject)."', '".formulize_db_escape($mailTemplate)."')";
    $res = $xoopsDB->queryF($sql);
}
