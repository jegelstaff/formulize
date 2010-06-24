<?php
/**
* Manage of imagesets
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	LICENSE.txt
* @package	core
* @since	XOOPS
* @author	http://www.xoops.org The XOOPS Project
* @author	modified by UnderDog <underdog@impresscms.org>
* @version	$Id: imagesetimg.php 9520 2009-11-11 14:32:52Z pesianstranger $
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
 * An imageset image
 *
 * These sets are managed through a {@link XoopsImagesetimgHandler} object
 *
 * @package     kernel
 *
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 */
class XoopsImagesetimg extends XoopsObject
{
    /**
     * Constructor
     *
     */
  	function XoopsImagesetimg()
  	{
  		$this->XoopsObject();
  		$this->initVar('imgsetimg_id', XOBJ_DTYPE_INT, null, false);
  		$this->initVar('imgsetimg_file', XOBJ_DTYPE_OTHER, null, false);
  		$this->initVar('imgsetimg_body', XOBJ_DTYPE_SOURCE, null, false);
  		$this->initVar('imgsetimg_imgset', XOBJ_DTYPE_INT, null, false);
  	}
}




/**
* XOOPS imageset image handler class.
* This class is responsible for providing data access mechanisms to the data source
* of XOOPS imageset image class objects.
*
*
* @author  Kazumi Ono <onokazu@xoops.org>
*/
class XoopsImagesetimgHandler extends XoopsObjectHandler
{

    /**
     * Creates a new imageset image
     *
  	 * @param bool $isNew is the new imageset image new??
  	 * @return object $imgcat {@link XoopsImagesetimg} reference to the new imageset image
     **/
    function &create($isNew = true)
    {
        $imgsetimg = new XoopsImagesetimg();
        if ($isNew) {
            $imgsetimg->setNew();
        }
        return $imgsetimg;
    }


    /**
     * retrieve a specific {@link XoopsImagesetimg}
     *
  	 * @see XoopsImagesetimg
  	 * @param integer $id imgsetimageID (imgsetimg_id) of the imageset image
  	 * @return object XoopsImagesetimg reference to the image set image
     **/
    function &get($id)
    {
        $imgsetimg = false;
      	$id = intval($id);
        if ($id > 0) {
            $sql = "SELECT * FROM ".$this->db->prefix('imgsetimg')." WHERE imgsetimg_id='".$id."'";
            if (!$result = $this->db->query($sql)) {
                return $imgsetimg;
            }
            $numrows = $this->db->getRowsNum($result);
            if ($numrows == 1) {
                $imgsetimg = new XoopsImagesetimg();
                $imgsetimg->assignVars($this->db->fetchArray($result));
            }
        }
        return $imgsetimg;
    }


    /**
     * Insert a new {@link XoopsImagesetimg} into the database
     *
  	 * @param object XoopsImagesetimg $imgsetimg reference to the imageset image to insert
  	 * @return bool TRUE if succesful
     **/
    function insert(&$imgsetimg)
    {
        /**
        * @TODO: Change to if (!(class_exists($this->className) && $obj instanceof $this->className)) when going fully PHP5
        */
        if (!is_a($imgsetimg, 'xoopsimagesetimg')) {
            return false;
        }

        if (!$imgsetimg->isDirty()) {
            return true;
        }
        if (!$imgsetimg->cleanVars()) {
            return false;
        }
        foreach ($imgsetimg->cleanVars as $k => $v) {
            ${$k} = $v;
        }
        if ($imgsetimg->isNew()) {
            $imgsetimg_id = $this->db->genId('imgsetimg_imgsetimg_id_seq');
            $sql = sprintf("INSERT INTO %s (imgsetimg_id, imgsetimg_file, imgsetimg_body, imgsetimg_imgset) VALUES ('%u', %s, %s, %s)", $this->db->prefix('imgsetimg'), intval($imgsetimg_id), $this->db->quoteString($imgsetimg_file), $this->db->quoteString($imgsetimg_body), $this->db->quoteString($imgsetimg_imgset));
        } else {
            $sql = sprintf("UPDATE %s SET imgsetimg_file = %s, imgsetimg_body = %s, imgsetimg_imgset = %s WHERE imgsetimg_id = '%u'", $this->db->prefix('imgsetimg'), $this->db->quoteString($imgsetimg_file), $this->db->quoteString($imgsetimg_body), $this->db->quoteString($imgsetimg_imgset), intval($imgsetimg_id));
        }
        if (!$result = $this->db->query($sql)) {
            return false;
        }
        if (empty($imgsetimg_id)) {
            $imgsetimg_id = $this->db->getInsertId();
        }
    		$imgsetimg->assignVar('imgsetimg_id', $imgsetimg_id);
        return true;
    }


