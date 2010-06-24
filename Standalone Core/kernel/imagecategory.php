<?php
/**
* Manage of Image categories
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	LICENSE.txt
* @package	core
* @since	XOOPS
* @author	http://www.xoops.org The XOOPS Project
* @author	modified by UnderDog <underdog@impresscms.org>
* @version	$Id: imagecategory.php 9520 2009-11-11 14:32:52Z pesianstranger $
*/

if (!defined('XOOPS_ROOT_PATH')) {
	exit();
}

/**
 *
 *
 * @package     kernel
 *
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 */

/**
 * An image category
 *
 * These categories are managed through a {@link XoopsImagecategoryHandler} object
 *
 * @package     kernel
 *
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 */
class XoopsImagecategory extends XoopsObject
{
  	var $_imageCount;

    /**
     * Constructor
     *
     */
  	function XoopsImagecategory()
  	{
  		$this->XoopsObject();
  		$this->initVar('imgcat_id', XOBJ_DTYPE_INT, null, false);
			$this->initVar('imgcat_pid', XOBJ_DTYPE_INT, null, false);
  		$this->initVar('imgcat_name', XOBJ_DTYPE_TXTBOX, null, true, 100);
			$this->initVar('imgcat_foldername', XOBJ_DTYPE_TXTBOX, null, true, 100);
  		$this->initVar('imgcat_display', XOBJ_DTYPE_INT, 1, false);
  		$this->initVar('imgcat_weight', XOBJ_DTYPE_INT, 0, false);
  		$this->initVar('imgcat_maxsize', XOBJ_DTYPE_INT, 0, false);
  		$this->initVar('imgcat_maxwidth', XOBJ_DTYPE_INT, 0, false);
  		$this->initVar('imgcat_maxheight', XOBJ_DTYPE_INT, 0, false);
  		$this->initVar('imgcat_type', XOBJ_DTYPE_OTHER, null, false);
  		$this->initVar('imgcat_storetype', XOBJ_DTYPE_OTHER, null, false);
  	}



    /**
     * Set Image count to a value
     * @param	int $value Value
     */
  	function setImageCount($value)
  	{
  		$this->_imageCount = intval($value);
  	}



    /**
     * Gets Image count
     * @return	int _imageCount number of images
     */
  	function getImageCount()
  	{
  		return $this->_imageCount;
  	}
}






/**
* XOOPS image caetgory handler class.
* This class is responsible for providing data access mechanisms to the data source
* of XOOPS image category class objects.
*
*
* @author  Kazumi Ono <onokazu@xoops.org>
*/
class XoopsImagecategoryHandler extends XoopsObjectHandler
{

    /**
     * Creates a new image category
     *
  	 * @param bool $isNew is the new image category new??
  	 * @return object $imgcat {@link XoopsImagecategory} reference to the new image category
     **/
    function &create($isNew = true)
    {
        $imgcat = new XoopsImagecategory();
        if ($isNew) {
            $imgcat->setNew();
        }
        return $imgcat;
    }



    /**
     * retrieve a specific {@link XoopsImagecategory}
     *
  	 * @see XoopsImagecategory
  	 * @param integer $id imgcatID (imgcat_id) of the image category
  	 * @return object XoopsImagecategory reference to the image category
     **/
    function &get($id)
    {
      $id = intval($id);
      $imgcat = false;
    	if ($id > 0) {
            $sql = "SELECT * FROM ".$this->db->prefix('imagecategory')." WHERE imgcat_id='".$id."'";
            if (!$result = $this->db->query($sql)) {
                return $imgcat;
            }
            $numrows = $this->db->getRowsNum($result);
            if ($numrows == 1) {
                $imgcat = new XoopsImagecategory();
                $imgcat->assignVars($this->db->fetchArray($result));
            }
        }
        return $imgcat;
    }


