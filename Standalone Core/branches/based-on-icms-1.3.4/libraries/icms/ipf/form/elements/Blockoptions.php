<?php
/**
 * Form control creating the options of a block
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @category	ICMS
 * @package		ipf
 * @subpackage	form
 * @since		1.2
 * @author		marcan <marcan@impresscms.org>
 * @author		phoenyx
 * @version		$Id: Blockoptions.php 10733 2010-10-16 17:31:12Z phoenyx $
 */

defined('ICMS_ROOT_PATH') or die("ImpressCMS root path not defined");

class icms_ipf_form_elements_Blockoptions extends icms_form_elements_Tray {
	/**
	 * Constructor
	 * @param	object    $object   reference to targetobject (@link icms_ipf_Object)
	 * @param	string    $key      the form name
	 */
	public function __construct($object, $key) {
		$var = $object->vars[$key];
		parent::__construct($var['form_caption'], ' ', 'options_tray');
		$func = $object->getVar('edit_func');

		require_once ICMS_ROOT_PATH . "/modules/" . $object->handler->getModuleDirname($object->getVar('mid', 'e')) . "/blocks/" . $object->getVar('func_file') ;
		icms_loadLanguageFile($object->handler->getModuleDirname($object->getVar('mid', 'e')), 'blocks');

		if (!function_exists($func)) return;
		$visible_label = new icms_form_elements_Label('', $func(explode('|', $object->getVar('options'))));
		$this->addElement($visible_label);
	}
}