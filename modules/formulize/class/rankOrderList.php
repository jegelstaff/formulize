<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2010 Freeform Solutions                  ##
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
##  Author of this file: Freeform Solutions                                  ##
##  Project: Formulize                                                       ##
###############################################################################

// This class is meant to extend the base element class, and handle all operations in the Formulize module that are specific to this element type
// This includes: admin UI options and saving logic for this element (subject to change in 4.0 code),
// element rendering (including the prior step of loading the current value into the element), and including
// validation logic and anything else specific to the element, prepDataForWrite logic that gets the submitted values ready for
// insertion in the DB, and basically anything else we can think of.
// Intention is to make the module code aware of the potential for class files to exist, but not require all element
// types to be turned into classes right away.

class formulizeRankOrderListHandler {
  var $db;
  var $needsDataType;
  function formulizeRankOrderListHandler(&$db) {
    $this->db =& $db;
    $this->needsDataType = true;
    $this->needsjQuery = true;
  }
  
  // form is the admin form object,
  // value is the element specific data that applies to this specific element instance
  // ele_id is the element id
  function adminUI($form, $value, $ele_id) {
    
    $myts =& MyTextSanitizer::getInstance();
    
    $options = array();
    $opt_count = 0;
    $addopt = $_POST['addopt'];
    if( !empty($ele_id) ){
      $keys = array_keys($value);
      for( $i=0; $i<count($keys); $i++ ){
        $r = $value[$keys[$i]] ? $opt_count : null;
        $v = $myts->makeTboxData4PreviewInForm($keys[$i]);
        $options[] = new xoopsFormText('', 'ele_value['.$opt_count.']', 40, 255, $v); // function in the admin/elements.php file
        $opt_count++;
      }
    }
      
      // added check below to add in blank rows that are unaccounted for above.
      // above code adds in all the options the user has typed in.  If there are blank rows on the form, this code will add in the appropriate amount, based on the 'rowcount' hidden element.
      // This code added by jwe 01/05/05
      if($opt_count < $_POST['rowcount']) {
        for($i=$opt_count;$i<$_POST['rowcount'];$i++) {
          $options[] = new xoopsFormText('', 'ele_value['.$i.']', 40, 255, '');
        }
        $opt_count = $_POST['rowcount']; // make the opt_count equal to the number of rows, since we've now brought the number back up to where it should be.
      }
    
      if(empty($addopt) AND empty($ele_id)) {
        $addopt = 2;
      } 
      for( $i=0; $i<$addopt; $i++ ){ 
        $options[] = new xoopsFormText('', 'ele_value['.$opt_count.']', 40, 255, ''); 
        $opt_count++;
      }
      // these two lines part of the jwe added code
      $rowcount = new XoopsFormHidden("rowcount", $opt_count);
      $form->addElement($rowcount);
    
    $opt_tray = new XoopsFormElementTray(_AM_ELE_OPT, '<br />');
    $opt_tray->setDescription(_AM_ELE_OPT_DESC_RANKORDERLISTS.'<br /><br />'._AM_ELE_OPT_UITEXT);
    
    for( $i=0; $i<count($options); $i++ ){
      $opt_tray->addElement($options[$i]);
    }
    $opt_tray->addElement(addOptionsTray());
    $form->addElement($opt_tray);
    return $form;
  }
  
  // this function handles the saving of data sent back by the adminUI
  // ele_value is the information sent back by the form
  function adminSave($ele_value) {
    $value = array();
    list($ele_value, $uitext) = formulize_extractUIText($ele_value);
    while( $v = each($ele_value) ){
      if( $v['value'] !== "" ){
        $value[$v['value']] = 0;
      }
    }
    return array(0=>$value, 1=>$uitext);
  }
  
  // this function renders the element
  function render($elementObject, $form_ele_id, $isDisabled) {
    
    $order = new xoopsFormHidden($form_ele_id, ''); // this element will receive the order of the sortable options upon saving, which is then read by prepDataForWrite

    if(!isset($GLOBALS['formulize_jQuery_included'])) { // may already be included by formdisplay.php, otherwise we're rendering element at a time and we need to account for this here
      $sortableListHTML = "<script type=\"text/javascript\" src=\"".XOOPS_URL."/modules/formulize/libraries/jquery/jquery-1.3.2.min.js\"></script>
<script type=\"text/javascript\" src=\"".XOOPS_URL."/modules/formulize/libraries/jquery/jquery-ui-1.7.2.custom.min.js\"></script>";
      $GLOBALS['formulize_jQuery_included'] = true;
    }
    
    if(!isset($GLOBALS['formulize_sortable_style_included'])) {
      print "<style>
      .sortableitem {
        width: 200px; background-color: white; border: 1px solid black; margin-top: 2px; margin-bottom: 2px; padding: 2px;        
      }
      </style>";
      $GLOBALS['formulize_sortable_style_included'] = true;
    }

    $sortableListHTML .= '
    
	<script type="text/javascript">
	$(function() {
		$("#sortable-'.$elementObject->getVar('ele_id').'").sortable({
      axis: "y",
      items: "div"
    });
		$("#sortable-'.$elementObject->getVar('ele_id').'").disableSelection();
	});
	</script>

<div id="sortable-'.$elementObject->getVar('ele_id').'">';

    $counter = 1;
    foreach($elementObject->getVar('ele_value') as $thisOption=>$dummyValue) {
      $sortableListHTML .= "<div id=\"sortable-$counter\" class=\"sortableitem\">$thisOption</div>\n";
      $counter++;
    }
    
    $sortableListHTML .= "</div>";
    
    $list = new xoopsFormLabel('', $sortableListHTML);
    
    $tray = new xoopsFormElementTray($elementObject->getVar('ele_caption'), '\n');
    $tray->addElement($order);
    $tray->addElement($list);
    return $tray;
  }
  
  
}

