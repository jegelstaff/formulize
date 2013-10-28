<?php
/**
 * SmartPartner plugin
 *
 * @author Marius Scurtescu <mariuss@romanians.bc.ca>
 *
 */
function b_waiting_smartpartner() {
	$block = array();

	// smartpartner submitted
	$result = icms::$xoopsDB->query("SELECT COUNT(*) FROM ".icms::$xoopsDB->prefix("smartpartner_partner")." WHERE status=1");
	if ($result) {
		$block['adminlink'] = ICMS_URL."/modules/smartpartner/admin/index.php?statussel=1";
		list($block['pendingnum']) = icms::$xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_SUBMITTED;
	}

	return $block;
}

?>