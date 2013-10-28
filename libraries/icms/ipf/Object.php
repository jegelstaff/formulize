<?php
/**
 * Contains the basis classes for managing any objects derived from icms_ipf_Object
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @category	ICMS
 * @package		Ipf
 * @subpackage	Object
 * @since		1.1
 * @author		marcan <marcan@impresscms.org>
 * @version		SVN: $Id:Object.php 19775 2010-07-11 18:54:25Z malanciault $
 */

defined("ICMS_ROOT_PATH") or die("ImpressCMS root path not defined");

icms_loadLanguageFile('system', 'common');

if (!defined('XOBJ_DTYPE_SIMPLE_ARRAY')) define('XOBJ_DTYPE_SIMPLE_ARRAY', 101);
if (!defined('XOBJ_DTYPE_CURRENCY')) define('XOBJ_DTYPE_CURRENCY', 200);
if (!defined('XOBJ_DTYPE_FLOAT')) define('XOBJ_DTYPE_FLOAT', 201);
if (!defined('XOBJ_DTYPE_TIME_ONLY')) define('XOBJ_DTYPE_TIME_ONLY', 202);
if (!defined('XOBJ_DTYPE_URLLINK')) define('XOBJ_DTYPE_URLLINK', 203);
if (!defined('XOBJ_DTYPE_FILE')) define('XOBJ_DTYPE_FILE', 204);
if (!defined('XOBJ_DTYPE_IMAGE')) define('XOBJ_DTYPE_IMAGE', 205);
if (!defined('XOBJ_DTYPE_FORM_SECTION')) define('XOBJ_DTYPE_FORM_SECTION', 210);
if (!defined('XOBJ_DTYPE_FORM_SECTION_CLOSE')) define('XOBJ_DTYPE_FORM_SECTION_CLOSE', 211);

/**
 * icms_ipf_Object base class
 *
 * Base class representing a single icms_ipf_Object
 *
 * @category	ICMS
 * @package		Ipf
 * @subpackage	Object
 * @author marcan <marcan@smartfactory.ca>
 * @todo		Properly identify and declare the visibility of vars and functions
 */
class icms_ipf_Object extends icms_core_Object {

	public $_image_path;
	public $_image_url;

	public $seoEnabled = false;
	public $titleField;
	public $summaryField = false;

	/**
	 * Reference to the handler managing this object
	 *
	 * @var object reference to {@link icms_ipf_Handler}
	 */
	public $handler;

	/**
	 * References to control objects, managing the form fields of this object
	 */
	public $controls = array();

	public function __construct(&$handler) {
		$this->handler = $handler;
	}

	/**
	 * For backward compat
	 *
	 * @todo to be removed in 1.4
	 */
	function IcmsPersistableObject(&$handler) {
		$this->handler = $handler;
	}

	/**
	 * Checks if the user has a specific access on this object
	 *
	 * @param string $gperm_name name of the permission to test
	 * @return boolean : TRUE if user has access, false if not
	 **/
	public function accessGranted($perm_name) {
		$icmspermissions_handler = new icms_ipf_permission_Handler($this->handler);
		return $icmspermissions_handler->accessGranted($perm_name, $this->id());
	}
	/**
	 * open a new form section to seperate form elements
	 *
	 * @param	str		$section_name
	 * @param	bool	$value
	 */
	public function openFormSection($section_name, $value = FALSE) {
		$this->initVar($section_name, XOBJ_DTYPE_FORM_SECTION, $value, FALSE, NULL, '', FALSE, '', '', FALSE, FALSE, TRUE);
	}

	/**
	 * close a form section
	 *
	 * @param	str		$section_name
	 */
	public function closeFormSection($section_name) {
		$this->initVar('close_section_' . $section_name, XOBJ_DTYPE_FORM_SECTION_CLOSE, '', FALSE, NULL, '', FALSE, '', '', FALSE, FALSE, TRUE);
	}

