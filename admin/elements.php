<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2004 Freeform Solutions                  ##
##                Portions copyright (c) 2003 NS Tai (aka tuff)              ##
##                       <http://www.brandycoke.com/>                        ##
###############################################################################
##                    XOOPS - PHP Content Management System                  ##
##                       Copyright (c) 2000 XOOPS.org                        ##
##                          <http://www.xoops.org/>                          ##
###############################################################################
##  This program is free software; you can redistribute it and/or modify     ##
##  it under the terms of the GNU General Public License as published by     ##
##  the Free Software Foundation; either version 2 of the License, or        ##
##  (at your option) any later version.                                      ##
##                                                                           ##
##  You may not change or alter any portion of this comment or credits       ##
##  of supporting developers from this source code or any supporting         ##
##  source code which is considered copyrighted (c) material of the          ##
##  original comment or credit authors.                                      ##
##                                                                           ##
##  This program is distributed in the hope that it will be useful,          ##
##  but WITHOUT ANY WARRANTY; without even the implied warranty of           ##
##  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            ##
##  GNU General Public License for more details.                             ##
##                                                                           ##
##  You should have received a copy of the GNU General Public License        ##
##  along with this program; if not, write to the Free Software              ##
##  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA ##
###############################################################################
##  Author of this file: Freeform Solutions and NS Tai (aka tuff) and others ##
##  URL: http://www.brandycoke.com/                                          ##
##  Project: Formulize                                                       ##
###############################################################################

include_once("admin_header.php");

if(!isset($HTTP_POST_VARS['title'])){
	$title = isset ($HTTP_GET_VARS['title']) ? $HTTP_GET_VARS['title'] : '';
}else {
	$title = $HTTP_POST_VARS['title'];
}
if(!isset($HTTP_POST_VARS['ele_id'])){
	$ele_id = isset ($HTTP_GET_VARS['ele_id']) ? $HTTP_GET_VARS['ele_id'] : '';
}else {
	$ele_id = $HTTP_POST_VARS['ele_id'];
}

	$sql=sprintf("SELECT id_form FROM ".$xoopsDB->prefix("form_id")." WHERE desc_form='%s'",$title);
	$res = mysql_query ( $sql ) or die('Erreur SQL !<br>'.$requete.'<br>'.mysql_error());

if ( $res ) {
  while ( $row = mysql_fetch_row ( $res ) ) {
    $id_form = $row[0];
  }
}

if( !empty($_POST) ){
	foreach( $_POST as $k => $v ){
		${$k} = $v;
	}
}elseif( !empty($_GET) ){
	foreach( $_GET as $k => $v ){
		${$k} = $v;
	}
}
if( $_POST['submit'] == _AM_ELE_ADD_OPT_SUBMIT && intval($_POST['addopt']) > 0 ){
	$op = 'edit';
}



$ele_id = !empty($ele_id) ? intval($ele_id) : 0;
$myts =& MyTextSanitizer::getInstance();

