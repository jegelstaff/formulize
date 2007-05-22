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

if(!isset($_POST['title'])){
	$title = isset ($_GET['title']) ? $_GET['title'] : '';
}else {
	$title = $_POST['title'];
}
if(!isset($_POST['ele_id'])){
	$ele_id = isset ($_GET['ele_id']) ? $_GET['ele_id'] : '';
}else {
	$ele_id = $_POST['ele_id'];
}
/* // commented due to title now being identical with id_form
	$sql=sprintf("SELECT id_form FROM ".$xoopsDB->prefix("formulize_id")." WHERE desc_form='%s'",$title);
	$res = mysql_query ( $sql ) or die('Erreur SQL !<br>'.$requete.'<br>'.mysql_error());

if ( $res ) {
  while ( $row = mysql_fetch_row ( $res ) ) {
    $id_form = $row[0];
  }
}
*/

$id_form = $title;

if( !empty($_POST) ){
	foreach( $_POST as $k => $v ){
		${$k} = $v;
	}
}elseif( !empty($_GET) ){
	foreach( $_GET as $k => $v ){
		${$k} = $v;
	}
}
if(( $_POST['submit'] == _AM_ELE_ADD_OPT_SUBMIT && intval($_POST['addopt']) > 0 ) OR isset($_POST['subformrefresh'])) {
	$op = 'edit';
}



$ele_id = !empty($ele_id) ? intval($ele_id) : 0;
$myts =& MyTextSanitizer::getInstance();

