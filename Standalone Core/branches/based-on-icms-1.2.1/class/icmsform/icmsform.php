<?php
/**
* Form control creating an image upload element for an object derived from IcmsPersistableObject
*
* @copyright	The ImpressCMS Project http://www.impresscms.org/
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package		IcmsPersistableObject
* @since		  1.1
* @author		  marcan <marcan@impresscms.org>
* @version		$Id: icmsform.php 9391 2009-09-13 13:31:08Z Phoenyx $
*/

if (!defined('ICMS_ROOT_PATH')) die("ImpressCMS root path not defined");

/**
 * Including the XoopsFormLoader classes
 */
include_once ICMS_ROOT_PATH . '/class/xoopsformloader.php';

/**
* IcmsForm base class
*
* Base class representing a single form for a specific IcmsPersistableObject
*
* @copyright	The ImpressCMS Project http://www.impresscms.org/
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package		IcmsPersistableObject
* @since		  1.1
* @author		  marcan <marcan@impresscms.org>
* @version		$Id: icmsform.php 9391 2009-09-13 13:31:08Z Phoenyx $
*/
class IcmsForm extends XoopsThemeForm {

	var $targetObject = null;
	var $form_fields = null;
	var $_cancel_js_action=false;
	var $_custom_button=false;
	var $_captcha=false;
	var $_form_name=false;
	var $_form_caption=false;
	var $_submit_button_caption=false;



	/**
	 * Constructor
     * Sets all the values / variables for the IcmsForm class
	 * @param	string    &$target                  reference to targetobject (@todo, which object will be passed here?)
	 * @param	string    $form_name                the form name
	 * @param	string    $form_caption             the form caption
	 * @param	string    $form_action              the form action
	 * @param	string    $form_fields              the form fields
	 * @param	string    $submit_button_caption    whether to add a caption to the submit button
	 * @param	bool      $cancel_js_action         whether to invoke a javascript action when cancel button is clicked
	 * @param	bool      $captcha                  whether to add captcha
	 */
	function IcmsForm(&$target, $form_name, $form_caption, $form_action, $form_fields=null, $submit_button_caption = false, $cancel_js_action=false, $captcha=false) {

		$this->targetObject =& $target;
		$this->form_fields = $form_fields;
		$this->_cancel_js_action = $cancel_js_action;
		$this->_captcha= $captcha;
		$this->_form_name= $form_name;
		$this->_form_caption= $form_caption;
		$this->_submit_button_caption= $submit_button_caption;

		if (!isset($form_action)) {
			$form_action = xoops_getenv('PHP_SELF');
		}

		$this->XoopsThemeForm( $form_caption , $form_name, $form_action);
		$this->setExtra('enctype="multipart/form-data"');

		$this->createElements();

		if ($captcha) {
			$this->addCaptcha();
		}

		$this->createPermissionControls();

		$this->createButtons($form_name, $form_caption, $submit_button_caption);
	}

	/**
	 * @todo to be implented later...
	 */
	/*
	function addCaptcha() {
		include_once(SMARTOBJECT_ROOT_PATH . 'include/captcha/formcaptcha.php');
		$this->addElement(new XoopsFormCaptcha(), true);
	}
	*/

	/**
	 * Sets variables for adding custom button
	 *
	 * @param   string  $name       button name
	 * @param   string  $caption    load the config's options now?
	 * @return	bool    $onclick    wheter to add onclick event
	 */
	function addCustomButton($name, $caption, $onclick=false) {
		$custom_button_array = array(
						'name' => $name,
						'caption' => $caption,
						'onclick' => $onclick
		);
		$this->_custom_button[] = $custom_button_array;
	}