	/**
	 *
	 * @param string $key key of this field. This needs to be the name of the field in the related database table
	 * @param int $data_type  set to one of XOBJ_DTYPE_XXX constants (set to XOBJ_DTYPE_OTHER if no data type ckecking nor text sanitizing is required)
	 * @param mixed $value default value of this variable
	 * @param bool $required set to TRUE if this variable needs to have a value set before storing the object in the table
	 * @param int $maxlength maximum length of this variable, for XOBJ_DTYPE_TXTBOX type only
	 * @param string $options does this data have any select options?
	 * @param bool $multilingual is this field needs to support multilingual features (NOT YET IMPLEMENTED...)
	 * @param string $form_caption caption of this variable in a {@link icms_ipf_form_Base} and title of a column in a  {@link icms_ipf_ObjectTable}
	 * @param string $form_dsc description of this variable in a {@link icms_ipf_form_Base}
	 * @param bool $sortby set to TRUE to make this field used to sort objects in icms_ipf_ObjectTable
	 * @param bool $persistent set to FALSE if this field is not to be saved in the database
	 * @param bool $displayOnForm to be displayed on the form or not
	 */
	public function initVar($key, $data_type, $value = null, $required = false, $maxlength = null, $options = '', $multilingual = false, $form_caption = '', $form_dsc = '', $sortby = false, $persistent = true, $displayOnForm = true) {
		//url_ is reserved for files.
		if (substr($key, 0,4) == 'url_') {
			trigger_error("Cannot use variable starting with 'url_'.");
		}
		parent::initVar($key, $data_type, $value, $required, $maxlength, $options);
		if ($this->handler && (!$form_caption || $form_caption == '')) {
			$dyn_form_caption = strtoupper('_CO_' . $this->handler->_moduleName . '_' . $this->handler->_itemname . '_' . $key);
			if (defined($dyn_form_caption)) {
				$form_caption = constant($dyn_form_caption);
			}
		}
		if ($this->handler && (!$form_dsc || $form_dsc == '')) {
			$dyn_form_dsc = strtoupper('_CO_' . $this->handler->_moduleName . '_' . $this->handler->_itemname . '_' . $key . '_DSC');
			if (defined($dyn_form_dsc)) {
				$form_dsc = constant($dyn_form_dsc);
			}
		}

		$this->vars[$key] = array_merge($this->vars[$key], array('multilingual' => $multilingual,
        'form_caption' => $form_caption,
        'form_dsc' => $form_dsc,
        'sortby' => $sortby,
        'persistent' => $persistent,
        'displayOnForm' => $displayOnForm,
        'displayOnSingleView' => true,
        'readonly' => false));
	}

	/**
	 *
	 *
	 * @param			$key
	 * @param			$data_type
	 * @param	str		$itemName
	 * @param	str 	$form_caption
	 * @param			$sortby
	 * @param			$value
	 * @param	bool	$displayOnForm
	 * @param	bool	$required
	 */
	public function initNonPersistableVar($key, $data_type, $itemName = false, $form_caption = '', $sortby = false, $value = '', $displayOnForm = false, $required = false) {
		$this->initVar($key, $data_type, $value, $required, null, '', false, $form_caption, '', $sortby, false, $displayOnForm);
		$this->vars[$key]['itemName'] = $itemName;
		$this->vars[$key]['displayOnSingleView'] = false;
	}

	/**
	 * Quickly initiate a var
	 *
	 * Since many vars do have the same config, let's use this method with some of these configuration as a convention ;-)
	 *
	 * - $maxlength = 0 unless $data_type is a TEXTBOX, then $maxlength will be 255
	 * - all other vars are NULL or '' depending of the parameter
	 *
	 * @param string $key key of this field. This needs to be the name of the field in the related database table
	 * @param int $data_type  set to one of XOBJ_DTYPE_XXX constants (set to XOBJ_DTYPE_OTHER if no data type ckecking nor text sanitizing is required)
	 * @param bool $required set to TRUE if this variable needs to have a value set before storing the object in the table
	 * @param string $form_caption caption of this variable in a {@link icms_ipf_form_Base} and title of a column in a  {@link icms_ipf_ObjectTable}
	 * @param string $form_dsc description of this variable in a {@link icms_ipf_form_Base}
	 * @param mixed $value default value of this variable
	 */
	public function quickInitVar($key, $data_type, $required = false, $form_caption = '', $form_dsc = '', $value = null) {
		$maxlength = $data_type == 'XOBJ_DTYPE_TXTBOX' ? 255 : null;
		$this->initVar($key, $data_type, $value, $required, $maxlength, '', false, $form_caption, $form_dsc, false, true, true);
	}

