<?php
if( !preg_match("/elements.php/", $_SERVER['PHP_SELF']) ){
	exit("Access Denied");
}

// altered to better handle default setting -- jwe 7/28/04

if( !empty($ele_id) ){
	if( $value['_YES'] == 1 ){
		$selected = '_YES';
	}elseif( $value['_NO'] == 1) {
		$selected = '_NO';
	}
}
if($selected)
{
	$options = new XoopsFormRadio(_AM_ELE_DEFAULT, 'ele_value', $selected);
}
else
{
	$options = new XoopsFormRadio(_AM_ELE_DEFAULT, 'ele_value');
}	
$options->addOption('_YES', _YES);
$options->addOption('_NO', _NO);
$form->addElement($options);


?>