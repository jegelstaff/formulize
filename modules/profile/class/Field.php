<?php
/**
 * Class representing the profile field object
 *
 * @copyright	The ImpressCMS <Project http://www.impresscms.org/>
 * @license		LICENSE.txt
 * @license		GNU General Public License (GPL) http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @package		profile
 * @since		1.2
 * @author		Jan Pedersen
 * @author		The SmartFactory <http://www.smartfactory.ca>
 * @author		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version		$Id: Field.php 22530 2011-09-02 19:57:57Z phoenyx $
 */

defined('ICMS_ROOT_PATH') or die('ICMS root path not defined');

class mod_profile_Field extends icms_ipf_Object {
	/**
	 * Constructor
	 *
	 * @param mod_profile_FieldHandler $handler object handler
	 */
	public function __construct(&$handler) {
		parent::__construct($handler);

		$this->quickInitVar('fieldid', XOBJ_DTYPE_INT, true);
		$this->quickInitVar('catid', XOBJ_DTYPE_INT, false);
		$this->quickInitVar('field_type', XOBJ_DTYPE_TXTBOX, false);
		$this->quickInitVar('field_valuetype', XOBJ_DTYPE_TXTBOX, false);
		$this->quickInitVar('field_name', XOBJ_DTYPE_TXTBOX, true);
		$this->quickInitVar('field_title', XOBJ_DTYPE_TXTBOX, true);
		$this->quickInitVar('url', XOBJ_DTYPE_IMAGE, true);
		$this->quickInitVar('field_description', XOBJ_DTYPE_TXTAREA, false);
		$this->quickInitVar('field_required', XOBJ_DTYPE_INT, false);
		$this->quickInitVar('field_maxlength', XOBJ_DTYPE_TXTBOX, false, false, false, 0);
		$this->quickInitVar('field_weight', XOBJ_DTYPE_INT, false, false, false, 0);
		$this->quickInitVar('field_default', XOBJ_DTYPE_TXTAREA, false);
		$this->quickInitVar('field_notnull', XOBJ_DTYPE_INT, false);
		$this->quickInitVar('field_edit', XOBJ_DTYPE_INT, false);
		$this->quickInitVar('field_show', XOBJ_DTYPE_INT, false);
		$this->quickInitVar('field_options', XOBJ_DTYPE_TXTBOX, false);
		$this->quickInitVar('exportable', XOBJ_DTYPE_INT, false);
		$this->quickInitVar('step_id', XOBJ_DTYPE_INT, false);
		$this->quickInitVar('system', XOBJ_DTYPE_INT, false, false, false, 0);
		
		$this->hideFieldFromForm(array('field_valuetype', 'system'));

		$this->setControl('url', array('name' => 'image', 'nourl' => true));
		$this->setControl('field_required', 'yesno');
		$this->setControl('field_notnull', 'yesno');
		$this->setControl('field_edit', 'yesno');
		$this->setControl('field_show', 'yesno');
		$this->setControl('exportable', 'yesno');
		$this->setControl('system', 'yesno');
		$this->setControl('catid', array('itemHandler' => 'category', 'method' => 'getList', 'module' => 'profile'));
		$this->setControl('field_type', array ('itemHandler' => 'field', 'method' => 'getFieldTypeArray', 'module' => 'profile'));
		$this->setControl('step_id', array('itemHandler' => 'regstep', 'method' => 'getListForFields', 'module' => 'profile'));
	}

