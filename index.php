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

// ****************
// This file contains the highest level logic of the module.  The "grunt work" is carried out by the included files, for the most part, with only some of the highest level control structures present in this file.
// The dependancies and outputs of the included files are documented here, with some notes about their purposes.  Note that the code is subdivided using includes, and not actual function calls, or instantiations of objects (for the most part).
// Several variables include the initials jwe.  That's because they were introduced very early in development before I knew the complete module codebase well, so the initials helped identify what code was new, and ensured that variable names were unique (it wasn't meant as programmer narcissism!).
// These docs are a first pass; the lists of variables that are relied on and produced may not be 100% complete.

// ****************
// ******DOCS Last updated: January 1 2005
// ****************

// start up by calling header.php and other standard stuff (getting the user object, etc)
include 'initialize.php';

// ***********
// initialize.php docs:
//
// ***produces:
// $uid -- the ID number of the current user
// $realuid -- the ID number of the current user, used in the case of proxy submissions (admins making submissions on behalf of other users)
// $usernamejwe -- the username of the current user
// $realnamejwe -- the full name of the current user
// $title -- the name of the current form, passed on the URL, used to determine the ID of the form.  Future revisions pass the ID on the URL instead.
// $id_form -- the ID number of the current form
// $admin -- formulaire option related to sending e-mail copies of submissions, which is a feature that has been commented.
// $groupe -- formulaire option related to sending e-mail copies of submissions, which is a feature that has been commented.
// $expe -- formulaire option related to sending e-mail copies of submissions, which is a feature that has been commented.
// $email -- formulaire option related to sending e-mail copies of submissions, which is a feature that has been commented.
//
// ***********

// check to see that the user is allowed to be here in the first place, and if not, kick them out
include 'security.php';

// ***********
// security.php docs:
//
// ***relies on:
// $uid
// $id_form
// 
// ***produces:
// $module_id -- the ID number of the formulize module in the current XOOPS installation
// $groupuser -- ARRAY, an array of the group IDs of the groups the user is a member of
// $groupidadd -- ARRAY, an array of the group IDs of groups that have permission to add entries to the current form
// $groupid -- ARRAY, an array of the group IDs of the groups that have permission to view entries in this form
// $theycanadd -- a flag that is set to 1 if user has permission to add entries to the form
//
// ***********

// set all the variables and stuff used to keep track of everything in the module
include 'setflags.php';

// ***********
// setflags.php docs:
//
// ***relies on:
// $groupuser ARRAY
// $module_id
// $id_form
// $uid
// $theycanadd
//
// ***produces:
// $ismoduleadmin -- a flag that is set to 1 if the user has admin rights for this module
// $isadmin -- a flag that is set to 1 if the user has admin rights for the current form
// $hasgroupscope -- a flag that is set to 1 if the current form has "group-scope", that is, if the current form allows, by default, for users to see entries made by other users in the same group(s) as them 
// $issingle -- a flag that is set if the current form allows only one entry per user (or per group in the case of groupscope forms).  Returning to an issingle form pulls up the previous entry for editing; there's no way to create a second or third or fourth, etc, entry.
// $selectjwe -- a flag that is set by the URL, indicating whether the user is on the add-an-entry page (flag off) or the view-entries page (flag on).
// $viewentry -- the ID of the entry that is to be displayed on the add page (if set to zero, or not set, then the default add-an-entry page is displayed)
// $editingent -- a flag that is set when the user is modifying an existing entry (as opposed to creating a new one)
// $veuid -- the uid of the user that owns the current entry (when a user is viewing an existing entry)
// $enuid -- seems to be the same as $veuid, though only set if the form has groupscope 
// $vereportcheck -- a flag that indicates that a further check is necessary to see if the user might be allowed to view an entry, based on them having received a report that gives them access to the entry; if set, will contain the ID of the entry they might be allowed to see
// $reportingyn -- a flag that is set to 1 if the user is on the Report Writing Mode page
// $report -- the ID of the report that the user is currently viewing
// $showscope -- a flag that is set to 1 if a scope selection box should be displayed or not
// $sent -- a flag that is initialized here, but set later on after a submission is made
// $showviewentries -- a flag that is set to 1 if the form should allow users to see pages other than the add-an-entry page
//
// ***other:
// Sets the notification list -- the users who will be notified of events in this form
// Deletes entries if the user clicked the delete button for an entry
// Does some security checking to make sure the user is allowed to see the entry that they have requested, that the user is allowed to see the add-an-entry page, etc
// Deletes reports if the user called for that (this code happens at the end of the file, so that it overrides any settings that have already been made for $report).
//
// ************

