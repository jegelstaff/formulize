<?php
/**
 * Manage configuration items
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @category	ICMS
 * @package		Config
 * @subpackage	Item
 * @author		Kazumi Ono (aka onokazo)
 * @version		SVN: $Id: Object.php 21211 2011-03-24 00:27:18Z m0nty_ $
 */

if (!defined('ICMS_ROOT_PATH')) die("ImpressCMS root path not defined");

/**
 * @category	ICMS
 * @package		Config
 * @subpackage	Item
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 */
class icms_config_Item_Object extends icms_core_Object {
	/**
	 * Config options
	 *
	 * @var	array
	 * @access	private
	 */
	public $_confOptions = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->initVar('conf_id', XOBJ_DTYPE_INT, null, false);
		$this->initVar('conf_modid', XOBJ_DTYPE_INT, null, false);
		$this->initVar('conf_catid', XOBJ_DTYPE_INT, null, false);
		$this->initVar('conf_name', XOBJ_DTYPE_OTHER);
		$this->initVar('conf_title', XOBJ_DTYPE_TXTBOX);
		$this->initVar('conf_value', XOBJ_DTYPE_TXTAREA);
		$this->initVar('conf_desc', XOBJ_DTYPE_OTHER);
		$this->initVar('conf_formtype', XOBJ_DTYPE_OTHER);
		$this->initVar('conf_valuetype', XOBJ_DTYPE_OTHER);
		$this->initVar('conf_order', XOBJ_DTYPE_INT);
	}

	/**
	 * Get a config value in a format ready for output
	 *
	 * @return	string
	 */
	public function getConfValueForOutput() {
		switch($this->getVar('conf_valuetype')) {
			case 'int':
				return (int) ($this->getVar('conf_value', 'N'));
				break;

			case 'array':
				$value = @ unserialize($this->getVar('conf_value', 'N'));
				return $value ? $value : array();

			case 'float':
				$value = $this->getVar('conf_value', 'N');
				return (float) $value;
				break;

			case 'textsarea':
				return icms_core_DataFilter::checkVar($this->getVar('conf_value'), 'text', 'output');
				break;

			case 'textarea':
				return icms_core_DataFilter::checkVar($this->getVar('conf_value'), 'html', 'output');
			default:
				return $this->getVar('conf_value', 'N');
				break;
		}
	}

	/**
	 * Set a config value
	 *
	 * @param	mixed   &$value Value
	 * @param	bool    $force_slash
	 */
	public function setConfValueForInput($value, $force_slash = false) {
		if ($this->getVar('conf_formtype') == 'textarea') {
			$value = icms_core_DataFilter::checkVar($value, 'html', 'input');
		} elseif ($this->getVar('conf_formtype') == 'textsarea') {
			$value = icms_core_DataFilter::checkVar($value, 'text', 'input');
		} elseif ($this->getVar('conf_formtype') == 'password') {
			$value = filter_var($value, FILTER_SANITIZE_URL);
		} else {
			$value = StopXSS($value);
		}
		switch($this->getVar('conf_valuetype')) {
			case 'array':
				if (!is_array($value)) {
					$value = explode('|', trim($value));
				}
				$this->setVar('conf_value', serialize($value), $force_slash);
				break;

			case 'text':
				$this->setVar('conf_value', trim($value), $force_slash);
				break;

			default:
				$this->setVar('conf_value', $value, $force_slash);
				break;
		}
	}

	/**
	 * Assign one or more {@link icms_config_Item_ObjectOption}s
	 *
	 * @param	mixed   $option either a {@link icms_config_Item_ObjectOption} object or an array of them
	 */
	public function setConfOptions($option) {
		if (is_array($option)) {
			$count = count($option);
			for ($i = 0; $i < $count; $i++) {
				$this->setConfOptions($option[$i]);
			}
		} else {
			if (is_object($option)) {
				$this->_confOptions[] =& $option;
			}
		}
	}

	/**
	 * Get the {@link icms_config_Item_ObjectOption}s of this Config
	 *
	 * @return	array   array of {@link icms_config_Item_ObjectOption}
	 */
	public function &getConfOptions() {
		return $this->_confOptions;
	}
}

