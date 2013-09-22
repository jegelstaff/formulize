<?php
/**
 * Images Manager - Image Editor Tool
 *
 * Tool for resize, crop, rotate, apply filters and much more in images.
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		core
 * @since		1.2
 * @author		Rodrigo Pereira Lima (AKA TheRplima) <therplima@impresscms.org>
 * @version		$Id: image-edit.php 1244 2008-03-18 17:09:11Z real_therplima $
 */
$xoopsOption ['nodebug'] = 1;
if (file_exists ( '../../mainfile.php' ))
	include_once '../../mainfile.php';
defined('ICMS_ROOT_PATH') or die('ImpressCMS root path not defined');

include_once ICMS_LIBRARIES_PATH . '/wideimage/lib/WideImage.php';

icms_loadLanguageFile('system', 'images', true);

$icmsTpl = new icms_view_Tpl();

$icmsTpl->assign('icms_url', ICMS_URL);
$icmsTpl->assign('icms_root_path', ICMS_ROOT_PATH);
$icmsTpl->assign('icms_lib_path', ICMS_LIBRARIES_PATH);
$icmsTpl->assign('icms_lib_url', ICMS_LIBRARIES_URL);
$icmsTpl->assign('icms_imanager_temp_path', ICMS_IMANAGER_FOLDER_PATH . '/temp');
$icmsTpl->assign('icms_imanager_temp_url', ICMS_IMANAGER_FOLDER_URL . '/temp');

$image_id = (isset($_GET['image_id'])) ? (int) $_GET['image_id'] : ((isset($_POST['image_id'])) ? (int) $_POST['image_id'] : null);
$uniq = (isset($_GET ['uniq'])) ? $_GET['uniq'] : ((isset($_POST['uniq'])) ? $_POST['uniq'] : null);
$type = (isset($_GET['type'])) ? filter_input(INPUT_GET, 'type') : ((isset($_POST['type'])) ? filter_input(INPUT_POST, 'type') : null);
$target = (isset($_GET['target'])) ? filter_input(INPUT_GET, 'target') : ((isset($_POST['target'])) ? filter_input(INPUT_POST, 'target') : null);
$op = (isset($_GET['op'])) ? filter_input(INPUT_GET, 'op') : ((isset($_POST['op'])) ? filter_input(INPUT_POST, 'op') : null);

if (!file_exists(ICMS_IMANAGER_FOLDER_PATH . '/temp/')) {
	if(!@mkdir(ICMS_IMANAGER_FOLDER_PATH . '/temp', 0777)) {
		echo '<script>alert("Temporary folder doesn\'t exist and cannot be created. Create it manually and try again. Folder: ' . str_ireplace(ICMS_ROOT_PATH, "", ICMS_IMANAGER_FOLDER_PATH) . '/temp/' . '");window.close();</script>';
		exit();
	}
}

if (!is_null($target) && !is_null($type)) {
	if (!isset($_SESSION['icms_imanager'])) {
		session_start();
		$_SESSION['icms_imanager'] = array();
	}
	if (!isset($_SESSION['icms_imanager'] ['imedit_target'])) {
		$_SESSION['icms_imanager'] ['imedit_target'] = $target;
	}
	if (!isset($_SESSION['icms_imanager'] ['imedit_type'])) {
		$_SESSION['icms_imanager'] ['imedit_type'] = $type;
	}
}

