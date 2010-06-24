<?php
function b_waiting_news(){
	$xoopsDB =& Database::getInstance();
	$block = array();

	// news
	$result = $xoopsDB->query("SELECT COUNT(*) FROM ".$xoopsDB->prefix("stories")." WHERE published=0");
	if ( $result ) {
		$block['adminlink'] = XOOPS_URL."/modules/news/admin/index.php?op=newarticle" ;
		list($block['pendingnum']) = $xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_SUBMITTED ;
	}

	return $block;
}
?>