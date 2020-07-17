<?php
function b_waiting_extcal() {
	$block = array();

	// extcal events
	$result = icms::$xoopsDB->query("SELECT COUNT(*) FROM ".icms::$xoopsDB->prefix("extcal_event")." WHERE event_approved=0");
	if ($result) {
		$block['adminlink'] = ICMS_URL."/modules/extcal/admin/index.php";
		list($block['pendingnum']) = icms::$xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_EVENTS ;
	}

	return $block;
}
?>