    /**
     * delete an {@link XoopsImagesetimg} from the database
     *
  	 * @param object XoopsImagesetimg $imgsetimg reference to the imageset image to delete
  	 * @return bool TRUE if succesful
     **/
    function delete(&$imgsetimg)
    {
        /**
        * @TODO: Change to if (!(class_exists($this->className) && $obj instanceof $this->className)) when going fully PHP5
        */
        if (!is_a($imgsetimg, 'xoopsimagesetimg')) {
            return false;
        }

        $sql = sprintf("DELETE FROM %s WHERE imgsetimg_id = '%u'", $this->db->prefix('imgsetimg'), intval($imgsetimg->getVar('imgsetimg_id')));
        if (!$result = $this->db->query($sql)) {
            return false;
        }
        return true;
    }


    /**
     * retrieve array of {@link XoopsImagesetimg}s meeting certain conditions
  	 * @param object $criteria {@link CriteriaElement} with conditions for the imageset images
  	 * @param bool $id_as_key should the imagesetimg's imgsetimg_id be the key for the returned array?
  	 * @return array {@link XoopsImagesetimg}s matching the conditions
     **/
    function getObjects($criteria = null, $id_as_key = false)
    {
        $ret = array();
        $limit = $start = 0;
        $sql = 'SELECT DISTINCT i.* FROM '.$this->db->prefix('imgsetimg'). ' i LEFT JOIN '.$this->db->prefix('imgset_tplset_link'). ' l ON l.imgset_id=i.imgsetimg_imgset LEFT JOIN '.$this->db->prefix('imgset').' s ON s.imgset_id=l.imgset_id';
        if (isset($criteria) && is_subclass_of($criteria, 'criteriaelement')) {
            $sql .= ' '.$criteria->renderWhere();
            $sql .= ' ORDER BY imgsetimg_id '.$criteria->getOrder();
            $limit = $criteria->getLimit();
            $start = $criteria->getStart();
        }
        $result = $this->db->query($sql, $limit, $start);
        if (!$result) {
            return $ret;
        }
        while ($myrow = $this->db->fetchArray($result)) {
            $imgsetimg = new XoopsImagesetimg();
            $imgsetimg->assignVars($myrow);
            if (!$id_as_key) {
                $ret[] =& $imgsetimg;
            } else {
                $ret[$myrow['imgsetimg_id']] =& $imgsetimg;
            }
            unset($imgsetimg);
        }
        return $ret;
    }


    /**
     * Count some imageset images
     *
     * @param   object  $criteria   {@link CriteriaElement}
     * @return  int number of imageset images
     **/
    function getCount($criteria = null)
    {
        $sql = 'SELECT COUNT(i.imgsetimg_id) FROM '.$this->db->prefix('imgsetimg'). ' i LEFT JOIN '.$this->db->prefix('imgset_tplset_link'). ' l ON l.imgset_id=i.imgsetimg_imgset';
        if (isset($criteria) && is_subclass_of($criteria, 'criteriaelement')) {
            $sql .= ' '.$criteria->renderWhere().' GROUP BY i.imgsetimg_id';
        }
        if (!$result =& $this->db->query($sql)) {
            return 0;
        }
        list($count) = $this->db->fetchRow($result);
        return $count;
    }





/**
 * Function-Documentation
 * @param type $imgset_id documentation
 * @param type $id_as_key = false documentation
 * @return type documentation
 * @author Kazumi Ono <onokazu@xoops.org>
 **/
    function getByImageset($imgset_id, $id_as_key = false)
    {
        return $this->getObjects(new Criteria('imgsetimg_imgset', intval($imgset_id)), $id_as_key);
    }




/**
 * Function-Documentation
 * @param type $filename documentation
 * @param type $imgset_id documentation
 * @return type documentation
 * @author Kazumi Ono <onokazu@xoops.org>
 **/
    function imageExists($filename, $imgset_id)
    {
        $criteria = new CriteriaCompo(new Criteria('imgsetimg_file', $filename));
        $criteria->add(new Criteria('imgsetimg_imgset', intval($imgset_id)));
        if ($this->getCount($criteria) > 0) {
            return true;
        }
        return false;
    }
}


?>