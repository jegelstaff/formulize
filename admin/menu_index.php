<?php
// Copyright (c) 2004 Freeform Solutions and Marcel Widmer (for the original
// mymenu module).
// ------------------------------------------------------------------------- //
//                XOOPS - PHP Content Management System                      //
//                       <http://www.xoops.org/>                             //
// ------------------------------------------------------------------------- //
// Based on:                                                                 //
// myPHPNUKE Web Portal System - http://myphpnuke.com/                       //
// PHP-NUKE Web Portal System - http://phpnuke.org/                          //
// Thatware - http://thatware.org/                                           //
// ------------------------------------------------------------------------- //
//  This program is free software; you can redistribute it and/or modify     //
//  it under the terms of the GNU General Public License as published by     //
//  the Free Software Foundation; either version 2 of the License, or        //
//  (at your option) any later version.                                      //
//                                                                           //
//  This program is distributed in the hope that it will be useful,          //
//  but WITHOUT ANY WARRANTY; without even the implied warranty of           //
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            //
//  GNU General Public License for more details.                             //
//                                                                           //
//  You should have received a copy of the GNU General Public License        //
//  along with this program; if not, write to the Free Software              //
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA //
// ------------------------------------------------------------------------- //

include_once("admin_header.php");

include_once "../include/functions.php";

