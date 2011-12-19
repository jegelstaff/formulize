<?php
/**
* Contains the basic classe for managing a category object based on IcmsPersistableObject
*
* @copyright	The ImpressCMS Project http://www.impresscms.org/
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package		IcmsPersistableObject
* @since		1.2
* @author		marcan <marcan@impresscms.org>
* @author	    Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
* @version		$Id: icmspersistablecategory.php 9520 2009-11-11 14:32:52Z pesianstranger $
*/


if (!defined("ICMS_ROOT_PATH")) {
	die("ImpressCMS root path not defined");
}
/** Make sure the IcmsPersistableOject class is loaded */
include_once ICMS_ROOT_PATH . "/kernel/icmspersistableseoobject.php";
/**
 * Persistble category object
 * @package 	IcmsPersistableObject
 * @subpackage 	IcmsPersistableCategory
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @since 		1.1
 */
class IcmsPersistableCategory extends IcmsPersistableSeoObject {
	/** Path that corresponds to the category */
	var $_categoryPath;
	/**
	 * Constructor for IcmsPersistableCategory
	 * @return IcmsPersistableCategory
	 */
	function IcmsPersistableCategory() {
	    $this->initVar('categoryid', XOBJ_DTYPE_INT, '', true);
    	$this->initVar('parentid', XOBJ_DTYPE_INT, '', false, null, '', false, _CO_ICMS_CATEGORY_PARENTID, _CO_ICMS_CATEGORY_PARENTID_DSC);
    	$this->initVar('name', XOBJ_DTYPE_TXTBOX, '', false, null, '', false, _CO_ICMS_CATEGORY_NAME, _CO_ICMS_CATEGORY_NAME_DSC);
        $this->initVar('description', XOBJ_DTYPE_TXTAREA, '', false, null, '', false, _CO_ICMS_CATEGORY_DESCRIPTION, _CO_ICMS_CATEGORY_DESCRIPTION_DSC);
        $this->initVar('image', XOBJ_DTYPE_TXTBOX, '', false, null, '',  false, _CO_ICMS_CATEGORY_IMAGE, _CO_ICMS_CATEGORY_IMAGE_DSC);

        $this->initCommonVar('doxcode');

        $this->setControl('image', array('name' => 'image'));
        $this->setControl('parentid', array('name' => 'parentcategory'));
        $this->setControl('description', array('name' => 'textarea',
                                            'itemHandler' => false,
                                            'method' => false,
                                            'module' => false,
                                            'form_editor' => 'default'));

        // call parent constructor to get SEO fields initiated
        $this->IcmsPersistableSeoObject();
	}

    /**
    * returns a specific variable for the object in a proper format
    *
    * @access public
    * @param string $key key of the object's variable to be returned
    * @param string $format format to use for the output
    * @return mixed formatted value of the variable
    */
    function getVar($key, $format = 's') {
        if ($format == 's' && in_array($key, array('description', 'image'))) {
            return call_user_func(array($this,$key));
        }
        return parent::getVar($key, $format);
    }
	/**
	 * Returns the description for the category
	 * @see 	IcmsPersistableObject::getValueFor()
	 * @return 	string	Text to display as the description
	 */
    function description() {
    	return $this->getValueFor('description', false);
    }
	/**
	 * Returns the image for the category
	 *
	 * @return 	mixed	Returns false if there is no image, or the image, if it exists
	 */
    function image() {
    	$ret = $this->getVar('image', 'e');
    	if ($ret == '-1') {
    		return false;
    	} else {
    		return $ret;
    	}
    }
	/**
	 * Create an array of the category's properties
	 *
	 * @return 	array An array of the category's properties
	 */
    function toArray() {
    	$this->setVar('doxcode', true);
    	global $myts;
    	$objectArray = parent::toArray();
    	if ($objectArray['image']) {
    		$objectArray['image'] = $this->getImageDir() . $objectArray['image'];
    	}
    	return $objectArray;
    }
    /**
     * Create the complete path of a category
     *
     * @todo this could be improved as it uses multiple queries
     * @param bool $withAllLink make all name clickable
     * @return string complete path (breadcrumb)
     */
	function getCategoryPath($withAllLink=true, $currentCategory=false)	{

		include_once ICMS_ROOT_PATH . "/kernel/icmspersistablecontroller.php";
        $controller = new IcmsPersistableObjectController($this->handler);

		if (!$this->_categoryPath) {
			if ($withAllLink && !$currentCategory) {
				$ret = $controller->getItemLink($this);
			} else {
				$currentCategory = false;
				$ret = $this->getVar('name');
			}
			$parentid = $this->getVar('parentid');
			if ($parentid != 0) {
				$parentObj =& $this->handler->get($parentid);
				if ($parentObj->isNew()) {
					exit;
				}
				$parentid = $parentObj->getVar('parentid');
				$ret = $parentObj->getCategoryPath($withAllLink, $currentCategory) . " > " .$ret;
			}
			$this->_categoryPath = $ret;
        }

		return $this->_categoryPath;
	}

}
/**
 * Provides data access mechanisms to the IcmsPersistableCategory object
 * @copyright 	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @since 		1.1
 */
