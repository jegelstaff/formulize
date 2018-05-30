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

// read digest notification data out of the database and send messages 

$startTime = microtime(TRUE);
$maxExec = 60; // max seconds the script has to execute in. Based on the lowest time limit the script is operating under, could be fastcgi limit, php limit, something else...we could set this with config option in xoopsVersion.php if we want to get fancy and give the user control

if(!defined("XOOPS_MAINFILE_INCLUDED")) {

    include '../../mainfile.php';
    include_once XOOPS_ROOT_PATH."/modules/formulize/include/functions.php";
    $elementHandler = xoops_getmodulehandler('elements', 'formulize');
    $memberHandler = xoops_gethandler('member');
    
    global $xoopsDB, $xoopsConfig;
    while(formulize_notifyStillTime($startTime, $maxExec)) {
        $sql = "SELECT * FROM ".$xoopsDB->prefix("formulize_digest_data")." WHERE email = (SELECT email FROM ".$xoopsDB->prefix("formulize_digest_data")." ORDER BY email LIMIT 0,1) ORDER BY fid, digest_id ASC";
        $res = $xoopsDB->query($sql);
        $ids = array();
        $digestedEntries = array();
        $mailTemplate = "";
        $groupedMessages = array(); // will be indexed by group title so they can be collected together
        while($array = $xoopsDB->fetchArray($res)) {
            // check that fid and extra tags ENTRYID have not already been prepared, if so skip
            // otherwise, compile all the events/fids/records into a single template, and send that.
            // this will involve pre-processing the tags in the templates, since each entry might have its own values that are being inserted
            if(!isset($email)) {
                $email = $array['email'];
            }
            $criteria = new Criteria('email', $email);
            $targetUsers = $memberHandler->getUsers($criteria);
            $targetUser = $targetUsers[0];
            $fid = $array['fid'];
            $extra_tags = unserialize($array['extra_tags']);
            $event = $array['event'];
            if(!$array['mailTemplate']) {
                switch($event) {
                    case("new_entry"):
                        $thisMailTemplate = 'form_newentry.tpl';
                        break;  
                    case("update_entry"):
                        $thisMailTemplate = 'form_upentry.tpl';
                        break;
                    case("delete_entry"):
                        $thisMailTemplate = 'form_delentry.tpl';
                }
            } else {
                $thisMailTemplate = substr($array['mailTemplate'], -4) == ".tpl" ? $array['mailTemplate'] : $array['mailTemplate'] . ".tpl";
            }
            $revisionDescriptor = ":";
            switch($event) {
                case("new_entry"):
                    $revisionDescriptor = "added:";
                    break;  
                case("update_entry"):
                    $revisionDescriptor = "changed:";
                    break;
            }
            $thisMailTemplate = file_get_contents(XOOPS_ROOT_PATH."/modules/formulize/language/".$xoopsConfig['language']."/mail_template/".$thisMailTemplate);
            $revisionsNeeded = false;
            $groupTitle = "";
            if (strstr($thisMailTemplate, "{")) {
                // need to isolate {GROUP_TITLE}{/GROUP_TITLE} if any
                $groupTitle = "";
                if(strstr($thisMailTemplate, "{GROUP_TITLE}")) {
                    $startPos = strpos($thisMailTemplate,"{GROUP_TITLE}")+13;
                    $endPos = strpos($thisMailTemplate,"{/GROUP_TITLE}")-13;
                    $groupTitle = substr($thisMailTemplate,$startPos,$endPos);
                    foreach ($extra_tags as $tag=>$value) {
                        $groupTitle = str_replace("{".$tag."}",$value, $groupTitle); // Sub in extra tags
                    }
                }
                // Also generate and sub in revision history, if applicable
                $revisionsNeeded = strstr($thisMailTemplate, "{REVISION_HISTORY}") ? true : false;
                if($revisionsNeeded) {
                    $revisionHistory = array();
                }
                $priorRevisionLog = array();
                foreach ($extra_tags as $tag=>$value) {
                    $thisMailTemplate = str_replace("{".$tag."}",$value, $thisMailTemplate); // Sub in extra tags
                    $elementHandle = "";
                    if(substr($tag, 0, 8)=="ELEMENT_") {
                        $elementHandle = strtolower(str_replace("ELEMENT_", "", $tag));
                    }
                    if($revisionsNeeded AND $elementHandle AND $elementHandler->isElementVisibleForUser($elementHandle, $targetUser) AND (($event == "update_entry" AND isset($extra_tags['REVISION_'.$tag]) AND $extra_tags['REVISION_'.$tag] != $value) OR ($event == "new_entry" AND ($value != "" OR is_numeric($value))))) {
                        $elementHandle = strtolower(str_replace("ELEMENT_", "", $tag));
                        $elementObject = $elementHandler->get($elementHandle);
                        $capOrColHead = $elementObject->getVar('ele_colhead') ? $elementObject->getVar('ele_colhead') : $elementObject->getVar('ele_caption');
                        switch($event) {
                            case "update_entry":
                                $value = (!is_numeric($value) AND $value == "") ? "[blank]" : $value;
                                $revValue = strip_tags(htmlspecialchars_decode($extra_tags['REVISION_'.$tag], ENT_QUOTES));
                                $revValue = (!is_numeric($revValue) AND $revValue == "") ? "[blank]" : $value;
                                if($revValue != strip_tags(htmlspecialchars_decode($value, ENT_QUOTES))) {
                                    $revision = "\t".$capOrColHead." $revisionDescriptor ".$revValue." -> ".strip_tags(htmlspecialchars_decode($value, ENT_QUOTES));
                                } else {
                                    continue;
                                }
                                break;
                            case "new_entry":
                                $revision = "\t".$capOrColHead." $revisionDescriptor ".strip_tags(htmlspecialchars_decode($value, ENT_QUOTES));
                                break;
                        }
                        if($priorRevisionLog[$capOrColHead] != $revision) {
                            if($groupTitle) { // stored revisions for grouped messages for later
                                $groupedMessages[$groupTitle]['revisionHistory'][] = $revision;
                            } else {
                                $revisionHistory[] = $revision;
                            }
                        }
                        $priorRevisionLog[$capOrColHead] = $revision;
                    }
                }
                $appendMessage = true;
                if($revisionsNeeded AND !$groupTitle) { // regular message, with revisions
                    $thisMailTemplate = str_replace("{REVISION_HISTORY}", implode("\n", $revisionHistory), $thisMailTemplate);
                } elseif($groupTitle) { // grouped message, store for later, don't append right now
                    $groupedMessages[$groupTitle]['theseMailTemplates'][] = $thisMailTemplate;
                    $thisMailTemplate = "";
                } 
            }
            if($thisMailTemplate) { // add non-grouped messages to the mail template
                $mailTemplate .= $thisMailTemplate."\n\n---\n\n";
            }
            $ids[] = $array['digest_id'];
        }
        // process grouped messages and add to the mail template
        foreach($groupedMessages as $groupTitle=>$messageTexts) {
            if(!$groupTitle) {
                continue;
            }
            $groupedMailTemplate = "";
            $revisionHistory = $messageTexts['revisionHistory'];
            $groupTitleReplaced = false;
            $revisionsReplaced = false;
            foreach($messageTexts['theseMailTemplates'] as $thisMailTemplate) {
                // replace the group title and revision history the first time we encounter each
                // erase all subsequent occurences of the group title and revision history
                // append any other text in the mailTemplate if any, ignore messages that reduce to only whitespace
                if(strstr($thisMailTemplate, "{GROUP_TITLE}")) {
                    $startPos = strpos($thisMailTemplate,"{GROUP_TITLE}");
                    $endPos = strpos($thisMailTemplate,"{/GROUP_TITLE}");
                    if($groupTitleReplaced) {
                        $replacementString = "";
                    } else {
                        $replacementString = $groupTitle;
                        $groupTitleReplaced = true;
                    }
                    $thisMailTemplate = substr_replace($thisMailTemplate,$replacementString,$startPos,$endPos+14);
                }
                if(strstr($thisMailTemplate, "{REVISION_HISTORY}")) {
                    if($revisionsReplaced) {
                        $replacementString = "";
                    } else {
                        $replacementString = implode("\n", $revisionHistory);
                        $revisionsReplaced = true;
                    }
                    $thisMailTemplate = str_replace("{REVISION_HISTORY}", $replacementString, $thisMailTemplate);
                }
                if(trim($thisMailTemplate)) {
                    $groupedMailTemplate .= $thisMailTemplate;
                }
            }
            $mailTemplate .= $groupedMailTemplate."\n\n---\n\n";
        }
        $mailSubject = $xoopsConfig['sitename']." "._formulize_DE_NOT_DIGEST_SUBJECT." - ".date(_SHORTDATESTRING);
        file_put_contents(XOOPS_ROOT_PATH."/modules/formulize/language/".$xoopsConfig['language']."/mail_template/digestTemplate.tpl", $mailTemplate);
        sendNotificationToEmail($email, "", "", $mailSubject, 'digestTemplate.tpl');
        unset($email);
        if(count($ids)>0) {
            $sql = "DELETE FROM ".$xoopsDB->prefix("formulize_digest_data")." WHERE digest_id IN (".implode(",",$ids).")";
            $res = $xoopsDB->queryF($sql);
        } else {
            break; // we didn't find any more data to send
        }
    }
}

// check how much time has elapsed since we started the script, and since the last message was sent
// figure out if we have enough time left to send another message.
// SAME IN NOTIFY.PHP
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