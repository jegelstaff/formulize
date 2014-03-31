<?php


$filename = "";
	// $settings['xport'] no longer set by a page load, except if called as part of the import process to create a template for updating
	if(!$settings['xport']) {
		$settings['xport'] = $settings['hlist'] ? "calcs" : "comma";
		$xportDivText1 = "<div id=exportlink style=\"display: none;\">"; // export button turns this link on and off now
		$xportDivText2 = "</div>";
	} else {
		$xportDivText1 = "";
		$xportDivText2 = "";
	}
  
	if( @$_POST['advcalc_acid'] ) {
    
    if( $_POST['acid'] > 0 ) {
      $result = formulize_runAdvancedCalculation( intval($_POST['acid'] )); // result will be an array with two or three keys: 'text' and 'output', and possibly 'groupingMap'.  Text is for display on screen "raw" and Output is a variable that can be used by a dev.  The output variable will be an array if groupings are in effect.  The keys of the array will be the various grouping values in effect.  The groupingMap will be present if there's a set of groupings in effect.  It is an array that contains all the grouping choices, their text equivalents and their data values (which are the keys in the output array) -- groupingMap is still to be developed/added to the mix....will be necessary when we are integrating with Drupal or other API uses.
      print "<br/>" . $result['text'] . "<br/><br/>";
    }
  }
	
	// export of Data is moved out to a popup
	// Calculations still handled in the old way for now
	if($settings['xport'] == "calcs") {
		$filename = prepExport($headers, $cols, $data, $settings['xport'], $settings['xport_cust'], $settings['title'], false, $fid, $groups);
		$linktext = $_POST['xport'] == "update" ? _formulize_DE_CLICKSAVE_TEMPLATE : _formulize_DE_CLICKSAVE;
		print "$xportDivText1<center><p><a href='$filename' target=\"_blank\">$linktext</a></p></center>";
		print "<br>$xportDivText2";
	}

	$scrollBoxWasSet = false;
	if($useScrollBox AND count($data) > 0) {
		print "<div class=scrollbox id=resbox>\n";
		$scrollBoxWasSet = true;
	}

	// perform calculations...
	// calc_cols is the columns requested (separated by / -- ele_id for each, also metadata is indicated with uid, proxyid, creation_date, mod_date)
	// calc_calcs is the calcs for each column, columns separated by / and calcs for a column separated by ,. possible calcs are sum, avg, min, max, count, per
	// calc_blanks is the blank setting for each calculation, setup the same way as the calcs, possible settings are all,  noblanks, onlyblanks
	// calc_grouping is the grouping option.  same format as calcs.  possible values are ele_ids or the uid, proxyid, creation_date and mod_date metadata terms

	// 1. extract data from four settings into arrays
	// 2. loop through the array and perform all the requested calculations
	
	if($settings['calc_cols'] AND !$settings['hcalc']) {
		$ccols = explode("/", $settings['calc_cols']);
		$ccalcs = explode("/", $settings['calc_calcs']);
		// need to add in proper handling of long calculation results, like grouping percent breakdowns that result in many, many rows.
		foreach($ccalcs as $onecalc) {
			$thesecalcs = explode(",", $onecalc);
			if(!is_array($thesecalcs)) { $thesecalcs[0] = ""; }
			$totalalcs = $totalcalcs + count($thesecalcs);
		}
		$cblanks = explode("/", $settings['calc_blanks']);
		$cgrouping = explode("/", $settings['calc_grouping']);
    //formulize_benchmark("before performing calcs");
		$cResults = performCalcs($ccols, $ccalcs, $cblanks, $cgrouping, $frid, $fid);
    //formulize_benchmark("after performing calcs");
//		print "<p><input type=button style=\"width: 140px;\" name=cancelcalcs1 value='" . _formulize_DE_CANCELCALCS . "' onclick=\"javascript:cancelCalcs();\"></input></p>\n";
//		print "<div";
//		if($totalcalcs>4) { print " class=scrollbox"; }
//		print " id=calculations>
		$calc_cols = $settings['calc_cols'];
		$calc_calcs = $settings['calc_calcs'];
		$calc_blanks = $settings['calc_blanks'];
		$calc_grouping = $settings['calc_grouping'];

 		print "<table class=outer><tr><th colspan=2>" . _formulize_DE_CALCHEAD . "</th></tr>\n";
 		if(!$settings['lockcontrols'] AND ($useSearchCalcMsgs == 1 OR $useSearchCalcMsgs == 3)) { // AND !$loadview) { // -- loadview removed from this function sept 24 2005
 			print "<tr><td class=head colspan=2><input type=button style=\"width: 140px;\" name=mod_calculations value='" . _formulize_DE_MODCALCS . "' onclick=\"javascript:showPop('" . XOOPS_URL ."/modules/formulize/include/pickcalcs.php?fid=$fid&frid=$frid&calc_cols=".urlencode($calc_cols)."&calc_calcs=".urlencode($calc_calcs)."&calc_blanks=".urlencode($calc_blanks)."&calc_grouping=".urlencode($calc_grouping)."');\"></input>&nbsp;&nbsp;<input type=button style=\"width: 140px;\" name=cancelcalcs value='" . _formulize_DE_CANCELCALCS . "' onclick=\"javascript:cancelCalcs();\"></input>&nbsp;&nbsp<input type=button style=\"width: 140px;\" name=showlist value='" . _formulize_DE_SHOWLIST . "' onclick=\"javascript:showList();\"></input></td></tr>";
 		}

		$exportFilename = $settings['xport'] == "calcs" ? $filename : "";
    //formulize_benchmark("before printing results");
    printResults($cResults[0], $cResults[1], $cResults[2], $cResults[3], $exportFilename, $settings['title']); // 0 is the masterresults, 1 is the blanksettings, 2 is grouping settings -- exportFilename is the name of the file that we need to create and into which we need to dump a copy of the calcs
    //formulize_benchmark("after printing results");
		print "</table>\n";

	} 
	// MASTER HIDELIST CONDITIONAL...
	if(!$settings['hlist']) {
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
			$drawRow($entry, $linkids);
			
				}
			
			else { // this is a blank entry
				$blankentries++;
			} // end of not "" check
			
			print "</tr>\n";
		} // end of foreach data as entry

	} // end of if there is any data to draw

		print "</table></div>";
	}// END OF MASTER HIDELIST CONDITIONAL
	if((!isset($data) OR count($data) == $blankentries) AND !$LOE_limit) { // if no data was returned, or the dataset was empty...
		print "<p><b>" . _formulize_DE_NODATAFOUND . "</b></p>\n";
	} elseif($LOE_limit) {
		print "<p>" . _formulize_DE_LOE_LIMIT_REACHED1 . " <b>" . $LOE_limit . "</b> " . _formulize_DE_LOE_LIMIT_REACHED2 . " <a href=\"\" onclick=\"javascript:forceQ();return false;\">" . _formulize_DE_LOE_LIMIT_REACHED3 . "</a></p>\n";
	}
	
	if($scrollBoxWasSet) {
		print "</div>";
	}