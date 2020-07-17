<?php
function b_waiting_tutorials() {
	$block = array();

	// tutorials
	$myts =& icms_core_Textsanitizer::getInstance();

	$result = icms::$xoopsDB->query("select count(*) from ".icms::$xoopsDB->prefix("tutorials")." WHERE status=0 or status=2 order by date");
	if ($result) {
		$block['adminlink'] = ICMS_URL."/modules/tutorials/admin/index.php" ;
		list($block['pendingnum']) = icms::$xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_WAITINGS ;
	}

	return $block;
}

?>