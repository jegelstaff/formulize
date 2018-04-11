<?php


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
                        // check to see if we should draw in the delete checkbox
			// 2 is none, 1 is all
                        if ($useCheckboxes != 2 and ($useCheckboxes == 1 or formulizePermHandler::user_can_delete_entry($fid, $uid, $linkids[0]))) {

							print "<input type=checkbox title='" . _formulize_DE_DELBOXDESC . "' class='formulize_selection_checkbox' name='delete_" . $linkids[0] . "' id='delete_" . $linkids[0] . "' value='delete_" . $linkids[0] . "'>";
						}
						if($useViewEntryLinks) {
							print "<a href='" . $currentURL;
							if(strstr($currentURL, "?")) { // if params are already part of the URL...
								print "&";
							} else {
								print "?";
							}
							print "ve=" . $linkids[0] . "' onclick=\"javascript:goDetails('" . $linkids[0] . "');return false;\" ".
								" class=\"loe-edit-entry\" alt=\"" . _formulize_DE_VIEWDETAILS . "\" title=\"" . _formulize_DE_VIEWDETAILS . "\" >";
							print "&nbsp;</a>";
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
                                                    if($deThisIntId) { print "\n<br />\n"; } // extra break to separate multiple form elements in the same cell, for readability/usability
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
						} elseif($col != "creation_uid" AND $col!= "mod_uid" AND $col != "entry_id") {
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
		
?>