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

if ( file_exists("../language/".$xoopsConfig['language']."/main.php") ) {
	include "../language/".$xoopsConfig['language']."/main.php";
} else {
	include "../language/english/main.php";
}

if(!isset($HTTP_POST_VARS['op'])){
	$op = isset ($HTTP_GET_VARS['op']) ? $HTTP_GET_VARS['op'] : 'main';
}else {
	$op = $HTTP_POST_VARS['op'];
}



if(!isset($HTTP_POST_VARS['title'])){
	$title = isset ($HTTP_GET_VARS['title']) ? $HTTP_GET_VARS['title'] : '0';
}else {
	$title = $HTTP_POST_VARS['title'];
}

if($op == "clone") { clone($title, 0); }
// added August 12 2005 - jpc
if($op == "clonedata") { clone($title, 1); }


// Classe permissions
include_once XOOPS_ROOT_PATH.'/class/xoopsform/grouppermform.php';
$module_id = $xoopsModule->getVar('mid'); // recupere le numero id du module

$n = 0;
$m = 0;
//include XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";
include_once XOOPS_ROOT_PATH."/class/xoopstree.php";
include_once XOOPS_ROOT_PATH."/class/xoopslists.php";
include_once XOOPS_ROOT_PATH."/include/xoopscodes.php";
include_once XOOPS_ROOT_PATH."/class/module.errorhandler.php";
$myts =& MyTextSanitizer::getInstance();
$eh = new ErrorHandler;

global $title2, $op, $data;

	xoops_cp_header();


//Sélection des formulizes
	$sql="SELECT distinct desc_form, id_form FROM ".$xoopsDB->prefix("form_id");
	$res = mysql_query ( $sql );

if ( $res ) {
  while ( $row = mysql_fetch_row ( $res ) ) {
    $data[$row[1]] = $row[0];
  }
}

if( $op != 'addform' && $op != 'modform' && $op != 'renform' && $op != 'delform' && $op != 'showform' && $op != 'permform' && $op != 'permlinks' && $op != "permsub" && $op != "permeditor" && $op != "newpermform"){ // permlinks condition added jwe 08/29/04, sub and editor added May 23 2005
	echo '
	<table class="outer" width="100%">
	<th><center>'._FORM_OPT.'</center></th>
	
	<tr class="head"><td><A HREF="menu_index.php">'._FORM_MENU.'</a></td></tr>
	<tr class="head"><td><A HREF="../../system/admin.php?fct=preferences&amp;op=showmod&amp;mod='.$module_id.'">'._FORM_PREF.'</a></td></tr>
	</table><br>';
}

/******************* Affichage des formulizes *******************/
if( $op != 'addform' && $op != 'modform' && $op != 'renform' && $op != 'delform' && $op != 'showform' && $op != 'permform' && $op != 'permlinks' && $op != "permsub" && $op != "permeditor" && $op != "newpermform"){ // permlinks condition added jwe 08/29/04, sub and editor added May 23 2005

	// javascript to confirm deletion added by jwe 8/30/04

	print "<script type='text/javascript'>\n";
	print "	function confirmdel() {\n";
	print "		var answer = confirm ('" . _AM_CONFIRM_DEL . "')\n";
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




	echo '
	<table class="outer" width="100%">

	<th colspan=2><center>'._FORM_ACT.'</center></th>';

	$renom = new XoopsFormButton('', 'renom', _FORM_RENOM_IMG, 'submit');
	$hidden_renom = new XoopsFormHidden('op', 'renform');
	$sup = new XoopsFormButton('', 'sup', _FORM_SUP, 'submit');
	$hidden_sup = new XoopsFormHidden('op', 'delform');
	$mod = new XoopsFormButton('', 'modif', _FORM_MODIF, 'submit');
	$hidden_mod = new XoopsFormHidden('op', 'modform');
	$show = new XoopsFormButton('', 'show', _FORM_SHOW, 'submit');
	$hidden_show = new XoopsFormHidden('op', 'showform');
	
	echo '<tr><td class="head" ALIGN=center>'._FORM_CREAT.'</td>
	      <td class="odd"><A HREF="mailindex.php">
	      <center><img src="../images/filenew2.png" title='._FORM_NEW.' alt='._FORM_NEW.'>  </center></a></td></tr>';
	//old export section, not used any more
	//echo '<tr><td class="head" ALIGN=center>'._FORM_EXPORT.'</td>
	//      <td class="odd"><A HREF="export.php">
	//      <center><img src="../images/xls.png" alt='._FORM_ALT_EXPORT.'>  </center></a></td></tr>';


	// added framework link 01/11/05 -- jwe

	echo '<tr><td class="head" ALIGN=center>'._FORM_MODFRAME.'</td>
	      <td class="odd" align=center><A HREF="modframe.php">
	      <center><img src="../images/attach.png" title='._FORM_FRAME.' alt='._FORM_FRAME.'> </center></a></td></tr>';

//	echo '<tr><td class="head" ALIGN=center>'._FORM_MODPERM.' (OLD)</td>
//	      <td class="odd"><A HREF="formindex.php?op=permform">
//	      <center><img src="../images/perm.png" title='._FORM_PERM.' alt='._FORM_PERM.'> </center></a></td></tr>';

	echo '<tr><td class="head" ALIGN=center>'._FORM_MODPERM.'</td>
	      <td class="odd"><A HREF="formindex.php?op=newpermform">
	      <center><img src="../images/perm.png" title='._FORM_PERM.' alt='._FORM_PERM.'> </center></a></td></tr>';

	echo '<tr><td class="head" ALIGN=center>'._FORM_MODPERMLINKS.'</td>
	      <td class="odd"><A HREF="formindex.php?op=permlinks">
	      <center><img src="../images/perm.png" title='._FORM_PERMLINKS.' alt='._FORM_PERMLINKS.'> </center></a></td></tr>';

	
	echo '</table><table class="outer" width="100%"><br>';


	echo '<th colspan=2><center>'._AM_FORMUL.'</center></th>';


	// added August 12 2005 - jpc
	$gperm_handler = &xoops_gethandler('groupperm');
	$groups = $xoopsUser ? $xoopsUser->getGroups() : XOOPS_GROUP_ANONYMOUS;


	foreach($data as $id => $titre) {
	    if($gperm_handler->checkRight("edit_form", $id, $groups, $module_id))
        {
	        echo '<tr><td class="head" ALIGN=center>'.$titre.'</td>';

	        echo '<td class="odd" align="center">  
	             <A HREF="renom.php?title='.$id.'">  <img src="../images/signature.png" title="'._FORM_RENOM.'" alt="'._FORM_RENOM.'">  </a>';

	        if($gperm_handler->checkRight("delete_form", $id, $groups, $module_id))
	        {
	            echo '<A HREF="formindex.php?title='.$id.'&op=delform" onclick="return confirmdel();">  <img src="../images/editdelete.png" title="'._FORM_SUP.'" alt="'._FORM_SUP.'">  </a>';
			}
            
	        echo '<A HREF="formindex.php?title='.$id.'&op=modform">  <img src="../images/kedit.png" title="'._FORM_MODIF.'" alt="'._FORM_MODIF.'">  </a>';

	        //old display entries section, not used anymore 
	        //echo '<A HREF="formindex.php?title='.$id.'&op=showform">  <img src="../images/kfind.png" title="'._FORM_SHOW.'" alt="'._FORM_SHOW.'">  </a>';     

	        echo '<A HREF="mailindex.php?title='.$titre.'">  <img src="../images/xfmail.png" title="'._FORM_ADD.'" alt="'._FORM_ADD.'">  </a>';

	        // cloning added June 17 2005
	        echo '<A HREF="formindex.php?title='.$id.'&op=clone">  <img src="../images/clone.gif" title="'._FORM_MODCLONE.'" alt="'._FORM_MODCLONE.'"></a>';

	        // added August 12 2005 - jpc
	        echo '<A HREF="formindex.php?title='.$id.'&op=clonedata">  <img src="../images/clonedata.gif" title="'._FORM_MODCLONEDATA.'" alt="'._FORM_MODCLONEDATA.'"></a>';

	        echo '</td></tr>';
    	}	   
	}
	echo '</table>';

}

