<?php
// not tested
function b_waiting_agendax()
{
	$xoopsDB =& Database::getInstance();
	$block = array();

	// agenda-x events
	$result = $xoopsDB->query("SELECT COUNT(*) FROM ".$xoopsDB->prefix("agendax_events")." WHERE approved=0");
	if ( $result ) {
		$block['adminlink'] = XOOPS_URL."/modules/agendax/admin/index.php?listNewLinks";
		list($block['pendingnum']) = $xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_EVENTS ;
	}

	return $block;
}
?>