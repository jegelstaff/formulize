<?php
/**
* Handler registry Class
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	LICENSE.txt
* @package	core
* @since	XOOPS
* @author	http://www.xoops.org The XOOPS Project
* @author	modified by UnderDog <underdog@impresscms.org>
* @version	$Id: handlerregistry.php 9520 2009-11-11 14:32:52Z pesianstranger $
*/

/**
 *
 *
 * @package     kernel
 *
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 */

/**
 * A registry for holding references to {@link XoopsObjectHandler} classes
 *
 * @package     kernel
 *
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 */
class XoopsHandlerRegistry
{
	/**
	 * holds references to handler class objects
	 *
	 * @var     array
	 * @access	private
	 */
	var $_handlers = array();

	/**
	 * get a reference to the only instance of this class
	 *
	 * if the class has not been instantiated yet, this will also take
	 * care of that
	 *
	 * @static
	 * @staticvar   object  The only instance of this class
	 * @return      object  Reference to the only instance of this class
	 */
	function &instance()
	{
		static $instance;
		if (!isset($instance)) {
			$instance = new XoopsHandlerRegistry();
		}
		return $instance;
	}

	/**
	 * Register a handler class object
	 *
	 * @param	string  $name     Short name of a handler class
	 * @param	object  &$handler {@link XoopsObjectHandler} class object
	 */
	function setHandler($name, &$handler)
	{
		$this->_handlers['kernel'][$name] =& $handler;
	}

	/**
	 * Get a registered handler class object
	 *
	 * @param	string  $name     Short name of a handler class
	 *
	 * @return	object {@link XoopsObjectHandler}, FALSE if not registered
	 */
	function &getHandler($name)
	{
		if (!isset($this->_handlers['kernel'][$name])) {
			return false;
		}
		return $this->_handlers['kernel'][$name];
	}

	/**
	 * Unregister a handler class object
	 *
	 * @param	string  $name     Short name of a handler class
	 */
	function unsetHandler($name)
	{
		unset($this->_handlers['kernel'][$name]);
	}

	/**
	 * Register a handler class object for a module
	 *
	 * @param	string  $module   Directory name of a module
	 * @param	string  $name     Short name of a handler class
	 * @param	object  &$handler {@link XoopsObjectHandler} class object
	 */
	function setModuleHandler($module, $name, &$handler)
	{
		$this->_handlers['module'][$module][$name] =& $handler;
	}

	/**
	 * Get a registered handler class object for a module
	 *
	 * @param	string  $module   Directory name of a module
	 * @param	string  $name     Short name of a handler class
	 *
	 * @return	object {@link XoopsObjectHandler}, FALSE if not registered
	 */
	function &getModuleHandler($module, $name)
	{
		if (!isset($this->_handlers['module'][$module][$name])) {
			return false;
		}
		return $this->_handlers['module'][$module][$name];
	}

	/**
	 * Unregister a handler class object for a module
	 *
	 * @param	string  $module   Directory name of a module
	 * @param	string  $name     Short name of a handler class
	 */
	function unsetModuleHandler($module, $name)
	{
		unset($this->_handlers['module'][$module][$name]);
	}

}

?>