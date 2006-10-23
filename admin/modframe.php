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

include("admin_header.php");

if ( file_exists("../language/".$xoopsConfig['language']."/main.php") ) {
	include "../language/".$xoopsConfig['language']."/main.php";
} else {
	include "../language/english/main.php";
}

xoops_cp_header();

$cf = $_GET['cf'];
if(!$cf)
{
	$cf = 0;
}

$op = $_GET['op']; // the switch that tells us what page we're on


function mainpage()
{
global $xoopsDB;
// javascript function to reload page everytime someone selects a new Framework to edit...
		print "<script type='text/javascript'>\n";
		print "<!--//\n";
		print "	function redrawpage(f) {\n";
//		print "	alert(f)\n";
		print "	if(f != 0) {\n";
		print "		window.location = 'modframe.php?op=edit&cf=' + f;\n";
		print "	}\n";
		print "	}\n";
		print "//--></script>\n\n";

// javascript function to handle deletion of a frame UI ...
		print "<script type='text/javascript'>\n";
		print "<!--//\n";
		print "	function delframe(f) {\n";
		print "	if(f != 0) {\n";
		print "		var answer = confirm ('" . _AM_CONFIRM_DEL_FF_FRAME . "')\n";
		print "		if (answer)\n";
		print "		{\n";
		print "			window.location = 'modframe.php?op=delete&cf=' + f;\n";
		print "		}\n";
		print "		else\n";
		print "		{\n";
		print "			return false;\n";
		print "		}\n";
		print "	}\n";
		print "	}\n";
		print "//--></script>\n\n";




	print "<table><tr><td><table class='outer' cellspacing='1'><tr><td class=head valign=top><form name=startframe action=modframe.php?op=new method=post>\n";
	print "<p><center><b>" . _AM_FRAME_NEW . "</b><br>";
	print "<nobr><INPUT TYPE=text name=framename value='" . _AM_FRAME_TYPENEWNAME . "' size=30><br>";
	print "<INPUT TYPE=SUBMIT class=formbutton name='" . _AM_FRAME_NEWBUTTON . "' value='" . _AM_FRAME_NEWBUTTON . "' id='" . _AM_FRAME_NEWBUTTON . "'></nobr></center></p>";
	print "</td></tr><tr><td class=head valign=top>\n";
	print "<p><center><b>" . _AM_FRAME_EDIT . "</b><br>";

	// gather and draw in the list of Frameworks

	$sql = "SELECT frame_name, frame_id FROM " . $xoopsDB->prefix("formulize_frameworks") . " ORDER BY frame_name";
	$res = $xoopsDB->query($sql);
	print "<SELECT name=currentFrame size=1 onChange='redrawpage(document.startframe.currentFrame.value)'>\n";
	if($xoopsDB->getRowsNum($res)>0) 
	{
	
		print "<option value='0'>" . _AM_FRAME_CHOOSE . "</option>\n";

		while($array = $xoopsDB->fetchArray($res))
		{
			$optiontext .= "<option value='" . $array['frame_id'] . "'>". $array['frame_name']. "</option>\n";
		}
		print "$optiontext";
	
	}
	else // no frameworks found, no result to the query
	{
		print "<option value='0' selected>" . _AM_FRAME_NONE . "</option>\n";
	} 
	print "</SELECT></center><br>&nbsp;</p></td></tr>\n";

	// delete a framework UI...

	print "<tr><td class=head valign=top>\n";
	print "<p><center><b>" . _AM_FRAME_DELETE . "</b><br>";

	// gather and draw in the list of Frameworks

	print "<SELECT name=deleteFrame size=1 onChange='delframe(document.startframe.deleteFrame.value)'>\n";
	if($xoopsDB->getRowsNum($res)>0) 
	{
	
		print "<option value='0'>" . _AM_FRAME_CHOOSE . "</option>\n";

		print "$optiontext";
	
	}
	else // no frameworks found, no result to the query
	{
		print "<option value='0' selected>" . _AM_FRAME_NONE . "</option>\n";
	} 
	print "</SELECT></center><br>&nbsp;</p></form></td></tr></table>\n";
	

	print '</td></tr><tr><td valign=top>';
	print '<center><a href="../admin/formindex.php">' . _AM_GOTO_MAIN . ' <br><img src="../images/formulize.gif" height=35></a></center></td></tr></table>';
}

// **********
//
// This function sets a series of global arrays which 
//
// **********

