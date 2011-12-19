<?php
/**
 * Editor framework for XOOPS
 *
 * @copyright	The XOOPS project http://www.xoops.org/
 * @license		http://www.fsf.org/copyleft/gpl.html GNU public license
 * @author		Taiwen Jiang (phppp or D.J.) <php_pp@hotmail.com>
 * @since		  1.00
 * @version		$Id: xoopseditor.php 20509 2010-12-11 12:02:57Z phoenyx $
 * @package		xoopseditor
 */
class XoopsEditorHandler extends icms_plugins_EditorHandler {
	private $_deprecated;
	
	public function __construct($type = '') {
		parent::__construct($type);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_plugins_EditorHandler', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}	
}