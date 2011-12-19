<?php
function b_waiting_AMS()
{
	$xoopsDB =& Database::getInstance();
	$block = array();

	// AMS articles
	$result = $xoopsDB->query("SELECT COUNT(*) FROM ".$xoopsDB->prefix("ams_article")." WHERE published=0");
	if ( $result ) {
		$block['adminlink'] = XOOPS_URL."/modules/AMS/admin/index.php?op=newarticle";
		list($block['pendingnum']) = $xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_WAITINGS ;
	}

	return $block;
}
?>