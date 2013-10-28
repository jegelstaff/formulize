<?php
/**
 * Images Manager
 *
 * System tool that allow manage images to use in the site
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		Administration
 * @subpackage	Images
 * @since		1.2
 * @author		Rodrigo Pereira Lima (AKA TheRplima) <therplima@impresscms.org>
 * @version		SVN: $Id: main.php 22506 2011-09-01 06:49:28Z blauer-fisch $
 */

/*
 * GET variables
 * (str) op:		list, listimg, addcat, editcat, updatecat, delcat, delcatok,
 * 					reordercateg, addfile, save, delfile, delfileok, cloneimg
 * 					save_edit_ok
 * (int) limit
 * (int) start
 * (int) imgcat_id
 * (int) image_id
 * (str) msg
 * (str) fct:		images
 * 
 * POST variables
 * (str) op
 * (int) limit
 * (int) start
 * (int) imgcat_id
 * (int) image_id
 * (str) query
 * 
 */
if (!is_object(icms::$user) || !is_object($icmsModule) || !icms::$user->isAdmin($icmsModule->getVar('mid'))) {
	exit(_CT_ACCESS_DENIED);
} else {
	if (!empty($_POST)) foreach ($_POST as $k => $v) ${$k} = StopXSS($v);
	if (!empty($_GET)) foreach ($_GET as $k => $v) ${$k} = StopXSS($v);
	$op = (isset($_GET['op'])) ? trim(filter_input(INPUT_GET, 'op')) : ((isset($_POST['op'])) ? trim(filter_input(INPUT_POST, 'op')) : 'list');
	$image_id = (isset($_GET['image_id'])) ? (int) $_GET['image_id'] : ((isset($_POST['image_id'])) ? (int) $_POST['image_id'] : NULL);
	$imgcat_id = (isset($_GET['imgcat_id'])) ? (int) $_GET['imgcat_id'] : ((isset($_POST['imgcat_id'])) ? (int) $_POST['imgcat_id'] : NULL);
	$limit = (isset($_GET['limit'])) ? (int) $_GET['limit'] : ((isset($_POST['limit'])) ? (int) $_POST['limit'] : 15);
	$start = (isset($_GET['start'])) ? (int) $_GET['start'] : ((isset($_POST['start'])) ? (int) $_POST['start'] : 0);

	switch ($op) {
		default:
		case 'list':
			icms_cp_header();
			echo imanager_index($imgcat_id);
			icms_cp_footer();
			break;
			
		case 'listimg':
			icms_cp_header();
			echo imanager_listimg($imgcat_id, $start);
			icms_cp_footer();
			break;
			
		case 'addcat':
			imanager_addcat();
			break;
			
		case 'editcat':
			imanager_editcat($imgcat_id);
			icms_cp_footer();
			break;
			
		case 'updatecat':
			imanager_updatecat();
			break;
			
		case 'delcat':
			icms_cp_header();
			icms_core_Message::confirm(array('op' => 'delcatok', 'imgcat_id' => $imgcat_id, 'fct' => 'images'), 'admin.php', _MD_RUDELIMGCAT);
			icms_cp_footer();
			break;
			
		case 'delcatok':
			imanager_delcatok($imgcat_id);
			break;
			
		case 'reordercateg':
			imanager_reordercateg();
			break;
			
		case 'addfile':
			imanager_addfile();
			break;
			
		case 'save':
			imanager_updateimage();
			break;
			
		case 'delfile':
			icms_cp_header();
			$image_handler = icms::handler('icms_image');
			$image =& $image_handler->get($image_id);
			$imgcat_handler = icms::handler('icms_image_category');
			$imagecategory =& $imgcat_handler->get($image->getVar('imgcat_id'));
			$src = '<img src="' . ICMS_MODULES_URL . "/system/admin/images/preview.php?file=" . $image->getVar('image_name') . '" title="' . $image->getVar('image_nicename') . '" /><br />';
			echo '<div style="margin:5px;" align="center">' . $src . '</div>';
			icms_core_Message::confirm(array('op' => 'delfileok', 'image_id' => $image_id, 'imgcat_id' => $imgcat_id, 'fct' => 'images'), 'admin.php', _MD_RUDELIMG);
			icms_cp_footer();
			break;
			
		case 'delfileok':
			imanager_delfileok($image_id, $imgcat_id);
			break;
			
		case 'cloneimg':
			imanager_clone();
			break;
			
		case 'save_edit_ok':
			$msg = isset($_GET['msg']) ? urldecode($_GET['msg']) : NULL;
			redir($imgcat_id, $msg);
			break;
			
	}
}

/**
 * Logic and rendering for the image manager main page
 * 
 * @param $imgcat_id
 */
