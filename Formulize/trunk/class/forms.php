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

class formulizeForm extends XoopsObject {
	function formulizeForm($id_form="", $includeAllElements=false){

		// validate $id_form
		global $xoopsDB;

		if(!is_numeric($id_form)) {
			// set empty defaults
			$id_form = "";
			$formq[0]['desc_form'] = "";
			$single = "";
			$elements = array();
			$elementCaptions = array();
			$elementColheads = array();
			$elementHandles = array();
		} else {
			$formq = q("SELECT * FROM " . $xoopsDB->prefix("formulize_id") . " WHERE id_form=$id_form");
			if(!isset($formq[0])) {
				unset($formq);
				$id_form = "";
				$formq[0]['desc_form'] = "";
				$formq[0]['tableform'] = "";
				$single = "";
				$elements = array();
				$elementCaptions = array();
				$elementColheads = array();
				$elementHandles = array();
				$elementTypes = array();
			} else {
				// gather element ids for this form
				$displayFilter = $includeAllElements ? "" : "AND ele_display != \"0\"";
				$elementsq = q("SELECT ele_id, ele_caption, ele_colhead, ele_handle, ele_type FROM " . $xoopsDB->prefix("formulize") . " WHERE id_form=$id_form $displayFilter ORDER BY ele_order ASC");
				foreach($elementsq as $row=>$value) {
					$elements[$value['ele_id']] = $value['ele_id'];
					$elementCaptions[$value['ele_id']] = $value['ele_caption'];
					$elementColheads[$value['ele_id']] = $value['ele_colhead'];
					$elementHandles[$value['ele_id']] = $value['ele_handle'];
					$elementTypes[$value['ele_id']] = $value['ele_type'];
				}
				// propertly format the single value

				switch($formq[0]['singleentry']) {
					case "group":
						$single = "group";
						break;
					case "on":
						$single = "user";
						break;
					case "":
						$single = "off";
						break;
					default:
						$single = "";
						break;
				}
			}
			$viewq = q("SELECT * FROM " . $xoopsDB->prefix("formulize_saved_views") . " WHERE sv_mainform = '$id_form' OR (sv_mainform = '' AND sv_formframe = '$id_form')");
			if(!isset($viewq[0])) {
				$views = array();
				$viewNames = array();
				$viewFrids = array();
				$viewPublished = array();
			} else {
				for($i=0;$i<count($viewq);$i++) {
					$views[$i] = $viewq[$i]['sv_id'];
					$viewNames[$i] = stripslashes($viewq[$i]['sv_name']);
					$viewFrids[$i] = $viewq[$i]['sv_mainform'] ? $viewq[$i]['sv_formframe'] : "";
					$viewPublished[$i] = $viewq[$i]['sv_pubgroups'] ? true : false;
				}
			}
		}

		$this->XoopsObject();
		//initVar params: key, data_type, value, req, max, opt
		$this->initVar("id_form", XOBJ_DTYPE_INT, $id_form, true);
		$this->initVar("title", XOBJ_DTYPE_TXTBOX, $formq[0]['desc_form'], true, 255);
		$this->initVar("tableform", XOBJ_DTYPE_TXTBOX, $formq[0]['tableform'], true, 255);
		$this->initVar("single", XOBJ_DTYPE_TXTBOX, $single, true, 5);
		$this->initVar("elements", XOBJ_DTYPE_ARRAY, serialize($elements));
		$this->initVar("elementCaptions", XOBJ_DTYPE_ARRAY, serialize($elementCaptions));
		$this->initVar("elementColheads", XOBJ_DTYPE_ARRAY, serialize($elementColheads));
		$this->initVar("elementHandles", XOBJ_DTYPE_ARRAY, serialize($elementHandles));
		$this->initVar("elementTypes", XOBJ_DTYPE_ARRAY, serialize($elementTypes));
		$this->initVar("views", XOBJ_DTYPE_ARRAY, serialize($views));
		$this->initVar("viewNames", XOBJ_DTYPE_ARRAY, serialize($viewNames));
		$this->initVar("viewFrids", XOBJ_DTYPE_ARRAY, serialize($viewFrids));
		$this->initVar("viewPublished", XOBJ_DTYPE_ARRAY, serialize($viewPublished));
	}
}

