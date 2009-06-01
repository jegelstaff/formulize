<?php
// ------------------------------------------------------------------------- 
//	pageworks
//		Copyright 2004, Freeform Solutions
// 		
//	Template
//		Copyright 2004 Thomas Hill
//		<a href="http://www.worldware.com">worldware.com</a>
// ------------------------------------------------------------------------- 
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

// File must be included only once since this logic should only execute once per page load.

// 5.6 handle displayButtons...
// look for passed back:
// $_POST['displayButtonProcessEle'] -- element that is being handled
// $_POST['displayButtonProcessEntry'] -- entry that is being handled (can be "new" to indicate a new entry)
// $_POST['displayButtonProcessValue'] -- the value to put in this element in this entry
// $_POST['displayButtonProcessAppend'] -- flag indicating whether the value replaces the current value or gets appended to it
// $_POST['displayButtonProcessPrevValue'] -- a flag indicating whether there is an existing value for this element in this entry
// $_POST['displayButtonProcessFormFrame'] -- a flag indicating whether there is a form framework

//writeElementValue($_POST['displayButtonProcessEle'], $_POST['displayButtonProcessEntry'], $_POST['displayButtonProcessValue'], $_POST['displayButtonProcessAppend'], $_POST['displayButtonProcessPrevValue']);
if(get_magic_quotes_gpc()) { $_POST['displayButtonProcessValue'] = stripslashes($_POST['displayButtonProcessValue']); }
include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";
$writtenIdReq = writeElementValue($_POST['displayButtonProcessFormFrame'], $_POST['displayButtonProcessEle'], $_POST['displayButtonProcessEntry'], $_POST['displayButtonProcessValue'], $_POST['displayButtonProcessAppend'], $_POST['displayButtonProcessPrevValue']);

?>