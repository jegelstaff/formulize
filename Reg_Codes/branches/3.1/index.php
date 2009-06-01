<?php
// ------------------------------------------------------------------------- 
//	Registration Codes
//		Copyright 2004, Freeform Solutions
// 		
//	Template
//		Copyright 2004 Thomas Hill
//		<a href="http://www.worldware.com">worldware.com</a>
// ------------------------------------------------------------------------- 
// ------------------------------------------------------------------------- //
//  This program is free software; you can redistribute it and/or modify     //
//  it under the terms of the GNU General Public License as published by     //
//  the Free Software Foundation; either version 2 of the License, or        //
//  (at your option) any later version.                                      //
//                                                                           //
//  You may not change or alter any portion of this comment or credits       //
//  of supporting developers from this source code or any supporting         //
//  source code which is considered copyrighted (c) material of the          //
//  original comment or credit authors.                                      //
//                                                                           //
//  This program is distributed in the hope that it will be useful,          //
//  but WITHOUT ANY WARRANTY; without even the implied warranty of           //
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            //
//  GNU General Public License for more details.                             //
//                                                                           //
//  You should have received a copy of the GNU General Public License        //
//  along with this program; if not, write to the Free Software              //
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA //
//  ------------------------------------------------------------------------ //
require_once "../../mainfile.php";
if ( file_exists(XOOPS_ROOT_PATH ."/modules/reg_codes/language/".$xoopsConfig['language']."/templates.php") ) {
    require_once XOOPS_ROOT_PATH ."/modules/reg_codes/language/".$xoopsConfig['language']."/templates.php";
    require_once XOOPS_ROOT_PATH ."/modules/reg_codes/language/".$xoopsConfig['language']."/modinfo.php";
} else {
	include_once XOOPS_ROOT_PATH ."/modules/reg_codes/language/english/templates.php";
	include_once XOOPS_ROOT_PATH ."/modules/reg_codes/language/english/modinfo.php";
}
// Include any common code for this module.
require_once(XOOPS_ROOT_PATH ."/modules/reg_codes/include/reg_codes_includes.php");


// **************************************************************************************************************************************//
// This takes the $msg and $data, and puts them into the template.
// Templates used by this module have a space to display
// error messages at the top of the page.
// **************************************************************************************************************************************//
function reg_codes_add_error($msg, $data)
{
	global $xoopsTpl;

	$xerror = array();
	$xerror['msg'] = $msg;
	$xerror['data'] = $data;
	$xoopsTpl->append('errors', $xerror);
}

// **************************************************************************************************************************************//
// A function that makes up the random code
// **************************************************************************************************************************************//
function make_code($lastkey)
{

	for($i=0;$i<4;$i++)
	{
		$letterid = mt_rand(1, 26);
		switch ($letterid)
		{
			case 1:
				$thiscode .= "a";
				break;
			case 2:
				$thiscode .= "b";
				break;
			case 3:
				$thiscode .= "c";
				break;
			case 4:
				$thiscode .= "d";
				break;
			case 5:
				$thiscode .= "e";
				break;
			case 6:
				$thiscode .= "f";
				break;
			case 7:
				$thiscode .= "g";
				break;
			case 8:
				$thiscode .= "h";
				break;
			case 9:
				$thiscode .= "h";
				break;
			case 10:
				$thiscode .= "j";
				break;
			case 11:
				$thiscode .= "k";
				break;
			case 12:
				$thiscode .= "k";
				break;
			case 13:
				$thiscode .= "m";
				break;
			case 14:
				$thiscode .= "n";
				break;
			case 15:
				$thiscode .= "n";
				break;
			case 16:
				$thiscode .= "p";
				break;
			case 17:
				$thiscode .= "q";
				break;
			case 18:
				$thiscode .= "r";
				break;
			case 19:
				$thiscode .= "s";
				break;
			case 20:
				$thiscode .= "t";
				break;
			case 21:
				$thiscode .= "u";
				break;
			case 22:
				$thiscode .= "v";
				break;
			case 23:
				$thiscode .= "w";
				break;
			case 24:
				$thiscode .= "x";
				break;
			case 25:
				$thiscode .= "y";
				break;
			case 26:
				$thiscode .= "z";
				break;
		}

	}
	if(!$lastkey)
	{
		$lastkey = 1;
	}
	$thiscode .= $lastkey;
	return $thiscode;

}