switch($op){
	// convert option added for textboxes July 1 2006, by JWE.  Happy Canada Day.
	case 'convert':
		$element =& $formulize_mgr->get($ele_id);
		$ele_type = $element->getVar('ele_type');
		$new_ele_value = array();
		if($ele_type == "text") { // converting to textarea
			$ele_value = $element->getVar('ele_value');
			$new_ele_value[0] = $ele_value[2]; // default value
			$new_ele_value[1] = $xoopsModuleConfig['ta_rows'];
			$new_ele_value[2] = $ele_value[0]; // width become cols
			$new_ele_value[3] = $ele_value[4]; // preserve any association that is going on
			$element->setVar('ele_value', $new_ele_value);
			$element->setVar('ele_type', "textarea");
			if( !$formulize_mgr->insert($element, true) ){ // true causes a forced insert (necessary when there is no POST)
				xoops_cp_header();
				echo $element->getHtmlErrors();
			} else {
				redirect_header("index.php?title=$title", 3, _AM_ELE_CONVERTED_TO_TEXTAREA);
			}
		} elseif($ele_type=="textarea") {
			$ele_value = $element->getVar('ele_value');
			$new_ele_value[0] = $ele_value[2]; // cols become width
			$new_ele_value[1] = $xoopsModuleConfig['t_max'];
			$new_ele_value[2] = $ele_value[0]; // default value
			$new_ele_value[3] = 0; // allow anything (do not restrict to just numbers)
			$new_ele_value[4] = $ele_value[3]; // preserve any association that is going on
			$element->setVar('ele_value', $new_ele_value);
			$element->setVar('ele_type', "text");
			if( !$formulize_mgr->insert($element, true) ){ // true causes a forced insert (necessary when there is no POST)
				xoops_cp_header();
				echo $element->getHtmlErrors();
			} else {
				redirect_header("index.php?title=$title", 3, _AM_ELE_CONVERTED_TO_TEXTBOX);
			}
		} else {
			redirect_header("index.php?title=$title", 3, _AM_ELE_CANNOT_CONVERT);
		}
		break;
	case 'edit':
		xoops_cp_header();
		if( !empty($ele_id) ){
			$element =& $formulize_mgr->get($ele_id);
			$ele_type = $element->getVar('ele_type');
			$form_title = $clone ? _AM_ELE_CREATE : sprintf(_AM_ELE_EDIT, $element->getVar('ele_caption'));
			$editing = $clone ? 0 : 1;
		}else{
			$editing = 0;
			$element =& $formulize_mgr->create();
			$form_title = _AM_ELE_CREATE;
		}
		$form = new XoopsThemeForm($form_title, 'form_ele', 'elements.php?title='.$title.'');
		if( empty($addopt) ){
			$nb_fichier = 0;
			// no longer make cloned captions have the word copy at the end, since we add it when saving if the caption is not unique
			// $ele_caption = $clone ? sprintf(_AM_COPIED, $element->getVar('ele_caption', 'f')) : $element->getVar('ele_caption', 'f');
			$ele_caption = $element->getVar('ele_caption', 'f');
			if ($ele_type=='sep' && substr(0, 7, $ele_caption)=='{SEPAR}') { 
				$ele_caption = new XoopsFormText(_AM_ELE_CAPTION, 'ele_caption', 50, 255, '{SEPAR}'.$ele_caption); }
			else { $ele_caption = new XoopsFormText(_AM_ELE_CAPTION, 'ele_caption', 50, 255, $ele_caption); }
			$value = $element->getVar('ele_value', 'f');
			$ele_colhead_default = $element->getVar('ele_colhead', 'f');
			$ele_desc_default = $element->getVar('ele_desc', 'f');
		}else{
			$ele_caption = $myts->makeTboxData4PreviewInForm($ele_caption);
			// if ($addopt==1) {$ele_caption = '<h5>'.$ele_caption.'</h5>';} // jwe 01/05/05 -- deemed a bug
			if ($ele_type=='sep') { 
				$ele_caption = new XoopsFormText(_AM_ELE_CAPTION, 'ele_caption', 50, 255, '{SEPAR}'.$ele_caption); }
			else { $ele_caption = new XoopsFormText(_AM_ELE_CAPTION, 'ele_caption', 50, 255, $ele_caption); }
			$ele_colhead_default = $ele_colhead;
			$ele_desc_default = get_magic_quotes_gpc() ? stripslashes($ele_desc) : $ele_desc;
			unset($ele_colhead);
			unset($ele_desc);
		}
/*
// now altering sent captions on the saving side.  More reliable anyway, and saves sending every single caption in the form to the client.
		// added javascript validation of uniqueness of caption to the caption element -- jwe 7/27/04
		// 1. gather existing captions for this form
		// 2. write function to the page to check the captions
		// 3. add function call to caption element
		
		// gather captions...
		array ($captionarray);
		$captionquery = "SELECT ele_caption FROM ".$xoopsDB->prefix("formulize")." WHERE id_form=$id_form";
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
*/	


	//	$captionmatchalert = ;
/*		
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
// checking of captions for uniqueness is no longer necessary, since captions are altered on saving side
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

*/


		$form->addElement($ele_caption, 1);

		if($ele_type != "subform" AND $ele_type != "grid") {
			// column heading added June 25 2006 -- jwe
			$ele_colhead = new XoopsFormText(_AM_ELE_COLHEAD, 'ele_colhead', 50, 255, $ele_colhead_default);
			$ele_colhead->setDescription(_AM_ELE_COLHEAD_HELP);
			$form->addElement($ele_colhead);
		

			// descriptive text added June 6 2006 -- jwe
			if($ele_type != "ib" AND $ele_type != "derived") {
				$ele_desc = new XoopsFormTextArea(_AM_ELE_DESC, 'ele_desc', $ele_desc_default, 5, 35);
				$ele_desc->setDescription(_AM_ELE_DESC_HELP);
				$form->addElement($ele_desc);
			}
		}

		switch($ele_type){
			case 'subform':
				include 'ele_subform.php';
			break;
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
			case 'grid':
				include 'ele_grid.php';
			break;
			case 'upload':
				include 'ele_upload.php';
			break;
			// "derived columns" added March 27 2007
			case 'derived':
				include 'ele_derived.php';
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
		$ele_display->setDescription(_AM_FORM_DISPLAY_EXTRA);
	

	    $fs_member_handler =& xoops_gethandler('member');
	    $fs_xoops_groups =& $fs_member_handler->getGroups();

        $ele_display->addOption("all", _AM_FORM_DISPLAY_ALLGROUPS);     
        $ele_display->addOption("none", _AM_FORM_DISPLAY_NOGROUPS);     

	    $fs_count = count($fs_xoops_groups);
	    for($i = 0; $i < $fs_count; $i++) 
	    {
	        $ele_display->addOption($fs_xoops_groups[$i]->getVar('groupid'), $fs_xoops_groups[$i]->getVar('name'));     
	    }
		$form->addElement($ele_display);
		// replaced - end - August 18 2005 - jpc


		if($ele_type == "radio" OR $ele_type == "text" OR $ele_type == "textarea") {
			// added by jwe Nov 7 2005, a checkbox to indicate if the element should be included as a hidden element, even when the user does not have permission to view (ie: it is hidden by the display option above)
			$fhide = !empty($ele_id) ? $element->getVar('ele_forcehidden') : 0;
			$forcehidden = new XoopsFormCheckBox(_AM_FORM_FORCEHIDDEN, "fhide", $fhide);
			$forcehidden->addOption(1, ' ');
			$forcehidden->setDescription(_AM_FORM_FORCEHIDDEN_DESC);
			$form->addElement($forcehidden);
		}
		if($ele_type != "subform" AND $ele_type != "grid") {
			// added private option July 15 2006, jwe
			$priv = !empty($ele_id) ? $element->getVar('ele_private') : 0;
			$private = new XoopsFormCheckBox(_AM_FORM_PRIVATE, "private", $priv);
			$private->addOption(1, ' ');
			$private->setDescription(_AM_FORM_PRIVATE_DESC);
			$form->addElement($private);
		}
		
		$highorder = formulize_getElementHighOrder($id_form);
		
		$order = !empty($ele_id) ? $element->getVar('ele_order') : $highorder;
		$order = $clone ? $highorder : $order;
		$ele_order = new XoopsFormText(_AM_ELE_ORDER, 'ele_order', 4, 4, $order);

		// need to add hidden element to indicate if the order has been modified (detectable by javascript)
		// then listen for that flag, and if order has not been modified, check again when saving to see if this is in fact the right order number, since multiple clicks on the clone link at the same time will result in multiple windows with the same order number in the box, and we don't want to have to manually alter those orders after saving -- re: OACAS HR survey project, January 22, 2007

		$ele_order->setExtra("onchange='javascript:window.document.form_ele.ele_order_changed.value=1;'");
		$ele_order_changed = new XoopsFormHidden('ele_order_changed', $editing);

		$form->addElement($ele_order);
		$form->addElement($ele_order_changed);
		
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
			$ocq = "SELECT ele_caption FROM " . $xoopsDB->prefix("formulize") . " WHERE ele_id='$ele_id'";
			$res_ocq = $xoopsDB->query($ocq);
			$array_ocq = $xoopsDB->fetchArray($res_ocq);
			$original_caption = $array_ocq['ele_caption'];
		}else{
			$element =& $formulize_mgr->create();
		}

		$ele_caption = formulize_verifyUniqueCaption($ele_caption, $ele_id, $id_form);

		$ele_caption = get_magic_quotes_gpc() ? stripslashes($ele_caption) : $ele_caption;
		$element->setVar('ele_caption', $ele_caption);
		$ele_delim = $ele_delim=='custom' ? $ele_delim_custom : $ele_delim;
		$element->setVar('ele_delim', $ele_delim); // only set for radio and checkbox, but cannot be put into ele_value because ele_value is not a multidimensional array for those elements, so must be treated as a separate db field for now
		$element->setVar('ele_desc', $ele_desc);
		$element->setVar('ele_colhead', $ele_colhead);
		$req = !empty($ele_req) ? 1 : 0;
		$element->setVar('ele_req', $req);
		$order = empty($ele_order) ? 0 : intval($ele_order);
		$order = $ele_order_changed ? $order : formulize_getElementHighOrder($id_form); // if order was not modified from it's original state, then make sure we're writing the current highest order to the DB
		$element->setVar('ele_order', $order);

		// grab the forcehidden setting -- added Nov 7 2005
		if(!$fhide) { $fhide_checked = 0; } else { $fhide_checked = $fhide; }
		$element->setVar('ele_forcehidden', $fhide_checked);

		// grab the private setting -- added July 15 2006
		if(!$private) { $private_checked = 0; } else { $private_checked = $private; }
		$element->setVar('ele_private', $private_checked);

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
				// formlink option added to textboxes June 20 2006 -- jwe
				if($_POST['formlink'] != "none") {
					$value[] = $_POST['formlink'];
				} else {
					$value[] = "";
				}

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
				// formlink option added to textboxes June 20 2006 -- jwe
				if($_POST['formlink'] != "none") {
					$value[] = $_POST['formlink'];
				} else {
					$value[] = "";
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
				if($ele_value != "YYYY-mm-dd" AND $ele_value != "") { 
					$ele_value = date("Y-m-d", strtotime($ele_value)); 
				} else {
					$ele_value = "";
				}
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
				$value[3] = implode(",", $_POST['formlink_scope']); // added august 30 2006
				$value[4] = $_POST['linkscopelimit'];
				// check to see if a link to another form was made and if so put in a marker that will be picked up at render time and handled accordingly...  -- jwe 7/29/04
				if($_POST['formlink'] != "none")
				{
					// $value[2] = stripslashes($_POST['formlink']);
					// now receiving an ele_id due to the effects of xlanguage, so get the real caption out of the DB
					$sql_link = "SELECT ele_caption, id_form FROM " . $xoopsDB->prefix("formulize") . " WHERE ele_id = " . intval($_POST['formlink']);
					$res_link = $xoopsDB->query($sql_link);
					$array_link = $xoopsDB->fetchArray($res_link);
					$value[2] = $array_link['id_form'] . "#*=:*" . $array_link['ele_caption'];
				} 
				else
				{
				$v2 = array();
				$multi_flag = 1;
				while( $v = each($ele_value[2]) ){
					if( $v['value'] !== "" ){
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
			// subform added September 4 2006
			case 'subform':
				$value[0] = $_POST['subform'];
				$value[1] = $_POST['subformelements'] ? implode(",",$_POST['subformelements']) : "";
			break;
			// grid added January 19 2007
			case 'grid':
				foreach($ele_value as $key=>$val) {
					$value[$key] = get_magic_quotes_gpc() ? stripslashes($val) : $val;
				}
			break;
			// derived added March 27 2007
			case 'derived':
				$ele_value[0] = get_magic_quotes_gpc() ? stripslashes($ele_value[0]) : $ele_value[0];
				$value[0] = $ele_value[0];
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
			if($original_caption) {
				// add code here to rewrite existing captions in form_form table so that changes to the captions don't orphan all existing data! -- jwe 09/03/05
				// get the current caption so we know what to replace
				$ele_caption = stripslashes($ele_caption);
				$ele_caption = eregi_replace ("&#039;", "`", $ele_caption);
				$ele_caption = eregi_replace ("'", "`", $ele_caption);
				$ele_caption = eregi_replace ("&quot;", "`", $ele_caption);
				$original_caption = eregi_replace ("&#039;", "`", $original_caption);
				$original_caption = eregi_replace ("'", "`", $original_caption);
				$original_caption = eregi_replace ("&quot;", "`", $original_caption);
				$updateq = "UPDATE " . $xoopsDB->prefix("formulize_form") . " SET ele_caption='" . mysql_real_escape_string($ele_caption) . "' WHERE id_form = '$id_form' AND ele_caption='" . mysql_real_escape_string($original_caption) . "'";
				if($ele_caption != $original_caption) {
					if(!$res = $xoopsDB->query($updateq)) {
						print "Error:  update of captions in form $id_form failed.";
					}
				}
				//end of added code
			}
			//redirect_header("index.php?title=$title", 1, _AM_DBUPDATED);
			header("Location: " . XOOPS_URL . "/modules/formulize/admin/index.php?title=$title");
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

function formulize_getElementHighOrder($id_form) {
	global $xoopsDB;
	// added by jwe 01/06/05 -- get the current highest order value for the form, and add up to 5 to it to reach the nearest mod 5 value
	$highorderq = "SELECT MAX(ele_order) FROM " . $xoopsDB->prefix("formulize") . " WHERE id_form=$id_form";
	$reshighorderq = $xoopsDB->query($highorderq);
	$rowhighorderq = $xoopsDB->fetchRow($reshighorderq);
	$highorder = $rowhighorderq[0]+1;
	while($highorder % 5 != 0)
	{
		$highorder++;
	}
	return $highorder;
}

function formulize_verifyUniqueCaption($ele_caption, $ele_id, $id_form) {
	// check the form to see if elements of a different ID have the same caption, and if so, append "copy" to the end of the caption
	global $xoopsDB;
	$sql = "SELECT COUNT(*) FROM " . $xoopsDB->prefix("formulize") . " WHERE id_form=" . intval($id_form) . " AND ele_id != " . intval($ele_id) . " AND ele_caption = \"" . mysql_real_escape_string($ele_caption) . "\"";
	if(!$res = $xoopsDB->query($sql)) {
		print "Error: could not verify uniqueness of caption";
		return $ele_caption;
	}
	$row = $xoopsDB->fetchRow($res);
	$count = $row[0];
	if($count > 0) {
		return sprintf(_AM_COPIED, $ele_caption);
	} else {
		return $ele_caption;
	}		

}

?>