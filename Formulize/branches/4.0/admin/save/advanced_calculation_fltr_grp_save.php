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
##  URL: http://www.freeformsolutions.ca/formulize                           ##
##  Project: Formulize                                                       ##
###############################################################################

// this file handles saving of submissions from the advance_calculation_fltr_grp filters and grouping of the new admin UI

// if we aren't coming from what appears to be save.php, then return nothing
if(!isset($processedValues)) {
  return;
}

/*print_r($_POST);
print_r($processedValues);
return;*/

$aid = intval($_POST['aid']);
$acid = intval($_POST['formulize_admin_key']);
$op = $_POST['formulize_admin_op'];
$index = $_POST['formulize_admin_index'];

$advcalc = $processedValues['advcalc'];

// load an existing item
$advanced_calculation_handler = xoops_getmodulehandler('advancedCalculation', 'formulize');
$advCalcObject = $advanced_calculation_handler->get($acid);

// CHECK IF THE FORM IS LOCKED DOWN AND SCOOT IF SO
$form_handler = xoops_getmodulehandler('forms', 'formulize');
$formObject = $form_handler->get($advCalcObject->getVar('fid'));
if($formObject->getVar('lockedform')) {
  return;
}
// check if the user has permission to edit the form
if(!$gperm_handler->checkRight("edit_form", $advCalcObject->getVar('fid'), $groups, $mid)) {
  return;
}

/*
// apply user changes
$advCalcObject->setVar('input',$advCalc['input']);
$advCalcObject->setVar('output',$advCalc['output']);

// save object, and if a new item, reload page
if(!$acid = $advanced_calculation_handler->insert($advCalcObject)) {
  print "Error: could not save the advanced calculation properly: ".mysql_error();
}
*/

$fltr_grps = array();
$fltr_grptitles = array();

$has_blank_options = false;

foreach($advcalc as $k=>$v) {
  //print $k . '=>' . $v ."\n";
	if(substr($k, 0, 14) == "fltr_grptitle_") {
		$fltr_grp_number = intval(substr($k, 14));
		$fltr_grptitles[$fltr_grp_number] = $v;
  } else if(substr($k, 0, 12) == "description_") {
		$fltr_grp_number = intval(substr($k, 12));
		$fltr_grps[$fltr_grp_number][substr($k, 0, 11)] = $v;
  } else if(substr($k, 0, 7) == "handle_") {
		$fltr_grp_number = intval(substr($k, 7));
		$fltr_grps[$fltr_grp_number][substr($k, 0, 6)] = $v;
  } else if(substr($k, 0, 13) == "type_options_") {
		$pos = strrpos($k, "_");
		$fltr_grp_number = intval(substr($k, 13));
		$fltr_grp_type = intval(substr($k, $pos + 1));
    $fltr_type = $advcalc['type_'.$fltr_grp_number];
    //print $k . ':' . $fltr_grp_number . ':' . $fltr_grp_type . ':' . $fltr_type; exit();
    if( $fltr_type == $fltr_grp_type ) {
      if( $fltr_grps[$fltr_grp_number][substr($k, 0, 4)]['kind'] == 1 ) {
    		$fltr_grps[$fltr_grp_number][substr($k, 0, 4)]['options'] = null;
      } else if( $fltr_grps[$fltr_grp_number][substr($k, 0, 4)]['kind'] == 2 || $fltr_grps[$fltr_grp_number][substr($k, 0, 4)]['kind'] == 3 ) {
    		//$fltr_grps[$fltr_grp_number][substr($k, 0, 4)]['options'] = $v;
        $t_options = unserialize($v);
        foreach( $t_options as $t_key => $t_value ) {
          if( $t_value == "" ) {
            $has_blank_options = true;
            unset( $t_options[ $t_key ] );
          }
        }
    		$fltr_grps[$fltr_grp_number][substr($k, 0, 4)]['options'] = $t_options;
      }
    }
  } else if(substr($k, 0, 5) == "type_") {
		$fltr_grp_number = intval(substr($k, 5));
		$fltr_grps[$fltr_grp_number][substr($k, 0, 4)]['kind'] = $v;
  } else if(substr($k, 0, 11) == "fltr_label_") {
		$fltr_grp_number = intval(substr($k, 11));
		$fltr_grps[$fltr_grp_number][substr($k, 0, 10)] = $v;
  } else if(substr($k, 0, 10) == "grp_label_") {
		$fltr_grp_number = intval(substr($k, 10));
		$fltr_grps[$fltr_grp_number][substr($k, 0, 9)] = $v;
  } else if(substr($k, 0, 11) == "form_alias_") {
		$fltr_grp_number = intval(substr($k, 11));
		$fltr_grps[$fltr_grp_number][substr($k, 0, 10)] = $v;
  } else if(substr($k, 0, 5) == "form_") {
		$fltr_grp_number = intval(substr($k, 5));
		$fltr_grps[$fltr_grp_number][substr($k, 0, 4)] = $v;
  } else if(substr($k, 0, 10) == "is_filter_") {
		$fltr_grp_number = intval(substr($k, 10));
		$fltr_grps[$fltr_grp_number][substr($k, 0, 9)] = $v;
  } else if(substr($k, 0, 9) == "is_group_") {
		$fltr_grp_number = intval(substr($k, 9));
		$fltr_grps[$fltr_grp_number][substr($k, 0, 8)] = $v;
  }
}

