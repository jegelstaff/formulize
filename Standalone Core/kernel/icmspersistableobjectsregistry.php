<?php
/**
* IcmsPersistableObjects Registry
*
* The IcmsPersistableObjects Registry is an object containing IcmsPersistableObject objects that will be reused in the same process
*
* @copyright	The ImpressCMS Project http://www.impresscms.org/
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package		IcmsPersistableObject
* @since		1.2
* @author		marcan <marcan@impresscms.org>
* @author	    Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
* @version		$Id: icmspersistableobjectsregistry.php 8768 2009-05-16 22:48:26Z pesianstranger $
*/

if (!defined("ICMS_ROOT_PATH")) {
	die("ImpressCMS root path not defined");
}

class IcmsPersistableObjectsRegistry {

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
			$instance = new IcmsPersistableObjectsRegistry();
		}
		return $instance;
	}

	/**
    * Adding objects to the registry
    *
    * @param SmartPersistableObjectHandler $handler of the objects to add
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

		if (method_exists($object_handler, 'getObjects')) {
			$objects = $object_handler->getObjects($criteria, true);
			$this->_registryArray['objects'][$object_handler->_moduleName][$object_handler->_itemname] = $objects;
			return $objects;
		} else {
			return false;
		}
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