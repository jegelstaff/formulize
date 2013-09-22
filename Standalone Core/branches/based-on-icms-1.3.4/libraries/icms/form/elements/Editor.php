<?php
/**
 * Creates a form editor object
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	LICENSE.txt
 * @category	ICMS
 * @package		Form
 * @subpackage	Elements
 * @since	XOOPS
 * @author	http://www.xoops.org The XOOPS Project
 * @author	modified by UnderDog <underdog@impresscms.org>
 * @version	$Id: Editor.php 11569 2012-02-09 23:15:32Z fiammy $
 */

if (!defined('ICMS_ROOT_PATH')) die("ImpressCMS root path not defined");

/**
 * XoopsEditor hanlder
 *
 * @author	D.J.
 * @copyright	copyright (c) 2000-2005 XOOPS.org
 *
 * @todo		To be removed as this is not used anywhere in the core
 */
class icms_form_elements_Editor extends icms_form_elements_Textarea {
	var $editor;

	/**
	 * Constructor
	 *
	 * @param	string  $caption    Caption
	 * @param	string  $name       "name" attribute
	 * @param	string  $value      Initial text
	 * @param	array 	$configs     configures
	 * @param	bool  	$noHtml       use non-WYSIWYG eitor onfailure
	 * @param	string  $OnFailure editor to be used if current one failed
	 */
	function icms_form_elements_Editor($caption, $name, $editor_configs = null, $noHtml=false, $OnFailure = "")
	{
		parent::__construct($caption, $editor_configs["name"]);
		$editor_handler = icms_plugins_EditorHandler::getInstance();
		$this->editor =& $editor_handler->get($name, $editor_configs, $noHtml, $OnFailure);
	}

	/**
	 * Renders the editor
	 * @return	string  the constructed html string for the editor
	 */
	function render()
	{
		return $this->editor->render();
	}
}