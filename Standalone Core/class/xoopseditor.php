<?php
/**
 * Editor framework for XOOPS
 *
 * @copyright	The XOOPS project http://www.xoops.org/
 * @license		http://www.fsf.org/copyleft/gpl.html GNU public license
 * @author		Taiwen Jiang (phppp or D.J.) <php_pp@hotmail.com>
 * @since		  1.00
 * @version		$Id: xoopseditor.php 9046 2009-07-22 14:14:40Z pesianstranger $
 * @package		xoopseditor
 */
class XoopsEditorHandler
{
	var $root_path = "";
	var $nohtml = false;
	var $allowed_editors = array();

  /**
   * Constructor
   *
   * @param	string	type
   */
  function XoopsEditorHandler($type = '')
  {
    include_once dirname(__FILE__)."/xoopseditor.inc.php";
    $this->root_path = xoopseditor_get_rootpath($type);
  }

	/**
	 * Access the only instance of this class
   *
   * @param	    string	type
   * @return	object
   * @static
   * @staticvar   object
	 */
	static function &getInstance($type = '')
	{
		static $instances = array();
		if (!isset($instances[$type])) {
			$instances[$type] = new XoopsEditorHandler($type);
		}
		return $instances[$type];
	}

	/**
   * @param	string	$name		Editor name which is actually the folder name
   * @param	array 	$options	editor options: $key => $val
   * @param	string	$OnFailure  a pre-validated editor that will be used if the required editor is failed to create
   * @param	bool	$noHtml		dohtml disabled
	 */
  function &get($name = "", $options = null, $noHtml = false, $OnFailure = "")
  {
    if($editor = $this->_loadEditor($name, $options)) {
      return $editor;
    }
    $list = array_keys($this->getList($noHtml));
    /*
    if(!empty($name) && in_array($name, $list)){
    $editor = $this->_loadEditor($name, $options);
    }
    */
    //if(!is_object($editor)){
    if(empty($OnFailure) || !in_array($OnFailure, $list)){
      $OnFailure = $list[0];
    }
    $editor = $this->_loadEditor($OnFailure, $options);
    //}
    return $editor;
  }


  /**
   * Gets list of available editors
   *
   * @param   bool    $noHtml   is this an editor with no html options?
   * @return  array   $_list    list of available editors that are allowed (through admin config)
   */
  function &getList($noHtml = false)
  {
    if(@ include_once ICMS_ROOT_PATH."/Frameworks/art/functions.ini.php") {
      load_functions("cache");
      $list = mod_loadCacheFile("list", "xoopseditor");
    }

		if(empty($list)) {

			$list = array();
			$order = array();
			require_once ICMS_ROOT_PATH."/class/xoopslists.php";
			$_list = XoopsLists::getDirListAsArray($this->root_path.'/');

			foreach($_list as $item){
				if(@include $this->root_path.'/'.$item.'/editor_registry.php'){
					if(empty($config['order'])) continue;
					$order[] = $config['order'];
					$list[$item] = array("title" => $config["title"], "nohtml" => @$config["nohtml"]);
				}
			}

			array_multisort($order, $list);
			if(function_exists("mod_createCacheFile")) {
				mod_createCacheFile($list, "list", "xoopseditor");
			}
		}

		$editors = array_keys($list);
		if(!empty($this->allowed_editors)) {
			$editors = array_intersect($editors, $this->allowed_editors);
		}

		$_list = array();
		foreach($editors as $name){
			if(!empty($noHtml) && empty($list[$name]['nohtml'])) continue;
			$_list[$name] = $list[$name]['title'];
		}
		return $_list;
  }


  /**
   * Render the editor
   * @param   string    &$editor    Reference to the editor object
   * @return  string    The rendered Editor string
   */
  function render(&$editor)
  {
    return $editor->render();
  }


  /**
   * Sets the config of the editor
   *
   * @param   string    &$editor    Reference to the editor object
   * @param   string    $options    Options in the configuration to set
   */
  function setConfig(&$editor, $options)
  {
    if(method_exists($editor, 'setConfig')) {
        $editor->setConfig($options);
      }else{
      foreach($options as $key => $val){
        $editor->$key = $val;
      }
    }
  }


  /**
   * Loads the editor
   *
   * @param   string    $name       Name of the editor to load
   * @param   string    $options    Options in the editor to load (configuration)
   * @return  object                The loaded Editor object
   *
   */
  function &_loadEditor($name, $options = null)
  {
    $editor = null;

    if(empty($name)) {
      return $editor;
    }
    $editor_path = $this->root_path."/".$name;

    if(!include $editor_path."/editor_registry.php") {
      return $editor;
    }
    if(empty($config['order'])) {
      return null;
    }
    require_once $config['file'];
    $editor =& new $config['class']($options);
    return $editor;
  }
}
?>