	/**
	 * Returns a {@link icms_form_Element} for editing the value of this field
	 *
	 * @param icms_member_user_Object $user {@link icms_member_user_Object} object to edit the value of
	 * @param mod_profile_Profile $profile {@link mod_profile_Profile} object to edit the value of
	 *
	 * @return icms_form_Element
	 */
	public function getEditElement($user, $profile) {
		$value = in_array($this->getVar('field_name'), $this->getUserVars()) ? $user->getVar($this->getVar('field_name'), 'e') : $profile->getVar($this->getVar('field_name'), 'e');
		if ($value === null) $value = $this->getVar('field_default');
		$caption = $this->getVar('field_title');
		$caption = defined($caption) ? constant($caption) : $caption;
		$name = $this->getVar('field_name', 'e');
		$options = unserialize($this->getVar('field_options', 'n'));
		if ($this->getVar('field_type') != "image" && is_array($options)) {
			asort($options);

			foreach(array_keys($options) as $key){
				$optval = defined($options[$key]) ? constant($options[$key]) : $options[$key];
				$optkey = defined($key) ? constant($key) : $key;
				unset($options[$key]);
				$options[$optkey] = $optval;
			}
		}
		switch ($this->getVar('field_type')) {
			case "autotext":
				$element = new icms_form_elements_Label($caption, $value);
				break;
			case "textarea":
				$element = new icms_form_elements_Textarea($caption, $name, $value, 4, 30);
				break;
			case "dhtml":
				$element = new icms_form_elements_Dhtmltextarea($caption, $name, $value, 10, 30);
				break;
			case "select":
				$element = new icms_form_elements_Select($caption, $name, $value);
				$element->addOptionArray($options);
				break;
			case "select_multi":
				$element = new icms_form_elements_Select($caption, $name, $value, 5, true);
				$element->addOptionArray($options);
				break;
			case "radio":
				$element = new icms_form_elements_Radio($caption, $name, $value);
				$element->addOptionArray($options);
				break;
			case "checkbox":
				$element = new icms_form_elements_Checkbox($caption, $name, $value);
				$element->addOptionArray($options);
				break;
			case "yesno":
				$element = new icms_form_elements_Radioyn($caption, $name, $value);
				break;
			case "group":
				$element = new icms_form_elements_select_Group($caption, $name, true, $value);
				break;
			case "group_multi":
				$element = new icms_form_elements_select_Group($caption, $name, true, $value, 5, true);
				break;
			case "language":
				$element = new icms_form_elements_select_Lang($caption, $name, $value);
				break;
			case "date":
				$element = new icms_form_elements_Date($caption, $name, 15, $value);
				break;
			case "longdate":
				$element = new icms_form_elements_Date($caption, $name, 15, str_replace("-", "/", $value));
				break;
			case "datetime":
				$element = new icms_form_elements_Datetime($caption, $name, 15, $value);
				break;
			case "timezone":
				$element = new icms_form_elements_select_Timezone($caption, $name, $value);
				$element->setExtra("style='width: 280px;'");
				break;
			case "rank":
				$element = new icms_form_elements_Select($caption, $name, $value);
				$ranks = icms_getModuleHandler("userrank", "system")->getList(icms_buildCriteria(array("rank_special" => 1)));
				$element->addOption(0, "--------------");
				$element->addOptionArray($ranks);
				break;
			case "theme":
				$element = new icms_form_elements_Select($caption, $name, $value);
				$element->addOption("0", _MD_PROFILE_SITEDEFAULT);
				$handle = opendir(ICMS_THEME_PATH.'/');
				$dirlist = array();
				while (false !== ($file = readdir($handle))) {
					if (is_dir(ICMS_THEME_PATH.'/'.$file) && !preg_match("/^[.]{1,2}$/",$file) && strtolower($file) != 'cvs') {
						if (file_exists(ICMS_THEME_PATH.'/'.$file.'/theme.html') && in_array($file, $GLOBALS['icmsConfig']['theme_set_allowed'])) {
							$dirlist[$file]=$file;
						}
					}
				}
				closedir($handle);
				if (!empty($dirlist)) {
					asort($dirlist);
					$element->addOptionArray($dirlist);
				}
				break;
			case "image":
				$element = new icms_form_elements_File($caption, $name, $options['maxsize']*1024);
				if ($value != "") {
					$this->assignVar('field_description', "");
					$element->setDescription($this->getOutputValue($user, $profile));
				}
				break;
			case "openid":
				if ($icmsConfigAuth['auth_openid'] != 1) break;
				$element = new icms_form_elements_Text($caption, $name, 35, $this->getVar('field_maxlength'), $value);
				break;
			case "textbox":
			default:
				$element = new icms_form_elements_Text($caption, $name, 35, $this->getVar('field_maxlength'), $value);
				break;
		}
		if ($this->getVar('field_description') != '') $element->setDescription($this->getVar('field_description'));
		return $element;
	}

