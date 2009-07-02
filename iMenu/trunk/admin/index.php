<?php
include "../../../include/cp_header.php";
if ( file_exists("../language/".$xoopsConfig['language']."/admin.php") ) {
	include "../language/".$xoopsConfig['language']."/admin.php";
} else include "../language/english/admin.php";


// begin - Nov 6, 2005 - jpc - Freeform Solutions

if(file_exists(XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php")) {
	include_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php";
}

// end - Nov 6, 2005 - jpc - Freeform Solutions


$op = '';
foreach ($_POST as $k => $v) {
	${$k} = $v;
}

if (isset($_GET['op'])) {
	$op = $_GET['op'];
	if (isset($_GET['id'])) {
		$id = intval($_GET['id']);
	}
}
switch($op) {
case "new":
	im_admin_new();
	break;
case "edit":
	im_admin_edit($id);
	break;
case "update":
	im_admin_update($id, $title, $link, $hide, $groups, $target, $parent);
	break;
case "del":
	im_admin_del($id, $del);
	break;
case "patch31":
	patch31();
	break;
case "move":

	// added check for GET values here since this operation relies on GET params -- jwe 02/18/05
	$id = $_GET['id'];
	$weight = $_GET['weight'];

	im_admin_move($id, $weight);
	im_admin_list();
	break;
case "perms":
	
	xoops_cp_header();
	show_menu();
	global $xoopsDB, $xoopsModule;
	$myts =& MyTextSanitizer::getInstance();
	include_once XOOPS_ROOT_PATH.'/class/xoopsform/grouppermform.php';

	$module_id = $xoopsModule->getVar('mid');


	$sql="SELECT id,title FROM ".$xoopsDB->prefix("imenu");
	$res = $xoopsDB->query( $sql );
	if ( $res ) {
		$tab = array();
		while ( $array = $xoopsDB->fetchArray( $res ) ) {
			$title = $myts->formatForML($array['title']);
			$tab[$array['id']] = $title." (".$array['id'].")";
		  }
	}
	$title_of_form = "iMenu Permissions";
	$perm_name = "view";
	$perm_desc = 'Select the menu entries that each group is allowed to view:';
	$form = new XoopsGroupPermForm($title_of_form, $module_id, $perm_name, $perm_desc); 
	foreach($tab as $item_id => $item_name) {
		if($item_name != "") $form->addItem($item_id, $item_name);
	} 
	echo $form->render();
	xoops_cp_footer();
	break;
default:
	im_admin_list();
	break;
}

function im_admin_update($id, $title, $link, $hide, $groups, $target, $parent) {
	$xoopsDB =& Database::getInstance();
	$myts =& MyTextSanitizer::getInstance();
	$title = $myts->makeTboxData4Save($title);
	$link = $myts->makeTboxData4Save($link);
	$groups = (is_array($groups)) ? implode(" ", $groups) : '';
	if ( empty($id) ) {
		$newid = $xoopsDB->genId($xoopsDB->prefix("imenu")."_id_seq");
		$success = $xoopsDB->query("INSERT INTO ".$xoopsDB->prefix("imenu")." (id,title,hide,link,weight,groups,target,parent) VALUES ($newid,'$title','$hide','$link','255','$groups','$target','$parent')");
		im_admin_clean();
	} else {
		$success = $xoopsDB->query("UPDATE ".$xoopsDB->prefix("imenu")." SET title='$title', hide='$hide', link='$link', groups='$groups', target='$target', parent='$parent' WHERE id='$id'");
	}
	if ( !$success ) redirect_header("index.php",2,_IM_UPDATED);
	else redirect_header("index.php",2,_IM_UPDATED);
	exit();
}

function im_admin_edit ($id) {
	xoops_cp_header();
	$xoopsDB =& Database::getInstance();
	$result = $xoopsDB->query("SELECT title, hide, link, groups, target, parent FROM ".$xoopsDB->prefix("imenu")." WHERE id=$id");
	list($title, $hide, $link, $groups, $target, $parent) = $xoopsDB->fetchrow($result);
	$groups = explode(" ", $groups);
	include XOOPS_ROOT_PATH."/class/xoopsformloader.php";
	$form = new XoopsThemeForm(_IM_EDITIMENU, "editform", "index.php");
	include '../imenuform.inc.php';
	$form->display();
	xoops_cp_footer();
}

function im_admin_del($id, $del=0) {
	$xoopsDB =& Database::getInstance();
	if ( $del == 1 ) {
		if ( $xoopsDB->query("DELETE FROM ".$xoopsDB->prefix("imenu")." WHERE id=$id") ) {
			im_admin_clean();
			redirect_header("index.php", 2, _IM_UPDATED);
		} else {
			redirect_header("index.php", 2, _IM_NOTUPDATED);
		}
		exit();
	} else {
		xoops_cp_header();
		echo "<h4>"._IM_IMENUADMIN."</h4>";
		xoops_confirm(array('op' => 'del', 'id' => $id, 'del' => 1), 'index.php', _IM_SUREDELETE);
		xoops_cp_footer();
	}

}

function im_admin_move ($id, $weight) {
	$xoopsDB =& Database::getInstance();
	$parentQ = $xoopsDB->query("SELECT parent FROM " . $xoopsDB->prefix("imenu") . " WHERE id='$id'");
	$array = $xoopsDB->fetchArray($parentQ);
	$parent = $array['parent'];
	$xoopsDB->queryF("UPDATE ".$xoopsDB->prefix("imenu")." SET weight=weight+1 WHERE weight>=$weight AND id<>$id AND parent='$parent'");
	$xoopsDB->queryF("UPDATE ".$xoopsDB->prefix("imenu")." SET weight=$weight WHERE id=$id");
	im_admin_clean();
}

function im_admin_new() {
	xoops_cp_header();
	$id = 0;
	$title = '';
	$link = '';
	$hide = '';
	$weight = 255;
	$target = "_self";
	$member_handler =& xoops_gethandler('member');
	$xoopsgroups =& $member_handler->getGroups();
	$count = count($xoopsgroups);
	$groups = array();
	for ($i = 0; $i < $count; $i++)  $groups[] = $xoopsgroups[$i]->getVar('groupid');
	include XOOPS_ROOT_PATH."/class/xoopsformloader.php";
	$form = new XoopsThemeForm(_IM_NEWIMENU, "newform", "index.php");
	include '../imenuform.inc.php';
	$form->display();
	xoops_cp_footer();
}

function show_menu() {
		echo "<h4>"._IM_IMENUADMIN."</h4>";

	/*print "<table><tr>";
	    print "<td><a href='index.php'>" . _IM_MENU_ITEMS . "</a> | <a href='index.php?op=perms'>" . _IM_PERMS . "</a>";
	    print "</td>\n";
	    print "</tr></table><BR>";*/
}

function im_admin_list() {

	// modified by Freeform Solutions May 19, 2005
	// 1. get all the parents
	// 2. loop through the parents by weight
	// 3. for each parent, get all its kids
	// 4. loop through the kids by weight

	xoops_cp_header();
	$xoopsDB =& Database::getInstance();
	show_menu();

	echo "<form action='index.php?op=new' method='post' name='form1'>
	<table width='100%' border='0' cellspacing='1' cellpadding='0' class='outer'><tr>
	<th align='center'>"._IM_TITLE."</th>
	<th align='center'>"._IM_HIDE."</th>
	<th align='center'>"._IM_LINK."</th>
	<th align='center'>"._IM_OPERATION."</th></tr>";

	$resultParent = $xoopsDB->query("SELECT id, link, title, hide, weight FROM ".$xoopsDB->prefix("imenu")." WHERE parent='0' ORDER BY weight ASC");
	$numParents = $xoopsDB->getRowsNum($resultParent);

	$upP = 0;
	$downP = 1;
	$counterP = 0;

	while($resP = $xoopsDB->fetchArray($resultParent)) {
		$counterP++;
		if($counterP == $numParents) { $downP = 0; } // turn off down link for last entry
		writeRow($resP['id'], $resP['link'], $resP['title'], $resP['hide'], $resP['weight'], 'foot', $upP, $downP, $prevWeightP);
		$prevWeightP = $resP['weight'];
		$upP = 1; // turn on up link for all entries after first one
		$upC = 0;
		$downC = 1;
		$counterC = 0;
		$resultChild=$xoopsDB->query("SELECT id, link, title, hide, weight FROM ".$xoopsDB->prefix("imenu")." WHERE parent='" . $resP['id'] . "' ORDER BY weight ASC");
		$numChildren = $xoopsDB->getRowsNum($resultChild);
		while($resC = $xoopsDB->fetchArray($resultChild)) {
			$counterC++;
			if($counterC == $numChildren) { $downC = 0; } // turn off down link for last entry
		
			if($class == 'even') {
				$class = 'odd';
			} else {
				$class = 'even';
			}
			writeRow($resC['id'], $resC['link'], $resC['title'], $resC['hide'], $resC['weight'], $class, $upC, $downC, $prevWeightC);
			$upC = 1; // turn on up link for all entries after first one		
			$prevWeightC = $resC['weight'];
		}
	}
	echo "<tr><td class='foot' colspan='4' align='right'>
	<input type='submit' name='submit' value='"._IM_NEW."'>
	</td></tr></table></form>";
	print "<p>version 3.1 Final</p>";
	xoops_cp_footer();

}

function writeRow($id, $link, $title, $hide, $weight, $class, $up, $down, $prevWeight) {
	if($hide == 0) {
		$status = _NO; 
	} else {
		$status = _YES;
	}
	if ($up) {
		$moveup = "<a href='index.php?op=move&id=".$id."&weight=".($prevWeight)."'>["._IM_UP."]</a>"; 
	} else {
		$moveup = "["._IM_UP."]";
	}
	if($down) {
		$movedown = "<a href='index.php?op=move&id=".$id."&weight=".($weight+2)."'>["._IM_DOWN."]</a>"; 
	} else {
		$movedown = "["._IM_DOWN."]";
	}
	echo "<tr><td class='$class'>";
	if($class != "foot") { echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"; }
	echo "$title</td>";
	echo "<td class='$class' align='center'>$status</td>";


    // begin - Nov 6, 2005 - jpc - Freeform Solutions

    // Is this an executable link?
    if(substr($link, 0, 2) == "<?")
    {
        $endPos = strlen($link) - 4;
        $executableLink = substr($link, 2, $endPos);

        $link = $executableLink;

        //echo "<br><br>link (execute php) <pre>" . $link . "</pre><br><br>";
    }                            
    // end - Nov 6, 2005 - jpc - Freeform Solutions


	echo "<td class='$class'>$link</td>";
	echo "<td class='$class' align='center'>";
	echo "<small><a href='index.php?op=del&id=$id'>["._DELETE."]</a>";
	echo "<a href='index.php?op=edit&id=$id'>["._EDIT."]</a><br>".$moveup.$movedown."</small>";
	echo "</td></tr>";

}

function patch31() {
	xoops_cp_header();
	if(!isset($_POST['migratedb'])) {
		print "<form action=\"index.php?op=patch31\" method=post>";
		print "<input type = submit name=migratedb value=\"Update Database\">";
		print "</form>";
	} else {
      	global $xoopsDB;      
			$sql[] = "ALTER TABLE ".$xoopsDB->prefix("imenu")." CHANGE `groups` `groups` text default NULL;";

      	for($i=0;$i<count($sql);$i++) {
      		if(!$result = $xoopsDB->query($sql[$i])) {
      			exit("Error in database update for iMenu 3.1.  SQL dump: " . $sql[$i]);
      		}
      	} 
      	print "DB update completed.";
	}
	xoops_cp_footer();
}


function im_admin_clean() {
	global $xoopsDB;
	$i=0;
	$result = $xoopsDB->query("SELECT id FROM ".$xoopsDB->prefix("imenu")." ORDER BY weight ASC");
	while (list($id) = $xoopsDB->fetchrow($result)) {
		$xoopsDB->queryF("UPDATE ".$xoopsDB->prefix("imenu")." SET weight='$i' WHERE id=$id");
		$i++;
	}
}

?>