    /**
     * Insert a new {@link XoopsImagecategory} into the database
     *
  	 * @param object XoopsImagecategory $imgcat reference to the image category to insert
  	 * @return bool TRUE if succesful
     **/
    function insert(&$imgcat)
    {
        /**
        * @TODO: Change to if (!(class_exists($this->className) && $obj instanceof $this->className)) when going fully PHP5
        */
        if (!is_a($imgcat, 'xoopsimagecategory')) {
            return false;
        }

        if (!$imgcat->isDirty()) {
            return true;
        }
        if (!$imgcat->cleanVars()) {
            return false;
        }
        foreach ($imgcat->cleanVars as $k => $v) {
            ${$k} = $v;
        }
        if ($imgcat->isNew()) {
            $imgcat_id = $this->db->genId('imgcat_imgcat_id_seq');
            $sql = sprintf("INSERT INTO %s (imgcat_id, imgcat_pid, imgcat_name, imgcat_foldername, imgcat_display, imgcat_weight, imgcat_maxsize, imgcat_maxwidth, imgcat_maxheight, imgcat_type, imgcat_storetype) VALUES ('%u', '%u', %s, %s, '%u', '%u', '%u', '%u', '%u', %s, %s)", $this->db->prefix('imagecategory'), intval($imgcat_id), intval($imgcat_pid), $this->db->quoteString($imgcat_name), $this->db->quoteString($imgcat_foldername), intval($imgcat_display), intval($imgcat_weight), intval($imgcat_maxsize), intval($imgcat_maxwidth), intval($imgcat_maxheight), $this->db->quoteString($imgcat_type), $this->db->quoteString($imgcat_storetype));
        } else {
            $sql = sprintf("UPDATE %s SET imgcat_pid = %u, imgcat_name = %s, imgcat_foldername = %s, imgcat_display = '%u', imgcat_weight = '%u', imgcat_maxsize = '%u', imgcat_maxwidth = '%u', imgcat_maxheight = '%u', imgcat_type = %s WHERE imgcat_id = '%u'", $this->db->prefix('imagecategory'), intval($imgcat_pid), $this->db->quoteString($imgcat_name), $this->db->quoteString($imgcat_foldername), intval($imgcat_display), intval($imgcat_weight), intval($imgcat_maxsize), intval($imgcat_maxwidth), intval($imgcat_maxheight), $this->db->quoteString($imgcat_type), intval($imgcat_id));
        }
        if (!$result = $this->db->query($sql)) {
            return false;
        }
        if (empty($imgcat_id)) {
            $imgcat_id = $this->db->getInsertId();
        }
        $imgcat->assignVar('imgcat_id', $imgcat_id);
        return true;
    }


    /**
     * delete an {@link XoopsImagecategory} from the database
     *
  	 * @param object XoopsImagecategory $imgcat reference to the image category to delete
  	 * @return bool TRUE if succesful
     **/
    function delete(&$imgcat)
    {
        /**
        * @TODO: Change to if (!(class_exists($this->className) && $obj instanceof $this->className)) when going fully PHP5
        */
        if (!is_a($imgcat, 'xoopsimagecategory')) {
            return false;
        }

        $sql = sprintf("DELETE FROM %s WHERE imgcat_id = '%u'", $this->db->prefix('imagecategory'), intval($imgcat->getVar('imgcat_id')));
        if (!$result = $this->db->query($sql)) {
            return false;
        }
        return true;
    }


    /**
     * retrieve array of {@link XoopsImagecategory}s meeting certain conditions
  	 * @param object $criteria {@link CriteriaElement} with conditions for the image categories
  	 * @param bool $id_as_key should the image category's imgcat_id be the key for the returned array?
  	 * @return array {@link XoopsImagecategory}s matching the conditions
     **/
    function getObjects($criteria = null, $id_as_key = false)
    {
        $ret = array();
        $limit = $start = 0;
        $sql = 'SELECT DISTINCT c.* FROM '.$this->db->prefix('imagecategory').' c LEFT JOIN '.$this->db->prefix('group_permission')." l ON l.gperm_itemid=c.imgcat_id WHERE (l.gperm_name = 'imgcat_read' OR l.gperm_name = 'imgcat_write')";
        if (isset($criteria) && is_subclass_of($criteria, 'criteriaelement')) {
            $where = $criteria->render();
            $sql .= ($where != '') ? ' AND '.$where : '';
            $limit = $criteria->getLimit();
            $start = $criteria->getStart();
        }
	    	$sql .= ' ORDER BY imgcat_weight, imgcat_id ASC';
        $result = $this->db->query($sql, $limit, $start);
        if (!$result) {
            return $ret;
        }
        while ($myrow = $this->db->fetchArray($result)) {
            $imgcat = new XoopsImagecategory();
            $imgcat->assignVars($myrow);
            if (!$id_as_key) {
                $ret[] =& $imgcat;
            } else {
                $ret[$myrow['imgcat_id']] =& $imgcat;
            }
            unset($imgcat);
        }
        return $ret;
    }


    /**
     * get number of {@link XoopsImagecategory}s matching certain conditions
  	 *
  	 * @param string $criteria conditions to match
  	 * @return int number of {@link XoopsImagecategory}s matching the conditions
     **/
    function getCount($criteria = null)
    {
        $sql = 'SELECT COUNT(*) FROM '.$this->db->prefix('imagecategory').' i LEFT JOIN '.$this->db->prefix('group_permission')." l ON l.gperm_itemid=i.imgcat_id WHERE (l.gperm_name = 'imgcat_read' OR l.gperm_name = 'imgcat_write')";
        if (isset($criteria) && is_subclass_of($criteria, 'criteriaelement')) {
            $where = $criteria->render();
            $sql .= ($where != '') ? ' AND '.$where : '';
        }
        if (!$result =& $this->db->query($sql)) {
            return 0;
        }
        list($count) = $this->db->fetchRow($result);
        return $count;
    }