function getlinks() {

	global $xoopsDB;

	


	
	/*array($forms);
	$forms[] = $form1;
	$forms[] = $form2;

	// search for all the fields in the form that are linked FROM another form
	foreach($forms as $aform) {
		



	}*/
}

function modframe($cf)
{

	global $xoopsDB;

			array($hits);
			array($truehits);

	print "<script type='text/javascript'>\n";
	print "	function confirmdel() {\n";
	print "		var answer = confirm ('" . _AM_CONFIRM_DEL_FF_FORM . "')\n";
	print "		if (answer)\n";
	print "		{\n";
	print "			return true;\n";
	print "		}\n";
	print "		else\n";
	print "		{\n";
	print "			return false;\n";
	print "		}\n";
	print "	}\n";

	// checkForCommon added July 19 2006 -- jwe
	print "	function checkForCommon(Obj, form1, form2, lid) {\n";
	print "		for (var i=0; i < Obj.options.length; i++) {\n";
	print "			if(Obj.options[i].selected && Obj.options[i].value == 'common') {\n";
	print "				showPop('" . XOOPS_URL . "/modules/formulize/admin/frameCommonElements.php?form1=' + form1 + '&form2=' + form2 + '&lid=' + lid);\n";
	print "			}\n";
	print "		}\n";
	print "	}\n";

?>

	function showPop(url) {

	if (window.popup == null) {
		popup = window.open(url,'popup','toolbar=no,scrollbars=yes,resizable=yes,width=800,height=450,screenX=0,screenY=0,top=0,left=0');
      } else {
		if (window.popup.closed) {
			popup = window.open(url,'popup','toolbar=no,scrollbars=yes,resizable=yes,width=800,height=450,screenX=0,screenY=0,top=0,left=0');
            } else {
			window.popup.location = url;              
		}
	}
	window.popup.focus();

}

<?php

	print "</script>\n";


	//retrieve name for the current framework

	$nameq = "SELECT frame_name FROM " . $xoopsDB->prefix("formulize_frameworks") . " WHERE frame_id=$cf";
	$res = $xoopsDB->query($nameq);
	$array = $xoopsDB->fetchArray($res);
	$framename = $array['frame_name'];

	// retrieve the names and ids of all forms

	$formsq = "SELECT id_form, desc_form FROM " . $xoopsDB->prefix("formulize_id");
	$res = $xoopsDB->query($formsq);
	while($array = $xoopsDB->fetchArray($res)) {
		$form_names[] = $array['desc_form'];
		$form_ids[] = $array['id_form'];
	}

	// sort forms by name	
	array_multisort($form_names, $form_ids);

	//create the form options for the Add Form section

	$formoptions = "";
	for($i=0;$i<count($form_names);$i++) {
		$formoptions .= "<option value='" . $form_ids[$i] . "'>" . $form_names[$i] . "</option>\n";
	}

	// **************
	// GET THE MASTER LIST OF LINKS
	// **************

	// initialize the class that can read the ele_value field
	$formulize_mgr =& xoops_getmodulehandler('elements');

	// get a list of all the linked select boxes since we need to know if any fields in these two forms are the source for any links

	$getlinksq = "SELECT id_form, ele_caption, ele_id FROM " . $xoopsDB->prefix("formulize") . " WHERE ele_type=\"select\" AND ele_value LIKE '%#*=:*%' ORDER BY id_form";
	// print "$getlinksq<br>";
	$resgetlinksq = $xoopsDB->query($getlinksq);
	while ($rowlinksq = $xoopsDB->fetchRow($resgetlinksq))
	{
		//print_r($rowlinksq);
		//print "<br>";
		$target_form_ids[] = $rowlinksq[0];
		$target_caps[] = $rowlinksq[1];
		$target_ele_ids[] = $rowlinksq[2];

		// returns an object containing all the details about the form
		$elements =& $formulize_mgr->getObjects2($criteria,$rowlinksq[0]);
	
		// search for the elements where the link exists
		foreach ($elements as $e) {
			$ele_id = $e->getVar('ele_id');
			// if this is the right element, then proceed and get the source of the link
			if($ele_id == $rowlinksq[2]) {
				$ele_value = $e->getVar('ele_value');
				$details = explode("#*=:*", $ele_value[2]);
				$source_form_ids[] = $details[0];
				$source_caps[] = $details[1];

				//get the element ID for the source we've just found
				$sourceq = "SELECT ele_id FROM " . $xoopsDB->prefix("formulize") . " WHERE ele_caption = '" . addslashes($details[1]) . "' AND id_form = '$details[0]'";
				if($ressourceq = $xoopsDB->query($sourceq)) {
					$rowsourceq = $xoopsDB->fetchRow($ressourceq);
					$source_ele_ids[] = $rowsourceq[0];
				} else {
					print "Error:  Query failed.  Searching for element ID for the caption $details[1] in form $details[0]";
				}
			}
		}
	}

	// Arrays now set as follows:
	// target_form_ids == the ID of the form where the current linked selectbox resides
	// target_caps == the caption of the current linked selectbox
	// target_ele_ids == the element ID of the current linked selectbox
	// source_form_ids == the ID of the form where the source for the current linked selectbox resides
	// source_caps == the caption of the source for the current linked selectbox
	// source_ele_ids == the element ID of the source for the current linked selectbox

	// each index in those arrays denotes a distinct linked selectbox

	// example:
	// target_form_ids == 11
	// target_caps == Link to Name
	// target_ele_ids == 22
	// source_form_ids == 10
	// source_caps == Name
	// source_ele_ids == 20

	// debug code:
	//	for($i=0;$i<count($target_form_ids);$i++) {
	//		print "Form ID: $target_form_ids[$i]<br>Caption: $target_caps[$i]<br>Element ID: $target_ele_ids[$i]<br>Source Form: $source_form_ids[$i]<br>Source Caption: $source_caps[$i]<br>Source Element ID: $source_ele_ids[$i]<br><br>";
	//	}

	// need to get list of all forms/links in the current framework

	$getFrameInfoq = "SELECT fl_id, fl_form1_id, fl_form2_id, fl_key1, fl_key2, fl_relationship, fl_unified_display, fl_common_value FROM " . $xoopsDB->prefix("formulize_framework_links") . " WHERE fl_frame_id = '$cf' ORDER BY fl_id";
	$res = $xoopsDB->query($getFrameInfoq);
	while ($array = $xoopsDB->fetchArray($res)) {
		$links[] = $array;
	}

	//print_r($links);

	
	
	// now build the page 

	print "<form name='modframe' action='modframe.php?op=addlink&cf=$cf' method=post>\n";

	print "<h3>" . _AM_FRAME_NAMEOF . "&nbsp;&nbsp;<i>$framename</i></h3>\n";

	print '<table><tr><td>';

	print "<table class=outer><th colspan=2>" . _AM_FRAME_ADDFORM . "</th>\n";
	print "<tr><td class=even valign=top><center><p>" . _AM_FRAME_AVAILFORMS1 . "<br><SELECT name=fid1 size=1>\n";

	print "$formoptions";

	print "</SELECT></center></td><td class=even valign=top><center><p> " . _AM_FRAME_AVAILFORMS2 . "<br><SELECT name=fid2 size=1>\n";

	print $formoptions;

	print "</SELECT></center></td></tr><tr><td class=head colspan=2><INPUT type=submit class=formbutton name=addlink id=addlink value='" . _AM_FRAME_NEWFORMBUTTON . "'></form></td></tr></table>\n";


	print '</td><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td valign=top>';
	print '<center><a href="../admin/modframe.php">' . _AM_GOTO_MODFRAME . ' <br><img src="../images/attach.png"></a></center></td>';
	print '<td valign=top><center><a href="../admin/formindex.php">' . _AM_GOTO_MAIN . ' <br><img src="../images/formulize.gif" height=35></a></center></td></tr></table>';



	// draw the existing structure of this framework

	//print_r($links);

	print "<br><table class=outer><th colspan=3>";

	if(count($links)<1) {
		print _AM_FRAME_NOFORMS . "</th></table>";
	} else {
		print _AM_FRAME_FORMSIN . "</th><form name=updateframe action='modframe.php?op=updateframe&cf=$cf' method=post>\n";

		print "<input type=hidden name=common1choice value=\"\">\n";
		print "<input type=hidden name=common2choice value=\"\">\n";

		foreach($links as $link) {

			if($class == "even") {
				$class = "odd";
			} else {
				$class = "even";
			}

			print "<tr><td class=head rowspan = 2 valign=middle><center>\n";
			print "<INPUT type=submit class=formbutton name=delform" . $link['fl_id'] . " onclick='return confirmdel();' id=delform value='" . _AM_FRAME_DELFORM . "'></center></td>\n";

			print "<td class=$class>";

			// get names of forms in the link
			$name1q = "SELECT desc_form FROM " . $xoopsDB->prefix("formulize_id") . " WHERE id_form = '" . $link['fl_form1_id'] . "'";
			$res = $xoopsDB->query($name1q);
			$row = $xoopsDB->fetchRow($res);
			$form1 = $row[0];
			$name2q = "SELECT desc_form FROM " . $xoopsDB->prefix("formulize_id") . " WHERE id_form = '" . $link['fl_form2_id'] . "'";
			$res = $xoopsDB->query($name2q);
			$row = $xoopsDB->fetchRow($res);
			$form2 = $row[0];

			print "<table><tr><td>" . _AM_FRAME_AVAILFORMS1 . " <b><a href='modframe.php?op=editform&cf=$cf&fid=" . $link['fl_form1_id'] . "'>" . $form1 . "</a></b>";
			print "</td><td>&nbsp;&nbsp;&nbsp;&nbsp;</td><td>" . _AM_FRAME_AVAILFORMS2 . " <b><a href='modframe.php?op=editform&cf=$cf&fid=" . $link['fl_form2_id'] . "'>". $form2 . "</a></b></td></tr></table>";

			// determine relationship
			if($link['fl_relationship'] == "1") {
				$rel1 = "value=1 checked";
				$rel2 = "value=2";
				$rel3 = "value=3";
			} elseif ($link['fl_relationship'] == "2") {
				$rel1 = "value=1";
				$rel2 = "value=2 checked";
				$rel3 = "value=3";
			} elseif ($link['fl_relationship'] == "3") {
				$rel1 = "value=1";
				$rel2 = "value=2";
				$rel3 = "value=3 checked";
			}

			
			// determine display setting
			if($link['fl_unified_display'] == "1") {
				$dis1 = "value=1 checked";
				$dis2 = "value=0";
			} else {
				$dis1 = "value=1";
				$dis2 = "value=0 checked";
			}

			//determine the contents of the linkage box
			//find all links between these forms, but add User ID as the top value in the box
			// 1. Find all target links for form 1
			// 2. Check if the source is form 2
			// 3. If yes, add to the stack
			// 4. Repeat for form 2, looking for form 1
			// 5. Draw entries in box as follows:
			// form 1 field name/form 2 field name
			// 6. Account for the current link if one is specified, and make that the default selection

			$hits12 = findlink($link['fl_form1_id'], $link['fl_form2_id'], $target_form_ids, $source_form_ids);
			$hits21 = findlink($link['fl_form2_id'], $link['fl_form1_id'], $target_form_ids, $source_form_ids);

			//print_r($hits12);
			//print_r($hits21);

			$selected = "";
			if($link['fl_key1'] == 0 AND $link['fl_key2'] == 0) { $selected = " selected"; }

			$linkoptions = "<option value='0+0'" . $selected . ">" . _AM_FRAME_UIDLINK . "</option>\n"; 

			// common value option added July 19 2006 -- jwe
			if($link['fl_common_value'] != 1) {
				$linkoptions .= "<option value='common'>" . _AM_FRAME_COMMONLINK . "</option>\n";
			} else {
				// must retrieve the names of the fields, since they won't be in the target and source caps arrays, since those are focused only on the linked fields
				$element_handler =& xoops_getmodulehandler('elements', 'formulize');
				$ele1 = $element_handler->get($link['fl_key1']);
				$ele2 = $element_handler->get($link['fl_key2']);
				$name1 = $ele1->getVar('ele_colhead') ? printSmart($ele1->getVar('ele_colhead')) : printSmart($ele1->getVar('ele_caption'));
				$name2 = $ele2->getVar('ele_colhead') ? printSmart($ele2->getVar('ele_colhead')) : printSmart($ele2->getVar('ele_caption'));
				$linkoptions .= "<option value='" . $link['fl_key1'] . "+" . $link['fl_key2'] . "' selected>" . _AM_FRAME_COMMON_VALUES . "$name1 & $name2</option>\n";
				print "<input type=hidden name=preservecommon value=" . $link['fl_key1'] . "+" . $link['fl_key2'] . ">\n";
			}
			$linkoptions .= writelinks($hits12, 0, $link['fl_key1'], $link['fl_key2'], $target_ele_ids, $source_ele_ids, $target_caps, $source_caps);
			$linkoptions .= writelinks($hits21, 1, $link['fl_key1'], $link['fl_key2'], $target_ele_ids, $source_ele_ids, $target_caps, $source_caps);			

			// debug code
			/*print $rel1 . "<br>";
			print $rel2 . "<br>";
			print $dis1 . "<br>";
			print $dis2 . "<br>";*/

			print "</td><td class=$class><table><tr><td valign=middle>" . _AM_FRAME_RELATIONSHIP . "&nbsp;&nbsp;</td><td valign=middle><input type=radio name=rel" . $link['fl_id'] . " " . $rel1 . ">" . _AM_FRAME_ONETOONE . "</input><br><input type=radio name=rel" . $link['fl_id'] . " " . $rel2 . ">" . _AM_FRAME_ONETOMANY . "</input><br><input type=radio name=rel" . $link['fl_id'] . " " . $rel3 . ">" . _AM_FRAME_MANYTOONE . "</input></td></tr></table></td></tr>\n";

			// javascript handler for common values added July 19 2006 -- jwe
			print "<tr><td class=$class><center>" . _AM_FRAME_LINKAGE . "<br><SELECT name=linkages" .  $link['fl_id'] . " id=linkages" . $link['fl_id'] . " size=1 onchange=\"javascript:checkForCommon(this.form.linkages" . $link['fl_id'] . ", '" . $link['fl_form1_id'] . "', '" . $link['fl_form2_id'] . "', '" . $link['fl_id'] . "');\">\n>" . $linkoptions . "</SELECT>";

			print "</td><td class=$class><table><tr><td valign=middle>" . _AM_FRAME_DISPLAY . "&nbsp;&nbsp;</td><td valign=middle><input type=radio name=display" . $link['fl_id'] . " " . $dis1 . ">" . _formulize_TEMP_QYES . "</input><br><input type=radio name=display" . $link['fl_id'] .  " " . $dis2 . ">" . _formulize_TEMP_QNO . "</input></td></tr></table></td></tr>";
			
			
		}
		print "<tr><td class=head colspan=3><center><INPUT type=submit name=updateframebutton id=updateframebutton class=formbutton value='" . _AM_FRAME_UPDATEBUTTON . "'></form></center></td></tr></table>";
	}
}

			function findlink($targetform, $sourceform, $target_form_ids, $source_form_ids) {
				array_splice($hits, 0);
				array_splice($truehits, 0);

/*				print "<br>target: " . $targetform . "<br>";
				print "source: " . $sourceform . "<br>targetarray: ";
				print_r($target_form_ids);
				print "<br>sourcearray: ";
				print_r($source_form_ids);*/

				$hits = array_keys($target_form_ids, $targetform);
				foreach($hits as $hit) {
					if($source_form_ids[$hit] == $sourceform) {
						$truehits[] = $hit;
					}
				}
				return $truehits;
			}

			function writelinks($links, $invert, $key1, $key2, $target_ele_ids, $source_ele_ids, $target_caps, $source_caps) {
				foreach($links as $link) {
					$selected = "";
					if($invert) {
						if($key1 == $source_ele_ids[$link] AND $key2 == $target_ele_ids[$link]) {
							$selected = " selected";
						}
						$linkoptions .= "<option value='" . $source_ele_ids[$link] . "+" . $target_ele_ids[$link] . "'" . $selected . ">" . $source_caps[$link] . "/" . $target_caps[$link] . "</option>\n"; 
					} else { 
						if($key1 == $target_ele_ids[$link] AND $key2 == $source_ele_ids[$link]) {
							$selected = " selected";
						}
						$linkoptions .= "<option value='" . $target_ele_ids[$link] . "+" . $source_ele_ids[$link] . "'" . $selected . ">" . $target_caps[$link] . "/" . $source_caps[$link] . "</option>\n"; 
					}
				}
				return $linkoptions;
			}



