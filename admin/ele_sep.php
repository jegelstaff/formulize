<?php

if( !preg_match("/elements.php/", $_SERVER['PHP_SELF']) ){
	exit("Access Denied");
}
$options = array();
$opt_count = 0;

$rows = !empty($value[0]) ? $value[1] : $xoopsModuleConfig['ta_rows'];
$cols = !empty($value[0]) ? $value[2] : $xoopsModuleConfig['ta_cols'];
$rows = new XoopsFormText (_AM_ELE_ROWS, 'ele_value[1]', 3, 3, $rows);
$cols = new XoopsFormText (_AM_ELE_COLS, 'ele_value[2]', 3, 3, $cols);
$type = new XoopsFormCheckBox (_AM_ELE_TYPE, 'option', null);
$type->addOption ('centre', ' '._AM_ELE_CTRE.'<br />');
$type->addOption ('souligné', ' '._AM_ELE_SOUL.'<br />');
$type->addOption ('italique', ' '._AM_ELE_ITALIQ.'<br />');
$default = new XoopsFormTextArea(_AM_ELE_DEFAULT, 'ele_value[0]', $value[0], 5, 35);

$tab = array ("Noir"=>"#000000", "Marron"=>"#97694F", "Bleu"=>"#7093DB", "Rouge"=>"#e00000", "Vert"=>"#4A766E", "Rose"=>"#9F5F9F", "Jaune"=>"#ffff00", "Blanc"=>"#ffffff");
$couleur = new XoopsFormSelect (_AM_ELE_CLR, 'couleur', null, 5, false);
foreach ($tab as $cle=>$tab) {
	$couleur->addOption($tab, $cle);
}

$form->addElement($rows, 1);
$form->addElement($cols, 1);
$form->addElement($default);
$form->addElement($type);
$form->addElement ($couleur);
?>