    /**
     * get a list of {@link XoopsImagecategory}s matching certain conditions
  	 * @param string $criteria conditions to match
  	 * @return array array of {@link XoopsImagecategory}s matching the conditions
     **/
    function getList($groups = array(), $perm = 'imgcat_read', $display = null, $storetype = null)
    {
        $criteria = new CriteriaCompo();
        if (is_array($groups) && !empty($groups)) {
            $criteriaTray = new CriteriaCompo();
            foreach ($groups as $gid) {
                $criteriaTray->add(new Criteria('gperm_groupid', $gid), 'OR');
            }
            $criteria->add($criteriaTray);
            if ($perm == 'imgcat_read' || $perm == 'imgcat_write') {
                $criteria->add(new Criteria('gperm_name', $perm));
                $criteria->add(new Criteria('gperm_modid', 1));
            }
        }
        if (isset($display)) {
            $criteria->add(new Criteria('imgcat_display', intval($display)));
        }
        if (isset($storetype)) {
            $criteria->add(new Criteria('imgcat_storetype', $storetype));
        }
        $categories =& $this->getObjects($criteria, true);
        $ret = array();
        foreach (array_keys($categories) as $i) {
                $ret[$i] = $categories[$i]->getVar('imgcat_name');
        }
        return $ret;
    }

		/**
		* Gets list of categories for that image
		* 
		* @param array  $groups  the usergroups to get the permissions for
		* @param string  $perm  the permissions to retrieve
		* @param string  $display
		* @param string  $storetype
		* @param int  $imgcat_id  the image cat id
		* 
		* @return array  list of categories
		*/
    function getCategList($groups = array(), $perm = 'imgcat_read', $display = null, $storetype = null, $imgcat_id=null)
    {
    	$criteria = new CriteriaCompo();
    	if (is_array($groups) && !empty($groups)) {
    		$criteriaTray = new CriteriaCompo();
    		foreach ($groups as $gid) {
    			$criteriaTray->add(new Criteria('gperm_groupid', $gid), 'OR');
    		}
    		$criteria->add($criteriaTray);
    		if ($perm == 'imgcat_read' || $perm == 'imgcat_write') {
    			$criteria->add(new Criteria('gperm_name', $perm));
    			$criteria->add(new Criteria('gperm_modid', 1));
    		}
    	}
    	if (isset($display)) {
    		$criteria->add(new Criteria('imgcat_display', intval($display)));
    	}
    	if (isset($storetype)) {
    		$criteria->add(new Criteria('imgcat_storetype', $storetype));
    	}
    	if (is_null($imgcat_id))$imgcat_id = 0;
    	$criteria->add(new Criteria('imgcat_pid', $imgcat_id));
    	$categories =& $this->getObjects($criteria, true);
    	$ret = array();
    	foreach (array_keys($categories) as $i) {
    		$ret[$i] = $categories[$i]->getVar('imgcat_name');
    		$subcategories = $this->getCategList($groups, $perm, $display, $storetype, $categories[$i]->getVar('imgcat_id'));
    		foreach (array_keys($subcategories) as $j) {
    			$ret[$j] = '-'.$subcategories[$j];
    		}
    	}
    	
    	return $ret;
    }

    /**
     * Get the folder path or url
     *
     * @param integer $imgcat_id - Category ID
     * @param string $full - if true return the full path or url else the relative path
     * @param string $type - path or url
     * 
     * @return string - ful folder path or url
     */
    function getCategFolder(&$imgcat,$full=true,$type='path'){
        /**
        * @TODO: Change to if (!(class_exists($this->className) && $obj instanceof $this->className)) when going fully PHP5
        */
        if (!is_a($imgcat, 'xoopsimagecategory')) {
            return false;
        }
    	if ($imgcat->getVar('imgcat_pid') != 0){
    		$sup = $this->get($imgcat->getVar('imgcat_pid'));
    		$supcateg = $this->getCategFolder($sup,false,$type);
    	}else{
    		$supcateg = 0;
    	}
    	$folder = ($supcateg)?$supcateg.'/':'';
    	if ($full){
    		$folder = ($type == 'path')?ICMS_IMANAGER_FOLDER_PATH.'/'.$folder:ICMS_IMANAGER_FOLDER_URL.'/'.$folder;
    	}

    	return $folder.$imgcat->getVar('imgcat_foldername');
    }
}
?>