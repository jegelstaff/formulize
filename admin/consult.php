<?php
//	Version du portable marchand bien, sauf quand aucun nom n'est ajouté au fichier joint
include("admin_header.php");
global $xoopsDB, $xoopsConfig;

if( is_dir(formulize_ROOT_PATH."/language/".$xoopsConfig['language']."/mail_template") ){
	$template_dir = formulize_ROOT_PATH."/language/".$xoopsConfig['language']."/mail_template";
}else{
	$template_dir = formulize_ROOT_PATH."/language/english/mail_template";
}
        xoops_cp_header();

if(!isset($HTTP_POST_VARS['form'])){
	$form = isset ($HTTP_GET_VARS['form']) ? $HTTP_GET_VARS['form'] : '1';
}else {
	$form = $HTTP_POST_VARS['form'];
}
if(!isset($HTTP_POST_VARS['req'])){
	$req = isset ($HTTP_GET_VARS['req']) ? $HTTP_GET_VARS['req'] : '';
}else {
	$req = $HTTP_POST_VARS['req'];
}


// formindex.php renvoi le desc_form :
$id_form = $form;
		/************* Affichage de tous les enregistrements *************/
if ($req == null) {
	$req = array();
	$date = array();
	$desc_form = array();
	$sql = "SELECT id_req, uid, date FROM " . $xoopsDB->prefix("form_form") . " WHERE id_form= ".$id_form." GROUP BY id_req" ;
	$result = mysql_query($sql) or die("Requete SQL ligne 52");
	if ($result) {
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
	       		$req[$row["id_req"]] = $row["uid"];
          		$date[$row["id_req"]] = $row["date"];
          	}
	}
	//Selection du nom du formulize
	$sql = "SELECT desc_form FROM " . $xoopsDB->prefix("form_id") . " WHERE id_form=".$id_form;
	$result = mysql_query($sql) or die("Requete SQL ligne 59");
	if ($result) {
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)){ $desc_form[] = $row['desc_form']; }
	} $desc_form = $desc_form[0];

	foreach ($date as $id => $d) {
		$a = substr ($d, 0, 4);
		$m = substr ($d, 5, 2);
		$j = substr ($d, 8, 2);
		$date[$id] = $j.'/'.$m.'/'.$a;
	}
	
	echo '<table class="outer" cellspacing="1" width="100%"><th><center><font size=5>'._AM_FORM.$desc_form.'</font></center></th>';
	foreach ($req as $id_req => $uid) {
		echo '<tr><td class="even"><li><a href="consult.php?form='.$id_form.'&req='.$id_req.'">'._AM_FORM_REQ.XoopsUser::getUnameFromId($uid)." le ".$date[$id_req].'</a></td></tr>';
	}		
	echo '</table>';
}

		/************************** Affichage d'un enregistrement sélectionné **************************/
else {


//check if start page is defined

	$sql= "SELECT id_form,admin,groupe,email,expe FROM ".$xoopsDB->prefix("form_id")." WHERE id_form=".$form;
	$res = mysql_query ( $sql ) or die('Erreur SQL consult.php ligne 90!<br>'.$sql.'<br>'.mysql_error());

	$sql2= "SELECT ele_caption, ele_value, ele_type, uid FROM ".$xoopsDB->prefix("form_form")." WHERE id_form=".$form." AND id_req= ".$req;
	$res2 = mysql_query ( $sql2 ) or die('Erreur SQL consult.php ligne 93<br>'.$sql2.'<br>'.mysql_error());

if ( $res ) {
  while ( $row = mysql_fetch_array ( $res ) ) {
    $id_form = $row['id_form'];
    $admin = $row['admin'];
    $groupe = $row['groupe'];
    $email = $row['email'];
    $expe = $row['expe'];
  }
}

$requete = array();
$type = array();
if ($res2) {
	while ($row = mysql_fetch_array ($res2)) {
		if ($row['ele_caption'] != _AM_SEPAR){
			$row['ele_value'] = nl2br ($row['ele_value']);
			$requete[$row['ele_caption']] = $row['ele_value'];
			$type[$row['ele_caption']] = $row['ele_type'];
			$uid = $row['uid'];
		}
	}
}
$uname_submitter = XoopsUser::getUnameFromId($uid);
 
$sql =  mysql_query("SELECT desc_form FROM " . $xoopsDB->prefix("form_id") . " WHERE id_form= ".$form);
$desc_form = mysql_fetch_row($sql);
if ($uid != 0){ 
	$sub = "<br>"._AM_SUBMITBY.$uname_submitter;
	}
	else
	{
		$sub = "";
		}
$form2 = "<center><font size=4>"._AM_FORM."</font></center>";

//foreach ($requete as $r) echo '<br>'.$r;
	
	echo '
	<form action="formindex.php" method="post">
	<table class="outer" cellspacing="1" width="100%">
	<th><center><font size=5>'._AM_REQ.$desc_form[0].$sub.'</font></center></th>
	</table>';

echo '<table class="outer" cellspacing="1" width="100%">';

foreach( $requete as $k => $v ){
	if (substr ($v, 0, 1) == ':') {
		$selected = array();
		$v = substr ($v, 1);
		$selected = split (':', $v);
		$v = implode (',', $selected);
	}
	foreach ($type as $tk => $t) {
		if ($t == 'yn' && $tk == $k){
			if ($v == '1') $v = _YES;
			if ($v == '2') $v = _NO;
		}
		/*if ($t == 'date' && $tk == $k){
			$datea=substr($v, 0, 4); 
			$datem=substr($v, 5, 2); 
			$datej=substr($v, 8, 2); 
			$v = $datej .'/' .$datem .'/' .$datea; 
		}*/
	}
	echo '<tr><td class="even"><li>'.$k.'</td><td class="even">'.$v.'</td></tr>';
}

echo '</table>
	<table class="outer" cellspacing="1" width="100%">
	';
	echo '<td class="foot" colspan="7" align=center><a href="formindex.php"><img src="../images/retour.png" alt="Retour"></a></tr></table>';
	echo '</form>';
}


include 'footer.php';
    xoops_cp_footer();

?>