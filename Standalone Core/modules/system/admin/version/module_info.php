<?php
/**
* Version checker, module_info file
*
* @copyright      http://www.impresscms.org/ The ImpressCMS Project
* @license         LICENSE.txt
* @package	Administration
* @since            1.0
* @version		$Id: module_info.php 8570 2009-04-11 13:48:52Z icmsunderdog $
*/

if (!defined('XOOPS_ROOT_PATH')) {
	die("ImpressCMS root path not defined");
}

if (intval($_GET['mid'])) {
	$module_handler =& xoops_gethandler('module');
	$versioninfo =& $module_handler->get(intval($_GET['mid']));
} else {
	$mid = str_replace('..', '', trim($_GET['mid']));
	if (file_exists(XOOPS_ROOT_PATH.'/modules/'.$mid.'/xoops_version.php') || file_exists(XOOPS_ROOT_PATH.'/modules/'.$mid.'/icms_version.php')) {
		$module_handler =& xoops_gethandler('module');
		$versioninfo =& $module_handler->create();
		$versioninfo->loadInfo($mid);
	}
}
if (!isset($versioninfo) || !is_object($versioninfo)) {
	exit();
}

//$css = getCss($theme);
echo "<html>\n<head>\n";
echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset="._CHARSET."\"></meta>\n";
echo "<title>".htmlspecialchars($xoopsConfig['sitename'])."</title>\n";

?>
<script type="text/javascript">
<!--//
scrollID=0;
vPos=0;

function onWard() {
   vPos+=2;
   window.scroll(0,vPos);
   vPos%=1000;
   scrollID=setTimeout("onWard()",30);
   }
function stop(){
   clearTimeout(scrollID);
}
//-->
</script>
<?php
/*
if($css){
    echo "<link rel=\"stylesheet\" href=\"".$css."\" type=\"text/css\">\n\n";
}
*/
echo "</head>\n";
echo "<body onLoad=\"if(window.scroll)onWard()\" onmouseover=\"stop()\" onmouseout=\"if(window.scroll)onWard()\">\n";
echo "<div><table width=\"100%\"><tr><td align=\"center\"><br /><br /><br /><br /><br />";
if ($modimage = $versioninfo->getInfo('image')) {
	$modimage_path = '/modules/'.$versioninfo->getInfo('dirname').'/'.$modimage;
	$modimage_realpath = str_replace("\\", "/", realpath(XOOPS_ROOT_PATH.$modimage_path));
	if (0 === strpos($modimage_realpath, XOOPS_ROOT_PATH) && is_file($modimage_realpath)) {
		echo "<img src='".XOOPS_URL.$modimage_path."' border='0' /><br />";
	}
}
if ($modname = $versioninfo->getInfo('name')) {
    echo "<big><b>".htmlspecialchars($modname)."</b></big>";
}

$modinfo = array('Version', 'Description', 'Author', 'Credits', 'License');
foreach ($modinfo as $info) {
	if ($info_output = $versioninfo->getInfo(strtolower($info))) {
		echo "<br /><br /><u>$info</u><br />";
		echo htmlspecialchars($info_output);
	}
}
echo "<br /><br /><br /><br /><br />";
echo "<br /><br /><br /><br /><br />";
echo "<a href=\"javascript:window.close();\">Close</a>";
echo "<br /><br /><br /><br /><br /><br />";
echo "</td></tr></table></div>";
echo "</body></html>";

?>