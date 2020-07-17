<?php
function b_waiting_catads() {
   	$block = array();
	$ads_hnd =& icms_getModuleHandler('ads', 'catads');
	$criteria = new icms_db_criteria_Item('waiting', '1', '=');
	$nbads = $ads_hnd->getCount($criteria);
   	if ($nbads > 0) {
       $block['adminlink'] = ICMS_URL."/modules/catads/admin/index.php?action=waiting";
       $block['pendingnum'] = $nbads;
       $block['lang_linkname'] = _PI_WAITING_WAITINGS ;

   	}
   return $block;
}
?>