	/**
	 * Add an element to the form
	 *
   * @param	object  &$formElement   reference to a {@link XoopsFormElement}
   * @param	string  $key            encrypted key string for the form
   * @param	string  $var            some form variables?
   * @param	bool    $required       is this a "required" element?
	 */
	function addElement(&$formElement, $key=false, $var=false, $required='notset'){
		if ($key) {
			if ($this->targetObject->vars[$key]['readonly']) {
				$formElement->setExtra('disabled="disabled"');
				$formElement->setName($key . '-readonly');
				// Since this element is disabled, we still want to pass it's value in the form
				$hidden = new XoopsFormHidden($key, $this->targetObject->vars[$key]['value']);
				$this->addElement($hidden);
			}
			$formElement->setDescription($var['form_dsc']);
			if (isset($this->targetObject->controls[$key]['onSelect'])) {
				$hidden = new XoopsFormHidden('changedField', false);
				$this->addElement($hidden);
				$otherExtra = isset($var['form_extra']) ? $var['form_extra'] : '';
				$onchangedString = "this.form.elements.changedField.value='$key'; this.form.elements.op.value='changedField'; submit()";
				$formElement->setExtra('onchange="' . $onchangedString . '"' . ' ' . $otherExtra);
			} else {
				if (isset($var['form_extra'])) {
					$formElement->setExtra($var['form_extra']);
				}
			}
			$controls = $this->targetObject->controls;
			if(isset($controls[$key]['js'])){
				$formElement->customValidationCode[] = $controls[$key]['js'];
			}
			parent::AddElement($formElement, $required == 'notset' ? $var['required'] : $required);
		} else {
			parent::AddElement($formElement, $required == 'notset' ? false : true);
		}
		unset($formElement);
	}


	/**
	 * Adds an element to the form
	 *
   * gets all variables from &targetobject (@todo, which object will be passed here?)
	 * that was set during the construction of this object (in the constructor)
   * loops through all variables and determines what data type the key has
   * adds an element for each key based on the datatype
	 */
	function createElements() {
		$controls = $this->targetObject->controls;
		$vars = $this->targetObject->vars;
		foreach ($vars as $key=>$var) {

			// If $displayOnForm is false OR this is the primary key, it doesn't
			// need to be displayed, then we only create an hidden field
			if ($key == $this->targetObject->handler->keyName || !$var['displayOnForm']) {
				$elementToAdd = new XoopsFormHidden($key, $var['value']);
				$this->addElement($elementToAdd, $key, $var, false);
				unset($elementToAdd);
				// If not, the we need to create the proper form control for this fields
			} else {
				// If this field has a specific control, we will use it

				if ($key == 'parentid') {
					/**
					 * Why this ?
					 */
				}
				if (isset($controls[$key])) {
					/* If the control has name, it's because it's an object already present in the script
					* for example, "user"
					* If the field does not have a name, than we will use a "select" (ie XoopsFormSelect)
					*/
					if (!isset($controls[$key]['name']) || !$controls[$key]['name']) {
						$controls[$key]['name'] = 'select';
					}

					$form_select = $this->getControl($controls[$key]['name'], $key);

					// Adding on the form, the control for this field
					$this->addElement($form_select, $key, $var);
					unset($form_select);

					// If this field don't have a specific control, we will use the standard one, depending on its data type
				} else {
					switch ($var['data_type']) {

						case XOBJ_DTYPE_TXTBOX:

							$form_text = $this->getControl("text", $key);
							$this->addElement($form_text, $key, $var);
							unset($form_text);
							break;

						case XOBJ_DTYPE_INT:
							$this->targetObject->setControl($key, array(
																'name' => 'text',
																'size' => '5'
															));
							$form_text = $this->getControl("text", $key);
							$this->addElement($form_text, $key, $var);
							unset($form_text);
							break;

						case XOBJ_DTYPE_FLOAT:
							$this->targetObject->setControl($key, array(
																'name' => 'text',
																'size' => '5'
															));
							$form_text = $this->getControl("text", $key);
							$this->addElement($form_text, $key, $var);
							unset($form_text);
							break;

						case XOBJ_DTYPE_LTIME:
							$form_date_time = $this->getControl('date_time', $key);
							$this->addElement($form_date_time, $key, $var);
							unset($form_date_time);
							break;

						case XOBJ_DTYPE_STIME:
							$form_date_time = $this->getControl('date', $key);
							$this->addElement($form_date_time, $key, $var);
							unset($form_date_time);
							break;

						case XOBJ_DTYPE_TIME_ONLY:
							$form_time = $this->getControl('time', $key);
							$this->addElement($form_time, $key, $var);
							unset($form_time);
							break;

						case XOBJ_DTYPE_CURRENCY:
							$this->targetObject->setControl($key, array(
																'name' => 'text',
																'size' => '15'
															));
							$form_currency = $this->getControl("text", $key);
							$this->addElement($form_currency, $key, $var);
							unset($form_currency);
							break;

						case XOBJ_DTYPE_URLLINK:
							$form_urllink = $this->getControl("urllink", $key);
							$this->addElement($form_urllink, $key, $var);
							unset($form_urllink);
							break;

						case XOBJ_DTYPE_FILE:
							$form_file = $this->getControl("richfile", $key);
							$this->addElement($form_file, $key, $var);
							unset($form_file);
							break;

						case XOBJ_DTYPE_TXTAREA:
							$form_text_area = $this->getControl('textarea', $key);
							$this->addElement($form_text_area, $key, $var);
							unset($form_text_area);
							break;

						case XOBJ_DTYPE_ARRAY:
							// TODO : To come...
							break;
						case XOBJ_DTYPE_SOURCE:
							$form_source_editor = $this->getControl('source', $key);							
							$this->addElement($form_source_editor, $key, $var);
							unset($form_source_editor);
							break;
						case XOBJ_DTYPE_FORM_SECTION:
							$section_control = new IcmsFormSection($key, $var['value']);
							$this->addElement($section_control, $key, $var);
							unset($section_control);
							break;
						case XOBJ_DTYPE_FORM_SECTION_CLOSE:
							$section_control = new IcmsFormSectionClose($key, $var['value']);
							$this->addElement($section_control, $key, $var);
							unset($section_control);
							break;
					}
				}
			}
		}
		// Add a hidden field to store the URL of the page before this form
		$this->addElement(new XoopsFormHidden('icms_page_before_form', icms_get_page_before_form()));
	}

