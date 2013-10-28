<?php
/**
 * Logic and rendering for adminstration of modules
 * 
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		System
 * @subpackage	Modules
 * @author		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version		SVN: $Id: modulesadmin.php 21379 2011-03-30 13:53:00Z m0nty_ $
 */

if (!is_object(icms::$user) || !is_object($icmsModule) || !icms::$user->isAdmin($icmsModule->getVar('mid'))) {
	exit("Access Denied");
}

/**
 * Logic and rendering for listing modules
 * @return NULL	Assigns content to the template
 */
function xoops_module_list() {
	global $icmsAdminTpl, $icmsConfig;

	$icmsAdminTpl->assign('lang_madmin', _MD_AM_MODADMIN);
	$icmsAdminTpl->assign('lang_module', _MD_AM_MODULE);
	$icmsAdminTpl->assign('lang_version', _MD_AM_VERSION);
	$icmsAdminTpl->assign('lang_modstatus', _MD_AM_MODULESADMIN_STATUS);
	$icmsAdminTpl->assign('lang_lastup', _MD_AM_LASTUP);
	$icmsAdminTpl->assign('lang_active', _MD_AM_ACTIVE);
	$icmsAdminTpl->assign('lang_order', _MD_AM_ORDER);
	$icmsAdminTpl->assign('lang_order0', _MD_AM_ORDER0);
	$icmsAdminTpl->assign('lang_action', _MD_AM_ACTION);
	$icmsAdminTpl->assign('lang_modulename', _MD_AM_MODULESADMIN_MODULENAME);
	$icmsAdminTpl->assign('lang_moduletitle', _MD_AM_MODULESADMIN_MODULETITLE);
	$icmsAdminTpl->assign('lang_info', _INFO);
	$icmsAdminTpl->assign('lang_update', _MD_AM_UPDATE);
	$icmsAdminTpl->assign('lang_unistall', _MD_AM_UNINSTALL);
	$icmsAdminTpl->assign('lang_support', _MD_AM_MODULESADMIN_SUPPORT);
	$icmsAdminTpl->assign('lang_submit', _MD_AM_SUBMIT);
	$icmsAdminTpl->assign('lang_install', _MD_AM_INSTALL);
	$icmsAdminTpl->assign('lang_installed', _MD_AM_INSTALLED);
	$icmsAdminTpl->assign('lang_noninstall', _MD_AM_NONINSTALL);

	$module_handler = icms::handler('icms_module');
	$installed_mods =& $module_handler->getObjects();
	$listed_mods = array();
	foreach ($installed_mods as $module) {
		$module->registerClassPath(FALSE);
		$module->getInfo();
		$mod = array(
			'mid' => $module->getVar('mid'),
			'dirname' => $module->getVar('dirname'),
			'name' => $module->getInfo('name'),
			'title' => $module->getVar('name'),
			'image' => $module->getInfo('image'),
			'adminindex' => $module->getInfo('adminindex'),
			'hasadmin' => $module->getVar('hasadmin'),
			'hasmain' => $module->getVar('hasmain'),
			'isactive' => $module->getVar('isactive'),
			'version' => icms_conv_nr2local(round($module -> getVar('version') / 100, 2)),
			'status' => ($module->getInfo('status')) ? $module->getInfo('status') : '&nbsp;',
			'last_update' => ($module->getVar('last_update') != 0) ? formatTimestamp($module->getVar('last_update'), 'm') : '&nbsp;',
			'weight' => $module->getVar('weight'),
			'support_site_url' => $module->getInfo('support_site_url'),
		);
		$icmsAdminTpl->append('modules', $mod);
		$listed_mods[] = $module->getVar('dirname');
	}

	$dirlist = icms_module_Handler::getAvailable();
	$uninstalled = array_diff($dirlist, $listed_mods);
	foreach ($uninstalled as $file) {
		clearstatcache();
		$file = trim($file);
			$module =& $module_handler->create();
			if (!$module->loadInfo($file, FALSE)) {
				continue;
			}
			$mod = array(
				'dirname' => $module->getInfo('dirname'),
				'name' => $module->getInfo('name'),
				'image' => $module->getInfo('image'),
				'version' => icms_conv_nr2local(round($module->getInfo('version'), 2)),
				'status' => $module->getInfo('status'),
			);
			$icmsAdminTpl->append('avmodules', $mod);
			unset($module);
	}

	return $icmsAdminTpl->fetch('db:admin/modulesadmin/system_adm_modulesadmin.html');
}

/**
 * Function and rendering for installation of a module
 * 
 * @param 	string	$dirname
 * @return	string	Results of the installation process
 */