// create a list of reports the user is allowed to see for the current form
include 'getreportlist.php';

// ************
// getreportlist.php docs:
//
// ***relies on:
// $uid
// $id_form
// $isadmin
// $vereportcheck
//
// ***produces:
// $finalreportlist -- ARRAY, an array that contains the reports that should be placed in the list of available reports.  Formatted for direct insertion in the HTML page (ie: contains the ID and the name, setup to be written into the template alongside all the other HTML).
// $viewentry -- OVERRIDE, can possibly restore a previously blanked viewentry variable if the last-chance "view entry report check" is passed.
// $selectjwe -- OVERRIDE, can possibly change this to zero, based on passing the vereportcheck
// $showviewentries -- OVERRIDE, can possibly override an existing value of this variable
//
// ***other:
// Contains a check for whether the user is allowed to see a report which is duplicated lower down.  The check lower down should be removed and that code should be made to rely on the check that is done in this file.  
//
// ************

// THIS IS THE KEY SWITCH BETWEEN THE PAGES RIGHT HERE...
// if selectjwe, then all the logic pertaining to displaying, versus adding, entries kicks in
//print "**select status check: $selectjwe (viewentry: $viewentry)";
if($selectjwe) // if we're selecting entries...check to see that this form really shows entries...
{
	if(!$showviewentries)
	{
		$selectjwe = 0;
	}
}

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

include 'readreport.php';

// ************
// readreport.php docs:
//
// ***relies on:
// $report
// $uid
// $ismoduleadmin
// $groupuser ARRAY
//
// ***produces:
// $reqFieldsJwe -- ARRAY, the list of fields that are to be displayed
// $ascdscArray -- ARRAY, an array indicating whether the requested sort direction for each field is ascending or descending
// $search_typeArray -- ARRAY, an array indicating the operator used in a search for each field
// $search_textArray -- ARRAY, the search text for each field
// $andorArray -- ARRAY, the local and/or setting that indicates how to treat multiple terms for this field.  Set to either "and" or "or"
// $calc_typeArray -- ARRAY, an array containing arrays that indicate the calculations requested for each field.
// $sort_orderArray -- ARRAY, an array showing the priority of fields for sorting, ie: which one to sort by first and then second, etc
// $globalandor -- a flag, set to either "and" or "or", indicating how to treat the logic of searches against more than one field
// $candeletereport -- a flag, set to 1 if the user is allowed to delete this report (controls whether the delete options appear on the screen or not)
// $report_ispublished -- a flag that indicates whether the report is published or not
// $report -- OVERRIDE, can be set to 0 if the user is deemed unable to access this report
// $sentscope -- ARRAY, the scope that a report carries with it, overriding the user's default scope
// $report_nove -- a flag that shows that the View This Entry links are NOT to be displayed for this report
// $report_calconly -- a flag that shows that only calculations (NO summary table) are to be displayed for this report
//
// ***other:
// The main arrays, and globalandor, set by this file are set by getpassedparams.php in cases where no report was specified.
// These arrays can also be set by the writereport.php file if the user has just saved a report.
// The resetting of report=0 could/should be done by getreportlist.php since there is a check for the available reports up there. 
//
// ************

} // END OF if-A-REPORT HAS BEEN REQUESTED...

