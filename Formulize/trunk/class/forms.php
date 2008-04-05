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
	function formulizeForm($id_form=""){

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
		} else {
			$formq = q("SELECT * FROM " . $xoopsDB->prefix("formulize_id") . " WHERE id_form=$id_form");
			if(!isset($formq[0])) {
				unset($formq);
				$id_form = "";
				$formq[0]['desc_form'] = "";
				$single = "";
				$elements = array();
				$elementCaptions = array();
				$elementColheads = array();
			} else {
				// gather element ids for this form
				$elementsq = q("SELECT ele_id, ele_caption, ele_colhead FROM " . $xoopsDB->prefix("formulize") . " WHERE id_form=$id_form AND ele_display != \"0\" ORDER BY ele_order ASC");
				foreach($elementsq as $row=>$value) {
					$elements[] = $value['ele_id'];
					$elementCaptions[] = $value['ele_caption'];
					$elementColheads[] = $value['ele_colhead'];
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
		$this->initVar("single", XOBJ_DTYPE_TXTBOX, $single, true, 5);
		$this->initVar("elements", XOBJ_DTYPE_ARRAY, serialize($elements));
		$this->initVar("elementCaptions", XOBJ_DTYPE_ARRAY, serialize($elementCaptions));
		$this->initVar("elementColheads", XOBJ_DTYPE_ARRAY, serialize($elementColheads));
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
	
	function &get($fid) {
		$fid = intval($fid);
		static $cachedForms = array();
		if(isset($cachedForms[$fid])) { return $cachedForms[$fid]; }
		if($fid > 0) {
			$cachedForms[$fid] = new formulizeForm($fid);
			return $cachedForms[$fid];
		}
		return false;
	}

	function getAllForms() {
		global $xoopsDB;
		$allFidsQuery = "SELECT id_form FROM " . $xoopsDB->prefix("formulize_id");
		$allFidsRes = $xoopsDB->query($allFidsQuery);
		$foundFids = array();
		while($allFidsArray = $xoopsDB->fetchArray($allFidsRes)) {
			$foundFids[] = $this->get($allFidsArray['id_form']);
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
		
}
?>