<?php
/**
 * Form control creating a parent category selectbox for an object derived from icms_ipf_Object
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @category	ICMS
 * @package		ipf
 * @subpackage	form
 * @since		1.1
 * @author		marcan <marcan@impresscms.org>
 * @version		$Id: Parentcategory.php 10833 2010-12-04 21:47:36Z skenow $
 */

defined('ICMS_ROOT_PATH') or die("ImpressCMS root path not defined");

class icms_ipf_form_elements_Parentcategory extends icms_form_elements_Select {
	/**
	 * Constructor
	 * @param	object    $object   reference to targetobject (@link icms_ipf_Object)
	 * @param	string    $key      the form name
	 */
	public function __construct($object, $key) {
		$category_title_field = $object->handler->identifierName;

		$addNoParent = isset($object->controls[$key]['addNoParent']) ? $object->controls[$key]['addNoParent'] : true;
		$criteria = new icms_db_criteria_Compo();
		$criteria->setSort("weight, " . $category_title_field);
		$category_handler = icms_getModuleHandler('category', $object->handler->_moduleName);
		$categories = $category_handler->getObjects($criteria);

		$mytree = new icms_ipf_Tree($categories, "category_id", "category_pid");
		parent::__construct($object->vars[$key]['form_caption'], $key, $object->getVar($key, 'e'));

		$ret = array();
		$options = $this->getOptionArray($mytree, $category_title_field, 0, $ret, "");
		if ($addNoParent) {
			$newOptions = array('0' => '----');
			foreach ($options as $k => $v) {
				$newOptions[$k] = $v;
			}
			$options = $newOptions;
		}
		$this->addOptionArray($options);
	}

	/**
	 * Get options for a category select with hierarchy (recursive)
	 *
	 * @param object  $tree         icms_ipf_Tree $tree (@link icms_ipf_Tree)
	 * @param string  $fieldName    The fieldname to get the option array for
	 * @param int     $key          the key to get the optionarray for
	 * @param string  $prefix_curr  the prefix
	 * @param array   $ret          passed return array
	 *
	 * @return array  $ret          the constructed option array
	 */
	private function getOptionArray($tree, $fieldName, $key, &$ret, $prefix_curr = "") {
		if ($key > 0) {
			$value = $tree->_tree[$key]['obj']->getVar($tree->_myId);
			$ret[$key] = $prefix_curr . $tree->_tree[$key]['obj']->getVar($fieldName);
			$prefix_curr .= "-";
		}

		if (isset($tree->_tree[$key]['child']) && !empty($tree->_tree[$key]['child'])) {
			foreach ($tree->_tree[$key]['child'] as $childkey) {
				$this->getOptionArray($tree, $fieldName, $childkey, $ret, $prefix_curr);
			}
		}
		return $ret;
	}
}