	/**
	 * Returns a value for output of this field
	 *
	 * @param icms_member_user_Object $user object to get the value of
	 * @param mod_profile_Profile $profile object to get the value of
	 * @global array $icmsConfigAuth
	 * @return mixed
	 **/
	public function getOutputValue(&$user, $profile) {
		global $icmsConfigAuth;

		$value = in_array($this->getVar('field_name'), $this->getUserVars()) ? $user->getVar($this->getVar('field_name')) : $profile->getVar($this->getVar('field_name'));

		switch ($this->getVar('field_type')) {
			case "textarea":
			case "dhtml":
		  		return icms_core_DataFilter::undoHtmlSpecialChars(str_replace('&amp;', '&', $value), 1);
				break;
			case "select":
			case "radio":
				$options = unserialize($this->getVar('field_options', 'n'));
				return isset($options[$value]) ? htmlspecialchars($options[$value]) : "";
				break;
			case "select_multi":
			case "checkbox":
				$options = unserialize($this->getVar('field_options', 'n'));
				$ret = array();
				if (count($options) > 0) {
					foreach (array_keys($options) as $key) {
						if (in_array($key, $value)) {
							$ret[$key] = htmlspecialchars($options[$key]);
						}
					}
				}
				return $ret;
				break;
			case "group":
				//change to retrieve groups and return name of group
				return $value;
				break;
			case "group_multi":
				//change to retrieve groups and return array of group names
				return "";
				break;
			case "longdate":
				//return YYYY/MM/DD format - not optimal as it is not using local date format, but how do we do that
				//when we cannot convert it to a UNIX timestamp?
				return str_replace("-", "/", $value);
			case "date":
				if ($value > 0) return formatTimestamp($value, 's');
				return "";
				break;
			case "datetime":
				if ($value > 0) return formatTimestamp($value, 'm');
				return "";
				break;
			case "autotext":
				$value = $user->getVar($this->getVar('field_name'), 'n'); //autotext can have HTML in it
				$value = str_replace("{X_UID}", $user->getVar("uid"), $value );
				$value = str_replace("{X_URL}", ICMS_URL, $value );
				$value = str_replace("{X_UNAME}", $user->getVar("uname"), $value );
				return $value;
				break;
			case "rank":
				$userrank = $user->rank();
				return '<img src="' . $userrank['image'] . '" alt="' . $userrank['title'] . '" />&nbsp;'.$userrank['title'];
				break;
			case "yesno":
				return $value ? _YES : _NO;
				break;
			case "timezone":
				$timezones = icms_form_elements_select_Timezone::getTimeZoneList();
				return $timezones[str_replace('.0', '', $value)];
				break;
			case "image":
				if ($value == "") return '';
				return "<img src='".ICMS_UPLOAD_URL."/".basename(dirname(dirname(__FILE__)))."/".$value."' alt='image' />";
				break;
			case "url":
				if ($value == "") return '';
				return icms_core_DataFilter::makeClickable(formatURL($value));
			case "location":
				if ($value == "") return '';
				return $value.'&nbsp;<a href="http://maps.google.com/?q='.$value.'" target="_blank" ><img src="'.ICMS_URL.'/modules/'.basename(dirname(dirname(__FILE__))).'/images/mapsgoogle.gif" alt="" /></a>';
			case "email":
				if ($value == "") return '';
				if ($user->getVar('user_viewemail') || (is_object(icms::$user) && (icms::$user->isAdmin() || icms::$user->getVar('uid') == $user->getVar('uid')))) return '<a href="mailto:'.$value.'">'.$value.'</a>';
				return '';
			case "openid":
				if ($value == "") return '';
				if ($icmsConfigAuth['auth_openid'] == 1 && ($user->getVar('user_viewoid') || (is_object(icms::$user) && (icms::$user->isAdmin() || icms::$user->getVar('uid') == $user->getVar('uid'))))) return $value;
				return '';
			case "textbox":
			case "theme":
			case "language":
			default:
				return $value;
				break;
		}
	}

