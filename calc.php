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

					if(strstr($thiscalcArray, "count")) // if we're counting and NOT percentaging...
					{

					$percentcount = round(($nonblanks/$countvals)*100, 2);

					$theuniquekeys = array_keys($valdist);
					$countuniquekeys = count($theuniquekeys);

					// count the unique number of users who have created entries
					// 1. get array of user ids
					// 2. count unique values
					// 3. write out results to screen

					$uniqueEnteredIds = array_unique($entereduids);
					$nonuniqueCount = count($entereduids);
					$uniqueCount = count($uniqueEnteredIds);
			
					$calcoutput .= "<h4>" . _formulize_COUNT . ":</h4>";
					$calcoutput .= "<ul><li>" . _formulize_COUNT_UNIQUEUSERS . " $uniqueCount<br>&nbsp;</li>";
					$calcoutput .= "<li>" . _formulize_COUNT_ENTRIES . " $nonuniqueCount</li>";
					$calcoutput .= "<li>" . _formulize_COUNT_NONBLANKS . " $noblankscounter<br>&nbsp;</li>";
					$calcoutput .= "<li>" . _formulize_COUNT_INCLBLANKS . " $countvals</li>";
					$calcoutput .= "<li>" . _formulize_COUNT_UNIQUES . " $countuniquekeys</li>";		
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

?>