switch($op){
	case 'edit':
		xoops_cp_header();
		if( !empty($ele_id) ){
			$element =& $formulize_mgr->get($ele_id);
			$ele_type = $element->getVar('ele_type');
			$form_title = $clone ? _AM_ELE_CREATE : sprintf(_AM_ELE_EDIT, $element->getVar('ele_caption'));
		}else{
			$element =& $formulize_mgr->create();
			$form_title = _AM_ELE_CREATE;
		}
		$form = new XoopsThemeForm($form_title, 'form_ele', 'elements.php?title='.$title.'');
		if( empty($addopt) ){
			$nb_fichier = 0;
			$ele_caption = $clone ? sprintf(_AM_COPIED, $element->getVar('ele_caption', 'f')) : $element->getVar('ele_caption', 'f');
			if ($ele_type=='sep' && substr(0, 7, $ele_caption)=='{SEPAR}') { 
				$ele_caption = new XoopsFormText(_AM_ELE_CAPTION, 'ele_caption', 50, 255, '{SEPAR}'.$ele_caption); }
			else { $ele_caption = new XoopsFormText(_AM_ELE_CAPTION, 'ele_caption', 50, 255, $ele_caption); }
			$value = $element->getVar('ele_value', 'f');
		}else{
			$ele_caption = $myts->makeTboxData4PreviewInForm($ele_caption);
			// if ($addopt==1) {$ele_caption = '<h5>'.$ele_caption.'</h5>';} // jwe 01/05/05 -- deemed a bug
			if ($ele_type=='sep') { 
				$ele_caption = new XoopsFormText(_AM_ELE_CAPTION, 'ele_caption', 50, 255, '{SEPAR}'.$ele_caption); }
			else { $ele_caption = new XoopsFormText(_AM_ELE_CAPTION, 'ele_caption', 50, 255, $ele_caption); }
		}

		// added javascript validation of uniqueness of caption to the caption element -- jwe 7/27/04
		// 1. gather existing captions for this form
		// 2. write function to the page to check the captions
		// 3. add function call to caption element
		
		// gather captions...
		array ($captionarray);
		$captionquery = "SELECT ele_caption FROM ".$xoopsDB->prefix("form")." WHERE id_form=$id_form";
		// note that we exclude the caption of the current element if there is one (so you're not forced to change the caption just cause you're editing).
		if($ele_id)
		{
			$captionquery = $captionquery . " AND ele_id != $ele_id";
		}
		$resultCaptionquery = mysql_query($captionquery);
		if($resultCaptionquery)
		{
			$maxcaps = 0;
			while ($resrow = mysql_fetch_row($resultCaptionquery))
			{
				$captionarray[$maxcaps] = $resrow[0];
				$maxcaps++;
			}
		}
		else // if query failed (no captions yet?)
		{
			$captionarray[0] = "";
		}

		// use PHP to format the array contents for javascript
		for($caploop = 0;$caploop < $maxcaps;$caploop++)
		{
			if($caploop+1 == $maxcaps)
			{
				$javacaptionlist = $javacaptionlist . "\"$captionarray[$caploop]\"";
			}
			else
			{
				$javacaptionlist = $javacaptionlist . "\"$captionarray[$caploop]\", ";
			}
		}
	


	//	$captionmatchalert = ;
		
		// javascript function that checks the caption -- added < > 9/02/04
		print "<script type='text/javascript'>\n";
		print "<!--//\n";
		print "	function checkUnique(caps) {\n";
		print "		var quotes=caps.indexOf('\"');\n";
		print "		if(quotes > -1)\n";
		print "		{\n";
		print "			alert(\"" . _formulize_CAPTION_QUOTES . "\")\n";
		print "			var replacement = '';\n";
		print "			var replacearray=caps.split('\"');\n";
		print "			for(var i = 0;i<replacearray.length;i++)\n";
		print "			{\n";
		print "				replacement = replacement + replacearray[i];\n";
		print "			}\n";
		print "			document.form_ele.ele_caption.value = replacement;\n";
		print "		}\n";		
		print "		var slash=caps.indexOf('\\\\');\n";
		print "		if(replacement) {caps=replacement;}\n";		
		print "		var replacement = '';\n";
		print "		if(slash > -1) {\n";
		print "			alert(\"" . _formulize_CAPTION_SLASH . "\")\n";
		print "			var replacearray=caps.split('\\\\');\n";
		print "			for(var i = 0;i<replacearray.length;i++)\n";
		print "			{\n";
		print "				replacement = replacement + replacearray[i];\n";
		print "			}\n";
		print "			document.form_ele.ele_caption.value = replacement;\n";
		print "		}\n";
		print "		var slash=caps.indexOf('>');\n";
		print "		if(replacement) {caps=replacement;}\n";		
		print "		var replacement = '';\n";
		print "		if(slash > -1) {\n";
		print "			alert(\"" . _formulize_CAPTION_GT . "\")\n";
		print "			var replacearray=caps.split('>');\n";
		print "			for(var i = 0;i<replacearray.length;i++)\n";
		print "			{\n";
		print "				replacement = replacement + replacearray[i];\n";
		print "			}\n";
		print "			document.form_ele.ele_caption.value = replacement;\n";
		print "		}\n";
		print "		var slash=caps.indexOf('<');\n";
		print "		if(replacement) {caps=replacement;}\n";		
		print "		var replacement = '';\n";
		print "		if(slash > -1) {\n";
		print "			alert(\"" . _formulize_CAPTION_LT . "\")\n";
		print "			var replacearray=caps.split('<');\n";
		print "			for(var i = 0;i<replacearray.length;i++)\n";
		print "			{\n";
		print "				replacement = replacement + replacearray[i];\n";
		print "			}\n";
		print "			document.form_ele.ele_caption.value = replacement;\n";
		print "		}\n";
		print "		if(replacement) {caps=replacement;}\n";		
		print "		existingcaps = new Array($javacaptionlist);\n";
		print "		for(var i = 0;i<$maxcaps;i++)\n";
		print "		{\n";
		print "			if(caps == existingcaps[i])\n";
		print "			{\n";
		print "				alert(\"" . _formulize_CAPTION_MATCH . "\")\n";
		print "				var replacement = caps + ' 2';\n";
		print "				document.form_ele.ele_caption.value = replacement;\n";
		print "				break;\n";
		print "			}\n";
		print "		}\n";
		print "	}\n";
		print "//--></script>\n\n";

		// add function call to caption element
		$ele_caption->setExtra("onChange='checkUnique(this.value)'");

		$form->addElement($ele_caption, 1);
		switch($ele_type){
			case 'text':
				include 'ele_text.php';
				$req = true;
			break;
			case 'textarea':
				include 'ele_tarea.php';
				$req = true;
			break;
			case 'areamodif':
				include 'ele_modif.php';
			break;
			case 'ib': // added June 20 2005
				include 'ele_insertbreak.php';
			break;
			case 'select':
				include 'ele_select.php';
			break;
			case 'checkbox':
				include 'ele_check.php';
			break;
			case 'radio':
				include 'ele_radio.php';
			break;
			case 'yn':
				include 'ele_yn.php';
			break;
			case 'date':
				include 'ele_date.php';
			break;
			case 'sep':
				include 'ele_sep.php';
			break;
			case 'upload':
				include 'ele_upload.php';
			break;
		}
		if( $req ){
			$ele_req = new XoopsFormCheckBox(_AM_ELE_REQ, 'ele_req', $element->getVar('ele_req'));
			$ele_req->addOption(1, ' ');
		}

		$form->addElement($ele_req);
        

		// replaced - start - August 18 2005 - jpc
		/*$display = !empty($ele_id) ? $element->getVar('ele_display') : 1;
		$ele_display = new XoopsFormCheckBox(_AM_ELE_DISPLAY, 'ele_display', $display);
		$ele_display->addOption(1, ' ');
		$form->addElement($ele_display);*/

		$display = !empty($ele_id) ? $element->getVar('ele_display') : "all";
        $display = ($display == "0") ? "none" : $display;
        $display = ($display == "1") ? "all" : $display;

        $displayIsGroupList = false;
		if(substr($display, 0, 1) == ",")
        {
	        $displayIsGroupList = true;
	        $displayGroupList = explode(",", $display);
			$ele_display = new XoopsFormSelect(_AM_ELE_DISPLAY, 'ele_display', $displayGroupList, 10, true);
        }
        else
        {
			$ele_display = new XoopsFormSelect(_AM_ELE_DISPLAY, 'ele_display', $display, 10, true);
        } 

	    $fs_member_handler =& xoops_gethandler('member');
	    $fs_xoops_groups =& $fs_member_handler->getGroups();

        $ele_display->addOption("all", "All Groups");     
        $ele_display->addOption("none", "No Groups");     

	    $fs_count = count($fs_xoops_groups);
	    for($i = 0; $i < $fs_count; $i++) 
	    {
	        $ele_display->addOption($fs_xoops_groups[$i]->getVar('groupid'), $fs_xoops_groups[$i]->getVar('name'));     
	    }
		$form->addElement($ele_display);
		// replaced - end - August 18 2005 - jpc

		
		// added by jwe 01/06/05 -- get the current highest order value for the form, and add up to 10 to it to reach the nearest mod 5 value
		$highorderq = "SELECT MAX(ele_order) FROM " . $xoopsDB->prefix("form") . " WHERE id_form=$id_form";
		$reshighorderq = $xoopsDB->query($highorderq);
		$rowhighorderq = $xoopsDB->fetchRow($reshighorderq);
		$highorder = $rowhighorderq[0]+1;
		while($highorder % 10 != 0)
		{
			$highorder++;
		}
		
		$order = !empty($ele_id) ? $element->getVar('ele_order') : $highorder;
		$ele_order = new XoopsFormText(_AM_ELE_ORDER, 'ele_order', 3, 3, $order);
		$form->addElement($ele_order);
		
		$submit = new XoopsFormButton('', 'submit', _AM_SAVE, 'submit');
		$cancel = new XoopsFormButton('', 'cancel', _CANCEL, 'button');
		// behaviour of cancel button changed to actually physically go back to the editing elements page -- jwe 01/05/05
		//$cancel->setExtra('onclick="javascript:history.go(-1);"');
		$cancelExtra = "onclick='javascript:location.href=\"../admin/index.php?title=$title\"'";
		$cancelExtra = str_replace(" ", "%20", $cancelExtra);
		$cancel->setExtra($cancelExtra);
		$tray = new XoopsFormElementTray('');
		$tray->addElement($submit);
		$tray->addElement($cancel);
		$form->addElement($tray);
		
		$hidden_op = new XoopsFormHidden('op', 'save');
		$hidden_type = new XoopsFormHidden('ele_type', $ele_type);
		$form->addElement($hidden_op);
		$form->addElement($hidden_type);
		if( !empty($ele_id) && !$clone ){
			$hidden_id = new XoopsFormHidden('ele_id', $ele_id);
			$form->addElement($hidden_id);
		}
		$form->display();

	break;
	case 'delete':
		if( empty($ele_id) ){
			redirect_header("index.php?title=$title", 0, _AM_ELE_SELECT_NONE);
		}
		if( empty($_POST['ok']) ){
			xoops_cp_header();
			xoops_confirm(array('op' => 'delete', 'ele_id' => $ele_id, 'ok' => 1), 'elements.php?title='.$title.'', _AM_ELE_CONFIRM_DELETE);
		}else{
			$element =& $formulize_mgr->get($ele_id);
			$formulize_mgr->delete($element);
			$formulize_mgr->deleteData($element); //added aug 14 2005 by jwe
			redirect_header("index.php?title=$title", 0, _AM_DBUPDATED);
		}
	break;
	case 'save':
		if( !empty($ele_id) ){
			$element =& $formulize_mgr->get($ele_id);
		}else{
			$element =& $formulize_mgr->create();
		}
		$original_caption = $element->getVar('ele_caption'); // added by jwe 09/03/05, used in new code below
		$element->setVar('ele_caption', $ele_caption);
		$req = !empty($ele_req) ? 1 : 0;
		$element->setVar('ele_req', $req);
		$order = empty($ele_order) ? 0 : intval($ele_order);
		$element->setVar('ele_order', $order);


		// replaced - start - August 18 2005 - jpc
		//$display = !empty($ele_display) ? 1 : 0;
		if($ele_display[0] == "all")
        {
			$display = 1;        
        }
        else if($ele_display[0] == "none" || $ele_display[1] == "none")
        {
			$display = 0;        
        }
        else
        {
			$display = "," . implode(",", $ele_display) . ",";
        }
		//var_dump($ele_display); echo $display; die();
		// replaced - end - August 18 2005 - jpc


		$element->setVar('ele_display', $display);
		$element->setVar('ele_type', $ele_type);
		//$element->setVar('poids',$poids);
		switch($ele_type){
			case 'text':
				$value = array();
				$value[] = !empty($ele_value[0]) ? intval($ele_value[0]) : $xoopsModuleConfig['t_width'];
				$value[] = !empty($ele_value[1]) ? intval($ele_value[1]) : $xoopsModuleConfig['t_max'];
				$value[] = $ele_value[2];
				$value[] = $ele_value[3];
			break;
			case 'textarea':
				$value = array();
				$value[] = $ele_value[0];
				if( intval($ele_value[1]) != 0 ){
					$value[] = intval($ele_value[1]);
				}else{
					$value[] = $xoopsModuleConfig['ta_rows'];
				}
				if( intval($ele_value[2]) != 0 ){
					$value[] = intval($ele_value[2]);
				}else{
					$value[] = $xoopsModuleConfig['ta_cols'];
				}
			break;
			case 'areamodif':
				$value = array();
				$value[] = $ele_value[0];
				if( intval($ele_value[1]) != 0 ){
					$value[] = intval($ele_value[1]);
				}else{
					$value[] = $xoopsModuleConfig['ta_rows'];
				}
				if( intval($ele_value[2]) != 0 ){
					$value[] = intval($ele_value[2]);
				}else{
					$value[] = $xoopsModuleConfig['ta_cols'];
				}
			break;
			case 'ib': // added June 20 2005
				$value[] = $ele_value[0];
				$value[] = $ele_value[1];
			break;
			case 'checkbox':
				$value = array();
				while( $v = each($ele_value) ){
					if( !empty($v['value']) ){
						if( $checked[$v['key']] == 1 ){
							$check = 1;
						}else{
							$check = 0;
						}
						$value[$v['value']] = $check;
					}
				}
			break;
			case 'radio':
				// added this next line to actually set checked so radio button defaults are set correctly. -- jwe 7/28/04
				$checked = $_POST['checked'];
				$value = array();
				while( $v = each($ele_value) ){
				/*	print "<br>debug:<br>v = ";
					print_r ($v);
					print "<br>v[value] =";
					print_r($v['value']);
					print "<br>v[key] =";
					print_r($v['key']);
					print "<br>checked = $checked";*/

					if( !empty($v['value']) ){
						// added if loop below to account for the similarity of checked =0 (first element) or checked is not set (no defaults)
						if(isset($checked))
						{
							if( $checked == $v['key'] ){
								$value[$v['value']] = 1;
							}else{
								$value[$v['value']] = 0;
							}
						}
						else
						{
							$value[$v['value']] = 0;
						}
					}
				}
				/*print "<br><br>Final output: ";
				print_r ($value);*/
			break;
			// this case altered to properly accept cases with no default value.
			case 'yn':
				$value = array();
				if( $ele_value == '_NO' ){
					$value = array('_YES'=>0,'_NO'=>1);
				}elseif ( $ele_value == '_YES' ){
					$value = array('_YES'=>1,'_NO'=>0);
				}else {
					$value = array('_YES'=>0,'_NO'=>0);
				}
			break;
			case 'date':
				$value = array();
				$value[] = $ele_value;
			break;
			case 'sep':
				$value = array();
				$value[] = $ele_value[0];
				if( intval($ele_value[1]) != 0 ){
					$value[] = intval($ele_value[1]);
				}else{
					$value[] = $xoopsModuleConfig['ta_rows'];
				}
				if( intval($ele_value[2]) != 0 ){
					$value[] = intval($ele_value[2]);
				}else{
					$value[] = $xoopsModuleConfig['ta_cols'];
				}
				if ($option[0]) {$value[0] = '<center>'.$value[0].'</center>';}
				if ($option[1]) {$value[0] = '<u>'.$value[0].'</u>';}
				if ($option[2]) {$value[0] = '<I>'.$value[0].'</I>';}
				// $value[0] = '<h5>'.$value[0].'</h5>'; // jwe 01/05/05 -- deemed a bug
				
				$value[0] = '<font color='.$couleur.'>'.$value[0].'</font>';
				
			break;
			case 'select':
				$value = array();
				$value[0] = $ele_value[0]>1 ? intval($ele_value[0]) : 1;
				$value[1] = !empty($ele_value[1]) ? 1 : 0;
				// check to see if a link to another form was made and if so put in a marker that will be picked up at render time and handled accordingly...  -- jwe 7/29/04
				if($_POST['formlink'] != "none")
				{
					$value[2] = $_POST['formlink'];
				} 
				else
				{
				$v2 = array();
				$multi_flag = 1;
				while( $v = each($ele_value[2]) ){
					if( !empty($v['value']) ){
						if( $value[1] == 1 || $multi_flag ){
							if( $checked[$v['key']] == 1 ){
								$check = 1;
								$multi_flag = 0;
							}else{
								$check = 0;
							}
						}else{
							$check = 0;
						}
					$v2[$v['value']] = $check;
					}
				}
				$value[2] = $v2;
				} // end of formlink check -- jwe
			break;
			case 'upload': 
				$value = array();
				$v2 = array();
				$value[] = $ele_value[0];
				$value[] = $ele_value[1];
				while( $v = each($ele_value[2]) ){
					if( !empty($v) ) $v2[] = $v;
				}
				$value[] = $v2;
			break;
		}
		$element->setVar('ele_value', $value);
		$element->setVar('id_form', $id_form);
        
		if( !$formulize_mgr->insert($element) ){
			xoops_cp_header();
			echo $element->getHtmlErrors();
		}else{
			// add code here to rewrite existing captions in form_form table so that changes to the captions don't orphan all existing data! -- jwe 09/03/05
			// get the current caption so we know what to replace
			$ele_caption = stripslashes($ele_caption);
			$ele_caption = eregi_replace ("&#039;", "`", $ele_caption);
			$ele_caption = eregi_replace ("'", "`", $ele_caption);
			$ele_caption = eregi_replace ("&quot;", "`", $ele_caption);
			$original_caption = eregi_replace ("&#039;", "`", $original_caption);
			$original_caption = eregi_replace ("'", "`", $original_caption);
			$original_caption = eregi_replace ("&quot;", "`", $original_caption);
			$updateq = "UPDATE " . $xoopsDB->prefix("form_form") . " SET ele_caption='$ele_caption' WHERE id_form = '$id_form' AND ele_caption='$original_caption'";
			if($ele_caption != $original_caption) {
				if(!$res = $xoopsDB->query($updateq)) {
					print "Error:  update of captions in form $id_form failed.";
				}
			}
			// end of added code
			redirect_header("index.php?title=$title", 1, _AM_DBUPDATED);
		}
	break;
/*	default:
		xoops_cp_header();
	//	OpenTable();
		echo "<h4>"._AM_ELE_CREATE."</h4>
		<ul>
		<li><a href='elements.php?op=edit&amp;ele_type=text'>"._AM_ELE_TEXT."</a></li>
		<li><a href='elements.php?op=edit&amp;ele_type=textarea'>"._AM_ELE_TAREA."</a></li>
		<li><a href='elements.php?op=edit&amp;ele_type=select'>"._AM_ELE_SELECT."</a></li>
		<li><a href='elements.php?op=edit&amp;ele_type=checkbox'>"._AM_ELE_CHECK."</a></li>
		<li><a href='elements.php?op=edit&amp;ele_type=radio'>"._AM_ELE_RADIO."</a></li>
		<li><a href='elements.php?op=edit&amp;ele_type=yn'>"._AM_ELE_YN."</a></li>
		</ul>"
		;
	//	CloseTable();
	break;*/
}
include 'footer.php';
xoops_cp_footer();


function addOption($id1, $id2, $text, $type='check', $checked=null){
	$d = new XoopsFormText('', $id1, 40, 255, $text);
	if( $type == 'check' ){
		$c = new XoopsFormCheckBox('', $id2, $checked);
		$c->addOption(1, ' ');
	}
	else{
		$c = new XoopsFormRadio('', 'checked', $checked);
		$c->addOption($id2, ' ');
	}
	$t = new XoopsFormElementTray('');
	$t->addElement($c);
	$t->addElement($d);
	return $t;
}

function addOptionsTray(){
	$t = new XoopsFormText('', 'addopt', 3, 2);
	$l = new XoopsFormLabel('', sprintf(_AM_ELE_ADD_OPT, $t->render()));
	$b = new XoopsFormButton('', 'submit', _AM_ELE_ADD_OPT_SUBMIT, 'submit');
	$r = new XoopsFormElementTray('');
	$r->addElement($l);
	$r->addElement($b);
	return $r;
}


?>