<?php
/**
* Form control creating a textbox for an object derived from IcmsPersistableObject
*
* @copyright	The ImpressCMS Project http://www.impresscms.org/
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package		IcmsPersistableObject
* @since		  1.2
* @author		  MekDrop <mekdrop@gmail.com>
* @version		$Id: icmsformsourceeditorelement.php 01 2009-06-09 11:34:22Z mekdrop $
*/

if (!defined('ICMS_ROOT_PATH')) die("ImpressCMS root path not defined");

class IcmsFormSourceEditor extends XoopsFormTextArea {

   /*
    * Editor's class instance
    */
   private $editor = null;

	/**
	 * Constructor
	 * @param	object    $object   reference to targetobject (@link IcmsPersistableObject)
	 * @param	string    $key      the form name
	 */
   function __construct($form_caption, $key, $value, $width = '100%', $height = '400px', $editor_name = null, $language='php') {		
   		$this->XoopsFormTextArea($form_caption, $key, $value);		
		
		if ($editor_name == null) {
			global $xoopsConfig;
			$editor_name = $xoopsConfig['sourceeditor_default'];			
		}

   		require_once ICMS_ROOT_PATH . '/class/xoopseditor.php';

   		$editor_handler = XoopsEditorHandler::getInstance('source');
		$this->editor = &$editor_handler->get($editor_name, array('name' => $key,
																  'value' => $value, 
																  'language' => $language, 
																  'width' => $width, 
																  'height' => $height));
   }

    /**
     * Renders the editor
     * @return	string  the constructed html string for the editor
	 */
	function render()
	{
		if ($this->editor) {
			return $this->editor->render();
		} else {
			return parent::render();
		}
	}

}

?>