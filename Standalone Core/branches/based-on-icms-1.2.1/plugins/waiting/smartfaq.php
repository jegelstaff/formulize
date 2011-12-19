<?php
/**
 * XoopsFAQ plugin
 *
 * @author Marius Scurtescu <mariuss@romanians.bc.ca>
 *
 */
function b_waiting_smartfaq()
{
	$xoopsDB =& Database::getInstance();
	$ret = array() ;

	// smartfaq submitted
	$block = array();
	$result = $xoopsDB->query("SELECT COUNT(*) FROM ".$xoopsDB->prefix("smartfaq_faq")." WHERE status=4");
	if ( $result ) {
		$block['adminlink'] = XOOPS_URL."/modules/smartfaq/admin/index.php?statussel=4";
		list($block['pendingnum']) = $xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_SUBMITTED;
	}
	$ret[] = $block ;

	// smartfaq asked
	$block = array();
	$result = $xoopsDB->query("SELECT COUNT(*) FROM ".$xoopsDB->prefix("smartfaq_faq")." WHERE status=1");
	if ( $result ) {
		$block['adminlink'] = XOOPS_URL."/modules/smartfaq/admin/index.php?statussel=1";
		list($block['pendingnum']) = $xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_ASKED;
	}
	$ret[] = $block ;

	// smartfaq new answer
	$block = array();
	$result = $xoopsDB->query("SELECT COUNT(*) FROM ".$xoopsDB->prefix("smartfaq_faq")." WHERE status=6");
	if ( $result ) {
		$block['adminlink'] = XOOPS_URL."/modules/smartfaq/admin/index.php?statussel=6";
		list($block['pendingnum']) = $xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_NEWANSWERS;
	}
	$ret[] = $block ;

	// smartfaq answered
	$block = array();
	$result = $xoopsDB->query("SELECT COUNT(*) FROM ".$xoopsDB->prefix("smartfaq_faq")." WHERE status=3");
	if ( $result ) {
		$block['adminlink'] = XOOPS_URL."/modules/smartfaq/admin/index.php?statussel=3";
		list($block['pendingnum']) = $xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_ANSWERED;
	}
	$ret[] = $block ;

	return $ret;
}

?>