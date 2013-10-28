<?php
/**
 * Creates a form object (Base Class)
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	LICENSE.txt
 * @package	XoopsForms
 * @since	XOOPS
 * @author	http://www.xoops.org The XOOPS Project
 * @author	modified by UnderDog <underdog@impresscms.org>
 * @version	$Id: form.php 19813 2010-07-13 23:49:13Z malanciault $
 */

/**
 * Abstract base class for forms
 *
 * @author	Kazumi Ono	<onokazu@xoops.org>
 * @author  Taiwen Jiang    <phppp@users.sourceforge.net>
 * @copyright	copyright (c) 2000-2007 XOOPS.org
 *
 * @package     kernel
 * @subpackage  form
 */
abstract class icms_form_Base {

	/**#@+
	 * @access  private
	 */
	/**
	 * "action" attribute for the html form
	 * @var string
	 */
	private $_action;

	/**
	 * "method" attribute for the form.
	 * @var string
	 */
	private $_method;

	/**
	 * "name" attribute of the form - this is not strict HTML
	 * @var string
	 */
	private $_name;

	/**
	 * title for the form
	 * @var string
	 */
	private $_title;

	/**
	 * array of {@link icms_form_Element} objects
	 * @var  array
	 */
	private $_elements = array();

	/**
	 * extra information for the <form> tag
	 * @var array
	 */
	private $_extra = array();

	/**
	 * required elements
	 * @var array
	 */
	private $_required = array();

	/**#@-*/

	/**
	 * constructor
	 *
	 * @param string  $title  title of the form
	 * @param	string  $name   "name" attribute for the <form> tag
	 * @param	string  $action "action" attribute for the <form> tag
	 * @param string  $method "method" attribute for the <form> tag
	 * @param bool    $addtoken whether to add a security token to the form
	 */
	public function __construct($title, $name, $action, $method = "post", $addtoken = false) {
		$this->_title = $title;
		$this->_name = $name;
		$this->_action = $action;
		$this->_method = $method;
		if ($addtoken != false) {
			$this->addElement(new icms_form_elements_Hiddentoken());
		}
	}

	/**
	 * return the title of the form
	 *
	 * @param	bool    $encode   Would you like to sanitize the text?
	 * @return	string
	 */
	public function getTitle($encode = false) {
		return $encode ? htmlspecialchars($this->_title, ENT_QUOTES, _CHARSET) : $this->_title;
	}

	/**
	 * get the "name" attribute for the <form> tag
	 *
	 * Deprecated, to be refactored
	 *
	 * @param	bool    $encode To sanitizer the text?
	 * @return	string
	 * @deprecated The "name" attribute is not strict HTML
	 */
	public function getName($encode = true) {
		return $encode ? htmlspecialchars($this->_name, ENT_QUOTES, _CHARSET) : $this->_name;
	}

	/**
	 * get the "action" attribute for the <form> tag
	 *
	 * @param	bool    $encode   Would you like to sanitize the text?
	 * @return	string
	 */
	public function getAction($encode = true) {
		return $encode ? htmlspecialchars($this->_action, ENT_QUOTES, _CHARSET) : $this->_action;
	}

	/**
	 * get the "method" attribute for the <form> tag
	 *
	 * @return	string
	 */
	public function getMethod(){
		return ( strtolower($this->_method) == "get" ) ? "get" : "post";
	}

	/**
	 * Add an element to the form
	 *
	 * @param	object  &$formElement   reference to a {@link icms_form_Element}
	 * @param	bool    $required       is this a "required" element?
	 */
	public function addElement(&$formElement, $required = false) {
		if ( is_string( $formElement ) ) {
			$this->_elements[] = $formElement;
		} elseif ( is_subclass_of($formElement, 'icms_form_Element') ) {
			$this->_elements[] =& $formElement;
			if (!$formElement->isContainer()) {
				if ($required) {
					$formElement->setRequired();
					$this->_required[] =& $formElement;
				}
			} else {
				$required_elements =& $formElement->getRequired();
				$count = count($required_elements);
				for ($i = 0 ; $i < $count; $i++) {
					$this->_required[] =& $required_elements[$i];
				}
			}
		}
	}

