<?

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
##  Author of this file: Freeform Solutions 					     ##
##  Project: Formulize                                                       ##
###############################################################################

// gather the default values if needed
//print "**viewentry check: $viewentry";
if($viewentry)
{

//replicate the veuid generation from above:
// print "Viewentry: $viewentry<br>";
// get the uid that belongs to the entry
$getveuid = "SELECT uid FROM " . $xoopsDB->prefix("form_form") . " WHERE id_req=$viewentry GROUP BY id_req";
$resgetveuid = $xoopsDB->query($getveuid);
$rowgetveuid = $xoopsDB->fetchRow($resgetveuid);
$veuid = $rowgetveuid[0]; // the uid that belongs to the entry
//print "VEUID is set: $veuid<br>";


$viewqueryjwe = "SELECT ele_caption, ele_value FROM " . $xoopsDB->prefix("form_form") . " WHERE id_req=$viewentry";
// print $viewqueryjwe; // debug line
$resultViewQueryJwe = mysql_query($viewqueryjwe);

array ($reqCaptionsJwe);
array ($reqValuesJwe);

while ($rowjwe = mysql_fetch_assoc($resultViewQueryJwe))
{
	//print_r($rowjwe);
	$reqCaptionsJwe[] = $rowjwe["ele_caption"];
	$reqValuesJwe[] = $rowjwe["ele_value"];
}

} // end of gathering the default values
// end of the ELSE is way at the bottom of the page, to encompass the other conditions.



// ---------------------------- end jwe mod

?>