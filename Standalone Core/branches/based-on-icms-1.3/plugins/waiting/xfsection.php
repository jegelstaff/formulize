<?php
//
// xf-section ext waiting plugin
// author: Mel Bezos <xoops@bezos.cc, www.bezoops.net> 14-Oct-2005
//
function b_waiting_xfsection() {
	$ret = array();

	// xf-section articles - waiting
	$block = array();
	$result = icms::$xoopsDB->query("SELECT COUNT(*) FROM ".icms::$xoopsDB->prefix("xfs_article")." WHERE published=0");
	if ($result) {
		$block['adminlink'] = ICMS_URL."/modules/xfsection/admin/allarticles.php?action=submitted";
		list($block['pendingnum']) = icms::$xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_WAITINGS;
	}
	$ret[] = $block;

	// xf-section articles - attach broken
	$block = array();
	$result = icms::$xoopsDB->query("SELECT COUNT(*) FROM ".icms::$xoopsDB->prefix("xfs_broken")."");
	if ($result) {
		$block['adminlink'] = ICMS_URL."/modules/xfsection/admin/brokendown.php?op=listBrokenDownloads";
		list($block['pendingnum']) = icms::$xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_FILES."&nbsp;"._PI_WAITING_BROKENS;
	}
	$ret[] = $block;

	return $ret;
}
?>