function xoops_module_install($dirname) {
	global $icmsConfig, $icmsAdminTpl;
	$dirname = trim($dirname);
	$db =& icms_db_Factory::instance();
	$reservedTables = array(
		'avatar', 'avatar_users_link', 'block_module_link', 'xoopscomments', 
		'config', 'configcategory', 'configoption', 'image', 'imagebody', 
		'imagecategory', 'imgset', 'imgset_tplset_link', 'imgsetimg', 'groups', 
		'groups_users_link', 'group_permission', 'online', 'bannerclient', 'banner', 
		'bannerfinish', 'priv_msgs', 'ranks', 'session', 'smiles', 'users', 'newblocks', 
		'modules', 'tplfile', 'tplset', 'tplsource', 'xoopsnotifications', 'banner', 
		'bannerclient', 'bannerfinish',
	);
	$module_handler = icms::handler('icms_module');
	if ($module_handler->getCount(new icms_db_criteria_Item('dirname', $dirname)) == 0) {
		$module =& $module_handler->create();
		$module->loadInfoAsVar($dirname);
		$module->registerClassPath();
		$module->setVar('weight', 1);
		$error = FALSE;
		$errs = array();
		$sqlfile =& $module->getInfo('sqlfile');
		$msgs = array();
		$msgs[] = '<h4 style="text-align:' . _GLOBAL_LEFT . ';margin-bottom: 0px;border-bottom: dashed 1px #000000;">'
			. _MD_AM_INSTALLING . $module->getInfo('name') . '</h4>';
		if ($module->getInfo('image') !== FALSE && trim($module->getInfo('image')) != '') {
			$msgs[] ='<img src="' . ICMS_MODULES_URL . '/' . $dirname . '/' . trim($module->getInfo('image')) . '" alt="" />';
		}
		$msgs[] ='<strong>'._VERSION . ':</strong> ' . icms_conv_nr2local($module->getInfo('version'));
		if ($module->getInfo('author') !== FALSE && trim($module->getInfo('author')) != '') {
			$msgs[] ='<strong>' . _AUTHOR .':</strong> ' . trim($module->getInfo('author'));
		}
		$msgs[] = '';
		$errs[] = '<h4 style="text-align:' . _GLOBAL_LEFT . ';margin-bottom: 0px;border-bottom: dashed 1px #000000;">'
			. _MD_AM_INSTALLING . $module->getInfo('name') . '</h4>';
		if ($sqlfile !== FALSE && is_array($sqlfile)) {

			$sql_file_path = ICMS_MODULES_PATH . "/" . $dirname . "/" . $sqlfile[XOOPS_DB_TYPE];
			if (!file_exists($sql_file_path)) {
				$errs[] = sprintf(_MD_AM_SQL_NOT_FOUND, '<strong>' . $sql_file_path . '</strong>');
				$error = TRUE;
			} else {
				$msgs[] = sprintf(_MD_AM_SQL_FOUND, '<strong>' . $sql_file_path . '</strong>');
				$sql_query = fread(fopen($sql_file_path, 'r'), filesize($sql_file_path));
				$sql_query = trim($sql_query);
				icms_db_legacy_mysql_Utility::splitSqlFile($pieces, $sql_query);
				$created_tables = array();
				foreach ($pieces as $piece) {
					// [0] contains the prefixed query
					// [4] contains unprefixed table name
					$prefixed_query = icms_db_legacy_mysql_Utility::prefixQuery($piece, $db->prefix());
					if (!$prefixed_query) {
						$errs[] = "<strong>$piece</strong>" . _MD_SQL_NOT_VALID;
						$error = TRUE;
						break;
					}
					// check if the table name is reserved
					if (!in_array($prefixed_query[4], $reservedTables)) {
						// not reserved, so try to create one
						if (!$db->query($prefixed_query[0])) {
							$errs[] = $db->error();
							$error = TRUE;
							break;
						} else {

							if (!in_array($prefixed_query[4], $created_tables)) {
								$msgs[] = sprintf('&nbsp;&nbsp;' . _MD_AM_TABLE_CREATED, 
									'<strong>' . $db->prefix($prefixed_query[4]) . '</strong>');
								$created_tables[] = $prefixed_query[4];
							} else {
								$msgs[] = sprintf('&nbsp;&nbsp;' . _MD_AM_DATA_INSERT_SUCCESS, 
									'<strong>' . $db->prefix($prefixed_query[4]) . '</strong>');
							}
						}
					} else {
						// the table name is reserved, so halt the installation
						$errs[] = sprintf(_MD_AM_RESERVED_TABLE, '<strong>' . $prefixed_query[4] . '</strong>');
						$error = TRUE;
						break;
					}
				}

				// if there was an error, delete the tables created so far, so the next installation will not fail
				if ($error === TRUE) {
					foreach ($created_tables as $ct) {
						$db->query("DROP TABLE " . $db->prefix($ct));
					}
				}
			}
		}

		// if no error, save the module info and blocks info associated with it
		if ($error === FALSE) {
			if (!$module_handler->insert($module)) {
				$errs[] = sprintf(_MD_AM_DATA_INSERT_FAIL, '<strong>' . $module->getVar('name') . '</strong>');
				foreach ($created_tables as $ct) {
					$db->query("DROP TABLE " . $db->prefix($ct));
				}
				$ret = "<p>" . sprintf(_MD_AM_FAILINS, 
					"<strong>" . $module->name() . "</strong>") . "&nbsp;" . _MD_AM_ERRORSC . "<br />";
				$ret .= " - " . implode("<br /> - ", $errs) . "<br /></p>";
				unset($module, $created_tables, $errs, $msgs);
				return $ret;
			} else {
				$newmid = $module->getVar('mid');
				unset($created_tables);
				$msgs[] = sprintf(_MD_AM_MOD_DATA_INSERT_SUCCESS, '<strong>' . icms_conv_nr2local($newmid) . '</strong>');
				$tplfile_handler =& icms::handler('icms_view_template_file');
				$templates = $module->getInfo('templates');
				if ($templates !== FALSE) {
					$msgs[] = _MD_AM_TEMPLATES_ADDING;
					foreach ($templates as $tpl) {
						$tplfile =& $tplfile_handler->create();
						$tpldata =& xoops_module_gettemplate($dirname, $tpl['file']);
						$tplfile->setVar('tpl_source', $tpldata, TRUE);
						$tplfile->setVar('tpl_refid', $newmid);

						$tplfile->setVar('tpl_tplset', 'default');
						$tplfile->setVar('tpl_file', $tpl['file']);
						$tplfile->setVar('tpl_desc', $tpl['description'], TRUE);
						$tplfile->setVar('tpl_module', $dirname);
						$tplfile->setVar('tpl_lastmodified', time());
						$tplfile->setVar('tpl_lastimported', 0);
						$tplfile->setVar('tpl_type', 'module');
						if (!$tplfile_handler->insert($tplfile)) {
							$msgs[] = sprintf('&nbsp;&nbsp;<span style="color:#ff0000;">' . _MD_AM_TEMPLATE_INSERT_FAIL . '</span>', 
								'<strong>' . $tpl['file'] . '</strong>');
						} else {
							$newtplid = $tplfile->getVar('tpl_id');
							$msgs[] = sprintf('&nbsp;&nbsp;' . _MD_AM_TEMPLATE_INSERTED, 
								'<strong>' . $tpl['file'] . '</strong>', '<strong>' . $newtplid . '</strong>');

							// generate compiled file
							if (!$icmsAdminTpl->template_touch($newtplid)) {
								$msgs[] = sprintf('&nbsp;&nbsp;<span style="color:#ff0000;">' . _MD_AM_TEMPLATE_COMPILE_FAIL . '</span>', 
									'<strong>' . $tpl['file'] . '</strong>', '<strong>' . $newtplid . '</strong>');
							} else {
								$msgs[] = sprintf('&nbsp;&nbsp;' . _MD_AM_TEMPLATE_COMPILED, '<strong>' . $tpl['file'] . '</strong>');
							}
						}
						unset($tpldata);
					}
				}
				$icmsAdminTpl->template_clear_module_cache($newmid);
				$blocks = $module->getInfo('blocks');
				if ($blocks !== FALSE) {
					$msgs[] = _MD_AM_BLOCKS_ADDING;
					foreach ($blocks as $blockkey => $block) {
						// break the loop if missing block config
						if (!isset($block['file']) || !isset($block['show_func'])) {
							break;
						}
						$options = '';
						if (!empty($block['options'])) {
							$options = trim($block['options']);
						}
						$newbid = $db->genId($db->prefix('newblocks') . '_bid_seq');
						$edit_func = isset($block['edit_func']) ? trim($block['edit_func']) : '';
						$template = '';
						if ((isset($block['template']) && trim($block['template']) != '')) {
							$content =& xoops_module_gettemplate($dirname, $block['template'], TRUE);
						}
						if (empty($content)) {
							$content = '';
						} else {
							$template = trim($block['template']);
						}
						$block_name = addslashes(trim($block['name']));
						$sql = "INSERT INTO " . $db->prefix("newblocks") 
							. " (bid, mid, func_num, options, name, title, content, side, weight, visible, block_type, c_type, isactive, dirname, func_file, show_func, edit_func, template, bcachetime, last_modified) VALUES ('"
							. (int) $newbid . "', '". (int) $newmid . "', '". (int) $blockkey . "', '$options', '" . $block_name . "', '" . $block_name . "', '', '1', '0', '0', 'M', 'H', '1', '" . addslashes($dirname) . "', '" . addslashes(trim($block['file'])) . "', '" . addslashes(trim($block['show_func'])) . "', '" . addslashes($edit_func) . "', '" . $template . "', '0', '" . time() . "')";
						if (!$db->query($sql)) {
							$msgs[] = sprintf('&nbsp;&nbsp;<span style="color:#ff0000;">' . _MD_AM_BLOCKS_ADD_FAIL . '</span>', 
								'<strong>' . $block['name'] . '</strong>', '<strong>' . $db->error() . '</strong>');
						} else {
							if (empty($newbid)) {
								$newbid = $db->getInsertId();
							}
							$msgs[] = sprintf(_MD_AM_BLOCK_ADDED, '<strong>' . $block['name'] . '</strong>', 
								'<strong>' . icms_conv_nr2local($newbid) . '</strong>');
							$sql = 'INSERT INTO ' . $db->prefix('block_module_link') 
								. ' (block_id, module_id, page_id) VALUES ('
								. (int) $newbid . ', 0, 1)';
							$db->query($sql);
							if ($template != '') {
								$tplfile =& $tplfile_handler->create();
								$tplfile->setVar('tpl_refid', $newbid);
								$tplfile->setVar('tpl_source', $content, TRUE);
								$tplfile->setVar('tpl_tplset', 'default');
								$tplfile->setVar('tpl_file', $block['template']);
								$tplfile->setVar('tpl_module', $dirname);
								$tplfile->setVar('tpl_type', 'block');
								$tplfile->setVar('tpl_desc', isset($block['description']) ? $block['description'] : '', TRUE);
								$tplfile->setVar('tpl_lastimported', 0);
								$tplfile->setVar('tpl_lastmodified', time());
								if (!$tplfile_handler->insert($tplfile)) {
									$msgs[] = sprintf('&nbsp;&nbsp;<span style="color:#ff0000;">' . _MD_AM_TEMPLATE_INSERT_FAIL . '</span>', 
										'<strong>' . $block['template'] . '</strong>');
								} else {
									$newtplid = $tplfile->getVar('tpl_id');
									$msgs[] = sprintf(_MD_AM_TEMPLATE_INSERTED, '<strong>' . $block['template'] . '</strong>', 
										'<strong>' . icms_conv_nr2local($newtplid) . '</strong>');
									// generate compiled file
									if (!$icmsAdminTpl->template_touch($newtplid)) {
										$msgs[] = sprintf('&nbsp;&nbsp;<span style="color:#ff0000;">' . _MD_AM_TEMPLATE_COMPILE_FAIL . '</span>', 
											'<strong>' . $block['template'] . '</strong>', '<strong>' . icms_conv_nr2local($newtplid) . '</strong>');
									} else {
										$msgs[] = sprintf('&nbsp;&nbsp;' . _MD_AM_TEMPLATE_COMPILED, '<strong>' . $block['template'] . '</strong>');
									}
								}
							}
						}
						unset($content);
					}
					unset($blocks);
				}
				$configs = $module->getInfo('config');
				if ($configs !== FALSE) {
					if ($module->getVar('hascomments') != 0) {
						include_once ICMS_INCLUDE_PATH . '/comment_constants.php' ;
						$configs[] = array(
							'name' => 'com_rule', 
							'title' => '_CM_COMRULES', 
							'description' => '', 
							'formtype' => 'select', 
							'valuetype' => 'int', 
							'default' => 1, 
							'options' => array(
								'_CM_COMNOCOM' => XOOPS_COMMENT_APPROVENONE, 
								'_CM_COMAPPROVEALL' => XOOPS_COMMENT_APPROVEALL, 
								'_CM_COMAPPROVEUSER' => XOOPS_COMMENT_APPROVEUSER, 
								'_CM_COMAPPROVEADMIN' => XOOPS_COMMENT_APPROVEADMIN
							)
						);
						$configs[] = array(
							'name' => 'com_anonpost', 
							'title' => '_CM_COMANONPOST', 
							'description' => '', 
							'formtype' => 'yesno', 
							'valuetype' => 'int', 
							'default' => 0,
						);
					}
				} else {
					if ($module->getVar('hascomments') != 0) {
						include_once ICMS_INCLUDE_PATH . '/comment_constants.php' ;
						$configs[] = array(
							'name' => 'com_rule', 
							'title' => '_CM_COMRULES', 
							'description' => '', 
							'formtype' => 'select', 
							'valuetype' => 'int', 
							'default' => 1, 
							'options' => array(
								'_CM_COMNOCOM' => XOOPS_COMMENT_APPROVENONE, 
								'_CM_COMAPPROVEALL' => XOOPS_COMMENT_APPROVEALL, 
								'_CM_COMAPPROVEUSER' => XOOPS_COMMENT_APPROVEUSER, 
								'_CM_COMAPPROVEADMIN' => XOOPS_COMMENT_APPROVEADMIN
						)
						);
						$configs[] = array(
							'name' => 'com_anonpost', 
							'title' => '_CM_COMANONPOST', 
							'description' => '', 
							'formtype' => 'yesno', 
							'valuetype' => 'int', 
							'default' => 0
						);
					}
				}

				if ($module->getVar('hasnotification') != 0) {
					if (empty($configs)) {
						$configs = array();
					}
					// Main notification options
					include_once ICMS_INCLUDE_PATH . '/notification_constants.php';
					$options = array(
						'_NOT_CONFIG_DISABLE'=> XOOPS_NOTIFICATION_DISABLE,
						'_NOT_CONFIG_ENABLEBLOCK' => XOOPS_NOTIFICATION_ENABLEBLOCK,
						'_NOT_CONFIG_ENABLEINLINE' => XOOPS_NOTIFICATION_ENABLEINLINE,
						'_NOT_CONFIG_ENABLEBOTH' => XOOPS_NOTIFICATION_ENABLEBOTH,
					);
					$configs[] = array(
						'name' => 'notification_enabled', 
						'title' => '_NOT_CONFIG_ENABLE', 
						'description' => '_NOT_CONFIG_ENABLEDSC', 
						'formtype' => 'select', 
						'valuetype' => 'int', 
						'default' => XOOPS_NOTIFICATION_ENABLEBOTH, 
						'options' => $options,
					);
					// Event-specific notification options
					// FIXME: doesn't work when update module... can't read back the array of options properly...  " changing to &quot;
					$options = array();
					$notification_handler = icms::handler('icms_data_notification');
					$categories =& $notification_handler->categoryInfo('', $module->getVar('mid'));
					foreach ($categories as $category) {
						$events =& $notification_handler->categoryEvents($category['name'], FALSE, $module->getVar('mid'));
						foreach ($events as $event) {
							if (!empty($event['invisible'])) {
								continue;
							}
							$option_name = $category['title'] . ' : ' . $event['title'];
							$option_value = $category['name'] . '-' . $event['name'];
							$options[$option_name] = $option_value;
						}
					}
					$configs[] = array(
						'name' => 'notification_events', 
						'title' => '_NOT_CONFIG_EVENTS', 
						'description' => '_NOT_CONFIG_EVENTSDSC', 
						'formtype' => 'select_multi', 
						'valuetype' => 'array', 
						'default' => array_values($options), 
						'options' => $options
					);
				}

				if ($configs !== FALSE) {
					$msgs[] = _MD_AM_CONFIG_ADDING;
					$config_handler = icms::handler('icms_config');
					$order = 0;
					foreach ($configs as $config) {
						$confobj =& $config_handler->createConfig();
						$confobj->setVar('conf_modid', $newmid);
						$confobj->setVar('conf_catid', 0);
						$confobj->setVar('conf_name', $config['name']);
						$confobj->setVar('conf_title', $config['title'], TRUE);
						$confobj->setVar('conf_desc', $config['description'], TRUE);
						$confobj->setVar('conf_formtype', $config['formtype']);
						$confobj->setVar('conf_valuetype', $config['valuetype']);
						$confobj->setConfValueForInput($config['default'], TRUE);
						$confobj->setVar('conf_order', $order);
						$confop_msgs = '';
						if (isset($config['options']) && is_array($config['options'])) {
							foreach ($config['options'] as $key => $value) {
								$confop =& $config_handler->createConfigOption();
								$confop->setVar('confop_name', $key, TRUE);
								$confop->setVar('confop_value', $value, TRUE);
								$confobj->setConfOptions($confop);
								$confop_msgs .= sprintf('<br />&nbsp;&nbsp;&nbsp;&nbsp;'. _MD_AM_CONFIGOPTION_ADDED, '<strong>' . $key . '</strong>', '<strong>' . $value . '</strong>');
								unset($confop);
							}
						}
						$order++;
						if ($config_handler->insertConfig($confobj) !== FALSE) {
							$msgs[] = sprintf('&nbsp;&nbsp;' . _MD_AM_CONFIG_ADDED . $confop_msgs, '<strong>' . $config['name'] . '</strong>');
						} else {
							$msgs[] = sprintf('&nbsp;&nbsp;<span style="color:#ff0000;">' . _MD_AM_CONFIG_ADD_FAIL . '</span>', '<strong>' . $config['name'] . '</strong>');
						}
						unset($confobj);
					}
					unset($configs);
				}
			}

			if ($module->getInfo('hasMain')) {
				$groups = array(XOOPS_GROUP_ADMIN, XOOPS_GROUP_USERS, XOOPS_GROUP_ANONYMOUS);
			} else {
				$groups = array(XOOPS_GROUP_ADMIN);
			}

			// retrieve all block ids for this module
			$icms_block_handler = icms::handler('icms_view_block');
			$blocks =& $icms_block_handler->getByModule($newmid, FALSE);
			$msgs[] = _MD_AM_PERMS_ADDING;
			$gperm_handler = icms::handler('icms_member_groupperm');
			foreach ($groups as $mygroup) {
				if ($gperm_handler->checkRight('module_admin', 0, $mygroup)) {
					$mperm =& $gperm_handler->create();
					$mperm->setVar('gperm_groupid', $mygroup);
					$mperm->setVar('gperm_itemid', $newmid);
					$mperm->setVar('gperm_name', 'module_admin');
					$mperm->setVar('gperm_modid', 1);
					if (!$gperm_handler->insert($mperm)) {
						$msgs[] = sprintf('&nbsp;&nbsp;<span style="color:#ff0000;">' . _MD_AM_ADMIN_PERM_ADD_FAIL . '</span>' , '<strong>' . icms_conv_nr2local($mygroup) . '</strong>');
					} else {
						$msgs[] = sprintf('&nbsp;&nbsp;' . _MD_AM_ADMIN_PERM_ADDED, '<strong>' . icms_conv_nr2local($mygroup) . '</strong>');
					}
					unset($mperm);
				}
				$mperm =& $gperm_handler->create();
				$mperm->setVar('gperm_groupid', $mygroup);
				$mperm->setVar('gperm_itemid', $newmid);
				$mperm->setVar('gperm_name', 'module_read');
				$mperm->setVar('gperm_modid', 1);
				if (!$gperm_handler->insert($mperm)) {
					$msgs[] = sprintf('&nbsp;&nbsp;<span style="color:#ff0000;">' . _MD_AM_USER_PERM_ADD_FAIL . '</span>', '<strong>' . icms_conv_nr2local($mygroup) . '</strong>');
				} else {
					$msgs[] = sprintf('&nbsp;&nbsp;' . _MD_AM_USER_PERM_ADDED, '<strong>' . icms_conv_nr2local($mygroup) . '</strong>');
				}
				unset($mperm);
				foreach ($blocks as $blc) {
					$bperm =& $gperm_handler->create();
					$bperm->setVar('gperm_groupid', $mygroup);
					$bperm->setVar('gperm_itemid', $blc);
					$bperm->setVar('gperm_name', 'block_read');
					$bperm->setVar('gperm_modid', 1);
					if (!$gperm_handler->insert($bperm)) {
						$msgs[] = sprintf('&nbsp;&nbsp;<span style="color:#ff0000;">' . _MD_AM_BLOCK_ACCESS_FAIL . '</span>',
							'<strong>' . icms_conv_nr2local($blc) . '</strong>',
							'<strong>' . icms_conv_nr2local($mygroup) . '</strong>');
					} else {
						$msgs[] = sprintf('&nbsp;&nbsp;' . _MD_AM_BLOCK_ACCESS_ADDED,
							'<strong>' . icms_conv_nr2local($blc) . '</strong>',
							'<strong>' . icms_conv_nr2local($mygroup) . '</strong>');
					}
					unset($bperm);
				}
			}
			unset($blocks);
			unset($groups);

			// add module specific tasks to system autotasks list
			$atasks = $module->getInfo('autotasks');
			if (isset($atasks) && is_array($atasks) && (count($atasks) > 0)) {
				$atasks_handler = &icms_getModuleHandler('autotasks', 'system');
				foreach ($atasks as $taskID => $taskData) {
					$task = &$atasks_handler->create();
					if (isset($taskData['enabled'])) $task->setVar('sat_enabled', $taskData['enabled']);
					if (isset($taskData['repeat'])) $task->setVar('sat_repeat', $taskData['repeat']);
					if (isset($taskData['interval'])) $task->setVar('sat_interval', $taskData['interval']);
					if (isset($taskData['onfinish'])) $task->setVar('sat_onfinish', $taskData['onfinish']);
					$task->setVar('sat_name', $taskData['name']);
					$task->setVar('sat_code', $taskData['code']);
					$task->setVar('sat_type', 'addon/' . $module->getInfo('dirname'));
					$task->setVar('sat_addon_id', (int) $taskID);
					if (!($atasks_handler->insert($task))) {
						$msgs[] = sprintf('&nbsp;&nbsp;<span style="color:#ff0000;">' . _MD_AM_AUTOTASK_FAIL . '</span>', '<strong>' . $taskData['name'] . '</strong>');
					} else {
						$msgs[] = sprintf('&nbsp;&nbsp;' . _MD_AM_AUTOTASK_ADDED, '<strong>' . $taskData['name'] . '</strong>');
					}
				}
				unset($atasks_handler, $task, $taskData, $criteria, $items, $taskID);
			}
			unset($atasks);

			// execute module specific install script if any
			$install_script = $module->getInfo('onInstall');
			$ModName = ($module->getInfo('modname') != '') ? trim($module->getInfo('modname')) : $dirname;
			if (FALSE !== $install_script && trim($install_script) != '') {
				include_once ICMS_MODULES_PATH . '/' . $dirname . '/' . trim($install_script);

				$is_IPF = $module->getInfo('object_items');
				if (!empty($is_IPF)) {
					$icmsDatabaseUpdater = icms_db_legacy_Factory::getDatabaseUpdater();
					$icmsDatabaseUpdater->moduleUpgrade($module, TRUE);
					array_merge($msgs, $icmsDatabaseUpdater->_messages);
				}

				if (function_exists('xoops_module_install_' . $ModName)) {
					$func = 'xoops_module_install_' . $ModName;
					if (!($lastmsg = $func($module))) {
						$msgs[] = sprintf(_MD_AM_FAIL_EXEC, '<strong>' . $func . '</strong>');
					} else {
						$msgs[] = $module->messages;
						$msgs[] = sprintf(_MD_AM_FUNCT_EXEC, '<strong>' . $func . '</strong>');
						if (is_string($lastmsg)) {
							$msgs[] = $lastmsg;
						}
					}
				} elseif (function_exists('icms_module_install_' . $ModName)) {
					$func = 'icms_module_install_' . $ModName;
					if (!($lastmsg = $func($module))) {
						$msgs[] = sprintf(_MD_AM_FAIL_EXEC, '<strong>' . $func . '</strong>');
					} else {
						$msgs[] = $module->messages;
						$msgs[] = sprintf(_MD_AM_FUNCT_EXEC, '<strong>' . $func . '</strong>');
						if (is_string($lastmsg)) {
							$msgs[] = $lastmsg;
						}
					}
				}
			}
			$ret = '<p><code>' . implode("<br />", $msgs);
			unset($msgs, $errs);
			$ret .= '</code><br />' . sprintf(_MD_AM_OKINS, "<strong>" . $module->getVar('name') . "</strong>") . '</p>';
			unset($module);
			return $ret;
		} else {
			$ret = '<p>&nbsp;&nbsp;' . implode('<br />&nbsp;&nbsp;', $errs);
				
			unset($msgs, $errs);
			$ret .= '<br />' . sprintf(_MD_AM_FAILINS, '<strong>' . $dirname . '</strong>') . '&nbsp;' . _MD_AM_ERRORSC . '</p>';
			return $ret;
		}
	} else {
		return "<p>" . sprintf(_MD_AM_FAILINS, "<strong>" . $dirname . "</strong>") . "&nbsp;" . _MD_AM_ERRORSC
		. "<br />&nbsp;&nbsp;" . sprintf(_MD_AM_ALEXISTS, $dirname) . "</p>";
	}
}

