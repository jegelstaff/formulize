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
$width = isset($_GET['width'])?$_GET['width']:null;
$height = isset($_GET['height'])?$_GET['height']:null;

if (substr($width,0,strlen($width)-1) == '%' || substr($height,0,strlen($height)-1) == '%'){
	$fit = 'fill';
}else{
	$fit = 'inside';
}

$img = WideImage::load($file);

header('Content-type: image/png');
echo $img->resize($width, $height, $fit)->asString('png');
?>