// copy a form -- added June 17 2005
function clone($title, $clonedata) {
	
	global $xoopsDB;
	
	// procedure:
	// get fid based on title
	// duplicate row for that fid in db but use next incremental fid
	// duplicate rows in form table for that fid, but use new fid and increment ele_ids of course
	// redraw page

	$fid = $title;

	$newtitle = _FORM_MODCLONED_FORM;	

	$getrow = q("SELECT * FROM " . $xoopsDB->prefix("form_id") . " WHERE id_form = $fid");

	$insert_sql = "INSERT INTO " . $xoopsDB->prefix("form_id") . " (";
	$start = 1;
	foreach($getrow[0] as $field=>$value) {
		if(!$start) { $insert_sql .= ", "; }
		$start = 0;
		$insert_sql .= $field;
	}
	$insert_sql .= ") VALUES (";
	$start = 1;
	foreach($getrow[0] as $field=>$value) {
		if($field == "id_form") { $value = ""; }
		if($field == "desc_form") { $value = $newtitle; }
		if(!$start) { $insert_sql .= ", "; }
		$start = 0;
		$insert_sql .= "\"$value\"";
	}
	$insert_sql .= ")";
	if(!$result = $xoopsDB->queryF($insert_sql)) {
		exit("error duplicating form: '$title'<br>SQL: $insert_sql");
	}

	$newfid = $xoopsDB->getInsertId();

	$getelements = q("SELECT * FROM " . $xoopsDB->prefix("form") . " WHERE id_form = $fid");
	foreach($getelements as $ele) {
       	$insert_sql = "INSERT INTO " . $xoopsDB->prefix("form") . " (";
       	$start = 1;
       	foreach($ele as $field=>$value) {
       		if(!$start) { $insert_sql .= ", "; }
       		$start = 0;
       		$insert_sql .= $field;
       	}
       	$insert_sql .= ") VALUES (";
       	$start = 1;
       	foreach($ele as $field=>$value) {
       		if($field == "id_form") { $value = "$newfid"; }
       		if($field == "ele_id") { $value = ""; }
       		if(!$start) { $insert_sql .= ", "; }
       		$start = 0;
			$value = addslashes($value);
       		$insert_sql .= "\"$value\"";
       	}
       	$insert_sql .= ")";
       	if(!$result = $xoopsDB->queryF($insert_sql)) {
       		exit("error duplicating elements in form: '$title'<br>SQL: $insert_sql");
       	}
	}

	$getmenu = q("SELECT * FROM " . $xoopsDB->prefix("form_menu") . " WHERE menuid=$fid");
	foreach($getmenu as $menu) {
       	$insert_sql = "INSERT INTO " . $xoopsDB->prefix("form_menu") . " (";
       	$start = 1;
       	foreach($menu as $field=>$value) {
       		if(!$start) { $insert_sql .= ", "; }
       		$start = 0;
       		$insert_sql .= $field;
       	}
       	$insert_sql .= ") VALUES (";
       	$start = 1;
       	foreach($menu as $field=>$value) {
       		if($field == "menuid") { $value = "$newfid"; }
			if($field == "itemname") { $value = "$newtitle"; }
       		if(!$start) { $insert_sql .= ", "; }
       		$start = 0;
			$value = addslashes($value);
       		$insert_sql .= "\"$value\"";
       	}
       	$insert_sql .= ")";
       	if(!$result = $xoopsDB->queryF($insert_sql)) {
       		exit("error duplicating menu entry for form: '$title'<br>SQL: $insert_sql");
       	}
	}

	$getCat = getMenuCat($fid); // returns the id of the cat entry
	if($getCat > 0) { // not in the general category, therefore write this form to the array for the cat it's in (general cat is not recorded)
		$fid_array = q("SELECT id_form_array FROM " . $xoopsDB->prefix("formulize_menu_cats") . " WHERE cat_id=$getCat");
		$new_fid_array = $fid_array[0]['id_form_array'] . $newfid . ","; // add a trailing comma, since there is a comma before and after each item even the first and last
		if(!$result = $xoopsDB->queryF("UPDATE " . $xoopsDB->prefix("formulize_menu_cats") . " SET id_form_array=\"$new_fid_array\" WHERE cat_id=$getCat")) {
			exit("error duplicating menu category for form: '$title'<br>SQL: $insert_sql");
		}
	}
    
    
	// added August 12 2005 - jpc
	// updated by jwe Aug 14 2005
    if($clonedata == 1)
    {

		// get the current max id_req
		$max_id_reqq = q("SELECT MAX(id_req) FROM " . $xoopsDB->prefix("form_form"));
		$max_id_req = $max_id_reqq[0]['MAX(id_req)'];

	    $curdata = q("SELECT * FROM " . $xoopsDB->prefix("form_form") . " WHERE id_form=$title ORDER BY id_req");

		$prev_id_req = 0;
	    foreach($curdata as $thisdata) {
			if($thisdata['id_req'] != $prev_id_req) { $max_id_req++; }
			$prev_id_req = $thisdata['id_req'];
		    $sql = "INSERT INTO " . $xoopsDB->prefix("form_form") . " (";

	       	$start = 1;
	        foreach($thisdata as $thisfield=>$value) {
	            // Handle the commas necessary between fields
	            if(!$start) { $sql .= ", "; }
	            $start = 0;
	            $sql .= "`$thisfield`";
	        } 

	        $sql .= ") VALUES (";

	       	$start = 1;
	        foreach($thisdata as $thisfield=>$value) {
	            // this is the key part that changes the id of the form to the new form that was just made
	            if($thisfield == "id_form") { $value = $newfid; }
	            if($thisfield == "id_req") { $value = $max_id_req; }
	            if($thisfield == "ele_id") { $value = ""; }
	            // Handle the commas necessary between fields
	            if(!$start) { $sql .= ", "; }
	            $start = 0;
			$value = addslashes($value);
	            $sql .= "\"$value\"";
	        }
	        $sql .= ")";

	        
//            echo $sql . "<br>";
            
            if(!$datares = $xoopsDB->queryF($sql)) {
		        exit("Error cloning data for form: $title.  SQL statement that caused the error:<br>$sql<br>");
	        }
	    }
/*    
Here's a high level view of how you can use q to do the cloning of data 
pretty easily I think...I'm skipping details obviously....

The form_form table looks something like this:
id_form, id_req, ele_id, ele_caption, ele_value....etc

And q nicely returns $curdata that will look like this:

$curdata[0]['id_form']
$curdata[0]['id_req']
$curdata[0]['ele_id']
$curdata[0]['ele_caption']
$curdata[0]['ele_value']
$curdata[1]['id_form']
$curdata[1]['id_req']
$curdata[1]['ele_id']
etc...

I like it because it gets rid of all the low level mucking around 
dealing with result objects from the DB, and gives me a nice two 
dimensional table with the data I want to work with.

But obviously there's lots of ways this could work and if you're more 
comfortable with a different approach, no problem.  If you do want to 
use this, then a rough pass at the code might be like this:

$curdata = q("SELECT * FROM " . $xoopsDB->prefix("form_form") . " WHERE 
id_form=$title);
foreach($curdata as $thisdata) {
$sql = "INSERT INTO xoops_form_form (";
foreach($thisdata as $thisfield=>$value) {
// I'm missing proper handling of the commas necessary between fields
$sql .= "\"$thisfield\";
} 
$sql .= ") VALUES (";
foreach($thisdata as $thisfield=>$value) {
// this is the key part that changes the id of the form to the new 
form that was just made
if($thisfield == "id_form") { $value = $newfid; }
// comma handling missing again
$sql .= \"$value\";
}
$sql .= ")";
if(!$datares = $xoopsDB->query($sql)) {
exit("Error cloning data for form: $title");
}
}
*/    
    }
}