	/**
	 * Creates Permission Controls
	 */
	function createPermissionControls() {
		$icmsModuleConfig = $this->targetObject->handler->getModuleConfig();

		$permissions = $this->targetObject->handler->getPermissions();

		if ($permissions) {
			$member_handler = &xoops_gethandler('member');
			$group_list = $member_handler->getGroupList();
			asort($group_list);
			foreach($permissions as $permission) {
				$groups_value = false;
				if ($this->targetObject->isNew()) {
					if (isset($icmsModuleConfig['def_perm_' . $permission['perm_name']])) {
						$groups_value = $icmsModuleConfig['def_perm_' . $permission['perm_name']];
					}
				} else {
					$groups_value = $this->targetObject->getGroupPerm($permission['perm_name']);
				}
				$groups_select = new XoopsFormSelect($permission['caption'], $permission['perm_name'], $groups_value, 4, true);
				$groups_select->setDescription($permission['description']);
				$groups_select->addOptionArray($group_list);
				$this->addElement($groups_select);
				unset($groups_select);
			}
		}
	}

	/**
	 * Add an element to the form
	 *
   * @param	string  $form_name              name of the form
   * @param	string  $form_caption           caption of the form
   * @param	string  $submit_button_caption  caption of the button
	 */
	function createButtons($form_name, $form_caption, $submit_button_caption = false) {

		$button_tray = new XoopsFormElementTray('', '');
		$button_tray->addElement(new XoopsFormHidden('op', $form_name));
		if(!$submit_button_caption){
			if ($this->targetObject->isNew()) {
				$butt_create = new XoopsFormButton('', 'create_button', _CO_ICMS_CREATE, 'submit');
			} else {
				$butt_create = new XoopsFormButton('', 'modify_button', _CO_ICMS_MODIFY, 'submit');
			}
		}
		else {
			$butt_create = new XoopsFormButton('', 'modify_button', $submit_button_caption , 'submit');
		}
		$butt_create->setExtra('onclick="this.form.elements.op.value=\'' . $form_name . '\'"');
		$button_tray->addElement($butt_create);

		//creating custom buttons
		if ($this->_custom_button) {
			foreach($this->_custom_button as $custom_button) {
				$butt_custom = new XoopsFormButton('', $custom_button['name'], $custom_button['caption'], 'submit');
				if ($custom_button['onclick']) {
					$butt_custom->setExtra('onclick="' . $custom_button['onclick'] . '"');
				}
				$button_tray->addElement($butt_custom);
				unset($butt_custom);
			}
		}

		// creating the "cancel" button
		$butt_cancel = new XoopsFormButton('', 'cancel_button', _CO_ICMS_CANCEL, 'button');
		if ($this->_cancel_js_action) {
			$butt_cancel->setExtra('onclick="' . $this->_cancel_js_action . '"');
		} else {
			$butt_cancel->setExtra('onclick="history.go(-1)"');
		}
		$button_tray->addElement($butt_cancel);

		$this->addElement($button_tray);
	}


