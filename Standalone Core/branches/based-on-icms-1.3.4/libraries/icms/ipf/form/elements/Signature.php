<?php
/**
 * Form control creating a user signature textarea for an object derived from icms_ipf_Object
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @category	ICMS
 * @package		ipf
 * @subpackage	form
 * @since		1.1
 * @author		marcan <marcan@impresscms.org>
 * @version		$Id: Signature.php 10711 2010-10-10 17:11:29Z phoenyx $
 */

defined('ICMS_ROOT_PATH') or die("ImpressCMS root path not defined");

class icms_ipf_form_elements_Signature extends icms_form_elements_Tray {
	/**
	 * Constructor
	 * @param	object    $object   reference to targetobject (@link icms_ipf_Object)
	 * @param	string    $key      the form name
	 */
	public function __construct($object, $key){
		$var = $object->vars[$key];
		parent::__construct($var['form_caption'], '<br /><br />', $key . '_signature_tray');

		icms_loadLanguageFile('core', 'user');
		$signature_textarea = new icms_form_elements_Dhtmltextarea('', $key, $object->getVar($key, 'e'));
		$this->addElement($signature_textarea);
		$attach_checkbox = new icms_form_elements_Checkbox('', 'attachsig', $object->getVar('attachsig', 'e'));
		$attach_checkbox->addOption(1, _US_SHOWSIG);
		$this->addElement($attach_checkbox);
	}
}