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
            print "<tr><td class=head colspan=$count_colspan_calcs><input type=button style=\"width: 140px;\" name=mod_calculations value='".
                _formulize_DE_MODCALCS . "' onclick=\"javascript:showPop('" . XOOPS_URL.
                "/modules/formulize/include/pickcalcs.php?fid=$fid&frid=$frid&calc_cols=$calc_cols&calc_calcs=$calc_calcs&calc_blanks=$calc_blanks&calc_grouping=".
                urlencode($calc_grouping)."&cols=".urlencode(implode(",",$cols)).
                "');\"></input>&nbsp;&nbsp;<input type=button style=\"width: 140px;\" name=cancelcalcs value='".
                _formulize_DE_CANCELCALCS . "' onclick=\"javascript:cancelCalcs();\"></input>&nbsp;&nbsp;<input type=button style=\"width: 140px;\" name=hidelist value='".
                _formulize_DE_HIDELIST . "' onclick=\"javascript:hideList();\"></input></td></tr>";
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
			drawSearches($searches, $settings, $useCheckboxes, $useViewEntryLinks, count($inlineButtons), false, $hiddenQuickSearches);
		}

		
?>