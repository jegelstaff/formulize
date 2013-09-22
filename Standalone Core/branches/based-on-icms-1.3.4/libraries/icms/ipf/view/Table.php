<?php
/**
 * icms_ipf_Object Table Listing
 *
 * Contains the classes responsible for displaying a highly configurable and features rich listing of IcmseristableObject objects
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @category	ICMS
 * @package		Ipf
 * @subpackage	View
 * @since		1.1
 * @author		marcan <marcan@impresscms.org>
 * @version		SVN: $Id: Table.php 12013 2012-09-11 17:41:40Z m0nty $
 */

defined('ICMS_ROOT_PATH') or die('ImpressCMS root path not defined');

/**
 * icms_ipf_view_Table base class
 *
 * Base class representing a table for displaying icms_ipf_Object objects
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @category	ICMS
 * @package		Ipf
 * @subpackage	View
 * @since		1.1
 * @author		marcan <marcan@impresscms.org>
 * @todo		Properly declare all vars with their visibility (private, protected, public) and follow naming convention
 */
class icms_ipf_view_Table {

	var $_id;
	var $_objectHandler;
	var $_columns;
	var $_criteria;
	var $_actions;
	var $_objects = false;
	var $_aObjects;
	var $_custom_actions;
	var $_sortsel;
	var $_ordersel;
	var $_limitsel;
	var $_filtersel;
	var $_filterseloptions;
	var $_filtersel2;
	var $_filtersel2options;
	var $_filtersel2optionsDefault;

	var $_tempObject;
	var $_tpl;
	var $_introButtons;
	var $_quickSearch = false;
	var $_actionButtons = false;
	var $_head_css_class = 'bg3';
	var $_hasActions = false;
	var $_userSide = false;
	var $_printerFriendlyPage = false;
	var $_tableHeader = false;
	var $_tableFooter = false;
	var $_showActionsColumnTitle = true;
	var $_isTree = false;
	var $_showFilterAndLimit = true;
	var $_enableColumnsSorting = true;
	var $_customTemplate = false;
	var $_withSelectedActions = array();

	/**
	 * Constructor
	 *
	 * @param object $objectHandler {@link icms_ipf_Handler}
	 * @param array $columns array representing the columns to display in the table
	 * @param object $criteria
	 * @param array $actions array representing the actions to offer
	 *
	 * @return array
	 */
	public function __construct(&$objectHandler, $criteria = false, $actions = array('edit', 'delete'), $userSide = false) {
		$this->_id = $objectHandler->className;
		$this->_objectHandler = $objectHandler;

		if (!$criteria) {
			$criteria = new icms_db_criteria_Compo();
		}
		$this->_criteria = $criteria;
		$this->_actions = $actions;
		$this->_custom_actions = array();
		$this->_userSide = $userSide;
		if ($userSide) {
			$this->_head_css_class = 'head';
		}
	}

	/**
	 *
	 * @param $op
	 * @param $caption
	 * @param $text
	 */
	public function addActionButton($op, $caption = false, $text = false) {
		$action = array(
					'op' => $op,
					'caption' => $caption,
					'text' => $text
		);
		$this->_actionButtons[] = $action;
	}

	/**
	 *
	 * @param $columnObj
	 */
	public function addColumn($columnObj) {
		$this->_columns[] = $columnObj;
	}

	/**
	 *
	 * @param $name
	 * @param $location
	 * @param $value
	 */
	public function addIntroButton($name, $location, $value) {
		$introButton = array();
		$introButton['name'] = $name;
		$introButton['location'] = $location;
		$introButton['value'] = $value;
		$this->_introButtons[] = $introButton;
		unset($introButton);
	}

	/**
	 *
	 */
	public function addPrinterFriendlyLink() {
		global $impresscms;

		$current_url = $impresscms->urls['full'];
		$this->_printerFriendlyPage = $current_url . '&print';
	}

	/**
	 *
	 * @param $fields
	 * @param $caption
	 */
	public function addQuickSearch($fields, $caption = _CO_ICMS_QUICK_SEARCH) {
		$this->_quickSearch = array('fields' => $fields, 'caption' => $caption);
	}

