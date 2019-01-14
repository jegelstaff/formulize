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
	function __construct($frid=""){

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
			$frame_links_q = q("SELECT * FROM " . $xoopsDB->prefix("formulize_framework_links") . " WHERE fl_frame_id=\"" . formulize_db_escape($frid). "\"");
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
					
		}
		if($notAFramework) { list($frid, $fids, $name, $handles, $element_ids, $links, $formHandles) = $this->initializeNull(); }

        parent::__construct();
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


    function __get($name) {
        if (!isset($this->$name)) {
            if (method_exists($this, $name)) {
                $this->$name = $this->$name();
            } else {
                $this->$name = $this->getVar($name);
            }
        }
        return $this->$name;
    }
}


class formulizeFrameworkLink extends XoopsObject {
	function __construct($lid=""){
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
			$link_q = q("SELECT * FROM " . $xoopsDB->prefix("formulize_framework_links") . " WHERE fl_id = \"" . formulize_db_escape($lid). "\"");
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
				$unified_delete = "";
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
				$unified_delete = $link_q[0]['fl_unified_delete'];
			}
		}

        parent::__construct();
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
		$this->initVar("unified_delete", XOBJ_DTYPE_INT, $unified_delete, true);
	}


    function main_form() {
        $form_handler = xoops_getmodulehandler('forms', 'formulize');
        return $form_handler->get($this->getVar('form1'));
    }


    function linked_form() {
        $form_handler = xoops_getmodulehandler('forms', 'formulize');
        return $form_handler->get($this->getVar('form2'));
    }


    function link_selected() {
        return "{$this->key1}+{$this->key2}";
    }


    function link_options() {
        global $xoopsDB;

        $elements = array();

        // initialize the class that can read the ele_value field
        $formulize_mgr =& xoops_getmodulehandler('elements');

        // get a list of all the linked select boxes since we need to know if any fields in these two forms are the source for any links
        $resgetlinksq = $xoopsDB->query("SELECT id_form, ele_caption, ele_id, ele_handle FROM " . $xoopsDB->prefix("formulize") . " WHERE ele_type=\"select\" AND ele_value LIKE '%#*=:*%' ORDER BY id_form");
        while ($rowlinksq = $xoopsDB->fetchRow($resgetlinksq)) {
            $target_form_ids[] = $rowlinksq[0];
            $target_captions[] = $rowlinksq[1];
            $target_ele_ids[] = $rowlinksq[2];

            // returns an object containing all the details about the form
            $elements =& $formulize_mgr->getObjects($criteria, $rowlinksq[0]);

            // search for the elements where the link exists
            foreach ($elements as $e) {
                $ele_id = $e->getVar('ele_id');
                // if this is the right element, then proceed and get the source of the link
                if ($ele_id == $rowlinksq[2]) {
                    $ele_value = $e->getVar('ele_value');
                    $details = explode("#*=:*", $ele_value[2]);
                    $source_form_ids[] = $details[0];

                    //get the element ID for the source we've just found
                    $sourceq = "SELECT ele_id, ele_caption FROM " . $xoopsDB->prefix("formulize") . " WHERE ele_handle = '" . formulize_db_escape($details[1]) . "' AND id_form = '$details[0]'";
                    if ($ressourceq = $xoopsDB->query($sourceq)) {
                        $rowsourceq = $xoopsDB->fetchRow($ressourceq);
                        $source_ele_ids[] = $rowsourceq[0];
                        $source_captions[] = $rowsourceq[1];
                    } else {
                        print "Error:  Query failed.  Searching for element ID for the caption $details[1] in form $details[0]";
                    }
                }
            }
        }

        // Arrays now set as follows:
        // target_form_ids == the ID of the form where the current linked selectbox resides
        // target_captions == the caption of the current linked selectbox
        // target_ele_ids == the element ID of the current linked selectbox
        // source_form_ids == the ID of the form where the source for the current linked selectbox resides
        // source_captions == the caption of the source for the current linked selectbox
        // source_ele_ids == the element ID of the source for the current linked selectbox

        // each index in those arrays denotes a distinct linked selectbox

        // example:
        // target_form_ids == 11
        // target_captions == Link to Name
        // target_ele_ids == 22
        // source_form_ids == 10
        // source_captions == Name
        // source_ele_ids == 20

        //determine the contents of the linkage box
        //find all links between these forms, but add User ID as the top value in the box
        // 1. Find all target links for form 1
        // 2. Check if the source is form 2
        // 3. If yes, add to the stack
        // 4. Repeat for form 2, looking for form 1
        // 5. Draw entries in box as follows:
        // form 1 field name/form 2 field name
        // 6. Account for the current link if one is specified, and make that the default selection

        $hits12 = $this->_findlink($this->getVar('form1'), $this->getVar('form2'), $target_form_ids, $source_form_ids);
        $hits21 = $this->_findlink($this->getVar('form2'), $this->getVar('form1'), $target_form_ids, $source_form_ids);

        $link_options = array();
        $loi = 1;
        if ($this->getVar('common') == 1) {
            // must retrieve the names of the fields, since they won't be in the target and source caps arrays, since those are focused only on the linked fields
            $element_handler =& xoops_getmodulehandler('elements', 'formulize');
            $ele1 = $element_handler->get($this->getVar('key1'));
            $ele2 = $element_handler->get($this->getVar('key2'));
            if (is_object($ele1)) {
                $name1 = $ele1->getVar('ele_colhead') ? printSmart($ele1->getVar('ele_colhead')) : printSmart($ele1->getVar('ele_caption'));
            } else {
                $name1 = '';
            }
            if (is_object($ele2)) {
                $name2 = $ele2->getVar('ele_colhead') ? printSmart($ele2->getVar('ele_colhead')) : printSmart($ele2->getVar('ele_caption'));
            } else {
                $name2 = '';
            }
            $link_options[$loi]['value'] = $this->getVar('key1') . "+" . $this->getVar('key2');
            $link_options[$loi]['name'] = _AM_FRAME_COMMON_VALUES . printSmart($name1,20) . " & " . printSmart($name2,20);
            $loi++;
        }
        $this->_buildlinkoptions($hits12, 0, $this->getVar('key1'), $this->getVar('key2'), $target_ele_ids, $source_ele_ids, $target_captions, $source_captions, $link_options, $loi);
        $this->_buildlinkoptions($hits21, 1, $this->getVar('key1'), $this->getVar('key2'), $target_ele_ids, $source_ele_ids, $target_captions, $source_captions, $link_options, $loi);

        return $link_options;
    }


    protected function _findlink($target_form, $source_form, $target_form_ids, $source_form_ids) {
        $truehits = array();
        $hits = array_keys($target_form_ids, $target_form);
        foreach ($hits as $hit) {
            if ($source_form_ids[$hit] == $source_form) {
                $truehits[] = $hit;
            }
        }
        return $truehits;
    }


    protected function _buildlinkoptions($links, $invert, $key1, $key2, $target_ele_ids, $source_ele_ids, $target_captions, $source_captions, &$linkoptions, $loi) {
        foreach ($links as $link) {
            if ($invert) {
                $linkoptions[$loi]['value'] = $source_ele_ids[$link] . "+" . $target_ele_ids[$link];
                $linkoptions[$loi]['name'] = "Linked elements: ".printSmart(htmlspecialchars(strip_tags($source_captions[$link])),20) . " & " . printSmart(htmlspecialchars(strip_tags($target_captions[$link])),20);
            } else {
                $linkoptions[$loi]['value'] = $target_ele_ids[$link] . "+" . $source_ele_ids[$link];
                $linkoptions[$loi]['name'] = "Linked elements: ".printSmart(htmlspecialchars(strip_tags($source_captions[$link])),20) . " & " . printSmart(htmlspecialchars(strip_tags($target_captions[$link])),20);
            }
            $loi++;
        }
    }


    function __get($name) {
        if (!isset($this->$name)) {
            if (method_exists($this, $name)) {
                $this->$name = $this->$name();
            } else {
                $this->$name = $this->getVar($name);
            }
        }
        return $this->$name;
    }
}