if(!$report) // handled as a separate condition, not an else, since we can set the report to 0 after it is read if the user doesn't have perms on the report
{

// look for query controls passed from the user and other display config data
include 'getpassedparams.php';

// ***********
// getpassedparams.php docs:
//
// ***relies on:
// $id_form
//
// ***produces:
// $reqFieldsJwe -- ARRAY, the list of fields that are to be displayed
// $ascdscArray -- ARRAY, an array indicating whether the requested sort direction for each field is ascending or descending
// $search_typeArray -- ARRAY, an array indicating the operator used in a search for each field
// $search_textArray -- ARRAY, the search text for each field
// $andorArray -- ARRAY, the local and/or setting that indicates how to treat multiple terms for this field.  Set to either "and" or "or"
// $calc_typeArray -- ARRAY, an array containing arrays that indicate the calculations requested for each field.
// $sort_orderArray -- ARRAY, an array showing the priority of fields for sorting, ie: which one to sort by first and then second, etc
// $globalandor -- a flag, set to either "and" or "or", indicating how to treat the logic of searches against more than one field
// $sentscope -- ARRAY, the scope the user requested for this query, overriding the default scope
//
// ***********

} // END OF BIG IF THAT CONTROLS READING USER'S OWN QUERY DATA.

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

// determine the scope of the form, based on the user's permissions and the form settings, and setup the array of userids that controls scope
include 'setupscope.php';

// ***************
// setupscope.php docs:
//
// ***relies on:
// $sentscope
// $hasgroupscope
// $groupidadd ARRAY
// $groupuser ARRAY 
//
// ***produces:
// $hasgroupscope -- OVERRIDE, can change this setting if the a report/user-specified scope is in place
// $gscopeparam -- the list of user ids that constitute the scope that is to be used.  Only entries associated with these user ids will be returned.  Text of variable is formatted in such a way that it can be dropped into an SQL query.
//
// ***other:
// The setup of gscopeparam based on the internally specified masteruserlist array is repeated/duplicated in setupsingle.php.
//
// **************


// save a report that the user has saved
include 'writereport.php';

// *************
// writereport.php docs:
// 
// ***relies on:
// $uid
// $id_form 
// $reqFieldsJwe
// $ascdscArray
// $search_typeArray
// $search_textArray
// $andorArray
// $calc_typeArray
// $sort_orderArray
// $globalandor
// $sentscope
// $hasgroupscope
// $module_id
// $id_form
//
// ***produces:
// $showscope -- OVERRIDE, can change the value of this variable based on the settings the user selected for this report
// $report -- OVERRIDE, can change the value of this variable to match the report that was just written
// $finalreportlist -- ARRAY, OVERRIDE, adds the saved report to the list of available reports
// $candeletereport -- OVERRIDE, sets this flag to "on" since the user created the report and can always delete their own reports
// $report_nove -- a flag that shows that the View This Entry links are NOT to be displayed for this report
// $report_calconly -- a flag that shows that only calculations (NO summary table) are to be displayed for this report
//
// *************

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

// setup the list of possible scopes the user can choose from if they are allowed to have a choice of scopes for the current form or report
include 'generatereportscopes.php';

// ***********
// generatereportscopes.php docs:
//
// ***relies on:
// $showscope
// $ismoduleadmin
// $groupuser ARRAY
// $groupidadd
//
// ***other:
// doesn't produce any variables, but sends data to the template
//
// *************

// setup info for the template about what fields are allowed to be sorted on
include 'sorthandling.php';

// ************
// sorthandling.php docs:
// 
// ***relies on:
// $id_form
// $reqFieldsJwe ARRAY
// 
// ***other
// doesn't produce variables, but sets up data for the template
//
// *************

// setup any searches the user has requested (to filter the results)
include 'search.php';

