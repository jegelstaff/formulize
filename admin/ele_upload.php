<?php
if( !preg_match("/elements.php/", $_SERVER['PHP_SELF']) ){
	exit("Access Denied");
}
include_once XOOPS_ROOT_PATH.'/class/xoopsformloader.php';

$p = !empty($value[1]) ? $value[1] : $xoopsModuleConfig['weight'];

$pds = new XoopsFormElementTray (_AM_ELE_TAILLEFICH, '');
$pds->addElement (new XoopsFormText ('', 'ele_value[1]', 15, 15, $p));
$pds->addElement (new XoopsFormLabel ('', ' bits'));
$form->addElement ($pds);

$tab = array();
foreach ($value[2] as $t => $k) {
	foreach ($k as $c => $f){
		$tab[] = $value[2][$t]['value'];
	}
}

$mime = new XoopsFormCheckBox (_AM_ELE_TYPEMIME, 'ele_value[2]', $tab);
$mime->addOption('pdf',' pdf ');
$mime->addOption('doc',' doc ');
$mime->addOption('txt',' txt ');
$mime->addOption('gif',' gif ');
$mime->addOption('mpeg',' mpeg ');
$mime->addOption('jpg',' jpg ');
$form->addElement($mime);

$fichier = new XoopsFormFile (_AM_ELE_FICH, $ele_value[0], $ele_value[1]);	
$form->addElement ($fichier);


?>