// **************************************************************************************************************************************//
// Displays the Main Form
// **************************************************************************************************************************************//
function reg_codes_main()
{
	global $xoopsTpl;
	global $xoopsDB;
	global $xoopsModule;
	global $xoopsUser;	

	$xoopsTpl->assign("page_title", _MI_REG_CODES_TITLE_MAIN);

	// Need to get a bunch of things...
	// 1. user id
	// 2. module id
	// 3. isadmin (user has module admin rights on this module)
	// 4. groups user is a member of
	// 5. groups user can make codes for (based on permissions given to groups they are a member of)
	// 6. existing codes they can view (filtered list based on ownership of codes by groups they are a member of -- or all codes if they are admin)

	//userid -- note we're assuming the user is not an anon user, anons should NOT have access to this module!!
	$uid = $xoopsUser->getVar("uid");

	// module id
	$module_id = $xoopsModule->getVar('mid');

	// user's groups
	$ugroups = $xoopsUser->getGroups();

	// FIND OUT IF THE CURRENT USER IS A MODULE ADMIN (AND USE THIS LATER ON TO SET CERTAIN THINGS, OVERRIDE OTHERS)


	$start=1;
	$gpermmodq = "(";
	foreach($ugroups as $agroup) // loop through all the groups the user is a member of to make a query that we can use to check if they've got module admin perms
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
	$resmodadminq = $xoopsDB->query($modadminq);
	$isadmin = $xoopsDB->fetchRow($resmodadminq); // if no rows, ie: no permission, isadmin will be 0 so will evaluate to false.

	//print "isadmin: $isadmin*";


	// get list of groups user can write codes for...

	$issue_codesq = "SELECT gperm_itemid FROM " . $xoopsDB->prefix("group_permission") . " WHERE $gpermmodq AND gperm_modid=$module_id AND gperm_name=\"issue_codes\"";
	$resissue_codesq = $xoopsDB->query($issue_codesq);
	while ($rowissue_codesq = $xoopsDB->fetchRow($resissue_codesq))
	{
		if (!in_array($rowissue_codesq[0], (array)$igroups)) { // avoid creating duplicates
			$igroups[] = $rowissue_codesq[0];
		}
	}

	// get a list of codes they have ownership of...
	// 0. if they're admin, just give them all codes...
	// 1. get data for all codes
	// 2. compare the ownership field with uid to assign ownership 

	$codeq = "SELECT reg_codes_code, reg_codes_groups, reg_codes_owner, " 
				 . " reg_codes_expiry date, reg_codes_maxuses, reg_codes_curuses, "
				 . " reg_codes_key, reg_codes_instant, reg_codes_redirect, reg_codes_approval, reg_codes_key "
				 . " FROM " . $xoopsDB->prefix("reg_codes") .  " ORDER BY reg_codes_key";
	$rescodeq = $xoopsDB->query($codeq);
	$largestkey = 1;
	while ($rowcodeq = $xoopsDB->fetchRow($rescodeq))
	{
		$codes[] = $rowcodeq[0];
		$cgroups[] = $rowcodeq[1];
		$owners[] = $rowcodeq[2];
		$expiries[] = $rowcodeq[3];
		$maxuses[] = $rowcodeq[4];
		$curuses[] = $rowcodeq[5];
		$ckeys[] = $rowcodeq[6];
		$thiskey = $rowcodeq[6]; // used for making the random code below
		if($thiskey > $largestkey) { $largestkey = $thiskey; }
		$instants[] = $rowcodeq[7];
		$redirects[] = $rowcodeq[8];
		$approvals[] = $rowcodeq[9];
		$regkey[] = $rowcodeq[10];
	}

	// mark any expired codes, or codes where the max has been reached...
	for($i=0;$i<count($ckeys);$i++)
	{
		$status[$i] = _TPL_REGCODES_STATUS_ACTIVE;
		$year = substr($expiries[$i], 0, 4);
		$month = substr($expiries[$i], 5, 2);
		$day = substr($expiries[$i], 8, 2);
		$exptime = mktime(0, 0, 0, $month, $day, $year);
		if($exptime < time())
		{
			$status[$i] = _TPL_REGCODES_STATUS_EXPIRED;
		}
		
		if($maxuses[$i] <= $curuses[$i] AND $maxuses[$i] > 0)
		{
			$status[$i] = _TPL_REGCODES_STATUS_MAXEDOUT;
		}
	}


	$ocodesindexer = 0; // counter used to fill the ocodes array, which will contain the addresses of the allowed codes in all the arrays built from the reg_code DB query -- ocodes will contain all addresses if the user is an admin

	if($isadmin)	{
		for($i=0;$i<count($owners);$i++)	{
			$ocodes[$i] = $i+1;
		}
	}
	else	{
	for($i=0;$i<count($ckeys);$i++) {
		if($owners[$i] == $uid)	{
			$ocodes[$ocodesindexer] = $i+1;
			$ocodesindexer++;
		}
		else {
			// If this users allowed groups are a superset of the groups associated with this code, then allow them see this code
			$thiscodesgroups = explode("&8(%$", $cgroups[$i]);
			$is_subset = true;
			for  ($j=0;$j<count($thiscodesgroups); $j++) {
				if (!in_array($thiscodesgroups[$j], (array)$igroups)){
					$is_subset = false;
				}
			}
			if ($is_subset) {
				$ocodes[$ocodesindexer] = $i+1;
				$ocodesindexer++;
			}
		}
	}

	//print_r($ocodes);

	} // end if isadmin

	// create data for the make-new-code form
	// 1. get a list of group names using the igroups array
	// 2. pass the language constants for the form
	// 3. pass the allowed groups array for the form
	// 4. make up the unique code that will be used

	foreach($igroups as $gr)
	{
		$groupnameq = "SELECT name FROM " . $xoopsDB->prefix("groups") . " WHERE groupid = $gr";
		$resgroupnameq = $xoopsDB->query($groupnameq); 
		while ($rowgroupnameq = $xoopsDB->fetchRow($resgroupnameq))
		{
			$igroupnames[] = $rowgroupnameq[0];
			//print "<br>$rowgroupnameq[0]"; // debug line
		}
	}

	//passing array of groups they can issue codes for...
	array_multisort($igroupnames, $igroups);


	for($i=0;$i<count($igroupnames);$i++)
	{
		$tempigroupnames[$i] .= $igroups[$i] . ">" . $igroupnames[$i];
	}

	//print_r($tempigroupnames); // debug code
	$xoopsTpl->assign("igroupnames", $tempigroupnames);

	$member_handler =& xoops_gethandler('member');
	$agroups = $member_handler->getGroups('', true); // true gets objects with id as key
	$agroupnames[0]['name'] = _TPL_REG_CODES_APPROVAL_GROUPS_NOTREQ;
	$agroupnames[0]['id'] = "";
	$agindexer = 1;
	foreach($agroups as $gid=>$thisgroup) {
		$agroupnames[$agindexer]['name'] = $thisgroup->getVar('name');
		$agroupnames[$agindexer]['id'] = $gid;
		$agindexer++;
	}
	$xoopsTpl->assign("agroupnames", $agroupnames);


	//passing constants for language in form...
	$xoopsTpl->assign("title", _MI_REG_CODES_NAME);
	
	// passing header text for the table of codes
	$xoopsTpl->assign("createnewc",_TPL_REG_CODES_CREATEIT);
	$xoopsTpl->assign("modurl", XOOPS_URL . "/modules/reg_codes/index.php");
	$xoopsTpl->assign("codelist", _TPL_REGCODES_CODELIST);
	$xoopsTpl->assign("headerActions",_TPL_REGCODES_HEADERACTIONS);
	$xoopsTpl->assign("headerStatus",_TPL_REGCODES_HEADERSTATUS);
	$xoopsTpl->assign("headerCode", _TPL_REGCODES_HEADERCODE);
	$xoopsTpl->assign("headerGroups", _TPL_REGCODES_HEADERGROUPS);
	$xoopsTpl->assign("headerExpiry", _TPL_REGCODES_HEADEREXPIRY);
	$xoopsTpl->assign("headerMaxUses", _TPL_REGCODES_HEADERMAXUSES);
	$xoopsTpl->assign("headerCurUses", _TPL_REGCODES_HEADERCURUSES);
	$xoopsTpl->assign("headerRedirect", _TPL_REGCODES_HEADERREDIRECT);
	$xoopsTpl->assign("headerInstant", _TPL_REGCODES_HEADERINSTANT);
	$xoopsTpl->assign("headerApproval", _TPL_REGCODES_HEADERAPPROVAL);
	$xoopsTpl->assign("headerApproval", _TPL_REGCODES_HEADERAPPROVAL);	
	$xoopsTpl->assign("headerPAUsers", _TPL_REG_CODES_PA_HEADER_DISPLAY);		


	if($ocodes[0]) // if there is a code that they can view...
	{ 
		
		$xoopsTpl->assign("ocodes", $ocodes); // codes equals the ids of the codes they are allowed to see
		$xoopsTpl->assign("codes", $codes);
		$xoopsTpl->assign("codeUrl", XOOPS_URL . "/register.php?code=");	// codeUrl will be used in reg_codes_index.html for displaying a registration code as a web page link .. kw 09.12.2007 
		$xoopsTpl->assign("expiries", $expiries);
		$xoopsTpl->assign("maxuses", $maxuses);
		$xoopsTpl->assign("curuses", $curuses);
		$xoopsTpl->assign("ckeys", $ckeys);
		$xoopsTpl->assign("redirects", $redirects);
		$xoopsTpl->assign("instants", $instants);
		$xoopsTpl->assign("delete", _TPL_REGCODES_DELETE);
		$xoopsTpl->assign("modify", _TPL_REGCODES_MODIFY);
		$xoopsTpl->assign("pa_display", _TPL_REG_CODES_PA_DISPLAY);		
		// convert the approval list into a readable list of groups
		$gnindexer = 0;
		foreach($approvals as $thisapproval) {
			$theseapprovals = explode(",", trim($thisapproval, ","));
			if(!is_array($theseapprovals)) {
				$theseapprovals[0] = $theseapprovals;
			} 
			$start = 1;
			foreach($theseapprovals as $ta) {
				if(!$ta) {
					$approval_groupnames[$gnindexer] = _TPL_REG_CODES_APPROVAL_GROUPS_NOTREQ;
				} else {
					if($start) {
						$approval_groupnames[$gnindexer] = $agroups[$ta]->getVar('name');
						$start = 0;
					} else {
						$approval_groupnames[$gnindexer] .= "<br />" . $agroups[$ta]->getVar('name');
					}
				}
			}
			$gnindexer++;
		}
		$xoopsTpl->assign("approvals", $approval_groupnames);
		$xoopsTpl->assign("status", $status);	
		$xoopsTpl->assign("regkey", $regkey);		
		
		// transform the cgroups into a readable list of names
		$cgnindexer = 0;
		foreach($cgroups as $gr)
		{
			$cgroupids = explode("&8(%$", $gr);
			$start = 1;
			foreach($cgroupids as $gr2)
			{
				$groupnameq = "SELECT name FROM " . $xoopsDB->prefix("groups") . " WHERE groupid = $gr2";
				$resgroupnameq = $xoopsDB->query($groupnameq); 
				while ($rowgroupnameq = $xoopsDB->fetchRow($resgroupnameq))
				{
					if($start)
					{
						$cgroupnames[$cgnindexer] = "$rowgroupnameq[0]";
						$start=0;
					}
					else
					{
						$cgroupnames[$cgnindexer] .= "<br>$rowgroupnameq[0]";
					}
				}
			}
			$cgnindexer++;
		}

		$xoopsTpl->assign("cgroupnames", $cgroupnames);

	}
	else // no allowed codes...
	{
		$xoopsTpl->assign("nocodes", _TPL_REGCODES_NOCODES);
	}
	
	} // end of main function
