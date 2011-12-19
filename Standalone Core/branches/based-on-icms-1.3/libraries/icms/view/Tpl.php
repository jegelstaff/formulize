<?php
/**
 * The templates class that extends Smarty
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @category	ICMS
 * @package		View
 * @subpackage	Templates
 * @author		modified by UnderDog <underdog@impresscms.org>
 * @version		SVN: $Id: Tpl.php 22529 2011-09-02 19:55:40Z phoenyx $
 */

if (!defined('SMARTY_DIR')) {
	exit();
}
/**
 * Base class: Smarty template engine
 */
require_once SMARTY_DIR . 'Smarty.class.php';

/**
 * Template engine
 *
 * @category	ICMS
 * @package		View
 * @subpackage	Templates
 * @author		Kazumi Ono 	<onokazu@xoops.org>
 */
class icms_view_Tpl extends Smarty {

	public $left_delimiter = '<{';
	public $right_delimiter = '}>';

	public $template_dir = ICMS_THEME_PATH;
	public $cache_dir = ICMS_CACHE_PATH;
	public $compile_dir = ICMS_COMPILE_PATH;

	public function __construct() {
		global $icmsConfig;

		$this->compile_id = $icmsConfig['template_set'] . '-' . $icmsConfig['theme_set'];
		$this->_compile_id = $this->compile_id;
		$this->compile_check = ( $icmsConfig['theme_fromfile'] == 1 );
		$this->plugins_dir = array(
			SMARTY_DIR . 'icms_plugins',
			SMARTY_DIR . 'plugins',
		);

		// For backwars compatibility...
		if (file_exists(ICMS_ROOT_PATH . '/class/smarty/plugins')) {
			$this->plugins_dir[] = ICMS_ROOT_PATH . '/class/smarty/plugins';
		}

		if (file_exists(ICMS_ROOT_PATH . '/class/smarty/xoops_plugins')) {
			$this->plugins_dir[] = ICMS_ROOT_PATH . '/class/smarty/xoops_plugins';
		}

		if ($icmsConfig['debug_mode']) {
			$this->debugging_ctrl = 'URL';
			$groups = (is_object(icms::$user)) ? icms::$user->getGroups() : array(ICMS_GROUP_ANONYMOUS);
			$moduleid = (isset($icmsModule) && is_object($icmsModule)) ? $icmsModule->getVar('mid') : 1;
			$gperm_handler = icms::handler('icms_member_groupperm');
			if ($icmsConfig['debug_mode'] == 3 && $gperm_handler->checkRight('enable_debug', $moduleid, $groups)) {
				$this->debugging = true;
			}
		}
		$this->Smarty();
		if (defined('_ADM_USE_RTL') && _ADM_USE_RTL) {
			$this->assign('icms_rtl', true);
		}

		$this->assign(
			array(
			'icms_url' => ICMS_URL,
			'icms_rootpath' => ICMS_ROOT_PATH,
			'modules_url' => ICMS_MODULES_URL,
			'modules_rootpath' => ICMS_MODULES_PATH,
			'icms_langcode' => _LANGCODE,
			'icms_langname' => $GLOBALS["icmsConfig"]["language"],
			'icms_charset' => _CHARSET,
			'icms_version' => ICMS_VERSION_NAME,
			'icms_upload_url' => ICMS_UPLOAD_URL,
			'xoops_url' => ICMS_URL,
			'xoops_rootpath' => ICMS_ROOT_PATH,
			'xoops_langcode' => _LANGCODE,
			'xoops_charset' => _CHARSET,
			'xoops_version' => ICMS_VERSION_NAME,
			'xoops_upload_url' => ICMS_UPLOAD_URL
			)
		);
	}

	/**
	 * Renders output from template data
	 *
	 * @param   string  $data		The template to render
	 * @param	bool	$display	If rendered text should be output or returned
	 * @return  string  			Rendered output if $display was false
	 **/
	public function fetchFromData($tplSource, $display = false, $vars = null) {
		if (!function_exists('smarty_function_eval')) {
			require_once SMARTY_DIR . '/plugins/function.eval.php';
		}
		if (isset($vars)) {
			$oldVars = $this->_tpl_vars;
			$this->assign($vars);
			$out = smarty_function_eval(array('var' => $tplSource), $this);
			$this->_tpl_vars = $oldVars;
			return $out;
		}
		return smarty_function_eval(array('var' => $tplSource), $this );
	}

	/**
	 * Touch the resource (file) which means get it to recompile the resource
	 *
	 * @param   string  $resourcename	Resourcename to touch
	 * @return  string  $result         Was the resource recompiled
	 **/
	public function touch($resourceName) {
		$isForced = $this->force_compile;
		$this->force_compile = true;
		$this->clear_cache($resourceName);
		$result = $this->_compile_resource($resourceName, $this->_get_compile_path($resourceName));
		$this->force_compile = $isForced;
		return $result;
	}