function imanager_index($imgcat_id = NULL) {
	global $icmsAdminTpl, $icmsConfig, $limit;

	if (!is_object(icms::$user)) {
		$groups = array(XOOPS_GROUP_ANONYMOUS);
		$admin = FALSE;
	} else {
		$groups =& icms::$user->getGroups();
		$admin = (!icms::$user->isAdmin(1)) ? FALSE : TRUE;
	}

	if (!is_writable(ICMS_IMANAGER_FOLDER_PATH)) {
		icms_core_Message::warning(sprintf(_WARNINNOTWRITEABLE, str_ireplace(ICMS_ROOT_PATH, "", ICMS_IMANAGER_FOLDER_PATH)));
		echo '<br />';
	}

	$imgcat_handler = icms::handler('icms_image_category');

	$criteriaRead = new icms_db_criteria_Compo();
	if (is_array($groups) && !empty($groups)) {
		$criteriaTray = new icms_db_criteria_Compo();
		foreach ($groups as $gid) {
			$criteriaTray->add(new icms_db_criteria_Item('gperm_groupid', $gid), 'OR');
		}
		$criteriaRead->add($criteriaTray);
		$criteriaRead->add(new icms_db_criteria_Item('gperm_name', 'imgcat_read'));
		$criteriaRead->add(new icms_db_criteria_Item('gperm_modid', 1));
	}
	$id = (NULL !== $imgcat_id ? $imgcat_id : 0);
	$criteriaRead->add(new icms_db_criteria_Item('imgcat_pid', $id));
	$imagecategorys =& $imgcat_handler->getObjects($criteriaRead);
	$criteriaWrite = new icms_db_criteria_Compo();
	if (is_array($groups) && !empty($groups)) {
		$criteriaWrite->add($criteriaTray);
		$criteriaWrite->add(new icms_db_criteria_Item('gperm_name', 'imgcat_write'));
		$criteriaWrite->add(new icms_db_criteria_Item('gperm_modid', 1));
	}
	$criteriaWrite->add(new icms_db_criteria_Item('imgcat_pid', $id));
	$imagecategorysWrite =& $imgcat_handler->getObjects($criteriaWrite);

	$icmsAdminTpl->assign('lang_imanager_title', _IMGMANAGER);
	$icmsAdminTpl->assign('lang_imanager_catid', _MD_IMAGECATID);
	$icmsAdminTpl->assign('lang_imanager_catname', _MD_IMAGECATNAME);
	$icmsAdminTpl->assign('lang_imanager_catmsize', _MD_IMAGECATMSIZE);
	$icmsAdminTpl->assign('lang_imanager_catmwidth', _MD_IMAGECATMWIDTH);
	$icmsAdminTpl->assign('lang_imanager_catmheight', _MD_IMAGECATMHEIGHT);
	$icmsAdminTpl->assign('lang_imanager_catstype', _MD_IMAGECATSTYPE);
	$icmsAdminTpl->assign('lang_imanager_catdisp', _MD_IMAGECATDISP);
	$icmsAdminTpl->assign('lang_imanager_catautoresize', _MD_IMAGECATATUORESIZE);
	$icmsAdminTpl->assign('lang_imanager_catweight', _MD_IMAGECATWEIGHT);
	$icmsAdminTpl->assign('lang_imanager_catsubs', _MD_IMAGECATSUBS);
	$icmsAdminTpl->assign('lang_imanager_catqtde', _MD_IMAGECATQTDE);
	$icmsAdminTpl->assign('lang_imanager_catoptions', _MD_IMAGECATOPTIONS);

	$icmsAdminTpl->assign('lang_imanager_cat_edit', _EDIT);
	$icmsAdminTpl->assign('lang_imanager_cat_del', _DELETE);
	$icmsAdminTpl->assign('lang_imanager_cat_listimg', _LIST);
	$icmsAdminTpl->assign('lang_imanager_cat_submit', _SUBMIT);
	$icmsAdminTpl->assign('lang_imanager_folder_not_writable', IMANAGER_FOLDER_NOT_WRITABLE);

	$icmsAdminTpl->assign('lang_imanager_cat_addnewcat', _MD_ADDIMGCATBTN);
	$icmsAdminTpl->assign('lang_imanager_cat_addnewimg', _MD_ADDIMGBTN);
	$icmsAdminTpl->assign('lang_imanager_viewsubs', _MD_IMAGE_VIEWSUBS);

	$icmsAdminTpl->assign('token', icms::$security->getTokenHTML());
	$icmsAdminTpl->assign('catcount', count($imagecategorys));
	$icmsAdminTpl->assign('writecatcount', count($imagecategorysWrite));
	$icmsAdminTpl->assign('isAdmin', $admin);

	$icmsAdminTpl->assign('imagecategorys', $imagecategorys);
	$icmsAdminTpl->assign('admnav', adminNav($imgcat_id));

	$image_handler = icms::handler('icms_image');
	$count = $msize = $subs = $nwrite = array();
	$hasnwrite = array();
	$icmsAdminTpl->assign('catcount', $catcount = count($imagecategorys));
	for ($i = 0; $i < $catcount; $i++) {
		$nwrite[$i] = is_writable($imgcat_handler->getCategFolder($imagecategorys[$i]));
		if (!$nwrite[$i]) {
			$hasnwrite[] = $imagecategorys[$i]->getVar('imgcat_name');
		}
		$msize[$i] = icms_convert_size($imagecategorys[$i]->getVar('imgcat_maxsize'));
		$count[$i] = $image_handler->getCount(new icms_db_criteria_Item('imgcat_id', $imagecategorys[$i]->getVar('imgcat_id')));
		$criteriaRead = new icms_db_criteria_Compo();
		if (is_array($groups) && !empty($groups)) {
			$criteriaTray = new icms_db_criteria_Compo();
			foreach ($groups as $gid) {
				$criteriaTray->add(new icms_db_criteria_Item('gperm_groupid', $gid), 'OR');
			}
			$criteriaRead->add($criteriaTray);
			$criteriaRead->add(new icms_db_criteria_Item('gperm_name', 'imgcat_read'));
			$criteriaRead->add(new icms_db_criteria_Item('gperm_modid', 1));
		}
		$id = (NULL !== $imgcat_id ? $imgcat_id : 0);
		$criteriaRead->add(new icms_db_criteria_Item('imgcat_pid', $imagecategorys[$i]->getVar('imgcat_id')));
		$subs[$i]  = count($imgcat_handler->getObjects($criteriaRead));
	}
	$hasnwrite = implode(', ', $hasnwrite);
	$scount = array();
	foreach ($subs as $k=>$v) {
		$criteriaRead = new icms_db_criteria_Compo();
		if (is_array($groups) && !empty($groups)) {
			$criteriaTray = new icms_db_criteria_Compo();
			foreach ($groups as $gid) {
				$criteriaTray->add(new icms_db_criteria_Item('gperm_groupid', $gid), 'OR');
			}
			$criteriaRead->add($criteriaTray);
			$criteriaRead->add(new icms_db_criteria_Item('gperm_name', 'imgcat_read'));
			$criteriaRead->add(new icms_db_criteria_Item('gperm_modid', 1));
		}
		$id = (NULL !== $imgcat_id ? $imgcat_id : 0);
		$criteriaRead->add(new icms_db_criteria_Item('imgcat_pid', $imagecategorys[$k]->getVar('imgcat_id')));
		$ssubs = $imgcat_handler->getObjects($criteriaRead);
		$sc = 0;
		foreach ($ssubs as $id=>$va) {
			$sc += $image_handler->getCount(new icms_db_criteria_Item('imgcat_id', $va->getVar('imgcat_id')));
		}
		$scount[$k] = $sc;
	}
	$icmsAdminTpl->assign('nwrite', $nwrite);
	$icmsAdminTpl->assign('hasnwrite', $hasnwrite);
	$icmsAdminTpl->assign('msize', $msize);
	$icmsAdminTpl->assign('count', $count);
	$icmsAdminTpl->assign('subs', $subs);
	$icmsAdminTpl->assign('scount', $scount);

	if (!empty($catcount)) {
		$form = new icms_form_Theme(_ADDIMAGE, 'image_form', 'admin.php', 'post', TRUE);
		$form->setExtra('enctype="multipart/form-data"');
		$form->addElement(new icms_form_elements_Text(_IMAGENAME, 'image_nicename', 50, 255), TRUE);
		$list =& $imgcat_handler->getCategList($groups, 'imgcat_write');
		$select = new icms_form_elements_Select(_IMAGECAT, 'imgcat_id', $imgcat_id);
		$list[0] = '--------------------';
		ksort($list);
		$select->addOptionArray($list);
		$form->addElement($select, TRUE);
		$form->addElement(new icms_form_elements_File(_IMAGEFILE, 'image_file', 5000000));
		$form->addElement(new icms_form_elements_Text(_IMGWEIGHT, 'image_weight', 3, 4, 0));
		$form->addElement(new icms_form_elements_Radioyn(_IMGDISPLAY, 'image_display', 1, _YES, _NO));
		$form->addElement(new icms_form_elements_Hidden('op', 'addfile'));
		$form->addElement(new icms_form_elements_Hidden('fct', 'images'));
		$tray = new icms_form_elements_Tray('', '');
		$tray->addElement(new icms_form_elements_Button('', 'img_button', _SUBMIT, 'submit'));
		$btn = new icms_form_elements_Button('', 'reset', _CANCEL, 'button');
		$btn->setExtra('onclick="document.getElementById(\'addimgform\').style.display = \'none\'; return FALSE;"');
		$tray->addElement($btn);
		$form->addElement($tray);
		$icmsAdminTpl->assign('addimgform', $form->render());
	}
	$form = new icms_form_Theme(_MD_ADDIMGCAT, 'imagecat_form', 'admin.php', 'post', TRUE);
	$list =& $imgcat_handler->getCategList($groups, 'imgcat_write');
	$sup = new icms_form_elements_Select(_MD_IMGCATPARENT, 'imgcat_pid', $imgcat_id);
	$list[0] = '--------------------';
	ksort($list);
	$sup->addOptionArray($list);
	$form->addElement($sup);
	$form->addElement(new icms_form_elements_Text(_MD_IMGCATNAME, 'imgcat_name', 50, 255), TRUE);
	$form->addElement(new icms_form_elements_select_Group(_MD_IMGCATRGRP, 'readgroup', TRUE, XOOPS_GROUP_ADMIN, 5, TRUE));
	$form->addElement(new icms_form_elements_select_Group(_MD_IMGCATWGRP, 'writegroup', TRUE, XOOPS_GROUP_ADMIN, 5, TRUE));
	$form->addElement(new icms_form_elements_Text(_IMGMAXSIZE, 'imgcat_maxsize', 10, 10, 50000));
	$form->addElement(new icms_form_elements_Text(_IMGMAXWIDTH, 'imgcat_maxwidth', 3, 4, 120));
	$form->addElement(new icms_form_elements_Text(_IMGMAXHEIGHT, 'imgcat_maxheight', 3, 4, 120));
	$form->addElement(new icms_form_elements_Text(_MD_IMGCATWEIGHT, 'imgcat_weight', 3, 4, 0));
	$form->addElement(new icms_form_elements_Radioyn(_MD_IMGCATDISPLAY, 'imgcat_display', 1, _YES, _NO));
	$storetype = new icms_form_elements_Radio(_MD_IMGCATSTRTYPE, 'imgcat_storetype', 'file');
	$storetype->setDescription('<span style="color:#ff0000;">' . _MD_STRTYOPENG . '</span>');
	$storetype->addOptionArray(array('file' => sprintf(_MD_ASFILE, str_ireplace(ICMS_ROOT_PATH, '', ICMS_IMANAGER_FOLDER_PATH) . '/foldername'), 'db' => _MD_INDB));
	$storetype->setExtra('onchange="actField(this.value,\'imgcat_foldername\');"');
	$form->addElement($storetype);
	$fname = new icms_form_elements_Text(_MD_IMGCATFOLDERNAME, 'imgcat_foldername', 50, 255, '');
	$fname->setDescription('<span style="color:#ff0000;">' . _MD_IMGCATFOLDERNAME_DESC . '<br />' . _MD_STRTYOPENG . '</span>');
	$js = 'var fname = document.getElementById("imgcat_foldername");';
	$js .= 'if (fname.disabled == FALSE && fname.value == "") {alert("' . sprintf(_FORM_ENTER, _MD_IMGCATFOLDERNAME ) . '"); return FALSE;}';
	$fname->customValidationCode[] = $js;
	$form->addElement($fname, TRUE);
	$form->addElement(new icms_form_elements_Hidden('op', 'addcat'));
	$form->addElement(new icms_form_elements_Hidden('fct', 'images'));
	$tray1 = new icms_form_elements_Tray('', '');
	$tray1->addElement(new icms_form_elements_Button('', 'imgcat_button', _SUBMIT, 'submit'));
	$btn = new icms_form_elements_Button('', 'reset', _CANCEL, 'button');
	$btn->setExtra('onclick="document.getElementById(\'addcatform\').style.display = \'none\'; return FALSE;"');
	$tray1->addElement($btn);
	$form->addElement($tray1);
	$icmsAdminTpl->assign('addcatform', $form->render());

	return $icmsAdminTpl->fetch('db:admin/images/system_adm_imagemanager.html');
}