if (! is_null ( $op ) && $op == 'cancel') {
	$image_path = isset ( $_GET ['image_path'] ) ? $_GET ['image_path'] : null;

	if (file_exists ( $image_path )) {
		@unlink ( $image_path );
	}

	$arr = explode ( '/', $image_path );
	$arr [count ( $arr ) - 1] = 'orig_' . substr ( $arr [count ( $arr ) - 1], 5, strlen ( $arr [count ( $arr ) - 1] ) );
	$orig_img_path = implode ( '/', $arr );

	if (file_exists ( $orig_img_path )) {
		@unlink ( $orig_img_path );
	}

	$plugins_arr = icms_core_Filesystem::getDirList ( ICMS_LIBRARIES_PATH . '/image-editor/plugins' );
	foreach ( $plugins_arr as $plugin_folder ) {
		if (file_exists ( ICMS_LIBRARIES_PATH . '/image-editor/plugins/' . $plugin_folder . '/icms_plugin_version.php' )) {
			$arr = explode ( '/', $image_path );
			$arr [count ( $arr ) - 1] = $plugin_folder . '_' . $arr [count ( $arr ) - 1];
			$temp_img_path = implode ( '/', $arr );
			@unlink ( $image_path );
		}
	}
	if (isset ( $_SESSION ['icms_imanager'] )) {
		unset ( $_SESSION ['icms_imanager'] );
	}
	echo 'window.close();';
	exit ();
}
if (!is_null($op) && $op == 'save') {
	$simage_id = isset($_GET['image_id']) ? (int) $_GET['image_id'] : null;
	$simage_name = isset($_GET['image_name']) ? filter_input(INPUT_GET, 'image_name') : null;
	$simage_weight = isset($_GET['image_weight']) ? (int) $_GET['image_weight'] : null;
	$simage_display = isset($_GET['image_display']) ? (int) $_GET['image_display'] : null;
	$simage_temp = isset($_GET['image_temp']) ? filter_input(INPUT_GET, 'image_temp') : null;
	$soverwrite = isset($_GET['overwrite']) ? (int) $_GET['overwrite'] : 1;

	$image_handler = icms::handler('icms_image');
	$simage = & $image_handler->get($simage_id);
	$imgcat_handler = icms::handler('icms_image_category');
	$imagecategory = & $imgcat_handler->get ( $simage->getVar ( 'imgcat_id' ) );

	$categ_path = $imgcat_handler->getCategFolder ( $imagecategory );
	$categ_path = (substr ( $categ_path, - 1 ) != '/') ? $categ_path . '/' : $categ_path;
	$categ_url = $imgcat_handler->getCategFolder ( $imagecategory, 1, 'url' );
	$categ_url = (substr ( $categ_url, - 1 ) != '/') ? $categ_url . '/' : $categ_url;

	if ($soverwrite) {
		if ($imagecategory->getVar ( 'imgcat_storetype' ) == 'db') {
			$fp = @fopen ( ICMS_IMANAGER_FOLDER_PATH . '/temp/' . $simage_temp, 'rb' );
			$fbinary = @fread ( $fp, filesize ( ICMS_IMANAGER_FOLDER_PATH . '/temp/' . $simage_temp ) );
			@fclose ( $fp );
			$simage->setVar ( 'image_body', $fbinary, true );
			if (! $image_handler->insert ( $simage )) {
				$msg = sprintf ( _FAILSAVEIMG, $simage->getVar ( 'image_nicename' ) );
			} else {
				$msg = _MD_AM_DBUPDATED;
			}
		} else {
			if (@unlink ( $categ_path . $simage->getVar ( 'image_name' ) )) {
				if (@copy ( ICMS_IMANAGER_FOLDER_PATH . '/temp/' . $simage_temp, $categ_path . $simage->getVar ( 'image_name' ) )) {
					if (@unlink ( ICMS_IMANAGER_FOLDER_PATH . '/temp/' . $simage_temp )) {
						$msg = _MD_AM_DBUPDATED;
					} else {
						$msg = sprintf ( _FAILSAVEIMG, $simage->getVar ( 'image_nicename' ) );
					}
				} else {
					$msg = sprintf ( _FAILSAVEIMG, $simage->getVar ( 'image_nicename' ) );
				}
			} else {
				$msg = sprintf ( _FAILSAVEIMG, $simage->getVar ( 'image_nicename' ) );
			}
		}
	} else {
		$ext = substr ( $simage->getVar ( 'image_name' ), strlen ( $simage->getVar ( 'image_name' ) ) - 3, 3 );
		$imgname = 'img' . icms_random_str ( 12 ) . '.' . $ext;
		$newimg = & $image_handler->create ();
		$newimg->setVar ( 'image_name', $imgname );
		$newimg->setVar ( 'image_nicename', $simage_name );
		$newimg->setVar ( 'image_mimetype', $simage->getVar ( 'image_mimetype' ) );
		$newimg->setVar ( 'image_created', time () );
		$newimg->setVar ( 'image_display', $simage_display );
		$newimg->setVar ( 'image_weight', $simage_weight );
		$newimg->setVar ( 'imgcat_id', $simage->getVar ( 'imgcat_id' ) );
		if ($imagecategory->getVar ( 'imgcat_storetype' ) == 'db') {
			$fp = @fopen ( ICMS_IMANAGER_FOLDER_PATH . '/temp/' . $simage_temp, 'rb' );
			$fbinary = @fread ( $fp, filesize ( ICMS_IMANAGER_FOLDER_PATH . '/temp/' . $simage_temp ) );
			@fclose ( $fp );
			$newimg->setVar ( 'image_body', $fbinary, true );
		} else {
			if (copy ( ICMS_IMANAGER_FOLDER_PATH . '/temp/' . $simage_temp, $categ_path . $imgname )) {
				@unlink ( ICMS_IMANAGER_FOLDER_PATH . '/temp/' . $simage_temp );
			}
		}
		if (! $image_handler->insert ( $newimg )) {
			$msg = sprintf ( _FAILSAVEIMG, $newimg->getVar ( 'image_nicename' ) );
		} else {
			$msg = _MD_AM_DBUPDATED;
		}
	}

	if (isset ( $_SESSION ['icms_imanager'] )) { //Image Editor open by some editor
		$params = '?op=save_edit_ok&amp;imgcat_id=' . (int) $simage->getVar('imgcat_id') . '&amp;msg=' . urlencode($msg);
		if (isset ( $_SESSION ['icms_imanager'] ['imedit_target'] )) {
			$params .= '&target=' . $_SESSION ['icms_imanager'] ['imedit_target'];
		}
		if (isset ( $_SESSION ['icms_imanager'] ['imedit_type'] )) {
			$params .= '&type=' . $_SESSION ['icms_imanager'] ['imedit_type'];
		}
		unset ( $_SESSION ['icms_imanager'] );
	} else { //Image Editor used inside the Image Manager
		$params = '?fct=images&op=save_edit_ok&amp;imgcat_id=' . (int) $simage->getVar('imgcat_id') . '&amp;msg=' . urlencode($msg);
	}
	echo 'cancel_edit();';
	echo 'var url = getOpenerUrl()+"' . $params . '";';
	echo 'window.opener.location.href=url;';
	echo 'window.opener.focus();';
	echo 'window.close();';
	exit ();
}