	/**
	 * Gets a control from the targetobject (@todo, which object will be passed here?)
	 *
   * @param	string  $controlName   name of the control element
   * @param	string  $key           key of the form variables in the targetobject
	 */
	function getControl($controlName, $key) {
		switch ($controlName) {
			case 'check':
				include_once(ICMS_ROOT_PATH."/class/icmsform/elements/icmsformcheckelement.php");
				$control = $this->targetObject->getControl($key);
				$controlObj = new IcmsFormCheckElement($this->targetObject->vars[$key]['form_caption'], $key, $this->targetObject->getVar($key));
				$controlObj->addOptionArray($control['options']);
				return $controlObj;
				break;

			case 'color':
				$control = $this->targetObject->getControl($key);
				$controlObj = new XoopsFormColorPicker($this->targetObject->vars[$key]['form_caption'], $key, $this->targetObject->getVar($key));
				return $controlObj;
				break;

			case 'radio':
				$control = $this->targetObject->getControl($key);

				$controlObj = new XoopsFormRadio($this->targetObject->vars[$key]['form_caption'], $key, $this->targetObject->getVar($key));
				$controlObj->addOptionArray($control['options']);
				return $controlObj;
				break;

			case 'label':
				return new XoopsFormLabel($this->targetObject->vars[$key]['form_caption'], $this->targetObject->getVar($key));
				break;

			case 'textarea' :
				$form_rows = isset($this->targetObject->controls[$key]['rows']) ? $this->targetObject->controls[$key]['rows'] : 5;
				$form_cols = isset($this->targetObject->controls[$key]['cols']) ? $this->targetObject->controls[$key]['cols'] : 60;

				$editor = new XoopsFormTextArea($this->targetObject->vars[$key]['form_caption'], $key, $this->targetObject->getVar($key, 'e'), $form_rows, $form_cols);
				if ($this->targetObject->vars[$key]['form_dsc']) {
					$editor->setDescription($this->targetObject->vars[$key]['form_dsc']);
				}
				return $editor;
				break;

			case 'dhtmltextarea' :
				$editor = new XoopsFormDhtmlTextArea($this->targetObject->vars[$key]['form_caption'], $key, $this->targetObject->getVar($key, 'e'), 15, 50);
				if ($this->targetObject->vars[$key]['form_dsc']) {
					$editor->setDescription($this->targetObject->vars[$key]['form_dsc']);
				}
				return $editor;
				break;

			case 'theme':
				return $this->getThemeSelect($key, $this->targetObject->vars[$key]);

			case 'theme_multi':
				return $this->getThemeSelect($key, $this->targetObject->vars[$key], true);
				break;

			case 'timezone':
				return new XoopsFormSelectTimezone($this->targetObject->vars[$key]['form_caption'], $key, $this->targetObject->getVar($key));
				break;

			case 'group':
				return new XoopsFormSelectGroup($this->targetObject->vars[$key]['form_caption'], $key, false, $this->targetObject->getVar($key, 'e'), 1, false);
				break;

			case 'group_multi':
				return new XoopsFormSelectGroup($this->targetObject->vars[$key]['form_caption'], $key, false, $this->targetObject->getVar($key, 'e'), 5, true);
				break;

			/*case 'user':
				return new XoopsFormSelectUser($this->targetObject->vars[$key]['form_caption'], $key, false, $this->targetObject->getVar($key, 'e'), 1, false);
				break;*/

			case 'user_multi':
				return new XoopsFormSelectUser($this->targetObject->vars[$key]['form_caption'], $key, false, $this->targetObject->getVar($key, 'e'), 5, true);
				break;

			case 'password':
				return new XoopsFormPassword($this->targetObject->vars[$key]['form_caption'], $key, 50, 255, $this->targetObject->getVar($key, 'e'));
				break;

			case 'country':
				return new XoopsFormSelectCountry($this->targetObject->vars[$key]['form_caption'], $key, $this->targetObject->getVar($key, 'e'));
				break;

			case 'urllink':
				include_once(ICMS_ROOT_PATH."/class/icmsform/elements/icmsformurllinkelement.php");
				return new IcmsFormUrlLinkElement($this->targetObject->vars[$key]['form_caption'], $key, $this->targetObject->getUrlLinkObj($key));
				break;

			case 'richfile':
				include_once(ICMS_ROOT_PATH."/class/icmsform/elements/icmsformrichfileelement.php");
				return new IcmsFormRichFileElement($this->targetObject->vars[$key]['form_caption'], $key, $this->targetObject->getFileObj($key));
				break;

			case 'source':
			case 'sourceeditor':
				include_once(ICMS_ROOT_PATH."/class/icmsform/elements/icmsformsourceeditor.php");
				return new IcmsFormSourceEditor($this->targetObject->vars[$key]['form_caption'], $key, $this->targetObject->getVar($key, 'e'));
			break;


			default:
				$classname = "IcmsForm".ucfirst($controlName)."Element";
				if (!class_exists($classname)) {
					if (file_exists(ICMS_ROOT_PATH."/class/icmsform/elements/".strtolower($classname).".php")) {
						include_once(ICMS_ROOT_PATH."/class/icmsform/elements/".strtolower($classname).".php");
					} else {
						// perhaps this is a control created by the module
						$moduleName = $this->targetObject->handler->_moduleName;
						if($module_dir != 'system')
							$moduleFormElementsPath = $this->targetObject->handler->_modulePath.'/class/form/elements/';
						else
							$moduleFormElementsPath = $this->targetObject->handler->_modulePath.'/admin/{$name}/class/form/elements/';
						$classname = ucfirst($moduleName) . ucfirst($controlName) . "Element";
						$classFileName = strtolower($classname).".php";

						if (file_exists($moduleFormElementsPath . $classFileName)) {
							include_once($moduleFormElementsPath . $classFileName);
						} else {
							trigger_error($classname." Not found", E_USER_WARNING);
							return new XoopsFormLabel(); //Empty object
						}
					}
				}
				return new $classname($this->targetObject, $key);
				break;
		}
	}