function createframe($framename)
{

	global $xoopsDB;

	//write the name to the DB and then get the ID of the name just written
	$writename = "INSERT INTO " . $xoopsDB->prefix("formulize_frameworks") . " (frame_name) VALUES (\"$framename\")";
	if($res = $xoopsDB->query($writename)) {
	} else {
		print "Error: Framework creation unsuccessful";
	}

	// set $cf to next available ID value so we know what number to pass for the new framework
	$highcf = "SELECT MAX(frame_id) FROM " . $xoopsDB->prefix("formulize_frameworks") . " WHERE frame_name = \"$framename\"";
	$res = $xoopsDB->query($highcf);
	$row = $xoopsDB->fetchRow($res);

	return $row[0];

}

function createformslink($fid1, $fid2, $cf) {

	global $xoopsDB;

	// This is the function that makes a new form entry in the framework DB tables

	array($forms);
	$forms[] = $fid1;
	$forms[] = $fid2;
	
	// check that the forms are new and if so add them to the forms table

	foreach($forms as $fid) {
		$checkq = "SELECT ff_id FROM " . $xoopsDB->prefix("formulize_framework_forms") . " WHERE ff_form_id='$fid' AND ff_frame_id = '$cf'";
		$res=$xoopsDB->query($checkq);
		if($xoopsDB->getRowsNum($res)==0) {
			$writeform = "INSERT INTO " . $xoopsDB->prefix("formulize_framework_forms") . " (ff_frame_id, ff_form_id) VALUES ('$cf', '$fid')";
			if(!$res = $xoopsDB->query($writeform)) {
				print "Error:  could not add form: $fid to framework: $cf";
			}
		}		
	}

	// write the link to the links table

	$writelink = "INSERT INTO " . $xoopsDB->prefix("formulize_framework_links") . " (fl_frame_id, fl_form1_id, fl_form2_id, fl_key1, fl_key2, fl_relationship, fl_unified_display, fl_common_value) VALUES ('$cf', '$fid1', '$fid2', 0, 0, 1, 0, 0)";
	if(!$res = $xoopsDB->query($writelink)) {
		print "Error:  could not write link of $fid1 and $fid2 to the links table for framework $cf";
	}

}