$image_handler = icms::handler('icms_image');
$original_image = & $image_handler->get ( $image_id );
if (! is_object ( $original_image )) {
	die ( _ERROR );
}

$imgcat_handler = icms::handler('icms_image_category');
$imagecategory = & $imgcat_handler->get ( $original_image->getVar ( 'imgcat_id' ) );
if (! is_object ( $imagecategory )) {
	die ( _ERROR );
}
$categ_path = $imgcat_handler->getCategFolder ( $imagecategory );
$categ_path = (substr ( $categ_path, - 1 ) != '/') ? $categ_path . '/' : $categ_path;
$categ_url = $imgcat_handler->getCategFolder ( $imagecategory, 1, 'url' );
$categ_url = (substr ( $categ_url, - 1 ) != '/') ? $categ_url . '/' : $categ_url;

#Creating the temporary image. This temp image that will be edited and at the end will be converted to the final image.
$temp_img_name = 'temp_' . $uniq . '_' . $original_image->getVar ( 'image_name' );
$orig_img_name = 'orig_' . $uniq . '_' . $original_image->getVar ( 'image_name' );
if (! file_exists ( ICMS_IMANAGER_FOLDER_PATH . '/temp/' . $temp_img_name )) {
	if ($imagecategory->getVar ( 'imgcat_storetype' ) == 'db') {
		$temp_img = WideImage::loadFromString ( $original_image->getVar ( 'image_body' ) );
		$orig_img = WideImage::loadFromString ( $original_image->getVar ( 'image_body' ) );
	} else {
		$temp_img = WideImage::load ( $categ_path . $original_image->getVar ( 'image_name' ) );
		$orig_img = WideImage::load ( $categ_path . $original_image->getVar ( 'image_name' ) );
	}
	$temp_img->saveToFile ( ICMS_IMANAGER_FOLDER_PATH . '/temp/' . $temp_img_name );
	$orig_img->saveToFile ( ICMS_IMANAGER_FOLDER_PATH . '/temp/' . $orig_img_name );
} else {
	$temp_img = WideImage::load ( ICMS_IMANAGER_FOLDER_PATH . '/temp/' . $temp_img_name );
	$orig_img = WideImage::load ( ICMS_IMANAGER_FOLDER_PATH . '/temp/' . $orig_img_name );
}
$img = array ( );
$img ['name'] = $temp_img_name;
$img ['originalname'] = $original_image->getVar ( 'image_name' );
$img ['id'] = $original_image->getVar ( 'image_id' );
$img ['title'] = $original_image->getVar ( 'image_nicename' );
$img ['url'] = ICMS_IMANAGER_FOLDER_URL . '/temp/' . $temp_img_name;
$img ['previewurl'] = ICMS_IMANAGER_FOLDER_URL . '/temp/' . $temp_img_name . '?' . time ();
$img ['originalurl'] = ICMS_IMANAGER_FOLDER_URL . '/temp/' . $orig_img_name;
$img ['path'] = ICMS_IMANAGER_FOLDER_PATH . '/temp/' . $temp_img_name;
$img ['width'] = $temp_img->getWidth ();
$img ['height'] = $temp_img->getHeight ();
$img ['size'] = icms_convert_size ( filesize ( ICMS_IMANAGER_FOLDER_PATH . '/temp/' . $temp_img_name ) );
$img ['ori_width'] = $orig_img->getWidth ();
$img ['ori_height'] = $orig_img->getHeight ();
$img ['ori_size'] = icms_convert_size ( filesize ( ICMS_IMANAGER_FOLDER_PATH . '/temp/' . $orig_img_name ) );

$icmsTpl->assign ( 'image', $img );

#Getting the plugins for the editor
$plugins_arr = icms_core_Filesystem::getDirList ( ICMS_LIBRARIES_PATH . '/image-editor/plugins' );
foreach ( $plugins_arr as $plugin_folder ) {
	if (file_exists ( ICMS_LIBRARIES_PATH . '/image-editor/plugins/' . $plugin_folder . '/icms_plugin_version.php' )) {
		if (file_exists ( ICMS_LIBRARIES_PATH . '/image-editor/plugins/' . $plugin_folder . '/language/' . $icmsConfig ['language'] . '/main.php' )) {
			include_once ICMS_LIBRARIES_PATH . '/image-editor/plugins/' . $plugin_folder . '/language/' . $icmsConfig ['language'] . '/main.php';
		}
		include_once ICMS_LIBRARIES_PATH . '/image-editor/plugins/' . $plugin_folder . '/icms_plugin_version.php';
		$icmsTpl->append ( 'plugins', $plugversion );
		unset ( $plugversion );
	}
}

echo $icmsTpl->fetch ( ICMS_LIBRARIES_PATH . '/image-editor/templates/image-editor.html' );
?>