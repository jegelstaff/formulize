<?php
if( !preg_match("/elements.php/", $_SERVER['PHP_SELF']) ){
	exit("Access Denied");
}
$options = array();
$opt_count = 0;
if( empty($addopt) && !empty($ele_id) ){
	$keys = array_keys($value);
	for( $i=0; $i<count($keys); $i++ ){
		$v = $myts->makeTboxData4PreviewInForm($keys[$i]);
		$options[] = addOption('ele_value['.$opt_count.']', 'checked['.$opt_count.']', $v, 'check', $value[$keys[$i]]);
		$opt_count++;
	}
/*	while( $var = each($value) ){
		$v = $myts->makeTboxData4PreviewInForm($var['key']);
		$t1 = new XoopsFormCheckBox('', 'checked['.$opt_count.']', $var['value']);
		$t1->addOption(1, ' ');
		$t2 = new XoopsFormText('', 'ele_value['.$opt_count.']', 40, 255, $v);
		$t3 = new XoopsFormElementTray('');
		$t3->addElement($t1);
		$t3->addElement($t2);
		$options[] = $t3;
		$opt_count++;
	}	*/
}else{
	while( $v = each($ele_value) ){
		$v['value'] = $myts->makeTboxData4PreviewInForm($v['value']);
		if( !empty($v['value']) ){
	/*		$t1 = new XoopsFormCheckBox('', 'checked['.$opt_count.']', $checked[$v['key']]);
			$t1->addOption(1, ' ');
			$t2 = new XoopsFormText('', 'ele_value['.$opt_count.']', 40, 255, $v['value']);
			$t3 = new XoopsFormElementTray('');
			$t3->addElement($t1);
			$t3->addElement($t2);
			$options[] = $t3;	*/
			$options[] = addOption('ele_value['.$opt_count.']', 'checked['.$opt_count.']', $v['value'], 'check', $checked[$v['key']]);
			$opt_count++;
		}
	}
	$addopt = empty($addopt) ? 2 : $addopt;
	for( $i=0; $i<$addopt; $i++ ){
		$options[] = addOption('ele_value['.$opt_count.']', 'checked['.$opt_count.']');
		$opt_count++;
	}
}
$add_opt = addOptionsTray();
$options[] = $add_opt;
$opt_tray = new XoopsFormElementTray(_AM_ELE_OPT, '<br />');
$opt_tray->setDescription(_AM_ELE_OPT_DESC);
for( $i=0; $i<count($options); $i++ ){
	$opt_tray->addElement($options[$i]);
}
$form->addElement($opt_tray);
?>