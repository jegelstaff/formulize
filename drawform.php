<?
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



	$xoopsOption['template_main'] = 'formulize.html';
	include_once XOOPS_ROOT_PATH.'/header.php';
	$criteria = new Criteria('ele_display', 1);
	$criteria->setSort('ele_order');
	$criteria->setOrder('ASC');
	$elements =& $formulize_mgr->getObjects2($criteria,$id_form);
	$form = new XoopsThemeForm($form2, 'formulize', XOOPS_URL.'/modules/formulize/index.php?title='.$title.'&reporting='.$reportingyn.'&reportname='.$report.'');
	$form->setExtra("enctype='multipart/form-data'") ; // impératif !
	include_once(XOOPS_ROOT_PATH . "/class/uploader.php");

	$count = 0;

	foreach( $elements as $i ){
		$ele_value = $i->getVar('ele_value');
		
		// modifications to handle displaying a previously entered record -- jwe 7/24/04
		// 1. findout what type of element we're currently dealing with
		// 2. match the caption of the element we're dealing with, with an entry in the DB, if there is one
		// 3. extract the value of the entry
		// 4. sub in the entry from the DB in place of the default value in ele_value
		// 5. let the rest of the script carry on drawing the form as usual.
	
		// template line here so that it can be overridden by something in viewentry if viewentry is the current state.
		$xoopsTpl->assign('tempaddingentry', _formulize_TEMP_ADDINGENTRY);
//		$xoopsTpl->assign('issingle', $issingle); // not needed intemplate any more
		if($showviewentries) // if viewing entries is permitted, then send the cue for showing them
		{
			$xoopsTpl->assign('formallowsviews', "on"); 
		}

		if($isadmin) // always allow module admins to view entries
		{
			$xoopsTpl->assign('issingle', "off"); //sends issingle=off to the template
		}

		if($viewentry)
		{


			$xoopsTpl->assign('tempaddingentry', _formulize_TEMP_EDITINGENTRY);

		
			$typejwe = $i->getVar('ele_type');
			$captionjwe = $i->getVar('ele_caption');
	
			// two lines to mimic how captions are written to the DB...
			$captionjwe = eregi_replace ("&#039;", "`", $captionjwe);
			$captionjwe = eregi_replace ("&quot;", "`", $captionjwe);

			// match the captions...
			$matchingcap = 0;
			foreach($reqCaptionsJwe as $capjwe)
			{
				if($captionjwe == $capjwe)
				{
					/* if we've found a match...
					print_r ($reqCaptionsJwe);
					print "<br>$captionjwe<br>";
					print "$capjwe<br>";
					print "$matchingcap<br>"; */ // debug code block
					break;
				}
				$matchingcap++;
			}

			$selectedValueJwe = $reqValuesJwe[$matchingcap];

			/*print_r($ele_value);
			print "<br>";*/ // debug block

			switch ($typejwe)
			{
				case "text":
					$ele_value[2] = $selectedValueJwe;								
					break;
				case "textarea":
					$ele_value[0] = $selectedValueJwe;								
					break;
				case "select":
				case "radio":
				case "checkbox":
					// NOTE:  unique delimiter used to identify LINKED select boxes, so they can be handled differently.
					if(strstr($selectedValueJwe, "#*=:*")) // if we've got a linked select box, then do everything differently
					{
						$ele_value[2] = $selectedValueJwe;
					}
					else
					{

					// put the array into another array (clearing all default values)
					// then we modify our place holder array and then reassign
					array ($temparrayjwe);
					if ($typejwe != "select")
					{
						$temparrayjwe = $ele_value;
					}
					else
					{
						$temparrayjwe = $ele_value[2];
					}					
					$temparraykeys = array_keys($temparrayjwe);

					$selvalarray = explode("*=+*:", $selectedValueJwe);
					
					foreach($temparraykeys as $keyjwe)
					{
						if($keyjwe == $selectedValueJwe) // if there's a straight match (not a multiple selection)
						{
							$temparrayjwe[$keyjwe] = 1;
						}
						elseif( in_array($keyjwe, $selvalarray) ) // or if there's a match within a multiple selection array)
						{
							$temparrayjwe[$keyjwe] = 1;
						}
						else // otherwise set to zero.
						{
							$temparrayjwe[$keyjwe] = 0;
						}
					}
					
					if ($typejwe != "select")
					{
						$ele_value = $temparrayjwe;
					}
					else
					{
						$ele_value[2] = $temparrayjwe;
					}
					} // end of IF we have a linked select box
					break;
				case "yn":

					if($selectedValueJwe == 1)
					{
						$ele_value = array("_YES"=>1, "_NO"=>0);
					}
					elseif($selectedValueJwe == 2)
					{
						$ele_value = array("_YES"=>0, "_NO"=>1);
					}
					else
					{
						$ele_value = array("_YES"=>0, "_NO"=>0);

					}
					break;
				case "date":

					$ele_value[0] = $selectedValueJwe;

					break;
			} // end switch

			/*print_r($ele_value);
			print "<br>";*/ //debug block
		}

		// ---------------- end mod to handle displaying an entry --jwe 7/24/04

		$renderer =& new formulizeElementRenderer($i);
		$form_ele =& $renderer->constructElement('ele_'.$i->getVar('ele_id'), $ele_value);

		if (isset ($ele_value[0])) {
			$ele_value[0] = eregi_replace("'", "`", $ele_value[0]);
			$ele_value[0] = stripslashes($ele_value[0]); } 

		if ($i->getVar('ele_type') == 'sep'){
			$ele_value = split ('<*>', $ele_value[0]);		
			foreach ($ele_value as $t){
				if (strpos($t, '<')!=false) {
					$ele_value[0] = $t;
			}	}
			$ele_value = split ('</', $ele_value[0]);			
			$hid = new XoopsFormHidden('ele_'.$i->getVar('ele_id'), $ele_value[0]);
			$form->addElement ($hid);
		}
		if ($i->getVar('ele_type') == 'areamodif'){
			$hid2 = new XoopsFormHidden('ele_'.$i->getVar('ele_id'), $ele_value[0]);
			$form->addElement ($hid2);
		}
		if ($i->getVar('ele_type') == 'upload'){
			$hid3 = new XoopsFormHidden($ele_value[1], $ele_value[1]);
			$form->addElement ($hid3);
		}
		$req = intval($i->getVar('ele_req'));
		$form->addElement($form_ele, $req);
		$count++;
		unset($hidden);
	}
	$form->addElement (new XoopsFormHidden ('counter', $count));
	// line below added to pass the viewentry setting onto the writing portion of index.php...  (and the editingent setting)
	$form->addElement (new XoopsFormHidden ('viewentry', $viewentry));
	$form->addElement (new XoopsFormHidden ('editingent', $editingent));


	// check if users have add permission and if they do then put in a submit button. -- jwe 7/28/04 -- updated 8/05/04 -- updated 8/28/04 to put in proxy entry capability
     	if ($theycanadd) {
	
		//print "$uid: $uid<br>";
		//print "$veuid: $veuid<br>";

		if($isadmin AND (($issingle AND !$editingent) OR (!$issingle AND !$viewentry))) // make a tray with a proxy entry dd box but only for form admins and only on their own entry or new entries in issingle forms, and on new entries in multi forms
		{			
			$submittray = new XoopsFormElementTray('', '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;');
			$submittray->addElement(new XoopsFormButton('', 'submit', _SUBMIT, 'submit'));
			$proxylist = new XoopsFormSelect('', 'proxyuser', 0, 1, FALSE);
			$proxylist->addOption('noproxy', _formulize_PICKAPROXY);

			//1. Get list of groups user is a member of
			//2. limit list to groups that can add to this form
			//3. Get list of users in those groups
			//4. Format list for box and send to tray

			// 1 and 2...
			array($ugrpadd);			
			$ugindexer = 0;
			foreach($groupuser as $ugp)
			{
				//print "usergroups: $ugp<br>";
				if(in_array($ugp, $groupidadd))
				{
					$ugrpadd[$ugindexer] = $ugp;
					$ugindexer++;
				}
			}

			// 3...

			$start = 1;
			foreach($ugrpadd as $agp)
			{
				if($start)
				{
					$uga = "groupid = $agp";
					$start = 0;
				}
				else
				{
					$uga .= " OR groupid = $agp";
				}
			}

			$proxyulistq = "SELECT uid FROM " . $xoopsDB->prefix("groups_users_link") . " WHERE $uga";
			//print $proxyulistq;
			$resproxyulistq = $xoopsDB->query($proxyulistq);
			while ($rowproxyulistq = $xoopsDB->fetchRow($resproxyulistq))
			{
				$puids[] = $rowproxyulistq[0];
				$uqueryforrealnames = "SELECT name FROM " . $xoopsDB->prefix("users") . " WHERE uid=$rowproxyulistq[0]";
				$uresqforrealnames = $xoopsDB->query($uqueryforrealnames);
				$urowqforrealnames = $xoopsDB->fetchRow($uresqforrealnames);
				$punames[] = $urowqforrealnames[0];
				//print "username: $urowqforrealnames[0]<br>";
				//$proxylist->addOption($rowproxyulistq[0], $urowqforrealnames[0]);
			}

			// alphabetize the proxy list added 11/2/04
			array_multisort($punames, $puids);
			for($i=0;$i<count($puids);$i++)
			{
				$proxylist->addOption($puids[$i], $punames[$i]);
			}

			$submittray->addElement($proxylist);
			$form->addElement($submittray);

		}
		elseif($uid == $veuid OR $isadmin OR !$viewentry OR ($issingle AND $hasgroupscope)) // only put in add button for their own entries, or all entries if they're an admin, or new entries, or any entry in a single-groupscope form
		{
			$form->addElement(new XoopsFormButton('', 'submit', _SUBMIT, 'submit'));
		}
	}
	


	//other template terms added by jwe 7/24/04
	$tempformurl = XOOPS_URL . "/modules/formulize/index.php?title=$title";
	$xoopsTpl->assign('tempformurl', $tempformurl);


	

	$xoopsTpl->assign('tempviewentries', _formulize_TEMP_VIEWENTRIES);

	$xoopsTpl->assign('theycanadd', $theycanadd);

	//assign isadmin and hasgroupscope to template, so we can hide notification options for non-admin users of non-groupscope forms -- jwe 09/03/05
	$xoopsTpl->assign('isadmin', $isadmin);
	$xoopsTpl->assign('hasgroupscope', $hasgroupscope);	


	//added by jwe 10/10/04 -- send id_form to template for use in the notifications block which is hard coded in (since the id_form cannot be accessed by the notifications system in the normal way on account of the title and not the id_form being used in the URL
	//send title too so the notification redirect is correct
	$xoopsTpl->assign('id_form', $id_form);
	$xoopsTpl->assign('title', $title);

	// do our own checking for subscribed events, for the same reason...  // added check to see that there is a user (ie: don't do this for anons) 10/27/04
	if($uid) {
	$notification_handler =& xoops_gethandler('notification');
	$subscribed_events =& $notification_handler->getSubscribedEvents("form", $id_form, $xoopsModule->getVar('mid'), $xoopsUser->getVar('uid'));
	$subscribedNew = in_array("new_entry", $subscribed_events) ? 1 : 0;
	$subscribedUp = in_array("update_entry", $subscribed_events) ? 1 : 0;
	$subscribedDel = in_array("delete_entry", $subscribed_events) ? 1 : 0;
	$xoopsTpl->assign('subNew', $subscribedNew);
	$xoopsTpl->assign('subUp', $subscribedUp);
	$xoopsTpl->assign('subDel', $subscribedDel);
	}
	$form->assign($xoopsTpl);


	include_once XOOPS_ROOT_PATH.'/footer.php';
?>