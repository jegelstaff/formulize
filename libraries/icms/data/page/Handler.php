<?php
/**
 * Classes responsible for managing core page objects
 *
 * @copyright	The ImpressCMS Project <http://www.impresscms.org/>
 * @license		LICENSE.txt
 * @category	ICMS
 * @package		Page
 * @since		ImpressCMS 1.1
 * @author		modified by UnderDog <underdog@impresscms.org>
 * @author		Gustavo Pilla (aka nekro) <nekro@impresscms.org> <gpilla@nubee.com.ar>
 * @version		SVN: $Id: Handler.php 20110 2010-09-08 17:59:13Z malanciault $
 */

defined('ICMS_ROOT_PATH') or die('ImpressCMS root path not defined');

/**
 * ImpressCMS page handler class.
 *
 * @since	ImpressCMS 1.2
 * @author	Gustavo Pilla (aka nekro) <nekro@impresscms.org> <gpilla@nubee.com.ar>
 */
class icms_data_page_Handler extends icms_ipf_Handler {

	public function __construct(& $db) {
		parent::__construct($db, 'page', 'page_id', 'page_title', '', 'icms');
		$this->table = $db->prefix('icmspage');
		$this->className = 'icms_data_page_Object';
	}

	public function getList($criteria = null, $limit = 0, $start = 0, $debug = false) {
		$rtn = array();
		$pages =& $this->getObjects($criteria, true);
		foreach ($pages as $page) {
			$rtn[$page->getVar('page_moduleid') . '-' . $page->getVar('page_id')] = $page->getVar('page_title');
		}
		return $rtn;
	}

	public function getPageSelOptions($value = null) {
		if (!is_array($value)) {
			$value = array($value);
		}
		$module_handler = icms::handler('icms_module');
		$criteria = new icms_db_criteria_Compo(new icms_db_criteria_Item('hasmain', 1));
		$criteria->add(new icms_db_criteria_Item('isactive', 1));
		$module_list =& $module_handler->getObjects($criteria);
		$mods = '';
		foreach ($module_list as $module) {
			$mods .= '<optgroup label="' . $module->getVar('name') . '">';
			$criteria = new icms_db_criteria_Compo(new icms_db_criteria_Item('page_moduleid', $module->getVar('mid')));
			$criteria->add(new icms_db_criteria_Item('page_status', 1));
			$pages =& $this->getObjects($criteria);
			$sel = '';
			if (in_array($module->getVar('mid') . '-0', $value)) {
				$sel = ' selected=selected';
			}
			$mods .= '<option value="' . $module->getVar('mid') . '-0"' . $sel . '>' . _AM_ALLPAGES . '</option>';
			foreach ($pages as $page) {
				$sel = '';
				if (in_array($module->getVar('mid') . '-' . $page->getVar('page_id'), $value)) {
					$sel = ' selected=selected';
				}
				$mods .= '<option value="'
						. $module->getVar('mid') . '-' . $page->getVar('page_id') . '"' . $sel . '>'
						. $page->getVar('page_title')
						 . '</option>';
			}
			$mods .= '</optgroup>';
		}

		$module = $module_handler->get(1);
		$criteria = new icms_db_criteria_Compo(new icms_db_criteria_Item('page_moduleid', 1));
		$criteria->add(new icms_db_criteria_Item('page_status', 1));
		$pages =& $this->getObjects($criteria);
		$cont = '';
		if (count($pages) > 0) {
			$cont = '<optgroup label="' . $module->getVar('name') . '">';
			$sel = '';
			if (in_array($module->getVar('mid') . '-0', $value)) {
				$sel = ' selected=selected';
			}
			$cont .= '<option value="' . $module->getVar('mid') . '-0"' . $sel . '>' . _AM_ALLPAGES . '</option>';
			foreach ($pages as $page) {
				$sel = '';
				if (in_array($module->getVar('mid') . '-' . $page->getVar('page_id'), $value)) {
					$sel = ' selected=selected';
				}
				$cont .= '<option value="' . $module->getVar('mid') . '-' . $page->getVar('page_id') . '"' . $sel . '>'
						. $page->getVar('page_title')
						. '</option>';
			}
			$cont .= '</optgroup>';
		}
		$sel = $sel1 = '';
		if (in_array('0-1',$value)) {
			$sel = ' selected=selected';
		}
		if (in_array('0-0',$value)) {
			$sel1 = ' selected=selected';
		}
		$ret = '<option value="0-1"' . $sel . '>'. _AM_TOPPAGE
			. '</option><option value="0-0"' . $sel1 . '>' . _AM_ALLPAGES
			. '</option>';
		$ret .= $cont . $mods;

		return $ret;
	}
}