class formulizeFormsHandler {
	var $db;
	function formulizeFormsHandler(&$db) {
		$this->db =& $db;
	}
	function &getInstance(&$db) {
		static $instance;
		if (!isset($instance)) {
			$instance = new formulizeFormsHandler($db);
		}
		return $instance;
	}
	function &create() {
		return new formulizeForm();
	}
	
	function &get($fid,$includeAllElements=false) {
		$fid = intval($fid);
		static $cachedForms = array();
		if(isset($cachedForms[$fid])) { return $cachedForms[$fid]; }
		if($fid > 0) {
			$cachedForms[$fid] = new formulizeForm($fid,$includeAllElements);
			return $cachedForms[$fid];
		}
		return false;
	}

	function getAllForms($includeAllElements=false) {
		global $xoopsDB;
		$allFidsQuery = "SELECT id_form FROM " . $xoopsDB->prefix("formulize_id");
		$allFidsRes = $xoopsDB->query($allFidsQuery);
		$foundFids = array();
		while($allFidsArray = $xoopsDB->fetchArray($allFidsRes)) {
			$foundFids[] = $this->get($allFidsArray['id_form'],$includeAllElements);
		}
		return $foundFids;
	}
		
	// accepts a framework object or frid
	function getFormsByFramework($framework_Object_or_Frid) {
		if(is_object($framework_Object_or_Frid)) {
			if(get_class($framework_Object_or_Frid) == "formulizeFramework") {
				$frameworkObject = $framework_Object_or_Frid;
			} else {
				return false;
			}
		} elseif(is_numeric($framework_Object_or_Frid)) {
			include_once XOOPS_ROOT_PATH . "/modules/formulize/class/frameworks.php";
			$frameworkObject = new formulizeFramework($frid);
		} else {
			return false;
		}
		$fids = array();
		foreach($frameworkObject->getVar('fids') as $thisFid) {
			$fids[] = $this->get($thisFid);
		}
		return $fids;
	}
		
	// check to see if a handle is unique within a form
	function isHandleUnique($handle, $element_id="") {
		$ucHandle = strtoupper($handle);
		if($ucHandle == "CREATION_UID" OR $ucHandle == "CREATION_DATETIME" OR $ucHandle == "MOD_UID" OR $ucHandle == "MOD_DATETIME" OR $ucHandle == "CREATOR_EMAIL" OR $ucHandle == "UID" OR $ucHandle == "PROXYID" OR $ucHandle == "CREATION_DATE" OR $ucHandle == "MOD_DATE" OR $ucHandle == "MAIN_EMAIL" OR $ucHandle == "MAIN_USER_VIEWEMAIL") {
			return false; // don't allow reserved words that will be used in the main data extraction queries
		}
		global $xoopsDB;
		$element_id_condition = $element_id ? " AND ele_id != " . intval($element_id) : "";
		$sql = "SELECT count(ele_handle) FROM " . $xoopsDB->prefix("formulize") . " WHERE ele_handle = '" . mysql_real_escape_string($handle) . "' $element_id_condition";
		if(!$res = $xoopsDB->query($sql)) {
			print "Error: could not verify uniqueness of handle '$handle' in form $fid";
		} else {
			$row = $xoopsDB->fetchRow($res);
			if($row[0] == 0) { // zero rows found with that handle in this form
				return true;
			} else {
				return false;
			}
		}
	}
			
