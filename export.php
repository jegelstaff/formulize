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


if(isset($_POST['export'])) // write a file to the server and display a download link for it
{
	$fdchoice = $_POST['filedelimiter'];
	if($fdchoice == "comma") 
	{ 
		$fd = ",";
		$fxt = ".csv";
	}
	if($fdchoice == "tab")
	{
		$fd = "\t";
		$fxt = ".tabDelimited";
	}
	if($fdchoice == "custom")
	{
		$fd = $_POST['cusdel'];
		if(!$fd) { $fd = "*"; }
		$fxt = ".customDelimited";
	}
	$csvfile = "";
	$runtext = ""; // a variable that holds the field header for the user's full name, if such a thing is used for this query
	if($realusernames[0]) { $runtext = "User's Full Name" . $fd; } // if there are user full names, then make the field header
	$headercount = 0;
	foreach($reqFieldsJwe as $csvheader)
	{
		if(!$headercount)
		{
			$csvfile =  $runtext . "Modification Date" . $fd . $csvheader;
		}
		else
		{
			$csvfile .= $fd . $csvheader;
		}
		$headercount++;
	}

	$csvfile .= "\r\n";

	$colcounter = 0;
	$i=0;
	foreach($selvals as $acell)
	{
		$acell = str_replace("*=+*:", " ++ ", $acell); // replace the custom delimiter with ++
		if(!$colcounter)
		{
			if($realusernames[$i]) { $csvfile .= $realusernames[$i] . $fd; }
			$csvfile .= $entereddates[$i] . $fd . $acell;
		}
		else
		{
			$csvfile .= $fd . $acell;
		}
		$colcounter++;
		if($colcounter == $headercount)
		{
			$colcounter = 0; 
			$i++; // increment the counter used to pull in the right names and dates
			$csvfile .= "\r\n";
		}
	}
	$tempfold = time();
	$exfilename = _formulize_exfile . $tempfold . $fxt;
	// open the output file for writing
	$wpath = XOOPS_ROOT_PATH."/modules/formulize/export/$exfilename";
	//print $wpath;
	$exportfile = fopen($wpath, "w");
	fwrite ($exportfile, $csvfile);
	fclose ($exportfile);
	
	// need to add in logic to cull old files...

	$dlpath = XOOPS_URL . "/modules/formulize/export/$exfilename";
	$xoopsTpl->assign('dlpath', $dlpath);
	$xoopsTpl->assign('downloadtext', _formulize_DLTEXT);
	$xoopsTpl->assign('downloadtext', _formulize_DLTEXT);
	$xoopsTpl->assign('dlheader', _formulize_DLHEADER);
	$xoopsTpl->assign('exfilename', $exfilename);
}
?>