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

// THE ACTUAL QUERY OF DATA FROM THE DATABASE TO DISPLAY ENTRIES
// apply the groupscope param to the query (query with the whole userlist)
if($gscopeparam)
{
	$queryjwe = "SELECT id_req FROM " . $xoopsDB->prefix("form_form") . " WHERE id_form=$id_form AND ($gscopeparam) $userreportingquery ORDER BY id_req";
}
else
{
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

?>