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
		global $xoopsDB;
		$links = array();
		$fids = array();
		$frameName = "";
		if(is_numeric($frid)) {
			$frame_links_q = "SELECT * FROM " . $xoopsDB->prefix("formulize_framework_links") . " WHERE fl_frame_id='" . formulize_db_escape($frid). "'";
			if($res = $xoopsDB->query($frame_links_q) AND $xoopsDB->getRowsNum($res) > 0) {
				while($row = $xoopsDB->fetchArray($res)) {
					$links[] = new formulizeFrameworkLink($row['fl_id']);
					// note that you cannot query the framework_forms table to learn what forms are in a framework, since we keep entries in that table after links have been deleted, since forms might rejoin a framework and we don't want to lose their information.  The links table is the only authoritative source of information about what forms make up a framework.
					$fids[] = $row['fl_form1_id'];
					$fids[] = $row['fl_form2_id'];
				}
				$fids = array_unique($fids);
			}
			$frame_name_q = q("SELECT * FROM " . $xoopsDB->prefix("formulize_frameworks") . " WHERE frame_id=$frid");
			$frameName = isset($frame_name_q[0]) ? $frame_name_q[0]['frame_name'] : "";
		}
		if(!$frameName) {
			list($frid, $fids, $frameName, $links) = $this->initializeNull();
		}
    parent::__construct();
		//initVar params: key, data_type, value, req, max, opt
		$this->initVar("frid", XOBJ_DTYPE_INT, $frid, false);
		$this->initVar("fids", XOBJ_DTYPE_ARRAY, serialize($fids));
		$this->initVar("name", XOBJ_DTYPE_TXTBOX, $frameName, true, 255);
		$this->initVar("links", XOBJ_DTYPE_ARRAY, serialize($links));
		// for legacy compatibility, just in case... these should be checked as being totally not necessary, and removed, because it's pretty clear they have no values so are useless, but if we're doing a getVar somewhere, can't break it.
		$this->initVar("element_ids", XOBJ_DTYPE_ARRAY, serialize(array()));
		$this->initVar("handles", XOBJ_DTYPE_ARRAY, serialize(array()));
		$this->initVar("formHandles", XOBJ_DTYPE_ARRAY, serialize(array()));
	}

	function initializeNull() {
		$ret[] = 0; // frid
		$ret[] = array(); //fids
		$ret[] = ""; // name
		$ret[] = array(); // links
		return $ret;
	}

	// this method returns either "one" or "many" or "onetoone" to indicate if a given handle is on a one side or a many side of the relationship in the framework
    // also returns a second param in an array to indicate if the handle is in the mainform or not
    // usage: list($side, $onMainForm) = $framework->whatSideIsHandleOn($handleOrElementId, $mainformFid);
	function whatSideIsHandleOn($key, $mainformFid) {
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
		return array($cachedHandles[$key],($mainformFid==$targetFid));
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
			$one2one_conditional = 1;
			$one2one_bookkeeping = 1;
		} else {
			$link_q = q("SELECT * FROM " . $xoopsDB->prefix("formulize_framework_links") . " WHERE fl_id = '" . formulize_db_escape($lid). "'");
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
				$unified_delete = "";
				$one2one_conditional = 1;
				$one2one_bookkeeping = 1;
			} else {
				$lid = $lid;
				$frid = $link_q[0]['fl_frame_id'];
				$form1 = $link_q[0]['fl_form1_id'];
				$form2 = $link_q[0]['fl_form2_id'];
				$key1 = $link_q[0]['fl_key1'];
				$key2 = $link_q[0]['fl_key2'];
				$common = $link_q[0]['fl_common_value'];
				$relationship = $link_q[0]['fl_relationship'];
				$unified_delete = $link_q[0]['fl_unified_delete'];
				$one2one_conditional = $link_q[0]['fl_one2one_conditional'];
				$one2one_bookkeeping = $link_q[0]['fl_one2one_bookkeeping'];
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
		$this->initVar("unifiedDisplay", XOBJ_DTYPE_INT, 1, true);
		$this->initVar("unified_delete", XOBJ_DTYPE_INT, $unified_delete, true);
		$this->initVar("one2one_conditional", XOBJ_DTYPE_INT, $one2one_conditional, true);
		$this->initVar("one2one_bookkeeping", XOBJ_DTYPE_INT, $one2one_bookkeeping, true);
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
        $resgetlinksq = $xoopsDB->query("SELECT id_form, ele_caption, ele_id, ele_handle FROM " . $xoopsDB->prefix("formulize") . " WHERE (ele_type=\"select\" OR ele_type=\"checkbox\") AND ele_value LIKE '%#*=:*%' AND (id_form = ".intval($this->getVar('form1'))." OR id_form = ".intval($this->getVar('form2')).") ORDER BY id_form");
        while ($rowlinksq = $xoopsDB->fetchRow($resgetlinksq)) {
            $target_form_ids[] = $rowlinksq[0];
            $target_captions[] = $rowlinksq[1];
            $target_ele_ids[] = $rowlinksq[2];

            // returns an object containing all the details about the form
            $elements =& $formulize_mgr->getObjects(id_form: $rowlinksq[0]);

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
                $name1 = $this->getVar('key1') == -1 ? _AM_FRAME_KEY_ENTRYID : '';
            }
            if (is_object($ele2)) {
                $name2 = $ele2->getVar('ele_colhead') ? printSmart($ele2->getVar('ele_colhead')) : printSmart($ele2->getVar('ele_caption'));
            } else {
                $name2 = $this->getVar('key2') == -1 ? _AM_FRAME_KEY_ENTRYID : '';
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
        $hits = array_keys((array)$target_form_ids, $target_form);
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

#[AllowDynamicProperties]
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
		if($frid != 0) {
			$cachedFrameworks[$frid] = new formulizeFramework($frid);
			return $cachedFrameworks[$frid];
		}
		return false;
	}

	function getFrameworksByForm($fid, $includePrimaryRelationship=false) {
		static $cachedResults = array();
		if(isset($cachedResults[$fid])) { return $cachedResults[$fid]; }
		$ret = array();
		$includePrimaryRelationship = $includePrimaryRelationship ? "" : "fl_frame_id > 0 AND";
		$sql = 'SELECT DISTINCT(fl_frame_id) FROM '.$this->db->prefix("formulize_framework_links")." WHERE $includePrimaryRelationship (fl_form1_id=".intval($fid).' OR fl_form2_id='.intval($fid).') ORDER BY fl_frame_id ASC';

		$result = $this->db->query($sql);

		while( $myrow = $this->db->fetchArray($result) ){
			$framework = new formulizeFramework($myrow['fl_frame_id']);
			$ret[$framework->getVar('frid')] =& $framework;
			unset($framework);
		}
		$cachedResults[$fid] = $ret;
		return $ret;
	}

	/**
	 * Get the links in a relationship, and return them in an ordered array, grouped by form
	 * @param object relationship The relationship we're getting links from
	 * @return array An array of the links in the form, ordered by form, normalized into one to many and one to one for each form (many to one switched around)
	 */
	function getLinksGroupedByForm($relationship, $fid=null) {
		$normalizedLinks = array();
		if(!is_a($relationship, 'formulizeFramework')) {
			return $normalizedLinks;
		}
		$relationshipLinks = $relationship->getVar('links');
		foreach($relationshipLinks as $link) {
			if($fid AND $link->getVar('form2') != $fid AND $link->getVar('form1') != $fid) { continue; }
			switch($link->getVar('relationship')) {
				case 3:
					$normalizedLinks[$link->getVar('form2')][] = array(
						'lid' => $link->getVar('lid'),
						'frid' => $link->getVar('frid'),
						'form1' => $link->getVar('form2'),
						'form2' => $link->getVar('form1'),
						'type' => 3,
						'key1' => $link->getVar('key2'),
						'key2' => $link->getVar('key1'),
						'del' => $link->getVar('unified_delete'),
						'con' => $link->getVar('one2one_conditional'),
						'book' => $link->getVar('one2one_bookkeeping')
					);
					break;
				default:
					$normalizedLinks[$link->getVar('form1')][] = array(
						'lid' => $link->getVar('lid'),
						'frid' => $link->getVar('frid'),
						'form1' => $link->getVar('form1'),
						'form2' => $link->getVar('form2'),
						'type' => $link->getVar('relationship'),
						'key1' => $link->getVar('key1'),
						'key2' => $link->getVar('key2'),
						'del' => $link->getVar('unified_delete'),
						'con' => $link->getVar('one2one_conditional'),
						'book' => $link->getVar('one2one_bookkeeping')
					);
			}
		}
		ksort($normalizedLinks);
		return $normalizedLinks;
	}

	function formatFrameworksAsRelationships($frameworks=null, $limitToFid=null) {
		$form_handler = xoops_getmodulehandler('forms', 'formulize');
		$relationships = array();
		$relationshipIndices = array();
		$i = 0;
		if(!$frameworks AND $limitToFid) {
			$frameworks = $this->getFrameworksByForm($limitToFid, includePrimaryRelationship: true);
		}
		foreach($frameworks as $framework) {
			$frid = $framework->getVar('frid');
			if (isset($relationshipIndices[$frid])) { continue; }
			$frameworkLinks = $this->getLinksGroupedByForm($framework, $limitToFid);
			$links = array();
			foreach($frameworkLinks as $fid=>$fidLinks) {
				foreach($fidLinks as $link) {
					$form1Object = $form_handler->get($link['form1']);
					$form2Object = $form_handler->get($link['form2']);
					if($limitToFid AND $form1Object->getVar('fid') != $limitToFid AND $form2Object->getVar('fid') != $limitToFid) { continue; }
					$form1Text = $form1Object->getSingular();
					$form2TextSingular = $form2Object->getSingular();
					$form2Text = $link['type'] == 1 ? $form2TextSingular : $form2Object->getPlural();
					$connectionText = $link['type'] == 1 ? _AM_FRAME_ONE : _AM_FRAME_MANY;
					$links[] = array(
						'frid'=>$link['frid'],
						'linkId'=>$link['lid'],
						'each'=>ucfirst(_AM_FRAME_EACH),
						'form1'=>$form1Text,
						'has'=>_AM_FRAME_HAS.' '.$connectionText,
						'form2'=>$form2Text,
						'form1Id'=>$link['form1'],
						'form2Id'=>$link['form2'],
						'key1'=>$link['key1'],
						'key2'=>$link['key2'],
					);
				}
			}
			$relationships[$i]['name'] = $framework->getVar('name') . ' (id: '.$framework->getVar('frid').')';
			$relationships[$i]['content']['frid'] = $frid;
			$relationships[$i]['content']['links'] = $links;
			$relationshipIndices[$frid] = true;
			$i++;
		}
		return $relationships;
	}

	/**
	 * Gets all data related to displaying the help text and options for a relationship link on the admin side
	 * @param object link - the link data from the database, a formulizeFrameworkLink object
	 * @return array all the data needed for the help text and options
	 */
	function gatherRelationshipHelpAndOptionsContent($link) {
		$form_handler = xoops_getmodulehandler('forms', 'formulize');
		$firstApp1 = 0;
		$firstApp2 = 0;
		$element1Id = 0;
		$element2Id = 0;
		$element1Text = $this->getElementDescriptor($link->getVar('key1'));
		$element2Text = $this->getElementDescriptor($link->getVar('key2'));
		if($form1Object = $form_handler->get($link->getVar('form1'))) {
			$firstApp1 = formulize_getFirstApplicationForForm($form1Object);
			$element1Id = $link->getVar('key1');
		}
		if($form2Object = $form_handler->get($link->getVar('form2'))) {
			$firstApp2 = formulize_getFirstApplicationForForm($form2Object);
			$element2Id = $link->getVar('key2');
		}
		$delChecked = $link->getVar('unified_delete') ? "checked='checked'" : '';
		$conChecked = $link->getVar('one2one_conditional') ? "checked='checked'" : '';
		$bookChecked = $link->getVar('one2one_bookkeeping') ? "checked='checked'" : '';
		$connectionText = $link->getVar('relationship') == 1 ? _AM_FRAME_ONE : _AM_FRAME_MANY;
		$title = ucfirst(_AM_FRAME_EACH).' '.$form1Object->getSingular().' '._AM_FRAME_HAS.' '.$connectionText.' '.($link->getVar('relationship') == 1 ? $form2Object->getSingular() : $form2Object->getPlural());
		return array(
			'linkId'=>$link->getVar('lid'),
			'element1Id'=>$element1Id,
			'element2Id'=>$element2Id,
			'firstApp1'=>$firstApp1,
			'firstApp2'=>$firstApp2,
			'element1Text'=>trans($form1Object->getVar('title').': '.$element1Text),
			'element2Text'=>trans($form2Object->getVar('title').': '.$element2Text),
			'type'=>$link->getVar('relationship'),
			'delChecked'=>$delChecked,
			'conChecked'=>$conChecked,
			'bookChecked'=>$bookChecked,
			'title'=>$title
		);
	}

	/**
	 * Check if there is a subform interface on the main form of this link, pointing to the subform of this link
	 * @param object link - A formulize framework link object. Framework is the original name for Relationship.
	 * @return boolean Return true or false depending if a subform interface exists on the main form that points to the subform, or not
	 */
	function subformInterfaceExistsForLink($link) {
		if(is_a($link, 'formulizeFrameworkLink')) {
			if($rel = $link->getVar('relationship')) {
				if($rel > 1) {
					$element_handler = xoops_getmodulehandler('elements', 'formulize');
					$form_handler = xoops_getmodulehandler('forms', 'formulize');
					$mainFormId = $rel == 2 ? $link->getVar('form1') : $link->getVar('form2');
					$subformId = $rel == 2 ? $link->getVar('form2') : $link->getVar('form1');
					if($mainFormObject = $form_handler->get($mainFormId, includeAllElements: true)) {
						if($mainFormObject->hasSubformInterfaceForForm($subformId)) {
							return true;
						}
					}
				}
			}
		}
		return false;
	}

	/**
	 * Check if this link exists in other relationships besides the primary relationship
	 * @param object link - A formulize framework link object. Framework is the original name for Relationship.
	 * @return boolean Returns true or false depending if the link is only present in the primary relationship, or not.
	 */
	function linkIsOnlyInPrimaryRelationship($link) {
		$result = false;
		if(is_a($link, 'formulizeFrameworkLink')) {
			$sql = "SELECT fl_id FROM ".$this->db->prefix("formulize_framework_links")."
			WHERE fl_frame_id != -1
			AND fl_common_value = ".intval($link->getVar('common'))."
			AND ((
				fl_relationship = ".intval($link->getVar('relationship'))."
				AND fl_form1_id = ".intval($link->getVar('form1'))."
				AND fl_form2_id = ".intval($link->getVar('form2'))."
				AND fl_key1 = ".intval($link->getVar('key1'))."
				AND fl_key2 = ".intval($link->getVar('key2'))."
			) ";
			if($link->getVar('relationship') == 1) {
				$sql .= ")"; // simply close out, nothing more to do
			} else {
				// need to add an OR for the inverse of the relationship direction
				$sql .= " OR (fl_relationship = ".mirrorRelationship(intval($link->getVar('relationship')))."
				AND fl_form1_id = ".intval($link->getVar('form2'))."
				AND fl_form2_id = ".intval($link->getVar('form1'))."
				AND fl_key1 = ".intval($link->getVar('key2'))."
				AND fl_key2 = ".intval($link->getVar('key1'))."
				))";
			}
			$res = $this->db->query($sql);
			$result = $this->db->getRowsNum($res) === 0 ? true : false;
		}
		return $result;
	}

	/**
	 * Return the descriptor of the passed element ID
	 * @param mixed elementIdentifier The element ID number, or handle, or the full element object. Or -1 to indicate the Entry ID of the record, instead of a regular element.
	 * @return string The readable descriptor to use for this element, or false if the elementIdentifier is invalid
	 */
	function getElementDescriptor($elementIndentifier) {
		if(!$elementIndentifier) {
			return false;
		}
		if($elementIndentifier == -1) {
			return _AM_FRAME_KEY_ENTRYID;
		}
		if(!$elementObject = _getElementObject($elementIndentifier)) {
			return false;
		}
		return $elementObject->getVar('ele_colhead') ? $elementObject->getVar('ele_colhead') : printSmart($elementObject->getVar('ele_caption'));
	}

}



