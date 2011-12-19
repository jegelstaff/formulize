<?php
/*************************************************************************/
# Waiting Contents Extensible                                            #
# Plugin for module PDlinks                                              #
#                                                                        #
# Author                                                                 #
# flying.tux     -   flying.tux@gmail.com                                #
#                                                                        #
# Last modified on 21.04.2005                                            #
/*************************************************************************/
function b_waiting_PDlinks()
{
	$xoopsDB =& Database::getInstance();
	$ret = array() ;

	// PDlinks waiting
	$block = array();
	$result = $xoopsDB->query("SELECT COUNT(*) FROM ".$xoopsDB->prefix("PDlinks_links")." WHERE status=0");
	if ( $result ) {
		$block['adminlink'] = XOOPS_URL."/modules/PDlinks/admin/newlinks.php";
		list($block['pendingnum']) = $xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_WAITINGS ;
	}
	$ret[] = $block ;

	// PDlinks broken
	$block = array();
	$result = $xoopsDB->query("SELECT COUNT(*) FROM ".$xoopsDB->prefix("PDlinks_broken"));
	if ( $result ) {
		$block['adminlink'] = XOOPS_URL."/modules/PDlinks/admin/brokenlink.php";
		list($block['pendingnum']) = $xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_BROKENS ;
	}
	$ret[] = $block ;

	// PDlinks modreq
	$block = array();
	$result = $xoopsDB->query("SELECT COUNT(*) FROM ".$xoopsDB->prefix("PDlinks_mod"));
	if ( $result ) {
		$block['adminlink'] = XOOPS_URL."/modules/PDlinks/admin/index.php?op=listModReq";
		list($block['pendingnum']) = $xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_MODREQS ;
	}
	$ret[] = $block ;

	return $ret;
}

?>