/**
 * Logic and rendering for listing images within a category
 * 
 * @param int $imgcat_id	Unique ID of the image category to list
 * @param int $start		Starting location for long lists
 */
function imanager_listimg($imgcat_id, $start = 0) {
	global $icmsAdminTpl;

	if (!is_object(icms::$user)) {
		$groups = array(XOOPS_GROUP_ANONYMOUS);
		$admin = FALSE;
	} else {
		$groups =& icms::$user->getGroups();
		$admin = (!icms::$user->isAdmin(1)) ? FALSE : TRUE;
	}

	$query = isset($_POST['query']) ? $_POST['query'] : NULL;

	if ($imgcat_id <= 0) {
		redirect_header('admin.php?fct=images', 1, '');
	}
	$imgcat_handler = icms::handler('icms_image_category');
	$imagecategory =& $imgcat_handler->get($imgcat_id);
	$categ_path = $imgcat_handler->getCategFolder($imagecategory);
	$categ_url  = $imgcat_handler->getCategFolder($imagecategory, 1, 'url');
	if (!is_object($imagecategory)) {
		redirect_header('admin.php?fct=images', 1, '');
	}

	$icmsAdminTpl->assign('admnav', adminNav($imgcat_id, '/', 1));
	$icmsAdminTpl->assign('lang_imanager_title', _IMGMANAGER);
	$icmsAdminTpl->assign('lang_imanager_catmsize', _MD_IMAGECATMSIZE);
	$icmsAdminTpl->assign('lang_imanager_catmwidth', _MD_IMAGECATMWIDTH);
	$icmsAdminTpl->assign('lang_imanager_catmheight', _MD_IMAGECATMHEIGHT);
	$icmsAdminTpl->assign('lang_imanager_catstype', _MD_IMAGECATSTYPE);
	$icmsAdminTpl->assign('lang_imanager_catdisp', _MD_IMAGECATDISP);
	$icmsAdminTpl->assign('lang_imanager_catsubs', _MD_IMAGECATSUBS);
	$icmsAdminTpl->assign('lang_imanager_catqtde', _MD_IMAGECATQTDE);
	$icmsAdminTpl->assign('lang_imanager_catoptions', _MD_IMAGECATOPTIONS);

	$icmsAdminTpl->assign('lang_imanager_cat_edit', _EDIT);
	$icmsAdminTpl->assign('lang_imanager_cat_clone', _CLONE);
	$icmsAdminTpl->assign('lang_imanager_cat_del', _DELETE);
	$icmsAdminTpl->assign('lang_imanager_cat_listimg', _LIST);
	$icmsAdminTpl->assign('lang_imanager_cat_submit', _SUBMIT);
	$icmsAdminTpl->assign('lang_imanager_folder_not_writable', IMANAGER_FOLDER_NOT_WRITABLE);
	$icmsAdminTpl->assign('lang_imanager_cat_back', _BACK);
	$icmsAdminTpl->assign('lang_imanager_cat_addimg', _ADDIMAGE);

	$icmsAdminTpl->assign('lang_imanager_cat_addnewcat', _MD_ADDIMGCATBTN);
	$icmsAdminTpl->assign('lang_imanager_cat_addnewimg', _MD_ADDIMGBTN);

	$icmsAdminTpl->assign('cat_maxsize', icms_convert_size($imagecategory->getVar('imgcat_maxsize')));
	$icmsAdminTpl->assign('cat_maxwidth', $imagecategory->getVar('imgcat_maxwidth'));
	$icmsAdminTpl->assign('cat_maxheight', $imagecategory->getVar('imgcat_maxheight'));
	$icmsAdminTpl->assign('cat_storetype', $imagecategory->getVar('imgcat_storetype'));
	$icmsAdminTpl->assign('cat_display', $imagecategory->getVar('imgcat_display'));
	$icmsAdminTpl->assign('cat_id', $imagecategory->getVar('imgcat_id'));

	$criteriaRead = new icms_db_criteria_Compo();
	if (is_array($groups) && !empty($groups)) {
		$criteriaTray = new icms_db_criteria_Compo();
		foreach ($groups as $gid) {
			$criteriaTray->add(new icms_db_criteria_Item('gperm_groupid', $gid), 'OR');
		}
		$criteriaRead->add($criteriaTray);
		$criteriaRead->add(new icms_db_criteria_Item('gperm_name', 'imgcat_read'));
		$criteriaRead->add(new icms_db_criteria_Item('gperm_modid', 1));
	}
	$criteriaRead->add(new icms_db_criteria_Item('imgcat_pid', $imagecategory->getVar('imgcat_id')));
	$subcats = $imgcat_handler->getObjects($criteriaRead);
	$subs  = count($subcats);
	$icmsAdminTpl->assign('cat_subs', $subs);

	$image_handler = icms::handler('icms_image');

	$criteriaRead = new icms_db_criteria_Compo();
	if (is_array($groups) && !empty($groups)) {
		$criteriaTray = new icms_db_criteria_Compo();
		foreach ($groups as $gid) {
			$criteriaTray->add(new icms_db_criteria_Item('gperm_groupid', $gid), 'OR');
		}
		$criteriaRead->add($criteriaTray);
		$criteriaRead->add(new icms_db_criteria_Item('gperm_name', 'imgcat_read'));
		$criteriaRead->add(new icms_db_criteria_Item('gperm_modid', 1));
	}
	$id = (NULL !== $imgcat_id ? $imgcat_id : 0);
	$criteriaRead->add(new icms_db_criteria_Item('imgcat_pid', $imagecategory->getVar('imgcat_id')));
	$ssubs = $imgcat_handler->getObjects($criteriaRead);
	$sc = 0;
	foreach ($ssubs as $id=>$va) {
		$sc += $image_handler->getCount(new icms_db_criteria_Item('imgcat_id', $va->getVar('imgcat_id')));
	}
	$scount = $sc;
	$icmsAdminTpl->assign('simgcount', $scount);

	$icmsAdminTpl->assign('lang_imanager_img_preview', _PREVIEW);

	$icmsAdminTpl->assign('lang_image_name', _IMAGENAME);
	$icmsAdminTpl->assign('lang_image_mimetype', _IMAGEMIME);
	$icmsAdminTpl->assign('lang_image_cat', _IMAGECAT);
	$icmsAdminTpl->assign('lang_image_weight', _IMGWEIGHT);
	$icmsAdminTpl->assign('lang_image_disp', _IMGDISPLAY);
	$icmsAdminTpl->assign('lang_submit', _SUBMIT);
	$icmsAdminTpl->assign('lang_cancel', _CANCEL);
	$icmsAdminTpl->assign('lang_yes', _YES);
	$icmsAdminTpl->assign('lang_no', _NO);
	$icmsAdminTpl->assign('lang_search', _SEARCH);
	$icmsAdminTpl->assign('lang_search_title', _QSEARCH);

	$icmsAdminTpl->assign('lang_imanager_img_editor', _MD_IMAGE_EDITORTITLE);

	$icmsAdminTpl->assign('lang_imanager_copyof', _MD_IMAGE_COPYOF);

	$icmsAdminTpl->assign('xoops_root_path', ICMS_ROOT_PATH);
	$icmsAdminTpl->assign('query', $query);

	$criteria = new icms_db_criteria_Compo(new icms_db_criteria_Item('imgcat_id', $imgcat_id));
	if (NULL !== $query) {
		$criteria->add(new icms_db_criteria_Item('image_nicename', $query . '%', 'LIKE'));
	}
	$imgcount = $image_handler->getCount($criteria);
	$criteria->setStart($start);
	$criteria->setOrder('DESC');
	$criteria->setSort('image_weight');
	$criteria->setLimit(15);
	$images =& $image_handler->getObjects($criteria, TRUE, TRUE);

	$icmsAdminTpl->assign('imgcount', $imgcount);

	$arrimg = array();
	foreach (array_keys($images) as $i) {
		$arrimg[$i]['id'] = $images[$i]->getVar('image_id');
		$arrimg[$i]['name'] = $images[$i]->getVar('image_name');
		$arrimg[$i]['nicename'] = $images[$i]->getVar('image_nicename');
		$arrimg[$i]['mimetype'] = $images[$i]->getVar('image_mimetype');
		$arrimg[$i]['weight'] = $images[$i]->getVar('image_weight');
		$arrimg[$i]['display'] = $images[$i]->getVar('image_display');
		$arrimg[$i]['categ_id'] = $images[$i]->getVar('imgcat_id');
		$arrimg[$i]['display_nicename'] = icms_core_DataFilter::icms_substr($images[$i]->getVar('image_nicename'), 0, 20);

		$uniq = icms_random_str(5);

		if ($imagecategory->getVar('imgcat_storetype') == 'db') {
			$src = ICMS_MODULES_URL . "/system/admin/images/preview.php?file=" . $images[$i]->getVar('image_name') . '&resize=0';
			$img = WideImage::load($images[$i]->getVar('image_body'))->saveToFile(ICMS_IMANAGER_FOLDER_PATH . '/' . $images[$i]->getVar('image_name'));
			$arrimg[$i]['size'] = icms_convert_size(filesize(ICMS_IMANAGER_FOLDER_PATH . '/' . $images[$i]->getVar('image_name')));
			$img_info = WideImage::load(ICMS_IMANAGER_FOLDER_PATH . '/' . $images[$i]->getVar('image_name'));
			$arrimg[$i]['width'] = $img_info->getWidth();
			$arrimg[$i]['height'] = $img_info->getHeight();
			@unlink(ICMS_IMANAGER_FOLDER_PATH . '/' . $images[$i]->getVar('image_name'));
			$path = ICMS_IMANAGER_FOLDER_PATH . '/';
		} else {
			$url = (substr($categ_url, -1) != '/') ? $categ_url . '/' : $categ_url;
			$path = (substr($categ_path, -1) != '/') ? $categ_path . '/' : $categ_path;
			$src = $url . $images[$i]->getVar('image_name');
			$arrimg[$i]['size'] = icms_convert_size(filesize($path . $images[$i]->getVar('image_name')));
			$img_info = WideImage::load($path . $images[$i]->getVar('image_name'));
			$arrimg[$i]['width'] = $img_info->getWidth();
			$arrimg[$i]['height'] = $img_info->getHeight();
		}
		$arrimg[$i]['src'] = $src . '?' . time();
		$src_lightbox = ICMS_MODULES_URL . "/system/admin/images/preview.php?file=" . $images[$i]->getVar('image_name');
		$preview_url = '<a href="' . $src_lightbox . '" rel="lightbox[categ' . $images[$i]->getVar('imgcat_id') . ']" title="' . $images[$i]->getVar('image_nicename') . '"><img src="'. ICMS_IMAGES_SET_URL . '/actions/viewmag.png" alt="' . _PREVIEW . '" title="' . _PREVIEW . '" /></a>';
		$arrimg[$i]['preview_link'] = $preview_url;

		$extra_perm = array("image/jpeg", "image/jpeg", "image/png", "image/gif");
		if (in_array($images[$i]->getVar('image_mimetype'), $extra_perm)) {
			$arrimg[$i]['hasextra_link'] = 1;
			if (file_exists(ICMS_LIBRARIES_PATH . '/image-editor/image-edit.php')) {
				$arrimg[$i]['editor_link'] = 'window.open(\'' . ICMS_LIBRARIES_URL . '/image-editor/image-edit.php?image_id=' . $images[$i]->getVar('image_id') . '&uniq=' . $uniq . '\',\'icmsDHTMLImageEditor\',\'width=800,height=600,left=\'+parseInt(screen.availWidth/2-400)+\',top=\'+parseInt(screen.availHeight/2-350)+\',resizable=no,location=no,menubar=no,status=no,titlebar=no,scrollbars=no\'); return FALSE;';
			} else {
				$arrimg[$i]['editor_link'] = '';
			}
		} else {
			$arrimg[$i]['hasextra_link'] = 0;
		}

		$list =& $imgcat_handler->getList(array(), NULL, NULL, $imagecategory->getVar('imgcat_storetype'));
		$div = '';
		foreach ($list as $value => $name) {
			$sel = '';
			if ($value == $images[$i]->getVar('imgcat_id')) {
				$sel = ' selected="selected"';
			}
			$div .= '<option value="' . $value . '"' . $sel . '>' . $name . '</option>';
		}
		$arrimg[$i]['ed_selcat_options'] = $div;

		$arrimg[$i]['ed_token'] = icms::$security->getTokenHTML();
		$arrimg[$i]['clone_token'] = icms::$security->getTokenHTML();
	}

	$icmsAdminTpl->assign('images', $arrimg);
	if ($imgcount > 0) {
		if ($imgcount > 15) {
			$nav = new icms_view_PageNav($imgcount, 15, $start, 'start', 'fct=images&amp;op=listimg&amp;imgcat_id=' . $imgcat_id);
			$icmsAdminTpl->assign('pag', '<div class="img_list_info_panel" align="center">' . $nav->renderNav() . '</div>');
		} else {
			$icmsAdminTpl->assign('pag', '');
		}
	} else {
		$icmsAdminTpl->assign('pag', '');
	}
	$icmsAdminTpl->assign('addimgform', showAddImgForm($imgcat_id));

	return $icmsAdminTpl->fetch('db:admin/images/system_adm_imagemanager_imglist.html');
}