	/**
	 * get an array of forms elements
	 *
	 * @param	  bool	  get elements recursively?
	 * @return	array   array of {@link icms_form_Element}s
	 */
	public function &getElements($recurse = false) {
		if (!$recurse) {
			return $this->_elements;
		} else {
			$ret = array();
			$count = count($this->_elements);
			for ($i = 0; $i < $count; $i++) {
				if ( is_object( $this->_elements[$i] ) ) {
					if (!$this->_elements[$i]->isContainer()) {
						$ret[] =& $this->_elements[$i];
					} else {
						$elements =& $this->_elements[$i]->getElements(true);
						$count2 = count($elements);
						for ($j = 0; $j < $count2; $j++) {
							$ret[] =& $elements[$j];
						}
						unset($elements);
					}
				}
			}
			return $ret;
		}
	}

	/**
	 * get an array of "name" attributes of form elements
	 *
	 * @return	array   array of form element names
	 */
	public function getElementNames() {
		$ret = array();
		$elements =& $this->getElements(true);
		$count = count($elements);
		for ($i = 0; $i < $count; $i++) {
			$ret[] = $elements[$i]->getName();
		}
		return $ret;
	}

	/**
	 * get a reference to a {@link icms_form_Element} object by its "name"
	 *
	 * @param  string  $name	"name" attribute assigned to a {@link icms_form_Element}
	 * @return mixed  reference to a {@link icms_form_Element}, false if not found
	 */
	public function &getElementByName($name) {
		$elements = $this->getElements(true);
		$count = count($elements);
		for ($i = 0; $i < $count; $i++) {
			if ($name == $elements[$i]->getName(false)) {
				return $elements[$i];
			}
		}
		$elt = null;
		return $elt;
	}

	/**
	 * Sets the "value" attribute of a form element
	 *
	 * @param	string $name	the "name" attribute of a form element
	 * @param	string $value	the "value" attribute of a form element
	 */
	public function setElementValue($name, $value){
		$ele =& $this->getElementByName($name);
		if (is_object($ele) && method_exists($ele, 'setValue')) {
			$ele->setValue($value);
		}
	}

	/**
	 * Sets the "value" attribute of form elements in a batch
	 *
	 * @param	array $values	array of name/value pairs to be assigned to form elements
	 */
	public function setElementValues($values){
		if (is_array($values) && !empty($values)) {
			// will not use getElementByName() for performance..
			$elements =& $this->getElements(true);
			$count = count($elements);
			for ($i = 0; $i < $count; $i++) {
				$name = $elements[$i]->getName(false);
				if ($name && isset($values[$name]) && method_exists($elements[$i], 'setValue')) {
					$elements[$i]->setValue($values[$name]);
				}
			}
		}
	}

	/**
	 * Gets the "value" attribute of a form element
	 *
	 * @param	  string 	$name	the "name" attribute of a form element
	 * @param	  bool    $encode To sanitizer the text?
	 * @return	string 	the "value" attribute assigned to a form element, null if not set
	 */
	public function getElementValue($name, $encode = false) {
		$ele =& $this->getElementByName($name);
		if (is_object($ele) && method_exists($ele, 'getValue')) {
			return $ele->getValue($encode);
		}
		return;
	}

	/**
	 * gets the "value" attribute of all form elements
	 *
	 * @param	  bool    $encode To sanitizer the text?
	 * @return	array 	array of name/value pairs assigned to form elements
	 */
	public function getElementValues($encode = false) {
		// will not use getElementByName() for performance..
		$elements =& $this->getElements(true);
		$count = count($elements);
		$values = array();
		for ($i = 0; $i < $count; $i++) {
			$name = $elements[$i]->getName(false);
			if ($name && method_exists($elements[$i], 'getValue')) {
				$values[$name] =& $elements[$i]->getValue($encode);
			}
		}
		return $values;
	}

	/**
	 * set the extra attributes for the <form> tag
	 *
	 * @param	string  $extra  extra attributes for the <form> tag
	 */
	public function setExtra($extra) {
		if (!empty($extra)) {
			$this->_extra[] = $extra;
		}
	}

