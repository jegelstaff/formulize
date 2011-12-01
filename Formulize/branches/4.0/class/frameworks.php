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
		$notAFramework = false;
		if(!is_numeric($frid)) {
			// set empty defaults
			$notAFramework = true;
		} else {
			// check if framework_elements table exists first, since in 4.0 and higher, it should not.
			// but we'll keep it around if it did exist (prior to an upgrade) so we can check framework handles first when necessary
			$handles = array();
			$element_ids = array();
			if($GLOBALS['formulize_versionFourOrHigher'] == false) {
				$frame_elements_q = q("SELECT * FROM " . $xoopsDB->prefix("formulize_framework_elements") . " WHERE fe_frame_id=$frid");
				if(isset($frame_elements_q[0])) { // elements are not a required part of a framework...well, they should be, but if they're not defined, that doesn't mean the rest of the data is invalid, so don't call NotAFramework on this framework
					foreach($frame_elements_q as $row=>$value) {
						$handles[$value['fe_element_id']] = $value['fe_handle'];
						$element_ids[$value['fe_handle']] = $value['fe_element_id'];
					}
				}
			}
			$frame_links_q = q("SELECT * FROM " . $xoopsDB->prefix("formulize_framework_links") . " WHERE fl_frame_id=\"" . mysql_real_escape_string($frid). "\"");
			if(!isset($frame_links_q[0])) {
				$notAFramework = true;
			} else {
				$links = array();
				$fids = array();
				foreach($frame_links_q as $row=>$value) {
					$links[] = new formulizeFrameworkLink($value['fl_id']);
					// note that you cannot query the framework_forms table to learn what forms are in a framework, since we keep entries in that table after links have been deleted, since forms might rejoin a framework and we don't want to lose their information.  The links table is the only authoritative source of information about what forms make up a framework.
					$fids[] = $value['fl_form1_id'];
					$fids[] = $value['fl_form2_id'];
				}
				$fids = array_unique($fids);
			}
			$frame_name_q = q("SELECT * FROM " . $xoopsDB->prefix("formulize_frameworks") . " WHERE frame_id=$frid");
			if(!isset($frame_name_q[0])) {
				$notAFramework = true;
			}
			$formHandles = array();
			if(in_array($xoopsDB->prefix("formulize_framework_forms"), $existingTables)) {
				$frame_form_handles_q = q("SELECT * FROM " . $xoopsDB->prefix("formulize_framework_forms") . " WHERE ff_frame_id=$frid");
				if(!isset($frame_form_handles_q[0])) {
					$notAFramework = true;
				} else {
					$formHandles = array();
					foreach($frame_form_handles_q as $row=>$value) {
						if($fidKey = array_search($value['ff_form_id'], $fids)) { // find this form in the fids array, and use that fid as the key to access this form's handle.  Remember, not all forms in this table are actually in the Framework, so we have to check.
							$formHandles[$fids[$fidKey]] = $value['ff_handle'];
						}
					}
				}
			}
					
		}
		if($notAFramework) { list($frid, $fids, $name, $handles, $element_ids, $links, $formHandles) = $this->initializeNull(); }

		$this->XoopsObject();
		//initVar params: key, data_type, value, req, max, opt
		$this->initVar("frid", XOBJ_DTYPE_INT, $frid, false);
		$this->initVar("fids", XOBJ_DTYPE_ARRAY, serialize($fids));
		$this->initVar("name", XOBJ_DTYPE_TXTBOX, $frame_name_q[0]['frame_name'], true, 255);
		$this->initVar("element_ids", XOBJ_DTYPE_ARRAY, serialize($element_ids));
		$this->initVar("handles", XOBJ_DTYPE_ARRAY, serialize($handles));
		$this->initVar("links", XOBJ_DTYPE_ARRAY, serialize($links));
		$this->initVar("formHandles", XOBJ_DTYPE_ARRAY, serialize($formHandles));
	}

	function initializeNull() {
		$ret[] = 0; // frid
		$ret[] = array(); //fids
		$ret[] = ""; // name
		$ret[] = array(); // handles 
		$ret[] = array(); // element_ids
		$ret[] = array(); // links
		$ret[] = array(); // formHandles
		return $ret;
	}

	// this method returns either "one" or "many" or "onetoone" to indicate if a given handle is on a one side or a many side of the relationship in the framework
	function whatSideIsHandleOn($key) {
		static $cachedHandles = array();
		if(!isset($cachedHandles[$key])) {
			
			// 1. figure out the form of the $key that was passed
			// 2. check the form1 and form2 properties of the links to see which side that form is on
			$element_handler = xoops_getmodulehandler('elements', 'formulize');
			$elementObject = $element_handler->get($key);
			$targetFid = $elementObject->getVar('id_form');
			
			foreach($this->getVar('links') as $thisLink) {
				if($thisLink->getVar('form1') == $targetFid) {
					switch($thisLink->getVar('relationship')) {
						case 1:
							$cachedHandles[$key] = "onetoone";
							break;
						case 2:
							$cachedHandles[$key] = "one";
							break;
						case 3:
							$cachedHandles[$key] = "many";
							break;
					}
				} elseif($thisLink->getVar('form2') == $targetFid) {
					switch($thisLink->getVar('relationship')) { // when the form is the second one listed, then 1 and 3 (one to one and many to one) result in "one" being the correct response, while 2 (one to many) results in "many"
						case 1:
							$cachedHandles[$key] = "onetoone";
							break;
						case 2:
							$cachedHandles[$key] = "many";
							break;
						case 3:
							$cachedHandles[$key] = "one";
							break;
					}
				}
				if(isset($cachedHandles[$key])) {
					break;
				}
			}
		}
		return $cachedHandles[$key];
	}

}

