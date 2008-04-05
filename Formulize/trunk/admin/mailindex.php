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

include("admin_header.php");
include_once '../../../include/cp_header.php';
include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";
if ( file_exists("../language/".$xoopsConfig['language']."/main.php") ) {
	include "../language/".$xoopsConfig['language']."/main.php";
} else {
	include "../language/english/main.php";
}

if(!is_numeric($_GET['title'])) {

	if(!isset($_POST['title'])){
		$title = isset ($_GET['title']) ? $_GET['title'] : '';
	}else {
		$title = $_POST['title'];
	}

	$sql=sprintf("SELECT id_form FROM ".$xoopsDB->prefix("formulize_id")." WHERE desc_form='%s'",$title);
	$res = mysql_query ( $sql ) or die('Erreur SQL !<br>'.$requete.'<br>'.mysql_error());

	if ( $res ) {
		  while ( $row = mysql_fetch_row ( $res ) ) {
		    $id_form = $row[0];
  		}
	}
} else {
	$id_form = $_GET['title'];
	$title = $_GET['title'];
	$rtsql = "SELECT desc_form FROM " . $xoopsDB->prefix("formulize_id") . " WHERE id_form=$id_form";
	$rtres = $xoopsDB->query($rtsql);
	$rtarray = $xoopsDB->fetchArray($rtres);
	$realtitle = $rtarray['desc_form'];
}
if(!isset($_POST['op'])){
	$op = isset ($_GET['op']) ? $_GET['op'] : '';
}else {
	$op = $_POST['op'];
}


if(!isset($_POST['op'])){ $_POST['op']=" ";}

// query modified to call in new fields -- jwe 7/25/04. 7/28/04

if ( isset ($title)) {
	$sql=sprintf("SELECT id_form,admin,groupe,email,expe,singleentry,groupscope,headerlist,showviewentries,maxentries,even,odd FROM ".$xoopsDB->prefix("formulize_id")." WHERE desc_form='%s'",$realtitle);
	$res = mysql_query ( $sql ) or die('Erreur SQL !<br>'.$requete.'<br>'.mysql_error());

	if ( $res ) {
	  while ( $row = mysql_fetch_array ( $res ) ) {
		//print"<br>";
		//print_r($row);
		//print"<br>";

	    $id_form = $row['id_form'];
	    $admin = $row['admin'];
	    $groupe = $row['groupe'];
	    $email = $row['email'];
	    $expe = $row['expe'];
	    // added new entries -- jwe 7/25/04
	    $singleentry = $row['singleentry'];
	    $groupscope = $row['groupscope'];
	    $headerlist = $row['headerlist'];
	    $showviewentries = $row['showviewentries'];
	    $maxentries = $row['maxentries'];
	    $coloreven = $row['even']; // colors added 9/02/04 by jwe
	    $colorodd = $row['odd'];

	  }
	}

}

$m = 0;
//include "../include/functions.php";
include_once XOOPS_ROOT_PATH."/class/xoopstree.php";
include_once XOOPS_ROOT_PATH."/class/xoopslists.php";
include_once XOOPS_ROOT_PATH."/include/xoopscodes.php";
include_once XOOPS_ROOT_PATH."/class/module.errorhandler.php";
$myts =& MyTextSanitizer::getInstance();
$eh = new ErrorHandler;

	xoops_cp_header();

