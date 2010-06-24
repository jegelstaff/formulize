<?php
function b_waiting_xcgal(){
	$xoopsDB =& Database::getInstance();
	$block = array();

	$result = $xoopsDB->query("SELECT count(*) FROM ".$xoopsDB->prefix("xcgal_pictures")." WHERE approved = 'NO'");
	if ( $result ) {
		$block['adminlink'] = XOOPS_URL."/modules/xcgal/editpics.php?mode=upload_approval";
		list($block['pendingnum']) = $xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_WAITINGS ;
	}

	return $block;
}
?>