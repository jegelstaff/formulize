<?php
function b_waiting_wordpress_0($wp_num="") {
	$block = array();

	// wordpress
	$result = icms::$xoopsDB->query("SELECT COUNT(*) FROM ".icms::$xoopsDB->prefix("wp".$wp_num."_comments")." WHERE comment_approved='0'");
	if ($result) {
		$block['adminlink'] = ICMS_URL."/modules/wordpress".$wp_num."/wp-admin/moderation.php" ;
		list($block['pendingnum']) = icms::$xoopsDB->fetchRow($result);
		$block['lang_linkname'] = sprintf(_PI_WAITING_WAITINGS_FMT,$wp_num) ;
	}
	return $block;
}

for ($i = 0; $i < 10; $i++) {
	if (file_exists(ICMS_ROOT_PATH."/modules/wordpress".$i."/xoops_version.php")) {
		eval ('
		function b_waiting_wordpress_'.($i+1).'() {
			return b_waiting_wordpress_0('.$i.');
		}
		');
	}
}
?>