function MyMenuAdmin($cat_id="") { // modified to accept passing of category names APRIL 25/05
        global $xoopsDB, $xoopsConfig, $xoopsModule;
        xoops_cp_header();
        OpenTable();

        echo "<big><b>"._AM_TITLE."</big></b>";
/* COMMENTED THE ADD ITEM UI SINCE ADDING AN ITEM DOES BAD THINGS.  see note below re: deleting items.  09/03/05 jwe
        //*********** Menueintrag hinzufügen ******************************************************
        echo "<h4 style='text-align:left;'>"._AM_ADDMENUITEM."</h4>
        <form action='menu_index.php' method='post'>
        <table border='0' cellpadding='0' cellspacing='0' valign='top' width='100%'>
        <tr>
        <td class='bg2'>
                <table width='100%' border='0' cellpadding='4' cellspacing='1'>
                <tr>
                <td class='bg3'><b>"._AM_POS."</b></td>
                <td class='bg1'><input type='text' name='xposition' size='4' maxlength='4' />&nbsp&nbsp&nbsp(0000-9999)</td>
                </tr>
                <tr>
                <td class='bg3'><b>"._AM_ITEMNAME."</b></td>
                <td class='bg1'><input type='text' name='itemname' size='50' maxlength='60' /></td>
                </tr>
                <tr>
                <td class='bg3'><b>"._AM_INDENT."</b></td>
                <td class='bg1'><input type='text' name='indent' size='12' maxlength='12' value='0px' /></td>
                </tr>
                <tr>
                <td class='bg3'><b>"._AM_FONT."</b></td>
                <td class='bg1'>
                <input type='radio' checked name='bold' value='0'>"._AM_NORMAL."
                <input type='radio'         name='bold' value='1'>"._AM_BOLD."
                </td>
                </tr>
                <tr>
                <td class='bg3'><b>"._AM_ITEMURL."</b></td>
                <td class='bg1'><input type='text' name='itemurl' size='65' maxlength='255' /></td>
                </tr>
                <tr>
                <td class='bg3'><b>"._AM_MARGINTOP."</b></td>
                <td class='bg1'><input type='text' name='margintop' size='12' maxlength='12' value='0px' /></td>
                </tr>
                <tr>
                <td class='bg3'><b>"._AM_MARGINBOTTOM."</b></td>
                <td class='bg1'><input type='text' name='marginbottom' size='12' maxlength='12' value='0px' /></td>
                </tr>";
         / * L'autentification est gérée par les formulizes
                <tr>
                <td class='bg3'><b>"._AM_MEMBERSONLY."</b></td>
                <td class='bg1'>
                <input type='radio' checked name='membersonly' value='1'>"._AM_MEMBERS."
                <input type='radio'         name='membersonly' value='0'>"._AM_ALL."
                </td>
                </tr>* /
                echo "
                <tr>
                <td class='bg3'><b>Menu-Style</b></td>
                <td class='bg1'>
                <input type='radio' checked name='mainmenu' value='1'>"._AM_formulizeMENUSTYLE."
                <input type='radio'         name='mainmenu' value='0'>"._AM_MAINMENUSTYLE."
                </td>
                </tr>
                <tr>
                <td class='bg3'><b>"._AM_STATUS."</b></td>
                <td class='bg1'>
                <input type='radio' checked name='status' value='1'>"._AM_ACTIVE."
                <input type='radio'         name='status' value='0'>"._AM_INACTIVE."
                </td>
                </tr>
                <tr>
                <td class='bg3'>&nbsp;</td>
                <td class='bg1'><input type='hidden' name='fct' value='mymenu' /><input type='hidden' name='op' value='MyMenuAdd' /><input type='submit' value='"._AM_ADD."' /></td>
                </tr>
                </table>
        </td>
        </tr>
        </table>
        </form>
        <br />";
*/

// ADDED MENU CATEGORY UI -- April 25/05

	// javascript to confirm deletion 

	print "<script type='text/javascript'>\n";
	print "	function confirmdel() {\n";
	print "		var answer = confirm ('" . _AM_CONFIRM_DELCAT . "')\n";
	print "		if (answer)\n";
	print "		{\n";
	print "			return true;\n";
	print "		}\n";
	print "		else\n";
	print "		{\n";
	print "			return false;\n";
	print "		}\n";
	print "	}\n";
	print "</script>\n";


echo "<h4 style='text-align:left;'>"._AM_MENUCATEGORIES."</H4>
<table><tr><td valign=top>
<form action='menu_index.php' method='post'>
<input type='hidden' name='op' value='menuCatEditDel'>";
echo "<p>" . _AM_MENUCATLIST . "&nbsp;&nbsp;<SELECT name=cat_id>";
$cats = fetchCats();
if(count($cats)>0) {
	foreach($cats as $catid=>$catname) {
		$catoptions .= "<option value=$catid>$catname</option>";
	}

echo "$catoptions</SELECT>
<br>
<input type=submit name=edit value='" . _AM_MENUEDIT . "' id=edit>&nbsp;&nbsp;<input type=submit name=del value='" . _AM_MENUDEL . "' id=del onclick='return confirmdel();'>";

} else {
	$catoptions .= "<option value=0>" . _AM_MENUNOCATS . "</option>";
echo "$catoptions</SELECT>";

}

echo "</p></form></td></tr><tr><td valign=top>
<form action='menu_index.php' method='post'>
<input type='hidden' name='op' value='menuCatUpdate'>";

// if cat_id is zero (ie: not set) then get the next cat 
if($cat_id == 0) {
	$cat_id_q = q("SELECT MAX(cat_id) FROM " . $xoopsDB->prefix("formulize_menu_cats"));
	$cat_id = $cat_id_q[0]['MAX(cat_id)'];
	if(!$cat_id == 0) {
		$cat_id++;
	} else {
		$cat_id = 0;
	}
	echo"<input type='hidden' name='new_cat' value=1>";
} else { // otherwise, set the $cat_name
	$cat_name_q = q("SELECT cat_name FROM " . $xoopsDB->prefix("formulize_menu_cats") . " WHERE cat_id='$cat_id'");
	$cat_name = $cat_name_q[0]['cat_name'];
}



echo "<input type='hidden' name='cat_id' value='$cat_id'>
<p>" . _AM_MENUCATNAME . "&nbsp;&nbsp;<input type=text name=cat_name value='$cat_name'><br>
<input type=submit name=update value='" . _AM_MENUSAVEADD . "' id=update></p></form>
</td></tr></table>";



// END OF MENU CAT UI


        //*********** Menueintrag ändern/löschen ******************************************************
        echo "<h4 style='text-align:left;'>"._AM_CHANGEMENUITEM."</h4>
        <form action='menu_index.php' method='post'>
        <table border='0' cellpadding='0' cellspacing='0' valign='top' width='100%'>
        <tr>
        <td class='bg2'>
                <table width='100%' border='0' cellpadding='4' cellspacing='1'>
                <tr class='bg3'>
		
		    <td><b>"._AM_CATSHORT."</b></td>
                <td><b>"._AM_POS_SHORT."</b></td>
                <td><b>"._AM_ITEMNAME."</b></td>
                <td><b>"._AM_STATUS."</b></td></tr>";

			// CHANGED THE LOOPING HERE SO THAT ENTRIES ARE SORTED BY CATEGORY
			$allcats = q("SELECT cat_id, cat_name, id_form_array FROM " . $xoopsDB->prefix("formulize_menu_cats") . " ORDER BY cat_name");
			foreach($allcats as $thiscat) {
				$formids = explode(",", trim($thiscat['id_form_array'], ",")); // note that there is a trailing and leading comma on this array

				$sortfilter = "(menuid='";
				$sortfilter .= implode("' OR menuid='", $formids);
				$sortfilter .= "')";


				// do an interim query to sort the formids according to position
				$positions = q("SELECT menuid FROM " . $xoopsDB->prefix("formulize_menu") . " WHERE $sortfilter ORDER BY position");
				foreach($positions as $id=>$thispos) {
					drawRow($thiscat['cat_name'], $thispos['menuid']);
					$foundForms[] = $thispos['menuid'];
                		} 
			} // ENDS OF NEW LOOPING STRUCTURE TO SORT BY CATEGORY

			// IF NO FORMS ARE FOUND, ADD A DUMMY ENTRY TO FOUNDFORMS	
			if(count($foundForms)==0) { $foundForms[0] = ""; }

			// NOW GET LIST OF ALL FORMS(MENUIDS)
			// draw a row for each one that isn't in the foundForms array
			$allMenuIds_q = q("SELECT menuid FROM " . $xoopsDB->prefix("formulize_menu"));
			foreach($allMenuIds_q as $aMenuId) {
				$allMenuIds[] = $aMenuId['menuid'];
			}
			$leftOverForms = array_diff($allMenuIds, $foundForms);			
			foreach($leftOverForms as $thisformid) {
				drawRow(_AM_CATGENERAL, $thisformid);
			}

                echo "</table>
        </td>
        </tr>
        </table>
        </form>";

        CloseTable();
}

