<?php
/**
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package		Administration
* @since		XOOPS
* @author		http://www.xoops.org The XOOPS Project
* @author		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
* @version		$Id: modulesadmin.php 9446 2009-10-20 01:03:07Z skenow $
*/

if ( !is_object($icmsUser) || !is_object($icmsModule) || !$icmsUser->isAdmin($icmsModule->mid()) ) {
	exit("Access Denied");
}

function xoops_module_list() {
	global $icmsAdminTpl,$icmsUser,$xoopsConfig;

	$icmsAdminTpl->assign('lang_madmin',_MD_AM_MODADMIN);
	$icmsAdminTpl->assign('lang_module',_MD_AM_MODULE);
	$icmsAdminTpl->assign('lang_version',_MD_AM_VERSION);
	$icmsAdminTpl->assign('lang_modstatus',_MD_AM_MODULESADMIN_STATUS);
	$icmsAdminTpl->assign('lang_lastup',_MD_AM_LASTUP);
	$icmsAdminTpl->assign('lang_active',_MD_AM_ACTIVE);
	$icmsAdminTpl->assign('lang_order',_MD_AM_ORDER);
	$icmsAdminTpl->assign('lang_order0',_MD_AM_ORDER0);
	$icmsAdminTpl->assign('lang_action',_MD_AM_ACTION);
	$icmsAdminTpl->assign('lang_modulename',_MD_AM_MODULESADMIN_MODULENAME);
	$icmsAdminTpl->assign('lang_moduletitle',_MD_AM_MODULESADMIN_MODULETITLE);
	$icmsAdminTpl->assign('lang_info',_INFO);
	$icmsAdminTpl->assign('lang_update',_MD_AM_UPDATE);
	$icmsAdminTpl->assign('lang_unistall',_MD_AM_UNINSTALL);
	$icmsAdminTpl->assign('lang_support',_MD_AM_MODULESADMIN_SUPPORT);
	$icmsAdminTpl->assign('lang_submit',_MD_AM_SUBMIT);
	$icmsAdminTpl->assign('lang_install',_MD_AM_INSTALL);
	$icmsAdminTpl->assign('lang_installed',_MD_AM_INSTALLED);
	$icmsAdminTpl->assign('lang_noninstall',_MD_AM_NONINSTALL);

	$module_handler =& xoops_gethandler('module');
	$installed_mods =& $module_handler->getObjects();
	$listed_mods = array();
	foreach ( $installed_mods as $module ) {
		$module -> getInfo();
		$mod = array();
		$mod['mid'] = $module->getVar('mid');
		$mod['dirname'] = $module->getVar('dirname');
		$mod['name'] = $module -> getInfo('name');
		$mod['title'] = $module -> getVar('name');
		$mod['image'] = $module -> getInfo('image');
		$mod['adminindex'] = $module->getInfo('adminindex');
		$mod['hasadmin'] = $module->getVar('hasadmin');
		$mod['hasmain'] = $module->getVar('hasmain');
		$mod['isactive'] = $module->getVar('isactive');
		$mod['version'] = icms_conv_nr2local(round($module -> getVar('version') / 100, 2));
		$mod['status'] = ($module->getInfo('status'))?$module->getInfo('status'):'&nbsp;';
		$mod['last_update'] = ($module -> getVar('last_update') != 0)?formatTimestamp($module -> getVar('last_update'), 'm'):'&nbsp;';
		$mod['weight'] = $module->getVar('weight');
		$mod['support_site_url'] = $module->getInfo('support_site_url');
		$icmsAdminTpl->append('modules',$mod);
		$listed_mods[] = $module->getVar('dirname');
	}

	$dirlist = XoopsLists::getModulesList();
	foreach($dirlist as $file){
		clearstatcache();
		$file = trim($file);
		if ( !in_array($file, $listed_mods) ) {
			$module =& $module_handler->create();
			if (!$module->loadInfo($file, false)) {
				continue;
			}
			$mod = array();
			$mod['dirname'] = $module->getInfo('dirname');
			$mod['name'] = $module->getInfo('name');
			$mod['image'] = $module->getInfo('image');
			$mod['version'] = icms_conv_nr2local(round($module->getInfo('version'), 2));
			$mod['status'] = $module->getInfo('status');
			$icmsAdminTpl->append('avmodules',$mod);
			unset($module);
		}
	}

	return $icmsAdminTpl->fetch('db:admin/modulesadmin/system_adm_modulesadmin.html');
}