	/**
	 *
	 *
	 * @param	str		$varname
	 * @param	bool	$displayOnForm
	 * @param	str		$default
	 */
	public function initCommonVar($varname, $displayOnForm = true, $default = 'notdefined') {
		switch ($varname) {
			case "dohtml":
				$value = $default != 'notdefined' ? $default : true;
				$this->initVar($varname, XOBJ_DTYPE_INT, $value, false, null, "", false, _CO_ICMS_DOHTML_FORM_CAPTION, '', false, true, $displayOnForm);
				$this->setControl($varname, "yesno");
				break;

			case "dobr":
				$value = ($default === 'notdefined') ? true : $default;
				$this->initVar($varname, XOBJ_DTYPE_INT,$value, false, null, "", false, _CO_ICMS_DOBR_FORM_CAPTION, '', false, true, $displayOnForm);
				$this->setControl($varname, "yesno");
				break;

			case "doimage":
				$value = $default != 'notdefined' ? $default : true;
				$this->initVar($varname, XOBJ_DTYPE_INT,$value, false, null, "", false, _CO_ICMS_DOIMAGE_FORM_CAPTION, '', false, true, $displayOnForm);
				$this->setControl($varname, "yesno");
				break;

			case "dosmiley":
				$value = $default != 'notdefined' ? $default : true;
				$this->initVar($varname, XOBJ_DTYPE_INT,$value, false, null, "", false, _CO_ICMS_DOSMILEY_FORM_CAPTION, '', false, true, $displayOnForm);
				$this->setControl($varname, "yesno");
				break;

			case "doxcode":
				$value = $default != 'notdefined' ? $default : true;
				$this->initVar($varname, XOBJ_DTYPE_INT,$value, false, null, "", false, _CO_ICMS_DOXCODE_FORM_CAPTION, '', false, true, $displayOnForm);
				$this->setControl($varname, "yesno");
				break;

			case "meta_keywords":
				$value = $default != 'notdefined' ? $default : '';
				$this->initVar($varname, XOBJ_DTYPE_TXTAREA, $value, false, null, '', false, _CO_ICMS_META_KEYWORDS, _CO_ICMS_META_KEYWORDS_DSC, false, true, $displayOnForm);
				$this->setControl('meta_keywords', array(
										'name' => 'textarea',
										'form_editor'=>'textarea'
										));
				break;

			case "meta_description":
				$value = $default != 'notdefined' ? $default : '';
				$this->initVar($varname, XOBJ_DTYPE_TXTAREA, $value, false, null, '', false, _CO_ICMS_META_DESCRIPTION, _CO_ICMS_META_DESCRIPTION_DSC, false, true, $displayOnForm);
				$this->setControl('meta_description', array(
										'name' => 'textarea',
										'form_editor'=>'textarea'
										));
				break;

			case "short_url":
				$value = $default != 'notdefined' ? $default : '';
				$this->initVar($varname, XOBJ_DTYPE_TXTBOX,$value, false, null, "", false, _CO_ICMS_SHORT_URL, _CO_ICMS_SHORT_URL_DSC, false, true, $displayOnForm);
				break;

			case "hierarchy_path":
				$value = $default != 'notdefined' ? $default : '';
				$this->initVar($varname, XOBJ_DTYPE_ARRAY, $value, false, null, "", false, _CO_ICMS_HIERARCHY_PATH, _CO_ICMS_HIERARCHY_PATH_DSC, false, true, $displayOnForm);
				break;

			case "counter":
				$value = $default != 'notdefined' ? $default : 0;
				$this->initVar($varname, XOBJ_DTYPE_INT,$value, false, null, '', false, _CO_ICMS_COUNTER_FORM_CAPTION, '', false, true, $displayOnForm);
				break;

			case "weight":
				$value = $default != 'notdefined' ? $default : 0;
				$this->initVar($varname, XOBJ_DTYPE_INT,$value, false, null, '', false, _CO_ICMS_WEIGHT_FORM_CAPTION, '', true, true, $displayOnForm);
				break;
			case "custom_css":
				$value = $default != 'notdefined' ? $default : '';
				$this->initVar($varname, XOBJ_DTYPE_TXTAREA, $value, false, null, '', false, _CO_ICMS_CUSTOM_CSS, _CO_ICMS_CUSTOM_CSS_DSC, false, true, $displayOnForm);
				$this->setControl('custom_css', array(
									'name' => 'textarea',
									'form_editor'=>'textarea',
					)
				);
				break;

			default:
				break;
		}
		$this->hideFieldFromSingleView($varname);
	}

	/**
	 * Set control information for an instance variable
	 *
	 * The $options parameter can be a string or an array. Using a string
	 * is the quickest way :
	 *
	 * $this->setControl('date', 'datetime');
	 *
	 * This will create a date and time selectbox for the 'date' var on the
	 * form to edit or create this item.
	 *
	 * Here are the currently supported controls :
	 *
	 * 		- color
	 * 		- country
	 * 		- datetime
	 * 		- date
	 * 		- email
	 * 		- group
	 * 		- group_multi
	 * 		- image
	 * 		- imageupload
	 * 		- label
	 * 		- language
	 * 		- parentcategory
	 * 		- password
	 * 		- selectmulti
	 * 		- select
	 * 		- text
	 * 		- textarea
	 * 		- theme
	 * 		- theme_multi
	 * 		- timezone
	 * 		- user
	 * 		- user_multi
	 * 		- yesno
	 *
	 * Now, using an array as $options, you can customize what information to
	 * use in the control. For example, if one needs to display a select box for
	 * the user to choose the status of an item. We only need to tell icms_ipf_Object
	 * what method to execute within what handler to retreive the options of the
	 * selectbox.
	 *
	 * $this->setControl('status', array('name' => false,
	 * 	                                 'itemHandler' => 'item',
	 *                                   'method' => 'getStatus',
	 *                                   'module' => 'smartshop'));
	 *
	 * In this example, the array elements are the following :
	 * 		- name : false, as we don't need to set a special control here.
	 * 				 we will use the default control related to the object type (defined in initVar)
	 * 		- itemHandler : name of the object for which we will use the handler
	 * 		- method : name of the method of this handler that we will execute
	 * 		- module : name of the module from wich the handler is
	 *
	 * So in this example, icms_ipf_Object will create a selectbox for the variable 'status' and it will
	 * populate this selectbox with the result from SmartshopItemHandler::getStatus()
	 *
	 * Another example of the use of $options as an array is for TextArea :
	 *
	 * $this->setControl('body', array('name' => 'textarea',
	 *                                   'form_editor' => 'default'));
	 *
	 * In this example, icms_ipf_Object will create a TextArea for the variable 'body'. And it will use
	 * the 'default' editor, providing it is defined in the module
	 * preferences : $icmsModuleConfig['default_editor']
	 *
	 * Of course, you can force the use of a specific editor :
	 *
	 * $this->setControl('body', array('name' => 'textarea',
	 *                                   'form_editor' => 'koivi'));
	 *
	 * Here is a list of supported editor :
	 * 		- tiny : TinyEditor
	 * 		- dhtmltextarea : ImpressCMS DHTML Area
	 * 		- fckeditor	: FCKEditor
	 * 		- inbetween : InBetween
	 * 		- koivi : Koivi
	 * 		- spaw : Spaw WYSIWYG Editor
	 * 		- htmlarea : HTMLArea
	 * 		- textarea : basic textarea with no options
	 *
	 * @param string $var name of the variable for which we want to set a control
	 * @param array $options
	 */
	public function setControl($var, $options = array()) {
		if (isset($this->controls[$var])) {
			unset($this->controls[$var]);
		}
		if (is_string($options)) {
			$options = array('name' => $options);
		}
		$this->controls[$var] = $options;
	}