function deleteform($ff_id, $cf) {

	global $xoopsDB;

		$deleteq = "DELETE FROM " . $xoopsDB->prefix("formulize_framework_forms") . " WHERE ff_id='$ff_id'";
		if ($res = $xoopsDB->queryF($deleteq)) {
		} else {
			print "Error: form deletion unsuccessful";
		}
}


function deletelink($fl_id, $cf) {

	global $xoopsDB;

		$deleteq = "DELETE FROM " . $xoopsDB->prefix("formulize_framework_links") . " WHERE fl_id='$fl_id'";
		if ($res = $xoopsDB->queryF($deleteq)) {
		} else {
			print "Error: link deletion unsuccessful";
		}
}

function deleteelements($fe_id, $cf) {

	global $xoopsDB;

		$deleteq = "DELETE FROM " . $xoopsDB->prefix("formulize_framework_elements") . " WHERE fe_id='$fe_id'";
		if ($res = $xoopsDB->queryF($deleteq)) {
		} else {
			print "Error: element deletion unsuccessful";
		}


}

function editform($fid, $cf) {

	global $xoopsDB;

	// This is the edit form page

	// Retrieve the following:
	// 0. Form name
	// 1. Handle of form in this framework
	// 2. Complete element list for this form
	// 3. Handle of each element for this form (in this framework)

	$nameq = "SELECT desc_form FROM " . $xoopsDB->prefix("formulize_id") . " WHERE id_form = '" . $fid . "'";
	$res = $xoopsDB->query($nameq);
	$row = $xoopsDB->fetchRow($res);
	$formname = $row[0];

	$handleq = "SELECT ff_handle FROM " . $xoopsDB->prefix("formulize_framework_forms") . " WHERE ff_frame_id = '" . $cf . "' AND ff_form_id = '" . $fid . "'";
	$res = $xoopsDB->query($handleq);
	$row = $xoopsDB->fetchRow($res);
	$formhandle = $row[0];

	$indexer = 0;
	$elementsq = "SELECT ele_caption, ele_id FROM " . $xoopsDB->prefix("formulize") . " WHERE id_form = '" . $fid . "' ORDER BY ele_order";
	$res = $xoopsDB->query($elementsq);
	while ($row = $xoopsDB->fetchRow($res)) {
		$elements[$indexer]['caption'] = $row[0];
		$elements[$indexer]['id'] = $row[1];

		$ehandleq = "SELECT fe_handle FROM " . $xoopsDB->prefix("formulize_framework_elements") . " WHERE fe_frame_id = '$cf' AND fe_form_id = '$fid' AND fe_element_id = '" . $row[1] . "'";
		$res2 = $xoopsDB->query($ehandleq);
		$row2 = $xoopsDB->fetchRow($res2);
		$elements[$indexer]['handle'] = $row2[0];

		$indexer++;
	}

	// draw the form...
	
	print "<h3>" . _AM_FRAME_EDITFORM . "&nbsp;&nbsp;<i>$formname</i></h3>\n";

	print "<p>" . _AM_FRAME_HANDLESHELP . "</p>";

	print "<form name=modform action='modframe.php?op=saveform&cf=$cf&fid=$fid' method=post>\n";

	print "<table class=outer><th><center>" . _AM_FRAME_FORMHANDLE . "</center></th>\n";
	print "<tr><td class=head><center><INPUT type=text size=60 length=255 name=formhandle value='$formhandle'></center></td></tr></table>\n";

	print "<br><table class=outer><th colspan=2><center>" . _AM_FRAME_FORMELEMENTS . "</center></td>\n";
	print "<tr><td class=head><center>" . _AM_FRAME_ELEMENT_CAPTIONS . "</center></td><td class=head><center>" . _AM_FRAME_ELEMENT_HANDLES . "</center></td></tr>\n";
	
	// loop through all elements and draw them in
	for($i=0;$i<$indexer;$i++) {
		$handletext = $elements[$i]['handle'] ? $elements[$i]['handle'] : "handle-$fid-" . $elements[$i]['id'];
		print "<tr><td class=even>" . $elements[$i]['caption'] . "</td><td class=odd><center><INPUT type=text size=45 length=255 name=elehandle" . $elements[$i]['id'] . " value='" . $handletext . "'></center></td></tr>\n";
	}
	
	print "<tr><td class=head colspan=2><table><tr><td><INPUT type=submit name=updateformbutton id=updateformbutton class=formbutton value='" . _AM_FRAME_UPDATEFORMBUTTON . "'></td><td width=15%></td><td><INPUT type=submit name=updateandgo id=updateformbutton class=formbutton value='" . _AM_FRAME_UPDATEANDGO . "'></form></td></tr></table></td></tr></table>";

}


