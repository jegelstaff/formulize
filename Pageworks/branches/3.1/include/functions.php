<?php

###############################################################################
##             Pageworks - page logic and display module for XOOPS           ##
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
##  Project: Pageworks                                                       ##
###############################################################################

// THIS FILE CONTAINS FUNCTIONS RELATED TO THE GENERAL OPERATION OF THE PAGEWORKS MODULE
// Primarily concerned with the logging of stats initially

// This function returns the current user, plus the current date and the current time
function getUserDateTime() {
	global $xoopsUser;
	$uid = $xoopsUser ? $xoopsUser->getVar('uid') : '0';
	$now = getDate();
	$date = $now['year'] . "-" . $now['mon'] . "-" . $now['mday'];
	$time = $now['hours'] . ":" . $now['minutes'] . ":" . $now['seconds'];
	$to_return['uid'] = $uid;
	$to_return['date'] = $date;
	$to_return['time'] = $time;
	return $to_return;
}

// GET THE MODULE ID -- specifically get Pageworks' module ID from DB
function getPageworksModId() {
	global $xoopsDB;
	static $mid = "";
	if(!$mid) {
		$res4 = $xoopsDB->query("SELECT mid FROM ".$xoopsDB->prefix("modules")." WHERE dirname='pageworks'");
		if ($res4) {
			while ($row = $xoopsDB->fetchRow($res4))
				$mid = $row[0];
		}
	}
	return $mid;
}

// This function writes log info to the DB
function writeLogEntry($item, $uid, $date, $time) {
	global $xoopsDB;
	$item = addslashes($item);
	$sql = "INSERT INTO " . $xoopsDB->prefix("pageworks_log") . " (log_item, log_uid, log_date, log_time) VALUES (\"$item\", \"$uid\", \"$date\", \"$time\")";
	if(!$result = $xoopsDB->queryF($sql)) {
		exit("Error writing log info to the database for item: $item");
	}
}


?>