<?php
/**
 * CKeditor adapter for XOOPS
 *
 */
if (!defined('ICMS_ROOT_PATH')) die("ImpressCMS root path not defined");

class XoopsFormckeditor extends icms_form_elements_Textarea
{
	var $rootpath = "";
	var $_language = _LANGCODE;
	var $_upload = true;
	var $_width = "100%";
	var $_height = "500px";

    var $config = array();

	/**
	 * Constructor
	 *
     * @param	array   $configs  Editor Options
     * @param	binary 	$checkCompatible  true - return false on failure
	 */
	function XoopsFormckeditor($configs, $checkCompatible = false)
	{
		$current_path = __FILE__;
		if (DIRECTORY_SEPARATOR != "/" ) $current_path = str_replace( strpos( $current_path, "\\\\", 2 ) ? "\\\\" : DIRECTORY_SEPARATOR, "/", $current_path);
		$this->rootpath = substr(dirname($current_path), strlen(ICMS_ROOT_PATH));

		if (is_array($configs)) {
			$vars = array_keys(get_object_vars($this));
			foreach ($configs as $key => $val) {
				if (in_array("_".$key, $vars)) {
					$this->{"_".$key} = $val;
				} else {
					$this->config[$key] = $val;
				}
			}
		}

		parent::__construct("", @$this->_name, @$this->_value);
		parent::setExtra("style='width: ".$this->_width."; height: ".$this->_height.";'");
	}

	/**
	 * get language
	 *
     * @return	string
	 */
	function getLanguage()
	{
		if (defined("_XOOPS_EDITOR_FCKEDITOR_LANGUAGE")) {
			$language = strtolower(constant("_XOOPS_EDITOR_FCKEDITOR_LANGUAGE"));
		} else {
			$language = str_replace('-', '_', strtolower($this->_language));
		if (strtolower ( _CHARSET ) != "utf-8") {
			$language .= "_ansi";
			}
		}

		return $language;
	}

	/**
	 * prepare HTML for output
	 *
     * @return	sting HTML
	 */
	function render()
	{
        static $editorInstantiated = false;
        global $xoTheme;
        if($xoTheme AND !$editorInstantiated) {
            $xoTheme->addScript("/editors/CKEditor/ckeditor.js");
            $editorInstantiated = true;
        }
        return parent::render();
        
	}

}