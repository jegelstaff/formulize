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

?>