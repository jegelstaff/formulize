<?php

include_once XOOPS_ROOT_PATH.'/modules/simpleblog/simpleblog.php';

function b_waiting_simpleblog(){
	$result = array();
	$result['adminlink'] = XOOPS_URL.'/modules/simpleblog/admin/index.php';
	$result['pendingnum'] = SimpleBlog::getApplicationNum();
	$result['lang_linkname'] = _PI_WAITING_BLOGS;
	return $result;

}
?>