	/**
	 * Get control information for an instance variable
	 *
	 * @param string $var
	 */
	public function getControl($var) {
		return isset($this->controls[$var]) ? $this->controls[$var] : false;
	}

	/**
	 * Create the form for this object
	 *
	 * @return a {@link SmartobjectForm} object for this object
	 *
	 * @see icms_ipf_ObjectForm::icms_ipf_ObjectForm()
	 */
	public function getForm($form_caption, $form_name, $form_action = false, $submit_button_caption = _CO_ICMS_SUBMIT, $cancel_js_action = false, $captcha = false) {
		return new icms_ipf_form_Base($this, $form_name, $form_caption, $form_action, null, $submit_button_caption, $cancel_js_action, $captcha);
	}

	/**
	 * Create the secure form for this object
	 *
	 * @return a {@link icms_ipf_form_Secure} object for this object
	 *
	 * @see icms_ipf_ObjectForm::icms_ipf_ObjectForm()
	 */
	public function getSecureForm($form_caption, $form_name, $form_action = false, $submit_button_caption = _CO_ICMS_SUBMIT, $cancel_js_action = false, $captcha = false) {
		$form = new icms_ipf_form_Secure($this, $form_name, $form_caption, $form_action, null, $submit_button_caption, $cancel_js_action, $captcha);

		return $form;
	}

	/**
	 *
	 */
	public function toArray() {
		$ret = array();
		$vars = $this->getVars();
		foreach ($vars as $key=>$var) {
			$value = $this->getVar($key);
			$ret[$key] = $value;
		}
		if ($this->handler->identifierName != "") {
			$controller = new icms_ipf_Controller($this->handler);
			/**
			 * Addition of some automatic value
			 */
			$ret['itemLink'] = $controller->getItemLink($this);
			$ret['itemUrl'] = $controller->getItemLink($this, true);
			$ret['editItemLink'] = $controller->getEditItemLink($this, false, true);
			$ret['deleteItemLink'] = $controller->getDeleteItemLink($this, false, true);
			$ret['printAndMailLink'] = $controller->getPrintAndMailLink($this);
		}
		/**
		 * @todo implement this in ImpressCMS core
		 */
		/*
		 // Hightlighting searched words
		 include_once SMARTOBJECT_ROOT_PATH . 'class/smarthighlighter.php' ;
		 $highlight = icms_getConfig('module_search_highlighter', false, true);

		 if ($highlight && isset($_GET['keywords']))
		 {
			$myts =& icms_core_Textsanitizer::getInstance();
			$keywords=$myts->htmlSpecialChars(trim(urldecode($_GET['keywords'])));
			$h= new SmartHighlighter ($keywords, true , 'smart_highlighter');
			foreach ($this->handler->highlightFields as $field) {
			$ret[$field] = $h->highlight($ret[$field]);
			}
			}
			*/
		return $ret;
	}

	/**
	 * add an error
	 *
	 * @param string $value error to add
	 * @access public
	 */
	public function setErrors($err_str, $prefix = false) {
		if (is_array($err_str)) {
			foreach ($err_str as $str) {
				$this->setErrors($str, $prefix);
			}
		} else {
			if ($prefix) {
				$err_str = "[" . $prefix . "] " . $err_str;
			}
			parent::setErrors($err_str);
		}
	}

	/**
	 *
	 *
	 * @param $field
	 * @param $required
	 */
	public function setFieldAsRequired($field, $required = true) {
		if (is_array($field)) {
			foreach ($field as $v) {
				$this->doSetFieldAsRequired($v, $required);
			}
		} else {
			$this->doSetFieldAsRequired($field, $required);
		}
	}

	/**
	 *
	 *
	 * @param $field
	 */
	public function setFieldForSorting($field) {
		if (is_array($field)) {
			foreach ($field as $v) {
				$this->doSetFieldForSorting($v);
			}
		} else {
			$this->doSetFieldForSorting($field);
		}
	}

	/**
	 *
	 */
	public function hasError() {
		return count($this->_errors) > 0;
	}

	/**
	 *
	 *
	 * @param $url
	 * @param $path
	 */
	public function setImageDir($url, $path) {
		$this->_image_url = $url;
		$this->_image_path = $path;
	}

