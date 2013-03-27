<?php

print "<table cellpadding=10><tr><td style=\"vertical-align: top;\" width=100%>";
		
		print "<h1>" . trans($title) . "</h1>";
	
	
		if($loadview AND $lockcontrols) {
			print "<h3>" . $loadviewname . "</h3></td><td>";
			print "<input type=hidden name=currentview id=currentview value=\"$currentview\"></input>\n<input type=hidden name=loadviewname id=loadviewname value=\"$loadviewname\"></input>$submitButton";
		} else {
			print "</td>";
			if(!$settings['lockcontrols']) {
	
				print "<td rowspan=3 style=\"vertical-align: bottom;\">";	      
		
				print "<table><tr><td style=\"vertical-align: bottom;\">";
		
				print "<p>$submitButton<br>";
				if($atLeastOneActionButton) {
					print "<b>" . _formulize_DE_ACTIONS . "</b>";
				}
				print "\n";
					
				if( $thisButtonCode = $buttonCodeArray['changeColsButton']) { print "<br>$thisButtonCode"; }
				if( $thisButtonCode = $buttonCodeArray['resetViewButton']) { print "<br>$thisButtonCode"; }
				// there is a create reports permission, but we are currently allowing everyone to save their own views regardless of that permission.  The publishing permissions do kick in on the save popup.
				if( $thisButtonCode = $buttonCodeArray['saveViewButton']) { print "<br>$thisButtonCode"; }
				// you can always create and delete your own reports right now (delete_own_reports perm has no effect).  If can delete other reports, then set $pubstart to 10000 -- which is done above -- (ie: can delete published as well as your own, because the javascript will consider everything beyond the start of 'your saved views' to be saved instead of published (published be thought to never begin))
				if( $thisButtonCode = $buttonCodeArray['deleteViewButton']) { print "<br>$thisButtonCode"; }

				print "</p></td><td style=\"vertical-align: bottom;\"><p style=\"text-align: center;\">";

				if(($add_own_entry AND $singleMulti[0]['singleentry'] == "") OR (($del_own OR $del_others) AND !$settings['lockcontrols'])) {
					if( $thisButtonCode = $buttonCodeArray['selectAllButton']) { print "$thisButtonCode"; }
					if( $thisButtonCode = $buttonCodeArray['clearSelectButton']) { print "<br>$thisButtonCode<br>"; }
				}
				if($add_own_entry AND $singleMulti[0]['singleentry'] == "") {
					if( $thisButtonCode = $buttonCodeArray['cloneButton']) { print "$thisButtonCode<br>"; }
				}
				if(($del_own OR $del_others) AND !$settings['lockcontrols']) {
					if( $thisButtonCode = $buttonCodeArray['deleteButton']) { print "$thisButtonCode<br>"; }
				}

				print "</p></td><td style=\"vertical-align: bottom;\"><p style=\"text-align: center;\">";

				if( $thisButtonCode = $buttonCodeArray['calcButton']) { print "<br>$thisButtonCode"; }
				if( $thisButtonCode = $buttonCodeArray['advCalcButton']) { print "<br>$thisButtonCode"; }
				if( $thisButtonCode = $buttonCodeArray['advSearchButton']) { print "<br>$thisButtonCode"; }
				if( $thisButtonCode = $buttonCodeArray['exportButton']) { print "<br>$thisButtonCode"; }
				if($import_data = $gperm_handler->checkRight("import_data", $fid, $groups, $mid) AND !$frid AND $thisButtonCode = $buttonCodeArray['importButton']) { // cannot import into a framework currently
					print "<br>$thisButtonCode";
				}
				if( $thisButtonCode = $buttonCodeArray['notifButton']) { print "$thisButtonCode"; } 
				print "</p>";
				print "</td></tr></table></td></tr>\n";
			} else { // if lockcontrols set, then write in explanation...
				print "<td></td></tr></table>";
				print "<table><tr><td style=\"vertical-align: bottom;\">";
				print "<input type=hidden name=curviewid id=curviewid value=$curviewid></input>";
				print "<p>$submitButton<br>" . _formulize_DE_WARNLOCK . "</p>";
				print "</td></tr>";
			} // end of if controls are locked

			// cell for add entry buttons
			print "<tr><td style=\"vertical-align: top;\">\n";

			if(!$settings['lockcontrols']) {
				// added October 18 2006 -- moved add entry buttons to left side to emphasize them more
				print "<table><tr><td style=\"vertical-align: bottom;\"><p>\n";
	
				$addButton = $buttonCodeArray['addButton'];
				$addMultiButton = $buttonCodeArray['addMultiButton'];
				$addProxyButton = $buttonCodeArray['addProxyButton'];
			
				if($add_own_entry AND $singleMulti[0]['singleentry'] == "" AND ($addButton OR $addMultiButton)) {
					print "<b>" . _formulize_DE_FILLINFORM . "</b>\n";
					if( $addButton) { print "<br>$addButton"; } // this will include proxy box if necessary
					if( $addMultiButton) { print "<br>$addMultiButton"; }
				} elseif($add_own_entry AND $proxy AND ($addButton OR $addProxyButton)) { // this is a single entry form, so add in the update and proxy buttons if they have proxy, otherwise, just add in update button
					print "<b>" . _formulize_DE_FILLINFORM . "</b>\n";
					if( $addButton) { print "<br>$addButton"; }
					if( $addProxyButton) { print "<br>$addProxyButton"; }
				} elseif($add_own_entry AND $addButton) {
					print "<b>" . _formulize_DE_FILLINFORM . "</b>\n";
					if( $addButton) { print "<br>$addButton"; }
				} elseif($proxy AND $addProxyButton) {
					print "<b>" . _formulize_DE_FILLINFORM . "</b>\n";
					if( $addProxyButton) { print "<br>$addProxyButton"; }
				}
				print "<br><br></p></td></tr></table>\n";
			}
	
			print "</td></tr><tr><td style=\"vertical-align: bottom;\">";
	
			if ($currentViewList = $buttonCodeArray['currentViewList']) { print $currentViewList; }
	
		} // end of if there's a loadview or not
		
		// regardless of if a view is loaded and/or controls are locked, always print the page navigation controls
		if ($pageNavControls = $buttonCodeArray['pageNavControls']) { print $pageNavControls; }
		
		print "</td></tr></table>";