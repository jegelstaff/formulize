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

// ***** IMPORTANT LOOP THAT PREPARES ENTRIES FOR DISPLAY ON VIEW ENTRIES PAGE *******
	// Loop alters display of entries to suit printing to screen
	$fieldnamecounter = 0;
	foreach($selvals as $selvalstostripfrom)
	{

		//modify a linked selectbox to display correctly.
		//1. identify values that are like this
		//2. query what their actual text is right now
		//3. reformat as text[standard delimiter]text[starndard delimiter]text, etc
		if(strstr($selvalstostripfrom, "#*=:*"))
		{
			array($templinkedvals);
			array_splice($templinkedvals, 0);
			array($templinkedvals2);
			array_splice($templinkedvals2, 0);
			$templinkedvals = explode("#*=:*", $selvalstostripfrom);
			$templinkedvals2 = explode("[=*9*:", $templinkedvals[2]);
			$selvalstostripfrom = ''; // reset string so we can write it again.

			/*print "templinkedvals for display: ";
			print_r($templinkedvals2);*/
			
			foreach($templinkedvals2 as $anentry)
			{
				$textq = "SELECT ele_value FROM " . $xoopsDB->prefix("form_form") . " WHERE ele_id=$anentry GROUP BY ele_value ORDER BY ele_value";
				$restextq = mysql_query($textq);
				$rowtextq = mysql_fetch_row($restextq);
				$selvalstostripfrom = $selvalstostripfrom . "*=+*:" . $rowtextq[0];
			}
		}


		//sql to get is yes/no
		if($gscopeparam)
		{
			$isyesnoquestion = "SELECT ele_type FROM " . $xoopsDB->prefix("form_form") . " WHERE id_form=$id_form AND ele_value=$selvalstostripfrom AND ele_caption = \"$reqFieldsJwe[$fieldnamecounter]\" AND ($gscopeparam) ORDER BY id_req";
		}
		else
		{
			$isyesnoquestion = "SELECT ele_type FROM " . $xoopsDB->prefix("form_form") . " WHERE id_form=$id_form AND ele_value=$selvalstostripfrom AND ele_caption = \"$reqFieldsJwe[$fieldnamecounter]\" AND uid=$uid ORDER BY id_req";
		}
		$resisyesno = mysql_query($isyesnoquestion);
		
		while ($rowisyesno = mysql_fetch_row($resisyesno))
		{
			if($rowisyesno[0] == "yn") // if we've found one
			{
				if($selvalstostripfrom == "1")
				{
					$selvalstostripfrom = _formulize_TEMP_QYES;
				}
				elseif($selvalstostripfrom == "2")
				{
					$selvalstostripfrom = _formulize_TEMP_QNO;
				}
				else
				{
					$selvalstostripfrom = "";
				}
				break; // we're done
			}
		} 

		$placeholderVals = stripslashes($selvalstostripfrom);
		//and remove any leading *=+*: while we're at it...
		if(substr($placeholderVals, 0, 5) == "*=+*:")
		{
			$placeholderVals = substr_replace($placeholderVals, "", 0, 5); 
		}
		//print "*$placeholderVals*<br>";
		$tempValsJwe[] = $placeholderVals;
		$fieldnamecounter++;
		if($fieldnamecounter == count($reqFieldsJwe)) // reset the fieldname counter if we've reached the end of a row.
		{
			$fieldnamecounter = 0;
		}
	}
	$selvals = $tempValsJwe;

//=================
?>