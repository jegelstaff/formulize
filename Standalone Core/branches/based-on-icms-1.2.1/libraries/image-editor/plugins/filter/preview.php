<?php
$xoopsOption['nodebug'] = 1;
if (file_exists('../../../../../../../mainfile.php')) include_once '../../../../../../../mainfile.php';
if (file_exists('../../../../../../mainfile.php')) include_once '../../../../../../mainfile.php';
if (file_exists('../../../../../mainfile.php')) include_once '../../../../../mainfile.php';
if (file_exists('../../../../mainfile.php')) include_once '../../../../mainfile.php';
if (file_exists('../../../mainfile.php')) include_once '../../../mainfile.php';
if (file_exists('../../mainfile.php')) include_once '../../mainfile.php';
if (file_exists('../mainfile.php')) include_once '../mainfile.php';
if (!defined('XOOPS_ROOT_PATH')) exit();
include(ICMS_LIBRARIES_PATH."/wideimage/lib/WideImage.php");

$file = $_GET['file'];
$resize = isset($_GET['resize'])?$_GET['resize']:1;
$filter = isset($_GET['filter'])?$_GET['filter']:null;
$args = array();
if (isset($_GET['arg1'])){
	$args[] = $_GET['arg1'];
}
if (isset($_GET['arg2'])){
	$args[] = $_GET['arg2'];
}
if (isset($_GET['arg3'])){
	$args[] = $_GET['arg3'];
}

$img = WideImage::load($file);

$width = $img->getWidth();
$height = $img->getHeight();

header('Content-type: image/png');
if (!is_null($filter)){
	if ($filter == 'IMG_FILTER_SEPIA'){
		if ($resize && ($width > 400 || $height > 300)){
			echo $img->resize(400, 300)->applyFilter(IMG_FILTER_GRAYSCALE)->applyFilter(IMG_FILTER_COLORIZE, 90, 60, 30)->asString('png');
		}else{
			echo $img->applyFilter(IMG_FILTER_GRAYSCALE)->applyFilter(IMG_FILTER_COLORIZE, 90, 60, 30)->asString('png');
		}
	}else{
		if ($resize && ($width > 400 || $height > 300)){
			echo $img->resize(400, 300)->applyFilter(constant($filter),implode(',',$args))->asString('png');
		}else{
			echo $img->applyFilter(constant($filter),implode(',',$args))->asString('png');
		}
	}
}
?>