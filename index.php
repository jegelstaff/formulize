<?php
include 'header.php';
include_once XOOPS_ROOT_PATH.'/class/mail/phpmailer/class.phpmailer.php';

global $xoopsDB, $myts, $xoopsUser, $xoopsModule, $xoopsTpl, $xoopsConfig;
$block = array();
$groupuser = array();

//userobject variable gathering moved up here by jwe 7/23/04

if( is_object($xoopsUser) )
{
	$uid = $xoopsUser->getVar("uid");
	$realuid = $uid; // used in the case of proxy submissions
	$usernamejwe = $xoopsUser->getVar("uname");
	$realnamejwe = $xoopsUser->getVar("name");
}
else {
	$uid =0;
}

// print "*$realnamejwe*"; //JWE DEBUG CODE


if(!isset($HTTP_POST_VARS['title'])){
	$title = isset ($HTTP_GET_VARS['title']) ? $HTTP_GET_VARS['title'] : '';
}else {
	$title = $HTTP_POST_VARS['title'];
}
/*
if ($title=="") {
}
		
*/

$sql=sprintf("SELECT id_form,admin,groupe,email,expe FROM ".$xoopsDB->prefix("form_id")." WHERE desc_form='%s'",$title);
$res = mysql_query ( $sql ) or die('Erreur SQL !<br>'.$sql.'<br>'.mysql_error());
//global $nb_fichier;
 
      	$myts =& MyTextSanitizer::getInstance();
        $title = $myts->displayTarea($title);

if ( $res ) {
  while ( $row = mysql_fetch_array ( $res ) ) {
    $id_form = $row['id_form'];
    $admin = $row['admin'];
    $groupe = $row['groupe'];
    $email = $row['email'];
    $expe = $row['expe'];
  }
}

// -------------------------------------- JWE MODS BELOW... end of july, early august 2004

// SECURITY!

// 1. find the user id (done above $uid)
// 2. find the form id (done above $id_form)
// 3. find the group membership of the user
// 4. find the groups that have access to the form
// 5. find out if there's overlap
// 6. halt execution with error message if there's no overlap

// code below borrowed from mymenu.php

      // SET THE MODULE id 
	$res4 = $xoopsDB->query("SELECT mid FROM ".$xoopsDB->prefix("modules")." WHERE dirname='formulize'");
	if ($res4) {
		while ($row = mysql_fetch_row($res4)) {
			$module_id = $row[0];
		}
	}
	
	// SET THE GROUPUSER ARRAY -- user is a member of these groups
	if (!$uid) {
		$groupuser[0] = XOOPS_GROUP_ANONYMOUS; 
		// print "ANON USER!!!"; // debug code
	}
	$res = $xoopsDB->query("SELECT groupid FROM ".$xoopsDB->prefix("groups_users_link")." WHERE uid= ".$uid);
	if ( $res ) {
  		while ( $row = mysql_fetch_row ( $res ) ) {
	  		$groupuser[] = $row[0];
			//print "GROUPS: $row[0]"; // DEBUG CODE
  		}
	}

	// SET THE GROUPIDADD ARRAY, AND THEN DO THE CHECK FOR **ADD** PERMISSION - groups that can add to this form
     	$groupidadd = array();
      $res3 = $xoopsDB->query("SELECT gperm_groupid FROM ".$xoopsDB->prefix("group_permission")." WHERE gperm_itemid= ".$id_form." AND gperm_modid=".$module_id . " AND gperm_name=\"add\"");
	if ( $res3 ) {
  		while ( $row = mysql_fetch_row ( $res3 ) ) {
  			$groupidadd[] = $row[0];
  	  	}
	}
/*	print "groupidadd: ";
	print_r($groupidadd);
	print "<br>";*/

	// SET THE GROUPID ARRAY, AND THEN DO THE CHECK FOR **VIEW** PERMISSION - groups this form is permitted for
     	$groupid = array();
      $res2 = $xoopsDB->query("SELECT gperm_groupid FROM ".$xoopsDB->prefix("group_permission")." WHERE gperm_itemid= ".$id_form." AND gperm_modid=".$module_id . " AND gperm_name=\"view\"");
	if ( $res2 ) {
  		while ( $row = mysql_fetch_row ( $res2 ) ) {
  			$groupid[] = $row[0];
  	  	}
	}
	
	// this code checks to see if the user is a member of a group that has been given permission to access the form
	$lettheminjwe = 0;
	foreach ($groupid as $gr){
           	if ( in_array ($gr, $groupuser)) {
			$lettheminjwe = 1;
			break;
		}
	}

	// this code checks to see if the user is a member of a group that has been given permission to ADD TO the form
	$theycanadd = 0;
	foreach ($groupidadd as $gr){
           	if ( in_array ($gr, $groupuser)) {
			$theycanadd = 1;
			break;
		}
	}

	// this code kicks the user out...
	if (!$lettheminjwe)
	{
		//	print "$gr: user is NOT allowed!!!"; // DEBUG CODE
		//redirect to main page if they don't have permission
		$kickmsgjwe = _formulize_NO_PERMISSION;			
		redirect_header(XOOPS_URL."/index.php", 3, $kickmsgjwe);
	}

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

// GET A LIST OF AVAILABLE REPORTS
//$userreportq = "SELECT report_id, report_name, report_groupids FROM " . $xoopsDB->prefix("form_reports") . " WHERE report_id_form=$id_form AND (report_uid=$uid OR report_ispublished=1) GROUP BY report_id ORDER BY report_id";
$userreportq = "SELECT report_id, report_name FROM " . $xoopsDB->prefix("form_reports") . " WHERE report_id_form=$id_form AND (report_uid=$uid OR report_ispublished>0) GROUP BY report_id ORDER BY report_id";
$resuserreportq = mysql_query($userreportq);
$userreportsindexer = 0;
array($userreportslist);
array($userreportsnames);
array($userreportsgroupids);
while ($rowuserreportq = mysql_fetch_row($resuserreportq))
{
	$userreportslist[$userreportsindexer] = $rowuserreportq[0];
	$userreportsnames[$userreportsindexer] = $rowuserreportq[1];
//	$userreportsgroupids[$userreportsindexer] = $rowuserreportq[2];
	$userreportsindexer++;
}

if($userreportsindexer) // if at least one report was found...
{
	// cull reports based on groupid matches with user id and membership
	// make the current report selected.
	$frlindexer = 0;
	$urpnameindexer = 0;
	foreach($userreportslist as $arep)
	{

		$isallowedforuser = 0; //assume no reports are allowed...
		$isallowedforuserq = "SELECT report_uid, report_groupids, report_scope, report_ispublished FROM " . $xoopsDB->prefix("form_reports") . " WHERE report_id=$arep";
		$resisallowedforuserq = mysql_query($isallowedforuserq);
		$rowisallowedforuserq = mysql_fetch_row($resisallowedforuserq);

		if($rowisallowedforuserq[0] == $uid OR $ismoduleadmin) // module admins see all reports for all forms
		{
			$isallowedforuser = 1;
		} 

		$rgids = explode("&*=%4#", $rowisallowedforuserq[1]);
		foreach($rgids as $argid)
		{
			//print "allowed group: $argid<br>";
			if(in_array($argid, $groupuser))
			{
				$isallowedforuser = 1;
				break;
			}
		}
 
		
		if($isallowedforuser)
		{

		// check to see if this report (allowed for this user) permits the viewing of an entry that we need to check

		if($vereportcheck)
		{

		// first check to see that this report allows entry viewing...
		if(strstr($rowisallowedforuserq[3], "2") OR strstr($rowisallowedforuserq[3], "3"))
		{
		}
		else
		{

		//print "in report check";
		// check to see if the groups or users in this report's scope match the match user or groups of the viewentry requested
		// 1. check to see if uid=$enuid is in the scope string
		$needlestr = "uid=" . $enuid;
		$vesaver = strstr($rowisallowedforuserq[2], $needlestr);
		if($vesaver)
		{
			// if there is a match, then restore the view entry setting and the selectjwe setting (to zero)
			$viewentry = $vereportcheck;
			$selectjwe = 0;
		}// end of if the vesaver found something


		}// end of if report allows viewing of entries

		}// end of if vereportcheck

		$finalreportlist[$frlindexer] = $arep;
//		print "currently allowing report number: $arep<br>currently selected report: $report<br>";
		if($arep == $report)
		{
			$finalreportlist[$frlindexer] .= " selected";
		}
		$finalreportlist[$frlindexer] .= ">" . $userreportsnames[$urpnameindexer];
		$frlindexer++;
		}
		$urpnameindexer++;
	}

} 

// override showviewentries iff the user can view reports for this form (they would have to have been published) or they are an admin

if($finalreportlist[0] OR $isadmin)
{
	$showviewentries = 1;
}