function saveform($fid, $cf) {

	global $xoopsDB;

	// read the $_POST array and get all the handles out of it and write them to the DB

	$formhandleq = "UPDATE " . $xoopsDB->prefix("formulize_framework_forms") . " SET ff_handle='" . $_POST['formhandle'] . "' WHERE ff_frame_id='$cf' AND ff_form_id='$fid'";
	if(!$res = $xoopsDB->query($formhandleq)) {
 		print "Error: could not update form handle";
	}

	foreach($_POST as $key=>$value) {
		if(substr($key, 0, 9) == "elehandle") {
			$eleid = substr($key, 9);
			// check to see if element has been written to DB already
			$elecheckq = "SELECT fe_id FROM " . $xoopsDB->prefix("formulize_framework_elements") . " WHERE fe_frame_id='$cf' AND fe_form_id='$fid' AND fe_element_id='$eleid'";
			$res = $xoopsDB->query($elecheckq);
			// INSERT 
			if($xoopsDB->getRowsNum($res)==0) {
				$eleq = "INSERT INTO " . $xoopsDB->prefix("formulize_framework_elements") . " (fe_frame_id, fe_form_id, fe_element_id, fe_handle) VALUES ('$cf', '$fid', '$eleid', '$value')";
				if(!$res2 = $xoopsDB->query($eleq)) {
					print "Error: could not insert element handle $value";
				}
			} else {
			// UPDATE
				$row = $xoopsDB->fetchRow($res);
				$eleq = "UPDATE " . $xoopsDB->prefix("formulize_framework_elements") . " SET fe_handle='$value' WHERE fe_id='" . $row[0] . "'";
				if(!$res2 = $xoopsDB->query($eleq)) {
					print "Error: could not update element handle $value";
				}
			}
		}		
	}
}