function addform()
{
	global $xoopsDB, $HTTP_POST_VARS, $myts, $eh;
	$data[$title] = $myts->makeTboxData4Save($HTTP_POST_VARS["desc_form"]);
	$admin = $myts->makeTboxData4Save($HTTP_POST_VARS["admin"]);
	$groupe = $myts->makeTboxData4Save($HTTP_POST_VARS["groupe"]);
	$email = $myts->makeTboxData4Save($HTTP_POST_VARS["email"]);
	$expe = $myts->makeTboxData4Save($HTTP_POST_VARS["expe"]);
	if (empty($data[$title])) {
		redirect_header("formindex.php", 2, _MD_ERRORTITLE);
	}
	if((!empty($email)) && (!eregi("^[_a-z0-9.-]+@[a-z0-9.-]{2,}[.][a-z]{2,3}$",$email))){

		redirect_header("formindex.php", 2, _MD_ERROREMAIL);
	}
	if (empty($email) && empty($admin) && $groupe=="0" && empty($expe)) {
		redirect_header("formindex.php", 2, _MD_ERRORMAIL);
	}
	$data[$title] = stripslashes($data[$title]);
	$data[$title] = eregi_replace ("'", "`", $data[$title]);
	$data[$title] = eregi_replace ('"', "`", $data[$title]);
	$data[$title] = eregi_replace ('&', "_", $data[$title]);

	$sql = sprintf("INSERT INTO %s (desc_form, admin, groupe, email, expe) VALUES ('%s', '%s', '%s', '%s', '%s')", $xoopsDB->prefix("form_id"), $data[$title], $admin, $groupe, $email, $expe);
	$xoopsDB->queryF($sql) or $eh->show("error insertion 1 dans addform");

//	$sql2 = sprintf("INSERT INTO %s (itemname,itemurl) VALUES ('%s', '%s')", $xoopsDB->prefix("form_menu"), $title, XOOPS_URL.'/modules/formulize/index.php?title='.$data[$title].'');
	$sql2 = sprintf("INSERT INTO %s (itemname,itemurl) VALUES ('%s', '%s')", $xoopsDB->prefix("form_menu"), $title, XOOPS_URL.'/modules/formulize/index.php?title='.$data[$title].'');
	$xoopsDB->queryF($sql2) or $eh->show("error insertion 2 in addform");

	redirect_header("index.php?title=$data[$title]",1,_formulize_NEWFORMADDED);
}

function renform()
{
	global $xoopsDB, $HTTP_POST_VARS, $myts, $eh, $title, $title2, $data;
	$title2 = $myts->makeTboxData4Save($HTTP_POST_VARS["title2"]);
	//$title3 = $myts->makeTboxData4Save($HTTP_POST_VARS["desc_form3"]);
	if (empty($data[$title])) {
		redirect_header("formindex.php", 2, _MD_ERRORTITLE);
	}
	$title2 = stripslashes($title2);
	$title2 = eregi_replace ("'", "`", $title2);
	$title2 = eregi_replace ('"', "`", $title2);
	$title2 = eregi_replace ('&', "_", $title2);

	$sql = sprintf("UPDATE %s SET desc_form='%s' WHERE desc_form='%s'", $xoopsDB->prefix("form_id"), $title2, $data[$title]);
	$xoopsDB->queryF($sql) or $eh->show("error insertion 1 dans renform");

	$sql2 = sprintf("UPDATE %s SET itemname='%s',itemurl='%s' WHERE itemname='%s'", $xoopsDB->prefix("form_menu"), $title2, XOOPS_URL.'/modules/formulize/index.php?title='.$title2, $data[$title]);
	$xoopsDB->query($sql2) or $eh->show("error insertion 2 dans renform");
	redirect_header("formindex.php",1,_formulize_FORMMOD);
}

function modform()
{
	global $xoopsDB, $HTTP_POST_VARS, $myts, $eh, $title, $data;
	//$title5 = $myts->makeTboxData4Save($HTTP_POST_VARS["desc_form5"]);
	if (empty($data[$title])) {
		redirect_header("formindex.php", 2, _MD_ERRORTITLE);
	}
	redirect_header("index.php?title=$data[$title]",2,_formulize_FORMCHARG);
}

function delform()
{



	global $xoopsDB, $HTTP_POST_VARS, $myts, $eh, $title, $data, $xoopsModule; // module ID added by JWE 10/11/04
	$module_id = $xoopsModule->getVar('mid');

	//$title4 = $myts->makeTboxData4Save($HTTP_POST_VARS["desc_form4"]);
	$sql=sprintf("SELECT id_form FROM ".$xoopsDB->prefix("form_id")." WHERE desc_form='%s'",$data[$title]);
	$res = mysql_query ( $sql ) or die('Erreur SQL !<br>'.$requete.'<br>'.mysql_error());
	if ( $res ) {
  	while ( $row = mysql_fetch_row ( $res ) ) {
    	$id_form = $row[0];
  		}
	}
	$sql = sprintf("DELETE FROM %s WHERE desc_form = '%s'", $xoopsDB->prefix("form_id"), $data[$title]);
	$xoopsDB->queryF($sql) or $eh->show("error supression 1 dans delform");

	$sql = sprintf("DELETE FROM %s WHERE id_form = '%u'", $xoopsDB->prefix("form"), $title);
	$xoopsDB->queryF($sql) or $eh->show("error supression 2 dans delform");

	$sql = sprintf("DELETE FROM %s WHERE itemname = '%s'", $xoopsDB->prefix("form_menu"), $data[$title]);
	$xoopsDB->queryF($sql) or $eh->show("error supression 3 dans delform");

	$sql = sprintf("DELETE FROM %s WHERE id_form = '%u'", $xoopsDB->prefix("form_form"), $title);
	$xoopsDB->queryF($sql) or $eh->show("error supression 4 dans delform");

// PERMISSION DELETION NOT OPERATING PROPERLY RIGHT NOW	
/*	$perms = getFormulizePerms();
	foreach($perms as $perm_name) {
		xoops_groupperm_deletebymoditem ($module_id,$perm_name,$id_form) ;
	}
*/
	xoops_notification_deletebyitem ($module_id, "form", $id_form); // added by jwe-10/10/04 to handle removing notifications for a form once it's gone

	redirect_header("formindex.php",3,_formulize_FORMDEL._formulize_MSG_SUP);
}