/**
 * 
 * 
 * @param	string	$dirname	Directory name of the module
 * @param	string	$template	Name of the template file
 * @param	boolean	$block		Are you trying to retrieve the template for a block?
 */
function &xoops_module_gettemplate($dirname, $template, $block = FALSE) {
	global $icmsConfig;
	$ret = '';
	if ($block) {
		$path = ICMS_MODULES_PATH . '/' . $dirname . '/templates/blocks/' . $template;
	} else {
		$path = ICMS_MODULES_PATH . '/' . $dirname . '/templates/' . $template;
	}
	if (!file_exists($path)) {
		return $ret;
	} else {
		$lines = file($path);
	}
	if (!$lines) {
		return $ret;
	}
	$count = count($lines);
	for ($i = 0; $i < $count; $i++) {
		$ret .= str_replace("\n", "\r\n", str_replace("\r\n", "\n", $lines[$i]));
	}
	return $ret;
}

/**
 * Logic for uninstalling a module
 * 
 * @param unknown_type $dirname
 * @return	string	Result messages for uninstallation
 */
function xoops_module_uninstall($dirname) {
	global $icmsConfig, $icmsAdminTpl;

	$reservedTables = array(
		'avatar', 'avatar_users_link', 'block_module_link', 'xoopscomments', 'config', 
		'configcategory', 'configoption', 'image', 'imagebody', 'imagecategory', 'imgset', 
		'imgset_tplset_link', 'imgsetimg', 'groups', 'groups_users_link', 'group_permission',
		'online', 'bannerclient', 'banner', 'bannerfinish', 'priv_msgs', 'ranks', 'session', 
		'smiles', 'users', 'newblocks', 'modules', 'tplfile', 'tplset', 'tplsource', 
		'xoopsnotifications', 'banner', 'bannerclient', 'bannerfinish');

	$db =& icms_db_Factory::instance();
	$module_handler = icms::handler('icms_module');
	$module =& $module_handler->getByDirname($dirname);
	$module->registerClassPath();
	$icmsAdminTpl->template_clear_module_cache($module->getVar('mid'));
	if ($module->getVar('dirname') == 'system') {
		return "<p>" . sprintf(_MD_AM_FAILUNINS, "<strong>" . $module->getVar('name') . "</strong>")
		. "&nbsp;" . _MD_AM_ERRORSC . "<br /> - " . _MD_AM_SYSNO . "</p>";
	} elseif ($module->getVar('dirname') == $icmsConfig['startpage']) {
		return "<p>" . sprintf(_MD_AM_FAILUNINS, "<strong>" . $module->getVar('name') . "</strong>")
		. "&nbsp;" . _MD_AM_ERRORSC . "<br /> - " . _MD_AM_STRTNO . "</p>";
	} else {
		$msgs = array();

		$member_handler = icms::handler('icms_member');
		$grps = $member_handler->getGroupList();
		foreach ($grps as $k => $v) {
			$stararr = explode('-', $icmsConfig['startpage'][$k]);
			if (count($stararr) > 0) {
				if ($module->getVar('mid') == $stararr[0]) {
					return "<p>" . sprintf(_MD_AM_FAILDEACT, "<strong>" . $module->getVar('name')
					. "</strong>") . "&nbsp;" . _MD_AM_ERRORSC . "<br /> - " . _MD_AM_STRTNO
					. "</p>";
				}
			}
		}
		if (in_array($module->getVar('dirname'), $icmsConfig ['startpage'])) {
			return "<p>" . sprintf(_MD_AM_FAILDEACT, "<strong>" . $module->getVar('name') . "</strong>")
			. "&nbsp;" . _MD_AM_ERRORSC . "<br /> - " . _MD_AM_STRTNO . "</p>";
		}

		$page_handler = icms::handler('icms_data_page');
		$criteria = new icms_db_criteria_Compo(new icms_db_criteria_Item('page_moduleid', $module->getVar('mid')));
		$pages = $page_handler->getCount($criteria);

		if ($pages > 0) {
			$pages = $page_handler->getObjects($criteria);
			$msgs[] = _MD_AM_SYMLINKS_DELETE;
			foreach ($pages as $page) {
				if (!$page_handler->delete($page)) {
					$msgs[] = sprintf('&nbsp;&nbsp;<span style="color:#ff0000;">' . _MD_AM_SYMLINK_DELETE_FAIL . '</span>',
					$page->getVar('page_title'),  '<strong>'. $page->getVar('page_id') . '</strong>');
				} else {
					$msgs[] = sprintf('&nbsp;&nbsp;' . _MD_AM_SYMLINK_DELETED,
						'<strong>' . $page->getVar('page_title') . '</strong>', '<strong>' . $page->getVar('page_id') . '</strong>');
				}
			}
		}

		if (!$module_handler->delete($module)) {
			$msgs[] = sprintf('&nbsp;&nbsp;<span style="color:#ff0000;">' . _MD_AM_DELETE_FAIL . '</span>', $module->getVar('name'));
		} else {
			// delete template files
			$tplfile_handler = icms::handler('icms_view_template_file');
			$templates =& $tplfile_handler->find(NULL, 'module', $module->getVar('mid'));
			$tcount = count($templates);
			if ($tcount > 0) {
				$msgs[] = _MD_AM_TEMPLATES_DELETE;
				for ($i = 0; $i < $tcount; $i++) {
					if (!$tplfile_handler->delete($templates[$i])) {
						$msgs[] = sprintf('&nbsp;&nbsp;<span style="color:#ff0000;">' . _MD_AM_TEMPLATE_DELETE_FAIL . '</span>',
						$templates[$i]->getVar('tpl_file') , '<strong>' . icms_conv_nr2local($templates[$i]->getVar('tpl_id')) . '</strong>');
					} else {
						$msgs[] = sprintf('&nbsp;&nbsp;' . _MD_AM_TEMPLATE_DELETED,
							'<strong>' . icms_conv_nr2local($templates[$i]->getVar('tpl_file')) . '</strong>',
							'<strong>' . icms_conv_nr2local($templates[$i]->getVar('tpl_id')) . '</strong>'
							);
					}
				}
			}
			unset($templates);

			// delete blocks and block template files
			$icms_block_handler = icms::handler('icms_view_block');
			$block_arr =& $icms_block_handler->getByModule($module->getVar('mid'));
			if (is_array($block_arr)) {
				$bcount = count($block_arr);
				$msgs[] = _MD_AM_BLOCKS_DELETE;
				for ($i = 0; $i < $bcount; $i++) {
					if (!$icms_block_handler->delete($block_arr[$i])) {
						$msgs[] = sprintf('&nbsp;&nbsp;<span style="color:#ff0000;">' . _MD_AM_BLOCK_DELETE_FAIL . '</span>',
							'<strong>' . $block_arr[$i]->getVar('name') . '</strong>',
							'<strong>' . icms_conv_nr2local($block_arr[$i]->getVar('bid')) . '</strong>'
							);
					} else {
						$msgs[] = sprintf('&nbsp;&nbsp;' . _MD_AM_BLOCK_DELETED,
							'<strong>' . $block_arr[$i]->getVar('name')	. '</strong>',
							'<strong>' . icms_conv_nr2local($block_arr[$i]->getVar('bid')) . '</strong>'
							);
					}
					if ($block_arr[$i]->getVar('template') != '') {
						$templates =& $tplfile_handler->find(NULL, 'block', $block_arr[$i]->getVar('bid'));
						$btcount = count($templates);
						if ($btcount > 0) {
							for ($j = 0; $j < $btcount; $j++) {
								if (!$tplfile_handler->delete($templates[$j])) {
									$msgs[] = sprintf('&nbsp;&nbsp;<span style="color:#ff0000;">' . _MD_AM_BLOCK_TMPLT_DELETE_FAILED . '</span>',
									$templates[$j]->getVar('tpl_file'),
										'<strong>' . icms_conv_nr2local($templates[$j]->getVar('tpl_id')) . '</strong>'
										);
								} else {
									$msgs[] = sprintf('&nbsp;&nbsp;' . _MD_AM_BLOCK_TMPLT_DELETED,
										'<strong>' . $templates[$j]->getVar('tpl_file') . '</strong>',
										'<strong>' . icms_conv_nr2local($templates[$j]->getVar('tpl_id')) . '</strong>'
										);
								}
							}
						}
						unset($templates);
					}
				}
			}

			// delete tables used by this module
			$modtables = $module->getInfo('tables');
			if ($modtables !== FALSE && is_array($modtables)) {
				$msgs[] = _MD_AM_MOD_TABLES_DELETE;
				foreach ($modtables as $table) {
					// prevent deletion of reserved core tables!
					if (!in_array($table, $reservedTables)) {
						$sql = 'DROP TABLE ' . $db->prefix($table);
						if (!$db->query($sql)) {
							$msgs[] = sprintf('&nbsp;&nbsp;<span style="color:#ff0000;">' . _MD_AM_MOD_TABLE_DELETE_FAIL . '</span>',
								'<strong>'. $db->prefix($table) . '<strong> . '
								);
						} else {
							$msgs[] = sprintf('&nbsp;&nbsp;' . _MD_AM_MOD_TABLE_DELETED,
								'<strong>' . $db->prefix($table) . '</strong>'
								);
						}
					} else {
						$msgs[] = sprintf('&nbsp;&nbsp;<span style="color:#ff0000;">' . _MD_AM_MOD_TABLE_DELETE_NOTALLOWED . '</span>',
							'<strong>' . $db->prefix($table) . '</strong>'
							);
					}
				}
			}

			// delete comments if any
			if ($module->getVar('hascomments') != 0) {
				$msgs[] = _MD_AM_COMMENTS_DELETE;
				$comment_handler = icms::handler('icms_data_comment');
				if (!$comment_handler->deleteByModule($module->getVar('mid'))) {
					$msgs[] = '&nbsp;&nbsp;<span style="color:#ff0000;">' . _MD_AM_COMMENT_DELETE_FAIL . '</span>';
				} else {
					$msgs[] = '&nbsp;&nbsp;' . _MD_AM_COMMENT_DELETED;
				}
			}

			// delete notifications if any
			if ($module->getVar('hasnotification') != 0) {
				$msgs[] = _MD_AM_NOTIFICATIONS_DELETE;
				if (!xoops_notification_deletebymodule($module->getVar('mid'))) {
					$msgs[] = '&nbsp;&nbsp;<span style="color:#ff0000;">' . _MD_AM_NOTIFICATION_DELETE_FAIL .'</span>';
				} else {
					$msgs[] = '&nbsp;&nbsp;' . _MD_AM_NOTIFICATION_DELETED;
				}
			}

			// delete permissions if any
			$msgs[] = _MD_AM_GROUPPERM_DELETE;
			$gperm_handler = icms::handler('icms_member_groupperm');
			if (!$gperm_handler->deleteByModule($module->getVar('mid'))) {
				$msgs[] = '&nbsp;&nbsp;<span style="color:#ff0000;">' . _MD_AM_GROUPPERM_DELETE_FAIL . '</span>';
			} else {
				$msgs[] = '&nbsp;&nbsp;' . _MD_AM_GROUPPERM_DELETED;
			}

			// delete module config options if any
			if ($module->getVar('hasconfig') != 0 || $module->getVar('hascomments') != 0) {
				$config_handler = icms::handler('icms_config');
				$configs =& $config_handler->getConfigs(new icms_db_criteria_Item('conf_modid', $module->getVar('mid')));
				$confcount = count($configs);
				if ($confcount > 0) {
					$msgs[] = _MD_AM_CONFIGOPTIONS_DELETE;
					for ($i = 0; $i < $confcount; $i++) {
						if (!$config_handler->deleteConfig($configs[$i])) {
							$msgs[] = sprintf('&nbsp;&nbsp;<span style="color:#ff0000;">' . _MD_AM_CONFIGOPTION_DELETE_FAIL .'</span>',
								'<strong>' . icms_conv_nr2local($configs[$i]->getvar('conf_id')) . '</strong>');
						} else {
							$msgs[] = sprintf('&nbsp;&nbsp;' . _MD_AM_CONFIGOPTION_DELETED,
								'<strong>' . icms_conv_nr2local($configs[$i]->getVar('conf_id')) . '</strong>');
						}
					}
				}
			}

			// delete autotasks
			$atasks = $module->getInfo('autotasks');
			if (isset($atasks) && is_array($atasks) && (count($atasks) > 0)) {
				$msgs[] = _MD_AM_AUTOTASKS_DELETE;
				$atasks_handler = &icms_getModuleHandler('autotasks', 'system');
				$criteria = new icms_db_criteria_Compo();
				$criteria->add(new icms_db_criteria_Item('sat_type', 'addon/' . $module->getInfo('dirname')));
				$atasks_handler->deleteAll($criteria);
				unset($atasks_handler, $criteria, $taskData);
			}
			unset($atasks);

			// delete urllinks
			$urllink_handler = icms::handler('icms_data_urllink');
			$urllink_handler->deleteAll(icms_buildCriteria(array("mid" => $module->getVar("mid"))));

			// delete files
			$file_handler = icms::handler('icms_data_file');
			$file_handler->deleteAll(icms_buildCriteria(array("mid" => $module->getVar("mid"))));

			// execute module specific install script if any
			$uninstall_script = $module->getInfo('onUninstall');
			$ModName = ($module->getInfo('modname') != '') ? trim($module->getInfo('modname')) : $dirname;
			if (FALSE !== $uninstall_script && trim($uninstall_script) != '') {
				include_once ICMS_MODULES_PATH . '/' . $dirname . '/' . trim($uninstall_script);
				if (function_exists('xoops_module_uninstall_' . $ModName)) {
					$func = 'xoops_module_uninstall_' . $ModName;
					if (!$func($module)) {
						$msgs[] = sprintf(_MD_AM_FAIL_EXEC, '<strong>' . $func . '</strong>');
					} else {
						$msgs[] = $module->messages;
						$msgs[] = sprintf(_MD_AM_FUNCT_EXEC, '<strong>' . $func . '</strong>');
					}
				} elseif (function_exists('icms_module_uninstall_' . $ModName)) {
					$func = 'icms_module_uninstall_' . $ModName;
					if (!$func($module)) {
						$msgs[] = sprintf(_MD_AM_FAIL_EXEC, '<strong>' . $func . '</strong>');
					} else {
						$msgs[] = $module->messages;
						$msgs[] = sprintf(_MD_AM_FUNCT_EXEC, '<strong>' . $func . '</strong>');
					}
				}
			}

			$msgs[] = '</code><p>' . sprintf(_MD_AM_OKUNINS, "<strong>" . $module->getVar('name') . "</strong>") . '</p>';
		}
		$ret = '<code>' . implode('<br />', $msgs);
		return $ret;
	}
}

