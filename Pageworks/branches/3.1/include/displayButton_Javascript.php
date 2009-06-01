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


	// write in displayButtonProcess javascript
	print "\n<script type='text/javascript'>\n";
	print "function displayButtonProcess(formframe, ele, entry, value, append, prevValue) {\n";
//	print "alert(ele + ', ' + entry + ', ' + value + ', ' + append + ', ' + prevValue + ', ' + formframe);\n"; // DEBUG LINE
	print "	window.document.displaybuttonform.displayButtonProcessEle.value = ele;\n";
	print "	window.document.displaybuttonform.displayButtonProcessEntry.value = entry;\n";
	print "	window.document.displaybuttonform.displayButtonProcessValue.value = value;\n";
	print "	window.document.displaybuttonform.displayButtonProcessAppend.value = append;\n";
	print "	window.document.displaybuttonform.displayButtonProcessPrevValue.value = prevValue;\n";
	print "	window.document.displaybuttonform.displayButtonProcessFormFrame.value = formframe;\n";
	print "	window.document.displaybuttonform.submit();\n";
	print "}\n";
	print "</script>\n";
?>