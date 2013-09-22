<?php
/**
 * All the blocks that are awaiting approval or admin intervention
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @package		System
 * @subpackage	Blocks
 * @version		SVN: $Id: system_waiting.php 11152 2011-03-30 16:45:08Z m0nty_ $
 */

/**
 * EXTENSIBLE "waiting block" by plugins in both waiting and modules
 *
 * @param array $options The options for this block
 * @return mixed $block The generated waiting block or empty array
 */
function b_system_waiting_show($options) {
	global $icmsConfig;

	$userlang = $icmsConfig['language'] ;

	$sql_cache_min = empty($options[1]) ? 0 : (int) $options[1] ;
	$sql_cache_file = ICMS_CACHE_PATH . '/waiting_touch' ;

	// SQL cache check (you have to use this cache with block's cache by system)
	if (file_exists($sql_cache_file)) {
		$sql_cache_mtime = filemtime($sql_cache_file) ;
		if (time() < $sql_cache_mtime + $sql_cache_min * 60) return array() ;
		else {
			unlink($sql_cache_file) ;
		}
	}

	// read language files for plugins
	icms_loadLanguageFile('system', 'plugins');

	$plugins_path = ICMS_PLUGINS_PATH . "/waiting";
	$module_handler = icms::handler('icms_module');
	$block = array();

	// get module's list installed
	$mod_lists = $module_handler->getList(new icms_db_criteria_Item(1,1),true);
	foreach ($mod_lists as $dirname => $name) {

		$plugin_info = system_get_plugin_info($dirname , $icmsConfig['language']) ;
		if (empty($plugin_info) || empty($plugin_info['plugin_path'])) continue ;

		if (! empty($plugin_info['langfile_path'])) {
			include_once $plugin_info['langfile_path'] ;
		}
		include_once $plugin_info['plugin_path'] ;

		// call the plugin
		if (function_exists(@$plugin_info['func'])) {
			// get the list of waitings
			$_tmp = call_user_func($plugin_info['func'] , $dirname) ;
			if (isset($_tmp["lang_linkname"])) {
				if (@$_tmp["pendingnum"] > 0 || $options[0] > 0) {
					$block["modules"][$dirname]["pending"][] = $_tmp;
				}
				unset($_tmp) ;
			} else {
				// Judging the plugin returns multiple items
				// if lang_linkname does not exist
				foreach ($_tmp as $_one) {
					if (@$_one["pendingnum"] > 0 || $options[0] > 0) {
						$block["modules"][$dirname]["pending"][] = $_one;
					}
				}
			}
		}

		// for older compatibilities
		// Hacked by GIJOE
		$i = 0 ;
		while (1) {
			$function_name = "b_waiting_{$dirname}_$i" ;
			if (function_exists($function_name)) {
				$_tmp = call_user_func($function_name) ;
				++ $i ;
				if ($_tmp["pendingnum"] > 0 || $options[0] > 0) {
					$block["modules"][$dirname]["pending"][] = $_tmp;
				}
				unset($_tmp);
			} else break ;
		}
		// End of Hack

		// if (count($block["modules"][$dirname]) > 0) {
		if (! empty($block["modules"][$dirname])) {
			$block["modules"][$dirname]["name"] = $name;
		}
	}
	//print_r($block);

	// SQL cache touch (you have to use this cache with block's cache by system)
	if (empty($block) && $sql_cache_min > 0) {
		$fp = fopen($sql_cache_file , "w") ;
		fclose($fp) ;
	}

	return $block ;
}

/**
 * The edit "waiting block" form
 *
 * @param array $options The options for this block
 * @return string $form The Edit waiting block form HTML string
 */
function b_system_waiting_edit($options) {

	$sql_cache_min = empty($options[1]) ? 0 : (int) $options[1] ;

	$form = _MB_SYSTEM_NOWAITING_DISPLAY . ":&nbsp;<input type='radio' name='options[0]' value='1'";
	if ($options[0] == 1) {
		$form .= " checked='checked'";
	}
	$form .= " />&nbsp;" . _YES . "<input type='radio' name='options[0]' value='0'";
	if ($options[0] == 0) {
		$form .= " checked='checked'";
	}
	$form .=" />&nbsp;" . _NO . "<br />\n";
	$form .= sprintf(_MINUTES , _MB_SYSTEM_SQL_CACHE . ":&nbsp;<input type='text' name='options[1]' value='$sql_cache_min' size='2' />") ;

	return $form;
}

/**
 * Gets the plugin information
 *
 * @param string $dirname The directory to get the plugin from
 * @param string $language The language for the plugin
 * @return array $ret The plugin information array or an empty array
 */
function system_get_plugin_info($dirname , $language = 'english') {
	// get $mytrustdirname for D3 modules
	$mytrustdirname = '' ;
	if (defined('XOOPS_TRUST_PATH') && file_exists(ICMS_MODULES_PATH . "/" . $dirname . "/mytrustdirname.php")) {
		@include ICMS_MODULES_PATH . "/" . $dirname . "/mytrustdirname.php" ;
	}

	$module_plugin_file = ICMS_MODULES_PATH . "/" . $dirname . "/include/waiting.plugin.php" ;
	$d3module_plugin_file = XOOPS_TRUST_PATH . "/modules/" . $mytrustdirname . "/include/waiting.plugin.php" ;
	$builtin_plugin_file = ICMS_PLUGINS_PATH . "/waiting/" . $dirname . ".php" ;

	if (file_exists($module_plugin_file)) {
		// module side (1st priority)
		$lang_files = array(
		ICMS_MODULES_PATH . "/$dirname/language/$language/waiting.php" ,
		ICMS_MODULES_PATH . "/$dirname/language/english/waiting.php" ,
		) ;
		$langfile_path = '' ;
		foreach ($lang_files as $lang_file) {
			if (file_exists($lang_file)) {
				$langfile_path = $lang_file ;
				break ;
			}
		}
		$ret = array(
			'plugin_path' => $module_plugin_file ,
			'langfile_path' => $langfile_path ,
			'func' => 'b_waiting_' . $dirname ,
			'type' => 'module' ,
		) ;
	} else if (! empty($mytrustdirname) && file_exists($d3module_plugin_file)) {
		// D3 module's plugin under xoops_trust_path (2nd priority)
		$lang_files = array(
		XOOPS_TRUST_PATH . "/modules/$mytrustdirname/language/$language/waiting.php" ,
		XOOPS_TRUST_PATH . "/modules/$mytrustdirname/language/english/waiting.php" ,
		) ;
		$langfile_path = '' ;
		foreach ($lang_files as $lang_file) {
			if (file_exists($lang_file)) {
				$langfile_path = $lang_file ;
				break ;
			}
		}
		$ret = array(
			'plugin_path' => $d3module_plugin_file ,
			'langfile_path' => $langfile_path ,
			'func' => 'b_waiting_' . $mytrustdirname ,
			'type' => 'module (D3)' ,
		) ;
	} else if (file_exists($builtin_plugin_file)) {
		// built-in plugin under modules/waiting (3rd priority)
		$ret = array(
			'plugin_path' => $builtin_plugin_file ,
			'langfile_path' => '' ,
			'func' => 'b_waiting_' . $dirname ,
			'type' => 'built-in' ,
		) ;
	} else {
		$ret = array() ;
	}

	return $ret ;
}

