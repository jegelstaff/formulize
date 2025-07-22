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

	function __construct(&$element){
		$this->_ele =& $element;
	}

	// function params modified to accept passing of $ele_value from index.php
	// $entry added June 1 2006 as part of 'Other' option for radio buttons and checkboxes (now $entry_id)
    // $ele_value is the prepared ele_value that has had the existing value of the element loaded into it as if that were a default!
    // ele_value from the element object is unchanged
	function constructElement($renderedElementMarkupName, $ele_value, $entry_id, $isDisabled=false, $screen=null, $validationOnly=false){
        global $xoopsDB;
        $wasDisabled = false; // yuck, see comment below when this is reassigned
		if (strstr(getCurrentURL(),"printview.php")) {
			$isDisabled = true; // disabled all elements if we're on the printable view
		}
		global $xoopsUser, $xoopsModuleConfig, $separ, $myts;
		$myts = MyTextSanitizer::getInstance();

		if(strstr($renderedElementMarkupName, "de_")) { // display element uses a slightly different element name so it can be distinguished on subsequent page load from regular elements...THIS IS NOT TRUE/NECESSARY ANYMORE SINCE FORMULIZE 3, WHERE ALL ELEMENTS ARE DISPLAY ELEMENTS
			$true_ele_id = str_replace("de_".$this->_ele->getVar('id_form')."_".$entry_id."_", "", $renderedElementMarkupName);
			$displayElementInEffect = true;
		} else {
			$true_ele_id = str_replace("ele_", "", $renderedElementMarkupName);
			$displayElementInEffect = false;
		}


		// added July 6 2005.
		if(!$xoopsModuleConfig['delimeter']) {
			// assume that we're accessing a form from outside the Formulize module, therefore the Formulize delimiter setting is not available, so we have to query for it directly.
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

		$ele_caption = $this->formulize_replaceCurlyBracketVariables($ele_caption, $entry_id, $id_form, $renderedElementMarkupName);

		// ele_desc added June 6 2006 -- jwe
		$ele_desc = $this->_ele->getVar('ele_desc', "f"); // the f causes no stupid reformatting by the ICMS core to take place
		$helpText = $ele_desc != "" ? $this->formulize_replaceCurlyBracketVariables($myts->makeClickable(html_entity_decode($ele_desc,ENT_QUOTES)), $entry_id, $id_form, $renderedElementMarkupName) : "";

		// determine the entry owner
		if($entry_id != "new") {
					$owner = getEntryOwner($entry_id, $id_form);
		} else {
					$owner = $xoopsUser ? $xoopsUser->getVar('uid') : 0;
		}

		// setup the previous entry UI if necessary -- this is an option that can be specified for certain screens
		$previousEntryUI = "";
		if($screen AND $ele_type != "derived") {
			if($screen->getVar('paraentryform') > 0) {
				$previousEntryUI = $this->formulize_setupPreviousEntryUI($screen, $true_ele_id, $ele_type, $owner, $displayElementInEffect, $entry_id, $this->_ele->getVar('ele_handle'), $this->_ele->getVar('id_form'));
			}
		}

		$form_handler = xoops_getmodulehandler('forms', 'formulize');
		$formObject = $form_handler->get($id_form, true); // true includes all elements even if they're not displayed

		switch ($ele_type){
			case 'derived':
				if($entry_id != "new") {
					$form_ele = new xoopsFormLabel($this->_ele->getVar('ele_caption'), $ele_value[5], $renderedElementMarkupName);
				} else {
					$form_ele = new xoopsFormLabel($this->_ele->getVar('ele_caption'), _formulize_VALUE_WILL_BE_CALCULATED_AFTER_SAVE, $renderedElementMarkupName);
				}
				$form_ele->setDescription($helpText);
				break;


			case 'ib':
				if(trim($ele_value[0]) == "") { $ele_value[0] = $ele_caption; }
				$ele_value[0] = $this->formulize_replaceCurlyBracketVariables($ele_value[0], $entry_id, $id_form, $renderedElementMarkupName);
				if(strstr($ele_value[0], "\$value=") OR strstr($ele_value[0], "\$value =")) {
					$form_id = $id_form;
					$entry = $this->formulize_getCachedEntryData($id_form, $entry_id);
					$creation_datetime = getValue($entry, "creation_datetime");
					$entryData = $entry; // alternate variable name for backwards compatibility
					$ele_value[0] = removeOpeningPHPTag($ele_value[0]);
					$value = ""; // will be set in eval
					$evalResult = eval($ele_value[0]);
					if($evalResult === false) {
						$ele_value[0] = _formulize_ERROR_IN_LEFTRIGHT;
					} else {
						$ele_value[0] = $value; // value is supposed to be the thing set in the eval'd code
						$ele_value[0] = $this->formulize_replaceCurlyBracketVariables($ele_value[0], $entry_id, $id_form, $renderedElementMarkupName); // in case PHP code generated some { } references
					}
				}
				$form_ele = $ele_value; // an array, item 0 is the contents of the break, item 1 is the class of the table cell (for when the form is table rendered)
				break;

			case 'textarea':
				$ele_value[0] = (isset($ele_value[0]) AND $ele_value[0]) ? stripslashes($ele_value[0]) : "";
//        $ele_value[0] = $myts->displayTarea($ele_value[0]); // commented by jwe 12/14/04 so that info displayed for viewing in a form box does not contain HTML formatting
				$ele_value[0] = interpretTextboxValue($this->_ele, $entry_id, $ele_value[0]);
				if (!strstr(getCurrentURL(),"printview.php") AND !$isDisabled) { 				// nmc 2007.03.24 - added
					if(isset($ele_value['use_rich_text']) AND $ele_value['use_rich_text']) {
						include_once XOOPS_ROOT_PATH."/class/xoopsform/formeditor.php";
						$form_ele = new XoopsFormEditor(
							$ele_caption,
							'CKEditor',
							$editor_configs = array("name"=>$renderedElementMarkupName, "value"=>$ele_value[0]),
							$noHtml=false,
							$OnFailure = ""
						);

                        if($this->_ele->getVar('ele_required')) {
                            $eltname = $renderedElementMarkupName;
                            $eltcaption = $ele_caption;
                            $eltmsg = empty($eltcaption) ? sprintf( _FORM_ENTER, $eltname ) : sprintf( _FORM_ENTER, strip_tags(htmlspecialchars_decode($eltcaption, ENT_QUOTES)));
                            $eltmsg = str_replace('"', '\"', stripslashes($eltmsg));
                            $form_ele->customValidationCode[] = "var getText = CKEditors['".$eltname."_tarea'].getData();\n";
                            $form_ele->customValidationCode[] = "var StripTag = getText.replace(/(<([^>]+)>)/ig,''); \n";
                            $form_ele->customValidationCode[] = "if(StripTag=='' || StripTag=='&nbsp;') {\n";
                            $form_ele->customValidationCode[] = "window.alert(\"{$eltmsg}\");\n CKEditors['".$eltname."_tarea'].focus();\n return false;\n";
                            $form_ele->customValidationCode[] = "}\n";
                        }

						$GLOBALS['formulize_CKEditors'][] = $renderedElementMarkupName.'_tarea';

					} else {
					$form_ele = new XoopsFormTextArea(
						$ele_caption,
						$renderedElementMarkupName,
						$ele_value[0],	//	default value
						$ele_value[1],	//	rows
						$ele_value[2]	  //	cols
					);
					}
				} else {															// nmc 2007.03.24 - added
					$form_ele = new XoopsFormLabel ($ele_caption, str_replace("\n", "<br>", undoAllHTMLChars($ele_value[0], ENT_QUOTES)), $renderedElementMarkupName);	// nmc 2007.03.24 - added
				}
			break;


			case 'areamodif':
				$ele_value[0] = $this->formulize_replaceCurlyBracketVariables($ele_value[0], $entry_id, $id_form, $renderedElementMarkupName);
				if(strstr($ele_value[0], "\$value=") OR strstr($ele_value[0], "\$value =")) {
					$form_id = $id_form;
					$entry = $this->formulize_getCachedEntryData($id_form, $entry_id);
					$creation_datetime = getValue($entry, "creation_datetime");
					$entryData = $entry; // alternate variable name for backwards compatibility
					$ele_value[0] = removeOpeningPHPTag($ele_value[0]);
					$value = ""; // will be set in eval
					$evalResult = eval($ele_value[0]);
					if($evalResult === false) {
						$ele_value[0] = _formulize_ERROR_IN_LEFTRIGHT;
					} else {
						$ele_value[0] = $value; // value is supposed to be the thing set in the eval'd code
						$ele_value[0] = $this->formulize_replaceCurlyBracketVariables($ele_value[0], $entry_id, $id_form, $renderedElementMarkupName); // just in case PHP might have added { } refs
					}
				}
				$form_ele = new XoopsFormLabel(
					$ele_caption,
					$ele_value[0],
          $renderedElementMarkupName
				);
			break;


			case 'select':

                $element_handler = xoops_getmodulehandler('elements', 'formulize');

				if(is_string($ele_value[2]) and strstr($ele_value[2], "#*=:*")) // if we've got a link on our hands... -- jwe 7/29/04
				{
					// new process for handling links...May 10 2008...new datastructure for formulize 3.0
					$boxproperties = explode("#*=:*", $ele_value[2]);
					$sourceFid = $boxproperties[0];
					$sourceHandle = $boxproperties[1];
					$sourceEntryIds = $ele_value['snapshot'] == 0 ? explode(",", trim($boxproperties[2],",")) : array(); // if we snapshot values, then there are no entry ids
                    $snapshotValues = $ele_value['snapshot'] == 1 ? explode("*=+*:", trim($boxproperties[2], "*=+*:")) : array(); // if we snapshot values, then put them into an array

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

                    $ele_value['formlink_useonlyusersentries'] = isset($ele_value['formlink_useonlyusersentries']) ? $ele_value['formlink_useonlyusersentries'] : 0;
                    $pgroupsfilter = prepareLinkedElementGroupFilter($sourceFid, $ele_value[3], $ele_value[4], $ele_value[6], $ele_value['formlink_useonlyusersentries']);

					$sourceFormObject = $form_handler->get($sourceFid);

					list($conditionsfilter, $conditionsfilter_oom, $parentFormFrom) = buildConditionsFilterSQL($ele_value[5], $sourceFid, $entry_id, $owner, $formObject, "t1");
					catalogDynamicFilterConditionElements($renderedElementMarkupName, $ele_value[5], $formObject);

					// if there is a restriction in effect, then add some SQL to reject options that have already been selected ??
					$restrictSQL = "";
					if($ele_value[9]) {
					 $t4_ele_value = $this->_ele->getVar('ele_value');
                     if($t4_ele_value[1]) { // allows multiple selections

						$restrictSQL = " AND (
						NOT EXISTS (
						SELECT 1 FROM ".$xoopsDB->prefix("formulize_".$formObject->getVar('form_handle'))." AS t4 WHERE t4.`".$this->_ele->getVar('ele_handle')."` LIKE CONCAT( '%,', t1.`entry_id` , ',%' ) AND t4.entry_id != ".intval($entry_id);
						} else {
                                                    $restrictSQL = " AND (
                                                    NOT EXISTS (
                                                    SELECT 1 FROM ".$xoopsDB->prefix("formulize_".$formObject->getVar('form_handle'))." AS t4 WHERE t4.`".$this->_ele->getVar('ele_handle')."` = t1.`entry_id` AND t4.entry_id != ".intval($entry_id);
                                                    $restrictSQL .= $this->addEntryRestrictionSQL($ele_value[9], $id_form, $groups); // pass in the flag about restriction scope, and the form id, and the groups
                                                    $restrictSQL .= " ) OR EXISTS (
                                                    SELECT 1 FROM ".$xoopsDB->prefix("formulize_".$formObject->getVar('form_handle'))." AS t4 WHERE t4.`".$this->_ele->getVar('ele_handle')."` = t1.`entry_id` AND t4.entry_id = ".intval($entry_id);

                                                }


						$restrictSQL .= $this->addEntryRestrictionSQL($ele_value[9], $id_form, $groups); // pass in the flag about restriction scope, and the form id, and the groups
						$restrictSQL .= " ) OR EXISTS (
						SELECT 1 FROM ".$xoopsDB->prefix("formulize_".$formObject->getVar('form_handle'))." AS t4 WHERE t4.`".$this->_ele->getVar('ele_handle')."` LIKE CONCAT( '%,', t1.`entry_id` , ',%' ) AND t4.entry_id = ".intval($entry_id);
						$restrictSQL .= $this->addEntryRestrictionSQL($ele_value[9], $id_form, $groups);
						$restrictSQL .= ") )";
					}

                    static $cachedSourceValuesQ = array();
                    static $cachedSourceValuesAutocompleteFile = array();
                    // horrible hack to handle cases where new subform entries are created and we need to flush values that would have been generated when we were fake making the page before we knew a new subform entry is what we were really aiming for. See comment where global is instantiated.
                    // all comes from not having proper controller in charge of what we should be displaying. Ugh.
                    if(isset($GLOBALS['formulize_unsetSelectboxCaches'])) {
                        //formulize_benchmark('unsetting caches!');
                        $cachedSourceValuesQ = array();
                        $cachedSourceValuesAutocompleteFile = array();
                        unset($GLOBALS['formulize_unsetSelectboxCaches']);
                    }

					// setup the sort order based on ele_value[12], which is an element id number
					$sortOrder = $ele_value[15] == 2 ? " DESC" : "ASC";
					if($ele_value[12]=="none" OR !$ele_value[12]) {
						$sortOrderClause = " ORDER BY t1.`$sourceHandle` $sortOrder";
					} else {
						list($sortHandle) = convertElementIdsToElementHandles(array($ele_value[12]), $sourceFormObject->getVar('id_form'));
						$sortOrderClause = " ORDER BY t1.`$sortHandle` $sortOrder";
					}

					// if no extra elements are selected for display as a form element, then display the linked element
					if (!is_array($ele_value[EV_MULTIPLE_FORM_COLUMNS]) OR 0 == count((array) $ele_value[EV_MULTIPLE_FORM_COLUMNS]) OR $ele_value[EV_MULTIPLE_FORM_COLUMNS][0] == 'none') {
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

					list($sourceEntrySafetyNetStart, $sourceEntrySafetyNetEnd) = prepareLinkedElementSafetyNets($sourceEntryIds);

					$extra_clause = prepareLinkedElementExtraClause($pgroupsfilter, $parentFormFrom, $sourceEntrySafetyNetStart);

					// if we're supposed to limit based on the values in an arbitrary other element, add those to the clause too
					$directLimit = '';
					$dbValue = '';
					if(isset($ele_value['optionsLimitByElement']) AND is_numeric($ele_value['optionsLimitByElement'])) {
						if($optionsLimitByElement_ElementObject = $element_handler->get($ele_value['optionsLimitByElement'])) {
							$dbValue = '';
							if(isset($GLOBALS['formulize_asynchronousFormDataInDatabaseReadyFormat'][$entry_id][$optionsLimitByElement_ElementObject->getVar('ele_handle')])) {
								$dbValue = $GLOBALS['formulize_asynchronousFormDataInDatabaseReadyFormat'][$entry_id][$optionsLimitByElement_ElementObject->getVar('ele_handle')];
							} else {
								list($optionsLimitFilter, $optionsLimitFilter_oom, $optionsLimitFilter_parentFormFrom) = buildConditionsFilterSQL($ele_value['optionsLimitByElementFilter'], $optionsLimitByElement_ElementObject->getVar('id_form'), $entry_id, $owner, $formObject, "olf");
								catalogDynamicFilterConditionElements($renderedElementMarkupName, $ele_value['optionsLimitByElementFilter'], $formObject);
								$optionsLimitFilterFormObject = $form_handler->get($optionsLimitByElement_ElementObject->getVar('id_form'));
								$sql = "SELECT ".$optionsLimitByElement_ElementObject->getVar('ele_handle')." FROM ".$xoopsDB->prefix('formulize_'.$optionsLimitFilterFormObject->getVar('form_handle'))." as olf $optionsLimitFilter_parentFormFrom WHERE 1 $optionsLimitFilter $optionsLimitFilter_oom";
								if($res = $xoopsDB->query($sql)) {
									if($xoopsDB->getRowsNum($res)==1) {
										$row = $xoopsDB->fetchRow($res);
										$dbValue = $row[0];
									}
								}
							}
							$directLimit = convertEntryIdsFromDBToArray($dbValue);
						}
					}
					if($directLimit) {
						$directLimit = ' AND t1.entry_id IN ('.implode(',',$directLimit).') ';
					}

					$selfReferenceExclusion = generateSelfReferenceExclusionSQL($entry_id, $id_form, $sourceFid, $ele_value, 't1');

					// $extra_clause will always include WHERE, must come first
					// all "AND" clauses come after that, through to $sourceEntrySafetyNetEnd
					// $sourceEntrySafetyNetEnd concludes the "AND" clauses, and starts the "OR" clauses, which must always come last
					// all clauses must be self contained, which means in ( ) if they have multiple parts, and must be introduced with AND if in the first section, or OR if in the last section (after $sourceEntrySafetyNetEnd)
					$sourceValuesQ = "SELECT t1.entry_id, $select_column
						FROM ".$xoopsDB->prefix("formulize_".$sourceFormObject->getVar('form_handle'))." AS t1
						$extra_clause
						$conditionsfilter
						$conditionsfilter_oom
						$restrictSQL
						$directLimit
						$selfReferenceExclusion
						$sourceEntrySafetyNetEnd
						GROUP BY t1.entry_id $sortOrderClause, t1.entry_id ASC ";

					if(!$isDisabled) {
						// set the default selections, based on the entry_ids that have been selected as the defaults, if applicable
						$hasNoValues = count((array) $sourceEntryIds) == 0 ? true : false;
						$useDefaultsWhenEntryHasNoValue = $ele_value[14];
						if(($entry_id == "new" OR ($useDefaultsWhenEntryHasNoValue AND $hasNoValues)) AND ((is_array($ele_value[13]) AND count((array) $ele_value[13]) > 0) OR $ele_value[13])) {
							$defaultSelected = $ele_value[13];
						} else {
							$defaultSelected = "";
						}
						$form_ele = new XoopsFormSelect($ele_caption, $renderedElementMarkupName, $defaultSelected, $ele_value[0], $ele_value[1]);
						$form_ele->setExtra("onchange=\"javascript:formulizechanged=1;\" jquerytag='$renderedElementMarkupName'");
						if($ele_value[0] == 1) { // add the initial default entry, singular or plural based on whether the box is one line or not.
							$form_ele->addOption("none", _AM_FORMLINK_PICK);
						}
					} else {
						$disabledHiddenValue = array();
						$disabledOutputText = array();
					}

					if(!isset($cachedSourceValuesQ[intval($ele_value['snapshot'])][$sourceValuesQ])) {

            $linkedElementOptions = array();
						$reslinkedvaluesq = $xoopsDB->query($sourceValuesQ);
						if($reslinkedvaluesq) {
							$linked_column_count = count((array) $linked_columns);
							while($rowlinkedvaluesq = $xoopsDB->fetchRow($reslinkedvaluesq)) {
								$linked_column_values = array();
								foreach (range(1, $linked_column_count) as $linked_column_index) {
                  $linked_value = '';
									if ($rowlinkedvaluesq[$linked_column_index] !== "") {
										$linked_value = prepvalues($rowlinkedvaluesq[$linked_column_index], $linked_columns[$linked_column_index - 1], $rowlinkedvaluesq[0]);
										$linked_value = $linked_value[0];
									}
                  if($linked_value != '' OR is_numeric($linked_value)) {
                  	$linked_column_values[] = $linked_value;
                  }
								}
                if(count((array) $linked_column_values)>0) {
									$leoIndex = $ele_value['snapshot'] ? implode(" | ", $linked_column_values) : $rowlinkedvaluesq[0];
									$linkedElementOptions[$leoIndex] = implode(" | ", $linked_column_values);
                }
              }
						}
						// in case there are duplicate options, and there's a selected value that is a duplicate, then preserve the duplicate value rather than the first duplicate in the list
						// do this by removing duplicate values from the list, other than the one that was selected
						// convoluted process preserves ordering of the array
						if(count((array) $sourceEntryIds) > 0) {
							foreach($sourceEntryIds as $sei) {
                $targetKeys = array_keys($linkedElementOptions, $linkedElementOptions[$sei]);
								foreach($targetKeys as $tk) {
									if($sei != $tk) {
										unset($linkedElementOptions[$tk]);
									}
								}
							}
						}
						$linkedElementOptions = array_unique($linkedElementOptions); // remove duplicates
						$cachedSourceValuesQ[intval($ele_value['snapshot'])][$sourceValuesQ] = $linkedElementOptions;

						/* ALTERED - 20100318 - freeform - jeff/julian - start */
						if(!$isDisabled AND $ele_value[8] == 1) {
							// write the possible values to a cached file so we can look them up easily when we need them, don't want to actually send them to the browser, since it could be huge, but don't want to replicate all the logic that has already gathered the values for us, each time there's an ajax request
							$cachedLinkedOptionsFileName = "formulize_linkedOptions_".str_replace(".","",microtime(true));
							formulize_scandirAndClean(XOOPS_ROOT_PATH."/cache/", "formulize_linkedOptions_");
							$the_values = array();
							asort($linkedElementOptions);
							foreach($linkedElementOptions as $id=>$text) {
								$the_values[$id] = undoAllHTMLChars(trans($text));
							}
							file_put_contents(XOOPS_ROOT_PATH."/cache/$cachedLinkedOptionsFileName",
								"<?php\n\$$cachedLinkedOptionsFileName = ".var_export($the_values, true).";\n");
							$cachedSourceValuesAutocompleteFile[intval($ele_value['snapshot'])][$sourceValuesQ] = $cachedLinkedOptionsFileName;
						}
					}

          $default_value = array();
					if(count((array) $sourceEntryIds) > 0) {
						$default_value = $sourceEntryIds;
						//$default_value_user = $cachedSourceValuesQ[intval($ele_value['snapshot'])][$sourceValuesQ][$boxproperties[2]];
					} elseif(count((array) $snapshotValues) > 0) {
          	$default_value = $snapshotValues;
					}
					// if we're rendering an autocomplete box
					if(!$isDisabled AND $ele_value[8] == 1) {
						foreach($default_value as $dv) {
							$default_value_user[$dv] = count((array) $snapshotValues) > 0 ? $dv : $cachedSourceValuesQ[intval($ele_value['snapshot'])][$sourceValuesQ][$dv]; // take the literal or the reference, depending if we snapshot or not
						}
						$renderedComboBox = $this->formulize_renderQuickSelect($renderedElementMarkupName, $cachedSourceValuesAutocompleteFile[intval($ele_value['snapshot'])][$sourceValuesQ], $default_value, $default_value_user, $ele_value[1]);
						$form_ele = new xoopsFormLabel($ele_caption, $renderedComboBox, $renderedElementMarkupName);
						$form_ele->setDescription($helpText);

          // if we're rendering a disabled autocomplete box
					} elseif($isDisabled AND $ele_value[8] == 1) {
						if($ele_value['snapshot'] == 1) {
							$disabledOutputText = $snapshotValues;
						} else {
							foreach($default_value as $dv) {
								$disabledOutputText[] = $cachedSourceValuesQ[intval($ele_value['snapshot'])][$sourceValuesQ][$dv];
							}
						}
					}

					// rendering a non-autocomplete box
					if($ele_value[8] == 0) {
                        if(!$isDisabled) {
    						$form_ele->addOptionArray($cachedSourceValuesQ[intval($ele_value['snapshot'])][$sourceValuesQ]);
    					}
						foreach($default_value as $thisDV) {
							if(!$isDisabled) {
								$form_ele->setValue($thisDV);
							} else {
								$disabledOutputText[] = $cachedSourceValuesQ[intval($ele_value['snapshot'])][$sourceValuesQ][$thisDV]; // the text value of the option(s) that are currently selected
							}
						}
					}

                    $GLOBALS['formulize_lastRenderedElementOptions'] = $cachedSourceValuesQ[intval($ele_value['snapshot'])][$sourceValuesQ];

                    if($isDisabled) {
						$form_ele = new XoopsFormLabel($ele_caption, implode(", ", $disabledOutputText), $renderedElementMarkupName);
						$form_ele->setDescription($helpText);
					} elseif($ele_value[8] == 0) {
						// this is a hack because the size attribute is private and only has a getSize and not a setSize, setting the size can only be done through the constructor
					        $count = count((array)  $form_ele->getOptions() );
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
                    if(is_array($ele_value[2])) {
                        foreach($ele_value[2] as $iKey=>$iValue) {
                            $i = array('key'=>$iKey, 'value'=>$iValue); // kinda ugly compatibility hack to refactor the really ugly use of 'each' for PHP 8
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

                                $directLimitUserIds = false;
                                if(isset($ele_value['optionsLimitByElement']) AND is_numeric($ele_value['optionsLimitByElement'])) {
                                    if($optionsLimitByElement_ElementObject = $element_handler->get($ele_value['optionsLimitByElement'])) {
                                        list($optionsLimitFilter, $optionsLimitFilter_oom, $optionsLimitFilter_parentFormFrom) = buildConditionsFilterSQL($ele_value['optionsLimitByElementFilter'], $optionsLimitByElement_ElementObject->getVar('id_form'), $entry_id, $owner, $formObject, "olf");
																				catalogDynamicFilterConditionElements($renderedElementMarkupName, $ele_value['optionsLimitByElementFilter'], $formObject);
                                        $optionsLimitFilterFormObject = $form_handler->get($optionsLimitByElement_ElementObject->getVar('id_form'));
                                        $sql = "SELECT ".$optionsLimitByElement_ElementObject->getVar('ele_handle')." FROM ".$xoopsDB->prefix('formulize_'.$optionsLimitFilterFormObject->getVar('form_handle'))." as olf $optionsLimitFilter_parentFormFrom WHERE 1 $optionsLimitFilter $optionsLimitFilter_oom";
                                        if($res = $xoopsDB->query($sql)) {
                                            if($xoopsDB->getRowsNum($res)==1) {
                                                $row = $xoopsDB->fetchRow($res);
                                                $elementObjectEleValue = $optionsLimitByElement_ElementObject->getVar('ele_value');
                                                if(is_array($elementObjectEleValue[2]) AND strstr(implode('',array_keys($elementObjectEleValue[2])), "NAMES}")) {
                                                    $directLimitUserIds = explode("*=+*:",trim($row[0], "*=+*:"));
                                                }
                                            }
                                        }
                                    }
                                }

                                foreach($namelist as $auid=>$aname) {
                                    if($directLimitUserIds AND !in_array($auid, $directLimitUserIds)) { continue; }
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
                    }

					$count = count((array) $options);
					$size = $ele_value[0];
					$final_size = ( $count < $size ) ? $count : $size;

					$form_ele1 = new XoopsFormSelect(
						$ele_caption,
						$renderedElementMarkupName,
						$selected,
						$final_size,	//	size
						$ele_value[1]	  //	multiple
					);

					$form_ele1->setExtra("onchange=\"javascript:formulizechanged=1;\" jquerytag='$renderedElementMarkupName'");

					// must check the options for uitext before adding to the element -- aug 25, 2007
					foreach($options as $okey=>$ovalue) {
						$options[$okey] = formulize_swapUIText($ovalue, $this->_ele->getVar('ele_uitext'));
					}
					$form_ele1->addOptionArray($options);
                    $GLOBALS['formulize_lastRenderedElementOptions'] = $options;

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
					if(count((array) $hiddenOutOfRangeValuesToWrite) > 0) {
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
						// write the possible values to a cached file so we can look them up easily when we need them,
						//don't want to actually send them to the browser, since it could be huge,
						//but don't want to replicate all the logic that has already gathered the values for us, each time there's an ajax request
						$cachedLinkedOptionsFileName = "formulize_Options_".str_replace(".","",microtime(true));
						formulize_scandirAndClean(XOOPS_ROOT_PATH."/cache/", "formulize_Options_");
						$the_values = array();
						foreach($options as $id => $text) {
							$the_values[$id] = undoAllHTMLChars(trans($text));
						}
						file_put_contents(XOOPS_ROOT_PATH."/cache/$cachedLinkedOptionsFileName",
							"<?php\n\$$cachedLinkedOptionsFileName = ".var_export($the_values, true).";\n");
						$defaultSelected = is_array($selected) ? $selected[0] : $selected;
                        $defaultSelectedUser = $options[$defaultSelected];
                        if(is_array($selected) AND $ele_value[1]) { // multiselect autocompletes work differently, send all values
                            $defaultSelected = $selected;
                            $defaultSelectedUser = array();
                            foreach($selected as $thisSel) {
                                $defaultSelectedUser[$thisSel] = $options[$thisSel];
                            }
                            natsort($defaultSelectedUser);
                        }
                        $defaultSelected = !is_array($defaultSelected) ? array($defaultSelected) : $defaultSelected;
                        $defaultSelectedUser = !is_array($defaultSelectedUser) ? array($defaultSelectedUser) : $defaultSelectedUser;
						$renderedComboBox = $this->formulize_renderQuickSelect($renderedElementMarkupName, $cachedLinkedOptionsFileName, $defaultSelected, $defaultSelectedUser, $ele_value[1]);
						$form_ele2 = new xoopsFormLabel($ele_caption, $renderedComboBox, $renderedElementMarkupName);
						$renderedElement = $form_ele2->render();
					} else { // normal element
						$renderedElement = $form_ele1->render();
					}

					$form_ele = new XoopsFormLabel(
						$ele_caption,
						"<nobr>$renderedElement</nobr>\n$renderedHoorvs\n$disabledHiddenValues\n",
						$renderedElementMarkupName
					);
					$form_ele->setDescription($helpText);

				} // end of if we have a link on our hands. -- jwe 7/29/04

				// set required validation code
				if($this->_ele->getVar('ele_required') AND !$isDisabled) {
					$eltname = $renderedElementMarkupName;
					$eltcaption = $ele_caption;
					$eltmsg = empty($eltcaption) ? sprintf( _FORM_ENTER, $eltname ) : sprintf( _FORM_ENTER, strip_tags(htmlspecialchars_decode($eltcaption, ENT_QUOTES)));
					$eltmsg = str_replace('"', '\"', stripslashes( $eltmsg ) );
					if($ele_value[8] == 1) {// Has been edited in order to not allow the user to submit a form when "No match found" or "Choose an Option" is selected from the quickselect box.
						if($ele_value[1]) {
							$form_ele->customValidationCode[] = "\nif ( window.document.getElementsByName('{$eltname}[]').length == 0 ) {\n window.alert(\"{$eltmsg}\");\n myform.{$eltname}_user.focus();\n return false;\n }\n";
						} else {
							$form_ele->customValidationCode[] = "\nif ( myform.{$eltname}.value == '' || myform.{$eltname}.value == 'none'  ) {\n window.alert(\"{$eltmsg}\");\n myform.{$eltname}_user.focus();\n return false;\n }\n";
            }
					} elseif($ele_value[0] == 1) {
						$form_ele->customValidationCode[] = "\nif ( myform.{$eltname}.options[0].selected && myform.{$eltname}.options[0].value == 'none') {\n window.alert(\"{$eltmsg}\");\n myform.{$eltname}.focus();\n return false;\n }\n";
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
                    $wasDisabled = true; // the worst kind of spaghetti code!!! We need to rationalize the handling of cue elements, so that disabled elements never get cues!
					$isDisabled = false; // disabled stuff handled here in element, so don't invoke generic disabled handling below (which is only for textboxes and their variations)
				}
			break;

      case 'radio':
			case 'yn':
				$selected = '';
				$disabledHiddenValue = "";
				$options = array();
				$opt_count = 1;
                foreach($ele_value as $iKey=>$iValue) {
					switch ($ele_type){
						case 'radio':
                            $options[$opt_count] = $myts->displayTarea($iKey, 1); // 1 means allow HTML through
						break;
						case 'yn':
							$options[$opt_count] = constant($iKey);
						break;
					}
					if( $iValue > 0 ){
						$selected = $opt_count;
					}
					$opt_count++;
				}
				if($this->_ele->getVar('ele_delim') != "") {
					$delimSetting = $this->_ele->getVar('ele_delim');
				}
				$delimSetting = $myts->undoHtmlSpecialChars($delimSetting);
				if($delimSetting == "br") { $delimSetting = "<br />"; }
				$hiddenOutOfRangeValuesToWrite = array();
				switch($delimSetting){
					case 'space':
						$form_ele1 = new XoopsFormRadio(
							'',
							$renderedElementMarkupName,
							$selected
						);
						$counter = 0;
                        foreach($options as $oKey=>$oValue) {
							$oValue = formulize_swapUIText($oValue, $this->_ele->getVar('ele_uitext'));
							$other = optOther($oValue, $renderedElementMarkupName, $entry_id, $counter, false, $isDisabled);
							if( $other != false ){
								$form_ele1->addOption($oKey, _formulize_OPT_OTHER.$other);
								if($oKey == $selected) {
									$disabledOutputText = _formulize_OPT_OTHER.$other;
								}
							}else{
								$form_ele1->addOption($oKey, $oValue);
								if($oKey == $selected) {
									$disabledOutputText = $oValue;
								}
								if(strstr($oValue, _formulize_OUTOFRANGE_DATA)) {
									$hiddenOutOfRangeValuesToWrite[$oKey] = str_replace(_formulize_OUTOFRANGE_DATA, "", $oValue); // if this is an out of range value, grab the actual value so we can stick it in a hidden element later
								}
							}
							$counter++;
						}
						$form_ele1->setExtra("onchange=\"javascript:formulizechanged=1;\"");
                        $GLOBALS['formulize_lastRenderedElementOptions'] = $form_ele1->getOptions();
					break;


					default:
						$form_ele1 = new XoopsFormElementTray('', $delimSetting);
						$counter = 0;
                        foreach($options as $oKey=>$oValue) {
							$oValue = formulize_swapUIText($oValue, $this->_ele->getVar('ele_uitext'));
							$t = new XoopsFormRadio(
								'',
								$renderedElementMarkupName,
								$selected
							);
							$other = optOther($oValue, $renderedElementMarkupName, $entry_id, $counter, false, $isDisabled);
							if( $other != false ){
								$t->addOption($oKey, _formulize_OPT_OTHER."</label><label>$other"); // epic hack to terminate radio button's label so it doesn't include the clickable 'other' box!!
								if($oKey == $selected) {
									$disabledOutputText = _formulize_OPT_OTHER.$other;
								}
                                $GLOBALS['formulize_lastRenderedElementOptions'][$oKey] = _formulize_OPT_OTHER;
							}else{
								$t->addOption($oKey, $oValue);
								if($oKey == $selected) {
									$disabledOutputText = $oValue;
								}
								if(strstr($oValue, _formulize_OUTOFRANGE_DATA)) {
									$hiddenOutOfRangeValuesToWrite[$oKey] = str_replace(_formulize_OUTOFRANGE_DATA, "", $oValue); // if this is an out of range value, grab the actual value so we can stick it in a hidden element later
								}
                                $GLOBALS['formulize_lastRenderedElementOptions'][$oKey] = $oValue;
							}
							$t->setExtra("onchange=\"javascript:formulizechanged=1;\"");
							$form_ele1->addElement($t);
							unset($t);
							$counter++;
						}
					break;
				}
				$renderedHoorvs = "";
				if(count((array) $hiddenOutOfRangeValuesToWrite) > 0) {
					foreach($hiddenOutOfRangeValuesToWrite as $hoorKey=>$hoorValue) {
						$thisHoorv = new xoopsFormHidden('formulize_hoorv_'.$true_ele_id.'_'.$hoorKey, $hoorValue);
						$renderedHoorvs .= $thisHoorv->render() . "\n";
						unset($thisHoorv);
					}
				}
				if($isDisabled) {
					$disabledHiddenValue = "<input type=hidden name=\"".$renderedElementMarkupName."\" value=\"$selected\">\n";
					$renderedElement = $disabledOutputText; // just text for disabled elements
				} else {
					$renderedElement = $form_ele1->render();
                    if($this->_ele->getVar('ele_forcehidden')) {
                        $renderedElement .= "\n$renderedHoorvs\n$disabledHiddenValues\n";
                    }
				}
				$form_ele = new XoopsFormLabel(
					$ele_caption,
					trans($renderedElement),
					$renderedElementMarkupName
				);
				$form_ele->setDescription($helpText);

				if($this->_ele->getVar('ele_required') AND !$isDisabled) {
					$eltname = $renderedElementMarkupName;
					$eltcaption = $ele_caption;
					$eltmsg = empty($eltcaption) ? sprintf( _FORM_ENTER, $eltname ) : sprintf( _FORM_ENTER, strip_tags(htmlspecialchars_decode($eltcaption, ENT_QUOTES)));
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
                    $wasDisabled = true; // the worst kind of spaghetti code!!! We need to rationalize the handling of cue elements, so that disabled elements never get cues!
					$isDisabled = false; // disabled stuff handled here in element, so don't invoke generic disabled handling below (which is only for textboxes and their variations)
				}
			break;


            case 'date':
                // if there's no value (ie: it's blank) ... OR it's the default value because someone submitted a date field without actually specifying a date, that last part added by jwe 10/23/04
                if(!$ele_value[0] OR $ele_value[0] == _DATE_DEFAULT) {
                    $form_ele = new XoopsFormTextDateSelect($ele_caption, $renderedElementMarkupName, 15, "");
                    $form_ele->setExtra(" onchange=\"javascript:formulizechanged=1;\" jquerytag=\"$renderedElementMarkupName\" ");
                } else {
                    $form_ele = new XoopsFormTextDateSelect($ele_caption, $renderedElementMarkupName, 15, getDateElementDefault($ele_value[0], $entry_id));
                    $form_ele->setExtra(" onchange=\"javascript:formulizechanged=1;\" jquerytag=\"$renderedElementMarkupName\" ");
                } // end of check to see if the default setting is for real
				// added validation code - sept 5 2007 - jwe
				if($this->_ele->getVar('ele_required') AND !$isDisabled) {
					$eltname = $renderedElementMarkupName;
					$eltcaption = $ele_caption;
					$eltmsg = empty($eltcaption) ? sprintf( _FORM_ENTER, $eltname ) : sprintf( _FORM_ENTER, strip_tags(htmlspecialchars_decode($eltcaption, ENT_QUOTES)));
					$eltmsg = str_replace('"', '\"', stripslashes( $eltmsg ) );
					// parseInt() is used to determine if the element value contains a number
					// Date.parse() would be better, except that it will fail for dd-mm-YYYY format, ie: 22-11-2013
					$form_ele->customValidationCode[] = "\nif (isNaN(parseInt(myform.{$eltname}.value))) {\n window.alert(\"{$eltmsg}\");\n myform.{$eltname}.focus();\n return false;\n }\n";
				}
                if (!$isDisabled) {
                    $limit_past = (isset($ele_value["date_past_days"]) and $ele_value["date_past_days"] != "");
                    $limit_future = (isset($ele_value["date_future_days"]) and $ele_value["date_future_days"] != "");
                    if ($limit_past or $limit_future) {
                        if($limit_past AND $pastSeedDate = getDateElementDefault($ele_value["date_past_days"], $entry_id)) {
                            $form_ele->setExtra(" min='".date('Y-m-d', $pastSeedDate)."' ");
                        }
                        if($limit_future AND $futureSeedDate = getDateElementDefault($ele_value["date_future_days"], $entry_id)) {
                            $form_ele->setExtra(" max='".date('Y-m-d', $futureSeedDate)."' ");
                        }
                        $form_ele->setExtra(" onchange=\"javascript:formulizechanged=1;check_date_limits('$renderedElementMarkupName');\" onclick=\"javascript:check_date_limits('$renderedElementMarkupName');\" onblur=\"javascript:check_date_limits('$renderedElementMarkupName');\" jquerytag=\"$renderedElementMarkupName\" ");
                    } else {
                        $form_ele->setExtra(" onchange=\"javascript:formulizechanged=1;\" jquerytag=\"$renderedElementMarkupName\" ");
                    }
                }
			break;

			/*
			 * Hack by F�lix<INBOX International>
			 * Adding colorpicker form element
			 */
			case 'colorpick':
				$ele_value[0] = $ele_value[0] ? $ele_value[0] : "#FFFFFF";
				$form_ele = new XoopsFormColorPicker (
					$ele_caption,
					$renderedElementMarkupName,
					$ele_value[0]
				);

			break;


			/*
			 * End of Hack by F�lix<INBOX International>
			 * Adding colorpicker form element
			 */
			default:
				if(file_exists(XOOPS_ROOT_PATH."/modules/formulize/class/".$ele_type."Element.php")) {
					$elementTypeHandler = xoops_getmodulehandler($ele_type."Element", "formulize");
					$form_ele = $elementTypeHandler->render($ele_value, $ele_caption, $renderedElementMarkupName, $isDisabled, $this->_ele, $entry_id, $screen, $owner); // $ele_value as passed in here, $caption, name that we use for the element in the markup, flag for whether it's disabled or not, element object, entry id number that this element belongs to, $screen is the screen object that was passed in, if any
					// if form_ele is an array, then we want to treat it the same as an "insertbreak" element, ie: it's not a real form element object
					if(is_object($form_ele)) {
						if(!$isDisabled AND ($this->_ele->getVar('ele_required') OR $this->_ele->alwaysValidateInputs) AND $this->_ele->hasData) { // if it's not disabled, and either a declared required element according to the webmaster, or the element type itself always forces validation...
							$form_ele->customValidationCode = $elementTypeHandler->generateValidationCode($ele_caption, $renderedElementMarkupName, $this->_ele, $entry_id);
						}
						$form_ele->setDescription($helpText);
						$wasDisabled = $isDisabled; // Ack!! see spaghetti code comments with $wasDisabled elsewhere
						$isDisabled = false; // the render method must handle providing a disabled output, so as far as the rest of the logic here goes, the element is not disabled but should be rendered as is
						$baseCustomElementObject = $elementTypeHandler->create();
						if($baseCustomElementObject->hasData) {
							$customElementHasData = true;
						}
					}
				} else {
					return false;
				}
			break;
		} // end element-type case

		// if element is object, with data, not disabled, let's get it ready for rendering
		// by rendering everything now, and sticking it in a clean "label" element
		if(is_object($form_ele) AND !$isDisabled AND $this->_ele->hasData) {

			// put in cue if element has data we should be handling on save
			$elementCue = "";
			if(substr($renderedElementMarkupName, 0, 9) != "desubform"
				AND !$isDisabled
				AND !$wasDisabled
				AND ($ele_type == "textarea"
					OR $ele_type == "select"
					OR $ele_type=="radio"
					OR $ele_type=="date"
					OR $ele_type=="colorpick"
					OR $ele_type=="yn"
					OR $customElementHasData)) {
				$elementCue = "\n<input type=\"hidden\" id=\"decue_".trim($renderedElementMarkupName,"de_")."\" name=\"decue_".trim($renderedElementMarkupName,"de_")."\" value=1>\n";
			}

			// put in special validation logic, if the element has special validation logic
			// hard coded for dara to start with
			$specialValidationLogicDisplay = "";
			if(strstr(getCurrentURL(), 'dara.daniels') AND $true_ele_id == 88) {
					$GLOBALS['formulize_specialValidationLogicHook'][$renderedElementMarkupName] = $true_ele_id;
					$specialValidationLogicDisplay = "&nbsp;&nbsp;<span id='va_".trim($renderedElementMarkupName,"de_")."'></span>";
			}

			$previousEntryUIRendered = $previousEntryUI ? "&nbsp;&nbsp;" . $previousEntryUI->render() : "";

			$form_ele->setExtra(" onchange=\"javascript:formulizechanged=1;\"");
			$form_ele_new = new xoopsFormLabel($form_ele->getCaption(), $form_ele->render().$previousEntryUIRendered.$specialValidationLogicDisplay.$elementCue, $renderedElementMarkupName);

			$form_ele_new->setName($renderedElementMarkupName); // need to set this as the name, in case it is required and then the name will be picked up by any "required" checks that get done and used in the required validation javascript for textboxes
			if(!empty($form_ele->customValidationCode)) {
				$form_ele_new->customValidationCode = $form_ele->customValidationCode;
			}
			if($form_ele->isRequired()) {
				$form_ele_new->setRequired();
			}

		// else if element is an old "classic" element (no custom class) and is disabled (elements with custom class are not disabled at this point because their render method must handle disabling and so the disabled flag is off on those by now)
		} elseif(is_object($form_ele) AND $isDisabled AND $this->_ele->hasData) {
			$form_ele_new = $this->formulize_disableElement($form_ele, $this->_ele->getVar('ele_type'));

		// else form_ele is not an object...and/or has no data.  Happens for IBs and for non-interactive elements, like grids.
		} else {
			if(is_object($form_ele)) {
					$form_ele->formulize_element = $this->_ele;
			}
			return $form_ele;
		}

		// if we haven't returned yet, element was an object with data, so do final prep of the element and return it
		$form_ele_new->formulize_element = $this->_ele;
		if($helpText) {
			$form_ele_new->setDescription($helpText);
		}
		return $form_ele_new;
	}


	// a function that builds some SQL snippets that we use to properly scope queries related to ensuring the uniqueness of selections in linked selectboxes
	// uniquenessFlag is the ele_value[9] property of the element, that tells us how strict the uniqueness is (per user or per group or neither)
	// id_form is the id of the form where the data resides
	// groups is the list of groups we're using as the membership scope in this case (probably the user's groups, but might not be)
	function addEntryRestrictionSQL($uniquenessFlag, $id_form, $groups) {
		$sql = "";
		global $xoopsUser, $xoopsDB;
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

  // replace { } terms with data handle values from the current entry, if any exist
	function formulize_replaceCurlyBracketVariables($text, $entry_id, $id_form, $renderedElementMarkupName='') {
		if(strstr($text, "}") AND strstr($text, "{")) {
			$entryData = $this->formulize_getCachedEntryData($id_form, $entry_id);
      $element_handler = xoops_getmodulehandler('elements', 'formulize');
			$bracketPos = 0;
			$start = true; // flag used to force the loop to execute, even if the 0th position has the {
			while($bracketPos <= strlen($text) AND $bracketPos = strpos($text, "{", $bracketPos) OR $start == true) {
				$start = false;
        $endBracketPos = strpos($text, "}", $bracketPos+1);
				$term = substr($text, $bracketPos+1, $endBracketPos-$bracketPos-1);
				$elementObject = $element_handler->get($term);
				if($elementObject) {
					if(isset($GLOBALS['formulize_asynchronousFormDataInAPIFormat'][$entry_id][$term])) {
						$replacementTerm = $GLOBALS['formulize_asynchronousFormDataInAPIFormat'][$entry_id][$term];
					} else {
           	$replacementTerm = getValue($entryData, $term, localEntryId: $entry_id);
					}
					// get the uitext value if necessary
					$replacementTerm = formulize_swapUIText($replacementTerm, $elementObject->getVar('ele_uitext'));
					$replacementTerm = formulize_numberFormat($replacementTerm, $term);
					$text = str_replace("{".$term."}",$replacementTerm,$text);
					$lookAhead = strlen($replacementTerm); // move ahead the length of what we replaced
					if($renderedElementMarkupName) {
						catalogConditionalElement($renderedElementMarkupName,array($elementObject->getVar('ele_handle')));
					}
				} else {
					$lookAhead = 1;
				}
				$bracketPos = $bracketPos + $lookAhead;
			}
		}
		return $text;
	}

	// gather an entry when required...this should really be abstracted out to the data handler class, which also needs a proper getter in a handler of its own, so we don't keep creating new instances of the data handler and it can store the cached info about entries that we want it to.
	function formulize_getCachedEntryData($id_form, $entry_id) {
		static $cachedEntryData = array();
		if(!is_numeric($entry_id) OR $entry_id < 1) {
            return array();
		}
		if(!isset($cachedEntryData[$id_form][$entry_id])) {
			$cachedEntryData[$id_form][$entry_id] = gatherDataset($id_form, filter: $entry_id, frid: 0);
		}
		return $cachedEntryData[$id_form][$entry_id][0];
	}

	function formulize_renderQuickSelect($renderedElementMarkupName, $cachedLinkedOptionsFilename, $default_value, $default_value_user, $multiple = 0) {

        if($multiple) {
            global $easiestml_lang;
            $selectedValues = $default_value_user;
            $default_value_user = '';
            $frenchSpace = $easiestml_lang == 'fr' ? '&nbsp;' : '';
            $multipleClass = 'formulize_autocomplete_multiple';
        } else {
            $selectedValues = '';
            $default_value_user = $default_value_user[key($default_value_user)];
            $multipleClass = '';
        }

        // put markup for autocomplete boxes here



        $output = "<div class=\"formulize_autocomplete\"><input type='text' class='formulize_autocomplete $multipleClass' name='".$renderedElementMarkupName."_user' id = '".$renderedElementMarkupName."_user' autocomplete='off' value='".str_replace("'", "&#039;", $default_value_user)."' aria-describedby='".$renderedElementMarkupName."-help-text' /></div><img src='".XOOPS_URL."/modules/formulize/images/magnifying_glass.png' class='autocomplete-icon'>\n";
        $output .= "<div id='".$renderedElementMarkupName."_defaults'>\n";
        if(!$multiple) {
            $output .= "<input type='hidden' name='".$renderedElementMarkupName."' id = '".$renderedElementMarkupName."' value='".$default_value[0]."' />\n";
        } else {
            $output .= "<input type='hidden' name='last_selected_".$renderedElementMarkupName."' id = 'last_selected_".$renderedElementMarkupName."' value='' />\n";
            foreach($default_value as $i=>$this_default_value) {
                if($this_default_value OR $this_default_value === 0) {
                    $output .= "<input type='hidden' name='".$renderedElementMarkupName."[]' jquerytag='".$renderedElementMarkupName."' id='".$renderedElementMarkupName."_".$i."' target='".str_replace("'", "&#039;", $i)."' value='".str_replace("'", "&#039;", $this_default_value)."' />\n";
                }
            }
        }
        $output .= '</div>';
        if(is_array($selectedValues) OR $multiple) {
            $output .= '<div id="'.$renderedElementMarkupName.'_formulize_autocomplete_selections" class="formulize_autocomplete_selections" style="padding-right: 10px;">';
            foreach($selectedValues as $id=>$value) {
                if($value OR $value === 0) {
                    $output .= "<p class='auto_multi auto_multi_".$renderedElementMarkupName."' target='".str_replace("'", "&#039;", $id)."'>".str_replace("'", "&#039;", $value)."</p>\n";
                }
            }
            $output .= "</div>\n";
        }

        // jQuery code for make it work as autocomplete
        // need to wrap it in window.load because Chrome does unusual things with the DOM and makes it ready before it's populated with content!!  (so document.ready doesn't do the trick)
        // item 16 determines whether the list box allows new values to be entered

        $ele_value = $this->_ele->getVar('ele_value');
        $allow_new_values = isset($ele_value[16]) ? $ele_value[16] : 0;
        // setup the autocomplete, and make it pass the value of the selected item into the hidden element
        // note reference to master jQuery not jq3 in order to cause the change event to affect the normal scope of javascript in the page. Very funky!
        $output .= "<script type='text/javascript'>

        function formulize_initializeAutocomplete".$renderedElementMarkupName."() {
            ";
            // if it's a single quickselect with an existing value, don't clear the initial value if the user just focuses and blurs
            if(!$multiple AND $default_value_user) {
                $output .= $renderedElementMarkupName."_clearbox = false;\n";
            } else {
                // for all other quickselects, clear whatever the user might have typed, if it isn't a matching value to a valid option
                $output .= $renderedElementMarkupName."_clearbox = true;\n";
            }
            $output .= "
            jQuery('#".$renderedElementMarkupName."_user').autocomplete({
								source: function(request, response) {
									var excludeCurrentSelection = jQuery('input[name=\"".$renderedElementMarkupName."[]\"]').map(function () { return $(this).val(); }).get().join(',');
									jQuery.get('".XOOPS_URL."/modules/formulize/include/formulize_quickselect.php?cache=".$cachedLinkedOptionsFilename."&allow_new_values=".$allow_new_values."&term='+encodeURIComponent(request.term)+'&current='+encodeURIComponent(excludeCurrentSelection), function(data) {
										response(eval(data));
									})},
                minLength: 1,
                delay: 0,
                select: function(event, ui) {
                    event.preventDefault();
                    if(ui.item.value != 'none') {
                        jQuery('#".$renderedElementMarkupName."_user').val(ui.item.label.replace('"._formulize_NEW_VALUE."', ''));
                        setAutocompleteValue('".$renderedElementMarkupName."', ui.item.value, 1, ".$multiple.");
                        ".$renderedElementMarkupName."_clearbox = false;
                    } else {
                        jQuery('#".$renderedElementMarkupName."_user').val('');
                        setAutocompleteValue('".$renderedElementMarkupName."', ui.item.value, 1, ".$multiple.");
                    }
                },
                focus: function( event, ui ) {
                    event.preventDefault();
                    if(ui.item.value != 'none') {
                        var itemLabel = ui.item.label;
                        var itemLabelPrefix = itemLabel.substr(0, ".strlen(_formulize_NEW_VALUE).");
                        if(itemLabelPrefix == '"._formulize_NEW_VALUE."') {
                            itemLabel = itemLabel.substr(".strlen(_formulize_NEW_VALUE).");
                        }
                        jQuery('#".$renderedElementMarkupName."_user').val(itemLabel);
                        setAutocompleteValue('".$renderedElementMarkupName."', ui.item.value, 0, ".$multiple.");
                        ".$renderedElementMarkupName."_clearbox = false;
                    } else {
                        setAutocompleteValue('".$renderedElementMarkupName."', ui.item.value, 0, ".$multiple.");
                    }
                },
                search: function(event, ui) {
                    ".$renderedElementMarkupName."_clearbox = true;
                }";
                if($allow_new_values) {
                    // if we allow new values and the first (and therefore only) response is a new value item, then mark that for saving right away without selection by user
                    $output .= ",
                    response: function(event, ui) {
												if(ui.content.length == 1 && typeof ui.content[0].value === 'string' && ui.content[0].value.indexOf('newvalue:')>-1) {
                            setAutocompleteValue('".$renderedElementMarkupName."', ui.content[0].value, 0, ".$multiple.");
                            ".$renderedElementMarkupName."_clearbox = false;
                        }
                    }";
                }
                if($multiple) {
                    $output .= ",
                    close: function(event, ui) {
                        value = jQuery('#last_selected_".$renderedElementMarkupName."').val();
                        label = jQuery('#".$renderedElementMarkupName."_user').val();
                        label = label.replace('"._formulize_NEW_VALUE."', '');
                        if(value != 'none' && (value || value === 0)) {
                            if(isNaN(value)) {
                                value = String(value).replace(/'/g,\"&#039;\");
                                i = value;
                            } else {
                            		i = parseInt(jQuery('#".$renderedElementMarkupName."_defaults').children().last().attr('target')) + 1;
                            }
                            jQuery('#".$renderedElementMarkupName."_defaults').append(\"<input type='hidden' name='".$renderedElementMarkupName."[]' jquerytag='".$renderedElementMarkupName."' id='".$renderedElementMarkupName."_\"+i+\"' target='\"+i+\"' value='\"+value+\"' />\");
                            jQuery('#".$renderedElementMarkupName."_formulize_autocomplete_selections').append(\"<p class='auto_multi auto_multi_".$renderedElementMarkupName."' target='\"+value+\"'>\"+label+\"</p>\");
                            jQuery('#".$renderedElementMarkupName."_user').val('');
                            jQuery('#last_selected_".$renderedElementMarkupName."').val('');
														triggerChangeOnMultiValueAutocomplete('".$renderedElementMarkupName."');
                        }
                    }";
                }
                $output .= "
            }).blur(function() {
                if(".$renderedElementMarkupName."_clearbox == true || jQuery('#".$renderedElementMarkupName."_user').val() == '') {
                    jQuery('#".$renderedElementMarkupName."_user').val('');
                    setAutocompleteValue('".$renderedElementMarkupName."', 'none', 0, ".$multiple.");
                }
            });
        }

        jQuery(window).load(formulize_initializeAutocomplete".$renderedElementMarkupName."());
        jQuery(document).ready(function() { checkForChrome(); });
";

if($multiple ){
    $output.= "
        jQuery('#".$renderedElementMarkupName."_formulize_autocomplete_selections').on('click', '.auto_multi_".$renderedElementMarkupName."', function() {
						removeFromMultiValueAutocomplete(jQuery(this).attr('target'), '".$renderedElementMarkupName."');
        });
    ";
}

        $output .= "\n</script>";

		return $output;
	}


	function formulize_disableElement($element, $type) {
		if($type == "textarea" OR $type == "date" OR $type == "colorpick") {
			switch($type) {
				case 'date':
					if($timeval = $element->getValue()) {
						if($timeval == _DATE_DEFAULT OR $timeval == '0000-00-00' OR !$timeval) {
							$hiddenValue = "";
						} else {
							$timeval = is_numeric($timeval) ? $timeval : strtotime($timeval);
							$hiddenValue = date(_SHORTDATESTRING, $timeval);
						}
					} else {
						$hiddenValue = "";
					}
					break;
				default:
					// should work for all elements, since non-textbox type elements where the value would not be passed straight back, are handled differently at the time they are constructed
          $hiddenValue = $element->getValue();
			}
			if(is_array($hiddenValue)) { // not sure when/if this would ever happen
				$newElement = new xoopsFormLabel($element->getCaption(), implode(", ", $hiddenValue));
			} else {
				$newElement = new xoopsFormLabel($element->getCaption(), $hiddenValue);
			}
      $newElement->setName($element->getName());
			return $newElement;
		} else {
			return $element;
		}
	}


	// this function creates the previous values drop down that people can use to set the value of an element
	// screen is the screen object with the data we need (form id with previous entries and rule for lining them up with current form)
	// element_id is the ID of the element we're drawing (add ele_ to the front to make the javascript ID we need to know in order to set the value of the element to the one the user selects)
	// type is the type of element, which affects how the javascript is written (textboxes aren't set the same as radio buttons, etc)
	function formulize_setupPreviousEntryUI($screen, $element_id, $type, $owner, $de, $entry_id, $ele_handle, $fid) {

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
				$cachedEntries[$screen->getVar('sid')] = gatherDataset($screen->getVar('paraentryform'), filter: $singleData['entry'], frid: 0);
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
		$elementName = $de ? "de_".$fid."_".$entry_id."_".$element_id : "ele_".$element_id; // displayElement elements have different names from regular elements
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
			$value = htmlspecialchars(strip_tags(getValue($entry, $previousElementHandle)));
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

// THIS FUNCTION COPIED FROM LIASE 1.26, onchange control added
// JWE -- JUNE 1 2006
function optOther($s, $id, $entry_id, $counter, $checkbox=false, $isDisabled=false){
    static $blankSubformCounters = array();
    global $xoopsModuleConfig, $xoopsDB;
    if( !is_string($s) OR !preg_match('/\{OTHER\|+[0-9]+\}/', $s) ){
        return false;
    }
    // deal with displayElement elements...
    $id_parts = explode("_", $id);
    /* // displayElement elements will be in the format de_{id_req}_{ele_id} (deh?)
    // regular elements will be in the format ele_{ele_id}
    if(count((array) $id_parts) == 3) {
        $ele_id = $id_parts[2];
    } else {
        $ele_id = $id_parts[1];
    }*/
    // NOW, in Formulize 3, id_parts[3] will always be the element id. :-)
    $ele_id = $id_parts[3];

    // gather the current value if there is one
    $other_text = "";
    if(is_numeric($entry_id)) {
        $otherq = q("SELECT other_text FROM " . $xoopsDB->prefix("formulize_other") . " WHERE id_req='$entry_id' AND ele_id='$ele_id' LIMIT 0,1");
        $other_text = $otherq[0]['other_text'];
    }
    if(strstr($_SERVER['PHP_SELF'], "formulize/printview.php") OR $isDisabled) {
        return $other_text;
    }
    $s = explode('|', preg_replace('/[\{\}]/', '', $s));
    $len = !empty($s[1]) ? $s[1] : $xoopsModuleConfig['t_width'];
    if($entry_id == "new") {
        $blankSubformCounters[$ele_id] = isset($blankSubformCounters[$ele_id]) ? $blankSubformCounters[$ele_id] + 1 : 0;
        $blankSubformCounter = $blankSubformCounters[$ele_id];
        $otherKey = 'ele_'.$ele_id.'_'.$entry_id.'_'.$blankSubformCounter;
    } else {
        $otherKey = 'ele_'.$ele_id.'_'.$entry_id;
    }
    $box = new XoopsFormText('', 'other['.$otherKey.']', $len, 255, $other_text);
    if($checkbox) {
        $box->setExtra("onchange=\"javascript:formulizechanged=1;\" onkeydown=\"javascript:if(this.value != ''){this.form.elements['" . $id . "[]'][$counter].checked = true;}\"");
    } else {
        $box->setExtra("onchange=\"javascript:formulizechanged=1;\" onkeydown=\"javascript:if(this.value != ''){this.form." . $id . "[$counter].checked = true;}\"");
    }
    return $box->render();
}

/**
 * Convert some raw value from a user entered record in the database, into an array of entry ids
 *
 * @param string $dbValue The raw value being converted
 * @return array An array of the entry ids represented in the string
 */
function convertEntryIdsFromDBToArray($dbValue) {
	// isolate the values selected in this entry, and then use those as an entry id filter
	if(strstr($dbValue, '*=+*:')) {
		$directLimit = explode("*=+*:",trim($dbValue, "*=+*:"));
		foreach($directLimit as $v) {
			$directLimit[] = intval($v);
		}
	} elseif(strstr($dbValue, ',') AND is_numeric(str_replace(',','',$dbValue))) { // if it has commas, but if you remove them then it's numbers...
		$directLimit = explode(",",trim($dbValue, ","));
	} else {
		$directLimit = array(intval($dbValue));
	}
	return $directLimit;
}