function deleteframe($cf) {

	global $xoopsDB;

	// delete the framework and all its forms and elements in the other tables
	// need to add more stuff here to delete everything

	$deleteq = "DELETE FROM " . $xoopsDB->prefix("formulize_frameworks") . " WHERE frame_id=$cf";
	if ($res = $xoopsDB->queryF($deleteq)) {
	} else {
		print "Error: Framework deletion unsuccessful";
	}

	$findformsq = "SELECT ff_id FROM " . $xoopsDB->prefix("formulize_framework_forms") . " WHERE ff_frame_id='$cf'";
	$res = $xoopsDB->query($findformsq);
	while ($array = $xoopsDB->fetchArray($res)){
		deleteform($array['ff_id'], $cf);
	}

	$findlinksq = "SELECT fl_id FROM " . $xoopsDB->prefix("formulize_framework_links") . " WHERE fl_frame_id='$cf'";
	$res = $xoopsDB->query($findlinksq);
	while ($array = $xoopsDB->fetchArray($res)) {
		deletelink($array['fl_id'], $cf);
	}

	$findelementsq = "SELECT fe_id FROM " . $xoopsDB->prefix("formulize_framework_elements") . " WHERE fe_frame_id='$cf'";
	$res = $xoopsDB->query($findelementsq);
	while ($array = $xoopsDB->fetchArray($res)) {
		deleteelements($array['fe_id'], $cf);
	}
}