	/**
	 *
	 * @param unknown_type $content
	 */
	public function addHeader($content) {
		$this->_tableHeader = $content;
	}

	/**
	 *
	 * @param $content
	 */
	public function addFooter($content) {
		$this->_tableFooter = $content;
	}

	/**
	 *
	 * @param $caption
	 */
	public function addDefaultIntroButton($caption) {
		$this->addIntroButton($this->_objectHandler->_itemname, $this->_objectHandler->_page . "?op=mod", $caption);
	}

	/**
	 *
	 * @param $method
	 */
	public function addCustomAction($method) {
		$this->_custom_actions[] = $method;
	}

	/**
	 *
	 * @param $default_sort
	 */
	public function setDefaultSort($default_sort) {
		$this->_sortsel = $default_sort;
	}

	/**
	 *
	 */
	public function getDefaultSort() {
		if ($this->_sortsel) {
			return icms_getCookieVar($_SERVER['SCRIPT_NAME'] . '_' . $this->_id . '_sortsel', $this->_sortsel);
		} else {
			return icms_getCookieVar($_SERVER['SCRIPT_NAME'] . '_' . $this->_id . '_sortsel', $this->_objectHandler->identifierName);
		}
	}

	/**
	 *
	 * @param unknown_type $default_order
	 */
	public function setDefaultOrder($default_order) {
		$this->_ordersel = $default_order;
	}

	/**
	 *
	 */
	public function getDefaultOrder() {
		if ($this->_ordersel) {
			return icms_getCookieVar($_SERVER['SCRIPT_NAME'] . '_' . $this->_id . '_ordersel', $this->_ordersel);
		} else {
			return icms_getCookieVar($_SERVER['SCRIPT_NAME'] . '_' . $this->_id . '_ordersel', 'ASC');
		}
	}

	/**
	 *
	 * @param $actions
	 */
	public function addWithSelectedActions($actions = array()) {
		$this->addColumn(new icms_ipf_view_Column('checked', 'center', 20, false, false, '&nbsp;'));
		$this->_withSelectedActions = $actions;
	}

	/**
	 * Adding a filter in the table
	 *
	 * @param string $key key to the field that will be used for sorting
	 * @param string $method method of the handler that will be called to populate the options when this filter is selected
	 */
	public function addFilter($key, $method, $default = false) {
		$this->_filterseloptions[$key] = $method;
		$this->_filtersel2optionsDefault = $default;
	}

	/**
	 *
	 * @param $default_filter
	 */
	public function setDefaultFilter($default_filter) {
		$this->_filtersel = $default_filter;
	}

	/**
	 *
	 */
	public function isForUserSide() {
		$this->_userSide = true;
	}

	/**
	 *
	 * @param $template
	 */
	public function setCustomTemplate($template) {
		$this->_customTemplate = $template;
	}

	/**
	 *
	 */
	public function setSortOrder() {
		$this->_sortsel = isset($_GET[$this->_objectHandler->_itemname . '_' . 'sortsel']) ? $_GET[$this->_objectHandler->_itemname . '_' . 'sortsel'] : $this->getDefaultSort();
		//$this->_sortsel = isset($_POST['sortsel']) ? $_POST['sortsel'] : $this->_sortsel;

		icms_setCookieVar($_SERVER['SCRIPT_NAME'] . '_' . $this->_id . '_sortsel', $this->_sortsel);
		$fieldsForSorting = $this->_tempObject->getFieldsForSorting($this->_sortsel);

		if (isset($this->_tempObject->vars[$this->_sortsel]['itemName'])) {
			$this->_criteria->setSort($this->_tempObject->vars[$this->_sortsel]['itemName'] . "." . $this->_sortsel);
		} else {
			$this->_criteria->setSort($this->_objectHandler->_itemname . "." . $this->_sortsel);
		}

		$this->_ordersel = isset($_GET[$this->_objectHandler->_itemname . '_' . 'ordersel']) ? $_GET[$this->_objectHandler->_itemname . '_' . 'ordersel'] : $this->getDefaultOrder();
		//$this->_ordersel = isset($_POST['ordersel']) ? $_POST['ordersel'] :$this->_ordersel;
		icms_setCookieVar($_SERVER['SCRIPT_NAME'] . '_' . $this->_id . '_ordersel', $this->_ordersel);
		$ordersArray = $this->getOrdersArray();
		$this->_criteria->setOrder($this->_ordersel);
	}