class formulizeFrameworkLink extends XoopsObject {
	function formulizeFrameworkLink($lid=""){
		
		// validate $lid
		global $xoopsDB;
		if(!is_numeric($lid)) {
			// set empty defaults
			$lid = "";
			$frid = "";
			$form1 = "";
			$form2 = "";
			$key1 = "";
			$key2 = "";
			$common = "";
			$relationship = "";
			$unified_display = "";
		} else {		
			$link_q = q("SELECT * FROM " . $xoopsDB->prefix("formulize_framework_links") . " WHERE fl_id=\"" . mysql_real_escape_string($lid). "\"");
			if(!isset($link_q[0])) {
				// set empty defaults
  			$lid = "";
				$frid = "";
				$form1 = "";
				$form2 = "";
				$key1 = "";
				$key2 = "";
				$common = "";
				$relationship = "";
				$unified_display = "";
			} else {
  			$lid = $lid;
				$frid = $link_q[0]['fl_frame_id'];
				$form1 = $link_q[0]['fl_form1_id'];
				$form2 = $link_q[0]['fl_form2_id'];
				$key1 = $link_q[0]['fl_key1'];
				$key2 = $link_q[0]['fl_key2'];
				$common = $link_q[0]['fl_common_value'];
				$relationship = $link_q[0]['fl_relationship'];
				$unified_display = $link_q[0]['fl_unified_display'];
			}
		}

		$this->XoopsObject();
		//initVar params: key, data_type, value, req, max, opt
		$this->initVar("lid", XOBJ_DTYPE_INT, $lid, true);
		$this->initVar("frid", XOBJ_DTYPE_INT, $frid, true);
		$this->initVar("form1", XOBJ_DTYPE_INT, $form1, true);
		$this->initVar("form2", XOBJ_DTYPE_INT, $form2, true);
		$this->initVar("key1", XOBJ_DTYPE_INT, $key1, true);
		$this->initVar("key2", XOBJ_DTYPE_INT, $key2, true);
		$this->initVar("common", XOBJ_DTYPE_INT, $common, true);
		$this->initVar("relationship", XOBJ_DTYPE_INT, $relationship, true);
		$this->initVar("unifiedDisplay", XOBJ_DTYPE_INT, $unified_display, true);
	}
	
	
	
}

class formulizeFrameworksHandler {
	var $db;
	function formulizeFrameworksHandler(&$db) {
		$this->db =& $db;
	}
	function &getInstance(&$db) {
		static $instance;
		if (!isset($instance)) {
			$instance = new formulizeFrameworksHandler($db);
		}
		return $instance;
	}
	function &create() {
		return new formulizeFramework();
	}

	function insert(&$framework) {
		if(!is_object($framework) OR get_class($framework) != 'formulizeFramework') { return false; }
		if(!$framework->getVar('frid')) {
			$sql = "INSERT INTO ".$this->db->prefix("formulize_frameworks")." (`frame_name`) VALUES (".$this->db->quoteString($framework->getVar('name')).")";
			if(!$res = $this->db->query($sql)) {
				return false;
			}
			$frid = $this->db->getInsertId();
			$framework->setVar('frame_id',$frid);
		} else {
			$sql = "UPDATE ".$this->db->prefix("formulize_frameworks")." SET `frame_name` = ".$this->db->quoteString($framework->getVar('name'))." WHERE `frame_id` = ".intval($framework->getVar('frid'));
			if(!$res = $this->db->query($sql)) {
				return false;
			}
			$frid = $framework->getVar('frid');
		}
		return $frid;
	}

	function delete($framework) {
		if(!is_object($framework) OR get_class($framework) != 'formulizeFramework') { return false; }
		$sql = array();
		$sql[] = "DELETE FROM ".$this->db->prefix("formulize_frameworks")." WHERE `frame_id` = ".intval($framework->getVar('frid'));
		$sql[] = "DELETE FROM ".$this->db->prefix("formulize_framework_links")." WHERE `fl_frame_id` = ".intval($framework->getVar('frid'));
		$success = true;
		foreach($sql as $thisSql) {
			if(!$res = $this->db->query($thisSql)) {
				$success = false;
			}
		}
		return $success;
	}


	function get($frid) {
		$frid = intval($frid);
		static $cachedFrameworks = array();
		if(isset($cachedFrameworks[$frid])) {
			return $cachedFrameworks[$frid];
		}
		if($frid > 0) {
			$cachedFrameworks[$frid] = new formulizeFramework($frid);
			return $cachedFrameworks[$frid];
		}
		return false;
	}

	function getFrameworksByForm($fid) {
		static $cachedResults = array();
		if(isset($cachedResults[$fid])) { return $cachedResults[$fid]; }
		$ret = array();
		$sql = 'SELECT DISTINCT(fl_frame_id) FROM '.$this->db->prefix("formulize_framework_links").' WHERE fl_form1_id='.intval($fid).' OR fl_form2_id='.intval($fid);

		$result = $this->db->query($sql);

		while( $myrow = $this->db->fetchArray($result) ){
			$framework = new formulizeFramework($myrow['fl_frame_id']);
			$ret[$framework->getVar('frid')] =& $framework;
			unset($framework);
		}
		$cachedResults[$fid] = $ret;
		return $ret;

	}

  


}
?>
