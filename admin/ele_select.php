<?php
if( !preg_match("/elements.php/", $_SERVER['PHP_SELF']) ){
	exit("Access Denied");
}
if( empty($addopt) && !empty($ele_id) ){
	$ele_value = $element->getVar('ele_value');
}
$ele_size = !empty($ele_value[0]) ? $ele_value[0] : 1;
$size = new XoopsFormText(_AM_ELE_SIZE, 'ele_value[0]', 3, 2, $ele_size);
$allow_multi = empty($ele_value[1]) ? 0 : 1;
$multiple = new XoopsFormRadioYN(_AM_ELE_MULTIPLE, 'ele_value[1]', $allow_multi);

$options = array();
$opt_count = 0;
if( empty($addopt) && !empty($ele_id) ){
	$keys = array_keys($ele_value[2]);
	for( $i=0; $i<count($keys); $i++ ){
		$v = $myts->makeTboxData4PreviewInForm($keys[$i]);
		$options[] = addOption('ele_value[2]['.$opt_count.']', 'checked['.$opt_count.']', $v, 'check', $ele_value[2][$keys[$i]]);
		$opt_count++;
	}
/*	while( $var = each($ele_value[2]) ){
		$v = $myts->makeTboxData4PreviewInForm($var['key']);
		$t1 = new XoopsFormCheckBox('', 'checked['.$opt_count.']', $var['value']);
		$t1->addOption(1, ' ');
		$t2 = new XoopsFormText('', 'ele_value[2]['.$opt_count.']', 40, 255, $v);
		$t3 = new XoopsFormElementTray('');
		$t3->addElement($t1);
		$t3->addElement($t2);
//  		$t3 = new XoopsFormLabel('', $t1->render().$t2->render());
		$options[] = $t3;
		$opt_count++;
	}	*/
}else{
	if( !empty($ele_value[2]) ){
		while( $v = each($ele_value[2]) ){
			$v['value'] = $myts->makeTboxData4PreviewInForm($v['value']);
			if( !empty($v['value']) ){
		/*		$t1 = new XoopsFormCheckBox('', 'checked['.$opt_count.']', $checked[$v['key']]);
				$t1->addOption(1, ' ');
				$t2 = new XoopsFormText('', 'ele_value[2]['.$opt_count.']', 40, 255, $v['value']);
// 				$t3 = new XoopsFormElementTray('');
// 				$t3->addElement($t1);
// 				$t3->addElement($t2);
 				$t3 = new XoopsFormLabel('', $t1->render().$t2->render());
				$options[] = $t3;	*/
				
				$options[] = addOption('ele_value[2]['.$opt_count.']', 'checked['.$opt_count.']', $v['value'], 'check', $checked[$v['key']]);
				$opt_count++;
			}
		}
	}
	$addopt = empty($addopt) ? 2 : $addopt;
	for( $i=0; $i<$addopt; $i++ ){
		$options[] = addOption('ele_value[2]['.$opt_count.']', 'checked['.$opt_count.']');
		$opt_count++;
	}
}

$add_opt = addOptionsTray();
$options[] = $add_opt;

$opt_tray = new XoopsFormElementTray(_AM_ELE_OPT, '<br />');
$opt_tray->setDescription(_AM_ELE_OPT_DESC._AM_ELE_OPT_DESC1);
for( $i=0; $i<count($options); $i++ ){
	$opt_tray->addElement($options[$i]);
}

// create the $formlink, the part of the selectbox form that links to another field in another form to populate the select box. -- jwe 7/29/04
array($formids);
array($formnames);
array($totalcaptionlist);
array($totalvaluelist);
$captionlistindex = 0;

$formlist = "SELECT id_form, desc_form FROM " . $xoopsDB->prefix("form_id") . " ORDER BY desc_form";
$resformlist = mysql_query($formlist);
if($resformlist)
{
	while ($rowformlist = mysql_fetch_row($resformlist)) // loop through each form
	{
		$fieldnames = "SELECT ele_caption FROM " . $xoopsDB->prefix("form") . " WHERE id_form=$rowformlist[0] ORDER BY ele_order";
		$resfieldnames = mysql_query($fieldnames);
		
		while ($rowfieldnames = mysql_fetch_row($resfieldnames)) // loop through each caption in the current form
		{
			$totalcaptionlist[$captionlistindex] = $rowformlist[1] . ": " . $rowfieldnames[0];  // write formname: caption to the master array that will be passed to the select box.
			$totalvaluelist[$captionlistindex] = $rowformlist[0] . "#*=:*" . $rowfieldnames[0];
			if($ele_value[2] == $totalvaluelist[$captionlistindex]) // if this is the selected entry...
			{
				$defaultlinkselection = $captionlistindex;
			}
			$captionlistindex++;
		}
	}
}
// make the select box and add all the options... -- jwe 7/29/04
$formlink = new XoopsFormSelect(_AM_ELE_FORMLINK, 'formlink', '' , 1, false);
$formlink->addOption("none", _AM_FORMLINK_NONE);
for($i=0;$i<$captionlistindex;$i++)
{
	$formlink->addOption($totalvaluelist[$i], $totalcaptionlist[$i]);
}
if($defaultlinkselection)
{
	$formlink->setValue($totalvaluelist[$defaultlinkselection]);
}
$formlink->setDescription(_AM_ELE_FORMLINK_DESC);


$form->addElement($size, 1);
$form->addElement($multiple);
$form->addElement($opt_tray);
// added another form element, the dynamic link to another form's field to populate the selectbox.  -- jwe 7/29/04
$form->addElement($formlink);


// print_r($options);
// 	echo '<br />';

?>