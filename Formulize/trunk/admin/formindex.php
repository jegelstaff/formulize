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

if(!isset($_POST['op'])){
	$op = isset ($_GET['op']) ? $_GET['op'] : 'main';
}else {
	$op = $_POST['op'];
}

if($op=="main") {
	header('Location: '.XOOPS_URL.'/modules/formulize/admin/ui.php');
	exit();
}

if(!isset($_POST['title'])){
	$title = isset ($_GET['title']) ? $_GET['title'] : '0';
}else {
	$title = $_POST['title'];
}


// changed clone to cloneFormulize for php5 compat. August 17 2005 - jpc

if($op == "clone") { cloneFormulize($title, 0); }
// added August 12 2005 - jpc
if($op == "clonedata") { cloneFormulize($title, 1); }


include_once XOOPS_ROOT_PATH . "/modules/formulize/class/forms.php"; // form class
include_once XOOPS_ROOT_PATH.'/class/xoopsform/grouppermform.php'; // Classe permissions
$module_id = $xoopsModule->getVar('mid'); // recupere le numero id du module

$n = 0;
$m = 0;
include_once XOOPS_ROOT_PATH."/class/xoopstree.php";
include_once XOOPS_ROOT_PATH."/class/xoopslists.php";
include_once XOOPS_ROOT_PATH."/include/xoopscodes.php";
include_once XOOPS_ROOT_PATH."/class/module.errorhandler.php";
$myts =& MyTextSanitizer::getInstance();
$eh = new ErrorHandler;

global $title2, $op, $data;

	xoops_cp_header();


//Sélection des formulizes

	$sql="SELECT distinct desc_form, id_form, tableform, lockedform FROM ".$xoopsDB->prefix("formulize_id");
	$res = $xoopsDB->query( $sql );

$titlesForSort = array();
$data = array();
$tableforms = array();
$lockedforms = array();
if ( $res ) {
  while ( $row = $xoopsDB->fetchRow( $res ) ) {
    $data[$row[1]] = trans($row[0]);
		$tableforms[$row[1]] = $row[2];
		$lockedforms[$row[1]] = $row[3];
  }
}

asort($data); // sorts forms alphabetically by title (and asort, as opposed to sort, keeps key/value association in the array)

if( $op != 'addform' && $op != 'modform' && $op != 'renform' && $op != 'delform' && $op != 'lockform' && $op != 'showform' && $op != 'permform' && $op != "permsub" && $op != "permeditor" && $op != "newpermform"){ 
	echo '
	<table class="outer" width="100%">
	<th><center>'._FORM_OPT.'</center></th>
	
	<tr class="head"><td><A HREF="menu_index.php">'._FORM_MENU.'</a></td></tr>
	<tr class="head"><td><A HREF="../../system/admin.php?fct=preferences&amp;op=showmod&amp;mod='.$module_id.'">'._FORM_PREF.'</a></td></tr>
	</table><br>';
}

/******************* Affichage des formulizes *******************/
if( $op != 'addform' && $op != 'modform' && $op != 'renform' && $op != 'delform' && $op != 'lockform' && $op != 'showform' && $op != 'permform' && $op != "permsub" && $op != "permeditor" && $op != "newpermform"){ 

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
	
	print "	function confirmlock() {\n";
	print "		var answer = confirm ('" . _AM_CONFIRM_LOCK . "')\n";
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
				
	echo '<tr><td class="head" ALIGN=center>'._FORM_TABLE_CREAT.'</td>
	      <td class="odd"><A HREF="mailindex.php?table=true">
	      <center><img src="../images/filenew3.gif" title='._FORM_TABLE_NEW.' alt='._FORM_TABLE_NEW.'>  </center></a></td></tr>';
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

	echo '</table><table class="outer" width="100%"><br>';


	echo '<th colspan=2><center>'._AM_FORMUL.'</center></th>';


	// added August 12 2005 - jpc
	$gperm_handler = &xoops_gethandler('groupperm');
	$groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);


	foreach($data as $id => $titre) {
	    if($gperm_handler->checkRight("edit_form", $id, $groups, $module_id))
        {
	        echo '<tr><td class="head">';
					if($lockedforms[$id]) {
						echo '<img src="../images/perm.png" align="left">';
					}
					echo trans($titre).'<br>(id: '.$id.')</td>';

	        echo '<td class="odd" align="center">
          
                <table cellpadding=10><tr>
								<td rowspan=3><nobr><a href="../index.php?fid='.$id.'" target="_blank">' . _AM_VIEW_FORM . ' <img src="../images/kfind.png" title="'._AM_VIEW_FORM.'" alt="'._AM_VIEW_FORM.'"></a></nobr></td>
								<td>';
								
					if(!$lockedforms[$id]) {
							echo '<nobr><A HREF="renom.php?title='.$id.'">'._FORM_RENAME_TEXT.' <img src="../images/signature.png" title="'._FORM_RENOM.'" alt="'._FORM_RENOM.'">  </a></nobr>';
					} else {
						  echo "&nbsp;";
					}
					echo "</td>";
								
					if($gperm_handler->checkRight("delete_form", $id, $groups, $module_id))
	        {
	            echo '<td><nobr><A HREF="formindex.php?title='.$id.'&op=delform" onclick="return confirmdel();">'._FORM_DELETE_TEXT.' <img src="../images/editdelete.png" title="'._FORM_SUP.'" alt="'._FORM_SUP.'">  </a>';
							
							if(!$lockedforms[$id]) {
								echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
								<A HREF="formindex.php?title='.$id.'&op=lockform" onclick="return confirmlock();">'._FORM_LOCK_TEXT.' <img src="../images/perm.png" title="'._FORM_LOCK.'" alt="'._FORM_LOCK.'">  </a>';
							}
							
							echo '</nobr></td></tr>';
							
    			} else {
						echo '<td></td></tr>';
					}
								

          if($tableforms[$id] == "" AND !$lockedforms[$id]) {  
						echo '<tr><td><nobr><A HREF="index.php?title='.$id.'">'._FORM_EDIT_ELEMENTS_TEXT.' <img src="../images/kedit.png" title="'._FORM_MODIF.'" alt="'._FORM_MODIF.'">  </a></nobr> </td>';
					

						//old display entries section, not used anymore 
						//echo '<A HREF="formindex.php?title='.$id.'&op=showform">  <img src="../images/kfind.png" title="'._FORM_SHOW.'" alt="'._FORM_SHOW.'">  </a>';     
					} else {
						echo '<tr>';
					}
					
					$tableFormsFlag = $tableforms[$id] == "" ? "" : "&table=true";
					if(!$lockedforms[$id]) {
						echo '<td><nobr><A HREF="mailindex.php?title='.$id.$tableFormsFlag.'">'._FORM_EDIT_SETTINGS_TEXT.' <img src="../images/xfmail.png" title="'._FORM_ADD.'" alt="'._FORM_ADD.'">  </a></nobr></td></tr><tr>';
					} else {
						echo '<td>&nbsp;</td></tr><tr>';
					}
					
					if($tableforms[$id] == "") {
						// cloning added June 17 2005
						echo '<td><nobr><A HREF="formindex.php?title='.$id.'&op=clone">'._FORM_CLONE_TEXT.' <img src="../images/clone.gif" title="'._FORM_MODCLONE.'" alt="'._FORM_MODCLONE.'"></a></nobr> </td>';

						// added August 12 2005 - jpc
						echo '<td><nobr><A HREF="clone.php?title='.$id.'">'._FORM_CLONEDATA_TEXT.' <img src="../images/clonedata.gif" title="'._FORM_MODCLONEDATA.'" alt="'._FORM_MODCLONEDATA.'"></a></nobr> </td></tr>';
					} else {
						echo '</tr>';
					}

          

	        echo '</tr></table></td></tr>';
    	}	   
	}
	echo '</table>';

}

// copy a form -- added June 17 2005
function cloneFormulize($title, $clonedata) {
	
	global $xoopsDB;
	
	// procedure:
	// get fid based on title
	// duplicate row for that fid in db but use next incremental fid
	// duplicate rows in form table for that fid, but use new fid and increment ele_ids of course
	// redraw page

	$fid = $title;


  // check if the default title is already in use as the name of a form...keep looking for the title and add numbers onto the end, until we don't find a match any longer
  $foundTitle = 1;
  $titleCounter = 0;
  while($foundTitle) {
    if(!isset($titleSearchingFor)) {
      $titleSearchingFor = _FORM_MODCLONED_FORM;
    } else {
      $titleCounter++;
      $titleSearchingFor = _FORM_MODCLONED_FORM." $titleCounter";
    }
    $titleCheckSQL = "SELECT desc_form FROM " . $xoopsDB->prefix("formulize_id") . " WHERE desc_form = '$titleSearchingFor'";
    $titleCheckResult = $xoopsDB->query($titleCheckSQL);
    $foundTitle = $xoopsDB->getRowsNum($titleCheckResult);
  }
  $newtitle = $titleSearchingFor;	// use whatever the last searched for title is (because it was not found)

	$getrow = q("SELECT * FROM " . $xoopsDB->prefix("formulize_id") . " WHERE id_form = $fid");

	$insert_sql = "INSERT INTO " . $xoopsDB->prefix("formulize_id") . " (";
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
		if($field == "headerlist") { $value = ""; }
		if(!$start) { $insert_sql .= ", "; }
		$start = 0;
		$insert_sql .= "\"$value\"";
	}
	$insert_sql .= ")";
	if(!$result = $xoopsDB->queryF($insert_sql)) {
		exit("error duplicating form: '$title'<br>SQL: $insert_sql<br>".mysql_error());
	}

	$newfid = $xoopsDB->getInsertId();

	$getelements = q("SELECT * FROM " . $xoopsDB->prefix("formulize") . " WHERE id_form = $fid");
        $oldNewEleIdMap = array();
	foreach($getelements as $ele) { // for each element in the form....
                $insert_sql = "INSERT INTO " . $xoopsDB->prefix("formulize") . " (";
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
                  if($field == "ele_handle") {
                    if($value === $ele['ele_id']) {
                      $value = "replace_with_ele_id";
                    } else {
                      $value .= "_cloned";
                    }
                    $oldNewEleIdMap[$ele['ele_handle']] = $value;
                  }
                  if(!$start) { $insert_sql .= ", "; }
                  $start = 0;
                  $value = addslashes($value);
                  $insert_sql .= "\"$value\"";
                }
                $insert_sql .= ")";
                if(!$result = $xoopsDB->queryF($insert_sql)) {
                  exit("error duplicating elements in form: '$title'<br>SQL: $insert_sql<br>".mysql_error());
                }
                if($oldNewEleIdMap[$ele['ele_handle']] == "replace_with_ele_id") {
                  $oldNewEleIdMap[$ele['ele_handle']] = $xoopsDB->getInsertId();
                }
	}

  // replace ele_id flags that need replacing
  $replaceSQL = "UPDATE ". $xoopsDB->prefix("formulize") . " SET ele_handle=ele_id WHERE ele_handle=\"replace_with_ele_id\"";
  if(!$result = $xoopsDB->queryF($replaceSQL)) {
    exit("error setting the ele_handle values for the new form.<br>".mysql_error());
  }

	$getmenu = q("SELECT * FROM " . $xoopsDB->prefix("formulize_menu") . " WHERE menuid=$fid");
	foreach($getmenu as $menu) {
       	$insert_sql = "INSERT INTO " . $xoopsDB->prefix("formulize_menu") . " (";
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
       		exit("error duplicating menu entry for form: '$title'<br>SQL: $insert_sql<br>".mysql_error());
       	}
	}

	$getCat = getMenuCat($fid); // returns the id of the cat entry
	if($getCat > 0) { // not in the general category, therefore write this form to the array for the cat it's in (general cat is not recorded)
		$fid_array = q("SELECT id_form_array FROM " . $xoopsDB->prefix("formulize_menu_cats") . " WHERE cat_id=$getCat");
		$new_fid_array = $fid_array[0]['id_form_array'] . $newfid . ","; // add a trailing comma, since there is a comma before and after each item even the first and last
		if(!$result = $xoopsDB->queryF("UPDATE " . $xoopsDB->prefix("formulize_menu_cats") . " SET id_form_array=\"$new_fid_array\" WHERE cat_id=$getCat")) {
			exit("error duplicating menu category for form: '$title'<br>SQL: $insert_sql<br>".mysql_error());
		}
	}
    
        // Need to create the new data table now -- July 1 2007
        $formHandler =& xoops_getmodulehandler('forms', 'formulize');
        if(!$tableCreationResult = $formHandler->createDataTable($newfid, $fid, $oldNewEleIdMap)) { 
                print "Error: could not make the necessary new datatable for form " . $newfid . ".  Please delete the cloned form and report this error to <a href=\"mailto:formulize@freeformsolutions.ca\">Freeform Solutions</a>.<br>".mysql_error();
        }
        
          
    
	// added August 12 2005 - jpc
	// updated by jwe Aug 14 2005
    if($clonedata == 1)
    {
        // July 1 2007 -- changed how cloning happens with new data structure
        include_once XOOPS_ROOT_PATH . "/modules/formulize/class/data.php"; // formulize data handler
        $dataHandler = new formulizeDataHandler($newfid);
        if(!$cloneResult = $dataHandler->cloneData($fid, $oldNewEleIdMap)) {
                print "Error:  could not clone the data from the old form to the new form.  Please delete the cloned form and report this error to <a href=\"mailto:formulize@freeformsolutions.ca\">Freeform Solutions</a>.<br>".mysql_error();
        }
    }
    
    // replicate permissions of the original form on the new cloned form
    include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";
    $criteria = new CriteriaCompo();
    $criteria->add(new Criteria('gperm_itemid', $fid), 'AND');
    $criteria->add(new Criteria('gperm_modid', getFormulizeModId()), 'AND');
    $gperm_handler = xoops_gethandler('groupperm');
    $oldFormPerms = $gperm_handler->getObjects($criteria);
    foreach($oldFormPerms as $thisOldPerm) {
      // do manual inserts, since addRight uses the xoopsDB query method, which won't do updates/inserts on GET requests
      $sql = "INSERT INTO ".$xoopsDB->prefix("group_permission"). " (gperm_name, gperm_itemid, gperm_groupid, gperm_modid) VALUES ('".$thisOldPerm->getVar('gperm_name')."', $newfid, ".$thisOldPerm->getVar('gperm_groupid').", ".getFormulizeModId().")";
      $res = $xoopsDB->queryF($sql);
    }
    
}

