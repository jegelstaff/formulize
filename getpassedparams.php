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

// GET THE HEADERS (AND THEN USE THEM TO GET INFO PASSED BACK FROM FORM
$reqFieldsJwe = array();
// check to see if the user specified different fields
if($_POST['colchange'] == _formulize_CHANGE)
{
	$fieldsjweindexer=0;
	foreach($_POST['allformcaplist'] as $userSelectedHeader)
	{
		$reqFieldsJwe[$fieldsjweindexer] = stripslashes($userSelectedHeader);
		$fieldsjweindexer++;
	}
}
elseif(isset($_POST['hiddencap1'])) // check to see if current headers were passed back from the form
{
	$hiddencapmarker = 1;
	$hiddencheckstring = "hiddencap" . $hiddencapmarker;
	$fieldsjweindexer=0;
	while($_POST[$hiddencheckstring])
	{
		$reqFieldsJwe[$fieldsjweindexer] = stripslashes($_POST[$hiddencheckstring]);
		$fieldsjweindexer++;
		$hiddencapmarker++;
		$hiddencheckstring = "hiddencap" . $hiddencapmarker;
	}
}
else // otherwise rely on the headerlist...
{

$headerlistQueryJwe = "SELECT headerlist FROM " . $xoopsDB->prefix("form_id") . " WHERE id_form=$id_form";
$resultheaderlistFieldQueryJwe = mysql_query($headerlistQueryJwe);
// if there are rows in the result...ie: there are Header fields
if(mysql_num_rows($resultheaderlistFieldQueryJwe)>0)
{
	while ($rowjwe = mysql_fetch_row($resultheaderlistFieldQueryJwe))
	{
		$reqFieldsJwe = explode("*=+*:", $rowjwe[0]); 
		array_shift($reqFieldsJwe);
	}
}

if(count($reqFieldsJwe)==0) // if no header fields specified, then....
{
// GATHER REQUIRED FIELDS FOR THIS FORM...
$reqFieldQueryJwe = "SELECT ele_caption FROM " . $xoopsDB->prefix("form") . " WHERE ele_req=1 AND id_form=$id_form";
$resultReqFieldQueryJwe = mysql_query($reqFieldQueryJwe);
// if there are rows in the result...ie: there are required fields
if(mysql_num_rows($resultReqFieldQueryJwe)>0)
{
	while ($rowjwe = mysql_fetch_assoc($resultReqFieldQueryJwe))
	{
		$reqFieldsJwe[] = $rowjwe["ele_caption"];
	}
}
else
{
// IF there are no required fields THEN ... go with first field 
$firstFieldQueryJwe = "SELECT ele_caption FROM " . $xoopsDB->prefix("form") . " WHERE id_form=$id_form GROUP BY id_form";
$resultFirstFieldQueryJwe = mysql_query($firstFieldQueryJwe);

while ($rowjwe = mysql_fetch_assoc($resultFirstFieldQueryJwe))
{
	$reqFieldsJwe[] = $rowjwe["ele_caption"];
}

} // end else covering case with no required fields
} // end else covering no headerlist fields.

} // end else that gets us to look at the headerlist (instead of use specified fields)

