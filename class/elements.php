<?php
require_once XOOPS_ROOT_PATH.'/kernel/object.php';

global $xoopsDB;
define('formulize_TABLE', $xoopsDB->prefix("form"));

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
		$this->initVar("ele_display", XOBJ_DTYPE_INT);
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
			$sql = sprintf("INSERT INTO %s (
				id_form, ele_id, ele_type, ele_caption, ele_order, ele_req, ele_value, ele_display
				) VALUES (
				%u, %u, %s, %s, %u, %u, %s, %u
				)",
				formulize_TABLE,
				$id_form,
				$ele_id,
				$this->db->quoteString($ele_type),
				$this->db->quoteString($ele_caption),
				$ele_order,
				$ele_req,
				$this->db->quoteString($ele_value),
				$ele_display
			);
		}else{
			$sql = sprintf("UPDATE %s SET
				ele_type = %s,
				ele_caption = %s,
				ele_order = %u,
				ele_req = %u,
				ele_value = %s,
				ele_display = %u
				WHERE ele_id = %u AND id_form = %u",
				formulize_TABLE,
				$this->db->quoteString($ele_type),
				$this->db->quoteString($ele_caption),
				$ele_order,
				$ele_req,
				$this->db->quoteString($ele_value),
				$ele_display,
				$ele_id,
				$id_form
			);
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
		$sql = 'SELECT * FROM '.formulize_TABLE.' WHERE id_form='.$id_form.' AND ele_display=1';

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