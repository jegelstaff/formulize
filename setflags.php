<?

###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2004 Freeform Solutions                  ##
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
##  Author of this file: Freeform Solutions 					     ##
##  Project: Formulize                                                       ##
###############################################################################
// FIND OUT IF THE CURRENT USER IS A MODULE ADMIN (AND USE THIS LATER ON TO SET CERTAIN THINGS, OVERRIDE OTHERS)

$start=1;
$gpermmodq = "(";
foreach($groupuser as $agroup) // loop through all the groups the user is a member of to make a query that we can use to check if they've got module admin perms
{
	if($start)
	{
		$gpermmodq .= "gperm_groupid=$agroup";
		$start=0;
	}
	else
	{
		$gpermmodq .= " OR gperm_groupid=$agroup";
	}
}
$gpermmodq .= ")";

$modadminq = "SELECT * FROM " . $xoopsDB->prefix("group_permission") . " WHERE $gpermmodq AND gperm_itemid=$module_id AND gperm_modid=1 AND gperm_name=\"module_admin\"";
$resmodadminq = mysql_query($modadminq);
$ismoduleadmin = mysql_num_rows($resmodadminq); // if no rows, ie: no permission, isadmin will be 0 so will evaluate to false.

//print "ismoduleadmin: $ismoduleadmin*";

// SET THE ISADMIN VARIABLE BY LOOKING FOR ADMIN PERM ON THE FORM (NOT MODULE)
	array($groupidadmin);
      $adminq = $xoopsDB->query("SELECT gperm_groupid FROM ".$xoopsDB->prefix("group_permission")." WHERE gperm_itemid= ".$id_form." AND gperm_modid=".$module_id . " AND gperm_name=\"admin\"");
	if ( $adminq ) {
  		while ( $row = mysql_fetch_row ( $adminq ) ) {
  			$groupidadmin[] = $row[0];
  	  	}
	}
//check that the user is in a group with admin perm
	$isadmin = 0;
	foreach ($groupidadmin as $gr){
           	if ( in_array ($gr, $groupuser)) {
			$isadmin = 1;
			break;
		}
	}


// CHECK FOR FLAGS...

// GET TEH GROUPSCOPE SETTING
$hasgroupscope = "SELECT groupscope FROM " . $xoopsDB->prefix("form_id") . " WHERE id_form=$id_form";
$reshasgroupscope = mysql_query($hasgroupscope);
$rowreshasgroupscope = mysql_fetch_row($reshasgroupscope);
$hasgroupscope = $rowreshasgroupscope[0];

//print "*$hasgroupscope*";

// SET THE NOTIFICATION USER LIST! -- based on the groupscope and the admin rights
// For groupscope forms, get list of users in the same groups as the current user, and limit notifications to that list of users
// For non-groupscope forms, ie: you only see your own entries by default, get list of form admins, and limit notifications to them
	$NotUs[] = $uid; // put the current user in the notification list, just to make sure it's not blank (current user is omitted from notifications by default)
	foreach($groupid as $canviewgroup) // groupid is the array of groups that have view perm
	{
		// check to see if this group is one the user is a member of (in which case we broadcast to the members of this group)
		if(in_array($canviewgroup, $groupuser))
		{
			$selNotUs = "SELECT uid FROM " . $xoopsDB->prefix("groups_users_link") . " WHERE groupid = $canviewgroup";
			$resNotUs = $xoopsDB->query($selNotUs);
			while($rowNotUs = $xoopsDB->fetchRow($resNotUs))
			{
				if($hasgroupscope)
				{
					$NotUs[] = $rowNotUs[0];
				}
				else // for non-groupscope forms, only notify users who are in groups that can admin the form (because non-admins won't be able to see the entries)
				{
					$selIsInAdminG = "SELECT groupid FROM " . $xoopsDB->prefix("groups_users_link") . " WHERE uid = $rowNotUs[0]";
					$resIsInAdminG = $xoopsDB->query($selIsInAdminG);
					while($rowIsInAdminG = $xoopsDB->fetchRow($resIsInAdminG))
					{
						if(in_array($rowIsInAdminG[0], $groupidadmin))
						{
							$NotUs[] = $rowNotUs[0];
							break;
						}  					
					}
				}
			}
		}
	}
	array_unique($NotUs);


// CHECK TO SEE IF A DELETE was requested and if so then perform the delete

if(isset($_POST['delid']))
{
	
	$delsql="DELETE FROM " .$xoopsDB->prefix("form_form") . " WHERE id_req = " . $_POST['delid'];
	$result = $xoopsDB->query($delsql);

	// notification added 10/10/04 by jwe
	$notification_handler =& xoops_gethandler('notification');
	array($extra_tags);
	$extra_tags['ENTRYUSERNAME'] = $realnamejwe;
	$extra_tags['FORMNAME'] = $title;
	$notification_handler->triggerEvent ("form", $id_form, "delete_entry", $extra_tags, $NotUs);

}


$issingle = "SELECT singleentry FROM " . $xoopsDB->prefix("form_id") . " WHERE id_form=$id_form";
$resissingle = mysql_query($issingle);

