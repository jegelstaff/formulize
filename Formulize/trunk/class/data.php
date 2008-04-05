<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2006 Freeform Solutions                  ##
##                Portions copyright (c) 2003 NS Tai (aka tuff)              ##
##                       <http://www.brandycoke.com/>                        ##
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
##  Author of this file: Freeform Solutions                                  ##
##  Project: Formulize                                                       ##
###############################################################################

// require_once XOOPS_ROOT_PATH.'/kernel/object.php'; // xoops object not used
include_once XOOPS_ROOT_PATH.'/modules/formulize/include/functions.php';

class formulizeDataHandler  {
	
	var $fid; // the form this Data Handler object is attached to

	// $fid must be an id
	function formulizeDataHandler($fid){
		$this->fid = $fid;
	}
	
	// this function copies data from one form to another
	// sourceFid is the ID of the form that we're copying data from
	// elementMap is an array with keys being the IDs of the elements in the old form and the values are the corresponding IDs of the elements in the new form
	function cloneData($sourceFid, $elementMap) {
		global $xoopsDB;
		// 1. get all the data in the source form
		// 2. loop through it, and swap the old field names for the new ones
		// 3. write the data with new field names to the new datatable
		$sourceDataSQL = "SELECT * FROM " . $xoopsDB->prefix("formulize_".$sourceFid);
		if(!$sourceDataRes = $xoopsDB->query($sourceDataSQL)) {
			return false;
		}
		while($sourceDataArray = $xoopsDB->fetchArray($sourceDataRes)) {
			$start = true;
			$insertSQL = "INSERT INTO " . $xoopsDB->prefix("formulize_" . $this->fid) . " SET ";
			
			foreach($sourceDataArray as $field=>$value) {
				if(substr($field, 0, 8) == "element_") {
					$oldElementID = substr($field, 8);
					$newElementID = $elementMap[$oldElementID];
					$field = "element_" . $newElementID;
				}
				if($field == "entry_id") { $value = ""; } // use new ID numbers in the new table, in case there's ever a case where we're copying data into a table that already has data in it
				if(!$start) { $insertSQL .= ", "; }
				$insertSQL .= $field . " = \"" . mysql_real_escape_string($value) . "\"";
				$start = false;
			}
			if(!$insertResult = $xoopsDB->queryF($insertSQL)) {
				return false;
			}
		}
		return true;
	}

	
}
	
?>