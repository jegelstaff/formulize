<?php
$xoopsOption['nodebug'] = 1;
if (file_exists('../../../../mainfile.php')) include_once '../../../../mainfile.php';
if (!defined('ICMS_ROOT_PATH')) die("ImpressCMS root path not defined");
include_once ICMS_LIBRARIES_PATH . '/wideimage/lib/WideImage.php';

if (isset($_GET['image_path']) && isset($_GET['image_url'])) {
	$image_path = isset($_GET['image_path']) ? filter_input(INPUT_GET, 'image_path') : null;
	$image_url = isset($_GET['image_url']) ? filter_input(INPUT_GET, 'image_url', FILTER_SANITIZE_URL) : null;
	$width = isset($_GET['width']) ? (int) $_GET['width'] : null;
	$height = isset($_GET['height']) ? (int) $_GET['height'] : null;

	if (substr($width, 0, strlen($width)-1) == '%' || substr($height, 0, strlen($height)-1) == '%') {
		$fit = 'fill';
	} else {
		$fit = 'inside';
	}

	$save = isset($_GET['save']) ? (int) $_GET['save'] : 0;
	$del  = isset($_GET['delprev']) ? (int) $_GET['delprev'] : 0;

	$img = WideImage::load($image_path);
	$arr = explode('/', $image_path);
	$arr[count($arr)-1] = 'resize_' . $arr[count($arr)-1];
	$temp_img_path = implode('/', $arr);
	$arr = explode('/', $image_url);
	$arr[count($arr)-1] = 'resize_' . $arr[count($arr)-1];
	$temp_img_url = implode('/', $arr);

	if ($del) {
		@unlink($temp_img_path);
		exit;
	}

	$img->resize($width, $height, $fit)->saveToFile($temp_img_path);


	if ($save) {
		if (!@unlink($image_path)) {
			echo "alert('" . _ERROR . "');";
			exit;
		}
		if (!@copy($temp_img_path, $image_path)) {
			echo "alert('" . _ERROR . "');";
			exit;
		}
		if (!@unlink($temp_img_path)) {
			echo "alert('" . _ERROR . "');";
			exit;
		}
		echo 'window.location.reload( true );';
	} else {
		echo "var w = window.open('".$temp_img_url."','resize_image_preview','width=".($width+20).",height=".($height+20).",resizable=yes');";
		echo "w.onunload = function (){resize_delpreview();}";
	}
}
