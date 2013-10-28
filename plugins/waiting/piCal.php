<?php
function b_waiting_piCal() {
	$block = array();

	//piCal
	$result = icms::$xoopsDB->query("SELECT COUNT(*) FROM ".icms::$xoopsDB->prefix("pical_event")." WHERE admission<1 AND (rrule_pid=0 OR rrule_pid=id)");
	if ($result) {
		$block['adminlink'] = ICMS_URL."/modules/piCal/admin/admission.php";
		list($block['pendingnum']) = icms::$xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_EVENTS ;
	}

	return $block;
}
?>