	/**
	 * Returns a value ready to be saved in the database
	 *
	 * @param mixed $value Value to format
	 * @param mixed $oldvalue old value
	 *
	 * @return mixed
	 */
	public function getValueForSave($value, $oldvalue) {
		switch ($this->getVar('field_type')) {
			default:
			case "textbox":
			case "textarea":
			case "dhtml":
			case "yesno":
			case "timezone":
			case "theme":
			case "language":
			case "select":
			case "radio":
			case "select_multi":
			case "checkbox":
			case "group":
			case "group_multi":
			case "longdate":
				return $value;
			case "date":
				if ($value != "") return strtotime($value);
				return $value;
				break;
			case "datetime":
				if ($value != "") return strtotime($value['date']) + $value['time'];
				return $value;
				break;
			case "image":
				if (!isset($_FILES[$_POST['xoops_upload_file'][0]])) return $oldvalue;

				$options = unserialize($this->getVar('field_options', 'n'));
				$dirname = ICMS_UPLOAD_PATH.'/'.basename(dirname(dirname(__FILE__)));
				if (!is_dir($dirname)) mkdir($dirname);

				$uploader = new icms_file_MediaUploadHandler($dirname, array('image/gif', 'image/jpeg', 'image/pjpeg', 'image/x-png', 'image/png'), $options['maxsize']*1024, $options['maxwidth'], $options['maxheight']);
				if ($uploader->fetchMedia($_POST['xoops_upload_file'][0])) {
					$uploader->setPrefix('image');
					if ($uploader->upload()) {
						@unlink($dirname.'/'.$oldvalue);
						return $uploader->getSavedFileName();
					} else {
						echo $uploader->getErrors();
						return $oldvalue;
					}
				} else {
					echo $uploader->getErrors();
					return $oldvalue;
				}
				break;
		}
	}

	/**
	 * Get names of user variables
	 *
	 * @return array
	 */
	private function getUserVars() {
		$profile_handler = icms_getModuleHandler('profile', basename(dirname(dirname(__FILE__))), 'profile');
		return $profile_handler->getUserVars();
	}
	
	/**
	 * get show icon for table display
	 * 
	 * @return str html code for image
	 */
	public function getShow() {
		if ($this->getVar('field_show')) {
			$rtn = '<img src="' . ICMS_IMAGES_SET_URL . '/actions/button_ok.png" alt="1" />';
		} else {
			$rtn = '<img src="' . ICMS_IMAGES_SET_URL . '/actions/button_cancel.png" alt="0" />';
		}
		return $rtn;
	}

	/**
	 * get category title for table display
	 *
	 * @return str category title
	 */
	public function getCatid() {
		$icmsPersistableRegistry = icms_ipf_registry_Handler::getInstance();
		$category = $icmsPersistableRegistry->getSingleObject('category', $this->getVar('catid'), basename(dirname(dirname(__FILE__))));
		return $category->getVar('cat_title');
	}

	/**
	 * return the field name
	 *
	 * @return string field name
	 */
	public function getFieldName() {
		return $this->getVar('field_name');
	}

	/**
	 * build image html tag
	 *
	 * @return str image
	 */
	public function getImage() {
		$imgUrl = $this->getVar('url');
		if (empty($imgUrl)) return '<img src="'.$this->handler->_moduleUrl.'images/blank18.png" alt="" />';
		return '<img src="'.$this->handler->getImageUrl().$imgUrl.'" alt="" />';
	}

	/**
	 * generate delete button
	 *
	 * @staticvar icms_ipf_Controller $controller
	 * @return str linked icon to delete the object
	 */
	public function getDeleteButtonForDisplay() {
		static $controller = null;
		if ($this->getVar('system') == 1) return;
		if ($controller === null) $controller = new icms_ipf_Controller($this->handler);
		return $controller->getDeleteItemLink($this, false, true, false);
	}

	/**
	 * generate textbox control to edit weight on acp
	 *
	 * @return str textbox control
	 */
	public function getField_weightControl() {
		$control = new icms_form_elements_Text('', 'field_weight[]', 5, 4, $this->getVar('field_weight'));
		return $control->render();
	}
}
?>