// **************************************************************************************************************************************//
// Display the New Form
// **************************************************************************************************************************************//

function reg_codes_new()
{
	global $xoopsTpl;
	global $xoopsDB;
	global $xoopsModule;
	global $xoopsUser;	

	$xoopsTpl->assign("page_title", _MI_REG_CODES_TITLE_NEW);
	$uid = $xoopsUser->getVar("uid"); //userid -- note we're assuming the user is not an anon user, anons should NOT have access to this module!!
	$module_id = $xoopsModule->getVar('mid'); 	// module id
	$ugroups = $xoopsUser->getGroups(); 	// user's groups

	// FIND OUT IF THE CURRENT USER IS A MODULE ADMIN (AND USE THIS LATER ON TO SET CERTAIN THINGS, OVERRIDE OTHERS)
	$start=1;
	$gpermmodq = "(";
	// loop through all the groups the user is a member of to make a query that we can use to check if they've got module admin perms
	foreach($ugroups as $agroup) {
		if($start) {
			$gpermmodq .= "gperm_groupid=$agroup";
			$start=0;
		}
		else {
			$gpermmodq .= " OR gperm_groupid=$agroup";
		}
	}
	$gpermmodq .= ")";

	$modadminq = "SELECT * FROM " . $xoopsDB->prefix("group_permission") 
						. " WHERE $gpermmodq AND gperm_itemid=$module_id "
						. " AND gperm_modid=1 AND gperm_name=\"module_admin\"";
	$resmodadminq = $xoopsDB->query($modadminq);
	$isadmin = $xoopsDB->fetchRow($resmodadminq); // if no rows, ie: no permission, isadmin will be 0 so will evaluate to false.
	// get list of groups user can write codes for...
	$issue_codesq = "SELECT gperm_itemid FROM " . $xoopsDB->prefix("group_permission") 
							. " WHERE $gpermmodq AND gperm_modid=$module_id " 
							. " AND gperm_name=\"issue_codes\"";
	$resissue_codesq = $xoopsDB->query($issue_codesq);
	while ($rowissue_codesq = $xoopsDB->fetchRow($resissue_codesq)) {
		if (!in_array($rowissue_codesq[0], (array)$igroups)) { // avoid creating duplicates
			$igroups[] = $rowissue_codesq[0];
		}
	}
	// create data for the make-new-code form
	// 1. get a list of group names using the igroups array
	// 2. pass the language constants for the form
	// 3. pass the allowed groups array for the form
	// 4. make up the unique code that will be used

	foreach($igroups as $gr) {
		$groupnameq = "SELECT name FROM " . $xoopsDB->prefix("groups") . " WHERE groupid = $gr";
		$resgroupnameq = $xoopsDB->query($groupnameq); 
		while ($rowgroupnameq = $xoopsDB->fetchRow($resgroupnameq)) {
			$igroupnames[] = $rowgroupnameq[0];
		}
	}
	//passing array of groups they can issue codes for...
	array_multisort($igroupnames, $igroups);

	for($i=0;$i<count($igroupnames);$i++)
	{
		$tempigroupnames[$i] .= $igroups[$i] . ">" . $igroupnames[$i];
	}
	$xoopsTpl->assign("igroupnames", $tempigroupnames);

	$member_handler =& xoops_gethandler('member');
	$agroups = $member_handler->getGroups('', true); // true gets objects with id as key
	$agroupnames[0]['name'] = _TPL_REG_CODES_APPROVAL_GROUPS_NOTREQ;
	$agroupnames[0]['id'] = "";
	$agindexer = 1;
	foreach($agroups as $gid=>$thisgroup) {
		$agroupnames[$agindexer]['name'] = $thisgroup->getVar('name');
		$agroupnames[$agindexer]['id'] = $gid;
		$agindexer++;
	}
	$xoopsTpl->assign("agroupnames", $agroupnames);

	//passing constants for language in form...
	$xoopsTpl->assign("createnewc", _TPL_REG_CODES_CREATEIT);
	$xoopsTpl->assign("modurl", XOOPS_URL . "/modules/reg_codes/index.php");
	$xoopsTpl->assign("typecode_l", _TPL_REG_CODES_TYPECODE);
	$xoopsTpl->assign("selectgroups_l", _TPL_REG_CODES_SELECTGROUPS);
	$xoopsTpl->assign("expirydate_l", _TPL_REG_CODES_EXPIRYDATE);
	$xoopsTpl->assign("maxuses_l", _TPL_REG_CODES_MAXUSES);
	$xoopsTpl->assign("instant_l", _TPL_REG_CODES_INSTANT);
	$xoopsTpl->assign("redirect_l", _TPL_REG_CODES_REDIRECT);
	$xoopsTpl->assign("regform", _TPL_REG_CODES_REGFORM);
	$xoopsTpl->assign("noregform", _TPL_REG_CODES_NOREGFORM);
	$xoopsTpl->assign("approval_groups_l", _TPL_REG_CODES_APPROVAL_GROUPS);
	$xoopsTpl->assign("yes", _YES);
	$xoopsTpl->assign("no", _NO);
	$xoopsTpl->assign("none", _NONE);
	$xoopsTpl->assign("savenew_l", _TPL_REG_CODES_SAVEIT);

	$codeq = "SELECT max(reg_codes_key) FROM " . $xoopsDB->prefix("reg_codes") ;
	$rescodeq = $xoopsDB->query($codeq);
	$largestkey = 1;
	while ($rowcodeq = $xoopsDB->fetchRow($rescodeq))
	{
		$largestkey = $rowcodeq[0] + 1;
	}		
	//Create unique code...
	$ucode = make_code($largestkey);
	$xoopsTpl->assign("ucode", $ucode);

} // end of new function

