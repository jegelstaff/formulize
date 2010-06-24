<?php
/*************************************************************************/
# Waiting Contents Extensible                                            #
# Plugin for module PDdownloads                                          #
#                                                                        #
# Author                                                                 #
# flying.tux     -   flying.tux@gmail.com                                #
#                                                                        #
# Last modified on 21.04.2005                                            #
/*************************************************************************/
function b_waiting_xyp4all()
{
	$xoopsDB =& Database::getInstance();
	$ret = array() ;

	// xyp4all links
	$block = array();
	$result = $xoopsDB->query("SELECT COUNT(*) FROM ".$xoopsDB->prefix("xyp_links")." WHERE status=0");
	if ( $result ) {
		$block['adminlink'] = XOOPS_URL."/modules/xyp4all/admin/index.php?op=listNewLinks";
		list($block['pendingnum']) = $xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_LINKS ;
	}
	$ret[] = $block ;

	// xyp4all broken
	$block = array();
	$result = $xoopsDB->query("SELECT COUNT(*) FROM ".$xoopsDB->prefix("xyp_broken"));
	if ( $result ) {
		$block['adminlink'] = XOOPS_URL."/modules/xyp4all/admin/index.php?op=listBrokenLinks";
		list($block['pendingnum']) = $xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_BROKENS ;
	}
	$ret[] = $block ;

	// xyp4all modreq
	$block = array();
	$result = $xoopsDB->query("SELECT COUNT(*) FROM ".$xoopsDB->prefix("xyp_mod"));
	if ( $result ) {
		$block['adminlink'] = XOOPS_URL."/modules/xyp4all/admin/index.php?op=listModReq";
		list($block['pendingnum']) = $xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_MODREQS ;
	}
	$ret[] = $block ;

	return $ret;
}

?>