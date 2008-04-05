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

class formulizeElementRenderer{
	var $_ele;

	function formulizeElementRenderer(&$element){
		$this->_ele =& $element;
	}

	// function params modified to accept passing of $ele_value from index.php
	// $entry added June 1 2006 as part of 'Other' option for radio buttons and checkboxes
	function constructElement($form_ele_id, $ele_value, $entry, $isDisabled=false){
		global $xoopsUser, $xoopsModuleConfig, $separ, $myts;
		$myts =& MyTextSanitizer::getInstance();
		
		// $form_ele_id contains the ele_id of the current link select box, but we have to remove "ele_" from the front of it.
		//print "form_ele_id: $form_ele_id<br>"; // debug code
		$true_ele_id = str_replace("ele_", "", $form_ele_id);
		
		// added July 6 2005.
		if(!$xoopsModuleConfig['delimeter']) {
			// assume that we're accessing a form from outside the Formulize module, therefore the Formulize delimiter setting is not available, so we have to query for it directly.
			global $xoopsDB;
			$delimq = q("SELECT conf_value FROM " . $xoopsDB->prefix("config") . ", " . $xoopsDB->prefix("modules") . " WHERE " . $xoopsDB->prefix("modules") . ".mid=" . $xoopsDB->prefix("config") . ".conf_modid AND " . $xoopsDB->prefix("modules") . ".dirname=\"formulize\" AND " . $xoopsDB->prefix("config") . ".conf_name=\"delimeter\"");
			$delimSetting = $delimq[0]['conf_value'];
		} else {
			$delimSetting = $xoopsModuleConfig['delimeter'];
		}

		$id_form = $this->_ele->getVar('id_form');
		$ele_caption = $this->_ele->getVar('ele_caption', 'e');
		$ele_caption = preg_replace('/\{SEPAR\}/', '', $ele_caption);
		// $ele_caption = stripslashes($ele_caption);
		// next line commented out to accomodate passing of ele_value from index.php
		// $ele_value = $this->_ele->getVar('ele_value');
		$e = $this->_ele->getVar('ele_type');


		// call the text sanitizer, first try to convert HTML chars, and if there were no conversions, then do a textarea conversion to automatically make links clickable
		$htmlCaption = $myts->undoHtmlSpecialChars($ele_caption);
		if($htmlCaption == $ele_caption) {
        	$ele_caption = $myts->displayTarea($ele_caption);
		} else {
			$ele_caption = $htmlCaption;
		}
		// ele_desc added June 6 2006 -- jwe
		$ele_desc = $this->_ele->getVar('ele_desc');

		switch ($e){
			case 'derived':
				// quick hack to get these in for elementdisplay.php which relies on the element renderer.
				// does not work with Frameworks
				static $derivedValueData = array();
				$baseEntryForDerivation = $entry ? $entry : "new";
				//print $this->_ele->getVar('ele_caption') . " -- $baseEntryForDerivation<br>";
				if(!isset($derivedValueData[$baseEntryForDerivation])) {
					include_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php";
					$GLOBALS['formulize_onForm'] = true;
					$derivedValueData[$baseEntryForDerivation] = getData($frid, $id_form, $baseEntryForDerivation);
					$GLOBALS['formulize_onForm'] = false;
				}
				$elementHandle = $frid ? handleFromId($this->_ele->getVar('ele_id'), $fid, $frid) : $this->_ele->getVar('ele_id');
				$form_ele = new xoopsFormLabel($this->_ele->getVar('ele_caption'), display($derivedValueData[$baseEntryForDerivation][0], $elementHandle));
				break;

			case 'ib':
				if(get_magic_quotes_gpc()) {
					$ele_value[0] = stripslashes($ele_value[0]);
 				}
				if(trim($ele_value[0]) == "") { $ele_value[0] = $ele_caption; }
				$form_ele = $ele_value; // an array, item 0 is the contents of the break, item 1 is the class of the table cell (for when the form is table rendered)
				break;
			case 'text':
				$ele_value[2] = stripslashes($ele_value[2]);
//        $ele_value[2] = $myts->displayTarea($ele_value[2]); // commented by jwe 12/14/04 so that info displayed for viewing in a form box does not contain HTML formatting
				
				include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";
				$ele_value[2] = getTextboxDefault($ele_value[2]);

				if (!strstr(getCurrentURL(),"printview.php")) { 				// nmc 2007.03.24 - added
					
					$form_ele = new XoopsFormText(
					$ele_caption,
					$form_ele_id,
					$ele_value[0],	//	box width
					$ele_value[1],	//	max width
					$ele_value[2]	  //	default value
					);
					if($isDisabled) {
						$form_ele = $this->formulize_disableElement($form_ele);
					}
				} else {															// nmc 2007.03.24 - added 
					$form_ele = new XoopsFormLabel ($ele_caption, $ele_value[2]);	// nmc 2007.03.24 - added 
				}

			break;
			
			case 'textarea':
				$ele_value[0] = stripslashes($ele_value[0]);
//        $ele_value[0] = $myts->displayTarea($ele_value[0]); // commented by jwe 12/14/04 so that info displayed for viewing in a form box does not contain HTML formatting
				include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";
				$ele_value[0] = getTextboxDefault($ele_value[0]);
				if (!strstr(getCurrentURL(),"printview.php")) { 				// nmc 2007.03.24 - added 
				$form_ele = new XoopsFormTextArea(
					$ele_caption,
					$form_ele_id,
					$ele_value[0],	//	default value
					$ele_value[1],	//	rows
					$ele_value[2]	  //	cols
				);
					if($isDisabled) {
						$form_ele = $this->formulize_disableElement($form_ele);
					}
								
					}															// nmc 2007.03.24 - added 
				else {															// nmc 2007.03.24 - added 
					$form_ele = new XoopsFormLabel ($ele_caption, $ele_value[0]);	// nmc 2007.03.24 - added 
					}
			break;
			case 'areamodif':
				$ele_value[0] =  stripslashes($ele_value[0]);
        $ele_value[0] = $myts->displayTarea($ele_value[0]);
				$form_ele = new XoopsFormLabel(
					$ele_caption,
					$ele_value[0]
				);
			break;
			
			case 'select':
				if(strstr($ele_value[2], "#*=:*")) // if we've got a link on our hands... -- jwe 7/29/04
				{
					global $xoopsDB;
					// gather the values from the selected field
					// 1. split the value of formlink into the formid and the caption
					// 2. use this info to gather the values from the field selected field
					array($gatheredentries);
					array($selectedvalues);
					array($boxproperties);

					$boxproperties = explode("#*=:*", $ele_value[2]);
					$selectedvalues = explode("[=*9*:", $boxproperties[2]);
					// handle all possible apostrophe screwiness -- September 3 2007 -- a safeguard more than anything, these searches should never find anything
					$boxproperties[1] = str_replace("`", "'", $boxproperties[1]);
					$boxproperties[1] = str_replace("&#039;", "'", $boxproperties[1]);
					$boxproperties[1] = str_replace("&quot;", "'", $boxproperties[1]);

					// NOTE:
					// boxproperties[0] is form_id
					// [1] is caption of linked field
					// [2] is a series of entries separated by another custom separator that we explode into the selection array.
					$form_ele = new XoopsFormSelect($ele_caption, $form_ele_id, '', $ele_value[0], $ele_value[1]);

// add the initial default entry, singular or plural based on whether the box is one line or not.
if($ele_value[0] == 1)
{
	$form_ele->addOption("none", _AM_FORMLINK_PICK);
}

// add in a query to limit the elements displays in the linked select box, limit determined by the group permissions on this link that have been established in the admin side of the module. -- jwe 8/29/04

// grab the user's groups and the module id
global $regcode;
if($regcode) { // if we're dealing with a registration code, determine group membership based on the code
	$reggroupsq = q("SELECT reg_codes_groups FROM " . XOOPS_DB_PREFIX . "_reg_codes WHERE reg_codes_code=\"$regcode\"");
	$groupuser = explode("&8(%$", $reggroupsq[0]['reg_codes_groups']);
	if($groupuser[0] === "") { unset($groupuser); } // if a code has no groups associated with it, then kill the null value that will be in position 0 in the groups array.
	$groupuser[] = XOOPS_GROUP_USERS;
	$groupuser[] = XOOPS_GROUP_ANONYMOUS;
} else {
	$groupuser = $xoopsUser ? $xoopsUser->getGroups() : XOOPS_GROUP_ANONYMOUS;
}
$module_id = getFormulizeModId();
global $xoopsDB;

// grab the target groups for this link as specified for all the user's groups...

$start = 1;
foreach($groupuser as $agrp) // setup a query based on all the user's groups
{
	if($start)
	{
		$agrpq = "gperm_groupid = \"$agrp\"";
		$start=0;
	}
	else
	{
		$agrpq .= " OR gperm_groupid = \"$agrp\"";
	}
}
// query for the groups that links are permitted for, based on the user's groups and this link box.
$linkscopepermq = "SELECT gperm_itemid FROM " . $xoopsDB->prefix("group_permission") . " WHERE ($agrpq) AND gperm_modid=\"$module_id\" AND gperm_name=\"$true_ele_id\"";
//print "$linkscopepermq<br>"; // debug code
$reslsq = $xoopsDB->query($linkscopepermq);
$pgroups = array();
while ($rowlsq = $xoopsDB->fetchRow($reslsq)) // loop through all the itemids (permitted groups) found and save them in an array...
{
	$pgroups[] = $rowlsq[0];
}

// handle new linkscope option -- August 30 2006
$emptylist = false;
if($ele_value[3]) {
	$scopegroups = explode(",",$ele_value[3]);
	if(!in_array("all", $scopegroups)) {
		if($ele_value[4]) { // limit by user's groups
			foreach($groupuser as $gid) { // want to loop so we can get rid of reg users group simply
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
			foreach($groupuser as $gid) { // want to loop so we can get rid of reg users group simply
				if($gid == XOOPS_GROUP_USERS) { continue; }
				$pgroups[] = $gid;
			}
		} else { // all groups should be used
			unset($pgroups);
			$allgroupsq = q("SELECT groupid FROM " . $xoopsDB->prefix("groups") . " WHERE groupid != " . XOOPS_GROUP_USERS);
			foreach($allgroupsq as $thisgid) {
				$pgroups[] = $thisgid['groupid'];
			}
		}
	}
}

// Note: OLD WAY: if no groups were found, then pguidq will be empty and so all entries will be shown, no restrictions
// NEW WAY: if a specific group(s) was specified, and no match with the current user was found, then we return an empty list
array_unique($pgroups); // remove duplicate groups from the list
//print_r ($pgroups); // debug code
//print "<br>"; // debug code
if(!$emptylist) {
	$start = 1;
	foreach($pgroups as $agrp2) // setup a query based on all these groups
	{
		if(isset($_GET['sdebug'])) { print "$agrp2<br>"; }
		if($start)
		{
			$agrpq2 = "groupid = " . $agrp2;
			$start=0;
		}
		else
		{
			$agrpq2 .= " OR groupid = " . $agrp2;
		}
	}
	$puserq = "SELECT uid FROM " . $xoopsDB->prefix("groups_users_link") . " WHERE $agrpq2";
	//print "$puserq<br>"; // debug code
	$respuq = $xoopsDB->query($puserq);
	while ($rowpuq = $xoopsDB->fetchRow($respuq)) // build the pguidq string for use in the next query...
	{
		$pguid[] = $rowpuq[0];
	}
	array_unique($pguid); // remove duplicate users from the list
	$start = 1;
	foreach($pguid as $apuid) // setup the pguidq based on all these users
	{
		if($start)
		{
			$pguidq = "AND (uid = " . $apuid;
			$start=0;
		}
		else
		{
			$pguidq .= " OR uid = " . $apuid;
		}
	}
	if($pguidq) { $pguidq .= ")"; } // close the pguidq if it has been started
} else {
	$pguidq = "AND (uid < 0)";
} // end of if emptylist


// determine the filter conditions if any, and the allowable id_reqs
$id_reqq = ""; 
if(is_array($ele_value[5])) {
	$filterElements = $ele_value[5][0];
	$filterOps = $ele_value[5][1];
	$filterTerms = $ele_value[5][2];
	include_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php";
	$start = true;
	for($filterId = 0;$filterId<count($filterElements);$filterId++) {
		if($ops[$i] == "NOT") { $ops[$i] = "!="; }
		if(!$start) {
			$filter .= "][";
		}
		$start = false;
		$filter .= $filterElements[$filterId]."/**/".$filterTerms[$filterId]."/**/".$filterOps[$filterId];
	}
	$id_reqs = getData("", $boxproperties[0], $filter, "AND", $pguidq, false, "", "", 0, 0, 0, false, "", true); // IDREQS ONLY, only works with the main form!! returns array where keys and values are the id_reqs
	$pguidq = ""; // scope filter does not need to be applied again since it has already limited the chosen id_reqs
	if(count($id_reqs) > 0) {
		$id_reqq = "AND (id_req IN(" .implode(",",$id_reqs) . ")";
		if($selectedvalues[0] !== "") {
			$id_reqq .= " OR ele_id IN(" . implode(",",$selectedvalues) . ")"; // always include the selected values even if they are outside the normal selection params (item could have been selected, then over time other properties change...that doesn't mean it should be unselected now)
		}
		$id_reqq .= ")";
	} 
} 

					// query below modified to include pguidq which will limit the returned values to just the ones that are allowed for this user's groups to see -- jwe 8/29/04

					// query below modified to include an id_req filter for cases where there are filter conditions in effect.  allowable id_reqs are predetermined and then limit the values that are returned. -- jwe feb 6 2008

					$boxprop1_formform = str_replace("'", "`", stripslashes($boxproperties[1])); // sept 2 2005 -- convert to formform format
					$boxprop1_formform = str_replace("&#039;", "`", $boxprop1_formform); // November 13, 2006 -- handle new HTML chars DB storage format
					$boxprop1_formform = str_replace("&quot;", "`", $boxprop1_formform); 
					$linkedvaluesq = "SELECT ele_value, ele_id FROM " . $xoopsDB->prefix("formulize_form") . " WHERE id_form=$boxproperties[0] AND ele_caption=\"$boxprop1_formform\" $pguidq $id_reqq ORDER BY ele_value"; // GROUP BY ele_value ORDER BY ele_value"; // GROUP BY removed 8/12/05 in order to allow duplicate listings in linked select boxes
					$reslinkedvaluesq = $xoopsDB->query($linkedvaluesq);
					
					// Check to see if there's more than 50 options, and if so, render as newfangled combobox
					// Pretty darn inefficient to do this here, we should have a flag on the element itself to indicate how it should be rendered, but
					// this will suffice for the time being
					// Only works for dropdown boxes!!!
					// turned off for now until the UI (and bugs?) are better worked out
					/*if($xoopsDB->getRowsNum($reslinkedvaluesq) > 50) {
						unset($form_ele);
						// must figure out element ID of the linked field, through the caption
						$getIdFromCap = "SELECT ele_id FROM " . $xoopsDB->prefix("formulize") . " WHERE ele_caption = \"" . mysql_real_escape_string($boxproperties[1]) . "\" AND id_form=" . $boxproperties[0] . " LIMIT 0,1";
						$getIdFromCapRes = $xoopsDB->query($getIdFromCap);
						$getIdFromCapRow = $xoopsDB->fetchRow($getIdFromCapRes);
						// must get current value to put in box as default
						if($selectedvalues[0]) {
							$getSelVal = "SELECT ele_value FROM " .$xoopsDB->prefix("formulize_form") . " WHERE ele_id=" . intval($selectedvalues[0]) . " LIMIT 0,1";
							$getSelValRes = $xoopsDB->query($getSelVal);
							$getSelValRow = $xoopsDB->fetchRow($getSelValRes);
						} else {
							$getSelValRow = array(0=>"");
						}
						$renderedComboBox = $this->formulize_renderAutoCompleteBox($form_ele_id, $id_form, $boxproperties[0] . "#*=:*" . $boxproperties[1] . "#*=:*", $getIdFromCapRow[0], $boxproperties[0], $getSelValRow[0], $selectedvalues[0]);
						$form_ele = new xoopsFormLabel($ele_caption, $renderedComboBox);
			
					} else {*/
						

						if($reslinkedvaluesq)
						{
							while($rowlinkedvaluesq = $xoopsDB->fetchRow($reslinkedvaluesq))
							{
								$slashfreevalue = stripslashes($rowlinkedvaluesq[0]);
								$form_ele->addOption($boxproperties[0] . "#*=:*" . $boxproperties[1] . "#*=:*" . $rowlinkedvaluesq[1], $slashfreevalue); // form_id, caption and ele_id from form_form are the value, value from form_form is name.
								foreach($selectedvalues as $thisselection)
								{
									if($thisselection == $rowlinkedvaluesq[1]) // if this is our selected entry...set it as the default
									{
										$form_ele->setValue($boxproperties[0] . "#*=:*" . $boxproperties[1] . "#*=:*" . $rowlinkedvaluesq[1]);
									}
								}
							}
						}
					//}// the end of the commented condition above where the auto-complete box is
					
				} 
				else // or if we don't have a link...
				{
					$selected = array();
					$options = array();
					
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
									$groups = $xoopsUser ? $xoopsUser->getGroups() : XOOPS_GROUP_ANONYMOUS;
								}
							}
							$pgroups = array();
							if($ele_value[3]) {
								$scopegroups = explode(",",$ele_value[3]);
										if(!in_array("all", $scopegroups)) {
									if($ele_value[4]) { // limit by users's groups
												foreach($groups as $gid) { // want to loop so we can get rid of reg users group simply
													if($gid == XOOPS_GROUP_USERS) { continue; }
													if(in_array($gid, $scopegroups)) {
														$pgroups[] = $gid;
													}
												}
												if(count($pgroups) > 0) { 
													unset($groups);
													$groups = $pgroups;
												} else {
													$groups = array();
												}
											} else { // don't limit by user's groups
										$groups = $scopegroups;
									}
								} else { // use all
									if(!$ele_value[4]) { // really use all (otherwise, we're just going will all user's groups, so existing value of $groups will be okay
										unset($groups);
										global $xoopsDB;
										$allgroupsq = q("SELECT groupid FROM " . $xoopsDB->prefix("groups") . " WHERE groupid != " . XOOPS_GROUP_USERS);
										foreach($allgroupsq as $thisgid) {
											$groups[] = $thisgid['groupid'];
										}
									} 
								}
							}
							$namelist = gatherNames($groups, $nametype);
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
	
					$form_ele1 = new XoopsFormSelect(
						$ele_caption,
						$form_ele_id,
						$selected,
						$ele_value[0],	//	size
						$ele_value[1]	  //	multiple
					);
	
					// must check the options for uitext before adding to the element -- aug 25, 2007
					foreach($options as $okey=>$ovalue) {
						$options[$okey] = $this->formulize_swapUIText($ovalue, $this->_ele->getVar('ele_uitext'));
					}
					$form_ele1->addOptionArray($options);
	
					$renderedHoorvs = "";
					if(count($hiddenOutOfRangeValuesToWrite) > 0) {
						foreach($hiddenOutOfRangeValuesToWrite as $hoorKey=>$hoorValue) {
							$thisHoorv = new xoopsFormHidden('formulize_hoorv_'.$true_ele_id.'_'.$hoorKey, $hoorValue);
							$renderedHoorvs .= $thisHoorv->render() . "\n";
							unset($thisHoorv);
						}
					}
	
					$form_ele = new XoopsFormLabel(
						$ele_caption,
						"<nobr>" . $form_ele1->render() . "</nobr>\n" . $renderedHoorvs
					);

				
				} // end of if we have a link on our hands. -- jwe 7/29/04
				
				// set required validation code
					if($this->_ele->getVar('ele_req')) {
						$eltname = $form_ele_id;
						$eltcaption = $ele_caption;
						$eltmsg = empty($eltcaption) ? sprintf( _FORM_ENTER, $eltname ) : sprintf( _FORM_ENTER, $eltcaption );
						$eltmsg = str_replace('"', '\"', stripslashes( $eltmsg ) );
						if($ele_value[0] == 1) { 
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
				
				
			break;
			
			case 'checkbox':
				$selected = array();
				$options = array();
				$opt_count = 1;
				while( $i = each($ele_value) ){
					$options[$opt_count] = $myts->stripSlashesGPC($i['key']);
					if( $i['value'] > 0 ){
						$selected[] = $opt_count;
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
							$o = $this->formulize_swapUIText($o, $this->_ele->getVar('ele_uitext'));
							$other = $this->optOther($o['value'], $form_ele_id, $entry, $counter, true);
							if( $other != false ){
								$form_ele1->addOption($o['key'], _formulize_OPT_OTHER.$other);
							}else{
								$form_ele1->addOption($o['key'], $o['value']);
								if(strstr($o['value'], _formulize_OUTOFRANGE_DATA)) {
									$hiddenOutOfRangeValuesToWrite[$o['key']] = str_replace(_formulize_OUTOFRANGE_DATA, "", $o['value']); // if this is an out of range value, grab the actual value so we can stick it in a hidden element later
								}
							}
							$counter++;
						}
						$form_ele1->setExtra("onchange=\"javascript:formulizechanged=1;\"");
					break;
					default:
						$form_ele1 = new XoopsFormElementTray($ele_caption, $delimSetting);
						$counter = 0; // counter used for javascript that works with 'Other' box
						while( $o = each($options) ){
							$o = $this->formulize_swapUIText($o, $this->_ele->getVar('ele_uitext'));
							$t =& new XoopsFormCheckBox(
								'',
								$form_ele_id.'[]',
								$selected
							);
							$other = $this->optOther($o['value'], $form_ele_id, $entry, $counter, true);
							if( $other != false ){
								$t->addOption($o['key'], _formulize_OPT_OTHER.$other);
							}else{
								$t->addOption($o['key'], $o['value']);
								if(strstr($o['value'], _formulize_OUTOFRANGE_DATA)) {
									$hiddenOutOfRangeValuesToWrite[$o['key']] = str_replace(_formulize_OUTOFRANGE_DATA, "", $o['value']); // if this is an out of range value, grab the actual value so we can stick it in a hidden element later
								}
							}
							$t->setExtra("onchange=\"javascript:formulizechanged=1;\"");
							$form_ele1->addElement($t);
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
				
				$form_ele = new XoopsFormLabel(
					$ele_caption,
					"<nobr>" . $form_ele1->render() . "</nobr>\n" . $renderedHoorvs
				);
				
				
			break;
			
			case 'radio':
			case 'yn':
				$selected = '';
				$options = array();
				$opt_count = 1;
				while( $i = each($ele_value) ){
					switch ($e){
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
							$o = $this->formulize_swapUIText($o, $this->_ele->getVar('ele_uitext'));
							$other = $this->optOther($o['value'], $form_ele_id, $entry, $counter);
							if( $other != false ){
								$form_ele1->addOption($o['key'], _formulize_OPT_OTHER.$other);
							}else{
								$o['value'] = get_magic_quotes_gpc() ? stripslashes($o['value']) : $o['value'];
								$form_ele1->addOption($o['key'], $o['value']);
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
							$o = $this->formulize_swapUIText($o, $this->_ele->getVar('ele_uitext'));
							$t =& new XoopsFormRadio(
								'',
								$form_ele_id,
								$selected
							);
							$other = $this->optOther($o['value'], $form_ele_id, $entry, $counter);
							if( $other != false ){
								$t->addOption($o['key'], _formulize_OPT_OTHER.$other);
							}else{
								$o['value'] = get_magic_quotes_gpc() ? stripslashes($o['value']) : $o['value'];
								$t->addOption($o['key'], $o['value']);
								if(strstr($o['value'], _formulize_OUTOFRANGE_DATA)) {
									$hiddenOutOfRangeValuesToWrite[$o['key']] = str_replace(_formulize_OUTOFRANGE_DATA, "", $o['value']); // if this is an out of range value, grab the actual value so we can stick it in a hidden element later
								}
							}
							$t->setExtra("onchange=\"javascript:formulizechanged=1;\"");
							$form_ele1->addElement($t);
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
				$form_ele = new XoopsFormLabel(
					$ele_caption,
					"<nobr>" . $form_ele1->render() . "</nobr>\n" . $renderedHoorvs
				);

				if($this->_ele->getVar('ele_req')) {
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
					$form_ele = new XoopsFormTextDateSelect (
						$ele_caption,
						$form_ele_id,
						15,
						strtotime($ele_value[0])
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
				if($isDisabled) {
						$form_ele = $this->formulize_disableElement($form_ele, 'date');
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
				return false;
			break;
		}
		if($ele_desc != ""  AND is_object($form_ele)) { $form_ele->setDescription($myts->undoHtmlSpecialChars($ele_desc)); }
		return $form_ele;
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
		// displayElement elements will be in the format de_{id_req}_{ele_id} (deh?)
		// regular elements will be in the format ele_{ele_id}
		if(count($id_parts) == 3) {
			$ele_id = $id_parts[2];
		} else {
			$ele_id = $id_parts[1];
		}
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

	function formulize_renderAutoCompleteBox($form_ele_id, $form_id, $passBackIdPrefix, $source_element_id, $source_form_id, $default_value, $default_ele_id) {
		static $numberOfBoxes = 0;
		if(!$numberOfBoxes) {
			$output .= "<!-- Dependencies -->\n
<script type=\"text/javascript\" src=\"http://yui.yahooapis.com/2.2.2/build/yahoo-dom-event/yahoo-dom-event.js\"></script>\n
<!-- OPTIONAL: Connection (required only if using XHR DataSource) -->\n
<script type=\"text/javascript\" src=\"http://yui.yahooapis.com/2.2.2/build/connection/connection-min.js\"></script>\n
<!-- OPTIONAL: Animation (required only if enabling animation) -->\n
<script type=\"text/javascript\" src=\"http://yui.yahooapis.com/2.2.2/build/animation/animation-min.js\"></script>\n
<!-- OPTIONAL: External JSON parser from http://www.json.org/ (enables JSON validation) -->\n
<script type=\"text/javascript\" src=\"http://www.json.org/json.js\"></script>\n
<!-- Source file -->\n
<script type=\"text/javascript\" src=\"http://yui.yahooapis.com/2.2.2/build/autocomplete/autocomplete-min.js\"></script>\n";
		}
		$numberOfBoxes++;
		// end of stuff that happens once
		// start of rendering of each specific combobox

		$output .= "<style>\n
#autoCompleteBox$numberOfBoxes {width:300px;}\n
#autoCompleteContainer$numberOfBoxes {position:absolute;z-index:9050;}\n
#autoCompleteContainer$numberOfBoxes .yui-ac-content {position:absolute;left:0;top:0;width:300px;border:1px solid #404040;background:#fff;overflow:hidden;text-align:left;z-index:9050;}\n
#autoCompleteContainer$numberOfBoxes ul {padding:5px 0;width:100%;}\n
#autoCompleteContainer$numberOfBoxes li {padding:0 5px;cursor:default;white-space:nowrap;font-family:arial,helvetica,sans-serif;font-size:12pt;list-style: none;}\n
#autoCompleteContainer$numberOfBoxes li.yui-ac-highlight {background:lightgrey;}\n
</style>\n";
		
		$output .= "<input id=\"autoCompleteBox$numberOfBoxes\" type=\"text\" value=\"$default_value\" onchange=\"javascript:formulizechanged=1;\">\n";
		$output .= "<div id=\"autoCompleteContainer$numberOfBoxes\"></div>\n";
		$output .= "<input id=\"autoCompleteValueBox$numberOfBoxes\" name=\"$form_ele_id\" value=\"$passBackIdPrefix$default_ele_id\" type=\"hidden\">\n";

		$output .= "<script type=\"text/javascript\">\n
 var myServer$numberOfBoxes = \"" . XOOPS_URL . "/modules/formulize/include/formulize_autoCompleteBox.php\";\n
 var mySchema$numberOfBoxes = [\"resultSet.result\", \"value\", \"id\"];\n 
 var myDataSource$numberOfBoxes = new YAHOO.widget.DS_XHR(myServer$numberOfBoxes, mySchema$numberOfBoxes);\n
 myDataSource$numberOfBoxes.scriptQueryAppend = \"ele_id=$source_element_id&form_id=$source_form_id\";\n
 \n
 var myAutoComp$numberOfBoxes = new YAHOO.widget.AutoComplete(\"autoCompleteBox$numberOfBoxes\",\"autoCompleteContainer$numberOfBoxes\", myDataSource$numberOfBoxes);\n
 myAutoComp$numberOfBoxes.forceSelection = false;\n
 \n
 // when an item is selected, stick it's ID into the right place\n
 myAutoComp$numberOfBoxes.itemSelectEvent.fire = function(oSelf, elItem, oData) {\n
    window.document.getElementById('autoCompleteValueBox$numberOfBoxes').value = '$passBackIdPrefix'+oData[1];\n
 }\n
</script>\n";

		return $output;
	}

	// THIS FUNCTION ADDS DISABLED=1 TO AN ELEMENT, AND CREATES A HIDDEN FIELD WITH THE CURRENT OR DEFAULT VALUE OF THE ELEMENT, SO IT IS SAVED WHEN A FORM IS SUBMITTED DESPITE BEING DISABLED
	function formulize_disableElement($element, $type) {
		$element->setExtra("disabled=1");
		$newElement = new xoopsFormElementTray($element->getCaption(), "\n");
		$element->setCaption('');
		switch($type) {
			case 'date':
				$hiddenValue = date("Y-m-d", $element->getValue());
				break;
			default:
				$hiddenValue = $element->getValue();
		}
		$newElement->addElement(new xoopsFormHidden($element->getName(), $hiddenValue));
		$element->setName('disabled_'.$element->getName());
		$newElement->addElement($element);
		
		return $newElement;
	}

	// THIS FUNCTION TAKES A VALUE AND THE UITEXT FOR THE ELEMENT, AND RETURNS THE UITEXT IN PLACE OF THE "DATA" TEXT
	function formulize_swapUIText($value, $uitexts) {
		// if value is an array, it has a key valled 'value', which needs to be swapped
		if(is_array($value)) {
			$value['value'] = isset($uitexts[$value['value']]) ? $uitexts[$value['value']] : $value['value'];
		} else {
			$value = isset($uitexts[$value]) ? $uitexts[$value] : $value;
		}
		return $value;
	}

}
?>