/**
 * Logic and rendering for adding an image category
 */
function imanager_addcat() {
	if (isset($_POST)) {
		foreach ($_POST as $k => $v) {
			${$k} = $v;
		}
	}
	$imgcat_foldername = preg_replace('/[?".<>\|\s]/', '_', $imgcat_foldername);

	if (!icms::$security->check()) {
		redirect_header('admin.php?fct=images', 3, implode('<br />', icms::$security->getErrors()));
	}
	$imgcat_handler = icms::handler('icms_image_category');
	$imagecategory =& $imgcat_handler->create();
	$imagecategory->setVar('imgcat_pid', $imgcat_pid);
	$imagecategory->setVar('imgcat_name', $imgcat_name);
	$imagecategory->setVar('imgcat_maxsize', $imgcat_maxsize);
	$imagecategory->setVar('imgcat_maxwidth', $imgcat_maxwidth);
	$imagecategory->setVar('imgcat_maxheight', $imgcat_maxheight);
	$imgcat_display = empty($imgcat_display) ? 0 : 1;
	$imagecategory->setVar('imgcat_display', $imgcat_display);
	$imagecategory->setVar('imgcat_weight', $imgcat_weight);
	$imagecategory->setVar('imgcat_storetype', $imgcat_storetype);
	if ($imgcat_storetype == 'file') {
		$imagecategory->setVar('imgcat_foldername', $imgcat_foldername);
		$categ_path = $imgcat_handler->getCategFolder($imagecategory);
	}

	$imagecategory->setVar('imgcat_type', 'C');

	if ($imgcat_storetype == 'file') {
		if (!file_exists($categ_path)) {
			if (!icms_core_Filesystem::mkdir($categ_path)) {
				redirect_header('admin.php?fct=images', 1, _MD_FAILADDCAT);
			} else {
				if ($fh = @fopen($categ_path . '/index.html', 'w'))
				fwrite($fh, '<script>history.go(-1);</script>');
				@fclose($fh);
			}
		}
	}

	if (!$imgcat_handler->insert($imagecategory)) {
		redirect_header('admin.php?fct=images', 1, _MD_FAILADDCAT);
	}

	$newid = $imagecategory->getVar('imgcat_id');
	$imagecategoryperm_handler = icms::handler('icms_member_groupperm');
	if (!isset($readgroup)) {
		$readgroup = array();
	}

	if (!in_array(XOOPS_GROUP_ADMIN, $readgroup)) {
		array_push($readgroup, XOOPS_GROUP_ADMIN);
	}

	foreach ($readgroup as $rgroup) {
		$imagecategoryperm =& $imagecategoryperm_handler->create();
		$imagecategoryperm->setVar('gperm_groupid', $rgroup);
		$imagecategoryperm->setVar('gperm_itemid', $newid);
		$imagecategoryperm->setVar('gperm_name', 'imgcat_read');
		$imagecategoryperm->setVar('gperm_modid', 1);
		$imagecategoryperm_handler->insert($imagecategoryperm);
		unset($imagecategoryperm);
	}

	if (!isset($writegroup)) {
		$writegroup = array();
	}

	if (!in_array(XOOPS_GROUP_ADMIN, $writegroup)) {
		array_push($writegroup, XOOPS_GROUP_ADMIN);
	}

	foreach ($writegroup as $wgroup) {
		$imagecategoryperm =& $imagecategoryperm_handler->create();
		$imagecategoryperm->setVar('gperm_groupid', $wgroup);
		$imagecategoryperm->setVar('gperm_itemid', $newid);
		$imagecategoryperm->setVar('gperm_name', 'imgcat_write');
		$imagecategoryperm->setVar('gperm_modid', 1);
		$imagecategoryperm_handler->insert($imagecategoryperm);
		unset($imagecategoryperm);
	}
	redirect_header('admin.php?fct=images', 2, _MD_AM_DBUPDATED);
}