	/**
	 * Get information for the theme select box
	 *
   * @param	string  $key        key of the variables in the targetobject
   * @param	string  $var        key of the variables in the targetobject
   * @param	bool    $multiple   will you need a form element which shows multiple items
	 */
	function getThemeSelect($key, $var, $multiple=false) {

		$size = $multiple ? 5 : 1;
		$theme_select = new XoopsFormSelect($var['form_caption'], $key, $this->targetObject->getVar($key), $size, $multiple);

		$handle = opendir(XOOPS_THEME_PATH.'/');
		$dirlist = array();
		while (false !== ($file = readdir($handle))) {
			if (is_dir(XOOPS_THEME_PATH.'/'.$file) && !preg_match("/^[.]{1,2}$/",$file) && strtolower($file) != 'cvs') {
				$dirlist[$file]=$file;
			}
		}
		closedir($handle);
		if (!empty($dirlist)) {
			asort($dirlist);
			$theme_select->addOptionArray($dirlist);
		}

		return $theme_select;
	}


	/**
	 * Gets reference to the object for each key in the variables of the targetobject
	 *
   * @param	string  $keyname  name of the key
   * @param	mixed   $ret      Object if the returned object is set, false if no object called (if getname is not equal to the passed keyname)
	 */
	function &getElementById($keyname) {
		foreach ($this->_elements as $eleObj) {
			if ($eleObj->getName() == $keyname) {
				$ret =& $eleObj;
				break;
			}
		}
		return isset($ret) ? $ret : false;
	}






