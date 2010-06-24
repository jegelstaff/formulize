<?
$xoopsOption['nodebug'] = 1;
if (file_exists('../../../../mainfile.php')) include_once '../../../../mainfile.php';
if (!defined('XOOPS_ROOT_PATH')) exit();
include_once ICMS_LIBRARIES_PATH.'/wideimage/lib/WideImage.php';

if(isset($_GET['image_path']) && isset($_GET['image_url'])){
	$x = $_GET['x'];
	$y = $_GET['y'];
	$width = $_GET['width'];
	$height = $_GET['height'];
	$image_path = $_GET['image_path'];
	$image_url = $_GET['image_url'];
	$percentSize = $_GET['percentSize'];
	$save = isset($_GET['save'])?$_GET['save']:0;
	$del  = isset($_GET['delprev'])?$_GET['delprev']:0;

	$x = preg_replace("/[^0-9]/si","",$x);
	$y = preg_replace("/[^0-9]/si","",$y);
	$width = preg_replace("/[^0-9]/si","",$width);
	$height = preg_replace("/[^0-9]/si","",$height);
	$percentSize = preg_replace("/[^0-9]/si","",$percentSize);

	if($percentSize>200)$percentSize = 200;


	$img = WideImage::load($image_path);
	$arr = explode('/',$image_path);
	$arr[count($arr)-1] = 'crop_'.$arr[count($arr)-1];
	$temp_img_path = implode('/',$arr);
	$arr = explode('/',$image_url);
	$arr[count($arr)-1] = 'crop_'.$arr[count($arr)-1];
	$temp_img_url = implode('/',$arr);

	if ($del){
		@unlink($temp_img_path);
		exit;
	}

	if(strlen($x) && strlen($y) && $width && $height && $percentSize){

		if($percentSize!="100"){
			$x = $x * ($percentSize/100);
			$y = $y * ($percentSize/100);
			$width = $width * ($percentSize/100);
			$height = $height * ($percentSize/100);
		}
		$img->crop($x,$y,$width,$height)->saveToFile($temp_img_path);

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
			echo "var w = window.open('".$temp_img_url."','crop_image_preview','width=".($width+20).",height=".($height+20).",resizable=yes');";
			echo "w.onunload = function (){crop_delpreview();}";
		}
	}else{
		echo "alert('"._ERROR."');";
	}
}
?>