function xoops_module_install($dirname) {
	global $icmsUser, $xoopsConfig;
	$dirname = trim($dirname);
	$db =& Database::getInstance();
	$reservedTables = array('avatar', 'avatar_users_link', 'block_module_link', 'xoopscomments', 'config', 'configcategory', 'configoption', 'image', 'imagebody', 'imagecategory', 'imgset', 'imgset_tplset_link', 'imgsetimg', 'groups','groups_users_link','group_permission', 'online', 'bannerclient', 'banner', 'bannerfinish', 'priv_msgs', 'ranks', 'session', 'smiles', 'users', 'newblocks', 'modules', 'tplfile', 'tplset', 'tplsource', 'xoopsnotifications', 'banner', 'bannerclient', 'bannerfinish');
	$module_handler =& xoops_gethandler('module');
	if ($module_handler->getCount(new Criteria('dirname', $dirname)) == 0) {
		$module =& $module_handler->create();
		$module->loadInfoAsVar($dirname);
		$module->setVar('weight', 1);
		$error = false;
		$errs = array();
		$sqlfile =& $module->getInfo('sqlfile');
		$msgs = array();
		$msgs[] = '<h4 style="text-align:'._GLOBAL_LEFT.';margin-bottom: 0px;border-bottom: dashed 1px #000000;">Installing '.$module->getInfo('name').'</h4>';
		if ($module->getInfo('image') != false && trim($module->getInfo('image')) != '') {
			$msgs[] ='<img src="'.XOOPS_URL.'/modules/'.$dirname.'/'.trim($module->getInfo('image')).'" alt="" />';
		}
		$msgs[] ='<b>Version:</b> '.icms_conv_nr2local($module->getInfo('version'));
		if ($module->getInfo('author') != false && trim($module->getInfo('author')) != '') {
			$msgs[] ='<b>Author:</b> '.trim($module->getInfo('author'));
		}
		$msgs[] = '';
		$errs[] = '<h4 style="text-align:'._GLOBAL_LEFT.';margin-bottom: 0px;border-bottom: dashed 1px #000000;">Installing '.$module->getInfo('name').'</h4>';
		if ($sqlfile != false && is_array($sqlfile)) {

			$sql_file_path = XOOPS_ROOT_PATH."/modules/".$dirname."/".$sqlfile[XOOPS_DB_TYPE];
			if (!file_exists($sql_file_path)) {
				$errs[] = "SQL file not found at <b>$sql_file_path</b>";
				$error = true;
			} else {
				$msgs[] = "SQL file found at <b>$sql_file_path</b>.<br  /> Creating tables...";
				include_once XOOPS_ROOT_PATH.'/class/database/drivers/'.XOOPS_DB_TYPE.'/sqlutility.php';
				$sql_query = fread(fopen($sql_file_path, 'r'), filesize($sql_file_path));
				$sql_query = trim($sql_query);
				SqlUtility::splitSqlFile($pieces, $sql_query);
				$created_tables = array();
				foreach ($pieces as $piece) {
					// [0] contains the prefixed query
					// [4] contains unprefixed table name
					$prefixed_query = SqlUtility::prefixQuery($piece, $db->prefix());
					if (!$prefixed_query) {
						$errs[] = "<b>$piece</b> is not a valid SQL!";
						$error = true;
						break;
					}
					// check if the table name is reserved
					if (!in_array($prefixed_query[4], $reservedTables)) {
						// not reserved, so try to create one
						if (!$db->query($prefixed_query[0])) {
							$errs[] = $db->error();
							$error = true;
							break;
						} else {

							if (!in_array($prefixed_query[4], $created_tables)) {
								$msgs[] = '&nbsp;&nbsp;Table <b>'.$db->prefix($prefixed_query[4]).'</b> created.';
								$created_tables[] = $prefixed_query[4];
							} else {
								$msgs[] = '&nbsp;&nbsp;Data inserted to table <b>'.$db->prefix($prefixed_query[4]).'</b>.';
							}
						}
					} else {
						// the table name is reserved, so halt the installation
						$errs[] = '<b>'.$prefixed_query[4]."</b> is a reserved table!";
						$error = true;
						break;
					}
				}

				// if there was an error, delete the tables created so far, so the next installation will not fail
				if ($error == true) {
					foreach ($created_tables as $ct) {
						//echo $ct;
						$db->query("DROP TABLE ".$db->prefix($ct));
					}
				}
			}
		}

		// if no error, save the module info and blocks info associated with it
		if ($error == false) {
			if (!$module_handler->insert($module)) {
				$errs[] = 'Could not insert <b>'.$module->getVar('name').'</b> to database.';
				foreach ($created_tables as $ct) {
					$db->query("DROP TABLE ".$db->prefix($ct));
				}
				$ret = "<p>".sprintf(_MD_AM_FAILINS, "<b>".$module->name()."</b>")."&nbsp;"._MD_AM_ERRORSC."<br />";
				foreach ( $errs as $err ) {
					$ret .= " - ".$err."<br />";
				}
				$ret .= "</p>";
				unset($module);
				unset($created_tables);
				unset($errs);
				unset($msgs);
				return $ret;
			} else {
				$newmid = $module->getVar('mid');
				unset($created_tables);
				$msgs[] = 'Module data inserted successfully. Module ID: <b>'.icms_conv_nr2local($newmid).'</b>';
				$tplfile_handler =& xoops_gethandler('tplfile');
				$templates = $module->getInfo('templates');
				if ($templates != false) {
					$msgs[] = 'Adding templates...';
					foreach ($templates as $tpl) {
						$tplfile =& $tplfile_handler->create();
						$tpldata =& xoops_module_gettemplate($dirname, $tpl['file']);
						$tplfile->setVar('tpl_source', $tpldata, true);
						$tplfile->setVar('tpl_refid', $newmid);

						$tplfile->setVar('tpl_tplset', 'default');
						$tplfile->setVar('tpl_file', $tpl['file']);
						$tplfile->setVar('tpl_desc', $tpl['description'], true);
						$tplfile->setVar('tpl_module', $dirname);
						$tplfile->setVar('tpl_lastmodified', time());
						$tplfile->setVar('tpl_lastimported', 0);
						$tplfile->setVar('tpl_type', 'module');
						if (!$tplfile_handler->insert($tplfile)) {
							$msgs[] = sprintf('&nbsp;&nbsp;<span style="color:#ff0000;">'._MD_AM_FAILINSTEMPFILE.'</span>', $tpl['file']);
						} else {
							$newtplid = $tplfile->getVar('tpl_id');
							$msgs[] = sprintf('&nbsp;&nbsp;'._MD_AM_INSTEMPFILE, $tpl['file'], $newtplid);

							// generate compiled file
							include_once XOOPS_ROOT_PATH.'/class/template.php';
							if (!xoops_template_touch($newtplid)) {
								$msgs[] = sprintf('&nbsp;&nbsp;<span style="color:#ff0000;">'._MD_AM_FAILCOMPTEMPFILE.'</span>', $tpl['file']);
							} else {
								$msgs[] = sprintf('&nbsp;&nbsp;'._MD_AM_COMPTEMPFILE, $tpl['file']);
							}
						}
						unset($tpldata);
					}
				}
				include_once XOOPS_ROOT_PATH.'/class/template.php';
				xoops_template_clear_module_cache($newmid);
				$blocks = $module->getInfo('blocks');
				if ($blocks != false) {
					$msgs[] = 'Adding blocks...';
					foreach ($blocks as $blockkey => $block) {
						// break the loop if missing block config
						if (!isset($block['file']) || !isset($block['show_func'])) {
							break;
						}
						$options = '';
						if (!empty($block['options'])) {
							$options = trim($block['options']);
						}
						$newbid = $db->genId($db->prefix('newblocks').'_bid_seq');
						$edit_func = isset($block['edit_func']) ? trim($block['edit_func']) : '';
						$template = '';
						if ((isset($block['template']) && trim($block['template']) != '')) {
							$content =& xoops_module_gettemplate($dirname, $block['template'], true);
						}
						if (empty($content)) {
							$content = '';
						} else {
							$template = trim($block['template']);
						}
						$block_name = addslashes(trim($block['name']));
						$sql = "INSERT INTO ".$db->prefix("newblocks")." (bid, mid, func_num, options, name, title, content, side, weight, visible, block_type, c_type, isactive, dirname, func_file, show_func, edit_func, template, bcachetime, last_modified) VALUES ('".intval($newbid)."', '".intval($newmid)."', '".intval($blockkey)."', '$options', '".$block_name."','".$block_name."', '', '1', '0', '0', 'M', 'H', '1', '".addslashes($dirname)."', '".addslashes(trim($block['file']))."', '".addslashes(trim($block['show_func']))."', '".addslashes($edit_func)."', '".$template."', '0', '".time()."')";
						if (!$db->query($sql)) {
							$msgs[] = '&nbsp;&nbsp;<span style="color:#ff0000;">ERROR: Could not add block <b>'.$block['name'].'</b> to the database! Database error: <b>'.$db->error().'</b></span>';
						} else {
							if (empty($newbid)) {
								$newbid = $db->getInsertId();
							}
							$msgs[] = '&nbsp;&nbsp;Block <b>'.$block['name'].'</b> added. Block ID: <b>'.icms_conv_nr2local($newbid).'</b>';
							$sql = 'INSERT INTO '.$db->prefix('block_module_link').' (block_id, module_id,page_id) VALUES ('.intval($newbid).', 0,1)';
							$db->query($sql);
							if ($template != '') {
								$tplfile =& $tplfile_handler->create();
								$tplfile->setVar('tpl_refid', $newbid);
								$tplfile->setVar('tpl_source', $content, true);
								$tplfile->setVar('tpl_tplset', 'default');
								$tplfile->setVar('tpl_file', $block['template']);
								$tplfile->setVar('tpl_module', $dirname);
								$tplfile->setVar('tpl_type', 'block');
								$tplfile->setVar('tpl_desc', $block['description'], true);
								$tplfile->setVar('tpl_lastimported', 0);
								$tplfile->setVar('tpl_lastmodified', time());
								if (!$tplfile_handler->insert($tplfile)) {
									$msgs[] = sprintf('&nbsp;&nbsp;<span style="color:#ff0000;">'._MD_AM_FAILINSTEMP.'</span>', $block['template']);
								} else {
									$newtplid = $tplfile->getVar('tpl_id');
									$msgs[] = '&nbsp;&nbsp;Template <b>'.$block['template'].'</b> added to the database. (ID: <b>'.icms_conv_nr2local($newtplid).'</b>)';
									// generate compiled file
									include_once XOOPS_ROOT_PATH.'/class/template.php';
									if (!xoops_template_touch($newtplid)) {
										$msgs[] = '&nbsp;&nbsp;<span style="color:#ff0000;">ERROR: Failed compiling template <b>'.$block['template'].'</b>.</span>';
									} else {
										$msgs[] = '&nbsp;&nbsp;Template <b>'.$block['template'].'</b> compiled.</span>';
									}
								}
							}
						}
						unset($content);
					}
					unset($blocks);
				}
				$configs = $module->getInfo('config');
				if ($configs != false) {
					if ($module->getVar('hascomments') != 0) {
						include_once(XOOPS_ROOT_PATH.'/include/comment_constants.php');
						array_push($configs, array('name' => 'com_rule', 'title' => '_CM_COMRULES', 'description' => '', 'formtype' => 'select', 'valuetype' => 'int', 'default' => 1, 'options' => array('_CM_COMNOCOM' => XOOPS_COMMENT_APPROVENONE, '_CM_COMAPPROVEALL' => XOOPS_COMMENT_APPROVEALL, '_CM_COMAPPROVEUSER' => XOOPS_COMMENT_APPROVEUSER, '_CM_COMAPPROVEADMIN' => XOOPS_COMMENT_APPROVEADMIN)));
						array_push($configs, array('name' => 'com_anonpost', 'title' => '_CM_COMANONPOST', 'description' => '', 'formtype' => 'yesno', 'valuetype' => 'int', 'default' => 0));
					}
				} else {
					if ($module->getVar('hascomments') != 0) {
						$configs = array();
						include_once(XOOPS_ROOT_PATH.'/include/comment_constants.php');
						$configs[] = array('name' => 'com_rule', 'title' => '_CM_COMRULES', 'description' => '', 'formtype' => 'select', 'valuetype' => 'int', 'default' => 1, 'options' => array('_CM_COMNOCOM' => XOOPS_COMMENT_APPROVENONE, '_CM_COMAPPROVEALL' => XOOPS_COMMENT_APPROVEALL, '_CM_COMAPPROVEUSER' => XOOPS_COMMENT_APPROVEUSER, '_CM_COMAPPROVEADMIN' => XOOPS_COMMENT_APPROVEADMIN));
						$configs[] = array('name' => 'com_anonpost', 'title' => '_CM_COMANONPOST', 'description' => '', 'formtype' => 'yesno', 'valuetype' => 'int', 'default' => 0);
					}
				}

				// RMV-NOTIFY
				if ($module->getVar('hasnotification') != 0) {
					if (empty($configs)) {
						$configs = array();
					}
					// Main notification options
					include_once XOOPS_ROOT_PATH . '/include/notification_constants.php';
					include_once XOOPS_ROOT_PATH . '/include/notification_functions.php';
					$options = array();
					$options['_NOT_CONFIG_DISABLE'] = XOOPS_NOTIFICATION_DISABLE;
					$options['_NOT_CONFIG_ENABLEBLOCK'] = XOOPS_NOTIFICATION_ENABLEBLOCK;
					$options['_NOT_CONFIG_ENABLEINLINE'] = XOOPS_NOTIFICATION_ENABLEINLINE;
					$options['_NOT_CONFIG_ENABLEBOTH'] = XOOPS_NOTIFICATION_ENABLEBOTH;

					//$configs[] = array ('name' => 'notification_enabled', 'title' => '_NOT_CONFIG_ENABLED', 'description' => '_NOT_CONFIG_ENABLEDDSC', 'formtype' => 'yesno', 'valuetype' => 'int', 'default' => 1);
					$configs[] = array ('name' => 'notification_enabled', 'title' => '_NOT_CONFIG_ENABLE', 'description' => '_NOT_CONFIG_ENABLEDSC', 'formtype' => 'select', 'valuetype' => 'int', 'default' => XOOPS_NOTIFICATION_ENABLEBOTH, 'options' => $options);
					// Event-specific notification options
					// FIXME: doesn't work when update module... can't read back the array of options properly...  " changing to &quot;
					$options = array();
					$categories =& notificationCategoryInfo('',$module->getVar('mid'));
					foreach ($categories as $category) {
						$events =& notificationEvents ($category['name'], false, $module->getVar('mid'));
						foreach ($events as $event) {
							if (!empty($event['invisible'])) {
								continue;
							}
							$option_name = $category['title'] . ' : ' . $event['title'];
							$option_value = $category['name'] . '-' . $event['name'];
							$options[$option_name] = $option_value;
						}
					}
					$configs[] = array ('name' => 'notification_events', 'title' => '_NOT_CONFIG_EVENTS', 'description' => '_NOT_CONFIG_EVENTSDSC', 'formtype' => 'select_multi', 'valuetype' => 'array', 'default' => array_values($options), 'options' => $options);
				}

				if ($configs != false) {
					$msgs[] = 'Adding module config data...';
					$config_handler =& xoops_gethandler('config');
					$order = 0;
					foreach ($configs as $config) {
						$confobj =& $config_handler->createConfig();
						$confobj->setVar('conf_modid', $newmid);
						$confobj->setVar('conf_catid', 0);
						$confobj->setVar('conf_name', $config['name']);
						$confobj->setVar('conf_title', $config['title'], true);
						$confobj->setVar('conf_desc', $config['description'], true);
						$confobj->setVar('conf_formtype', $config['formtype']);
						$confobj->setVar('conf_valuetype', $config['valuetype']);
						$confobj->setConfValueForInput($config['default'], true);
						//$confobj->setVar('conf_value', $config['default'], true);
						$confobj->setVar('conf_order', $order);
						$confop_msgs = '';
						if (isset($config['options']) && is_array($config['options'])) {
							foreach ($config['options'] as $key => $value) {
								$confop =& $config_handler->createConfigOption();
								$confop->setVar('confop_name', $key, true);
								$confop->setVar('confop_value', $value, true);
								$confobj->setConfOptions($confop);
								$confop_msgs .= '<br />&nbsp;&nbsp;&nbsp;&nbsp;Config option added. Name: <b>'.$key.'</b> Value: <b>'.$value.'</b>';
								unset($confop);
							}
						}
						$order++;
						if ($config_handler->insertConfig($confobj) != false) {
							$msgs[] = '&nbsp;&nbsp;Config <b>'.$config['name'].'</b> added to the database.'.$confop_msgs;
						} else {
							$msgs[] = '&nbsp;&nbsp;<span style="color:#ff0000;">ERROR: Could not insert config <b>'.$config['name'].'</b> to the database.</span>';
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
			$icms_block_handler = xoops_gethandler('block');
			$blocks =& $icms_block_handler->getByModule($newmid, false);
			$msgs[] = 'Setting group rights...';
			$gperm_handler =& xoops_gethandler('groupperm');
			foreach ($groups as $mygroup) {
				if ($gperm_handler->checkRight('module_admin', 0, $mygroup)) {
					$mperm =& $gperm_handler->create();
					$mperm->setVar('gperm_groupid', $mygroup);
					$mperm->setVar('gperm_itemid', $newmid);
					$mperm->setVar('gperm_name', 'module_admin');
					$mperm->setVar('gperm_modid', 1);
					if (!$gperm_handler->insert($mperm)) {
						$msgs[] = '&nbsp;&nbsp;<span style="color:#ff0000;">ERROR: Could not add admin access right for Group ID <b>'.icms_conv_nr2local($mygroup).'</b></span>';
					} else {
						$msgs[] = '&nbsp;&nbsp;Added admin access right for Group ID <b>'.icms_conv_nr2local($mygroup).'</b>';
					}
					unset($mperm);
				}
				$mperm =& $gperm_handler->create();
				$mperm->setVar('gperm_groupid', $mygroup);
				$mperm->setVar('gperm_itemid', $newmid);
				$mperm->setVar('gperm_name', 'module_read');
				$mperm->setVar('gperm_modid', 1);
				if (!$gperm_handler->insert($mperm)) {
					$msgs[] = '&nbsp;&nbsp;<span style="color:#ff0000;">ERROR: Could not add user access right for Group ID: <b>'.icms_conv_nr2local($mygroup).'</b></span>';
				} else {
					$msgs[] = '&nbsp;&nbsp;Added user access right for Group ID: <b>'.icms_conv_nr2local($mygroup).'</b>';
				}
				unset($mperm);
				foreach ($blocks as $blc) {
					$bperm =& $gperm_handler->create();
					$bperm->setVar('gperm_groupid', $mygroup);
					$bperm->setVar('gperm_itemid', $blc);
					$bperm->setVar('gperm_name', 'block_read');
					$bperm->setVar('gperm_modid', 1);
					if (!$gperm_handler->insert($bperm)) {
						$msgs[] = '&nbsp;&nbsp;<span style="color:#ff0000;">ERROR: Could not add block access right. Block ID: <b>'.icms_conv_nr2local($blc).'</b> Group ID: <b>'.icms_conv_nr2local($mygroup).'</b></span>';
					} else {
						$msgs[] = '&nbsp;&nbsp;Added block access right. Block ID: <b>'.icms_conv_nr2local($blc).'</b> Group ID: <b>'.icms_conv_nr2local($mygroup).'</b>';
					}
					unset($bperm);
				}
			}
			unset($blocks);
			unset($groups);

			// add module specific tasks to system autotasks list
			$atasks = $module->getInfo('autotasks');
			if (isset($atasks) && is_array($atasks) && (count($atasks) > 0)) {
				$atasks_handler = &xoops_getModuleHandler('autotasks', 'system');
				foreach ($atasks as $taskID => $taskData) {
					$task = &$atasks_handler->create();
					if (isset($taskData['enabled'])) $task->setVar('sat_enabled', $taskData['enabled']);
					if (isset($taskData['repeat'])) $task->setVar('sat_repeat', $taskData['repeat']);
					if (isset($taskData['interval'])) $task->setVar('sat_interval', $taskData['interval']);
					if (isset($taskData['onfinish'])) $task->setVar('sat_onfinish', $taskData['onfinish']);
					$task->setVar('sat_name', $taskData['name']);
					$task->setVar('sat_code', $taskData['code']);
					$task->setVar('sat_type', 'addon/'.$module->getInfo('dirname'));
					$task->setVar('sat_addon_id', intval($taskID));
					if (!($atasks_handler->insert($task))) {
						$msgs[] = '&nbsp;&nbsp;<span style="color:#ff0000;">ERROR: Could not insert autotask to db. Name: <b>'.$taskData['name'].'</b></span>';
					} else {
						$msgs[] = '&nbsp;&nbsp;Added task to autotasks list. Task Name: <b>'.$taskData['name'].'</b>';
					}
				}
				unset($atasks_handler, $task, $taskData, $criteria, $items, $taskID);
			}
			unset($atasks);

			// execute module specific install script if any
			$install_script = $module->getInfo('onInstall');
			$ModName = ($module->getInfo('modname') != '') ? trim($module->getInfo('modname')) : $dirname;
			if (false != $install_script && trim($install_script) != '') {
				include_once XOOPS_ROOT_PATH.'/modules/'.$dirname.'/'.trim($install_script);

				$is_IPF = $module->getInfo('object_items');
				if(!empty($is_IPF)){
					$icmsDatabaseUpdater = XoopsDatabaseFactory::getDatabaseUpdater();
					$icmsDatabaseUpdater->moduleUpgrade($module, true);
					foreach ($icmsDatabaseUpdater->_messages as $msg) {
						$msgs[] = $msg;
					}
				}

				if (function_exists('xoops_module_install_'.$ModName)) {
					$func = 'xoops_module_install_'.$ModName;
					if ( !( $lastmsg = $func($module) ) ) {
						$msgs[] = sprintf(_MD_AM_FAIL_EXEC, $func);
					} else {
						$msgs[] = $module->messages;
						$msgs[] = sprintf(_MD_AM_FUNCT_EXEC, $func);
						if ( is_string( $lastmsg ) ) {
							$msgs[] = $lastmsg;
						}
					}
				}elseif (function_exists('icms_module_install_'.$ModName)) {
					$func = 'icms_module_install_'.$ModName;
					if ( !( $lastmsg = $func($module) ) ) {
						$msgs[] = sprintf(_MD_AM_FAIL_EXEC, $func);
					} else {
						$msgs[] = $module->messages;
						$msgs[] = sprintf(_MD_AM_FUNCT_EXEC, $func);
						if ( is_string( $lastmsg ) ) {
							$msgs[] = $lastmsg;
						}
					}
				}
			}

			$ret = '<p><code>';
			foreach ($msgs as $m) {
				$ret .= $m.'<br />';
			}
			unset($msgs);
			unset($errs);
			$ret .= '</code><br />'.sprintf(_MD_AM_OKINS, "<b>".$module->getVar('name')."</b>").'</p>';
			unset($module);
			return $ret;
		} else {
			$ret = '<p>';
			foreach ($errs as $er) {
				$ret .= '&nbsp;&nbsp;'.$er.'<br />';
			}
			unset($msgs);
			unset($errs);
			$ret .= '<br />'.sprintf(_MD_AM_FAILINS, '<b>'.$dirname.'</b>').'&nbsp;'._MD_AM_ERRORSC.'</p>';
			return $ret;
		}
	}
	else {
		return "<p>".sprintf(_MD_AM_FAILINS, "<b>".$dirname."</b>")."&nbsp;"._MD_AM_ERRORSC."<br />&nbsp;&nbsp;".sprintf(_MD_AM_ALEXISTS, $dirname)."</p>";
	}
}

function &xoops_module_gettemplate($dirname, $template, $block=false) {
	global $xoopsConfig;
	$ret = '';
	if ($block) {
		$path = XOOPS_ROOT_PATH.'/modules/'.$dirname.'/templates/blocks/'.$template;
	} else {
		$path = XOOPS_ROOT_PATH.'/modules/'.$dirname.'/templates/'.$template;
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

function xoops_module_uninstall($dirname) {
	global $xoopsConfig;
	$reservedTables = array('avatar', 'avatar_users_link', 'block_module_link', 'xoopscomments', 'config', 'configcategory', 'configoption', 'image', 'imagebody', 'imagecategory', 'imgset', 'imgset_tplset_link', 'imgsetimg', 'groups','groups_users_link','group_permission', 'online', 'bannerclient', 'banner', 'bannerfinish', 'priv_msgs', 'ranks', 'session', 'smiles', 'users', 'newblocks', 'modules', 'tplfile', 'tplset', 'tplsource', 'xoopsnotifications', 'banner', 'bannerclient', 'bannerfinish');
	$db =& Database::getInstance();
	$module_handler =& xoops_gethandler('module');
	$module =& $module_handler->getByDirname($dirname);
	include_once XOOPS_ROOT_PATH.'/class/template.php';
	xoops_template_clear_module_cache($module->getVar('mid'));
	if ($module->getVar('dirname') == 'system') {
		return "<p>".sprintf(_MD_AM_FAILUNINS, "<b>".$module->getVar('name')."</b>")."&nbsp;"._MD_AM_ERRORSC."<br /> - "._MD_AM_SYSNO."</p>";
	} elseif ($module->getVar('dirname') == $xoopsConfig['startpage']) {
		return "<p>".sprintf(_MD_AM_FAILUNINS, "<b>".$module->getVar('name')."</b>")."&nbsp;"._MD_AM_ERRORSC."<br /> - "._MD_AM_STRTNO."</p>";
	} else {
		$msgs = array();

		$member_handler = & xoops_gethandler ( 'member' );
		$grps = $member_handler->getGroupList ();
		foreach ( $grps as $k => $v ) {
			$stararr = explode('-',$xoopsConfig['startpage'][$k]);
			if (count($stararr) > 0){
				if ($module->getVar('mid') == $stararr[0]){
					return "<p>".sprintf(_MD_AM_FAILDEACT, "<b>".$module->getVar('name')."</b>")."&nbsp;"._MD_AM_ERRORSC."<br /> - "._MD_AM_STRTNO."</p>";
				}
			}
		}
		if (in_array($module->getVar('dirname'), $xoopsConfig ['startpage'])){
			return "<p>".sprintf(_MD_AM_FAILDEACT, "<b>".$module->getVar('name')."</b>")."&nbsp;"._MD_AM_ERRORSC."<br /> - "._MD_AM_STRTNO."</p>";
		}

		$page_handler = xoops_gethandler('page');
		$criteria = new CriteriaCompo(new Criteria('page_moduleid', $module->getVar('mid')));
		$pages = $page_handler->getCount($criteria);

		if ($pages > 0){
			$pages = $page_handler->getObjects($criteria);
			$msgs[] = 'Deleting links fom Symlink Manager...';
			foreach ($pages as $page){
				if (!$page_handler->delete($page)) {
					$msgs[] = '&nbsp;&nbsp;<span style="color:#ff0000;">ERROR: Could not delete link '.$page->getVar('page_title').' from the database. Link ID: <b>'.$page->getVar('page_id').'</b></span>';
				} else {
					$msgs[] = '&nbsp;&nbsp;Link <b>'.$page->getVar('page_title').'</b> deleted from the database. Link ID: <b>'.$page->getVar('page_id').'</b>';
				}
			}
		}

		if (!$module_handler->delete($module)) {
			$msgs[] = '&nbsp;&nbsp;<span style="color:#ff0000;">ERROR: Could not delete '.$module->getVar('name').'</span>';
		} else {

			// delete template files
			$tplfile_handler = xoops_gethandler('tplfile');
			$templates =& $tplfile_handler->find(null, 'module', $module->getVar('mid'));
			$tcount = count($templates);
			if ($tcount > 0) {
				$msgs[] = 'Deleting templates...';
				for ($i = 0; $i < $tcount; $i++) {
					if (!$tplfile_handler->delete($templates[$i])) {
						$msgs[] = '&nbsp;&nbsp;<span style="color:#ff0000;">ERROR: Could not delete template '.$templates[$i]->getVar('tpl_file').' from the database. Template ID: <b>'.icms_conv_nr2local($templates[$i]->getVar('tpl_id')).'</b></span>';
					} else {
						$msgs[] = '&nbsp;&nbsp;Template <b>'.icms_conv_nr2local($templates[$i]->getVar('tpl_file')).'</b> deleted from the database. Template ID: <b>'.icms_conv_nr2local($templates[$i]->getVar('tpl_id')).'</b>';
					}
				}
			}
			unset($templates);

			// delete blocks and block tempalte files
			$icms_block_handler = xoops_gethandler('block');
			$block_arr =& $icms_block_handler->getByModule($module->getVar('mid'));
			if (is_array($block_arr)) {
				$bcount = count($block_arr);
				$msgs[] = 'Deleting block...';
				for ($i = 0; $i < $bcount; $i++) {
					if (!$block_arr[$i]->delete()) {
						$msgs[] = '&nbsp;&nbsp;<span style="color:#ff0000;">ERROR: Could not delete block <b>'.$block_arr[$i]->getVar('name').'</b> Block ID: <b>'.icms_conv_nr2local($block_arr[$i]->getVar('bid')).'</b></span>';
					} else {
						$msgs[] = '&nbsp;&nbsp;Block <b>'.$block_arr[$i]->getVar('name').'</b> deleted. Block ID: <b>'.icms_conv_nr2local($block_arr[$i]->getVar('bid')).'</b>';
					}
					if ($block_arr[$i]->getVar('template') != ''){
						$templates =& $tplfile_handler->find(null, 'block', $block_arr[$i]->getVar('bid'));
						$btcount = count($templates);
						if ($btcount > 0) {
							for ($j = 0; $j < $btcount; $j++) {
								if (!$tplfile_handler->delete($templates[$j])) {
								$msgs[] = '&nbsp;&nbsp;<span style="color:#ff0000;">ERROR: Could not delete block template '.$templates[$j]->getVar('tpl_file').' from the database. Template ID: <b>'.icms_conv_nr2local($templates[$j]->getVar('tpl_id')).'</b></span>';
								} else {
								$msgs[] = '&nbsp;&nbsp;Block template <b>'.$templates[$j]->getVar('tpl_file').'</b> deleted from the database. Template ID: <b>'.icms_conv_nr2local($templates[$j]->getVar('tpl_id')).'</b>';
								}
							}
						}
						unset($templates);
					}
				}
			}

			// delete tables used by this module
			$modtables = $module->getInfo('tables');
			if ($modtables != false && is_array($modtables)) {
				$msgs[] = 'Deleting module tables...';
				foreach ($modtables as $table) {
					// prevent deletion of reserved core tables!
					if (!in_array($table, $reservedTables)) {
						$sql = 'DROP TABLE '.$db->prefix($table);
						if (!$db->query($sql)) {
							$msgs[] = '&nbsp;&nbsp;<span style="color:#ff0000;">ERROR: Could not drop table <b>'.$db->prefix($table).'<b>.</span>';
						} else {
							$msgs[] = '&nbsp;&nbsp;Table <b>'.$db->prefix($table).'</b> dropped.</span>';
						}
					} else {
						$msgs[] = '&nbsp;&nbsp;<span style="color:#ff0000;">ERROR: Not allowed to drop table <b>'.$db->prefix($table).'</b>!</span>';
					}
				}
			}

			// delete comments if any
			if ($module->getVar('hascomments') != 0) {
				$msgs[] = 'Deleting comments...';
				$comment_handler =& xoops_gethandler('comment');
				if (!$comment_handler->deleteByModule($module->getVar('mid'))) {
					$msgs[] = '&nbsp;&nbsp;<span style="color:#ff0000;">ERROR: Could not delete comments</span>';
				} else {
					$msgs[] = '&nbsp;&nbsp;Comments deleted';
				}
			}

			// RMV-NOTIFY
			// delete notifications if any
			if ($module->getVar('hasnotification') != 0) {
				$msgs[] = 'Deleting notifications...';
				if (!xoops_notification_deletebymodule($module->getVar('mid'))) {
					$msgs[] = '&nbsp;&nbsp;<span style="color:#ff0000;">ERROR: Could not delete notifications</span>';
				} else {
					$msgs[] = '&nbsp;&nbsp;Notifications deleted';
				}
			}

			// delete permissions if any
			$gperm_handler =& xoops_gethandler('groupperm');
			if (!$gperm_handler->deleteByModule($module->getVar('mid'))) {
				$msgs[] = '&nbsp;&nbsp;<span style="color:#ff0000;">ERROR: Could not delete group permissions</span>';
			} else {
				$msgs[] = '&nbsp;&nbsp;Group permissions deleted';
			}

			// delete module config options if any
			if ($module->getVar('hasconfig') != 0 || $module->getVar('hascomments') != 0) {
				$config_handler =& xoops_gethandler('config');
				$configs =& $config_handler->getConfigs(new Criteria('conf_modid', $module->getVar('mid')));
				$confcount = count($configs);
				if ($confcount > 0) {
					$msgs[] = 'Deleting module config options...';
					for ($i = 0; $i < $confcount; $i++) {
						if (!$config_handler->deleteConfig($configs[$i])) {
							$msgs[] = '&nbsp;&nbsp;<span style="color:#ff0000;">ERROR: Could not delete config data from the database. Config ID: <b>'.icms_conv_nr2local($configs[$i]->getvar('conf_id')).'</b></span>';
						} else {
							$msgs[] = '&nbsp;&nbsp;Config data deleted from the database. Config ID: <b>'.icms_conv_nr2local($configs[$i]->getVar('conf_id')).'</b>';
						}
					}
				}
			}

			$atasks = $module->getInfo('autotasks');
			if (isset($atasks) && is_array($atasks) && (count($atasks) > 0)) {
				$msgs[] = 'Deleting autotasks...';
				$atasks_handler = &xoops_getModuleHandler('autotasks', 'system');
				$criteria = new CriteriaCompo();
				$criteria->add( new Criteria( 'sat_type', 'addon/'.$module->getInfo('dirname') ) );
				$atasks_handler->deleteAll($criteria);
				unset($atasks_handler,$criteria,$taskData);
			}
			unset($atasks);

			// execute module specific install script if any
			$uninstall_script = $module->getInfo('onUninstall');
			$ModName = ($module->getInfo('modname') != '') ? trim($module->getInfo('modname')) : $dirname;
			if (false != $uninstall_script && trim($uninstall_script) != '') {
				include_once XOOPS_ROOT_PATH.'/modules/'.$dirname.'/'.trim($uninstall_script);
				if (function_exists('xoops_module_uninstall_'.$ModName)) {
					$func = 'xoops_module_uninstall_'.$ModName;
					if (!$func($module)) {
						$msgs[] = 'Failed to execute <b>'.$func.'</b>';
					} else {
						$msgs[] = $module->messages;
						$msgs[] = sprintf(_MD_AM_FUNCT_EXEC, $func);
					}
				}elseif (function_exists('icms_module_uninstall_'.$ModName)) {
					$func = 'icms_module_uninstall_'.$ModName;
					if (!$func($module)) {
						$msgs[] = 'Failed to execute <b>'.$func.'</b>';
					} else {
						$msgs[] = $module->messages;
						$msgs[] = sprintf(_MD_AM_FUNCT_EXEC, $func);
					}
				}

			}

			$msgs[] = '</code><p>'.sprintf(_MD_AM_OKUNINS, "<b>".$module->getVar('name')."</b>").'</p>';
		}
		$ret = '<code>';
		foreach ($msgs as $msg) {
			$ret .= $msg.'<br />';
		}
		return $ret;
	}
}

function xoops_module_activate($mid) {
	global $icms_block_handler;
	$module_handler =& xoops_gethandler('module');
	$module =& $module_handler->get($mid);
	include_once XOOPS_ROOT_PATH.'/class/template.php';
	xoops_template_clear_module_cache($module->getVar('mid'));
	$module->setVar('isactive', 1);
	if (!$module_handler->insert($module)) {
			$ret = "<p>".sprintf(_MD_AM_FAILACT, "<b>".$module->getVar('name')."</b>")."&nbsp;"._MD_AM_ERRORSC."<br />".$module->getHtmlErrors();
			return $ret."</p>";
	}
	$icms_block_handler = xoops_getmodulehandler ( 'blocksadmin', 'system' );
	$blocks =& $icms_block_handler->getByModule($module->getVar('mid'));
	$bcount = count($blocks);
	for ($i = 0; $i < $bcount; $i++) {
			$blocks[$i]->setVar('isactive', 1);
			$blocks[$i]->store();
	}
	return "<p>".sprintf(_MD_AM_OKACT, "<b>".$module->getVar('name')."</b>")."</p>";
}

function xoops_module_deactivate($mid) {
	global $icms_page_handler, $icms_block_handler, $xoopsConfig;
	if(!isset($icms_page_handler)){
	   $icms_page_handler = xoops_getmodulehandler ( 'pages', 'system' );
	}

	$module_handler =& xoops_gethandler('module');
	$module =& $module_handler->get($mid);
	include_once XOOPS_ROOT_PATH.'/class/template.php';
	xoops_template_clear_module_cache($mid);
	$module->setVar('isactive', 0);
	if ($module->getVar('dirname') == "system") {
		return "<p>".sprintf(_MD_AM_FAILDEACT, "<b>".$module->getVar('name')."</b>")."&nbsp;"._MD_AM_ERRORSC."<br /> - "._MD_AM_SYSNO."</p>";
	} elseif ($module->getVar('dirname') == $xoopsConfig['startpage']) {
		return "<p>".sprintf(_MD_AM_FAILDEACT, "<b>".$module->getVar('name')."</b>")."&nbsp;"._MD_AM_ERRORSC."<br /> - "._MD_AM_STRTNO."</p>";
	} else {
		$member_handler = & xoops_gethandler ( 'member' );
		$grps = $member_handler->getGroupList ();
		foreach ( $grps as $k => $v ) {
			$stararr = explode('-',$xoopsConfig['startpage'][$k]);
			if (count($stararr) > 0){
				if ($module->getVar('mid') == $stararr[0]){
					return "<p>".sprintf(_MD_AM_FAILDEACT, "<b>".$module->getVar('name')."</b>")."&nbsp;"._MD_AM_ERRORSC."<br /> - "._MD_AM_STRTNO."</p>";
				}
			}
		}
		if (in_array($module->getVar('dirname'), $xoopsConfig ['startpage'])){
			return "<p>".sprintf(_MD_AM_FAILDEACT, "<b>".$module->getVar('name')."</b>")."&nbsp;"._MD_AM_ERRORSC."<br /> - "._MD_AM_STRTNO."</p>";
		}
		if (!$module_handler->insert($module)) {
			$ret = "<p>".sprintf(_MD_AM_FAILDEACT, "<b>".$module->getVar('name')."</b>")."&nbsp;"._MD_AM_ERRORSC."<br />".$module->getHtmlErrors();
			return $ret."</p>";
		}

		$icms_block_handler = xoops_getmodulehandler ( 'blocksadmin', 'system' );
		$blocks =& $icms_block_handler->getByModule($module->getVar('mid'));
		$bcount = count($blocks);
		for ($i = 0; $i < $bcount; $i++) {
			$blocks[$i]->setVar('isactive', false);
			$blocks[$i]->store();
		}
		return "<p>".sprintf(_MD_AM_OKDEACT, "<b>".$module->getVar('name')."</b>")."</p>";
	}
}

function xoops_module_change($mid, $weight, $name) {
	$module_handler =& xoops_gethandler('module');
	$module =& $module_handler->get($mid);
	$module->setVar('weight', $weight);
	$module->setVar('name', $name);
	$myts =& MyTextSanitizer::getInstance();
	if (!$module_handler->insert($module)) {
		$ret = "<p>".sprintf(_MD_AM_FAILORDER, "<b>".$myts->stripSlashesGPC($name)."</b>")."&nbsp;"._MD_AM_ERRORSC."<br />";
		$ret .= $module->getHtmlErrors()."</p>";
		return $ret;
	}
	return "<p>".sprintf(_MD_AM_OKORDER, "<b>".$myts->stripSlashesGPC($name)."</b>")."</p>";
}

function icms_module_update($dirname) {
	global $icmsUser, $xoopsConfig, $xoopsDB;
	$dirname = trim($dirname);
	$module_handler =& xoops_gethandler('module');
	$module =& $module_handler->getByDirname($dirname);

	// Save current version for use in the update function
	$prev_version = $module->getVar('version');
	$prev_dbversion = $module->getVar('dbversion');
	include_once XOOPS_ROOT_PATH.'/class/template.php';
	xoops_template_clear_module_cache($module->getVar('mid'));
	// we dont want to change the module name set by admin
	$temp_name = $module->getVar('name');
	$module->loadInfoAsVar($dirname);
	$module->setVar('name', $temp_name);
	if (!$module_handler->insert($module)) {
		echo '<p>Could not update '.$module->getVar('name').'</p>';
		echo "<br /><a href='admin.php?fct=modulesadmin'>"._MD_AM_BTOMADMIN."</a>";
	} else {
		$newmid = $module->getVar('mid');
		$msgs = array();
		$msgs[] = _MD_AM_MOD_DATA_UPDATED;
		$tplfile_handler =& xoops_gethandler('tplfile');
		$deltpl =& $tplfile_handler->find('default', 'module', $module->getVar('mid'));
		$delng = array();
		if (is_array($deltpl)) {
			$xoopsDelTpl = new XoopsTpl();
			// clear cache files
			$xoopsDelTpl->clear_cache(null, 'mod_'.$dirname);
			// delete template file entry in db
			$dcount = count($deltpl);
			for ($i = 0; $i < $dcount; $i++) {
				if (!$tplfile_handler->delete($deltpl[$i])) {
					$delng[] = $deltpl[$i]->getVar('tpl_file');
				}
			}
		}

		$templates = $module->getInfo('templates');
		if ($templates != false) {
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
					$tplfile->setVar('tpl_source', $tpldata, true);
					$tplfile->setVar('tpl_module', $dirname);
					$tplfile->setVar('tpl_tplset', 'default');
					$tplfile->setVar('tpl_file', $tpl['file'], true);
					$tplfile->setVar('tpl_desc', $tpl['description'], true);
					if (!$tplfile_handler->insert($tplfile)) {
						$msgs[] = sprintf('&nbsp;&nbsp;<span style="color:#ff0000;">'._MD_AM_FAILINSTEMPFILE.'</span>', $tpl['file']);
					} else {
						$newid = $tplfile->getVar('tpl_id');
						$msgs[] = sprintf('&nbsp;&nbsp;<span>'._MD_AM_TEMPINS.'</span>', $tpl['file']);
						if ($xoopsConfig['template_set'] == 'default') {
							if (!xoops_template_touch($newid)) {
								$msgs[] = sprintf('&nbsp;&nbsp;<span style="color:#ff0000;">'._MD_AM_NOTRECOMPTEMPFILE.'</span>', $tpl['file']);
							} else {
								$msgs[] = sprintf('&nbsp;&nbsp;<span>'._MD_AM_RECOMPTEMPFILE.'</span>', $tpl['file']);
							}
						}
					}
					unset($tpldata);
				} else {
					$msgs[] = sprintf('&nbsp;&nbsp;<span style="color:#ff0000;">'._MD_AM_NOTDELTEMPFILE.'</span>', $tpl['file']);
				}
			}
		}
		$blocks = $module->getInfo('blocks');
		$msgs[] = _MD_AM_MOD_REBUILD_BLOCKS;
		if ($blocks != false) {
			$count = count($blocks);
			$showfuncs = array();
			$funcfiles = array();
			for ( $i = 1; $i <= $count; $i++ ) {
				if (isset($blocks[$i]['show_func']) && $blocks[$i]['show_func'] != '' && isset($blocks[$i]['file']) && $blocks[$i]['file'] != '') {
					$editfunc = isset($blocks[$i]['edit_func']) ? $blocks[$i]['edit_func'] : '';
					$showfuncs[] = $blocks[$i]['show_func'];
					$funcfiles[] = $blocks[$i]['file'];
					$template = '';
					if ((isset($blocks[$i]['template']) && trim($blocks[$i]['template']) != '')) {
						$content =& xoops_module_gettemplate($dirname, $blocks[$i]['template'], true);
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
					$sql = "SELECT bid, name FROM ".$xoopsDB->prefix('newblocks')." WHERE mid='".intval($module->getVar('mid'))."' AND func_num='".intval($i)."' AND show_func='".addslashes($blocks[$i]['show_func'])."' AND func_file='".addslashes($blocks[$i]['file'])."'";
					$fresult = $xoopsDB->query($sql);
					$fcount = 0;
					while ($fblock = $xoopsDB->fetchArray($fresult)) {
						$fcount++;
						$sql = "UPDATE ".$xoopsDB->prefix("newblocks")." SET name='".addslashes($blocks[$i]['name'])."', edit_func='".addslashes($editfunc)."', content='', template='".$template."', last_modified=".time()." WHERE bid='".intval($fblock['bid'])."'";
						$result = $xoopsDB->query($sql);
						if (!$result) {
							$msgs[] = sprintf('&nbsp;&nbsp;'._MD_AM_COULDNOTUPDATE,$fblock['name']);
						} else {
							$msgs[] = sprintf('&nbsp;&nbsp;'._MD_AM_BLOCKUPDATED,$fblock['name'],icms_conv_nr2local($fblock['bid']));
							if ($template != '') {
								$tplfile =& $tplfile_handler->find('default', 'block', $fblock['bid']);
								if (count($tplfile) == 0) {
									$tplfile_new =& $tplfile_handler->create();
									$tplfile_new->setVar('tpl_module', $dirname);
									$tplfile_new->setVar('tpl_refid', intval($fblock['bid']));
									$tplfile_new->setVar('tpl_tplset', 'default');
									$tplfile_new->setVar('tpl_file', $blocks[$i]['template'], true);
									$tplfile_new->setVar('tpl_type', 'block');
								}
								else {
									$tplfile_new = $tplfile[0];
								}
								$tplfile_new->setVar('tpl_source', $content, true);
								$tplfile_new->setVar('tpl_desc', $blocks[$i]['description'], true);
								$tplfile_new->setVar('tpl_lastmodified', time());
								$tplfile_new->setVar('tpl_lastimported', 0);
								if (!$tplfile_handler->insert($tplfile_new)) {
									$msgs[] = sprintf('&nbsp;&nbsp;<span style="color:#ff0000;">'._MD_AM_FAILUPDTEMP.'</span>', $blocks[$i]['template']);
								} else {
									$msgs[] = '&nbsp;&nbsp;Template <b>'.$blocks[$i]['template'].'</b> updated.';
									if ($xoopsConfig['template_set'] == 'default') {
										if (!xoops_template_touch($tplfile_new->getVar('tpl_id'))) {
											$msgs[] = sprintf('&nbsp;&nbsp;<span style="color:#ff0000;">'._MD_AM_NOTRECOMPTEMPFILE.'</span>', $blocks[$i]['template']);
										} else {
											$msgs[] = sprintf('&nbsp;&nbsp;'._MD_AM_RECOMPTEMPFILE, $blocks[$i]['template']);
										}
									}
								}
							}
						}
					}

					if ($fcount == 0) {
						$newbid = $xoopsDB->genId($xoopsDB->prefix('newblocks').'_bid_seq');
						$block_name = addslashes($blocks[$i]['name']);
						/* @todo properly handle the block_type when updating the system module */
						$sql = "INSERT INTO ".$xoopsDB->prefix("newblocks")." (bid, mid, func_num, options, name, title, content, side, weight, visible, block_type, c_type, isactive, dirname, func_file, show_func, edit_func, template, bcachetime, last_modified) VALUES ('".intval($newbid)."', '".intval($module->getVar('mid'))."', '".intval($i)."','".addslashes($options)."','".$block_name."', '".$block_name."', '', '1', '0', '0', 'M', 'H', '1', '".addslashes($dirname)."', '".addslashes($blocks[$i]['file'])."', '".addslashes($blocks[$i]['show_func'])."', '".addslashes($editfunc)."', '".$template."', '0', '".time()."')";
						$result = $xoopsDB->query($sql);
						if (!$result) {
							$msgs[] = '&nbsp;&nbsp;ERROR: Could not create '.$blocks[$i]['name'];echo $sql;
						} else {
							if (empty($newbid)) {
								$newbid = $xoopsDB->getInsertId();
							}
							$groups =& $icmsUser->getGroups();
							$gperm_handler =& xoops_gethandler('groupperm');
							foreach ($groups as $mygroup) {
								$bperm =& $gperm_handler->create();
								$bperm->setVar('gperm_groupid', intval($mygroup));
								$bperm->setVar('gperm_itemid', intval($newbid));
								$bperm->setVar('gperm_name', 'block_read');
								$bperm->setVar('gperm_modid', 1);
								if (!$gperm_handler->insert($bperm)) {
									$msgs[] = '&nbsp;&nbsp;<span style="color:#ff0000;">ERROR: Could not add block access right. Block ID: <b>'.$newbid.'</b> Group ID: <b>'.$mygroup.'</b></span>';
								} else {
									$msgs[] = '&nbsp;&nbsp;Added block access right. Block ID: <b>'.$newbid.'</b> Group ID: <b>'.$mygroup.'</b>';
								}
							}

							if ($template != '') {
								$tplfile =& $tplfile_handler->create();
								$tplfile->setVar('tpl_module', $dirname);
								$tplfile->setVar('tpl_refid', intval($newbid));
								$tplfile->setVar('tpl_source', $content, true);
								$tplfile->setVar('tpl_tplset', 'default');
								$tplfile->setVar('tpl_file', $blocks[$i]['template'], true);
								$tplfile->setVar('tpl_type', 'block');
								$tplfile->setVar('tpl_lastimported', 0);
								$tplfile->setVar('tpl_lastmodified', time());
								$tplfile->setVar('tpl_desc', $blocks[$i]['description'], true);
								if (!$tplfile_handler->insert($tplfile)) {
									$msgs[] = '&nbsp;&nbsp;<span style="color:#ff0000;">ERROR: Could not insert template <b>'.$blocks[$i]['template'].'</b> to the database.</span>';
								} else {
									$newid = $tplfile->getVar('tpl_id');
									$msgs[] = '&nbsp;&nbsp;Template <b>'.$blocks[$i]['template'].'</b> added to the database.';
									if ($xoopsConfig['template_set'] == 'default') {
										if (!xoops_template_touch($newid)) {
											$msgs[] = '&nbsp;&nbsp;<span style="color:#ff0000;">ERROR: Template <b>'.$blocks[$i]['template'].'</b> recompile failed.</span>';
										} else {
											$msgs[] = sprintf('&nbsp;&nbsp;'._MD_AM_RECOMPTEMPFILE, $blocks[$i]['template']);
										}
									}
								}
							}
							$msgs[] = '&nbsp;&nbsp;Block <b>'.$blocks[$i]['name'].'</b> created. Block ID: <b>'.$newbid.'</b>';
							$sql = "INSERT INTO ".$xoopsDB->prefix('block_module_link')." (block_id, module_id, page_id) VALUES ('".intval($newbid)."', '0', '1')";
							$xoopsDB->query($sql);
						}
					}
				}
			}

			$icms_block_handler = xoops_gethandler('block');
			$block_arr = $icms_block_handler->getByModule($module->getVar('mid'));
			foreach ($block_arr as $block) {
				if (!in_array($block->getVar('show_func'), $showfuncs) || !in_array($block->getVar('func_file'), $funcfiles)) {
					$sql = sprintf("DELETE FROM %s WHERE bid = '%u'", $xoopsDB->prefix('newblocks'), intval($block->getVar('bid')));
					if(!$xoopsDB->query($sql)) {
						$msgs[] = '&nbsp;&nbsp;<span style="color:#ff0000;">ERROR: Could not delete block <b>'.$block->getVar('name').'</b>. Block ID: <b>'.$block->getVar('bid').'</b></span>';
					} else {
						$msgs[] = '&nbsp;&nbsp;Block <b>'.$block->getVar('name').' deleted. Block ID: <b>'.$block->getVar('bid').'</b>';
						if ($block->getVar('template') != '') {
							$tplfiles =& $tplfile_handler->find(null, 'block', $block->getVar('bid'));
							if (is_array($tplfiles)) {
								$btcount = count($tplfiles);
								for ($k = 0; $k < $btcount; $k++) {
									if (!$tplfile_handler->delete($tplfiles[$k])) {
										$msgs[] = '&nbsp;&nbsp;<span style="color:#ff0000;">ERROR: Could not remove deprecated block template. (ID: <b>'.$tplfiles[$k]->getVar('tpl_id').'</b>)</span>';
									} else {
										$msgs[] = '&nbsp;&nbsp;Block template <b>'.$tplfiles[$k]->getVar('tpl_file').'</b> deprecated.';
									}
								}
							}
						}
					}
				}
			}
		}

		// first delete all config entries
		$config_handler =& xoops_gethandler('config');
		$configs =& $config_handler->getConfigs(new Criteria('conf_modid', $module->getVar('mid')));
		$confcount = count($configs);
		$config_delng = array();
		if ($confcount > 0) {
			$msgs[] = 'Deleting module config options...';
			for ($i = 0; $i < $confcount; $i++) {
				if (!$config_handler->deleteConfig($configs[$i])) {
					$msgs[] = '&nbsp;&nbsp;<span style="color:#ff0000;">ERROR: Could not delete config data from the database. Config ID: <b>'.$configs[$i]->getvar('conf_id').'</b></span>';
					// save the name of config failed to delete for later use
					$config_delng[] = $configs[$i]->getvar('conf_name');
				} else {
					$config_old[$configs[$i]->getvar('conf_name')]['value'] = $configs[$i]->getvar('conf_value', 'N');
					$config_old[$configs[$i]->getvar('conf_name')]['formtype'] = $configs[$i]->getvar('conf_formtype');
					$config_old[$configs[$i]->getvar('conf_name')]['valuetype'] = $configs[$i]->getvar('conf_valuetype');
					$msgs[] = '&nbsp;&nbsp;Config data deleted from the database. Config ID: <b>'.$configs[$i]->getVar('conf_id').'</b>';
				}
			}
		}

		// now reinsert them with the new settings
		$configs = $module->getInfo('config');
		if ($configs != false) {
			if ($module->getVar('hascomments') != 0) {
				include_once(XOOPS_ROOT_PATH.'/include/comment_constants.php');
				array_push($configs, array('name' => 'com_rule', 'title' => '_CM_COMRULES', 'description' => '', 'formtype' => 'select', 'valuetype' => 'int', 'default' => 1, 'options' => array('_CM_COMNOCOM' => XOOPS_COMMENT_APPROVENONE, '_CM_COMAPPROVEALL' => XOOPS_COMMENT_APPROVEALL, '_CM_COMAPPROVEUSER' => XOOPS_COMMENT_APPROVEUSER, '_CM_COMAPPROVEADMIN' => XOOPS_COMMENT_APPROVEADMIN)));
				array_push($configs, array('name' => 'com_anonpost', 'title' => '_CM_COMANONPOST', 'description' => '', 'formtype' => 'yesno', 'valuetype' => 'int', 'default' => 0));
			}
		} else {
			if ($module->getVar('hascomments') != 0) {
				$configs = array();
				include_once(XOOPS_ROOT_PATH.'/include/comment_constants.php');
				$configs[] = array('name' => 'com_rule', 'title' => '_CM_COMRULES', 'description' => '', 'formtype' => 'select', 'valuetype' => 'int', 'default' => 1, 'options' => array('_CM_COMNOCOM' => XOOPS_COMMENT_APPROVENONE, '_CM_COMAPPROVEALL' => XOOPS_COMMENT_APPROVEALL, '_CM_COMAPPROVEUSER' => XOOPS_COMMENT_APPROVEUSER, '_CM_COMAPPROVEADMIN' => XOOPS_COMMENT_APPROVEADMIN));
				$configs[] = array('name' => 'com_anonpost', 'title' => '_CM_COMANONPOST', 'description' => '', 'formtype' => 'yesno', 'valuetype' => 'int', 'default' => 0);
			}
		}

		// RMV-NOTIFY
		if ($module->getVar('hasnotification') != 0) {
			if (empty($configs)) {
				$configs = array();
			}
			// Main notification options
			include_once XOOPS_ROOT_PATH . '/include/notification_constants.php';
			include_once XOOPS_ROOT_PATH . '/include/notification_functions.php';
			$options = array();
			$options['_NOT_CONFIG_DISABLE'] = XOOPS_NOTIFICATION_DISABLE;
			$options['_NOT_CONFIG_ENABLEBLOCK'] = XOOPS_NOTIFICATION_ENABLEBLOCK;
			$options['_NOT_CONFIG_ENABLEINLINE'] = XOOPS_NOTIFICATION_ENABLEINLINE;
			$options['_NOT_CONFIG_ENABLEBOTH'] = XOOPS_NOTIFICATION_ENABLEBOTH;

			//$configs[] = array ('name' => 'notification_enabled', 'title' => '_NOT_CONFIG_ENABLED', 'description' => '_NOT_CONFIG_ENABLEDDSC', 'formtype' => 'yesno', 'valuetype' => 'int', 'default' => 1);
			$configs[] = array ('name' => 'notification_enabled', 'title' => '_NOT_CONFIG_ENABLE', 'description' => '_NOT_CONFIG_ENABLEDSC', 'formtype' => 'select', 'valuetype' => 'int', 'default' => XOOPS_NOTIFICATION_ENABLEBOTH, 'options'=>$options);
			// Event specific notification options
			// FIXME: for some reason the default doesn't come up properly
			//  initially is ok, but not when 'update' module..
			$options = array();
			$categories =& notificationCategoryInfo('',$module->getVar('mid'));
			foreach ($categories as $category) {
				$events =& notificationEvents ($category['name'], false, $module->getVar('mid'));
				foreach ($events as $event) {
					if (!empty($event['invisible'])) {
						continue;
					}
					$option_name = $category['title'] . ' : ' . $event['title'];
					$option_value = $category['name'] . '-' . $event['name'];
					$options[$option_name] = $option_value;
					//$configs[] = array ('name' => notificationGenerateConfig($category,$event,'name'), 'title' => notificationGenerateConfig($category,$event,'title_constant'), 'description' => notificationGenerateConfig($category,$event,'description_constant'), 'formtype' => 'yesno', 'valuetype' => 'int', 'default' => 1);
				}
			}
			$configs[] = array ('name' => 'notification_events', 'title' => '_NOT_CONFIG_EVENTS', 'description' => '_NOT_CONFIG_EVENTSDSC', 'formtype' => 'select_multi', 'valuetype' => 'array', 'default' => array_values($options), 'options' => $options);
		}

		if ($configs != false) {
			$msgs[] = 'Adding module config data...';
			$config_handler =& xoops_gethandler('config');
			$order = 0;
			foreach ($configs as $config) {
				// only insert ones that have been deleted previously with success
				if (!in_array($config['name'], $config_delng)) {
					$confobj =& $config_handler->createConfig();
					$confobj->setVar('conf_modid', intval($newmid));
					$confobj->setVar('conf_catid', 0);
					$confobj->setVar('conf_name', $config['name']);
					$confobj->setVar('conf_title', $config['title'], true);
					$confobj->setVar('conf_desc', $config['description'], true);
					$confobj->setVar('conf_formtype', $config['formtype']);
					$confobj->setVar('conf_valuetype', $config['valuetype']);
					if (isset($config_old[$config['name']]['value']) && $config_old[$config['name']]['formtype'] == $config['formtype'] && $config_old[$config['name']]['valuetype'] == $config['valuetype']) {
						// preserver the old value if any
						// form type and value type must be the same
						$confobj->setVar('conf_value', $config_old[$config['name']]['value'], true);
					} else {
						$confobj->setConfValueForInput($config['default'], true);

					//$confobj->setVar('conf_value', $config['default'], true);
					}
					$confobj->setVar('conf_order', $order);
					$confop_msgs = '';
					if (isset($config['options']) && is_array($config['options'])) {
						foreach ($config['options'] as $key => $value) {
							$confop =& $config_handler->createConfigOption();
							$confop->setVar('confop_name', $key, true);
							$confop->setVar('confop_value', $value, true);
							$confobj->setConfOptions($confop);
							$confop_msgs .= '<br />&nbsp;&nbsp;&nbsp;&nbsp;Config option added. Name: <b>'.$key.'</b> Value: <b>'.$value.'</b>';
							unset($confop);
						}
					}
					$order++;
					if (false != $config_handler->insertConfig($confobj)) {
						$msgs[] = '&nbsp;&nbsp;Config <b>'.$config['name'].'</b> added to the database.'.$confop_msgs;
					} else {
						$msgs[] = '&nbsp;&nbsp;<span style="color:#ff0000;">ERROR: Could not insert config <b>'.$config['name'].'</b> to the database.</span>';
					}
					unset($confobj);
				}
			}
			unset($configs);
		}

		// add module specific tasks to system autotasks list
		$atasks = $module->getInfo('autotasks');
		$atasks_handler = &xoops_getModuleHandler('autotasks', 'system');
		if (isset($atasks) && is_array($atasks) && (count($atasks) > 0)) {
			$msgs[] = 'Updating autotasks...';
	  	  	$criteria = new CriteriaCompo();
			$criteria->add( new Criteria( 'sat_type', 'addon/'.$module->getInfo('dirname')));
			$items_atasks = $atasks_handler->getObjects( $criteria , false );
			foreach ($items_atasks as $task) {
				$taskID = intval($task->getVar('sat_addon_id'));
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
					$task->setVar('sat_type', 'addon/'.$module->getInfo('dirname'));
					$task->setVar('sat_addon_id', intval($taskID));
					if (!($atasks_handler->insert($task))) {
						$msgs[] = '&nbsp;&nbsp;<span style="color:#ff0000;">ERROR: Could not insert autotask to db. Name: <b>'.$taskData['name'].'</b></span>';
					} else {
						$msgs[] = '&nbsp;&nbsp;Updated task from autotasks list. Task Name: <b>'.$taskData['name'].'</b>';
					}
				}
			}
			unset($atasks, $atasks_handler, $task, $taskData, $criteria, $items, $taskID);
		}

		// execute module specific update script if any
		$update_script = $module->getInfo('onUpdate');
		$ModName = ($module->getInfo('modname') != '') ? trim($module->getInfo('modname')) : $dirname;
		if (false != $update_script && trim($update_script) != '') {
			include_once XOOPS_ROOT_PATH.'/modules/'.$dirname.'/'.trim($update_script);

			$is_IPF = $module->getInfo('object_items');
			if(!empty($is_IPF)){
				$icmsDatabaseUpdater = XoopsDatabaseFactory::getDatabaseUpdater();
				$icmsDatabaseUpdater->moduleUpgrade($module, true);
				foreach ($icmsDatabaseUpdater->_messages as $msg) {
					$msgs[] = $msg;
				}
			}

			if (function_exists('xoops_module_update_'.$ModName)) {
				$func = 'xoops_module_update_'.$ModName;
				if (!$func($module, $prev_version, $prev_dbversion)) {
					$msgs[] = sprintf(_MD_AM_FAIL_EXEC, $func);
				} else {
					$msgs[] = $module->messages;
					$msgs[] = sprintf(_MD_AM_FUNCT_EXEC, $func);
				}
			}elseif (function_exists('icms_module_update_'.$ModName)) {
				$func = 'icms_module_update_'.$ModName;
				if (!$func($module, $prev_version, $prev_dbversion)) {
					$msgs[] = sprintf(_MD_AM_FAIL_EXEC, $func);
				} else {
					$msgs[] = $module->messages;
					$msgs[] = sprintf(_MD_AM_FUNCT_EXEC, $func);
				}
			}
		}

			$msgs[] = '</code><p>'.sprintf(_MD_AM_OKUPD, '<b>'.$module->getVar('name').'</b>').'</p>';
		}
		$ret = '<code>';
		foreach ($msgs as $msg) {
			$ret .= $msg.'<br />';
		}
		return $ret;
}

?>