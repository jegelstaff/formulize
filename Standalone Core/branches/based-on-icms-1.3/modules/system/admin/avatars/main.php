<?php
/**
 * Administration of avatars
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @package		Administration
 * @subpackage	Avatars
 * @version		SVN: $Id: main.php 21846 2011-06-23 16:37:07Z phoenyx $
 */

if (!is_object(icms::$user) || !is_object($icmsModule) || !icms::$user->isAdmin($icmsModule->getVar('mid'))) {
	exit("Access Denied");
} else {
	if (!empty($_POST)) foreach ($_POST as $k => $v) ${$k} = StopXSS($v);
	if (!empty($_GET)) foreach ($_GET as $k => $v) ${$k} = StopXSS($v);
	$op = (isset($_GET['op'])) 
		? trim(filter_input(INPUT_GET, 'op'))
		: ((isset($_POST['op'])) 
			? trim(filter_input(INPUT_POST, 'op'))
			: 'list'
		);
	if ($op == 'list') {
		icms_loadLanguageFile('system', 'preferences', TRUE);
		icms_cp_header();
		echo '<div class="CPbigTitle" style="background-image: url(' 
			. ICMS_URL . '/modules/system/admin/avatars/images/avatars_big.png)">' 
			. _MD_AVATARMAN . '</div><br />';
		$avt_handler = icms::handler('icms_data_avatar');
		$savatar_count = $avt_handler->getCount(new icms_db_criteria_Item('avatar_type', 'S'));
		$cavatar_count = $avt_handler->getCount(new icms_db_criteria_Item('avatar_type', 'C'));
		echo '<ul><li>' 
			. _MD_SYSAVATARS . ' (' . sprintf(_NUMIMAGES, '<strong>' . icms_conv_nr2local($savatar_count) . '</strong>') 
			. ') [<a href="admin.php?fct=avatars&amp;op=listavt&amp;type=S">' . _LIST . '</a>]</li><li>' 
			. _MD_CSTAVATARS . ' (' . sprintf(_NUMIMAGES, '<strong>' . icms_conv_nr2local($cavatar_count) . '</strong>') 
			. ') [<a href="admin.php?fct=avatars&amp;op=listavt&amp;type=C">' . _LIST 
			. '</a>]</li></ul>';
		$form = new icms_form_Theme(_MD_ADDAVT, 'avatar_form', 'admin.php', "post", TRUE);
		$form->setExtra('enctype="multipart/form-data"');
		$form->addElement(new icms_form_elements_Text(_IMAGENAME, 'avatar_name', 50, 255), TRUE);
		$form->addElement(new icms_form_elements_File(_IMAGEFILE, 'avatar_file', $icmsConfigUser['avatar_maxsize']));
		$form->addElement(new icms_form_elements_Text(_IMGWEIGHT, 'avatar_weight', 3, 4, 0));
		$form->addElement(new icms_form_elements_Radioyn(_IMGDISPLAY, 'avatar_display', 1, _YES, _NO));
		$restrictions  = _MD_AM_AVATARMAX . ": " . $icmsConfigUser['avatar_maxsize'] . "<br />";
		$restrictions .= _MD_AM_AVATARW . ": " . $icmsConfigUser['avatar_width'] . "px<br />";
		$restrictions .= _MD_AM_AVATARH . ": ". $icmsConfigUser['avatar_height']. "px";
		$form->addElement(new icms_form_elements_Label(_MD_RESTRICTIONS, $restrictions));
		$form->addElement(new icms_form_elements_Hidden('op', 'addfile'));
		$form->addElement(new icms_form_elements_Hidden('fct', 'avatars'));
		$form->addElement(new icms_form_elements_Button('', 'avt_button', _SUBMIT, 'submit'));
		$form->display();
		icms_cp_footer();
		exit();
	}

	if ($op == 'listavt') {
		$avt_handler = icms::handler('icms_data_avatar');
		icms_cp_header();
		$type = (isset($_GET['type']) && $_GET['type'] == 'C') ? 'C' : 'S';
		echo '<div class="CPbigTitle" style="background-image: url(' 
			. ICMS_URL . '/modules/system/admin/avatars/images/avatars_big.png)"><a href="admin.php?fct=avatars">'
			. _MD_AVATARMAN .'</a>&nbsp;<span style="font-weight:bold;">&raquo;&raquo;</span>&nbsp;';
		if ($type == 'S') {
			echo _MD_SYSAVATARS;
		} else {
			echo _MD_CSTAVATARS;
		}
		echo '</div><br /><br /><br />';
		$criteria = new icms_db_criteria_Item('avatar_type', $type);
		$avtcount = $avt_handler->getCount($criteria);
		$start = isset($_GET['start']) ? (int) ($_GET['start']) : 0;
		$criteria->setStart($start);
		$criteria->setLimit(10);
		$avatars =& $avt_handler->getObjects($criteria, TRUE);
		if ($type == 'S') {
			foreach (array_keys($avatars) as $i) {
				echo '<form action="admin.php" method="post">';
				$id = $avatars[$i]->getVar('avatar_id');
				echo '<table class="outer" cellspacing="1" width="100%">'
					. '<tr><td align="center" width="30%" rowspan="6"><img src="' 
					. ICMS_UPLOAD_URL . '/' . $avatars[$i]->getVar('avatar_file') 
					. '" alt="" /></td><td class="head">' . _IMAGENAME, '</td><td class="even"><input type="hidden" name="avatar_id[]" value="' 
					. $id . '" /><input type="text" name="avatar_name[]" value="' . $avatars[$i]->getVar('avatar_name', 'E') . '" size="20" maxlength="255" /></td></tr><tr><td class="head">' 
					. _IMAGEMIME . '</td><td class="odd">' . $avatars[$i]->getVar('avatar_mimetype') . '</td></tr><tr><td class="head">' . _MD_USERS . '</td><td class="even">' 
					. $avatars[$i]->getUserCount() . '</td></tr><tr><td class="head">' 
					. _IMGWEIGHT . '</td><td class="odd"><input type="text" name="avatar_weight[]" value="' 
					. $avatars[$i]->getVar('avatar_weight') . '" size="3" maxlength="4" /></td></tr><tr><td class="head">' 
					. _IMGDISPLAY . '</td><td class="even"><input type="checkbox" name="avatar_display[]" value="1"';
				if ($avatars[$i]->getVar('avatar_display') == 1) {
					echo ' checked="checked"';
				}
				echo ' /></td></tr><tr><td class="head">&nbsp;</td><td class="even"><a href="admin.php?fct=avatars&amp;op=delfile&amp;avatar_id=' 
					. $id . '">' . _DELETE . '</a></td></tr></table><br />';
			}
		} else {
			foreach (array_keys($avatars) as $i) {
				echo '<table cellspacing="1" class="outer" width="100%">'.
					'<tr><td width="30%" rowspan="6" align="center"><img src="' 
					. ICMS_UPLOAD_URL . '/' . $avatars[$i]->getVar('avatar_file') 
					. '" alt="" /></td><td class="head">' . _IMAGENAME, '</td><td class="even"><a href="' 
					. ICMS_URL . '/userinfo.php?uid=';
				$userids =& $avt_handler->getUser($avatars[$i]);
				echo $userids[0] . '">' . $avatars[$i]->getVar('avatar_name') 
					. '</a></td></tr><tr><td class="head">' . _IMAGEMIME 
					. '</td><td class="odd">' . $avatars[$i]->getVar('avatar_mimetype') 
					. '</td></tr><tr><td class="head">&nbsp;</td><td align="center" class="even">'
					. '<a href="admin.php?fct=avatars&amp;op=delfile&amp;avatar_id=' 
					. $avatars[$i]->getVar('avatar_id') . '&amp;user_id=' . $userids[0] . '">' 
					. _DELETE . '</a></td></tr></table><br />';
			}
		}
		if ($avtcount > 0) {
			if ($avtcount > 10) {
				$nav = new icms_view_PageNav($avtcount, 10, $start, 'start', 'fct=avatars&amp;type=' . $type . '&amp;op=listavt');
				echo '<div style="text-align:' . _GLOBAL_RIGHT . ';">' . $nav->renderImageNav() . '</div>';
			}
			if ($type == 'S') {
				echo '<div style="text-align:center;">'
					. '<input type="hidden" name="op" value="save" />'
					. '<input type="hidden" name="fct" value="avatars" />'
					. '<input type="submit" name="submit" value="' . _SUBMIT . '" />' 
					. icms::$security->getTokenHTML() 
					. '</div></form>';
			}
		}
		icms_cp_footer();
		exit();
	}

	if ($op == 'save') {
		if (!icms::$security->check()) {
			redirect_header('admin.php?fct=avatars', 3, implode('<br />', icms::$security->getErrors()));
			exit();
		}
		$count = count($avatar_id);
		if ($count > 0) {
			$avt_handler = icms::handler('icms_data_avatar');
			$error = array();
			for ($i = 0; $i < $count; $i++) {
				$avatar =& $avt_handler->get($avatar_id[$i]);
				if (!is_object($avatar)) {
					$error[] = sprintf(_FAILGETIMG, $avatar_id[$i]);
					continue;
				}
				$avatar_display[$i] = empty($avatar_display[$i]) ? 0 : 1;
				$avatar->setVar('avatar_display', $avatar_display[$i]);
				$avatar->setVar('avatar_weight', $avatar_weight[$i]);
				$avatar->setVar('avatar_name', $avatar_name[$i]);
				if (!$avt_handler->insert($avatar)) {
					$error[] = sprintf(_FAILSAVEIMG, $avatar_id[$i]);
				}
				unset($avatar_id[$i]);
				unset($avatar_name[$i]);
				unset($avatar_weight[$i]);
				unset($avatar_display[$i]);
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
		redirect_header('admin.php?fct=avatars', 2, _MD_AM_DBUPDATED);
	}

	if ($op == 'addfile') {
		if (!icms::$security->check()) {
			redirect_header('admin.php?fct=avatars', 3, implode('<br />', icms::$security->getErrors()));
		}
		$uploader = new icms_file_MediaUploadHandler(ICMS_UPLOAD_PATH, array('image/gif', 'image/jpeg', 'image/pjpeg', 'image/x-png', 'image/png'), $icmsConfigUser['avatar_maxsize'], $icmsConfigUser['avatar_width'], $icmsConfigUser['avatar_height']);
		$uploader->setPrefix('savt');
		$err = array();
		if ($uploader->fetchMedia($_POST['xoops_upload_file'][0])) {
			if (!$uploader->upload()) {
				$err[] = $uploader->getErrors();
			} else {
				$avt_handler = icms::handler('icms_data_avatar');
				$avatar =& $avt_handler->create();
				$avatar->setVar('avatar_file', $uploader->getSavedFileName());
				$avatar->setVar('avatar_name', $avatar_name);
				$avatar->setVar('avatar_mimetype', $uploader->getMediaType());
				$avatar_display = empty($avatar_display) ? 0 : 1;
				$avatar->setVar('avatar_display', $avatar_display);
				$avatar->setVar('avatar_weight', $avatar_weight);
				$avatar->setVar('avatar_type', 'S');
				if (!$avt_handler->insert($avatar)) {
					$err[] = sprintf(_FAILSAVEIMG, $avatar->getVar('avatar_name'));
				}
			}
		} else {
			$err = array_merge($err, $uploader->getErrors(FALSE));
		}
		if (count($err) > 0) {
			icms_cp_header();
			icms_core_Message::error($err);
			icms_cp_footer();
			exit();
		}
		redirect_header('admin.php?fct=avatars', 2, _MD_AM_DBUPDATED);
	}

	if ($op == 'delfile') {
		icms_cp_header();
		$user_id = isset($_GET['user_id']) ? (int) ($_GET['user_id']) : 0;
		icms_core_Message::confirm(array('op' => 'delfileok', 'avatar_id' => (int) ($_GET['avatar_id']), 'fct' => 'avatars', 'user_id' => $user_id), 'admin.php', _MD_RUDELIMG);
		icms_cp_footer();
		exit();
	}

	if ($op == 'delfileok') {
		if (!icms::$security->check()) {
			redirect_header('admin.php?fct=avatars', 1, 3, implode('<br />', icms::$security->getErrors()));
		}
		$avatar_id = (int) ($avatar_id);
		if ($avatar_id <= 0) {
			redirect_header('admin.php?fct=avatars', 1);
		}
		$avt_handler = icms::handler('icms_data_avatar');
		$avatar =& $avt_handler->get($avatar_id);
		if (!is_object($avatar)) {
			redirect_header('admin.php?fct=avatars', 1);
		}
		if (!$avt_handler->delete($avatar)) {
			icms_cp_header();
			icms_core_Message::error(sprintf(_MD_FAILDEL, $avatar->getVar('avatar_id')));
			icms_cp_footer();
			exit();
		}
		$file = $avatar->getVar('avatar_file');
		@unlink(ICMS_UPLOAD_PATH . '/' . $file);
		if (isset($user_id) && $avatar->getVar('avatar_type') == 'C') {
			icms::$xoopsDB->query("UPDATE " . icms::$xoopsDB->prefix('users') . " SET user_avatar='blank.gif' WHERE uid='". (int) ($user_id) . "'");
		} else {
			icms::$xoopsDB->query("UPDATE " . icms::$xoopsDB->prefix('users') . " SET user_avatar='blank.gif' WHERE user_avatar='" . $file . "'");
		}
		redirect_header('admin.php?fct=avatars', 2, _MD_AM_DBUPDATED);
	}
}

