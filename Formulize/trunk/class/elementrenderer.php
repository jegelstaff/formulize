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

include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";

class formulizeElementRenderer{
	var $_ele;

	function formulizeElementRenderer(&$element){
		$this->_ele =& $element;
	}

	// function params modified to accept passing of $ele_value from index.php
	// $entry added June 1 2006 as part of 'Other' option for radio buttons and checkboxes
	function constructElement($form_ele_id, $ele_value, $entry, $isDisabled=false, $screen=null){
		if (strstr(getCurrentURL(),"printview.php")) {
			$isDisabled = true; // disabled all elements if we're on the printable view
		} 
		global $xoopsUser, $xoopsModuleConfig, $separ, $myts;
		$myts =& MyTextSanitizer::getInstance();
		
		
		// $form_ele_id contains the ele_id of the current link select box, but we have to remove "ele_" from the front of it.
		//print "form_ele_id: $form_ele_id<br>"; // debug code
		if(strstr($form_ele_id, "de_")) { // display element uses a slightly different element name so it can be distinguished on subsequent page load from regular elements...THIS IS NOT TRUE/NECESSARY ANYMORE SINCE FORMULIZE 3, WHERE ALL ELEMENTS ARE DISPLAY ELEMENTS
			$true_ele_id = str_replace("de_".$this->_ele->getVar('id_form')."_".$entry."_", "", $form_ele_id);
			$displayElementInEffect = true;
		} else {
			$true_ele_id = str_replace("ele_", "", $form_ele_id);
			$displayElementInEffect = false;
		}
	
		
		// added July 6 2005.
		if(!$xoopsModuleConfig['delimeter']) {
			// assume that we're accessing a form from outside the Formulize module, therefore the Formulize delimiter setting is not available, so we have to query for it directly.
			global $xoopsDB;
			$delimq = q("SELECT conf_value FROM " . $xoopsDB->prefix("config") . ", " . $xoopsDB->prefix("modules") . " WHERE " . $xoopsDB->prefix("modules") . ".mid=" . $xoopsDB->prefix("config") . ".conf_modid AND " . $xoopsDB->prefix("modules") . ".dirname=\"formulize\" AND " . $xoopsDB->prefix("config") . ".conf_name=\"delimeter\"");
			$delimSetting = $delimq[0]['conf_value'];
		} else {
			$delimSetting = $xoopsModuleConfig['delimeter'];
		}
	
		$customElementHasData = false;
		$id_form = $this->_ele->getVar('id_form');
		$ele_caption = $this->_ele->getVar('ele_caption', 'e');
		$ele_caption = preg_replace('/\{SEPAR\}/', '', $ele_caption);
		// $ele_caption = stripslashes($ele_caption);
		// next line commented out to accomodate passing of ele_value from index.php
		// $ele_value = $this->_ele->getVar('ele_value');
		$ele_type = $this->_ele->getVar('ele_type');


		// call the text sanitizer, first try to convert HTML chars, and if there were no conversions, then do a textarea conversion to automatically make links clickable
		$ele_caption = trans($ele_caption); 
		$htmlCaption = htmlspecialchars_decode($myts->undoHtmlSpecialChars($ele_caption)); // do twice, because we need to handle &amp;lt; and other stupid stuff...do first time through XOOPS myts just because it might be doing a couple extra things that are useful...can probably just use PHP's own filter twice, not too big a deal
		if($htmlCaption == $ele_caption) {
        	$ele_caption = $myts->displayTarea($ele_caption);
		} else {
			$ele_caption = $htmlCaption;
		}
		
		$ele_caption = $this->formulize_replaceCurlyBracketVariables($ele_caption, $entry, $id_form);
		
		// ele_desc added June 6 2006 -- jwe
		$ele_desc = $this->_ele->getVar('ele_desc', "f"); // the f causes no stupid reformatting by the ICMS core to take place

		// determine the entry owner
		if($entry != "new") {
					$owner = getEntryOwner($entry, $id_form);
		} else {
					$owner = $xoopsUser ? $xoopsUser->getVar('uid') : 0;
		}

		// setup the previous entry UI if necessary -- this is an option that can be specified for certain screens
		$previousEntryUI = "";
		if($screen AND $ele_type != "derived") {
			if($screen->getVar('paraentryform') > 0) {
				$previousEntryUI = $this->formulize_setupPreviousEntryUI($screen, $true_ele_id, $ele_type, $owner, $displayElementInEffect, $entry, $this->_ele->getVar('ele_handle'), $this->_ele->getVar('id_form'));
			}
		}
		
		$form_handler = xoops_getmodulehandler('forms', 'formulize');
		$formObject = $form_handler->get($id_form);
	
		switch ($ele_type){
			case 'derived':
				if($entry != "new") {
					$form_ele = new xoopsFormLabel($this->_ele->getVar('ele_caption'), formulize_numberFormat($ele_value[5], $this->_ele->getVar('ele_handle')));
					$form_ele->setDescription(html_entity_decode($ele_desc,ENT_QUOTES));
				} else {
					$form_ele = new xoopsFormLabel($this->_ele->getVar('ele_caption'), _formulize_VALUE_WILL_BE_CALCULATED_AFTER_SAVE);
					$form_ele->setDescription(html_entity_decode($ele_desc,ENT_QUOTES));
				}
				break;

			case 'ib':
				if(get_magic_quotes_gpc()) {
					$ele_value[0] = stripslashes($ele_value[0]);
 				}
				if(trim($ele_value[0]) == "") { $ele_value[0] = $ele_caption; }
				if(strstr($ele_value[0], "\$value=") OR strstr($ele_value[0], "\$value =")) {
					$form_id = $id_form;
					$entry_id = $entry;
					$entryData = $this->formulize_getCachedEntryData($id_form, $entry);
					$creation_datetime = display($entryData, "creation_datetime");
					$evalResult = eval($ele_value[0]);
					if($evalResult === false) {
						$ele_value[0] = _formulize_ERROR_IN_LEFTRIGHT;
					} else {
						$ele_value[0] = $value; // value is supposed to be the thing set in the eval'd code
					}
				}
				$ele_value[0] = $this->formulize_replaceCurlyBracketVariables($ele_value[0], $entry, $id_form);
				$form_ele = $ele_value; // an array, item 0 is the contents of the break, item 1 is the class of the table cell (for when the form is table rendered)
				break;
			case 'text':
				$ele_value[2] = stripslashes($ele_value[2]);
//        $ele_value[2] = $myts->displayTarea($ele_value[2]); // commented by jwe 12/14/04 so that info displayed for viewing in a form box does not contain HTML formatting
				
				$ele_value[2] = getTextboxDefault($ele_value[2], $id_form, $entry);

				if (!strstr(getCurrentURL(),"printview.php")) { 				// nmc 2007.03.24 - added
					
					$form_ele = new XoopsFormText(
					$ele_caption,
					$form_ele_id,
					$ele_value[0],	//	box width
					$ele_value[1],	//	max width
					$ele_value[2]	  //	default value
					);
				} else {															// nmc 2007.03.24 - added 
					$form_ele = new XoopsFormLabel ($ele_caption, $ele_value[2]);	// nmc 2007.03.24 - added 
				}

				// if required unique option is set, create validation javascript that will ask the database if the value is unique or not
				if($ele_value[9]) {
					$eltname = $form_ele_id;
					$eltcaption = $ele_caption;
					$eltmsg = empty($eltcaption) ? sprintf( _FORM_ENTER, $eltname ) : sprintf( _FORM_ENTER, $eltcaption );
					$eltmsg = str_replace('"', '\"', stripslashes($eltmsg));
					$eltmsgUnique = empty($eltcaption) ? sprintf( _formulize_REQUIRED_UNIQUE, $eltname ) : sprintf( _formulize_REQUIRED_UNIQUE, $eltcaption );
					if($this->_ele->getVar('ele_req')) { // need to manually handle required setting, since only one validation routine can run for an element, so we need to include required checking in this unique checking routine, if the user selected required too
						$form_ele->customValidationCode[] = "\nif ( myform.{$eltname}.value == '' ) {\n";
						$form_ele->customValidationCode[] = "window.alert(\"{$eltmsg}\");\n myform.{$eltname}.focus();\n return false;\n";
						$form_ele->customValidationCode[] = "}\n";
					}
					$form_ele->customValidationCode[] = "if(formulize_xhr_returned_check_for_unique_value != 'notreturned') {\n"; // a value has already been returned from xhr, so let's check that out...
					$form_ele->customValidationCode[] = "if(formulize_xhr_returned_check_for_unique_value != 'valuenotfound') {\n"; // request has come back, form has been resubmitted, but the check turned up postive, ie: value is not unique, so we have to halt submission , and reset the check for unique flag so we can check again when the user has typed again and is ready to submit
					$form_ele->customValidationCode[] = "window.alert(\"{$eltmsgUnique}\");\n";
					$form_ele->customValidationCode[] = "formulize_xhr_returned_check_for_unique_value = 'notreturned'\n";
					$form_ele->customValidationCode[] = "myform.{$eltname}.focus();\n return false;\n";
					$form_ele->customValidationCode[] = "}\n";
					$form_ele->customValidationCode[] = "} else {\n";	 // do not submit the form, just send off the request, which will trigger a resubmission after setting the returned flag above to true so that we won't send again on resubmission
					$form_ele->customValidationCode[] = "\nvar formulize_xhr_params = []\n";
					$form_ele->customValidationCode[] = "formulize_xhr_params[0] = myform.{$eltname}.value;\n";
					$form_ele->customValidationCode[] = "formulize_xhr_params[1] = ".$this->_ele->getVar('ele_id').";\n";
					$xhr_entry_to_send = is_numeric($entry) ? $entry : 0;
					$form_ele->customValidationCode[] = "formulize_xhr_params[2] = ".$xhr_entry_to_send.";\n";
					$form_ele->customValidationCode[] = "formulize_xhr_send('check_for_unique_value', formulize_xhr_params);\n";
					$form_ele->customValidationCode[] = "return false;\n"; 
					$form_ele->customValidationCode[] = "}\n";
					
				} elseif($this->_ele->getVar('ele_req') AND !$isDisabled) {
					$eltname = $form_ele_id;
					$eltcaption = $ele_caption;
					$eltmsg = empty($eltcaption) ? sprintf( _FORM_ENTER, $eltname ) : sprintf( _FORM_ENTER, $eltcaption );
					$eltmsg = str_replace('"', '\"', stripslashes($eltmsg));
					$form_ele->customValidationCode[] = "if (myform.{$eltname}.value == \"\") { window.alert(\"{$eltmsg}\"); myform.{$eltname}.focus(); return false; }";
				}

			break;
			
			case 'textarea':
				$ele_value[0] = stripslashes($ele_value[0]);
//        $ele_value[0] = $myts->displayTarea($ele_value[0]); // commented by jwe 12/14/04 so that info displayed for viewing in a form box does not contain HTML formatting
				$ele_value[0] = getTextboxDefault($ele_value[0], $id_form, $entry);
				if (!strstr(getCurrentURL(),"printview.php")) { 				// nmc 2007.03.24 - added 
					$form_ele = new XoopsFormTextArea(
						$ele_caption,
						$form_ele_id,
						$ele_value[0],	//	default value
						$ele_value[1],	//	rows
						$ele_value[2]	  //	cols
					);
				} else {															// nmc 2007.03.24 - added 
					$form_ele = new XoopsFormLabel ($ele_caption, str_replace("\n", "<br>", $ele_value[0]));	// nmc 2007.03.24 - added 
				}
			break;
			case 'areamodif':
				if(strstr($ele_value[0], "\$value=") OR strstr($ele_value[0], "\$value =")) {
					$form_id = $id_form;
					$entry_id = $entry;
					$entryData = $this->formulize_getCachedEntryData($id_form, $entry);
					$creation_datetime = display($entryData, "creation_datetime");
					$evalResult = eval($ele_value[0]);
					if($evalResult === false) {
						$ele_value[0] = _formulize_ERROR_IN_LEFTRIGHT;
					} else {
						$ele_value[0] = $value; // value is supposed to be the thing set in the eval'd code
					}
				}
				$ele_value[0] = $this->formulize_replaceCurlyBracketVariables($ele_value[0], $entry, $id_form);
				$form_ele = new XoopsFormLabel(
					$ele_caption,
					$ele_value[0]
				);
			break;
			
			case 'select':
				if(strstr($ele_value[2], "#*=:*")) // if we've got a link on our hands... -- jwe 7/29/04
				{
					
					// new process for handling links...May 10 2008...new datastructure for formulize 3.0
					$boxproperties = explode("#*=:*", $ele_value[2]);
					$sourceFid = $boxproperties[0];
					$sourceHandle = $boxproperties[1];
					$sourceEntryIds = explode(",", trim($boxproperties[2],","));

					// grab the user's groups and the module id
					global $regcode;
					if($regcode) { // if we're dealing with a registration code, determine group membership based on the code
						$reggroupsq = q("SELECT reg_codes_groups FROM " . XOOPS_DB_PREFIX . "_reg_codes WHERE reg_codes_code=\"$regcode\"");
						$groups = explode("&8(%$", $reggroupsq[0]['reg_codes_groups']);
						if($groups[0] === "") { unset($groups); } // if a code has no groups associated with it, then kill the null value that will be in position 0 in the groups array.
						$groups[] = XOOPS_GROUP_USERS;
						$groups[] = XOOPS_GROUP_ANONYMOUS;
					} else {
						$groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
					}
					$module_id = getFormulizeModId();
					
					global $xoopsDB;
					
					$pgroups = array();
					// handle new linkscope option -- August 30 2006
					$emptylist = false;
					if($ele_value[3]) {
						$scopegroups = explode(",",$ele_value[3]);
						if(!in_array("all", $scopegroups)) {
							if($ele_value[4]) { // limit by user's groups
								foreach($groups as $gid) { // want to loop so we can get rid of reg users group simply
									if($gid == XOOPS_GROUP_USERS) { continue; }
									if(in_array($gid, $scopegroups)) { 
										$pgroups[] = $gid;
									}
								}
							} else { // just use scopegroups
								$pgroups = $scopegroups;
							}
							if(count($pgroups) == 0) { // specific scope was specified, and nothing found, so we should show nothing
								$emptylist = true;
							}
						} else {
							if($ele_value[4]) { // all groups selected, but limiting by user's groups is turned on
								foreach($groups as $gid) { // want to loop so we can get rid of reg users group simply
									if($gid == XOOPS_GROUP_USERS) { continue; }
									$pgroups[] = $gid;
								}
							} else { // all groups should be used
								unset($pgroups);
								$allgroupsq = q("SELECT groupid FROM " . $xoopsDB->prefix("groups")); //  . " WHERE groupid != " . XOOPS_GROUP_USERS); // use all groups now, if all groups are picked, with no restrictions on membership or anything, then use all groups
								foreach($allgroupsq as $thisgid) {
									$pgroups[] = $thisgid['groupid'];
								}
							}
						}
					}

					// Note: OLD WAY: if no groups were found, then pguidq will be empty and so all entries will be shown, no restrictions
					// NEW WAY: if a specific group(s) was specified, and no match with the current user was found, then we return an empty list
					array_unique($pgroups); // remove duplicate groups from the list
					
					if($ele_value[6] AND count($pgroups) > 0) {  
						$pgroupsfilter = " (";
						$start = true;
						foreach($pgroups as $thisPgroup) {
							if(!$start) { $pgroupsfilter .= " AND "; }
							$pgroupsfilter .= "EXISTS(SELECT 1 FROM ".$xoopsDB->prefix("formulize_entry_owner_groups")." AS t2 WHERE t2.groupid=$thisPgroup AND t2.fid=$sourceFid AND t2.entry_id=t1.entry_id)";
							$start = false;
						}
						$pgroupsfilter .= ")";
					} elseif(count($pgroups) > 0) {
						$pgroupsfilter = " t2.groupid IN (".mysql_real_escape_string(implode(",",$pgroups)).") AND t2.entry_id=t1.entry_id AND t2.fid=$sourceFid";
					} else {
						$pgroupsfilter = "";
					}
					
					$sourceFormObject = $form_handler->get($sourceFid);

					list($conditionsfilter, $conditionsfilter_oom, $parentFormFrom) = buildConditionsFilterSQL($ele_value[5], $sourceFid, $entry, $owner, $formObject, "t1");

					// if there is a restriction in effect, then add some SQL to reject options that have already been selected ??
					$restrictSQL = "";
					if($ele_value[9]) {
						$restrictSQL = " AND (
						NOT EXISTS (
						SELECT 1 FROM ".$xoopsDB->prefix("formulize_".$formObject->getVar('form_handle'))." AS t4 WHERE t4.`".$this->_ele->getVar('ele_handle')."` LIKE CONCAT( '%,', t1.`entry_id` , ',%' ) AND t4.entry_id != ".intval($entry);
						$restrictSQL .= $this->addEntryRestrictionSQL($ele_value[9], $id_form, $groups); // pass in the flag about restriction scope, and the form id, and the groups
						$restrictSQL .= " ) OR EXISTS (
						SELECT 1 FROM ".$xoopsDB->prefix("formulize_".$formObject->getVar('form_handle'))." AS t4 WHERE t4.`".$this->_ele->getVar('ele_handle')."` LIKE CONCAT( '%,', t1.`entry_id` , ',%' ) AND t4.entry_id = ".intval($entry);
						$restrictSQL .= $this->addEntryRestrictionSQL($ele_value[9], $id_form, $groups);
						$restrictSQL .= ") )";
					}

					static $cachedSourceValuesQ = array();
					static $cachedSourceValuesAutocompleteFile = array();
					static $cachedSourceValuesAutocompleteLength = array();

					// setup the sort order based on ele_value[12], which is an element id number
					$sortOrder = $ele_value[15] == 2 ? " DESC" : "ASC";
					if($ele_value[12]=="none" OR !$ele_value[12]) {
						$sortOrderClause = " ORDER BY t1.`$sourceHandle` $sortOrder";
					} else {
						list($sortHandle) = convertElementIdsToElementHandles(array($ele_value[12]), $sourceFormObject->getVar('id_form'));
						$sortOrderClause = " ORDER BY t1.`$sortHandle` $sortOrder";
					}

					// if no extra elements are selected for display as a form element, then display the linked element
					if (0 == count($ele_value[EV_MULTIPLE_FORM_COLUMNS])) {
						$linked_columns = array($boxproperties[1]);
					} else {
						$linked_columns = convertElementIdsToElementHandles($ele_value[EV_MULTIPLE_FORM_COLUMNS], $sourceFormObject->getVar('id_form'));
		                // remove empty entries, which can happen if the "use the linked field selected above" option is selected
		                $linked_columns = array_filter($linked_columns);
					}
					if (is_array($linked_columns)) {
					    $select_column = "t1.`".implode("`, t1.`", $linked_columns)."`";
					} else {
					    $select_column = "t1.`{$linked_columns}`";	// in this case, it's just one linked column
					}

					// if there is a groups filter, then join to the group ownership table
					$extra_clause = "";
					if ($pgroupsfilter) {
						$extra_clause = ", ".$xoopsDB->prefix("formulize_entry_owner_groups")." AS t2 $parentFormFrom WHERE $pgroupsfilter";
					} else {
						$extra_clause = " $parentFormFrom WHERE t1.entry_id>0";
					}
					$sourceValuesQ = "SELECT t1.entry_id, ".$select_column." FROM ".$xoopsDB->prefix("formulize_".
						$sourceFormObject->getVar('form_handle'))." AS t1".$extra_clause.
						"$conditionsfilter $conditionsfilter_oom $restrictSQL"."GROUP BY t1.entry_id $sortOrderClause";
					if(!$isDisabled) {
						// set the default selections, based on the entry_ids that have been selected as the defaults, if applicable
						$hasNoValues = trim($boxproperties[2]) == "" ? true : false;
						$useDefaultsWhenEntryHasNoValue = $ele_value[14];
						if(($entry == "new" OR ($useDefaultsWhenEntryHasNoValue AND $hasNoValues)) AND ((is_array($ele_value[13]) AND count($ele_value[13]) > 0) OR $ele_value[13])) {
							$defaultSelected = $ele_value[13];
						} else {
							$defaultSelected = "";
						}
						$form_ele = new XoopsFormSelect($ele_caption, $form_ele_id, $defaultSelected, $ele_value[0], $ele_value[1]);
						$form_ele->setExtra("onchange=\"javascript:formulizechanged=1;\" jquerytag='$form_ele_id'");
						if($ele_value[0] == 1) { // add the initial default entry, singular or plural based on whether the box is one line or not.
							$form_ele->addOption("none", _AM_FORMLINK_PICK);
						}
					} else {
						$disabledHiddenValue = array();
						$disabledOutputText = array();
					}
					
					if(!isset($cachedSourceValuesQ[$sourceValuesQ])) {
						$element_handler = xoops_getmodulehandler('elements', 'formulize');
						$sourceElementObject = $element_handler->get($boxproperties[1]);
						if($sourceElementObject->isLinked) {
							// need to jump one more level back to get value that this value is pointing at
							$sourceEleValue = $sourceElementObject->getVar('ele_value');
							$originalSource = explode("#*=:*", $sourceEleValue[2]);
							include_once XOOPS_ROOT_PATH . "/modules/formulize/class/data.php";
							$data_handler = new formulizeDataHandler($originalSource[0]);
						}
						$reslinkedvaluesq = $xoopsDB->query($sourceValuesQ);
						if($reslinkedvaluesq) {
							$linked_column_count = count($linked_columns);
							while($rowlinkedvaluesq = $xoopsDB->fetchRow($reslinkedvaluesq)) {
								$linked_column_values = array();
								foreach (range(1, $linked_column_count) as $linked_column_index) {
									if ($rowlinkedvaluesq[$linked_column_index] === "") {
										$linked_column_values[] = "";
									} else {
										if ($sourceElementObject->isLinked) {
											$linked_column_values[] = strip_tags($data_handler->getElementValueInEntry(trim($rowlinkedvaluesq[$linked_column_index], ","), $originalSource[$linked_column_index]));
										} else {
											$linked_column_values[] = strip_tags(trim($rowlinkedvaluesq[$linked_column_index]));
										}
									}
								}
								$linkedElementOptions[$rowlinkedvaluesq[0]] = implode(" - ", $linked_column_values);
							}
						}
						$cachedSourceValuesQ[$sourceValuesQ] = $linkedElementOptions;
						/* ALTERED - 20100318 - freeform - jeff/julian - start */
						if(!$isDisabled AND $ele_value[8] == 1) {
							// write the possible values to a cached file so we can look them up easily when we need them, don't want to actually send them to the browser, since it could be huge, but don't want to replicate all the logic that has already gathered the values for us, each time there's an ajax request
							$cachedLinkedOptionsFileName = "formulize_linkedOptions_".str_replace(".","",microtime(true));
							formulize_scandirAndClean(XOOPS_ROOT_PATH."/cache/", "formulize_linkedOptions_");
							$cachedLinkedOptions = fopen(XOOPS_ROOT_PATH."/cache/$cachedLinkedOptionsFileName","w");
							fwrite($cachedLinkedOptions, "<?php\n\r");
							$maxLength = 0;
							foreach($linkedElementOptions as $id=>$text) {
								$thisTextLength = strlen($text);
								$maxLength = $thisTextLength > $maxLength ? $thisTextLength : $maxLength;
								$text = str_replace("\$", "\\\$", $text);
								$quotedText = "\"".str_replace("\"", "\\\"", html_entity_decode($text, ENT_QUOTES))."\"";
								$singleQuotedText = str_replace("'", "\'", "[$quotedText,$id]");
								fwrite($cachedLinkedOptions,"if(stristr($quotedText, \$term)){ \$found[]='".$singleQuotedText."'; }\n");
							}
							fwrite($cachedLinkedOptions, "?>");
							fclose($cachedLinkedOptions);
							$cachedSourceValuesAutocompleteFile[$sourceValuesQ] = $cachedLinkedOptionsFileName;
							$cachedSourceValuesAutocompleteLength[$sourceValuesQ] = $maxLength;
						} 
					}
					
					// if we're rendering an autocomplete box
					if(!$isDisabled AND $ele_value[8] == 1) {
						// do autocomplete rendering logic here
						if($boxproperties[2]) {
							$default_value = trim($boxproperties[2], ",");
							$data_handler_autocomplete = new formulizeDataHandler($boxproperties[0]);
							$default_value_user = $data_handler_autocomplete->getElementValueInEntry(trim($boxproperties[2], ","), $boxproperties[1]);
						}
						$renderedComboBox = $this->formulize_renderQuickSelect($form_ele_id, $cachedSourceValuesAutocompleteFile[$sourceValuesQ], $default_value, $default_value_user, $cachedSourceValuesAutocompleteLength[$sourceValuesQ]);
						$form_ele = new xoopsFormLabel($ele_caption, $renderedComboBox);
						$form_ele->setDescription(html_entity_decode($ele_desc,ENT_QUOTES));
					}
					
					// only do this if we're rendering a normal element, that is not disabled
					if(!$isDisabled AND $ele_value[8] == 0) {
						$form_ele->addOptionArray($cachedSourceValuesQ[$sourceValuesQ]);
					}

					// only do this if we're rendering a normal element (may be disabled)
					if($ele_value[8] == 0) {
						foreach($sourceEntryIds as $thisEntryId) {
							if(!$isDisabled) {
								$form_ele->setValue($thisEntryId);
							} else {
								$disabledName = $ele_value[1] ? $form_ele_id."[]" : $form_ele_id;
								$disabledHiddenValue[] = "<input type=hidden name=\"$disabledName\" value=\"$thisEntryId\">";
								$disabledOutputText[] = $cachedSourceValuesQ[$sourceValuesQ][$thisEntryId]; // the text value of the option(s) that are currently selected
							}
						}
					}

					if($isDisabled) {
						$form_ele = new XoopsFormLabel($ele_caption, implode(", ", $disabledOutputText) . implode("\n", $disabledHiddenValue));
						$form_ele->setDescription(html_entity_decode($ele_desc,ENT_QUOTES));
					} elseif($ele_value[8] == 0) {
						// this is a hack because the size attribute is private and only has a getSize and not a setSize, setting the size can only be done through the constructor
					        $count = count( $form_ele->getOptions() );
					        $size = $ele_value[0];
					        $new_size = ( $count < $size ) ? $count : $size;
					        $form_ele->_size = $new_size;
					}
					/* ALTERED - 20100318 - freeform - jeff/julian - stop */
					
				} 
				else // or if we don't have a link...
				{
					$selected = array();
					$options = array();
					$disabledOutputText	= array();
					$disabledHiddenValue = array();
					$disabledHiddenValues = "";
					// add the initial default entry, singular or plural based on whether the box is one line or not.
					if($ele_value[0] == 1)
					{
						$options["none"] = _AM_FORMLINK_PICK;
					}
					
					// set opt_count to 1 if the box is NOT a multiple selection box. -- jwe 7/26/04
					if($ele_value[1])
					{
						$opt_count = 0;
					}
					else
					{
						$opt_count = 1;
					}
					$hiddenOutOfRangeValuesToWrite = array();
					while( $i = each($ele_value[2]) ){
	
						// handle requests for full names or usernames -- will only kick in if there is no saved value (otherwise ele_value will have been rewritten by the loadValues function in the form display
						// note: if the user is about to make a proxy entry, then the list of users displayed will be from their own groups, but not from the groups of the user they are about to make a proxy entry for.  ie: until the proxy user is known, the choice of users for this list can only be based on the current user.  This could lead to confusing or buggy situations, such as users being selected who are outside the groups of the proxy user (who will become the owner) and so there will be an invalid value stored for this element in the db.
						if($i['key'] === "{FULLNAMES}" OR $i['key'] === "{USERNAMES}") { // ADDED June 18 2005 to handle pulling in usernames for the user's group(s)
							if($i['key'] === "{FULLNAMES}") { $nametype = "name"; }
							if($i['key'] === "{USERNAMES}") { $nametype = "uname"; }
							if(isset($ele_value[2]['{OWNERGROUPS}'])) {
								$groups = $ele_value[2]['{OWNERGROUPS}'];
							} else {
								global $regcode;
								if($regcode) { // if we're dealing with a registration code, determine group membership based on the code
									$reggroupsq = q("SELECT reg_codes_groups FROM " . XOOPS_DB_PREFIX . "_reg_codes WHERE reg_codes_code=\"$regcode\"");
									$groups = explode("&8(%$", $reggroupsq[0]['reg_codes_groups']);
									if($groups[0] === "") { unset($groups); } // if a code has no groups associated with it, then kill the null value that will be in position 0 in the groups array.
									$groups[] = XOOPS_GROUP_USERS;
									$groups[] = XOOPS_GROUP_ANONYMOUS;
								} else {
									global $xoopsUser;
									$groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
								}
							}
							$pgroups = array();
							$declaredUsersGroups = $groups;
							if($ele_value[3]) {
								$scopegroups = explode(",",$ele_value[3]);
								if(!in_array("all", $scopegroups)) {
									$groups = $scopegroups;
								} else { // use all 
									if(!$ele_value[4]) { // really use all (otherwise, we're just going with all user's groups, so existing value of $groups will be okay
										unset($groups);
										global $xoopsDB;
										$allgroupsq = q("SELECT groupid FROM " . $xoopsDB->prefix("groups")); //  . " WHERE groupid != " . XOOPS_GROUP_USERS); // removed exclusion of registered users group March 18 2009, since it doesn't make sense in this situation.  All groups should mean everyone, period.
										foreach($allgroupsq as $thisgid) {
											$groups[] = $thisgid['groupid'];
										}
									} 
								}
							}
							$namelist = gatherNames($groups, $nametype, $ele_value[6], $ele_value[5], $ele_value[4], $declaredUsersGroups);
							foreach($namelist as $auid=>$aname) {
								$options[$auid] = $aname;
							}
						} elseif($i['key'] === "{SELECTEDNAMES}") { // loadValue in formDisplay will create a second option with this key that contains an array of the selected values
							$selected = $i['value'];
						} elseif($i['key'] === "{OWNERGROUPS}") { // do nothing with this piece of metadata that gets set in loadValue, since it's used above
						} else { // regular selection list....
							$options[$opt_count] = $myts->stripSlashesGPC($i['key']);
							if(strstr($i['key'], _formulize_OUTOFRANGE_DATA)) {
								$hiddenOutOfRangeValuesToWrite[$opt_count] = str_replace(_formulize_OUTOFRANGE_DATA, "", $i['key']); // if this is an out of range value, grab the actual value so we can stick it in a hidden element later
							}
							if( $i['value'] > 0 ){
								$selected[] = $opt_count;
							}
							$opt_count++;
						}
					}
	
					$count = count($options);
					$size = $ele_value[0];
					$final_size = ( $count < $size ) ? $count : $size;
	
					$form_ele1 = new XoopsFormSelect(
						$ele_caption,
						$form_ele_id,
						$selected,
						$final_size,	//	size
						$ele_value[1]	  //	multiple
					);
	
					$form_ele1->setExtra("onchange=\"javascript:formulizechanged=1;\" jquerytag='$form_ele_id'");
	
					// must check the options for uitext before adding to the element -- aug 25, 2007
					foreach($options as $okey=>$ovalue) {
						$options[$okey] = formulize_swapUIText($ovalue, $this->_ele->getVar('ele_uitext'));
					}
					$form_ele1->addOptionArray($options);

					if($selected) {
						if(is_array($selected)) {
							$hiddenElementName = $ele_value[1] ? $form_ele1->getName()."[]" : $form_ele1->getName();
							foreach($selected as $thisSelected) {
								$disabledOutputText[] = $options[$thisSelected];
								$disabledHiddenValue[] = "<input type=hidden name=\"$hiddenElementName\" value=\"$thisSelected\">";
							}
						} elseif($ele_value[1]) { // need to keep [] in the hidden element name if multiple values are expected, even if only one is chosen
							$disabledOutputText[] = $options[$selected];
							$disabledHiddenValue[] = "<input type=hidden name=\"".$form_ele1->getName()."[]\" value=\"$selected\">";
						} else {
							$disabledOutputText[] = $options[$selected];
							$disabledHiddenValue[] = "<input type=hidden name=\"".$form_ele1->getName()."\" value=\"$selected\">";
						}
					}
	
					$renderedHoorvs = "";
					if(count($hiddenOutOfRangeValuesToWrite) > 0) {
						foreach($hiddenOutOfRangeValuesToWrite as $hoorKey=>$hoorValue) {
							$thisHoorv = new xoopsFormHidden('formulize_hoorv_'.$true_ele_id.'_'.$hoorKey, $hoorValue);
							$renderedHoorvs .= $thisHoorv->render() . "\n";
							unset($thisHoorv);
						}
					}
	
					if($isDisabled) {
						$disabledHiddenValues = implode("\n", $disabledHiddenValue); // glue the individual value elements together into a set of values
						$renderedElement = implode(", ", $disabledOutputText);
          } elseif($ele_value[8] == 1) {
            // autocomplete construction: make sure that $renderedElement is the final output of this chunk of code
            // write the possible values to a cached file so we can look them up easily when we need them, don't want to actually send them to the browser, since it could be huge, but don't want to replicate all the logic that has already gathered the values for us, each time there's an ajax request
            $cachedOptionsFileName = "formulize_Options_".str_replace(".","",microtime(true));
            formulize_scandirAndClean(XOOPS_ROOT_PATH."/cache/", "formulize_Options_");
            $cachedOptions = fopen(XOOPS_ROOT_PATH."/cache/$cachedOptionsFileName","w");
            fwrite($cachedOptions, "<?php\n\r");
	    $maxLength = 0;
            foreach($options as $id=>$text) {
	      $thisTextLength = strlen($text);
	      $maxLength = $thisTextLength > $maxLength ? $thisTextLength : $maxLength;
              //$quotedText = "\"".str_replace("\"", "\\\"", trim($text))."\"";
              $quotedText = "\"".str_replace("\"", "\\\"", $text)."\"";
              fwrite($cachedOptions,"if(stristr($quotedText, \$term)){ \$found[]='[$quotedText,$id]'; }\n\r");

            }
            fwrite($cachedOptions, "?>");
            fclose($cachedOptions);
            //print_r($selected); print_r($options);
            $defaultSelected = is_array($selected) ? $selected[0] : $selected;
            $renderedComboBox = $this->formulize_renderQuickSelect($form_ele_id, $cachedOptionsFileName, $defaultSelected, $options[$defaultSelected], $maxLength);
            $form_ele2 = new xoopsFormLabel($ele_caption, $renderedComboBox);
            $renderedElement = $form_ele2->render();
					} else { // normal element
						$renderedElement = $form_ele1->render();
					}
					
					$form_ele = new XoopsFormLabel(
						$ele_caption,
						"<nobr>$renderedElement</nobr>\n$renderedHoorvs\n$disabledHiddenValues\n"
					);
					$form_ele->setDescription(html_entity_decode($ele_desc,ENT_QUOTES));
				
				} // end of if we have a link on our hands. -- jwe 7/29/04
				
				// set required validation code
				if($this->_ele->getVar('ele_req') AND !$isDisabled) {
					$eltname = $form_ele_id;
					$eltcaption = $ele_caption;
					$eltmsg = empty($eltcaption) ? sprintf( _FORM_ENTER, $eltname ) : sprintf( _FORM_ENTER, $eltcaption );
					$eltmsg = str_replace('"', '\"', stripslashes( $eltmsg ) );
          if($ele_value[8] == 1) {
						$form_ele->customValidationCode[] = "\nif ( myform.{$eltname}.value == '' ) {\n window.alert(\"{$eltmsg}\");\n myform.{$eltname}_user.focus();\n return false;\n }\n";
          } elseif($ele_value[0] == 1) { 
						$form_ele->customValidationCode[] = "\nif ( myform.{$eltname}.options[0].selected ) {\n window.alert(\"{$eltmsg}\");\n myform.{$eltname}.focus();\n return false;\n }\n";
					} elseif($ele_value[0] > 1) {
						$form_ele->customValidationCode[] = "selection = false;\n";
						$form_ele->customValidationCode[] = "\nfor(i=0;i<myform.{$eltname}.options.length;i++) {\n";
						$form_ele->customValidationCode[] = "if(myform.{$eltname}.options[i].selected) {\n";
						$form_ele->customValidationCode[] = "selection = true;\n";
						$form_ele->customValidationCode[] = "}\n";
						$form_ele->customValidationCode[] = "}\n";
						$form_ele->customValidationCode[] = "if(selection == false) { window.alert(\"{$eltmsg}\");\n myform.{$eltname}.focus();\n return false;\n }\n";
					}
				}
				
				if($isDisabled) {
					$isDisabled = false; // disabled stuff handled here in element, so don't invoke generic disabled handling below (which is only for textboxes and their variations)
				}
				
			break;
			
			case 'checkbox':
				$selected = array();
				$options = array();
				$disabledHiddenValue = array();
				$disabledHiddenValues = "";
				$disabledOutputText = array();
				$opt_count = 1;
				while( $i = each($ele_value) ){
					$options[$opt_count] = $myts->stripSlashesGPC($i['key']);
					if( $i['value'] > 0 ){
						$selected[] = $opt_count;
						$disabledHiddenValue[] = "<input type=hidden name=\"".$form_ele_id."[]\" value=\"$opt_count\">";
					}
					$opt_count++;
				}
				if($this->_ele->getVar('ele_delim') != "") {
					$delimSetting = $this->_ele->getVar('ele_delim');
				} 
				$delimSetting =& $myts->undoHtmlSpecialChars($delimSetting);
				if($delimSetting == "br") { $delimSetting = "<br />"; }
				$hiddenOutOfRangeValuesToWrite = array();
				switch($delimSetting){
					case 'space':
						$form_ele1 = new XoopsFormCheckBox(
							$ele_caption,
							$form_ele_id,
							$selected
						);
						$counter = 0; // counter used for javascript that works with 'Other' box
						while( $o = each($options) ){
							$o = formulize_swapUIText($o, $this->_ele->getVar('ele_uitext'));
							$other = $this->optOther($o['value'], $form_ele_id, $entry, $counter, true);
							if( $other != false ){
								$form_ele1->addOption($o['key'], _formulize_OPT_OTHER.$other);
								if(in_array($o['key'], $selected)) {
									$disabledOutputText[] = _formulize_OPT_OTHER.$other;
								}
							}else{
								$form_ele1->addOption($o['key'], $o['value']);
								if(in_array($o['key'], $selected)) {
									$disabledOutputText[] = $o['value'];
								}
								if(strstr($o['value'], _formulize_OUTOFRANGE_DATA)) {
									$hiddenOutOfRangeValuesToWrite[$o['key']] = str_replace(_formulize_OUTOFRANGE_DATA, "", $o['value']); // if this is an out of range value, grab the actual value so we can stick it in a hidden element later
								}
							}
							$counter++;
						}
						$form_ele1->setExtra(" onchange=\"javascript:formulizechanged=1;\" jquerytag=\"$form_ele_id\" ");
					break;
					default:
						$form_ele1 = new XoopsFormElementTray($ele_caption, $delimSetting);
						$counter = 0; // counter used for javascript that works with 'Other' box
						while( $o = each($options) ){
							$o = formulize_swapUIText($o, $this->_ele->getVar('ele_uitext'));
							$other = $this->optOther($o['value'], $form_ele_id, $entry, $counter, true);
							$t = new XoopsFormCheckBox(
								'',
								$form_ele_id.'[]',
								$selected,
								($other === $false ? "" : $other).$delimSetting
							);
							if($other != false){
								$t->addOption($o['key'], _formulize_OPT_OTHER);
								if(in_array($o['key'], $selected)) {
									$disabledOutputText[] = _formulize_OPT_OTHER;
								}
							}else{
								$t->addOption($o['key'], $o['value']);
								if(in_array($o['key'], $selected)) {
									$disabledOutputText[] = $o['value'];
								}
								if(strstr($o['value'], _formulize_OUTOFRANGE_DATA)) {
									$hiddenOutOfRangeValuesToWrite[$o['key']] = str_replace(_formulize_OUTOFRANGE_DATA, "", $o['value']); // if this is an out of range value, grab the actual value so we can stick it in a hidden element later
								}
							}
							$t->setExtra(" onchange=\"javascript:formulizechanged=1;\" jquerytag=\"$form_ele_id\" ");
							$form_ele1->addElement($t);
							unset($t);
							$counter++;
						}
					break;
				}
				$renderedHoorvs = "";

				if(count($hiddenOutOfRangeValuesToWrite) > 0) {
					foreach($hiddenOutOfRangeValuesToWrite as $hoorKey=>$hoorValue) {
						$thisHoorv = new xoopsFormHidden('formulize_hoorv_'.$true_ele_id.'_'.$hoorKey, $hoorValue);
						$renderedHoorvs .= $thisHoorv->render() . "\n";
						unset($thisHoorv);
					}
				}
				
				if($isDisabled) {
					$disabledHiddenValues = implode("\n", $disabledHiddenValue); // glue the individual value elements together into a set of values
					$renderedElement = implode(", ", $disabledOutputText);
				} else {
					$renderedElement = $form_ele1->render();
				}
				
				$form_ele = new XoopsFormLabel(
					$ele_caption,
					"<nobr>$renderedElement</nobr>\n$renderedHoorvs\n$disabledHiddenValues\n"
				);
				$form_ele->setDescription(html_entity_decode($ele_desc,ENT_QUOTES));
				
				if($this->_ele->getVar('ele_req') AND !$isDisabled) {
					$eltname = $form_ele_id;
					$eltcaption = $ele_caption;
					$eltmsg = empty($eltcaption) ? sprintf( _FORM_ENTER, $eltname ) : sprintf( _FORM_ENTER, $eltcaption );
					$eltmsg = str_replace('"', '\"', stripslashes( $eltmsg ) );
					$form_ele->customValidationCode[] = "selection = true;\n";
					$form_ele->customValidationCode[] = "checkboxes = $('[jquerytag={$eltname}]:checked');\n"; // need to use this made up attribute here, because there is no good way to select the checkboxes using the name or anything else that XOOPS/Impress is giving us!!
					$form_ele->customValidationCode[] = "if(checkboxes.length == 0) { window.alert(\"{$eltmsg}\");\n $('[jquerytag={$eltname}]').focus();\n return false;\n }\n";
				}
			
				if($isDisabled) {
					$isDisabled = false; // disabled stuff handled here in element, so don't invoke generic disabled handling below (which is only for textboxes and their variations)
				}
				
			break;
			
			case 'radio':
			case 'yn':
				$selected = '';
				$disabledHiddenValue = "";
				$options = array();
				$opt_count = 1;
				while( $i = each($ele_value) ){
					switch ($ele_type){
						case 'radio':
							$options[$opt_count] = $myts->stripSlashesGPC($i['key']);
              $options[$opt_count] = $myts->displayTarea($options[$opt_count]);
						break;
						case 'yn':
							$options[$opt_count] = constant($i['key']);
							$options[$opt_count] = $myts->stripSlashesGPC($options[$opt_count]);
						break;
					}
					if( $i['value'] > 0 ){
						$selected = $opt_count;
					}
					$opt_count++;
				}
				if($this->_ele->getVar('ele_delim') != "") {
					$delimSetting = $this->_ele->getVar('ele_delim');
				} 
				$delimSetting =& $myts->undoHtmlSpecialChars($delimSetting);
				if($delimSetting == "br") { $delimSetting = "<br />"; }
				$hiddenOutOfRangeValuesToWrite = array();
				switch($delimSetting){
					case 'space':
						$form_ele1 = new XoopsFormRadio(
							'',
							$form_ele_id,
							$selected
						);
						$counter = 0;
						while( $o = each($options) ){
							$o = formulize_swapUIText($o, $this->_ele->getVar('ele_uitext'));
							$other = $this->optOther($o['value'], $form_ele_id, $entry, $counter);
							if( $other != false ){
								$form_ele1->addOption($o['key'], _formulize_OPT_OTHER.$other);
								if($o['key'] == $selected) {
									$disabledOutputText = _formulize_OPT_OTHER.$other;
								}
							}else{
								$o['value'] = get_magic_quotes_gpc() ? stripslashes($o['value']) : $o['value'];
								$form_ele1->addOption($o['key'], $o['value']);
								if($o['key'] == $selected) {
									$disabledOutputText = $o['value'];
								}
								if(strstr($o['value'], _formulize_OUTOFRANGE_DATA)) {
									$hiddenOutOfRangeValuesToWrite[$o['key']] = str_replace(_formulize_OUTOFRANGE_DATA, "", $o['value']); // if this is an out of range value, grab the actual value so we can stick it in a hidden element later
								}
							}
							$counter++;
						}
						$form_ele1->setExtra("onchange=\"javascript:formulizechanged=1;\"");
					break;
					default:
						$form_ele1 = new XoopsFormElementTray('', $delimSetting);
						$counter = 0;
						while( $o = each($options) ){
							$o = formulize_swapUIText($o, $this->_ele->getVar('ele_uitext'));
							$t = new XoopsFormRadio(
								'',
								$form_ele_id,
								$selected
							);
							$other = $this->optOther($o['value'], $form_ele_id, $entry, $counter);
							if( $other != false ){
								$t->addOption($o['key'], _formulize_OPT_OTHER.$other);
								if($o['key'] == $selected) {
									$disabledOutputText = _formulize_OPT_OTHER.$other;
								}
							}else{
								$o['value'] = get_magic_quotes_gpc() ? stripslashes($o['value']) : $o['value'];
								$t->addOption($o['key'], $o['value']);
								if($o['key'] == $selected) {
									$disabledOutputText = $o['value'];
								}
								if(strstr($o['value'], _formulize_OUTOFRANGE_DATA)) {
									$hiddenOutOfRangeValuesToWrite[$o['key']] = str_replace(_formulize_OUTOFRANGE_DATA, "", $o['value']); // if this is an out of range value, grab the actual value so we can stick it in a hidden element later
								}
							}
							$t->setExtra("onchange=\"javascript:formulizechanged=1;\"");
							$form_ele1->addElement($t);
							unset($t);
							$counter++;
						}
					break;
				}
				$renderedHoorvs = "";
				if(count($hiddenOutOfRangeValuesToWrite) > 0) {
					foreach($hiddenOutOfRangeValuesToWrite as $hoorKey=>$hoorValue) {
						$thisHoorv = new xoopsFormHidden('formulize_hoorv_'.$true_ele_id.'_'.$hoorKey, $hoorValue);
						$renderedHoorvs .= $thisHoorv->render() . "\n";
						unset($thisHoorv);
					}
				}
				if($isDisabled) {
					$disabledHiddenValue = "<input type=hidden name=\"".$form_ele_id."\" value=\"$selected\">\n";
					$renderedElement = $disabledOutputText; // just text for disabled elements
				} else {
					$renderedElement = $form_ele1->render();
				}
				$form_ele = new XoopsFormLabel(
					$ele_caption,
					"<nobr>$renderedElement</nobr>\n$renderedHoorvs\n$disabledHiddenValue\n"
				);
				$form_ele->setDescription(html_entity_decode($ele_desc,ENT_QUOTES));
				
				if($this->_ele->getVar('ele_req') AND !$isDisabled) {
					$eltname = $form_ele_id;
					$eltcaption = $ele_caption;
					$eltmsg = empty($eltcaption) ? sprintf( _FORM_ENTER, $eltname ) : sprintf( _FORM_ENTER, $eltcaption );
					$eltmsg = str_replace('"', '\"', stripslashes( $eltmsg ) );
					$form_ele->customValidationCode[] = "selection = false;\n";
					$form_ele->customValidationCode[] = "if(myform.{$eltname}.length) {\n";
					$form_ele->customValidationCode[] = "for(var i=0;i<myform.{$eltname}.length;i++){\n";
					$form_ele->customValidationCode[] = "if(myform.{$eltname}[i].checked){\n";
					$form_ele->customValidationCode[] = "selection = true;\n";
					$form_ele->customValidationCode[] = "}\n";
					$form_ele->customValidationCode[] = "}\n";
					$form_ele->customValidationCode[] = "}\n";
					$form_ele->customValidationCode[] = "if(selection == false) { window.alert(\"{$eltmsg}\");\n myform.{$eltname}.focus();\n return false;\n }\n";
				}

				if($isDisabled) {
					$isDisabled = false; // disabled stuff handled here in element, so don't invoke generic disabled handling below (which is only for textboxes and their variations)
				}


			break;
			//Marie le 20/04/04
			case 'date':
				/*$jr = substr ($ele_value[0], 0, 2);
				$ms = substr ($ele_value[0], 3, 2);
				$an = substr ($ele_value[0], 6, 4);
				$ele_value[0] = $an.'-'.$ms.'-'.$jr;*/ // code block commented to fix bug in remembering previously entered dates.  -- jwe 7/24/04
				// lines below added/modified to check that the default setting is a valid timestamp, otherwise, send no default value to the date box. -- jwe 9/23/04
				//print "ele_value: ";
				//print_r($ele_value);
				//print "<br>" . strtotime("") . "<br>";
				//print "<br>" . strtotime("now") . "<br>";

				if($ele_value[0] == "" OR $ele_value[0] == "YYYY-mm-dd") // if there's no value (ie: it's blank) ... OR it's the default value because someone submitted a date field without actually specifying a date, that last part added by jwe 10/23/04
				{
						//print "Bad date";
					$form_ele = new XoopsFormTextDateSelect (
						$ele_caption,
						$form_ele_id,
						15,
						""
					);
				}
				else
				{
						//print "good date";
					if (ereg_replace("[^A-Z{}]","", $ele_value[0]) === "{TODAY}") {
						$number = ereg_replace("[^0-9+-]","", $ele_value[0]);
						$timestampToUse = mktime(0, 0, 0, date("m") , date("d")+$number, date("Y"));
					} else {
						$timestampToUse = strtotime($ele_value[0]);
					}
					$form_ele = new XoopsFormTextDateSelect (
						$ele_caption,
						$form_ele_id,
						15,
						$timestampToUse
						//$ele_value[0]
					);
				} // end of check to see if the default setting is for real
				// added validation code - sept 5 2007 - jwe
				if($this->_ele->getVar('ele_req') AND !$isDisabled) {
					$eltname = $form_ele_id;
					$eltcaption = $ele_caption;
					$eltmsg = empty($eltcaption) ? sprintf( _FORM_ENTER, $eltname ) : sprintf( _FORM_ENTER, $eltcaption );
					$eltmsg = str_replace('"', '\"', stripslashes( $eltmsg ) );
					$form_ele->customValidationCode[] = "\nif ( myform.{$eltname}.value == \"\" || myform.{$eltname}.value == \"YYYY-mm-dd\" ) {\n window.alert(\"{$eltmsg}\");\n myform.{$eltname}.focus();\n return false;\n }\n";
				}
			break;
			case 'sep':
				//$ele_value[0] = $myts->displayTarea($ele_value[0]);
				$ele_value[0] = $myts->xoopsCodeDecode($ele_value[0]);
				$form_ele = new XoopsFormLabel(
					$ele_caption,
					$ele_value[0]
				);
			break;
			case 'upload':
				$form_ele = new XoopsFormFile (
					$ele_caption,
					$form_ele_id,
					$ele_value[1]
				);
			break;
			/*
			 * Hack by Félix<INBOX International>
			 * Adding colorpicker form element
			 */
			case 'colorpick':


				if($ele_value[0] == "") // if there's no value (ie: it's blank) ... OR it's the default value because someone submitted a date field without actually specifying a date, that last part added by jwe 10/23/04
				{
					//print "Bad date";
				$form_ele = new XoopsFormColorPicker (
					$ele_caption,
					$form_ele_id,
					""
				);
				
				}
				else
				{
					//print "good date";
				$form_ele = new XoopsFormColorPicker (
					$ele_caption,
					$form_ele_id,
					$ele_value[0]

				);
				
				} // end of check to see if the default setting is for real
			break;
			/*
			 * End of Hack by Félix<INBOX International>
			 * Adding colorpicker form element
			 */
			default:
				if(file_exists(XOOPS_ROOT_PATH."/modules/formulize/class/".$ele_type."Element.php")) {
					$elementTypeHandler = xoops_getmodulehandler($ele_type."Element", "formulize");
					$form_ele = $elementTypeHandler->render($ele_value, $ele_caption, $form_ele_id, $isDisabled, $this->_ele, $entry); // $ele_value as passed in here, $caption, name that we use for the element in the markup, flag for whether it's disabled or not, element object, entry id number that this element belongs to
					if(!$isDisabled AND ($this->_ele->getVar('ele_req') OR $this->_ele->alwaysValidateInputs)) { // if it's not disabled, and either a declared required element according to the webmaster, or the element type itself always forces validation...
						$form_ele->customValidationCode = $elementTypeHandler->generateValidationCode($ele_caption, $form_ele_id, $this->_ele);
					}
					$form_ele->setDescription(html_entity_decode($ele_desc,ENT_QUOTES));
					$isDisabled = false; // the render method must handle providing a disabled output, so as far as the rest of the logic here goes, the element is not disabled but should be rendered as is
					$baseCustomElementObject = $elementTypeHandler->create();
					if($baseCustomElementObject->hasData) {
						$customElementHasData = true;
					}
				} else {
					return false;
				}
			break;
		}
		if(is_object($form_ele) AND !$isDisabled) {
			if($previousEntryUI) {
				$previousEntryUIRendered = "&nbsp;&nbsp;" . $previousEntryUI->render();				
			} else {
				$previousEntryUIRendered = "";
			}
			// $ele_type is the type value...only put in a cue for certain kinds of elements, and definitely not for blank subforms
			if(substr($form_ele_id, 0, 9) != "desubform" AND ($ele_type == "text" OR $ele_type == "textarea" OR $ele_type == "select" OR $ele_type=="radio" OR $ele_type=="checkbox" OR $ele_type=="date" OR $ele_type=="colorpick" OR $ele_type=="yn" OR $customElementHasData)) {
				$elementCue = "\n<input type=\"hidden\" id=\"decue_".trim($form_ele_id,"de_")."\" name=\"decue_".trim($form_ele_id,"de_")."\" value=1>\n";
			} else {
				$elementCue = "";
			}
			$form_ele->setExtra(" onchange=\"javascript:formulizechanged=1;\"");
			$form_ele_new = new xoopsFormLabel($form_ele->getCaption(), $form_ele->render().$previousEntryUIRendered.$elementCue); // reuse caption, put two spaces between element and previous entry UI
			if($ele_desc != "") {
				$ele_desc = html_entity_decode($ele_desc,ENT_QUOTES);
				$ele_desc = $myts->makeClickable($ele_desc);
				$form_ele_new->setDescription($ele_desc);
			}
			$form_ele_new->setName($form_ele_id); // need to set this as the name, in case it is required and then the name will be picked up by any "required" checks that get done and used in the required validation javascript for textboxes
			if(!empty($form_ele->customValidationCode)) {
				$form_ele_new->customValidationCode = $form_ele->customValidationCode;
			}
			if($form_ele->isRequired()) {
				$form_ele_new->setRequired();
			}
			return $form_ele_new;
		} elseif(is_object($form_ele) AND $isDisabled) { // element is disabled
			$form_ele = $this->formulize_disableElement($form_ele, $ele_type, $ele_desc);
			return $form_ele;
		} else { // form ele is not an object...only happens for IBs?
			return $form_ele;
		}
		
	}

	// a function that builds some SQL snippets that we use to properly scope queries related to ensuring the uniqueness of selections in linked selectboxes
	// uniquenessFlag is the ele_value[9] property of the element, that tells us how strict the uniqueness is (per user or per group or neither)
	// id_form is the id of the form where the data resides
	// groups is the list of groups we're using as the membership scope in this case (probably the user's groups, but might not be)
	function addEntryRestrictionSQL($uniquenessFlag, $id_form, $groups) {
		$sql = "";
		global $xoopsUser;
		switch($uniquenessFlag) {
			case 2:
				$sql .= " AND t4.`creation_uid` = ";
				$sql .= $xoopsUser ? $xoopsUser->getVar('uid') : 0;
				break;
			case 3:
				$gperm_handler =& xoops_gethandler('groupperm');
				$groupsThatCanView = $gperm_handler->getGroupIds("view_form", $id_form, getFormulizeModId());
				$groupsToLimitBy = array_intersect($groups, $groupsThatCanView);
				$sql .= " AND EXISTS(SELECT 1 FROM ".$xoopsDB->prefix("formulize_entry_owner_groups")." AS t5 WHERE t5.groupid IN (".implode(", ",$groupsToLimitBy).") AND t5.fid=$id_form AND t5.entry_id=t4.entry_id) ";
				break;
		}
		return $sql;
	}
	

	// THIS FUNCTION COPIED FROM LIASE 1.26, onchange control added
	// JWE -- JUNE 1 2006
	function optOther($s='', $id, $entry, $counter, $checkbox=false){
		global $xoopsModuleConfig, $xoopsDB;
		if( !preg_match('/\{OTHER\|+[0-9]+\}/', $s) ){
			return false;
		}
		// deal with displayElement elements...
		$id_parts = explode("_", $id);
		/* // displayElement elements will be in the format de_{id_req}_{ele_id} (deh?)
		// regular elements will be in the format ele_{ele_id}
		if(count($id_parts) == 3) {
			$ele_id = $id_parts[2];
		} else {
			$ele_id = $id_parts[1];
		}*/
		// NOW, in Formulize 3, id_parts[3] will always be the element id. :-)
		$ele_id = $id_parts[3];
		
		// gather the current value if there is one
		$other_text = "";
		if(is_numeric($entry)) {
			$otherq = q("SELECT other_text FROM " . $xoopsDB->prefix("formulize_other") . " WHERE id_req='$entry' AND ele_id='$ele_id' LIMIT 0,1");
			$other_text = $otherq[0]['other_text'];
		}
		$s = explode('|', preg_replace('/[\{\}]/', '', $s));
		$len = !empty($s[1]) ? $s[1] : $xoopsModuleConfig['t_width'];
		$box = new XoopsFormText('', 'other[ele_'.$ele_id.']', $len, 255, $other_text);
		if($checkbox) {
			$box->setExtra("onchange=\"javascript:formulizechanged=1;\" onfocus=\"javascript:this.form.elements['" . $id . "[]'][$counter].checked = true;\"");
		} else {
			$box->setExtra("onchange=\"javascript:formulizechanged=1;\" onfocus=\"javascript:this.form." . $id . "[$counter].checked = true;\"");
		}
		return $box->render();
	}

  // replace { } terms with data handle values from the current entry, if any exist
	function formulize_replaceCurlyBracketVariables($text, $entry, $id_form) {
		if(strstr($text, "}") AND strstr($text, "{")) {
			$entryData = $this->formulize_getCachedEntryData($id_form, $entry);
			$bracketPos = -1;
			$start = true; // flag used to force the loop to execute, even if the 0th position has the {
			while($bracketPos = strpos($text, "{", $bracketPos+1) OR $start == true) {
				$start = false;
        $endBracketPos = strpos($text, "}", $bracketPos+1);
				$term = substr($text, $bracketPos+1, $endBracketPos-$bracketPos-1);
				$replacementTerm = display($entryData, $term);
				if($replacementTerm !== "") {
					// get the uitext value if necessary
					$element_handler = xoops_getmodulehandler('elements', 'formulize');
					$elementObject = $element_handler->get($term);
					$replacementTerm = formulize_swapUIText($replacementTerm, $elementObject->getVar('ele_uitext'));
				} else {
					$replacementTerm = "{".$term."}";
				}
				$text = str_replace("{".$term."}",$replacementTerm,$text);
				$bracketPos = $bracketPos + strlen($replacementTerm); // move ahead the length of what we replaced
      }
		}
		return $text;
	}

	// gather an entry when required...this should really be abstracted out to the data handler class, which also needs a proper getter in a handler of its own, so we don't keep creating new instances of the data handler and it can store the cached info about entries that we want it to.
	function formulize_getCachedEntryData($id_form, $entry) {
		static $cachedEntryData = array();
		if($entry === "new") {
			return array();
		}
		if(!isset($cachedEntryData[$id_form][$entry])) {
			$cachedEntryData[$id_form][$entry] = getData("", $id_form, $entry);
		}
		return $cachedEntryData[$id_form][$entry][0];
	}

	/* ALTERED - 20100318 - freeform - jeff/julian - start */
	function formulize_renderQuickSelect($form_ele_id, $cachedLinkedOptionsFilename, $default_value='', $default_value_user='none', $maxLength=30) {
		$maxLength = $maxLength > 50 ? 50 : $maxLength; // don't create giant boxes, too disruptive to the layout...though we should probably give the users a way to override this!  They can use the class attribute assigned to the 'user' box below, and CSS.
		static $autocompleteIncluded = false;
		if(!$autocompleteIncluded) {
			// quickselect-formulize has a change in it so that "none" is an allowed value for matches, so that we can give the user good UI when something wrong is happening
			$output = "<!-- Dependencies - note: quickselect-formulize has a change in it so that \"none\" is an allowed value for matches, so that we can give the user good UI when something wrong is happening -->\n";

			if( ! defined( "FORMULIZE_DONT_INCLUDE_JQUERY" ) )
				$output .= "<script type=\"text/javascript\" src=\"".XOOPS_URL."/modules/formulize/libraries/jquery/jquery-1.4.2.min.js\"></script>\n";

			$output .= "<script type=\"text/javascript\" src=\"".XOOPS_URL."/modules/formulize/libraries/jquery/quicksilver.js\"></script>\n
<script type=\"text/javascript\" src=\"".XOOPS_URL."/modules/formulize/libraries/jquery/jquery.quickselect-formulize.min.js\"></script>\n
<link rel=\"stylesheet\" type=\"text/css\" media=\"all\" href=\"".XOOPS_URL."/modules/formulize/libraries/jquery/css/jquery.quickselect.css\"/>\n";
		}
		$autocompleteIncluded = true;

		$output .= "<div class=\"formulize_autocomplete\" style=\"padding-right: 10px;\"><input type='text' class='formulize_autocomplete' name='${form_ele_id}_user' id = '${form_ele_id}_user' autocomplete='on' value='".str_replace("'", "&#039;", $default_value_user)."' size='$maxLength' /></div>";
		$output .= "<input type='hidden' name='${form_ele_id}' id = '${form_ele_id}' value='$default_value' />";
		$output .= "<script type='text/javascript'>";
		// need to declare this as jQuery so that everything "just works" when being called in an environment where $ is owned by something else
		// need to wrap it in window.load because Chrome does unusual things with the DOM and makes it ready before it's populated with content!!  (so document.ready doesn't do the trick)

		// item 16 determines whether the list box allows new values to be entered
        $ele_value = $this->_ele->getVar('ele_value');
        $allow_new_values = isset($ele_value[16]) ? $ele_value[16] : 0;
		$output .= 'jQuery(window).load(function() { ';
		$output .= "if( window.on_".$form_ele_id." )\n";
		$output .= 'jQuery(function(){jQuery("#'.$form_ele_id.'_user'.'").quickselect({ajax: "'.XOOPS_URL.'/modules/formulize/include/formulize_quickselect.php",ajaxParams:{cache:"'.$cachedLinkedOptionsFilename.'", allow_new_values:"'.$allow_new_values.'"},maxVisibleItems:12,additionalFields: jQuery("#'.$form_ele_id.'"),onItemSelect: on_'.$form_ele_id.'});});';
		$output .= "\nelse\n";
		$output .= 'jQuery(function(){jQuery("#'.$form_ele_id.'_user'.'").quickselect({ajax: "'.XOOPS_URL.'/modules/formulize/include/formulize_quickselect.php",ajaxParams:{cache:"'.$cachedLinkedOptionsFilename.'", allow_new_values:"'.$allow_new_values.'"},maxVisibleItems:12,additionalFields: jQuery("#'.$form_ele_id.'")});});';
		$output .= '});';
		$output .= "\n</script>";

		return $output;
	}
	/* ALTERED - 20100318 - freeform - jeff/julian - stop */

	// creates a hidden version of the element so that it can pass its value back, but not be available to the user
	
	function formulize_disableElement($element, $type, $ele_desc) {
		if($type == "text" OR $type == "textarea" OR $type == "date" OR $type == "colorpick") {
			$newElement = new xoopsFormElementTray($element->getCaption(), "\n");
			$newElement->setName($element->getName());
			switch($type) {
				case 'date':
					$hiddenValue = date("Y-m-d", $element->getValue());
					break;
				default:
					$hiddenValue = $element->getValue(); // should work for all elements, since non-textbox type elements where the value would not be passed straight back, are handled differently at the time they are constructed
			}
			if(is_array($hiddenValue)) { // not sure when/if this would ever happen
				foreach($hiddenValue as $value) {
					$newElement->addElement(new xoopsFormHidden($element->getName()."[]", $value));
					unset($value);
				}
				$newElement->addElement(new xoopsFormLabel('', implode(", ", $hiddenValue)));
			} else {
				$newElement->addElement(new xoopsFormHidden($element->getName(), $hiddenValue));
				$newElement->addElement(new xoopsFormLabel('', $hiddenValue));
			}
			if(substr($element->getName(), 0, 9) != "desubform") { // we should consider not having a cue at all for any disabled elements, but we're not going to pull it out just yet...more investigation of this is necessary
				$newElement->addElement(new xoopsFormHidden("decue_".trim($element->getName(),"de_"), 1));
			}
			$newElement->setDescription(html_entity_decode($ele_desc,ENT_QUOTES));
			return $newElement;
		} else {
			return $element;
		}
	}


	// this function creates the previous values drop down that people can use to set the value of an element
	// screen is the screen object with the data we need (form id with previous entries and rule for lining them up with current form)
	// element_id is the ID of the element we're drawing (add ele_ to the front to make the javascript ID we need to know in order to set the value of the element to the one the user selects)
	// type is the type of element, which affects how the javascript is written (textboxes aren't set the same as radio buttons, etc)
	function formulize_setupPreviousEntryUI($screen, $element_id, $type, $owner, $de=false, $entryId="", $ele_handle, $fid) {
		
		// 1. need to get and cache the values of the entry for this screen
		// 2. need to put the values into a dropdown list with an onchange event that populates the actual form element
		// this should be cached in some other way, since every instance of the renderer will need to cache this.  If it were a GLOBAL or this whole thing were in some other function, that would work.
		static $cachedEntries = array();
		if(!isset($cachedEntries[$screen->getVar('sid')])) {
			// identify the entry belonging to this user's group(s) in the other form.  Currently only group correspondence is supported.
			global $xoopsUser;
			$groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
			$member_handler =& xoops_gethandler('member');
			$gperm_handler =& xoops_gethandler('groupperm');
			$mid = getFormulizeModId();
			$owner_groups =& $member_handler->getGroupsByUser($owner, FALSE); // in this particular case, it's okay to make the owner_groups based on the users's memberships, since we want to present the single entry that belongs to whichever groups the user is a member of...I think.  :-)
			$singleData = getSingle($screen->getVar('paraentryform'), $owner, $owner_groups, $member_handler, $gperm_handler, $mid);
			if($singleData['flag'] == "group" AND $singleData['entry'] > 0) { // only proceed if there is a one-entry-per-group situation in the target form
				formulize_benchmark("Ready to do previous entry query.");
				$cachedEntries[$screen->getVar('sid')] = getData("", $screen->getVar('paraentryform'), $singleData['entry']);
				formulize_benchmark("Done query.");
			} else {
				return "";
			}
		}
		$entries = $cachedEntries[$screen->getVar('sid')]; 
		
		// big assumption below is corresponding captions.  In future there will be more ad hoc ways of describing which elements align to which other ones.
		// 1. figure out the corresponding element ID based on matching captions
		// 2. grab the previous value from the $entry/entries
		// 3. create the dropdown list with these values, including javascript
		
		$formHandler =& xoops_getmodulehandler('forms', 'formulize');
		$currentForm = $formHandler->get($screen->getVar('fid'));
		$previousForm = $formHandler->get($screen->getVar('paraentryform'));
		$currentCaptions = $currentForm->getVar('elementCaptions');
		$captionToMatch = $currentCaptions[$ele_handle];
		$previousCaptions = $previousForm->getVar('elementCaptions');
		$previousElementHandle = array_search($captionToMatch, $previousCaptions);
		if(!$previousElementHandle) { return ""; }
		$elementName = $de ? "de_".$fid."_".$entryId."_".$element_id : "ele_".$element_id; // displayElement elements have different names from regular elements
		$previousElementId = formulize_getIdFromElementHandle($previousElementHandle); // function is in extract.php
		// setup the javascript based on the type of question, and setup other data that is required
		switch($type) {
			case "text":
			case "date":
				$javascript = "onchange='javascript:this.form.".$elementName.".value=this.form.prev_".$element_id.".value;'";
				break;
			case "radio":
				// need to get the options of the question so we know what to match
				$prevElementMetaData = formulize_getElementMetaData($previousElementId); // use this function in extract instead of the get element method in handler, since this is guaranteed to be already be cached in memory
				$prevElement_ele_value = unserialize($prevElementMetaData['ele_value']);
				$prevElementOptions = array_keys($prevElement_ele_value);
				$javascript = "onchange='javascript:if(this.form.prev_".$element_id.".value !== \"\") { this.form.".$elementName."[this.form.prev_".$element_id.".value].checked=true; }'";
				break;
			case "yn":
				$javascript = "onchange='javascript:if(this.form.prev_".$element_id.".value !== \"\") { this.form.".$elementName."[this.form.prev_".$element_id.".value].checked=true; }'";
				break;
		}
		$previousOptions = array();
		$prevOptionsExist = false;
		foreach($entries as $id=>$entry) {
			$value = htmlspecialchars(strip_tags(display($entry, $previousElementHandle)));
			if(is_array($value)) {
				$value = printSmart(implode(", ", $value));
			}
			if(trim($value) === "" OR trim($value) == "0000-00-00") { continue; }
			$prevOptionsExist = true;
			switch($type) {
				case "text":
				case "date":
					$previousOptions[$value] = $value;
					break;
				case "radio":
					$prevElementPosition = array_search($value, $prevElementOptions); // need to figure out which option matches the text of the value
					if($prevElementPosition !== false) {
						$previousOptions[$prevElementPosition] = $value; // for radio buttons, we need to pass the position of the option
					}
					break;
				case "yn":
					if($value == _formulize_TEMP_QYES) {
						$previousOptions[0] = $value;
					} elseif($value == _formulize_TEMP_QNO) {
						$previousOptions[1] = $value;
					}
					break;
					
			}
		}
		if(!$prevOptionsExist) { return ""; }
		$prevUI = new xoopsFormSelect('', 'prev_'.$element_id, '123qweasdzxc', 1, false); // 123qweasdzxc is meant to be a unique value that will never be selected, since we don't ever want a previous selection showing by default
		$prevUI->addOption('', _AM_FORMULIZE_PREVIOUS_OPTION);
		$prevUI->addOptionArray($previousOptions);
		$prevUI->setExtra($javascript);
		return $prevUI;
	}

}
?>
