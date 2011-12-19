<?php
function b_waiting_myAds(){
	$xoopsDB =& Database::getInstance();
	$block = array();

	$result = $xoopsDB->query("SELECT COUNT(*) FROM ".$xoopsDB->prefix("ann_annonces")." WHERE valid='No'");
	if ( $result ) {
		$block['adminlink'] = XOOPS_URL."/modules/myAds/admin/index.php";
		list($block['pendingnum']) = $xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_WAITINGS ;
	}

	return $block;
}
?>