<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2004 Freeform Solutions                  ##
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
##  Author of this file: Freeform Solutions and NS Tai (aka tuff) and others ##
##  URL: http://www.brandycoke.com/                                          ##
##  Project: Formulize                                                       ##
###############################################################################

require_once XOOPS_ROOT_PATH.'/kernel/object.php';

global $xoopsDB;
define('formulize_TABLE', $xoopsDB->prefix("formulize"));
// added data table - Aug 14 2005
define('formulize_DATATABLE', $xoopsDB->prefix("formulize_form"));


class formulizeformulize extends XoopsObject {
	function formulizeformulize(){
		$this->XoopsObject();
	//	key, data_type, value, req, max, opt
		$this->initVar("id_form", XOBJ_DTYPE_INT, NULL, false);
		$this->initVar("ele_id", XOBJ_DTYPE_INT, NULL, false);
		$this->initVar("ele_type", XOBJ_DTYPE_TXTBOX, NULL, true, 10);
		$this->initVar("ele_caption", XOBJ_DTYPE_TXTBOX, NULL, true, 255);
		$this->initVar("ele_order", XOBJ_DTYPE_INT);
		$this->initVar("ele_req", XOBJ_DTYPE_INT);
		$this->initVar("ele_value", XOBJ_DTYPE_ARRAY);
		$this->initVar("ele_forcehidden", XOBJ_DTYPE_INT);
 		// changed - start - August 19 2005 - jpc 		
		//$this->initVar("ele_display", XOBJ_DTYPE_INT);
		$this->initVar("ele_display", XOBJ_DTYPE_TXTBOX);
		// changed - end - August 19 2005 - jpc 	
	}
	
}

class formulizeElementsHandler {
	var $db;
	function formulizeElementsHandler(&$db) {
		$this->db =& $db;
	}
	function &getInstance(&$db) {
		static $instance;
		if (!isset($instance)) {
			$instance = new formulizeElementsHandler($db);
		}
		return $instance;
	}
	function &create() {
		return new formulizeformulize();
	}

	function &get($id){
		$id = intval($id);
		if ($id > 0) {
			$sql = 'SELECT * FROM '.formulize_TABLE.' WHERE ele_id='.$id;
			if (!$result = $this->db->query($sql)) {
				return false;
			}
			$numrows = $this->db->getRowsNum($result);
			if ($numrows == 1) {
				$element = new formulizeformulize();
				$element->assignVars($this->db->fetchArray($result));
				return $element;
			}
		}
		return false;
	}

	function insert(&$element, $force = false){
        if( get_class($element) != 'formulizeformulize'){
            return false;
        }
        if( !$element->isDirty() ){
            return true;
        }
        if( !$element->cleanVars() ){
            return false;
        }
		foreach( $element->cleanVars as $k=>$v ){
			${$k} = $v;
		}
           		if( $element->isNew() || empty($ele_id) ){
			$ele_id = $this->db->genId(formulize_TABLE."_ele_id_seq");
            // changed - start - August 19 2005 - jpc
			/*$sql = sprintf("INSERT INTO %s (
				id_form, ele_id, ele_type, ele_caption, ele_order, ele_req, ele_value, ele_display, ele_forcehidden
				) VALUES (
				%u, %u, %s, %s, %u, %u, %s, %u, $u
				)",
				formulize_TABLE,
				$id_form,
				$ele_id,
				$this->db->quoteString($ele_type),
				$this->db->quoteString($ele_caption),
				$ele_order,
				$ele_req,
				$this->db->quoteString($ele_value),
				$ele_display,
				$ele_forcehidden
			);*/
			$sql = sprintf("INSERT INTO %s (
				id_form, ele_id, ele_type, ele_caption, ele_order, ele_req, ele_value, ele_display, ele_forcehidden
				) VALUES (
				%u, %u, %s, %s, %u, %u, %s, %s, %u
				)",
				formulize_TABLE,
				$id_form,
				$ele_id,
				$this->db->quoteString($ele_type),
				$this->db->quoteString($ele_caption),
				$ele_order,
				$ele_req,
				$this->db->quoteString($ele_value),
				$this->db->quoteString($ele_display),
				$ele_forcehidden
			);            
            // changed - end - August 19 2005 - jpc
			}else{
            // changed - start - August 19 2005 - jpc
            /*$sql = sprintf("UPDATE %s SET
				ele_type = %s,
				ele_caption = %s,
				ele_order = %u,
				ele_req = %u,
				ele_value = %s,
				ele_display = %u,
				ele_forcehidden = %u
				WHERE ele_id = %u AND id_form = %u",
				formulize_TABLE,
				$this->db->quoteString($ele_type),
				$this->db->quoteString($ele_caption),
				$ele_order,
				$ele_req,
				$this->db->quoteString($ele_value),
				$ele_display,
				$ele_forcehidden,
				$ele_id,
				$id_form
			);*/
            $sql = sprintf("UPDATE %s SET
				ele_type = %s,
				ele_caption = %s,
				ele_order = %u,
				ele_req = %u,
				ele_value = %s,
				ele_display = %s,
				ele_forcehidden = %u
				WHERE ele_id = %u AND id_form = %u",
				formulize_TABLE,
				$this->db->quoteString($ele_type),
				$this->db->quoteString($ele_caption),
				$ele_order,
				$ele_req,
				$this->db->quoteString($ele_value),
				$this->db->quoteString($ele_display),
				$ele_forcehidden,
				$ele_id,
				$id_form
			);
            // changed - end - August 19 2005 - jpc
 		}
        if( false != $force ){
            $result = $this->db->queryF($sql);
        }else{
            $result = $this->db->query($sql);
        }

