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

?>