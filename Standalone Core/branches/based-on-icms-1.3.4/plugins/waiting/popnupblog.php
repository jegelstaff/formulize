<?php

include_once ICMS_ROOT_PATH.'/modules/popnupblog/popnupblog.php';

function b_waiting_popnupblog() {
	$result = array();
	$result['adminlink'] = ICMS_URL.'/modules/popnupblog/admin/index.php';
	$result['pendingnum'] = popnupblog::getApplicationNum();
	$result['lang_linkname'] = _PI_WAITING_WAITINGS;
	return $result;

}
?>