//print_r( $fltr_grps ); exit();

//print_r( $_POST['fltr_grporder'] );

// get the new order of the filters and groupings...
$newOrder = explode("drawer-4[]=", str_replace("&", "", $_POST['fltr_grporder']));
unset($newOrder[0]);
// newOrder will have keys corresponding to the new order, and values corresponding to the old order
// need to add in conditions handling here too
$newfltr_grps = array();
$newfltr_grptitles = array();
$fltr_grpsHaveBeenReordered = false;
foreach($fltr_grptitles as $oldOrderNumber=>$values) {
	$newOrderNumber = array_search($oldOrderNumber,$newOrder);
	$newOrderNumberKey = $newOrderNumber-1;
	$newfltr_grps[$newOrderNumberKey] = $fltr_grps[$oldOrderNumber];
	$newfltr_grptitles[$newOrderNumberKey] = $fltr_grptitles[$oldOrderNumber];
	if(($newOrderNumber - 1) != $oldOrderNumber) {
		$fltr_grpsHaveBeenReordered = true;
		$_POST['reload_advance_calculation_fltr_grps'] = 1;
	}
}

if($fltr_grpsHaveBeenReordered) {
	$fltr_grps = $newfltr_grps;
	$fltr_grptitles = $newfltr_grptitles;
}


// alter the information based on a user add or delete
switch ($op) {
	case "addfltr_grp":
    $fltr_grps[]=array('description'=>'','handle'=>'','type'=>array("kind"=>1,"options"=>null),'form'=>'','form_alias'=>'','is_filter'=>0,'is_group'=>0);
    $fltr_grptitles[]='New filers and grouping';
		break;
	case "delfltr_grp":
    array_splice($fltr_grps, $index, 1);
    array_splice($fltr_grptitles, $index, 1);
		break;
  case "clonefltr_grp":
    $fltr_grp = $fltr_grps[$index];
    $newFiltersAndGrouping = array();
    foreach( $fltr_grp as $key => $value ) {
      $newFiltersAndGrouping[$key] = $value;
    }
    $fltr_grps[]=$newFiltersAndGrouping;
    $fltr_grptitles[]=$fltr_grptitles[$index].' copy';
    break;
}


/*print_r($fltr_grps);
print_r($fltr_grptitles);
return;*/

$advCalcObject->setVar('fltr_grps',$fltr_grps);
$advCalcObject->setVar('fltr_grptitles',$fltr_grptitles);

if(!$advanced_calculation_handler->insert($advCalcObject)) {
  print "Error: could not save the advanced calculation properly: ".mysql_error();
}

// reload the filter and grouping if the state has changed
if($op == "addfltr_grp" OR $op=="delfltr_grp" OR $op=="clonefltr_grp" OR $_POST['reload_advance_calculation_fltr_grps'] OR $has_blank_options) {
    print "/* eval */ reloadWithScrollPosition();";
}
?>