// **************
// search.php docs:
//
// ***relies on:
// $search_typeArray ARRAY
// $search_textArray ARRAY
// $andorArray ARRAY
// $globalandor 
// $reqFieldsJwe ARRAY 
// $id_form
// $gscopeparam
// $uid
//
// ***produces:
// $userreportingquery -- a string, formatted for dropping into an SQL query, that limits the records returned to those that match the search criteria specified by the user.  Limiting is based on the id_req (unique ID) of each record.
//
// *************

// grab results from the DB based on the user's searches
include 'readresults.php';

// **************
// readresults.php docs:
//
// ***relies on:
// $gscopeparam
// $id_form
// $userreportingquery
// $uid
// 
// ***produces:
// $finalselectidreq -- ARRAY, the master list of the unique IDs of each record that is to be shown to the user.
// $totalentriesindex -- the number of unique IDs returned to $finalselectidreq, minus 1 (first record gets ID 0 in the array).
// $totalresultarray -- ARRAY, contains the unique ID, caption and value for each record to be displayed to the user.
// $atleastonereq -- a flag used below to determine if anything has been returned for the user to see
//
// ***other:
// currently performs two searches, this code could be optimized greatly to one search I believe.
//
// ***************

// make the master array that contains all the values of all fields in all entries returned when reading results -- the fundamental purpose of this block is to add "blanks" since blanks are not stored in the DB
include 'buildvalues.php';

// ***************
// buildvalues.php docs:
// 
// ***relies on:
// $totalresultarray ARRAY
// $totalentriesindex
// $reqFieldsJwe
// 
// ***produces:
// $selvals -- ARRAY, containing all the core data to display on screen for the user.  (ie: the info returned from the DB, not the metadata about who entered it, when it was entered, etc, just the values)
// 
// ***other:
// This code is a complete mess and could be optimized greatly.  I believe much of the speed hit caused by large result sets is caused by this code block (and by how the data is extracted from the DB in the first place).
//
// ***************

// prepare entries for display on the screen to the user (split multiple entry fields into component parts, stripslashes, etc) 
include 'prepvalues.php';

// *************
// prepvalues.php docs:
//
// ***relies on:
// $selvals ARRAY
// $gscopeparam
// $id_form
// $reqFieldsJwe ARRAY
// $uid
//
// ***produces:
// $selvals -- OVERRIDE, ARRAY, simply rewrites the values in the array to make them look right (convert codes to YES and NO, stripslashes, etc).
//
// ***other:
// This code block will be causing some of the slowdown too, by traversing the whole array again to make these corrections.
//
// ***************


//prepare some variables used in both the sort and calculation routines
include 'sortcalcprep.php';

// ***************
// sortcalcprep.php docs:
//
// ***relies on:
// $reqFieldsJwe ARRAY
// $calc_typeArray ARRAY
// $sort_orderArray ARRAY
// $selvals ARRAY
//
// ***produces:
// $calccolscounter -- count of the number of columns (fields) for which a calculation was requested 
// $sortcolscouter -- count of the number  of columns (fields) for which a sort was requested 
// $sortcols -- ARRAY, contains the IDs of the columns, as found in the reqFieldsJwe array
// $sortpri -- ARRAY, contains the sort priority setting for the column (indexed the same as sortcols)
// $sortdir -- ARRAY, contains the sort direction setting for the column (indexed the same as sortcols)
// $numcols -- a counter equal to the number of fields being displayed (minus 1 so it can be used as a max value for traversing the array)
// $colarrayname -- ARRAY, contains the "caption" name of each field.
//
// ***other:
// $calcFieldsJwe -- ARRAY, sent to template, not used anywhere else, contains the captions for the calculation fields
//
// *************


//run any sorts
include 'sort.php';

