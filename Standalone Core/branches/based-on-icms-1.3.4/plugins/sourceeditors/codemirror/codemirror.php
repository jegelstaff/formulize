<?php
/**
 * CodeMirror adapter for ImpressCMS
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
class IcmsSourceEditorCodeMirror extends icms_form_elements_Textarea {
	public $rootpath = "";
    private $_width = "100%";
    private $_height = "400px";

	/**
	 * Constructor
	 *
     * @param	array   $configs  Editor Options
     * @param	binary 	$checkCompatible  true - return false on failure
	 */
	function __construct($configs, $checkCompatible = false) {
		$current_path = __FILE__;
		if (DIRECTORY_SEPARATOR != "/" ) $current_path = str_replace(strpos($current_path, "\\\\", 2) ? "\\\\" : DIRECTORY_SEPARATOR, "/", $current_path);
		$this->rootpath = substr(dirname($current_path), strlen(ICMS_ROOT_PATH));

		if(is_array($configs)) {
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
		parent::setExtra("style='width:" . $this->_width . ";height:" . $this->_height . ";'");
	}

	/**
	 * Check if compatible
	 *
     * @return
	 */
	function isCompatible() {
		return is_readable(ICMS_ROOT_PATH . $this->rootpath. "/codemirror.php");
	}

	function render() {
		$ret = parent::render();

		// take xml for html rendering
		if ($this->config['syntax'] == 'html') $this->config['syntax'] = 'xml';

		$css = array();
		$js = array();
		$this->config['syntax'] = (!isset($this->config['syntax']) ? 'php' : $this->config['syntax']);
		switch ($this->config['syntax']) {
			case 'php':
				$js[] = '"../contrib/' . $this->config['syntax'] . '/js/tokenizephp.js"';
			case 'lua':
			case 'python':
				$css[] = '"' . ICMS_URL . $this->rootpath . '/editor/contrib/' . $this->config['syntax'] . '/css/' . $this->config['syntax'] . 'colors.css"';
				$js[] = '"../contrib/' . $this->config['syntax'] . '/js/parse' . $this->config['syntax'] . '.js"';
				break;
			case 'xml':
			case 'css':
			case 'javascript':
			case 'js':
			case 'sparql':
				if ($this->config['syntax'] == 'javascript') $this->config['syntax'] = 'js';
				$js[] = '"parse' . $this->config['syntax'] . '.js"';
				$css[] = '"' . ICMS_URL . $this->rootpath . '/editor/css/' . $this->config['syntax'] . 'colors.css"';
				break;
			case 'mixed':
				$js[] = '"parsexml.js"';
				$js[] = '"parsecss.js"';
				$js[] = '"tokenizejavascript.js"';
				$js[] = '"parsejavascript.js"';
				$js[] = '"parsehtmlmixed.js"';
				$css[] = '"' . ICMS_URL . $this->rootpath . '/editor/css/csscolors.css"';
				$css[] = '"' . ICMS_URL . $this->rootpath . '/editor/css/jscolors.css"';
				$css[] = '"' . ICMS_URL . $this->rootpath . '/editor/css/xmlcolors.css"';
				break;
		}
		$css[] = '"' . ICMS_URL . $this->rootpath . '/editor/css/docs.css"';

		if (isset($this->config["is_editable"])) {
			if ($this->config["is_editable"]) {
				$readonly = 'false';
			} else {
				$readonly = 'true';
			}
		} else {
			$readonly = 'false';
		}
		$ret .= '
		<script src="' . ICMS_URL . $this->rootpath . '/editor/js/codemirror.js" type="text/javascript"></script>
		<script type="text/javascript">
		  var editor = CodeMirror.fromTextArea(\'' . $this->getName() . '_tarea\', {
		  	width: "' . $this->_width . '",
    		height: "' . $this->_height . '",
    		parserfile: [' . implode(',',$js) . '],
    		stylesheet: [' . implode(',',$css) . '],
    		path: "' . ICMS_URL . $this->rootpath . '/editor/js/",
			lineNumbers: true,
			continuousScanning: 500,
			textWrapping: false,
			readOnly: ' . $readonly . '
  		});
		</script>
		<link rel="stylesheet" type="text/css" media="all" href="' . ICMS_URL . $this->rootpath . '/css/editor.css" />';

		return $ret;
	}
}