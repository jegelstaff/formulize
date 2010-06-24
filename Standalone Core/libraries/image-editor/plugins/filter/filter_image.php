<?
$xoopsOption['nodebug'] = 1;
if (file_exists('../../../../mainfile.php')) include_once '../../../../mainfile.php';
if (!defined('XOOPS_ROOT_PATH')) exit();
include_once ICMS_LIBRARIES_PATH.'/wideimage/lib/WideImage.php';

if(isset($_GET['image_path']) && isset($_GET['image_url'])){
	$image_path = isset($_GET['image_path'])?$_GET['image_path']:null;
	$image_url = isset($_GET['image_url'])?$_GET['image_url']:null;
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
	$save = isset($_GET['save'])?$_GET['save']:0;
	$del  = isset($_GET['delprev'])?$_GET['delprev']:0;

	if (is_null($filter) || $filter == ''){
		exit;
	}

	$img = WideImage::load($image_path);
	$arr = explode('/',$image_path);
	$arr[count($arr)-1] = 'filter_'.$arr[count($arr)-1];
	$temp_img_path = implode('/',$arr);
	$arr = explode('/',$image_url);
	$arr[count($arr)-1] = 'filter_'.$arr[count($arr)-1];
	$temp_img_url = implode('/',$arr);

	if ($del){
		@unlink($temp_img_path);
		exit;
	}

	if (!is_null($filter)){
		if ($filter == 'IMG_FILTER_SEPIA'){
			$img->applyFilter(IMG_FILTER_GRAYSCALE)->applyFilter(IMG_FILTER_COLORIZE, 90, 60, 30)->saveToFile($temp_img_path);
		}else{
			$img->applyFilter(constant($filter),implode(',',$args))->saveToFile($temp_img_path);
		}
	}

	if ($save){
		if (!@unlink($image_path)){
			echo "alert('"._ERROR."');";
			exit;
		}
		if (!@copy($temp_img_path,$image_path)) {
			echo "alert('"._ERROR."');";
			exit;
		}
		if (!@unlink($temp_img_path)){
			echo "alert('"._ERROR."');";
			exit;
		}
		echo 'window.location.reload( true );';
	}else{
		$width = $img->getWidth();
		$height = $img->getHeight();
		echo "var w = window.open('".$temp_img_url."','crop_image_preview','width=".($width+20).",height=".($height+20).",resizable=yes');";
		echo "w.onunload = function (){filter_delpreview();}";
	}
}
?>