	/**
	 *
	 * @param $id
	 */
	public function setTableId($id) {
		$this->_id = $id;
	}

	/**
	 *
	 * @param $objects
	 */
	public function setObjects($objects) {
		$this->_objects = $objects;
	}

	/**
	 *
	 */
	public function createTableRows() {
		$this->_aObjects = array();

		$doWeHaveActions = false;

		$objectclass = 'odd';

		if (count($this->_objects) > 0) {
			foreach ($this->_objects as $object) {

				$aObject = array();

				$i = 0;

				$aColumns = array();

				foreach ($this->_columns as $column) {

					$aColumn = array();

					if ($i==0) {
						$class = "head";
					} elseif ($i % 2 == 0) {
						$class = "even";
					} else {
						$class = "odd";
					}
					if (method_exists($object, 'initiateCustomFields')) {
						//$object->initiateCustomFields();
					}
					if ($column->_keyname == 'checked') {
						$value = '<input type ="checkbox" name="selected_icms_persistableobjects[]" value="' . $object->id() . '" />';
					} elseif ($column->_customMethodForValue && method_exists($object, $column->_customMethodForValue)) {
						$method = $column->_customMethodForValue;
						if ($column->_param) {
							$value = $object->$method($column->_param);
						} else {
							$value = $object->$method();
						}
					} else {
						/**
						 * If the column is the identifier, then put a link on it
						 */
						if ($column->getKeyName() == $this->_objectHandler->identifierName) {
							$value = $object->getViewItemLink(false, false, $this->_userSide);
						} else {
							$value = $object->getVar($column->getKeyName());
						}
					}

					$aColumn['keyname'] = $column->getKeyName();
					$aColumn['value'] = $value;
					$aColumn['class'] = $class;
					$aColumn['width'] = $column->getWidth();
					$aColumn['align'] = $column->getAlign();

					$aColumns[] = $aColumn;
					$i++;
				}

				$aObject['columns'] = $aColumns;
				$aObject['id'] = $object->id();

				$objectclass = ($objectclass == 'even') ? 'odd' : 'even';

				$aObject['class'] = $objectclass;

				$actions = array();

				// Adding the custom actions if any
				foreach ($this->_custom_actions as $action) {
					if (method_exists($object, $action)) {
						$actions[] = $object->$action();
					}
				}

				if ((!is_array($this->_actions)) || in_array('edit', $this->_actions)) {
					$actions[] = $object->getEditItemLink(false, true, $this->_userSide);
				}
				if ((!is_array($this->_actions)) || in_array('delete', $this->_actions)) {
					$actions[] = $object->getDeleteItemLink(false, true, $this->_userSide);
				}
				$aObject['actions'] = $actions;

				$this->_tpl->assign('icms_actions_column_width', count($actions) * 30);

				$doWeHaveActions = $doWeHaveActions ? true : count($actions) > 0;

				$this->_aObjects[] = $aObject;
			}
			$this->_tpl->assign('icms_persistable_objects', $this->_aObjects);
		} else {
			$colspan = count($this->_columns) + 1;
			$this->_tpl->assign('icms_colspan', $colspan);
		}
		$this->_hasActions = $doWeHaveActions;
	}

	/**
	 *
	 * @param unknown_type $debug
	 */
	public function fetchObjects($debug = false) {
		return $this->_objectHandler->getObjects($this->_criteria, true,true, false, $debug);
	}

	/**
	 *
	 */
	public function getDefaultFilter() {
		if ($this->_filtersel) {
			return icms_getCookieVar($_SERVER['SCRIPT_NAME'] . '_' . $this->_id . '_filtersel', $this->_filtersel);
		} else {
			return icms_getCookieVar($_SERVER['SCRIPT_NAME'] . '_' . $this->_id . '_filtersel', 'default');
		}
	}

