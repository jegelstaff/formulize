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



// control below should only kick in the blanking logic and redirects when we're ready to leave the page -- jwe 7/25/04
if($sent) // if $sent is set, ie we're ready to leave... WILL ALWAYS BE TRUE!  Should be removed.
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

?>