// CODE TO HANDLE READING DATA SENT FROM THE VIEW-ENTRIES REPORTING SECTION

	//Grab values from the _POST array that have been sent from the reporting section
	if(isset($_POST['go']) OR isset($_POST['save']) OR isset($_POST['export']) OR isset($_POST['selectscope'])) // if reporting options were sent... 
	{
		// now go through the caption list to grab all the values sent back
		// then assign those values to be returned to the view entries page
		// and change the data sent to the view entries page to reflect the values sent back by the user

			// the global and or setting
			$globalandor = $_POST['globalandor'];

			array($sentscope);
			// need to grab the scope setting....
			$sentscope = $_POST['scopeselector'];
						
		$allcapindexer = 0;
		foreach($reqFieldsJwe as $thisformcap) // loop through all the captions
		{
			$pc = str_replace(" ", "_", $thisformcap); // replace the spaces
			$pc = str_replace(".", "_", $pc); // replace the periods


			// the search operator settings
			$search_type_check = $pc . "_search_type";
			if(isset($_POST[$search_type_check]))
			{
				$search_typeArray[$allcapindexer] = $_POST[$search_type_check];
			}
			else
			{
				$search_typeArray[$allcapindexer] = "";
			}

			// the search text settings
			$search_text_check = $pc . "_search_text";
			if(isset($_POST[$search_text_check]))
			{
				$search_textArray[$allcapindexer] = $_POST[$search_text_check];
				if($search_textArray[$allcapindexer] == 0);
				{
					// the "zeroSave" does not appear to be referenced anywhere, though could be part of a solution to the how-to-search-for-blanks problem.
					$search_textZeroSave[$allcapindexer] = "&*#+zero";
				}
			}
			else
			{
				$search_textArray[$allcapindexer] = "";
			}

			// the local andor settings
			$andor_check = $pc . "_andor";
			$andorArray[$allcapindexer] = $_POST[$andor_check];
			
			// the calculation settings -- IS AN ARRAY need to handle it differently
			$calc_type_check = $pc . "_calc_type";
			if(isset($_POST[$calc_type_check]))
			{
				array($intermediate_calc_typeArray);
				array($secondintermediate);
				$intermediate_calc_typeArray = $_POST[$calc_type_check];

				/*print_r($intermediate_calc_typeArray);
				print "<br><br>";*/
				if(in_array("sum", $intermediate_calc_typeArray))
				{
					$secondintermediate[0] = "\"sum\" selected>" . _formulize_SUM;
				}
				else
				{
					$secondintermediate[0] = "\"sum\">" . _formulize_SUM;
				}
				
									
				if(in_array("average", $intermediate_calc_typeArray))
				{
					$secondintermediate[1] = "\"average\" selected>" . _formulize_AVERAGE;
				}
				else
				{
					$secondintermediate[1] = "\"average\">" . _formulize_AVERAGE;
				}
	
				if(in_array("min", $intermediate_calc_typeArray))
				{
					$secondintermediate[2] = "\"min\" selected>" . _formulize_MINIMUM;

				}
				else
				{
					$secondintermediate[2] = "\"min\">" . _formulize_MINIMUM;
				}
			
				if(in_array("max", $intermediate_calc_typeArray))
				{
					$secondintermediate[3] = "\"max\" selected>" . _formulize_MAXIMUM;
				}
				else
				{
					$secondintermediate[3] = "\"max\">" . _formulize_MAXIMUM;
				}
				
				if(in_array("count", $intermediate_calc_typeArray))
				{
					$secondintermediate[4] = "\"count\" selected>" . _formulize_COUNT;
				}
				else
				{
					$secondintermediate[4] = "\"count\">" . _formulize_COUNT;
				}
			
				if(in_array("percent", $intermediate_calc_typeArray))
				{
					$secondintermediate[5] = "\"percent\" selected>" . _formulize_PERCENTAGES;
				}
				else
				{
					$secondintermediate[5] = "\"percent\">" . _formulize_PERCENTAGES;
				}
				$calc_typeArray[$allcapindexer] = $secondintermediate;			
			}
			else
			{
				$calc_typeArray[$allcapindexer] = "";
			}
			/*print "$thisformcap:<br>";
			print_r($calc_typeArray[$allcapindexer]);
			print "<br><br>";*/
			
			// the sort priority settings -- make all entries equal something!
			$sort_order_check = $pc . "_sort_order";
			if($_POST[$sort_order_check] != "none")
			{
				$sort_orderArray[$allcapindexer] = $_POST[$sort_order_check];
			}
			else
			{
				$sort_orderArray[$allcapindexer] = "";
			}


			// asc desc setting for sort options
			$sort_dir_check = $pc . "_sort_dir"; // setup the proper suffix 
			if(isset($_POST[$sort_dir_check])) 
			{
				$ascdscArray[$allcapindexer] = $_POST[$sort_dir_check];
			}
			else
			{
				$ascdscArray[$allcapindexer] = "";
			}		

		
			// increment counter for next caption
			$allcapindexer++;
		}
		// write all detected settings back to the template

/*		print "passback array checks:<br>";
		print_r($search_typeArray);
		print "<br>";
		print_r($search_textArray);
		print "<br>";
		print_r($andorArray);
		print "<br>";
		print_r($calc_typeArray);
		print "<br>";
		print_r($sort_orderArray);
		print "<br>";
		print_r($ascdscArray);
		print "<br>$globalandor<br><br>";*/
		
		$xoopsTpl->assign('captions_sort_dir', $ascdscArray); // asc desc sort settings
		$xoopsTpl->assign('captions_search_type', $search_typeArray);
		$xoopsTpl->assign('captions_search_text', $search_textArray);
		$xoopsTpl->assign('captions_andor', $andorArray);
		$xoopsTpl->assign('captions_calc_type', $calc_typeArray);
		$xoopsTpl->assign('captions_sort_order', $sort_orderArray);
		$xoopsTpl->assign('globalandor', $globalandor);
	}


?>