	/**
	 * Retreive the group that have been granted access to a specific permission for this object
	 *
	 * @return string $group_perm name of the permission
	 */
	public function getGroupPerm($group_perm) {
		if (!$this->handler->getPermissions()) {
			$this->setError("Trying to access a permission that does not exists for this object's handler");
			return false;
		}

		$icmspermissions_handler = new icms_ipf_permission_Handler($this->handler);
		$ret = $icmspermissions_handler->getGrantedGroups($group_perm, $this->id());

		if (count($ret) == 0) {
			return false;
		} else {
			return $ret;
		}
	}

	/**
	 *
	 *
	 * @param $path
	 */
	public function getImageDir($path = false) {
		if ($path) {
			return $this->_image_path;
		} else {
			return $this->_image_url;
		}
	}

	/**
	 *
	 *
	 * @param	str		$path
	 */
	public function getUploadDir($path = false) {
		if ($path) {
			return $this->_image_path;
		} else {
			return $this->_image_url;
		}
	}

	/**
	 *
	 *
	 * @param	str		$key
	 * @param	str		$info
	 */
	public function getVarInfo($key = '', $info = '') {
		if (isset($this->vars[$key][$info])) {
			return $this->vars[$key][$info];
		} elseif ($info == '' && isset($this->vars[$key])) {
			return $this->vars[$key];
		} else {
			return $this->vars;
		}
	}

	/**
	 * Get the id of the object
	 *
	 * @return int id of this object
	 */
	public function id() {
		return $this->getVar($this->handler->keyName, 'e');
	}

	/**
	 * Return the value of the title field of this object
	 *
	 * @return string
	 */
	public function title($format = 's') {
		return $this->getVar($this->handler->identifierName, $format);
	}

	/**
	 * Return the value of the title field of this object
	 *
	 * @return string
	 */
	public function summary() {
		if ($this->handler->summaryName) {
			return $this->getVar($this->handler->summaryName);
		} else {
			return false;
		}
	}

	/**
	 * Retreive the object admin side link, displayijng a SingleView page
	 *
	 * @param bool $onlyUrl wether or not to return a simple URL or a full <a> link
	 * @return string user side link to the object
	 */
	public function getAdminViewItemLink($onlyUrl = false) {
		$controller = new icms_ipf_Controller($this->handler);
		return $controller->getAdminViewItemLink($this, $onlyUrl);
	}

	/**
	 * Retreive the object user side link
	 *
	 * @param bool $onlyUrl wether or not to return a simple URL or a full <a> link
	 * @return string user side link to the object
	 */
	public function getItemLink($onlyUrl = false) {
		$controller = new icms_ipf_Controller($this->handler);
		return $controller->getItemLink($this, $onlyUrl);
	}

	/**
	 *
	 *
	 * @param $onlyUrl
	 * @param $withimage
	 * @param $userSide
	 */
	public function getViewItemLink($onlyUrl = false, $withimage = true, $userSide = false) {
		$controller = new icms_ipf_Controller($this->handler);
		return $controller->getViewItemLink($this, $onlyUrl, $withimage, $userSide);
	}

	/**
	 *
	 *
	 * @param	bool	$onlyUrl
	 * @param	bool	$withimage
	 * @param	bool	$userSide
	 */
	public function getEditItemLink($onlyUrl = false, $withimage = true, $userSide = false) {
		$controller = new icms_ipf_Controller($this->handler);
		return $controller->getEditItemLink($this, $onlyUrl, $withimage, $userSide);
	}

	/**
	 *
	 *
	 * @param	bool	$onlyUrl
	 * @param	bool	$withimage
	 * @param	bool	$userSide
	 */
	public function getDeleteItemLink($onlyUrl = false, $withimage = false, $userSide = false) {
		$controller = new icms_ipf_Controller($this->handler);
		return $controller->getDeleteItemLink($this, $onlyUrl, $withimage, $userSide);
	}

	/**
	 *
	 */
	public function getPrintAndMailLink() {
		$controller = new icms_ipf_Controller($this->handler);
		return $controller->getPrintAndMailLink($this);
	}

	/**
	 *
	 *
	 * @param		$sortsel
	 */
	public function getFieldsForSorting($sortsel) {
		$ret = array();

		foreach ($this->vars as $key=>$field_info) {
			if ($field_info['sortby']) {
				$ret[$key]['caption'] = $field_info['form_caption'];
				$ret[$key]['selected'] = $key == $sortsel ? "selected='selected'" : '';
			}
		}

		if (count($ret) > 0) {
			return $ret;
		} else {
			return false;
		}
	}

	/**
	 *
	 *
	 * @param 		$key
	 * @param 		$newType
	 */
	public function setType($key, $newType) {
		$this->vars[$key]['data_type'] = $newType;
	}

	/**
	 *
	 *
	 * @param unknown_type $key
	 * @param unknown_type $info
	 * @param unknown_type $value
	 */
	public function setVarInfo($key, $info, $value) {
		$this->vars[$key][$info] = $value;
	}

	/**
	 * store object
	 *
	 * @param bool $force
	 * @return bool true if successful, false if not
	 */
	public function store($force = false) {
		return $this->handler->insert($this, $force);
	}