class IcmsPersistableCategoryHandler extends IcmsPersistableObjectHandler {
	/** */
	var $allCategoriesObj = false;
	/** */
	var $_allCategoriesId = false;

	/**
	 * Constructor for the object handler
	 *
	 * @param object $db A database object
	 * @param string $modulename The directory name for the module
	 * @return IcmsPersistableCategoryHandler
	 */
    function IcmsPersistableCategoryHandler($db, $modulename) {
        $this->IcmsPersistableObjectHandler($db, 'category', 'categoryid', 'name', 'description', $modulename);
    }

    /**
     * Return all categories in an array
     *
     * @param int $parentid
     * @param string $perm_name
     * @param string $sort
     * @param string $order
     * @return array
     */
	function getAllCategoriesArray($parentid=0, $perm_name=false, $sort = 'parentid', $order='ASC') {

		if (!$this->allCategoriesObj) {
			$criteria = new CriteriaCompo();
			$criteria->setSort($sort);
			$criteria->setOrder($order);
			global $icmsUser;
			$userIsAdmin = is_object($icmsUser) && $icmsUser->isAdmin();

			if ($perm_name && !$userIsAdmin) {
				if (!$this->setGrantedObjectsCriteria($criteria, $perm_name)) {
					return false;
				}
			}

			$this->allCategoriesObj =& $this->getObjects($criteria, 'parentid');
		}

		$ret = array();
		if (isset($this->allCategoriesObj[$parentid])) {
			foreach($this->allCategoriesObj[$parentid] as $categoryid=>$categoryObj) {
				$ret[$categoryid]['self'] =& $categoryObj->toArray();
				if (isset($this->allCategoriesObj[$categoryid])) {
					$ret[$categoryid]['sub'] =& $this->getAllCategoriesArray($categoryid);
					$ret[$categoryid]['subcatscount'] = count($ret[$categoryid]['sub']);
				}
			}
		}
		return $ret;
	}

	function getParentIds($parentid, $asString=true) {

		if (!$this->allCategoriesId) {

	    	$ret = array();
	        $sql = 'SELECT categoryid, parentid FROM '.$this->table . " AS " . $this->_itemname . ' ORDER BY parentid';

	        $result = $this->db->query($sql);

	        if (!$result) {
	            return $ret;
	        }

	        while ($myrow = $this->db->fetchArray($result)) {
	        	$this->allCategoriesId[$myrow['categoryid']] =  $myrow['parentid'];
	        }
		}

		$retArray = array($parentid);
		while ($parentid != 0) {
			$parentid = $this->allCategoriesId[$parentid];
			if ($parentid != 0) {
				$retArray[] = $parentid;
			}
		}
		if ($asString) {
			return implode(', ', $retArray);
		} else {
			return $retArray;
		}
	}
}

?>