// FUNCTION HANDLES DRAWING OF A ROW OF THE MAIN LISTING OF FORMS
function drawRow($thiscat, $thisformid) {

	global $xoopsDB;

	$result = $xoopsDB->query("SELECT menuid, position, itemname, indent, margintop, marginbottom, itemurl, bold, membersonly, mainmenu, status FROM ".$xoopsDB->prefix("formulize_menu")." WHERE menuid = '$thisformid' ORDER BY position");
	$myts =& MyTextSanitizer::getInstance();
	while ( list($menuid, $position, $itemname, $indent, $margintop, $marginbottom, $itemurl, $bold, $membersonly, $mainmenu, $status) = $xoopsDB->fetchRow($result) ) {
	     	$itemname = $myts->makeTboxData4Show($itemname);
            $itemurl = $myts->makeTboxData4Show($itemurl);
		echo "<tr class='bg1'><td>" . $thiscat . "</td>"; // added to display category names (class moved from position line below)
		echo "<td align='right'>$position</td>";
		echo "<td><a href='menu_index.php?op=MyMenuEdit&amp;menuid=$menuid'>$itemname</a></td>";
		if ( $status == 1 ) {
			echo "<td>"._AM_ACTIVE."</td>";
		} else {
			echo "<td>"._AM_INACTIVE."</td>";
		}
		echo "</tr>";
	}

}