function showform()
{
	global $xoopsDB, $HTTP_POST_VARS, $myts, $eh, $title, $data;
	//$title5 = $myts->makeTboxData4Save($HTTP_POST_VARS["desc_form5"]);
	if (empty($data[$title])) {
		redirect_header("formindex.php", 2, _MD_ERRORTITLE);
	}
	
	$sql = "SELECT count(*) FROM ".$xoopsDB->prefix("form_form")." WHERE id_form= ".$title;	
	$res = mysql_query($sql);		
	if ($res){
       		list($count) = mysql_fetch_row($res);
		if ($count == 0)
		redirect_header("formindex.php",2,_formulize_NOTSHOW.$data[$title]._formulize_NOTSHOW2);
		else redirect_header("consult.php?form=".$title,2,_formulize_FORMSHOW.$data[$title]);
	}
		

}

//
// ADDED BY JWE 8/29/04 TO CREATE A PERMISSION PAGE USED TO ASSIGN SCOPE TO LINKS WITH OTHER FORMS
//

function permlinks()
{

	global $xoopsDB, $xoopsModule;
	$module_id = $xoopsModule->getVar('mid');

	$currentperm = $_GET['currentperm'];
	if(!$currentperm)
	{
		$currentperm = "none";
	}

	// NEED TO GATHER ALL LINKS INTO ARRAYS FOR DRAWING TO THE SELECTION BOX
	$getlinksq = "SELECT id_form, ele_caption, ele_id FROM " . $xoopsDB->prefix("form") . " WHERE ele_type=\"select\" AND ele_value LIKE '%#*=:*%'";
	// print "$getlinksq<br>";
	$resgetlinksq = $xoopsDB->query($getlinksq);
	while ($rowlinksq = $xoopsDB->fetchRow($resgetlinksq))
	{
		//print_r($rowlinksq);
		//print "<br>";
		$linkformids[] = $rowlinksq[0];
		$linkcaptions[] = $rowlinksq[1];
		$linkeleids[] = $rowlinksq[2];
	}
	// now get all the form names for each form that has a link

	if($linkformids) // if a link was found...
	{

	foreach($linkformids as $aform)
	{
	$getlformnamesq = "SELECT desc_form FROM " . $xoopsDB->prefix("form_id") . " WHERE id_form = $aform";
	$resq = $xoopsDB->query($getlformnamesq);
	$rowq = $xoopsDB->fetchRow($resq);
	$linkformnames[] = $rowq[0];
	}
	// now the formid array is the ids
	// cations array is the linked select box names
	// names is the form names, 
	// eleids is the element ids, all indexed together
	array_multisort($linkformnames, $linkformids, $linkcaptions, $linkeleids);

	// set the item array...
	// 1. get a list of all the groups
	// 2. put the list into the array

	array($grouplist_id);
	array($grouplist_name);
	$grouplistq = "SELECT groupid, name FROM " . $xoopsDB->prefix("groups");
	$resgrouplistq = $xoopsDB->query($grouplistq); 
	while ($rowgrouplistq = $xoopsDB->fetchRow($resgrouplistq))
	{
		$grouplist_id[] = $rowgrouplistq[0];
		$grouplist_name[] = $rowgrouplistq[1];
	}

	// javascript function to reload page everytime someone selects a new linkbox...
		print "<script type='text/javascript'>\n";
		print "<!--//\n";
		print "	function redrawpage(perm) {\n";
//		print "	alert(perm)\n";
		print "		window.location = 'formindex.php?op=permlinks&currentperm=' + perm;\n";
		print "	}\n";
		print "//--></script>\n\n";

	print "<table class='outer' cellspacing='1'><tr><td class=head><center><form name=permselect action=formindex.php?op=permlinks method=post>\n";
	print "<p><b>" . _AM_FORM_CURPERMLINKS . "</b><br>";
	print "<SELECT name=currentperm size=1 onChange='redrawpage(document.permselect.currentperm.value)'>\n";
	for($i=0;$i<count($linkformnames);$i++)
	{
		if($currentperm == "none")
		{
			$currentperm = $linkeleids[$i];
		}
		print "<option value=\"" . $linkeleids[$i]. "\"";
		if ($currentperm == $linkeleids[$i])
		{
			print " selected";
		}
		print ">" . $linkformnames[$i] . ": " . $linkcaptions[$i] . "</option>\n";
	}

	print "</SELECT></p></form></center></td></tr></table>\n";

	$title_of_form = "";
	$perm_name = $currentperm;
	$perm_desc = '';
	$form = new XoopsGroupPermForm($title_of_form, $module_id, $perm_name, $perm_desc); 
	for($i=0;$i<count($grouplist_id);$i++)
	{
		$form->addItem($grouplist_id[$i], $grouplist_name[$i]);
	}

	echo $form->render();
	
	}
	else // of if no link was found
	{
	
		print "NO LINKS!";
		
	} // end of if a link was found
}

// TWO FUNCTIONS BELOW BORROWED FROM MULTI-GROUP PERM CODE
function saveList()
{
    global $xoopsDB;


    $op = 'insert'; 

	$name = $_POST['list_name'];
	$id = $_POST['list_id'];
	$groups = implode(",", $_POST['fs_groups']);  

    //echo "save list: name " . $name . ", id " . $id . ", groups " . $groups;
	if($id > 0)
	{
		// Get exisitng name to see if we update, or create new.	
	    $result = $xoopsDB->query("SELECT gl_name FROM ".$xoopsDB->prefix("group_lists")." WHERE gl_id='".$id."'");
	    if($xoopsDB->getRowsNum($result) > 0)
	    {
	        $entry = $xoopsDB->fetchArray($result); 

			//echo $entry['gl_name'];
			  
            if($entry['gl_name'] == $name)
			{ 
				$op = 'update';
			}				  
	    }
	}

	//echo "op" . $op;
	
	switch($op)
    {
		case 'insert':
	        $insert_query = "INSERT INTO ". $xoopsDB->prefix("group_lists") .
	            " (gl_id, gl_name, gl_groups) VALUES ('', '" . $name . "', '" . $groups . "')";

	        $insert_result = $xoopsDB->queryf($insert_query);
	        
	        return $xoopsDB->getInsertId();
						   
		case 'update':
	        $update_query = "UPDATE ". $xoopsDB->prefix("group_lists") .
	            " SET gl_groups = '" . $groups . "' WHERE gl_id='" . $id . "'";

	        $update_result = $xoopsDB->queryf($update_query);
			
			//echo $update_result . "-----" . $xoopsDB->error();  
	        
	        return $id;
	}        
}


