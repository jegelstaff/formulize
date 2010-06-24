<?php
//
// xf-section ext waiting plugin
// author: Mel Bezos <xoops@bezos.cc, www.bezoops.net> 14-Oct-2005
//
function b_waiting_xfsection()
{
	$xoopsDB =& Database::getInstance();
	$ret = array();

	// xf-section articles - waiting
	$block = array();
	$result = $xoopsDB->query("SELECT COUNT(*) FROM ".$xoopsDB->prefix("xfs_article")." WHERE published=0");
	if ( $result ) {
		$block['adminlink'] = XOOPS_URL."/modules/xfsection/admin/allarticles.php?action=submitted";
		list($block['pendingnum']) = $xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_WAITINGS;
	}
	$ret[] = $block;

	// xf-section articles - attach broken
	$block = array();
	$result = $xoopsDB->query("SELECT COUNT(*) FROM ".$xoopsDB->prefix("xfs_broken")."");
	if ( $result ) {
		$block['adminlink'] = XOOPS_URL."/modules/xfsection/admin/brokendown.php?op=listBrokenDownloads";
		list($block['pendingnum']) = $xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_FILES."&nbsp;"._PI_WAITING_BROKENS;
	}
	$ret[] = $block;

	return $ret;
}
?>