	/**
	 *
	 *
	 * @param unknown_type $key
	 * @param unknown_type $editor
	 */
	public function getValueFor ($key, $editor = true) {
		global $icmsModuleConfig;

		$ret = $this->getVar($key, 'n');
		$myts = icms_core_Textsanitizer::getInstance();

		$control = isset($this->controls[$key]) ? $this->controls[$key] : false;
		$form_editor = isset($control['form_editor']) ? $control['form_editor'] : 'textarea';

		$html = isset($this->vars['dohtml']) ? $this->getVar('dohtml') : true;
		$smiley = true;
		$xcode = true;
		$image = true;
		$br = isset($this->vars['dobr']) ? $this->getVar('dobr') : true;
		$formatML = true;

		if ($form_editor == 'default') {
			global $icmsModuleConfig;
			$form_editor = isset($icmsModuleConfig['default_editor']) ? $icmsModuleConfig['default_editor'] : 'textarea';
		}

		if ($editor) {
			if (defined('XOOPS_EDITOR_IS_HTML') && !(in_array($form_editor, array('formtextarea', 'textarea', 'dhtmltextarea')))) {
				$br = false;
				$formatML = !$editor;
			} else {
				return htmlspecialchars($ret, ENT_QUOTES);
			}
		}

		if (method_exists($myts, 'formatForML')) {
			return $myts->displayTarea($ret, $html, $smiley, $xcode, $image, $br, $formatML);
		} else {
			if ($html) {
				return $myts->displayTarea($ret, $html, $smiley, $xcode, $image, $br);
			} else {
				return icms_core_DataFilter::checkVar($ret, 'text', 'output');
			}
		}
	}