function deleteList()
{
    global $xoopsDB;

	$id = $_POST['list_id'];
    
	//echo "Removing " . $id;

    
	if($id)
    {
	    $delete_query = "DELETE FROM ".$xoopsDB->prefix("group_lists") .
	        " WHERE gl_id='" . $id . "'";

	    $delete_result = $xoopsDB->queryf($delete_query);
	}        
}



function drawGroupList($list_id="") {

global $xoopsDB;
include_once XOOPS_ROOT_PATH."/class/xoopsformloader.php";

$form = new XoopsThemeForm(_formulize_MODFORM_TITLE, "groupform", "formindex.php?op=permsub");

$list_op = $_POST['list_op'];
$list_name = $_POST['list_name'];
global $new_list_id;
if(isset($new_list_id))
{
	//echo "new id " . $new_list_id;
	$list_id = $new_list_id;
}
else
{
	$list_id = $_POST['list_id'];
} 

	 
// Permissions
$fs_form_group_perms = new XoopsSimpleForm("", "fs_grouppermsform", "javascript:;");

$fs_select_groups = new XoopsFormSelect(_AM_MULTI_PERMISSIONS, 'fs_groups', null, 10, true);

if($list_op == 'select')
{
	if(isset($list_id))
	{
	    $saved_group_list_results = $xoopsDB->query("SELECT gl_groups FROM ".$xoopsDB->prefix("group_lists")." WHERE gl_id='" . $list_id . "'");
	    if($xoopsDB->getRowsNum($saved_group_list_results) > 0)
	    {
	        $saved_group_list_rowset = $xoopsDB->fetchArray($saved_group_list_results);
	        //echo $saved_group_list_rowset['gl_groups'];  
	        $saved_group_list_ids = explode(",", $saved_group_list_rowset['gl_groups']);
	        
	        $fs_select_groups->setValue($saved_group_list_ids);  
	    }
	}
	
	$list_op = '';  
}
else if($list_op == 'delete')
{
}
else if(isset($_POST['fs_groups']))
{
	$fs_groups = $_POST['fs_groups'];
	$saved_group_list_ids = $fs_groups;  
	
    $fs_select_groups->setValue($fs_groups);  
}


if($_POST['groupsubmit'] == $submit_value)
{	
	$modification_type = $_POST['fs_groupmodificationtype'];

	/*foreach ($saved_group_list_ids as $saved_group_list_id) 
	{
		//echo 'updating' . $saved_group_list_id;
		modifyMultiGroupItem($saved_group_list_id, $s_cat_value, $a_mod_value, $r_mod_value, $r_block_value, $modification_type);
	} */
}


$fs_member_handler =& xoops_gethandler('member');
$fs_xoops_groups =& $fs_member_handler->getGroups();

$fs_count = count($fs_xoops_groups);
for($i = 0; $i < $fs_count; $i++) 
{
	$fs_select_groups->addOption($fs_xoops_groups[$i]->getVar('groupid'), $fs_xoops_groups[$i]->getVar('name'));	 
}

$fs_permsorder = (isset($_POST['fs_permsorder'])) ? $_POST['fs_permsorder'] : 0;

if($fs_permsorder == 1)
{
	asort($fs_select_groups->_options);
	reset($fs_select_groups->_options);
}

$form->addElement($fs_select_groups);

$fs_select_groups_order = new XoopsFormRadio("", 'fs_permsorder', $fs_permsorder);
$fs_select_groups_order->addOptionArray(array('0' => _AM_MULTI_CREATION_ORDER, '1' => _AM_MULTI_ALPHABETICAL_ORDER));
$form->addElement($fs_select_groups_order);

// Lists
$fs_select_lists = new XoopsFormSelect(_AM_MULTI_GROUP_LISTS, 'fs_grouplistnames');
$fs_select_lists->addOption('0', _AM_MULTI_GROUP_LISTS_NOSELECT);

if(isset($list_id))
{
	$fs_select_lists->setValue($list_id); 
}

$result = $xoopsDB->query("SELECT gl_id, gl_name FROM ".$xoopsDB->prefix("group_lists")." ORDER BY gl_name");
if($xoopsDB->getRowsNum($result) > 0)
{
    while($group_list = $xoopsDB->fetchArray($result)) 
    {
        $fs_select_lists->addOption($group_list['gl_id'], $group_list['gl_name']);
    }
}

$form->addElement($fs_select_lists);

$fs_button_group = new XoopsFormElementTray("");

$fs_button_save_list = new XoopsFormButton("", "save_list", _AM_MULTI_SAVE_LIST, "submit");
$fs_button_group->addElement($fs_button_save_list);

$fs_button_delete_list = new XoopsFormButton("", "delete_list", _AM_MULTI_DELETE_LIST, "submit");
$fs_button_group->addElement($fs_button_delete_list);

$form->addElement($fs_button_group);

$list_op_hidden = new XoopsFormHidden("list_op", $list_op);
$form->addElement($list_op_hidden);
$list_name_hidden = new XoopsFormHidden("list_name", $list_name);
$form->addElement($list_name_hidden);
$list_id_hidden = new XoopsFormHidden("list_id", $list_id);
$form->addElement($list_id_hidden);

// Form list goes here:

	$form_list = new XoopsFormSelect(_formulize_FORM_LIST, 'fs_forms', null, 10, true);
	$sql="SELECT id_form,desc_form FROM ".$xoopsDB->prefix("form_id") . " ORDER BY desc_form";
	$res = mysql_query ( $sql );
	if ( $res ) {
		$tab = array();
		while ( $row = mysql_fetch_array ( $res ) ) {
			$form_list->addOption($row['id_form'], $row['desc_form']);
		  }
	}
	$form->addElement($form_list);


$submit_button = new XoopsFormButton("", "groupsubmit", _formulize_SHOW_PERMS, "submit");
$form->addElement($submit_button);
$form->display();




/*
 * Freeform Solutions -begin 
 */
?>
<script language="javascript">
<!--
	debug = false;


    function ChangeOrder()
    {
		if(debug) alert("Changing Order");

        document.groupform.submit(); 
    }


    function SelectList()
    {
		if(debug) alert("Selected " + document.groupform.elements[3].selectedIndex);
		 
		document.groupform.list_op.value = "select";
		document.groupform.list_id.value = document.groupform.elements[3].options[document.groupform.elements[3].selectedIndex].value;		  
		document.groupform.submit(); 
	}
	

    function SaveList()
    {
		default_value = "";

        if(document.groupform.elements[3].selectedIndex > 0)
		{
			default_value = document.groupform.elements[3].options[document.groupform.elements[3].selectedIndex].text; 		
		}   
		
		if(result = prompt("<? echo _AM_MULTI_GROUP_LIST_NAME ?>", default_value))
        {
            if(debug) alert("Adding " + result);
            
	        document.groupform.list_op.value = "save";
	        document.groupform.list_name.value = result;
	        document.groupform.list_id.value = document.groupform.elements[3].options[document.groupform.elements[3].selectedIndex].value;

			return true;  
        }
		
		return false;  
	}
	

    function DeleteList()
    {
		if(document.groupform.elements[3].selectedIndex > 0)
        {
	        if(confirm("<? echo _AM_MULTI_GROUP_LIST_DELETE ?> '" + 
	            document.groupform.elements[3].options[document.groupform.elements[3].selectedIndex].text + "'?"))
	        {
	            if(debug) alert("Removing " + 
                	document.groupform.elements[3].options[document.groupform.elements[3].selectedIndex].value);

	            document.groupform.list_op.value = "delete";
	            document.groupform.list_name.value = document.groupform.elements[3].options[document.groupform.elements[3].selectedIndex].value;
				
				return true;  
	        }
		}            
		
		return false;  
	}
	 	  

    document.groupform.elements[1].onclick = ChangeOrder;  
    document.groupform.elements[2].onclick = ChangeOrder;  

    document.groupform.elements[3].onchange = SelectList;  

	document.groupform.save_list.onclick = SaveList;  
	document.groupform.delete_list.onclick = DeleteList;  


-->
</script>
<?
/*
 * Freeform Solutions -end 
 */


}

