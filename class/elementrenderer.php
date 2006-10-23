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
	function constructElement($form_ele_id, $ele_value, $entry){
		global $xoopsUser, $xoopsModuleConfig, $separ, $myts;
		$myts =& MyTextSanitizer::getInstance();
		
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
		$ele_caption = stripslashes($ele_caption);
		// next line commented out to accomodate passing of ele_value from index.php
		// $ele_value = $this->_ele->getVar('ele_value');
		$e = $this->_ele->getVar('ele_type');

//multilangue
        $ele_caption = $myts->displayTarea($ele_caption);
		// ele_desc added June 6 2006 -- jwe
		$ele_desc = $this->_ele->getVar('ele_desc');

		switch ($e){
			case 'ib':
				if(get_magic_quotes_gpc()) {
					$ele_value[0] = stripslashes($ele_value[0]);
 				} 
				$form_ele = $ele_value; // an array, item 0 is the contents of the break, item 1 is the class of the table cell (for when the form is table rendered)
				break;
			case 'text':
				$ele_value[2] = stripslashes($ele_value[2]);
//        $ele_value[2] = $myts->displayTarea($ele_value[2]); // commented by jwe 12/14/04 so that info displayed for viewing in a form box does not contain HTML formatting
				
				include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";
				$ele_value[2] = getTextboxDefault($ele_value[2]);

				$form_ele = new XoopsFormText(
					$ele_caption,
					$form_ele_id,
					$ele_value[0],	//	box width
					$ele_value[1],	//	max width
					$ele_value[2]	  //	default value
				);
			break;
			
			case 'textarea':
				$ele_value[0] = stripslashes($ele_value[0]);
//        $ele_value[0] = $myts->displayTarea($ele_value[0]); // commented by jwe 12/14/04 so that info displayed for viewing in a form box does not contain HTML formatting
				include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";
				$ele_value[0] = getTextboxDefault($ele_value[0]);

				$form_ele = new XoopsFormTextArea(
					$ele_caption,
					$form_ele_id,
					$ele_value[0],	//	default value
					$ele_value[1],	//	rows
					$ele_value[2]	  //	cols
				);
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

					// NOTE:
					// boxproperties[0] is form_id
					// [1] is caption of linked field
					// [2] is a series of entries separated by another custom separator that we explode into the selection array.
					$form_ele = new XoopsFormSelect($ele_caption, $form_ele_id, '', $ele_value[0], $ele_value[1]);

// add the initial default entry, singular or plural based on whether the box is multiple or not.
if($ele_value[0] == 1)
{
	$form_ele->addOption("none", _AM_FORMLINK_PICK);
}

// add in a query to limit the elements displays in the linked select box, limit determined by the group permissions on this link that have been established in the admin side of the module. -- jwe 8/29/04

// $form_ele_id is the ele_id of the current link select box, but we have to remove "ele_" from the front of it.
//print "form_ele_id: $form_ele_id<br>"; // debug code
$true_ele_id = str_replace("ele_", "", $form_ele_id);

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

// query below modified to include pguidq which will limit the returned values to just the ones that are allowed for this user's groups to see -- jwe 8/29/04
					$boxprop1_formform = str_replace("'", "`", stripslashes($boxproperties[1])); // sept 2 2005 -- convert to formform format
//if($_GET['sdebug']) { print "<br>SELECT ele_value, ele_id FROM " . $xoopsDB->prefix("formulize_form") . " WHERE id_form=$boxproperties[0] AND ele_caption=\"$boxprop1_formform\" $pguidq ORDER BY ele_value"; } // GROUP BY ele_value ORDER BY ele_value";
					$linkedvaluesq = "SELECT ele_value, ele_id FROM " . $xoopsDB->prefix("formulize_form") . " WHERE id_form=$boxproperties[0] AND ele_caption=\"$boxprop1_formform\" $pguidq ORDER BY ele_value"; // GROUP BY ele_value ORDER BY ele_value"; // GROUP BY removed 8/12/05 in order to allow duplicate listings in linked select boxes
					$reslinkedvaluesq = mysql_query($linkedvaluesq);
					if($reslinkedvaluesq)
					{
						while($rowlinkedvaluesq = mysql_fetch_row($reslinkedvaluesq))
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
				} 
				else // or if we don't have a link...
				{
				$selected = array();
				$options = array();
				// set opt_count to 1 if the box is NOT a multiple selection box. -- jwe 7/26/04
				if($ele_value[1])
				{
					$opt_count = 0;
				}
				else
				{
					$opt_count = 1;
				}	
				while( $i = each($ele_value[2]) ){

					// handle requests for full names or usernames -- will only kick in if there is no saved value (otherwise ele_value will have been rewritten by the loadValues function in the form display
					// note: if the user is about to make a proxy entry, then the list of users displayed will be from their own groups, but not from the groups of the user they are about to make a proxy entry for.  ie: until the proxy user is known, the choice of users for this list can only be based on the current user.  This could lead to confusing or buggy situations, such as users being selected who are outside the groups of the proxy user (who will become the owner) and so there will be an invalid value stored for this element in the db.
					if($i['key'] === "{FULLNAMES}" OR $i['key'] === "{USERNAMES}") { // ADDED June 18 2005 to handle pulling in usernames for the user's group(s)
						if($i['key'] == "{FULLNAMES}") { $nametype = "name"; }
						if($i['key'] == "{USERNAMES}") { $nametype = "uname"; }
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
						if( $i['value'] > 0 ){
							$selected[] = $opt_count;
						}
						$opt_count++;
					}
				}
				$form_ele = new XoopsFormSelect(
					$ele_caption,
					$form_ele_id,
					$selected,
					$ele_value[0],	//	size
					$ele_value[1]	  //	multiple
				);
				$form_ele->addOptionArray($options);
				} // end of if we have a link on our hands. -- jwe 7/29/04
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
				switch($delimSetting){
					case 'space':
						$form_ele = new XoopsFormCheckBox(
							$ele_caption,
							$form_ele_id,
							$selected
						);
						$counter = 0; // counter used for javascript that works with 'Other' box
						while( $o = each($options) ){
							$other = $this->optOther($o['value'], $form_ele_id, $entry, $counter, true);
							if( $other != false ){
								$form_ele->addOption($o['key'], _formulize_OPT_OTHER.$other);
							}else{
								$form_ele->addOption($o['key'], $o['value']);
							}
							$counter++;
						}
						$form_ele->setExtra("onchange=\"javascript:formulizechanged=1;\"");
					break;
					default:
						$form_ele = new XoopsFormElementTray($ele_caption, $delimSetting);
						$counter = 0; // counter used for javascript that works with 'Other' box
						while( $o = each($options) ){
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
							}
							$t->setExtra("onchange=\"javascript:formulizechanged=1;\"");
							$form_ele->addElement($t);
							$counter++;
						}
					break;
				}
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
				switch($delimSetting){
					case 'space':
						$form_ele1 = new XoopsFormRadio(
							'',
							$form_ele_id,
							$selected
						);
						$counter = 0;
						while( $o = each($options) ){
							$other = $this->optOther($o['value'], $form_ele_id, $entry, $counter);
							if( $other != false ){
								$form_ele1->addOption($o['key'], _formulize_OPT_OTHER.$other);
							}else{
								$form_ele1->addOption($o['key'], $o['value']);
							}
							$counter++;
						}
						$form_ele1->setExtra("onchange=\"javascript:formulizechanged=1;\"");
					break;
					default:
						$form_ele1 = new XoopsFormElementTray('', $delimSetting);
						$counter = 0;
						while( $o = each($options) ){
							$t =& new XoopsFormRadio(
								'',
								$form_ele_id,
								$selected
							);
							$other = $this->optOther($o['value'], $form_ele_id, $entry, $counter);
							if( $other != false ){
								$t->addOption($o['key'], _formulize_OPT_OTHER.$other);
							}else{
								$t->addOption($o['key'], $o['value']);
							}
							$t->setExtra("onchange=\"javascript:formulizechanged=1;\"");
							$form_ele1->addElement($t);
							$counter++;
						}
					break;
				}
				$form_ele = new XoopsFormLabel(
					$ele_caption,
					"<nobr>" . $form_ele1->render() . "</nobr>"
				);

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


}
?>