// **************************************************************************************************************************************//
// Display the Edit Form
// **************************************************************************************************************************************//

function reg_codes_edit()
{
	global $xoopsTpl;
	global $xoopsDB;
	global $xoopsModule;
	global $xoopsUser;	

	//Set up Labels
	$xoopsTpl->assign("page_title", _MI_REG_CODES_TITLE_EDIT);
	$xoopsTpl->assign("createnewc", _TPL_REG_CODES_CREATEIT);
	$xoopsTpl->assign("modurl", XOOPS_URL . "/modules/reg_codes/index.php");
	$xoopsTpl->assign("typecode_l", _TPL_REG_CODES_TYPECODE);
	$xoopsTpl->assign("selectgroups_l", _TPL_REG_CODES_SELECTGROUPS);
	$xoopsTpl->assign("expirydate_l", _TPL_REG_CODES_EXPIRYDATE);
	$xoopsTpl->assign("maxuses_l", _TPL_REG_CODES_MAXUSES);
	$xoopsTpl->assign("instant_l", _TPL_REG_CODES_INSTANT);
	$xoopsTpl->assign("redirect_l", _TPL_REG_CODES_REDIRECT);
	$xoopsTpl->assign("regform_l", _TPL_REG_CODES_REGFORM);
	$xoopsTpl->assign("noregform_l", _TPL_REG_CODES_NOREGFORM);
	$xoopsTpl->assign("approval_groups_l", _TPL_REG_CODES_APPROVAL_GROUPS);
	$xoopsTpl->assign("yes", _YES);
	$xoopsTpl->assign("no", _NO);
	$xoopsTpl->assign("none", _NONE);
	$xoopsTpl->assign("saveedit_l", _TPL_REG_CODES_SAVEIT);

	$uid = $xoopsUser->getVar("uid"); //userid -- note we're assuming the user is not an anon user, anons should NOT have access to this module!!
	$module_id = $xoopsModule->getVar('mid'); 	// module id
	$ugroups = $xoopsUser->getGroups(); 	// user's groups

	// FIND OUT IF THE CURRENT USER IS A MODULE ADMIN (AND USE THIS LATER ON TO SET CERTAIN THINGS, OVERRIDE OTHERS)
	$start=1;
	$gpermmodq = "(";
	// loop through all the groups the user is a member of to make a query that we can use to check if they've got module admin perms
	foreach($ugroups as $agroup) {
		if($start) {
			$gpermmodq .= "gperm_groupid=$agroup";
			$start=0;
		}
		else {
			$gpermmodq .= " OR gperm_groupid=$agroup";
		}
	}
	$gpermmodq .= ")";
	$modadminq = "SELECT * FROM " . $xoopsDB->prefix("group_permission") 
						. " WHERE $gpermmodq AND gperm_itemid=$module_id "
						. " AND gperm_modid=1 AND gperm_name=\"module_admin\"";
	$resmodadminq = $xoopsDB->query($modadminq);
	$isadmin = $xoopsDB->fetchRow($resmodadminq); // if no rows, ie: no permission, isadmin will be 0 so will evaluate to false.
	// get list of groups this user can write codes for...
	$issue_codesq = "SELECT gperm_itemid FROM " . $xoopsDB->prefix("group_permission") 
							. " WHERE $gpermmodq AND gperm_modid=$module_id " 
							. " AND gperm_name=\"issue_codes\"";
	$resissue_codesq = $xoopsDB->query($issue_codesq);
	while ($rowissue_codesq = $xoopsDB->fetchRow($resissue_codesq)) {
		if (!in_array($rowissue_codesq[0], (array)$igroups)) { // avoid creating duplicates
			$igroups[] = $rowissue_codesq[0];
		}
	}
	//create array of groups they can issue codes for...
	foreach($igroups as $gr) {
		$groupnameq = "SELECT name FROM " . $xoopsDB->prefix("groups") . " WHERE groupid = $gr";
		$resgroupnameq = $xoopsDB->query($groupnameq); 
		while ($rowgroupnameq = $xoopsDB->fetchRow($resgroupnameq)) {
			$igroupnames[] = $rowgroupnameq[0];
		}
	}
	array_multisort($igroupnames, $igroups);

	// Requery the code we are editing
	$editkey = $_POST['editkey'];
	$editq = "SELECT * FROM " . $xoopsDB->prefix("reg_codes") . " WHERE reg_codes_key=\"$editkey\"";
	$reseditq = $xoopsDB->queryF($editq);
	$r=0;
	while ($roweq = $xoopsDB->fetchRow($reseditq)) {
		$xoopsTpl->assign("typecode_d",$roweq[1] );
		$xoopsTpl->assign("ucode",$roweq[1] );
		$xoopsTpl->assign("expirydate_d",$roweq[4] );
		$xoopsTpl->assign("maxuses_d",$roweq[5] );
		$xoopsTpl->assign("instant_d", $roweq[7] );
		$xoopsTpl->assign("redirect_d",$roweq[8]  );
		// Set up Select Box for Groups this code applies to 
		$savedgroupids = explode("&8(%$", $roweq[2]);
		for($i=0;$i<count($igroupnames);$i++)
		{
			if (in_array( $igroups[$i], (array)$savedgroupids)) {
				$tempigroupnames[$i] .= $igroups[$i] . " selected >" . $igroupnames[$i];
			}
			else {
				$tempigroupnames[$i] .= $igroups[$i] . " >" . $igroupnames[$i];
			}
		}
		$xoopsTpl->assign("igroupnames", $tempigroupnames);
		// Set up Select Box for Approval Groups 
		$member_handler =& xoops_gethandler('member');
		$agroups = $member_handler->getGroups('', true); // true gets objects with id as key
		$agroupnames[0]['name'] = _TPL_REG_CODES_APPROVAL_GROUPS_NOTREQ;
		$agroupnames[0]['id'] = "";
		$agroupnames[0]['selected'] = "";
		$agindexer = 1;
	
		foreach($agroups as $gid=>$thisgroup) {
			$agroupnames[$agindexer]['name'] = $thisgroup->getVar('name');
			$agroupnames[$agindexer]['id'] = $gid;
			if (strpos($roweq[9], ','.$gid.',') ===false ) {
				$agroupnames[$agindexer]['selected'] = '';
			}
			else {
				$agroupnames[$agindexer]['selected'] = 'selected';
			}
			$agindexer++;
		}
		$xoopsTpl->assign("agroupnames", $agroupnames);
	
	}
} // end of edit function
		