function getFormulizePerms() {
	$formulize_perms = array("view_form", "add_own_entry", "update_own_entry", "delete_own_entry", "update_other_entries", "delete_other_entries", "add_proxy_entries", "view_groupscope", "view_globalscope", "create_reports", "update_own_reports", "delete_own_reports", "update_other_reports", "delete_other_reports", "publish_reports", "publish_globalscope", "edit_form", "delete_form", "include_in_framework");
	return $formulize_perms;
}


function newpermform($group_list="", $form_list="")
{

	print '<table><tr><td><center><p><a href="../admin/formindex.php">' . _AM_GOTO_MAIN . ' <br><img src="../images/formulize.gif" height=35></a></p></center></td></tr></table>';

	// carry on with drawing of the permissions list, if groups and forms are specified
	if(is_array($group_list) AND is_array($form_list) AND (isset($_POST['groupsubmit']) OR isset($_POST['apply']))) {

		global $xoopsDB, $xoopsModule;

		$formulize_perms = getFormulizePerms();

		$module_id = $xoopsModule->getVar('mid');

		$gperm_handler = &xoops_gethandler('groupperm');

		print "<h4>" . _formulize_MODPERM_TITLE . "</h4>";
		print "<form action=\"formindex.php?op=permeditor\" method = post>";
		print "<table width='100%' class='outer' cellpadding = 1>";		

		foreach($group_list as $group_id) {
			print "<input type=hidden name='hidden_group_" . $group_id . "' value='" . $group_id . "'>";
			// get groupname
			$group_name = $xoopsDB->query("SELECT name, description FROM " . $xoopsDB->prefix("groups") . " WHERE groupid=$group_id");	
			$gn = $xoopsDB->fetchArray($group_name);
			if($class == 'even') {
				$class = 'odd';
			} else {
				$class = 'even';
			}
			print "<tr><td valign=top class=head><p><b>" . $gn['name'] . "</b><br><br>" . $gn['description'] . "</p>";
			//description of permissions added July 28/05
			$perm_desc[0] = "view_form -- allows access to a form, and to your own entry or entries in it.";
			$perm_desc[1] = "add_own_entry -- allows people to create entries of their own.";
			$perm_desc[2] = "update_own_entry -- allows people to edit entries they make.";
			$perm_desc[3] = "delete_own_entry -- allows people to delete entries they make.";
			$perm_desc[4] = "update_other_entries -- allows people to update entries made by other people.";
			$perm_desc[5] = "delete_other_entries -- allows people to delete entries made by other people.";
			$perm_desc[6] = "add_proxy_entries -- allows people to make entries on behalf of other people.";
			$perm_desc[7] = "view_groupscope -- allows access to entries made by everyone in the same group(s).";
			$perm_desc[8] = "view_globalscope -- allows access to entries made by everyone in all groups.";
			$perm_desc[9] = "create_reports -- currently has no effect.  Everyone can make saved views.";
			$perm_desc[10] = "update_own_reports -- currently has no effect.  Everyone can update their own views.";
			$perm_desc[11] = "delete_own_reports -- currently has no effect.  Everyone can delete their own views.";
			$perm_desc[12] = "update_other_reports -- allows someone to make changes to published views.";
			$perm_desc[13] = "delete_other_reports -- allows someone to delete a published view.";
			$perm_desc[14] = "publish_reports -- allows someone to publish a saved view to their group(s).";
			$perm_desc[15] = "publish_globalscope -- allows someone to publish a saved view to any group.";
			$perm_desc[16] = "edit_form -- allows module admins access to a form.";
			$perm_desc[17] = "delete_form -- allows module admins to delete a form.";
			$perm_desc[18] = "include_in_framework -- currently has no effect.";
			print "</td><td class=$class valign=top>";
			print "<table><tr>";
			$colcounter = 0;
			$hidden_once = 0;
			foreach($form_list as $form_id) {
				if(!$hidden_once) { print "<input type=hidden name='hidden_form_" . $form_id . "' value='" . $form_id . "'>"; }
				if($colcounter == "5") {
					$colcounter = 0;
					print "</tr><tr>";
				}
				// get formname
				$form_name = $xoopsDB->query("SELECT desc_form FROM " . $xoopsDB->prefix("form_id") . " WHERE id_form=$form_id");
				$fn = $xoopsDB->fetchArray($form_name);
				$plistsize = count($formulize_perms);
				print "<td valign=bottom class=$class><p><b>" . $fn['desc_form'] . "</b><br><select size=$plistsize multiple='multiple' name='" . $group_id . "-" . $form_id . "[]'>";
				$i = 0;
				foreach($formulize_perms as $perm) {
					print "<option title='" . $perm_desc[$i] . "' value='$perm'" ; 
					$i++;
					if($gperm_handler->checkRight($perm, $form_id, $group_id, $module_id)) {
						print " selected='selected'";	
					} 
					print ">$perm</option>";
				}
				print "</select></p></td>";		
				$colcounter++;
			}
			print "</tr></table></td></tr>";
			$hidden_once = 1;
		}

		print "<tr><td class=foot></td><td class=foot><input type=submit name=apply value='" . _formulize_SUBMITTEXT . "'>&nbsp;&nbsp;&nbsp;<input type=reset name=reset value='" . _formulize_RESETBUTTON . "'>&nbsp;&nbsp;&nbsp;<input type=submit name=done value='" . _AM_FRAME_DONEBUTTON . "'></td></tr>";

		print "</table></form>";

	} else {
		drawGroupList();
	}

}

