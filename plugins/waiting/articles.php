<?php
function b_waiting_articles() {
	$block = array();

	$result = icms::$xoopsDB->query("SELECT COUNT(*) FROM ".icms::$xoopsDB->prefix("articles_main")." WHERE art_validated = 0");
	if ($result) {
		$block['adminlink'] = ICMS_URL."/modules/articles/admin/validate.php" ;
		list($block['pendingnum']) = icms::$xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_SUBMITTED ;
	}

	return $block;
}
?>