// **************************************************************************************************************************************//
// Handles data posted from any form on the main page
// **************************************************************************************************************************************//
function reg_codes_post($exists)
{
	global $xoopsDB;
	global $xoopsUser;

	// need to get some data... userid -- note we're assuming the user is not an anon user, anons should NOT have access to this module!!
	$uid = $xoopsUser->getVar("uid");

	//make the groups array into a flat field...
	$start=1;
	foreach($_POST['codegroups'] as $agroup)
	{
		if($start){
			$flatgroups = "$agroup";
			$start=0;
		}
		else 	{
			$flatgroups .= "&8(%$" . "$agroup";
		}
	}

	$thecode = $_POST['thecode'];
	if(isset($_POST['customcode']) AND $_POST['customcode'] != "") { $thecode = $_POST['customcode']; }
	$expirydate = $_POST['expirydate'];
	$maxuses = $_POST['maxuses'];
	$redirect = $_POST['redirect'];
	$instant = $_POST['instant'];

	$approval = "," . implode(",", $_POST['approvalgroups']) . ","; // leading and trailing comma allows a search for ,5, in the string to find it even if there is only a single value
	if ($exists) {	
			$codewriteq = "UPDATE " . $xoopsDB->prefix("reg_codes") 
								. " set reg_codes_code = \"$thecode\", " 
								. " reg_codes_groups = \"$flatgroups\", "
								. " reg_codes_owner = \"$uid\", " 
								. " reg_codes_expiry = \"$expirydate\", "
								. " reg_codes_maxuses = \"$maxuses\", " 
								. " reg_codes_redirect = \"$redirect\", " 
								. " reg_codes_instant = \"$instant\", "
								. " reg_codes_approval = \"$approval\" " 
								. " where  reg_codes_code = \"$thecode\" ";
	}
	else {
			$codewriteq = "INSERT INTO " . $xoopsDB->prefix("reg_codes") 
								. " (reg_codes_code, reg_codes_groups, reg_codes_owner, reg_codes_expiry, "
								. " reg_codes_maxuses, reg_codes_curuses, reg_codes_redirect, reg_codes_instant, "
								. " reg_codes_approval) "
								. " VALUES (\"$thecode\", \"$flatgroups\", \"$uid\", \"$expirydate\", \"$maxuses\","
								. " \"0\", \"$redirect\", \"$instant\", \"$approval\")";
	}
	$rescodewriteq = $xoopsDB->query($codewriteq);
}