	/**
	 * returns a specific variable for the object in a proper format
	 *
	 * We had to put this method in the icms_ipf_Object because the XOBJ_DTYPE_ARRAY does not work properly
	 * at least on PHP 5.1. So we have created a new type XOBJ_DTYPE_SIMPLE_ARRAY to handle 1 level array
	 * as a string separated by |
	 *
	 * @access public
	 * @param string $key key of the object's variable to be returned
	 * @param string $format format to use for the output
	 * @return mixed formatted value of the variable
	 */
	public function getVar($key, $format = 's') {
		global $myts;

		$ret = $this->vars[$key]['value'];

		switch ($this->vars[$key]['data_type']) {

			case XOBJ_DTYPE_TXTBOX:
				switch (strtolower($format)) {
					case 's':
					case 'show':
						// ML Hack by marcan
						$ret = icms_core_DataFilter::htmlSpecialChars($ret);

						if (method_exists($myts, 'formatForML')) {
							return $ts->formatForML($ret);
						} else {
							return $ret;
						}
						break 1;

						// End of ML Hack by marcan

					case 'clean':
						$ret = icms_html2text($ret);
						$ret = icms_purifyText($ret);

						return $ret;
						break 1;
						// End of ML Hack by marcan

					case 'e':
					case 'edit':
						return icms_core_DataFilter::htmlSpecialChars($ret);
						break 1;

					case 'p':
					case 'preview':
					case 'f':
					case 'formpreview':
						return icms_core_DataFilter::htmlSpecialChars(icms_core_DataFilter::stripSlashesGPC($ret));
						break 1;

					case 'n':
					case 'none':
					default:
						break 1;
				}
				break;

			case XOBJ_DTYPE_LTIME:
				switch (strtolower($format)) {
					case 's':
					case 'show':
					case 'p':
					case 'preview':
					case 'f':
					case 'formpreview':
						$ret = formatTimestamp($ret, _DATESTRING);
						return $ret;
						break 1;

					case 'n':
					case 'none':
					case 'e':
					case 'edit':
						break 1;

					default:
						break 1;
				}
				break;

			case XOBJ_DTYPE_STIME:
				switch (strtolower($format)) {
					case 's':
					case 'show':
					case 'p':
					case 'preview':
					case 'f':
					case 'formpreview':
						$ret = formatTimestamp($ret, _SHORTDATESTRING);
						return $ret;
						break 1;

					case 'n':
					case 'none':
					case 'e':
					case 'edit':
						break 1;

					default:
						break 1;
				}
				break;

			case XOBJ_DTYPE_TIME_ONLY:
				switch (strtolower($format)) {
					case 's':
					case 'show':
					case 'p':
					case 'preview':
					case 'f':
					case 'formpreview':
						$ret = formatTimestamp($ret, 'G:i');
						return $ret;
						break 1;

					case 'n':
					case 'none':
					case 'e':
					case 'edit':
						break 1;

					default:
						break 1;
				}
				break;

			case XOBJ_DTYPE_CURRENCY:
				$decimal_section_original = strstr($ret, '.');
				$decimal_section = $decimal_section_original;
				if ($decimal_section) {
					if (strlen($decimal_section) == 1) {
						$decimal_section = '.00';
					} elseif (strlen($decimal_section) == 2) {
						$decimal_section = $decimal_section . '0';
					}
					$ret = str_replace($decimal_section_original, $decimal_section, $ret);
				} else {
					$ret = $ret . '.00';
				}
				break;

			case XOBJ_DTYPE_TXTAREA:
				switch (strtolower($format)) {
					case 's':
					case 'show':
						$ts = icms_core_Textsanitizer::getInstance();
						$html = !empty($this->vars['dohtml']['value']) ? 1 : 0;
						$xcode = (!isset($this->vars['doxcode']['value']) || $this->vars['doxcode']['value'] == 1) ? 1 : 0;
						$smiley = (!isset($this->vars['dosmiley']['value']) || $this->vars['dosmiley']['value'] == 1) ? 1 : 0;
						$image = (!isset($this->vars['doimage']['value']) || $this->vars['doimage']['value'] == 1) ? 1 : 0;
						$br = (!isset($this->vars['dobr']['value']) || $this->vars['dobr']['value'] == 1) ? 1 : 0;
						if (defined('XOOPS_EDITOR_IS_HTML')) {
							$br = false;
						}
						if ($html) {
							return $ts->displayTarea($ret, $html, $smiley, $xcode, $image, $br);
						} else {
							return icms_core_DataFilter::checkVar($ret, 'text', 'output');
						}
						break 1;

					case 'e':
					case 'edit':
						return htmlspecialchars($ret, ENT_QUOTES);
						break 1;

					case 'p':
					case 'preview':
						$ts = icms_core_Textsanitizer::getInstance();
						$html = !empty($this->vars['dohtml']['value']) ? 1 : 0;
						$xcode = (!isset($this->vars['doxcode']['value']) || $this->vars['doxcode']['value'] == 1) ? 1 : 0;
						$smiley = (!isset($this->vars['dosmiley']['value']) || $this->vars['dosmiley']['value'] == 1) ? 1 : 0;
						$image = (!isset($this->vars['doimage']['value']) || $this->vars['doimage']['value'] == 1) ? 1 : 0;
						$br = (!isset($this->vars['dobr']['value']) || $this->vars['dobr']['value'] == 1) ? 1 : 0;
						if ($html) {
							return $ts->previewTarea($ret, $html, $smiley, $xcode, $image, $br);
						} else {
							return icms_core_DataFilter::checkVar($ret, 'text', 'output');
						}
						break 1;

					case 'f':
					case 'formpreview':
						return htmlspecialchars(icms_core_DataFilter::stripSlashesGPC($ret), ENT_QUOTES);
						break 1;

					case 'n':
					case 'none':
					default:
						break 1;
				}
				break;

			case XOBJ_DTYPE_SIMPLE_ARRAY:
				$ret =& explode('|', $ret);
				break;

			case XOBJ_DTYPE_ARRAY:
				$ret =& unserialize($ret);
				break;

			case XOBJ_DTYPE_SOURCE:
				switch (strtolower($format)) {
					case 's':
					case 'show':
						break 1;

					case 'e':
					case 'edit':
						return htmlspecialchars($ret, ENT_QUOTES);
						break 1;

					case 'p':
					case 'preview':
						return icms_core_DataFilter::stripSlashesGPC($ret);
						break 1;

					case 'f':
					case 'formpreview':
						return htmlspecialchars(icms_core_DataFilter::stripSlashesGPC($ret), ENT_QUOTES);
						break 1;

					case 'n':
					case 'none':
					default:
						break 1;
				}
				break;

			default:
				if ($this->vars[$key]['options'] != '' && $ret != '') {
					switch (strtolower($format)) {
						case 's':
						case 'show':
							$selected = explode('|', $ret);
							$options = explode('|', $this->vars[$key]['options']);
							$i = 1;
							$ret = array();
							foreach ($options as $op) {
								if (in_array($i, $selected)) {
									$ret[] = $op;
								}
								$i++;
							}
							return implode(', ', $ret);

						case 'e':
						case 'edit':
							$ret = explode('|', $ret);
							break 1;

						default:
							break 1;
					}

				}
				break;
		}
		return $ret;
	}

	/**
	 *
	 *
	 * @param	str	$key
	 */
	public function doMakeFieldreadOnly($key) {
		if (isset($this->vars[$key])) {
			$this->vars[$key]['readonly'] = true;
			$this->vars[$key]['displayOnForm'] = true;
		}
	}

	/**
	 *
	 *
	 * @param	str|arr	$key
	 */
	public function makeFieldReadOnly($key) {
		if (is_array($key)) {
			foreach ($key as $v) {
				$this->doMakeFieldreadOnly($v);
			}
		} else {
			$this->doMakeFieldreadOnly($key);
		}
	}

	/**
	 *
	 *
	 * @param	str	$key
	 */
	public function doHideFieldFromForm($key) {
		if (isset($this->vars[$key])) {
			$this->vars[$key]['displayOnForm'] = false;
		}
	}

	/**
	 *
	 *
	 * @param $key
	 */
	public function doHideFieldFromSingleView($key) {
		if (isset($this->vars[$key])) {
			$this->vars[$key]['displayOnSingleView'] = false;
		}
	}

	/**
	 *
	 *
	 * @param $key
	 */
	public function hideFieldFromForm($key) {
		if (is_array($key)) {
			foreach ($key as $v) {
				$this->doHideFieldFromForm($v);
			}
		} else {
			$this->doHideFieldFromForm($key);
		}
	}

