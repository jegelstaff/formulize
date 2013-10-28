<?php
/**
 * Administration of smilies, main file
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @package		System
 * @subpackage	Smilies
 * @version		SVN: $Id: main.php 21383 2011-03-30 14:18:16Z m0nty_ $
 */

if (!is_object(icms::$user) || !is_object($icmsModule) || !icms::$user->isAdmin($icmsModule->getVar('mid'))) {
	exit("Access Denied");
}
/*
 * GET variables
 * (int) id
 * (str) op
 *
 * POST variables
 * (str) op
 * (int|arr) smile_id
 * (arr) smile_display
 * (arr) old_display
 * (str) xoops_upload_file
 * (str) smile_code
 * (str) smile_desc
 * id
 */
include_once ICMS_MODULES_PATH . "/system/admin/smilies/smilies.php";

if (!empty($_POST)) foreach ($_POST as $k => $v) ${$k} = StopXSS($v);
if (!empty($_GET)) foreach ($_GET as $k => $v) ${$k} = StopXSS($v);
$op = (isset($_GET['op']))
	? trim(filter_input(INPUT_GET, 'op'))
	: ((isset($_POST['op']))
		? trim(filter_input(INPUT_POST, 'op'))
		: 'SmilesAdmin');

switch($op) {
	case "SmilesUpdate":
		if (!icms::$security->check()) {
			redirect_header('admin.php?fct=smilies', 3, implode('<br />', icms::$security->getErrors()));
		}
		$count = (!empty($_POST['smile_id']) && is_array($_POST['smile_id'])) ? count($_POST['smile_id']) : 0;
		$db =& icms_db_Factory::instance();
		for ($i = 0; $i < $count; $i++) {
			$smile_id = (int) $_POST['smile_id'][$i];
			if (empty($smile_id)) {
				continue;
			}
			$smile_display = empty($_POST['smile_display'][$i]) ? 0 : 1;
			if (isset($_POST['old_display'][$i]) && $_POST['old_display'][$i] != $smile_display[$i]) {
				$db->query("UPDATE " . $db->prefix('smiles') . " SET display='" . (int) $smile_display . "' WHERE id ='" . $smile_id . "'");
			}
		}
		redirect_header('admin.php?fct=smilies', 2, _AM_DBUPDATED);
		break;

	case "SmilesAdd":
		if (!icms::$security->check()) {
			redirect_header('admin.php?fct=smilies', 3, implode('<br />', icms::$security->getErrors()));
		}
		$db =& icms_db_Factory::instance();
		$uploader = new icms_file_MediaUploadHandler(ICMS_UPLOAD_PATH, array('image/gif', 'image/jpeg', 'image/pjpeg', 'image/x-png'), 100000, 120, 120);
		$uploader->setPrefix('smil');
		if ($uploader->fetchMedia($_POST['xoops_upload_file'][0])) {
			if (!$uploader->upload()) {
				$err = $uploader->getErrors();
			} else {
				$smile_url = $uploader->getSavedFileName();
				$smile_code = icms_core_DataFilter::stripSlashesGPC($_POST['smile_code']);
				$smile_desc = icms_core_DataFilter::stripSlashesGPC($_POST['smile_desc']);
				$smile_display = (int) $_POST['smile_display'] > 0 ? 1 : 0;
				$newid = $db->genId($db->prefix('smilies') . "_id_seq");
				$sql = sprintf("INSERT INTO %s (id, code, smile_url, emotion, display) VALUES ('%d', %s, %s, %s, '%d')", $db->prefix('smiles'), (int) $newid, $db->quoteString($smile_code), $db->quoteString($smile_url), $db->quoteString($smile_desc), $smile_display);
				if (!$db->query($sql)) {
					$err = 'Failed storing smiley data into the database';
				}
			}
		} else {
			$err = $uploader->getErrors();
		}

		if (!isset($err)) {
			redirect_header('admin.php?fct=smilies&amp;op=SmilesAdmin', 2, _AM_DBUPDATED);
		} else {
			icms_cp_header();
			icms_core_Message::error($err);
			icms_cp_footer();
		}
		break;

	case "SmilesEdit":
		$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
		if ($id > 0) {
			SmilesEdit($id);
		}
		break;

	case "SmilesSave":
		$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
		if ($id <= 0 | !icms::$security->check()) {
			redirect_header('admin.php?fct=smilies', 3, implode('<br />', icms::$security->getErrors()));
		}
		$smile_code = icms_core_DataFilter::stripSlashesGPC($_POST['smile_code']);
		$smile_desc = icms_core_DataFilter::stripSlashesGPC($_POST['smile_desc']);
		$smile_display = (int) $_POST['smile_display'] > 0 ? 1 : 0;
		$db =& icms_db_Factory::instance();
		if ($_FILES['smile_url']['name'] != "") {
			$uploader = new icms_file_MediaUploadHandler(ICMS_UPLOAD_PATH, array('image/gif', 'image/jpeg', 'image/pjpeg', 'image/x-png'), 100000, 120, 120);
			$uploader->setPrefix('smil');
			if ($uploader->fetchMedia($_POST['xoops_upload_file'][0])) {
				if (!$uploader->upload()) {
					$err = $uploader->getErrors();
				} else {
					$smile_url = $uploader->getSavedFileName();
					if (!$db->query(sprintf("UPDATE %s SET code = %s, smile_url = %s, emotion = %s, display = %d WHERE id = '%d'", $db->prefix('smiles'), $db->quoteString($smile_code), $db->quoteString($smile_url), $db->quoteString($smile_desc), $smile_display, $id))) {
						$err = 'Failed storing smiley data into the database';
					} else {
						$oldsmile_path = str_replace("\\", "/", realpath(ICMS_UPLOAD_PATH . '/' . trim($_POST['old_smile'])));
						if (0 === strpos($oldsmile_path, ICMS_UPLOAD_PATH) && is_file($oldsmile_path)) {
							unlink($oldsmile_path);
						}
					}
				}
			} else {
				$err = $uploader->getErrors();
			}
		} else {
			$sql = sprintf("UPDATE %s SET code = %s, emotion = %s, display = '%d' WHERE id = '%d'", $db->prefix('smiles'), $db->quoteString($smile_code), $db->quoteString($smile_desc), $smile_display, $id);
			if (!$db->query($sql)) {
				$err = 'Failed storing smiley data into the database';
			}
		}

		if (!isset($err)) {
			redirect_header('admin.php?fct=smilies&amp;op=SmilesAdmin', 2, _AM_DBUPDATED);
		} else {
			icms_cp_header();
			icms_core_Message::error($err);
			icms_cp_footer();
			exit();
		}
		break;

	case "SmilesDel":
		$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
		if ($id > 0) {
			icms_cp_header();
			icms_core_Message::confirm(array('fct' => 'smilies', 'op' => 'SmilesDelOk', 'id' => $id), 'admin.php', _AM_WAYSYWTDTS);
			icms_cp_footer();
		}
		break;

	case "SmilesDelOk":
		$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
		if ($id <= 0 | !icms::$security->check()) {
			redirect_header('admin.php?fct=smilies', 3, implode('<br />', icms::$security->getErrors()));
		}
		$db =& icms_db_Factory::instance();
		$sql = sprintf("DELETE FROM %s WHERE id = '%u'", $db->prefix('smiles'), $id);
		$db->query($sql);
		redirect_header("admin.php?fct=smilies&amp;op=SmilesAdmin", 2, _AM_DBUPDATED);
		break;

	case "SmilesAdmin":
	default:
		SmilesAdmin();
		break;
}
