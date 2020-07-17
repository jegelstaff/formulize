<?php
// not tested
function b_waiting_agendax() {
	$block = array();

	// agenda-x events
	$result = icms::$xoopsDB->query("SELECT COUNT(*) FROM ".icms::$xoopsDB->prefix("agendax_events")." WHERE approved=0");
	if ($result) {
		$block['adminlink'] = ICMS_URL."/modules/agendax/admin/index.php?listNewLinks";
		list($block['pendingnum']) = icms::$xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_EVENTS ;
	}

	return $block;
}
?>