/**
 * Insert/Update relationship links between two forms/form elements. Only operates on one-to-many (2) and many-to-one (3) connections,
 * that are not common value. When passed a form/element id pair, and a source for the link (the element the options are being drawn from),
 * this function will create the link in the primary relationship if no link exists yet, or it will update all links for the form/element id
 * pair and any previously connected source that may have been updated to the new source.
 * @param int fid The form id where the linked element exists
 * @param int elementId The element id of the linked element
 * @param int sourceFid The form id of the source element from which options are gathered for the linked element
 * @param int sourceElementId The element id of the source element from which options are gathered for the linked element
 * @param int currentSourceFid The form id of the source element that the linked element is pointing to prior to this update
 * @param int currentSourceElementId The element id of the source element that the linked element is point to prior to this update
 * @return boolean Returns true or false indicating if the update operation succeeded
 */
function updateLinkedElementConnectionsInRelationships($fid, $elementId, $sourceFid, $sourceElementId, $currentSourceFid, $currentSourceElementId) {
	global $xoopsDB;
	$fid = intval($fid);
	$elementId = intval($elementId);
	$sourceFid = intval($sourceFid);
	$sourceElementId = intval($sourceElementId);
	$currentSourceFid = intval($currentSourceFid);
	$currentSourceElementId = intval($currentSourceElementId);
	// updating existing link...
	if($currentSourceFid AND $currentSourceElementId) {
		// if there's been a change to the source of this link...
		if($currentSourceFid != $sourceFid OR $currentSourceElementId != $sourceElementId) {
			$sql1 = "UPDATE ".$xoopsDB->prefix('formulize_framework_links')."
				SET fl_form1_id = $sourceFid,
				fl_key1 = $sourceElementId
				WHERE fl_common_value = 0
				AND fl_relationship = 2
				AND fl_form1_id = $currentSourceFid
				AND fl_form2_id = $fid
				AND fl_key1 = $currentSourceElementId
				AND fl_key2 = $elementId";
			$sql2 = "UPDATE ".$xoopsDB->prefix('formulize_framework_links')."
				SET fl_form2_id = $sourceFid,
				fl_key2 = $sourceElementId
				WHERE fl_common_value = 0
				AND fl_relationship = 3
				AND fl_form2_id = $currentSourceFid
				AND fl_form1_id = $fid
				AND fl_key2 = $currentSourceElementId
				AND fl_key1 = $elementId";
			$result1 = $xoopsDB->query($sql1);
			$result2 = $xoopsDB->query($sql2);
		}
	// adding a link to primary relationship (element not currently linked)
	} else {
		$result1 = insertLinkIntoPrimaryRelationship(0, 2, $sourceFid, $fid, $sourceElementId, $elementId);
		$result2 = true;
	}
	return ($result1 AND $result2) ? true : false;
}

