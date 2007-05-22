<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2006 Freeform Solutions                  ##
##                Portions copyright (c) 2003 NS Tai (aka tuff)              ##
##                       <http://www.brandycoke.com/>                        ##
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
##  Author of this file: Freeform Solutions 					     ##
##  Project: Formulize                                                       ##
###############################################################################
if( !preg_match("/elements.php/", $_SERVER['PHP_SELF']) ){
	exit("Access Denied");
}

if( !empty($ele_id) ){
	$ele_value = $element->getVar('ele_value');
}

// five parts for this element:
// 1. heading
// 2. row captions
// 3. col captions
// 4. background shading
// 5. starting element

// 1. heading

$grid_heading = new XoopsFormElementTray(_AM_ELE_GRID_HEADING, "<br>");
$grid_heading_use_caption = new XoopsFormRadio('', 'ele_value[0]', $ele_value[0]);
$grid_heading_use_caption->addOption('caption', _AM_ELE_GRID_HEADING_USE_CAPTION);
$grid_heading_use_form = new XoopsFormRadio('', 'ele_value[0]', $ele_value[0]);
$grid_heading_use_form->addOption('form', _AM_ELE_GRID_HEADING_USE_FORM);
$grid_heading_none = new XoopsFormRadio('', 'ele_value[0]', $ele_value[0]);
$grid_heading_none->addOption('none', _AM_ELE_GRID_HEADING_NONE);
$grid_heading->addElement($grid_heading_use_caption);
$grid_heading->addElement($grid_heading_use_form);
$grid_heading->addElement($grid_heading_none);

// 2. row captions

$grid_row_captions = new XoopsFormTextArea(_AM_ELE_GRID_ROW_CAPTIONS, 'ele_value[1]', $ele_value[1]);
$grid_row_captions->setDescription(_AM_ELE_GRID_ROW_CAPTIONS_DESC);

// 3. col captions

$grid_col_captions = new XoopsFormTextArea(_AM_ELE_GRID_COL_CAPTIONS, 'ele_value[2]', $ele_value[2]);
$grid_col_captions->setDescription(_AM_ELE_GRID_COL_CAPTIONS_DESC);

// 4. background shading

$grid_background = new XoopsFormElementTray(_AM_ELE_GRID_BACKGROUND, "<br>");
$grid_background_hor = new XoopsFormRadio('', 'ele_value[3]', $ele_value[3]);
$grid_background_hor->addOption('horizontal', _AM_ELE_GRID_BACKGROUND_HOR);
$grid_background_ver = new XoopsFormRadio('', 'ele_value[3]', $ele_value[3]);
$grid_background_ver->addOption('vertical', _AM_ELE_GRID_BACKGROUND_VER);
$grid_background->addElement($grid_background_hor);
$grid_background->addElement($grid_background_ver);

// 5. starting element

$formulize_mgr =& xoops_getmodulehandler('elements', 'formulize');
$grid_elements_criteria = new Criteria();
$grid_elements_criteria->setSort('ele_order');
$grid_elements_criteria->setOrder('ASC');
$grid_elements = $formulize_mgr->getObjects2($grid_elements_criteria, $id_form);

include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";
foreach($grid_elements as $this_element) {
	$grid_start_options[$this_element->getVar('ele_id')] = $this_element->getVar('ele_colhead') ? printSmart(trans($this_element->getVar('ele_colhead'))) : printSmart(trans($this_element->getVar('ele_caption')));
}

$grid_start = new XoopsFormSelect(_AM_ELE_GRID_START, 'ele_value[4]', $ele_value[4], 1, false); // 1 and false are size and multiple
$grid_start->addOptionArray($grid_start_options);
$grid_start->setDescription(_AM_ELE_GRID_START_DESC);

// rollup elements into form

$form->addElement($grid_heading);
$form->addElement($grid_row_captions);
$form->addElement($grid_col_captions);
$form->addElement($grid_background);
$form->addElement($grid_start);


?>