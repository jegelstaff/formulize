<?php
###############################################################################
##             Formulaire - Information submitting module for XOOPS          ##
##                    Copyright (c) 2003 NS Tai (aka tuff)                   ##
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
##  Author of this file: NS Tai (aka tuff)                                   ##
##  URL: http://www.brandycoke.com/                                          ##
##  Project: Formulaire                                                      ##
###############################################################################
if( !preg_match("/elements.php/", $_SERVER['PHP_SELF']) ){
	exit("Access Denied");
}
if( empty($addopt) && !empty($ele_id) ){
	$ele_value = $element->getVar('ele_value');
}
$ele_size = !empty($ele_value[0]) ? $ele_value[0] : 1;
$size = new XoopsFormText(_AM_ELE_SIZE, 'ele_value[0]', 3, 2, $ele_size);
$allow_multi = empty($ele_value[1]) ? 0 : 1;
$multiple = new XoopsFormRadioYN(_AM_ELE_MULTIPLE, 'ele_value[1]', $allow_multi);

$options = array();
$opt_count = 0;
if( empty($addopt) && !empty($ele_id) ){
	$keys = array_keys($ele_value[2]);
	for( $i=0; $i<count($keys); $i++ ){
		$v = $myts->makeTboxData4PreviewInForm($keys[$i]);
		$options[] = addOption('ele_value[2]['.$opt_count.']', 'checked['.$opt_count.']', $v, 'check', $ele_value[2][$keys[$i]]);
		$opt_count++;
	}
/*	while( $var = each($ele_value[2]) ){
		$v = $myts->makeTboxData4PreviewInForm($var['key']);
		$t1 = new XoopsFormCheckBox('', 'checked['.$opt_count.']', $var['value']);
		$t1->addOption(1, ' ');
		$t2 = new XoopsFormText('', 'ele_value[2]['.$opt_count.']', 40, 255, $v);
		$t3 = new XoopsFormElementTray('');
		$t3->addElement($t1);
		$t3->addElement($t2);
//  		$t3 = new XoopsFormLabel('', $t1->render().$t2->render());
		$options[] = $t3;
		$opt_count++;
	}	*/
}else{
	if( !empty($ele_value[2]) ){
		while( $v = each($ele_value[2]) ){
			$v['value'] = $myts->makeTboxData4PreviewInForm($v['value']);
			if( !empty($v['value']) ){
		/*		$t1 = new XoopsFormCheckBox('', 'checked['.$opt_count.']', $checked[$v['key']]);
				$t1->addOption(1, ' ');
				$t2 = new XoopsFormText('', 'ele_value[2]['.$opt_count.']', 40, 255, $v['value']);
// 				$t3 = new XoopsFormElementTray('');
// 				$t3->addElement($t1);
// 				$t3->addElement($t2);
 				$t3 = new XoopsFormLabel('', $t1->render().$t2->render());
				$options[] = $t3;	*/
				
				$options[] = addOption('ele_value[2]['.$opt_count.']', 'checked['.$opt_count.']', $v['value'], 'check', $checked[$v['key']]);
				$opt_count++;
			}
		}
	}
	$addopt = empty($addopt) ? 2 : $addopt;
	for( $i=0; $i<$addopt; $i++ ){
		$options[] = addOption('ele_value[2]['.$opt_count.']', 'checked['.$opt_count.']');
		$opt_count++;
	}
}

$add_opt = addOptionsTray();
$options[] = $add_opt;

$opt_tray = new XoopsFormElementTray(_AM_ELE_OPT, '<br />');
$opt_tray->setDescription(_AM_ELE_OPT_DESC._AM_ELE_OPT_DESC1);
for( $i=0; $i<count($options); $i++ ){
	$opt_tray->addElement($options[$i]);
}
$form->addElement($size, 1);
$form->addElement($multiple);
$form->addElement($opt_tray);

// print_r($options);
// 	echo '<br />';

?>