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

// add in javascript for clearing radio button default selections
// jwe 01/06/05

		print "<script type='text/javascript'>\n";
		print "<!--//\n";
		print "	function clearDefaults() {\n";
		print " 	var el_collection=eval(\"document.forms.form_ele.ele_value\")\n";
		print "	for (c=0;c<el_collection.length;c++)\n";
		print "	el_collection[c].checked=false\n";
		print "	}\n";
		print "//--></script>\n\n";
// END OF JAVASCRIPT FUNCTION


// altered to better handle default setting -- jwe 7/28/04

if( !empty($ele_id) ){
	if( $value['_YES'] == 1 ){
		$selected = '_YES';
	}elseif( $value['_NO'] == 1) {
		$selected = '_NO';
	}
}
if($selected)
{
	$options = new XoopsFormRadio(_AM_ELE_DEFAULT, 'ele_value', $selected);
}
else
{
	$options = new XoopsFormRadio(_AM_ELE_DEFAULT, 'ele_value');
}	
$options->addOption('_YES', _YES);
$options->addOption('_NO', _NO);

$form->addElement($options);

// next four lines added to provide the clearing-defaults UI 
$clearDefault = new XoopsFormButton('', 'cleardef', _AM_CLEAR_DEFAULT, 'button');
$clearDefaultExtra = "onclick='clearDefaults()'";
$clearDefault->setExtra($clearDefaultExtra);
$form->addElement($clearDefault);


?>