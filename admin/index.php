<?php
include_once ("admin_header.php");
include_once '../../../include/cp_header.php';

if(!isset($HTTP_POST_VARS['op'])){
	$op = isset ($HTTP_GET_VARS['op']) ? $HTTP_GET_VARS['op'] : '';
}else {
	$op = $HTTP_POST_VARS['op'];
}
if(!isset($HTTP_POST_VARS['title'])){
	$title = isset ($HTTP_GET_VARS['title']) ? $HTTP_GET_VARS['title'] : '';
}else {
	$title = $HTTP_POST_VARS['title'];
}


	$sql=sprintf("SELECT id_form FROM ".$xoopsDB->prefix("form_id")." WHERE desc_form='%s'",$title);
	$res = mysql_query ( $sql ) or die('Erreur SQL !<br>'.$requete.'<br>'.mysql_error());

if ( $res ) {
  while ( $row = mysql_fetch_row ( $res ) ) {
    $id_form = $row[0];
  }
}
 
if( $_POST['op'] != 'save' ){
	xoops_cp_header();

	echo '
	<form action="index.php?title='.$title.'" method="post">

	<table class="outer" cellspacing="1" width="98%">
	<th><center><font size=5>'._AM_FORM.$title.'<font></center></th>
	</table>';

	echo '<table class="outer" cellspacing="1" width="98%">
	<th><center>'._AM_ELE_CREATE.'</center></th>
	<tr><td class="even"><li><a href="elements.php?title='.$title.'&op=edit&amp;ele_type=text">'._AM_ELE_TEXT.'</a></td></tr>
	<tr><td class="even"><li><a href="elements.php?title='.$title.'&op=edit&amp;ele_type=textarea">'._AM_ELE_TAREA.'</a></td></tr>
	<tr><td class="even"><li><a href="elements.php?title='.$title.'&op=edit&amp;ele_type=areamodif">'._AM_ELE_MODIF.'</a></td></tr>
	<tr><td class="even"><li><a href="elements.php?title='.$title.'&op=edit&amp;ele_type=select">'._AM_ELE_SELECT.'</a></td></tr>
	<tr><td class="even"><li><a href="elements.php?title='.$title.'&op=edit&amp;ele_type=checkbox">'._AM_ELE_CHECK.'</a></td></tr>
	<tr><td class="even"><li><a href="elements.php?title='.$title.'&op=edit&amp;ele_type=radio">'._AM_ELE_RADIO.'</a></td></tr>
	<tr><td class="even"><li><a href="elements.php?title='.$title.'&op=edit&amp;ele_type=yn">'._AM_ELE_YN.'</a></td></tr>
	<tr><td class="even"><li><a href="elements.php?title='.$title.'&op=edit&amp;ele_type=date">'._AM_ELE_DATE.'</a></td></tr>
	<tr><td class="even"><li><a href="elements.php?title='.$title.'&op=edit&amp;ele_type=sep">'._AM_ELE_SEP.'</a></td></tr>
	<tr><td class="even"><li><a href="elements.php?title='.$title.'&op=edit&amp;ele_type=upload">'._AM_ELE_UPLOAD.'</a></td></tr>
	</table>';

	echo ' <table class="outer" cellspacing="1" width="98%">
		<tr>
			<th>'._AM_ELE_CAPTION.'</th>
			<th>'._AM_ELE_DEFAULT.'</th>
			<th>'._AM_ELE_REQ.'</th>
			<th>'._AM_ELE_ORDER.'</th>
			<th>'._AM_ELE_DISPLAY.'</th>
			<th colspan="3">&nbsp;</th>
		</tr>
	';
	$criteria = new Criteria(1,1);
	$criteria->setSort('ele_order');
	$criteria->setOrder('ASC');
	$elements =& $formulize_mgr->getObjects($criteria,$id_form);
	foreach( $elements as $i ){
		$id = $i->getVar('ele_id');
		$ele_value = $i->getVar('ele_value');
		$ele_value[0] = stripslashes ($ele_value[0]);
		$renderer =& new formulizeElementRenderer($i);
		$ele_value =& $renderer->constructElement('ele_value['.$id.']', true);
		$req = $i->getVar('ele_req');
		$check_req = new XoopsFormCheckBox('', 'ele_req['.$id.']', $req);
		$check_req->addOption(1, ' ');
		//if( $ele_type == 'checkbox' || $ele_type == 'radio' || $ele_type == 'yn' || $ele_type == 'select' || $ele_type == 'date' || $ele_type== 'areamodif' || $ele_type == 'upload' || $ele_type == 'areamodif' || $ele_type == 'sep'){
			$check_req->setExtra('disabled="disabled"');
		//}
		$order = $i->getVar('ele_order');
		$text_order = new XoopsFormText('', 'ele_order['.$id.']', 3, 2, $order);
		$display = $i->getVar('ele_display');
		$check_display = new XoopsFormCheckBox('', 'ele_display['.$id.']', $display);
		$check_display->addOption(1, ' ');
		$hidden_id = new XoopsFormHidden('ele_id[]', $id);
		if(is_array($ele_value))$ele_value[0] = addslashes ($ele_value[0]);

		echo '<tr>';
		echo '<td class="even">'.$i->getVar('ele_caption')."</td>\n";
		echo '<td class="even">'.$ele_value->render()."</td>\n";
		echo '<td class="even" align="center">'.$check_req->render()."</td>\n";
		echo '<td class="even" align="center">'.$text_order->render()."</td>\n";
		echo '<td class="even" align="center">'.$check_display->render().$hidden_id->render()."</td>\n";
		echo '<td class="even" align="center"><a href="elements.php?title='.$title.'&op=edit&amp;ele_id='.$id.'">'._EDIT.'</a></td>';
		echo '<td class="even" align="center"><a href="elements.php?title='.$title.'&op=edit&amp;ele_id='.$id.'&clone=1">'._CLONE.'</a></td>';
		echo '<td class="even" align="center"><a href="elements.php?title='.$title.'&op=delete&amp;ele_id='.$id.'">'._DELETE.'</a></td>';
		echo '</tr>';
	}
	
	$submit = new XoopsFormButton('', 'submit', _AM_REORD, 'submit');
	echo '
		<tr>
			<td class="foot" colspan="3"></td>
			<td class="foot" colspan="2" align="center">'.$submit->render().'</td>
			<td class="foot" colspan="3"></td>
		</tr>
	</table>
	';
	$hidden_op = new XoopsFormHidden('op', 'save');
	echo $hidden_op->render();
	echo '</form>';
}else{
        xoops_cp_header();
	extract($_POST);
	$error = '';
	foreach( $ele_id as $id ){
		$element =& $formulize_mgr->get($id);
		$req = !empty($ele_req[$id]) ? 1 : 0;
		$element->setVar('ele_req', $req);
		$order = !empty($ele_order[$id]) ? intval($ele_order[$id]) : 0;
		$element->setVar('ele_order', $order);
		$display = !empty($ele_display[$id]) ? 1 : 0;
		$element->setVar('ele_display', $display);
		$type = $element->getVar('ele_type');
		$value = $element->getVar('ele_value');
		if ($type == 'areamodif') $ele_value = $element->getVar('ele_value');
		$ele_value[0] = eregi_replace("'", "`", $ele_value[0]);
		$ele_value[0] = stripslashes($ele_value[0]);
		switch($type){
			case 'text':
				$value[2] = $ele_value[$id];
			break;
			case 'textarea':
				$value[0] = $ele_value[$id];
			break;
			case 'select':
				$new_vars = array();
				$opt_count = 1;
				if( is_array($ele_value[$id]) ){
					while( $j = each($value[2]) ){
						if( in_array($opt_count, $ele_value[$id]) ){
							$new_vars[$j['key']] = 1;
						}else{
							$new_vars[$j['key']] = 0;
						}
					$opt_count++;
					}
				}else{
					if( count($value[2]) > 1 ){
						while( $j = each($value[2]) ){
							if( $opt_count == $ele_value[$id] ){
								$new_vars[$j['key']] = 1;
							}else{
								$new_vars[$j['key']] = 0;
							}
						$opt_count++;
						}
					}else{
						while( $j = each($value[2]) ){
							if( !empty($ele_value[$id]) ){
								$new_vars = array($j['key']=>1);
							}else{
								$new_vars = array($j['key']=>0);
							}
						}
					}
				}
				
				$value[2] = $new_vars;
			break;
			case 'checkbox':
// 				$myts =& MyTextSanitizer::getInstance();
				$new_vars = array();
				$opt_count = 1;
				if( is_array($ele_value[$id]) ){
					while( $j = each($value) ){
						if( in_array($opt_count, $ele_value[$id]) ){
							$new_vars[$j['key']] = 1;
						}else{
							$new_vars[$j['key']] = 0;
						}
					$opt_count++;
					}
				}else{
					if( count($value) > 1 ){
						while( $j = each($value) ){
							$new_vars[$j['key']] = 0;
						}
					}else{
						while( $j = each($value) ){
							if( !empty($ele_value[$id]) ){
								$new_vars = array($j['key']=>1);
							}else{
								$new_vars = array($j['key']=>0);
							}
						}
					}
				}
				$value = $new_vars;
			break;
			case 'radio':
			case 'yn':
				$new_vars = array();
				$i = 1;
				while( $j = each($value) ){
					if( $ele_value[$id] == $i ){
						$new_vars[$j['key']] = 1;
					}else{
						$new_vars[$j['key']] = 0;
					}
					$i++;
				}
				$value = $new_vars;
			break;
			//Marie le 20/04/04
			case 'date':
				$value[0] = $ele_value[$id];
			break; 
			case 'areamodif':
				$value[0] = $ele_value[0];
			break;
			case 'sep':
				$value[2] = $ele_value[$id];
			break;
			case 'upload':
				$value[0] = $ele_value[$id];
				$value[1] = $ele_value[$id+1];
				$value[2] = $ele_value[$id+2];
			break;
			default:
			break;
		}
		$element->setVar('ele_value', $value);
		$element->setVar('id_form', $id_form);
		if( !$formulize_mgr->insert($element) ){
			$error .= $element->getHtmlErrors();
		}
	}
	if( empty($error) ){
		redirect_header("index.php?title=$title", 0, _AM_DBUPDATED);
	}else{
		xoops_cp_header();
		echo error;
	}
}

	echo '<center><a href="../index.php?title='.$title.'" target="_blank">Afficher le formulize <br><img src="../images/kdict.png"></a></center>';

	//echo '<br><br>lien a insérer : &lt;a href&nbsp;="'.XOOPS_URL.'/modules/formulize/index.php?title='.$title.'">'.$title.'&lt;/a><br><br>';   


include 'footer.php';
xoops_cp_footer();
?>