function addform()
{
	global $xoopsDB, $_POST, $myts, $eh;
	$data[$title] = $myts->makeTboxData4Save($_POST["desc_form"]);
	if (empty($data[$title])) {
		redirect_header("formindex.php", 2, _MD_ERRORTITLE);
	}
	
	$data[$title] = stripslashes($data[$title]);
	$data[$title] = eregi_replace ("'", "`", $data[$title]);
	$data[$title] = eregi_replace ("&quot;", "`", $data[$title]);
	$data[$title] = eregi_replace ("&#039;", "`", $data[$title]);
	$data[$title] = eregi_replace ('"', "`", $data[$title]);
	$data[$title] = eregi_replace ('&', "_", $data[$title]);

	$sql = sprintf("INSERT INTO %s (desc_form) VALUES ('%s')", $xoopsDB->prefix("formulize_id"), $data[$title]);
	$xoopsDB->queryF($sql) or $eh->show("error insertion 1 dans addform");

//	$sql2 = sprintf("INSERT INTO %s (itemname,itemurl) VALUES ('%s', '%s')", $xoopsDB->prefix("formulize_menu"), $title, XOOPS_URL.'/modules/formulize/index.php?title='.$data[$title].'');
	$sql2 = sprintf("INSERT INTO %s (itemname,itemurl) VALUES ('%s', '%s')", $xoopsDB->prefix("formulize_menu"), $title, XOOPS_URL.'/modules/formulize/index.php?title='.$data[$title].'');
	$xoopsDB->queryF($sql2) or $eh->show("error insertion 2 in addform");

	redirect_header("index.php?title=$data[$title]",1,_formulize_NEWFORMADDED);
}

function renform()
{
	global $xoopsDB, $_POST, $myts, $eh, $title, $title2, $data;
	$title2 = $myts->makeTboxData4Save($_POST["title2"]);
	//$title3 = $myts->makeTboxData4Save($_POST["desc_form3"]);
	if (empty($data[$title])) {
		redirect_header("formindex.php", 2, _MD_ERRORTITLE);
	}
	$title2 = stripslashes($title2);
	$title2 = eregi_replace ("'", "`", $title2);
	$title2 = eregi_replace ("&quot;", "`", $title2);
	$title2 = eregi_replace ("&#039;", "`", $title2);
	$title2 = eregi_replace ('"', "`", $title2);
	$title2 = eregi_replace ('&', "_", $title2);

	$sql = sprintf("UPDATE %s SET desc_form='%s' WHERE desc_form='%s'", $xoopsDB->prefix("formulize_id"), $title2, $data[$title]);
	$xoopsDB->queryF($sql) or $eh->show("error insertion 1 dans renform");

	$sql2 = sprintf("UPDATE %s SET itemname='%s',itemurl='%s' WHERE itemname='%s'", $xoopsDB->prefix("formulize_menu"), $title2, XOOPS_URL.'/modules/formulize/index.php?title='.$title2, $data[$title]);
	$xoopsDB->query($sql2) or $eh->show("error insertion 2 dans renform");
	redirect_header("formindex.php",1,_formulize_FORMMOD);
}

function modform($fid)
{
	global $xoopsDB, $_POST, $myts, $eh, $title, $data;
	//$title5 = $myts->makeTboxData4Save($_POST["desc_form5"]);
	if (empty($data[$title])) {
		redirect_header("formindex.php", 2, _MD_ERRORTITLE);
	}
	redirect_header("index.php?title=$fid",2,_formulize_FORMCHARG);
}


function lockform() {
	
	$gperm_handler = &xoops_gethandler('groupperm');
	global $xoopsUser, $xoopsModule;
	$groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
	$module_id = $xoopsModule->getVar('mid');
	if(!$gperm_handler->checkRight("delete_form", intval($_GET['title']), $groups, $module_id)) {
		redirect_header("formindex.php",3,_NO_PERM);
	}
	$form_handler = xoops_getmodulehandler('forms', 'formulize');
	if(!$form_handler->lockForm(intval($_GET['title']))) {
		redirect_header("formindex.php",3,_formulize_FORMLOCK_FAILED);
	} else {
		redirect_header("formindex.php",3,_formulize_FORMLOCK);
	}
}

function delform()
{

	$gperm_handler = &xoops_gethandler('groupperm');
	global $xoopsUser, $xoopsModule;
	$groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
	$module_id = $xoopsModule->getVar('mid');
	if(!$gperm_handler->checkRight("delete_form", intval($_GET['title']), $groups, $module_id)) {
		redirect_header("formindex.php",3,_NO_PERM);
	}

	global $xoopsDB, $_POST, $myts, $eh, $title, $data; 
	$module_id = $xoopsModule->getVar('mid'); // module ID added by JWE 10/11/04

	//$title4 = $myts->makeTboxData4Save($_POST["desc_form4"]);
	$sql=sprintf("SELECT id_form FROM ".$xoopsDB->prefix("formulize_id")." WHERE desc_form='%s'",$data[$title]);
	$res = $xoopsDB->query ( $sql ) or die('Erreur SQL !<br>'.$requete.'<br>'.mysql_error());
	if ( $res ) {
  	while ( $row = $xoopsDB->fetchRow ( $res ) ) {
    	$id_form = $row[0];
  		}
	}
        
        // NOW WE DROP THE DATA TABLE, INSTEAD OF DELETING FROM FORMULIZE FORM
/*
	$sql = sprintf("DELETE FROM %s WHERE id_form = '%u'", $xoopsDB->prefix("formulize_form"), $title);
	$xoopsDB->queryF($sql) or $eh->show("error supression 4 dans delform");
*/
        $form_handler = xoops_getmodulehandler('forms', 'formulize');
        $form_handler->dropDataTable($title);
        
	$sql = sprintf("DELETE FROM %s WHERE desc_form = '%s'", $xoopsDB->prefix("formulize_id"), $data[$title]);
	$xoopsDB->queryF($sql) or $eh->show("error supression 1 dans delform");

	$sql = sprintf("DELETE FROM %s WHERE id_form = '%u'", $xoopsDB->prefix("formulize"), $title);
	$xoopsDB->queryF($sql) or $eh->show("error supression 2 dans delform");

	$sql = sprintf("DELETE FROM %s WHERE itemname = '%s'", $xoopsDB->prefix("formulize_menu"), $data[$title]);
	$xoopsDB->queryF($sql) or $eh->show("error supression 3 dans delform");


// PERMISSION DELETION NOT OPERATING PROPERLY RIGHT NOW	
/*	$perms = getFormulizePerms();
	foreach($perms as $perm_name) {
		xoops_groupperm_deletebymoditem ($module_id,$perm_name,$id_form) ;
	}
*/
	xoops_notification_deletebyitem ($module_id, "form", $id_form); // added by jwe-10/10/04 to handle removing notifications for a form once it's gone

	redirect_header("formindex.php",3,_formulize_FORMDEL._formulize_MSG_SUP);
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
	$sql="SELECT id_form,desc_form FROM ".$xoopsDB->prefix("formulize_id") . " ORDER BY desc_form";
	$res = mysql_query ( $sql );
	if ( $res ) {
		$tab = array();
		while ( $row = mysql_fetch_array ( $res ) ) {
			$form_list->addOption($row['id_form'], $row['desc_form']);
		  }
	}
	$form->addElement($form_list);


// same perms for all groups option added Aug 1 2006 -- jwe
$sameForAllGroups = new XoopsFormRadioYN(_formulize_SAME_PERMS, 'sameperms', 0);
$form->addElement($sameForAllGroups);

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
		
		if(result = prompt("<?php echo _AM_MULTI_GROUP_LIST_NAME ?>", default_value))
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
	        if(confirm("<?php echo _AM_MULTI_GROUP_LIST_DELETE ?> '" + 
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
<?php
/*
 * Freeform Solutions -end 
 */


}

function getFormulizePerms() {
	$formulize_perms = array("view_form", "add_own_entry", "update_own_entry", "delete_own_entry", "update_other_entries", "delete_other_entries", "add_proxy_entries", "view_groupscope", "view_globalscope", "view_private_elements", "create_reports", "update_own_reports", "delete_own_reports", "update_other_reports", "delete_other_reports", "publish_reports", "publish_globalscope", "set_notifications_for_others", "import_data", "edit_form", "delete_form", "include_in_framework", "bypass_form_menu");
	return $formulize_perms;
}


function newpermform($group_list="", $form_list="")
{

	print '<table><tr><td><center><p><a href="../admin/formindex.php">' . _AM_GOTO_MAIN . ' <br><img src="../images/formulize.gif" height=35></a></p></center></td></tr></table>';

	// carry on with drawing of the permissions list, if groups and forms are specified
	if(is_array($group_list) AND is_array($form_list) AND (isset($_POST['groupsubmit']) OR isset($_POST['apply']) OR isset($_POST['addcon']))) {

		global $xoopsDB, $xoopsModule;

		$formulize_perms = getFormulizePerms();
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
			$perm_desc[9] = "view_private_elements -- allows access to form elements that have been flagged as private.";
			$perm_desc[10] = "create_reports -- currently has no effect.  Everyone can make saved views.";
			$perm_desc[11] = "update_own_reports -- currently has no effect.  Everyone can update their own views.";
			$perm_desc[12] = "delete_own_reports -- currently has no effect.  Everyone can delete their own views.";
			$perm_desc[13] = "update_other_reports -- allows someone to make changes to published views.";
			$perm_desc[14] = "delete_other_reports -- allows someone to delete a published view.";
			$perm_desc[15] = "publish_reports -- allows someone to publish a saved view to their group(s).";
			$perm_desc[16] = "publish_globalscope -- allows someone to publish a saved view to any group.";
			$perm_desc[17] = "set_notifications_for_others -- allows someone to set the groups to be notified when entries change.";
			$perm_desc[18] = "import_data -- allows someone to import entries from a .csv file.";
			$perm_desc[19] = "edit_form -- allows module admins access to a form.";
			$perm_desc[20] = "delete_form -- allows module admins to delete a form.";
			$perm_desc[21] = "include_in_framework -- currently has no effect.";
			$perm_desc[22] = "bypass_form_menu -- deprecated, currently has no effect.";

		$module_id = $xoopsModule->getVar('mid');

		print "<h4>" . _formulize_MODPERM_TITLE . "</h4>";
		print "<form action=\"formindex.php?op=permeditor\" name=\"permeditor\" method = \"post\">";
		print "<table width='100%' class='outer' cellpadding = 1>";		

		// set same perms for all selected groups -- added Aug 1 2006 -- jwe
		if($_POST['sameperms'] == 1 AND count($group_list) > 1) {
			print "<tr><td valign=top class=head><p><b>" . _formulize_SAME_PERMS_TEXT . "</b><br><br>";
			print "<input type=hidden name=sameperms value=1>\n";
			$countgroups = 1;
			foreach($group_list as $group_id) {
      			print "<input type=hidden name='hidden_group_" . $group_id . "' value='" . $group_id . "'>";
      			// get groupname
      			$group_name = $xoopsDB->query("SELECT name, description FROM " . $xoopsDB->prefix("groups") . " WHERE groupid=$group_id");	
      			$gn = $xoopsDB->fetchArray($group_name);
				print $gn['name'];
				if($countgroups < count($group_list)) { print ", "; }
				$countgroups++;
			}
			print "</p>";

			drawformperms($form_list, $formulize_perms, $perm_desc);

		} else {
      		$hidden_once = 0;
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
				print "<tr><td valign=top class=head><p><b>" . $gn['name'] . "</b><br><br>" . $gn['description'] . "<br><br>"._AM_FORCE_GROUPSCOPE_HELP."</p>";      			
				drawformperms($form_list, $formulize_perms, $perm_desc, $group_id, $class, false, $hidden_once);
      			$hidden_once = 1;
      		}

		} // end of if same perms for all groups or not

		print "<tr><td class=foot></td><td class=foot><input type=submit name=apply value='" . _formulize_SUBMITTEXT . "'>&nbsp;&nbsp;&nbsp;<input type=reset name=reset value='" . _formulize_RESETBUTTON . "'>&nbsp;&nbsp;&nbsp;<input type=submit name=done value='" . _AM_FRAME_DONEBUTTON . "'></td></tr>";

		print "</table></form>";

	} else {
		drawGroupList();
	}

}

// THIS FUNCTION DRAWS IN THE FORM PERMISSION LISTS -- CALLED INTERNALLY FROM THE NEWPERMFORM FUNCTION
function drawformperms($form_list, $formulize_perms, $perm_desc, $group_id="all", $class="even", $same=true, $hidden_once=0) {

	global $xoopsDB, $module_id;
	$gperm_handler =& xoops_gethandler('groupperm');
	$form_handler = xoops_getmodulehandler('forms', 'formulize');
	

      print "</td><td class=$class valign=top>";
      print "<table><tr>";
      $colcounter = 0;
	foreach($form_list as $form_id) {
			$formObject = $form_handler->get(intval($form_id));
			if($formObject->getVar('lockedform')) {
				continue;
			}	
		
      	if(!$hidden_once OR $same) { print "<input type=hidden name='hidden_form_" . $form_id . "' value='" . $form_id . "'>"; }
      	if($colcounter == "5") {
      		$colcounter = 0;
      		print "</tr><tr>";
      	}
      	// get formname
      	$form_name = $xoopsDB->query("SELECT desc_form FROM " . $xoopsDB->prefix("formulize_id") . " WHERE id_form=$form_id");
      	$fn = $xoopsDB->fetchArray($form_name);
      	$plistsize = count($formulize_perms);
      	print "<td valign=bottom class=$class><p><b>" . $fn['desc_form'] . "</b><br><select size=$plistsize multiple='multiple' name='" . $group_id . "-" . $form_id . "[]'>";
      	$i = 0;
      	foreach($formulize_perms as $perm) {
      		print "<option title='" . $perm_desc[$i] . "' value='$perm'" ; 
      		$i++;
      		if(($gperm_handler->checkRight($perm, $form_id, $group_id, $module_id) AND !$same) OR in_array($perm, $_POST['all-' . $form_id])) {
      			print " selected='selected'";	
      		} 
      		print ">$perm</option>";
      	}
      	print "</select></p>";
				
				// now add in the grouplist selection box, which can optionally be used to force groupscope to be over certain other group(s).  If no groups selected, then the user's groups with view_form are used instead. -- added July 31 2009, jwe
				if(!$same) {
					$groupDataQueryResult = $xoopsDB->query("SELECT groupid, name FROM " .$xoopsDB->prefix("groups"));
					$numGroups = $xoopsDB->getRowsNum($groupDataQueryResult);
					$glistsize = $numGroups < 10 ? $numGroups : 10;
					include_once XOOPS_ROOT_PATH . "/modules/formulize/class/usersGroupsPerms.php";
					if(!isset($$formulize_permHandler{$form_id})) {
						$formulize_permHandler{$form_id} = new formulizePermHandler($form_id);
					}
					$groupScopeGroupIds = $formulize_permHandler{$form_id}->getGroupScopeGroupIds($group_id);
					print "<p><b>"._AM_FORCE_GROUPSCOPE_INTRO."</b><br><select size=$glistsize multiple='multiple' name='".$group_id."-".$form_id."-groupscope[]'>\n";
					while($groupDataArray = $xoopsDB->fetchArray($groupDataQueryResult)) {
						$selectedGroup = in_array($groupDataArray['groupid'], $groupScopeGroupIds) ? " selected='selected'" : "";
						print "<option value=".$groupDataArray['groupid'] . $selectedGroup . ">".$groupDataArray['name']."</option>\n";
					}
					print "</select></p>";
					
					// now add in the per group filter options, which can be used to limit for all time what entries certain groups see in any list of entries (these filters are applied by extract.php at all times) -- added by jwe Aug 28 2009
					print "<p><b>"._AM_PER_GROUP_FILTER_INTRO."</b><br>";
					// need to get the current filter settings for this group on this form, if any
					if(!isset($form_handler)) {
						$form_handler = xoops_getmodulehandler('forms', 'formulize');
					}
					$formObject = $form_handler->get($form_id);
					$filterSettings = $formObject->getVar('filterSettings');
					$filterSettingsToSend = isset($filterSettings[$group_id]) ? $filterSettings[$group_id] : "";
					$filterUI = formulize_createFilterUI($filterSettingsToSend, "filter_".$form_id."_".$group_id, $form_id, "permeditor", "oom");
					print $filterUI;
					
				}
				
				print "</td>";		
      	$colcounter++;
      }
      print "</tr></table></td></tr>";
}

// FUNCTION DELETES AND ADDS PERMISSIONS FOR A FORM FOR A SPECIFIC GROUP
// "Have to do this via DB since no method exists to do it for us" -- not true, could create xoopsgroupperm objects and then insert them in the DB using the insert method in the gperm handler
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
	$form_handler = xoops_getmodulehandler('forms', 'formulize');

	foreach($_POST as $k=>$v) {
		if(strstr($k, "hidden_group_")) { // find list of groups

			$group_list[] = $v; 
		} elseif (strstr($k, "hidden_form_")) { // find list of forms
			$formObject = $form_handler->get(intval($v));
			if($formObject->getVar('lockedform')) {
					continue;
			}
			$form_list[] = $v;
		}
	}

	foreach($group_list as $gid) {
		foreach($form_list as $fid) {
			$perm_key = isset($_POST['all-'.$fid]) ? 'all-'.$fid : $gid . "-" . $fid;
			$filter_key = "filter_".$fid."_".$gid;
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
			
			// handle groupscope groups
			include_once XOOPS_ROOT_PATH . "/modules/formulize/class/usersGroupsPerms.php";
   		if(!isset($formulize_permHandler{$fid})) {
		  	$formulize_permHandler{$fid} = new formulizePermHandler($fid);
			}
			if(!$formulize_permHandler{$fid}->setGroupScopeGroups($gid, $_POST[$perm_key."-groupscope"])) {
				print "Error: could not set the groupscope groups for form $fid.<br>";
			}
			
			/* ALTERED - 20100316 - freeform - jeff/julian - start */
			// index all the per-group form filters so we can then run queries to update this info after the looping is done
			if($_POST[$filter_key]=="all") {
				$groupsToClear[$fid][] = $gid;
			} else {
				if($_POST["new_".$filter_key."_term"] != "") {
					$_POST[$filter_key."_elements"][] = $_POST["new_".$filter_key."_element"];
					$_POST[$filter_key."_ops"][] = $_POST["new_".$filter_key."_op"];
					$_POST[$filter_key."_terms"][] = $_POST["new_".$filter_key."_term"];
					$_POST[$filter_key."_types"][] = "all";
				}
				if($_POST["new_".$filter_key."_oom_term"] != "") {
					$_POST[$filter_key."_elements"][] = $_POST["new_".$filter_key."_oom_element"];
					$_POST[$filter_key."_ops"][] = $_POST["new_".$filter_key."_oom_op"];
					$_POST[$filter_key."_terms"][] = $_POST["new_".$filter_key."_oom_term"];
					$_POST[$filter_key."_types"][] = "oom";
				}
				$filterSettings[$fid][$gid][0] = $_POST[$filter_key."_elements"];
				$filterSettings[$fid][$gid][1] = $_POST[$filter_key."_ops"];
				$filterSettings[$fid][$gid][2] = $_POST[$filter_key."_terms"];
				$filterSettings[$fid][$gid][3] = $_POST[$filter_key."_types"];
			}
			/* ALTERED - 20100316 - freeform - jeff/julian - stop */
		
		}
	}

	// now update the per group filters
	foreach($groupsToClear as $fid=>$gids) {
		$form_handler->clearPerGroupFilters($gids, $fid);
	}
	foreach($filterSettings as $fid=>$groupsSettingsData) {
		$form_handler->setPerGroupFilters($groupsSettingsData, $fid);
	}
	
}


