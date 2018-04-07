<?php
	                // include who the entry belongs to and the date
    	            // include acknowledgement that information has been updated if we have just done a submit
        	        // form_meta includes: last_update, created, last_update_by, created_by

					$breakHTML = "";

                    // build the break HTML and then add the break to the form
                    if(!strstr($currentURL, "printview.php")) {
                        $breakHTML .= "<center class=\"no-print\">";
                        $breakHTML .= "<p><b>";
                        if($info_received_msg) {
                            $breakHTML .= _formulize_INFO_SAVED . "&nbsp;";
                        }
                        if($info_continue == 1 and formulizePermHandler::user_can_edit_entry($fid, $uid, $entry)) {
                            $breakHTML .= "<p class=\"no-print\">"._formulize_INFO_CONTINUE1."</p>";
                        } elseif($info_continue == 2) {
                            $breakHTML .=  "<p class=\"no-print\">"._formulize_INFO_CONTINUE2."</p>";
                        } elseif(!$entry and formulizePermHandler::user_can_edit_entry($fid, $uid, $entry)) {
                            $breakHTML .=  "<p class=\"no-print\">"._formulize_INFO_MAKENEW."</p>";
                        }
                        $breakHTML .= "</b></p>";
                        $breakHTML .= "</center>";
                    } else {
                        // Update for Ajax Save
                        $breakHTML = "<p id='formInstruction' style='text-align: center; font-weight: bold;'>";
                        $formInstructions = genFormInstruction($info_continue, $fid, $entryId, $info_received_msg, $owner, $uid, $groups, $mid);
                        $breakHTML .= $formInstructions;
                        $breakHTML .= "</p>";
                    }

                    $breakHTML .= "<table cellpadding=5 width=100%><tr><td width=50% style=\"vertical-align: bottom;\">";
                    $breakHTML .= "<p><b>" . _formulize_FD_ABOUT . "</b><br>";

                    if($entries[$this_fid][0]) {
                        $form_meta = getMetaData($entries[$this_fid][0], $member_handler, $this_fid);
                        $breakHTML .= _formulize_FD_CREATED . $form_meta['created_by'] . " " . formulize_formatDateTime($form_meta['created']) . "<br>" . _formulize_FD_MODIFIED . $form_meta['last_update_by'] . " " . formulize_formatDateTime($form_meta['last_update']) . "</p>";
                    } else {
                        $breakHTML .= _formulize_FD_NEWENTRY . "</p>";
                    }

                    $breakHTML .= "</p>";

					$breakHTML .= "</td><td width=50% style=\"vertical-align: bottom;\">";
			        // End of Update for Ajax Save
          			if (strstr($currentURL, "printview.php") or !formulizePermHandler::user_can_edit_entry($fid, $uid, $entry)) {
						$breakHTML .= "<p>";
					} else {
						// get save and button button options
						$save_button_text = "";
						$done_button_text = "";
						if(is_array($button_text)) {
							$save_button_text = $button_text[1];
							$done_button_text = $button_text[0];
						} else {
							$done_button_text = $button_text;
						}
						if(!$done_button_text AND !$allDoneOverride) {
							$done_button_text = _formulize_INFO_DONE1 . _formulize_DONE . _formulize_INFO_DONE2;
						} elseif($done_button_text != "{NOBUTTON}" AND !$allDoneOverride) {
							$done_button_text = _formulize_INFO_DONE1 . $done_button_text . _formulize_INFO_DONE2;
						// check to see if the user is allowed to modify the existing entry, and if they're not, then we have to draw in the all done button so they have a way of getting back where they're going
						} elseif (($entry and formulizePermHandler::user_can_edit_entry($fid, $uid, $entry)) OR !$entry) {
							$done_button_text = "";
						} else {
							$done_button_text = _formulize_INFO_DONE1 . _formulize_DONE . _formulize_INFO_DONE2;
						}

						$nosave = false;
						if(!$save_button_text and formulizePermHandler::user_can_edit_entry($fid, $uid, $entry)) {
							$save_button_text = _formulize_INFO_SAVEBUTTON;
						} elseif ($save_button_text != "{NOBUTTON}" and formulizePermHandler::user_can_edit_entry($fid, $uid, $entry)) {
							$save_button_text = _formulize_INFO_SAVE1 . $save_button_text . _formulize_INFO_SAVE2;
						} else {
							$save_button_text = _formulize_INFO_NOSAVE;
							$nosave = true;
						}
            $saveInstructions = $save_button_text;
            $breakHTML .= "<p class='no-print'>" . $save_button_text;
						if($done_button_text) {
              $doneInstructions = $done_button_text;
              $breakHTML .= "<br>" . $done_button_text;
						}
                    }
					$breakHTML .= "</p></td></tr></table>";
                    print $breakHTML;