	/**
	 * get the extra attributes for the <form> tag
	 *
	 * @return	string $extra
	 */
	public function &getExtra() {
		$extra = empty($this->_extra) ? "" : " ". implode(" ", $this->_extra);
		return $extra;
	}

	/**
	 * make an element "required"
	 *
	 * @param	object  &$formElement    reference to a {@link icms_form_Element}
	 */
	public function setRequired(&$formElement) {
		$this->_required[] =& $formElement;
	}

	/**
	 * get an array of "required" form elements
	 *
	 * @return	array   array of {@link icms_form_Element}s
	 */
	public function &getRequired() {
		return $this->_required;
	}

	/**
	 * insert a break in the form
	 *
	 * This method is abstract. It must be overwritten in the child classes.
	 *
	 * @param	string  $extra  extra information for the break
	 * @abstract
	 */
	abstract public function insertBreak($extra = null);

	/**
	 * returns renderered form
	 *
	 * This method is abstract. It must be overwritten in the child classes.
	 *
	 * @abstract
	 */
	abstract public function render();

	/**
	 * displays rendered form
	 */
	public function display() {
		echo $this->render();
	}

	/**
	 * Renders the Javascript function needed for client-side for validation
	 *
	 * Form elements that have been declared "required" and not set will prevent the form from being
	 * submitted. Additionally, each element class may provide its own "renderValidationJS" method
	 * that is supposed to return custom validation code for the element.
	 *
	 * The element validation code can assume that the JS "myform" variable points to the form, and must
	 * execute <i>return false</i> if validation fails.
	 *
	 * A basic element validation method may contain something like this:
	 * <code>
	 * function renderValidationJS() {
	 *   $name = $this->getName();
	 *   return "if ( myform.{$name}.value != 'valid' ) { " .
	 *     "myform.{$name}.focus(); window.alert( '$name is invalid' ); return false;" .
	 *     " }";
	 * }
	 * </code>
	 *
	 * @param		boolean  $withtags	Include the < javascript > tags in the returned string
	 */
	public function renderValidationJS( $withtags = true ) {
		$js = "";
		if ( $withtags ) {
			$js .= "\n<!-- Start Form Validation JavaScript //-->\n<script type='text/javascript'>\n<!--//\n";
		}
		$formname = $this->getName();
		$js .= "function xoopsFormValidate_{$formname}() { myform = window.document.{$formname}; ";
		$elements = $this->getElements( true );
		foreach ( $elements as $elt ) {
			if ( method_exists( $elt, 'renderValidationJS' ) ) {
				$js .= $elt->renderValidationJS();
			}
		}
		$js .= "return true;\n}\n";
		if ( $withtags ) {
			$js .= "//--></script>\n<!-- End Form Vaidation JavaScript //-->\n";
		}
		return $js;
	}

	/**
	 * assign to smarty form template instead of displaying directly
	 *
	 * @param	object  &$tpl    reference to a {@link Smarty} object
	 * @see     Smarty
	 */
	public function assign(&$tpl) {
		$i = -1;
		$elements = array();
		foreach ( $this->getElements() as $ele ) {
			++$i;
			if (is_string( $ele )) {
				$elements[$i]['body']	= $ele;
				continue;
			}
			$ele_name = $ele->getName();
			$ele_description = $ele->getDescription();
			$n = $ele_name ? $ele_name : $i;
			$elements[$n]['name']       = $ele_name;
			$elements[$n]['caption']    = $ele->getCaption();
			$elements[$n]['body']       = $ele->render();
			$elements[$n]['hidden']     = $ele->isHidden();
			$elements[$n]['required']   = $ele->isRequired();
			if ($ele_description != '') {
				$elements[$n]['description']  = $ele_description;
			}
		}
		$js = $this->renderValidationJS();
		$tpl->assign($this->getName(), array('title' => $this->getTitle(), 'name' => $this->getName(), 'action' => $this->getAction(),  'method' => $this->getMethod(), 'extra' => 'onsubmit="return xoopsFormValidate_'.$this->getName().'();"'.$this->getExtra(), 'javascript' => $js, 'elements' => $elements));
	}
}