function MyMenuEdit($menuid) {
        global $xoopsDB, $xoopsConfig, $xoopsModule;
        xoops_cp_header();
        $result = $xoopsDB->query("SELECT position, itemname, indent, margintop, marginbottom, itemurl, bold, membersonly, mainmenu, status FROM ".$xoopsDB->prefix("formulize_menu")." WHERE menuid=$menuid");
        list($xposition, $itemname, $indent, $margintop, $marginbottom, $itemurl, $bold, $membersonly, $mainmenu, $status) = $xoopsDB->fetchRow($result);
        $myts =& MyTextSanitizer::getInstance();
        $itemname  = $myts->makeTboxData4Edit($itemname);
        $itemurl   = $myts->makeTboxData4Edit($itemurl);
        OpenTable();
        echo "<big><b>"._AM_TITLE."</big></b>
        <h4 style='text-align:left;'>"._AM_EDITMENUITEM.": $itemname</h4>
        <form action='menu_index.php' method='post'>
        <input type='hidden' name='menuid' value='$menuid' />
        <table border='0' cellpadding='0' cellspacing='0' valign='top' width='100%'>
        <tr>
        <td class='bg2'>
                <table width='100%' border='0' cellpadding='4' cellspacing='1'>
                <tr>";

			// ADDED UI FOR CATEGORY NAME April 25/05
			// 1. get categories and ids
			// 1.5 get current category
			// 2. format string for selectbox
			// 3. write out HTML

			$cats = fetchCats();
			$curcat_q = q("SELECT cat_id FROM " . $xoopsDB->prefix("formulize_menu_cats") . " WHERE id_form_array LIKE \"%,$menuid,%\"");
			$curcat_id = $curcat_q[0]['cat_id'];
			$catselected = 0;
			foreach($cats as $catid=>$catname) {
				$catoptions .= "<option value='$catid'";
				if($catid == $curcat_id) { 
					$catoptions .= " selected='selected'"; 
					$catselected = 1;
				}
				$catoptions .= ">$catname</option>";
			}
			$catoptions .= "<option value='0'";
			if($catselected == 0) {
				$catoptions .= " selected='selected'"; 
			}
			$catoptions .= ">" . _AM_CATGENERAL . "</option>";
			
			echo "<input type='hidden' name=old_cat value='$curcat_id'>";

			echo "<td class='bg3' width=25%><b>"._AM_CATSHORT."</b></td>
			<td class='bg1'><SELECT name=cat_id size=1 id=cat_id>$catoptions</SELECT></td>
			</tr>
			</tr>";


                echo "<td class='bg3'><b>"._AM_POS."</b></td>
                <td class='bg1'><input type='text' name='xposition' size='4' maxlength='4' value='$xposition' />&nbsp&nbsp&nbsp(0000-9999)</td>
                </tr>
                <tr>
                <td class='bg3'><b>"._AM_STATUS."</b></td>
                <td class='bg1'>";
                if( $status == 1 ) {
                           $checked_active   = "checked";$checked_inactive = "";
               } else {
                           $checked_inactive = "checked";$checked_active   = "";
                      }
                echo "
                <input type='radio' $checked_active   name='status' value='1'>"._AM_ACTIVE."
                <input type='radio' $checked_inactive name='status' value='0'>"._AM_INACTIVE."
                </td>
                </tr>

                <tr>
                <td class='bg3'>&nbsp;</td>
                <td class='bg1'><input type='hidden' name='fct' value='mymenu' /><input type='hidden' name='op' value='MyMenuSave' /><input type='submit' value='"._AM_SAVECHANG."' />&nbsp;&nbsp;<input type=button name=cancel value='" . _AM_CANCEL . "' onclick='javascript:location.href=\"../admin/menu_index.php\"'></td>
                </tr>
                </table>
        </td>
        </tr>
        </table>

        </form>";

        CloseTable();
}