/**
 * Logic and rendering for editing an image category
 * @param int $imgcat_id	Unique ID of the image category to edit
 */
function imanager_editcat($imgcat_id) {
	if ($imgcat_id <= 0) {
		redirect_header('admin.php?fct=images', 1);
	}
	$imgcat_handler = icms::handler('icms_image_category');
	$imagecategory =& $imgcat_handler->get($imgcat_id);
	if (!is_object($imagecategory)) {
		redirect_header('admin.php?fct=images', 1);
	}
	$imagecategoryperm_handler = icms::handler('icms_member_groupperm');
	$form = new icms_form_Theme(_MD_EDITIMGCAT, 'imagecat_form', 'admin.php', 'post', TRUE);
	$form->addElement(new icms_form_elements_Text(_MD_IMGCATNAME, 'imgcat_name', 50, 255, $imagecategory->getVar('imgcat_name')), TRUE);
	$form->addElement(new icms_form_elements_select_Group(_MD_IMGCATRGRP, 'readgroup', TRUE, $imagecategoryperm_handler->getGroupIds('imgcat_read', $imgcat_id), 5, TRUE));
	$form->addElement(new icms_form_elements_select_Group(_MD_IMGCATWGRP, 'writegroup', TRUE, $imagecategoryperm_handler->getGroupIds('imgcat_write', $imgcat_id), 5, TRUE));
	$form->addElement(new icms_form_elements_Text(_IMGMAXSIZE, 'imgcat_maxsize', 10, 10, $imagecategory->getVar('imgcat_maxsize')));
	$form->addElement(new icms_form_elements_Text(_IMGMAXWIDTH, 'imgcat_maxwidth', 3, 4, $imagecategory->getVar('imgcat_maxwidth')));
	$form->addElement(new icms_form_elements_Text(_IMGMAXHEIGHT, 'imgcat_maxheight', 3, 4, $imagecategory->getVar('imgcat_maxheight')));
	$form->addElement(new icms_form_elements_Text(_MD_IMGCATWEIGHT, 'imgcat_weight', 3, 4, $imagecategory->getVar('imgcat_weight')));
	$form->addElement(new icms_form_elements_Radioyn(_MD_IMGCATDISPLAY, 'imgcat_display', $imagecategory->getVar('imgcat_display'), _YES, _NO));
	$storetype = array('db' => _MD_INDB, 'file' => sprintf(_MD_ASFILE, str_ireplace(ICMS_ROOT_PATH, '', $imgcat_handler->getCategFolder($imagecategory))));
	$form->addElement(new icms_form_elements_Label(_MD_IMGCATSTRTYPE, $storetype[$imagecategory->getVar('imgcat_storetype')]));
	$form->addElement(new icms_form_elements_Hidden('imgcat_id', $imgcat_id));
	$form->addElement(new icms_form_elements_Hidden('op', 'updatecat'));
	$form->addElement(new icms_form_elements_Hidden('fct', 'images'));
	$form->addElement(new icms_form_elements_Button('', 'imgcat_button', _SUBMIT, 'submit'));
	icms_cp_header();
	echo '<div class="CPbigTitle" style="background-image: url(admin/images/images/images_big.png)">' . adminNav($imgcat_id) . '</div><br />';
	$form->display();
}