		if( !$result ){
			$this->setErrors("Could not store data in the database.<br />".mysql_error());
			return false;
		}
		if( empty($ele_id) ){
			$ele_id = $this->db->getInsertId();
		}
        $element->assignVar('ele_id', $ele_id);
		return $ele_id;
	}
	
	function delete(&$element, $force = false){
		if( get_class($this) != 'formulizeelementshandler') {
			return false;
		}
		$sql = "DELETE FROM ".formulize_TABLE." WHERE ele_id=".$element->getVar("ele_id")."";
        if( false != $force ){
            $result = $this->db->queryF($sql);
        }else{
            $result = $this->db->query($sql);
        }
		return true;
	}

	// this function added by jwe Aug 14 2005 -- deletes the data associated with a particular element in a particular form
	function deleteData(&$element, $force = false){
		if( get_class($this) != 'formulizeelementshandler') {
			return false;
		}
		// convert the caption to form_form formatting (' becomes `)
		$caption = $element->getVar('ele_caption');
		$caption = str_replace("'", "`", $caption);
		$caption = str_replace("&#039;", "`", $caption);
		$fid = $element->getVar('id_form');
		$sql = "DELETE FROM ".formulize_DATATABLE." WHERE id_form='$fid' AND ele_caption='$caption'";

        if( false != $force ){
            $result = $this->db->queryF($sql);
        }else{
            $result = $this->db->query($sql);
        }
		return true;
	}


	function &getObjects($criteria = null, $id_form , $id_as_key = false){
		$ret = array();
		$limit = $start = 0;
		$sql = 'SELECT * FROM '.formulize_TABLE.' WHERE id_form='.$id_form;

		if( isset($criteria) && is_subclass_of($criteria, 'criteriaelement') ){
		//	$sql .= ' '.$criteria->renderWhere();
			if( $criteria->getSort() != '' ){
				$sql .= ' ORDER BY '.$criteria->getSort().' '.$criteria->getOrder();
			}
			$limit = $criteria->getLimit();
			$start = $criteria->getStart();
		}
		$result = $this->db->query($sql, $limit, $start);

		if( !$result ){
			return false;
		}
		while( $myrow = $this->db->fetchArray($result) ){
			$elements = new formulizeformulize();
			$elements->assignVars($myrow);
			if( !$id_as_key ){
				$ret[] =& $elements;
			}else{
				$ret[$myrow['ele_id']] =& $elements;
			}
			unset($elements);
		}
		return $ret;
	}

	function &getObjects2($criteria = null, $id_form , $id_as_key = false){
		$ret = array();
		$limit = $start = 0;
//		awareness of $criteria added, Sept 1 2005, jwe
//		removal of ele_display=1 from next line and addition of the renderWhere line in the conditional below
		$sql = 'SELECT * FROM '.formulize_TABLE.' WHERE id_form='.$id_form;


		if( isset($criteria) && is_subclass_of($criteria, 'criteriaelement') ){
			$sql .= ' AND ('.$criteria->render().')';
			if( $criteria->getSort() != '' ){
				$sql .= ' ORDER BY '.$criteria->getSort().' '.$criteria->getOrder();
			}
			$limit = $criteria->getLimit();
			$start = $criteria->getStart();
		}
		$result = $this->db->query($sql, $limit, $start);

		if( !$result ){
			return false;
		}
		while( $myrow = $this->db->fetchArray($result) ){
			$elements = new formulizeformulize();
			$elements->assignVars($myrow);
			if( !$id_as_key ){
				$ret[] =& $elements;
			}else{
				$ret[$myrow['ele_id']] =& $elements;
			}
			unset($elements);
		}
		return $ret;
	}

	
    function getCount($criteria = null){
		$sql = 'SELECT COUNT(*) FROM '.formulize_TABLE;
		if( isset($criteria) && is_subclass_of($criteria, 'criteriaelement') ){
			$sql .= ' '.$criteria->renderWhere();
		}
		$result = $this->db->query($sql);
		if( !$result ){
			return 0;
		}
		list($count) = $xoopsDB->fetchRow($result);
		return $count;
	}
    
    function deleteAll($criteria = null){
    	global $xoopsDB;
		$sql = 'DELETE FROM '.formulize_TABLE;
		if( isset($criteria) && is_subclass_of($criteria, 'criteriaelement') ){
			$sql .= ' '.$criteria->renderWhere();
		}
		if( !$result = $this->db->query($sql) ){
			return false;
		}
		return true;
	}
}

?>