function MyMenuSave($menuid, $xposition, $itemname, $indent, $margintop, $marginbottom, $itemurl, $bold, $membersonly, $mainmenu, $status, $cat_id, $old_cat) {
        global $xoopsDB;
        $myts =& MyTextSanitizer::getInstance();
   		 	$itemname  = $myts->makeTboxData4Save(trim($itemname));
    		$itemurl   = $myts->makeTboxData4Save(trim($itemurl));
        $xoopsDB->query("UPDATE ".$xoopsDB->prefix("formulize_menu")." SET position=$xposition, status=$status WHERE menuid=$menuid");

		// ADDED CODE TO HANDLE SAVING OF CATEGORY April 25/05
		// 0. compare cat_id and old_cat to see if there's been a change
		// 1. GET THE id_form_array for the cat_id
		// 2. add the menuid to the array
		// 3. write the new array to the db

		if($cat_id != $old_cat) { 

		// write to the new cat...
		$flatarray = q("SELECT id_form_array FROM " . $xoopsDB->prefix("formulize_menu_cats") . " WHERE cat_id='$cat_id'");
		if($flatarray[0]['id_form_array'] == ",,") {
			$newarray = "," . $menuid . ","; // note the leading and trailing commas
		} else {
			$newarray = $flatarray[0]['id_form_array'] . $menuid . ",";
		}
		q("UPDATE " . $xoopsDB->prefix("formulize_menu_cats") . " SET id_form_array='$newarray' WHERE cat_id='$cat_id'");

		//erase from the old cat...
		$oldflatarray = q("SELECT id_form_array FROM " . $xoopsDB->prefix("formulize_menu_cats") . " WHERE cat_id='$old_cat'");
		$oldarray = explode(",", trim($oldflatarray[0]['id_form_array'], ",")); // note the trim of commas
		$key = array_search($menuid, $oldarray); // get the key of the old item
		$olditem = array_splice($oldarray, $key, 1); // extract that part of the old array
		$newarray = array_diff($oldarray, $olditem); // return the old array minus the extracted item
		$newflatarray = ",";
		$newflatarray .= implode(",", $newarray);
		$newflatarray .= ",";
		q("UPDATE " . $xoopsDB->prefix("formulize_menu_cats") . " SET id_form_array='$newflatarray' WHERE cat_id='$old_cat'");
		} // end of if new cat not equal to old cat

        redirect_header("menu_index.php?op=MyMenuAdmin",1,_AM_DBUPDATED);
        exit();
}


function MyMenuAdd($xposition, $itemname, $indent, $margintop, $marginbottom, $itemurl, $bold, $membersonly, $mainmenu, $status, $cat_id) {
        global $xoopsDB;
        $myts =& MyTextSanitizer::getInstance();
    		$itemname  = $myts->makeTboxData4Save(trim($itemname));
    		$itemurl   = $myts->makeTboxData4Save(trim($itemurl));
        $newid = $xoopsDB->genId($xoopsDB->prefix("formulize_menu")."_menuid_seq");
        
    		$xoopsDB->query("INSERT INTO ".$xoopsDB->prefix("formulize_menu")." (menuid, position, itemname, indent, margintop, marginbottom, itemurl, bold, membersonly, mainmenu, status) VALUES ('$newid', '$xposition', '$itemname', '$indent', '$margintop', '$marginbottom', '$itemurl', '$bold', '0', '$mainmenu', '$status')");

    redirect_header("menu_index.php?op=MyMenuAdmin",1,_AM_DBUPDATED);
    exit();
}

// FUNCTION ADDED TO HANDLE SAVING OF CATEGORY CHANGES
function menuCatUpdate($cat_id, $cat_name, $new_cat="") {

	global $xoopsDB;	

	if($new_cat) { // if we're adding a new category...
		q("INSERT INTO " .$xoopsDB->prefix("formulize_menu_cats") . " (cat_name, id_form_array) VALUES (\"$cat_name\", \",,\")");
	} else { // we're updating an existing category...
		q("UPDATE " .$xoopsDB->prefix("formulize_menu_cats") . " SET cat_name=\"$cat_name\" WHERE cat_id=\"$cat_id\"");
	}
}

