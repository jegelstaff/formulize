<?php
//
// wf-sections ext waiting plugin
// author: karedokx <karedokx@yahoo.com> 15-Apr-2005
//
function b_waiting_wfsection()
{
	$xoopsDB =& Database::getInstance();
	$ret = array();

	// wf-section articles - new
	$block = array();
	$result = $xoopsDB->query("SELECT COUNT(*) FROM ".$xoopsDB->prefix("wfs_article")." WHERE published=0");
	if ( $result ) {
		$block['adminlink'] = XOOPS_URL."/modules/wfsection/admin/allarticles.php?action=submitted";
		list($block['pendingnum']) = $xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_WAITINGS;
	}
	$ret[] = $block;

	// wf-section articles - modified
	$block = array();
	$result = $xoopsDB->query("SELECT COUNT(*) FROM ".$xoopsDB->prefix("wfs_article_mod")."");
	if ( $result ) {
		$block['adminlink'] = XOOPS_URL."/modules/wfsection/admin/modified.php";
		list($block['pendingnum']) = $xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_MODREQS;
	}
	$ret[] = $block;

	return $ret;
}
?>