/**
 * Logic and rendering for updating an image category details 
 */
function imanager_updatecat() {
	if (isset($_POST)) {
		foreach ($_POST as $k => $v) {
			${$k} = $v;
		}
	}

	if (!icms::$security->check() || $imgcat_id <= 0) {
		redirect_header('admin.php?fct=images', 1, implode('<br />', icms::$security->getErrors()));
	}
	$imgcat_handler = icms::handler('icms_image_category');
	$imagecategory =& $imgcat_handler->get($imgcat_id);
	if (!is_object($imagecategory)) {
		redirect_header('admin.php?fct=images', 1);
	}
	$imagecategory->setVar('imgcat_name', $imgcat_name);
	$imgcat_display = empty($imgcat_display) ? 0 : 1;
	$imagecategory->setVar('imgcat_display', $imgcat_display);
	$imagecategory->setVar('imgcat_maxsize', $imgcat_maxsize);
	$imagecategory->setVar('imgcat_maxwidth', $imgcat_maxwidth);
	$imagecategory->setVar('imgcat_maxheight', $imgcat_maxheight);
	$imagecategory->setVar('imgcat_weight', $imgcat_weight);
	if (!$imgcat_handler->insert($imagecategory)) {
		exit();
	}
	$imagecategoryperm_handler = icms::handler('icms_member_groupperm');
	$criteria = new icms_db_criteria_Compo(new icms_db_criteria_Item('gperm_itemid', $imgcat_id));
	$criteria->add(new icms_db_criteria_Item('gperm_modid', 1));
	$criteria2 = new icms_db_criteria_Compo(new icms_db_criteria_Item('gperm_name', 'imgcat_write'));
	$criteria2->add(new icms_db_criteria_Item('gperm_name', 'imgcat_read'), 'OR');
	$criteria->add($criteria2);
	$imagecategoryperm_handler->deleteAll($criteria);
	if (!isset($readgroup)) {
		$readgroup = array();
	}
	if (!in_array(XOOPS_GROUP_ADMIN, $readgroup)) {
		array_push($readgroup, XOOPS_GROUP_ADMIN);
	}
	foreach ($readgroup as $rgroup) {
		$imagecategoryperm =& $imagecategoryperm_handler->create();
		$imagecategoryperm->setVar('gperm_groupid', $rgroup);
		$imagecategoryperm->setVar('gperm_itemid', $imgcat_id);
		$imagecategoryperm->setVar('gperm_name', 'imgcat_read');
		$imagecategoryperm->setVar('gperm_modid', 1);
		$imagecategoryperm_handler->insert($imagecategoryperm);
		unset($imagecategoryperm);
	}
	if (!isset($writegroup)) {
		$writegroup = array();
	}
	if (!in_array(XOOPS_GROUP_ADMIN, $writegroup)) {
		array_push($writegroup, XOOPS_GROUP_ADMIN);
	}
	foreach ($writegroup as $wgroup) {
		$imagecategoryperm =& $imagecategoryperm_handler->create();
		$imagecategoryperm->setVar('gperm_groupid', $wgroup);
		$imagecategoryperm->setVar('gperm_itemid', $imgcat_id);
		$imagecategoryperm->setVar('gperm_name', 'imgcat_write');
		$imagecategoryperm->setVar('gperm_modid', 1);
		$imagecategoryperm_handler->insert($imagecategoryperm);
		unset($imagecategoryperm);
	}
	redirect_header('admin.php?fct=images', 2, _MD_AM_DBUPDATED);
}

/**
 * Logic and rendering for deleting an image category
 * @param int $imgcat_id	Unique ID for the image category to be deleted
 */
function imanager_delcatok($imgcat_id) {
	if (!icms::$security->check()) {
		redirect_header('admin.php?fct=images', 3, implode('<br />', icms::$security->getErrors()));
	}

	$imgcat_id = (int) $imgcat_id;
	if ($imgcat_id <= 0) {
		redirect_header('admin.php?fct=images', 1, '1');
	}
	$imgcat_handler = icms::handler('icms_image_category');
	$imagecategory =& $imgcat_handler->get($imgcat_id);

	if (!is_object($imagecategory)) {
		redirect_header('admin.php?fct=images', 1, '2');
	}
	$categ_path = $imgcat_handler->getCategFolder($imagecategory);
	$categ_path = (substr($categ_path, -1) != '/') ? $categ_path . '/' : $categ_path;
	if ($imagecategory->getVar('imgcat_type') != 'C') {
		redirect_header('admin.php?fct=images', 1, _MD_SCATDELNG);
	}

	$image_handler = icms::handler('icms_image');
	$images =& $image_handler->getObjects(new icms_db_criteria_Item('imgcat_id', $imgcat_id), TRUE, FALSE);

	$errors = array();

	if ($imagecategory->getVar('imgcat_storetype') == 'db') {
		foreach (array_keys($images) as $i) {
			if (!$image_handler->delete($images[$i])) {
				$errors[] = sprintf(_MD_FAILDEL, $i);
			}
		}
	} else {
		foreach (array_keys($images) as $i) {
			if (!$image_handler->delete($images[$i])) {
				$errors[] = sprintf(_MD_FAILDEL, $i);
			} else {
				if (file_exists($categ_path . $images[$i]->getVar('image_name'))) {
					if (!@unlink($categ_path . $images[$i]->getVar('image_name'))) {
						$errors[] = sprintf(_MD_FAILUNLINK, $i);
					}
				}
			}
		}
		unlink($categ_path . 'index.html');
	}

	if (!$imgcat_handler->delete($imagecategory)) {
		$errors[] = sprintf(_MD_FAILDELCAT, $imagecategory->getVar('imgcat_name'));
	} else {
		if ($imagecategory->getVar('imgcat_storetype') != 'db') {
			if (!@rmdir($categ_path)) {
				$errors[] = sprintf(_MD_FAILDELCAT, $i);
			}
		}
	}
	if (count($errors) > 0) {
		icms_cp_header();
		icms_core_Message::error($errors);
		icms_cp_footer();
		exit();
	}
	redirect_header('admin.php?fct=images', 2, _MD_AM_DBUPDATED);
}