function permform() {

	global $xoopsDB, $xoopsModule;
	$module_id = $xoopsModule->getVar('mid');

	$currentperm = $_GET['currentperm'];
	if(!$currentperm)
	{
		$currentperm = "view";
	}

	$sql="SELECT id_form,desc_form FROM ".$xoopsDB->prefix("form_id");
	$res = mysql_query ( $sql );
	if ( $res ) {
		$tab = array();
		while ( $row = mysql_fetch_array ( $res ) ) {
		    $tab[$row['id_form']] = $row['desc_form']." (".$row['id_form'].")";
		  }
	}
	
	// javascript function to reload page everytime someone selects a new perm...
		print "<script type='text/javascript'>\n";
		print "<!--//\n";
		print "	function redrawpage(perm) {\n";
//		print "	alert(perm)\n";
		print "		window.location = 'formindex.php?op=permform&currentperm=' + perm;\n";
		print "	}\n";
		print "//--></script>\n\n";

	print "<table><tr><td valign=top><table class='outer' cellspacing='1'><tr><td class=head><center><form name=permselect action=formindex.php?op=permform method=post>\n";
	print "<p><b>" . _AM_FORM_CURPERM . "</b><br>";
	print "<SELECT name=currentperm size=1 onChange='redrawpage(document.permselect.currentperm.value)'>\n";
	print "<option value=view";
	if($currentperm == "view")
	{
		print " selected";
	}
	print ">" . _AM_FORM_PERMVIEW . "</option>\n";
	print "<option value=add";
	if($currentperm == "add")
	{
		print " selected";
	}
	print ">" . _AM_FORM_PERMADD . "</option>\n";
	print "<option value=admin";
	if($currentperm == "admin")
	{
		print " selected";
	}
	print ">" . _AM_FORM_PERMADMIN . "</option>\n";
	print "</SELECT></p></form></center></td></tr></table>\n";

	print '</td><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td valign=top>';
	print '<center><a href="../admin/formindex.php">' . _AM_GOTO_MAIN . ' <br><img src="../images/formulize.gif" height=35></a></center></td></tr></table>';


	$title_of_form = "";
	$perm_name = $currentperm;
	$perm_desc = '';
	$form = new XoopsGroupPermForm($title_of_form, $module_id, $perm_name, $perm_desc); 
	foreach($tab as $item_id => $item_name) {
		if($item_name != "") $form->addItem($item_id, $item_name);
	} 
	echo $form->render(); 


}

// FUNCTION DELETES PERMISSIONS FOR A FORM FOR A SPECIFIC GROUP
// Have to do this via DB since no method exists to do it for us
function updatePermsDB($mid, $gid, $fid, $perm, $add="") {
	global $xoopsDB;
	if($add) {
		// check if it exists
		$result = $xoopsDB->query("SELECT * FROM  " . $xoopsDB->prefix("group_permission") . " WHERE gperm_groupid='$gid' AND gperm_itemid='$fid' AND gperm_modid='$mid' AND gperm_name='$perm'");
		$res = $xoopsDB->getRowsNum($result);
		if($res == 0) {
			//print "Adding $perm<br>";
			$res2 = $xoopsDB->query("INSERT INTO " . $xoopsDB->prefix("group_permission") . " (gperm_modid, gperm_groupid, gperm_itemid, gperm_name) VALUES (\"$mid\", \"$gid\", \"$fid\", \"$perm\")");
		}
	} else {
		//print "Deleting $perm<br>";
		$result = $xoopsDB->query("DELETE FROM " . $xoopsDB->prefix("group_permission") . " WHERE gperm_groupid='$gid' AND gperm_itemid='$fid' AND gperm_modid='$mid' AND gperm_name='$perm'");
	}
}

function updateperms() {

	global $xoopsDB, $xoopsModule;
	$formulize_perms = getFormulizePerms();
	$module_id = $xoopsModule->getVar('mid');
	$gperm_handler = &xoops_gethandler('groupperm');

	foreach($_POST as $k=>$v) {
		if(strstr($k, "hidden_group_")) { // find list of groups

			$group_list[] = $v; 
		} elseif (strstr($k, "hidden_form_")) { // find list of forms
			$form_list[] = $v;
		}
	}
	foreach($group_list as $gid) {
		foreach($form_list as $fid) {
			$perm_key = $gid . "-" . $fid;
			if($_POST[$perm_key]) { // if perms were sent for this form for this group...
				//print "<br>";
				//print_r($_POST[$perm_key]);
				foreach($formulize_perms as $perm) {
					if(in_array($perm, $_POST[$perm_key])) { // if the perm was selected
						updatePermsDB($module_id, $gid, $fid, $perm, 1); // last value is the add flag
					} else {
						updatePermsDB($module_id, $gid, $fid, $perm, 0); // last value is the add flag
					}
				}
			} else { // no perms sent for this form at all
				foreach($formulize_perms as $perm) {
					updatePermsDB($module_id, $gid, $fid, $perm, 0); // last value is the add flag
				}
			}
		}
	}
}

// FUNCTION ACTUALLY SETS NEW PERMISSIONS, CALLED BY MIGRATEperms FUNCTION
function setNewPerms($oldperm, $form, $group, $module_id) {
	//print "<br>Perm: $oldperm ... Form: $form ... Group: $group ... Module: $module_id";
	$gperm_handler = &xoops_gethandler('groupperm');	
	switch($oldperm) {
		case "view":
			$result = $gperm_handler->addRight("view_form", $form, $group, $module_id);
			break;
		case "add":
			$gperm_handler->addRight('add_own_entry', $form, $group, $module_id);
			$gperm_handler->addRight('update_own_entry', $form, $group, $module_id);
			$gperm_handler->addRight('delete_own_entry', $form, $group, $module_id);
			$gperm_handler->addRight('create_reports', $form, $group, $module_id);
			$gperm_handler->addRight('update_own_reports', $form, $group, $module_id);
			$gperm_handler->addRight('delete_own_reports', $form, $group, $module_id);
			break;
		case "admin":
			$gperm_handler->addRight('update_other_entries', $form, $group, $module_id);
			$gperm_handler->addRight('delete_other_entries', $form, $group, $module_id);
			$gperm_handler->addRight('view_groupscope', $form, $group, $module_id);
			$gperm_handler->addRight('add_proxy_entries', $form, $group, $module_id);
			$gperm_handler->addRight('update_other_reports', $form, $group, $module_id);
			$gperm_handler->addRight('delete_other_reports', $form, $group, $module_id);
			$gperm_handler->addRight('publish_reports', $form, $group, $module_id);
			break;
	}
}

// FUNCTION TAKES OLD STYLE PERMS (VIEW, ADD, ADMIN) AND UPDATES THEM TO THE NEW PERM SYSTEM
// does not delete old perms, just adds new ones to roughly correspond to old functionality
function migratePerms() {

	if(!isset($_POST['migrateperms'])) {
		print "<form action=\"formindex.php?op=migrateperms\" method=post>";
		print "<input type = submit name=migrateperms value=\"Migrate Perms\">";
		print "</form>";
	} else {

	global $xoopsDB, $xoopsModule;
	$module_id = $xoopsModule->getVar('mid'); // get module id

	$gperm_handler = &xoops_gethandler('groupperm');
	$fs_member_handler =& xoops_gethandler('member');
	$groups =& $fs_member_handler->getGroups(); // get groups (big object)
	$sql="SELECT id_form FROM ".$xoopsDB->prefix("form_id") . " ORDER BY desc_form";
	$res = $xoopsDB->query( $sql );
	while ($array = $xoopsDB->fetchArray($res)) {
		$forms[] = $array['id_form']; // get forms
	}

	foreach($groups as $group) {
		$gid = $group->getVar('groupid');
		foreach($forms as $form) {
		
			if($gperm_handler->checkRight('add', $form, $gid, $module_id)) {
				//print "Setting add perm...$form...$gid...$module_id";
				setNewPerms("add", $form, $gid, $module_id);
			}
			if($gperm_handler->checkRight('view', $form, $gid, $module_id)) {
				setNewPerms("view", $form, $gid, $module_id);
			}
			if($gperm_handler->checkRight('admin', $form, $gid, $module_id)) {
				setNewPerms("admin", $form, $gid, $module_id);
			}
		}
	}

	print "Migration of Permissions Completed";
	}
}

