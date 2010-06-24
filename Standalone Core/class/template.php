<?php
/**
* The templates class that extends Smarty
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	LICENSE.txt
* @package	core
* @subpackage Templates
* @since	XOOPS
* @author	http://www.xoops.org The XOOPS Project
* @author	modified by UnderDog <underdog@impresscms.org>
* @version	$Id: template.php 9329 2009-09-05 10:32:12Z pesianstranger $
*/

if (!defined('SMARTY_DIR')) {
	exit();
}
/**
 * Base class: Smarty template engine
 */
require_once SMARTY_DIR.'Smarty.class.php';

/**
 * Template engine
 *
 * @package		kernel
 * @subpackage	core
 *
 * @author		Kazumi Ono 	<onokazu@xoops.org>
 * @copyright	(c) 2000-2003 The Xoops Project - www.xoops.org
 */
class XoopsTpl extends Smarty {

	public $left_delimiter = '<{';
	public $right_delimiter = '}>';

	public $template_dir = XOOPS_THEME_PATH;
	public $cache_dir = XOOPS_CACHE_PATH;
	public $compile_dir = XOOPS_COMPILE_PATH;

	function XoopsTpl() {
		global $icmsConfig;

		$this->compile_id = $icmsConfig['template_set'] . '-' . $icmsConfig['theme_set'];
		$this->_compile_id = $this->compile_id;
		$this->compile_check = ( $icmsConfig['theme_fromfile'] == 1 );
		$this->plugins_dir = array(
			SMARTY_DIR . 'icms_plugins',
			SMARTY_DIR . 'plugins',
		);
		
		// For backwars compatibility...
		if(file_exists(ICMS_ROOT_PATH."/class/smarty/plugins")){
			$this->plugins_dir[] = ICMS_ROOT_PATH.'/class/smarty/plugins';	
		}
		
		if(file_exists(ICMS_ROOT_PATH."/class/smarty/xoops_plugins")){
			$this->plugins_dir[] = ICMS_ROOT_PATH.'/class/smarty/xoops_plugins';	
		}
		
		if ( $icmsConfig['debug_mode'] ) {
			$this->debugging_ctrl = 'URL';
		    if ( $icmsConfig['debug_mode'] == 3 ) {
		    	$this->debugging = true;
		    }
		}
		$this->Smarty();
	if ( defined('_ADM_USE_RTL') && _ADM_USE_RTL ){
		$this->assign( 'icms_rtl', true );
    }

		$this->assign( array(
			'icms_url' => ICMS_URL,
			'icms_rootpath' => ICMS_ROOT_PATH,
			'modules_url' => ICMS_MODULES_URL,
			'modules_rootpath' => ICMS_MODULES_PATH,
			'icms_langcode' => _LANGCODE,
			'icms_langname' => $GLOBALS["xoopsConfig"]["language"],
			'icms_charset' => _CHARSET,
			'icms_version' => XOOPS_VERSION,
			'icms_upload_url' => XOOPS_UPLOAD_URL,
			'xoops_url' => ICMS_URL,
			'xoops_rootpath' => ICMS_ROOT_PATH,
			'xoops_langcode' => _LANGCODE,
			'xoops_charset' => _CHARSET,
			'xoops_version' => XOOPS_VERSION,
			'xoops_upload_url' => XOOPS_UPLOAD_URL
		) );
	}

	/**
	 * Renders output from template data
	 *
	 * @param   string  $data		The template to render
	 * @param	bool	$display	If rendered text should be output or returned
	 * @return  string  Rendered output if $display was false
	 **/
    function fetchFromData( $tplSource, $display = false, $vars = null ) {
        if ( !function_exists('smarty_function_eval') ) {
            require_once SMARTY_DIR . '/plugins/function.eval.php';
        }
    	if ( isset( $vars ) ) {
    		$oldVars = $this->_tpl_vars;
    		$this->assign( $vars );
	        $out = smarty_function_eval( array('var' => $tplSource), $this );
        	$this->_tpl_vars = $oldVars;
        	return $out;
    	}
        return smarty_function_eval( array('var' => $tplSource), $this );
    }


	/**
	 * Touch the resource (file) which means get it to recompile the resource
	 *
	 * @param   string  $resourcename		Resourcename to touch
	 * @return  string  $result         Was the resource recompiled
	 **/
    function touch( $resourceName ) {
    	$isForced = $this->force_compile;
    	$this->force_compile = true;
    	$this->clear_cache( $resourceName );
    	$result = $this->_compile_resource( $resourceName, $this->_get_compile_path( $resourceName ) );
    	$this->force_compile = $isForced;
    	return $result;
	}

  /**
   * @deprecated DO NOT USE THESE METHODS, ACCESS THE CORRESPONDING PROPERTIES INSTEAD
   */
	function xoops_setTemplateDir($dirname) {		$this->template_dir = $dirname;			}
	function xoops_getTemplateDir() {				return $this->template_dir;				}
	function xoops_setDebugging($flag=false) {		$this->debugging = is_bool($flag) ? $flag : false;	}
	function xoops_setCaching( $num = 0 ) {			$this->caching = (int)$num;				}
	function xoops_setCompileDir($dirname) {		$this->compile_dir = $dirname;			}
	function xoops_setCacheDir($dirname) {			$this->cache_dir = $dirname;			}
	function xoops_canUpdateFromFile() {			return $this->compile_check;			}
	function xoops_fetchFromData( $data ) {			return $this->fetchFromData( $data );	}
	function xoops_setCacheTime( $num = 0 ) {
		if ( ( $num = (int)$num ) <= 0) {
			$this->caching = 0;
		} else {
			$this->cache_lifetime = $num;
		}
	}
}





/**
 * function to update compiled template file in templates_c folder
 *
 * @param   string  $tpl_id
 * @param   boolean $clear_old
 * @return  boolean
 **/
function xoops_template_touch($tpl_id, $clear_old = true) {
	$tplfile_handler =& xoops_gethandler('tplfile');
	$tplfile =& $tplfile_handler->get($tpl_id);

	if ( is_object($tplfile) ) {
		$file = $tplfile->getVar( 'tpl_file', 'n' );
		$tpl = new XoopsTpl();
		return $tpl->touch( "db:$file" );
	}
	return false;
}

/**
 * Clear the module cache
 *
 * @param   int $mid    Module ID
 * @return
 **/
function xoops_template_clear_module_cache($mid)
{	
	$icms_block_handler = xoops_gethandler('block');
	$block_arr = $icms_block_handler->getByModule($mid);
	$count = count($block_arr);
	if ($count > 0) {
		$xoopsTpl = new XoopsTpl();
		$xoopsTpl->xoops_setCaching(2);
		for ($i = 0; $i < $count; $i++) {
			if ($block_arr[$i]->getVar('template') != '') {
				$xoopsTpl->clear_cache('db:'.$block_arr[$i]->getVar('template'), 'blk_'.$block_arr[$i]->getVar('bid'));
			}
		}
	}
}
?>