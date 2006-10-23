<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2004 Freeform Solutions                  ##
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
##  Author of this file: Freeform Solutions and NS Tai (aka tuff) and others ##
##  URL: http://www.brandycoke.com/                                          ##
##  Project: Formulize                                                       ##
###############################################################################

if( !preg_match("/elements.php/", $_SERVER['PHP_SELF']) ){
	exit("Access Denied");
}

$size = !empty($value[0]) ? intval($value[0]) : $xoopsModuleConfig['t_width'];
$max = !empty($value[1]) ? intval($value[1]) : $xoopsModuleConfig['t_max'];
$size = new XoopsFormText(_AM_ELE_SIZE, 'ele_value[0]', 3, 3, $size);
$max = new XoopsFormText(_AM_ELE_MAX_LENGTH, 'ele_value[1]', 3, 3, $max);
$default = new XoopsFormTextarea(_AM_ELE_DEFAULT, 'ele_value[2]', stripslashes($value[2]), 5, 35);
$default->setExtra('wrap=off');
$default->setDescription(_AM_ELE_TEXT_DESC . _AM_ELE_TEXT_DESC2);

// added - start - August 22 2005 - jpc
$valueType = new XoopsFormSelect(_AM_ELE_TYPE, 'ele_value[3]', $value[3], 1, false);
$valueType->addOption(0, _AM_ELE_TYPE_STRING);
$valueType->addOption(1, _AM_ELE_TYPE_NUMBER);
$valueType->setDescription(_AM_ELE_TYPE_DESC);
// added - end - August 22 2005 - jpc

// added June 20 2006, jwe
$formlink = createFieldList($value[4], true); 

$form->addElement($size, 1);
$form->addElement($max, 1);
$form->addElement($default);

// added - start - August 22 2005 - jpc
$form->addElement($valueType);
// added - end - August 22 2005 - jpc

$form->addElement($formlink);
?>