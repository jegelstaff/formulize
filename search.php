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

?>