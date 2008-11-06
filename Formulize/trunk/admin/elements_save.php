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

// code snippet that handles saving of data...called in the normal save operation in admin/elements.php, but also invoked in certain cases when the page reloads but the user should not have left the editing screen yet

$databaseElement = ($ele_type == "derived" OR $ele_type == "areamodif" OR $ele_type == "ib" OR $ele_type == "sep" OR $ele_type == "subform" OR $ele_type == "grid") ? false : true;

if( !empty($ele_id) AND $clone == 0){
      
      $element = $formulize_mgr->get($ele_id);

			$ocq = "SELECT ele_caption, ele_handle FROM " . $xoopsDB->prefix("formulize") . " WHERE ele_id='$ele_id'";
			$res_ocq = $xoopsDB->query($ocq);
			$array_ocq = $xoopsDB->fetchArray($res_ocq);
			//$original_caption = $array_ocq['ele_caption'];
      $newFieldNeeded = false;
			$original_handle = $array_ocq['ele_handle'];
			
		}else{
			unset($ele_id); // just in case we're cloning something
			$element =& $formulize_mgr->create();
      $newFieldNeeded = $databaseElement ? true : false; // derived fields and others don't exist in the database
		}

    $ele_caption = get_magic_quotes_gpc() ? stripslashes($ele_caption) : $ele_caption;
		//$ele_caption = formulize_verifyUniqueCaption($ele_caption, $ele_id, $id_form);
		
		$element->setVar('ele_caption', $ele_caption);
		$ele_delim = $ele_delim=='custom' ? $ele_delim_custom : $ele_delim;
		$element->setVar('ele_delim', $ele_delim); // only set for radio and checkbox, but cannot be put into ele_value because ele_value is not a multidimensional array for those elements, so must be treated as a separate db field for now
		$element->setVar('ele_desc', $ele_desc);
		$element->setVar('ele_colhead', $ele_colhead);
		// check that handle is unique
    $ele_handle = str_replace(" ", "_", $ele_handle);
    $ele_handle = str_replace("'", "", $ele_handle);
    $ele_handle = str_replace("\"", "", $ele_handle);
		if($ele_handle) {
			$form_handler =& xoops_getmodulehandler('forms');
			while(!$uniqueCheck = $form_handler->isHandleUnique($ele_handle, $ele_id)) {
				$ele_handle = $ele_handle . "_copy";
			}			
		}
		$element->setVar('ele_handle', $ele_handle);
		$req = !empty($ele_req) ? 1 : 0;
		$element->setVar('ele_req', $req);
		$order = empty($ele_order) ? 0 : intval($ele_order);
		$order = $ele_order_changed ? $order : formulize_getElementHighOrder($id_form); // if order was not modified from it's original state, then make sure we're writing the current highest order to the DB
		$element->setVar('ele_order', $order);
		$uitext = "";

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
                
                if($ele_disabled[0] == "all"){
			$disabled = 1;        
                } else if($ele_disabled[0] == "none" || $ele_disabled[1] == "none"){
			$disabled = 0;        
                } else {
			$disabled = "," . implode(",", $ele_disabled) . ",";
                }
                $element->setVar('ele_disabled', $disabled);
                
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

			  $value[] = $ele_value[5];
				$value[] = $ele_value[6];
				$value[] = $ele_value[7];
				$value[] = $ele_value[8];

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
				$checked = $_POST['checked']; // note that $_POST is blindly parsed into a new set of variables at the top of this file, so this line is strictly speaking unnecessary but it aids readability
				$value = array();
				list($ele_value, $uitext) = formulize_extractUIText($ele_value);
				while( $v = each($ele_value) ){
					//if( !empty($v['value']) ){
					if( $v['value'] !== "" ){
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
				list($ele_value, $uitext) = formulize_extractUIText($ele_value);
				while( $v = each($ele_value) ){
					/*print "<br>debug:<br>v = ";
					print_r ($v);
					print "<br>v[value] =";
					print_r($v['value']);
					print "<br>v[key] =";
					print_r($v['key']);
					print "<br>checked = $checked";*/

					//if( !empty($v['value']) OR $v['value'] === 0 OR $v['value'] === "0"){
			    if( $v['value'] !== "" ){
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
        $value[6] = $_POST['linkscopeanyall'];
        
        // check for conditions...added jwe feb 6 2008
        if((isset($_POST['formlink']) AND $_POST['formlink'] != "none") OR $ele_value[2][0] === "{FULLNAMES}" OR $ele_value[2][0] === "{USERNAMES}") {
          if($_POST['setfor'] == "con") {
            
            if($_POST['new_term'] !== "") { // add the last specified condition if there was one
              $_POST['elements'][] = $_POST['new_element'];
              $_POST['ops'][] = $_POST['new_op'];
              $_POST['terms'][] = $_POST['new_term'];
            }

            $value[5] = array(0=>$_POST['elements'], 1=>$_POST['ops'], 2=>$_POST['terms']); 
          } else {
            $value[5] = "";
          }
        } else {
          $value[5] = "";
        }
				// check to see if a link to another form was made and if so put in a marker that will be picked up at render time and handled accordingly...  -- jwe 7/29/04
				if(isset($_POST['formlink']) AND $_POST['formlink'] != "none")
				{
					// $value[2] = stripslashes($_POST['formlink']);
					// now receiving an ele_id due to the effects of xlanguage, so get the real caption out of the DB
					$sql_link = "SELECT ele_caption, id_form, ele_handle FROM " . $xoopsDB->prefix("formulize") . " WHERE ele_id = " . intval($_POST['formlink']);
					$res_link = $xoopsDB->query($sql_link);
					$array_link = $xoopsDB->fetchArray($res_link);
					$value[2] = $array_link['id_form'] . "#*=:*" . $array_link['ele_handle'];
         
				} 
				else
				{
				$v2 = array();
				$multi_flag = 1;
				list($ele_value[2], $uitext) = formulize_extractUIText($ele_value[2]);
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
        $value[2] = intval($_POST['subformblanks']); 
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
				// Added for number formatting values 2008-10-31 kw
				$value[1] = $ele_value[1];
				$value[2] = $ele_value[2];
				$value[3] = $ele_value[3];
				$value[4] = $ele_value[4];	
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
    
		$element->setVar('ele_uitext', $uitext);
		$element->setVar('ele_value', $value);
		$element->setVar('id_form', $id_form);
       
		if( !$formulize_mgr->insert($element) ){
			xoops_cp_header();
			echo $element->getHtmlErrors();
		}else{
			
      $ele_id = $element->getVar('ele_id'); // ele_id set inside the insert method after writing to database
			// don't let ele_handle be blank
			if($element->getVar('ele_handle') == "") {
            $ele_handle = $ele_id;
            $form_handler =& xoops_getmodulehandler('forms');
            while(!$uniqueCheck = $form_handler->isHandleUnique($ele_handle, $ele_id)) {
                  $ele_handle = $ele_handle . "_copy";
            }	    
						$element->setVar('ele_handle', $ele_handle); 
						if( !$formulize_mgr->insert($element) ){
									xoops_cp_header();
									echo $element->getHtmlErrors();
						}
			}
      
      if($original_handle) { // rewrite references in other elements to this handle (linked selectboxes)
				$ele_handle_len = strlen($ele_handle) + 5 + strlen($id_form);
				$orig_handle_len = strlen($original_handle) + 5 + strlen($id_form);
				$lsbHandleFormDefSQL = "UPDATE " . $xoopsDB->prefix("formulize") . " SET ele_value = REPLACE(ele_value, 's:$orig_handle_len:\"$id_form#*=:*$original_handle', 's:$ele_handle_len:\"$id_form#*=:*$ele_handle') WHERE ele_value LIKE '%$id_form#*=:*$original_handle%'"; // must include the cap lengths or else the unserialization of this info won't work right later, since ele_value is a serialized array!
				if($ele_handle != $original_handle) {
					if(!$res = $xoopsDB->query($lsbHandleFormDefSQL)) {
						print "Error:  update of linked selectbox element definitions failed.";
					}
				}
			}

			if($newFieldNeeded) {
        global $xoopsDB;
				$form_handler =& xoops_getmodulehandler('forms', 'formulize');
        if(!$insertResult = $form_handler->insertElementField($element)) {
          exit("Error: could not add the new element to the data table in the database.");
        }
      }	elseif($original_handle != $ele_handle AND $databaseElement) {
						// need to update the name of the field in the data table
						$form_handler =& xoops_getmodulehandler('forms', 'formulize');
						if(!$updateResult = $form_handler->updateFieldName($element, $original_handle)) {
									print "Error: count not update the data table field name to match the new data handle";
						}
			}
			
			// need to serialize the ele_value now, since it was put into the element object as an array, but the writing operation will handle the serialization so it's ok in the DB, but meanwhile it's still an array in the object.
      // we need to serialize it so that it will be retrieved properly later.
      $element->setVar('ele_value', serialize($value));
			
    }
      

      ?>