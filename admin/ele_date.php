<?php

if( !preg_match("/elements.php/", $_SERVER['PHP_SELF']) ){
	exit("Access Denied");
}
$date = new XoopsFormTextDateSelect (_AM_ELE_DATE, 'ele_value',  $size = 15, $value = "01-01-2004");
$form->addElement($date);

?>