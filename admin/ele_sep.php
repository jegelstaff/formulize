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
##  Author of this file: Freeform Solutions					     ##
##  Project: Formulize                                                       ##
###############################################################################

if( !preg_match("/elements.php/", $_SERVER['PHP_SELF']) ){
	exit("Access Denied");
}
$options = array();
$opt_count = 0;

$rows = !empty($value[0]) ? $value[1] : $xoopsModuleConfig['ta_rows'];
$cols = !empty($value[0]) ? $value[2] : $xoopsModuleConfig['ta_cols'];
$rows = new XoopsFormText (_AM_ELE_ROWS, 'ele_value[1]', 3, 3, $rows);
$cols = new XoopsFormText (_AM_ELE_COLS, 'ele_value[2]', 3, 3, $cols);
$type = new XoopsFormCheckBox (_AM_ELE_TYPE, 'option', null);
$type->addOption ('centre', ' '._AM_ELE_CTRE.'<br />');
$type->addOption ('souligné', ' '._AM_ELE_SOUL.'<br />');
$type->addOption ('italique', ' '._AM_ELE_ITALIQ.'<br />');
$default = new XoopsFormTextArea(_AM_ELE_DEFAULT, 'ele_value[0]', $value[0], 5, 35);

$tab = array ("Noir"=>"#000000", "Marron"=>"#97694F", "Bleu"=>"#7093DB", "Rouge"=>"#e00000", "Vert"=>"#4A766E", "Rose"=>"#9F5F9F", "Jaune"=>"#ffff00", "Blanc"=>"#ffffff");
$couleur = new XoopsFormSelect (_AM_ELE_CLR, 'couleur', null, 5, false);
foreach ($tab as $cle=>$tab) {
	$couleur->addOption($tab, $cle);
}

$form->addElement($rows, 1);
$form->addElement($cols, 1);
$form->addElement($default);
$form->addElement($type);
$form->addElement ($couleur);
?>