<?php
// ------------------------------------------------------------------------- 
//	pageworks
//		Copyright 2004,2005 Freeform Solutions
// 		
// ------------------------------------------------------------------------- //
//  This program is free software; you can redistribute it and/or modify     //
//  it under the terms of the GNU General Public License as published by     //
//  the Free Software Foundation; either version 2 of the License, or        //
//  (at your option) any later version.                                      //
//                                                                           //
//  You may not change or alter any portion of this comment or credits       //
//  of supporting developers from this source code or any supporting         //
//  source code which is considered copyrighted (c) material of the          //
//  original comment or credit authors.                                      //
//                                                                           //
//  This program is distributed in the hope that it will be useful,          //
//  but WITHOUT ANY WARRANTY; without even the implied warranty of           //
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            //
//  GNU General Public License for more details.                             //
//                                                                           //
//  You should have received a copy of the GNU General Public License        //
//  along with this program; if not, write to the Free Software              //
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA //
//  ------------------------------------------------------------------------ //


	print "<form name=displaybuttonform action=" . getCurrentUrl() . " method=post>\n";
		// write in receivers for the displayButtonProcess data
		print "<input type=hidden name=displayButtonProcessEle id=displayButtonProcessEle value=\"\">\n";
		print "<input type=hidden name=displayButtonProcessEntry id=displayButtonProcessEntry value=\"\">\n";
		print "<input type=hidden name=displayButtonProcessValue id=displayButtonProcessValue value=\"\">\n";
		print "<input type=hidden name=displayButtonProcessAppend id=displayButtonProcessAppend value=\"\">\n";
		print "<input type=hidden name=displayButtonProcessPrevValue id=displayButtonProcessPrevValue value=\"\">\n";
		print "<input type=hidden name=displayButtonProcessFormFrame id=displayButtonProcessFormFrame value=\"\">\n";
		print "</form>";
?>