	/**
	 *
	 */
	public function getFiltersArray() {
		$ret = array();
		$field = array();
		$field['caption'] = _CO_ICMS_NONE;
		$field['selected'] = '';
		$ret['default'] = $field;
		unset($field);

		if ($this->_filterseloptions) {
			foreach ($this->_filterseloptions as $key=>$value) {
				$field = array();
				if (is_array($value)) {
					$field['caption'] = $key;
					$field['selected'] = $this->_filtersel == $key ? "selected='selected'" : '';
				} else {
					$field['caption'] = $this->_tempObject->vars[$key]['form_caption'];
					$field['selected'] = $this->_filtersel == $key ? "selected='selected'" : '';
				}
				$ret[$key] = $field;
				unset($field);
			}
		} else {
			$ret = false;
		}
		return $ret;
	}

	/**
	 *
	 * @param unknown_type $default_filter2
	 */
	public function setDefaultFilter2($default_filter2) {
		$this->_filtersel2 = $default_filter2;
	}

	/**
	 *
	 */
	public function getDefaultFilter2() {
		if ($this->_filtersel2) {
			return icms_getCookieVar($_SERVER['SCRIPT_NAME'] . '_filtersel2', $this->_filtersel2);
		} else {
			return icms_getCookieVar($_SERVER['SCRIPT_NAME'] . '_filtersel2', 'default');
		}
	}
	 /**
	  *
	  */
	public function getFilters2Array() {
		$ret = array();

		foreach ($this->_filtersel2options as $key=>$value) {
			$field = array();
			$field['caption'] = $value;
			$field['selected'] = $this->_filtersel2 == $key ? "selected='selected'" : '';
			$ret[$key] = $field;
			unset($field);
		}
		return $ret;
	}

	/**
	 *
	 * @param $limitsArray
	 * @param $params_of_the_options_sel
	 */
	public function renderOptionSelection($limitsArray, $params_of_the_options_sel) {
		global $impresscms;
		// Rendering the form to select options on the table
		$current_url = $impresscms->urls['full'];

		/**
		 * What was $params_of_the_options_sel doing again ?
		 */
		//$this->_tpl->assign('icms_optionssel_action', $_SERVER['SCRIPT_NAME'] . "?" . implode('&', $params_of_the_options_sel));
		$this->_tpl->assign('icms_optionssel_action', $current_url);
		$this->_tpl->assign('icms_optionssel_limitsArray', $limitsArray);
	}

	/**
	 *
	 */
	public function getLimitsArray() {
		$ret = array();
		$ret['all']['caption'] = _CO_ICMS_LIMIT_ALL;
		$ret['all']['selected'] = ('all' == $this->_limitsel) ? "selected='selected'" : "";

		$ret['5']['caption'] = icms_conv_nr2local('5');
		$ret['5']['selected'] = ('5' == $this->_limitsel) ? "selected='selected'" : "";

		$ret['10']['caption'] = icms_conv_nr2local('10');
		$ret['10']['selected'] = ('10' == $this->_limitsel) ? "selected='selected'" : "";

		$ret['15']['caption'] = icms_conv_nr2local('15');
		$ret['15']['selected'] = ('15' == $this->_limitsel) ? "selected='selected'" : "";

		$ret['20']['caption'] = icms_conv_nr2local('20');
		$ret['20']['selected'] = ('20' == $this->_limitsel) ? "selected='selected'" : "";

		$ret['25']['caption'] = icms_conv_nr2local('25');
		$ret['25']['selected'] = ('25' == $this->_limitsel) ? "selected='selected'" : "";

		$ret['30']['caption'] = icms_conv_nr2local('30');
		$ret['30']['selected'] = ('30' == $this->_limitsel) ? "selected='selected'" : "";

		$ret['35']['caption'] = icms_conv_nr2local('35');
		$ret['35']['selected'] = ('35' == $this->_limitsel) ? "selected='selected'" : "";

		$ret['40']['caption'] = icms_conv_nr2local('40');
		$ret['40']['selected'] = ('40' == $this->_limitsel) ? "selected='selected'" : "";
		return $ret;
	}

	/**
	 *
	 */
	public function getObjects() {
		return $this->_objects;
	}