// **************************************************************************************************************************************//
// this is the function that deletes selected codes
// **************************************************************************************************************************************//
function reg_codes_delete()
{
	global $xoopsDB;
	
	$deletekey = $_POST['deletekey'];
	$killq = "DELETE FROM " . $xoopsDB->prefix("reg_codes") . " WHERE reg_codes_key=\"$deletekey\"";
	//print "$killq";
	$reskillq = $xoopsDB->queryF($killq);
}

// **************************************************************************************************************************************//
// Main page to display pre-approved users asociated with chosen reg_code
// **************************************************************************************************************************************//

function displaypauser()
{
	global $xoopsTpl;
	global $xoopsDB;
	global $xoopsModule;
	global $xoopsUser;	

	$xoopsTpl->assign("page_title", _MI_REG_CODES_PA_TITLE_MAIN);

	$pa_regkey_disp = $_POST['pa_regkey_disp'];

	$pa_display = "SELECT * FROM " . $xoopsDB->prefix("reg_codes_preapproved_users") . " WHERE reg_codes_key=\"$pa_regkey_disp\"";
	$pa_displayq = $xoopsDB->queryF($pa_display);
	$pa_num_rows = $xoopsDB->getRowsNum( $pa_displayq );	
	while ($pa_displayrow = $xoopsDB->fetchRow($pa_displayq))
	{
		$pa_id[] = $pa_displayrow[0];
		$pa_user[] = $pa_displayrow[2];		
	}	

//	$xoopsTpl->assign("title", _MI_REG_CODES_PA_DISPLAY);
	$xoopsTpl->assign("modurl", XOOPS_URL . "/modules/reg_codes/index.php");

	$xoopsTpl->assign("headerUsers", _TPL_REG_CODES_PA_HEADER_USERS);
	$xoopsTpl->assign("headerActions",_TPL_REGCODES_HEADERACTIONS);	
	$xoopsTpl->assign("suspend",_TPL_REGCODES_SUSPEND);	
	$xoopsTpl->assign("delete",_TPL_REGCODES_PA_DELETE);
	$xoopsTpl->assign("modify",_TPL_REGCODES_MODIFY);	
	$xoopsTpl->assign("cancel",_TPL_REGCODES_CANCEL);
	$xoopsTpl->assign("adduser",_TPL_REGCODES_PA_ADDUSER);			
	$xoopsTpl->assign("go_back",_TPL_REGCODES_GO_BACK);		
	$xoopsTpl->assign("pa_num_rows", $pa_num_rows);	
	$xoopsTpl->assign("pa_id", $pa_id);
	$xoopsTpl->assign("pa_user", $pa_user);	
	$xoopsTpl->assign("pa_user_notfound",_TPL_REG_CODES_PA_NOTFOUND);	
	$xoopsTpl->assign("pa_regkey", $pa_regkey_disp); //needed for reg_codes_pa_main.html ADD new pre-app users button
	
	} // end of preapp_main function


// **************************************************************************************************************************************//
// Suspend (and archived user in users table) chosen pre-approved user - kw 09.13.2007
// **************************************************************************************************************************************//

function suspendpauser ()
{
	global $xoopsTpl;
	global $xoopsDB;
	global $xoopsModule;
	global $xoopsUser;

	$xoopsTpl->assign("page_title", _MI_REG_CODES_PA_TITLE_SUSPEND);

	$suspendpaid = $_POST['suspendpauser'];

	$suspendpa_q = "SELECT * FROM " . $xoopsDB->prefix("reg_codes_preapproved_users") . " WHERE reg_codes_preapproved_id=\"$suspendpaid\"";
	$suspendpa_rq = $xoopsDB->queryF($suspendpa_q);
	while ($suspendpa_row = $xoopsDB->fetchRow($suspendpa_rq))
	{
		$suspendpa_id = $suspendpa_row[0];
		$suspendpa_key = $suspendpa_row[1];
		$suspendpa_user = $suspendpa_row[2];	
	}	

	//passing constants for language in form...
	$xoopsTpl->assign("modurl", XOOPS_URL . "/modules/reg_codes/index.php");
	
	$xoopsTpl->assign("title", _MI_REG_CODES_PA_TITLE_SUSPEND);
	$xoopsTpl->assign("headerUsers", _TPL_REG_CODES_PA_HEADER_USERS);
	$xoopsTpl->assign("headerActions",_TPL_REGCODES_HEADERACTIONS);	
	$xoopsTpl->assign("suspend_sure",_TPL_REGCODES_PA_SUSPENDSURE);		
	$xoopsTpl->assign("yes",_TPL_REGCODES_PA_YES);
	$xoopsTpl->assign("no",_TPL_REGCODES_PA_NO);		
	$xoopsTpl->assign("suspendpa_id", $suspendpa_id);
	$xoopsTpl->assign("suspendpa_key", $suspendpa_key);
	$xoopsTpl->assign("suspendpa_user", $suspendpa_user);	
	$xoopsTpl->assign("pa_user_notfound",_TPL_REG_CODES_PA_NOTFOUND);	
	
} // end of suspendpauser function