/**
 * Logic and rendering for sorting the image categories
 */
function imanager_reordercateg() {
	if (!icms::$security->check()) {
		redirect_header('admin.php?fct=images', 1, implode('<br />', icms::$security->getErrors()));
	}
	$count = count($_POST['imgcat_weight']);
	$err = 0;
	if ($count > 0) {
		$imgcat_handler = icms::handler('icms_image_category');
		foreach ($_POST['imgcat_weight'] as $k=>$v) {
			$cat = $imgcat_handler->get($k);
			$cat->setVar('imgcat_weight', $v);
			if (!$imgcat_handler->insert($cat)) {
				$err++;
			}
		}
		if ($err) {
			$msg = _MD_FAILEDITCAT;
		} else {
			$msg = _MD_AM_DBUPDATED;
		}
		redirect_header('admin.php?fct=images', 2, $msg);
	}
}

/**
 * Logic and rendering for adding an image to an image category
 */
function imanager_addfile() {
	if (isset($_POST)) {
		foreach ($_POST as $k => $v) {
			${$k} = $v;
		}
	}
	if (!icms::$security->check()) {
		redirect_header('admin.php?fct=images', 3, implode('<br />', icms::$security->getErrors()));
	}
	$imgcat_handler = icms::handler('icms_image_category');
	$imagecategory =& $imgcat_handler->get((int) $imgcat_id);
	if (!is_object($imagecategory)) {
		redirect_header('admin.php?fct=images', 1);
	}
	$categ_path = $imgcat_handler->getCategFolder($imagecategory);

	if ($imagecategory->getVar('imgcat_storetype') == 'db') {
		$updir = ICMS_IMANAGER_FOLDER_PATH;
	} else {
		$updir = $categ_path;
	}
	$uploader = new icms_file_MediaUploadHandler($updir, array('image/gif', 'image/jpeg', 'image/pjpeg', 'image/x-png', 'image/png', 'image/bmp'), $imagecategory->getVar('imgcat_maxsize'), $imagecategory->getVar('imgcat_maxwidth'), $imagecategory->getVar('imgcat_maxheight'));
	$uploader->setPrefix('img');
	$err = array();
	$ucount = count($_POST['xoops_upload_file']);
	for ($i = 0; $i < $ucount; $i++) {
		if ($uploader->fetchMedia($_POST['xoops_upload_file'][$i])) {
			if (!$uploader->upload()) {
				$err[] = $uploader->getErrors();
			} else {
				$image_handler = icms::handler('icms_image');
				$image =& $image_handler->create();
				$image->setVar('image_name', $uploader->getSavedFileName());
				$image->setVar('image_nicename', $image_nicename);
				$image->setVar('image_mimetype', $uploader->getMediaType());
				$image->setVar('image_created', time());
				$image_display = empty($image_display) ? 0 : 1;
				$image->setVar('image_display', $image_display);
				$image->setVar('image_weight', $image_weight);
				$image->setVar('imgcat_id', $imgcat_id);
				if ($imagecategory->getVar('imgcat_storetype') == 'db') {
					$fp = @fopen($uploader->getSavedDestination(), 'rb');
					$fbinary = @fread($fp, filesize($uploader->getSavedDestination()));
					@fclose($fp);
					$image->setVar('image_body', $fbinary, TRUE);
					@unlink($uploader->getSavedDestination());
				}
				if (!$image_handler->insert($image)) {
					$err[] = sprintf(_FAILSAVEIMG, $image->getVar('image_nicename'));
				}
			}
		} else {
			$err[] = sprintf(_FAILFETCHIMG, $i);
			$err = array_merge($err, $uploader->getErrors(FALSE));
		}
	}
	if (count($err) > 0) {
		icms_cp_header();
		icms_core_Message::error($err);
		icms_cp_footer();
		exit();
	}
	if (isset($imgcat_id)) {
		$redir = '&op=listimg&imgcat_id=' . $imgcat_id;
	} else {
		$redir = '';
	}
	redirect_header('admin.php?fct=images' . $redir, 2, _MD_AM_DBUPDATED);
}

/**
 * Logic for updating details for an image
 */
function imanager_updateimage() {
	if (isset($_POST)) {
		foreach ($_POST as $k => $v) {
			${$k} = $v;
		}
	}
	if (!icms::$security->check()) {
		redirect_header('admin.php?fct=images', 3, implode('<br />', icms::$security->getErrors()));
	}
	$count = count($image_id);
	if ($count > 0) {
		$image_handler = icms::handler('icms_image');
		$error = array();
		for ($i = 0; $i < $count; $i++) {
			$image =& $image_handler->get($image_id[$i]);
			if (!is_object($image)) {
				$error[] = sprintf(_FAILGETIMG, $image_id[$i]);
				continue;
			}
			$image_display[$i] = empty($image_display[$i]) ? 0 : 1;
			$image->setVar('image_display', $image_display[$i]);
			$image->setVar('image_weight', $image_weight[$i]);
			$image->setVar('image_nicename', $image_nicename[$i]);
			if ($image->getVar('imgcat_id') != $imgcat_id[$i]) {
				$changedCat = TRUE;
				$oldcat = $image->getVar('imgcat_id');
			} else {
				$changedCat = FALSE;
			}
			$image->setVar('imgcat_id', $imgcat_id[$i]);
			if (!$image_handler->insert($image)) {
				$error[] = sprintf(_FAILSAVEIMG, $image_id[$i]);
			}
			if ($changedCat) {
				$imgcat_handler = icms::handler('icms_image_category');
				$imagecategory  =& $imgcat_handler->get((int) $imgcat_id[$i]);
				$dest_categ_path = $imgcat_handler->getCategFolder($imagecategory);
				if ($imagecategory->getVar('imgcat_storetype') != 'db') {
					$oldimgcategory =& $imgcat_handler->get((int) $oldcat);
					$src_categ_path = $imgcat_handler->getCategFolder($oldimgcategory);
					$src = $src_categ_path . '/' . $image->getVar('image_name');
					$dest = $dest_categ_path . '/' . $image->getVar('image_name');
					if (!copy($src, $dest)) {
						$error[] = sprintf(_FAILSAVEIMG, $image_id[$i]);
					}
					if (!@unlink($src)) {
						$error[] = sprintf(_MD_FAILUNLINK, $i);
					}
				}
			}
		}
		if (count($error) > 0) {
			icms_cp_header();
			foreach ($error as $err) {
				echo $err . '<br />';
			}
			icms_cp_footer();
			exit();
		}
	}
	if (isset($redir)) {
		$redir = '&op=listimg&imgcat_id=' . $redir;
	} else {
		$redir = '';
	}
	redirect_header('admin.php?fct=images' . $redir, 2, _MD_AM_DBUPDATED);
}

/**
 * Logic and rendering for deleting an image
 * @param int $image_id	Unique ID for the image to be deleted
 * @param int $redir	Optional. If set, the image category to display after deleting the image
 */
