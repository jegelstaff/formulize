<?php
/**
 * Administration of preferences, main file
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @package		System
 * @subpackage	Preferences
 * @version		SVN: $Id: main.php 22554 2011-09-05 07:52:29Z blauer-fisch $
 */

if (! is_object(icms::$user)
	|| ! is_object($icmsModule)
	|| ! icms::$user->isAdmin($icmsModule->getVar('mid'))
	) {
	exit("Access Denied");
}
if (isset($_POST)) {
	$post_vars = filter_input_array(INPUT_POST);
	if (is_array($post_vars)) extract($post_vars);
}
$icmsAdminTpl = new icms_view_Tpl();
$op = (isset($_GET['op'])) 
	? trim(filter_input(INPUT_GET, 'op'))
	: ((isset($_POST['op'])) 
		? trim(filter_input(INPUT_POST, 'op'))
		: 'list');

if (isset($_GET['confcat_id'])) {
	$confcat_id = (int) $_GET['confcat_id'];
}

switch ($op) {
	default:
	case 'list':
		/*
		 * Allow easely change the order of Preferences.
		 * $order = 1; Alphabetically order;
		 * $order = 0; Weight order;
		 *
		 * @todo: Create a preference option to set this value and improve the way to change the order.
		 */
		$order = 1;
		$confcat_handler = icms::handler('icms_config_category');
		$confcats = $confcat_handler->getObjects();
		$catcount = count($confcats);
		$ccats = array();
		$i = 0;
		foreach ($confcats as $confcat) {
			$ccats[$i]['id'] = $confcat->getVar('confcat_id');
			$ccats[$i]['name'] = constant($confcat->getVar('confcat_name'));
			$column[] = constant($confcat->getVar('confcat_name'));
			$i++;
		}
		if ($order == 1) {
			array_multisort($column, SORT_ASC, $ccats);
		}

		icms_cp_header();
		echo '<div class="CPbigTitle" style="background-image: url(' . ICMS_MODULES_URL . '/system/admin/preferences/images/preferences_big.png)">' . _MD_AM_SITEPREF . '</div><br /><ul>';
		foreach ($ccats as $confcat) {
			echo '<li><a href="admin.php?fct=preferences&amp;op=show&amp;confcat_id=' . $confcat['id'] . '" title="' . _EDIT . ' ' . $confcat['name'] . '">' . $confcat['name'] . '</a></li>';
		}
		echo '</ul>';
		icms_cp_footer();
		break;

	case 'show':
		if (empty($confcat_id)) {
			$confcat_id = 1;
		}
		$confcat_handler = icms::handler('icms_config_category');
		$confcat = & $confcat_handler->get($confcat_id);
		if (! is_object($confcat)) {
			redirect_header('admin.php?fct=preferences', 1);
		}
		global $icmsConfigUser;
		$form = new icms_form_Theme(constant($confcat->getVar('confcat_name')), 'pref_form', 'admin.php?fct=preferences', 'post', TRUE);
		$config_handler = icms::handler('icms_config');
		$criteria = new icms_db_criteria_Compo();
		$criteria->add(new icms_db_criteria_Item('conf_modid', 0));
		$criteria->add(new icms_db_criteria_Item('conf_catid', $confcat_id));
		$config = $config_handler->getConfigs($criteria);
		$confcount = count($config);
		for ($i = 0; $i < $confcount; $i++) {
			$title =(! defined($config[$i]->getVar('conf_desc')) || constant($config[$i]->getVar('conf_desc')) == '') ? constant($config[$i]->getVar('conf_title')) : constant($config[$i]->getVar('conf_title')) . '<img class="helptip" src="'. ICMS_IMAGES_SET_URL . '/actions/acp_help.png" alt="View help text" title="View help text" /><span class="helptext">' . constant($config[$i]->getVar('conf_desc')) . '</span>';
			switch ($config[$i]->getVar('conf_formtype')) {
				case 'textsarea' :
					if ($config[$i]->getVar('conf_valuetype') == 'array') {
						// this is exceptional.. only when value type is array, need a smarter way for this
						$ele =($config[$i]->getVar('conf_value') != '')
							? new icms_form_elements_Textarea($title, $config[$i]->getVar('conf_name'), icms_core_DataFilter::htmlSpecialChars(implode('|', $config[$i]->getConfValueForOutput())), 5, 50)
							: new icms_form_elements_Textarea($title, $config[$i]->getVar('conf_name'), '', 5, 50);
					} else {
						$ele = new icms_form_elements_Textarea($title, $config[$i]->getVar('conf_name'), icms_core_DataFilter::htmlSpecialChars($config[$i]->getConfValueForOutput()));
					}
					break;
						
				case 'textarea' :
					if ($config[$i]->getVar('conf_valuetype') == 'array') {
						// this is exceptional.. only when value type is array, need a smarter way for this
						$ele =($config[$i]->getVar('conf_value') != '')
							? new icms_form_elements_Textarea($title, $config[$i]->getVar('conf_name'), icms_core_DataFilter::htmlSpecialChars(implode('|', $config[$i]->getConfValueForOutput())), 5, 50)
							: new icms_form_elements_Textarea($title, $config[$i]->getVar('conf_name'), '', 5, 50);
					} else {
						$ele = new icms_form_elements_Dhtmltextarea($title, $config[$i]->getVar('conf_name'), icms_core_DataFilter::htmlSpecialChars($config[$i]->getConfValueForOutput()));
					}
					break;
						
				case 'autotasksystem':
					$handler = icms_getModuleHandler('autotasks', 'system');
					$options = &$handler->getSystemHandlersList(TRUE);
					$ele = new icms_form_elements_Select($title, $config[$i]->getVar('conf_name'), $config[$i]->getConfValueForOutput(), 1, FALSE);
					foreach ($options as $option) {
						$ele->addOption($option, $option);
					}
					unset($handler, $options, $option);
					break;
						
				case 'select' :
					$ele = new icms_form_elements_Select($title, $config[$i]->getVar('conf_name'),  $config[$i]->getConfValueForOutput());
					$options = $config_handler->getConfigOptions(new icms_db_criteria_Item('conf_id', $config[$i]->getVar('conf_id')));
					$opcount = count($options);
					for ($j = 0; $j < $opcount; $j++) {
						$optval = defined($options[$j]->getVar('confop_value')) ? constant($options[$j]->getVar('confop_value')) : $options[$j]->getVar('confop_value');
						$optkey = defined($options[$j]->getVar('confop_name')) ? constant($options[$j]->getVar('confop_name')) : $options[$j]->getVar('confop_name');
						$ele->addOption($optval, $optkey);
					}
					break;
						
				case 'select_multi' :
					$ele = new icms_form_elements_Select($title, $config[$i]->getVar('conf_name'), $config[$i]->getConfValueForOutput(), 5, TRUE);
					$options = $config_handler->getConfigOptions(new icms_db_criteria_Item('conf_id', $config[$i]->getVar('conf_id')));
					$opcount = count($options);
					for ($j = 0; $j < $opcount; $j++) {
						$optval = defined($options[$j]->getVar('confop_value'))
							? constant($options[$j]->getVar('confop_value'))
							: $options[$j]->getVar('confop_value');
						$optkey = defined($options[$j]->getVar('confop_name'))
							? constant($options[$j]->getVar('confop_name'))
							: $options[$j]->getVar('confop_name');
						$ele->addOption($optval, $optkey);
					}
					break;
						
				case 'yesno' :
					$ele = new icms_form_elements_Radioyn($title, $config[$i]->getVar('conf_name'), $config[$i]->getConfValueForOutput(), _YES, _NO);
					break;
						
				case 'theme' :
				case 'theme_multi' :
				case 'theme_admin' :
					$ele =($config[$i]->getVar('conf_formtype') != 'theme_multi')
						? new icms_form_elements_Select($title, $config[$i]->getVar('conf_name'), $config[$i]->getConfValueForOutput())
						: new icms_form_elements_Select($title, $config[$i]->getVar('conf_name'), $config[$i]->getConfValueForOutput(), 5, TRUE);
					$dirlist =($config[$i]->getVar('conf_formtype') != 'theme_admin')
						? icms_view_theme_Factory::getThemesList()
						: icms_view_theme_Factory::getAdminThemesList();
					if (! empty($dirlist)) {
						asort($dirlist);
						$ele->addOptionArray($dirlist);
					}
					$form->addElement(new icms_form_elements_Hidden('_old_theme', $config[$i]->getConfValueForOutput()));
					break;

				case 'editor' :
				case 'editor_source' :
					$type = explode('_', $config[$i]->getVar('conf_formtype'));
					$ele = new icms_form_elements_Select($title, $config[$i]->getVar('conf_name'), $config[$i]->getConfValueForOutput());
					$type = array_pop($type);
					if ($type == 'editor') $type = '';
					$dirlist = icms_plugins_EditorHandler::getListByType($type);
					if (! empty($dirlist)) {
						asort($dirlist);
						$ele->addOptionArray($dirlist);
					}
					unset($type);
					break;

				case 'editor_multi' :
					$ele = new icms_form_elements_Select($title, $config[$i]->getVar('conf_name'), $config[$i]->getConfValueForOutput(), 5, TRUE);
					$dirlist = icms_plugins_EditorHandler::getListByType();
					if (! empty($dirlist)) {
						asort($dirlist);
						$ele->addOptionArray($dirlist);
					}
					break;

				case 'select_font' :
					$ele = new icms_form_elements_Select($title, $config[$i]->getVar('conf_name'), $config[$i]->getConfValueForOutput());
					$dirlist = icms_core_Filesystem::getFileList(ICMS_LIBRARIES_PATH . '/icms/form/elements/captcha/fonts/', '', array('ttf'));
					if (! empty($dirlist)) {
						asort($dirlist);
						$ele->addOptionArray($dirlist);
					}
					break;
						
				case 'select_plugin' :
					$ele = new icms_form_elements_Select($title, $config[$i]->getVar('conf_name'), $config[$i]->getConfValueForOutput(), 8, TRUE);
					$dirlist = icms_core_Filesystem::getDirList(ICMS_PLUGINS_PATH . '/textsanitizer/');
					if (! empty($dirlist)) {
						asort($dirlist);
						$ele->addOptionArray($dirlist);
					}
					break;
						
				case 'tplset' :
					$ele = new icms_form_elements_Select($title, $config[$i]->getVar('conf_name'), $config[$i]->getConfValueForOutput());
					$tplset_handler = icms::handler('icms_view_template_set');
					$tplsetlist = $tplset_handler->getList();
					asort($tplsetlist);
					foreach ($tplsetlist as $key => $name) {
						$ele->addOption($key, $name);
					}
					// old theme value is used to determine whether to update cache or not. kind of dirty way
					$form->addElement(new icms_form_elements_Hidden('_old_theme', $config[$i]->getConfValueForOutput()));
					break;
						
				case 'timezone' :
					$ele = new icms_form_elements_select_Timezone($title, $config[$i]->getVar('conf_name'), $config[$i]->getConfValueForOutput());
					break;
						
				case 'language' :
					$ele = new icms_form_elements_select_Lang($title, $config[$i]->getVar('conf_name'), $config[$i]->getConfValueForOutput());
					break;
						
				case 'startpage' :
					$member_handler = icms::handler('icms_member');
					$grps = $member_handler->getGroupList();

					$value = $config[$i]->getConfValueForOutput();
					if (! is_array($value)) {
						$value = array();
						foreach ($grps as $k => $v) {
							$value[$k] = $config[$i]->getConfValueForOutput();
						}
					}

					$module_handler = icms::handler('icms_module');
					$criteria = new icms_db_criteria_Compo(new icms_db_criteria_Item('hasmain', 1));
					$criteria->add(new icms_db_criteria_Item('isactive', 1));
					$moduleslist = $module_handler->getList($criteria, TRUE);
					$moduleslist['--'] = _MD_AM_NONE;

					//Adding support to select custom links to be the start page
					$page_handler = icms::handler('icms_data_page');
					$criteria = new icms_db_criteria_Compo(new icms_db_criteria_Item('page_status', 1));
					$criteria->add(new icms_db_criteria_Item('page_url', '%*', 'NOT LIKE'));
					$pagelist = $page_handler->getList($criteria);

					$list = array_merge($moduleslist, $pagelist);
					asort($list);

					$ele = new icms_form_elements_Tray($title, '<br />');
					$hv = '';
					foreach ($grps as $k => $v) {
						if (! isset($value[$k])) {
							$value[$k] = '--';
						}
						$f = new icms_form_elements_Select('<b>' . $v . ':</b>', $config[$i]->getVar('conf_name') . '[' . $k . ']', $value[$k]);
						$f->addOptionArray($list);
						$ele->addElement($f);
						unset($f);
					}
					break;
						
				case 'group' :
					$ele = new icms_form_elements_select_Group($title, $config[$i]->getVar('conf_name'), TRUE, $config[$i]->getConfValueForOutput(), 1, FALSE);
					break;
						
				case 'group_multi' :
					$ele = new icms_form_elements_select_Group($title, $config[$i]->getVar('conf_name'), TRUE, $config[$i]->getConfValueForOutput(), 5, TRUE);
					break;
						
				case 'user' :
					$ele = new icms_form_elements_select_User($title, $config[$i]->getVar('conf_name'), FALSE, $config[$i]->getConfValueForOutput(), 1, FALSE);
					break;
						
				case 'user_multi' :
					$ele = new icms_form_elements_select_User($title, $config[$i]->getVar('conf_name'), FALSE, $config[$i]->getConfValueForOutput(), 5, TRUE);
					break;
						
				case 'module_cache' :
					$module_handler = icms::handler('icms_module');
					$modules = $module_handler->getObjects(new icms_db_criteria_Item('hasmain', 1), TRUE);
					$currrent_val = $config[$i]->getConfValueForOutput();
					$cache_options = array('0' => _NOCACHE, '30' => sprintf(_SECONDS, 30), '60' => _MINUTE, '300' => sprintf(_MINUTES, 5), '1800' => sprintf(_MINUTES, 30), '3600' => _HOUR, '18000' => sprintf(_HOURS, 5), '86400' => _DAY, '259200' => sprintf(_DAYS, 3), '604800' => _WEEK);
					if (count($modules) > 0) {
						$ele = new icms_form_elements_Tray($title, '<br />');
						foreach (array_keys($modules) as $mid) {
							$c_val = isset($currrent_val[$mid]) ?(int) $currrent_val[$mid] : NULL;
							$selform = new icms_form_elements_Select($modules[$mid]->getVar('name'), $config[$i]->getVar('conf_name') . "[$mid]", $c_val);
							$selform->addOptionArray($cache_options);
							$ele->addElement($selform);
							unset($selform);
						}
					} else {
						$ele = new icms_form_elements_Label($title, _MD_AM_NOMODULE);
					}
					break;
						
				case 'site_cache' :
					$ele = new icms_form_elements_Select($title, $config[$i]->getVar('conf_name'), $config[$i]->getConfValueForOutput());
					$ele->addOptionArray(array('0' => _NOCACHE, '30' => sprintf(_SECONDS, 30), '60' => _MINUTE, '300' => sprintf(_MINUTES, 5), '1800' => sprintf(_MINUTES, 30), '3600' => _HOUR, '18000' => sprintf(_HOURS, 5), '86400' => _DAY, '259200' => sprintf(_DAYS, 3), '604800' => _WEEK));
					break;
						
				case 'password' :
					$ele = new icms_form_elements_Password($title, $config[$i]->getVar('conf_name'), 50, 255, icms_core_DataFilter::htmlSpecialChars($config[$i]->getConfValueForOutput()), FALSE, ($icmsConfigUser['pass_level']?'password_adv':''));
					break;
						
				case 'color' :
					$ele = new icms_form_elements_Colorpicker($title, $config[$i]->getVar('conf_name'), icms_core_DataFilter::htmlSpecialChars($config[$i]->getConfValueForOutput()));
					break;
						
				case 'hidden' :
					$ele = new icms_form_elements_Hidden($config[$i]->getVar('conf_name'), icms_core_DataFilter::htmlSpecialChars($config[$i]->getConfValueForOutput()));
					break;
						
				case 'select_pages' :
					$content_handler = & icms_getModuleHandler('content', 'content');
					$ele = new icms_form_elements_Select($title, $config[$i]->getVar('conf_name'), $config[$i]->getConfValueForOutput());
					$ele->addOptionArray($content_handler->getContentList());
					break;
						
				# Added by Fábio Egas in XTXM version
				case 'select_image' :
					$ele = new icms_form_elements_select_Image($title, $config[$i]->getVar('conf_name'), $config[$i]->getConfValueForOutput());
					break;
						
				case 'select_paginati' :
					if (file_exists(ICMS_LIBRARIES_PATH . '/paginationstyles/paginationstyles.php')) {
						include ICMS_LIBRARIES_PATH . '/paginationstyles/paginationstyles.php';
						$st = & $styles;
						$arr = array();
						foreach ($st as $style) {
							$arr[$style['fcss']] = $style['name'];
						}
						$ele = new icms_form_elements_Select($title, $config[$i]->getVar('conf_name'), $config[$i]->getConfValueForOutput());
						$ele->addOptionArray($arr);
					}
					break;
						
				case 'select_geshi' :
					$ele = new icms_form_elements_Select($title, $config[$i]->getVar('conf_name'), $config[$i]->getConfValueForOutput());
					$dirlist = str_replace('.php', '', icms_core_Filesystem::getFileList(ICMS_LIBRARIES_PATH . '/geshi/geshi/', '', array('php')));
					if (! empty($dirlist)) {
						asort($dirlist);
						$ele->addOptionArray($dirlist);
					}
					break;
						
				case 'textbox' :
				default :
					$ele = new icms_form_elements_Text($title, $config[$i]->getVar('conf_name'), 50, 255, icms_core_DataFilter::htmlSpecialChars($config[$i]->getConfValueForOutput()));
					break;
			}
			$hidden = new icms_form_elements_Hidden('conf_ids[]', $config[$i]->getVar('conf_id'));
			$form->addElement($ele);
			$form->addElement($hidden);
			unset($ele, $hidden);
		}
		$form->addElement(new icms_form_elements_Hidden('op', 'save'));
		$form->addElement(new icms_form_elements_Button('', 'button', _GO, 'submit'));
		icms_cp_header();
		echo '<div class="CPbigTitle" style="background-image: url(' . ICMS_MODULES_URL . '/system/admin/preferences/images/preferences_big.png)"><a href="admin.php?fct=preferences">' . _MD_AM_PREFMAIN . '</a>&nbsp;<span style="font-weight:bold;">&raquo;&raquo;</span>&nbsp;' . constant($confcat->getVar('confcat_name')) . '<br /><br /></div><br />';
		$form->display();
		icms_cp_footer();
		break;

	case 'showmod':
		$config_handler = icms::handler('icms_config');
		$mod = isset($_GET['mod']) ? (int) $_GET['mod'] : 0;
		if (empty($mod)) {
			header('Location: admin.php?fct=preferences');
			exit();
		}
		$config = $config_handler->getConfigs(new icms_db_criteria_Item('conf_modid', $mod));
		$count = count($config);
		if ($count < 1) {
			redirect_header('admin.php?fct=preferences', 1);
		}
		$form = new icms_form_Theme(_MD_AM_MODCONFIG, 'pref_form', 'admin.php?fct=preferences', 'post', TRUE);
		$module_handler = icms::handler('icms_module');
		$module = & $module_handler->get($mod);
		icms_loadLanguageFile($module->getVar('dirname'), 'modinfo');
		// if has comments feature, need comment lang file
		if ($module->getVar('hascomments') == 1) {
			icms_loadLanguageFile('core', 'comment');
		}
		// if has notification feature, need notification lang file
		if ($module->getVar('hasnotification') == 1) {
			icms_loadLanguageFile('core', 'notification');
		}

		$modname = $module->getVar('name');
		if ($module->getInfo('adminindex')) {
			$form->addElement(new icms_form_elements_Hidden('redirect', ICMS_MODULES_URL . '/' . $module->getVar('dirname') . '/' . $module->getInfo('adminindex')));
		}
		for ($i = 0; $i < $count; $i++) {
			$title =(! defined($config[$i]->getVar('conf_desc')) || constant($config[$i]->getVar('conf_desc')) == '') ? constant($config[$i]->getVar('conf_title')) : constant($config[$i]->getVar('conf_title')) . '<img class="helptip" src="'. ICMS_IMAGES_SET_URL . '/actions/acp_help.png" alt="View help text" title="View help text" /><span class="helptext">' . constant($config[$i]->getVar('conf_desc')) . '</span>';
			switch ($config[$i]->getVar('conf_formtype')) {
				case 'textsarea' :
					if ($config[$i]->getVar('conf_valuetype') == 'array') {
						// this is exceptional.. only when value type is arrayneed a smarter way for this
						$ele =($config[$i]->getVar('conf_value') != '') ? new icms_form_elements_Textarea($title, $config[$i]->getVar('conf_name'), icms_core_DataFilter::htmlSpecialChars(implode('|', $config[$i]->getConfValueForOutput())), 5, 50) : new icms_form_elements_Textarea($title, $config[$i]->getVar('conf_name'), '', 5, 50);
					} else {
						$ele = new icms_form_elements_Textarea($title, $config[$i]->getVar('conf_name'), icms_core_DataFilter::htmlSpecialChars($config[$i]->getConfValueForOutput()), 5, 50);
					}
					break;
						
				case 'textarea' :
					if ($config[$i]->getVar('conf_valuetype') == 'array') {
						// this is exceptional.. only when value type is array need a smarter way for this
						$ele =($config[$i]->getVar('conf_value') != '') ? new icms_form_elements_Textarea($title, $config[$i]->getVar('conf_name'), icms_core_DataFilter::htmlSpecialChars(implode('|', $config[$i]->getConfValueForOutput())), 5, 50) : new icms_form_elements_Textarea($title, $config[$i]->getVar('conf_name'), '', 5, 50);
					} else {
						$ele = new icms_form_elements_Dhtmltextarea($title, $config[$i]->getVar('conf_name'), icms_core_DataFilter::htmlSpecialChars($config[$i]->getConfValueForOutput()), 5, 50);
					}
					break;
						
				case 'select' :
					$ele = new icms_form_elements_Select($title, $config[$i]->getVar('conf_name'), $config[$i]->getConfValueForOutput());

					$options = & $config_handler->getConfigOptions(new icms_db_criteria_Item('conf_id', $config[$i]->getVar('conf_id')));
					$opcount = count($options);
					for ($j = 0; $j < $opcount; $j++) {
						$optval = defined($options[$j]->getVar('confop_value')) ? constant($options[$j]->getVar('confop_value')) : $options[$j]->getVar('confop_value');
						$optkey = defined($options[$j]->getVar('confop_name')) ? constant($options[$j]->getVar('confop_name')) : $options[$j]->getVar('confop_name');
						$ele->addOption($optval, $optkey);
					}
					break;
						
				case 'select_multi' :
					$ele = new icms_form_elements_Select($title, $config[$i]->getVar('conf_name'), $config[$i]->getConfValueForOutput(), 5, TRUE);
					$options = & $config_handler->getConfigOptions(new icms_db_criteria_Item('conf_id', $config[$i]->getVar('conf_id')));
					$opcount = count($options);
					for ($j = 0; $j < $opcount; $j++) {
						$optval = defined($options[$j]->getVar('confop_value')) ? constant($options[$j]->getVar('confop_value')) : $options[$j]->getVar('confop_value');
						$optkey = defined($options[$j]->getVar('confop_name')) ? constant($options[$j]->getVar('confop_name')) : $options[$j]->getVar('confop_name');
						$ele->addOption($optval, $optkey);
					}
					break;
						
				case 'yesno' :
					$ele = new icms_form_elements_Radioyn($title, $config[$i]->getVar('conf_name'), $config[$i]->getConfValueForOutput(), _YES, _NO);
					break;
						
				case 'group' :
					$ele = new icms_form_elements_select_Group($title, $config[$i]->getVar('conf_name'), TRUE, $config[$i]->getConfValueForOutput(), 1, FALSE);
					break;
						
				case 'group_multi' :
					$ele = new icms_form_elements_select_Group($title, $config[$i]->getVar('conf_name'), TRUE, $config[$i]->getConfValueForOutput(), 5, TRUE);
					break;
						
				case 'user' :
					$ele = new icms_form_elements_select_User($title, $config[$i]->getVar('conf_name'), FALSE, $config[$i]->getConfValueForOutput(), 1, FALSE);
					break;
						
				case 'user_multi' :
					$ele = new icms_form_elements_select_User($title, $config[$i]->getVar('conf_name'), FALSE, $config[$i]->getConfValueForOutput(), 5, TRUE);
					break;
						
				case 'password' :
					$ele = new icms_form_elements_Password($title, $config[$i]->getVar('conf_name'), 50, 255, icms_core_DataFilter::htmlSpecialChars($config[$i]->getConfValueForOutput()));
					break;
						
				case 'color' :
					$ele = new icms_form_elements_Colorpicker($title, $config[$i]->getVar('conf_name'), icms_core_DataFilter::htmlSpecialChars($config[$i]->getConfValueForOutput()));
					break;
						
				case 'hidden' :
					$ele = new icms_form_elements_Hidden($config[$i]->getVar('conf_name'), icms_core_DataFilter::htmlSpecialChars($config[$i]->getConfValueForOutput()));
					break;
						
				case 'select_pages' :
					$content_handler = & icms_getModuleHandler('content', 'content');
					$ele = new icms_form_elements_Select($title, $config[$i]->getVar('conf_name'), $config[$i]->getConfValueForOutput());
					$ele->addOptionArray($content_handler->getContentList());
					break;
						
				case 'textbox' :
				default :
					$ele = new icms_form_elements_Text($title, $config[$i]->getVar('conf_name'), 50, 255, icms_core_DataFilter::htmlSpecialChars($config[$i]->getConfValueForOutput()));
					break;
			}
			$hidden = new icms_form_elements_Hidden('conf_ids[]', $config[$i]->getVar('conf_id'));
			$form->addElement($ele);
			$form->addElement($hidden);
			unset($ele, $hidden);
		}
		$form->addElement(new icms_form_elements_Hidden('op', 'save'));
		$form->addElement(new icms_form_elements_Button('', 'button', _GO, 'submit'));
		icms_cp_header();
		if ($module->getInfo('hasAdmin') == TRUE) {
			$modlink = '<a href="' . ICMS_MODULES_URL . '/' . $module->getVar('dirname') . '/' . $module->getInfo('adminindex') . '">' . $modname . '</a>';
		} else {
			$modlink = $modname;
		}
		$iconbig = $module->getInfo('iconbig');
		if (isset($iconbig) && $iconbig == FALSE) {
			echo '<div class="CPbigTitle" style="background-image: url(' . ICMS_MODULES_URL . '/system/admin/preferences/images/preferences_big.png);">' . $modlink . ' &raquo; ' . _PREFERENCES . '</div>';

		}
		if (isset($iconbig) && $iconbig == TRUE) {
			echo '<div class="CPbigTitle" style="background-image: url(' . ICMS_MODULES_URL . '/' . $module->getVar('dirname') . '/' . $iconbig . ')">' . $modlink . ' &raquo; ' . _PREFERENCES . '</div>';
		}
		$form->display();
		icms_cp_footer();
		break;

	case 'save':
		if (!icms::$security->check()) {
			redirect_header('admin.php?fct=preferences', 3, implode('<br />', icms::$security->getErrors()));
		}
		$xoopsTpl = new icms_view_Tpl();
		$count = count($conf_ids);
		$tpl_updated = FALSE;
		$theme_updated = FALSE;
		$startmod_updated = FALSE;
		$lang_updated = FALSE;
		$encryption_updated = FALSE;
		$purifier_style_updated = FALSE;
		$saved_config_items = array();
		if ($count > 0) {
			for ($i = 0; $i < $count; $i++) {
				$config = & $config_handler->getConfig($conf_ids[$i]);
				$new_value = & ${$config->getVar('conf_name')};
				$old_value = $config->getVar('conf_value');
				icms::$preload->triggerEvent('savingSystemAdminPreferencesItem', array((int) $config->getVar('conf_catid'), $config->getVar('conf_name'), $config->getVar('conf_value')));

				if (is_array($new_value) || $new_value != $config->getVar('conf_value')) {
					// if language has been changed
					if (!$lang_updated && $config->getVar('conf_catid') == ICMS_CONF && $config->getVar('conf_name') == 'language') {
						$icmsConfig['language'] = ${$config->getVar('conf_name')};
						$lang_updated = TRUE;
					}
					// if default theme has been changed
					if (!$theme_updated && $config->getVar('conf_catid') == ICMS_CONF && $config->getVar('conf_name') == 'theme_set') {
						$member_handler = icms::handler('icms_member');
						$member_handler->updateUsersByField('theme', ${$config->getVar('conf_name')});
						$theme_updated = TRUE;
					}
					// if password encryption has been changed
					if (!$encryption_updated && $config->getVar('conf_catid') == ICMS_CONF_USER && $config->getVar('conf_name') == 'enc_type') {
						if ($icmsConfig['closesite'] !== 1) {
							$member_handler = icms::handler('icms_member');
							$member_handler->updateUsersByField('pass_expired', 1);
							$encryption_updated = TRUE;
						} else {
							redirect_header('admin.php?fct=preferences', 2, _MD_AM_UNABLEENCCLOSED);
						}
					}

					if (!$purifier_style_updated
						&& $config->getVar('conf_catid') == ICMS_CONF_PURIFIER
						&& $config->getVar('conf_name') == 'purifier_Filter_ExtractStyleBlocks'
						) {
						if ($config->getVar('purifier_Filter_ExtractStyleBlocks') == 1) {
							if (!file_exists(ICMS_PLUGINS_PATH . '/csstidy/class.csstidy.php')) {
								redirect_header('admin.php?fct=preferences', 5, _MD_AM_UNABLECSSTIDY);
							}
							$purifier_style_updated = TRUE;
						}
					}

					// if default template set has been changed
					if (! $tpl_updated && $config->getVar('conf_catid') == ICMS_CONF && $config->getVar('conf_name') == 'template_set') {
						// clear cached/compiled files and regenerate them if default theme has been changed
						if ($icmsConfig['template_set'] != ${$config->getVar('conf_name')}) {
							$newtplset = ${$config->getVar('conf_name')};
							// clear all compiled and cachedfiles
							$xoopsTpl->clear_compiled_tpl();
							// generate compiled files for the new theme
							// block files only for now..
							$tplfile_handler = icms::handler('icms_view_template_file');
							$dtemplates = & $tplfile_handler->find('default', 'block');
							$dcount = count($dtemplates);

							// need to do this to pass to $icmsAdminTpl->template_touch function
							$GLOBALS['icmsConfig']['template_set'] = $newtplset;

							for ($i = 0; $i < $dcount; $i++) {
								$found = & $tplfile_handler->find($newtplset, 'block', $dtemplates[$i]->getVar('tpl_refid'), NULL);
								if (count($found) > 0) {
									// template for the new theme found, compile it
									$icmsAdminTpl->template_touch($found[0]->getVar('tpl_id'));
								} else {
									// not found, so compile 'default' template file
									$icmsAdminTpl->template_touch($dtemplates[$i]->getVar('tpl_id'));
								}
							}
						}
						$tpl_updated = TRUE;
					}

					// add read permission for the start module to all groups
					if (! $startmod_updated && $new_value != '--' && $config->getVar('conf_catid') == ICMS_CONF && $config->getVar('conf_name') == 'startpage') {
						$moduleperm_handler = icms::handler('icms_member_groupperm');
						$module_handler = icms::handler('icms_module');

						foreach ($new_value as $k => $v) {
							$arr = explode('-', $v);
							if (count($arr) > 1) {
								$mid = $arr[0];
								$module = & $module_handler->get($mid);
								if ($arr[0] == 1 && $arr[1] > 0) { //Set read permission to the content page for the selected group
									if (! $moduleperm_handler->checkRight('content_read', $arr[1], $k)) {
										$moduleperm_handler->addRight('content_read', $arr[1], $k);
									}
								}
							} else {
								$module = & $module_handler->getByDirname($v);
							}
							if (is_object($module)) {
								if (! $moduleperm_handler->checkRight('module_read', $module->getVar('mid'), $k)) {
									$moduleperm_handler->addRight('module_read', $module->getVar('mid'), $k);
								}
							}
						}
						$startmod_updated = TRUE;
					}

					$config->setConfValueForInput($new_value);
					$config_handler->insertConfig($config);
				}
				unset($new_value);

				if (!isset($saved_config_items[$config->getVar('conf_catid')])) {
					$saved_config_items[$config->getVar('conf_catid')] = array();
				}
				$saved_config_items[$config->getVar('conf_catid')][$config->getVar('conf_name')] = array($old_value, $config->getVar('conf_value'));

			}
		}

		icms::$preload->triggerEvent('afterSaveSystemAdminPreferencesItems', $saved_config_items);
		unset($saved_config_items);

		if (! empty($use_mysession) && $icmsConfig['use_mysession'] == 0 && $session_name != '') {
			setcookie($session_name, session_id(), time() +(60 *(int) $session_expire), '/', '', 0);
		}

		// Clean cached files, may take long time
		// Use register_shutdown_function to keep running after connection closes so that cleaning cached files can be finished
		// Cache management should be performed on a separate page
		register_shutdown_function(array(&$xoopsTpl, 'clear_all_cache'));

		// If language is changed, leave the admin menu file to be regenerated upon next request,
		// otherwise regenerate admin menu file for now
		if (! $lang_updated) {
			// regenerate admin menu file
			register_shutdown_function('xoops_module_write_admin_menu', impresscms_get_adminmenu());
		} else {
			$redirect = ICMS_URL . '/admin.php';
		}

		if (isset($redirect) && $redirect != '') {
			redirect_header($redirect, 2, _MD_AM_DBUPDATED);
		} else {
			redirect_header('admin.php?fct=preferences', 2, _MD_AM_DBUPDATED);
		}
}