// *************
// sort.php docs:
//
// ***relies on:
// $sortcolscounter
// $sortcols ARRAY
// $sortpri ARRAY
// $sortdir ARRAY
// $colarrayname ARRAY
// $finalselectidreq ARRAY
//
// ***produces:
// $numcols -- OVERRIDE, remakes this variable for just the columns being sorted, then resets it back to its original value for use by the calculation routines
// $finalselectidreq -- OVERRIDE, ARRAY, rewrites this array so that the order of the IDs matches the sort order produced by the sort routine
// $selvals -- OVERRIDE, ARRAY, rewrites the array so that all the values are in the order determined by the sort priority and direction chosen by the user
//
// ***other:
// This routine is supposed to sort the columns in reverse order according to the user's priorities, so that first and second (and third...) sort priorities are handled correctly.  However in some situations, the secondary groupings don't seem to work correctly (ie: records with sequential values on a secondary sort field are not ordered correctly when they have the same value on the primary sort field).
// This routine should be switched to a simple PHP array_multisort function call, but there are issues that prevent that right now, in that not all the necessary things that need to be sorted could be passed into such a function.
// With a complete rewrite of how data is grabbed from the DB in the first place, and then how selvals is built, and how the search works, perhaps a more streamlined sorting could be built in?
// With a radical rewrite of how data is stored (one table per form) then many, many, many things could be handled by SQL directly in the queries, including sorting.
//
// *************


//Setup the arrays of the usernames and ids, plus data about the proxy status and delete status of the entries
array (entereduids);
	// GET UIDS AND NAMES FROM THE FORM_FORM TABLE...
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

// run any calculations that have been requested
include 'calc.php';

// ************
// calc.php docs:
//
// ***relies on:
// $calccolscounter
// $numcols
// $calc_typeArray ARRAY
// $colarrayname ARRAY
// $entereduids -- set in index.php just above
//
// ***other:
// $totalcalcoutput -- produces nothing used elsewhere, except the HTML code that is sent directly to the template as an array, with one value for each column to display 
//
// *************

// send all the final info to the template
include 'output.php';

// **************
// output.php docs:
// 
// ***relies on:
// $atleastonereq
// $finalselectidreq ARRAY
// $entereduids ARRAY -- defined in index.php above
// $gscopeparam
// $showscope
// $report_nove
// $report_calconly
// $report
// $reportingyn
// $id_form
// $proxystatus ARRAY -- defined in index.php above
// $tempcandel ARRAY -- defined in index.php above
// $entereddates ARRAY -- defined in index.php above
// $reqFieldsJwe ARRAY
// $selvals ARRAY
// $title
// $isadmin
// $theycanadd
// 
// ***Produces:
// $realusernames -- ARRAY, contains the full names of the users attached to each entry
//
// ***********

// prepare the data for exporting, create the file and send info to the export page template
include 'export.php';

// ***********
// export.php docs:
//
// ***relies on: 
// $realusernames ARRAY
// $reqFieldsJwe
// $selvals ARRAY
// $entereddates ARRAY -- defined in index.php above
//
// ***********

require(XOOPS_ROOT_PATH."/footer.php");

