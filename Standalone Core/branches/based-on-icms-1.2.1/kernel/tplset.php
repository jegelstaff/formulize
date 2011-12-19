<?php
/**
* Manage of template sets
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	LICENSE.txt
* @package	core
* @since	XOOPS
* @author	http://www.xoops.org The XOOPS Project
* @author	modified by UnderDog <underdog@impresscms.org>
* @version	$Id: tplset.php 9520 2009-11-11 14:32:52Z pesianstranger $
*/

if (!defined('XOOPS_ROOT_PATH')) {
	exit();
}


/**
 * @package kernel
 * @copyright copyright &copy; 2000 XOOPS.org
 */


/**
 * Base class for all templatesets
 * 
 * @author Kazumi Ono (AKA onokazu)
 * @copyright copyright &copy; 2000 XOOPS.org
 * @package kernel
 **/
class XoopsTplset extends XoopsObject
{


    /**
    * constructor
    */
  	function XoopsTplset()
  	{
  		$this->XoopsObject();
  		$this->initVar('tplset_id', XOBJ_DTYPE_INT, null, false);
  		$this->initVar('tplset_name', XOBJ_DTYPE_OTHER, null, false);
  		$this->initVar('tplset_desc', XOBJ_DTYPE_TXTBOX, null, false, 255);
  		$this->initVar('tplset_credits', XOBJ_DTYPE_TXTAREA, null, false);
  		$this->initVar('tplset_created', XOBJ_DTYPE_INT, 0, false);
  	}
}









/**
* XOOPS tplset handler class.
* This class is responsible for providing data access mechanisms to the data source
* of XOOPS tplset class objects.
*
*
* @author  Kazumi Ono <onokazu@xoops.org>
*/
class XoopsTplsetHandler extends XoopsObjectHandler
{



    /**
     * create a new templateset instance
     *
  	 * @see XoopsTplset
  	 * @param bool $isNew is the new tempateset new??
  	 * @return object XoopsTplset {@link XoopsTplset} reference to the new template
     **/
    function &create($isNew = true)
    {
        $tplset = new XoopsTplset();
        if ($isNew) {
            $tplset->setNew();
        }
        return $tplset;
    }



    /**
     * Gets templateset from database by ID
     *
  	 * @see XoopsTplset
  	 * @param int $id of the tempateset to get
  	 * @return object XoopsTplset {@link XoopsTplset} reference to the new template
     **/
    function &get($id)
    {
        $tplset = false;
      	$id = intval($id);
        if ($id > 0) {
            $sql = "SELECT * FROM ".$this->db->prefix('tplset')." WHERE tplset_id='".$id."'";
            if (!$result = $this->db->query($sql)) {
                return $tplset;
            }
            $numrows = $this->db->getRowsNum($result);
            if ($numrows == 1) {
                $tplset = new XoopsTplset();
                $tplset->assignVars($this->db->fetchArray($result));
            }
        }
        return $tplset;
    }




    /**
     * Gets templateset from database by Name
     *
  	 * @see XoopsTplset
  	 * @param string $tplset_name of the tempateset to get
  	 * @return object XoopsTplset {@link XoopsTplset} reference to the new template
     **/
    function &getByName($tplset_name)
    {
        $tplset = false;
    	$tplset_name = trim($tplset_name);
        if ($tplset_name != '') {
            $sql = "SELECT * FROM ".$this->db->prefix('tplset')." WHERE tplset_name=".$this->db->quoteString($tplset_name)."";
            if (!$result = $this->db->query($sql)) {
                return $tplset;
            }
            $numrows = $this->db->getRowsNum($result);
            if ($numrows == 1) {
                $tplset = new XoopsTplset();
                $tplset->assignVars($this->db->fetchArray($result));
            }
        }
        return $tplset;
    }



