<?php
/**
* Persistable object registry
*
* @copyright      http://www.impresscms.org/ The ImpressCMS Project
* @license         LICENSE.txt
* @package	IcmsPersistableObject
* @since            1.1
* @author		marcan <marcan@impresscms.org>
* @version		$Id: icmspersistableregistry.php 8842 2009-06-12 15:22:49Z pesianstranger $
*/



if (!defined('ICMS_ROOT_PATH')) die("ImpressCMS root path not defined");

/**
* Registry of IcmsPersistableObject
*
* Class responsible of caching objects to make them easily reusable without querying the database
*
* @copyright	The ImpressCMS Project http://www.impresscms.org/
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package		IcmsPersistableObject
* @since		1.1
* @author		marcan <marcan@impresscms.org>
* @version		$Id: icmspersistableregistry.php 8842 2009-06-12 15:22:49Z pesianstranger $
*/
class IcmsPersistableRegistry {

	var $_registryArray;

	/**
	 * Access the only instance of this class
     *
     * @return	object
     *
     * @static
     * @staticvar   object
	 */
	function &getInstance()
	{
		static $instance;
		if (!isset($instance)) {
			$instance = new IcmsPersistableRegistry();
		}
		return $instance;
	}

	/**
    * Adding objects to the registry
    *
    * @param IcmsPersistableObjectHandler $handler of the objects to add
    * @param CriteriaCompo $criteria to pass to the getObjects method of the handler (with id_as_key)
    *
    * @return FALSE if an error occured
    */
	function addObjectsFromHandler(&$handler, $criteria=false) {
		if (method_exists($handler, 'getObjects')) {
			$objects = $handler->getObjects($criteria, true);
			$this->_registryArray['objects'][$handler->_moduleName][$handler->_itemname] = $objects;
			return $objects;
		} else {
			return false;
		}
	}

	/**
    * Adding objects as list to the registry
    *
    * @param IcmsPersistableObjectHandler $handler of the objects to add
    * @param CriteriaCompo $criteria to pass to the getObjects method of the handler (with id_as_key)
    *
    * @return FALSE if an error occured
    */
	function addListFromHandler(&$handler, $criteria=false) {
		if (method_exists($handler, 'getList')) {
			$list = $handler->getList($criteria);
			$this->_registryArray['list'][$handler->_moduleName][$handler->_itemname] = $list;
			return $list;
		} else {
			return false;
		}
	}

	/**
    * Adding objects to the registry from an item name
    * This method will fetch the handler of the item / module and call the addObjectsFromHandler
    *
    * @param string $item name of the item
    * @param string $modulename name of the module
    * @param CriteriaCompo $criteria to pass to the getObjects method of the handler (with id_as_key)
    *
    * @return FALSE if an error occured
    */
	function addObjectsFromItemName($item, $modulename=false, $criteria=false) {
		if (!$modulename) {
			global $icmsModule;
			if (!is_object($icmsModule)) {
				return false;
			} else {
				$modulename = $icmsModule->dirname();
			}
		}
		$object_handler = xoops_getModuleHandler($item, $modulename);
		return $this->addObjectsFromHandler($object_handler, $criteria);
	}

	/**
    * Adding objects as a list to the registry from an item name
    * This method will fetch the handler of the item / module and call the addListFromHandler
    *
    * @param string $item name of the item
    * @param string $modulename name of the module
    * @param CriteriaCompo $criteria to pass to the getObjects method of the handler (with id_as_key)
    *
    * @return FALSE if an error occured
    */
	function addListFromItemName($item, $modulename=false, $criteria=false) {
		if (!$modulename) {
			global $icmsModule;
			if (!is_object($icmsModule)) {
				return false;
			} else {
				$modulename = $icmsModule->dirname();
			}
		}
		$object_handler = xoops_getModuleHandler($item, $modulename);
		return $this->addListFromHandler($object_handler, $criteria);

	}

	/**
    * Fetching objects from the registry
    *
    * @param string $itemname
    * @param string $modulename
    *
    * @return the requested objects or FALSE if they don't exists in the registry
    */
	function getObjects($itemname, $modulename) {
		if (!$modulename) {
			global $icmsModule;
			if (!is_object($icmsModule)) {
				return false;
			} else {
				$modulename = $icmsModule->dirname();
			}
		}
		if (isset($this->_registryArray['objects'][$modulename][$itemname])) {
			return $this->_registryArray['objects'][$modulename][$itemname];
		} else {
			// if they were not in registry, let's fetch them and add them to the reigistry
			$module_handler = xoops_getModuleHandler($itemname, $modulename);
			if (method_exists($module_handler, 'getObjects')) {
				$objects = $module_handler->getObjects();
			}
			$this->_registryArray['objects'][$modulename][$itemname] = $objects;
			return $objects;
		}
	}

	/**
    * Fetching objects from the registry, as a list : objectid => identifier
    *
    * @param string $itemname
    * @param string $modulename
    *
    * @return the requested objects or FALSE if they don't exists in the registry
    */
	function getList($itemname, $modulename) {
		if (!$modulename) {
			global $icmsModule;
			if (!is_object($icmsModule)) {
				return false;
			} else {
				$modulename = $icmsModule->dirname();
			}
		}
		if (isset($this->_registryArray['list'][$modulename][$itemname])) {
			return $this->_registryArray['list'][$modulename][$itemname];
		} else {
			// if they were not in registry, let's fetch them and add them to the reigistry
			$module_handler = xoops_getModuleHandler($itemname, $modulename);
			if (method_exists($module_handler, 'getList')) {
				$objects = $module_handler->getList();
			}
			$this->_registryArray['list'][$modulename][$itemname] = $objects;
			return $objects;
		}
	}

	/**
    * Retreive a single object
    *
    * @param string $itemname
    * @param string $key
    *
    * @return the requestd object or FALSE if they don't exists in the registry
    */
	function getSingleObject($itemname, $key, $modulename=false) {
		if (!$modulename) {
			global $icmsModule;
			if (!is_object($icmsModule)) {
				return false;
			} else {
				$modulename = $icmsModule->dirname();
			}
		}
		if (isset($this->_registryArray['objects'][$modulename][$itemname][$key])) {
			return $this->_registryArray['objects'][$modulename][$itemname][$key];
		} else {
			$objectHandler = xoops_getModuleHandler($itemname, $modulename);
			$object = $objectHandler->get($key);

			if (!$object->isNew()) {
				$this->_registryArray['objects'][$modulename][$itemname][$key] = $object;
				return $object;
			} else {
				return false;
			}
		}
	}
}

?>