// database patch logic for 4.0 and higher
function patch40() {
	// CHECK THAT THEY ARE AT 3.1 LEVEL, IF NOT, LINK TO PATCH31
	// Check for ele_handle being 255 in formulize table
	global $xoopsDB;
	$fieldStateSQL = "SHOW COLUMNS FROM " . $xoopsDB->prefix("formulize") ." LIKE 'ele_handle'"; // note very odd use of LIKE as a clause of its own in SHOW statements, very strange, but that's what MySQL does
	if(!$fieldStateRes = $xoopsDB->queryF($fieldStateSQL)) {
		print "Error: could not determine if your Formulize database structure is up to date.  Please contact <a href=\"mailto:formulize@freeformsolutions.ca\">Freeform Solutions</a> for assistance.<br>\n"; 
		return false;
	}
	$fieldStateData = $xoopsDB->fetchArray($fieldStateRes);
	$dataType = $fieldStateData['Type'];
	if($dataType != "varchar(255)") {
		print "<h1>Your database schema is out of date.  You must run \"patch31\" before running \"patch40\".</h1>\n";
    print "<p><a href=\"" . XOOPS_URL . "/modules/formulize/admin/formindex.php?op=patch31\">Click here to run \"patch31\".</a></p>\n";
		return;
	}
	
  if(!isset($_POST['patch40'])) {
		print "<form action=\"formindex.php?op=patch40\" method=post>";
		print "<h1>Warning: this patch makes several changes to the database.  Backup your database prior to applying this patch!</h1>";
		print "<input type = submit name=patch40 value=\"Apply Database Patch for Formulize 4.0\">";
		print "</form>";
	} else {
		// PATCH LOGIC GOES HERE
		
		$testsql = "SHOW TABLES";
		$resultst = $xoopsDB->query($testsql);
		while($table = $xoopsDB->fetchRow($resultst)) {
			$existingTables[] = $table[0];
		}

		$sql = array();		

		if(!in_array($xoopsDB->prefix("formulize_groupscope_settings"), $existingTables)) {
			$sql[] = "CREATE TABLE `".$xoopsDB->prefix("formulize_groupscope_settings")."` (
  `groupscope_id` int(11) NOT NULL auto_increment,
  `groupid` int(11) NOT NULL default 0,
	`fid` int(11) NOT NULL default 0,
  `view_groupid` int(11) NOT NULL default 0,
  PRIMARY KEY (`groupscope_id`),
  INDEX i_groupid (`groupid`),
	INDEX i_fid (`fid`),
  INDEX i_view_groupid (`view_groupid`)
) TYPE=MyISAM;";
		}
		
		if(!in_array($xoopsDB->prefix("formulize_group_filters"), $existingTables)) {
			$sql[] = "CREATE TABLE `".$xoopsDB->prefix("formulize_group_filters")."` (
  `filterid` int(11) NOT NULL auto_increment,
  `fid` int(11) NOT NULL default 0,
  `groupid` int(11) NOT NULL default 0,
  `filter` text NOT NULL default '',
  PRIMARY KEY (`filterid`),
  INDEX i_fid (`fid`),
  INDEX i_groupid (`groupid`)
) TYPE=MyISAM;";
		}
	
		if(!in_array($xoopsDB->prefix("formulize_applications"), $existingTables)) {
			$sql[] = "CREATE TABLE `".$xoopsDB->prefix("formulize_applications")."` (
  `appid` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `description` text NOT NULL,
  PRIMARY KEY (`appid`)
) TYPE=MyISAM;";
		}

		if(!in_array($xoopsDB->prefix("formulize_application_form_link"), $existingTables)) {
			$sql[] = "CREATE TABLE `".$xoopsDB->prefix("formulize_application_form_link")."` (
  `linkid` int(11) NOT NULL auto_increment,
  `appid` int(11) NOT NULL default 0,
  `fid` int(11) NOT NULL default 0,
  PRIMARY KEY (`linkid`),
  INDEX i_fid (`fid`),
  INDEX i_appid (`appid`)
) TYPE=MyISAM;";
		}
		
		if(!in_array($xoopsDB->prefix("formulize_screen_form"), $existingTables)) {
			$sql[] = "CREATE TABLE `".$xoopsDB->prefix("formulize_screen_form")."` (
  `formid` int(11) NOT NULL auto_increment,
  `sid` int(11) NOT NULL default 0,
  `donedest` varchar(255) NOT NULL default '',
  `savebuttontext` varchar(255) NOT NULL default '',
  `alldonebuttontext` varchar(255) NOT NULL default '',
  `displayheading` tinyint(1) NOT NULL default 0,
  `reloadblank` tinyint(1) NOT NULL default 0,
  PRIMARY KEY (`formid`),
  INDEX i_sid (`sid`)
) TYPE=MyISAM;";
		}
		
		if(!in_array($xoopsDB->prefix("formulize_advanced_calculations"), $existingTables)) {
			$sql[] = "CREATE TABLE `".$xoopsDB->prefix("formulize_advanced_calculations")."` (
  `acid` int(11) NOT NULL auto_increment,
  `fid` int(11) NOT NULL default '0',
  `name` varchar(255) NOT NULL default '',
  `description` text NOT NULL,
  `input` text NOT NULL,
  `output` text NOT NULL,
  `steps` text NOT NULL,
  `steptitles` text NOT NULL,
  `fltr_grps` text NOT NULL,
  `fltr_grptitles` text NOT NULL,
  PRIMARY KEY  (`acid`),
  KEY `i_fid` (`fid`)
) TYPE=MyISAM;";
		}
		
	
		$sql['add_encrypt'] = "ALTER TABLE " . $xoopsDB->prefix("formulize") . " ADD `ele_encrypt` tinyint(1) NOT NULL default '0'";
		$sql['add_lockedform'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_id") . " ADD `lockedform` tinyint(1) NULL default NULL";
		$sql['drop_from_formulize_id_admin'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_id") . " DROP `admin`";
		$sql['drop_from_formulize_id_groupe'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_id") . " DROP `groupe`";
		$sql['drop_from_formulize_id_email'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_id") . " DROP `email`";
		$sql['drop_from_formulize_id_expe'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_id") . " DROP `expe`";
		$sql['drop_from_formulize_id_maxentries'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_id") . " DROP `maxentries`";
		$sql['drop_from_formulize_id_even'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_id") . " DROP `even`";
		$sql['drop_from_formulize_id_odd'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_id") . " DROP `odd`";
		$sql['drop_from_formulize_id_groupscope'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_id") . " DROP `groupscope`";
		$sql['drop_from_formulize_id_showviewentries'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_id") . " DROP `showviewentries`";
		$sql['add_filtersettings'] = "ALTER TABLE " . $xoopsDB->prefix("formulize") . " ADD `ele_filtersettings` text NOT NULL";
		$sql['ele_type_100'] = "ALTER TABLE " . $xoopsDB->prefix("formulize") . " CHANGE `ele_type` `ele_type` varchar(100) NOT NULL default ''";
		$sql['ele_disabled_text'] = "ALTER TABLE ". $xoopsDB->prefix("formulize") ." CHANGE `ele_disabled` `ele_disabled` text NOT NULL ";
		$sql['ele_display_dropindex'] = "ALTER TABLE ". $xoopsDB->prefix("formulize") ." DROP INDEX `ele_display`";
		$sql['ele_display_text'] = "ALTER TABLE ". $xoopsDB->prefix("formulize") ." CHANGE `ele_display` `ele_display` text NOT NULL ";
		$sql['ele_display_addindex'] = "ALTER TABLE ". $xoopsDB->prefix("formulize") ." ADD INDEX `ele_display` ( `ele_display` ( 255 ) )";
		$sql['sep_to_areamodif'] = "UPDATE ". $xoopsDB->prefix("formulize") ." SET ele_type='areamodif' WHERE ele_type='sep'";
		$sql['add_defaultform'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_id") . " ADD `defaultform` int(11) NOT NULL default 0";
		$sql['add_defaultlist'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_id") . " ADD `defaultlist` int(11) NOT NULL default 0";
		$sql['add_menutext'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_id") . " ADD `menutext` varchar(255) default 'Use the form\'s title'";
		$sql['add_useadvcalcs'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_screen_listofentries") . " ADD `useadvcalcs` varchar(255) NOT NULL default ''";
		$sql['add_not_elementemail'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_notification_conditions") . " ADD `not_cons_elementemail` smallint(5) NOT NULL default 0";
		$sql['add_form_handle'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_id") . " ADD `form_handle` varchar(255) NOT NULL";
		$sql['id_form_to_form_handle'] = "UPDATE " . $xoopsDB->prefix("formulize_id") . " SET form_handle = id_form WHERE form_handle IS NULL OR form_handle = ''";
		foreach($sql as $key=>$thissql) {
			if(!$result = $xoopsDB->query($thissql)) {
				if($key === "add_encrypt") {
					print "ele_encrypt field already added.  result: OK<br>";
				} elseif($key === "add_lockedform") {
					print "lockedform field already added.  result: OK<br>";
				} elseif($key === "add_filtersettings") {
					print "element filtersettings field already added.  result: OK<br>";
				} elseif($key === "add_defaultform") {
					print "defaultform field already added.  result: OK<br>";
				} elseif($key === "add_defaultlist") {
					print "defaultlist field already added.  result: OK<br>";
				} elseif($key === "add_menutext") {
					print "menutext field already added.  result: OK<br>";
				} elseif($key === "add_useadvcalcs") {
					print "useadvcalcs field already added.  result: OK<br>";
				} elseif($key === "add_not_elementemail") {
					print "elementemail notification option already added.  result: OK<br>";
				} elseif($key === "add_form_handle") {
					print "form handles already added.  result: OK<br>";
				} elseif(strstr($key, 'drop_from_formulize_id_')) {
					continue;					
				} else {
					exit("Error patching DB for Formulize 4.0. SQL dump:<br>" . $thissql . "<br>".mysql_error()."<br>Please contact <a href=mailto:formulize@freeformsolutions.ca>Freeform Solutions</a> for assistance.");
				}
			} 
		}
		
		// if there is a framework handles table present, then we need to check for a few things to ensure the integrity of code and our ability to disambiguate inputs to the API
		if(in_array($xoopsDB->prefix("formulize_framework_elements"), $existingTables)) {
			
			// need to change rules...framework handles must now be globally unique, so we can disambiguate them from each other when we are passed just a framework handle
			$uniqueSQL = "SELECT elements.ele_caption, elements.ele_id, elements.ele_handle, handles.fe_handle, handles.fe_frame_id FROM ".$xoopsDB->prefix("formulize")." as elements, ".$xoopsDB->prefix("formulize_framework_elements")." as handles WHERE EXISTS (SELECT 1 FROM ".$xoopsDB->prefix("formulize_framework_elements")." as checkhandles WHERE handles.fe_handle = checkhandles.fe_handle AND handles.fe_element_id != checkhandles.fe_element_id) AND handles.fe_element_id = elements.ele_id AND handles.fe_handle != \"\" ORDER BY handles.fe_handle";
			$uniqueRes = $xoopsDB->query($uniqueSQL);
			$haveWarning = false;
			$warningIdentifier = array();
			$warningContents = array();
			if($xoopsDB->getRowsNum($uniqueRes)) {
				$haveWarning = true;
				$warningIdentifier[] = "<li>You have some \"framework handles\" which are the same between different frameworks.</li>";
				ob_start();
				print "<ul>";
				$prevHandle = "";
				while($uniqueArray = $xoopsDB->fetchArray($uniqueRes)) {
					if($uniqueArray['fe_handle'] != $prevHandle) {
						if($prevHandle != "") {
							// need to finish previous set and print out what's missing
							print "</li>";
						}
						print "<li>Framework handle: <b>".$uniqueArray['fe_handle']."</b> is used in more than one place:<br>";
					}
					$prevHandle = $uniqueArray['fe_handle'];
					print "&nbsp;&nbsp;&nbsp;&nbsp;In framework ".$uniqueArray['fe_frame_id'].", it is used for element ".$uniqueArray['ele_id']." (".$uniqueArray['ele_caption'].")<br>";
					if($uniqueArray['fe_handle'] != $uniqueArray['ele_handle']) {
						print "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;For element ".$uniqueArray['ele_id'].", use the element's data handle instead: <b>".$uniqueArray['ele_handle']."</b><br>";
					}
				}
				// dump the last stuff we had found in the loop
				print "</li>";
				print "</ul>";
				$warningContents[] = ob_get_clean();
			}
			
			// need to disambiguate framework handles and elements' data handles.
			// no framework handle can be identical to the text of any data handle, unless they refer to the same element
			// So look up all the elements that have a data handle that matches a framework handle, which is not referring to the same element
			
			$handleSQL = "SELECT elements.ele_id, elements.ele_caption, elements.ele_handle, handles.fe_frame_id, handles.fe_handle, handles.fe_element_id, e2.ele_caption as handlecap, e2.ele_handle as newhandle FROM ".$xoopsDB->prefix("formulize")." AS elements, ".$xoopsDB->prefix("formulize_framework_elements")." AS handles, ".$xoopsDB->prefix("formulize")." AS e2 WHERE elements.ele_handle = handles.fe_handle AND handles.fe_element_id != elements.ele_id AND handles.fe_element_id = e2.ele_id ORDER BY elements.id_form, elements.ele_order";
			$handleRes = $xoopsDB->query($handleSQL);
			if($xoopsDB->getRowsNum($handleRes) > 0) {
				$haveWarning = true;
				$warningIdentifier[] = "<li>You have some \"data handles\" which are identical to the \"framework handles\" of other elements.</li>";
				ob_start();
				print "<ul>";
				while($handleArray = $xoopsDB->fetchArray($handleRes)) {
					print "<li>".$handleArray['handlecap']." (element ".$handleArray['fe_element_id'].") &mdash framework handle: <b>".$handleArray['fe_handle']."</b> in framework ".$handleArray['fe_frame_id']."<br>";
					print "&nbsp;&nbsp;&nbsp;&nbsp;Use the element's data handle instead: <b>".$handleArray['newhandle']."</b></li>";
				}
				print "</ul>";
				$warningContents[] = ob_get_clean();
			}
		}
		
		if($haveWarning) {
			print "<hr><p>MAJOR WARNING:</p>";
			print "<ol>";
			print implode("\n", $warningIdentifier);
			print "</ol>";
			print "<p>Framework handles are deprecated in Formulize 4, and having framework handles that are not entirely unique can now lead to serious errors in some situations.  However, we cannot automatically fix this situation for you, because you may have used the framework handles in programming code in your website.  If we try an automatic fix, we could break some other parts of your website.</p><p>Recommended actions:</p><p>1. Note down the following framework handles for the following elements (copy this information to a file, print this page, etc):<br>";
			print implode("\n", $warningContents);
			print "</p><p>2. Determine if you have any saved views based on a framework, that include the elements (columns) mentioned above.</p>";
			print "<p>3. For any elements mentioned above that are included in framework-based saved views, change their framework handles as suggested above.</p>";
			print "<p>4. Open up the affected saved views, and re-add the changed elements (columns) to them.  Search terms on changed columns will need to be respecified too, as well as sorting options.  Then re-save the view.  <b>Your users will need to do this too, if they have personal saved views which you cannot access.</b></p>";
			print "<p>5. Determine where you may be using the <b>framework handles</b> mentioned above in any programming code in your website.</p>";
			print "<p>6. For any elements where you are using the framework handles in programming code, change those framework handles as suggested above. You will need to make these changes in the framework configuration settings, as well as in the actual programming code where you are referring to the handle.</p>";
			print "<p> --- </p>";
			print "<p><b>You probably don't need to make all of the changes suggested above!</b>  You only need to make changes in places where there are saved views and/or programming code that uses the elements mentioned above.  For some websites, that will mean you don't have to make any changes (ie: if there are no saved views based on a framework, and you have no programming code referring to frameworks).</p>";
			print "<p> --- </p>";
			print "<p><b>You can re-run this patch after making changes.  If you do not get this warning, then your site should be OK.</b></p>";
			print "<p>If you have any questions about this upgrade issue, please contact <a href=mailto:formulize@freeformsolutions.ca>Freeform Solutions</a> for assistance.</p>";
		}

		print "DB updates completed.  result: OK";
	}
}


// THE 4.0 SERIES WILL NEED A SEPARATE PATCH ROUTINE.  THERE IS TOO MUCH BAGGAGE IN HERE TO KEEP CARRYING IT AROUND.  ESPECIALLY THE DATATYPE CONVERSION STUFF.
function patch31() {

  global $xoopsDB;
  // check if the new table structure is in place, and don't run this patch if so!
  $patchCheckSql = "SHOW TABLES";
	$resultPatchCheck = $xoopsDB->queryF($patchCheckSql);
  $entryOwnerGroupFound = false;
	while($table = $xoopsDB->fetchRow($resultPatchCheck)) {
    if($table[0] == $xoopsDB->prefix("formulize_entry_owner_groups")) {
      $entryOwnerGroupFound = true;
    } 
  }

	if(!isset($_POST['patch31'])) {
		print "<form action=\"formindex.php?op=patch31\" method=post>";
		print "<h1>Warning: this patch makes several changes to the database.  Backup your database prior to applying this patch!</h1>";
		print "<p>This patch may take a few minutes to apply.  Your page may take that long to reload, please be patient.</p>";
		print "<input type = submit name=patch31 value=\"Apply Database Patch for Formulize 3.1\">";
		print "</form>";
	} else {
		
    // if entryownergroupfound, then only do the 3.1 upgrade, otherwise, do the entire upgrade
    if($entryOwnerGroupFound) {
      if($derivedResult = formulize_createDerivedValueFieldsInDB()) {
        print "Created derived value fields in database.  result: OK<br>\n";
        $sql = array();
        $sql['ves_to_varchar'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_screen_listofentries") . " CHANGE `viewentryscreen` `viewentryscreen` varchar(10) NOT NULL DEFAULT ''";
        $sql['handlelength'] = "ALTER TABLE " . $xoopsDB->prefix("formulize") . " CHANGE `ele_handle` `ele_handle` varchar(255) NOT NULL default ''";
        /*$sql['copyTableForClean'] = "CREATE TABLE temp_entry_owner_groups like ".$xoopsDB->prefix("formulize_entry_owner_groups");
        $sql['lockForClean'] = "LOCK TABLES ".$xoopsDB->prefix("formulize_entry_owner_groups") . " WRITE, temp_entry_owner_groups WRITE";
        $sql['copyDataForClean'] = "INSERT INTO temp_entry_owner_groups SELECT * FROM ".$xoopsDB->prefix("formulize_entry_owner_groups");
        $sql['cleanEntryOwnerGroups'] = "DELETE FROM ".$xoopsDB->prefix("formulize_entry_owner_groups") . " WHERE owner_id NOT IN (SELECT MIN(owner_id) FROM temp_entry_owner_groups GROUP BY fid, entry_id, groupid)";
        $sql['dropTempTable'] = "DROP TABLE temp_entry_owner_groups";
        $sql['unlockForClean'] = "UNLOCK TABLES";*/ // this cleaning routine may be too intensive to unleash on unsuspecting servers
        foreach($sql as $key=>$thissql) {
          if(!$result = $xoopsDB->query($thissql)) {
          	if($key === "ves_to_carchar") {
          		print "viewentryscreen param already converted to varchar.  result: OK<br>";
            } elseif($key === "handlelength") {
              print "Length of element handles already extended.  result OK<br>";
            } elseif($key === "cleanEntryOwnerGroups" OR $key === "lockForClean" OR $key === "copyTableForClean" OR $key === "copyDataForClean" OR $key === "dropTempTable" OR $key === "unlockForClean") {
              print "Warning: could not delete duplicate entries from the entry_owner_groups table.  Please contact <a href=\"mailto:formulize@freeformsolutions.ca\">Freeform Solutions</a> for assistance.<br>\n";
            } else {
							exit("Error patching DB for Formulize 3.1. SQL dump:<br>" . $thissql . "<br>".mysql_error()."<br>Please contact <a href=mailto:formulize@freeformsolutions.ca>Freeform Solutions</a> for assistance.");
						}
          }
        }
				// need to handle datetype conversions for date fields, and making all fields accept NULL
				// 1. get a list of all forms
				// 2. loop through each element, getting it's metadata and datatable field data
				// 3. switch each one to accept NULL and default to NULL
				// 4. if it's a date field, switch its type to date
				// 5. update 0000-00-00 to NULL for dates
				$form_handler = xoops_getmodulehandler('forms', 'formulize');
				$allFids = $form_handler->getAllForms(true); // true means get all elements, even ones that are not displayed to any groups
				foreach($allFids as $thisFid) {
					if($thisFid->getVar('tableform') != "") { continue; } // don't do table forms obviously!
					$dataTableInfoSQL = "SHOW COLUMNS FROM ".$xoopsDB->prefix("formulize_".$thisFid->getVar('id_form'));
					if($dataTableInfoResult = $xoopsDB->query($dataTableInfoSQL)) {
						$foundDateFields = array();
						$thisFidElementHandles = $thisFid->getVar('elementHandles');
						$thisFidElementTypes = $thisFid->getVar('elementTypes');
						$alterTableSQL = "ALTER TABLE ".$xoopsDB->prefix("formulize_".$thisFid->getVar('id_form'));
						$start = true;
						while($thisFidDataTableInfo = $xoopsDB->fetchArray($dataTableInfoResult)) {
							if($thisFidDataTableInfo['Field'] == "entry_id" OR $thisFidDataTableInfo['Field'] == "creation_datetime" OR $thisFidDataTableInfo['Field'] == "mod_datetime" OR $thisFidDataTableInfo['Field'] == "creaiton_uid" OR $thisFidDataTableInfo['Field'] == "mod_uid" OR $thisFidDataTableInfo['Type'] != "text") {
								continue; // skip the metadata fields of course, and anything that has been manually changed from 'text'
							}
							if($thisFidElementTypes[array_search($thisFidDataTableInfo['Field'],$thisFidElementHandles)] == "date") { // if it's a date...
								$foundDateFields[] = $thisFidDataTableInfo['Field'];
								$alterTableSQL .= !$start ? "," : ""; // add comma if we're on a subsequent run through
								$alterTableSQL .= " CHANGE `".$thisFidDataTableInfo['Field']."` `".$thisFidDataTableInfo['Field']."` date NULL default NULL";
								$start = false;
							} elseif(strtoupper($thisFidDataTableInfo['Null']) != "YES") {
								$alterTableSQL .= !$start ? "," : ""; // add comma if we're on a subsequent run through
								$alterTableSQL .= " CHANGE `".$thisFidDataTableInfo['Field']."` `".$thisFidDataTableInfo['Field']."` text NULL default NULL";
								$start = false;
							}
						}
						if($start) {
							$alterTableSQL = ""; // blank it, so we don't actually do a query
						}
						if($alterTableSQL) {
							if(!$alterTableResult = $xoopsDB->query($alterTableSQL)) {
								exit("Error patching DB for Formulize 3.1. SQL dump:<br>" . $dataTableInfoSQL . "<br>".mysql_error()."<br>Please contact <a href=mailto:formulize@freeformsolutions.ca>Freeform Solutions</a> for assistance.");
							}
						}
						foreach($foundDateFields as $thisDateField) {
							$dateCorrectionSQL = "UPDATE ".$xoopsDB->prefix("formulize_".$thisFid->getVar('id_form'))." SET `".$thisDateField."` = NULL WHERE `".$thisDateField."` = '0000-00-00'";
							if(!$dateCorrectionResult = $xoopsDB->query($dateCorrectionSQL)) {
								exit("Error patching DB for Formulize 3.1. SQL dump:<br>" . $dataTableInfoSQL . "<br>".mysql_error()."<br>Please contact <a href=mailto:formulize@freeformsolutions.ca>Freeform Solutions</a> for assistance.");
							}
						}
					} else {
						exit("Error patching DB for Formulize 3.1. SQL dump:<br>" . $dataTableInfoSQL . "<br>".mysql_error()."<br>Please contact <a href=mailto:formulize@freeformsolutions.ca>Freeform Solutions</a> for assistance.");
					}
				}
				
				print "<br><br><b>NOTE:</b> Although the 3.x data structure is highly optimized compared to previous versions of Formulize, there are some situations which we cannot account for automatically in the upgrade process:  if you have elements in a form and users only enter numerical data there, you should edit those elements now and give them a truly numeric data type (use the new option on the element editing page to do this).  In Formulize 3 and higher, elements that have only numbers, but which are not stored as numbers in the database, will not sort properly and some calculations will not work correctly on them either.  Unfortunately, we cannot reliably determine which numeric data type should be used for all elements, therefore you will need to make this adjustment manually.  We apologize for any inconvenience.  Please contact <a href=\"mailto:formulize@freeformsolutions.ca\">Freeform Solutions</a> if you have any questions about this process.<br><br>\n";
				
        print "DB updates completed.  result: OK";
      } else {
        print "Unable to create derived value fields in database.  result: failed.  contact <a href=\"mailto:formulize@freeformsolutions.ca\">Freeform Solutions</a> for assistance.<br>\n";
      }
      return;
    }
		// put logic here

		// check to see if form table exists
		// need to put in check to make sure we're not finding the valid 'form' table from the Formulaire module
		$checkFormulaire = "SELECT * FROM " . $xoopsDB->prefix("modules") . " WHERE dirname='formulaire'";
		$cfresult = $xoopsDB->query($checkFormulaire);
		$sql = "SELECT * FROM " . $xoopsDB->prefix("form") . " LIMIT 0,1";
		$result = $xoopsDB->query($sql);
		if($xoopsDB->getRowsNum($result) AND $xoopsDB->getRowsNum($cfresult) == 0) {
                        
			// check to see if ele_forcehidden is in the table or not
			$sql = "SELECT * FROM " . $xoopsDB->prefix("form") . " LIMIT 0,1";
			$result = $xoopsDB->query($sql);
			if(!$result) {
				exit("Error patching DB for Formulize 3.1. SQL dump:<br>" . $sql . "<br>".mysql_error()."<br>Please contact <a href=mailto:formulize@freeformsolutions.ca>Freeform Solutions</a> for assistance.");
			}
			$array = $xoopsDB->fetchArray($result);
			unset($result);
			if(!isset($array['ele_forcehidden'])) {
				$sql = "ALTER TABLE " . $xoopsDB->prefix("form") . " ADD `ele_forcehidden` tinyint(1) NOT NULL default '0'";
				if(!$result = $xoopsDB->query($sql)) {
					exit("Error patching DB for Formulize 3.1. SQL dump:<br>" . $sql . "<br>".mysql_error()."<br>Please contact <a href=mailto:formulize@freeformsolutions.ca>Freeform Solutions</a> for assistance.");
				}
			} 
			unset($sql);

			$sql[] = "ALTER TABLE " . $xoopsDB->prefix("form") . " RENAME " . $xoopsDB->prefix("formulize");
			$sql[] = "ALTER TABLE " . $xoopsDB->prefix("form_id") . " RENAME " . $xoopsDB->prefix("formulize_id");
			$sql[] = "ALTER TABLE " . $xoopsDB->prefix("form_menu") . " RENAME " . $xoopsDB->prefix("formulize_menu");
			$sql[] = "ALTER TABLE " . $xoopsDB->prefix("form_form") . " RENAME " . $xoopsDB->prefix("formulize_form");
			$sql[] = "ALTER TABLE " . $xoopsDB->prefix("form_reports") . " RENAME " . $xoopsDB->prefix("formulize_reports");
			$sql[] = "ALTER TABLE " . $xoopsDB->prefix("formulize") . " CHANGE `ele_display` `ele_display` varchar(255) NOT NULL default '1'";
			$sql[] = "ALTER TABLE " . $xoopsDB->prefix("formulize_menu") . " CHANGE `itemname` `itemname` VARCHAR( 255 ) NOT NULL ";
			$sql[] = "DROP TABLE " . $xoopsDB->prefix("form_max_entries");
			$sql[] = "DROP TABLE " . $xoopsDB->prefix("form_chains");
			$sql[] = "DROP TABLE " . $xoopsDB->prefix("form_chains_entries");
			foreach($sql as $thissql) {
				if(!$result = $xoopsDB->query($thissql)) {
					exit("Error patching DB for Formulize 3.1. SQL dump:<br>" . $thissql . "<br>".mysql_error()."<br>Please contact <a href=mailto:formulize@freeformsolutions.ca>Freeform Solutions</a> for assistance.");
				}
			}

		} // end of if there's still a form table...

		unset($sql);


		$testsql = "SHOW TABLES";
		$resultst = $xoopsDB->query($testsql);
		while($table = $xoopsDB->fetchRow($resultst)) {
			$existingTables[] = $table[0];
		}
                $need22DataChecks = false;
		if(!in_array($xoopsDB->prefix("formulize_other"), $existingTables)) {
                        $need22DataChecks = true; // assume that if the formulize_other table is not present, then we have not patched up to 2.2 level yet
			$sql[] = "CREATE TABLE " . $xoopsDB->prefix("formulize_other") . " (
  other_id smallint(5) NOT NULL auto_increment,
  id_req smallint(5),
  ele_id int(5),
  other_text varchar(255) default NULL,
  PRIMARY KEY (`other_id`),
  INDEX i_ele_id (ele_id),
  INDEX i_id_req (id_req)
) TYPE=MyISAM;";
		}

		if(!in_array($xoopsDB->prefix("formulize_notification_conditions"), $existingTables)) {
			$sql[] = "CREATE TABLE " . $xoopsDB->prefix("formulize_notification_conditions") . " (
  not_cons_id smallint(5) NOT NULL auto_increment,
  not_cons_fid smallint(5) NOT NULL default 0,
  not_cons_event varchar(25) default '',
  not_cons_uid mediumint(8) NOT NULL default 0,
  not_cons_curuser tinyint(1),
  not_cons_groupid smallint(5) NOT NULL default 0,
  not_cons_con text NOT NULL,
  not_cons_template varchar(255) default '',
  not_cons_subject varchar(255) default '',
  PRIMARY KEY (`not_cons_id`),
  INDEX i_not_cons_fid (not_cons_fid),
  INDEX i_not_cons_uid (not_cons_uid),
  INDEX i_not_cons_groupid (not_cons_groupid),
  INDEX i_not_cons_fidevent (not_cons_fid, not_cons_event(1))
) TYPE=MyISAM;";
		}

		if(!in_array($xoopsDB->prefix("formulize_valid_imports"), $existingTables)) {
			$sql[] = "CREATE TABLE " . $xoopsDB->prefix("formulize_valid_imports") . " (
  import_id smallint(5) NOT NULL auto_increment,
  file varchar(255) NOT NULL default '',
  id_reqs text NOT NULL,
  PRIMARY KEY (`import_id`)
) TYPE=MyISAM;";
		}
                
                if(!in_array($xoopsDB->prefix("formulize_screen_listofentries"), $existingTables)) {
                        $sql[] = "CREATE TABLE " . $xoopsDB->prefix("formulize_screen_listofentries") . " (
  listofentriesid int(11) NOT NULL auto_increment,
  sid int(11) NOT NULL default 0,
  useworkingmsg tinyint(1) NOT NULL,
  repeatheaders tinyint(1) NOT NULL,
  useaddupdate varchar(255) NOT NULL default '',
  useaddmultiple varchar(255) NOT NULL default '',
  useaddproxy varchar(255) NOT NULL default '',
  usecurrentviewlist varchar(255) NOT NULL default '',
  limitviews text NOT NULL, 
  defaultview varchar(20) NOT NULL default '',
  usechangecols varchar(255) NOT NULL default '',
  usecalcs varchar(255) NOT NULL default '',
  useadvcalcs varchar(255) NOT NULL default '',
  useadvsearch varchar(255) NOT NULL default '',
  useexport varchar(255) NOT NULL default '',
  useexportcalcs varchar(255) NOT NULL default '',
  useimport varchar(255) NOT NULL default '',
  useclone varchar(255) NOT NULL default '',
  usedelete varchar(255) NOT NULL default '',
  useselectall varchar(255) NOT NULL default '',
  useclearall varchar(255) NOT NULL default '',
  usenotifications varchar(255) NOT NULL default '',
  usereset varchar(255) NOT NULL default '',
  usesave varchar(255) NOT NULL default '',
  usedeleteview varchar(255) NOT NULL default '',
  useheadings tinyint(1) NOT NULL,
  usesearch tinyint(1) NOT NULL, 
  usecheckboxes tinyint(1) NOT NULL, 
  useviewentrylinks tinyint(1) NOT NULL,
  usescrollbox tinyint(1) NOT NULL,
  usesearchcalcmsgs tinyint(1) NOT NULL,
  hiddencolumns text NOT NULL,
  decolumns text NOT NULL,
  desavetext varchar(255) NOT NULL default '',
  columnwidth int(1) NOT NULL,
  textwidth int(1) NOT NULL,
  customactions text NOT NULL, 
  toptemplate text NOT NULL,
  listtemplate text NOT NULL,
  bottomtemplate text NOT NULL,
  PRIMARY KEY (`listofentriesid`),
  INDEX i_sid (`sid`)
) TYPE=MyISAM;";
                }
                
                if(!in_array($xoopsDB->prefix("formulize_screen_multipage"), $existingTables)) {
                        $sql[] = "CREATE TABLE " . $xoopsDB->prefix("formulize_screen_multipage") . " (
  multipageid int(11) NOT NULL auto_increment,
  sid int(11) NOT NULL default 0,
  introtext text NOT NULL,
  thankstext text NOT NULL,
  donedest varchar(255) NOT NULL default '',
  buttontext varchar(255) NOT NULL default '',
  pages text NOT NULL,
  pagetitles text NOT NULL,
  conditions text NOT NULL,
  printall tinyint(1) NOT NULL,
  PRIMARY KEY (`multipageid`),
  INDEX i_sid (`sid`)
) TYPE=MyISAM;";
                }
                
                if(!in_array($xoopsDB->prefix("formulize_screen"), $existingTables)) {
                        $sql[] = "CREATE TABLE " . $xoopsDB->prefix("formulize_screen") . " (
  sid int(11) NOT NULL auto_increment,
  title varchar(255) NOT NULL default '',
  fid int(11) NOT NULL default 0,
  frid int(11) NOT NULL default 0,
  type varchar(100) NOT NULL default '',
  PRIMARY KEY  (`sid`)
) TYPE=MyISAM;";
                }

if(!in_array($xoopsDB->prefix("formulize_entry_owner_groups"), $existingTables)) {
	$sql[] = "CREATE TABLE ".$xoopsDB->prefix("formulize_entry_owner_groups")." (
  owner_id int(5) unsigned NOT NULL auto_increment,
  fid int(5) NOT NULL default '0',
  entry_id int(7) NOT NULL default '0',
  groupid int(5) NOT NULL default '0',
  PRIMARY KEY (`owner_id`),
  INDEX i_fid (fid),
  INDEX i_entry_id (entry_id),
  INDEX i_groupid (groupid)
) TYPE=MyISAM;";
}

		// check about altered fields
		$testsql = "SELECT * FROM " .  $xoopsDB->prefix("formulize") . " LIMIT 0,1";
		$result1 = $xoopsDB->query($testsql);
                if($xoopsDB->getRowsNum($result1) == 0) {
                        exit("Error patching DB for Formulize 3.1.<br>No forms exist in the database.<br>".mysql_error()."<br>Please contact <a href=mailto:formulize@freeformsolutions.ca>Freeform Solutions</a> for assistance.");
                }
		$array1 = $xoopsDB->fetchArray($result1); // for 2.1 we were checking explicitly whether we needed to add these fields.  But for 2.2 we just ran the SQL and caught the error appropriately in the condition below (ie: looked for failure for 'commonvalue' and ignored it) -- although ele_disabled was added this way...clearly we're not consistent about the patch approach!
		
		if(!array_key_exists('ele_desc',$array1)) {
			$sql[] = "ALTER TABLE " . $xoopsDB->prefix("formulize") . " ADD `ele_desc` text NULL";
		}
		if(!array_key_exists('ele_delim',$array1)) {
			$sql[] = "ALTER TABLE " . $xoopsDB->prefix("formulize") . " ADD `ele_delim` varchar(255) NOT NULL default ''";
		}
		if(!array_key_exists('ele_colhead',$array1)) {
			$sql[] = "ALTER TABLE " . $xoopsDB->prefix("formulize") . " ADD `ele_colhead` varchar(255) NULL default ''";
		}
		if(!array_key_exists('ele_private',$array1)) {
			$sql[] = "ALTER TABLE " . $xoopsDB->prefix("formulize") . " ADD `ele_private` tinyint(1) NOT NULL default '0'";
		}
                if(!array_key_exists('ele_disabled',$array1)) {
			$sql[] = "ALTER TABLE " . $xoopsDB->prefix("formulize") . " ADD `ele_disabled` varchar(255) NOT NULL default '0'";
		}
		if(!array_key_exists('ele_uitext',$array1)) {
			$sql[] = "ALTER TABLE " . $xoopsDB->prefix("formulize") . " ADD `ele_uitext` text NOT NULL";
		}
    
                // these commands can be run more than once, so no need to check them
                $sql['entriesperpage'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_screen_listofentries") . " ADD `entriesperpage` int(1) NOT NULL"; // part of 2.3, but dev sites will not have it, so they must be patched up to include this
                $sql['hiddencolumns'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_screen_listofentries") . " ADD `hiddencolumns` text NOT NULL"; // part of 2.3, but dev sites will not have it, so they must be patched up to include this
								$sql['tableforms'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_id") . " ADD `tableform` varchar(255) default NULL"; // part of 2.3, but dev sites will not have it, so they must be patched up to include this
		$sql['commonvalue'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_framework_links") . " ADD `fl_common_value` tinyint(1) NOT NULL default '0'";
		$sql['dropindex'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_form") . " DROP INDEX `ele_id`";
		$sql['deleteyyyy'] = "DELETE FROM " . $xoopsDB->prefix("formulize_form") . " WHERE ele_value =\"YYYY-mm-dd\" AND ele_type=\"date\"";
                // change alterations not checked for success below, since they can be repeated
		$sql['headerlist'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_id") .  " CHANGE `headerlist` `headerlist` text default NULL";
		$sql['grouplist'] = "ALTER TABLE " . $xoopsDB->prefix("group_lists") .  " CHANGE `gl_groups` `gl_groups` text NOT NULL";
                $sql['importidreqs'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_valid_imports") . " CHANGE `id_reqs` `id_reqs` text NOT NULL";
                $sql['sv_asearch'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_saved_views") . " CHANGE `sv_asearch` `sv_asearch` text default NULL";
                $sql['sv_oldcols'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_saved_views") . " CHANGE `sv_oldcols` `sv_oldcols` text default NULL";
                $sql['sv_currentview'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_saved_views") . " CHANGE `sv_currentview` `sv_currentview` text default NULL";
                $sql['sv_calc_cols'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_saved_views") . " CHANGE `sv_calc_cols` `sv_calc_cols` text default NULL";
                $sql['sv_calc_calcs'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_saved_views") . " CHANGE `sv_calc_calcs` `sv_calc_calcs` text default NULL";
                $sql['sv_calc_blanks'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_saved_views") . " CHANGE `sv_calc_blanks` `sv_calc_blanks` text default NULL";
                $sql['sv_calc_grouping'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_saved_views") . " CHANGE `sv_calc_grouping` `sv_calc_grouping` text default NULL";
                $sql['sv_quicksearches'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_saved_views") . " CHANGE `sv_quicksearches` `sv_quicksearches` text default NULL";
								$sql['fixlsbapos'] = "UPDATE " . $xoopsDB->prefix("formulize_form") . " SET `ele_value` = REPLACE(`ele_value`, '&#039;', '\'') WHERE `ele_type` = 'select' AND `ele_value` LIKE '%#*=:*%'"; // during the 2.2 patch, some apostrophes in the ele_value field would have been converted to html chars incorrectly
		$sql['sv_pubgroups'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_saved_views") . " CHANGE `sv_pubgroups` `sv_pubgroups` text default NULL";
                $sql['id_req_int'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_form") . " CHANGE `id_req` `id_req` int(7)";
								$sql['import_fid'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_valid_imports") . " ADD `fid` int(5)";
                $sql['useToken'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_screen") . " ADD `useToken` tinyint(1) NOT NULL";
                $sql['notCreator'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_notification_conditions") . " ADD `not_cons_creator` tinyint(1)";
                $sql['notElementUids'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_notification_conditions") . " ADD `not_cons_elementuids` smallint(5) NOT NULL default 0";
                $sql['notLinkCreator'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_notification_conditions") . " ADD `not_cons_linkcreator` smallint(5) NOT NULL default 0";
                $sql['printAll'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_screen_multipage") . " ADD `printall` TINYINT( 1 ) NOT NULL";
                $sql['pageTitles'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_screen_multipage") . " ADD `pagetitles` TEXT NOT NULL AFTER `pages`";
								$sql['paraentryform'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_screen_multipage") . " ADD `paraentryform` int(11) NOT NULL default 0";
								$sql['paraentryrel'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_screen_multipage") . " ADD `paraentryrelationship` tinyint(1) NOT NULL default 0";
                $sql['ele_handle'] = "ALTER TABLE " . $xoopsDB->prefix("formulize") . " ADD `ele_handle` varchar(255) NOT NULL default ''";
                $sql['viewentryscreen'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_screen_listofentries") . " ADD `viewentryscreen` varchar(10) NOT NULL DEFAULT ''"; 
                $sql['otherint1'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_other") . " CHANGE `other_id` `other_id` INT(5) NOT NULL AUTO_INCREMENT";
                $sql['otherint2'] = "ALTER TABLE " . $xoopsDB->prefix("formulize_other") . " CHANGE `id_req` `id_req` INT(5)";
                $sql['ele_caption_text'] = "ALTER TABLE " . $xoopsDB->prefix("formulize") . " CHANGE `ele_caption` `ele_caption` text NOT NULL default ''";
								
		foreach($sql as $key=>$thissql) {
			if(!$result = $xoopsDB->query($thissql)) {
				if($key === "dropindex") {
					print "Ele_id Index already dropped.  result: OK<br>";
				} elseif($key === "tableforms") {
					print "Tableform option already present.  result: OK<br>";
				} elseif($key === "deleteyyyy") {
					print "No redundant date values found.  result: OK<br>";
				} elseif($key === "commonvalue") {
					print "Common framework value already added.  result: OK<br>";
        } elseif($key === "entriesperpage") {
          print "Entries per page option already added.  result: OK<br>";
        } elseif($key === "hiddencolumns") {
          print "Hidden columns option already added.  result: OK<br>";
        } elseif($key === "useToken") {
          print "Security token awareness already added to screens.  result: OK<br>";
        } elseif($key === "notCreator" OR $key === "notElementUids" OR $key === "notLinkCreator" ) {
          print "Additional notification options already added.  result: OK<br>";
        } elseif($key === "printAll") {
          print "Multipage form \"print all\" option already added.  result: OK<br>";
        } elseif($key === "pageTitles") {
          print "Multipage form \"page titles\" options already added.  result: OK<br>";
				} elseif($key === "paraentryform") {
          print "Multipage form \"parallel entry form\" option already added.  result: OK<br>";
				} elseif($key === "paraentryrel") {
          print "Multipage form \"parallel entry form relationship\" option already added.  result: OK<br>";
				} elseif($key ==="onetoone_main") {
					print "Onetoone \"main_fid\" field already added.  result: OK<br>";
				} elseif($key === "onetoone_link") {
					print "Onetoone \"link_fid\" field already added.  result: OK<br>";
				} elseif($key === "import_fid") {
					print "Form id field already added to the import table.  result: OK<br>";
				} elseif($key === "ele_handle") {
					// assume it has already been added
					if($secondaryResult = $xoopsDB->query("ALTER TABLE " . $xoopsDB->prefix("formulize") . " CHANGE `ele_handle` `ele_handle` varchar(255) NOT NULL default ''")) {
						print "Element handle field already added.  result: OK<br>";	
					} else {
						exit("Error patching DB for Formulize 3.1. SQL dump:<br>ALTER TABLE " . $xoopsDB->prefix("formulize") . " CHANGE `ele_handle` `ele_handle` varchar(255) NOT NULL default ''<br>".mysql_error()."<br>Please contact <a href=mailto:formulize@freeformsolutions.ca>Freeform Solutions</a> for assistance.");
					}
				} elseif($key === "viewentryscreen") {
					// assume it has already been added
					if($secondaryResult = $xoopsDB->query("ALTER TABLE " . $xoopsDB->prefix("formulize_screen_listofentries") . " CHANGE `viewentryscreen` `viewentryscreen` varchar(10) NOT NULL DEFAULT ''")) {
						print "viewentryscreen field already added.  result: OK<br>";	
					} else {
						exit("Error patching DB for Formulize 3.1. SQL dump:<br>ALTER TABLE " . $xoopsDB->prefix("formulize_screen_listofentries") . " CHANGE `viewentryscreen` `viewentryscreen` varchar(10) NOT NULL DEFAULT ''<br>".mysql_error()."<br>Please contact <a href=mailto:formulize@freeformsolutions.ca>Freeform Solutions</a> for assistance.");
					}
				}else {
					exit("Error patching DB for Formulize 3.1. SQL dump:<br>" . $thissql . "<br>".mysql_error()."<br>Please contact <a href=mailto:formulize@freeformsolutions.ca>Freeform Solutions</a> for assistance.");
				}
			} elseif($key === "ele_handle") { // we just added the ele_handle field
				
				// use element id number for the initial element handles
				$eh_eleid_sql = "UPDATE " . $xoopsDB->prefix("formulize") . " SET ele_handle = ele_id WHERE ele_handle = ''";
				if(!$eh_eleid_res = $xoopsDB->query($eh_eleid_sql)) {
					exit("Error patching DB for Formulize 3.1.  SQL dump:<br>" . $eh_check_sql . "<br>".mysql_error()."<br>Please contact <a href=mailto:formulize@freeformsolutions.ca>Freeform Solutions</a> for assistance.");
				}
				
			}
		}

                if($need22DataChecks) {
                        // lock formulize_form
                        $xoopsDB->query("LOCK TABLES " . $xoopsDB->prefix("formulize_form") . " WRITE, " . $xoopsDB->prefix("formulize_form") . " AS t1 READ, " . $xoopsDB->prefix("formulize_form") . " AS t2 READ");
        
                        // check for ambiguous id_reqs
                        print "Searching for ambiguous id_reqs.  Please be patient.  This may take a few minutes on a large database.<br>";
                        $findSql = "SELECT distinct(t1.id_req) FROM " . $xoopsDB->prefix("formulize_form") . " AS t1, " . $xoopsDB->prefix("formulize_form") . " AS t2 WHERE t1.uid != t2.uid AND t1.id_req = t2.id_req";
                        if(!$findRes = $xoopsDB->query($findSql)) { print "None found.<br>"; }
                        // loop through all ambiguous id_reqs and fix them
        
                        while($find = $xoopsDB->fetchArray($findRes)) {
                                print "Found ambiguous id_req: " . $find['id_req'] . "<br>";
                                include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";
                                $maxIdReq = getMaxIdReq();		
                                $uidSql = "SELECT distinct(uid) FROM " . $xoopsDB->prefix("formulize_form") . " WHERE id_req='" . $find['id_req'] . "'";
                                $uidRes = $xoopsDB->query($uidSql);
                                $start = 1;
                                while($uid = $xoopsDB->fetchArray($uidRes)) {
                                        // ignore the first one, since one of the entries can keep the current id_req
                                        if($start) {
                                                print "Uid " . $uid['uid'] . " unchanged<br>";
                                                $start = 0;
                                                continue;
                                        }
                                        $fixSql = "UPDATE " . $xoopsDB->prefix("formulize_form") . " SET id_req='$maxIdReq' WHERE id_req='" . $find['id_req'] . "' AND uid='" . $uid['uid'] . "'";
                                        if(!$fixRes = $xoopsDB->query($fixSql)) {
                                                exit("Error patching DB for Formulize 3.1. SQL dump:<br>" . $fixsql . "<br>".mysql_error()."<br>Please contact <a href=mailto:formulize@freeformsolutions.ca>Freeform Solutions</a> for assistance.");
                                        }
                                        print "Uid " . $uid['uid'] . " now using id_req $maxIdReq<br>";
                                        $maxIdReq++;
                                }
                        }
        
                        // repeat check, but base on id_form instead...
                        correctAmbiguousIdReqsBasedOnFormIds(true); // true causes the uidFocus flag to be set which causes a special error message to appear to the user when duplicates are found.  This message only matters during this particular ambiguous ID pass
                        print "Finished checking for ambiguous id_reqs.<br>";
        
                        // unlock tables
                        $xoopsDB->query("UNLOCK TABLES");
        
                        // check for old data
                        print "Checking for old data left over from deleted form elements.  This may take a few minutes on a large database<br>";
                        $formsSql = "SELECT ele_caption, id_form FROM " . $xoopsDB->prefix("formulize");
                        $formsRes = $xoopsDB->query($formsSql);
                        while($formArray = $xoopsDB->fetchArray($formsRes)) {
                                $newcap = str_replace("'", "`", $formArray['ele_caption']);
                                $newcap = str_replace("&quot;", "`", $newcap);
                                $newcap = str_replace("&#039;", "`", $newcap);
                                $formCaptions[$formArray['id_form']][$newcap] = 1;
                        }
                        $dataSql = "SELECT id_form, ele_caption FROM " . $xoopsDB->prefix("formulize_form");
                        $dataRes = $xoopsDB->query($dataSql);
                        while($dataArray = $xoopsDB->fetchArray($dataRes)) {
                                if(!isset($formCaptions[$dataArray['id_form']][$dataArray['ele_caption']])) {
                                        $deleteSql = "DELETE FROM " . $xoopsDB->prefix("formulize_form") . " WHERE id_form=".$dataArray['id_form']." AND ele_caption=\"".mysql_real_escape_string($dataArray['ele_caption'])."\"";
                                        if(!$result = $xoopsDB->query($deleteSql)) {
                                                exit("Error patching DB for Formulize 3.1. SQL dump:<br>" . $deletesql . "<br>".mysql_error()."<br>Please contact <a href=mailto:formulize@freeformsolutions.ca>Freeform Solutions</a> for assistance.");
                                        }
                                }
                        }
                        print "Finished checking for old data.  result: OK<br>";
                }

    // added Feb 3 2008 by jwe
    // check for duplicate id_reqs...this is based not based on the same criteria that is in the 22 data check above.
    // in this case we are simply looking for the same id_req being applied to different forms
    // check for ambiguous id_reqs
    // only necessary if 22 checks were not done, since 22 check now includes this function call too
    if(!$need22DataChecks) {
      $xoopsDB->query("LOCK TABLES " . $xoopsDB->prefix("formulize_form") . " WRITE, " . $xoopsDB->prefix("formulize_form") . " AS t1 READ, " . $xoopsDB->prefix("formulize_form") . " AS t2 READ");
      print "Searching for duplicate entry ids in use on two or more forms.  Please be patient.  This may take a few minutes on a large database.<br>";
      correctAmbiguousIdReqsBasedOnFormIds();
      print "Finished checking for duplicate entry ids.<br>";
      // unlock tables
      $xoopsDB->query("UNLOCK TABLES");  
    }
    

		print "DB updates completed.  result: OK";
        } 
}

// this function reads all the derived value fields in the elements list and makes sure there is a field in the data tables for each one
function formulize_createDerivedValueFieldsInDB() {
  
  // 1. gather a list of all the derived elements, including their form ids (so we can reference the tables), and handles (so we know what the field should be called)
  // 2. foreach table, get a list of columns, and if the field name is not represented, then create it
  global $xoopsDB;
  $form_handler =& xoops_getmodulehandler('forms', 'formulize');
  $element_handler =& xoops_getmodulehandler('elements', 'formulize');
  $sql = "SELECT id_form, ele_handle, ele_id FROM ".$xoopsDB->prefix("formulize")." WHERE ele_type = 'derived' ORDER BY id_form";
  if($result = $xoopsDB->query($sql)) {
    $fieldMap = array();
    while($array = $xoopsDB->fetchArray($result)) {
      $fieldMap[$array['id_form']][] = array(0=>$array['ele_handle'], 1=>$array['ele_id']);
    }
    foreach($fieldMap as $fid=>$fields) {
      $sql2 = "SHOW COLUMNS FROM ".$xoopsDB->prefix("formulize_".$fid);
      if($result2 = $xoopsDB->query($sql2)) {
        $existingFields = array();
        while($array2 = $xoopsDB->fetchArray($result2)) {
          $existingFields[] = $array2['Field']; // show columns returns Field Type Null Key Default Extra
        }
      } else {
        return false;
      }
      foreach($fields as $thisFieldInfo) {
        if(!in_array($thisFieldInfo[0], $existingFields)) { // 0 is the handle
          if(!$insertResult = $form_handler->insertElementField($element_handler->get($thisFieldInfo[1]))) { // 1 is the id
            exit("Error: could not add the derived value field, $thisFieldInfo[1], to the data table.");
          }
        }
      }
    }
    
    // now that we know we have fields for all the derived value elements, tell the user to populate them with the correct data
    print "Before you can do calculations or sort lists based on derived values, you will have to go to every page in each list of entries that has derived values.  Going to each page will cause the derived values to be cached in the database, so that calculations and sorts based on the derived values will work.<br>\n";
    
    return true;
  } else {
    return false;
  }
  
}

// this function handles the checking for ambiguous id_reqs based on the form id
function correctAmbiguousIdReqsBasedOnFormIds($uidFocus = false) {

  global $xoopsDB;
  $findSql = "SELECT distinct(t1.id_req) FROM " . $xoopsDB->prefix("formulize_form") . " AS t1, " . $xoopsDB->prefix("formulize_form") . " AS t2 WHERE t1.id_form != t2.id_form AND t1.id_req = t2.id_req";
  if(!$findRes = $xoopsDB->query($findSql)) { print "None found.<br>"; }
  // loop through all ambiguous id_reqs and fix them

  while($find = $xoopsDB->fetchArray($findRes)) {
          print "Found ambiguous id_req: " . $find['id_req'] . "<br>";
          include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";
          $maxIdReq = getMaxIdReq();		
          $fidSql = "SELECT distinct(id_form) FROM " . $xoopsDB->prefix("formulize_form") . " WHERE id_req='" . $find['id_req'] . "' ORDER BY id_form";
          $fidRes = $xoopsDB->query($fidSql);
          $start = 1;
          while($id_form = $xoopsDB->fetchArray($fidRes)) {
            // ignore the first one, since one of the entries can keep the current id_req
            if($start) {
              print "Form id " . $id_form['id_form'] . " unchanged<br>";
              $start = 0;
              continue;
            }
            $fixSql = "UPDATE " . $xoopsDB->prefix("formulize_form") . " SET id_req='$maxIdReq' WHERE id_req='" . $find['id_req'] . "' AND id_form='" . $id_form['id_form'] . "'";
            if(!$fixRes = $xoopsDB->query($fixSql)) {
              exit("Error patching DB for Formulize 3.1. SQL dump:<br>" . $fixsql . "<br>".mysql_error()."<br>Please contact <a href=mailto:formulize@freeformsolutions.ca>Freeform Solutions</a> for assistance.");
            }
            print "Form id " . $id_form['id_form'] . " now using id_req $maxIdReq<br>";
            if($uidFocus) { print "EITHER ENTRY " . $find['id_req'] . " OR ENTRY $maxIdReq HAS THE WRONG OWNER (uid).  THERE IS NO WAY FOR THE SYSTEM TO TELL WHICH IS INCORRECT. YOU SHOULD CHECK THE ENTRIES AND MODIFY THE UID COLUMN IN THE DATABASE FOR THE ONE ENTRY THAT IS INCORRECT.  THE PROPER SQL SHOULD BE LIKE THIS: \"UPDATE xoops_formulize_form SET uid=123 WHERE id_req=456\" WHERE 123 IS THE CORRECT UID AND 456 IS THE ENTRY THAT CURRENTLY HAS AN INCORRECT UID.  PLEASE CONTACT FREEFORM SOLUTIONS FOR ASSISTANCE IF YOU ARE AT ALL UNSURE ABOUT THIS PROCEDURE!<br>"; }
            $maxIdReq++;
          }
  }

}

// convert data to the new no slashes, HTML special chars format
// previously, Formulize erroneously added slashes to the data that was stored in the database, meaning that an apostrophe was stored as \'
// now, slashes are removed from values entered in a form if magic quotes is on, and then htmlspecialchars is run on the values
// this results in apostrophes, for instance, stored as &#039;
// the search logic in the extract.php file has been modified as well to run htmlspecialchars on search terms so that matches are found properly
function patch22convertdata() {

  global $xoopsDB;
  $patchCheckSql = "SHOW TABLES";
	$resultPatchCheck = $xoopsDB->queryF($patchCheckSql);
  $formulizeFormFound = false;
  $newStructureFound = false;
  $entryOwnerGroupFound = false;
	while($table = $xoopsDB->fetchRow($resultPatchCheck)) {
    $secondPart = substr($table[0], strlen($xoopsDB->prefix("formulize_")));
    if(is_numeric($secondPart) AND strstr($table[0], "formulize_")) {
      $newStructureFound = true;
    }
    if($table[0] == $xoopsDB->prefix("formulize_form")) {
      $formulizeFormFound = true;
    }
    if($table[0] == $xoopsDB->prefix("formulize_entry_owner_groups")) {
      $entryOwnerGroupFound = true;
    }
	}
  if(!$formulizeFormFound AND $entryOwnerGroupFound) {
          print "<h1>It appears you have not upgraded from a previous version of Formulize.  You do not need to apply this patch unless you are upgrading from a version prior to 2.2</h1>\n";
          print "<p>If you did upgrade from a previous version, please contact <a href=mailto:formulize@freeformsolutions.ca>Freeform Solutions</a> for assistance.</p>\n";
          return;
  }
  if($newStructureFound) {
      print "<h1>You cannot run this patch after upgrading to the 3.0 data structure.</h1>";
      print "<p>If you upgraded from Formulize 2.1 or earlier, and you did not run the \"patch22convertdata\" already, please contact <a href=mailto:formulize@freeformsolutions.ca>Freeform Solutions</a> for assistance.</p>";
      return;
  }

	

	if(!isset($_POST['patch22convertdata'])) {

		// detect name of Formulize table
		$sql = "SELECT * FROM " . $xoopsDB->prefix("formulize_form") . " LIMIT 0,1";
		if($res = $xoopsDB->query($sql)) {

			print "<form action=\"formindex.php?op=patch22convertdata\" method=post>";
			print "<h1>Warning: this patch changes the formatting of data in your database, primarily to address security issues in how data is being stored.  Backup your database prior to applying this patch!</h1>";
			print "<h1>DO NOT APPLY THIS PATCH TWICE.  If you apply this patch again after applying it once already, then some data in your database may be damaged.  So, please backup your database prior to applying this patch!  If there is an error when the patch runs, returning to a backup is the only way to ensure the integrity of your data.</h1>";
			print "<p>This patch may take a few minutes to apply.  Your page may take that long to reload, please be patient.</p>";
                        print "<p>If you applied this patch previously when upgrading to Formulize 2.2, DO NOT apply it again when upgrading to a higher version!</p>";
                        print "<p>If the first version of Formulize that you installed was 2.2 or higher, you DO NOT need to apply this patch!</p>";
			print "<input type = submit name=patch22convertdata value=\"Apply Data Conversion Patch for upgrading to Formulize 2.2 and higher\">";
			print "</form>";
		} else {
			print "<h1>You do not appear to have applied 'patch31'.</h1>\n";
			print "<p>You must apply patch31 before applying this patch.  <a href=\"" . XOOPS_URL . "/modules/formulize/admin/formindex.php?op=patch31\">Click here to run \"patch31\".</a></p>";
		}
	} else {
		
		print "Sanitizing form entries.  On a large database, this may take a long time.<br>";

		global $myts;
		if(!$myts) { $myts =& MyTextSanitizer::getInstance(); }

		$sansql = "SELECT ele_id, ele_value FROM " . $xoopsDB->prefix("formulize_form") . " WHERE ele_type != \"date\" AND  ele_type != \"yn\" AND ele_type != \"areamodif\"";
		if(!$sanres = $xoopsDB->query($sansql)) { exit("Error patching DB for Formulize 2.2. SQL dump:<br>" . $sansql . "<br>".mysql_error()."<br>Could not collect all data for sanitizing.  Please contact <a href=mailto:formulize@freeformsolutions.ca>Freeform Solutions</a> for assistance."); }
		while($sanArray = $xoopsDB->fetchArray($sanres)) {
			$origvalue = $sanArray['ele_value'];
			if(get_magic_quotes_gpc()) { $sanArray['ele_value'] = stripslashes($sanArray['ele_value']); }
			$newvalue = $myts->htmlSpecialChars($sanArray['ele_value']);
			if($newvalue != $origvalue) {
				$newsql = "UPDATE " . $xoopsDB->prefix("formulize_form") . " SET ele_value = \"" . mysql_real_escape_string($newvalue) . "\" WHERE ele_id = " . $sanArray['ele_id'];
				if(!$newres = $xoopsDB->query($newsql)) {
					exit("Error patching DB for Formulize 2.2. SQL dump:<br>" . $sansql . "<br>".mysql_error()."<br>Could not write data for sanitizing.  Please contact <a href=mailto:formulize@freeformsolutions.ca>Freeform Solutions</a> for assistance.");
				}
			}
		}

		print "Sanitizing form entries completed.  result: OK<br>";
	}
}

// this patch copies the formulize_form table to separate datatables based on each form in the system
// this should have been done three years ago!
function patch30DataStructure($auto = false) {
        
        global $xoopsDB;
        // check for new data structure and don't run this patch if it already has been!
        // check that patch30 has been run and don't run this patch unless it already has been!
        // check that formulize_form table exists, or else don't run the patch
        $patchCheckSql = "SHOW TABLES";
        $resultPatchCheck = $xoopsDB->queryF($patchCheckSql);
        $entryOwnerGroupFound = false;
        $formulizeFormFound = false;
        $newStructureFound = false;
        while($table = $xoopsDB->fetchRow($resultPatchCheck)) {
          $secondPart = substr($table[0], strlen($xoopsDB->prefix("formulize_")));
          if(is_numeric($secondPart) AND strstr($table[0], $xoopsDB->prefix("formulize_"))) { // there will be a part after "formulize_" that is numeric in the new data structure
            $newStructureFound = true;
          }
          if($table[0] == $xoopsDB->prefix("formulize_entry_owner_groups")) {
            $entryOwnerGroupFound = true;
          }
          if($table[0] == $xoopsDB->prefix("formulize_form")) {
            $formulizeFormFound = true;
          }
        }
        if(!$formulizeFormFound AND $entryOwnerGroupFound) {
          print "<h1>It appears you have not upgraded from a previous version of Formulize.  You do not need to apply this patch unless you are upgrading from a version prior to 3.0</h1>\n";
          print "<p>If you did upgrade from a previous version, please contact <a href=mailto:formulize@freeformsolutions.ca>Freeform Solutions</a> for assistance.</p>\n";
          return;
        }
        if(!$entryOwnerGroupFound) {
          print "<h1>You must run \"patch31\" before upgrading to the 3.0 data structure.</h1>\n";
          print "<p><a href=\"" . XOOPS_URL . "/modules/formulize/admin/formindex.php?op=patch31\">Click here to run \"patch31\".</a></p>\n";
          return;
        }
        if($newStructureFound) {
            print "<h1>You cannot run this patch after upgrading to the 3.0 data structure.</h1>";
            return;
        }

        $carryon = true;
        if(!$auto) { // put UI control in if not called from another function....not actually used; this patch must be invoked manually on its own.
                if(!isset($_POST['patch30datastructure'])) {
                        $carryon = false;
												print "<form action=\"formindex.php?op=patch30datastructure\" method=post>";
												print "<h1>Warning: this patch completely changes the structure of the formulize data in your database.  Backup your database prior to applying this patch!</h1>";
												print "<p>This patch may take a few minutes to apply.  Your page may take that long to reload, please be patient.</p>";
                        print "<p>You may need to increase the memory limit and/or max execution time in PHP, if you have a large database (100,000 records or more, depending on the size of your forms).</p>";
                        print "<p>If the first version of Formulize that you installed was 3.0 or higher, you DO NOT need to apply this patch!</p>";
												print "<input type = submit name=patch30datastructure value=\"Apply Data Structure Patch for upgrading to Formulize 3.0 and higher\">";
												print "</form>";
								} 
				}
        
        if($carryon) {
                        
                // 1. figure out all the forms in existence
                // 2. for each one, devise the field names in its table
                // 3. create its table
                // 4. import its data from formulize_form
                
                
                include_once XOOPS_ROOT_PATH . "/modules/formulize/class/forms.php";
                include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";
                $formHandler =& xoops_getmodulehandler('forms', 'formulize');
                $allFormObjects = $formHandler->getAllForms(true); // true flag causes all elements to be included in objects, not just elements that are being displayed, which are ignored in every other situation
                foreach($allFormObjects as $formObjectId=>$thisFormObject) {
												if($thisFormObject->getVar('tableform')) { continue; } // only process actual Formulize forms
                        if(!$tableCreationResult = $formHandler->createDataTable($thisFormObject)) {
                                exit("Error: could not make the necessary new datatable for form " . $thisFormObject->getVar('id_form') . ".<br>".mysql_error()."<br>Please report this error to <a href=\"mailto:formulize@freeformsolutions.ca\">Freeform Solutions</a>.");
                        }
                        
                        print "Created data table formulize_" . $thisFormObject->getVar('id_form') . ".  result: OK<br>\n";
                        
                        // map data in formulize_form into new table
                        // 1. get an index of the captions to element ids
                        // 2. get all the data organized by id_req
                        // 3. insert the data
                        
                        $captionPlusHandlesSQL = "SELECT ele_caption, ele_handle FROM " . $xoopsDB->prefix("formulize") . " WHERE id_form = " . $thisFormObject->getVar('id_form');
                        $captionPlusHandlesRes = $xoopsDB->query($captionPlusHandlesSQL);
                        $captionHandleIndex = array();
                        while($captionPlusHandlesArray = $xoopsDB->fetchArray($captionPlusHandlesRes)) {
                                $captionHandleIndex[str_replace("'", "`", $captionPlusHandlesArray['ele_caption'])] = $captionPlusHandlesArray['ele_handle'];
                        }
                                                
                        $dataSQL = "SELECT id_req, ele_caption, ele_value, ele_type FROM " .$xoopsDB->prefix("formulize_form") . " WHERE id_form = " . $thisFormObject->getVar('id_form') . " AND ele_type != \"areamodif\" AND ele_type != \"sep\" ORDER BY id_req"; // for some reason areamodif and sep are stored in some really old data
                        $dataRes = $xoopsDB->query($dataSQL);
                        $prevIdReq = "";
                        $insertSQL = "";
                        unset($foundCaptions);
                        $foundCaptions = array();
                        while($dataArray = $xoopsDB->fetchArray($dataRes)) {
                                if(!isset($captionHandleIndex[$dataArray['ele_caption']])) {
																	if($dataArray['ele_caption'] === '') {
																		print "Warning: you have data saved, with no caption specified, for entry number ". $dataArray['id_req'] . " (id_req) in form ".$thisFormObject->getVar('id_form'). ".  This data will be ignored.  Please contact <a href=\"mailto:formulize@freeformsolutions.ca\">Freeform Solutions</a> if you would like assistance cleaning this up.  This will NOT affect the upgrade to version 3.<br>";
																		continue;
																	} else {
																		print "Warning: the form ". $thisFormObject->getVar('id_form') . " does not have an element with the caption '". $dataArray['ele_caption'] . "', but you have saved data associated with that caption.  This data will be ignored.  Please contact <a href=\"mailto:formulize@freeformsolutions.ca\">Freeform Solutions</a> if you would like assistance cleaning this up.  This will NOT affect the upgrade to version 3.<br>";
                                    continue;
																	}
                                }
                                if($dataArray['id_req'] != $prevIdReq) { // we're on a new entry
                                        unset($foundCaptions);
                                        $foundCaptions = array(); // reset the list of captions we've found in this entry so far, since we're moving onto a different entry
                                        $prevIdReq = $dataArray['id_req'];
                                        // write whatever we just finished working on
                                        if($insertSQL) {
                                                if(!$insertRes = $xoopsDB->query($insertSQL)) {
                                                        exit("Error: could not write data to the new table structure with this SQL: $insertSQL.<br>".mysql_error()."<br>Please report this error to <a href=\"mailto:formulize@freeformsolutions.ca\">Freeform Solutions</a>.");
                                                }
                                                $insertSQL = "";
                                        }
                                        // build the SQL for inserting this entry
                                        $insertSQL = "INSERT INTO " . $xoopsDB->prefix("formulize_" . $thisFormObject->getVar('id_form')) . " SET entry_id = \"" . $dataArray['id_req'] . "\"";
                                        $metaData = getMetaData($dataArray['id_req'], "", "", true); // special last param necessary because we need to use the old meta process when doing this patch!
                                        $creation_uid = $metaData['created_by_uid'];
                                        $mod_uid = $metaData['last_update_by_uid'];
                                        $creation_datetime = $metaData['created'] == "???" ? "" : $metaData['created'];
                                        $mod_datetime = $metaData['last_update'];
                                        $insertSQL .= ", creation_datetime = \"$creation_datetime\", mod_datetime = \"$mod_datetime\", creation_uid = \"$creation_uid\", mod_uid = \"$mod_uid\"";
																				
																				// derive the owner groups and write them to the owner groups table
																				$ownerGroups = array();
																				if($creation_uid) {
																					$member_handler =& xoops_gethandler('member');
																					$creationUser = $member_handler->getUser($creation_uid);
																					if(is_object($creationUser)) {
																						$ownerGroups = $creationUser->getGroups();
																					} else {
																						$ownerGroups[] = XOOPS_GROUP_ANONYMOUS;
																					}
																				} else {
																					$ownerGroups[] = XOOPS_GROUP_ANONYMOUS;
																				}
																				foreach($ownerGroups as $thisGroup) {
																					$ownerInsertSQL = "INSERT INTO " . $xoopsDB->prefix("formulize_entry_owner_groups") . " (`fid`, `entry_id`, `groupid`) VALUES ('". intval($thisFormObject->getVar('id_form')) . "', '". intval($dataArray['id_req']) . "', '". intval($thisGroup) . "')";
																					if(!$ownerInsertRes = $xoopsDB->query($ownerInsertSQL)) {
																						print "Error: could not write owner information to new data structure, using this SQL:<br>$ownerInsertSQL<br>".mysql_error()."<br>Please report this error to <a href=\"mailto:formulize@freeformsolutions.ca\">Freeform Solutions</a>.";
																					}
																				}
                                }
                                
                                // record the caption and go through to the next one if this one already exists in this form
                                if(isset($foundCaptions[$dataArray['ele_caption']])) {
                                  print "Warning: you have duplicate captions, '".$dataArray['ele_caption']."', in your data, at entry number " .$dataArray['id_req'] . " (id_req) in form ".$thisFormObject->getVar('id_form').".  Only the first value found will be copied to the new data structure.  Please contact <a href=\"mailto:formulize@freeformsolutions.ca\">Freeform Solutions</a> if you would like assistance cleaning this up.  This will NOT affect the upgrade to version 3.<br>";
                                  continue;
                                } else {
                                  $foundCaptions[$dataArray['ele_caption']] = true;
                                }                                
                                
                                // need to handle linked selectboxes, and convert them to a different format, and store the entry_id of the sources
                                // We are going to store a comma separated list of entry_ids, with leading and trailing commas so a LIKE operator can be used to do a join in the database
                                if(strstr($dataArray['ele_value'], "#*=:*")) {
                                        $boxproperties = explode("#*=:*", $dataArray['ele_value']);
                                        $source_ele_ids = explode("[=*9*:", $boxproperties[2]);
                                        // get the id_reqs of the source ele_ids
                                        $sourceIdReqSQL = "SELECT id_req FROM " . $xoopsDB->prefix("formulize_form") . " WHERE ele_id = " . implode(" OR ele_id = ", $source_ele_ids) . " ORDER BY id_req";
                                        $sourceIdReqRes = $xoopsDB->query($sourceIdReqSQL);
                                        $dataArray['ele_value'] = "";
                                        while($sourceIdReqArray = $xoopsDB->fetchArray($sourceIdReqRes)) {
                                                $dataArray['ele_value'] .=  "," . $sourceIdReqArray['id_req'];
                                        }
                                        if($dataArray['ele_value']) {
                                          $dataArray['ele_value'] .= ",";  
                                        }
                                }
                                if($dataArray['ele_type'] == "date" AND $dataArray['ele_value'] == "") {
																	continue; // don't write in blank date values, let them get the default NULL value for the field
																} 
																
                                $insertSQL .= ", `" . $captionHandleIndex[$dataArray['ele_caption']] . "`=\"" . mysql_real_escape_string($dataArray['ele_value']) . "\"";
                        }
                        if($insertSQL) {
                                if(!$insertRes = $xoopsDB->query($insertSQL)) {
                                        exit("Error: could not write data to the new table structure with this SQL: $insertSQL.<br>".mysql_error()."<br>Please report this error to <a href=\"mailto:formulize@freeformsolutions.ca\">Freeform Solutions</a>.");
                                }
                        }
                        print "Migrated data to new data structure for form " . $thisFormObject->getVar('id_form') . ".  result: OK<br>\n";
                        unset($allFormObjects[$formObjectId]); // attempt to free up some memory
                }
      
      if($derivedResult = formulize_createDerivedValueFieldsInDB()) {
        print "Created derived value fields in database.  result: OK<br>\n";
      } else {
        print "Unable to create derived value fields in database.  result: failed.  contact <a href=\"mailto:formulize@freeformsolutions.ca\">Freeform Solutions</a> for assistance.<br>\n";
      }
      
      // convert the captions in the linked selectbox defintions to the handles for those elements
      // 1. lookup all elements that are linked selectboxes in the formulize table (element table) -- db query for element ids
      // 2. for each one, get the caption that is stored there -- PHP level work with element handler
      // 3. get the handle corresponding to that caption
      // 4. rewrite the ele_value[2] with the handle instead of caption
      // 5. reinsert that value into the DB
      $sql = "SELECT ele_id FROM " . $xoopsDB->prefix("formulize") . " WHERE ele_value LIKE '%#*=:*%'";
      if(!$res = $xoopsDB->query($sql)) {
        exit("Error: cound not get the element ids of the linked selectboxes.  SQL: $sql<br>".mysql_error()."<br>Please report this error to <a href=\"mailto:formulize@freeformsolutions.ca\">Freeform Solutions</a>.");
      }
      $element_handler =& xoops_getmodulehandler('elements', 'formulize');
      while($array = $xoopsDB->fetchArray($res)) {
        $elementObject = $element_handler->get($array['ele_id']);
        $ele_value = $elementObject->getVar('ele_value');
        $parts = explode("#*=:*", $ele_value[2]);
        $sql2 = "SELECT ele_handle FROM " . $xoopsDB->prefix("formulize") . " WHERE ele_caption = '". mysql_real_escape_string($parts[1]) . "' AND id_form=". $parts[0];
				//print "$sql2<br>";
        if(!$res2 = $xoopsDB->query($sql2)) {
          exit("Error: could not get the handle for a linked selectbox source.  SQL: $sql2<br>".mysql_error()."<br>Please report this error to <a href=\"mailto:formulize@freeformsolutions.ca\">Freeform Solutions</a>.");
        }
        $array2 = $xoopsDB->fetchArray($res2);
        if($array2['ele_handle'] == "") {
          print "Warning: a handle could not be identified for this caption: '".$parts[1]."', in form ".$parts[0]."  This breaks linked selectboxes for element number ".$array['ele_id'].".  This is most likely caused by an old caption that was changed for the element, in an old version of Formulize.<br>Please report this error to <a href=\"mailto:formulize@freeformsolutions.ca\">Freeform Solutions</a>.<br>";
        }
        $ele_value[2] = $parts[0]."#*=:*".$array2['ele_handle'];
        $elementObject->setVar('ele_value', $ele_value);
        if(!$res3 = $element_handler->insert($elementObject)) {
          exit("Error: could not update the linked selectbox metadata. <br>".mysql_error()."<br>Please report this error to <a href=\"mailto:formulize@freeformsolutions.ca\">Freeform Solutions</a>.");
        }
        unset($parts);
        unset($elementObject);
      }
      print "Updated the linked selectbox definitions new metadata.  result: OK<br><br><b>NOTE:</b> Although the 3.0 data structure is highly optimized compared to previous versions of Formulize, there are some situations which we cannot account for automatically in the upgrade process:  if you have elements in a form and users only enter numerical data there, you should edit those elements now and give them a truly numeric data type (use the new option on the element editing page to do this).  In Formulize 3 and higher, elements that have only numbers, but which are not stored as numbers in the database, will not sort properly and some calculations will not work correctly on them either.  Unfortunately, we cannot reliably determine which numeric data type should be used for all elements, therefore you will need to make this adjustment manually.  We apologize for any inconvenience.  Please contact <a href=\"mailto:formulize@freeformsolutions.ca\">Freeform Solutions</a> if you have any questions about this process.<br><br>\n";
      
      print "Data migration complete.  result: OK\n";
		
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
	modform($_GET['title']);
	break;
case "delform":
	delform();
	break;
case "lockform":
	lockform();
	break;
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
case "patch40":
	patch40();
	break;
case "patch31":
	patch31();
	break;
case "patch22convertdata":
	patch22convertdata();
	break;
case "patch30datastructure":
        patch30DataStructure();
        break;
}

print "<p>version 4.0 SVN (trunk)</p>";

include 'footer.php';
    xoops_cp_footer();

?>