function updaterels($fl_id, $value) {
	global $xoopsDB;

	$sql = "UPDATE " . $xoopsDB->prefix("formulize_framework_links") . " SET fl_relationship='$value' WHERE fl_id='$fl_id'";
	if(!$res = $xoopsDB->query($sql)) {
		print "Error: could not update relationship for framework link $fl_id";
	}
}

function updatelinks($fl_id, $value) {
	global $xoopsDB;
	if($value == "common") {
		$keys[0] = $_POST['common1choice'];
		$keys[1] = $_POST['common2choice'];
		$common = 1;
	} else {
		$keys = explode("+", $value);
		$common = $_POST['preservecommon'] == $value ? 1 : 0;
	}
	$sql = "UPDATE " . $xoopsDB->prefix("formulize_framework_links") . " SET fl_key1='" . $keys[0] . "', fl_key2='" . $keys[1] . "', fl_common_value='$common' WHERE fl_id='$fl_id'";
	if(!$res = $xoopsDB->query($sql)) {
		print "Error: could not update key fields for framework link $fl_id";
	}
}

function updatedisplays($fl_id, $value) {
	global $xoopsDB;
	$sql = "UPDATE " . $xoopsDB->prefix("formulize_framework_links") . " SET fl_unified_display='$value' WHERE fl_id='$fl_id'";	
	if(!$res = $xoopsDB->query($sql)) {
		print "Error: could not update unified display setting for framework link $fl_id";
	}
}