if( $_POST['op'] != 'upform' && $op != 'addform'){

	$sql="SELECT groupid,name FROM ".$xoopsDB->prefix("groups");
	$res = mysql_query ( $sql );
	if ( $res ) {
	$tab[$m] = 0;
	$tab2[$m] = "";
	$m++;
	  while ( $row = mysql_fetch_array ( $res ) ) {
	    $tab[$m] = $row['groupid'];
	    $tab2[$m] = $row['name'];
	    $m++;
	  }
	}

	if ($title != '' AND !isset($_GET['table'])) {  // put in the name of the form
			echo '
		<form name=mainform action="mailindex.php?title='.$title.'" method="post">'; // name added by jwe 9/02/04
	
		echo'<table class="outer" cellspacing="1" width="100%">
		<th><center><font size=5>'._AM_FORM.trans($realtitle).'<font></center></th>
		</table>';
		
		echo '<table class="outer" cellspacing="1" width="100%">
		<th colspan="4">'._FORM_MODIF.'</th>';
		

	} elseif($title == '') { // put in a box to get a name for the form
		// check for presence of table flag and pass it on if it's there -- nov 13 2007
		$tableflag = isset($_GET['table']) ? "&table=true" : "";
		echo '
		<form action="mailindex.php?op=addform'.$tableflag.'" method="post">
	
		<table class="outer" cellspacing="1" width="100%">
		<th><center><font size=5>'._AM_FORM.'</font></center></th>
		</table>
		
		<table class="outer" cellspacing="1" width="100%">
		<th colspan="4">'._FORM_CREAT.'</th>
		
		<tr><td class="head" ><center>'._FORM_TITLE.'</center></td>
		<td class="even"><input maxlength="255" size="30" id="newtitle" name="newtitle" type="text"></td></tr>';

	}		

	if(!isset($_GET['table'])) {
		// new singleentry ui
		echo '<tr>
		<td class="head"><center>'._FORM_SINGLETYPE.'</center></td><td class="even">';
		
		$multiflag = 0;
		echo '<input type=radio name=singleentry value=group' ;
		if($title !='' && $singleentry == 'group') { 
			echo ' CHECKED'; 
			$multiflag = 1;
		}
		echo '>'._FORM_SINGLE_GROUP.'<br>';
		
		echo '<input type=radio name=singleentry value=on' ;
		if($title !='' && $singleentry == 'on') { 
			echo ' CHECKED'; 
			$multiflag = 1;
		}
		echo '>'._FORM_SINGLE_ON.'<br>';
	
		echo '<input type=radio name=singleentry value=""' ;
		if($title !='' && $multiflag==0) { echo ' CHECKED'; }
		echo '>'._FORM_SINGLE_MULTI;
	
		echo '</td>'; 
	
		echo '</tr>';
	}elseif($title == '') {
		// if this is a table form and we're adding it, then give some way of picking the table, let's start with just a plain connection string, user has to know what they're doing
		echo '<tr>
		<td class="head"><center>'._FORM_TABLE_CONNECTION.'</center></td><td class="even">';
		echo '<input type=text size=50 name=tablename value=""></td></tr>';
	}
		


if($title != '' AND !isset($_GET['table']))
{
echo '<tr>
	<td class="head"><center>'._FORM_HEADERLIST.'</center></td><td class="even">';
	if ($headerlist) {
		// split out the entries in the headerlist so they can mark the populate the listbox
		$headlistarray = explode("*=+*:", $headerlist);
		//print_r($headlistarray);
		//print"88";
	}	

	echo '<select name="headerlist[]" size="10" multiple>';
	if(!isset($_GET['table'])) {
		echo "<option value=uid";
		if($title != '' && in_array('uid', $headlistarray)) {echo " SELECTED";}  
		echo ">" . _formulize_DE_CALC_CREATOR . "</option>";
	
		echo "<option value=proxyid";
		if($title != '' && in_array('proxyid', $headlistarray)) {echo " SELECTED";}  
		echo ">" . _formulize_DE_CALC_MODIFIER. "</option>";
	
		echo "<option value=creation_date";
		if($title != '' && in_array('creation_date', $headlistarray)) {echo " SELECTED";}  
		echo ">" . _formulize_DE_CALC_CREATEDATE . "</option>";
	
		echo "<option value=mod_date";
		if($title != '' && in_array('mod_date', $headlistarray)) {echo " SELECTED";}  
		echo ">" . _formulize_DE_CALC_MODDATE . "</option>";
					
					echo "<option value=creator_email";
		if($title != '' && in_array('creator_email', $headlistarray)) {echo " SELECTED";}  
		echo ">" . _formulize_DE_CALC_CREATOR_EMAIL . "</option>";
	}

	$getform_id ="SELECT id_form FROM ".$xoopsDB->prefix("formulize_id")." WHERE desc_form=\"$realtitle\"";
	$resultgetform = mysql_query($getform_id);
	$resgetformrow = mysql_fetch_row($resultgetform);
	$thisformid = $resgetformrow[0];

	// get a list of captions in the form (fancy SQL Join query)
	// then draw them into the selection box
	// addition of column heading fields June 25 2006 -- jwe
	$sqljwe="SELECT ele_caption, ele_id, ele_colhead FROM ".$xoopsDB->prefix("formulize")." WHERE id_form = \"$thisformid\" AND ele_type != \"ib\" AND ele_type != \"areamodif\" AND ele_type != \"subform\" ORDER BY ele_order";
	$resjwe = mysql_query ( $sqljwe );
	if ( $resjwe ) {
		$loopiteration = 0;
		while ( $rowjwe = mysql_fetch_row ( $resjwe ) ) {
			echo "<option value=\"" . $rowjwe[1] . "\"";
			// check id and caption, since legacy systems will be using the caption
			// caption will not match if a form has recently been translated to another language, but once the headerlist is specified from scratch now, it will always be remembered since ids are now stored 
			if($title != '' && (in_array($rowjwe[1], $headlistarray) OR in_array($rowjwe[0], $headlistarray))) {echo " SELECTED";}  
			echo '>';
			if($rowjwe[2] != "") {
				echo printSmart(htmlspecialchars(trans($rowjwe[2])));
			} else {
				echo printSmart(htmlspecialchars(trans($rowjwe[0])));
			}
			echo '</option>';
			$loopiteration++;
		}
	}

	/*//debug code for testing submissions...
	echo '<option value="one">one</option>';
	echo '<option value="two">two</option>';
	echo '<option value="three">three</option>';*/

	echo '</select></td></tr>';
}// end of IF that controls drawing of headerlist box.








//================== end of jwe mod

if(($title != '' AND !isset($_GET['table'])) OR $title == '') {
echo '</table>
	<table class="outer" cellspacing="1" width="100%">
	';

	$submit = new XoopsFormButton('', 'submit', _SUBMIT, 'submit');
	echo '
			<td class="foot" colspan="7">'.$submit->render().'
		</tr>
	</table>
	';
}
	if ($title != '' AND !isset($_GET['table'])) { 	
		$hidden_op = new XoopsFormHidden('op', 'upform');
		echo $hidden_op->render();
	}
if(($title != '' AND !isset($_GET['table'])) OR $title == '') {	
	echo '</form>';
}


// *****************
// NEW UI FOR SCREENS
// LET USERS SPECIFY OPTIONS FOR DIFFERENT WAYS OF DISPLAYING A FORM, OR THE INFORMATION IN THE FORM
// January 20, 2007 -- jwe
// *****************


if($title != '')
{

if(isset($_GET['table'])) {
	echo'<table class="outer" cellspacing="1" width="100%">
		<th><center><font size=5>'._AM_FORM.trans($realtitle).'<font></center></th>
		</table>';
}

// Future use of smartobject planned...
/*
// Include SmartObject framework 
include_once XOOPS_ROOT_PATH.'/modules/smartobject/class/smartloader.php';
include_once(SMARTOBJECT_ROOT_PATH . "class/smartobjectcategory.php");

// Creating the screen handler object
$formulize_screen_handler =& xoops_getmodulehandler('screen', 'formulize');

$criteria = new CriteriaCompo();

include_once SMARTOBJECT_ROOT_PATH."class/smartobjecttable.php";
$objectTable = new SmartObjectTable($formulize_screen_handler, $criteria);
$objectTable->addColumn(new SmartObjectColumn('title', 'left'));
$objectTable->render();
*/

// Creating the screen handler object
$formulize_screen_handler =& xoops_getmodulehandler('screen', 'formulize');

// handle delete events coming from form below
if($_POST['deletescreenflag']) {
	$formulize_screen_handler->delete($_POST['deletescreenflag'], $_POST['deletescreentype']);
}

$formulizeScreens = $formulize_screen_handler->getObjects('', $id_form);

// javascript required for form
print "\n<script type='text/javascript'>\n";

print "	function confirmScreenDelete(sid, type) {\n";
print "		var answer = confirm ('" . _AM_FORMULIZE_CONFIRM_SCREEN_DELETE . "')\n";
print "		if (answer) {\n";
print "			document.deletescreenmanager.deletescreenflag.value=sid;\n";
print "			document.deletescreenmanager.deletescreentype.value=type;\n";
print "			document.deletescreenmanager.submit();\n";
print "		} else {\n";
print "			return false;\n";
print "		}\n";
print "	}\n";

print "</script>\n";

print "<form name=deletescreenmanager action=" . XOOPS_URL . "/modules/formulize/admin/mailindex.php?title=$id_form method=post>\n";
print "<input type=hidden name=deletescreenflag value=''>\n";
print "<input type=hidden name=deletescreentype value=''>\n";
print "</form>\n";

print "<form name=screenmanager action=" . XOOPS_URL . "/modules/formulize/admin/editscreen.php?fid=$id_form method=post>\n";
print "<center><table class=outer width=100%><tr><th colspan=4>" . _AM_FORMULIZE_DEFINED_SCREENS . "</th></tr>\n";

$class="odd";
foreach($formulizeScreens as $thisScreen) {
	$class = $class == "even" ? "odd" : "even";
        $thisScreenType = "unknown!";
        switch($thisScreen->getVar('type')) {
                case "listOfEntries":
                        $thisScreenType = _AM_FORMULIZE_SCREENTYPE_LISTOFENTRIES;
                        break;
                case "multiPage":
                        $thisScreenType = _AM_FORMULIZE_SCREENTYPE_MULTIPAGE;
                        break;
        }
	print "<tr><td class=$class><a href=" . XOOPS_URL . "/modules/formulize/admin/editscreen.php?sid=" . $thisScreen->getVar('sid') . "&type=" . $thisScreen->getVar('type') . "&fid=$id_form>" . $thisScreen->getVar('title') . "</a></td>\n";
        print "<td class=$class><b>" . _AM_FORMULIZE_SCREEN_TYPE . "</b>$thisScreenType</td>\n";
        print "<td class=$class><b>SID:</b> " . $thisScreen->getVar('sid') . "</td>\n";
	print "<td class=$class><center><input type=button name=deletescreen value=\"" . _AM_FORMULIZE_DELETE_SCREEN . "\" onclick='javascript:return confirmScreenDelete(\"". $thisScreen->getVar('sid') . "\", \"".  strtolower($thisScreen->getVar('type')). "\");'></center></td></tr>\n";
}

print "<tr><td class=foot colspan=3><p>" . _AM_FORMULIZE_ADD_NEW_SCREEN_OF_TYPE . "&nbsp;&nbsp;<select name=type size=1>\n";

// to make new types of screens, put new options here and create a corresponding class file
print "<option value=multiPage>" . _AM_FORMULIZE_SCREENTYPE_MULTIPAGE . "</option>\n";
print "<option value=listOfEntries>" . _AM_FORMULIZE_SCREENTYPE_LISTOFENTRIES . "</option>\n";
print "</select></p>\n";

print "</td><td class=foot><center><input type=submit name=addscreen value=\"" . _AM_FORMULIZE_ADD_SCREEN_NOW . "\"></center></td></tr>\n";

print "</table></center></form>\n";

} // end of if there's a title

// END OF SCREENS UI
// **********************

	// navigation elements for bottom of page -- jwe 01/06/05
	echo '<center><table><tr>';
	
	if($title != '' AND !isset($_GET['table'])) { 
		echo '<td valign=top><center><a href="../admin/index.php?title='.$title.'">' . _AM_EDIT_ELEMENTS . ' <br><img src="../images/kedit.png"></a></center></td>';
		echo '<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>';
	}		
	
	echo '<td valign=top><center><a href="../admin/formindex.php">' . _AM_GOTO_MAIN . ' <br><img src="../images/formulize.gif" height=35></a></center></td>';
	echo '</tr></table></center>';

}