// THIS FUNCTION CONVERTS THE UID/PROXYID DATA STORAGE CONVENTION FROM THE OLD TO THE NEW
// previously, last modification user was the uid, unless there was a proxy id present.
// now, uid is the creator and proxy id is the modifier.  Period, in all cases.
// this function copies the uid to the proxyid field if the proxyid field is currently equal to zero.
// data writing functions have been modified to comply with this new standard.
function migrateIds() {

	if(!isset($_POST['migrateids'])) {
		print "<form action=\"formindex.php?op=migrateids\" method=post>";
		print "<input type = submit name=migrateids value=\"Migrate Modification Uids\">";
		print "</form>";
	} else {
		// put logic here.
		global $xoopsDB;
		$sql = "UPDATE " . $xoopsDB->prefix("form_form") . " SET proxyid = uid WHERE proxyid = 0";
		if(!$result = $xoopsDB->query($sql)) {
			exit("Error:  Migration of Ids failed!  Please contact Freeform Solutions for assistance.");
		}
		print "Migration of Ids Completed";
	}
}

// THIS FUNCTION PROVIDES THE DB UPDATES FROM 1.6 RC TO 2.0 BETA
function migratedb() {

	if(!isset($_POST['migratedb'])) {
		print "<form action=\"formindex.php?op=migratedb\" method=post>";
		print "<input type = submit name=migratedb value=\"Migrate Database\">";
		print "</form>";
	} else {
		global $xoopsDB;
		// put logic here
		$sql[0] = "ALTER TABLE " . $xoopsDB->prefix("form_id") . " CHANGE `desc_form` `desc_form` VARCHAR( 255 ) NOT NULL"; 
		$sql[1] = "ALTER TABLE " . $xoopsDB->prefix("form_form") . " CHANGE `ele_id` `ele_id` INT( 5 ) UNSIGNED NOT NULL AUTO_INCREMENT";
		$sql[2] = "ALTER TABLE " . $xoopsDB->prefix("form_form") . " ADD `creation_date` DATE NOT NULL";
		$sql[3] = "ALTER TABLE " . $xoopsDB->prefix("form_form") . " ADD INDEX `i_id_req` ( `id_req` )";
		$sql[4] = "ALTER TABLE " . $xoopsDB->prefix("form_form") . " ADD INDEX `i_id_form` ( `id_form` )";
		$sql[5] = "ALTER TABLE " . $xoopsDB->prefix("form_form") . " ADD INDEX `i_ele_caption` ( `ele_caption` )";
		$sql[6] = "ALTER TABLE " . $xoopsDB->prefix("form_form") . " ADD INDEX `i_ele_value` ( `ele_value` ( 20 ) )";
		$sql[7] = "ALTER TABLE " . $xoopsDB->prefix("form_form") . " ADD INDEX `i_uid` ( `uid` )";
		$sql[8] = "CREATE TABLE " . $xoopsDB->prefix("formulize_saved_views") . " (
  sv_id smallint(5) NOT NULL auto_increment,
  sv_name varchar(255) default NULL,
  sv_pubgroups varchar(255) default NULL,
  sv_owner_uid int(5),
  sv_mod_uid int(5),
  sv_formframe varchar(255) default NULL,
  sv_mainform varchar(255) default NULL,
  sv_lockcontrols tinyint(1),
  sv_hidelist tinyint(1),
  sv_hidecalc tinyint(1),
  sv_asearch varchar(255) default NULL,
  sv_sort varchar(255) default NULL,
  sv_order varchar(30) default NULL,
  sv_oldcols varchar(255) default NULL,
  sv_currentview varchar(255) default NULL,
  sv_calc_cols varchar(255) default NULL,
  sv_calc_calcs varchar(255) default NULL,
  sv_calc_blanks varchar(255) default NULL,
  sv_calc_grouping varchar(255) default NULL,
  sv_quicksearches varchar(255) default NULL,
  PRIMARY KEY (sv_id)
) TYPE=MyISAM;";
		$sql[9] = "CREATE TABLE " . $xoopsDB->prefix("group_lists") . " (
  gl_id smallint(5) unsigned NOT NULL auto_increment,
  gl_name varchar(255) NOT NULL default '',
  gl_groups text default '',
  PRIMARY KEY (gl_id),
  UNIQUE gl_name_id (gl_name)
) TYPE=MyISAM;";

		$sql[10] = "CREATE TABLE " . $xoopsDB->prefix("formulize_onetoone_links") . " (
  link_id smallint(5) NOT NULL auto_increment,
  main_form int(5),
  link_form int(5),
  PRIMARY KEY (`link_id`)
) TYPE=MyISAM;";

		$sql[11] = "CREATE TABLE " . $xoopsDB->prefix("formulize_menu_cats") . " (
  cat_id smallint(5) NOT NULL auto_increment,
  cat_name varchar(255) default NULL,
  id_form_array varchar(255) default NULL,
  PRIMARY KEY (`cat_id`)
) TYPE=MyISAM;";

// END OF SQL 

		for($i=0;$i<12;$i++) {
			if(!$result = $xoopsDB->query($sql[$i])) {
				exit("Error migrating DB from 1.6rc to 2.0beta.  SQL dump: " . $sql[$i]);
			}
		} 
		print "Migration of DB completed.";
	}
}


switch ($op) {
case "addform":
	addform();
	break;
case "renform":
	renform();
	break;
case "modform":
	modform();
	break;
case "delform":
	delform();
	break;
case "showform":
	showform();
	break;
case "permform":
	permform();
	break;

// added jwe 8/29/04
case "permlinks":
	permlinks();
	break;
// added jwe May 23, 2005

case "permsub":
	if(isset($_POST['list_op']))
		{
			$list_op = $_POST['list_op']; 

			switch ($list_op) {
   				case "save":
					$new_list_id = saveList(); 
					break;
   				case "delete":
					deleteList(); 
					break;
			} 
		}
	newpermform($_POST['fs_groups'], $_POST['fs_forms']);
	break;

case "permeditor":
	updateperms();
	if(isset($_POST['done'])) {
		newpermform();
	} else {
		foreach($_POST as $k=>$v) {
			if(strstr($k, "hidden_group_")) { // find list of groups
				$group_list[] = $v; 
			} elseif (strstr($k, "hidden_form_")) { // find list of forms
				$form_list[] = $v;
			}
		}
		newpermform($group_list, $form_list);
	}
	break;

case "newpermform":
	newpermform();
	break;

case "migrateperms":
	migratePerms();
	break;

case "migrateids":
	migrateIds();
	break;

case "migratedb":
	migratedb();
	break;


}

include 'footer.php';
    xoops_cp_footer();

?>