    /**
     * Inserts templateset into the database
     *
  	 * @see XoopsTplset
  	 * @param string $tplset_name of the tempateset to get
  	 * @return object XoopsTplset {@link XoopsTplset} reference to the new template
     **/
    function insert(&$tplset)
    {
        /**
        * @TODO: Change to if (!(class_exists($this->className) && $obj instanceof $this->className)) when going fully PHP5
        */
        if (!is_a($tplset, 'xoopstplset')) {
            return false;
        }
        if (!$tplset->isDirty()) {
            return true;
        }
        if (!$tplset->cleanVars()) {
            return false;
        }
        foreach ($tplset->cleanVars as $k => $v) {
            ${$k} = $v;
        }
        if ($tplset->isNew()) {
            $tplset_id = $this->db->genId('tplset_tplset_id_seq');
            $sql = sprintf("INSERT INTO %s (tplset_id, tplset_name, tplset_desc, tplset_credits, tplset_created) VALUES ('%u', %s, %s, %s, '%u')", $this->db->prefix('tplset'), intval($tplset_id), $this->db->quoteString($tplset_name), $this->db->quoteString($tplset_desc), $this->db->quoteString($tplset_credits), intval($tplset_created));
        } else {
            $sql = sprintf("UPDATE %s SET tplset_name = %s, tplset_desc = %s, tplset_credits = %s, tplset_created = '%u' WHERE tplset_id = '%u'", $this->db->prefix('tplset'), $this->db->quoteString($tplset_name), $this->db->quoteString($tplset_desc), $this->db->quoteString($tplset_credits), intval($tplset_created), intval($tplset_id));
        }
        if (!$result = $this->db->query($sql)) {
            return false;
        }
        if (empty($tplset_id)) {
            $tplset_id = $this->db->getInsertId();
        }
    		$tplset->assignVar('tplset_id', $tplset_id);
        return true;
    }





    /**
     * Deletes templateset from the database
     *
  	 * @see XoopsTplset
  	 * @param object $tplset {@link XoopsTplset} reference to the object of the tempateset to delete
  	 * @return object XoopsTplset {@link XoopsTplset} reference to the new template
     **/
    function delete(&$tplset)
    {
        /**
        * @TODO: Change to if (!(class_exists($this->className) && $obj instanceof $this->className)) when going fully PHP5
        */
        if (!is_a($tplset, 'xoopstplset')) {
            return false;
        }

        $sql = sprintf("DELETE FROM %s WHERE tplset_id = '%u'", $this->db->prefix('tplset'), intval($tplset->getVar('tplset_id')));
        if (!$result = $this->db->query($sql)) {
            return false;
        }
        $sql = sprintf("DELETE FROM %s WHERE tplset_name = %s", $this->db->prefix('imgset_tplset_link'), $this->db->quoteString($tplset->getVar('tplset_name')));
        $this->db->query($sql);
        return true;
    }



    /**
     * retrieve array of {@link XoopsTplset}s meeting certain conditions
  	 * @param object $criteria {@link CriteriaElement} with conditions for the blocks
  	 * @param bool $id_as_key should the tplfile's tpl_id be the key for the returned array?
  	 * @return array {@link XoopsTplset}s matching the conditions
     **/
    function getObjects($criteria = null, $id_as_key = false)
    {
        $ret = array();
        $limit = $start = 0;
        $sql = 'SELECT * FROM '.$this->db->prefix('tplset');
        if (isset($criteria) && is_subclass_of($criteria, 'criteriaelement')) {
            $sql .= ' '.$criteria->renderWhere().' ORDER BY tplset_id';
            $limit = $criteria->getLimit();
            $start = $criteria->getStart();
        }
        $result = $this->db->query($sql, $limit, $start);
        if (!$result) {
            return $ret;
        }
        while ($myrow = $this->db->fetchArray($result)) {
            $tplset = new XoopsTplset();
            $tplset->assignVars($myrow);
            if (!$id_as_key) {
                $ret[] =& $tplset;
            } else {
                $ret[$myrow['tplset_id']] =& $tplset;
            }
            unset($tplset);
        }
        return $ret;
    }




    /**
     * Count some tplfilesets
     *
     * @param   object  $criteria   {@link CriteriaElement}
     * @return  int $count number of template filesets that match the criteria
     **/
    function getCount($criteria = null)
    {
        $sql = 'SELECT COUNT(*) FROM '.$this->db->prefix('tplset');
        if (isset($criteria) && is_subclass_of($criteria, 'criteriaelement')) {
            $sql .= ' '.$criteria->renderWhere();
        }
        if (!$result =& $this->db->query($sql)) {
            return 0;
        }
        list($count) = $this->db->fetchRow($result);
        return $count;
    }


		/**
		* Gets list of tplset objects into an array
		* 
		* @param array  $criteria  array of WHERE statement criteria
		* 
		* @return array  The array of tplset objects
		*/
    function getList($criteria = null)
  	{
        $ret = array();
    		$tplsets = $this->getObjects($criteria, true);
    		foreach (array_keys($tplsets) as $i) {
            $ret[$tplsets[$i]->getVar('tplset_name')] = $tplsets[$i]->getVar('tplset_name');
        }
    		return $ret;
  	}
}


?>