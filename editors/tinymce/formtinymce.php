<?php
/**
 * TinyMCE adapter for XOOPS
 *
 * @copyright	The XOOPS project http://www.xoops.org/
 * @license		http://www.fsf.org/copyleft/gpl.html GNU public license
 * @author		Taiwen Jiang (phppp or D.J.) <php_pp@hotmail.com>
 * @since		4.00
 * @version		$Id: formtinymce.php 21298 2011-03-26 23:48:15Z skenow $
 * @package		xoopseditor
 */
if (!defined('ICMS_ROOT_PATH')) die("ImpressCMS root path not defined");

class XoopsFormTinymce extends icms_form_elements_Textarea {
	var $rootpath = "";
	var $_language = _LANGCODE;
	var $_width = "100%";
	var $_height = "500px";

	var $tinymce;
	var $config = array ( );

	/**
	 * Constructor
	 *
	 * @param	array   $configs  Editor Options
	 * @param	bool 	  $checkCompatible  true - return false on failure
	 */
	function XoopsFormTinymce($configs, $checkCompatible = false) {
		$current_path = __FILE__;
		if (DIRECTORY_SEPARATOR != "/")
			$current_path = str_replace ( strpos ( $current_path, "\\\\", 2 ) ? "\\\\" : DIRECTORY_SEPARATOR, "/", $current_path );
		$this->rootpath = substr(strstr(dirname($current_path), ICMS_ROOT_PATH), strlen(ICMS_ROOT_PATH));

		if (is_array ( $configs )) {
			$vars = array_keys ( get_object_vars ( $this ) );
			foreach ( $configs as $key => $val) {
				if (in_array ( "_" . $key, $vars )) {
					$this->{"_" . $key} = $val;
				} else {
					$this->config [$key] = $val;
				}
			}
		}

		if ($checkCompatible && ! $this->isCompatible ()) {
			return false;
		}

		parent::__construct( @$this->_caption, @$this->_name, @$this->_value );
		parent::setExtra ( "style='width: " . $this->_width . "; height: " . $this->_height . ";'" );

		$this->initTinymce ();
	}

  /**
  * Initializes tinyMCE
  **/
	function initTinymce() {
		$this->config ["elements"] = $this->getName() . '_tarea';
		$this->config ["language"] = $this->getLanguage ();
		$this->config ["rootpath"] = $this->rootpath;
		$this->config ["area_width"] = $this->_width;
		$this->config ["area_height"] = $this->_height;
		$this->config ["fonts"] = $this->getFonts ();
		//$this->config["file_browser_callback"] = "ajaxfilemanager";
		/*$this->config["callback"] = 'function ajaxfilemanager(field_name, url, type, win) {
			var ajaxfilemanagerurl = "../../../../../editors/tinymce/jscripts/plugins/ajaxfilemanager/ajaxfilemanager.php";
			switch (type) {
				case "image":
					break;
				case "media":
					break;
				case "flash":
					break;
				case "file":
					break;
				default:
					return false;
			}
            tinyMCE.activeEditor.windowManager.open({
                url: ajaxfilemanagerurl,
                width: 782,
                height: 440,
                inline : "yes",
                close_previous : "no"
            },{
                window : win,
                input : field_name
            });

/*            return false;
			var fileBrowserWindow = new Array();
			fileBrowserWindow["file"] = ajaxfilemanagerurl;
			fileBrowserWindow["title"] = "Ajax File Manager";
			fileBrowserWindow["width"] = "782";
			fileBrowserWindow["height"] = "440";
			fileBrowserWindow["close_previous"] = "no";
			tinyMCE.openWindow(fileBrowserWindow, {
			  window : win,
			  input : field_name,
			  resizable : "yes",
			  inline : "yes",
			  editor_id : tinyMCE.getWindowArg("editor_id")
			});

			return false;
		}';*/

		require_once dirname ( __FILE__ ) . "/tinymce.php";
		$this->tinymce = TinyMCE::instance( $this->config );
	}

	/**
	 * get language
	 *
	 * @return	string
	 */
	function getLanguage() {
		$language = str_replace ( '-', '_', strtolower ( $this->_language ) );
		if (strtolower ( _CHARSET ) != "utf-8") {
			$language .= "_ansi";
		}
		return $language;
	}

  /**
  * Gets the fonts for tinymce
  **/
	function getFonts() {
		if (empty ( $this->config ["fonts"] ) && defined ( "_XOOPS_EDITOR_TINYMCE_FONTS" )) {
			$this->config ["fonts"] = constant ( "_XOOPS_EDITOR_TINYMCE_FONTS" );
		}

		return @$this->config ["fonts"];
	}

	/**
	 * prepare HTML for output
	 * @return	string    $ret    HTML
	 */
	function render() {
		$ret = $this->tinymce->render ();
		$ret .= parent::render ();

		$ret .= '<a href="#" id="switchtinymce" title="'._TOGGLETINY.'" onclick="showMCE(\''.$this->_name.'_tarea\'); return false;" style="float:'._GLOBAL_RIGHT.'; display:box; background:#F0F0EE; padding:3px; margin-right:2px; border: 1px solid #ccc; border-top: none;">'._TOGGLETINY.'</a>';
		$ret .= '<br clear="'._GLOBAL_RIGHT.'" />';

		return $ret;
	}

	/**
	 * Check if compatible
	 *
	 * @return  bool
	 */
	function isCompatible() {
		return is_readable( ICMS_ROOT_PATH . $this->rootpath . "/tinymce.php" );
	}
}
?>