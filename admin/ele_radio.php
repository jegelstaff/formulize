<?php
if( !preg_match("/elements.php/", $_SERVER['PHP_SELF']) ){
	exit("Access Denied");
}
$options = array();
$opt_count = 0;
if( empty($addopt) && !empty($ele_id) ){
	$keys = array_keys($value);
	for( $i=0; $i<count($keys); $i++ ){
		$r = $value[$keys[$i]] ? $opt_count : null;
		$v = $myts->makeTboxData4PreviewInForm($keys[$i]);
		$options[] = addOption('ele_value['.$opt_count.']', $opt_count, $v, 'radio', $r);
		$opt_count++;
	}
/*	while( $var = each($value) ){
		$v = $myts->makeTboxData4PreviewInForm($var['key']);
		$r = $var['value'] ? $opt_count : null;
		$t1 = new XoopsFormRadio('', 'checked', $r);
		$t1->addOption($opt_count, ' ');
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
		/*	if( $checked == $opt_count ){
				$t1 = new XoopsFormRadio('', 'checked', $opt_count);
			}else{
				$t1 = new XoopsFormRadio('', 'checked');
			}
			$t1->addOption($opt_count, ' ');
			$t2 = new XoopsFormText('', 'ele_value['.$opt_count.']', 40, 255, $v['value']);
			$t3 = new XoopsFormElementTray('');
			$t3->addElement($t1);
			$t3->addElement($t2);
			$options[] = $t3;	*/
			$r = ($checked == $opt_count) ? $opt_count : null;
			$options[] = addOption('ele_value['.$opt_count.']', $opt_count, $v['value'], 'radio', $r);
			$opt_count++;
		}
	}
	$addopt = empty($addopt) ? 2 : $addopt;
	for( $i=0; $i<$addopt; $i++ ){
		$options[] = addOption('ele_value['.$opt_count.']', $opt_count, '', 'radio');
		$opt_count++;
	}
}
$options[] = addOptionsTray();
$opt_tray = new XoopsFormElementTray(_AM_ELE_OPT, '<br />');
$opt_tray->setDescription(_AM_ELE_OPT_DESC2);
for( $i=0; $i<count($options); $i++ ){
	$opt_tray->addElement($options[$i]);
}
$form->addElement($opt_tray);
?>