	/**
	 * create HTML to output the form as a theme-enabled table with validation.
   *
	 * @return	string  $ret
	 */
	function render()
	{
		$required =& $this->getRequired();
		$ret = "
			<form name='".$this->getName()."_dorga' id='".$this->getName()."' action='".$this->getAction()."' method='".$this->getMethod()."' onsubmit='return xoopsFormValidate_".$this->getName()."(this);'".$this->getExtra().">
			<table width='100%' class='outer' cellspacing='1'>
			<tr><th colspan='2'>".$this->getTitle()."</th></tr>
		";
		$hidden = '';
		$class ='even';
		foreach ( $this->getElements() as $ele ) {
			if (!is_object($ele)) {
				$ret .= $ele;
			} elseif ( !$ele->isHidden() ) {
				//$class = ( $class == 'even' ) ? 'odd' : 'even';
				$ret .= "<tr id='" . $ele->getName() . "_row' valign='top' align='"._GLOBAL_LEFT."'><td class='head'>".$ele->getCaption();
				if ($ele->getDescription() != '') {
					$ret .= '<br /><br /><span style="font-weight: normal;">'.$ele->getDescription().'</span>';
				}
				$ret .= "</td><td class='$class'>".$ele->render()."</td></tr>\n";
			} else {
				$hidden .= $ele->render();
			}
		}
		$ret .= "</table>\n$hidden\n</form>\n";
		$ret .= $this->renderValidationJS( true );
		return $ret;
	}




	/**
	 * assign to smarty form template instead of displaying directly
	 *
	 * @param	object  &$tpl         reference to a {@link Smarty} object
	 * @see           Smarty
	 * @param	mixed   $smartyName   if smartyName is passed, assign it to the smarty call else assign the name of the form element
	 */
	function assign(&$tpl, $smartyName=false){
		$i = 0;
		$elements = array();
		foreach ( $this->getElements() as $ele ) {
      $n = ($ele->getName() != "") ? $ele->getName() : $i;
			$elements[$n]['name']	  = $ele->getName();
			$elements[$n]['caption']  = $ele->getCaption();
			$elements[$n]['body']	  = $ele->render();
			$elements[$n]['hidden']	  = $ele->isHidden();
			$elements[$n]['required']	  = $ele->isRequired();
			$elements[$n]['section']  = strToLower(get_class($ele)) == strToLower('IcmsFormSection');
			$elements[$n]['section_close']  = get_class($ele) == 'IcmsFormSectionClose';
			$elements[$n]['hide'] = isset($this->targetObject->vars[$n]['hide']) ? $this->targetObject->vars[$n]['hide'] : false;

			if ($ele->getDescription() != '') {
			    $elements[$n]['description']  = $ele->getDescription();
			}
			$i++;
		}
		$js = $this->renderValidationJS();
		if (!$smartyName) {
			$smartyName = $this->getName();
		}

		$tpl->assign($smartyName, array('title' => $this->getTitle(), 'name' => $this->getName(), 'action' => $this->getAction(),  'method' => $this->getMethod(), 'extra' => 'onsubmit="return xoopsFormValidate_'.$this->getName().'(this);"'.$this->getExtra(), 'javascript' => $js, 'elements' => $elements));
	}