	// create a data table for a form object (or form)
	// $fid can be an id or an object
	function createDataTable($fid) {
		if(is_numeric($fid)) {
			$fid = $this->get($fid);
		} elseif(!get_class($fid) == "formulizeForm") {
			return false;
		}
		$elementTypes = $fid->getVar('elementTypes');
		global $xoopsDB;
		// build SQL for new table
                $newTableSQL = "CREATE TABLE " . $xoopsDB->prefix("formulize_" . $fid->getVar('id_form')) . " (";
                $newTableSQL .= "entry_id int(7) unsigned NOT NULL auto_increment,";
                $newTableSQL .= "creation_datetime Datetime NULL default NULL, ";
                $newTableSQL .= "mod_datetime Datetime NULL default NULL, ";
                $newTableSQL .= "creation_uid int(7) default '0',";
                $newTableSQL .= "mod_uid int(7) default '0',";
                foreach($fid->getVar('elementHandles') as $elementId=>$thisHandle) {
												if($elementTypes[$elementId] == "areamodif" OR $elementTypes[$elementId] == "ib" OR $elementTypes[$elementId] == "sep" OR $elementTypes[$elementId] == "grid" OR $elementTypes[$elementId] == "subform") { continue; } // do not attempt to create certain types of fields since they don't live in the db!
                        $newTableSQL .= "`$thisHandle` text NULL default NULL,";
                }
                $newTableSQL .= "PRIMARY KEY (`entry_id`),";
                $newTableSQL .= "INDEX i_creation_uid (creation_uid)";
                $newTableSQL .= ") TYPE=MyISAM;";
                // make the table
                if(!$tableCreationRes = $xoopsDB->queryF($newTableSQL)) {
			return false;
                }
		return true;
	}

	// drop the data table
	// fid can be an id or object
	function dropDataTable($fid) {
		if(is_object($fid)) {
			if(!get_class("formulizeForm")) {
				return false;
			}
			$fid = $fid->getVar('id_form');
		} elseif(!is_numeric($fid)) {
			return false;
		}
		global $xoopsDB;
		$dropSQL = "DROP TABLE " . $xoopsDB->prefix("formulize_" . $fid);
		if(!$dropRes = $xoopsDB->queryF($dropSQL)) {
			return false;
		}
		// remove the entry owner groups info for that form
		$ownershipSQL = "DELETE FROM " . $xoopsDB->prefix("formulize_entry_owner_groups") . " WHERE fid=$fid";
		if(!$ownershipSQLRes = $xoopsDB->queryF($ownershipSQL)) {
			print "error: could not remove entry ownership data for form $fid";
		}
		return true;
	}
	
	// this function deletes an element field from the data table
	// $id can be numeric or an object
	function deleteElementField($element) {
		if(!$element = _getElementObject($element)) {
			return false;
		}
		global $xoopsDB;
		$deleteFieldSQL = "ALTER TABLE " . $xoopsDB->prefix("formulize_" . $element->getVar('id_form')) . " DROP `" . $element->getVar('ele_handle') . "`";
		if(!$deleteFieldRes = $xoopsDB->queryF($deleteFieldSQL)) {
			return false;
		}
		return true;
	}
	
	// this function adds an element field to the data table
	// $id can be numeric or an object
	function insertElementField($element) {
		if(!$element = _getElementObject($element)) {
			return false;
		}
		global $xoopsDB;
		$insertFieldSQL = "ALTER TABLE " . $xoopsDB->prefix("formulize_" . $element->getVar('id_form')) . " ADD `" . $element->getVar('ele_handle') . "` text NULL default NULL";
		if(!$insertFieldRes = $xoopsDB->queryF($insertFieldSQL)) {
			return false;
		}
		return true;
	}
	
	// update the field name in the datatable.  $element can be an id or an object.
	function updateFieldName($element, $oldname) {
		if(!$element = _getElementObject($element)) {
			return false;
		}
		global $xoopsDB;
		$updateFieldSQL = "ALTER TABLE " . $xoopsDB->prefix("formulize_" . $element->getVar('id_form')) . " CHANGE `".$oldname."` `".$element->getVar('ele_handle')."` text NULL default NULL";
		if(!$updateFieldRes = $xoopsDB->queryF($updateFieldSQL)) {
			return false;
		}
		return true;
	}
	
}
?>