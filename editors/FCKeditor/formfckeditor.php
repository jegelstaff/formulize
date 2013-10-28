<?php
/**
 * FCKeditor adapter for XOOPS
 *
 * @copyright	The XOOPS project http://www.xoops.org/
 * @license		http://www.fsf.org/copyleft/gpl.html GNU public license
 * @author		Taiwen Jiang (phppp or D.J.) <php_pp@hotmail.com>
 * @since		4.00
 * @version		$Id: formfckeditor.php 5674 2008-10-14 20:40:41Z pesian_stranger $
 * @package		xoopseditor
 */
if (!defined('ICMS_ROOT_PATH')) die("ImpressCMS root path not defined");

class XoopsFormFckeditor extends icms_form_elements_Textarea
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
	function XoopsFormFckeditor($configs, $checkCompatible = false)
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

		if ($checkCompatible && !$this->isCompatible()) {
			return false;
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
		//global $myts;
		$ret = '';
		if (@include_once(ICMS_ROOT_PATH . $this->rootpath. "/fckeditor.php") )	{
			$oFCKeditor = new FCKeditor($this->getName());
			$oFCKeditor->BasePath	= ICMS_URL.$this->rootpath. "/";
			$oFCKeditor->Width		= $this->_width;
			$oFCKeditor->Height		= $this->_height;
			//$conv_pattern = array("/&gt;/i", "/&lt;/i", "/&quot;/i", "/&#039;/i"/* , "/(\015\012)|(\015)|(\012)|(\r\n)/" */);
			//$conv_replace = array(">", "<", "\"", "'"/* , "<br />" */);
			//$this->Value			= preg_replace($conv_pattern, $conv_replace, $this->_value);
			$oFCKeditor->Value		= htmlspecialchars_decode($this->_value);

			//$oFCKeditor->Config['BaseHref'] = ICMS_URL.$this->rootpath. "/";
			if (is_readable(ICMS_ROOT_PATH . $this->rootpath. '/editor/lang/'.$this->getLanguage().'.js')) {
				$oFCKeditor->Config['DefaultLanguage'] = $this->getLanguage();
			}

			if (defined("_XOOPS_EDITOR_FCKEDITOR_FONTLIST")) {
				$oFCKeditor->Config['FontNames'] = _XOOPS_EDITOR_FCKEDITOR_FONTLIST;
			}
			if (is_object($GLOBALS['icmsModule'])) {
			if (!file_exists($config_file = ICMS_CACHE_PATH . "/fckconfig.".$GLOBALS["icmsModule"]->getVar("dirname", "n").".js")) {
				if ($fp = fopen( $config_file , "wt" )) {
					$fp_content = "/* FCKconfig module configuration */\n";
					if (is_readable($config_mod = ICMS_ROOT_PATH."/modules/".$GLOBALS["icmsModule"]->getVar("dirname")."/fckeditor.config.js")) {
						$fp_content .= "/* Loaded from module local config file */\n".implode("", file($config_mod))."\n\n";
					}
					if (is_readable(ICMS_ROOT_PATH."/modules/".$GLOBALS["icmsModule"]->getVar("dirname")."/fckeditor.connector.php")) {
						$fp_content .= "var browser_path = FCKConfig.BasePath + 'filemanager/browser/default/browser.html?Connector=".ICMS_URL."/modules/".$GLOBALS["icmsModule"]->getVar("dirname", "n")."/fckeditor.connector.php';\n";
						$fp_content .= "FCKConfig.LinkBrowserURL = browser_path ;\n";
						$fp_content .= "FCKConfig.ImageBrowserURL = browser_path + '&Type=Image';\n";
						$fp_content .= "FCKConfig.FlashBrowserURL = browser_path + '&Type=Flash';\n\n";
					}
					if (is_readable(ICMS_ROOT_PATH."/modules/".$GLOBALS["icmsModule"]->getVar("dirname")."/fckeditor.upload.php")) {
						$fp_content .= "var uploader_path = '".ICMS_URL."/modules/".$GLOBALS["icmsModule"]->getVar("dirname", "n")."/fckeditor.upload.php';\n";
						$fp_content .= "FCKConfig.LinkUploadURL = uploader_path;\n";
						$fp_content .= "FCKConfig.ImageUploadURL = uploader_path + '?Type=Image';\n";
						$fp_content .= "FCKConfig.FlashUploadURL = uploader_path + '?Type=Flash';\n\n";
					}
					if (empty($this->_upload)) {
						$fp_content .= "FCKConfig.LinkUpload = false;\n";
						$fp_content .= "FCKConfig.ImageUpload = false;\n";
						$fp_content .= "FCKConfig.FlashUpload = false;\n\n";
					}

					fwrite( $fp, $fp_content );
					fclose( $fp );
				} else {
					icms_core_Message::error( "Cannot create fckeditor config file" );
				}
			}

			if (is_readable($config_file = ICMS_CACHE_PATH . "/fckconfig.".$GLOBALS["icmsModule"]->getVar("dirname").".js")) {
				$oFCKeditor->Config['CustomConfigurationsPath'] = ICMS_URL . "/cache/fckconfig.".$GLOBALS["icmsModule"]->getVar("dirname", "n").".js";
			}
			}

			foreach ($this->config as $key => $val) {
				$oFCKeditor->Config[$key] = $val;
			}

			//$oFCKeditor->SetVar('ToolbarSet', "Basic");
			$ret = $oFCKeditor->CreateHtml();
		}
		return $ret;
	}

	/**
	 * Check if compatible
	 *
     * @return
	 */
	function isCompatible()
	{
		if (!is_readable(ICMS_ROOT_PATH . $this->rootpath. "/fckeditor.php")) return false;
		include_once ICMS_ROOT_PATH . $this->rootpath. "/fckeditor.php" ;
		return FCKeditor::IsCompatible();
	}
}
?>