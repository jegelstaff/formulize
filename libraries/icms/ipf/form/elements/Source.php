<?php
/**
 * Form control creating a textbox for an object derived from icms_ipf_Object
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @category	ICMS
 * @package		ipf
 * @subpackage	form
 * @since		1.2
 * @author		MekDrop <mekdrop@gmail.com>
 * @version		$Id: Source.php 20504 2010-12-08 04:40:32Z skenow $
 */

defined('ICMS_ROOT_PATH') or die("ImpressCMS root path not defined");

class icms_ipf_form_elements_Source extends icms_form_elements_Textarea {
	/*
	 * Editor's class instance
	 */
	private $_editor = null;

	/**
	 * Constructor
	 * @param	object    $object   reference to targetobject (@link icms_ipf_Object)
	 * @param	string    $key      the form name
	 */
	public function __construct($object, $key) {
		global $icmsConfig;

		parent::__construct($object->vars[$key]['form_caption'], $key, $object->getVar($key, 'e'));

		$control = $object->getControl($key);

		$editor_handler = icms_plugins_EditorHandler::getInstance('source');
		$this->_editor = &$editor_handler->get($icmsConfig['sourceeditor_default'],
			array('name' => $key,
				'value' => $object->getVar($key, 'e'),
				'language' => isset($control['language']) ? $control['language'] : _LANGCODE,
				'width' => isset($control['width']) ? $control['width'] : '100%',
				'height' => isset($control['height']) ? $control['height'] : '400px',
				'syntax' => isset($control['syntax']) ? $control['syntax'] : 'php'));
	}

	/**
	 * Renders the editor
	 * @return	string  the constructed html string for the editor
	 */
	public function render() {
		if ($this->_editor) {
			return $this->_editor->render();
		} else {
			return parent::render();
		}
	}
}