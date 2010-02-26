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

// need to listen for $_GET['aid'] later so we can limit this to just the application that is requested
$aid = intval($_GET['aid']);
$appName = "All forms"; // needs to be set based on aid in future
$framework_handler = xoops_getmodulehandler('frameworks', 'formulize');
$form_handler = xoops_getmodulehandler('forms', 'formulize');
$formObjects = $form_handler->getAllForms(); // returns array of objects


// $forms array is going to be used to populate accordion sections, so it must contain the following:
// a 'name' key and a 'content' key for each form that is found
// Name will be the heading of the section, content is data used in the template for each section
$forms = array();
$allRelationships = array();
$i = 1; 
foreach($formObjects as $thisForm) {
	$fid = $thisForm->getVar('id_form');
  $forms[$i]['name'] = $thisForm->getVar('title');
  $forms[$i]['content']['fid'] = $fid;
  $i++;
	$allRelationships = array_merge($allRelationships, $framework_handler->getFrameworksByForm($fid)); // returns array of objects
}
$relationships = array();
$relationshipIndex = array();
$i = 1;
foreach($allRelationships as $thisRelationship) {
	$frid = $thisRelationship->getVar('frid');
	if(isset($relationshipIndex[$frid])) { continue; }
	$relationships[$i]['name'] = $thisRelationship->getVar('name');
	$relationships[$i]['content']['frid'] = $frid;
	$relationshipIndex[$frid] = true;
	$i++;
}


$common['aid'] = $aid;

// adminPage tabs sections must contain a name, template and content key
// content is the data the is available in the tab as $content.foo
// any declared sub key of $content, such as 'forms' will be assigned to accordions
// accordion content is available as $sectionContent.foo
$adminPage['tabs'][1]['name'] = "Forms";
$adminPage['tabs'][1]['template'] = "db:admin/application_forms.html";
$adminPage['tabs'][1]['content'] = $common;
$adminPage['tabs'][1]['content']['forms'] = $forms; 

$adminPage['tabs'][2]['name'] = "Settings";
$adminPage['tabs'][2]['template'] = "db:admin/application_settings.html";
$adminPage['tabs'][2]['content'] = $common;

$adminPage['tabs'][3]['name'] = "Relationships";
$adminPage['tabs'][3]['template'] = "db:admin/application_relationships.html";
$adminPage['tabs'][3]['content'] = $common;
$adminPage['tabs'][3]['content']['relationships'] = $relationships; 


$breadcrumbtrail[1]['url'] = "page=home";
$breadcrumbtrail[1]['text'] = "Home";
$breadcrumbtrail[2]['text'] = $appName;