// jwe 09/03/05
// this function commented since deleting a menu item here causes serious problems due to the lack of proper linkage between the form ids and menu ids (deleting a menu entry throws those ids out of sync).
/*function MyMenuDel($menuid, $ok=0) {
        global $xoopsDB, $xoopsConfig, $xoopsModule;
        if ( $ok == 1 ) {
                $xoopsDB->query("DELETE FROM ".$xoopsDB->prefix(formulize_menu)." WHERE menuid=$menuid");
                redirect_header("menu_index.php?op=MyMenuAdmin",1,_AM_DBUPDATED);
                exit();
        } else {
                xoops_cp_header();
                OpenTable();
                $result = $xoopsDB->query("SELECT position, itemname, indent, margintop, marginbottom, itemurl, bold, membersonly, mainmenu, status FROM ".$xoopsDB->prefix("formulize_menu")." WHERE menuid=$menuid");
                list($position, $itemname, $indent, $margintop, $marginbottom, $itemurl, $bold, $membersonly, $mainmenu, $status) = $xoopsDB->fetchRow($result);
                echo "<big><b>"._AM_TITLE."</big></b>";
                echo "<h4 style='text-align:left;'>"._AM_DELETEMENUITEM."</h4>
                <form action='menu_index.php' method='post'>
                <input type='hidden' name='menuid' value='$menuid' />
                <table border='0' cellpadding='0' cellspacing='0' valign='top' width='100%'>
                        <tr>
                        <td class='bg2'>
                        <table width='100%' valign='top' border='0' cellpadding='4' cellspacing='1'>
                                <tr>
                                <td class='bg3' width='30%'><b>"._AM_POS."</b></td>
                                <td class='bg1'>".$position."</td>
                                </tr>
                                <tr>
                                <td class='bg3'><b>"._AM_ITEMNAME."</b></td>
                                <td class='bg1'>".$itemname."</td>
                                </tr>
                                <tr>
                                <td class='bg3'><b>"._AM_ITEMURL."</b></td>
                                <td class='bg1'>".$itemurl."</td>
                                </tr>
                        </table>
                        </td>
                        </tr>
                </table>
                </form>";
                echo "<table valign='top'><tr>";
                echo "<td width='30%'valign='top'><span style='color:#ff0000;'><b>"._AM_WANTDEL."</b></span></td>";
                echo "<td width='3%'>\n";
                echo myTextForm("menu_index.php?op=MyMenuDel&menuid=$menuid&ok=1", _AM_YES);
                echo "</td><td>\n";
                echo myTextForm("menu_index.php?op=MyMenuAdmin", _AM_NO);
                echo "</td></tr></table>\n";
                CloseTable();
        }
}*/

// FUNCTION DELETES A CATEGORY added April 25/05
function menuCatDel($cat_id) {
	
	global $xoopsDB;

	q("DELETE FROM " . $xoopsDB->prefix("formulize_menu_cats") . " WHERE cat_id='$cat_id'");
}


// April 25/05 -- changed this loop so that POST explicitly overrides GET (used to just use REQUEST)
 if (!ini_get("register_globals")){
   foreach ($_GET as $k=>$v){
     ${$k}=$v;
   }
   foreach ($_POST as $k=>$v){
     ${$k}=$v;
   }
}
 
if (!isset($op)) {
    $op = '';
}

switch($op) {
        case "MyMenuDel":
                MyMenuDel($menuid, $ok);
                break;
        case "MyMenuAdd":
                MyMenuAdd($xposition, $itemname, $indent, $margintop, $marginbottom, $itemurl, $bold, $membersonly, $mainmenu, $status, $cat_id); //$cat_id added April 25/05 HOWEVER THIS CASE NEVER OCCURS ANYMORE
                break;
        case "MyMenuSave":
                MyMenuSave($menuid, $xposition, $itemname, $indent, $margintop, $marginbottom, $itemurl, $bold, $membersonly, $mainmenu, $status, $cat_id, $old_cat); // $cat_id, $old_cat added April 25/05
                break;
        case "MyMenuAdmin":
                MyMenuAdmin();
                break;
        case "MyMenuEdit":
                MyMenuEdit($menuid);
                break;
	  case "menuCatUpdate": //this case added April 25/05 to handle updates to the categories of the menu
		    menuCatUpdate($cat_id, $cat_name, $new_cat);
		    MyMenuAdmin();	
		    break;
	  case "menuCatEditDel": //this case added April 25/05 to handle updates to the categories of the menu
		    if(isset($edit)) { MyMenuAdmin($cat_id); }
		    if(isset($del)) { 
			menuCatDel($cat_id); 
			MyMenuAdmin();
		    }
		    break;		    
        default:
                MyMenuAdmin();
                break;
}
xoops_cp_footer();
?>