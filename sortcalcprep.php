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

?>