/**
 * Delete all links involving the specified element. Intended to be called when an element is deleted.
 * @param int fid The form id where the linked element exists
 * @param int elementId The element id of the linked element
 * @return boolean Returns true or false indicating if the update operation succeeded
 */
function deleteElementConnectionsInRelationships($fid, $elementId) {
	global $xoopsDB;
	$fid = intval($fid);
	$elementId = intval($elementId);
	$sql = "DELETE FROM ".$xoopsDB->prefix('formulize_framework_links')."
		WHERE (
			fl_form2_id = $fid
			AND fl_key2 = $elementId
		) OR (
			fl_form1_id = $fid
			AND fl_key1 = $elementId
		)";
	return $xoopsDB->query($sql);
}

/**
 * Delete all links involving the specified linked element, so long as they are one-to-many (2) or many-to-one (3) connections,
 * that are not common value. Intended to be called when an element is no longer linked.
 * @param int fid The form id where the linked element exists
 * @param int elementId The element id of the linked element
 * @return boolean Returns true or false indicating if the update operation succeeded
 */
function deleteLinkedElementConnectionsInRelationships($fid, $elementId) {
	global $xoopsDB;
	$fid = intval($fid);
	$elementId = intval($elementId);
	$sql = "DELETE FROM ".$xoopsDB->prefix('formulize_framework_links')."
		WHERE fl_common_value = 0
		AND ((
				fl_relationship = 2
				AND fl_form2_id = $fid
				AND fl_key2 = $elementId
			) OR (
				fl_relationship = 3
				AND fl_form1_id = $fid
				AND fl_key1 = $elementId
		))";
	return $xoopsDB->query($sql);
}