// this is the key switch between the pages right here...
//print "**select status check: $selectjwe (viewentry: $viewentry)";
if($selectjwe) // if we're selecting entries...check to see that this form really shows entries...
{
	if(!$showviewentries)
	{
		$selectjwe = 0;
	}
}
// condition below commented because the no-add-button condition lower down will prevent them from adding entries, and users should be able to view entries in the form
/*else // if we're not selecting entries, but there's no add permission, then switch to selecting entries...
{
	if(!$theycanadd)
	{
		$selectjwe = 1;
	}
}*/
if($selectjwe) // if we're really selecting entries (cause we checked that they didn't just hack the URL)
{

	//turn on hasgroupscope for admins viewing entries in issingle forms
	if($issingle AND $isadmin)
	{
		$hasgroupscope = 1;
	}

	// set the template to the select template or the export template
	
	if(isset($_POST['export'])) 
	{
		$xoopsOption['template_main'] = 'formulize_export.html';
	}
	else
	{
		$xoopsOption['template_main'] = 'formulize_select.html';
	}
	
	require(XOOPS_ROOT_PATH."/header.php");

// BIG IF BELOW... CONTROLS READING OF REPORT INFORMATION, overrides gathering of other variables
if($report) // if a report was specified...
{
	//	$xoopsTpl->assign('reportname', $report); // I think this is done below...
	// read all the query/calc/sort arrays out of the database...


	$getreportq = "SELECT report_name, report_id_form, report_uid, report_ispublished, report_scope, report_groupids, report_fields, report_search_typeArray, report_search_textArray, report_andorArray, report_calc_typeArray, report_sort_orderArray, report_ascdscArray, report_globalandor FROM " . $xoopsDB->prefix("form_reports") . " WHERE report_id = $report";

	$resgetreportq = mysql_query($getreportq);
	$rowgetreportq = mysql_fetch_row($resgetreportq);
//	print_r($rowgetreportq);

	$report_name = $rowgetreportq[0];
	$id_form = $rowgetreportq[1];
	$report_uid = $rowgetreportq[2];
	$report_ispublished = $rowgetreportq[3];

	// check for the other publishing options, nove and calconly
	$report_nove = strstr($report_ispublished, "2");
	$report_calconly = strstr($report_ispublished, "3");

	if($rowgetreportq[4])
	{
		// need to set sentscope here and then process sentscope below to get an UP TO DATE LIST OF USERS for the scope param
		$sentscope = explode("&*=%4#", $rowgetreportq[4]);
		$showscope = 0;
	}
	else
	{
		array($sentscope);
		// need to grab the scope setting if the user specified one
		$sentscope = $_POST['scopeselector'];
	}
	$report_groupids = explode("&*=%4#", $rowgetreportq[5]);

	$apCorrectField = eregi_replace ("`", "'", $rowgetreportq[6]);

	$reqFieldsJwe = explode("&*=%4#", $apCorrectField);
//	print "reqFields: ";
//	print_r($reqFieldsJwe);
	$search_typeArray = explode("&*=%4#", $rowgetreportq[7]);
	$search_textArray = explode("&*=%4#", $rowgetreportq[8]);
	$andorArray = explode("&*=%4#", $rowgetreportq[9]);

	$tempcalc_typeArray = explode("!@+*+6-", $rowgetreportq[10]);
	$tcindexer=0;
	foreach($tempcalc_typeArray as $tc1)
	{
		$tcarray = explode("&*=%4#", $tc1);
		if ($tcarray[0] != "")
		{
			$calc_typeArray[$tcindexer] = $tcarray;
		}
		else
		{
			$calc_typeArray[$tcindexer] = "";
		}
		$tcindexer++;
	}
//	print "calc_typeArray: ";
//	print_r($calc_typeArray);
	$sort_orderArray = explode("&*=%4#", $rowgetreportq[11]);
	$ascdscArray = explode("&*=%4#", $rowgetreportq[12]);
	$globalandor = $rowgetreportq[13];


// verify that the user is a member of groups that can see the report, and if not, then report=0 -- also check to see if it is their own report and if so then candelete is set to on.

	$reportallowed = 0;
	$candeletereport = 0;
	if($report_uid == $uid) // if it's their own report...
	{
		$reportallowed = 1;
		$candeletereport = 1;
	}
	else
	{
		foreach($groupuser as $anothergid)
		{
			if(in_array($anothergid, $report_groupids))
			{
				$reportallowed = 1;
				break;
			}
		}
	}

if($reportallowed)
{
		$xoopsTpl->assign('ispublished', $report_ispublished);
		$xoopsTpl->assign('captions_sort_dir', $ascdscArray); // asc desc sort settings
		$xoopsTpl->assign('captions_search_type', $search_typeArray);
		$xoopsTpl->assign('captions_search_text', $search_textArray);
		$xoopsTpl->assign('captions_andor', $andorArray);
		$xoopsTpl->assign('captions_calc_type', $calc_typeArray);
		$xoopsTpl->assign('captions_sort_order', $sort_orderArray);
		$xoopsTpl->assign('globalandor', $globalandor);

		if($ismoduleadmin)
		{
			$candeletereport = 1;
		}

		$xoopsTpl->assign('candeletereport', $candeletereport);
		
		if($candeletereport)
		{
		$xoopsTpl->assign('reportdelete', _formulize_REPORTDELETE);
		$xoopsTpl->assign('delete', _formulize_DELETE);
		$xoopsTpl->assign('deleteconfirm', _formulize_DELETECONFIRM . "&nbsp;<b>" . $report_name . "</b>.");
		}
}
else
{
	$report=0;
}

} // END OF if-A-REPORT HAS BEEN REQUESTED...

if(!$report) // handled as a separate condition, not an else, since we can set the report to 0 after it is read if the user doesn't have perms on the report
{

// GET THE HEADERS (AND THEN USE THEM TO GET INFO PASSED BACK FROM FORM
$reqFieldsJwe = array();
// check to see if the user specified different fields
if($_POST['colchange'] == _formulize_CHANGE)
{
	$fieldsjweindexer=0;
	foreach($_POST['allformcaplist'] as $userSelectedHeader)
	{
		$reqFieldsJwe[$fieldsjweindexer] = stripslashes($userSelectedHeader);
		$fieldsjweindexer++;
	}
}
elseif(isset($_POST['hiddencap1'])) // check to see if current headers were passed back from the form
{
	$hiddencapmarker = 1;
	$hiddencheckstring = "hiddencap" . $hiddencapmarker;
	$fieldsjweindexer=0;
	while($_POST[$hiddencheckstring])
	{
		$reqFieldsJwe[$fieldsjweindexer] = stripslashes($_POST[$hiddencheckstring]);
		$fieldsjweindexer++;
		$hiddencapmarker++;
		$hiddencheckstring = "hiddencap" . $hiddencapmarker;
	}
}
else // otherwise rely on the headerlist...
{

$headerlistQueryJwe = "SELECT headerlist FROM " . $xoopsDB->prefix("form_id") . " WHERE id_form=$id_form";
$resultheaderlistFieldQueryJwe = mysql_query($headerlistQueryJwe);
// if there are rows in the result...ie: there are Header fields
if(mysql_num_rows($resultheaderlistFieldQueryJwe)>0)
{
	while ($rowjwe = mysql_fetch_row($resultheaderlistFieldQueryJwe))
	{
		$reqFieldsJwe = explode("*=+*:", $rowjwe[0]); 
		array_shift($reqFieldsJwe);
		/*print "req fields!:<br>";
		print_r($reqFieldsJwe); // DEBUG CODE
		print "<br><br>";*/
	}
}
else // if no header fields specified, then....


{
// GATHER REQUIRED FIELDS FOR THIS FORM...
$reqFieldQueryJwe = "SELECT ele_caption FROM " . $xoopsDB->prefix("form") . " WHERE ele_req=1 AND id_form=$id_form";
$resultReqFieldQueryJwe = mysql_query($reqFieldQueryJwe);
// if there are rows in the result...ie: there are required fields
if(mysql_num_rows($resultReqFieldQueryJwe)>0)
{
	while ($rowjwe = mysql_fetch_assoc($resultReqFieldQueryJwe))
	{
		$reqFieldsJwe[] = $rowjwe["ele_caption"];
		// print "hello"; // DEBUG CODE
	}
}
else
{
// IF there are no required fields THEN ... go with first field
// print "no required fields found"; // DEBUG CODE 	
$firstFieldQueryJwe = "SELECT ele_caption FROM " . $xoopsDB->prefix("form") . " WHERE id_form=$id_form GROUP BY id_form";
$resultFirstFieldQueryJwe = mysql_query($firstFieldQueryJwe);

while ($rowjwe = mysql_fetch_assoc($resultFirstFieldQueryJwe))
{
	$reqFieldsJwe[] = $rowjwe["ele_caption"];
//	print "*" . $rowjwe["ele_caption"] . "*"; // DEBUG CODE
}

} // end else covering case with no required fields
} // end else covering no headerlist fields.

} // end else that gets us to look at the headerlist (instead of use specified fields)

//************ STEPS BELOW NO LONGER APPLY EXACTLY, BUT ARE GENERALLY TRUE
// gather a list of record ids for entries the user has made in the form... jwe 7/23/04
// steps:
// 1. find form id (done above $id_form)
// 2. find user id (done above $uid)
// 3. find the required fields in the form -- or use the user-specified fields 
// 4. select entries from form_form that match on form id and user id and are required fields
// 5. save selected fields from each entry
// 6. draw summary table from which people can select their entry.

// INSERTION OF CODE TO HANDLE READING DATA SENT FROM THE VIEW-ENTRIES REPORTING SECTION

	//Grab values from the _POST array that have been sent from the reporting section
	if(isset($_POST['go']) OR isset($_POST['save']) OR isset($_POST['export']) OR isset($_POST['selectscope'])) // if reporting options were sent... 
	{
		// now go through the caption list to grab all the values sent back
		// then assign those values to be returned to the view entries page
		// and change the data sent to the view entries page to reflect the values sent back by the user

			// the global and or setting
			$globalandor = $_POST['globalandor'];

			array($sentscope);
			// need to grab the scope setting....
			$sentscope = $_POST['scopeselector'];
						
		$allcapindexer = 0;
		foreach($reqFieldsJwe as $thisformcap) // loop through all the captions
		{
			$pc = str_replace(" ", "_", $thisformcap); // replace the spaces
			$pc = str_replace(".", "_", $pc); // replace the periods


			// the search operator settings
			$search_type_check = $pc . "_search_type";
			if(isset($_POST[$search_type_check]))
			{
				$search_typeArray[$allcapindexer] = $_POST[$search_type_check];
			}
			else
			{
				$search_typeArray[$allcapindexer] = "";
			}

			// the search text settings
			$search_text_check = $pc . "_search_text";
			if(isset($_POST[$search_text_check]))
			{
				$search_textArray[$allcapindexer] = $_POST[$search_text_check];
				if($search_textArray[$allcapindexer] == 0);
				{
					$search_textZeroSave[$allcapindexer] = "&*#+zero";
				}
			}
			else
			{
				$search_textArray[$allcapindexer] = "";
			}

			// the local andor settings
			$andor_check = $pc . "_andor";
			$andorArray[$allcapindexer] = $_POST[$andor_check];
			
			// the calculation settings -- IS AN ARRAY need to handle it differently
			$calc_type_check = $pc . "_calc_type";
			if(isset($_POST[$calc_type_check]))
			{
				array($intermediate_calc_typeArray);
				array($secondintermediate);
				$intermediate_calc_typeArray = $_POST[$calc_type_check];

				/*print_r($intermediate_calc_typeArray);
				print "<br><br>";*/
				if(in_array("sum", $intermediate_calc_typeArray))
				{
					$secondintermediate[0] = "\"sum\" selected>" . _formulize_SUM;
				}
				else
				{
					$secondintermediate[0] = "\"sum\">" . _formulize_SUM;
				}
				
									
				if(in_array("average", $intermediate_calc_typeArray))
				{
					$secondintermediate[1] = "\"average\" selected>" . _formulize_AVERAGE;
				}
				else
				{
					$secondintermediate[1] = "\"average\">" . _formulize_AVERAGE;
				}
	
				if(in_array("min", $intermediate_calc_typeArray))
				{
					$secondintermediate[2] = "\"min\" selected>" . _formulize_MINIMUM;

				}
				else
				{
					$secondintermediate[2] = "\"min\">" . _formulize_MINIMUM;
				}
			
				if(in_array("max", $intermediate_calc_typeArray))
				{
					$secondintermediate[3] = "\"max\" selected>" . _formulize_MAXIMUM;
				}
				else
				{
					$secondintermediate[3] = "\"max\">" . _formulize_MAXIMUM;
				}
				
				if(in_array("count", $intermediate_calc_typeArray))
				{
					$secondintermediate[4] = "\"count\" selected>" . _formulize_COUNT;
				}
				else
				{
					$secondintermediate[4] = "\"count\">" . _formulize_COUNT;
				}
			
				if(in_array("percent", $intermediate_calc_typeArray))
				{
					$secondintermediate[5] = "\"percent\" selected>" . _formulize_PERCENTAGES;
				}
				else
				{
					$secondintermediate[5] = "\"percent\">" . _formulize_PERCENTAGES;
				}
				$calc_typeArray[$allcapindexer] = $secondintermediate;			
			}
			else
			{
				$calc_typeArray[$allcapindexer] = "";
			}
			/*print "$thisformcap:<br>";
			print_r($calc_typeArray[$allcapindexer]);
			print "<br><br>";*/
			
			// the sort priority settings -- make all entries equal something!
			$sort_order_check = $pc . "_sort_order";
			if($_POST[$sort_order_check] != "none")
			{
				$sort_orderArray[$allcapindexer] = $_POST[$sort_order_check];
			}
			else
			{
				$sort_orderArray[$allcapindexer] = "";
			}


			// asc desc setting for sort options
			$sort_dir_check = $pc . "_sort_dir"; // setup the proper suffix 
			if(isset($_POST[$sort_dir_check])) 
			{
				$ascdscArray[$allcapindexer] = $_POST[$sort_dir_check];
			}
			else
			{
				$ascdscArray[$allcapindexer] = "";
			}		

		
			// increment counter for next caption
			$allcapindexer++;
		}
		// write all detected settings back to the template

/*		print "passback array checks:<br>";
		print_r($search_typeArray);
		print "<br>";
		print_r($search_textArray);
		print "<br>";
		print_r($andorArray);
		print "<br>";
		print_r($calc_typeArray);
		print "<br>";
		print_r($sort_orderArray);
		print "<br>";
		print_r($ascdscArray);
		print "<br>$globalandor<br><br>";*/
		
		$xoopsTpl->assign('captions_sort_dir', $ascdscArray); // asc desc sort settings
		$xoopsTpl->assign('captions_search_type', $search_typeArray);
		$xoopsTpl->assign('captions_search_text', $search_textArray);
		$xoopsTpl->assign('captions_andor', $andorArray);
		$xoopsTpl->assign('captions_calc_type', $calc_typeArray);
		$xoopsTpl->assign('captions_sort_order', $sort_orderArray);
		$xoopsTpl->assign('globalandor', $globalandor);
	}

} // END OF BIG IF THAT CONTROLS READING OF REPORT SETTINGS OR READING USER'S OWN QUERY DATA.


	// get full caption list to send to template -- jwe 7/29/04
	// need to know this to send to the change columns box...
	array($allformcaps);
	$getfullcaplist = "SELECT ele_caption FROM ". $xoopsDB->prefix("form") . " WHERE id_form=$id_form ORDER BY ele_order";
	$resgetfullcaplist = mysql_query($getfullcaplist);
	$allformcapsindexer = 0;
	while ($rowgetfullcaplist = mysql_fetch_row($resgetfullcaplist))
	{
		$allformcaps[$allformcapsindexer] = $rowgetfullcaplist[0];
		$allformcapsindexer++;
	}
	$xoopsTpl->assign('allformcaps', $allformcaps); 




// create a masteruserlist for applying a specified report scope.

if($sentscope[0]) // if a scope was sent back from the user or a report then make it and let it override the groupsscope of the form
{

//1. turn groupids into a list of user ids
//2. add userids to the list

$mulindexer = 0;
foreach($sentscope as $ascope)
{
	//print "current scope setting: $ascope<br>";
	$hasgroupscope = "userspecified";
	if(strstr($ascope, "g")) // if the current entry is a group id then traverse the groups_users_link for user ids.
	{

		if($ascope == "g3") // if the selected scope is the anonymous group (group 3)
		{
			$masteruserlist[$mulindexer] = "anon";
			$mulindexer++;
		}
		else
		{
		//print "old gid: $ascope<br>";
		$ascope = substr($ascope, 1);
		//print "new gid: $ascope<br>";
		$getuidsq = "SELECT uid FROM " . $xoopsDB->prefix("groups_users_link") . " WHERE groupid=$ascope";
		$resgetuidsq = mysql_query($getuidsq);
		while($rowgetuidsq = mysql_fetch_row($resgetuidsq))
		{
			$masteruserlist[$mulindexer] = $rowgetuidsq[0];
			$mulindexer++;	
		}
		} // end of it it's an anon group or not.
	}
	else
	{
		$masteruserlist[$mulindexer] = $ascope;
		$mulindexer++;	
	}
} // end of foreach

if(!$masteruserlist[0]) // if the sentscope in fact returns no users... then set it to a null value which will result in no rows being returned
{
	$masteruserlist[0] = "null";
}

//print_r($masteruserlist); // debug code

} // end of it there's a user-sent scope
else // if there was no user-sent scope....check for a group scope on the form 
{

if($hasgroupscope)
{
	$gidindexer = 0;
	array ($groupid2);
	foreach($groupidadd as $gid)
	{
		// if the group is one the user is a member of...
		if( in_array($gid, $groupuser) )
		{
			$groupid2[$gidindexer] = $gid;
			$gidindexer++;
		}
	}
//	print "Add-permitted groups the user is a member of: ";
//	print_r($groupid2)
//	print "<br>";

	array($masteruserlist);
	foreach($groupid2 as $gid)
	{
		$userlistres = $xoopsDB->query("SELECT uid FROM ".$xoopsDB->prefix("groups_users_link")." WHERE groupid=".$gid);
		while ( $ulistrow = mysql_fetch_row ( $userlistres ) ) {
	  		$masteruserlist[] = $ulistrow[0];
  		}
	}

	$masteruserlist = array_unique($masteruserlist);
} // end of if the form hasgroupscope

} // end of if there's a user-sent scope or not

// now process the master user list, created either from the sent scope or based on the groupscope
if($masteruserlist[0])  // if there's an entry in the master userlist, then we need to make  the groupsscope parameter
{
	$beenthroughonce = 0;
	foreach($masteruserlist as $oneusersid)
	{
		if($oneusersid == "anon") {$oneusersid = "0";}
		if(!$beenthroughonce)
		{
			$gscopeparam = "uid=" . $oneusersid;
			$beenthroughonce = 1;
		}
		else
		{
			$gscopeparam = $gscopeparam . " OR uid=" . $oneusersid;
		}
	}

//	print_r($masteruserlist);
//	print "<br>$gscopeparam"; //debug lines
}


// WRITE A REPORT TO THE DB IF THE USER SAVED ONE...
// note that the form configuration means that if the user has typed in the formname box, then if they *do a query with the ENTER key* the form will be saved.
if($_POST['reportnametouse'] AND isset($_POST['save']))
{
//	print "WRITING REPORT<br>";
	$report_name = $_POST['reportnametouse'];
	$report_id_form = $id_form;
	$report_uid = $uid;
	$report_ispublished = $_POST['publishreport'];
	$report_globalandor = $globalandor;

	if($report_ispublished) // if the report is published then append the other options to the is published flag
	{
		$report_nove = $_POST['publishnove'];
		$report_calconly = $_POST['publishcalconly'];
		
		$report_ispublished .= $report_nove . $report_calconly;
	}

	function flatarray ($ain) // used to flatten arrays into a format that can be saved as text fields in the _reports table and then exploded into arrays again upon reading the report
	{
		$startsave = 1;
		foreach($ain as $fieldtosave)
		{
			$fieldtosave = eregi_replace ("&#039;", "`", $fieldtosave);
			$fieldtosave = eregi_replace ("&quot;", "`", $fieldtosave);
			$fieldtosave = eregi_replace ("'", "`", $fieldtosave);
			if($startsave)
			{
	  			$returnstring = $fieldtosave;
				$startsave=0;
			}
			else
			{
				$returnstring .= "&*=%4#" . $fieldtosave;
			}
		}
		return $returnstring;
	}

	if($_POST['lockscope'] == 1) // get the current scope into a form where we can save it.
	{
		if($sentscope)
		{
			$report_scope = flatarray($sentscope);
		}
		else 
		{
			// default scope (not a user selected scope) is in use, so we must turn the group list that the gscopeparam is based on into a sentscope style formatted array and then save it
			if($hasgroupscope)
			{
				// turn groupid2 array into an array of gids with "g" appended in front, so that they will be processed for userids when read back from the report
				foreach($groupid2 as $thisgid)
				{
					$groupid2towrite[] = "g" . $thisgid;
				}
				$report_scope = flatarray($groupid2towrite);
			}
			else
			{
				$report_scope = $uid;
			}
		}
		$showscope = 0;
	}
	
	$report_groupids = flatarray($_POST['reportpubgroups']);
	$report_fields = flatarray($reqFieldsJwe);
	$report_search_typeArray = flatarray($search_typeArray);
	$report_search_textArray = flatarray($search_textArray);
	$report_andorArray = flatarray($andorArray);
	$report_sort_orderArray = flatarray($sort_orderArray);
	$report_ascdscArray = flatarray($ascdscArray);

	//extract all the calc_type arrays and flatten them all and put them together with a unique delimiter
	$startcalcsave = 1;
	foreach($calc_typeArray as $onecalcarray)
	{
		if($startcalcsave)
		{
			$report_calc_typeArray = flatarray($onecalcarray);
			$startcalcsave = 0;
		}
		else
		{
			$report_calc_typeArray .= "!@+*+6-" . flatarray($onecalcarray);
		}
	}

	$reportwriteq = "INSERT INTO " . $xoopsDB->prefix("form_reports") . " (report_name, report_id_form, report_uid, report_ispublished, report_groupids, report_scope, report_fields, report_search_typeArray, report_search_textArray, report_andorArray, report_calc_typeArray, report_sort_orderArray, report_ascdscArray, report_globalandor) VALUES ('$report_name', '$report_id_form', '$report_uid', '$report_ispublished', '$report_groupids', '$report_scope', '$report_fields', '$report_search_typeArray', '$report_search_textArray', '$report_andorArray', '$report_calc_typeArray', '$report_sort_orderArray', '$report_ascdscArray', '$report_globalandor')";

//	print "$reportwriteq<br>";
	$resultReportWriteq = $xoopsDB->query($reportwriteq);
//	print "$resultReportWriteq<br>";
	// have to set view perm for groups that the form has been published to, if those groups don't have view perm right now.
	// 1. find out what groups published to don't have view perm on the form
	// 2. give those groups view perm

	if(isset($_POST['reportpubgroups']) AND isset($_POST['publishreport'])) // if the report was published to a group or more...
	{

	$ghavevpq ="SELECT gperm_groupid FROM " . $xoopsDB->prefix("group_permission") . " WHERE gperm_modid=$module_id AND gperm_itemid=$id_form AND gperm_name=\"view\"";
//	print "<br>Groups that can view query: $ghavevpq<br>";
	$resghavevpq = mysql_query($ghavevpq);
	$grpviewindexer = 0;
	while($rowghavevpq = mysql_fetch_row($resghavevpq))
	{
		$grpview[$grpviewindexer] = $rowghavevpq[0];
		$grpviewindexer++;
	}
//	print "Groups that can view: ";
//	print_r($grpview);
	foreach($_POST['reportpubgroups'] as $pubdgrp)
	{
		if(!in_array($pubdgrp, $grpview))
		{
//			print "<br>not found in groups that can view: $pubdgrp";
			// give this group access to the module
			// give this group access to the form

			// add view perm for this form to that group
		
			// first check to see if they have access and if not, then give it

			$haveaccessq = "SELECT * FROM " . $xoopsDB->prefix("group_permission") . " WHERE gperm_groupid=$pubdgrp AND gperm_itemid=$module_id AND gperm_modid=1 AND gperm_name=\"module_read\""; 
			$reshaveaccessq = mysql_query($haveaccessq);
			$rowhaveaccessq = mysql_fetch_row($reshaveaccessq);
			if(!$rowhaveaccessq[0]) // if that permisison wasn't found, then give it...
			{
				$setaccessq = "INSERT INTO " . $xoopsDB->prefix("group_permission") . " (gperm_groupid, gperm_itemid, gperm_modid, gperm_name) VALUES ($pubdgrp, $module_id, 1, \"module_read\")";
				$resultSetAccessq = $xoopsDB->query($setaccessq);
			}
		
			// find block id
			$findblockq = "SELECT bid FROM " . $xoopsDB->prefix("newblocks") . " WHERE mid=$module_id";
			$resfindblockq = mysql_query($findblockq);
			$rowfindblockq = mysql_fetch_row($resfindblockq);
			$block_id = $rowfindblockq[0];
		
			// check about block access...

			$havebaccessq = "SELECT * FROM " . $xoopsDB->prefix("group_permission") . " WHERE gperm_groupid=$pubdgrp AND gperm_itemid=$block_id AND gperm_modid=1 AND gperm_name=\"block_read\""; 
			$reshavebaccessq = mysql_query($havebaccessq);
			$rowhavebaccessq = mysql_fetch_row($reshavebaccessq);
			if(!$rowhavebaccessq[0]) // if no block access then set it.
			{
				$setbaccessq = "INSERT INTO " . $xoopsDB->prefix("group_permission") . " (gperm_groupid, gperm_itemid, gperm_modid, gperm_name) VALUES ($pubdgrp, $block_id, 1, \"block_read\")";
				$resultSetBAccess = $xoopsDB->query($setbaccessq);
			}
	
			// set their view permission on this form...
			$setviewq = "INSERT INTO " . $xoopsDB->prefix("group_permission") . " (gperm_groupid, gperm_itemid, gperm_modid, gperm_name) VALUES ($pubdgrp, $id_form, $module_id, \"view\")";
			$resultSetViewq = $xoopsDB->query($setviewq);
		}
	}

	} // END IF publish group list is set

	// get the id of the just-written report so we can select it in the dropdown list...
	$jwrq = "SELECT report_id FROM " . $xoopsDB->prefix("form_reports") . " WHERE report_name='$report_name' AND report_id_form='$report_id_form' AND report_uid='$report_uid' AND report_ispublished='$report_ispublished' AND report_groupids='$report_groupids' AND report_scope='$report_scope' AND report_fields='$report_fields' AND report_search_typeArray='$report_search_typeArray' AND report_search_textArray='$report_search_textArray' AND report_andorArray='$report_andorArray' AND report_calc_typeArray='$report_calc_typeArray' AND report_sort_orderArray='$report_sort_orderArray' AND report_ascdscArray='$report_ascdscArray'";
	$resjwrq = mysql_query($jwrq);
	$rowjwrq = mysql_fetch_row($resjwrq);
	$report = $rowjwrq[0];

	//add just written report to the finalreportlist and make it selected...(unselect any others)
	$frpindexer = 0;
	foreach($finalreportlist as $afreport)
	{
//		print "current frep entry: $afreport<br>";
		$finalreportlist[$frpindexer] = str_replace(" selected", "", $afreport);
//		print "mod'd entry: $finalreportlist[$frpindexer]<br>";
		$frpindexer++;
	}
	$finalreportlist[$frpindexer] = $report . " selected>" . stripslashes($report_name);
//	print_r($finalreportlist);
	$candeletereport = 1;  // set this and send it so they can delete the report they just made.
	$xoopsTpl->assign('candeletereport', $candeletereport);
	$xoopsTpl->assign('reportdelete', _formulize_REPORTDELETE);
	$xoopsTpl->assign('delete', _formulize_DELETE);
	$xoopsTpl->assign('deleteconfirm', _formulize_DELETECONFIRM . "&nbsp;<b>" . $report_name . "</b>.");
		



} // end of writing report to DB


// SEND LIST OF AVAILABLE REPORTS TO THE TEMPLATE
if($finalreportlist[0]) // if there is at least one report the user can see...send details to template
{
	$xoopsTpl->assign('defaultreportselector', _formulize_CHOOSEREPORT);
	$xoopsTpl->assign('availreports', $finalreportlist);
}
else
{
	$xoopsTpl->assign('defaultreportselector', _formulize_NOREPORTSAVAIL);
}


// GENERATE AVAILABLE REPORT SCOPES....
//if($reportingyn AND $showscope) // only generate if the report allows it.
if($showscope) // only generate if the report allows it.
{
// 1. get all groups that have add permission for the form (done above)
// 2. if user is not admin, find the user's groups in the groupidadd array (done through the group_user_link table
// 3. get all users in the final set of groups
// 4. make an array of all the groups plus all the users and send it to the template

array($scopegroups); // the list of groupids that are valid scopes
if(!$ismoduleadmin) // note that only module admins see all groups.  All other users see only the groups they are members of (regular admins will see scope selections even for nongroupscope forms).
{
	$sgindexer = 0;
	foreach($groupuser as $ugp)
	{
		if(in_array($ugp, $groupidadd))
		{
			$scopegroups[$sgindexer] = $ugp;
			$sgindexer++;
		}
	}
}
else
{
	$scopegroups = $groupidadd;
}

	//get the names of the scopegroups, and the users in the scope groups

   array($masteruserscopelist);
   $scopenamesindexer = 0;
   $userlistindexer = 0;
	foreach($scopegroups as $sgid)
	{
		$scopeuserlistres = $xoopsDB->query("SELECT uid FROM ".$xoopsDB->prefix("groups_users_link")." WHERE groupid=".$sgid);
		

		while ( $scopeulistrow = mysql_fetch_row ( $scopeuserlistres ) ) {
//			print "group: $sgid<br>";
//			print "uid found: $scopeulistrow[0]<br>";
	  		$masteruserscopelist[$userlistindexer] = $scopeulistrow[0];
			if(in_array($scopeulistrow[0], $sentscope))
			{
				$masteruserscopelist[$userlistindexer] .= " selected";
			}
			$queryforrealnames = "SELECT name FROM " . $xoopsDB->prefix("users") . " WHERE uid=$scopeulistrow[0]";
			$resqforrealnames = mysql_query($queryforrealnames);
			$rowqforrealnames = mysql_fetch_row($resqforrealnames);
//			print "username: $rowqforrealnames[0]<br>";
			$masteruserscopelist[$userlistindexer] .= ">" .  _formulize_USERSCOPE . $rowqforrealnames[0];
			$userlistindexer++;
  		}

		$sgnameq = "SELECT name FROM " . $xoopsDB->prefix("groups") . " WHERE groupid=$sgid";
		$ressgname = mysql_query($sgnameq);
		$rowsgname = mysql_fetch_row($ressgname);
		$scopegroups[$scopenamesindexer] = "g" . $sgid; // adds a g to the beginning of the  current groupid, so that it can be identified as a group id later on when reading scope selections the user makes.  
		// checks to see if this scope was a selected scope and adds on other text as needed to make the selection box work in the template.
		if(in_array($scopegroups[$scopenamesindexer], $sentscope))
		{
			$scopegroups[$scopenamesindexer] .= " selected";	
		}
		$scopegroups[$scopenamesindexer] .= ">" . _formulize_GROUPSCOPE . $rowsgname[0];
		$scopenamesindexer++;
	}

	// this will now be an array of all the unique user ids in groups that have add permission in the form (culled by groups the user is a member of if they are not an admin)
	$masteruserscopelist = array_unique($masteruserscopelist);

	function cmp($a, $b) // a function that will do a sort on an array by comparing the text after the last > in the values.
	{
		$a = substr(strrchr($a, ">"), 1);
		$b = substr(strrchr($b, ">"), 1);

		if ($a == $b) {
	      return 0;
   		}
		return ($a < $b) ? -1 : 1;
	}

	usort($masteruserscopelist, "cmp");

array($tempscopenames);

$masteruserscopelist = array_reverse($masteruserscopelist);
//print_r($masteruserscopelist);
//$scopegroups = array_reverse($scopegroups);
//print_r($scopegroups);
$tempscopenames = array_merge($masteruserscopelist, $scopegroups);
$tempscopenames = array_reverse($tempscopenames);

$xoopsTpl->assign('reportscope', _formulize_REPORTSCOPE);
$xoopsTpl->assign('scopenames', $tempscopenames);

}
// END OF GENERATING REPORT SCOPES...


//============ start of sorting handling that sends info to template (and excludes multiple entry form elements (ie: checkboxes))
//Filter the reqFieldsJwe for the fields we allow sorting on.
$sortq = "SELECT ele_caption, ele_value FROM ". $xoopsDB->prefix("form") . " WHERE id_form = $id_form AND (ele_type = \"checkbox\" OR (ele_type = \"select\" AND ele_value REGEXP \"^.{19}1\")) ORDER BY ele_order";
$ressortq = mysql_query($sortq);
$sortarrayb = 0;
while ($rowsortq = mysql_fetch_row($ressortq))
{
	$sortcheckarray[$sortarrayb] =  $rowsortq[0];
	$sortarrayb++;
}

//print_r($sortcheckarray);

$sortarrayb = 0;
$allowedsort = -1;
foreach($reqFieldsJwe as $onefield)
{
	if(!in_array($onefield, $sortcheckarray))
	{
		$reqSortFields[$sortarrayb] = $onefield;
		$allowedsort++;
	}
	else
	{
		$reqSortFields[$sortarrayb] = "";
	}
	$sortarrayb++;
}
$xoopsTpl->assign('tempcaptionssort', $reqSortFields);

//set the array that has 1,2,3,4,5 etc for using in the sort priority drop downs
	
	$sortcreateindexer = 0;
	array($sort_indexes);
	for($sl=0;$sl<=$allowedsort;$sl++) // by controlling the '<=' you can control the number of sorting order options. 
	{
		$sort_indexes[$sortcreateindexer] = $sortcreateindexer+1;
		$sortcreateindexer++;
	}
	$xoopsTpl->assign('sort_index_array', $sort_indexes);
// ================= end of sorting handling for template


//7th array element after exploding ele_value on means multiple select box if it starts with 1.
//a:3:{i:0;i:1;i:1;i:0


// START OF NEW SEARCH SYSTEM

// Initialization:
// 1. Pull in array of all search terms entered by the user
// 2. Convert multiple search terms to an array themselves
// 3. Pull in arrayS of all the values in all fields that have been searched on
// 5. Determine the global AND/OR setting
// 6. Determine all local AND/OR settings

// Preparation:
// 1. Y/N questions turned into 'yes' and 'no'
// 2. Links turned into multiterms
// 4. Convert multiple terms for an entry to an array themselves

// Search routine:
// 1. Check each field array against the search term(s) for that field
// i. check all values in each entry in the field (ie: consider multiple terms together, that's why they're an array)
// ii. if local AND, entry must pass all search terms in order to be flagged as found
// iii. if local OR, entry must pass at least one search term in order to be flagged as found
// 2. maintain separate arrays or records for the found entries in each field
// 3. once all fields have been checked, check all found entries in each field according to the global AND/OR setting
// i. if global OR, all found entries are returned
// ii. if global AND, intersection of found entries is returned

// Ultimately, create $userreportingquery which is applied to the main query to return the found entries.

// $search_typeArray -- Search operator, array index equals caption order
// $search_textArray -- Search terms, comma separated, \ escape character, array index equals caption order
// $andorArray -- local and/or setting, array index equal caption order
// $globalandor -- global and/or setting
// $reqFieldsJwe -- caption names, array index equals caption order

$userMadeASearch = 0; // flag used to tell if there was a search
// start looping through the fields
for($i=0;$i<count($reqFieldsJwe);$i++)
{
	if($search_textArray[$i] != "") // if this was a field where a search was requested, then do a search
	{
		$userMadeASearch = 1;
		//**************************
		// get all the values and other necessary data for this field
		array_splice($thisfv, 0); // blank the arrays
		array_splice($thisid, 0);
		array_splice($thistype, 0);
		if($gscopeparam)
		{
			$thisfieldvaluesq = "SELECT id_req, ele_type, ele_value FROM " . $xoopsDB->prefix("form_form") . " WHERE ele_caption=\"" . $reqFieldsJwe[$i] . "\" AND id_form = $id_form AND ($gscopeparam) ORDER BY id_req";
		}
		else
		{
			$thisfieldvaluesq = "SELECT id_req, ele_type, ele_value FROM " . $xoopsDB->prefix("form_form") . " WHERE ele_caption=\"" . $reqFieldsJwe[$i] . "\" AND id_form = $id_form AND uid=$uid ORDER BY id_req";
		}
		$resfvq = $xoopsDB->query($thisfieldvaluesq);		
		while ($rowfvq = $xoopsDB->fetchRow($resfvq))
		{
			$thisid[] = $rowfvq[0];
			$thistype[] = $rowfvq[1];
			$thisfv[] = $rowfvq[2];

		}

		// *******************************
		//setup the search term array
		$termsintermediate = str_replace("[,]", "*^%=*", $search_textArray[$i]); // save the escaped commas
		array_splice($termsarray, 0);										
		$termsarray = explode(",", $termsintermediate); // split out individual terms
		for($x=0;$x<count($termsarray);$x++)
		{
			$termsarray[$x] = str_replace("*^%=*", ",", $termsarray[$x]); // replace the escaped commas
		}
		
		// ********************************
		//convert yn questions from numbers into YES and NO 
		switch($xoops_config['language'])
		{
			case "french":
				$yeslangstring = "OUI";
				$nolangstring = "NON";
				break;
			case "english":
			default:
				$yeslangstring = "YES";
				$nolangstring = "NO";
		}
		for($x=0;$x<count($thistype);$x++)
		{		
			if($thistype[$x] == "yn") // we have a yes/no column
			{
				if($thisfv[$x] == "1")
				{
					$thisfv[$x] = $yeslangstring;
				}
				else
				{
					$thisfv[$x] = $nolangstring;
				}
			}
		}

		// **************************
		// convert links to multiterms
		for($x=0;$x<count($thisfv);$x++)
		{
			if(strstr($thisfv[$x], "#*=:*")) // if we've found a multiterm
			{
				array_splice($templinkedvals, 0);
				array_splice($templinkedvals2, 0);
				$templinkedvals = explode("#*=:*", $thisfv[$x]);
				$templinkedvals2 = explode("[=*9*:", $templinkedvals[2]);
				$thislinkedval = ''; // reset string so we can write it again.

				$thisfv[$x] = "";
				foreach($templinkedvals2 as $anentry)
				{
					$textq = "SELECT ele_value FROM " . $xoopsDB->prefix("form_form") . " WHERE ele_id=$anentry GROUP BY ele_value ORDER BY ele_value";
					$restextq = mysql_query($textq);
					while ($rowtextq = mysql_fetch_row($restextq))
					{
						$thisfv[$x] .= "*=+*:" . $rowtextq[0];
					}
				}
			}
		}				
			
		// *****************************
		// convert multiterms to an array and make thisfv equal to the array -- of all the prep procedures, DO THIS ONE LAST!
		for($x=0;$x<count($thisfv);$x++)
		{
			if(strstr($thisfv[$x], "*=+*:")) // if we've found a multiterm
			{
				$thesemultiterms = explode("*=+*:", $thisfv[$x]);
				//print_r($thesemultiterms);
				//print "<br>";
				if($thesemultiterms[0] == "") // get rid of any leading blanks, caused by the delimiter going at the beginning of the value string in the DB
				{
					array_shift($thesemultiterms);
				}				
				if(count($thesemultiterms)>1) // if there's more than one value, then make thisfv equal an array of the multiterms
				{
					$thisfv[$x] = $thesemultiterms;					
				}
				else
				{
					$thisfv[$x] = $thesemultiterms[0];
				}
				//print_r($thisfv[$x]);
				//print "<br>";
			}
		}

		// ***************************
		// START THE ACTUAL EVALUATION OF ENTRIES AGAINST SEARCH TERMS
		// ***************************
		for($x=0;$x<count($thisfv);$x++) // loop through all the values of all the entries in this field
		{
			if(!is_array($thisfv[$x])) // if it's not a multiterm, then turn it into an array anyway so we can put it in the same loop
			{
				$temparray[0] = $thisfv[$x];
				$thisfv[$x] = $temparray;
			}
			// set the matchtarget based on the local and/or setting -- only one search term needs to match if it's a local OR, otherwise we need each search term to match for a local AND
			if($andorArray[$i] == "or")
			{
				if($search_typeArray[$i] == "not" OR $search_typeArray[$i] == "notlike")
				{
					$matchtarget = count($thisfv[$x]); 
				}
				else
				{
					$matchtarget = 1;
				}
			}
			else
			{
				$matchtarget = count($termsarray);
			}
			$matchscore = 0;
			array_splice($matchoverride, 0);
			foreach($termsarray as $oneterm) // get the first search term and start looking for it
			{
				foreach($thisfv[$x] as $onevalue) // look through all the terms in the field and compare to the search term
				{
					$match = 0; // flag used to determine if we've got a hit
					// cleanup terms for matching...
					$oneterm = trim(strtoupper($oneterm));
					$onevalue = trim(strtoupper($onevalue));

					//handling of special terms
					if($oneterm == "{TODAY}")
					{
						$todaysdate = date("Y-m-d");
						//print $todaysdate;
						$oneterm = $todaysdate;
					}
					// BLANK OPTION IS COMMENTED UNTIL FOLLOWING PROBLEM FIXED:  blank values are not stored in the DB, they are actually inserted into the selvalues array (which isn't even created until after the master queries below).  Since they are not in the DB, the thisfv[$x] array will contain no value corresponding the blank.  It won't be evaluated against, no matter what search terms we think up.
					/*if($oneterm == "{BLANK}")
					{
						$oneterm = "";
					}*/

					//print "<br>term: $oneterm";
					//print "<br>value: $onevalue";
					switch($search_typeArray[$i])
					{
						case "equals":
							if($onevalue == $oneterm) { $match = 1; }
							break;
						case "not":
							if($onevalue == $oneterm) { $matchoverride[] = "override"; } 
							if($onevalue <> $oneterm) { $match = 1; }
							break;
						case "greaterthan":
							if($onevalue > $oneterm) { $match = 1; }
							break;
						case "greaterthanequal":
							if($onevalue >= $oneterm) { $match = 1; }
							break;
						case "lessthan":
							if($onevalue < $oneterm) { $match = 1; }
							break;
						case "lessthanequal":
							if($onevalue <= $oneterm) { $match = 1; }
							break;
						case "like":
							if(strstr($onevalue, $oneterm)) { $match = 1; }
							break;
						case "notlike":
							if(strstr($onevalue, $oneterm)) { $matchoverride[] = "override"; }
							if(!strstr($onevalue, $oneterm)) { $match = 1; }
							break;
					}
					//print "<br>match? $match";
					// handle a hit -- increment the number of matches we've made if we've got another match, and then break out of the look-at-this-entry loop if we've reached the match target
					if($match)
					{ 
						$matchscore++;
					}
				} // end of look-at-each-value-in-the-entry loop
				//print_r($matchoverride);
				//print "<br>";
				//print "number of terms in the previous entry: " . count($thisfv[$x]) . "<br>";
				if(count($matchoverride) == count($thisfv[$x])) // every term in this entry equaled a negative search term, so this entry is excluded no matter what
				{
					break;	
				}
			} // end of look-at-each-search-term-and-try-to-find-it-in-the-values-for-this-entry					
			// check that we aren't in an override situation (one override for a local OR or at least one override per search term for a local AND)
			if($matchoverride[0])
			{
				if($andorArray[$i] == "or")
				{
					$match = "override";
				}
				elseif(count($matchoverride)>=count($termsarray) OR count($matchoverride) == count($thisfv[$x]))
				{
					$match = "override";
				}
			}
			// check to see if we've reached our target for matches
			//print "<br>matchscore: $matchscore<br>matchtarget: $matchtarget<br>match: $match<br><br>";
			if($matchscore >= $matchtarget AND is_numeric($match)) // is numeric will exclude the "override" setting
			{ 
				$matchedentries[$i][] = $thisid[$x]; // record the id of this entry in the list of matches made for this field
				//print_r($matchedentries);
			}
		} // end of look-through-every-value-in-this-field		
	} // end of if-there-were-search-terms-for-this-field-then-do-a-search
}// end of loop-through-all-the-fields-and-perform-necessary-searches

// ************
// look at the GLOBAL AND/OR setting and determine which matchedentries to return
	
if($globalandor == "and" AND count($matchedentries)>1) // intersect the matchedentries arrays if there is more than one.
{
	$start = 1;
	foreach($matchedentries as $me1)
	{
		if($start) // set the savedids to be equal to the first array so that we can get the intersection going
		{
			$savedids = $me1;
			$start = 0;
		}
		$matchedintersect = array_intersect($savedids, $me1);	
		$savedids = $matchedintersect; // preserve the intersection for use on the next iteration
	}
	array_splice($matchedentries, 0); // blank the matchedentries and then replace with the intersection
	$matchedentries[0] = $matchedintersect;
}

// need to handle cases where no match has been made so that nothing is returned....
if($userMadeASearch AND count($matchedentries) == 0) 
{
	$userreportingquery = "AND (id_req=\"z\")"; 
}
elseif($userMadeASearch)
{
// setup the userreportingquery
$start = 1;
foreach($matchedentries as $me1)
{
	foreach($me1 as $me2)
	{
		if($start)
		{
			$userreportingquery = "AND (id_req=" . $me2;
			$start=0;
		}
		else
		{
			$userreportingquery .= " OR id_req=" . $me2;		
		}
	}
}
$userreportingquery .= ")";
$start=0;
} // end of if user made a search

// *******************
// END OF NEW SEARCH LOGIC
// *******************

// apply the groupscope param to the query (query with the whole userlist)
if($gscopeparam)
{
//	$queryjwe = "SELECT id_req, ele_caption, ele_value FROM " . $xoopsDB->prefix("form_form") . " WHERE id_form=$id_form AND ($gscopeparam) $userreportingquery ORDER BY id_req";
	$queryjwe = "SELECT id_req FROM " . $xoopsDB->prefix("form_form") . " WHERE id_form=$id_form AND ($gscopeparam) $userreportingquery ORDER BY id_req";
}
else
{
//	$queryjwe = "SELECT id_req, ele_caption, ele_value FROM " . $xoopsDB->prefix("form_form") . " WHERE id_form=$id_form AND uid=$uid $userreportingquery ORDER BY id_req";
	$queryjwe = "SELECT id_req FROM " . $xoopsDB->prefix("form_form") . " WHERE id_form=$id_form AND uid=$uid $userreportingquery ORDER BY id_req";
}
//print "initial req query: $queryjwe<br>";
$recordsjwe = mysql_query($queryjwe);
$previndex = "none";
array ($totalresultarray);
$totalentriesindex = 0;
while ($rowjwe = mysql_fetch_row($recordsjwe)) // go through result row by row.
{
//		$totalresultarray[$totalentriesindex] = $rowjwe;
		$finalselectidreq[$totalentriesindex] = $rowjwe[0];
//		$finalselectidele[$totalentriesindex] = $rowjwe[0];
		$totalentriesindex++;
}

// redo the query this time going only by id_req!
// first make a query expression out of all the reqids...

$finalreqq = "";

//remove duplicates from arrays to give us a count of the number of records to loop through
$finalselectidreq = array_unique($finalselectidreq);
//	print_r($finalselectidreq);

$atleastonereq = 0;
if($finalselectidreq[0]) // if there is at least one entry found...
{
$atleastonereq = 1;
$freq = 0;
foreach($finalselectidreq as $thisfinalreq)
{
	if($freq == 0)
	{
		$finalreqq .= "id_req=$thisfinalreq"; 
	}
	else
	{
		$finalreqq .= " OR id_req=$thisfinalreq"; 
	}
	$freq++;
}

$totalentriesindex = 0;
$realqueryjwe = "SELECT id_req, ele_caption, ele_value FROM " . $xoopsDB->prefix("form_form") . " WHERE $finalreqq ORDER BY id_req";

//print "<br>realquery: $realqueryjwe<br>";

$realrecordsjwe = mysql_query($realqueryjwe);

while($realrowjwe = mysql_fetch_row($realrecordsjwe))
{
		$totalresultarray[$totalentriesindex] = $realrowjwe;
		$finalselectidreq[$totalentriesindex] = $realrowjwe[0];

		$totalentriesindex++;
}

$finalselectidreq = array_unique($finalselectidreq);
sort($finalselectidreq);
/*print "totalresultarray:<br>";
print_r($totalresultarray);
print "<br>totalentriesindex = $totalentriesindex<br>";*/
} // end of if-there-is-at-least-one-entry-found


// gather all the captions for each record, then call up each required caption, and look through the record to find out if it's there...

// I AM SURE THERE IS A BETTER WAY TO DO THIS PART BELOW, THIS HAS BEEN A VERY DIFFICULT PIECE OF CODE TO DEBUG, BUT IT IS WORKING NOW, SO I'M LEAVING IT ALONE!
// start looping through the total array
$valueindexer = 0;
$captionindexer = 0;
$getinitialte = $totalresultarray[0];
$thisentry = $getinitialte[0];
for($i=0;$i<=$totalentriesindex;$i++)
{
	/*print "tec<br>";
	print_r($thisentryscaptions);
	print "<br>";*/
	// for each record returned from the DB...

	$thisrecordpointer = 0;
//	print "About to start a loop<br>";
	$preventry = $thisentry;



	foreach($totalresultarray[$i] as $currdbrecord)
	{
//		print "This record pointer: $thisrecordpointer<br>";
//		print "captionindexer: $captionindexer<br>";
		// set the entry checker...
		
//		print "P: $preventry<br>";
		if($thisrecordpointer == 0)
		{
			$thisentry = $currdbrecord;
		}
//		print "T: $thisentry<br>";
		// if we've moved on to a new entry, then look through the entry captions we've stored so far...
		if($thisentry != $preventry)
		{
/*			print "<br>We're in!</br>This entry's captions: ";
			print_r($thisentryscaptions);
			print "<br>This entry's values: ";
			print_r($thisentrysvalues);
			print "<br>";*/
			// now we can finally look for the required fields in the captions that exist for that entry...
			foreach($reqFieldsJwe as $curreqfield)
			{	
//				print "Searching for: $curreqfield<br>";
				// if the required field is present in the entry...
				$valuefinder = 0;
				foreach($thisentryscaptions as $thisentcap)
				{
					//convert apostrophes in reqFieldsJwe so they will match captions got from form_form table...
					$curreqfield = eregi_replace ("'", "`", $curreqfield);  //&#039;", "`", $curreqfield);
//					print "<br>Caption number: $valuefinder<br>";
//					print "reqfield: $curreqfield<br>thisentcap: $thisentcap<br>";
					if($thisentcap == $curreqfield)
					{
//						print "reqfield found! -- $thisentcap -- $thisentrysvalues[$valuefinder]<br>";
						$selvals[$valueindexer] = $thisentrysvalues[$valuefinder];
						$founditalready = 1;
						break;
					}
					$valuefinder++;
				}
				if(!$founditalready)
				{
					$selvals[$valueindexer] = "";
				}
//				print "$curreqfield: $selvals[$valueindexer]<br>";
				$valueindexer++;
				$founditalready = 0;
			}
			// kill the arrays used for captions and values...
			for($z=0;$z<=$captionindexer;$z++)
			{
				array_pop($thisentrysvalues);
				array_pop($thisentryscaptions);
			}
/*			print"deadcaptions: ";
			print_r($thisentryscaptions);
			print"<br>deadvalues: ";
			print_r($thisentrysvalues);
			print "<br><br>";*/
			$captionindexer = 0; //reset this to start capturing the next set of captions.
			$preventry = $thisentry;
		}
				
		// ignore the first field, because we only care about the second (caption) field
		if($thisrecordpointer == 1)
		{
			$thisentryscaptions[$captionindexer] = $currdbrecord;

		}
		if($thisrecordpointer == 2)
		{
			$thisentrysvalues[$captionindexer] = $currdbrecord;
			$captionindexer++;
		}
		$thisrecordpointer++;
/*		print "currdbrecord:<br>";
		print_r($currdbrecord);
		print "<br>";*/
	}
}


// ======================== code below is duplicated from inside the foreach loop above, since it needs to execute once more upon exiting the loops.  Big kludge, very ugly, but I can't think of a better way to do it right now.
//print "<br>We're in!</br>This entry's captions: ";
/*			print_r($thisentryscaptions);
			print "<br>This entry's values: ";
			print_r($thisentrysvalues);
			print "<br>";*/
			// now we can finally look for the required fields in the captions that exist for that entry...
			foreach($reqFieldsJwe as $curreqfield)
			{	
//				print "Searching for: $curreqfield<br>";
				// if the required field is present in the entry...
				$valuefinder = 0;
				foreach($thisentryscaptions as $thisentcap)
				{
					//convert apostrophes in reqFieldsJwe so they will match captions got from form_form table...
					$curreqfield = eregi_replace ("'", "`", $curreqfield);
//					print "<br>Caption number: $valuefinder<br>";
//					print "reqfield: $curreqfield<br>thisentcap: $thisentcap<br>";
					if($thisentcap == $curreqfield)
					{
//						print "reqfield found! -- $thisentcap -- $thisentrysvalues[$valuefinder]<br>";
						$selvals[$valueindexer] = $thisentrysvalues[$valuefinder];
						$founditalready = 1;
						break;
					}
					$valuefinder++;
				}
				if(!$founditalready)
				{
					$selvals[$valueindexer] = "";
				}
//				print "$curreqfield: $selvals[$valueindexer]<br>";
				$valueindexer++;
				$founditalready = 0;
			}
			// kill the arrays used for captions and values...
			for($z=0;$z<=$captionindexer;$z++)
			{
				array_pop($thisentrysvalues);
				array_pop($thisentryscaptions);
			}
/*			print"deadcaptions: ";
			print_r($thisentryscaptions);
			print"<br>deadvalues: ";
			print_r($thisentrysvalues);
			print "<br><br>";*/
			$captionindexer = 0; //reset this to start capturing the next set of captions.
			$preventry = $thisentry;

//==================================== end of duplicate code block

// ***** IMPORTANT LOOP THAT PREPARES ENTRIES FOR DISPLAY ON VIEW ENTRIES PAGE *******
	// Loop alters display of entries to suit printing to screen
	$fieldnamecounter = 0;
	foreach($selvals as $selvalstostripfrom)
	{

		//modify a linked selectbox to display correctly.
		//1. identify values that are like this
		//2. query what their actual text is right now
		//3. reformat as text[standard delimiter]text[starndard delimiter]text, etc
		if(strstr($selvalstostripfrom, "#*=:*"))
		{
			array($templinkedvals);
			array_splice($templinkedvals, 0);
			array($templinkedvals2);
			array_splice($templinkedvals2, 0);
			$templinkedvals = explode("#*=:*", $selvalstostripfrom);
			$templinkedvals2 = explode("[=*9*:", $templinkedvals[2]);
			$selvalstostripfrom = ''; // reset string so we can write it again.

			/*print "templinkedvals for display: ";
			print_r($templinkedvals2);*/
			
			foreach($templinkedvals2 as $anentry)
			{
				$textq = "SELECT ele_value FROM " . $xoopsDB->prefix("form_form") . " WHERE ele_id=$anentry GROUP BY ele_value ORDER BY ele_value";
				$restextq = mysql_query($textq);
				$rowtextq = mysql_fetch_row($restextq);
				$selvalstostripfrom = $selvalstostripfrom . "*=+*:" . $rowtextq[0];
			}
		}


		//sql to get is yes/no
		if($gscopeparam)
		{
			$isyesnoquestion = "SELECT ele_type FROM " . $xoopsDB->prefix("form_form") . " WHERE id_form=$id_form AND ele_value=$selvalstostripfrom AND ele_caption = \"$reqFieldsJwe[$fieldnamecounter]\" AND ($gscopeparam) ORDER BY id_req";
		}
		else
		{
			$isyesnoquestion = "SELECT ele_type FROM " . $xoopsDB->prefix("form_form") . " WHERE id_form=$id_form AND ele_value=$selvalstostripfrom AND ele_caption = \"$reqFieldsJwe[$fieldnamecounter]\" AND uid=$uid ORDER BY id_req";
		}
		$resisyesno = mysql_query($isyesnoquestion);
		
		while ($rowisyesno = mysql_fetch_row($resisyesno))
		{
			if($rowisyesno[0] == "yn") // if we've found one
			{
				if($selvalstostripfrom == "1")
				{
					$selvalstostripfrom = _formulize_TEMP_QYES;
				}
				elseif($selvalstostripfrom == "2")
				{
					$selvalstostripfrom = _formulize_TEMP_QNO;
				}
				else
				{
					$selvalstostripfrom = "";
				}
				break; // we're done
			}
		} 

		$placeholderVals = stripslashes($selvalstostripfrom);
		//and remove any leading *=+*: while we're at it...
		if(substr($placeholderVals, 0, 5) == "*=+*:")
		{
			$placeholderVals = substr_replace($placeholderVals, "", 0, 5); 
		}
		//print "*$placeholderVals*<br>";
		$tempValsJwe[] = $placeholderVals;
		$fieldnamecounter++;
		if($fieldnamecounter == count($reqFieldsJwe)) // reset the fieldname counter if we've reached the end of a row.
		{
			$fieldnamecounter = 0;
		}
	}
	$selvals = $tempValsJwe;

//=================
//perform summary calculations...
//put data into columns...

//find the captions where user has requested a calc or a sort
//calc_typeArray
//sort_orderArray
//ascdscArray

//array($calccols);
//array($sortcols);
//array($sortpri);
$calccolscounter = 0;
$sortcolscounter = 0;
for($y=0;$y<count($reqFieldsJwe);$y++)
{
	if($calc_typeArray[$y]) // if a calculation was requested, save the column ID
	{
		$calccols[$calccolscounter] = $y;
		$calccolscounter++;
	}
	if($sort_orderArray[$y] <> "") // if a sort was requested, save the column ID
	{
		$sortcols[$sortcolscounter] = $y;
		$sortpri[$sortcolscounter] = $sort_orderArray[$y];
		$sortdir[$sortcolscounter] = $ascdscArray[$y];
		$sortcolscounter++;
	}
}

if($sortcolscounter OR $calccolscounter) // if a calculation or a sort was requested then prepare the data for manipulation
{	
	// how many columns?
	$numcols = count($reqFieldsJwe);
	$numcols--; // minus 1 so we can nicely use this as an array address

	$colnamenums = 0;
	foreach($reqFieldsJwe as $colname)
	{
		$pc = str_replace(" ", "_", $colname); // replace the spaces
		$pc = str_replace(".", "_", $pc); // replace the periods
		$arrayindex = $colnamenums;
		$colarrayname[$arrayindex] = $pc . "ColArray";
		$colnamenums++;
	}

	$currow = 0;
	$curcol = 0;
//	print "NUMBER OF COLUMNS: $numcols";
	foreach($selvals as $thisvalue)
	{
		if($curcol <= $numcols)
		{
/*			print "<br>Now writing to $colarrayname[$curcol]";
			print "<br>Current key is: $currow";
			print "<br>Current value is: $thisvalue";*/
			$idtouse = $currow . "a"; // setup to be compatible with an asort below
			${$colarrayname[$curcol]}[$idtouse] = $thisvalue;
		}
		else // if we've moved onto a new row...
		{
			$curcol = 0;
			$currow++;
/*			print "<br>Now writing to $colarrayname[$curcol]";
			print "<br>Current key is: $currow";
			print "<br>Current value is: $thisvalue";*/
			$idtouse = $currow . "a"; // setup to be compatible with an asort below
			${$colarrayname[$curcol]}[$idtouse] = $thisvalue;
		}
		$curcol++;
	}

	// make up the array of column names to send to template
	array($calcFieldsJwe);
	$calcfieldsindexer = 0;
	foreach($calccols as $acolid)
	{
		$calcFieldsJwe[$calcfieldsindexer] = $reqFieldsJwe[$acolid];
		$calcfieldsindexer++;
	}
	$xoopsTpl->assign('tempcalccaptionsjwe', $calcFieldsJwe);

/*	for($nc=0;$nc<=$numcols;$nc++)
	{
		print "<br>$colarrayname[$nc]: ";
		print_r(${$colarrayname[$nc]});
		print "<br>";
	}*/

//now if it's required we have arrays as follows:
//[captionname]ColArray -- one for each caption
//$colarrayname is an array with all the names in it
//keys of each column array correspond to the rows, 0 through n, but are named 1i 2i 3i etc, so they can be asorted by the sort function.

} // end if a calc or sort was requested


if($sortcolscounter) // if sorts were requested, then do them...
{

// how many columns?
	$numcols = count($reqSortFields);
	$numcols--; // minus 1 so we can nicely use this as an array address
	

// 1. identify the column priority for sorts
// 1.5 identify the direction of sorting
// 2. sort all columns plus the req_ids from last to first sorting priority
// 3. write out the column data to selvals array
// finalselectidreq is the array of ids.

/*int "<br>cols: ";
print_r($sortcols);
print "<br>dir: ";
print_r($sortdir);
print "<br>pri: ";
print_r($sortpri);*/

// assign priorities to columns
for($sc=0;$sc<count($sortpri);$sc++)
{
	$currcol = $sortcols[$sc];
	$currcolname = $colarrayname[$currcol];
//	print "$currcolname<br>";
	$sp[$currcolname] = $sortpri[$sc];
	$sdir[$currcolname] = $sortdir[$sc];
	//print "<br><br>Column to sort: $sp[$sc]<br>priority: $sortpri[$sc]";
}
//print_r($sp);
arsort($sp); // puts the columns that need to be sorted into proper sorting order
//print_r($sp);

$sortcol = array_keys($sp);

/*print "<br>";
print_r($sortcol);
print "<br>";
print_r($sdir);*/


//prepare the finalselectidreq for resorting by adding a to the end of it's number indexes...
$newid = 0;
foreach($finalselectidreq as $idreqrekey)
{
	$newida = $newid . "a";
	$phfinal[$newida] = $idreqrekey;
	$newid++;
}
array_splice($finalselectidreq, 0);
$finalselectidreq = $phfinal;

/*print "<br>initial idreqs: ";
print_r($finalselectidreq);
print "<br>";*/

//print "numcols: $numcols<br>";
$colcounter = 0;
foreach($sortcol as $curcoltosort)
{
	foreach(${$curcoltosort} as $numorstr)
	{
		if($numorstr)
		{
//			print "<br>First entry evaluated: $numorstr<br>";
			$numericornot = is_numeric($numorstr);
			break;
		}
	}			

	if($numericornot)
	{

	$nextcol = $sortcol[$colcounter+1];
	if($sdir[$nextcol] == "DESC") // reverse the requested sorting order if the next column will sort things in reverse (putting DESC in the If instead of ASC allows the primary column -- last col -- to be sorted as requested since there is no next column)
	{
		if($sdir[$curcoltosort] == "ASC")
		{
			arsort(${$curcoltosort}, SORT_NUMERIC);
		}
		else
		{
			asort(${$curcoltosort}, SORT_NUMERIC);
		}
	}
	else
	{
		if($sdir[$curcoltosort] == "ASC")
		{
			asort(${$curcoltosort}, SORT_NUMERIC);
		}
		else
		{
			arsort(${$curcoltosort}, SORT_NUMERIC);
		}
	}


	} else { // middle of the isnumeric condition

	$nextcol = $sortcol[$colcounter+1];
	if($sdir[$nextcol] == "DESC") // reverse the requested sorting order if the next column will sort things in reverse (putting DESC in the If instead of ASC allows the primary column -- last col -- to be sorted as requested since there is no next column)
	{
		if($sdir[$curcoltosort] == "ASC")
		{
			arsort(${$curcoltosort}, SORT_STRING);
		}
		else
		{
			asort(${$curcoltosort}, SORT_STRING);
		}
	}
	else
	{
		if($sdir[$curcoltosort] == "ASC")
		{
			asort(${$curcoltosort}, SORT_STRING);
		}
		else
		{
			arsort(${$curcoltosort}, SORT_STRING);
		}
	}

	} // end of isnumeric
	$colcounter++;

	// now match id_reqs and all other columns to this one.

/*	print_r(${$curcoltosort});
	print "<br>";
	print "<br>old id_reqs: ";
	print_r($finalselectidreq);
	print "<br>old id_reqs: ";*/

	$synccounter = 0;
	foreach(array_keys(${$curcoltosort}) as $sortedkeys)
	{

//		print "<br><br>Now resorting idreqs:<br>";
//		print "New key for position $synccounter: $sortedkeys";
		//$fv = ${$curcoltosort}[$sortedkeys];
		//print "$fv";
//		$oldp = $finalselectidreq[$sortedkeys];
//		print "<br>Old id_req at with that key: $oldp";
		
		$newid = $synccounter . "a";
		$newreqs[$newid] = $finalselectidreq[$sortedkeys];

//		print "<br>New id_req at position $syncounter: ";
//		print "$newreqs[$newid]";
		
		$synccounter++;
	}
	array_splice($finalselectidreq, 0);
	$finalselectidreq = $newreqs;
	array_splice($newreqs, 0);
	
//	print "<br><br>New id_req array: ";
//	print_r($finalselectidreq);
//	print "<br>";


	
	foreach($colarrayname as $coltosync)
	{
		$synccounter = 0;
		if($coltosync != $curcoltosort)
		{
/*			print "<br>Now sorting $coltosync<br>";
			print "Old order of $coltosync: ";
			print_r(${$coltosync});*/
			foreach(array_keys(${$curcoltosort}) as $sortedkeys)
			{
				$newid = $synccounter . "a";
				$newreqs[$newid] = ${$coltosync}[$sortedkeys];
				$synccounter++;
			}
			array_splice(${$coltosync}, 0);
			${$coltosync} = $newreqs;
			array_splice($newreqs, 0);
//			print "New order of $coltosync: ";
//			print_r(${$coltosync});
		}
	}
	
	// normalize the keys on the curcoltosort so it can be manipulated again

/*	print "<BR><BR>Now normalizing current column ($curcoltosort)<br>";
	print "sorted array: ";
	print_r(${$curcoltosort});*/
	$synccounter = 0;
	foreach(${$curcoltosort} as $rationalizeval)
	{
		$newid = $synccounter . "a";
		$newreqs[$newid] = $rationalizeval;
		$synccounter++;
	}
	array_splice(${$curcoltosort}, 0);
	${$curcoltosort} = $newreqs;
	array_splice($newreqs, 0);
/*	print "<br>normalized array: ";
	print_r(${$curcoltosort});
	print "<br>";*/
}

// how many columns? Remake the variable since it was adjusted for sort only columns
	$numcols = count($reqFieldsJwe);
	$numcols--; // minus 1 so we can nicely use this as an array address


// take all the entries in the columns and turn them back into a single selvals array
$selvalindexer = 0;
for($rowcounter=0;$rowcounter<count($finalselectidreq);$rowcounter++)
{
	foreach($colarrayname as $remakeselvals)
	{
		// take the entry in the current row from each column and put it in selvals
		$idtouse = $rowcounter . "a";
		$selvals[$selvalindexer] = ${$remakeselvals}[$idtouse];
		$selvalindexer++;
	}
}	
//print "<br>Final sorted id_reqs: ";
//print_r($finalselectidreq);

} // end if there were sorts 


if($calccolscounter) // now do the calculations...
{

	
	$xoopsTpl->assign('summaryon', "on");
	for($v=0;$v<=$numcols;$v++) // for each column....
	{
		$calcoutput = "";
		foreach($calc_typeArray[$v] as $thiscalcArray)// for each possible calculation...
		{
			if(strstr($thiscalcArray, "selected")) // if the calculation was selected, then do the calculation on that column...
			{
				if(strstr($thiscalcArray, "sum"))
				{
					$sumtotal = 0;
					foreach(${$colarrayname[$v]} as $thisreqsval)
					{
						if(strstr($thisreqsval, "*=+*:")) // if it's a multi...
						{
							$thismultival = explode("*=+*:", $thisreqsval);
							foreach($thismultival as $thisindivval)
							{
								$sumtotal = $sumtotal + $thisindivval;
							}
						}
						else
						{
							$sumtotal = $sumtotal + $thisreqsval;
						}
					}						
					$calcoutput .= "<h4>" . _formulize_SUM . ":</h4>";
					$calcoutput .= "<ul><li>" . _formulize_SUM_TEXT . " $sumtotal</li></ul>";
					$sumtotal = "";
				}
				if(strstr($thiscalcArray, "average"))
				{
					$sumtotal = 0;
					$nonblankmultis = 0;
					$noblankscounter = 0;
					$blankscounter = 0;
					foreach(${$colarrayname[$v]} as $thisreqsval)
					{
						//print "<br>this row: $thisreqsval<br>";
						if(strstr($thisreqsval, "*=+*:")) // if it's a multi...
						{
							$thismultival = explode("*=+*:", $thisreqsval);
							foreach($thismultival as $thisindivval)
							{
								$sumtotal = $sumtotal + $thisindivval;
								$nonblankmultis++;
							}
						}
						else
						{
							$sumtotal = $sumtotal + $thisreqsval;
							if($thisreqsval)
							{
								$noblankscounter++;
							}
							else
							{
								$blankscounter++;
							}
						}
						/*print "nonblankmultis: $nonblankmultis<br>";
						print "noblankscounter: $noblankscounter<br>";
						print "blankscounter: $blankscounter<br>";*/
					}			
					if($nonblankmultis) // if it's a multi...
					{
						$average = round($sumtotal / ($blankscounter + $nonblankmultis + $noblankscounter), 2);
						$nbaverage = round($sumtotal / ($nonblankmultis + $noblankscounter), 2);
					}
					else
					{
						$average = round($sumtotal / count(${$colarrayname[$v]}), 2);
						$nbaverage = round($sumtotal / $noblankscounter, 2);
					}
					$calcoutput .= "<h4>" . _formulize_AVERAGE . ":</h4>";
					$calcoutput .= "<ul><li>" . _formulize_AVERAGE_INCLBLANKS . " $average</li>";
					$calcoutput .= "<li>" . _formulize_AVERAGE_EXCLBLANKS . " $nbaverage</li></ul>";
					$average = "";
					$nbaverage = "";
				}
				if(strstr($thiscalcArray, "min"))
				{
 					$start = "1";
					foreach(${$colarrayname[$v]} as $thisreqsval)
					{
//						print "<br>this row: $thisreqsval<br>";
						if(strstr($thisreqsval, "*=+*:")) // if it's a multi...
						{
							$thismultival = explode("*=+*:", $thisreqsval);
							foreach($thismultival as $thisindivval)
							{
//								print "<br>this indiv val: $thisindivval";
								if($start AND $thisindivval AND is_numeric($thisindivval))
								{
									$minval = $thisindivval;
									if($thisindivval)
									{
										$minvalnozero = $thisindivval;
									}
									$start = 0;
								}
								if($thisindivval < $minval AND is_numeric($thisindivval))
								{
									$minval = $thisindivval;
									if($thisindivval)
									{
										$minvalnozero = $thisindivval;
									}
								}
							}
						}
						else
						{
							//print "<br>this indiv val: $thisreqsval";

							if($start AND $thisreqsval AND is_numeric($thisreqsval))
							{
								$minval = $thisreqsval;
								if($thisreqsval)
								{
									$minvalnozero = $thisreqsval;
								}
								$start = 0;
							}
							if($thisreqsval < $minval AND is_numeric($thisreqsval))
							{
								$minval = $thisreqsval;
								if($thisreqsval)
								{
									$minvalnozero = $thisreqsval;
								}
							}
						}
					//print "minval: $minval<br>";
					//print "minvalnozero: $minvalnozero<br>";
					}
					$calcoutput .= "<h4>" . _formulize_MINIMUM . ":</h4>";
					$calcoutput .= "<ul><li>" . _formulize_MINIMUM_INCLBLANKS . " $minval</li>";
					$calcoutput .= "<li>" . _formulize_MINIMUM_EXCLBLANKS . " $minvalnozero</li></ul>";
					$minval = "";
					$minvalnozero = "";
				}

				if(strstr($thiscalcArray, "max"))
				{
 					$start = "1";
					foreach(${$colarrayname[$v]} as $thisreqsval)
					{
						if(strstr($thisreqsval, "*=+*:")) // if it's a multi...
						{
							$thismultival = explode("*=+*:", $thisreqsval);
							foreach($thismultival as $thisindivval)
							{
								if($start AND is_numeric($thisindivval))
								{
									$maxval = $thisindivval;
									$start = 0;
								}
								if($thisindivval > $maxval AND is_numeric($thisindivval))
								{
									$maxval = $thisindivval;
								}
							}
						}
						else
						{
							if($start AND is_numeric($thisreqsval))
							{
								$maxval = $thisreqsval;
								$start = 0;
							}
							if($thisreqsval > $maxval AND is_numeric($thisreqsval))
							{
								$maxval = $thisreqsval;
							}
						}
					}
					$calcoutput .= "<h4>" . _formulize_MAXIMUM . ":</h4>";
					$calcoutput .= "<ul><li>" . _formulize_MAXIMUM_TEXT . " $maxval</li></ul>";
					$maxval = "";
				}
				/*if(strstr($thiscalcArray, "count"))
				{
					$countvals = count(${$colarrayname[$v]});
					$multicount = 0;
					$noblankscounter = 0;
					foreach(${$colarrayname[$v]} as $thisreqsval)
					{
						if($thisreqsval)
						{
							$noblankscounter++;
						}
						if(strstr($thisreqsval, "*=+*:")) // if it's a multi...
						{
							$thismultival = explode("*=+*:", $thisreqsval);
							$extra = count($thismultival);
							$extra--;
							$multicount = $multicount + $extra;
						}
					}
					$countvals = $countvals + $multicount;
					$nonblanks = $noblankscounter+$multicount;
					$percentcount = round(($nonblanks/$countvals)*100, 2);
	

					$calcoutput .= "<h4>" . _formulize_COUNT . ":</h4>";
					$calcoutput .= "<ul><li>" . _formulize_COUNT_INCLBLANKS . " $countvals</li>";
					$calcoutput .= "<li>" . _formulize_COUNT_EXCLBLANKS . " $nonblanks</li>";
					$calcoutput .= "<li>" . _formulize_COUNT_PERCENTBLANKS . " $percentcount%</li></ul>";				
					$countvals = "";
					$nonblanks = "";
					$percentcount = "";
				}*/
				if(strstr($thiscalcArray, "percent") OR strstr($thiscalcArray, "count"))
				{
					array($valdist);
					array_splice($valdist, 0);
//					$valdist[""] = 0;
					foreach(${$colarrayname[$v]} as $thisreqsval)
					{
						if(strstr($thisreqsval, "*=+*:")) // if it's a multi...
						{
							$thismultival = explode("*=+*:", $thisreqsval);
							foreach($thismultival as $thisindivval)
							{
								$valdist[$thisindivval]++;
							}
						}
						else
						{
							$valdist[$thisreqsval]++;
						}
					}
					arsort($valdist);
					// ==== duplicated from COUNT above
					$countvals = count(${$colarrayname[$v]});
					$multicount = 0;
					$noblankscounter = 0;
					foreach(${$colarrayname[$v]} as $thisreqsval)
					{
						if($thisreqsval)
						{
							$noblankscounter++;
						}
						if(strstr($thisreqsval, "*=+*:")) // if it's a multi...
						{
							$thismultival = explode("*=+*:", $thisreqsval);
							$extra = count($thismultival);
							$extra--;
							$multicount = $multicount + $extra;
						}
					}
					$countvals = $countvals + $multicount;
					$nonblanks = $noblankscounter+$multicount;
					//===== end duplicated block

					if(strstr($thiscalcArray, "count")) // if we're counting and NOT percentaging...
					{

					$percentcount = round(($nonblanks/$countvals)*100, 2);

					$theuniquekeys = array_keys($valdist);
					$countuniquekeys = count($theuniquekeys);
					

					$calcoutput .= "<h4>" . _formulize_COUNT . ":</h4>";
					$calcoutput .= "<ul><li>" . _formulize_COUNT_UNIQUES . " $countuniquekeys</li>";		
					$calcoutput .= "<li>" . _formulize_COUNT_INCLBLANKS . " $countvals</li>";
					$calcoutput .= "<li>" . _formulize_COUNT_EXCLBLANKS . " $nonblanks</li>";
					$calcoutput .= "<li>" . _formulize_COUNT_PERCENTBLANKS . " $percentcount%</li></ul>";	
					


					}
					else // we're percentaging...
					{


					$calcoutput .= "<h4>" . _formulize_PERCENTAGES . "</h4>";
					$calcoutput .= "<table><tr><td><nobr>" . _formulize_PERCENTAGES_VALUE . "</nobr></td><td><nobr>" . _formulize_PERCENTAGES_COUNT . "</nobr></td><td><nobr>" . _formulize_PERCENTAGES_PERCENT . "</nobr></td><td><nobr>" . _formulize_PERCENTAGES_PERCENTEXCL . "</nobr></td></tr><tr><td><ul>";
					foreach(array_keys($valdist) as $uniquekeys)
					{
						$calcoutput .= "<nobr><li>$uniquekeys</li></nobr>";
					}
					$calcoutput .= "</ul></td><td><ul>";
					foreach($valdist as $uniqueval)
					{
						$calcoutput .= "<nobr><li>$uniqueval</li></nobr>";
					}
					$calcoutput .= "</ul></td><td><ul>";
					foreach($valdist as $uniqueval)
					{
						$percent = round(($uniqueval/$countvals)*100,2);
						$calcoutput .= "<nobr><li>$percent%</li></nobr>";
					}
					$calcoutput .= "</ul></td><td><ul>";
					$blankfinder = 0;
					foreach($valdist as $uniqueval)
					{
						$thiskey = array_keys($valdist, $uniqueval);
						if($thiskey[0])
						{
							$percent = round(($uniqueval/$nonblanks)*100,2);
							$calcoutput .= "<nobr><li>$percent%</li></nobr>";
						}
						else
						{
							$calcoutput .= "<li></li>";
						}
					}
					$calcoutput .= "</ul></td></tr></table>";
					} // end else that covers creating count or percent calcoutputs
					$countvals = "";
					$nonblanks = "";
					$percentcount = "";
				} // end percent (5th nesting)
			} // end of foreach calculation requested for this column
			if($calcoutput)
			{
				$totalcalcoutput[$v] = $calcoutput; // keys will not be sequential but a foreach handles it in the template so that's okay.
			}
		}
	}
	$xoopsTpl->assign('tempcalcresults', $totalcalcoutput);
}

	// text for the template...
	// add the final result array to the stack for the template...
	if($atleastonereq)
	{
		//print_r($finalselectidreq);
		$xoopsTpl->assign('tempidsjwe', $finalselectidreq);
		$xoopsTpl->assign('rows', "true");
	}
	else
	{
		$xoopsTpl->assign('noentries', _formulize_TEMP_NOENTRIES);
	}

	array (entereduids);
	// get uids and names from the form_form table...
	foreach($finalselectidreq as $finalreqs)
	{
		$queryfornames = "SELECT uid, date, proxyid FROM " . $xoopsDB->prefix("form_form") . " WHERE id_req=$finalreqs ORDER BY id_req";
		$resqfornames = mysql_query($queryfornames);
		$rowqfornames = mysql_fetch_row($resqfornames);
		$entereduids[] = $rowqfornames[0];
		$entereddates[] = $rowqfornames[1];
		// set proxy flags
		if($rowqfornames[2]) // if there is a proxy entry
		{
			//print "proxy!<br>";
			$proxystatus[] = _formulize_PROXYFLAG;
		}
		else
		{
			$proxystatus[] = "";
		}
		// set can delete flags
		if($isadmin OR $uid == $rowqfornames[0])
		{
			$tempcandel[] = "1";
		}
		else
		{
			$tempcandel[] = "";
		}
	}
	
	//print_r($proxystatus);
	
	if($gscopeparam) // if it's groupscope then pass the usernames to template
	{
foreach($entereduids as $entrduids)
	{
		$queryforrealnames = "SELECT name FROM " . $xoopsDB->prefix("users") . " WHERE uid=$entrduids";
		$resqforrealnames = mysql_query($queryforrealnames);
		$rowqforrealnames = mysql_fetch_row($resqforrealnames);
		$realusernames[] = $rowqforrealnames[0];
	}
	$xoopsTpl->assign('tempenteredbyname', $realusernames);
	$xoopsTpl->assign('selectentriestitle', _formulize_TEMP_SELENTTITLE_GS);
	$xoopsTpl->assign('tempenteredby', _formulize_TEMP_ENTEREDBY);
	}// end if $hasgroupscope
	else
	{
		$xoopsTpl->assign('tempenteredby', _formulize_TEMP_ENTEREDBYSINGLE);
		$xoopsTpl->assign('selectentriestitle', _formulize_TEMP_SELENTTITLE);
	}

	if($report OR $reportingyn)
	{
		$xoopsTpl->assign('selectentriestitle', _formulize_TEMP_SELENTTITLE_RP);
		$xoopsTpl->assign('selectentriestitle2', _formulize_TEMP_SELENTTITLE2_RP);
		$xoopsTpl->assign('showscope', $showscope);
		$xoopsTpl->assign('selectscopebutton', _formulize_SELECTSCOPEBUTTON);
		$xoopsTpl->assign('report_nove', $report_nove);
		$xoopsTpl->assign('report_calconly', $report_calconly);
	}

	if($reportingyn)
	{
		$xoopsTpl->assign('reportingyn', $reportingyn);
		$xoopsTpl->assign('reportsaving', _formulize_REPORTSAVING);
		$xoopsTpl->assign('publishingoptions', _formulize_PUBLISHINGOPTIONS);
		$xoopsTpl->assign('publishnove', _formulize_PUBLISHNOVE);
		$xoopsTpl->assign('publishcalconly', _formulize_PUBLISHCALCONLY);

		// check for alternate colors and send them

		$altcq = "SELECT even, odd FROM " . $xoopsDB->prefix("form_id") . " WHERE id_form=$id_form";
		$resaltcq = $xoopsDB->query($altcq);
		$rowaltcq = $xoopsDB->fetchRow($resaltcq);
		if($rowaltcq[0] AND $rowaltcq[0] != "default") // if the first alternate is set, send it to template
		{
			$xoopsTpl->assign('evenalt', $rowaltcq[0]);
		}
		if($rowaltcq[1] AND $rowaltcq[1] != "default") // if the second alternate is set, send it to template
		{
			$xoopsTpl->assign('oddalt', $rowaltcq[1]);
		}

		// generate a group list and send it.
	
		array($compgtosend);
		$compgidsq = "SELECT groupid, name FROM " . $xoopsDB->prefix("groups");
		$rescompgids = mysql_query($compgidsq);
		while ($rowcompgids = mysql_fetch_row($rescompgids))
		{
			$compgtosend[] = $rowcompgids[0] . ">" . $rowcompgids[1];		
		}

		$xoopsTpl->assign('groupnames', $compgtosend);
		$xoopsTpl->assign('andortitle', _formulize_ANDORTITLE);	


	}


	$xoopsTpl->assign('reportname', $report);
	$xoopsTpl->assign('tempenteredproxy', $proxystatus);
	$xoopsTpl->assign('tempcandel', $tempcandel);
	$xoopsTpl->assign('tempentereddates', $entereddates);
	$xoopsTpl->assign('tempcaptionsjwe', $reqFieldsJwe);
	$xoopsTpl->assign('tempvaluesjwe', $selvals);
	$tempformurl = XOOPS_URL . "/modules/formulize/index.php?title=$title";
	$xoopsTpl->assign('tempformurl', $tempformurl);
	$xoopsTpl->assign('tempaddentry', _formulize_TEMP_ADDENTRY);
	$xoopsTpl->assign('tempviewingentries', _formulize_TEMP_VIEWINGENTRIES);
	$xoopsTpl->assign('viewthisentry', _formulize_TEMP_VIEWTHISENTRY);
	$xoopsTpl->assign('tempformtitle', $title);
	$xoopsTpl->assign('tempon', _formulize_TEMP_ON);
	$xoopsTpl->assign('tempturnoffreporting', _formulize_REPORT_OFF);
	$xoopsTpl->assign('tempturnonreporting', _formulize_REPORT_ON);
	$xoopsTpl->assign('reportingoptions', _formulize_REPORTING_OPTION);
	$xoopsTpl->assign('submittext', _formulize_SUBMITTEXT);
	$xoopsTpl->assign('resetbutton', _formulize_RESETBUTTON);
	$xoopsTpl->assign('tempviewreport', _formulize_VIEWAVAILREPORTS);
	$xoopsTpl->assign('tempnoreports', _formulize_NOREPORTSAVAIL);
	$xoopsTpl->assign('tempchoosereport', _formulize_CHOOSEREPORT);
	$xoopsTpl->assign('querycontrols', _formulize_QUERYCONTROLS);
	$xoopsTpl->assign('searchterms', _formulize_SEARCH_TERMS);
	$xoopsTpl->assign('and', _formulize_AND);
	$xoopsTpl->assign('not', _formulize_NOT);
	$xoopsTpl->assign('like', _formulize_LIKE);
	$xoopsTpl->assign('notlike', _formulize_NOTLIKE);
	$xoopsTpl->assign('or', _formulize_OR);
	$xoopsTpl->assign('searchoperator', _formulize_SEARCH_OPERATOR);
	$xoopsTpl->assign('sterms', _formulize_STERMS);
	$xoopsTpl->assign('calculations', _formulize_CALCULATIONS);
	$xoopsTpl->assign('sum', _formulize_SUM);
	$xoopsTpl->assign('average', _formulize_AVERAGE);
	$xoopsTpl->assign('minimum', _formulize_MINIMUM);
	$xoopsTpl->assign('maximum', _formulize_MAXIMUM);
	$xoopsTpl->assign('count', _formulize_COUNT);
	$xoopsTpl->assign('percentages', _formulize_PERCENTAGES);
	$xoopsTpl->assign('sortingorder', _formulize_SORTING_ORDER);
	$xoopsTpl->assign('sortpriority', _formulize_SORT_PRIORITY);
	$xoopsTpl->assign('none', _formulize_NONE);
	$xoopsTpl->assign('changecolumns', _formulize_CHANGE_COLUMNS);
	$xoopsTpl->assign('change', _formulize_CHANGE);
	$xoopsTpl->assign('searchhelp', _formulize_SEARCH_HELP);
	$xoopsTpl->assign('sorthelp', _formulize_SORT_HELP);
	$xoopsTpl->assign('goreport', _formulize_GOREPORT);

	$xoopsTpl->assign('isadmin', $isadmin);
	$xoopsTpl->assign('theycanadd', $theycanadd);
	$xoopsTpl->assign('savereportbutton', _formulize_SAVEREPORTBUTTON);
	$xoopsTpl->assign('reportnametouse', _formulize_REPORTNAME);
	$xoopsTpl->assign('publishreport', _formulize_PUBLISHREPORT);
	$xoopsTpl->assign('lockscope', _formulize_LOCKSCOPE);
	$xoopsTpl->assign('reportpubgroups', _formulize_REPORTPUBGROUPS);
	
	$xoopsTpl->assign('reportexporting', _formulize_REPORTEXPORTING);
	$xoopsTpl->assign('exportreportbutton', _formulize_EXPORTREPORTBUTTON);
	$xoopsTpl->assign('exportexplanation', _formulize_EXPORTEXPLANATION);
	$xoopsTpl->assign('filedeltitle', _formulize_FILEDELTITLE);
	$xoopsTpl->assign('fdcomma', _formulize_FDCOMMA);
	$xoopsTpl->assign('fdtab', _formulize_FDTAB);
	$xoopsTpl->assign('fdcustom', _formulize_FDCUSTOM);

	$xoopsTpl->assign('delbutton', _formulize_DELBUTTON);
	$xoopsTpl->assign('delconf', _formulize_DELCONF);
	
	// make an array the is 0 on all indexes except on numbers equal to rows where a header needs to be redrawn, when the array is 1.
	$redrawon = 7; //the number of rows after which to redraw the headerrow	
	$countselectedrows = count($finalselectidreq);
	for($i=0;$i<$countselectedrows;$i++)
	{
		if($i>0 && $i % $redrawon == 0)
		{
			$redrawhead[$i] = 1;
		}
	}
	$xoopsTpl->assign('redrawhead', $redrawhead);

if(isset($_POST['export'])) // write a file to the server and display a download link for it
{
	$fdchoice = $_POST['filedelimiter'];
	if($fdchoice == "comma") 
	{ 
		$fd = ",";
		$fxt = ".csv";
	}
	if($fdchoice == "tab")
	{
		$fd = "\t";
		$fxt = ".tabDelimited";
	}
	if($fdchoice == "custom")
	{
		$fd = $_POST['cusdel'];
		if(!$fd) { $fd = "*"; }
		$fxt = ".customDelimited";
	}
	$csvfile = "";
	$headercount = 0;
	foreach($reqFieldsJwe as $csvheader)
	{
		if(!$headercount)
		{
			$csvfile = $csvheader;
		}
		else
		{
			$csvfile .= $fd . $csvheader;
		}
		$headercount++;
	}

	$csvfile .= "\r\n";

	$colcounter = 0;
	foreach($selvals as $acell)
	{
		$acell = str_replace("*=+*:", " ++ ", $acell); // replace the custom delimiter with ++
		if(!$colcounter)
		{
			$csvfile .= $acell;
		}
		else
		{
			$csvfile .= $fd . $acell;
		}
		$colcounter++;
		if($colcounter == $headercount)
		{
			$colcounter = 0; 
			$csvfile .= "\r\n";
		}
	}
	$tempfold = time();
	$exfilename = _formulize_exfile . $tempfold . $fxt;
	// open the output file for writing
	$wpath = XOOPS_ROOT_PATH."/modules/formulize/export/$exfilename";
	//print $wpath;
	$exportfile = fopen($wpath, "w");
	fwrite ($exportfile, $csvfile);
	fclose ($exportfile);
	
	// need to add in logic to cull old files...

	$dlpath = XOOPS_URL . "/modules/formulize/export/$exfilename";
	$xoopsTpl->assign('dlpath', $dlpath);
	$xoopsTpl->assign('downloadtext', _formulize_DLTEXT);
	$xoopsTpl->assign('downloadtext', _formulize_DLTEXT);
	$xoopsTpl->assign('dlheader', _formulize_DLHEADER);
	$xoopsTpl->assign('exfilename', $exfilename);
}

require(XOOPS_ROOT_PATH."/footer.php");




} // end if that controls display of select-an-entry page -- jwe 7/24/04
else // we're drawing the form, not select entry page...
{


if($issingle) // if it's a single entry form...
{
	if(!$hasgroupscope AND !$viewentry)
	{
		$issquery = "SELECT id_req FROM " . $xoopsDB->prefix("form_form") . " WHERE id_form=$id_form AND uid=$uid ORDER BY id_req";
		$issquery2 = mysql_query($issquery);
//		print "$issquery";
		$firstrowissquery = mysql_fetch_row($issquery2);
		/*print "**";
		print_r($firstrowissquery);
		print "**<br>";*/
		$viewentry = $firstrowissquery[0];
//		print "*$viewentry*<br>";


	}
	elseif(!$viewentry) // as long as no view entry has been specified (and the validity of the view entry has already been checked above) then if it's a group scope form, then we display the first entry in the DB that belongs to a group the user is a member of
	{

		//*******code duplicated from above in the if-we're-showing-the-select-page -- used to generate the groupscope:

	$gidindexer = 0;
	array ($groupid2);
	foreach($groupidadd as $gid)
	{
		// if the group is one the user is a member of...
		if( in_array($gid, $groupuser) )
		{
			$groupid2[$gidindexer] = $gid;
			$gidindexer++;
		}
	}
//	print "Add-permitted groups the user is a member of: ";
//	print_r($groupid2)
//	print "<br>";

	array($masteruserlist);
	foreach($groupid2 as $gid)
	{
		$userlistres = $xoopsDB->query("SELECT uid FROM ".$xoopsDB->prefix("groups_users_link")." WHERE groupid=".$gid);
		while ( $ulistrow = mysql_fetch_row ( $userlistres ) ) {
	  		$masteruserlist[] = $ulistrow[0];
  		}
	}

	$masteruserlist = array_unique($masteruserlist);
	$beenthroughonce = 0;
	foreach($masteruserlist as $oneusersid)
	{
		if(!$beenthroughonce)
		{
			$gscopeparam = "uid=" . $oneusersid;
			$beenthroughonce = 1;
		}
		else
		{
			$gscopeparam = $gscopeparam . " OR uid=" . $oneusersid;
		}
	}

//	print_r($masteruserlist);
//	print "<br>$gscopeparam"; //debug lines
//====================== end of duplicated lines

		$gsquery = "SELECT id_req FROM " . $xoopsDB->prefix("form_form") . " WHERE id_form=$id_form AND ($gscopeparam) ORDER BY id_req";
		$gsquery2 = mysql_query($gsquery);
		//print "$gsquery";
		$firstrowgsquery = mysql_fetch_row($gsquery2);
		/*print "**";
		print_r($firstrowgsquery);
		print "**<br>";*/
		$viewentry = $firstrowgsquery[0];
		//print "*$viewentry*<br>";
	}
}

// gather the default values if needed
//print "**viewentry check: $viewentry";
if($viewentry)
{

//replicate the veuid generation from above:
// print "Viewentry: $viewentry<br>";
// get the uid that belongs to the entry
$getveuid = "SELECT uid FROM " . $xoopsDB->prefix("form_form") . " WHERE id_req=$viewentry GROUP BY id_req";
$resgetveuid = $xoopsDB->query($getveuid);
$rowgetveuid = $xoopsDB->fetchRow($resgetveuid);
$veuid = $rowgetveuid[0]; // the uid that belongs to the entry
//print "VEUID is set: $veuid<br>";


$viewqueryjwe = "SELECT ele_caption, ele_value FROM " . $xoopsDB->prefix("form_form") . " WHERE id_req=$viewentry";
// print $viewqueryjwe; // debug line
$resultViewQueryJwe = mysql_query($viewqueryjwe);

array ($reqCaptionsJwe);
array ($reqValuesJwe);

while ($rowjwe = mysql_fetch_assoc($resultViewQueryJwe))
{
	//print_r($rowjwe);
	$reqCaptionsJwe[] = $rowjwe["ele_caption"];
	$reqValuesJwe[] = $rowjwe["ele_value"];
}

} // end of gathering the default values
// end of the ELSE is way at the bottom of the page, to encompass the other conditions.



// ---------------------------- end jwe mod


$result_form = $xoopsDB->query("SELECT margintop, marginbottom, itemurl, status FROM ".$xoopsDB->prefix("form_menu")." WHERE menuid='".$id_form);
       
$res_mod = $xoopsDB->query("SELECT mid FROM ".$xoopsDB->prefix("modules")." WHERE dirname='formulize'");
if ($res_mod) {
	while ($row = mysql_fetch_row($res_mod))
		$module_id = $row[0];
}


//if loop below modified to if not xoopsuser rather than if xoopsuser, to avoid resetting the uid which is set above now - jwe 7/23/04
//commented by jwe 8/28/04 since it is a duplicate of the groupuser query at the top.
/*$perm_name = 'Permission des catégories';
if (!$xoopsUser) { $groupuser[0] = 3; }
$res_gp = $xoopsDB->query("SELECT groupid FROM ".$xoopsDB->prefix("groups_users_link")." WHERE uid= ".$uid);
if ( $res_gp ) {
  while ( $row = mysql_fetch_row ( $res_gp ) ) {
	$groupuser[] = $row[0];
  }
}*/

$gperm_handler =& xoops_gethandler('groupperm');

if ($result_form) {
	while ($row = mysql_fetch_row ($result_form)) {
		$margintop = $row[0];
		$marginbottom = $row[1];
		$itemurl = $row[2];
		$status = $row[3];
	}
}
else $status = 0;

if ( $status == 1 ) {
	$groupid = array();
        $res2 = $xoopsDB->query("SELECT gperm_groupid,gperm_itemid FROM ".$xoopsDB->prefix("group_permission")." WHERE gperm_itemid= ".$menuid." AND gperm_modid=".$module_id);
	if ( $res2 ) {
	  while ( $row = mysql_fetch_row ( $res2 ) ) {
		$groupid[] = $row[0];
	  }
	}

	$block['content'] .= "<ul>";
        $display = 0;
	$perm_itemid = $menuid; //intval($_GET['category_id']);
        foreach ($groupid as $gr){
               	if ( in_array ($gr, $groupuser) && $display != 1) {
               		$block['content'] .= "<table cellspacing='0' border='0'><tr><td><li><div style='margin-left: $indent px; margin-right: 0; margin-top: $margintop px; margin-bottom: $marginbottom px;'>
               		<a style='font-weight: normal' href='$itemurl'>$title</a></li></td></tr></table>";
               		$display = 1;
               	}
               	else redirect_header(XOOPS_URL."/modules/formulize/index.php", 1, "pas la permission !!!");
        }
        $block['content'] .= "</ul>";
}



// following line modified to remove the name of the module from before the form's own name - jwe 07/23/04
$form2 = "<center><h3>$title</h3></center>";
     	//include_once(XOOPS_ROOT_PATH . "/class/uploader.php");
include_once(XOOPS_ROOT_PATH . "/modules/formulize/upload_FA.php");


if( empty($_POST['submit']) ){
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

		// block below moved to after the call to the constructElement function
		/*if (isset ($ele_value[0])) {
			$ele_value[0] = eregi_replace("'", "`", $ele_value[0]);
			$ele_value[0] = stripslashes($ele_value[0]); } */

		$renderer =& new formulizeElementRenderer($i);
		$form_ele =& $renderer->constructElement('ele_'.$i->getVar('ele_id'), $ele_value);

		// new location of block above.
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
				$uqueryforrealnames = "SELECT name FROM " . $xoopsDB->prefix("users") . " WHERE uid=$rowproxyulistq[0]";
				$uresqforrealnames = $xoopsDB->query($uqueryforrealnames);
				$urowqforrealnames = $xoopsDB->fetchRow($uresqforrealnames);
				//print "username: $urowqforrealnames[0]<br>";
				$proxylist->addOption($rowproxyulistq[0], $urowqforrealnames[0]);
			}
			
			$submittray->addElement($proxylist);
			$form->addElement($submittray);

		}
		elseif($uid == $veuid OR $isadmin OR !$viewentry) // only put in add button for their own entries, or all entries if they're an admin, or new entries in a multiple.
		{
			$form->addElement(new XoopsFormButton('', 'submit', _SUBMIT, 'submit'));
		}
	}
	


	//other template terms added by jwe 7/24/04
	$tempformurl = XOOPS_URL . "/modules/formulize/index.php?title=$title";
	$xoopsTpl->assign('tempformurl', $tempformurl);


	

	$xoopsTpl->assign('tempviewentries', _formulize_TEMP_VIEWENTRIES);

	$xoopsTpl->assign('theycanadd', $theycanadd);


	$form->assign($xoopsTpl);

	//added by jwe 10/10/04 -- send id_form to template for use in the notifications block which is hard coded in (since the id_form cannot be accessed by the notifications system in the normal way on account of the title and not the id_form being used in the URL
	//send title too so the notification redirect is correct
	$xoopsTpl->assign('id_form', $id_form);
	$xoopsTpl->assign('title', $title);

	// do our own checking for subscribed events, for the same reason...
	$notification_handler =& xoops_gethandler('notification');
	$subscribed_events =& $notification_handler->getSubscribedEvents("form", $id_form, $xoopsModule->getVar('mid'), $xoopsUser->getVar('uid'));
	$subscribedNew = in_array("new_entry", $subscribed_events) ? 1 : 0;
	$subscribedUp = in_array("update_entry", $subscribed_events) ? 1 : 0;
	$subscribedDel = in_array("delete_entry", $subscribed_events) ? 1 : 0;
	$xoopsTpl->assign('subNew', $subscribedNew);
	$xoopsTpl->assign('subUp', $subscribedUp);
	$xoopsTpl->assign('subDel', $subscribedDel);

	include_once XOOPS_ROOT_PATH.'/footer.php';
}else{

// ********
// PROCESSING OF DATA THAT HAS BEEN SUBMITTED....
// ********

	$myts =& MyTextSanitizer::getInstance();
	$msg = '';
	$i=0;
	unset($_POST['submit']);
	foreach( $_POST as $k => $v ){
		if( preg_match('/ele_/', $k)){
			$n = explode("_", $k);
			$ele[$n[1]] = $v;
			$id[$n[1]] = $n[1];
		}
		if($k == 'xoops_upload_file'){
			$tmp = $k;
			$k = $v[0];			
			$v = $tmp;
			$n = explode("_", $k);
			$ele[$n[1]] = $v;
			$id[$n[1]] = $n[1];
		}
	}
	
	$sql = $xoopsDB->query("SELECT id_req from " . $xoopsDB->prefix("form_form")." order by id_req DESC");
	list($id_req) = $xoopsDB->fetchRow($sql);
	if ($id_req == 0) { $num_id = 1; }
	else if ($num_id <= $id_req) $num_id = $id_req + 1;


	$up = array();
	$desc_form = array();
	$value = null;
	foreach( $id as $i ){
		$element =& $formulize_mgr->get($i);
		if( !empty($ele[$i]) ){
			//$pds = $element->getVar('pds');
			$id_form = $element->getVar('id_form');
			$ele_id = $element->getVar('ele_id');
			$ele_type = $element->getVar('ele_type');
			$ele_value = $element->getVar('ele_value');
			$ele_caption = $element->getVar('ele_caption');
			$ele_caption = stripslashes($ele_caption);
			$ele_caption = eregi_replace ("&#039;", "`", $ele_caption);
			$ele_caption = eregi_replace ("&quot;", "`", $ele_caption);
			$sql = $xoopsDB->query("SELECT desc_form from ".$xoopsDB->prefix("form_id")." WHERE id_form= ".$id_form.'');
			while ($row = mysql_fetch_array ($sql)) 
			{	$desc_form[] = $row['desc_form']; }
			
			switch($ele_type){
				case 'text':
			$msg.= "<table border=1 bordercolordark=black bordercolorlight=#C0C0C0 width=500><td><b>".$ele_caption."</b><br>";
					$msg.= $myts->stripSlashesGPC($ele[$i])."<br></td></table><br>";
					$value = $ele[$i]; // trim added by jwe 9/01/04 -- removed 10/07/04
				break;
				case 'textarea':
			$msg.= "<table border=1 bordercolordark=black bordercolorlight=#C0C0C0 width=500><td><b>".$ele_caption."</b><br>";
					$msg.= $myts->stripSlashesGPC($ele[$i])."<br></td></table><br>";
					$value = $ele[$i]; // trim added by jwe 9/01/04 -- removed 10/07/04

				break;
				case 'areamodif':
			$msg.= "<table border=1 bordercolordark=black bordercolorlight=#C0C0C0 width=500><td><b>".$ele_caption."</b><br>";
					$msg.= $myts->stripSlashesGPC($ele[$i])."<br></td></table><br>";
					$value = $myts->stripSlashesGPC($ele[$i]);
				break;
				case 'radio':
			$msg.= "<table border=1 bordercolordark=black bordercolorlight=#C0C0C0 width=500><td><b>".$ele_caption."</b><br>";
					$value = '';
					$opt_count = 1;
					while( $v = each($ele_value) ){
						if( $opt_count == $ele[$i] ){
							$msg.= $myts->stripSlashesGPC($v['key']).'<br>';
							$value = $v['key'];
						}
						$opt_count++;
					}
					$msg.= $myts->stripSlashesGPC("</td></table><br>");
				break;
				case 'yn':
			$msg.= "<table border=1 bordercolordark=black bordercolorlight=#C0C0C0 width=500><td><b>".$ele_caption."</b><br>";
					$v = ($ele[$i]==2) ? _NO : _YES;
					$msg.= $myts->stripSlashesGPC($v)."<br></td></table><br>";
					$value = $ele[$i];
				break;
				case 'checkbox':
			$msg.= "<table border=1 bordercolordark=black bordercolorlight=#C0C0C0 width=500><td><b>".$ele_caption."</b><br>";
					$value = '';
					$opt_count = 1;
					while( $v = each($ele_value) ){
						if( is_array($ele[$i]) ){
							if( in_array($opt_count, $ele[$i]) ){
								$msg.= $myts->stripSlashesGPC($v['key']).'<br>';
								$value = $value.'*=+*:'.$v['key'];
							}
							$opt_count++;
						}else{
							if( !empty($ele[$i]) ){
								$msg.= $myts->stripSlashesGPC($v['key']).'<br>';
								$value = $value.'*=+*:'.$v['key'];
							}
						}						
					}
					$msg.= $myts->stripSlashesGPC("</td></table><br>");
				break;
				case 'select':
					// section to handle linked select boxes differently from others...
					$formlinktrue = 0;
					if(is_array($ele[$i]))  // look for the formlink delimiter
					{
						foreach($ele[$i] as $justacheck)
						{
							if(strstr($justacheck, "#*=:*"))
							{
								$formlinktrue = 1;
								break;
							}
						}
					}
					else
					{
						if(strstr($ele[$i], "#*=:*"))
						{
							$formlinktrue = 1;
						}
					}
					if($formlinktrue) // if we've got a formlink, then handle it here...
					{
						if(is_array($ele[$i]))
						{
							//print_r($ele[$i]);
							array($compparts);
							$compinit = 0;
							$selinit = 0;
							foreach($ele[$i] as $whatwasselected)
							{
							//	print "<br>$whatwasselected<br>";
								$compparts = explode("#*=:*", $whatwasselected);
							//	print_r($compparts);
								if($compinit == 0)
								{
									$value = $compparts[0] . "#*=:*" . $compparts[1] . "#*=:*";
									$compinit = 1;
								}
								if($selinit == 1)
								{
									$value = $value . "[=*9*:";
								}
								$value = $value . $compparts[2];
								$selinit = 1;
							}
						}
						else
						{
							$value = $ele[$i];
						}	
//						print "<br>VALUE: $value";	
						break;			
					}
					else
					{


			$msg.= "<table border=1 bordercolordark=black bordercolorlight=#C0C0C0 width=500><td><b>".$ele_caption."</b><br>";
					$value = '';

// **********
// LARGE SECTION OF CODE COMMENTED AND REPLACED.
//					// changed $opt_count to equal 0 to fix a bug in recording the value of list boxes -- jwe 7/24/04
//					$opt_count = 0;
//					$writevaluejwe = 0;
//					print "what the form sends back:<br>";
//					print_r($ele_value[2]);
//					print "<br><br>The 'i': $i<br><br>"; // debug block
//					
//					//while( $v = each($ele_value[2]) ){
//						if( is_array($ele[$i]) ){
//							print "the key array for multiples:<br>";
//							print_r($ele[$i]);
//							print "<br><br>"; // debug block
//							if( in_array($opt_count, $ele[$i]) ){
//								$msg.= $myts->stripSlashesGPC($v['key']).'<br>';
//								$value = $value . "*=+*:" . $v['key'];
//							}
//							$opt_count++; 
//						//}else{
// ============== END OF FIRST LARGE COMMENTED SECTION



							// The following code block is a replacement for the commented blocks above and below.  -- jwe 7/26/04
							// print_r($ele_value[2]);
							$entriesPassedBack = array_keys($ele_value[2]);
							$keysPassedBack = array_keys($entriesPassedBack);
							$entrycounterjwe = 0;
							foreach($keysPassedBack as $masterentlistjwe)
							{
	      						if(is_array($ele[$i]))

								{
									foreach($ele[$i] as $whattheuserselected)
									{
										// if the user selected an entry found in the master list of all possible entries...
										//print "internal loop $entrycounterjwe<br>userselected: $whattheuserselected<br>selectbox contained: $masterentlistjwe<br><br>";	
										if($whattheuserselected == $masterentlistjwe)
										{
											//print "WE HAVE A MATCH!<BR>";
											$value = $value . "*=+*:" . $entriesPassedBack[$entrycounterjwe];
											$msg.= $myts->stripSlashesGPC($value).'<br>';
											//print "$value<br><br>";
										}
									}
									$entrycounterjwe++;
								}
								else
								{
									//print "internal loop $entrycounterjwe<br>userselected: $ele[$i]<br>selectbox contained: $masterentlistjwe<br><br>";	
									if($ele[$i] == ($masterentlistjwe+1)) // plus 1 because single entry select boxes start their option lists at 1.
									{
										//print "WE HAVE A MATCH!<BR>";
										$value = $entriesPassedBack[$entrycounterjwe];
										$msg.= $myts->stripSlashesGPC($value).'<br>';
										//print "$value<br><br>";
										break;
									}
									$entrycounterjwe++;
								}
							}

// ******COMMENTED CODE BLOCK FOLLOWS...*******
							// following block commented out due to major problems with a bug affecting single selection boxes.  Perhaps it's PHP related.  The each function was not returning the first register, but instead started at the second.  Tried to reset it.  Didn't help.
							/*print "the key array:<br>";
							print_r($ele[$i]);
							print "<br><br>ele_value[2]";
							print_r($ele_value[2]);
							print"<br>"; // debug block
							// resetalready is a flag used to indicate that we need to reset the pointer in the ele_value array so the first entry isn't missed.  This bug was causing single-entry select boxes to advance forward by one instead of saving the entry the user actually selected -- jwe 7/26/04
							// This looks like a PHP bug to me!
							$resetalready = 0;
							reset($ele_value[2]);
							while( $j = each($ele_value[2]) ){
								if($resetalready) // this construction was causing the going backwards problem.
								{
									reset($ele_value[2]);
									$resetalready = 1;
									// continue; // restarts loop and skips the rest of the lines
								}
								print_r($j);
								//print"<br>";
								if( $opt_count == $ele[$i] ){
									$msg.= $myts->stripSlashesGPC($j['key']).'<br>';
									$value = $j['key'];
									//$value = $j[$opt_count];
								}
								$opt_count++;
							}*/
							//================== end commented block two
						//}
					//}
					// print "selects: $value<br>";
					$msg.= $myts->stripSlashesGPC("</td></table><br>");
				break;
				//Marie le 20/04/04
					} // end of if that checks for a linked select box.
				case 'areamodif':
			$msg.= "<table border=1 bordercolordark=black bordercolorlight=#C0C0C0 width=500><td><b>".$ele_caption."</b><br>";
					$msg.= $myts->stripSlashesGPC($ele[$i])."<br></td></table><br>";
					$value = $ele[$i];
				break;
				case 'date':
			$msg.= "<table border=1 bordercolordark=black bordercolorlight=#C0C0C0 width=500><td><b>".$ele_caption."</b><br>";
					$msg.= $myts->stripSlashesGPC($ele[$i])."<br></td></table><br>";
					$value = ''.$ele[$i];
				break;
				case 'sep':
			/*if ($ele_caption != '{SEPAR}') {
				$msg.= "<table border=1 bordercolordark=black bordercolorlight=#C0C0C0 width=500><td><b>".$ele_caption."</b><br>";
				$msg.= $myts->stripSlashesGPC($ele[$i])."<br></td></table><br>"; }
			else {
				$msg.= "<table border=1 bordercolordark=black bordercolorlight=#C0C0C0 width=500><td><b>";
				$msg.= $myts->stripSlashesGPC($ele[$i])."</b></td></table><br>"; }*/
					$value = $myts->stripSlashesGPC($ele[$i]);
				break;
				case 'upload':
			$msg.= "<table border=1 bordercolordark=black bordercolorlight=#C0C0C0 width=500><td><b>".$ele_caption."</b><br>";
							/************* UPLOAD *************/
				$img_dir = XOOPS_ROOT_PATH . "/modules/formulize/upload" ;
				$allowed_mimetypes = array();
				foreach ($ele_value[2] as $v){ $allowed_mimetypes[] = 'image/'.$v[1];
				}
				// types proposés : pdf, doc, txt, gif, mpeg, jpeg
				$max_imgsize = $ele_value[1];
				$max_imgwidth = 12000;
				$max_imgheight = 12000;
				
				$fichier = $_POST["xoops_upload_file"][0] ; 
// teste si le champ a été rempli :
			if( !empty( $fichier ) || $fichier != "") {
// test si aucun fichier n'a été joint
				if($_FILES[$fichier]['error'] == '2' || $_FILES[$fichier]['error'] == '1') {	
					$error = sprintf(_formulize_MSG_BIG, $xoopsConfig['sitename'])._formulize_MSG_THANK;
					redirect_header(XOOPS_URL."/modules/formulize/index.php?title=".$desc_form[0], 3, $error);
				}
				if(filesize($_FILES[$fichier]['tmp_name']) ==null) {	
					$value = $path = '';
					$filename = '';
					$msg.= $filename.'</TD></table><br>';
					break;
				}
				if($_FILES[$fichier]['size'] > $max_imgsize) {	
					$error = sprintf(_formulize_MSG_UNSENT.$max_imgsize.' octets', $xoopsConfig['sitename'])._formulize_MSG_THANK;
					redirect_header(XOOPS_URL."/modules/formulize/index.php?title=".$desc_form[0], 3, $error);
				}
// teste si le fichier a été uploadé dans le répertoire temporaire:
				if( ! is_readable( $_FILES[$fichier]['tmp_name'])  || $_FILES[$fichier]['tmp_name'] == "" ) 
				{
				//redirect_header( XOOPS_URL.'/modules/formulize/index.php?title='.$title , 2, _MD_FILEERROR ) ; 
					$path = '';
					$filename = '';
					$error = sprintf(_formulize_MSG_UNSENT.$max_imgsize.' octets', $xoopsConfig['sitename'])._formulize_MSG_THANK;
					redirect_header(XOOPS_URL."/modules/formulize/index.php?title=".$desc_form[0], 3, $error);
				//	exit ;				
				}
// création de l'objet uploader
				$uploader = new XoopsMediaUploader_FA($img_dir, $allowed_mimetypes, $max_imgsize, $max_imgwidth, $max_imgheight);
// fichier uploadé conforme en dimension et taille, bien copié du répertoire temporaire au répertoire indiqué ??
				if( $uploader->fetchMedia( $fichier ) && $uploader->upload() ) { 
					$pos = strrpos($uploader->getSavedFileName(), '.');
					$type = 'image/'.substr($uploader->getSavedFileName(), $pos+1);
					if (!in_array ($type, $allowed_mimetypes)) {	//si ce type est autorisé
						$path = '';
						$filename = '';
						$error = sprintf(_formulize_MSG_UNTYPE.implode(', ',$allowed_mimetypes))._formulize_MSG_THANK;
						redirect_header(XOOPS_URL."/modules/formulize/index.php?title=".$desc_form[0], 3, $error);
					}
// L'upload a réussi 
					$path = $uploader->getSavedDestination();
					$filename = $uploader->getSavedFileName();
					$up[$path] = $filename;
					$value = $path;
					$msg.= $filename.'</TD></table><br>';
// sinon l''upload a échoué : message d'erreur 
				} 
			}
			else {
				$value = $path = '';
				$filename = '';
				$msg.= $filename.'</TD></table><br>';
			}
				break;
				default:
				break;
			}

	
$date = date ("Y-m-d");
$value = addslashes ($value);

//print "<br>Value about to write:  $value";

// added code to handle proxy entries -- jwe 8/28/04 and then updating entries 9/06/04
// 1. set the uid to be the value sent from the proxyuser form element
// 2. set the proxyid to the be the uid
// 3. add proxyid call to all SQL queries that handle form_form records




if(isset($_POST['proxyuser']) AND $_POST['proxyuser'] != "noproxy")
{
	$proxyid = $realuid; // proxy flag set to user who made entry
	$uid = $_POST['proxyuser']; // uid set to the proxy uid
	$viewentry = 0; // necessary to make everything work as if a new entry is being made, which it is.
}
elseif($viewentry AND $uid != $veuid) // they are an admin who has updated someone's entry
{
	$proxyid = $realuid; // proxy flag set to user who updated entry
	$uid = $veuid; // uid set to uid of the original entry
}

// modified to update existing entries -- jwe 7/24/04

// Process to write over an entry...  (once again, assume captions are unique)
// 1. check to see if the current caption has a record that matches the viewentry (a record that is part of the current submission)
// 2. if the current caption does have a record, extract the ele_id
// 3. if there is a record and we've extracted an ele_id, then update the record with that ele_id, viewentry for id_req, and the info from the form
// 4. if the current caption does not have a record, then we create a new record (same as if viewentry were false, *except* we use the current viewentry for the id_req)

array ($submittedcaptions);

if($viewentry)
{

	// make an array out of the ele_captions so we can do a check at the end to see if any existing ones were blanked.  
	$submittedcaptions[$subcapindex] = $ele_caption;
	$subcapindex++;

	// check to see if the caption exists...
	$captionExistsJwe = 0;
	foreach($reqCaptionsJwe as $existingCaption)
	{
		if($existingCaption == $ele_caption)
		{
			$captionExistsJwe = 1;
			break;
		}
	}

	if($captionExistsJwe)
	{
		//get the ele_id
		$extractEleid = "SELECT ele_id FROM " . $xoopsDB->prefix("form_form") . " WHERE ele_caption=\"$ele_caption\" AND id_req=$viewentry";
		//print "*extractEleid*". $extractEleid . "*";
		$resultExtractEleid = mysql_query($extractEleid);
		$finalresulteleidex = mysql_fetch_row($resultExtractEleid);
		$ele_id = $finalresulteleidex[0];

		$sql="UPDATE " .$xoopsDB->prefix("form_form") . " SET id_form=\"$id_form\", id_req=\"$viewentry\", ele_id=\"$ele_id\", ele_type=\"$ele_type\", ele_caption=\"$ele_caption\", ele_value=\"$value\", uid=\"$uid\", proxyid=\"$proxyid\", date=\"$date\" WHERE ele_id = $ele_id";
		
	}
	else // or if the caption does not exist (it was blank last time the form was filled in...make a new entry but use the current viewentry for the id_req (to tie this new entry to the other elements that are part of the same record)
	{
	$sql="INSERT INTO ".$xoopsDB->prefix("form_form")." (id_form, id_req, ele_id, ele_type, ele_caption, ele_value, uid, proxyid, date) VALUES (\"$id_form\", \"$viewentry\", \"\", \"$ele_type\", \"$ele_caption\", \"$value\", \"$uid\", \"$proxyid\", \"$date\")";
	}
}
else
{
$sql="INSERT INTO ".$xoopsDB->prefix("form_form")." (id_form, id_req, ele_id, ele_type, ele_caption, ele_value, uid, proxyid, date) VALUES (\"$id_form\", \"$num_id\", \"\", \"$ele_type\", \"$ele_caption\", \"$value\", \"$uid\", \"$proxyid\", \"$date\")";
}

$result = $xoopsDB->query($sql);
    if ($result == false) {
        die('Erreur insertion : <br>' . $sql . '<br>');
    } 
    		}
	}
	$msg = nl2br($msg);			
	

if( is_dir(formulize_ROOT_PATH."/language/".$xoopsConfig['language']."/mail_template") ){
	$template_dir = formulize_ROOT_PATH."/language/".$xoopsConfig['language']."/mail_template";
}else{
	$template_dir = formulize_ROOT_PATH."/language/english/mail_template";
}

	$xoopsMailer =&getMailer();
	$xoopsMailer->multimailer->isHTML(true);
	$xoopsMailer->setTemplateDir($template_dir);
	$xoopsMailer->setTemplate('formulize.tpl');
	$xoopsMailer->setSubject(_formulize_MSG_SUBJECT._formulize_MSG_FORM.$title);
	if( is_object($xoopsUser) ){
		$xoopsMailer->assign("UNAME", $xoopsUser->getVar("uname"));
		$xoopsMailer->assign("UID", $xoopsUser->getVar("uid"));
	}else{
		$xoopsMailer->assign("UNAME", $xoopsConfig['anonymous']);
		$xoopsMailer->assign("UID", '-');
	}
	$xoopsMailer->assign("IP", xoops_getenv('REMOTE_ADDR'));
	$xoopsMailer->assign("AGENT", xoops_getenv('HTTP_USER_AGENT'));
	$xoopsMailer->assign("MSG", $msg);
	$xoopsMailer->assign("TITLE", $title);

	foreach ($up as $k => $v ) {
		$path = $k;
		$filename = $v;
		if ($xoopsMailer->multimailer->AddAttachment($path,$filename,"base64","application/octet-stream"))
			{ }
		else { echo $xoopsMailer->getErrors();}
	}
	
	if( $xoopsModuleConfig['method'] == 'pm' && is_object($xoopsUser) ){
		$xoopsMailer->usePM();

	  	$sqlstr = "SELECT $xoopsDB->prefix" . "_users.uname AS UserName, $xoopsDB->prefix" . "_users.email AS UserEmail, $xoopsDB->prefix" . "_users.uid AS UserID FROM
	            ".$xoopsDB->prefix("groups").", ".$xoopsDB->prefix("groups_users_link").", ".$xoopsDB->prefix("users")." WHERE $xoopsDB->prefix" . "_users.uid = $xoopsDB->prefix" . "_groups_users_link.uid
	            AND $xoopsDB->prefix" . "_groups_users_link.groupid = $xoopsDB->prefix" . "_groups.groupid AND $xoopsDB->prefix" . "_groups.groupid = $groupe";

	$res = $xoopsDB->query($sqlstr);
        while (list($UserName,$UserEmail,$UserID) = $xoopsDB->fetchRow($res))
		{
			$xoopsMailer->setToEmails($UserEmail);
		}

	}else{
		$xoopsMailer->useMail();

		if( $expe == "on" ){
		  $email_expe   = $xoopsUser->getVar("email");
			$xoopsMailer->setToEmails($email_expe);
			$xoopsMailer->assign("EMAIL_EXPE", $email_expe);
		} else {$xoopsMailer->assign("EMAIL_EXPE", "");}


		if( $admin == "on" ){
			$xoopsMailer->setToEmails($xoopsConfig['adminmail']);
			$xoopsMailer->assign("ADMINEMAIL", $xoopsConfig['adminmail']);
			$xoopsMailer->assign("EMAIL", "");
			$xoopsMailer->assign("GROUPE", "");
		}else{

			$xoopsMailer->assign("ADMINEMAIL", " ");
			$xoopsMailer->setToEmails($email);
		  $xoopsMailer->assign("EMAIL", $email);

if (!empty($groupe) && ($groupe != "0")){
	$sql=sprintf("SELECT name FROM ".$xoopsDB->prefix("groups")." WHERE groupid='%s'",$groupe);
	$res = mysql_query ( $sql ) or die('Erreur SQL !<br>'.$sql.'<br>'.mysql_error());
	if ( $res ) {
  	while ( $row = mysql_fetch_row ( $res ) ) {
    	$gr = $row[0];
  		}
	}

			$xoopsMailer->assign("GROUPE", $gr);}

	  	$sqlstr = "SELECT $xoopsDB->prefix" . "_users.uname AS UserName, $xoopsDB->prefix" . "_users.email AS UserEmail, $xoopsDB->prefix" . "_users.uid AS UserID FROM
	            ".$xoopsDB->prefix("groups").", ".$xoopsDB->prefix("groups_users_link").", ".$xoopsDB->prefix("users")." WHERE $xoopsDB->prefix" . "_users.uid = $xoopsDB->prefix" . "_groups_users_link.uid
	            AND $xoopsDB->prefix" . "_groups_users_link.groupid = $xoopsDB->prefix" . "_groups.groupid AND $xoopsDB->prefix" . "_groups.groupid = $groupe";

	$res = $xoopsDB->query($sqlstr);
        while (list($UserName,$UserEmail,$UserID) = $xoopsDB->fetchRow($res))
		{
			$xoopsMailer->setToEmails($UserEmail);
		}
	}
}	
	//$xoopsMailer->send(); 
// altered to change the message presented on form submission jwe 7/23/04
//	$sent = sprintf(_formulize_MSG_SENT, $xoopsConfig['sitename'])._formulize_MSG_THANK;
	$sent = _formulize_INFO_RECEIVED;
	unlink($path);
	unset ($up);
	// altered to change default redirect behaviour on submit by jwe 7/23/04
	// redraw form after adding new, return to view entries page after editing an entry.

}// end of if select-and-entry page or display form page - jwe 7/24/04