//*************************************************
// BELOW IS LOGIC FOR THE ADDING AN ENTRY PAGE
//*************************************************

} // end if that controls display of select-an-entry page -- jwe 7/24/04
else // we're drawing the form, not select entry page...
{


// setup the scope and current entry of a single-entry form
include 'setupsingle.php';

// *************
// setupsingle.php docs:
//
// ***relies on:
// $issingle
// $hasgroupscope
// $viewentry
// $id_form
// $uid
// $groupidadd ARRAY
// $groupuser ARRAY
//
// ***produces:
// $gscopeparam -- identical to the same named variable used above, used to identify which records are to be displayed to the user, based on the IDs of the users who created the records.
// $viewentry -- sets this to the first valid entry in the case of single-entry forms only.
//
// **************



// setup the default values of an entry that is being displayed
include 'gatherdefaults.php';

// ***************
// gatherdefaults.php docs:
//
// ***relies on:
// $viewentry
// 
// ***produces:
// $veuid -- the uid of the user who created the entry that is currently displayed
// $reqCaptionsJwe -- ARRAY, containing all the captions for the fields that have values in this entry for this form
// $reqValuesJwe -- ARRAY, containing all the values for fields in the currently displayed entry.
//
// **************

// start laying out the form by getting params, drawing title, etc
include 'startform.php';

// **************
// startform.php docs:
//
// ***relies on:
// $id_form
// $groupuser ARRAY
// $title
//
// ***produces:
// $module_id -- OVERRIDE, appears to repeat the setting of the module ID done way above
// 
// ***************

// if we're drawing the form, rather than reading data that has been submitted....
if( empty($_POST['submit']) ){

// read all the form elements and get any existing values that need to be displayed, and display them (including notification options)
include 'drawform.php';

// **************
// drawform.php docs: 
//
// ***relies on:
// $id_form
// $title
// $reportingyn
// $report
// $issingle
// $showviewentries
// $isadmin
// $viewentry
// $reqCaptionsJwe ARRAY
// $reqValuesJwe ARRAY
// $editingent
// $theycanadd
// $uid
// $veuid
// $groupuser ARRAY
// $groupidadd ARRAY
//
// ***other:
// the display of previous entries calls up the values of the entry and subs in those values in place of the default values for the fields in the form.  This is done by rewriting certain parts of the ele_value array (different elements store their defaults in different parts of the array).
//
// ***************

}else{ // if we've received data from a submission

// ********
// PROCESSING OF DATA THAT HAS BEEN SUBMITTED....
// ********

// prepare to read the data
include 'readwritedata.php';

// ************
// readwritedata.php docs:
//
// ***relies on:
// $value
// $realuid
// $veuid 
// $viewentry
// $reqCaptionsJwe ARRAY
// $id_form
// $uid
// $ele_id
// $ele_type
// $ele_caption
// $num_id
//
// ***produces:
// $num_id -- the value to put into the database as the 'ele_id' when storing the current field's data
// $id_form -- OVERRIDE, sets the value of this variable based on an object instantiated from the formulize_mgr class
// $value -- a variable that contains the value of a given field to store in the database
// $ele_id -- the unique ID of this field's entry in the DB if one exists
// $ele_type -- the type of element this field uses
// $ele_caption -- the caption for this field.  Note the apostrophe replacements used throughout the module.  This is due to the use of captions as key fields in the DB, which will be fixed in a future version.
// $msg -- content to be sent out in the e-mail copy of the entry that was just submitted.  Part of old formulaire functionality that has been commented. 
// $viewentry -- OVERRIDE, can set this to 0 in the case of a proxy entry
// $submittedcaptions -- ARRAY, an array that contains the list of captions (fields) where data was actually submitted by the user.  Used to identify which fields need to be "blanked" (ie: the user deleted the value that was in that field).
//
// *************

// setup the template for sending a mail message with the results, although the actual sending of the mail has been disabled
include 'setupmail.php';	

// **************
// setupmail.php docs:
// 
// ***relies on:
// $title
// $msg
// $admin
// $expe
// $groupe
// $email
//
// ****************



//setup the "thanks for submitting your info" message... 
	$sent = _formulize_INFO_RECEIVED;
	unlink($path);
	unset ($up);

}// END OF READING THE SUBMITTED ELEMENTS



//blank entries that need blanking (user cleared the selection in the form)
//and then redirect as appropriate, and handle notifications
include 'cleanup.php';

// ************
// cleanup.php docs: 
//
// ***relies on:
// $sent -- set above  
// $viewentry
// $realnamejwe
// $title
// $id_form
// $reqCaptionsJwe ARRAY
// $submittedcaptions ARRAY
// $issingle
// $editingent
// $report
// $reportingyn
// $num_id
// 
// ************

}// end of the main conditional that controls whether you're viewing an entry or adding/editing one.

?>