	/**
	 *
	 */
	public function hideActionColumnTitle() {
		$this->_showActionsColumnTitle = false;
	}

	/**
	 *
	 */
	public function hideFilterAndLimit() {
		$this->_showFilterAndLimit = false;
	}

	/**
	 *
	 */
	public function getOrdersArray() {
		$ret = array();
		$ret['ASC']['caption'] = _CO_ICMS_SORT_ASC;
		$ret['ASC']['selected'] = ('ASC' == $this->_ordersel) ? "selected='selected'" : "";

		$ret['DESC']['caption'] = _CO_ICMS_SORT_DESC;
		$ret['DESC']['selected'] = ('DESC' == $this->_ordersel) ? "selected='selected'" : "";

		return $ret;
	}

	/**
	 *
	 */
	public function renderD() {
		return $this->render(false, true);
	}

	/**
	 *
	 */
	public function renderForPrint() {

	}

	/**
	 *
	 * @param $fetchOnly
	 * @param $debug
	 */
	public function render($fetchOnly = false, $debug = false) {
		global $impresscms;

		$this->_tpl = new icms_view_Tpl();

		/**
		 * We need access to the vars of the icms_ipf_Object for a few things in the table creation.
		 * Since we may not have an icms_ipf_Object to look into now, let's create one for this purpose
		 * and we will free it after
		 */
		$this->_tempObject =& $this->_objectHandler->create();

		$this->_criteria->setStart(isset($_GET['start' . $this->_objectHandler->keyName]) ? (int) ($_GET['start' . $this->_objectHandler->keyName]) : 0);

		$this->setSortOrder();

		if (!$this->_isTree) {
			$this->_limitsel = isset($_GET['limitsel']) ? $_GET['limitsel'] : icms_getCookieVar($_SERVER['SCRIPT_NAME'] . '_limitsel', '15');
		} else {
			$this->_limitsel = 'all';
		}

		$this->_limitsel = isset($_POST['limitsel']) ? $_POST['limitsel'] : $this->_limitsel;
		icms_setCookieVar($_SERVER['SCRIPT_NAME'] . '_limitsel', $this->_limitsel);
		$limitsArray = $this->getLimitsArray();
		$this->_criteria->setLimit($this->_limitsel);

		$this->_filtersel = isset($_GET['filtersel']) ? $_GET['filtersel'] : $this->getDefaultFilter();
		$this->_filtersel = isset($_POST['filtersel']) ? $_POST['filtersel'] : $this->_filtersel;
		icms_setCookieVar($_SERVER['SCRIPT_NAME'] . '_' . $this->_id . '_filtersel', $this->_filtersel);
		$filtersArray = $this->getFiltersArray();

		if ($filtersArray) {
			$this->_tpl->assign('icms_optionssel_filtersArray', $filtersArray);
		}

		// Check if the selected filter is defined and if so, create the selfilter2
		if (isset($this->_filterseloptions[$this->_filtersel])) {
			// check if method associate with this filter exists in the handler
			if (is_array($this->_filterseloptions[$this->_filtersel])) {
				$filter = $this->_filterseloptions[$this->_filtersel];
				$this->_criteria->add($filter['criteria']);
			} else {
				if (method_exists($this->_objectHandler, $this->_filterseloptions[$this->_filtersel])) {

					// then we will create the selfilter2 options by calling this method
					$method = $this->_filterseloptions[$this->_filtersel];
					$this->_filtersel2options = $this->_objectHandler->$method();

					$this->_filtersel2 = isset($_GET['filtersel2']) ? $_GET['filtersel2'] : $this->getDefaultFilter2();
					$this->_filtersel2 = isset($_POST['filtersel2']) ? $_POST['filtersel2'] : $this->_filtersel2;

					$filters2Array = $this->getFilters2Array();
					$this->_tpl->assign('icms_optionssel_filters2Array', $filters2Array);

					icms_setCookieVar($_SERVER['SCRIPT_NAME'] . '_filtersel2', $this->_filtersel2);
					if ($this->_filtersel2 != 'default') {
						$this->_criteria->add(new icms_db_criteria_Item($this->_filtersel, $this->_filtersel2));
					}
				}
			}
		}
		// Check if we have a quicksearch

		if (isset($_POST['quicksearch_' . $this->_id]) && $_POST['quicksearch_' . $this->_id] != '') {
			$quicksearch_criteria = new icms_db_criteria_Compo();
			if (is_array($this->_quickSearch['fields'])) {
				foreach ($this->_quickSearch['fields'] as $v) {
					$quicksearch_criteria->add(new icms_db_criteria_Item($v, '%' . $_POST['quicksearch_' . $this->_id] . '%', 'LIKE'), 'OR');
				}
			} else {
				$quicksearch_criteria->add(new icms_db_criteria_Item($this->_quickSearch['fields'], '%' . $_POST['quicksearch_' . $this->_id] . '%', 'LIKE'));
			}
			$this->_criteria->add($quicksearch_criteria);
		}

		$this->_objects = $this->fetchObjects($debug);

		/**
		 * $params_of_the_options_sel is an array with all the parameters of the page
		 * but without the pagenave parameters. This array will be used in the
		 * OptionsSelection
		 */
		$params_of_the_options_sel = array();
		if ($this->_criteria->getLimit() > 0) {

			/**
			 * Geeting rid of the old params
			 * $new_get_array is an array containing the new GET parameters
			 */
			$new_get_array = array();

			$not_needed_params = array('sortsel', 'limitsel', 'ordersel', 'start' . $this->_objectHandler->keyName);
			foreach ($_GET as $k => $v) {
				if (!in_array($k, $not_needed_params)) {
					$new_get_array[] = "$k=$v";
					$params_of_the_options_sel[] = "$k=$v";
				}
			}

			/**
			 * Adding the new params of the pagenav
			 */
			$new_get_array[] = "sortsel=" . $this->_sortsel;
			$new_get_array[] = "ordersel=" . $this->_ordersel;
			$new_get_array[] = "limitsel=" . $this->_limitsel;
			$otherParams = implode('&', $new_get_array);

			$pagenav = new icms_view_PageNav($this->_objectHandler->getCount($this->_criteria), $this->_criteria->getLimit(), $this->_criteria->getStart(), 'start' . $this->_objectHandler->keyName, $otherParams);
			$this->_tpl->assign('icms_pagenav', $pagenav->renderNav());
		}
		$this->renderOptionSelection($limitsArray, $params_of_the_options_sel);

		// retreive the current url and the query string
		$current_url = $impresscms->urls['full_phpself'];
		$query_string = $impresscms->urls['querystring'];
		if ($query_string) {
			$query_string = str_replace('?', '',$query_string);
		}
		$query_stringArray = explode('&', $query_string);
		$new_query_stringArray = array();
		foreach ($query_stringArray as $query_string) {
			if (strpos($query_string, 'sortsel') == FALSE && strpos($query_string, 'ordersel') == FALSE) {
				$new_query_stringArray[] = $query_string;
			}
		}
		$new_query_string = implode('&', $new_query_stringArray);

		$orderArray = array();
		$orderArray['ASC']['image'] = 'desc.png';
		$orderArray['ASC']['neworder'] = 'DESC';
		$orderArray['DESC']['image'] = 'asc.png';
		$orderArray['DESC']['neworder'] = 'ASC';

		$aColumns = array();

		foreach ($this->_columns as $column) {
			$aColumn = array();
			$aColumn['width'] = $column->getWidth();
			$aColumn['align'] = $column->getAlign();
			$aColumn['key'] = $column->getKeyName();

			if ($column->_keyname == 'checked') {
				$aColumn['caption'] = '<input type ="checkbox" id="checkall_icmspersistableobjects" name="checkall_icmspersistableobjects"' .
						' value="checkall_icmspersistableobjects" onclick="icms_checkall(window.document.form_' . $this->_id . ', \'selected_icmspersistableobjects\');" />';
			} elseif ($column->getCustomCaption()) {
				$aColumn['caption'] = $column->getCustomCaption();
			} else {
				$aColumn['caption'] = isset($this->_tempObject->vars[$column->getKeyName()]['form_caption']) ? $this->_tempObject->vars[$column->getKeyName()]['form_caption'] : $column->getKeyName();
			}
			// Are we doing a GET sort on this column ?
			$getSort = (isset($_GET[$this->_objectHandler->_itemname . '_' . 'sortsel']) && $_GET[$this->_objectHandler->_itemname . '_' . 'sortsel'] == $column->getKeyName()) || ($this->_sortsel == $column->getKeyName());
			$order = isset($_GET[$this->_objectHandler->_itemname . '_' . 'ordersel']) ? $_GET[$this->_objectHandler->_itemname . '_' . 'ordersel'] : 'DESC';

			if (isset($_REQUEST['quicksearch_' . $this->_id]) && $_REQUEST['quicksearch_' . $this->_id] != '') {
				$filter = isset($_POST['quicksearch_' . $this->_id]) ? INPUT_POST : INPUT_GET;
				$qs_param = "&amp;quicksearch_".$this->_id."=".filter_input($filter, 'quicksearch_' . $this->_id, FILTER_SANITIZE_SPECIAL_CHARS);
			} else {
				$qs_param = '';
			}
			if (!$this->_enableColumnsSorting || $column->_keyname == 'checked' || !$column->_sortable) {
				$aColumn['caption'] =  $aColumn['caption'];
			} elseif ($getSort) {
				$aColumn['caption'] =  '<a href="' . $current_url . '?' . $this->_objectHandler->_itemname . '_' . 'sortsel=' . $column->getKeyName() . '&amp;' . $this->_objectHandler->_itemname . '_' . 'ordersel=' . $orderArray[$order]['neworder'] . $qs_param . '&amp;' . $new_query_string . '">' . $aColumn['caption'] . ' <img src="' . ICMS_IMAGES_SET_URL .'/actions/' . $orderArray[$order]['image'] . '" alt="ASC" /></a>';
			} else {
				$aColumn['caption'] =  '<a href="' . $current_url . '?' . $this->_objectHandler->_itemname . '_' . 'sortsel=' . $column->getKeyName() . '&amp;' . $this->_objectHandler->_itemname . '_' . 'ordersel=ASC' . $qs_param . '&amp;' . $new_query_string . '">' . $aColumn['caption'] . '</a>';
			}
			$aColumns[] = $aColumn;
		}
		$this->_tpl->assign('icms_columns', $aColumns);

		if ($this->_quickSearch) {
			$this->_tpl->assign('icms_quicksearch', $this->_quickSearch['caption']);
		}

		$this->createTableRows();

		$this->_tpl->assign('icms_showFilterAndLimit', $this->_showFilterAndLimit);
		$this->_tpl->assign('icms_isTree', $this->_isTree);
		$this->_tpl->assign('icms_show_action_column_title', $this->_showActionsColumnTitle);
		$this->_tpl->assign('icms_table_header', $this->_tableHeader);
		$this->_tpl->assign('icms_table_footer', $this->_tableFooter);
		$this->_tpl->assign('icms_printer_friendly_page', $this->_printerFriendlyPage);
		$this->_tpl->assign('icms_user_side', $this->_userSide);
		$this->_tpl->assign('icms_has_actions', $this->_hasActions);
		$this->_tpl->assign('icms_head_css_class', $this->_head_css_class);
		$this->_tpl->assign('icms_actionButtons', $this->_actionButtons);
		$this->_tpl->assign('icms_introButtons', $this->_introButtons);
		$this->_tpl->assign('icms_id', $this->_id);
		if (!empty($this->_withSelectedActions)) {
			$this->_tpl->assign('icms_withSelectedActions', $this->_withSelectedActions);
		}

		$icms_table_template = $this->_customTemplate ? $this->_customTemplate : 'system_persistabletable_display.html';
		if ($fetchOnly) {
			return $this->_tpl->fetch('db:' . $icms_table_template);
		} else {
			$this->_tpl->display('db:' . $icms_table_template);
		}
	}

	/**
	 *
	 */
	public function disableColumnsSorting() {
		$this->_enableColumnsSorting = false;
	}

	/**
	 *
	 * @param $debug
	 */
	public function fetch($debug = false) {
		return $this->render(true, $debug);
	}
}

