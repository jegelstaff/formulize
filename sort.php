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

?>