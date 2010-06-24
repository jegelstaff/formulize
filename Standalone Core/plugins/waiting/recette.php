<?php
function b_waiting_recette(){
	$xoopsDB =& Database::getInstance();
	$block = array();

	// news
	$result = $xoopsDB->query("SELECT COUNT(*) FROM ".$xoopsDB->prefix("recette")." WHERE published=0");
	if ( $result ) {
		$block['adminlink'] = XOOPS_URL."/modules/recette/admin/index.php?op=newarticle" ;
		list($block['pendingnum']) = $xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_SUBMITTED ;
	}

	return $block;
}
?>