// **************************************************************************************************************************************//
// Suspend (and archived user in users table) chosen pre-approved user - kw 09.13.2007
// **************************************************************************************************************************************//


function suspendsavepauser ()
{
	global $xoopsTpl;
	global $xoopsDB;
	global $xoopsModule;
	global $xoopsUser;

	$xoopsTpl->assign("page_title", _MI_REG_CODES_PA_TITLE_SUSPENDSAVE);

	$suspsave_id = $_POST['suspendsavepauserid'];
	
	$suspendpa_q = "SELECT * FROM " . $xoopsDB->prefix("reg_codes_preapproved_users") . " WHERE reg_codes_preapproved_id=\"$suspsave_id\"";
	$suspendpa_rq = $xoopsDB->queryF($suspendpa_q);
	while ($suspendpa_row = $xoopsDB->fetchRow($suspendpa_rq))
	{
		$suspendpa_id = $suspendpa_row[0];
		$suspendpa_key = $suspendpa_row[1];
		$suspendpa_user = $suspendpa_row[2];	
	}		
	
	$suspendpa_user_x = $suspendpa_user.' (x)';
	$suspendselectu_q = "SELECT uid, uname, email, archived FROM " . $xoopsDB->prefix("users") . " WHERE uname=\"$suspendpa_user\" OR uname=\"$suspendpa_user_x\"";
	$suspendselectu_rq = $xoopsDB->queryF($suspendselectu_q);
	while ($suspendselectu_row = $xoopsDB->fetchRow($suspendselectu_rq))
	{
		$suspendselectu_uid = $suspendselectu_row[0];
		$suspendselectu_uname = $suspendselectu_row[1];
		$suspendselectu_email = $suspendselectu_row[2];		
		$suspendselectu_archived = $suspendselectu_row[3];	
	}		
	
	if ($suspendselectu_archived == 0) // active user
	{
		$uname_s = $suspendselectu_uname.' (x)';
		$email_s = $suspendselectu_email.' (x)';		
		$archived_s = 1;
	}
	else //if ($suspendselectu_archived == 1)  already archived user
	{
	    $uname_s = $suspendselectu_uname;
		$email_s = $suspendselectu_email;			
		$archived_s = 1;
	}
	
	$suspendsave = "UPDATE " . $xoopsDB->prefix("users") . " set uname = \"$uname_s\", email = \"$email_s\", archived = \"$archived_s\"  where uid = \"$suspendselectu_uid\" ";
	$suspendsaveq = $xoopsDB->queryF($suspendsave);

	$suspendsavepa_q = "DELETE FROM " . $xoopsDB->prefix("reg_codes_preapproved_users") . " WHERE reg_codes_preapproved_id=\"$suspsave_id\"";
	$suspendsavepa_rq = $xoopsDB->queryF($suspendsavepa_q);

	$xoopsTpl->assign("title", _MI_REG_CODES_PA_TITLE_EDIT);
	$xoopsTpl->assign("modurl", XOOPS_URL . "/modules/reg_codes/index.php");

}


// **************************************************************************************************************************************//
// Edit chosen pre-approved user - kw 09.13.2007
// **************************************************************************************************************************************//

function editpauser ()
{
	global $xoopsTpl;
	global $xoopsDB;
	global $xoopsModule;
	global $xoopsUser;

	$xoopsTpl->assign("page_title", _MI_REG_CODES_PA_TITLE_EDIT);

	$editpaid = $_POST['editpauser'];

	$editpa_q = "SELECT * FROM " . $xoopsDB->prefix("reg_codes_preapproved_users") . " WHERE reg_codes_preapproved_id=\"$editpaid\"";
	$editpa_rq = $xoopsDB->queryF($editpa_q);
	while ($editpa_row = $xoopsDB->fetchRow($editpa_rq))
	{
		$editpa_id = $editpa_row[0];
		$editpa_key = $editpa_row[1];
		$editpa_user = $editpa_row[2];	
	}	

	//passing constants for language in form...
	$xoopsTpl->assign("modurl", XOOPS_URL . "/modules/reg_codes/index.php");
	
	$xoopsTpl->assign("title", _MI_REG_CODES_PA_TITLE_EDIT);
	$xoopsTpl->assign("headerUsers", _TPL_REG_CODES_PA_HEADER_USERS);
	$xoopsTpl->assign("headerActions",_TPL_REGCODES_HEADERACTIONS);	
	$xoopsTpl->assign("save",_TPL_REGCODES_SAVE);
	$xoopsTpl->assign("cancel",_TPL_REGCODES_CANCEL);	
	$xoopsTpl->assign("editpa_id", $editpa_id);
	$xoopsTpl->assign("editpa_key", $editpa_key);
	$xoopsTpl->assign("editpa_user", $editpa_user);	
	$xoopsTpl->assign("pa_user_notfound",_TPL_REG_CODES_PA_NOTFOUND);	
	
} // end of editpauser function
	


// **************************************************************************************************************************************//
// Add new pre-approved user - kw 09.13.2007
// **************************************************************************************************************************************//

function addpauser ()
{
	global $xoopsTpl;
	global $xoopsDB;
	global $xoopsModule;
	global $xoopsUser;

	$xoopsTpl->assign("page_title", _MI_REG_CODES_PA_TITLE_ADD);

	$addpauser_regkey = $_POST['addpauser_regkey'];

	$xoopsTpl->assign("modurl", XOOPS_URL . "/modules/reg_codes/index.php");
	
	$xoopsTpl->assign("title", _MI_REG_CODES_PA_TITLE_ADD);
	$xoopsTpl->assign("headerPAAddUsers", _TPL_REG_CODES_PA_HEADER_ADDUSERS);
	$xoopsTpl->assign("add",_TPL_REGCODES_PA_ADD);
	$xoopsTpl->assign("cancel",_TPL_REGCODES_CANCEL);	
	$xoopsTpl->assign("addpauser_regkey",$addpauser_regkey);	
	
} // end of addpauser function	
	
	
// **************************************************************************************************************************************//
// Save new pre-approved user - kw 09.13.2007
// **************************************************************************************************************************************//

