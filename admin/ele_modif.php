<?php

if( !preg_match("/elements.php/", $_SERVER['PHP_SELF']) ){
	exit("Access Denied");
}
$rows = !empty($value[0]) ? $value[1] : $xoopsModuleConfig['ta_rows'];
$cols = !empty($value[0]) ? $value[2] : $xoopsModuleConfig['ta_cols'];
$rows = new XoopsFormText(_AM_ELE_ROWS, 'ele_value[1]', 3, 3, $rows);
$cols = new XoopsFormText(_AM_ELE_COLS, 'ele_value[2]', 3, 3, $cols);
$default = new XoopsFormTextArea(_AM_ELE_DEFAULT, 'ele_value[0]', $value[0], 5, 35);
$form->addElement($rows, 1);
$form->addElement($cols, 1);
$form->addElement($default);

?>