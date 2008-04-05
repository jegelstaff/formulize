<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2007 Freeform Solutions                  ##
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

// write out javascript necessary for sticking the current caption into the Formula box
?>

<script type="text/javascript">
    function writeCaptionToBox(formElement) {
        for (var i=0; i < formElement.options.length; i++) {
		if (formElement.options[i].selected) {
        		window.document.getElementById('ele_value[0]').value = window.document.getElementById('ele_value[0]').value + '"' + formElement.options[i].value + '"';
		}
	}
    }
</script>

<?php

$options = array();
$allColList = getAllColList($id_form);
foreach($allColList[$id_form] as $thisCol) {
    if($thisCol['ele_colhead'] != "") {
	$options[trans($thisCol['ele_colhead'])] = printSmart(trans($thisCol['ele_colhead']));
    } else {
	$options[trans(strip_tags($thisCol['ele_caption']))] = printSmart(trans(strip_tags($thisCol['ele_caption'])));
    }
}

$formulaBox = new XoopsFormTextArea(_AM_ELE_DERIVED_CAP, 'ele_value[0]', $value[0], 5, 35);
$listOfElements = new XoopsFormSelect("", 'listofelements');
$listOfElements->addOptionArray($options);
$listOfElements_output = $listOfElements->render() . "\n<br />\n<input type=button name=addele value=\"" . _AM_ELE_DERIVED_ADD . "\" onclick=\"javascript:writeCaptionToBox(this.form.listofelements);\"></input>";
$formulaBox->setDescription($listOfElements_output . "<br /><br />" . _AM_ELE_DERIVED_DESC);

$form->addElement($formulaBox);

?>