/**
 * Logic for activating a module
 * 
 * @param	int	$mid
 * @return	string	Result message for activating the module
 */
function xoops_module_activate($mid) {
	global $icms_block_handler, $icmsAdminTpl;
	$module_handler = icms::handler('icms_module');
	$module =& $module_handler->get($mid);
	$icmsAdminTpl->template_clear_module_cache($module->getVar('mid'));
	$module->setVar('isactive', 1);
	if (!$module_handler->insert($module)) {
		$ret = "<p>" . sprintf(_MD_AM_FAILACT, "<strong>" . $module->getVar('name') . "</strong>") . "&nbsp;"
			. _MD_AM_ERRORSC . "<br />" . $module->getHtmlErrors();
		return $ret . "</p>";
	}
	$icms_block_handler = icms_getModuleHandler('blocksadmin', 'system');
	$blocks =& $icms_block_handler->getByModule($module->getVar('mid'));
	$bcount = count($blocks);
	for ($i = 0; $i < $bcount; $i++) {
		$blocks[$i]->setVar('isactive', 1);
		$icms_block_handler->insert($blocks[$i]);
	}
	return "<p>" . sprintf(_MD_AM_OKACT, "<strong>" . $module->getVar('name') . "</strong>") . "</p>";
}

/**
 * Logic for deactivating a module
 * 
 * @param	int	$mid
 * @return	string	Result message for deactivating the module
 */
