<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2010 Freeform Solutions                  ##
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
##  Author of this file: Freeform Solutions                                  ##
##  URL: http://www.formulize.org                           ##
##  Project: Formulize                                                       ##
###############################################################################

// this function shows the new admin homepage in F4

$application_handler = xoops_getmodulehandler('applications', 'formulize');
$form_handler = xoops_getmodulehandler('forms', 'formulize');
$screen_handler = xoops_getmodulehandler('screen','formulize');
$gperm_handler = xoops_gethandler('groupperm');
$appObjects = $application_handler->getAllApplications();
$apps = array();
$foundForms = array();

foreach($appObjects as $thisAppObject) {
    $apps = readApplicationData($thisAppObject->getVar('appid'), $apps);
}
$apps = readApplicationData(0,$apps); // lastly, get forms that don't have an application

// refactoring possible to take advantage of simply gathering the applications and then interacting with the object in the template
// but tricky to get it working with the current way things are passed through the accordion template using conventional names
// also don't want to split how we handle applications vs forms with no app
//$xoopsTpl->assign('applications', xoops_getmodulehandler('applications', 'formulize')->getAllApplications());
//$xoopsTpl->assign('extra_forms', xoops_getmodulehandler('forms', 'formulize')->getFormsByApplication(0));

$adminPage['apps'] = $apps;
$adminPage['template'] = "db:admin/home2.html";

$breadcrumbtrail[1]['text'] = "Home";

function readApplicationData($aid, $apps) {
    static $i = 1;
    global $form_handler, $application_handler, $gperm_handler, $screen_handler, $xoopsUser;
    $groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
    if ($aid == 0) {
        $apps[$i]['name'] = _AM_APP_FORMWITHNOAPP;
        $apps[$i]['content']['description'] = "";
    } else {
        $thisAppObject = $application_handler->get($aid);
        $apps[$i]['name'] = "Application: ".$thisAppObject->getVar('name');
        $apps[$i]['content']['description'] = $thisAppObject->getVar('description');
    }
    $apps[$i]['content']['aid'] = $aid;
    $formObjects = $form_handler->getFormsByApplication($aid);
    $x = 0;
    foreach($formObjects as $thisFormObject) {
        $fid = $thisFormObject->getVar('id_form');
        // check if the user has edit permission on this form
        if (!$gperm_handler->checkRight("edit_form", $fid, $groups, getFormulizeModId())) {
            continue;
        }
        $hasDelete = $gperm_handler->checkRight("delete_form", $fid, $groups, getFormulizeModId());
        $apps[$i]['content']['forms'][$x]['fid'] = $fid;
        $apps[$i]['content']['forms'][$x]['name'] = $thisFormObject->getVar('title');
        $apps[$i]['content']['forms'][$x]['hasdelete'] = $hasDelete;
        $apps[$i]['content']['forms'][$x]['lockedform'] = $thisFormObject->getVar('lockedform');
        $apps[$i]['content']['forms'][$x]['istableform'] = $thisFormObject->getVar('tableform');
        $defaultFormScreen = $thisFormObject->getVar('defaultform');
        $defaultListScreen = $thisFormObject->getVar('defaultlist');
        $defaultFormObject = $screen_handler->get($defaultFormScreen);
        $defaultListObject = $screen_handler->get($defaultListScreen);
        if (is_object($defaultFormObject)) {
            $defaultFormName = $defaultFormObject->getVar('title');
            $apps[$i]['content']['forms'][$x]['defaultformscreenid'] = $defaultFormScreen;
            $apps[$i]['content']['forms'][$x]['defaultformscreenname'] = $defaultFormName;
        }
        if (is_object($defaultListObject)) {
            $defaultListName = $defaultListObject->getVar('title');
            $apps[$i]['content']['forms'][$x]['defaultlistscreenid'] = $defaultListScreen;
            $apps[$i]['content']['forms'][$x]['defaultlistscreenname'] = $defaultListName;
        }
        $apps[$i]['content']['forms'][$x]['form'] = $thisFormObject;
        $x++;
    }
    $apps[$i]['header'] = '<span class="formulize-toolbar right-toolbar">';
    if ($aid>0) {
        $apps[$i]['header'] .= '<a href="'.XOOPS_URL.'/modules/formulize/admin/ui.php?page=application&aid='.$aid.'&tab=settings"><i class="icon-config"></i> Settings</a>';
    }
    // menu entries link does not work!!  can't pass names with spaces?
    $apps[$i]['header'] .= '<a href="'.XOOPS_URL.'/modules/formulize/admin/ui.php?page=application&aid='.$aid.'&tab=forms"><i class="icon-form"></i> Forms</a>
        <a href="'.XOOPS_URL.'/modules/formulize/admin/ui.php?page=application&aid='.$aid.'&tab=screens"><i class="icon-screen"></i> Screens</a>
        <a href="'.XOOPS_URL.'/modules/formulize/admin/ui.php?page=application&aid='.$aid.'&tab=relationships"><i class="icon-connection"></i> Relationships</a>
        <a href="'.XOOPS_URL.'/modules/formulize/admin/ui.php?page=application&aid='.$aid.'&tab=menu%20entries"><i class="icon-menu"></i> Menu Entries</a>
        <a href="'.XOOPS_URL.'/modules/formulize/admin/ui.php?page=export&aid='.$aid.'"><i class="icon-download"></i> Export (beta!)</a>
        <a href="'.XOOPS_URL.'/modules/formulize/admin/ui.php?page=form&aid='.$aid.'&tab=settings&fid=new"><i class="icon-add"></i> Add a Form</a>';
    if ($aid>0) {
        $apps[$i]['header'] .= '<a href="" class="deleteapplink" target="'.$aid.'"><i class="icon-delete"></i> Delete</a>';
    }
    $apps[$i]['header'] .= '</span>';
    $i++;
    return $apps;
}