function updateframe($cf) {

	// update the frame with the settings specified on the main modframe page
	
	//print_r($_POST);

	foreach($_POST as $key=>$value) {
		
		if(substr($key, 0, 3) == "rel") {
			$fl_id = substr($key, 3);
			updaterels($fl_id, $value);
		}
	
		if(substr($key, 0, 8) == "linkages") {
			$fl_id = substr($key, 8);
			updatelinks($fl_id, $value);
		}
		
		if(substr($key, 0, 7) == "display") {
			$fl_id = substr($key, 7);
			updatedisplays($fl_id, $value);
		}
	}
}


function checkfordelete() {

	// look through the _POST array and figure out if a deletelink action was requested, then return the fl_id (part of the button name) so the delete can be carried out

	$delid = "";
	if($delid = array_search(_AM_FRAME_DELFORM, $_POST)) {
		$delid = substr($delid, 7); // 7 is the length of the text delform which prefixes the link id
	}
	return $delid;
}

function checkformodify() {
	// this function NOT CURRENTLY IN USE
	// look through the _POST array and figure out if a deletelink action was requested, then return the fl_id (part of the button name) so the delete can be carried out

	$modid = "";
	if($modid = array_search(_AM_FRAME_EDITFORM, $_POST)) {
		$modid = substr($modid, 7); // 7 is the length of the text "modform" which prefixes the formid
	}
	return $modid;
}


switch ($op) {
	case "new":
		$framename = $_POST['framename'];
		$cf = createframe($framename); // need to take the name value and store it and return the ID of the new frame
		modframe($cf);
		break;
	case "edit":
		modframe($cf);
		break;
	case "addlink": 
		$fid1 = $_POST['fid1'];
		$fid2 = $_POST['fid2'];
		$new = createformslink($fid1, $fid2, $cf); 
		modframe($cf);
		break;
	case "updateframe":
		$flid = checkfordelete();
		if($flid) { deletelink($flid, $cf); }

/*		$modid = checkformodify();
		if($modid) {
			editform($modid, $cf);
			break;
		}*/

		updateframe($cf);			
		modframe($cf);
		break;
	case "editform":
		$fid = $_GET['fid'];
		editform($fid, $cf);
		break;
	case "delete":
		deleteframe($cf);
		mainpage();
		break;	
	case "saveform":
		$fid = $_GET['fid'];
		saveform($fid, $cf);
		if(isset($_POST['updateandgo'])) {
			modframe($cf);
		} else {
			editform($fid, $cf);
		}
		break;
	default:
		mainpage();
		break;
}



include 'footer.php';
xoops_cp_footer();




?>