function xoops_module_deactivate($mid) {
	global $icms_page_handler, $icms_block_handler, $icmsConfig, $icmsAdminTpl;
	if (!isset($icms_page_handler)) {
		$icms_page_handler = icms_getModuleHandler('pages', 'system');
	}

	$module_handler = icms::handler('icms_module');
	$module =& $module_handler->get($mid);
	$icmsAdminTpl->template_clear_module_cache($mid);
	$module->setVar('isactive', 0);
	if ($module->getVar('dirname') == "system") {
		return "<p>" . sprintf(_MD_AM_FAILDEACT, "<strong>" . $module->getVar('name') . "</strong>")
		. "&nbsp;" . _MD_AM_ERRORSC . "<br /> - " . _MD_AM_SYSNO . "</p>";
	} elseif ($module->getVar('dirname') == $icmsConfig['startpage']) {
		return "<p>" . sprintf(_MD_AM_FAILDEACT, "<strong>" . $module->getVar('name') . "</strong>")
			. "&nbsp;" . _MD_AM_ERRORSC . "<br /> - " . _MD_AM_STRTNO . "</p>";
	} else {
		$member_handler = icms::handler('icms_member');
		$grps = $member_handler->getGroupList();
		foreach ($grps as $k => $v) {
			$stararr = explode('-', $icmsConfig['startpage'][$k]);
			if (count($stararr) > 0) {
				if ($module->getVar('mid') == $stararr[0]) {
					return "<p>" . sprintf(_MD_AM_FAILDEACT, "<strong>" . $module->getVar('name')
						. "</strong>") . "&nbsp;" . _MD_AM_ERRORSC . "<br /> - " . _MD_AM_STRTNO . "</p>";
				}
			}
		}
		if (in_array($module->getVar('dirname'), $icmsConfig ['startpage'])) {
			return "<p>" . sprintf(_MD_AM_FAILDEACT, "<strong>" . $module->getVar('name') . "</strong>")
				. "&nbsp;" . _MD_AM_ERRORSC . "<br /> - " . _MD_AM_STRTNO . "</p>";
		}
		if (!$module_handler->insert($module)) {
			$ret = "<p>" . sprintf(_MD_AM_FAILDEACT, "<strong>" . $module->getVar('name') . "</strong>")
				. "&nbsp;" . _MD_AM_ERRORSC . "<br />" . $module->getHtmlErrors();
			return $ret . "</p>";
		}

		$icms_block_handler = icms_getModuleHandler('blocksadmin', 'system');
		$blocks =& $icms_block_handler->getByModule($module->getVar('mid'));
		$bcount = count($blocks);
		for ($i = 0; $i < $bcount; $i++) {
			$blocks[$i]->setVar('isactive', FALSE);
			$icms_block_handler->insert($blocks[$i]);
		}
		return "<p>" . sprintf(_MD_AM_OKDEACT, "<strong>" . $module->getVar('name') . "</strong>") . "</p>";
	}
}

