<?php

include("admin_header.php");
include_once '../../../include/cp_header.php';
if ( file_exists("../language/".$xoopsConfig['language']."/main.php") ) {
	include "../language/".$xoopsConfig['language']."/main.php";
} else {
	include "../language/english/main.php";
}


global $HTTP_POST_VARS;
include_once XOOPS_ROOT_PATH."/class/xoopstree.php";
include_once XOOPS_ROOT_PATH."/class/xoopslists.php";
include_once XOOPS_ROOT_PATH."/include/xoopscodes.php";
include_once XOOPS_ROOT_PATH."/class/module.errorhandler.php";
$myts =& MyTextSanitizer::getInstance();

if(!isset($HTTP_POST_VARS['title'])){
	$title = isset ($HTTP_GET_VARS['title']) ? $HTTP_GET_VARS['title'] : '0';
}else {
	$title = $HTTP_POST_VARS['title'];
}
if(!isset($HTTP_POST_VARS['op'])){
	$op = isset ($HTTP_GET_VARS['op']) ? $HTTP_GET_VARS['op'] : '0';
}else {
	$op = $HTTP_POST_VARS['op'];
}



	$sql="SELECT desc_form FROM ".$xoopsDB->prefix("form_id")." WHERE id_form = ".$title;
	$res = mysql_query ( $sql );

if ( $res ) {
  while ( $row = mysql_fetch_row ( $res ) ) {
    $desc_form = $row[0];
  }
}

xoops_cp_header();
$op = isset ($HTTP_GET_VARS['op']) ? $HTTP_GET_VARS['op'] : '0';

if ($op != 1) {
	echo '	<form action="renom.php?title='.$title.'&op=1" method="post">
	<table class="outer" width="100%">

	<th colspan="2"><center><font size=4>'.$desc_form.'</font></center></th>';

	echo '<tr><td class="head" ALIGN=center>'._FORM_NOM.'</td>
	      <td class="odd" align="center">  
	      <input maxlength="255" size="50" id="title2" name="title2" type="text"></a></td></tr>';

	$submit = new XoopsFormButton('', 'submit', _SUBMIT, 'submit');
	echo '  <tr>
		<td class="foot" colspan="7">'.$submit->render().'
		</tr>
		</table>';
	//$renom = new XoopsFormHidden($title2, $title2);
	//$renom->render();
		
	echo '</form>';
}

else
{
	global $xoopsDB, $HTTP_POST_VARS, $myts, $eh, $desc_form, $title2;
	$title2 = $myts->makeTboxData4Save($HTTP_POST_VARS["title2"]);
	//$title3 = $myts->makeTboxData4Save($HTTP_POST_VARS["desc_form3"]);
	if (empty($title)) {
		redirect_header("formindex.php", 2, _MD_ERRORTITLE);
	}
	$title2 = stripslashes($title2);
	$title2 = eregi_replace ("'", "`", $title2);
	$title2 = eregi_replace ('"', "`", $title2);
	$title2 = eregi_replace ('&', "_", $title2);
	
	$sql = sprintf("UPDATE %s SET desc_form='%s' WHERE id_form='%s'", $xoopsDB->prefix("form_id"), $title2, $title);
	$xoopsDB->queryF($sql) or $eh->show("error insertion 1 dans renform");
	
	$sql2 = sprintf("UPDATE %s SET itemname='%s',itemurl='%s' WHERE itemname='%s'", $xoopsDB->prefix("form_menu"), $title2, XOOPS_URL.'/modules/formulize/index.php?title='.$title2, $desc_form);
	$xoopsDB->query($sql2) or $eh->show("error insertion 2 dans renform");
	redirect_header("formindex.php",1,_formulize_FORMMOD);
}