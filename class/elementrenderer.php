<?php

class formulizeElementRenderer{
	var $_ele;

	function formulizeElementRenderer(&$element){
		$this->_ele =& $element;
	}

	// function params modified to accept passing of $ele_value from index.php
	function constructElement($form_ele_id, $ele_value, $admin=false){
		global $xoopsUser, $xoopsModuleConfig, $separ, $myts;
		$myts =& MyTextSanitizer::getInstance();
		
		$id_form = $this->_ele->getVar('id_form');
		$ele_caption = $this->_ele->getVar('ele_caption', 'e');
		$ele_caption = preg_replace('/\{SEPAR\}/', '', $ele_caption);
		$ele_caption = stripslashes($ele_caption);
		// next line commented out to accomodate passing of ele_value from index.php
		// $ele_value = $this->_ele->getVar('ele_value');
		$e = $this->_ele->getVar('ele_type');

//multilangue
        $ele_caption = $myts->displayTarea($ele_caption);

		switch ($e){
			case 'text':
				$ele_value[2] = stripslashes($ele_value[2]);
        $ele_value[2] = $myts->displayTarea($ele_value[2]);
				if( !is_object($xoopsUser) ){
					$ele_value[2] = preg_replace('/\{NAME\}/', '', $ele_value[2]);
					$ele_value[2] = preg_replace('/\{name\}/', '', $ele_value[2]);
					$ele_value[2] = preg_replace('/\{UNAME\}/', '', $ele_value[2]);
					$ele_value[2] = preg_replace('/\{uname\}/', '', $ele_value[2]);
					$ele_value[2] = preg_replace('/\{EMAIL\}/', '', $ele_value[2]);
					$ele_value[2] = preg_replace('/\{email\}/', '', $ele_value[2]);
					$ele_value[2] = preg_replace('/\{MAIL\}/', '', $ele_value[2]);
					$ele_value[2] = preg_replace('/\{mail\}/', '', $ele_value[2]);
					$ele_value[2] = preg_replace('/\{DATE\}/', '', $ele_value[2]);
				}elseif( !$admin ){
					$ele_value[2] = preg_replace('/\{NAME\}/', $xoopsUser->getVar('name', 'e'), $ele_value[2]); // modified to call real name 9/16/04 by jwe
					$ele_value[2] = preg_replace('/\{name\}/', $xoopsUser->getVar('name', 'e'), $ele_value[2]); // modified to call real name 9/16/04 by jwe
					$ele_value[2] = preg_replace('/\{UNAME\}/', $xoopsUser->getVar('uname', 'e'), $ele_value[2]);
					$ele_value[2] = preg_replace('/\{uname\}/', $xoopsUser->getVar('uname', 'e'), $ele_value[2]);
					$ele_value[2] = preg_replace('/\{MAIL\}/', $xoopsUser->getVar('email', 'e'), $ele_value[2]);
					$ele_value[2] = preg_replace('/\{mail\}/', $xoopsUser->getVar('email', 'e'), $ele_value[2]);
					$ele_value[2] = preg_replace('/\{EMAIL\}/', $xoopsUser->getVar('email', 'e'), $ele_value[2]);
					$ele_value[2] = preg_replace('/\{email\}/', $xoopsUser->getVar('email', 'e'), $ele_value[2]);
					$ele_value[2] = preg_replace('/\{DATE\}/', date("d-m-Y"), $ele_value[2]);

				}

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
        $ele_value[0] = $myts->displayTarea($ele_value[0]);

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
global $groupuser;
global $module_id;
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
while ($rowlsq = $xoopsDB->fetchRow($reslsq)) // loop through all the itemids (permitted groups) found and save them in an array...
{
	$pgroups[] = $rowlsq[0];
}
// Note: if no groups were found, then pguidq will be empty and so all entries will be shown, no restrictions
array_unique($pgroups); // remove duplicate groups from the list
//print_r ($pgroups); // debug code
//print "<br>"; // debug code
$start = 1;
foreach($pgroups as $agrp2) // setup a query based on all these groups
{
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

// query below modified to include pguidq which will limit the returned values to just the ones that are allowed for this user's groups to see -- jwe 8/29/04
					$linkedvaluesq = "SELECT ele_value, ele_id FROM " . $xoopsDB->prefix("form_form") . " WHERE id_form=$boxproperties[0] AND ele_caption=\"$boxproperties[1]\" $pguidq GROUP BY ele_value ORDER BY ele_value";
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
					$options[$opt_count] = $myts->stripSlashesGPC($i['key']);
					if( $i['value'] > 0 ){
						$selected[] = $opt_count;
					}
				$opt_count++;
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
				switch($xoopsModuleConfig['delimeter']){
					case 'br':
						$form_ele = new XoopsFormElementTray($ele_caption, '<br />');
						while( $o = each($options) ){
							$t =& new XoopsFormCheckBox(
								'',
								$form_ele_id.'[]',
								$selected
							);
							$t->addOption($o['key'], $o['value']);
							$form_ele->addElement($t);
						}
					break;
					default:
						$form_ele = new XoopsFormCheckBox(
							$ele_caption,
							$form_ele_id,
							$selected
						);
						$form_ele->addOptionArray($options);
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
				switch($xoopsModuleConfig['delimeter']){
					case 'br':
						$form_ele = new XoopsFormElementTray($ele_caption, '<br />');
						while( $o = each($options) ){
							$t =& new XoopsFormRadio(
								'',
								$form_ele_id,
								$selected
							);
							$t->addOption($o['key'], $o['value']);
							$form_ele->addElement($t);
						}
					break;
					default:
						$form_ele = new XoopsFormRadio(
							$ele_caption,
							$form_ele_id,
							$selected
						);
						$form_ele->addOptionArray($options);
					break;
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
		return $form_ele;
	}

}
?>