class formulizeFrameworksHandler {
	var $db;
	function __construct(&$db) {
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
        global $xoopsDB;
		if(!is_object($framework) OR get_class($framework) != 'formulizeFramework') { return false; }
		$sql = array();
        //remove auto indexes
		$selectsql = "SELECT fl_key1,fl_key2 FROM " . $xoopsDB->prefix("formulize_framework_links") . " WHERE fl_frame_id=".intval($framework->getVar('frid'));
		$res = $xoopsDB->query($selectsql);
		
		if ( $res ) {
            while ( $row = $xoopsDB->fetchRow( $res ) ) {
                $this->deleteIndex($row[0]);
                $this->deleteIndex($row[1]);
            }
		}
		
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
    
	function deleteIndex($elementID){
		$element_handler = xoops_getmodulehandler('elements', 'formulize');
		$elementObject = $element_handler->get(intval($elementID));
		if(is_object($elementObject)){
			$originalName = $elementObject->has_index();
			if(strlen($originalName) > 0){
				$elementObject->deleteIndex($originalName);
			}
		}
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

    function formatFrameworksAsRelationships($frameworks) {
        $relationships = array();
        $relationshipIndices = array();
        $i = 1;
        foreach($frameworks as $framework) {
            $frid = $framework->getVar('frid');
            if (isset($relationshipIndices[$frid])) { continue; }

            $relationships[$i]['name'] = $framework->getVar('name');
            $relationships[$i]['content']['frid'] = $frid;

            $frameworkLinks = $framework->getVar('links');

            $li = 1;
            $links = array();
            foreach($frameworkLinks as $link) {
                $links[$li]['form1'] = printSmart(getFormTitle($link->getVar('form1')));
                $links[$li]['form2'] = printSmart(getFormTitle($link->getVar('form2')));

                switch($link->getVar('relationship')) {
                    case 1:
                        $relationship = _AM_FRAME_ONETOONE;
                        break;
                    case 2:
                        $relationship = _AM_FRAME_ONETOMANY;
                        break;
                    case 3:
                        $relationship = _AM_FRAME_MANYTOONE;
                        break;
                }
                $links[$li]['relationship'] = printSmart($relationship);
                $li++;
            }
            $relationships[$i]['content']['links'] = $links;
            $relationshipIndices[$frid] = true;
            $i++;
        }

        return $relationships;
    }
}
