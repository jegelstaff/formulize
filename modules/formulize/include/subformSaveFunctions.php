<?php

// determine the value to write into a new subform entry, based on the values in the main form
// $frid is the form relationship id
// $fid is the main form id
// $entry is the entry id in the main form
// $target_sub_to_use is the subform id
function formulize_subformSave_determineElementToWrite($frid, $fid, $entry, $target_sub_to_use) {

    global $xoopsDB;
    if(!is_numeric($entry) AND $entry != 'new') {
        $entry = '';
    }

    $elementq = q("SELECT fl_key1, fl_key2, fl_common_value, fl_form2_id FROM " . $xoopsDB->prefix("formulize_framework_links") . " WHERE fl_frame_id=" . intval($frid) . " AND fl_form2_id=" . intval($fid) . " AND fl_form1_id=" . intval($target_sub_to_use). " AND fl_relationship=3");
	// element_to_write is used below in writing results of "add x entries" clicks, plus it is used for defaultblanks on first drawing blank entries, so we need to get this outside of the saving routine
	if(count($elementq) > 0) {
		$element_to_write = $elementq[0]['fl_key1'];
		$value_source = $elementq[0]['fl_key2'];
		$value_source_form = $elementq[0]['fl_form2_id'];
	} else {
		$elementq = q("SELECT fl_key2, fl_key1, fl_common_value, fl_form1_id FROM " . $xoopsDB->prefix("formulize_framework_links") . " WHERE fl_frame_id=" . intval($frid) . " AND fl_form1_id=" . intval($fid) . " AND fl_form2_id=" . intval($target_sub_to_use) . " AND fl_relationship=2");
		$element_to_write = $elementq[0]['fl_key2'];
		$value_source = $elementq[0]['fl_key1'];
		$value_source_form = $elementq[0]['fl_form1_id'];		
	}
    
    // in case we need it for writing later...figure out the matching value in the main form, if any...
    if($elementq[0]['fl_common_value']) {
        // grab the value from the parent element -- assume that it is a textbox of some kind!
        if (isset($_POST['de_'.$value_source_form.'_'.$entry.'_'.$value_source])) {
            $value_to_write = $_POST['de_'.$value_source_form.'_'.$entry.'_'.$value_source];
        } else {
            // get this entry and see what the source value is
            $data_handler = new formulizeDataHandler($value_source_form);
            $value_to_write = $data_handler->getElementValueInEntry($entry, $value_source);
        }
    } else {
        $value_to_write = $entry; 
    }
    
    return array($elementq, $element_to_write, $value_to_write, $value_source, $value_source_form);
}

// this function writes entries into a subform
// $element_to_write is the element in the subform to write the key value to
// $value_to_write is the value to write into the element
// $mainFormFid is the mainform id number
// $frid is the form relationship id in effect, if any
// $target_sub is the form id of the subform
// $entry is the entry id in the main form (parent form)
// $subformConditions are the conditions in effect on any subform element in the parent form that is governing which sub entries are included/shown
// $overrideOwnerOfNewEntries is a flag to indicate if the mainform owner is assigned as the owner of newly created sub entries
// $mainFormOwner is the owner of the main form entry
// $numSubEnts is the number of sub entries to create
function formulize_subformSave_writeNewEntry($element_to_write, $value_to_write, $mainFormFid, $frid, $target_sub, $entry, $subformConditions, $overrideOwnerOfNewEntries, $mainFormOwner, $numSubEnts) {
    // need to handle things differently depending on whether it's a common value or a linked selectbox type of link
    // uid links need to result in a "new" value in the displayElement boxes -- odd things will happen if people start adding linked values to entries that aren't theirs!
    global $subformSubEntryMap;
    if(!is_array($subformSubEntryMap)) {
        $subformSubEntryMap = array(); // initialize as array
    }
    if($element_to_write != 0) {
        
        $sub_entry_new = "";
    
        for($i=0;$i<$numSubEnts;$i++) { // actually goahead and create the requested number of new sub entries...start with the key field, and then do all textboxes with defaults too...
            if($overrideOwnerOfNewEntries) {
              $creation_user_touse = $mainFormOwner;
            } else {
              $creation_user_touse = "";
            }
            $subEntWritten = writeElementValue($target_sub, $element_to_write, "new", $value_to_write, $creation_user_touse, "", true); // Last param is override that allows direct writing to linked selectboxes if we have prepped the value first!
            writeEntryDefaults($target_sub,$subEntWritten);
            $sub_entry_written[] = $subEntWritten;
            $subformSubEntryMap[$target_sub][] = array('parent'=>$entry, 'self'=>$subEntWritten);
        }
    } else {
        $sub_entry_new = "new"; // this happens in uid-link situations?
        $sub_entry_written = "";
    }

    // need to also enforce any equals conditions that are on the subform element, if any, and assign those values to the entries that were just added
    // also, enforce any derived values on the subform entry itself
    if(is_array($subformConditions)) {
        $filterValues = getFilterValuesForEntry($subformConditions, $entry);
        $filterValues = $filterValues[key($filterValues)]; // subform element conditions are always on one form only so we just take the first set of values found (filterValues are grouped by form id)
    } 
    foreach($sub_entry_written as $thisSubEntry) {
        if(isset($filterValues) AND count($filterValues)>0) {
            formulize_writeEntry($filterValues,$thisSubEntry);	
        }
        if($frid) {
            formulize_updateDerivedValues($entry,$mainFormFid,$frid);
        } else {
            formulize_updateDerivedValues($thisSubEntry,$target_sub);
        }
    }
    return array($sub_entry_new,$sub_entry_written,$filterValues);
}