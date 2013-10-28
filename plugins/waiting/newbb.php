<?php
function b_waiting_newbb() {
	$block = array();

	// judge the version of newbb/
	if (! file_exists( ICMS_ROOT_PATH . '/modules/newbb/polls.php' )) {
		// newbb1
		return array() ;
	}

	// works with newbb2 or CBB 1.14
	$result = icms::$xoopsDB->query("SELECT COUNT(*) FROM ".icms::$xoopsDB->prefix("bb_posts")." WHERE approved=0");
	if ($result) {
		$block['adminlink'] = ICMS_URL."/modules/newbb/admin/index.php" ;
		list($block['pendingnum']) = icms::$xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_SUBMITTED ;
	}

	return $block;
}
?>