function upform($title)
{
	global $xoopsDB, $_POST, $myts, $eh;
	$admin = $myts->makeTboxData4Save($_POST["admin"]);
	$groupe = $myts->makeTboxData4Save($_POST["groupe"]);
	$email = $myts->makeTboxData4Save($_POST["email"]);
	$expe = $myts->makeTboxData4Save($_POST["expe"]);
	
	// added to handle new params -- jwe 7/25/07
	$singleentry = $myts->makeTboxData4Save($_POST["singleentry"]);
	$groupscope = $myts->makeTboxData4Save($_POST["groupscope"]);
	$headerlist = $_POST["headerlist"];


	foreach($headerlist as $arrayelement)
	{
		$cheaderlist = $cheaderlist . "*=+*:" . $arrayelement;
	}


	$showviewentries = $myts->makeTboxData4Save($_POST["showviewentries"]);
	$maxentries = $_POST["maxentries"];
	$coloreven = $_POST["coloreven"];
	$colorodd = $_POST["colorodd"];
	if($coloreven == "FFFFFF") {$coloreven = "default";}
	if($colorodd == "FFFFFF") {$colorodd = "default";}
	
	// ========= end added code - jwe
	// the 'if' checks below commented by jwe 8/30/04
	/*if((!empty($email)) && (!eregi("^[_a-z0-9.-]+@[a-z0-9.-]{2,}[.][a-z]{2,3}$",$email))){
		redirect_header("mailindex.php?title=$title", 2, _MD_ERROREMAIL);
	}
	if (empty($email) && empty($admin) && $groupe=="0" && empty($expe)) {
		redirect_header("mailindex.php?title=$title", 2, _MD_ERRORMAIL);
	}*/
	// sql updated with new fields -- jwe 7/25/04 , 7/28/04
	$sql = sprintf("UPDATE %s SET admin='%s', groupe='%s', email='%s', expe='%s', singleentry='%s', groupscope='%s', headerlist='%s', showviewentries='%s', maxentries='%s', even='%s', odd='%s' WHERE id_form='%s'", $xoopsDB->prefix("formulize_id"), $admin, $groupe, $email, $expe, $singleentry, $groupscope, $cheaderlist, $showviewentries, $maxentries, $coloreven, $colorodd, $title);
	$xoopsDB->query($sql) or $eh->show("0013");
	redirect_header("formindex.php",1,_formulize_FORMTITRE);
}