function imanager_delfileok($image_id, $redir = NULL) {
	if (!icms::$security->check()) {
		redirect_header('admin.php?fct=images', 3, implode('<br />', icms::$security->getErrors()));
	}
	$image_id = (int) $image_id;
	if ($image_id <= 0) {
		redirect_header('admin.php?fct=images', 1);
	}
	$image_handler = icms::handler('icms_image');
	$image =& $image_handler->get($image_id);
	if (!is_object($image)) {
		redirect_header('admin.php?fct=images', 1);
	}
	$imgcat_handler = icms::handler('icms_image_category');
	$imagecategory  =& $imgcat_handler->get((int) $image->getVar('imgcat_id'));
	$categ_path = $imgcat_handler->getCategFolder($imagecategory);
	if (!$image_handler->delete($image)) {
		icms_cp_header();
		icms_core_Message::error(sprintf(_MD_FAILDEL, $image->getVar('image_id')));
		icms_cp_footer();
		exit();
	}
	@unlink($categ_path . '/' . $image->getVar('image_name'));
	if (isset($redir)) {
		$redir = '&op=listimg&imgcat_id=' . $redir;
	} else {
		$redir = '';
	}
	redirect_header('admin.php?fct=images' . $redir, 2, _MD_AM_DBUPDATED);
}

/**
 * Function and script to display the add image form
 * @param int $imgcat_id	Unique ID for the image category for the new image
 */
function showAddImgForm($imgcat_id) {
	$imgcat_handler = icms::handler('icms_image_category');
	$form = new icms_form_Theme(_ADDIMAGE, 'image_form', 'admin.php', 'post', TRUE);
	$form->setExtra('enctype="multipart/form-data"');
	$form->addElement(new icms_form_elements_Text(_IMAGENAME, 'image_nicename', 50, 255), TRUE);
	$select = new icms_form_elements_Select(_IMAGECAT, 'imgcat_id', (int) $imgcat_id);
	$select->addOptionArray($imgcat_handler->getCategList());
	$form->addElement($select, TRUE);
	$form->addElement(new icms_form_elements_File(_IMAGEFILE, 'image_file', 5000000));
	$form->addElement(new icms_form_elements_Text(_IMGWEIGHT, 'image_weight', 3, 4, 0));
	$form->addElement(new icms_form_elements_Radioyn(_IMGDISPLAY, 'image_display', 1, _YES, _NO));
	$form->addElement(new icms_form_elements_Hidden('imgcat_id', $imgcat_id));
	$form->addElement(new icms_form_elements_Hidden('op', 'addfile'));
	$form->addElement(new icms_form_elements_Hidden('fct', 'images'));
	$tray = new icms_form_elements_Tray('', '');
	$tray->addElement(new icms_form_elements_Button('', 'img_button', _SUBMIT, 'submit'));
	$btn = new icms_form_elements_Button('', 'reset', _CANCEL, 'button');
	$btn->setExtra('onclick="document.getElementById(\'addimgform\').style.display = \'none\'; return FALSE;"');
	$tray->addElement($btn);
	$form->addElement($tray);
	return $form->render();
}

/**
 * Logic to create a duplicate of an existing image
 */
function imanager_clone() {
	if (!icms::$security->check()) {
		redirect_header('admin.php?fct=images', 3, implode('<br />', icms::$security->getErrors()));
	}

	$imgcat_id = (int) $_POST['imgcat_id'];
	$image_id = (int) $_POST['image_id'];

	$imgcat_handler = icms::handler('icms_image_category');
	$imagecategory =& $imgcat_handler->get($imgcat_id);
	if (!is_object($imagecategory)) {
		redirect_header('admin.php?fct=images', 1);
	}
	$categ_path = $imgcat_handler->getCategFolder($imagecategory);

	$image_handler = icms::handler('icms_image');
	$image =& $image_handler->get($image_id);
	if (($ext = strrpos($image->getVar('image_name'), '.' )) !== FALSE) {
		$ext = strtolower(substr($image->getVar('image_name'), $ext + 1 ));
	}

	$imgname = 'img' . icms_random_str(12) . '.' . $ext;
	$newimg =& $image_handler->create();
	$newimg->setVar('image_name', $imgname);
	$newimg->setVar('image_nicename', $_POST['image_nicename']);
	$newimg->setVar('image_mimetype', $image->getVar('image_mimetype'));
	$newimg->setVar('image_created', time());
	$newimg->setVar('image_display', $_POST['image_display']);
	$newimg->setVar('image_weight', $_POST['image_weight']);
	$newimg->setVar('imgcat_id', $imgcat_id);
	if ($imagecategory->getVar('imgcat_storetype') == 'db') {
		$src = ICMS_MODULES_URL . "/system/admin/images/preview.php?file=" . $image->getVar('image_name') . '&resize=0';
		$img = WideImage::load($image->getVar('image_body'))->saveToFile(ICMS_IMANAGER_FOLDER_PATH . '/' . $image->getVar('image_name'));
		$fp = @fopen(ICMS_IMANAGER_FOLDER_PATH . '/' . $image->getVar('image_name'), 'rb');
		$fbinary = @fread($fp, filesize(ICMS_IMANAGER_FOLDER_PATH . '/' . $image->getVar('image_name')));
		@fclose($fp);
		$newimg->setVar('image_body', $fbinary, TRUE);
		@unlink(ICMS_IMANAGER_FOLDER_PATH . '/' . $image->getVar('image_name'));
	} else {
		if (!@copy($categ_path . '/' . $image->getVar('image_name'), $categ_path . '/' . $imgname)) {
			$msg = sprintf(_FAILSAVEIMG, $image->getVar('image_nicename'));
		}
	}
	if (!$image_handler->insert($newimg)) {
		$msg = sprintf(_FAILSAVEIMG, $newimg->getVar('image_nicename'));
	} else {
		$msg = _MD_AM_DBUPDATED;
	}

	if (isset($imgcat_id)) {
		$redir = '&op=listimg&imgcat_id=' . $imgcat_id;
	} else {
		$redir = '';
	}
	redirect_header('admin.php?fct=images' . $redir, 2, $msg);
}

/**
 * Function to create a navigation menu in content pages.
 * This function was based on the function that do the same in mastop publish module
 *
 * @param int $id				
 * @param str $separador
 * @param bln $list
 * @param str $style
 * @return str
 */
function adminNav($id = NULL, $separador = "/", $list = FALSE, $style="style='font-weight:bold'") {
	$admin_url = ICMS_MODULES_URL . "/system/admin.php?fct=images";
	if ($id === FALSE) {
		return FALSE;
	} else {
		if ($id > 0) {
			$id = (int) $id;
			$imgcat_handler = icms::handler('icms_image_category');
			$imagecategory =& $imgcat_handler->get($id);
			if ($imagecategory->getVar('imgcat_id') > 0) {
				if ($list) {
					$ret = $imagecategory->getVar('imgcat_name');
				} else {
					$ret = "<a href='" . $admin_url . "&imgcat_id=" . $imagecategory->getVar('imgcat_id') . "'>" . $imagecategory->getVar('imgcat_name') . "</a>";
				}
				if ($imagecategory->getVar('imgcat_pid') == 0) {
					return "<a href='" . $admin_url . "'>" . _MD_IMGMAIN . "</a> $separador " . $ret;
				} elseif ($imagecategory->getVar('imgcat_pid') > 0) {
					$ret = adminNav($imagecategory->getVar('imgcat_pid'), $separador) . " $separador ". $ret;
				}
			}
		} else {
			return FALSE;
		}
	}
	return $ret;
}

/**
 * Alias for the redirect_header function
 * @param int $imgcat_id	Unique ID for the image category to display after the redirect
 * @param str $msg			Message to display in the redirect page/message box
 */
function redir($imgcat_id, $msg = NULL) {
	redirect_header('admin.php?fct=images&op=listimg&imgcat_id=' . (int) $imgcat_id, 2, $msg);
}

