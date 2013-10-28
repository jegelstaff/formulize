<?php
function b_waiting_recette() {
	$block = array();

	// news
	$result = icms::$xoopsDB->query("SELECT COUNT(*) FROM ".icms::$xoopsDB->prefix("recette")." WHERE published=0");
	if ($result) {
		$block['adminlink'] = ICMS_URL."/modules/recette/admin/index.php?op=newarticle" ;
		list($block['pendingnum']) = icms::$xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_SUBMITTED ;
	}

	return $block;
}
?>