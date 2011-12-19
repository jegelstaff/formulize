<?php
/**
 * SmartPartner plugin
 *
 * @author Marius Scurtescu <mariuss@romanians.bc.ca>
 *
 */
function b_waiting_smartpartner()
{
	$xoopsDB =& Database::getInstance();
	$block = array();

	// smartpartner submitted
	$result = $xoopsDB->query("SELECT COUNT(*) FROM ".$xoopsDB->prefix("smartpartner_partner")." WHERE status=1");
	if ( $result ) {
		$block['adminlink'] = XOOPS_URL."/modules/smartpartner/admin/index.php?statussel=1";
		list($block['pendingnum']) = $xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_SUBMITTED;
	}

	return $block;
}

?>