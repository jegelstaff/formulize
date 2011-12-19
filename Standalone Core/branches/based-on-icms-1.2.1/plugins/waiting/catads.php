<?php
function b_waiting_catads(){
   	$block = array();
	$ads_hnd =& xoops_getmodulehandler('ads', 'catads');
	$criteria = new Criteria('waiting', '1', '=');
	$nbads = $ads_hnd->getCount($criteria);
   	if ( $nbads > 0 ) {
       $block['adminlink'] = XOOPS_URL."/modules/catads/admin/index.php?action=waiting";
       $block['pendingnum'] = $nbads;
       $block['lang_linkname'] = _PI_WAITING_WAITINGS ;

   	}
   return $block;
}
?>