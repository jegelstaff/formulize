<?php
/**
 * Administration of template sets, main file
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @package		System
 * @subpackage	Template Sets
 * @todo		Extract HTML and CSS to a template
 * @todo		Validate and sanitize user input using a standard method
 * @version		SVN: $Id: main.php 22485 2011-08-31 02:27:31Z blauer-fisch $
 */

if (!is_object(icms::$user) || !is_object($icmsModule) || !icms::$user->isAdmin($icmsModule->getVar('mid'))) {
	exit("Access Denied");
}

$allowedHTML = array('html');

if (!empty($_POST)) { 
	foreach ($_POST as $k => $v) { 
		if (!in_array($k,$allowedHTML)) {${$k} = StopXSS($v);} else {${$k} = $v;}
	}
}
if (!empty($_GET)) { 
	foreach ($_GET as $k => $v) { 
		if (!in_array($k,$allowedHTML)) {${$k} = StopXSS($v);} else {${$k} = $v;}
	}
}
$op = (isset($_GET['op']))
	? trim(filter_input(INPUT_GET, 'op'))
	: ((isset($_POST['op']))
		? trim(filter_input(INPUT_POST, 'op'))
		: 'list');

if ($op == 'edittpl_go') {
	if (isset($previewtpl)) {
		$op = 'previewtpl';
	}
}
$icmsAdminTpl = new icms_view_Tpl();
switch ($op) {
	case 'list':
		$tplset_handler = icms::handler('icms_view_template_set');
		$tplsets =& $tplset_handler->getObjects();
		icms_cp_header();
		echo '<div class="CPbigTitle" style="background-image: url('. ICMS_MODULES_URL 
			. '/system/admin/tplsets/images/tplsets_big.png)">' . _MD_TPLMAIN 
			. '</div><br />';
		$installed = array();
		$tpltpl_handler =& icms::handler('icms_view_template_file');
		$installed_mods = $tpltpl_handler->getModuleTplCount('default');
		$tcount = count($tplsets);
		if ($tcount == 1) icms_core_Message::warning(_MD_TPLSET_CREATE_OWN, "", TRUE);
		echo '<table width="100%" cellspacing="1" class="outer"><tr align="center"><th width="25%">' 
			. _MD_THMSETNAME . '</th><th>' . _MD_CREATED . '</th><th>' . _MD_TEMPLATES 
			. '</th><th>' . _MD_TPLSET_ACTIONS . '</th><th>' . _MD_TPLSET_STATUS . '</th></tr>';
		$class = 'even';
		for ($i = 0; $i < $tcount; $i++) {
			$tplsetname = $tplsets[$i]->getVar('tplset_name');
			$installed_themes[] = $tplsetname;
			$class = ($class == 'even') ? 'odd' : 'even';
			echo '<tr class="' . $class . '" align="center"><td  style="vertical-align: middle;" class="head">' 
				. $tplsetname . '<br /><br /><span style="font-weight:normal;">' 
				. $tplsets[$i]->getVar('tplset_desc') . '</span></td><td style="vertical-align: middle;">' 
				. formatTimestamp($tplsets[$i]->getVar('tplset_created'), 's') 
				. '</td><td align="' . _GLOBAL_LEFT . '"><ul>';
			$tplstats = $tpltpl_handler->getModuleTplCount($tplsetname);
			if (count($tplstats) > 0) {
				$module_handler = icms::handler('icms_module');
				echo '<ul>';
				foreach ($tplstats as $moddir => $filecount) {
					$module =& $module_handler->getByDirname($moddir);
					if (is_object($module)) {
						if ($installed_mods[$moddir] > $filecount) {
							$filecount = '<span style="color:#ff0000;">' . $filecount . '</span>';
						}
						echo '<li>' . $module->getVar('name') 
							. ' [<a href="admin.php?fct=tplsets&amp;op=listtpl&amp;tplset=' . $tplsetname 
							. '&amp;moddir=' . $moddir . '">' . _LIST . '</a> (<strong>' 
							. icms_conv_nr2local($filecount) . '</strong>)]</li>';
					}
					unset($module);
				}
				$not_installed = array_diff(array_keys($installed_mods), array_keys($tplstats));
			} else {
				$not_installed =& array_keys($installed_mods);
			}
			foreach ($not_installed as $ni) {
				$module =& $module_handler->getByDirname($ni);
				echo '<li>' . $module->getVar('name') 
					. ' <a href="admin.php?fct=tplsets&amp;op=listtpl&amp;tplset=' . $tplsetname 
					. '&amp;moddir=' . $ni . '"><img src="'. ICMS_IMAGES_SET_URL . '/actions/view_choose.png" alt="' . _LIST . '" title="' . _LIST . '" /></a> (<span style="color:#ff0000; font-weight: bold;">0</span>)' 
					. ' <a href="admin.php?fct=tplsets&amp;op=generatemod&amp;tplset=' . $tplsetname 
					. '&amp;moddir=' . $ni . '"><img src="'. ICMS_IMAGES_SET_URL . '/actions/filenew2.png" alt="' . _MD_GENERATE . '" title="' . _MD_GENERATE . '" /></a></li>';
			}
			echo '</ul></td><td style="vertical-align: middle;">'
				. '<a href="admin.php?fct=tplsets&amp;op=download&amp;method=tar&amp;tplset=' . $tplsetname 
				. '"><img src="'. ICMS_IMAGES_SET_URL . '/actions/filesave2.png" alt="' . _MD_DOWNLOAD . '" title="' . _MD_DOWNLOAD . '" /></a>&nbsp;<a href="admin.php?fct=tplsets&amp;op=clone&amp;tplset=' . $tplsetname 
				. '"><img src="'. ICMS_IMAGES_SET_URL . '/actions/editcopy.png" alt="' . _CLONE . '" title="' . _CLONE . '" /></a>';
			if ($tplsetname != 'default' && $tplsetname != $icmsConfig['template_set']) {
				echo '&nbsp;<a href="admin.php?fct=tplsets&amp;op=delete&amp;tplset=' . $tplsetname 
					. '"><img src="'. ICMS_IMAGES_SET_URL . '/actions/editdelete.png" alt="' . _DELETE . '" title="' . _DELETE . '" /></a>';
			}
			echo '</td>';
			if ($tplsetname == $icmsConfig['template_set']) {
				echo '<td style="vertical-align: middle;"><img src="'. ICMS_IMAGES_SET_URL . '/actions/button_ok.png" alt="' . _MD_DEFAULTTHEME . '" title="' . _MD_DEFAULTTHEME . '" /></td>';
			} else {
				echo '<td>&nbsp;</td>';
			}
			echo '</tr>';
		}
		echo '</table><br />';

		$form = new icms_form_Theme(_MD_UPLOADTAR, 'tplupload_form', 'admin.php', 'post', TRUE);
		$form->setExtra('enctype="multipart/form-data"');
		$form->addElement(new icms_form_elements_File(_MD_CHOOSETAR . '<br /><span style="color:#ff0000;">' . _MD_ONLYTAR . '</span>', 'tpl_upload', 1000000));
		$form->addElement(new icms_form_elements_Text(_MD_NTHEMENAME . '<br /><span style="font-weight:normal;">' . _MD_ENTERTH . '</span>', 'tplset_name', 20, 50));
		$form->addElement(new icms_form_elements_Hidden('op', 'uploadtar_go'));
		$form->addElement(new icms_form_elements_Hidden('fct', 'tplsets'));
		$form->addElement(new icms_form_elements_Button('', 'upload_button', _MD_UPLOAD, 'submit'));
		$form->display();
		icms_cp_footer();
		break;

	case 'listtpl':
		$tplset = trim($_GET['tplset']);
		if ($tplset == '') {
			redirect_header('admin.php?fct=tplsets', 1);
		}
		if ($moddir == '') {
			redirect_header('admin.php?fct=tplsets', 1);
		}
		icms_cp_header();
		$module_handler = icms::handler('icms_module');
		$module =& $module_handler->getByDirname($moddir);
		$modname = $module->getVar('name');
		echo '<div class="CPbigTitle" style="background-image: url('
			. ICMS_MODULES_URL . '/system/admin/tplsets/images/tplsets_big.png)">' 
			. '<a href="admin.php?fct=tplsets">'. _MD_TPLMAIN 
			.'</a>&nbsp;<span style="font-weight:bold;">&raquo;&raquo;</span>&nbsp;' 
			. $tplset . '&nbsp;<span style="font-weight:bold;">&raquo;&raquo;</span>&nbsp;' 
			. $modname . '<br /><br /></div><br />';

		echo '<form action="admin.php" method="post" enctype="multipart/form-data">' 
			. '<table width="100%" class="outer" cellspacing="1">' 
			. '<tr><th width="40%">' . _MD_FILENAME . '</th><th>' . _MD_LASTMOD . '</th>';
		if ($tplset != 'default') {
			echo '<th>' . _MD_LASTIMP . '</th><th colspan="2">' . _MD_TPLSET_ACTIONS . '</th></tr>';
		} else {
			echo '<th>' . _MD_TPLSET_ACTIONS . '</th></tr>';
		}
		$tpltpl_handler =& icms::handler('icms_view_template_file');
		// get files that are already installed
		$templates =& $tpltpl_handler->find($tplset, 'module', NULL, $moddir);
		$inst_files = array();
		$tcount = count($templates);
		for ($i = 0; $i < $tcount; $i++) {
			if ($i % 2 == 0) {
				$class = 'even';
			} else {
				$class = 'odd';
			}
			$last_modified = $templates[$i]->getVar('tpl_lastmodified');
			$last_imported = $templates[$i]->getVar('tpl_lastimported');
			$last_imported_f = ($last_imported > 0) ? formatTimestamp($last_imported, 'l') : '';
			echo  '<tr class="' . $class . '"><td class="head">' 
				. $templates[$i]->getVar('tpl_file') 
				. '<br /><br /><span style="font-weight:normal;">' . $templates[$i]->getVar('tpl_desc') . '</span></td><td style="vertical-align: middle;">' 
				. formatTimestamp($last_modified, 'l') . '</td>';
			$filename = $templates[$i]->getVar('tpl_file');
			if ($tplset != 'default') {
				$physical_file = ICMS_THEME_PATH . '/' . $tplset . '/templates/' . $moddir . '/' . $filename;
				if (file_exists($physical_file)) {
					$mtime = filemtime($physical_file);
					if ($last_imported < $mtime) {
						if ($mtime > $last_modified) {
							$bg = '#ff9999';
						} elseif ($mtime > $last_imported) {
							$bg = '#99ff99';
						}
						echo '<td style="background-color:' . $bg . ';">' . $last_imported_f 
							. ' [<a href="admin.php?fct=tplsets&amp;tplset=' . $tplset . '&amp;moddir=' . $moddir 
							. '&amp;op=importtpl&amp;id=' . $templates[$i]->getVar('tpl_id') . '">' 
							. _MD_IMPORT . '</a>]';
					} else {
						echo '<td style="vertical-align: middle;">' . $last_imported_f;
					}
				} else {
					echo '<td style="vertical-align: middle;">' . $last_imported_f;
				}
				echo '</td><td style="vertical-align: middle;">' 
					. '<a href="admin.php?fct=tplsets&amp;op=edittpl&amp;id=' . $templates[$i]->getVar('tpl_id') . '"><img src="'. ICMS_IMAGES_SET_URL . '/actions/edit.png" alt="' . _EDIT . '" title="' . _EDIT . '" /></a>' 
					. ' <a href="admin.php?fct=tplsets&amp;op=downloadtpl&amp;id=' . $templates[$i]->getVar('tpl_id') . '"><img src="'. ICMS_IMAGES_SET_URL . '/actions/filesave2.png" alt="' . _MD_DOWNLOAD . '" title="' . _MD_DOWNLOAD . '" /></a>' 
					. ' <a href="admin.php?fct=tplsets&amp;op=deletetpl&amp;id=' . $templates[$i]->getVar('tpl_id') . '"><img src="'. ICMS_IMAGES_SET_URL . '/actions/editdelete.png" alt="' . _DELETE . '" title="' . _DELETE . '" /></a>' 
					. '</td><td style="vertical-align: middle;" align="' . _GLOBAL_RIGHT . '"><input type="file" name="' . $filename . '" id="' . $filename . '" />' 
					. '<input type="hidden" name="xoops_upload_file[]" id="xoops_upload_file[]" value="' . $filename . '" />' 
					. '<input type="hidden" name="old_template[' . $filename . ']" value="' . $templates[$i]->getVar('tpl_id') . '" /></td>';
			} else {
				echo '<td style="vertical-align: middle;"><a href="admin.php?fct=tplsets&amp;op=edittpl&amp;id=' . $templates[$i]->getVar('tpl_id') . '"><img src="'. ICMS_IMAGES_SET_URL . '/actions/viewmag.png" alt="' . _MD_VIEW . '" title="' . _MD_VIEW . '" /></a>&nbsp;<a href="admin.php?fct=tplsets&amp;op=downloadtpl&amp;id=' . $templates[$i]->getVar('tpl_id') . '"><img src="'. ICMS_IMAGES_SET_URL . '/actions/filesave2.png" alt="' . _MD_DOWNLOAD . '" title="' . _MD_DOWNLOAD . '" /></a></td>';
			}
			echo '</tr>' . "\n";
			$inst_files[] = $filename;
		}
		if ($tplset != 'default') {
			// get difference between already installed files and the files under modules directory. which will be recognized as files that are not installed
			$notinst_files = array_diff(icms_core_Filesystem::getFileList(ICMS_MODULES_PATH . '/' . $moddir . '/templates/'), $inst_files);
			foreach ($notinst_files as $nfile) {
				$class = ($class == "even") ? "odd" : "even";
				if ($nfile != 'index.html') {
					echo  '<tr class="' . $class . '"><td style="background-color:#FFFF99;">' . $nfile . '<br />' . _MD_FILEGENER
						. '</td><td style="background-color:#FFFF99;">&nbsp;</td><td style="background-color:#FFFF99;">';
					$physical_file = ICMS_THEME_PATH . '/' . $tplset . '/templates/' . $moddir . '/' . $nfile;
					if (file_exists($physical_file)) {
						echo '[<a href="admin.php?fct=tplsets&amp;moddir=' . $moddir . '&amp;tplset=' . $tplset . '&amp;op=importtpl&amp;file=' . urlencode($nfile) . '">' . _MD_IMPORT . '</a>]';
					} else {
						echo '&nbsp;';
					}
					echo '</td><td style="background-color:#FFFF99;vertical-align: middle;">' 
						. '<a href="admin.php?fct=tplsets&amp;moddir=' . $moddir . '&amp;tplset=' . $tplset 
						. '&amp;op=generatetpl&amp;type=module&amp;file=' . urlencode($nfile) . '"><img src="'. ICMS_IMAGES_SET_URL . '/actions/filenew2.png" alt="' . _MD_GENERATE . '" title="' . _MD_GENERATE . '" /></a></td>
						<td style="background-color:#FFFF99;vertical-align: middle; text-align:' . _GLOBAL_RIGHT 
						. ';"><input type="file" name="' . $nfile . '" id="' . $nfile 
						. '" /><input type="hidden" name="xoops_upload_file[]" id="xoops_upload_file[]" value="' . $nfile 
						. '" /></td></tr>' . "\n";
				}
			}
		}
		echo '</table><br /><table width="100%" class="outer" cellspacing="1"><tr><th width="40%">' . _MD_FILENAME . '</th><th>' . _MD_LASTMOD . '</th>';
		if ($tplset != 'default') {
			echo '<th>' . _MD_LASTIMP . '</th><th colspan="2">' . _MD_TPLSET_ACTIONS . '</th></tr>';
		} else {
			echo '<th>' . _MD_TPLSET_ACTIONS . '</th></tr>';
		}
		$btemplates =& $tpltpl_handler->find($tplset, 'block', NULL, $moddir);
		$binst_files = array();
		$btcount = count($btemplates);
		for ($j = 0; $j < $btcount; $j++) {
			$last_imported = $btemplates[$j]->getVar('tpl_lastimported');
			$last_imported_f = ($last_imported > 0) ? formatTimestamp($last_imported, 'l') : '';
			$last_modified = $btemplates[$j]->getVar('tpl_lastmodified');
			if ($j % 2 == 0) {
				$class = 'even';
			} else {
				$class = 'odd';
			}
			echo  '<tr class="' . $class . '"><td class="head"><span style="font-weight:bold;">' 
				. $btemplates[$j]->getVar('tpl_file') . '</span><br /><br /><span style="font-weight:normal;">' 
				. $btemplates[$j]->getVar('tpl_desc') . '</span></td><td style="vertical-align: middle;">' . formatTimestamp($last_modified, 'l') . '</td>';
			$filename = $btemplates[$j]->getVar('tpl_file');
			$physical_file = ICMS_THEME_PATH . '/' . $tplset . '/templates/' . $moddir . '/blocks/' . $filename;
			if ($tplset != 'default') {
				if (file_exists($physical_file)) {
					$mtime = filemtime($physical_file);
					if ($last_imported < $mtime) {
						if ($mtime > $last_modified) {
							$bg = '#ff9999';
						} elseif ($mtime > $last_imported) {
							$bg = '#99ff99';
						}
						echo '<td style="background-color:' . $bg . ';">' . $last_imported_f . ' <a href="admin.php?fct=tplsets&amp;tplset=' . $tplset . '&amp;op=importtpl&amp;moddir=' . $moddir . '&amp;id=' . $btemplates[$j]->getVar('tpl_id') . '">' . _MD_IMPORT . '</a>]';
					} else {
						echo '<td>' . $last_imported_f;
					}
				} else {
					echo '<td>' . $last_imported_f;
				}
				echo '</td><td style="vertical-align: middle;">'
					. '<a href="admin.php?fct=tplsets&amp;op=edittpl&amp;id=' . $btemplates[$j]->getVar('tpl_id') . '"><img src="'. ICMS_IMAGES_SET_URL . '/actions/edit.png" alt="' . _EDIT . '" title="' . _EDIT . '" /></a>'
					. ' <a href="admin.php?fct=tplsets&amp;op=downloadtpl&amp;id=' . $btemplates[$j]->getVar('tpl_id') . '"><img src="'. ICMS_IMAGES_SET_URL . '/actions/filesave2.png" alt="' . _MD_DOWNLOAD . '" title="' . _MD_DOWNLOAD . '" /></a>' 
					. ' <a href="admin.php?fct=tplsets&amp;op=deletetpl&amp;id=' . $btemplates[$j]->getVar('tpl_id') . '"><img src="'. ICMS_IMAGES_SET_URL . '/actions/editdelete.png" alt="' . _DELETE . '" title="' . _DELETE . '" /></a>' 
					. '</td><td style="vertical-align: middle;" align="' . _GLOBAL_RIGHT . '"><input type="file" name="' . $filename . '" id="' . $filename . '" />'
					. '<input type="hidden" name="xoops_upload_file[]" id="xoops_upload_file[]" value="' . $filename . '" />'
					. '<input type="hidden" name="old_template[' . $filename . ']" value="' . $btemplates[$j]->getVar('tpl_id') . '" /></td>';
			} else {
				echo '<td><a href="admin.php?fct=tplsets&amp;op=edittpl&amp;id=' . $btemplates[$j]->getVar('tpl_id') . '"><img src="'. ICMS_IMAGES_SET_URL . '/actions/viewmag.png" alt="' . _MD_VIEW . '" title="' . _MD_VIEW . '" /></a>&nbsp;
				<a href="admin.php?fct=tplsets&amp;op=downloadtpl&amp;id=' . $btemplates[$j]->getVar('tpl_id') . '"><img src="'. ICMS_IMAGES_SET_URL . '/actions/filesave2.png" alt="' . _MD_DOWNLOAD . '" title="' . _MD_DOWNLOAD . '" /></a></td>';
			}
			echo '</tr>' . "\n";
			$binst_files[] = $filename;
		}
		if ($tplset != 'default') {
			$bnotinst_files = array_diff(icms_core_Filesystem::getFileList(ICMS_MODULES_PATH . '/' . $moddir . '/templates/blocks/'), $binst_files);
			foreach ($bnotinst_files as $nfile) {
				if ($nfile != 'index.html') {
					echo  '<tr style="background-color:#FFFF99;"><td style="background-color:#FFFF99;">' . $nfile 
						. '</td><td style="background-color:#FFFF99;">&nbsp;</td><td style="background-color:#FFFF99;">';
					$physical_file = ICMS_THEME_PATH . '/' . $tplset . '/templates/' . $moddir . '/blocks/' . $nfile;
					if (file_exists($physical_file)) {
						echo '[<a href="admin.php?fct=tplsets&amp;moddir=' . $moddir . '&amp;tplset=' . $tplset . '&amp;op=importtpl&amp;file=' . urlencode($nfile) . '">' . _MD_IMPORT . '</a>]';
					} else {
						echo '&nbsp;';
					}
					echo '</td><td style="background-color:#FFFF99;"><a href="admin.php?fct=tplsets&amp;moddir=' 
						. $moddir . '&amp;tplset=' . $tplset . '&amp;op=generatetpl&amp;type=block&amp;file=' . urlencode($nfile) . '"><img src="'. ICMS_IMAGES_SET_URL . '/actions/filenew2.png" alt="' . _MD_GENERATE . '" title="' . _MD_GENERATE . '" /></a></td>
						<td style="background-color:#FFFF99; vertical-align: middle; text-align: ' . _GLOBAL_RIGHT . '">' 
						. '<input type="file" name="' . $nfile . '" id="' . $nfile . '" />' 
						. '<input type="hidden" name="xoops_upload_file[]" id="xoops_upload_file[]" value="' . $nfile . '" />' 
						. '</td></tr>' . "\n";
				}
			}
		}
		echo '</table>';
		if ($tplset != 'default') {
			echo '<div style="text-align: ' . _GLOBAL_RIGHT . '; margin-top: 5px;">' 
				. '<input type="hidden" name="fct" value="tplsets" />' 
				. '<input type="hidden" name="op" value="update" />' . icms::$security->getTokenHTML() 
				. '<input type="hidden" name="moddir" value="' . $moddir . '" />' 
				. '<input type="hidden" name="tplset" value="' . $tplset . '" />' 
				. '<input type="submit" value="' . _MD_UPLOAD . '" /></div></form>';
		}
		icms_cp_footer();
		break;

	case 'edittpl':
		if ($id <= 0) {
			redirect_header('admin.php?fct=tplsets', 1);
		}
		$tpltpl_handler =& icms::handler('icms_view_template_file');
		$tplfile =& $tpltpl_handler->get($id, TRUE);
		if (is_object($tplfile)) {
			$tplset = $tplfile->getVar('tpl_tplset');
			$tform = array(
				'tpl_tplset' => $tplset, 
				'tpl_id' => $id, 
				'tpl_file' => $tplfile->getVar('tpl_file'), 
				'tpl_desc' => $tplfile->getVar('tpl_desc'), 
				'tpl_lastmodified' => $tplfile->getVar('tpl_lastmodified'), 
				'tpl_source' => $tplfile->getVar('tpl_source', 'E'), 
				'tpl_module' => $tplfile->getVar('tpl_module'));
			include_once ICMS_MODULES_PATH . '/system/admin/tplsets/tplform.php';
			icms_cp_header();
			echo '<a href="admin.php?fct=tplsets">'. _MD_TPLMAIN .'</a>' 
				. '&nbsp;<span style="font-weight:bold;">&raquo;&raquo;</span>&nbsp;' 
				. '<a href="./admin.php?fct=tplsets&amp;op=listtpl&amp;moddir=' . $tplfile->getVar('tpl_module') . '&amp;tplset=' . $tplset . '">' 
				. $tplset . '</a>' 
				. '&nbsp;<span style="font-weight:bold;">&raquo;&raquo;</span>&nbsp;' 
				. $tform['tpl_module'] . '&nbsp;<span style="font-weight:bold;">&raquo;&raquo;</span>&nbsp;' 
				. _MD_EDITTEMPLATE . '<br /><br />';
			$form->display();
			icms_cp_footer();
			exit();
		} else {
			$err[] = 'Selected template (ID: ' . '$id' . ') does not exist';
		}
		icms_cp_header();
		icms_core_Message::error($err);
		echo '<br /><a href="admin.php?fct=tplsets">' . _MD_AM_BTOTADMIN . '</a>';
		icms_cp_footer();
		break;

	case 'edittpl_go':
		if ($id <= 0 | !icms::$security->check()) {
			redirect_header('admin.php?fct=tplsets', 3, implode('<br />', icms::$security->getErrors()));
		}
		$tpltpl_handler =& icms::handler('icms_view_template_file');
		$tplfile =& $tpltpl_handler->get($id, TRUE);
		$err = array();
		if (!is_object($tplfile)) {
			$err[] = sprintf(_MD_TPLSET_TEMPLATE_NOTEXIST, $id);
		} else {
			if ($tplfile->getVar('tpl_tplset') != 'default') {
				$tplfile->setVar('tpl_source', $html);
				$tplfile->setVar('tpl_lastmodified', time());

				if (!$tpltpl_handler->insert($tplfile)) {
					$err[] = sprintf(_MD_TPLSET_INSERT_FAILED, $tplfile->getVar('tpl_file'));
				} else {
					$xoopsTpl = new icms_view_Tpl();
					if ($xoopsTpl->is_cached('db:' . $tplfile->getVar('tpl_file'))) {
						if (!$xoopsTpl->clear_cache('db:' . $tplfile->getVar('tpl_file'))) {
						}
					}
					if ($tplfile->getVar('tpl_tplset') == $icmsConfig['template_set']) {
						$icmsAdminTpl->template_touch($id);
					}
				}
			} else {
				$err[] = _MD_TPLSET_DEFAULT_NOEDIT;
			}
		}

		if (count($err) == 0) {
			if (!empty($moddir)) {
				redirect_header('admin.php?fct=tplsets&amp;op=edittpl&amp;id=' . $tplfile->getVar('tpl_id'), 2, _MD_AM_DBUPDATED);
			} elseif (isset($redirect)) {
				redirect_header('admin.php?fct=tplsets&amp;tplset=' . $tplfile->getVar('tpl_tplset') . '&amp;op=' . trim($redirect), 2, _MD_AM_DBUPDATED);
			} else {
				redirect_header('admin.php?fct=tplsets', 2, _MD_AM_DBUPDATED);
			}
		}
		icms_cp_header();
		icms_core_Message::error($err);
		echo '<br /><a href="admin.php?fct=tplsets">' . _MD_AM_BTOTADMIN . '</a>';
		icms_cp_footer();
		break;

	case 'deletetpl':
		icms_cp_header();
		icms_core_Message::confirm(array('id' => $id, 'op' => 'deletetpl_go', 'fct' => 'tplsets'), 'admin.php', _MD_RUSUREDELTPL, _YES);
		icms_cp_footer();
		break;

	case 'deletetpl_go':
		if ($id <= 0 | !icms::$security->check()) {
			redirect_header('admin.php?fct=tplsets', 1, implode('<br />', icms::$security->getErrors()));
		}
		$tpltpl_handler =& icms::handler('icms_view_template_file');
		$tplfile =& $tpltpl_handler->get($id);
		$err = array();
		if (!is_object($tplfile)) {
			$err[] = 'Selected template (ID: ' . '$id' . ') does not exist';
		} else {
			if ($tplfile->getVar('tpl_tplset') != 'default') {
				if (!$tpltpl_handler->delete($tplfile)) {
					$err[] = sprintf(_MD_TPLSET_DELETE_FAIL, $tplfile->getVar('tpl_file'));
				} else {
					// need to compile default xoops template
					if ($tplfile->getVar('tpl_tplset') == $icmsConfig['template_set']) {
						$defaulttpl =& $tpltpl_handler->find('default', $tplfile->getVar('tpl_type'), $tplfile->getVar('tpl_refid'), NULL, $tplfile->getVar('tpl_file'));
						if (count($defaulttpl) > 0) {

							$icmsAdminTpl->template_touch($defaulttpl[0]->getVar('tpl_id'), TRUE);
						}
					}
				}
			} else {
				$err[] = _MD_TPLSET_DEFAULT_NODELETE;
			}
		}

		if (count($err) == 0) {
			redirect_header('admin.php?fct=tplsets&amp;op=listtpl&amp;moddir=' 
				. $tplfile->getVar('tpl_module') . '&amp;tplset=' 
				. urlencode($tplfile->getVar('tpl_tplset')), 2, _MD_AM_DBUPDATED);
		}
		icms_cp_header();
		icms_core_Message::error($err);
		echo '<br /><a href="admin.php?fct=tplsets">' . _MD_AM_BTOTADMIN . '</a>';
		icms_cp_footer();
		break;

	case 'delete':
		icms_cp_header();
		icms_core_Message::confirm(array('tplset' => $tplset, 'op' => 'delete_go', 'fct' => 'tplsets'), 'admin.php', _MD_RUSUREDELTH, _YES);
		icms_cp_footer();
		break;

	case 'delete_go':
		if (!icms::$security->check()) {
			redirect_header('admin.php?fct=tplsets', 1, implode('<br />', icms::$security->getErrors()));
		}
		$msgs = array();
		if ($tplset != 'default' && $tplset != $icmsConfig['template_set']) {
			$tpltpl_handler =& icms::handler('icms_view_template_file');
			$templates =& $tpltpl_handler->getObjects(new icms_db_criteria_Item('tpl_tplset', $tplset));
			$tcount = count($templates);
			if ($tcount > 0) {
				$msgs[] = _MD_TPLSET_DELETING;
				for ($i = 0; $i < $tcount; $i++) {
					if (!$tpltpl_handler->delete($templates[$i])) {
						$msgs[] = '&nbsp;&nbsp;<span style="color:#ff0000;">' 
							. _ERROR . ': ' . sprintf(_MD_TPLSET_DELETE_FAIL, '<strong>' . $templates[$i]->getVar('tpl_file') . '</strong>') 
							. 'ID: ' . '<strong>' . $templates[$i]->getVar('tpl_id') . '</strong></span>';
					} else {
						$msgs[] = '&nbsp;&nbsp;' . sprintf(_MD_TPLSET_DELETE_OK, '<strong>' . $templates[$i]->getVar('tpl_file') . '</strong>') 
						. 'ID: <strong>' . $templates[$i]->getVar('tpl_id') . '</strong>';
					}
				}
			}
			$tplset_handler = icms::handler('icms_view_template_set');
			$tplsets =& $tplset_handler->getObjects(new icms_db_criteria_Item('tplset_name', $tplset));
			if (count($tplsets) > 0 && is_object($tplsets[0])) {
				$msgs[] = _MD_TPLSET_DELETING_DATA;
				if (!$tplset_handler->delete($tplsets[0])) {
					$msgs[] = '&nbsp;&nbsp;<span style="color:#ff0000;">' 
						. _ERROR . ': ' . sprintf(_MD_TPLSET_DELETE_FAIL, $tplset)
						. '</span>';
				} else {
					$msgs[] = '&nbsp;&nbsp;' . sprintf(_MD_TPLSET_DELETE_OK, $tplset);
				}
			}
		} else {
			$msgs[] = '<span style="color:#ff0000;">' 
				. _ERROR . ': ' . _MD_TPLSET_DEFAULT_NODELETE
				. '</span>';
		}
		icms_cp_header();
		echo '<code>' . implode("<br />", $msgs) . '</code><br />';
		echo '<br /><a href="admin.php?fct=tplsets">' . _MD_AM_BTOTADMIN . '</a>';
		icms_cp_footer();
		break;

	case 'clone':
		$form = new icms_form_Theme(_MD_CLONETHEME, 'template_form', 'admin.php', 'post', TRUE);
		$form->addElement(new icms_form_elements_Label(_MD_THEMENAME, $tplset));
		$form->addElement(new icms_form_elements_Text(_MD_NEWNAME, 'newtheme', 30, 50), TRUE);
		$form->addElement(new icms_form_elements_Hidden('tplset', $tplset));
		$form->addElement(new icms_form_elements_Hidden('op', 'clone_go'));
		$form->addElement(new icms_form_elements_Hidden('fct', 'tplsets'));
		$form->addElement(new icms_form_elements_Button('', 'tpl_button', _SUBMIT, 'submit'));
		icms_cp_header();
		echo '<div class="CPbigTitle" style="background-image: url('
			. ICMS_MODULES_URL . '/system/admin/tplsets/images/tplsets_big.png)"><a href="admin.php?fct=tplsets">'
			. _MD_TPLMAIN .'</a>&nbsp;<span style="font-weight:bold;">&raquo;&raquo;</span>&nbsp;' 
			. _MD_CLONETHEME . '<br /><br /></div><br />';
		$form->display();
		icms_cp_footer();
		break;

	case 'clone_go':
		if (!icms::$security->check()) {
			redirect_header('admin.php?fct=tplsets', 1, implode('<br />', icms::$security->getErrors()));
		}

		$msgs = array();
		$tplset = trim($tplset);
		$newtheme = trim($newtheme);
		$tpltpl_handler =& icms::handler('icms_view_template_file');
		if ($tplset == $newtheme) {
			icms_core_Message::error(_MD_TPLSET_UNIQUE_NAME);
		} elseif ($tpltpl_handler->getCount(new icms_db_criteria_Item('tpl_tplset', $newtheme)) > 0) {
			icms_core_Message::error(sprintf(_MD_TPLSET_EXISTS, '<strong>' . $newtheme . '</strong>'));
		} else {
			$tplset_handler = icms::handler('icms_view_template_set');
			$tplsetobj =& $tplset_handler->create();
			$tplsetobj->setVar('tplset_name', $newtheme);
			$tplsetobj->setVar('tplset_created', time());
			if (!$tplset_handler->insert($tplsetobj)) {
				$msgs[] = '<span style="color:#ff0000;">' . _ERROR . ': ' . sprintf(_MD_TPLSET_CREATE_FAILED, '<strong>' . $newtheme . '</strong>') . '</span><br />';
			} else {
				$tplsetid = $tplsetobj->getVar('tplset_id');
				$templates =& $tpltpl_handler->getObjects(new icms_db_criteria_Item('tpl_tplset', $tplset), TRUE);
				$tcount = count($templates);
				if ($tcount > 0) {
					$msgs[] = _MD_TPLSET_COPYING;
					for ($i = 0; $i < $tcount; $i++) {
						$newtpl =& $templates[$i]->xoopsClone();
						$newtpl->setVar('tpl_tplset', $newtheme);
						$newtpl->setVar('tpl_id', 0);
						$newtpl->setVar('tpl_lastimported', 0);
						$newtpl->setVar('tpl_lastmodified', time());
						if (!$tpltpl_handler->insert($newtpl)) {
							$msgs[] = '&nbsp;<span style="color:#ff0000;">' . _ERROR . ': ' . sprintf(_MD_TPLSET_COPY_FAILED, '<strong>' . $templates[$i]->getVar('tpl_file') . '</strong>')
								. 'ID: <strong>' . $templates[$i]->getVar('tpl_id') . '</strong>' . '</span>';
						} else {
							$msgs[] = '&nbsp;' . sprintf(_MD_TPLSET_COPY_OK, '<strong>' . $templates[$i]->getVar('tpl_file') . '</strong>')
							. ' ID: <strong>' . $newtpl->getVar('tpl_id') . '</strong>';
						}
						unset($newtpl);
					}
					$msgs[] = sprintf(_MD_TPLSET_CREATE_OK, '<strong>' . htmlspecialchars($newtheme, ENT_QUOTES) . '</strong>')
					. ' (ID: <strong>' . $tplsetid . '</strong>)<br />';
				} else {
					$msgs[] = '<span style="color:#ff0000;">' . _ERROR . ': ' . sprintf(_MD_TPLSET_FILE_NOTEXIST, $theme) . '</span>';
				}
			}
		}
		icms_cp_header();
		echo '<code>' . implode("<br />", $msgs) . '</code><br />';
		echo '<br /><a href="admin.php?fct=tplsets">' . _MD_AM_BTOTADMIN . '</a>';
		icms_cp_footer();
		break;

	case 'viewdefault':
		$tpltpl_handler =& icms::handler('icms_view_template_file');
		$tplfile =& $tpltpl_handler->get($id);
		$default =& $tpltpl_handler->find('default', $tplfile->getVar('tpl_type'), $tplfile->getVar('tpl_refid'), NULL, $tplfile->getVar('tpl_file'));
		echo "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>";
		echo '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="' . _LANGCODE . '" lang="' . _LANGCODE 
			. '"><head><meta http-equiv="content-type" content="text/html; charset=' . _CHARSET 
			. '" /><meta http-equiv="content-language" content="' . _LANGCODE 
			. '" /><title>' . htmlspecialchars($icmsConfig['sitename']) . ' Administration' . '</title>'
			. '<link rel="stylesheet" type="text/css" media="all" href="'
			. ICMS_URL . '/icms' . (( defined('_ADM_USE_RTL') && _ADM_USE_RTL )?'_rtl':'') . '.css" />' 
			. '<link rel="stylesheet" type="text/css" media="all" href="'
			. ICMS_MODULES_URL . ' /system/style' . (( defined('_ADM_USE_RTL') && _ADM_USE_RTL )?'_rtl':'') . '.css" />' 
			. '</head><body>';

		if (is_object($default[0])) {
			$tpltpl_handler->loadSource($default[0]);
			$last_modified = $default[0]->getVar('tpl_lastmodified');
			$last_imported = $default[0]->getVar('tpl_lastimported');
			if ($default[0]->getVar('tpl_type') == 'block') {
				$path = ICMS_MODULES_PATH . '/' . $default[0]->getVar('tpl_module') . '/blocks/' . $default[0]->getVar('tpl_file');
			} else {
				$path = ICMS_MODULES_PATH . '/' . $default[0]->getVar('tpl_module') . '/' . $default[0]->getVar('tpl_file');
			}
			$colorchange = '';
			if (!file_exists($path)) {
				$filemodified_date = _MD_NOFILE;
				$lastimported_date = _MD_NOFILE;
			} else {
				$tpl_modified = filemtime($path);
				$filemodified_date = formatTimestamp($tpl_modified, 'l');
				if ($tpl_modified > $last_imported) {
					$colorchange = ' bgcolor="#ffCC99"';
				}
				$lastimported_date = formatTimestamp($last_imported, 'l');
			}
			$form = new icms_form_Theme(_MD_VIEWDEFAULT, 'template_form', 'admin.php');
			$form->addElement(new icms_form_elements_Textarea(_MD_FILEHTML, 'html', $default[0]->getVar('tpl_source'), 25));
			$form->display();
		} else {
			echo _MD_TPLSET_FILE_NOTEXIST;
		}
		echo '<div style="text-align:center;">[<a href="#" onclick="javascript:window.close();">' . _CLOSE . '</a>]</div></body></html>';
		break;

	case 'downloadtpl':
		$tpltpl_handler =& icms::handler('icms_view_template_file');
		$tpl =& $tpltpl_handler->get( (int) ($id), TRUE);
		if (is_object($tpl)) {
			$output = $tpl->getVar('tpl_source');
			strlen($output);
			header('Cache-Control: no-cache, must-revalidate');
			header('Pragma: no-cache');
			header('Content-Type: application/force-download');
			if (preg_match("/MSIE 5.5/", $_SERVER['HTTP_USER_AGENT'])) {
				header('Content-Disposition: filename=' . $tpl->getVar('tpl_file'));
			} else {
				header('Content-Disposition: attachment; filename=' . $tpl->getVar('tpl_file'));
			}
			header('Content-length: ' . strlen($output));
			echo $output;
		}
		break;

	case 'download':
		if (isset($tplset)) {
			if (FALSE !== extension_loaded('zlib')) {
				if (isset($_GET['method']) && $_GET['method'] == 'tar') {
					if (@function_exists('gzencode')) {
						$downloader = new icms_file_TarDownloader();
					}
				} else {
					if (@function_exists('gzcompress')) {
						$downloader = new icms_file_ZipDownloader();
					}
				}
				$tplset_handler = icms::handler('icms_view_template_set');
				$tplsetobj =& $tplset_handler->getByName($tplset);
				$xml = "<"."?xml version=\"1.0\"?".">\r\n<tplset>\r\n  <name>" . $tplset . "</name>\r\n  <dateCreated>" . $tplsetobj->getVar('tplset_created') . "</dateCreated>\r\n  <credits>\r\n" . $tplsetobj->getVar('tplset_credits') . "\r\n  </credits>\r\n  <generator>" . XOOPS_VERSION . "</generator>\r\n  <templates>";
				$tpltpl_handler =& icms::handler('icms_view_template_file');
				$files =& $tpltpl_handler->getObjects(new icms_db_criteria_Item('tpl_tplset', $tplset), TRUE);
				$fcount = count($files);
				if ($fcount > 0) {
					for ($i = 0; $i < $fcount; $i++) {
						if ($files[$i]->getVar('tpl_type') == 'block') {
							$path = $tplset . '/templates/' . $files[$i]->getVar('tpl_module') . '/blocks/' . $files[$i]->getVar('tpl_file');
							$xml .= "\r\n    <template name=\"" . $files[$i]->getVar('tpl_file') . "\">\r\n      <module>" . $files[$i]->getVar('tpl_module') . "</module>\r\n      <type>block</type>\r\n      <lastModified>" . $files[$i]->getVar('tpl_lastmodified') . "</lastModified>\r\n    </template>";
						} elseif ($files[$i]->getVar('tpl_type') == 'module') {
							$path = $tplset . '/templates/' . $files[$i]->getVar('tpl_module') . '/' . $files[$i]->getVar('tpl_file');
							$xml .= "\r\n    <template name=\"" . $files[$i]->getVar('tpl_file') . "\">\r\n      <module>" . $files[$i]->getVar('tpl_module') . "</module>\r\n      <type>module</type>\r\n      <lastModified>" . $files[$i]->getVar('tpl_lastmodified') . "</lastModified>\r\n    </template>";
						}
						$downloader->addFileData($files[$i]->getVar('tpl_source'), $path, $files[$i]->getVar('tpl_lastmodified'));
					}

					$xml .= "\r\n  </templates>";

				}
				//$xml .= "\r\n  </images>
				$xml .= "\r\n</tplset>";
				$downloader->addFileData($xml, $tplset . '/tplset.xml', time());
				echo $downloader->download($tplset, TRUE);
			} else {
				icms_cp_header();
				icms_core_Message::error(_MD_NOZLIB);
				icms_cp_footer();
			}
		}
		break;

	case 'generatetpl':
		icms_cp_header();
		icms_core_Message::confirm(array('tplset' => $tplset, 'moddir' => $moddir, 'file' => $file, 'type' => $type, 'op' => 'generatetpl_go', 'fct' => 'tplsets'), 'admin.php', _MD_PLZGENERATE, _MD_GENERATE);
		icms_cp_footer();
		break;

	case 'generatetpl_go':
		if (!icms::$security->check()) {
			redirect_header('admin.php?fct=tplsets', 3, implode('<br />', icms::$security->getErrors()));
		}
		$tpltpl_handler =& icms::handler('icms_view_template_file');
		$tplfile =& $tpltpl_handler->find('default', $type, NULL, $moddir, $file, TRUE);
		if (count($tplfile) > 0) {
			$newtpl =& $tplfile[0]->xoopsClone();
			$newtpl->setVar('tpl_id', 0);
			$newtpl->setVar('tpl_tplset', $tplset);
			$newtpl->setVar('tpl_lastmodified', time());
			$newtpl->setVar('tpl_lastimported', 0);
			if (!$tpltpl_handler->insert($newtpl)) {
				$err = _ERROR . ': ' . sprintf(_MD_TPLSET_INSERT_FAILED, '<strong>' . $tplfile[0]->getVar('tpl_file') . '</strong>');
			} else {
				if ($tplset == $icmsConfig['template_set']) {

					$icmsAdminTpl->template_touch($newtpl->getVar('tpl_id'));
				}
			}
		} else {
			$err = _MD_TPLSET_FILE_NOTEXIST;
		}
		if (!isset($err)) {
			redirect_header('admin.php?fct=tplsets&amp;op=listtpl&amp;moddir=' . $newtpl->getVar('tpl_module') . '&amp;tplset=' . urlencode($newtpl->getVar('tpl_tplset')), 2, _MD_AM_DBUPDATED);
		}
		icms_cp_header();
		icms_core_Message::error($err);
		echo '<br /><a href="admin.php?fct=tplsets">' . _MD_AM_BTOTADMIN . '</a>';
		icms_cp_footer();
		break;

	case 'generatemod':
		icms_cp_header();
		icms_core_Message::confirm(array('tplset' => $tplset, 'op' => 'generatemod_go', 'fct' => 'tplsets', 'moddir' => $moddir), 'admin.php', _MD_PLZGENERATE, _MD_GENERATE);
		icms_cp_footer();
		break;

	case 'generatemod_go':
		if (!icms::$security->check()) {
			redirect_header('admin.php?fct=tplsets', 3, implode('<br />', icms::$security->getErrors()));
		}

		$tpltpl_handler =& icms::handler('icms_view_template_file');
		icms_cp_header();
		echo '<code>';
		$tplfiles =& $tpltpl_handler->find('default', 'module', NULL, $moddir, NULL, TRUE);
		$fcount = count($tplfiles);
		if ($fcount > 0) {
			echo sprintf(_MD_TPLSET_INSTALLING . $tplset) . '...<br />';
			for ($i = 0; $i < $fcount; $i++) {
				$newtpl =& $tplfiles[$i]->xoopsClone();
				$newtpl->setVar('tpl_id', 0);
				$newtpl->setVar('tpl_tplset', $tplset);
				$newtpl->setVar('tpl_lastmodified', time());
				$newtpl->setVar('tpl_lastimported', 0);
				if (!$tpltpl_handler->insert($newtpl)) {
					echo '&nbsp;&nbsp;<span style="color:#ff0000;">' 
					. _ERROR . ': ' . sprintf(_MD_TPLSET_INSERT_FAILED, '<strong>' . $file . '</strong>') . '</span><br />';
				} else {
					if ($tplset == $icmsConfig['template_set']) {
							
						$icmsAdminTpl->template_touch($newtpl->getVar('tpl_id'));
					}
					echo '&nbsp;&nbsp;' . sprintf(_MD_TPLSET_INSERT_OK, '<strong>' . $tplfiles[$i]->getVar('tpl_file') . '</strong>') . '<br />';
				}
			}
			flush();
			unset($newtpl);
		}
		unset($files);
		$tplfiles =& $tpltpl_handler->find('default', 'block', NULL, $moddir, NULL, TRUE);
		$fcount = count($tplfiles);
		if ($fcount > 0) {
			echo '&nbsp;&nbsp;' . _MD_TPLSET_INSTALLING_BLOCKS . '...<br />';
			for ($i = 0; $i < $fcount; $i++) {
				$newtpl =& $tplfiles[$i]->xoopsClone();
				$newtpl->setVar('tpl_id', 0);
				$newtpl->setVar('tpl_tplset', $tplset);
				$newtpl->setVar('tpl_lastmodified', time());
				$newtpl->setVar('tpl_lastimported', 0);
				if (!$tpltpl_handler->insert($newtpl)) {
					echo '&nbsp;&nbsp;&nbsp;&nbsp;<span style="color:#ff0000;">' 
						. _ERROR . ': ' . sprintf(_MD_TPLSET_BLOCK_INSERT_FAILED,'<strong>' . $tplfiles[$i]->getVar('tpl_file') . '</strong>')
						. '</span><br />';
					echo $newtpl->getHtmlErrors();
				} else {
					if ($tplset == $icmsConfig['template_set']) {
							
						$icmsAdminTpl->template_touch($newtpl->getVar('tpl_id'));
					}
					echo '&nbsp;&nbsp;&nbsp;&nbsp;' . sprintf(_MD_TPLSET_BLOCK_INSERT_OK, '<strong>' . $tplfiles[$i]->getVar('tpl_file') . '</strong>') . '<br />';
				}
			}
			flush();
			unset($newtpl);
		}
		echo '<br />' . sprintf(_MD_TPLSET_TEMPLATE_ADDED, '<strong>' . $tplset . '</strong>') 
			. '<br /></code><br /><a href="admin.php?fct=tplsets">' . _MD_AM_BTOTADMIN . '</a>';
		icms_cp_footer();
		break;

	case 'uploadtar_go':
		if (!icms::$security->check()) {
			redirect_header('admin.php?fct=tplsets', 3, implode('<br />', icms::$security->getErrors()));
		}
		$uploader = new icms_file_MediaUploadHandler(ICMS_UPLOAD_PATH, array(
			'application/x-gzip', 
			'application/gzip', 
			'application/gzip-compressed', 
			'application/x-gzip-compressed', 
			'application/x-tar', 
			'application/x-tar-compressed', 
			'application/octet-stream'
			), 
			1000000);
		$uploader->setPrefix('tmp');
		icms_cp_header();
		echo '<code>';
		if ($uploader->fetchMedia($_POST['xoops_upload_file'][0])) {
			if (!$uploader->upload()) {
				icms_core_Message::error($uploader->getErrors());
			} else {
				$tar = new icms_file_TarFileHandler();
				$tar->openTar($uploader->getSavedDestination());
				@unlink($uploader->getSavedDestination());
				$themefound = FALSE;
				foreach ($tar->files as $id => $info) {
					$infoarr = explode('/', str_replace("\\", '/', $info['name']));
					if (!isset($tplset_name)) {
						$tplset_name = trim($infoarr[0]);
					} else {
						$tplset_name = trim($tplset_name);
						if ($tplset_name == '') {
							$tplset_name = trim($infoarr[0]);
						}
					}
					if ($tplset_name != '') {
						break;
					}
				}

				if ($tplset_name == '') {
					echo '<span style="color:#ff0000;">' . _ERROR . ': ' . _MD_TPLSET_NAME_NOT_BLANK . '</span><br />';
				} elseif  (preg_match('/[' . preg_quote('\/:*?"<>|','/') . ']/', $tplset_name)) {
					echo '<span style="color:#ff0000;">' . _ERROR . ': ' . _MD_TPLSET_INVALID_NAME . '</span><br />';
				} else {
					$tplset_handler = icms::handler('icms_view_template_set');
					if ($tplset_handler->getCount(new icms_db_criteria_Item('tplset_name', $tplset_name)) > 0) {
						echo '<span style="color:#ff0000;">' . _ERROR . ': ' . sprintf(_MD_TPLSET_EXISTS, '<strong>' . htmlspecialchars($tplset_name, ENT_QUOTES) . '</strong>') . '</span><br />';
					} else {
						$tplset =& $tplset_handler->create();
						$tplset->setVar('tplset_name', $tplset_name);
						$tplset->setVar('tplset_created', time());
						if (!$tplset_handler->insert($tplset)) {
							echo '<span style="color:#ff0000;">' . _ERROR . ': ' . sprintf(_MD_TPLSET_CREATE_FAILED, '<strong>' . htmlspecialchars($tplset_name, ENT_QUOTES) . '</strong>') . '</span><br />';
						} else {
							$tplsetid = $tplset->getVar('tplset_id');
							echo sprintf(_MD_TPLSET_CREATE_OK, '<strong>' . htmlspecialchars($tplset_name, ENT_QUOTES) . '</strong>') 
								. '(ID: <strong>' . $tplsetid . '</strong>)</span><br />';
							$tpltpl_handler = icms::handler('icms_view_template_file');
							$themeimages = array();
							foreach ($tar->files as $id => $info) {
								$infoarr = explode('/', str_replace("\\", '/', $info['name']));
								if (isset($infoarr[3]) && trim($infoarr[3]) == 'blocks') {
									$default =& $tpltpl_handler->find('default', 'block', NULL, trim($infoarr[2]), trim($infoarr[4]));
								} elseif ((!isset($infoarr[4]) || trim($infoarr[4]) == '') && $infoarr[1] == 'templates') {
									$default =& $tpltpl_handler->find('default', 'module', NULL, trim($infoarr[2]), trim($infoarr[3]));
								} elseif ($infoarr[1] == "templates" && $infoarr[2] == "system" && $infoarr[3] == "admin") {
									$file = $infoarr[3];
									for ($i = 4; $i < count($infoarr); $i++) {
										$file .= "/" . $infoarr[$i];
									}
									$default =& $tpltpl_handler->find('default', 'module', NULL, trim($infoarr[2]), $file);
									unset($file);
								} elseif (isset($infoarr[3]) && trim($infoarr[3]) == 'images') {
									$infoarr[2] = trim($infoarr[2]);
									if (preg_match("/(.*)\.(gif|jpg|jpeg|png)$/i", $infoarr[2], $match)) {
										$themeimages[] = array('name' => $infoarr[2], 'content' => $info['file']);
									}
								}

								if (isset($default) && count($default) > 0) {
									$newtpl =& $default[0]->xoopsClone();
									$newtpl->setVar('tpl_id', 0);
									$newtpl->setVar('tpl_tplset', $tplset_name);
									$newtpl->setVar('tpl_source', $info['file'], TRUE);
									$newtpl->setVar('tpl_lastmodified', time());
									if (!$tpltpl_handler->insert($newtpl)) {
										echo '&nbsp;&nbsp;<span style="color:#ff0000;">' . _ERROR . ': ' . sprintf(_MD_TPLSET_INSERT_FAILED, '<strong>' . $info['name'] . '</strong>') . '</span><br />';
									} else {
										echo '&nbsp;&nbsp;' . sprintf(_MD_TPLSET_INSERT_OK, '<strong>' . $info['name'] . '</strong>') . '<br />';
									}
									unset($default);
								} else {
									if (strrpos($info["name"], "tplset.xml") === FALSE) {
										echo '&nbsp;&nbsp;<span style="color:#ff0000;">' . _ERROR . ': ' . sprintf(_MD_TPLSET_NOT_FOUND, '<strong>' . $info['name'] . '</strong>') . '</span><br />';
									}
								}
								unset($info);
							}

							$icount = count($themeimages);
							if ($icount > 0) {
								$imageset_handler = icms::handler('icms_image_set');
								$imgset =& $imageset_handler->create();
								$imgset->setVar('imgset_name', $tplset_name);
								$imgset->setVar('imgset_refid', 0);
								if (!$imageset_handler->insert($imgset)) {
									echo '&nbsp;&nbsp;<span style="color:#ff0000;">' . _ERROR . ': ' . _MD_TPLSET_IMGSET_CREATE_FAILED . '</span><br />';
								} else {
									$newimgsetid = $imgset->getVar('imgset_id');
									echo '&nbsp;&nbsp;' . sprintf(_MD_TPLSET_IMGSET_CREATED, '<strong>' . htmlspecialchars($tplset_name, ENT_QUOTES) . '</strong>') 
										. '(ID: <strong>' . $newimgsetid . '</strong>)<br />';
									if (!$imageset_handler->linktplset($newimgsetid, $tplset_name)) {
										echo '&nbsp;&nbsp;<span style="color:#ff0000;">' . _ERROR . ': ' . sprintf(_MD_TPLSET_IMGSET_LINK_FAILED, '<strong>' . htmlspecialchars($tplset_name, ENT_QUOTES) . '</strong>') . '</span><br />';
									}
								}
							}
						}
					}
				}
			}
		} else {
			$err = implode('<br />', $uploader->getErrors(FALSE));
			echo $err;
		}
		echo '</code><br /><a href="admin.php?fct=tplsets">' . _MD_AM_BTOTADMIN . '</a>';
		icms_cp_footer();
		break;

	case 'previewtpl':
		if (!icms::$security->check()) {
			redirect_header('admin.php?fct=tplsets', 3, implode('<br />', icms::$security->getErrors()));
		}

			
		$html = icms_core_DataFilter::stripSlashesGPC($html);
		$tpltpl_handler =& icms::handler('icms_view_template_file');
		$tplfile =& $tpltpl_handler->get($id, TRUE);
		$xoopsTpl = new icms_view_Tpl();

		if (is_object($tplfile)) {
			$dummylayout = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'
			. '<html><head><meta http-equiv="content-type" content="text/html; charset=' . _CHARSET 
			. '" /><meta http-equiv="content-language" content="' . _LANGCODE 
			. '" /><title>' . $icmsConfig['sitename'] . '</title>'
			. '<link rel="stylesheet" type="text/css" media="screen" href="' . ICMS_URL . '/icms' 
				. (( defined('_ADM_USE_RTL') && _ADM_USE_RTL ) 
					? '_rtl'
					:'') 
				. '.css" /><link rel="stylesheet" type="text/css" media="screen" href="' 
				. xoops_getcss($icmsConfig['theme_set']) . '" />';

			$css =& $tpltpl_handler->find($icmsConfig['template_set'], 'css', 0, NULL, NULL, TRUE);
			$csscount = count($css);

			for ($i = 0; $i < $csscount; $i++) {
				$dummylayout .= "\n" . $css[$i]->getVar('tpl_source');
			}

			$dummylayout .= "\n" . '</style></head><body><div id="xo-canvas"><{$content}></div></body></html>';
			if ($tplfile->getVar('tpl_type') == 'block') {

				$block = new icms_view_block_Object($tplfile->getVar('tpl_refid'));
				$xoopsTpl->assign('block', $block->buildBlock());
			}

			$dummytpl = '_dummytpl_' . time() . '.html';
			$fp = fopen(ICMS_CACHE_PATH . '/' . $dummytpl, 'w');
			fwrite($fp, $html);
			fclose($fp);
			$xoopsTpl->assign('content', $xoopsTpl->fetch('file:' . ICMS_CACHE_PATH . '/' . $dummytpl));
			$xoopsTpl->clear_compiled_tpl('file:' . ICMS_CACHE_PATH . '/' . $dummytpl);
			unlink(ICMS_CACHE_PATH . '/' . $dummytpl);
			$dummyfile = '_dummy_' . time() . '.html';
			$fp = fopen(ICMS_CACHE_PATH . '/' . $dummyfile, 'w');
			fwrite($fp, $dummylayout);
			fclose($fp);
			$tplset= $tplfile->getVar('tpl_tplset');
			$tform = array('tpl_tplset' => $tplset, 'tpl_id' => $id, 'tpl_file' => $tplfile->getVar('tpl_file'), 'tpl_desc' => $tplfile->getVar('tpl_desc'), 'tpl_lastmodified' => $tplfile->getVar('tpl_lastmodified'), 'tpl_source' => htmlspecialchars($html, ENT_QUOTES), 'tpl_module' => $moddir);
			include_once ICMS_MODULES_PATH . '/system/admin/tplsets/tplform.php';
			icms_cp_header();
			echo '<a href="admin.php?fct=tplsets">'. _MD_TPLMAIN .'</a>' 
				. '&nbsp;<span style="font-weight:bold;">&raquo;&raquo;</span>&nbsp;'
				. '<a href="./admin.php?fct=tplsets&amp;op=listtpl&amp;moddir=' . $moddir 
				. '&amp;tplset=' . urlencode($tplset) . '">' . htmlspecialchars($tplset, ENT_QUOTES) . '</a>'
				. '&nbsp;<span style="font-weight:bold;">&raquo;&raquo;</span>&nbsp;' . _MD_EDITTEMPLATE 
				. '<br /><br />';
			$form->display();
			icms_cp_footer();
			echo '<script type="text/javascript">
			<!--//
			preview_window = openWithSelfMain("", "popup", 680, 450, TRUE);
			preview_window.document.clear();
			';
			$lines = preg_split("/(\r\n|\r|\n)( *)/", $xoopsTpl->fetch('file:' . ICMS_CACHE_PATH . '/' . $dummyfile));
			$xoopsTpl->clear_compiled_tpl('file:' . ICMS_CACHE_PATH . '/' . $dummyfile);
			unlink(ICMS_CACHE_PATH . '/' . $dummyfile);
			foreach ($lines as $line) {
				echo 'preview_window.document.writeln("' . str_replace('"', '\"', $line) . '");';
			}
			echo '
			preview_window.focus();
			preview_window.document.close();
			//-->
			</script>';
		}
		break;

	case 'update':
		if (!icms::$security->check()) {
			redirect_header('admin.php?fct=tplsets', 3, implode('<br />', icms::$security->getErrors()));
		}
		$uploader = new icms_file_MediaUploadHandler(ICMS_UPLOAD_PATH, array('text/html', 'application/x-cdf'), 200000);
		$uploader->setPrefix('tmp');
		$msg = array();
		foreach ($_POST['xoops_upload_file'] as $upload_file) {
			// '.' is converted to '_' when upload
			$upload_file2 = str_replace('.', '_', $upload_file);
			if ($uploader->fetchMedia($upload_file2)) {
				if (!$uploader->upload()) {
					$msg[] = $uploader->getErrors();
				} else {
					$tpltpl_handler =& icms::handler('icms_view_template_file');
					if (!isset($old_template[$upload_file])) {
						$tplfile =& $tpltpl_handler->find('default', NULL, NULL, $moddir, $upload_file);
						if (count($tplfile) > 0) {
							$tpl =& $tplfile[0]->xoopsClone();
							$tpl->setVar('tpl_id', 0);
							$tpl->setVar('tpl_tplset', $tplset);
						} else {
							$msg[] = sprintf(_MD_TPLSET_FILE_UNNECESSARY, '<strong>' . $upload_file . '</strong>');
							continue;
						}
					} else {
						$tpl =& $tpltpl_handler->get($old_template[$upload_file]);
					}
					$tpl->setVar('tpl_lastmodified', time());
					$fp = @fopen($uploader->getSavedDestination(), 'r');
					$fsource = @fread($fp, filesize($uploader->getSavedDestination()));
					@fclose($fp);
					$tpl->setVar('tpl_source', $fsource, TRUE);
					@unlink($uploader->getSavedDestination());
					if (!$tpltpl_handler->insert($tpl)) {
						$msg[] = sprintf(_MD_TPLSET_INSERT_FAILED, $upload_file);
					} else {
						$msg[] = sprintf(_MD_TPLSET_UPDATED, '<strong>' . $upload_file . '</strong>');
						if ($tplset == $icmsConfig['template_set']) {

							if ($icmsAdminTpl->template_touch($tpl->getVar('tpl_id'), TRUE)) {
								$msg[] = sprintf(_MD_TPLSET_COMPILED, '<strong>' . $upload_file . '</strong>');
							}
						}
					}
				}
			} else {
				if ($uploader->getMediaName() == '') {
					continue;
				} else {
					$msg[] = $uploader->getErrors();
				}
			}
		}

		icms_cp_header();
		echo '<code>' . implode('<br />', $msg) . '<br />';

		echo '</code><br /><a href="admin.php?fct=tplsets&amp;op=listtpl&amp;tplset=' . urlencode($tplset) 
			. '&amp;moddir=' . $moddir . '">' . _MD_AM_BTOTADMIN . '</a>';
		icms_cp_footer();
		break;

	case 'importtpl':
		icms_cp_header();
		if (!empty($id)) {
			icms_core_Message::confirm(array('tplset' => $tplset, 'moddir' => $moddir, 'id' => $id, 'op' => 'importtpl_go', 'fct' => 'tplsets'), 'admin.php', _MD_RUSUREIMPT, _MD_IMPORT);
		} elseif (isset($file)) {
			icms_core_Message::confirm(array('tplset' => $tplset, 'moddir' => $moddir, 'file' => $file, 'op' => 'importtpl_go', 'fct' => 'tplsets'), 'admin.php', _MD_RUSUREIMPT, _MD_IMPORT);
		}
		icms_cp_footer();
		break;

	case 'importtpl_go':
		if (!icms::$security->check()) {
			redirect_header('admin.php?fct=tplsets', 3, implode('<br />', icms::$security->getErrors()));
		}
		$tpltpl_handler =& icms::handler('icms_view_template_file');
		$tplfile = '';
		if (!empty($id)) {
			$tplfile =& $tpltpl_handler->get($id, TRUE);
		} else {
			$tplfiles =& $tpltpl_handler->find('default', NULL, NULL, NULL, trim($file), TRUE);
			$tplfile = (count($tplfiles) > 0) ? $tplfiles[0] : '';
		}

		$error = TRUE;
		if (is_object($tplfile)) {
			switch ($tplfile->getVar('tpl_type')) {
				case 'module':
					$filepath = ICMS_THEME_PATH . '/' . $tplset . '/templates/' . $tplfile->getVar('tpl_module') . '/' . $tplfile->getVar('tpl_file');
					break;
				case 'block':
					$filepath = ICMS_THEME_PATH . '/' . $tplset . '/templates/' . $tplfile->getVar('tpl_module') . '/blocks/' . $tplfile->getVar('tpl_file');
					break;
				default:
					break;
			}

			if (file_exists($filepath)) {
				if (FALSE !== $fp = fopen($filepath, 'r')) {
					$filesource = fread($fp, filesize($filepath));
					fclose($fp);
					$tplfile->setVar('tpl_source', $filesource, TRUE);
					$tplfile->setVar('tpl_tplset', $tplset);
					$tplfile->setVar('tpl_lastmodified', time());
					$tplfile->setVar('tpl_lastimported', time());
					if (!$tpltpl_handler->insert($tplfile)) {
					} else {
						$error = FALSE;
					}
				}
			}
		}

		if (FALSE !== $error) {
			icms_cp_header();
			icms_core_Message::error(_MD_TPLSET_IMPORT_FAILED . ' ' . $filepath);
			echo '<br /><a href="admin.php?fct=tplsets&amp;op=listtpl&amp;tplset=' . urlencode($tplset) . '&amp;moddir=' . $moddir . '">' . _MD_AM_BTOTADMIN . '</a>';
			icms_cp_footer();
			exit();
		}
		redirect_header('admin.php?fct=tplsets&amp;op=listtpl&amp;moddir=' . $tplfile->getVar('tpl_module') . '&amp;tplset=' . urlencode($tplfile->getVar('tpl_tplset')), 2, _MD_AM_DBUPDATED);
		break;

	default:
		break;
}