	/**
	 * create HTML to output the form as a theme-enabled table with validation.
   *
	 * @param	  bool  $withtags   whether to add script HTML tag to the $js string
	 * @return	bool  $js         the constructed javascript validation string
	 */
  function renderValidationJS( $withtags = true ) {
		$js = "";
		if ( $withtags ) {
			$js .= "\n<!-- Start Form Validation JavaScript //-->\n<script type='text/javascript'>\n<!--//\n";
		}
		$myts =& MyTextSanitizer::getInstance();
		$formname = $this->getName();
		$js .= "function xoopsFormValidate_{$formname}(myform) {";
		// First, output code to check required elements
		$elements = $this->getRequired();
		foreach ( $elements as $elt ) {
			$eltname    = $elt->getName();
			$eltcaption = trim( $elt->getCaption() );
			$eltmsg = empty($eltcaption) ? sprintf( _FORM_ENTER, $eltname ) : sprintf( _FORM_ENTER, $eltcaption );
			$eltmsg = str_replace('"', '\"', stripslashes( $eltmsg ) );
			if (strtolower(get_class($elt)) == 'xoopsformradio') {
				$js .= "var myOption = -1;";
				$js .= "for (i=myform.{$eltname}.length-1; i > -1; i--) {
					if (myform.{$eltname}[i].checked) {
						myOption = i; i = -1;
					}
				}
				if (myOption == -1) {
					window.alert(\"{$eltmsg}\"); myform.{$eltname}[0].focus(); return false; }\n";

			}elseif (strtolower(get_class($elt)) == 'icmsformselect_multielement') {
				$js .= "var hasSelections = false;";
				$js .= "for(var i = 0; i < myform['{$eltname}[]'].length; i++){
					if (myform['{$eltname}[]'].options[i].selected) {
						hasSelections = true;
					}

				}
				if (hasSelections == false) {
					window.alert(\"{$eltmsg}\"); myform['{$eltname}[]'].options[0].focus(); return false; }\n";

			}elseif (strtolower(get_class($elt)) == 'xoopsformcheckbox') {
				$js .= "var hasSelections = false;";
				//sometimes, there is an implicit '[]', sometimes not
				if(strpos($eltname, '[') === false){
					$js .= "for(var i = 0; i < myform['{$eltname}[]'].length; i++){
						if (myform['{$eltname}[]'][i].checked) {
							hasSelections = true;
						}

					}
					if (hasSelections == false) {
						window.alert(\"{$eltmsg}\"); myform['{$eltname}[]'][0].focus(); return false; }\n";
				}else{
					$js .= "for(var i = 0; i < myform['{$eltname}'].length; i++){
						if (myform['{$eltname}'][i].checked) {
							hasSelections = true;
						}

					}
					if (hasSelections == false) {
						window.alert(\"{$eltmsg}\"); myform['{$eltname}'][0].focus(); return false; }\n";
				}

			}else{
				$js .= "if ( myform.{$eltname}.value == \"\" ) "
					. "{ window.alert(\"{$eltmsg}\"); myform.{$eltname}.focus(); return false; }\n";
				}
		}
		// Now, handle custom validation code
		$elements = $this->getElements( true );
		foreach ( $elements as $elt ) {
			if ( method_exists( $elt, 'renderValidationJS') && strtolower(get_class($elt)) != 'xoopsformcheckbox') {
				if ( $eltjs = $elt->renderValidationJS() ) {
					$js .= $eltjs . "\n";
				}
			}
		}
		$js .= "return true;\n}\n";
		if ( $withtags ) {
			$js .= "//--></script>\n<!-- End Form Vaidation JavaScript //-->\n";
		}
		return $js;
	}

	function renderValidationJS2( $withtags = true ) {
		global $xoTheme;
        $rules = $titles = '';
		$elements = $this->getRequired();
		foreach ( $elements as $elt ) {
			if(!empty($rules))
				$rules .= ",";
			$rules .= '\''.$elt->getName().'\': { required: true }';
			if(!empty($titles))
				$titles .= ",";
			$titles .= $elt->getName().': "'._REQUIRED.'"';
		}
		$xoTheme->addScript('', array('type' => 'text/javascript'), 'alert($());$().ready(function() { $("#'.$this->getName().'").validate({
		rules: {
			'.$rules.'
		},
		messages: {
			'.$titles.'
		}
		})});');
	}
}

?>