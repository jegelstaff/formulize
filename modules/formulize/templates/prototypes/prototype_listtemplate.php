<?php

print "<div class=\"list-of-entries-container\"><table class=\"outer\">";

		$count_colspan = count($cols)+1;
		if($useViewEntryLinks OR $useCheckboxes != 2) {
			$count_colspan_calcs = $count_colspan;
		} else {
			$count_colspan_calcs = $count_colspan - 1;
		}
		$count_colspan_calcs = $count_colspan_calcs + count($inlineButtons); // add to the column count for each inline custom button
		$count_colspan_calcs++; // add one more for the hidden floating column
		if(!$screen) { print "<tr><th colspan=$count_colspan_calcs>" . _formulize_DE_DATAHEADING . "</th></tr>\n"; }
	
		if($settings['calc_cols'] AND !$settings['lockcontrols'] AND ($useSearchCalcMsgs == 1 OR $useSearchCalcMsgs == 3)) { // AND !$loadview) { // -- loadview removed from this function sept 24 2005
			$calc_cols = $settings['calc_cols'];
			$calc_calcs = $settings['calc_calcs'];
			$calc_blanks = $settings['calc_blanks'];
			$calc_grouping = $settings['calc_grouping'];
			print "<tr><td class=head colspan=$count_colspan_calcs><input type=button style=\"width: 140px;\" name=mod_calculations value='" . _formulize_DE_MODCALCS . "' onclick=\"javascript:showPop('" . XOOPS_URL . "/modules/formulize/include/pickcalcs.php?fid=$fid&frid=$frid&calc_cols=$calc_cols&calc_calcs=$calc_calcs&calc_blanks=$calc_blanks&calc_grouping=$calc_grouping');\"></input>&nbsp;&nbsp;<input type=button style=\"width: 140px;\" name=cancelcalcs value='" . _formulize_DE_CANCELCALCS . "' onclick=\"javascript:cancelCalcs();\"></input>&nbsp;&nbsp;<input type=button style=\"width: 140px;\" name=hidelist value='" . _formulize_DE_HIDELIST . "' onclick=\"javascript:hideList();\"></input></td></tr>";
		}
	
		// draw advanced search notification
		if($settings['as_0'] AND ($useSearchCalcMsgs == 1 OR $useSearchCalcMsgs == 2)) {
			$writable_q = writableQuery($wq);
			$minus1colspan = $count_colspan-1+count($inlineButtons);
			if(!$asearch_parse_error) {
				print "<tr>";
				if($useViewEntryLinks OR $useCheckboxes != 2) { // only include this column if necessary
					print "<td class=head></td>";
				}
				print "<td colspan=$minus1colspan class=head>" . _formulize_DE_ADVSEARCH . ": $writable_q";
			} else {
				print "<tr>";
				if($useViewEntryLinks OR $useCheckboxes != 2) {
					print "<td class=head></td>";
				}
				print "<td colspan=$minus1colspan class=head><span style=\"font-weight: normal;\">" . _formulize_DE_ADVSEARCH_ERROR . "</span>";
			}
			if(!$settings['lockcontrols']) { // AND !$loadview) { // -- loadview removed from this function sept 24 2005
				print "<br><input type=button style=\"width: 140px;\" name=advsearch value='" . _formulize_DE_MOD_ADVSEARCH . "' onclick=\"javascript:showPop('" . XOOPS_URL . "/modules/formulize/include/advsearch.php?fid=$fid&frid=$frid";
				foreach($settings as $k=>$v) {
					if(substr($k, 0, 3) == "as_") {
						$v = str_replace("'", "&#39;", $v);
						$v = stripslashes($v);
						print "&$k=" . urlencode($v);
					}
				}
			print "');\"></input>&nbsp;&nbsp;<input type=button style=\"width: 140px;\" name=cancelasearch value='" . _formulize_DE_CANCELASEARCH . "' onclick=\"javascript:killSearch();\"></input>";
			}
			print "</td></tr>\n";
		}
	
		if($useHeadings) {
      $headers = getHeaders($cols, true); // second param indicates we're using element headers and not ids
      drawHeaders($headers, $cols, $useCheckboxes, $useViewEntryLinks, count($inlineButtons), $settings['lockedColumns']); 
    }
		if($useSearch) {
			drawSearches($searches, $cols, $useCheckboxes, $useViewEntryLinks, count($inlineButtons), false, $hiddenQuickSearches);
		}
		// get form handles in use
		$mainFormHandle = key($data[key($data)]);
	
		if(count($data) == 0) { // kill an empty dataset so there's no rows drawn
			unset($data);
		} 
	
  
    
  
		$headcounter = 0;
		$blankentries = 0;
		$GLOBALS['formulize_displayElement_LOE_Used'] = false;
		$formulize_LOEPageStart = (isset($_POST['formulize_LOEPageStart']) AND !$regeneratePageNumbers) ? intval($_POST['formulize_LOEPageStart']) : 0;
		// adjust formulize_LOEPageSize if the actual count of entries is less than the page size
		$formulize_LOEPageSize = $GLOBALS['formulize_countMasterResultsForPageNumbers'] < $formulize_LOEPageSize ? $GLOBALS['formulize_countMasterResultsForPageNumbers'] : $formulize_LOEPageSize;
		$actualPageSize = $formulize_LOEPageSize ? $formulize_LOEPageStart + $formulize_LOEPageSize : $GLOBALS['formulize_countMasterResultsForPageNumbers'];
    /*print "start: $formulize_LOEPageStart<br>";
    print "size: $formulize_LOEPageSize<br>";
    print "actualsize: $actualPageSize<br>";*/
		if(isset($data)) {
			//for($entryCounter=$formulize_LOEPageStart;$entryCounter<$actualPageSize;$entryCounter++) {
      foreach($data as $id=>$entry) {
        formulize_benchmark("starting to draw one row of results");
				//$entry = $data[$entryCounter];
				//$id=$entryCounter;
						
				// check to make sure this isn't an unset entry (ie: one that was blanked by the extraction layer just prior to sending back results
				// Since the extraction layer is unsetting entries to blank them, this condition should never be met?
				// If this condition is ever met, it may very well screw up the paging of results!
				// NOTE: this condition is met on the last page of a paged set of results, unless the last page as exactly the same number of entries on it as the limit of entries per page
				if($entry != "") { 
		
					if($headcounter == $repeatHeaders AND $repeatHeaders > 0) { 
						if($useHeadings) { drawHeaders($headers, $cols, $useCheckboxes, $useViewEntryLinks, count($inlineButtons)); }
						$headcounter = 0;
					}
					$headcounter++;		
			
					print "<tr>\n";
					if($class=="even") {
						$class="odd";
					} else {
						$class="even";
					}
					unset($linkids);
          
					$linkids = internalRecordIds($entry, $mainFormHandle);
					
					// draw in the margin column where the links and metadata goes
					if($useViewEntryLinks OR $useCheckboxes != 2) {
						print "<td class=\"head formulize-controls\">\n";
					}
			
					if(!$settings['lockcontrols']) { //  AND !$loadview) { // -- loadview removed from this function sept 24 2005
						if($useViewEntryLinks) {
							print "<p><center><a href='" . $currentURL;
							if(strstr($currentURL, "?")) { // if params are already part of the URL...
								print "&";
							} else {
								print "?";
							}
							print "ve=" . $linkids[0] . "' onclick=\"javascript:goDetails('" . $linkids[0] . "');return false;\"><img src='" . XOOPS_URL . "/modules/formulize/images/detail.gif' border=0 alt=\"" . _formulize_DE_VIEWDETAILS . "\" title=\"" . _formulize_DE_VIEWDETAILS . "\"></a>";
						}

                        if ($useCheckboxes != 2) { // two means no checkboxes -- should use a constant to make it clear
                            // check to see if we should draw in the delete checkbox
                            if ($useCheckboxes == 1 /* 1 means all */ or formulizePermHandler::user_can_delete_entry($fid, $uid, $linkids[0])) {
								if($useViewEntryLinks) {
									print "<br>";
								} else {
									print "<p><center>";
								}
								print "<input type=checkbox title='" . _formulize_DE_DELBOXDESC . "' class='formulize_selection_checkbox' name='delete_" . $linkids[0] . "' id='delete_" . $linkids[0] . "' value='delete_" . $linkids[0] . "'>";
							}
						}
						if($useViewEntryLinks OR $useCheckboxes != 2) { // at least one of the above was used
							print "</center></p>\n";
						}
					} // end of IF NO LOCKCONTROLS
					if($useViewEntryLinks OR $useCheckboxes != 2) {
						print "</td>\n";
					}	
			
					$column_counter = 0;
					
					if($columnWidth) {
						$columnWidthParam = "style=\"width: $columnWidth" . "px\"";
					} else {
						$columnWidthParam = "";
					}
					
          for($i=0;$i<count($cols);$i++) {
            //formulize_benchmark("drawing one column");
						$col = $cols[$i];
						$colhandle = $settings['columnhandles'][$i];
						$classToUse = $class . " column column".$i;
						$cellRowAddress = $id+2;
						if($i==0) {
							print "<td $columnWidthParam class=\"$class floating-column\" id='floatingcelladdress_$cellRowAddress'>\n";
						}
						print "<td $columnWidthParam class=\"$classToUse\" id=\"celladdress_".$cellRowAddress."_".$i."\">\n";
						if($col == "creation_uid" OR $col == "mod_uid") {
							$userObject = $member_handler->getUser(display($entry, $col));
							if($userObject) {
								$nameToDisplay = $userObject->getVar('name') ? $userObject->getVar('name') : $userObject->getVar('uname');
							} else {
								$nameToDisplay = _FORM_ANON_USER;
							}
							$value = "<a href=\"" . XOOPS_URL . "/userinfo.php?uid=" . display($entry, $col) . "\" target=_blank>" . $nameToDisplay . "</a>";
						} else {
							$value = display($entry, $col);
						}

                        // set in the display function, corresponds to the entry id of the record in the form where the current value was retrieved from.  If there is more than one local entry id, because of a one to many framework, then this will be an array that corresponds to the order of the values returned by display.
                        $currentColumnLocalId = $GLOBALS['formulize_mostRecentLocalId'];
                        // if we're supposed to display this column as an element... (only show it if they have permission to update this entry)
                        if (in_array($colhandle, $deColumns) and formulizePermHandler::user_can_edit_entry($fid, $uid, $entry)) {
							include_once XOOPS_ROOT_PATH . "/modules/formulize/include/elementdisplay.php";
							if($frid) { // need to work out which form this column belongs to, and use that form's entry ID.  Need to loop through the entry to find all possible internal IDs, since a subform situation would lead to multiple values appearing in a single cell, so multiple displayElement calls would be made each with their own internal ID.
								foreach($entry as $entryFormHandle=>$entryFormData) {
									foreach($entryFormData as $internalID=>$entryElements) {
										$deThisIntId = false;
										foreach($entryElements as $entryHandle=>$values) {
											if($entryHandle == $col) { // we found the element that we're trying to display
												if($deThisIntId) { print "\n<br />\n"; } // could be a subform so we'd display multiple values
												if($deDisplay) {
													print '<div id="deDiv_'.$colhandle.'_'.$internalID.'">';
													print getHTMLForList($values, $colhandle, $internalID, $deDisplay, $textWidth, $currentColumnLocalId, $fid, $cellRowAddress, $i);
													print "</div>";
												} else {
													displayElement("", $colhandle, $internalID);
												}
												$deThisIntId = true;
											}
										}
									}
								}
							} else { // display based on the mainform entry id
								if($deDisplay) {
									print '<div id="deDiv_'.$colhandle.'_'.$linkids[0].'">';
									print getHTMLForList($value,$colhandle,$linkids[0], $deDisplay, $textWidth, $currentColumnLocalId, $fid, $cellRowAddress, $i);
									print "</div>";
								} else {
									displayElement("", $colhandle, $linkids[0]); // works for mainform only!  To work on elements from a framework, we need to figure out the form the element is from, and the entry ID in that form, which is done above
								}
							}
							$GLOBALS['formulize_displayElement_LOE_Used'] = true;
						} elseif($col != "creation_uid" AND $col!= "mod_uid") {
							print getHTMLForList($value, $col, $linkids[0], 0, $textWidth, $currentColumnLocalId, $fid, $cellRowAddress, $i);
						} else { // no special formatting on the uid columns:
							print $value;
						}
						
						print "</td>\n";
						$column_counter++;
					}
					
					// handle inline custom buttons
					foreach($inlineButtons as $caid=>$thisCustomAction) {
						list($caCode) = processCustomButton($caid, $thisCustomAction, $linkids[0], $entry); // only bother with the code, since we already processed any clicked button above
						if($caCode) {
							print "<td $columnWidthParam class=$class>\n";
							print "<center>$caCode</center>\n";
							print "</td>\n";
						}
					}
					
					// handle hidden elements for passing back to custom buttons
					foreach($hiddenColumns as $thisHiddenCol) {
						print "\n<input type=\"hidden\" name=\"hiddencolumn_".$linkids[0]."_$thisHiddenCol\" value=\"" . htmlspecialchars(display($entry, $thisHiddenCol)) . "\"></input>\n";
					}				
					
					print "</tr>\n";
				
				} else { // this is a blank entry
					$blankentries++;
				} // end of not "" check
			
			} // end of foreach data as entry
		} // end of if there is any data to draw

		print "</table></div>";