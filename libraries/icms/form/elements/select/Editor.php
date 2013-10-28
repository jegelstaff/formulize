<?php
/**
 * Creates a form attribute which is able to select an editor
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @category	ICMS
 * @package		Forms
 * @subpackage	Elements
 * @version		SVN: $Id: formselecteditor.php 19892 2010-07-27 00:12:10Z skenow $
 */

/**
 * A select box with available editors
 *
 * @category	ICMS
 * @package     Form
 * @subpackage  Elements
 *
 * @author	    phppp (D.J.)
 */
class icms_form_elements_select_Editor extends icms_form_elements_Tray {
	/**
	 * Constructor
	 *
	 * @param	object	$form	the form calling the editor selection
	 * @param	string	$name	editor name
	 * @param	string	$value	Pre-selected text value
	 * @param	bool	$noHtml  dohtml disabled
	 */
	public function __construct(&$form, $name = "editor", $value = NULL, $noHtml = FALSE) {
		global $icmsConfig;

		if (empty($value)){
			$value = $icmsConfig['editor_default'];
		}

		parent::__construct(_SELECT);
		$edtlist = icms_plugins_EditorHandler::getListByType();
		$option_select = new icms_form_elements_Select("", $name, $value);
		$querys = preg_replace('/editor=(.*?)&/','',$_SERVER['QUERY_STRING']);
		$extra = 'onchange="if(this.options[this.selectedIndex].value.length > 0 ){
				window.location = \'?editor=\'+this.options[this.selectedIndex].value+\'&'.$querys.'\';
			}"';
		$option_select->setExtra($extra);
		$option_select->addOptionArray($edtlist);

		$this->addElement($option_select);
	}
}
