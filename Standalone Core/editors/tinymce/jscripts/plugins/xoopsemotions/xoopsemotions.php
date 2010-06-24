<?php
if (file_exists('../mainfile.php')) include_once '../mainfile.php';
if (file_exists('../../mainfile.php')) include_once '../../mainfile.php';
if (file_exists('../../../mainfile.php')) include_once '../../../mainfile.php';
if (file_exists('../../../../mainfile.php')) include_once '../../../../mainfile.php';
if (file_exists('../../../../../mainfile.php')) include_once '../../../../../mainfile.php';
if (file_exists('../../../../../../mainfile.php')) include_once '../../../../../../mainfile.php';
if (file_exists('../../../../../../../mainfile.php')) include_once '../../../../../../../mainfile.php';
if (!defined('XOOPS_ROOT_PATH')) exit();

include_once XOOPS_ROOT_PATH.'/language/'.$icmsConfig['language'].'/misc.php';

xoops_header(false);
?>
<body style="display: none">
	<script type="text/javascript" src="../../tiny_mce_popup.js"></script>
	<script type="text/javascript" src="js/xoopsemotions.js"></script>
	<div align="center">
		<div class="title">
			{#xoopsemotions.desc}
			<br /><br />
		</div>
	</div>
<?php
{
echo '<table width="100%" class="outer">';
echo '<tr><th colspan="3">'._MSC_SMILIES.'</th></tr>';
echo '<tr class="head"><td>'._MSC_CODE.'</td><td>'._MSC_EMOTION.'</td><td>'._IMAGE.'</td></tr>';
if ($getsmiles = $xoopsDB->query("SELECT * FROM ".$xoopsDB->prefix("smiles")))
	{
	$rcolor = 'even';
	while ( $smile = $xoopsDB->fetchArray($getsmiles) )
		{
		$html = "<tr class='$rcolor'>";
		$html.= "<td>".$smile['code']."</td>";
		$html.= "<td>".$smile['emotion']."</td>";
		$html.= "<td><img onmouseover='style.cursor=\"pointer\"' onclick='javascript:XoopsemotionsDialog.insert(\"".XOOPS_UPLOAD_URL."/".$smile['smile_url']."\",\"".$smile['emotion']."\");' src='".XOOPS_UPLOAD_URL."/".$smile['smile_url']."' alt='".$smile['emotion']."' /></td>";
		$html.= "</tr>";
		echo ($html);
		$rcolor = ($rcolor == 'even') ? 'odd' : 'even';
		}
	}
else
	{
	echo "Could not retrieve data from the database.";
	}
echo '</table><br />'._MSC_CLICKASMILIE.'<br />';
}
?>
<div align="right">
<input type="button" name="cancel" value="{#cancel}" onclick="tinyMCEPopup.close();" id="cancel" />
</div>
</body>
</html>