$rowissingle = mysql_fetch_row($resissingle);
$issingle = $rowissingle[0];

//print $issingle;



// get flags that control editing entries
$selectjwe = $_GET['select'];

$viewentry = $_POST['viewentry']; // only happens if we're accepting a submission
$editingent = $_POST['editingent']; // grab the value of editingent, which is set to on if we are on the add page thanks to clicking a view entries link

if(!$viewentry) 
{
	$viewentry = $_GET['viewentry'];
	if($viewentry) {$editingent = 1;} // set editing ent if we are viewing an entry due to a view this entry link.  editingent will be passed back to the script when the Submit button is clicked and then it will be set above.
}

// print "Viewentry: $viewentry<br>";
// get the uid that belongs to the entry
$getveuid = "SELECT uid FROM " . $xoopsDB->prefix("form_form") . " WHERE id_req=$viewentry GROUP BY id_req";
$resgetveuid = $xoopsDB->query($getveuid);
$rowgetveuid = $xoopsDB->fetchRow($resgetveuid);
$veuid = $rowgetveuid[0]; // the uid that belongs to the entry
//print "VEUID is set: $veuid<br>";


if($viewentry AND !$isadmin AND $veuid != $uid) // allow an entry's creator, and admins, to view entries, but check for perms for co-membership in a group for other users
{
$vevalid=0;
if($hasgroupscope) // do the security check, since maybe they are allowed to view this entry
{
//verify that viewentry is allowed -- required security check...
//enuid is the user who made the entry that is being requested
$vevq = "SELECT uid FROM " . $xoopsDB->prefix("form_form") . " WHERE id_req = $viewentry GROUP BY id_req";
$resvevq = mysql_query($vevq);
$rowvevq = mysql_fetch_row($resvevq);
$enuid = $rowvevq[0];

// vegroups is the groups the entry owner is a member of
array($vegroups);
$vegq = "SELECT groupid FROM " . $xoopsDB->prefix("groups_users_link") . " WHERE uid=$enuid";
$resvegq = mysql_query($vegq);
$vegindexer = 0;
while ($rowvegq = mysql_fetch_row($resvegq))
{
	$vegroups[$vegindexer] = $rowvegq[0];
	$vegindexer++;
}

// check that the current user and the owner of the entry overlap in at least one group (ie: they would share a groupscope somewhere.  Note that if a user makes an entry and then leaves the only group where they overlap with the current user, then this check will fail, even though the entry was made at a time when the two users did overlap. 
foreach($vegroups as $avgroup)
{
	if(in_array($avgroup, $groupuser))
	{
		// so there is an overlap in membership, so check to see if it occurs in a group which can view this form...and if so, then set the valid flag
		if(in_array($avgroup, $groupid))
		{
			$vevalid = 1;
			break;
		}
	}
}
} // end of if hasgroupscope
$vereportcheck = 0;
if(!$vevalid)
{
	// one last chance:  set a flag which will trigger a check below, to see if the entry belongs to a group about which the current user has received a report (the flag carries the view entry setting which we might want to restore).
	$vereportcheck = $viewentry;
	$viewentry = "";
	$selectjwe = 1;
}

/*print "vevalid (1 is yes, 0 is no): ";
print $vevalid;
print "<br>vereportcheck: ";
print $vereportcheck;
print "<br>viewentry: ";
print $viewentry;
print "<br>owner of entry: ";
print $enuid;
print "<br>owner's groups: ";
print_r($vegroups);
print "<br>user's groups: ";
print_r($groupuser);*/ // debug code block

} // end of viewentry security

if(!$viewentry AND !$theycanadd) // if they're not viewing an entry and they can't add new ones, then shunt them to the select page
{
	$selectjwe = 1;
}

$reportingyn = $_GET['reporting'];
$report = $_POST['reportselector'];
if(!$report)
{
	$report = $_GET['reportname'];
}
$showscope = 1; // controls display of the scope selection box.
//if there is no groupscope, then do not showscope on reporting pages (unless the user is an admin)
if(!$hasgroupscope AND !$isadmin)
{
	$showscope=0;
}

//set $sent to 0 since it's important to reference later on and must be empty until the redirect message is put into it.
$sent = 0;



// check to see if the form is supposed to show previous entries...
$showviewentries = "SELECT showviewentries FROM " . $xoopsDB->prefix("form_id") . " WHERE id_form=$id_form";
$resshowviewentries = mysql_query($showviewentries);
$rowresshowviewentries = mysql_fetch_row($resshowviewentries);
$showviewentries = $rowresshowviewentries[0];

//if a form is a single entry, then override the showviewentries setting to off (which is overridden again below if a report is available or the user is an admin)
if($issingle)
{
	$showviewentries = 0;
}

// delete a report if that is what was requested by user
if(isset($_POST['reportdelete']))
{
	if($_POST['deleteconfirm'] == 1)
	{
		$reportdeleteq = "DELETE FROM " .$xoopsDB->prefix("form_reports") . " WHERE report_id = $report";	
		$resreportdeleteq = mysql_query($reportdeleteq);
		$report = 0;
	}
}

?>