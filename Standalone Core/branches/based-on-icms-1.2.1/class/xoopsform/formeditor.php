<?php
/**
* Creates a form editor object
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	LICENSE.txt
* @package	XoopsForms
* @since	XOOPS
* @author	http://www.xoops.org The XOOPS Project
* @author	modified by UnderDog <underdog@impresscms.org>
* @version	$Id: formeditor.php 8662 2009-05-01 09:04:30Z pesianstranger $
*/
/**
 *
 *
 * @package     kernel
 * @subpackage  form
 *
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 */

/**
 * XoopsEditor hanlder
 *
 * @author	D.J.
 * @copyright	copyright (c) 2000-2005 XOOPS.org
 *
 * @package     kernel
 * @subpackage  form
 */
class XoopsFormEditor extends XoopsFormTextArea
{
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
	function XoopsFormEditor($caption, $name, $editor_configs = null, $noHtml=false, $OnFailure = "")
	{
		$this->XoopsFormTextArea($caption, $editor_configs["name"]);
		require_once ICMS_ROOT_PATH."/class/xoopseditor.php";
		$editor_handler = XoopsEditorHandler::getInstance();
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

?>