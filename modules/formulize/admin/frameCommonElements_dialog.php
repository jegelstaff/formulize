<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2004 Freeform Solutions                  ##
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
##  Author of this file: Freeform Solutions                                  ##
##  Project: Formulize                                                       ##
###############################################################################

// This file handles the selection of elements that are meant to have common values between two forms

require_once "../../../mainfile.php";

// setup a smarty object that we can use for templating our own pages
require_once XOOPS_ROOT_PATH.'/class/template.php';
require_once XOOPS_ROOT_PATH.'/class/theme.php';
require_once XOOPS_ROOT_PATH.'/class/theme_blocks.php';
$xoopsThemeFactory = new xos_opal_ThemeFactory();
$xoTheme =& $xoopsThemeFactory->createInstance();
$xoopsTpl =& $xoTheme->template;


global $xoopsConfig, $xoopsDB;
// load the formulize language constants if they haven't been loaded already
if ( file_exists(XOOPS_ROOT_PATH."/modules/formulize/language/".$xoopsConfig['language']."/main.php") ) {
    include_once XOOPS_ROOT_PATH."/modules/formulize/language/".$xoopsConfig['language']."/main.php";
} else {
    include_once XOOPS_ROOT_PATH."/modules/formulize/language/english/main.php";
}

include_once XOOPS_ROOT_PATH . "/modules/formulize/class/forms.php";
include_once XOOPS_ROOT_PATH . "/modules/formulize/class/frameworks.php";

global $xoopsDB;

include_once XOOPS_ROOT_PATH . "/class/xoopsformloader.php";

$form1 = is_numeric($_GET['form1']) ? $_GET['form1'] : 0;
$form2 = is_numeric($_GET['form2']) ? $_GET['form2'] : 0;
$lid = is_numeric($_GET['lid']) ? $_GET['lid'] : 0;
$content['lid'] = $lid;


$formObj1 = new formulizeForm($form1, true); // true causes elements shown to no one, to be included
$formObj2 = new formulizeForm($form2, true);

$content['form1']['name'] = $formObj1->getVar('title');
$content['form2']['name'] = $formObj2->getVar('title');

$content['form1']['elements'] = generateElementList($formObj1);
$content['form2']['elements'] = generateElementList($formObj2);

$content['form1']['default'] = getDefault($lid, 1);
$content['form2']['default'] = getDefault($lid, 2);


$xoopsTpl->assign("content",$content);
$xoopsTpl->display("db:admin/relationship_common_values.html");


// THIS FUNCTION CREATES THE ARRAY OF ELEMENTS FOR USE IN THE LISTBOXES
function generateElementList($form) {
	$element_handler =& xoops_getmodulehandler('elements', 'formulize');
	foreach($form->getVar('elementsWithData') as $element) {
		$ele = $element_handler->get($element);
		if($ele->getVar('ele_type') != "ib" AND $ele->getVar('ele_type') != "areamodif" AND $ele->getVar('ele_type') != "subform"){
			$saveoptions[$ele->getVar('ele_id')] = $ele->getVar('ele_colhead') ? $ele->getVar('ele_colhead') : $ele->getVar('ele_caption');
		}
	}
	return $saveoptions;
}

function getDefault($lid, $order) {
    $link = new formulizeFrameworkLink($lid);
    if ($link->getVar('common')) {
        if ($order == 1) {
            return $link->getVar('key1');
        }
        if ($order == 2) {
            return $link->getVar('key2');
        }
    } else {
        return false;
    }
}