// control below should only kick in the blanking logic and redirects when we're ready to leave the page -- jwe 7/25/04
if($sent) // if $sent is set, ie we're ready to leave...
{

// only want the blanking logic to kick in on rewrites, so if-viewentry controls that...
if($viewentry) // if we've been editing an entry...
{

// notification added 10/10/04 by jwe
$notification_handler =& xoops_gethandler('notification');
array($extra_tags);
$extra_tags['ENTRYUSERNAME'] = $realnamejwe;
$extra_tags['FORMNAME'] = $title;
$extra_tags['VIEWURL'] = XOOPS_URL."/modules/formulize/index.php?title=$title&viewentry=$viewentry";
$extra_tags['VIEWURL'] = str_replace(" ", "%20", $extra_tags['VIEWURL']);
$notification_handler->triggerEvent ("form", $id_form, "update_entry", $extra_tags, $NotUs);

// Logic for handling blanking previous entries that the user has deselected...
array ($missingcaptions);
$misscapindex = 0;

			/* print "Submitted captions:<br>";
			print_r($submittedcaptions);
			print "<br><br>"; */ // debug block

		foreach($reqCaptionsJwe as $existingCaption2)
		{
			// print"Exist: $existingCaption2<br>"; // debug code
			if(!in_array($existingCaption2, $submittedcaptions))
			{
				$missingcaptions[$misscapindex] = $existingCaption2;
				$misscapindex++;
			}

		} 

			/*print "<br>Missing captions:<br>";
			print_r($missingcaptions);
			print "<br>";*/ // debug block
		

		//If there are existing captions that have not been sent for writing, then blank them.
		if(count($missingcaptions > 0))
		{
			foreach($missingcaptions as $ele_cap2)
			{
		
			$extractEleid2 = "SELECT ele_id FROM " . $xoopsDB->prefix("form_form") . " WHERE ele_caption=\"$ele_cap2\" AND id_req=$viewentry";
			$resultExtractEleid2 = mysql_query($extractEleid2);
			$finalresulteleidex2 = mysql_fetch_row($resultExtractEleid2);
			$ele_id2 = $finalresulteleidex2[0];

			$sql="DELETE FROM " .$xoopsDB->prefix("form_form") . " WHERE ele_id = $ele_id2";
			
			$result = $xoopsDB->query($sql);
			}
		}




	// now redirect the user...
	//		print "exit to view"; // debug code
		if(!$issingle OR ($issingle AND $editingent == 1)) // redirect to view entries with the right reports, etc if we're viewing entry on a multiple entry form, or editing an entry on an issingle form.
		{
			redirect_header(XOOPS_URL."/modules/formulize/index.php?title=$title&select=1&reporting=$reportingyn&reportname=$report", 2, $sent);
		}
		else // if we're on a single form and have been updating our own entry (did not arrive via view entries page) then same redirect as if viewentry is off
		{
			redirect_header(XOOPS_URL."/modules/formulize/index.php?title=$title", 2, $sent);
		}
	}
	else // if there's no viewentry set (happens on adding/first page for multiple entry forms)
	{

	// notification added 10/10/04 by jwe
	$notification_handler =& xoops_gethandler('notification');
	array($extra_tags);
	$extra_tags['ENTRYUSERNAME'] = $realnamejwe;
	$extra_tags['FORMNAME'] = $title;
	$extra_tags['VIEWURL'] = XOOPS_URL."/modules/formulize/index.php?title=$title&viewentry=$num_id";
	$extra_tags['VIEWURL'] = str_replace(" ", "%20", $extra_tags['VIEWURL']);
	$notification_handler->triggerEvent ("form", $id_form, "new_entry", $extra_tags, $NotUs);

	//	print "exit to form"; // debug code
		redirect_header(XOOPS_URL."/modules/formulize/index.php?title=$title", 2, $sent);
	}// end if view entry
}// end if sent 
}// unknown what this is the end of, but it's necessary (!!!)


?>