	/**
	 *
	 *
	 * @param $key
	 */
	public function hideFieldFromSingleView($key) {
		if (is_array($key)) {
			foreach ($key as $v) {
				$this->doHideFieldFromSingleView($v);
			}
		} else {
			$this->doHideFieldFromSingleView($key);
		}
	}

	/**
	 *
	 *
	 * @param unknown_type $key
	 */
	public function doShowFieldOnForm($key) {
		if (isset($this->vars[$key])) {
			$this->vars[$key]['displayOnForm'] = true;
		}
	}

	/**
	 * Display an automatic SingleView of the object, based on the displayOnSingleView param of each vars
	 *
	 * @param bool $fetchOnly if set to TRUE, then the content will be return, if set to FALSE, the content will be outputed
	 * @param bool $userSide for futur use, to do something different on the user side
	 * @return content of the template if $fetchOnly or nothing if !$fetchOnly
	 */
	public function displaySingleObject($fetchOnly = false, $userSide = false, $actions = array(), $headerAsRow = true) {
		$singleview = new icms_ipf_view_Single($this, $userSide, $actions, $headerAsRow);
		// add all fields mark as displayOnSingleView except the keyid
		foreach ($this->vars as $key=>$var) {
			if ($key != $this->handler->keyName && $var['displayOnSingleView']) {
				$is_header = ($key == $this->handler->identifierName);
				$singleview->addRow(new icms_ipf_view_Row($key, false, $is_header));
			}
		}

		if ($fetchOnly) {
			$ret = $singleview->render($fetchOnly);;
			return $ret;
		} else {
			$singleview->render($fetchOnly);
		}
	}

	/**
	 *
	 *
	 * @param unknown_type $key
	 */
	public function doDisplayFieldOnSingleView($key) {
		if (isset($this->vars[$key])) {
			$this->vars[$key]['displayOnSingleView'] = true;
		}
	}

	/**
	 *
	 *
	 * @param $field
	 * @param $required
	 */
	public function doSetFieldAsRequired($field, $required = true) {
		$this->setVarInfo($field, 'required', $required);
	}

	/**
	 *
	 *
	 * @param unknown_type $field
	 */
	public function doSetFieldForSorting($field) {
		$this->setVarInfo($field, 'sortby', true);
	}

	/**
	 *
	 *
	 * @param unknown_type $key
	 */
	public function showFieldOnForm($key) {
		if (is_array($key)) {
			foreach ($key as $v) {
				$this->doShowFieldOnForm($v);
			}
		} else {
			$this->doShowFieldOnForm($key);
		}
	}

	/**
	 * delete object
	 *
	 * @param bool $force
	 * @return bool true if successful, false if not
	 */
	public function delete($force = false) {
		return $this->handler->delete($this, $force);
	}

	/**
	 *
	 *
	 * @param $key
	 */
	public function displayFieldOnSingleView($key) {
		if (is_array($key)) {
			foreach ($key as $v) {
				$this->doDisplayFieldOnSingleView($v);
			}
		} else {
			$this->doDisplayFieldOnSingleView($key);
		}
	}

	/**
	 *
	 *
	 * @param $key
	 */
	public function doSetAdvancedFormFields($key) {
		if (isset($this->vars[$key])) {
			$this->vars[$key]['advancedform'] = true;
		}
	}

	/**
	 *
	 * @param <type> $key
	 */
	public function setAdvancedFormFields($key) {
		if (is_array($key)) {
			foreach ($key as $v) {
				$this->doSetAdvancedFormFields($v);
			}
		} else {
			$this->doSetAdvancedFormFields($key);
		}
	}

	/**
	 * get urllink object
	 *
	 * @param string $key field name
	 * @return icms_data_urllink_Object
	 */
	public function getUrlLinkObj($key) {
		$urllink_handler = icms::handler("icms_data_urllink");
		$urllinkid = $this->getVar($key) != null ? $this->getVar($key) : 0;
		if ($urllinkid != 0) {
			return  $urllink_handler->get($urllinkid);
		} else {
			return $urllink_handler->create();
		}
	}

	/**
	 * store urllink object
	 *
	 * @param icms_data_urllink_Object $urllinkObj
	 * @return bool
	 */
	public function storeUrlLinkObj($urllinkObj) {
		$urllink_handler = icms::handler("icms_data_urllink");
		return $urllink_handler->insert($urllinkObj);
	}

	/**
	 * store file object
	 *
	 * @param string $key field name
	 * @return icms_data_file_Object
	 */
	function getFileObj($key) {
		$file_handler = icms::handler("icms_data_file");
		$fileid = $this->getVar($key) != null ? $this->getVar($key) : 0;
		if ($fileid != 0) {
			return  $file_handler->get($fileid);
		} else {
			return $file_handler->create();
		}
	}

	/**
	 * store file object
	 *
	 * @param icms_data_file_Object $fileObj
	 * @return bool
	 */
	function storeFileObj($fileObj) {
		$file_handler = icms::handler("icms_data_file");
		return $file_handler->insert($fileObj);
	}
}