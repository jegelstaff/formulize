<?php
/**
 * xos_opal_Theme component class file
 *
 * @copyright	The Xoops project http://www.xoops.org/
 * @license      http://www.fsf.org/copyleft/gpl.html GNU public license
 * @author       Skalpa Keo <skalpa@xoops.org>
 * @since        2.3.0
 * @version		$Id: theme.php 19473 2010-06-18 21:42:21Z david-sf $
 * @package 		core
 * @subpackage 	Templates
 */

/**
 * xos_opal_ThemeFactory
 *
 * @author 		Skalpa Keo
 * @package		xos_opal
 * @subpackage	xos_opal_Theme
 * @since        2.3.0
 * @deprecated	Use icms_view_theme_Factory, instead
 * @todo		Remove in version 1.4
 */
class xos_opal_ThemeFactory extends icms_view_theme_Factory {
	private $_deprecated;
	public function &createInstance($options = array(), $initArgs = array()) {
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_view_theme_Factory', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return parent::createInstance($options, $initArgs); // THIS LINE MOVED FROM ABOVE THE PREVIOUS LINE, AND RETURN ADDED.  BY FREEFORM SOLUTIONS JUNE 3 2012, FOR BACKWARDS COMPATIBILITY WITH CODE THAT RELIES ON THE XOOPS CLASS
	}
}

/**
 *
 * @deprecated	Use
 * @todo		Remove in version 1.4
 *
 */
class xos_opal_Theme extends icms_view_theme_Object {
	private $_deprecated;
	public function xoInit($options = array()) {
		parent::xoInit($options);
		$this->_deprecated('icms_view_theme_Object', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}

}