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
include_once XOOPS_ROOT_PATH.'/class/xoopsformloader.php';

$p = !empty($value[1]) ? $value[1] : $xoopsModuleConfig['weight'];

$pds = new XoopsFormElementTray (_AM_ELE_TAILLEFICH, '');
$pds->addElement (new XoopsFormText ('', 'ele_value[1]', 15, 15, $p));
$pds->addElement (new XoopsFormLabel ('', ' bits'));
$form->addElement ($pds);

$tab = array();
foreach ($value[2] as $t => $k) {
	foreach ($k as $c => $f){
		$tab[] = $value[2][$t]['value'];
	}
}

$mime = new XoopsFormCheckBox (_AM_ELE_TYPEMIME, 'ele_value[2]', $tab);
$mime->addOption('pdf',' pdf ');
$mime->addOption('doc',' doc ');
$mime->addOption('txt',' txt ');
$mime->addOption('gif',' gif ');
$mime->addOption('mpeg',' mpeg ');
$mime->addOption('jpg',' jpg ');
$form->addElement($mime);

$fichier = new XoopsFormFile (_AM_ELE_FICH, $ele_value[0], $ele_value[1]);	
$form->addElement ($fichier);


?>