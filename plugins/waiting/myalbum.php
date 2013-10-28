<?php
function b_waiting_myalbum_0( $mydirnumber = '') {
	$block = array();

	$result = icms::$xoopsDB->query("SELECT COUNT(*) FROM ".icms::$xoopsDB->prefix("myalbum{$mydirnumber}_photos")." WHERE status=0");
	if ($result) {
		$block['adminlink'] = ICMS_URL."/modules/myalbum{$mydirnumber}/admin/admission.php";
		list($block['pendingnum']) = icms::$xoopsDB->fetchRow($result);
		$block['lang_linkname'] = _PI_WAITING_WAITINGS . ( $mydirnumber === '' ? '' : "($mydirnumber)" ) ;
	}

	return $block;
}

for ($i = 0; $i < 3; $i++) {
	if (file_exists(ICMS_ROOT_PATH."/modules/myalbum{$i}/xoops_version.php")) {
		eval ('
		function b_waiting_myalbum_'.($i+1).'() {
			return b_waiting_myalbum_0('.$i.');
		}
		');
	}
}
?>