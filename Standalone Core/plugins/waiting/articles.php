<?php
function b_waiting_articles(){
	$xoopsDB =& Database::getInstance();
	$block = array();

	$result = $xoopsDB->query("SELECT COUNT(*) FROM ".$xoopsDB->prefix("articles_main")." WHERE art_validated = 0");
	if ( $result ) {
		$block['adminlink'] = XOOPS_URL."/modules/articles/admin/validate.php" ;
		list($block['pendingnum']) = $xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_SUBMITTED ;
	}

	return $block;
}
?>