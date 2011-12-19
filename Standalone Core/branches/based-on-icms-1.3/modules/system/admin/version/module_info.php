<?php
/**
 * Version checker, module_info file
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @package		System
 * @subpackage	Version
 * @since		1.0
 * @version		SVN: $Id: module_info.php 21828 2011-06-21 13:52:19Z mcdonald3072 $
 */

defined('ICMS_ROOT_PATH') || die("ImpressCMS root path not defined");

if ((int) $_GET['mid']) {
	$module_handler = icms::handler('icms_module');
	$versioninfo =& $module_handler->get($_GET['mid']);
} else {
	$mid = str_replace('..', '', trim($_GET['mid']));
	if (file_exists(ICMS_MODULES_PATH . '/' . $mid . '/icms_version.php') || file_exists(ICMS_MODULES_PATH . '/' . $mid . '/xoops_version.php')) {
		$module_handler = icms::handler('icms_module');
		$versioninfo =& $module_handler->create();
		$versioninfo->loadInfo($mid);
	}
}
if (!isset($versioninfo) || !is_object($versioninfo)) {
	exit();
}

//$css = getCss($theme);
echo "<html>\n<head>\n"
	. "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=" . _CHARSET . "\"></meta>\n"
	. "<title>" . htmlspecialchars($icmsConfig['sitename']) . "</title>\n";

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
	function stop() {
	   clearTimeout(scrollID);
	}
	//-->
	</script>
<?php
/*
 if ($css) {
 echo "<link rel=\"stylesheet\" href=\"".$css."\" type=\"text/css\">\n\n";
 }
 */
echo "</head>\n"
. "<body onLoad=\"if (window.scroll)onWard()\" onmouseover=\"stop()\" onmouseout=\"if (window.scroll)onWard()\">\n"
. "<div><table width=\"100%\"><tr><td align=\"center\"><br /><br /><br /><br /><br />";
if ($modimage = $versioninfo->getInfo('image')) {
	$modimage_path = '/modules/' . $versioninfo->getInfo('dirname') . '/' . $modimage;
	$modimage_realpath = str_replace("\\", "/", realpath(ICMS_ROOT_PATH . $modimage_path));
	if (0 === strpos($modimage_realpath, ICMS_ROOT_PATH) && is_file($modimage_realpath)) {
		echo "<img src='" . ICMS_URL . $modimage_path . "' border='0' /><br />";
	}
}
if ($modname = $versioninfo->getInfo('name')) {
	echo "<big><b>" . htmlspecialchars($modname) . "</b></big>";
}

$modinfo = array(_VERSION, _DESCRIPTION, _AUTHOR, _CREDITS, _LICENSE);
foreach ($modinfo as $info) {
	if ($info_output = $versioninfo->getInfo(strtolower($info))) {
		echo "<br /><br /><u>$info</u><br />";
		echo htmlspecialchars($info_output);
	}
}
echo "<br /><br /><br /><br /><br />";
echo "<br /><br /><br /><br /><br />";
echo "<a href=\"javascript:window.close();\">" . _CLOSE . "</a>";
echo "<br /><br /><br /><br /><br /><br />";
echo "</td></tr></table></div>";
echo "</body></html>";