/**
 * Logic for changing the weight (order) and name of modules
 * 
 * @param int $mid		Unique ID for the module to change
 * @param int $weight	Integer value of the weight to be applied to the module
 * @param str $name		Name to be applied to the module
 */
function xoops_module_change($mid, $weight, $name) {
	$module_handler = icms::handler('icms_module');
	$module =& $module_handler->get($mid);
	$module->setVar('weight', $weight);
	$module->setVar('name', $name);
	if (!$module_handler->insert($module)) {
		$ret = "<p>" . sprintf(_MD_AM_FAILORDER, "<strong>" . icms_core_DataFilter::stripSlashesGPC($name)
			. "</strong>") . "&nbsp;" . _MD_AM_ERRORSC . "<br />";
		$ret .= $module->getHtmlErrors() . "</p>";
		return $ret;
	}
	return "<p>" . sprintf(_MD_AM_OKORDER, "<strong>" . icms_core_DataFilter::stripSlashesGPC($name) . "</strong>") . "</p>";
}

/**
 * Logic for updating a module
 * 
 * @param 	str $dirname
 * @return	str	Result messages from the module update
 */
function icms_module_update($dirname) {
	global $icmsConfig, $icmsAdminTpl;

	$msgs = array();

	$dirname = trim($dirname);
	$module_handler = icms::handler('icms_module');
	$module =& $module_handler->getByDirname($dirname);

	// Save current version for use in the update function
	$prev_version = $module->getVar('version');
	$prev_dbversion = $module->getVar('dbversion');
	/**
	 * http://www.php.net/manual/en/language.oop5.paamayim-nekudotayim.php
	 * @todo PHP5.3.0 supports $icmsAdminTpl::template_clear_module_cache($module->getVar('mid'));
	 */
	$icmsAdminTpl->template_clear_module_cache($module->getVar('mid'));
	// we dont want to change the module name set by admin
	$temp_name = $module->getVar('name');
	$module->loadInfoAsVar($dirname);
	$module->setVar('name', $temp_name);

	/*
	 * ensure to only update those fields that are currently available in the database
	 * this is required to allow structural updates for the module table
	 */
	$table = new icms_db_legacy_updater_Table("modules");
	foreach (array_keys($module->vars) as $k) {
		if (!$table->fieldExists($k)) {
			unset($module->vars[$k]);
		}
	}

	if (!$module_handler->insert($module)) {
		$msgs[] = sprintf('<p>' . _MD_AM_UPDATE_FAIL . '</p>', $module->getVar('name'));
	} else {
		$newmid = $module->getVar('mid');
		$msgs[] = _MD_AM_MOD_DATA_UPDATED;
		$tplfile_handler =& icms::handler('icms_view_template_file');
		$deltpl =& $tplfile_handler->find('default', 'module', $module->getVar('mid'));
		$delng = array();
		if (is_array($deltpl)) {
			$xoopsDelTpl = new icms_view_Tpl();
			// clear cache files
			$xoopsDelTpl->clear_cache(NULL, 'mod_' . $dirname);
			// delete template file entry in db
			$dcount = count($deltpl);
			for ($i = 0; $i < $dcount; $i++) {
				if (!$tplfile_handler->delete($deltpl[$i])) {
					$delng[] = $deltpl[$i]->getVar('tpl_file');
				}
			}
		}

		$templates = $module->getInfo('templates');
		if ($templates !== FALSE) {
			$msgs[] = _MD_AM_MOD_UP_TEM;
			foreach ($templates as $tpl) {
				$tpl['file'] = trim($tpl['file']);
				if (!in_array($tpl['file'], $delng)) {
					$tpldata =& xoops_module_gettemplate($dirname, $tpl['file']);
					$tplfile =& $tplfile_handler->create();
					$tplfile->setVar('tpl_refid', $newmid);
					$tplfile->setVar('tpl_lastimported', 0);
					$tplfile->setVar('tpl_lastmodified', time());
					if (preg_match("/\.css$/i", $tpl['file'])) {
						$tplfile->setVar('tpl_type', 'css');
					} else {
						$tplfile->setVar('tpl_type', 'module');
					}
					$tplfile->setVar('tpl_source', $tpldata, TRUE);
					$tplfile->setVar('tpl_module', $dirname);
					$tplfile->setVar('tpl_tplset', 'default');
					$tplfile->setVar('tpl_file', $tpl['file'], TRUE);
					$tplfile->setVar('tpl_desc', $tpl['description'], TRUE);
					if (!$tplfile_handler->insert($tplfile)) {
						$msgs[] = sprintf('&nbsp;&nbsp;<span style="color:#ff0000;">'
						. _MD_AM_TEMPLATE_INSERT_FAIL . '</span>', '<strong>' . $tpl['file'] . '</strong>');
					} else {
						$newid = $tplfile->getVar('tpl_id');
						$msgs[] = sprintf('&nbsp;&nbsp;<span>' . _MD_AM_TEMPLATE_INSERTED . '</span>', '<strong>' . $tpl['file'] . '</strong>', '<strong>' . $newid . '</strong>');
						if ($icmsConfig['template_set'] == 'default') {
							if (!$icmsAdminTpl->template_touch($newid)) {
								$msgs[] = sprintf('&nbsp;&nbsp;<span style="color:#ff0000;">'
								. _MD_AM_TEMPLATE_RECOMPILE_FAIL . '</span>', '<strong>' . $tpl['file'] . '</strong>');
							} else {
								$msgs[] = sprintf('&nbsp;&nbsp;<span>' . _MD_AM_TEMPLATE_RECOMPILED . '</span>', '<strong>' . $tpl['file'] . '</strong>');
							}
						}
					}
					unset($tpldata);
				} else {
					$msgs[] = sprintf('&nbsp;&nbsp;<span style="color:#ff0000;">' . _MD_AM_TEMPLATE_DELETE_FAIL . '</span>', $tpl['file']);
				}
			}
		}
		$blocks = $module->getInfo('blocks');
		$msgs[] = _MD_AM_MOD_REBUILD_BLOCKS;
		if ($blocks !== FALSE) {
			$count = count($blocks);
			$showfuncs = array();
			$funcfiles = array();
			for ($i = 1; $i <= $count; $i++) {
				if (isset($blocks[$i]['show_func']) && $blocks[$i]['show_func'] != '' && isset($blocks[$i]['file']) && $blocks[$i]['file'] != '') {
					$editfunc = isset($blocks[$i]['edit_func']) ? $blocks[$i]['edit_func'] : '';
					$showfuncs[] = $blocks[$i]['show_func'];
					$funcfiles[] = $blocks[$i]['file'];
					$template = $content = '';
					if ((isset($blocks[$i]['template']) && trim($blocks[$i]['template']) != '')) {
						$content =& xoops_module_gettemplate($dirname, $blocks[$i]['template'], TRUE);
					}
					if (!$content) {
						$content = '';
					} else {
						$template = $blocks[$i]['template'];
					}
					$options = '';
					if (!empty($blocks[$i]['options'])) {
						$options = $blocks[$i]['options'];
					}
					$sql = "SELECT bid, name FROM " . icms::$xoopsDB->prefix('newblocks')
						. " WHERE mid='" . (int) $module->getVar('mid')
						. "' AND func_num='". (int) $i
						. "' AND show_func='" . addslashes($blocks[$i]['show_func'])
						. "' AND func_file='" . addslashes($blocks[$i]['file']) . "'";
					$fresult = icms::$xoopsDB->query($sql);
					$fcount = 0;
					while ($fblock = icms::$xoopsDB->fetchArray($fresult)) {
						$fcount++;
						$sql = "UPDATE " . icms::$xoopsDB->prefix("newblocks")
							. " SET name='" . addslashes($blocks[$i]['name'])
							. "', edit_func='" . addslashes($editfunc)
							. "', content='', template='" . $template
							. "', last_modified=" . time()
							. " WHERE bid='". (int) $fblock['bid'] . "'";
						$result = icms::$xoopsDB->query($sql);
						if (!$result) {
							$msgs[] = sprintf('&nbsp;&nbsp;' . _MD_AM_UPDATE_FAIL, $fblock['name']);
						} else {
							$msgs[] = sprintf('&nbsp;&nbsp;' . _MD_AM_BLOCK_UPDATED,
								'<strong>' . $fblock['name'] . '</strong>', 
								'<strong>' . icms_conv_nr2local($fblock['bid']) . '</strong>');
							if ($template != '') {
								$tplfile =& $tplfile_handler->find('default', 'block', $fblock['bid']);
								if (count($tplfile) == 0) {
									$tplfile_new =& $tplfile_handler->create();
									$tplfile_new->setVar('tpl_module', $dirname);
									$tplfile_new->setVar('tpl_refid', (int) $fblock['bid']);
									$tplfile_new->setVar('tpl_tplset', 'default');
									$tplfile_new->setVar('tpl_file', $blocks[$i]['template'], TRUE);
									$tplfile_new->setVar('tpl_type', 'block');
								}
								else {
									$tplfile_new = $tplfile[0];
								}
								$tplfile_new->setVar('tpl_source', $content, TRUE);
								$tplfile_new->setVar('tpl_desc', $blocks[$i]['description'], TRUE);
								$tplfile_new->setVar('tpl_lastmodified', time());
								$tplfile_new->setVar('tpl_lastimported', 0);
								if (!$tplfile_handler->insert($tplfile_new)) {
									$msgs[] = sprintf('&nbsp;&nbsp;<span style="color:#ff0000;">'
										. _MD_AM_TEMPLATE_UPDATE_FAIL . '</span>', '<strong>' . $blocks[$i]['template'] . '</strong>');
								} else {
									$msgs[] = sprintf('&nbsp;&nbsp;' . _MD_AM_TEMPLATE_UPDATED, '<strong>' . $blocks[$i]['template'] . '</strong>');
									if ($icmsConfig['template_set'] == 'default') {
										if (!$icmsAdminTpl->template_touch($tplfile_new->getVar('tpl_id'))) {
											$msgs[] = sprintf('&nbsp;&nbsp;<span style="color:#ff0000;">'
												. _MD_AM_TEMPLATE_RECOMPILE_FAIL . '</span>', '<strong>' . $blocks[$i]['template'] . '</strong>');
										} else {
											$msgs[] = sprintf('&nbsp;&nbsp;' . _MD_AM_TEMPLATE_RECOMPILED, '<strong>' . $blocks[$i]['template'] . '</strong>');
										}
									}
								}
							}
						}
					}

					if ($fcount == 0) {
						$newbid = icms::$xoopsDB->genId(icms::$xoopsDB->prefix('newblocks') . '_bid_seq');
						$block_name = addslashes($blocks[$i]['name']);
						/* @todo properly handle the block_type when updating the system module */
						$sql = "INSERT INTO " . icms::$xoopsDB->prefix("newblocks")
							. " (bid, mid, func_num, options, name, title, content, side, weight, visible, block_type, c_type, isactive, dirname, func_file, show_func, edit_func, template, bcachetime, last_modified) VALUES ('"
							. (int) $newbid . "', '". (int) $module->getVar('mid') . "', '". (int) $i . "', '" . addslashes($options) . "', '" . $block_name . "', '" . $block_name . "', '', '1', '0', '0', 'M', 'H', '1', '" . addslashes($dirname) . "', '" . addslashes($blocks[$i]['file']) . "', '" . addslashes($blocks[$i]['show_func']) . "', '" . addslashes($editfunc) . "', '" . $template . "', '0', '" . time() . "')";
						$result = icms::$xoopsDB->query($sql);
						if (!$result) {
							$msgs[] = sprintf('&nbsp;&nbsp;' .  _MD_AM_CREATE_FAIL, $blocks[$i]['name']);
							echo $sql;
						} else {
							if (empty($newbid)) {
								$newbid = icms::$xoopsDB->getInsertId();
							}
							$groups =& icms::$user->getGroups();
							$gperm_handler = icms::handler('icms_member_groupperm');
							foreach ($groups as $mygroup) {
								$bperm =& $gperm_handler->create();
								$bperm->setVar('gperm_groupid', (int) $mygroup);
								$bperm->setVar('gperm_itemid', (int) $newbid);
								$bperm->setVar('gperm_name', 'block_read');
								$bperm->setVar('gperm_modid', 1);
								if (!$gperm_handler->insert($bperm)) {
									$msgs[] = sprintf('&nbsp;&nbsp;<span style="color:#ff0000;">' . _MD_AM_BLOCK_ACCESS_FAIL . '</span>',
										'<strong>' . $newbid . '</strong>',
										'<strong>' . $mygroup . '</strong>');
								} else {
									$msgs[] = sprintf('&nbsp;&nbsp;' . _MD_AM_BLOCK_ACCESS_ADDED,
										'<strong>' . $newbid . '</strong>',
										'<strong>' . $mygroup . '</strong>');
								}
							}

							if ($template != '') {
								$tplfile =& $tplfile_handler->create();
								$tplfile->setVar('tpl_module', $dirname);
								$tplfile->setVar('tpl_refid', (int) $newbid);
								$tplfile->setVar('tpl_source', $content, TRUE);
								$tplfile->setVar('tpl_tplset', 'default');
								$tplfile->setVar('tpl_file', $blocks[$i]['template'], TRUE);
								$tplfile->setVar('tpl_type', 'block');
								$tplfile->setVar('tpl_lastimported', 0);
								$tplfile->setVar('tpl_lastmodified', time());
								$tplfile->setVar('tpl_desc', $blocks[$i]['description'], TRUE);
								if (!$tplfile_handler->insert($tplfile)) {
									$msgs[] = sprintf('&nbsp;&nbsp;<span style="color:#ff0000;">' . _MD_AM_TEMPLATE_INSERT_FAIL . '</span>',
										'<strong>' . $blocks[$i]['template'] . '</strong>');
								} else {
									$newid = $tplfile->getVar('tpl_id');
									$msgs[] = sprintf('&nbsp;&nbsp;' . _MD_AM_TEMPLATE_INSERTED,
										'<strong>' . $blocks[$i]['template'] . '</strong>', '<strong>' . $newid . '</strong>');
									if ($icmsConfig['template_set'] == 'default') {
										if (!$icmsAdminTpl->template_touch($newid)) {
											$msgs[] = sprintf('&nbsp;&nbsp;<span style="color:#ff0000;">' . _MD_AM_TEMPLATE_RECOMPILE_FAIL . '</span>',
												'<strong>' . $blocks[$i]['template'] . '</strong>');
										} else {
											$msgs[] = sprintf('&nbsp;&nbsp;' . _MD_AM_TEMPLATE_RECOMPILED, '<strong>' . $blocks[$i]['template'] . '</strong>');
										}
									}
								}
							}
							$msgs[] = sprintf('&nbsp;&nbsp;' . _MD_AM_BLOCK_CREATED,
								'<strong>' . $blocks[$i]['name'] . '</strong>', 
								'<strong>' . $newbid . '</strong>');
							$sql = "INSERT INTO " . icms::$xoopsDB->prefix('block_module_link')
								. " (block_id, module_id, page_id) VALUES ('"
								. (int) $newbid . "', '0', '1')";
							icms::$xoopsDB->query($sql);
						}
					}
				}
			}

			$icms_block_handler = icms::handler('icms_view_block');
			$block_arr = $icms_block_handler->getByModule($module->getVar('mid'));
			foreach ($block_arr as $block) {
				if (!in_array($block->getVar('show_func'), $showfuncs) || !in_array($block->getVar('func_file'), $funcfiles)) {
					$sql = sprintf("DELETE FROM %s WHERE bid = '%u'", icms::$xoopsDB->prefix('newblocks'), (int) $block->getVar('bid'));
					if (!icms::$xoopsDB->query($sql)) {
						$msgs[] = sprintf('&nbsp;&nbsp;<span style="color:#ff0000;">' . _MD_AM_BLOCK_DELETE_FAIL . '</span>',
							'<strong>' . $block->getVar('name') . '</strong>',
							'<strong>' . $block->getVar('bid') . '</strong>');
					} else {
						$msgs[] = sprintf('&nbsp;&nbsp;' . _MD_AM_BLOCK_DELETED,
							'<strong>' . $block->getVar('name') . '</strong>',
							'<strong>' . $block->getVar('bid') . '</strong>');
						if ($block->getVar('template') != '') {
							$tplfiles =& $tplfile_handler->find(NULL, 'block', $block->getVar('bid'));
							if (is_array($tplfiles)) {
								$btcount = count($tplfiles);
								for ($k = 0; $k < $btcount; $k++) {
									if (!$tplfile_handler->delete($tplfiles[$k])) {
										$msgs[] = sprintf('&nbsp;&nbsp;<span style="color:#ff0000;">' . _MD_AM_BLOCK_TMPLT_DELETE_FAILED . '</span>',
											'<strong>' . $tplfiles[$k]->getVar('tpl_file') . '</strong>',
											'<strong>' . $tplfiles[$k]->getVar('tpl_id') . '</strong>');
									} else {
										$msgs[] = sprintf('&nbsp;&nbsp;' . _MD_AM_BLOCK_TMPLT_DELETED,
											'<strong>' . $tplfiles[$k]->getVar('tpl_file') . '</strong>',
											'<strong>' . $tplfiles[$k]->getVar('tpl_id') . '</strong>');
									}
								}
							}
						}
					}
				}
			}
		}

		// first delete all config entries
		$config_handler = icms::handler('icms_config');
		$configs =& $config_handler->getConfigs(new icms_db_criteria_Item('conf_modid', $module->getVar('mid')));
		$confcount = count($configs);
		$config_delng = array();
		if ($confcount > 0) {
			$msgs[] = _MD_AM_CONFIGOPTION_DELETED;
			for ($i = 0; $i < $confcount; $i++) {
				if (!$config_handler->deleteConfig($configs[$i])) {
					$msgs[] = sprintf('&nbsp;&nbsp;<span style="color:#ff0000;">' . _MD_AM_CONFIGOPTION_DELETE_FAIL . '</span>',
						'<strong>' . $configs[$i]->getvar('conf_id') . '</strong>');
					// save the name of config failed to delete for later use
					$config_delng[] = $configs[$i]->getvar('conf_name');
				} else {
					$config_old[$configs[$i]->getvar('conf_name')]['value'] = $configs[$i]->getvar('conf_value', 'N');
					$config_old[$configs[$i]->getvar('conf_name')]['formtype'] = $configs[$i]->getvar('conf_formtype');
					$config_old[$configs[$i]->getvar('conf_name')]['valuetype'] = $configs[$i]->getvar('conf_valuetype');
					$msgs[] = sprintf('&nbsp;&nbsp;' . _MD_AM_CONFIGOPTION_DELETED,
						'<strong>' . $configs[$i]->getVar('conf_id') . '</strong>');
				}
			}
		}

		// now reinsert them with the new settings
		$configs = $module->getInfo('config');
		if ($configs !== FALSE) {
			if ($module->getVar('hascomments') != 0) {
				include_once ICMS_INCLUDE_PATH . '/comment_constants.php' ;
				array_push($configs, array(
					'name' => 'com_rule', 
					'title' => '_CM_COMRULES', 
					'description' => '', 
					'formtype' => 'select', 
					'valuetype' => 'int', 
					'default' => 1, 
					'options' => array(
						'_CM_COMNOCOM' => XOOPS_COMMENT_APPROVENONE, 
						'_CM_COMAPPROVEALL' => XOOPS_COMMENT_APPROVEALL, 
						'_CM_COMAPPROVEUSER' => XOOPS_COMMENT_APPROVEUSER, 
						'_CM_COMAPPROVEADMIN' => XOOPS_COMMENT_APPROVEADMIN)
					)
				);
				array_push($configs, array(
					'name' => 'com_anonpost', 
					'title' => '_CM_COMANONPOST', 
					'description' => '', 
					'formtype' => 'yesno', 
					'valuetype' => 'int', 
					'default' => 0
					)
				);
			}
		} else {
			if ($module->getVar('hascomments') != 0) {
				include_once ICMS_INCLUDE_PATH . '/comment_constants.php' ;
				$configs[] = array(
					'name' => 'com_rule', 
					'title' => '_CM_COMRULES', 
					'description' => '', 
					'formtype' => 'select', 
					'valuetype' => 'int', 
					'default' => 1, 
					'options' => array(
						'_CM_COMNOCOM' => XOOPS_COMMENT_APPROVENONE, 
						'_CM_COMAPPROVEALL' => XOOPS_COMMENT_APPROVEALL, 
						'_CM_COMAPPROVEUSER' => XOOPS_COMMENT_APPROVEUSER, 
						'_CM_COMAPPROVEADMIN' => XOOPS_COMMENT_APPROVEADMIN
					)
				);
				$configs[] = array(
					'name' => 'com_anonpost', 
					'title' => '_CM_COMANONPOST', 
					'description' => '', 
					'formtype' => 'yesno', 
					'valuetype' => 'int', 
					'default' => 0
				);
			}
		}

		if ($module->getVar('hasnotification') != 0) {
			if (empty($configs)) {
				$configs = array();
			}
			// Main notification options
			include_once ICMS_INCLUDE_PATH . '/notification_constants.php';
			$options = array(
				'_NOT_CONFIG_DISABLE' => XOOPS_NOTIFICATION_DISABLE,
				'_NOT_CONFIG_ENABLEBLOCK' => XOOPS_NOTIFICATION_ENABLEBLOCK,
				'_NOT_CONFIG_ENABLEINLINE' => XOOPS_NOTIFICATION_ENABLEINLINE,
				'_NOT_CONFIG_ENABLEBOTH' => XOOPS_NOTIFICATION_ENABLEBOTH,
			);

			$configs[] = array(
				'name' => 'notification_enabled', 
				'title' => '_NOT_CONFIG_ENABLE', 
				'description' => '_NOT_CONFIG_ENABLEDSC', 
				'formtype' => 'select', 
				'valuetype' => 'int', 
				'default' => XOOPS_NOTIFICATION_ENABLEBOTH, 
				'options'=>$options
			);
			// Event specific notification options
			// FIXME: for some reason the default doesn't come up properly
			//  initially is ok, but not when 'update' module..
			$options = array();
			$notification_handler = icms::handler('icms_data_notification');
			$categories =& $notification_handler->categoryInfo('', $module->getVar('mid'));
			foreach ($categories as $category) {
				$events =& $notification_handler->categoryEvents($category['name'], FALSE, $module->getVar('mid'));
				foreach ($events as $event) {
					if (!empty($event['invisible'])) {
						continue;
					}
					$option_name = $category['title'] . ' : ' . $event['title'];
					$option_value = $category['name'] . '-' . $event['name'];
					$options[$option_name] = $option_value;
				}
			}
			$configs[] = array(
				'name' => 'notification_events', 
				'title' => '_NOT_CONFIG_EVENTS', 
				'description' => '_NOT_CONFIG_EVENTSDSC', 
				'formtype' => 'select_multi', 
				'valuetype' => 'array', 
				'default' => array_values($options), 
				'options' => $options
			);
		}

		if ($configs !== FALSE) {
			$msgs[] = _MD_AM_CONFIG_ADDING;
			$config_handler = icms::handler('icms_config');
			$order = 0;
			foreach ($configs as $config) {
				// only insert ones that have been deleted previously with success
				if (!in_array($config['name'], $config_delng)) {
					$confobj =& $config_handler->createConfig();
					$confobj->setVar('conf_modid', (int) $newmid);
					$confobj->setVar('conf_catid', 0);
					$confobj->setVar('conf_name', $config['name']);
					$confobj->setVar('conf_title', $config['title'], TRUE);
					$confobj->setVar('conf_desc', $config['description'], TRUE);
					$confobj->setVar('conf_formtype', $config['formtype']);
					$confobj->setVar('conf_valuetype', $config['valuetype']);
					if (isset($config_old[$config['name']]['value'])
						&& $config_old[$config['name']]['formtype'] == $config['formtype']
						&& $config_old[$config['name']]['valuetype'] == $config['valuetype']
					) {
						// preserve the old value if any
						// form type and value type must be the same
						$confobj->setVar('conf_value', $config_old[$config['name']]['value'], TRUE);
					} else {
						$confobj->setConfValueForInput($config['default'], TRUE);
					}
					$confobj->setVar('conf_order', $order);
					$confop_msgs = '';
					if (isset($config['options']) && is_array($config['options'])) {
						foreach ($config['options'] as $key => $value) {
							$confop =& $config_handler->createConfigOption();
							$confop->setVar('confop_name', $key, TRUE);
							$confop->setVar('confop_value', $value, TRUE);
							$confobj->setConfOptions($confop);
							$confop_msgs .= sprintf('<br />&nbsp;&nbsp;&nbsp;&nbsp;' . _MD_AM_CONFIGOPTION_ADDED,
								'<strong>' . $key . '</strong>',
								'<strong>' . $value . '</strong>');
							unset($confop);
						}
					}
					$order++;
					if (FALSE !== $config_handler->insertConfig($confobj)) {
						$msgs[] = sprintf('&nbsp;&nbsp;' . _MD_AM_CONFIG_ADDED, '<strong>' . $config['name'] . '</strong>. ')
						. $confop_msgs;
					} else {
						$msgs[] = sprintf('&nbsp;&nbsp;<span style="color:#ff0000;">' . _MD_AM_CONFIG_ADD_FAIL . '</span>',
						'<strong>' . $config['name'] . '</strong>. ');
					}
					unset($confobj);
				}
			}
			unset($configs);
		}

		// add module specific tasks to system autotasks list
		$atasks = $module->getInfo('autotasks');
		$atasks_handler = &icms_getModuleHandler('autotasks', 'system');
		if (isset($atasks) && is_array($atasks) && (count($atasks) > 0)) {
			$msgs[] = _MD_AM_AUTOTASK_UPDATE;
			$criteria = new icms_db_criteria_Compo();
			$criteria->add(new icms_db_criteria_Item('sat_type', 'addon/' . $module->getInfo('dirname')));
			$items_atasks = $atasks_handler->getObjects($criteria, FALSE);
			foreach ($items_atasks as $task) {
				$taskID = (int) $task->getVar('sat_addon_id');
				$atasks[$taskID]['enabled'] = $task->getVar('sat_enabled');
				$atasks[$taskID]['repeat'] = $task->getVar('sat_repeat');
				$atasks[$taskID]['interval'] = $task->getVar('sat_interval');
				$atasks[$taskID]['name'] = $task->getVar('sat_name');
			}
			$atasks_handler->deleteAll($criteria);
			if (is_array($atasks)) {
				foreach ($atasks as $taskID => $taskData) {
					if (!isset($taskData['code']) || trim($taskData['code']) == '') continue;
					$task = &$atasks_handler->create();
					if (isset($taskData['enabled'])) $task->setVar('sat_enabled', $taskData['enabled']);
					if (isset($taskData['repeat'])) $task->setVar('sat_repeat', $taskData['repeat']);
					if (isset($taskData['interval'])) $task->setVar('sat_interval', $taskData['interval']);
					if (isset($taskData['onfinish'])) $task->setVar('sat_onfinish', $taskData['onfinish']);
					$task->setVar('sat_name', $taskData['name']);
					$task->setVar('sat_code', $taskData['code']);
					$task->setVar('sat_type', 'addon/' . $module->getInfo('dirname'));
					$task->setVar('sat_addon_id', (int) $taskID);
					if (!($atasks_handler->insert($task))) {
						$msgs[] = sprintf('&nbsp;&nbsp;<span style="color:#ff0000;">' . _MD_AM_AUTOTASK_FAIL . '</span>',
							'<strong>' . $taskData['name'] . '</strong>');
					} else {
						$msgs[] = sprintf('&nbsp;&nbsp;' . _MD_AM_AUTOTASK_ADDED,
							'<strong>' . $taskData['name'] . '</strong>');
					}
				}
			}
			unset($atasks, $atasks_handler, $task, $taskData, $criteria, $items, $taskID);
		}

		// execute module specific update script if any
		$update_script = $module->getInfo('onUpdate');
		$ModName = ($module->getInfo('modname') != '') ? trim($module->getInfo('modname')) : $dirname;
		if (FALSE !== $update_script && trim($update_script) != '') {
			include_once ICMS_MODULES_PATH . '/' . $dirname . '/' . trim($update_script);

			$is_IPF = $module->getInfo('object_items');
			if (!empty($is_IPF)) {
				$icmsDatabaseUpdater = icms_db_legacy_Factory::getDatabaseUpdater();
				$icmsDatabaseUpdater->moduleUpgrade($module, TRUE);
				array_merge($msgs, $icmsDatabaseUpdater->_messages);
			}

			if (function_exists('xoops_module_update_' . $ModName)) {
				$func = 'xoops_module_update_' . $ModName;
				if (!$func($module, $prev_version, $prev_dbversion)) {
					$msgs[] = sprintf(_MD_AM_FAIL_EXEC, '<strong>' . $func . '</strong>');
				} else {
					$msgs[] = $module->messages;
					$msgs[] = sprintf(_MD_AM_FUNCT_EXEC, '<strong>' . $func . '</strong>');
				}
			} elseif (function_exists('icms_module_update_' . $ModName)) {
				$func = 'icms_module_update_' . $ModName;
				if (!$func($module, $prev_version, $prev_dbversion)) {
					$msgs[] = sprintf(_MD_AM_FAIL_EXEC, '<strong>' . $func . '</strong>');
				} else {
					$msgs[] = $module->messages;
					$msgs[] = sprintf(_MD_AM_FUNCT_EXEC, '<strong>' . $func . '</strong>');
				}
			}
		}

		$msgs[] = '</code><p>' . sprintf(_MD_AM_OKUPD, '<strong>' . $module->getVar('name') . '</strong>') . '</p>';
	}
	$ret = '<code>' . implode('<br />', $msgs);
	return $ret;
}