function addform()
{
	global $xoopsDB, $_POST, $myts, $eh;
	$title = $myts->makeTboxData4Save($_POST["newtitle"]);
	$admin = $myts->makeTboxData4Save($_POST["admin"]);
	$groupe = $myts->makeTboxData4Save($_POST["groupe"]);
	$email = $myts->makeTboxData4Save($_POST["email"]);
	$expe = $myts->makeTboxData4Save($_POST["expe"]);
	
	// added to handle new params -- jwe 7/25/07
	$singleentry = $myts->makeTboxData4Save($_POST["singleentry"]);
	$groupscope = $myts->makeTboxData4Save($_POST["groupscope"]);
	
	$showviewentries = $myts->makeTboxData4Save($_POST["showviewentries"]);
	$maxentries = $_POST["maxentries"];
	$coloreven = $_POST["coloreven"];
	$colorodd = $_POST["colorodd"];
	if($coloreven == "FFFFFF") {$coloreven = "default";}
	if($colorodd == "FFFFFF") {$colorodd = "default";}

	// ============ end of added code - jwe

	if (empty($title)) {
		redirect_header("formindex.php", 2, _MD_ERRORTITLE);
	}
	if((!empty($email)) && (!eregi("^[_a-z0-9.-]+@[a-z0-9.-]{2,}[.][a-z]{2,3}$",$email))){
		redirect_header("formindex.php", 2, _MD_ERROREMAIL);
	}
	if (empty($email) && empty($admin) && $groupe=="0" && empty($expe)) {
		redirect_header("formindex.php", 2, _MD_ERRORMAIL);
	}
	$title = stripslashes($title);
	$title = eregi_replace ("'", "`", $title);
	$title = eregi_replace ("&quot;", "`", $title);
	$title = eregi_replace ("&#039;", "`", $title);
	$title = eregi_replace ('"', "`", $title);
	$title = eregi_replace ('&', "_", $title);

	// updated to handle new params -- jwe 7/25/07 , 7/28/04
	$sql = sprintf("INSERT INTO %s (desc_form, admin, groupe, email, expe, singleentry, groupscope, showviewentries, maxentries, even, odd, tableform) VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')", $xoopsDB->prefix("formulize_id"), $title, $admin, $groupe, $email, $expe, $singleentry, $groupscope, $showviewentries, $maxentries, $coloreven, $colorodd, mysql_real_escape_string($_POST['tablename']));
	$xoopsDB->queryF($sql) or $eh->show("error insertion 1 dans addform");

	// need to get the new form id -- added by jwe sept 13 2005
	$newfid = $xoopsDB->getInsertId();
	
	if($newfid) {
		$sql2 = sprintf("INSERT INTO %s (itemname,itemurl) VALUES ('%s', '%s')", $xoopsDB->prefix("formulize_menu"), $title, XOOPS_URL.'/modules/formulize/index.php?title='.$title.'');
		$xoopsDB->queryF($sql2) or $eh->show("error insertion 2 dans addform");
	}

	if(!isset($_GET['table'])) {
		// altered sept 13 2005 to use new form id
		//redirect_header("index.php?title=$title",1,_formulize_FORMCREA);
		redirect_header("index.php?title=$newfid",1,_formulize_FORMCREA);
	} elseif($newfid) {
		
		// for tableforms, create all the form elements based on the table fields, and then redirect to the main page
		// 1. read all the fields in the table
		// 2. create an array of the information
		// 3. write it all out into the formulize table
		
		$result = $xoopsDB->query("SHOW COLUMNS FROM " . mysql_real_escape_string($_POST['tablename']));
		static $element_order = 0;
		$formulize_mgr =& xoops_getmodulehandler('elements');
		while($row = mysql_fetch_row($result)) {
			$element =& $formulize_mgr->create();
			$element->setVar('ele_caption', str_replace("_", " ", str_replace("'", "`", $row[0]))); // should be no apostrophes in field names, but we better make sure to follow Formulize convention!
			//$element->setVar('ele_delim', ""); // only set for radio and checkbox, but cannot be put into ele_value because ele_value is not a multidimensional array for those elements, so must be treated as a separate db field for now
			$element->setVar('ele_desc', "");
			$element->setVar('ele_colhead', "");
			$element->setVar('ele_req', 0);
			$element->setVar('ele_order', $element_order);
			$element_order = $element_order + 5;
			$element->setVar('ele_forcehidden', 0);
			$element->setVar('ele_uitext', "");
			$element->setVar('ele_value', array(0=>"", 1=>$xoopsModuleConfig['ta_rows'], 2=>$xoopsModuleConfig['ta_cols'], 3=>"")); // 0 is default, 1 is rows, 2 is cols, 3 is association to another element
			$element->setVar('id_form', $newfid);
			$element->setVar('ele_private', 0);
			$element->setVar('ele_display', 1);
      $element->setVar('ele_disabled', 0);
			$element->setVar('ele_type', 'textarea');
			if( !$formulize_mgr->insert($element) ){
				xoops_cp_header();
				echo $element->getHtmlErrors();
			}	
			unset($element);
		}
		
		redirect_header("formindex.php",1,_formulize_FORMCREA);
	}
		

}


if(!isset($op)){$op=" ";}
switch ($op) {
case "upform":
	upform($title);
	break;
case "addform":
	addform();
	break;
}



include 'footer.php';
xoops_cp_footer();

?>