	/**
	 * @deprecated	Use $this->template_dir = $dirname instead
	 * @todo 		Remove in version 1.4 - there are no other occurrences in the core
	 */
	function xoops_setTemplateDir($dirname) {
		$this->template_dir = $dirname;
		icms_core_Debug::setDeprecated('$this->template_dir = $dirname', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
	/**
	 * @deprecated	Use $this->template_dir instead
	 * @todo		Remove in version 1.4 - there are no other occurrences in the core
	 */
	function xoops_getTemplateDir() {
		icms_core_Debug::setDeprecated('$this->template_dir', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return $this->template_dir;
	}
	/**
	 * @deprecated	Use $this->debugging = $flag, instead
	 * @todo		Remove in version 1.4 - there are no more occurrences in the core
	 * @param $flag
	 */
	function xoops_setDebugging($flag=false) {
		$this->debugging = is_bool($flag) ? $flag : false;
		icms_core_Debug::setDeprecated('$this->debugging = $flag', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
	/**
	 * @deprecated	Use $this->caching = $num, instead
	 * @todo		Remove in version 1.4 - all occurrences have been removed from the core
	 * @param $num
	 */
	function xoops_setCaching( $num = 0) {
		$this->caching = (int)$num;
		icms_core_Debug::setDeprecated('$this->caching = $num', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
	/**
	 * @deprecated	Use $this->compile_dir instead
	 * @todo		Remove in version 1.4 - there are no other occurrences in the core
	 * @param $dirname
	 */
	function xoops_setCompileDir($dirname) {
		$this->compile_dir = $dirname;
		icms_core_Debug::setDeprecated('$this->compile_dir = $dirname', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
	/**
	 * @deprecated	Use $this->cache_dir = $dirname, instead
	 * @todo		Remove in version 1.4 - there are no other occurrences in the core
	 * @param $dirname
	 */
	function xoops_setCacheDir($dirname) {
		$this->cache_dir = $dirname;
		icms_core_Debug::setDeprecated('$this->cache_dir = $dirname', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
	/**
	 * @deprecated	Use $this->compile_check, instead
	 * @todo		Remove in version 1.4 - there are no other occurrences in the core
	 */
	function xoops_canUpdateFromFile() {
		icms_core_Debug::setDeprecated('$this->compile_check', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return $this->compile_check;
	}
	/**
	 * @deprecated	Use $this->fetchFromData( $data ), instead
	 * @todo		Remove in version 1.4 - there are no other occurrences in the core
	 * @param $data
	 */
	function xoops_fetchFromData( $data) {
		icms_core_Debug::setDeprecated('$this->fetchFromData( $data )', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return $this->fetchFromData( $data );
	}
	/**
	 * @deprecated	use $this->caching or $this->cache_lifetime, instead
	 * @todo		Remove in version 1.4 - there are no other occurrences in the core
	 * @param unknown_type $num
	 */
	function xoops_setCacheTime( $num = 0) {
		if (( $num = (int)$num ) <= 0) {
			icms_core_Debug::setDeprecated('$this->caching = 0', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
			$this->caching = 0;
		} else {
			icms_core_Debug::setDeprecated('$this->cache_lifetime = $num', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
			$this->cache_lifetime = $num;
		}
	}

	/**
	 * function to update compiled template file in templates_c folder

	 * Prior to PHP5.3.0, when refering to the class with a variable, like $icmsAdminTpl, you
	 * still need to use the arrow operator instead of ::
	 * http://www.php.net/manual/en/language.oop5.paamayim-nekudotayim.php
	 *
	 * The proper way to use this would be
	 * icms_view_Tpl::template_touch($tplid);
	 * or
	 * $icmsAdminTpl->template_touch($tplid);
	 *
	 * @param   string  $tpl_id
	 * @return  boolean
	 **/
	static public function template_touch($tpl_id) {
		$tplfile_handler =& icms::handler('icms_view_template_file');
		$tplfile =& $tplfile_handler->get($tpl_id);

		if (is_object($tplfile)) {
			$file = $tplfile->getVar('tpl_file', 'n');
			$tpl = new icms_view_Tpl();
			return $tpl->touch("db:$file");
		}
		return false;
	}

	/**
	 * Clear the module cache
	 *
	 * Prior to PHP5.3.0, when refering to the class with a variable, like $icmsAdminTpl, you
	 * still need to use the arrow operator instead of ::
	 * http://www.php.net/manual/en/language.oop5.paamayim-nekudotayim.php
	 *
	 * The proper way to use this would be
	 * icms_view_Tpl::template_clear_module_cache($tplid);
	 * or
	 * $icmsAdminTpl->template_clear_module_cache($tplid);
	 *
	 * @param   int $mid    Module ID
	 * @return
	 **/
	static public function template_clear_module_cache($mid) {
		$icms_block_handler = icms::handler('icms_view_block');
		$block_arr = $icms_block_handler->getByModule($mid);
		$count = count($block_arr);
		if ($count > 0) {
			$xoopsTpl = new icms_view_Tpl();
			$xoopsTpl->caching = 2;
			for ($i = 0; $i < $count; $i++) {
				if ($block_arr[$i]->getVar('template') != '') {
					$xoopsTpl->clear_cache('db:'.$block_arr[$i]->getVar('template'), 'blk_'.$block_arr[$i]->getVar('bid'));
				}
			}
		}
	}
}