function addsavepauser ()
{
	global $xoopsTpl;
	global $xoopsDB;
	global $xoopsModule;
	global $xoopsUser;

	$xoopsTpl->assign("page_title", _MI_REG_CODES_PA_TITLE_ADDSAVE);

	$addsavepauser_regkey = $_POST['addsavepauser_regkey'];
	$addsavepauser = $_POST['addsavepauser'];
	
	$addsave_q = "INSERT INTO " . $xoopsDB->prefix("reg_codes_preapproved_users") . " (reg_codes_key, reg_codes_preapproved) VALUES (\"$addsavepauser_regkey\", \"$addsavepauser\")";
	$addsave_rq = $xoopsDB->queryF($addsave_q);

	$xoopsTpl->assign("title", _MI_REG_CODES_PA_TITLE_ADDSAVE);
	$xoopsTpl->assign("modurl", XOOPS_URL . "/modules/reg_codes/index.php");	
	
	
} // end of addsavepauser function		
	

// **************************************************************************************************************************************//
// Save edited pre-approved user - kw 09.13.2007
// **************************************************************************************************************************************//

function savepauser ()
{
	global $xoopsTpl;
	global $xoopsDB;
	global $xoopsModule;
	global $xoopsUser;

	$xoopsTpl->assign("page_title", _MI_REG_CODES_PA_TITLE_SAVE);

	$savepaid = $_POST['savepauserid'];
	$savepauser = $_POST['savepauser'];	

	$savepa_q = "UPDATE " . $xoopsDB->prefix("reg_codes_preapproved_users") . " SET reg_codes_preapproved=\"$savepauser\" WHERE reg_codes_preapproved_id=\"$savepaid\"";
	$savepa_rq = $xoopsDB->queryF($savepa_q);

	$xoopsTpl->assign("title", _MI_REG_CODES_PA_TITLE_EDIT);
	$xoopsTpl->assign("modurl", XOOPS_URL . "/modules/reg_codes/index.php");

} 							

// 
// **************************************************************************************************************************************//
//	Main
// **************************************************************************************************************************************//

// Get all HTTP post or get parameters into global variables that are prefixed with "param_"
import_request_variables("gp", "param_");

$mytemplate = 'reg_codes_index.html';

if (!isset($param_op)) 
	$param_op = "main";

if(isset($_POST['createnewcode'])) {
	$param_op = "new";
	$mytemplate = 'reg_codes_new.html';
	}

if(isset($_POST['savenewcode'])) {
	$param_op = "savenew";
	}

if(isset($_POST['saveeditcode'])) {
	$param_op = "saveedit";
	}	
if(isset($_POST['deletethiscode']))
	$param_op = "delete";

if(isset($_POST['editthiscode'])) {
	$param_op = "edit";
	$mytemplate = 'reg_codes_edit.html';
	}

// kw suspend user 091307

if(isset($_POST['displaypauser'])) 
{
	$param_op = "displaypauser";
	$mytemplate = 'reg_codes_pa_main.html';
	}	
	
if(isset($_POST['suspendthispauser'])) 
{
	$param_op = "suspendpauser";
	$mytemplate = 'reg_codes_pa_suspend.html';
	}		
	
if(isset($_POST['suspendsavethispauser'])) 
{
	$param_op = "suspendsavepauser";
	}	
	
if(isset($_POST['editthispauser'])) 
{
	$param_op = "editpauser";
	$mytemplate = 'reg_codes_pa_edit.html';
	}	

if(isset($_POST['addpauser'])) 
{
	$param_op = "addpauser";
	$mytemplate = 'reg_codes_pa_add.html';
	}	
	
if(isset($_POST['addsavepauser'])) 
{
	$param_op = "addsavepauser";
	}		
	
if(isset($_POST['savethispauser'])) 
{
	$param_op = "savepauser";
	}	
	
if(isset($_POST['cancelpauser'])) 
{
	$param_op = "cancelpauser";
	}	
	
// to jump back to reg_codes_pa_main.html
if(isset($_POST['cancel_topamain'])) 
{
	$param_op = "cancel_topamain";
	$mytemplate = 'reg_codes_pa_main.html';	
	}
	
// end test suspend user
	
// This page uses smarty templates. Set "$xoopsOption['template_main']" before including header
$xoopsOption['template_main'] = "$mytemplate";
include XOOPS_ROOT_PATH.'/header.php';
$xoopsTpl->assign('page_title', _AM_REG_CODES_LABEL_MAIN_TITLE);
	
switch ($param_op) 
{
	case "main":
		reg_codes_main();
		break;
		
	case "new":
		reg_codes_new();
		break;
		
	case "savenew": // process the post data, then render the main page
		reg_codes_post(false);
		reg_codes_main();
		break;

	case "saveedit": // process the post data, then render the main page
		reg_codes_post(true);
		reg_codes_main();
		break;		
		
	case "delete":
		reg_codes_delete();
		reg_codes_main();
		break;

	case "edit":
		reg_codes_edit();
		break;

// kw test suspend user2 kw 09.13.2007	
	case "displaypauser":
		displaypauser();		
		break;

	case "suspendpauser":
		suspendpauser();		
		break;
		
	case "suspendsavepauser":
		suspendsavepauser();
		reg_codes_main();		
		break;		
		
	case "editpauser":
		editpauser();		
		break;
		
	case "addpauser":
		addpauser();		
		break;	

	case "addsavepauser":
		addsavepauser();	
		reg_codes_main();		
		break;			
		
	case "savepauser":
		savepauser();
		reg_codes_main();		
		break;		
		
	case "cancelpauser":
		reg_codes_main();	
		break;	

	case "cancel_topamain":
		displaypauser();	
		break;	
		
// kw end test suspend user2		
		
	default:
		print "<h1>:Unknown method requested '$param_op' in index.php</h1>";
		exit();
}

include XOOPS_ROOT_PATH."/footer.php";

?>