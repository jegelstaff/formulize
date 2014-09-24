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
##  URL: http://www.freeformsolutions.ca/formulize                           ##
##  Project: Formulize                                                       ##
###############################################################################

// this file gets all the data about applications, so we can display the Settings/forms/relationships tabs for applications

include_once XOOPS_ROOT_PATH."/modules/formulize/include/functions.php";

if(0 == $aid = intval($_GET['aid'])) {
  $appName = "Forms with no app";
} else {
  $application_handler = xoops_getmodulehandler('applications', 'formulize');
  $appObject = $application_handler->get($aid);
  $appName = $appObject->getVar('name');
}

// retrieve the names and ids of all forms, and create the form options for the Add Form section
$formsq = "SELECT id_form, desc_form FROM " . $xoopsDB->prefix("formulize_id") . " ORDER BY desc_form";
$res = $xoopsDB->query($formsq);
$i = 0;
while($array = $xoopsDB->fetchArray($res)) {
  $common['formoptions'][$i]['value'] = $array['id_form'];
  $common['formoptions'][$i]['name'] = $array['desc_form'];
  $i++;
}

$breadcrumbtrail[1]['url'] = "page=home";
$breadcrumbtrail[1]['text'] = "Home";
$breadcrumbtrail[2]['url'] = "page=application&aid=$aid&tab=relationships";
$breadcrumbtrail[2]['text'] = $appName;

if($_GET['frid'] != "new") {
  $relationship_id = intval($_GET['frid']);
  $framework_handler = xoops_getmodulehandler('frameworks', 'formulize');
  $relationship = $framework_handler->get($relationship_id);
  $common['relationship'] = $relationship;
  $breadcrumbtrail[3]['text'] = $relationship->name;
} else {
    // new framework
  $common['name'] = "New Relationship";
  $relationship_id = "new";
  $breadcrumbtrail[3]['text'] = "New Relationship";
}

// common values should be assigned to all tabs
$common['frid'] = $relationship_id;
$common['aid'] = $aid;

$adminPage['tabs'][1]['name'] = "Relationship Settings";
$adminPage['tabs'][1]['template'] = "db:admin/relationship_settings.html";
$adminPage['tabs'][1]['content'] = $common;
$adminPage['needsave'] = true;
