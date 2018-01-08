<?php
function smarty_function_xoInboxCount( $params, &$smarty ) {
	if (!isset(icms::$user) || !is_object(icms::$user)) {
		return;
	}
	$time = time();
	if (isset($_SESSION['xoops_inbox_count']) && @$_SESSION['xoops_inbox_count_expire'] > $time) {
		$count = (int) $_SESSION['xoops_inbox_count'] ;
	} else {
        $pm_handler = icms::handler('icms_data_privmessage');
        $criteria = new icms_db_criteria_Compo(new icms_db_criteria_Item('read_msg', 0));
        $criteria->add(new icms_db_criteria_Item('to_userid', icms::$user->getVar('uid')));
        $count = (int)$pm_handler->getCount($criteria) ;
        $_SESSION['xoops_inbox_count'] = $count;
        $_SESSION['xoops_inbox_count_expire'] = $time + 60;
	}
	if (!@empty($params['assign'])) {
		$smarty->assign($params['assign'], $count);
	} else {
		echo $count;
	}
}