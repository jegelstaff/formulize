<?php
/**
 * EditArea adapter for ImpressCMS
 *
 * @copyright	ImpressCMS http://www.impresscms.org/
 * @license		http://www.fsf.org/copyleft/gpl.html GNU public license
 * @author		MekDrop <mekdrop@gmail.com>
 * @since		1.2
 * @package		sourceeditor
 */
defined("ICMS_ROOT_PATH") or die("ICMS root path not defined");

/**
 * Pseudo class
 *
 * @author	    MekDrop
 * @copyright	copyright (c) 2009 ImpressCMS.org
 */
class IcmsSourceEditorEditArea extends icms_form_elements_Textarea {
	public $rootpath = "";
    private $_width = "100%";
    private $_height = "400px";

	/**
	 * Constructor
	 *
     * @param	array   $configs  Editor Options
     * @param	binary 	$checkCompatible  true - return false on failure
	 */
	public function __construct($configs, $checkCompatible = false) {
		$current_path = __FILE__;
		if (DIRECTORY_SEPARATOR != "/" ) $current_path = str_replace(strpos($current_path, "\\\\", 2) ? "\\\\" : DIRECTORY_SEPARATOR, "/", $current_path);
		$this->rootpath = substr(dirname($current_path), strlen(ICMS_ROOT_PATH));

		if (is_array($configs)) {
			$vars = array_keys(get_object_vars($this));
			foreach ($configs as $key => $val){
				if (in_array("_".$key, $vars)) {
					$this->{"_".$key} = $val;
				} elseif (in_array($key, array('name', 'value'))) {
					$method = "set" . ucfirst($key);
					$this->$method($val);
				} else {
					$this->config[$key] = $val;
				}
			}
		}

		if ($checkCompatible && !$this->isCompatible()) return false;

		parent::__construct("", $this->getName(), $this->getValue());
		parent::setExtra("style='width: " . $this->_width . "; height: " . $this->_height . ";'");
	}

	/**
	 * Check if compatible
	 *
     * @return
	 */
	private function isCompatible() {
		return is_readable(ICMS_ROOT_PATH . $this->rootpath . "/editarea.php");
	}

	public function render() {
		global $xoTheme;
		$ret = parent::render();
		$xoTheme->addScript(ICMS_URL . $this->rootpath . '/editor/edit_area_full_with_plugins.js', array('type' => 'text/javascript'),'');
		// @todo this still has to be added like this - until someone figures it out
		$ret .= '
<script language="javascript" type="text/javascript" src="' . ICMS_URL . $this->rootpath . '/editor/edit_area_full_with_plugins.js"></script>
<script language="javascript" type="text/javascript">
editAreaLoader.init({
	id: "' . $this->getName() . '_tarea",
	syntax: "' . (!isset($this->config['syntax']) ? 'php' : $this->config['syntax']) . '",
	language: "' . (!isset($this->config['language']) ? 'en' : $this->config['language']) . '",
	start_highlight: ' . (!isset($this->config['start_highlight']) ? 'true' : ($this->config['start_highlight'] ? 'true' : 'false')) . ',
	allow_resize: ' . (!isset($this->config['allow_resize']) ? 'false' : ($this->config['allow_resize'] ? 'true' : 'false')) . ',
	allow_toggle: ' . (!isset($this->config['allow_toggle']) ? 'false' : ($this->config['allow_toggle'] ? 'true' : 'false')) . ',
	fullscreen: ' . (!isset($this->config['fullscreen']) ? 'false' : ($this->config['fullscreen'] ? 'true' : 'false')) . ',
	is_editable: ' . (!isset($this->config['is_editable']) ? 'true' : ($this->config['is_editable'] ? 'true' : 'false')) . ',
	autocompletion: ' . (!isset($this->config['autocompletion']) ? 'true' : ($this->config['autocompletion'] ? 'true' : 'false')) . '
});
</script>';

		return $ret;
	}
}