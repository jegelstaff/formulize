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

require_once XOOPS_ROOT_PATH.'/kernel/object.php';
include_once XOOPS_ROOT_PATH.'/modules/formulize/include/functions.php';

class formulizeFramework extends XoopsObject {
	function formulizeFramework($frid=""){

		// validate $id_form
		global $xoopsDB;
		if(!is_numeric($frid)) {
			// set empty defaults
			$handles = array();
			$element_ids = array();
			$links = array();
		} else {
			$frame_elements_q = q("SELECT * FROM " . $xoopsDB->prefix("formulize_framework_elements") . " WHERE fe_frame_id=$frid");
			if(!isset($frame_elements_q[0])) {
				$handles = array();
				$element_ids = array();
				$links = array();
			} else {
				foreach($frame_elements_q as $row=>$value) {
					$handles[$value['fe_element_id']] = $value['fe_handle'];
					$element_ids[$value['fe_handle']] = $value['fe_element_id'];
				}
			}
			$frame_links_q = q("SELECT * FROM " . $xoopsDB->prefix("formulize_framework_links") . " WHERE fl_frame_id=\"" . mysql_real_escape_string($frid). "\"");
			if(!isset($frame_links_q[0])) {
				$handles = array();
				$element_ids = array();
				$links = array();
			} else {
				foreach($frame_links_q as $row=>$value) {
					$links[] = new formulizeFrameworkLink($value['fl_id']);
				}						
			}
		}

		$this->XoopsObject();
		//initVar params: key, data_type, value, req, max, opt
		$this->initVar("element_ids", XOBJ_DTYPE_ARRAY, serialize($element_ids));
		$this->initVar("handles", XOBJ_DTYPE_ARRAY, serialize($handles));
		$this->initVar("links", XOBJ_DTYPE_ARRAY, serialize($links));
	}
}

class formulizeFrameworkLink extends XoopsObject {
	function formulizeFrameworkLink($lid=""){
		
		// validate $lid
		global $xoopsDB;
		if(!is_numeric($lid)) {
			// set empty defaults
			$frid = "";
			$form1 = "";
			$form2 = "";
			$key1 = "";
			$key2 = "";
			$common = "";
			$relationship = "";
		} else {		
			$link_q = q("SELECT * FROM " . $xoopsDB->prefix("formulize_framework_links") . " WHERE fl_id=\"" . mysql_real_escape_string($lid). "\"");
			if(!isset($link_q[0])) {
				// set empty defaults
				$frid = "";
				$form1 = "";
				$form2 = "";
				$key1 = "";
				$key2 = "";
				$common = "";
				$relationship = "";
			} else {
				$frid = $link_q[0]['fl_frame_id'];
				$form1 = $link_q[0]['fl_form1_id'];
				$form2 = $link_q[0]['fl_form2_id'];
				$key1 = $link_q[0]['fl_key1'];
				$key2 = $link_q[0]['fl_key2'];
				$common = $link_q[0]['fl_common_value'];
				$relationship = $link_q[0]['fl_relationship'];
			}
		}

		$this->XoopsObject();
		//initVar params: key, data_type, value, req, max, opt
		$this->initVar("frid", XOBJ_DTYPE_INT, $frid, true);
		$this->initVar("form1", XOBJ_DTYPE_INT, $form1, true);
		$this->initVar("form2", XOBJ_DTYPE_INT, $form2, true);
		$this->initVar("key1", XOBJ_DTYPE_INT, $key1, true);
		$this->initVar("key2", XOBJ_DTYPE_INT, $key2, true);
		$this->initVar("common", XOBJ_DTYPE_INT, $common, true);
		$this->initVar("relationship", XOBJ_DTYPE_INT, $relationship, true);
	}
}
?>