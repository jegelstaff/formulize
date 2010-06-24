<?php
/**
* Contains the basis classes for displaying a single IcmsPersistableObject
*
* @copyright	The ImpressCMS Project http://www.impresscms.org/
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package		IcmsPersistableObject
* @since		1.1
* @author		marcan <marcan@impresscms.org>
* @version		$Id: icmspersistablesingleview.php 9520 2009-11-11 14:32:52Z pesianstranger $
*/

/**
 * IcmsPersistableRow class
 *
 * Class representing a single row of a IcmsPersistableSingleView
 *
 * @package ImpressCMS Persistabke Framework
 * @author marcan <marcan@smartfactory.ca>
 * @link http://smartfactory.ca The SmartFactory
 */
class IcmsPersistableRow {

	var $_keyname;
	var $_align;
	var $_customMethodForValue;
	var $_header;
	var $_class;

	function IcmsPersistableRow($keyname, $customMethodForValue=false, $header=false, $class=false) {
		$this->_keyname = $keyname;
		$this->_customMethodForValue = $customMethodForValue;
		$this->_header = $header;
		$this->_class = $class;
	}

	function getKeyName() {
		return $this->_keyname;
	}

	function isHeader() {
		return $this->_header;
	}
}

/**
 * IcmsPersistableSingleView base class
 *
 * Base class handling the display of a single object
 *
 * @package ImpressCMS Persistabke Framework
 * @author marcan <marcan@smartfactory.ca>
 * @link http://smartfactory.ca The SmartFactory
 */
class IcmsPersistableSingleView {

	var $_object;
	var $_userSide;
	var $_tpl;
	var $_rows;
	var $_actions;
	var $_headerAsRow=true;

	/**
    * Constructor
    */
	function IcmsPersistableSingleView(&$object, $userSide=false, $actions=array(), $headerAsRow=true)
	{
		$this->_object = $object;
		$this->_userSide = $userSide;
		$this->_actions = $actions;
		$this->_headerAsRow = $headerAsRow;
	}

	function addRow($rowObj) {
		$this->_rows[] = $rowObj;
	}

	function render($fetchOnly=false, $debug=false)
	{
		include_once ICMS_ROOT_PATH . '/class/template.php';

		$this->_tpl =& new XoopsTpl();
		$vars = $this->_object->vars;
		$icms_object_array = array();

		foreach ($this->_rows as $row) {
			$key = $row->getKeyName();
			if ($row->_customMethodForValue && method_exists($this->_object, $row->_customMethodForValue)) {
				$method = $row->_customMethodForValue;
				$value = $this->_object->$method();
			} else {
				$value = $this->_object->getVar($row->getKeyName());
			}
			if ($row->isHeader()) {
				$this->_tpl->assign('icms_single_view_header_caption', $this->_object->vars[$key]['form_caption']);
				$this->_tpl->assign('icms_single_view_header_value', $value);
			} else {
				$icms_object_array[$key]['value'] = $value;
				$icms_object_array[$key]['header'] = $row->isHeader();
				$icms_object_array[$key]['caption'] = $this->_object->vars[$key]['form_caption'];
			}
		}
		$action_row = '';
		if (in_array('edit', $this->_actions)) {
			$action_row .= $this->_object->getEditItemLink(false, true, $this->_userSide);
		}
		if (in_array('delete', $this->_actions)) {
			$action_row .= $this->_object->getDeleteItemLink(false, true, $this->_userSide);
		}
		if ($action_row) {
			$icms_object_array['zaction']['value'] = $action_row;
			$icms_object_array['zaction']['caption'] = _CO_ICMS_ACTIONS;
		}

		$this->_tpl->assign('icms_header_as_row', $this->_headerAsRow);
		$this->_tpl->assign('icms_object_array', $icms_object_array);

		/**
		 * @todo when ICMS 1.2 is out, change this for system_persistable_singleview.html
		 */
		if ($fetchOnly) {
			return $this->_tpl->fetch( 'db:system_persistable_singleview.html' );
		} else {
			$this->_tpl->display( 'db:system_persistable_singleview.html' );
		}
	}

	function fetch($debug=false) {
		return $this->render(true, $debug);
	}
}

?>