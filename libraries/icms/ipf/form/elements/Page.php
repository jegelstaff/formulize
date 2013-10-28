<?php
/**
 * Form control creating a page element for an object derived from icms_ipf_Object
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @category	ICMS
 * @package		ipf
 * @subpackage	form
 * @since		1.1
 * @author		marcan <marcan@impresscms.org>
 * @version		$Id: Page.php 20289 2010-10-11 19:40:51Z phoenyx $
 */

defined('ICMS_ROOT_PATH') or die("ImpressCMS root path not defined");

class icms_ipf_form_elements_Page extends icms_form_elements_Tray {
	/**
	 * Constructor
	 * @param	object    $object   reference to targetobject (@link icms_ipf_Object)
	 * @param	string    $key      the form name
	 */
	public function __construct($object, $key) {
		icms_loadLanguageFile('system', 'blocksadmin', TRUE);
		parent::__construct(_AM_VISIBLEIN, ' ', $key . '_visiblein_tray');
		$visible_label = new icms_form_elements_Label('', '<select name="visiblein[]" id="visiblein[]" multiple="multiple" size="10">' . $this->getPageSelOptions($object->getVar('visiblein')) . '</select>');
		$this->addElement($visible_label);
	}

	/**
	 * build options string
	 *
	 * @param mixed $value module-page combination
	 * @return string html
	 */
	private function getPageSelOptions($value = NULL){
		$icms_page_handler = icms::handler('icms_data_page');
		if (!is_array($value)){
			$value = array($value);
		}
		$module_handler = icms::handler('icms_module');
		$criteria = new icms_db_criteria_Compo(new icms_db_criteria_Item('hasmain', 1));
		$criteria->add(new icms_db_criteria_Item('isactive', 1));
		$module_list = $module_handler->getObjects($criteria);
		$mods = '';
		foreach ($module_list as $module){
			$mods .= '<optgroup label="' . $module->getVar('name') . '">';
			$criteria = new icms_db_criteria_Compo(new icms_db_criteria_Item('page_moduleid', $module->getVar('mid')));
			$criteria->add(new icms_db_criteria_Item('page_status', 1));
			$pages = $icms_page_handler->getObjects($criteria);
			$sel = '';
			if (in_array($module->getVar('mid') . '-0', $value)){
				$sel = ' selected=selected';
			}
			$mods .= '<option value="' . $module->getVar('mid') . '-0"' . $sel . '>' . _AM_ALLPAGES . '</option>';
			foreach ($pages as $page){
				$sel = '';
				if (in_array($module->getVar('mid') . '-' . $page->getVar('page_id'), $value)){
					$sel = ' selected=selected';
				}
				$mods .= '<option value="' . $module->getVar('mid') . '-' . $page->getVar('page_id') . '"' . $sel . '>';
				$mods .= $page->getVar('page_title') . '</option>';
			}
			$mods .= '</optgroup>';
		}

		$module = $module_handler->get(1);
		$criteria = new icms_db_criteria_Compo(new icms_db_criteria_Item('page_moduleid', 1));
		$criteria->add(new icms_db_criteria_Item('page_status', 1));
		$pages = $icms_page_handler->getObjects($criteria);
		$cont = '';
		if (count($pages) > 0){
			$cont = '<optgroup label="' . $module->getVar('name') . '">';
			$sel = '';
			if (in_array($module->getVar('mid') . '-0', $value)){
				$sel = ' selected=selected';
			}
			$cont .= '<option value="' . $module->getVar('mid') . '-0"' . $sel . '>' . _AM_ALLPAGES . '</option>';
			foreach ($pages as $page){
				$sel = '';
				if (in_array($module->getVar('mid').'-'.$page->getVar('page_id'),$value)) {
					$sel = ' selected=selected';
				}
				$cont .= '<option value="' . $module->getVar('mid') . '-' . $page->getVar('page_id') . '"' . $sel . '>';
				$cont .= $page->getVar('page_title') . '</option>';
			}
			$cont .= '</optgroup>';
		}
		$sel = $sel1 = '';
		if (in_array('0-1', $value)){
			$sel = ' selected=selected';
		}
		if (in_array('0-0', $value)){
			$sel1 = ' selected=selected';
		}
		$ret = '<option value="0-1"' . $sel . '>' . _AM_TOPPAGE . '</option>';
		$ret .= '<option value="0-0"' . $sel1 . '>' . _AM_ALLPAGES . '</option>';
		$ret .= $cont.$mods;

		return $ret;
	}
}