/**
 * Delete a specified link from a relationship
 * @param int linkId - The primary key id of the link in the database that is being removed
 * @return boolean Returns true or false based on whether the operation succeeded
 */
function deleteLinkFromDatabase($linkId) {
	global $xoopsDB;
	$sql = "DELETE FROM ".$xoopsDB->prefix('formulize_framework_links')."
		WHERE fl_id = ".intval($linkId);
	return $xoopsDB->query($sql);
}

/**
 * Get a list of which elements are in relationship links. Optionally, specify a list of elements to limit the checking to.
 * @param array elementIdentifiers - Optional. An array of element id, handles or objects. If present, limit checking to these elements only.
 * @return array Returns an array where the keys and values are the element ids that were found in relationship links.
 */
function getElementsInRelationshipLinks($elementIdentifiers = array()) {
	$elementsInRelationshipLinks = array();
	$whereClause = "";
	if(is_array($elementIdentifiers)) {
		foreach($elementIdentifiers as $elementIdentifier) {
			$elementObject = _getElementObject($elementIdentifier);
			$elementId = $elementObject->getVar('ele_id');
			$whereClause .= $whereClause ? " OR " : "";
			$whereClause .= "fl_key = $elementId";
		}
	}
	global $xoopsDB;
	foreach(array("fl_key1", "fl_key2") as $field) {
		$sql = str_replace("fl_key", $field, "SELECT fl_key FROM ".$xoopsDB->prefix('formulize_framework_links')." WHERE $whereClause");
		$res = $xoopsDB->query($sql);
		$elementsInRelationshipLinks = array_merge($elementsInRelationshipLinks, (array)$xoopsDB->fetchColumn($res, column: 0));
	}
	return $elementsInRelationshipLinks;
}
