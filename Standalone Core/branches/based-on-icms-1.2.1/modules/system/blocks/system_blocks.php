<?php
// $Id: system_blocks.php 1129 2007-10-24 09:45:47Z dugris $
/**
* Good ol' system blocks
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license		LICENSE.txt
* @package	Systemblocks
* @since		XOOPS
* @author		http://www.xoops.org The XOOPS Project
* @version		$Id$
*/

/**
* Shows which users and guests are online
*
* @return mixed $block or false if no users were online
*/
function b_system_online_show()
{
	global $icmsUser, $icmsModule;
	$online_handler =& xoops_gethandler('online');
	mt_srand((double)microtime()*1000000);
	// set gc probabillity to 10% for now..
	if (mt_rand(1, 100) < 11) {
		$online_handler->gc(300);
	}
	if (is_object($icmsUser)) {
		$uid = $icmsUser->getVar('uid');
		$uname = $icmsUser->getVar('uname');
	} else {
		$uid = 0;
		$uname = '';
	}
	if (is_object($icmsModule)) {
		$online_handler->write($uid, $uname, time(), $icmsModule->getVar('mid'), $_SERVER['REMOTE_ADDR']);
	} else {
		$online_handler->write($uid, $uname, time(), 0, $_SERVER['REMOTE_ADDR']);
	}
	$onlines = $online_handler->getAll();
	if (false != $onlines) {
		$total = count($onlines);
		$block = array();
		$guests = 0;
		$members = '';
		for ($i = 0; $i < $total; $i++) {
			if ($onlines[$i]['online_uid'] > 0) {
				$members .= ' <a href="'.XOOPS_URL.'/userinfo.php?uid='.$onlines[$i]['online_uid'].'" title="'.$onlines[$i]['online_uname'].'\'s '._PROFILE.'">'.$onlines[$i]['online_uname'].'</a>,';
			} else {
				$guests++;
			}
		}
		$block['online_total'] = sprintf(_ONLINEPHRASE, $total);
		if (is_object($icmsModule)) {
			$mytotal = $online_handler->getCount(new Criteria('online_module', $icmsModule->getVar('mid')));
			$block['online_total'] .= ' ('.sprintf(_ONLINEPHRASEX, $mytotal, $icmsModule->getVar('name')).')';
		}
		$block['lang_members'] = _MEMBERS;
		$block['lang_guests'] = _GUESTS;
		$block['online_names'] = $members;
		$block['online_members'] = $total - $guests;
		$block['online_guests'] = $guests;
		$block['lang_more'] = _MORE;
		return $block;
	} else {
		return false;
	}
}

/**
* Shows the login block
*
* @return mixed $block or false if no users were online
*/
function b_system_login_show()
{
	global $icmsUser, $icmsConfig;
	if (!$icmsUser) {
		$block = array();
		$block['lang_username'] = _USERNAME;
		$block['unamevalue'] = "";
		if (isset($_COOKIE[$icmsConfig['usercookie']])) {
			$block['unamevalue'] = $_COOKIE[$icmsConfig['usercookie']];
		}
		$block['lang_password'] = _PASSWORD;
		$block['lang_login'] = _LOGIN;
		$block['lang_lostpass'] = _MB_SYSTEM_LPASS;
		$block['lang_registernow'] = _MB_SYSTEM_RNOW;
		$block['lang_rememberme'] = _MB_SYSTEM_REMEMBERME;
		$block['lang_youoid'] = _MB_SYSTEM_OPENID_URL;
		$block['lang_login_oid'] = _MB_SYSTEM_OPENID_LOGIN;
		$block['lang_back2normoid'] = _MB_SYSTEM_OPENID_NORMAL_LOGIN;
		if ($icmsConfig['use_ssl'] == 1 && $icmsConfig['sslloginlink'] != '') {
			$block['sslloginlink'] = "<a href=\"javascript:openWithSelfMain('".$icmsConfig['sslloginlink']."', 'ssllogin', 300, 200);\">"._MB_SYSTEM_SECURE."</a>";
		}

		$config_handler =& xoops_gethandler('config');
		$icmsConfigUser =& $config_handler->getConfigsByCat(XOOPS_CONF_USER);

		if ($icmsConfigUser['allow_register'] == 1) {
			$block['registration'] = $icmsConfigUser['allow_register'];
		}

		if ($icmsConfigUser['remember_me'] == 1) {
			$block['rememberme'] = $icmsConfigUser['remember_me'];
		}

		$xoopsAuthConfig =& $config_handler->getConfigsByCat(XOOPS_CONF_AUTH);
		if ($xoopsAuthConfig['auth_openid']) {
			$block['auth_openid'] = true;
		}
		return $block;
	}
	return false;
}

/**
* Shows the main menu block
*
* @return array $block the main menu block array
*/
function b_system_main_show()
{
	$config_handler =& xoops_gethandler('config');
	$icmsConfigUser =& $config_handler->getConfigsByCat(XOOPS_CONF_USER);

	global $icmsUser,$icmsModule;
	$block = array();
	$block['lang_home'] = _MB_SYSTEM_HOME;
	if ($icmsConfigUser['priv_dpolicy'] == 1)
	{
		$block['priv_enabled'] = true;
		$block['lang_privpolicy'] = _MB_SYSTEM_PRIVPOLICY;
	}
	$block['lang_close'] = _CLOSE;
	$module_handler =& xoops_gethandler('module');
	$criteria = new CriteriaCompo(new Criteria('hasmain', 1));
	$criteria->add(new Criteria('isactive', 1));
	$criteria->add(new Criteria('weight', 0, '>'));
	$modules = $module_handler->getObjects($criteria, true);
	$moduleperm_handler =& xoops_gethandler('groupperm');
	$groups = is_object($icmsUser) ? $icmsUser->getGroups() : XOOPS_GROUP_ANONYMOUS;
	$read_allowed = $moduleperm_handler->getItemIds('module_read', $groups);
	foreach (array_keys($modules) as $i) {
		if (in_array($i, $read_allowed)) {
			$block['modules'][$i]['name'] = $modules[$i]->getVar('name');
			$block['modules'][$i]['directory'] = $modules[$i]->getVar('dirname');
			$sublinks = $modules[$i]->subLink();
			if ((count($sublinks) > 0) && (!empty($icmsModule)) && ($i == $icmsModule->getVar('mid'))) {
				foreach($sublinks as $sublink){
					$block['modules'][$i]['sublinks'][] = array('name' => $sublink['name'], 'url' => XOOPS_URL.'/modules/'.$modules[$i]->getVar('dirname').'/'.$sublink['url']);
				}
			} else {
				$block['modules'][$i]['sublinks'] = array();
			}
		}
	}
	return $block;
}

/**
* Shows the search block
*
* @return array $block The search block
*/
function b_system_search_show()
{
	$block = array();
	$block['lang_search'] = _MB_SYSTEM_SEARCH;
	$block['lang_advsearch'] = _MB_SYSTEM_ADVS;
	return $block;
}

/**
* Shows the user menu block
*
* @return mixed $block or false if the user is a guest
*/
function b_system_user_show()
{
	global $icmsUser;
	if (is_object($icmsUser)) {
		$pm_handler =& xoops_gethandler('privmessage');
		$block = array();
		$block['lang_youraccount'] = _MB_SYSTEM_VACNT;
		$block['lang_editaccount'] = _MB_SYSTEM_EACNT;
		$block['lang_notifications'] = _MB_SYSTEM_NOTIF;
		$block['uid'] = $icmsUser->getVar('uid');
		$block['lang_logout'] = _MB_SYSTEM_LOUT;
		$criteria = new CriteriaCompo(new Criteria('read_msg', 0));
		$criteria->add(new Criteria('to_userid', $icmsUser->getVar('uid')));
		$block['new_messages'] = $pm_handler->getCount($criteria);
		$block['lang_inbox'] = _MB_SYSTEM_INBOX;
		$block['lang_adminmenu'] = _MB_SYSTEM_ADMENU;
		return $block;
	}
	return false;
}

/**
* Shows information about the user
*
* @param array $options The block options
* @return array $block the block array
*/
function b_system_info_show($options)
{
	global $icmsConfig, $icmsUser;
	$xoopsDB =& Database::getInstance();
	$myts =& MyTextSanitizer::getInstance();
	$block = array();
	if (!empty($options[3])) {
		$block['showgroups'] = true;
		$result = $xoopsDB->query("SELECT u.uid, u.uname, u.email, u.user_viewemail, u.user_avatar, g.name AS groupname FROM ".$xoopsDB->prefix("groups_users_link")." l LEFT JOIN ".$xoopsDB->prefix("users")." u ON l.uid=u.uid LEFT JOIN ".$xoopsDB->prefix("groups")." g ON l.groupid=g.groupid WHERE g.group_type='Admin' ORDER BY l.groupid, u.uid");
		if ($xoopsDB->getRowsNum($result) > 0) {
			$prev_caption = "";
			$i = 0;
			while  ($userinfo = $xoopsDB->fetchArray($result)) {
				if ($prev_caption != $userinfo['groupname']) {
					$prev_caption = $userinfo['groupname'];
					$block['groups'][$i]['name'] = $myts->htmlSpecialChars($userinfo['groupname']);
				}
				if (isset($icmsUser) && is_object($icmsUser)) {
					$block['groups'][$i]['users'][] = array('id' => $userinfo['uid'], 'name' => $myts->htmlspecialchars($userinfo['uname']), 'msglink' => "<a href=\"javascript:openWithSelfMain('".XOOPS_URL."/pmlite.php?send2=1&amp;to_userid=".$userinfo['uid']."','pmlite',800,680);\"><img src=\"".XOOPS_URL."/images/icons/".$GLOBALS["icmsConfig"]["language"]."/pm_small.gif\" width=\"27px\" height=\"17px\" alt=\"\" /></a>", 'avatar' => XOOPS_UPLOAD_URL.'/'.$userinfo['user_avatar']);
				} else {
					if ($userinfo['user_viewemail']) {
						$block['groups'][$i]['users'][] = array('id' => $userinfo['uid'], 'name' => $myts->htmlspecialchars($userinfo['uname']), 'msglink' => '<a href="mailto:'.$userinfo['email'].'"><img src="'.XOOPS_URL.'/images/icons/'.$GLOBALS["icmsConfig"]["language"].'/em_small.gif" width="16px" height="14px" alt="" /></a>', 'avatar' => XOOPS_UPLOAD_URL.'/'.$userinfo['user_avatar']);
					} else {
						$block['groups'][$i]['users'][] = array('id' => $userinfo['uid'], 'name' => $myts->htmlspecialchars($userinfo['uname']), 'msglink' => '&nbsp;', 'avatar' => XOOPS_UPLOAD_URL.'/'.$userinfo['user_avatar']);
					}
				}
				$i++;
			}
		}
	} else {
		$block['showgroups'] = false;
	}
	$block['logourl'] = XOOPS_URL.'/images/'.$options[2];
	$block['recommendlink'] = "<a href=\"javascript:openWithSelfMain('".XOOPS_URL."/misc.php?action=showpopups&amp;type=friend&amp;op=sendform&amp;t=".time()."','friend',".$options[0].",".$options[1].")\">"._MB_SYSTEM_RECO."</a>";
	return $block;
}

/**
* Shows the latest members that were added
*
* @param array $options The block options
* @return array $block The newest members block array
*/
function b_system_newmembers_show($options)
{
	global $icmsConfigUser;

	$block = array();
	$criteria = new CriteriaCompo(new Criteria('level', 0, '>'));
	$limit = (!empty($options[0])) ? $options[0] : 10;
	$criteria->setOrder('DESC');
	$criteria->setSort('user_regdate');
	$criteria->setLimit($limit);
	$member_handler =& xoops_gethandler('member');
	$newmembers = $member_handler->getUsers($criteria);
	$count = count($newmembers);
	for ($i = 0; $i < $count; $i++) {
		if ( $options[1] == 1 ) {
			if ($newmembers[$i]->getVar('user_avatar') && $newmembers[$i]->getVar('user_avatar') != 'blank.gif' && $newmembers[$i]->getVar('user_avatar') != ''){
				$block['users'][$i]['avatar'] = ICMS_UPLOAD_URL.'/'.$newmembers[$i]->getVar('user_avatar');
			} elseif ($icmsConfigUser['avatar_allow_gravatar'] == 1) {
				$block['users'][$i]['avatar'] = $newmembers[$i]->gravatar('G', $icmsConfigUser['avatar_width']);
			} else {
				$block['users'][$i]['avatar'] = '';
			}
		} else {
			$block['users'][$i]['avatar'] = '';
		}
		$block['users'][$i]['id'] = $newmembers[$i]->getVar('uid');
		$block['users'][$i]['name'] = $newmembers[$i]->getVar('uname');
		$block['users'][$i]['joindate'] = formatTimestamp($newmembers[$i]->getVar('user_regdate'), 's');
		$block['users'][$i]['login_name'] = $newmembers[$i]->getVar('login_name');
	}
		if ( !empty($options[2]) && $options[2] == 1 ) {
			$block['index_enabled'] = true;
			$block['registered'] = icms_conv_nr2local($member_handler->getUserCount(new Criteria('level')));
			$block['inactive'] = icms_conv_nr2local($member_handler->getUserCount(new Criteria('level', 0)));
			$block['active'] = icms_conv_nr2local($member_handler->getUserCount(new Criteria('level', 0, '>')));
			$block['lang_totalusers'] = _MB_SYSTEM_TOTAL_USERS;
			$block['lang_activeusers'] = _MB_SYSTEM_ACT_USERS;
			$block['lang_inactiveusers'] = _MB_SYSTEM_INACT_USERS;
		}
	return $block;
}

/**
* Shows the top posters block
*
* @param array $options The block options
* @return mixed $block or false if no users were online
*/
function b_system_topposters_show($options)
{
	global $icmsConfigUser;

	$block = array();
	$criteria = new CriteriaCompo(new Criteria('level', 0, '>'));
	$limit = (!empty($options[0])) ? $options[0] : 10;
	$size = count($options);
	for ( $i = 2; $i < $size; $i++) {
		$criteria->add(new Criteria('rank', $options[$i], '<>'));
	}
	$criteria->setOrder('DESC');
	$criteria->setSort('posts');
	$criteria->setLimit($limit);
	$member_handler =& xoops_gethandler('member');
	$topposters =& $member_handler->getUsers($criteria);
	$count = count($topposters);
	for ($i = 0; $i < $count; $i++) {
		if ( $options[1] == 1 ) {
			if ($topposters[$i]->getVar('user_avatar') && $topposters[$i]->getVar('user_avatar') != 'blank.gif' && $topposters[$i]->getVar('user_avatar') != ''){
				$block['users'][$i]['avatar'] = ICMS_UPLOAD_URL.'/'.$topposters[$i]->getVar('user_avatar');
			} elseif ($icmsConfigUser['avatar_allow_gravatar'] == 1) {
				$block['users'][$i]['avatar'] = $topposters[$i]->gravatar('G', $icmsConfigUser['avatar_width']);
			} else {
				$block['users'][$i]['avatar'] = '';
			}
		} else {
			$block['users'][$i]['avatar'] = '';
		}
		$block['users'][$i]['id'] = $topposters[$i]->getVar('uid');
		$block['users'][$i]['name'] = $topposters[$i]->getVar('uname');
		$block['users'][$i]['posts'] = $topposters[$i]->getVar('posts');
	}
	return $block;
}

/**
* Shows The latest comments
*
* @param array $options The block options
* @return array $block the block array
*/
function b_system_comments_show($options)
{
	$block = array();
	include_once XOOPS_ROOT_PATH.'/include/comment_constants.php';
	$comment_handler =& xoops_gethandler('comment');
	$criteria = new CriteriaCompo(new Criteria('com_status', XOOPS_COMMENT_ACTIVE));
	$criteria->setLimit(intval($options[0]));
	$criteria->setSort('com_created');
	$criteria->setOrder('DESC');

	// Check modules permissions
	global $icmsUser;
	$moduleperm_handler =& xoops_gethandler('groupperm');
	$gperm_groupid = is_object($icmsUser) ? $icmsUser->getGroups() : array(XOOPS_GROUP_ANONYMOUS);
	$criteria1 = new CriteriaCompo(new Criteria('gperm_name','module_read','='));
	$criteria1->add(new Criteria('gperm_groupid', '('.implode(',', $gperm_groupid).')', 'IN'));
	$perms = $moduleperm_handler->getObjects($criteria1, true);
	$modIds = array();
	foreach($perms as $item) {
		$modIds[] = $item->getVar('gperm_itemid');
	}
	if(count($modIds) > 0 ) {
		$modIds = array_unique($modIds);
		$criteria->add(new Criteria('com_modid', '('.implode(',', $modIds).')', 'IN'));
	}
	// Check modules permissions

	$comments = $comment_handler->getObjects($criteria, true);
	$member_handler =& xoops_gethandler('member');
	$module_handler =& xoops_gethandler('module');
	$modules = $module_handler->getObjects(new Criteria('hascomments', 1), true);
	$comment_config = array();
	foreach (array_keys($comments) as $i) {
		$mid = $comments[$i]->getVar('com_modid');
		$com['module'] = '<a href="'.XOOPS_URL.'/modules/'.$modules[$mid]->getVar('dirname').'/">'.$modules[$mid]->getVar('name').'</a>';
		if (!isset($comment_config[$mid])) {
			$comment_config[$mid] = $modules[$mid]->getInfo('comments');
		}
		$com['id'] = $i;
		$com['title'] = '<a href="'.XOOPS_URL.'/modules/'.$modules[$mid]->getVar('dirname').'/'.$comment_config[$mid]['pageName'].'?'.$comment_config[$mid]['itemName'].'='.$comments[$i]->getVar('com_itemid').'&amp;com_id='.$i.'&amp;com_rootid='.$comments[$i]->getVar('com_rootid').'&amp;'.htmlspecialchars($comments[$i]->getVar('com_exparams')).'#comment'.$i.'">'.$comments[$i]->getVar('com_title').'</a>';
		$com['icon'] = htmlspecialchars( $comments[$i]->getVar('com_icon'), ENT_QUOTES );
		$com['icon'] = ($com['icon'] != '') ? $com['icon'] : 'icon1.gif';
		$com['time'] = formatTimestamp($comments[$i]->getVar('com_created'),'m');
		if ($comments[$i]->getVar('com_uid') > 0) {
			$poster =& $member_handler->getUser($comments[$i]->getVar('com_uid'));
			if (is_object($poster)) {
				$com['poster'] = '<a href="'.XOOPS_URL.'/userinfo.php?uid='.$comments[$i]->getVar('com_uid').'">'.$poster->getVar('uname').'</a>';
			} else {
				$com['poster'] = $GLOBALS['icmsConfig']['anonymous'];
			}
		} else {
			$com['poster'] = $GLOBALS['icmsConfig']['anonymous'];
		}
		$block['comments'][] =& $com;
		unset($com);
	}
	return $block;
}

// RMV-NOTIFY
/**
* Shows The latest notifications
*
* @param array $options The block options
* @return array $block the block array
*/
function b_system_notification_show()
{
	global $icmsConfig, $icmsUser, $icmsModule;
	include_once XOOPS_ROOT_PATH . '/include/notification_functions.php';
	icms_loadLanguageFile('core', 'notification');
	// Notification must be enabled, and user must be logged in
	if (empty($icmsUser) || !notificationEnabled('block')) {
		return false; // do not display block
	}
	$notification_handler =& xoops_gethandler('notification');
	// Now build the a nested associative array of info to pass
	// to the block template.
	$block = array();
	$categories =& notificationSubscribableCategoryInfo();
	if (empty($categories)) {
		return false;
	}
	foreach ($categories as $category) {
		$section['name'] = $category['name'];
		$section['title'] = $category['title'];
		$section['description'] = $category['description'];
		$section['itemid'] = $category['item_id'];
		$section['events'] = array();
		$subscribed_events = $notification_handler->getSubscribedEvents ($category['name'], $category['item_id'], $icmsModule->getVar('mid'), $icmsUser->getVar('uid'));
		foreach (notificationEvents($category['name'], true) as $event) {
			if (!empty($event['admin_only']) && !$icmsUser->isAdmin($icmsModule->getVar('mid'))) {
				continue;
			}
			$subscribed = in_array($event['name'], $subscribed_events) ? 1 : 0;
			$section['events'][$event['name']] = array ('name'=>$event['name'], 'title'=>$event['title'], 'caption'=>$event['caption'], 'description'=>$event['description'], 'subscribed'=>$subscribed);
		}
		$block['categories'][$category['name']] = $section;
	}
	// Additional form data
	$block['target_page'] = "notification_update.php";
	// FIXME: better or more standardized way to do this?
	$script_url = explode('/', $_SERVER['PHP_SELF']);
	$script_name = $script_url[count($script_url)-1];
	$block['redirect_script'] = $script_name;
	$block['submit_button'] = _NOT_UPDATENOW;
	$block['notification_token'] = $GLOBALS['xoopsSecurity']->createToken();
	return $block;
}

/**
* Shows The multilanguage (flags) block
*
* @return array $block the block array
*/
function b_system_multilanguage_show()
{
	$block = array();
	$block['ml_tag'] = '[mlimg]';

	return $block;
}

/**
* Shows the form to edit the comments
*
* @param array $options The block options
* @return string $form The edit comments form HTML string
*/
function b_system_comments_edit($options)
{
	$inputtag = "<input type='text' name='options[]' value='".intval($options[0])."' />";
	$form = sprintf(_MB_SYSTEM_DISPLAYC, $inputtag);
	return $form;
}

/**
* Shows the form to edit the top posters
*
* @param array $options The block options
* @return string $form The edit top posters form HTML string
*/
function b_system_topposters_edit($options)
{
	include_once XOOPS_ROOT_PATH.'/class/xoopslists.php';
	$inputtag = "<input type='text' name='options[]' value='".intval($options[0])."' />";
	$form = sprintf(_MB_SYSTEM_DISPLAY,$inputtag);
	$form .= "<br />"._MB_SYSTEM_DISPLAYA."&nbsp;<input type='radio' id='options[]' name='options[]' value='1'";
	if ( $options[1] == 1 ) {
		$form .= " checked='checked'";
	}
	$form .= " />&nbsp;"._YES."<input type='radio' id='options[]' name='options[]' value='0'";
	if ( $options[1] == 0 ) {
		$form .= " checked='checked'";
	}
	$form .= " />&nbsp;"._NO."";
	$form .= "<br />"._MB_SYSTEM_NODISPGR."<br /><select id='options[]' name='options[]' multiple='multiple'>";
	$ranks =& XoopsLists::getUserRankList();
	$size = count($options);
	foreach ($ranks as $k => $v) {
		$sel = "";
		for ( $i = 2; $i < $size; $i++ ) {
			if ($k == $options[$i]) {
				$sel = " selected='selected'";
			}
		}
		$form .= "<option value='$k'$sel>$v</option>";
	}
	$form .= "</select>";
	return $form;
}

/**
* Shows the form to edit the newest members
*
* @param array $options The block options
* @return string $form The edit newest members form HTML string
*/
function b_system_newmembers_edit($options)
{
	$inputtag = "<input type='text' name='options[0]' value='".$options[0]."' />";
	$form = sprintf(_MB_SYSTEM_DISPLAY,$inputtag);
	$form .= "<br />"._MB_SYSTEM_DISPLAYA."&nbsp;<input type='radio' id='options[1]' name='options[1]' value='1'";
	if ( $options[1] == 1 ) {
		$form .= " checked='checked'";
	}
	$form .= " />&nbsp;"._YES."<input type='radio' id='options[1]' name='options[1]' value='0'";
	if ( $options[1] == 0 ) {
		$form .= " checked='checked'";
	}
	$form .= " />&nbsp;"._NO."";
	$form .= "<br />"._MB_SYSTEM_DISPLAYTOT."&nbsp;<input type='radio' id='options[2]' name='options[2]' value='1'";
	if ( $options[2] == 1 ) {
		$form .= " checked='checked'";
	}
	$form .= " />&nbsp;"._YES."<input type='radio' id='options[2]' name='options[2]' value='0'";
	if ( $options[2] == 0 ) {
		$form .= " checked='checked'";
	}
	$form .= " />&nbsp;"._NO."";
	return $form;
}

/**
* Shows the form to edit the sysem info
*
* @param array $options The block options
* @return string $form The edit system info form HTML string
*/
function b_system_info_edit($options)
{
	$form = _MB_SYSTEM_PWWIDTH."&nbsp;";
	$form .= "<input type='text' name='options[]' value='".$options[0]."' />";
	$form .= "<br />"._MB_SYSTEM_PWHEIGHT."&nbsp;";
	$form .= "<input type='text' name='options[]' value='".$options[1]."' />";
	$form .= "<br />".sprintf(_MB_SYSTEM_LOGO,XOOPS_URL."/images/")."&nbsp;";
	$form .= "<input type='text' name='options[]' value='".$options[2]."' />";
	$chk = "";
	$form .= "<br />"._MB_SYSTEM_SADMIN."&nbsp;";
	if ( $options[3] == 1 ) {
		$chk = " checked='checked'";
	}
	$form .= "<input type='radio' name='options[3]' value='1'".$chk." />&nbsp;"._YES."";
	$chk = "";
	if ( $options[3] == 0 ) {
		$chk = " checked=\"checked\"";
	}
	$form .= "&nbsp;<input type='radio' name='options[3]' value='0'".$chk." />"._NO."";
	return $form;
}

/**
* Shows the activated themes
*
* @param array $options The block options
* @return array $block The themes block array
*/
function b_system_themes_show($options)
{
	global $icmsConfig;
	$theme_options = '';
	foreach ($icmsConfig['theme_set_allowed'] as $theme) {
		$theme_options .= '<option value="'.$theme.'"';
		if ($theme == $icmsConfig['theme_set']) {
			$theme_options .= ' selected="selected"';
		}
		$theme_options .= '>'.$theme.'</option>';
	}
	$block = array();
	if ($options[0] == 1) {
		$block['theme_select'] = "<img vspace=\"2\" id=\"xoops_theme_img\" src=\"".XOOPS_THEME_URL."/".$icmsConfig['theme_set']."/shot.gif\" alt=\"screenshot\" width=\"".intval($options[1])."\" /><br /><select id=\"theme_select\" name=\"theme_select\" onchange=\"showImgSelected('xoops_theme_img', 'theme_select', 'themes', '/shot.gif', '".XOOPS_URL."');\">".$theme_options."</select><input type=\"submit\" value=\""._GO."\" />";
	} else {
		$block['theme_select'] = '<select name="theme_select" onchange="submit();" size="3">'.$theme_options.'</select>';
	}

	$block['theme_select'] .= '<p>('.sprintf(_MB_SYSTEM_NUMTHEME, count($icmsConfig['theme_set_allowed']).'').')</p>';
	return $block;
}

/**
* Shows the form to edit the themes
*
* @param array $options The block options
* @return string $form The edit themes form HTML string
*/
function b_system_themes_edit($options)
{

	$chk = "";
	$form = _MB_SYSTEM_THSHOW."&nbsp;";
	if ( $options[0] == 1 ) {
		$chk = " checked='checked'";
	}
	$form .= "<input type='radio' name='options[0]' value='1'".$chk." />&nbsp;"._YES;
	$chk = "";
	if ( $options[0] == 0 ) {
		$chk = ' checked="checked"';
	}
	$form .= '&nbsp;<input type="radio" name="options[0]" value="0"'.$chk.' />'._NO;
	$form .= '<br />'._MB_SYSTEM_THWIDTH.'&nbsp;';
	$form .= "<input type='text' name='options[1]' value='".$options[1]."' />";
	return $form;
}
/**
 * Gathers and displays the current user's bookmarks
 * @since 1.2
 * @return array Array of bookmark links for the current user
 */
function b_system_bookmarks_show()
{
	global $icmsConfig, $icmsUser;
	$block = array();
	icms_loadLanguageFile('core', 'notification');
	// User must be logged in
	if (empty($icmsUser)) {
		return false; // do not display block
	}
	// Get an array of all notifications for the selected user

	$notification_handler =& xoops_gethandler('notification');
	$notifications =& $notification_handler->getByUser($icmsUser->getVar('uid'));

	// Generate the info for the template

	$module_handler =& xoops_gethandler('module');

	$prev_modid = -1;

	$prev_item = -1;
	foreach ($notifications as $n) {
		$modid = $n->getVar('not_modid');
		if ($modid != $prev_modid) {
			$prev_modid = $modid;

			$prev_item = -1;
			$module =& $module_handler->get($modid);
			$module_name = $module->getVar('name');
			// Get the lookup function, if exists
			$not_config = $module->getInfo('notification');
			$lookup_func = '';
			if (!empty($not_config['lookup_file'])) {
				$lookup_file = ICMS_ROOT_PATH . '/modules/' . $module->getVar('dirname') . '/' . $not_config['lookup_file'];
				if (file_exists($lookup_file)) {
					include_once $lookup_file;
					if (!empty($not_config['lookup_func']) && function_exists($not_config['lookup_func'])) {
						$lookup_func = $not_config['lookup_func'];
					}
				}
			}
		}

		$category = $n->getVar('not_category');
		$item = $n->getVar('not_itemid');
		if ($item != $prev_item) {
			$prev_item = $item;
			if (!empty($lookup_func)) {
				$item_info = $lookup_func($category, $item);
			} else {
				$item_info = array ('name'=>'['._NOT_NAMENOTAVAILABLE.']', 'url'=>'');
			}
		}

		if ($n->getVar('not_event') == 'bookmark') {
			$block[$module_name][] = array ('name'=>$item_info['name'], 'url'=>$item_info['url']);
			}
	}

	return $block;
}

/**
 * @param array $options block config options
 */
function b_system_social_show($options) {
	$block = array();
	$block['provider'] = array();
	
	$i = 0;
	if ($options[$i]) $block['provider'][$i] = array(
		'title' => _MB_SYSTEM_SOCIAL_PROVIDER_BOOKMARK.constant('_MB_SYSTEM_SOCIAL_PROVIDER_'.$i),
		'link'  => "'http://twitter.com/home?status='+encodeURIComponent(location.href)+'&amp;description=&amp;tags=&amp;title='+encodeURIComponent(document.title)+'&amp;popup=yes'",
		'image' => "twitter.gif"
	);
	$i++;
	if ($options[$i]) $block['provider'][$i] = array(
		'title' => _MB_SYSTEM_SOCIAL_PROVIDER_BOOKMARK.constant('_MB_SYSTEM_SOCIAL_PROVIDER_'.$i),
		'link'  => "'http://www.facebook.com/sharer.php?u='+encodeURIComponent(location.href)+'&amp;description=&amp;tags=&amp;title='+encodeURIComponent(document.title)+'&amp;popup=yes'",
		'image' => "facebook.gif"
	);
	$i++;
	if ($options[$i]) $block['provider'][$i] = array(
		'title' => _MB_SYSTEM_SOCIAL_PROVIDER_BOOKMARK.constant('_MB_SYSTEM_SOCIAL_PROVIDER_'.$i),
		'link'  => "'http://www.myspace.com/Modules/PostTo/Pages/?t='+encodeURIComponent(document.title)+'&amp;c='+encodeURIComponent(document.title)+'&amp;u='+encodeURIComponent(location.href)+'&amp;popup=yes'",
		'image' => "myspace.gif"
	);
	$i++;
	if ($options[$i]) $block['provider'][$i] = array(
		'title' => _MB_SYSTEM_SOCIAL_PROVIDER_BOOKMARK.constant('_MB_SYSTEM_SOCIAL_PROVIDER_'.$i),
		'link'  => "'http://del.icio.us/post?v=2&amp;url='+encodeURIComponent(location.href)+'&amp;notes=&amp;tags=&amp;title='+encodeURIComponent(document.title)",
		'image' => "del.gif"
	);
	$i++;
	if ($options[$i]) $block['provider'][$i] = array(
		'title' => _MB_SYSTEM_SOCIAL_PROVIDER_BOOKMARK.constant('_MB_SYSTEM_SOCIAL_PROVIDER_'.$i),
		'link'  => "'http://myjeeves.ask.com/mysearch/BookmarkIt?v=1.2&amp;t=webpages&amp;url='+encodeURIComponent(location.href)+'&amp;description=&amp;tags=&amp;title='+encodeURIComponent(document.title)+'&amp;popup=yes'",
		'image' => "ask.gif"
	);
	$i++;
	if ($options[$i]) $block['provider'][$i] = array(
		'title' => _MB_SYSTEM_SOCIAL_PROVIDER_BOOKMARK.constant('_MB_SYSTEM_SOCIAL_PROVIDER_'.$i),
		'link'  => "'http://www.mister-wong.de/index.php?action=addurl&amp;bm_url='+encodeURIComponent(location.href)+'&amp;bm_notice=&amp;bm_description='+encodeURIComponent(document.title)+'&amp;bm_tags='",
		'image' => "wong.gif"
	);
	$i++;
	if ($options[$i]) $block['provider'][$i] = array(
		'title' => _MB_SYSTEM_SOCIAL_PROVIDER_BOOKMARK.constant('_MB_SYSTEM_SOCIAL_PROVIDER_'.$i),
		'link'  => "'http://www.webnews.de/einstellen?url='+encodeURIComponent(document.location)+'&amp;title='+encodeURIComponent(document.title)",
		'image' => "webnews.gif"
	);
	$i++;
	if ($options[$i]) $block['provider'][$i] = array(
		'title' => _MB_SYSTEM_SOCIAL_PROVIDER_BOOKMARK.constant('_MB_SYSTEM_SOCIAL_PROVIDER_'.$i),
		'link'  => "'http://www.icio.de/add.php?url='+encodeURIComponent(location.href)",
		'image' => "icio.gif"
	);
	$i++;
	if ($options[$i]) $block['provider'][$i] = array(
		'title' => _MB_SYSTEM_SOCIAL_PROVIDER_BOOKMARK.constant('_MB_SYSTEM_SOCIAL_PROVIDER_'.$i),
		'link'  => "'http://beta.oneview.de/quickadd/neu/addBookmark.jsf?URL='+encodeURIComponent(location.href)+'&amp;title='+encodeURIComponent(document.title)",
		'image' => "oneview.gif"
	);
	$i++;
	if ($options[$i]) $block['provider'][$i] = array(
		'title' => _MB_SYSTEM_SOCIAL_PROVIDER_BOOKMARK.constant('_MB_SYSTEM_SOCIAL_PROVIDER_'.$i),
		'link'  => "'http://www.newsider.de/submit.php?url='+encodeURIComponent(location.href)+'&amp;title='+encodeURIComponent(document.title)",
		'image' => "newsider.gif"
	);
	$i++;
	if ($options[$i]) $block['provider'][$i] = array(
		'title' => _MB_SYSTEM_SOCIAL_PROVIDER_BOOKMARK.constant('_MB_SYSTEM_SOCIAL_PROVIDER_'.$i),
		'link'  => "'http://www.folkd.com/submit/'+encodeURIComponent(location.href)+'&amp;title='+encodeURIComponent(document.title)",
		'image' => "folkd.gif"
	);
	$i++;
	if ($options[$i]) $block['provider'][$i] = array(
		'title' => _MB_SYSTEM_SOCIAL_PROVIDER_BOOKMARK.constant('_MB_SYSTEM_SOCIAL_PROVIDER_'.$i),
		'link'  => "'http://yigg.de/neu?exturl='+encodeURIComponent(location.href)",
		'image' => "yigg.gif"
	);
	$i++;
	if ($options[$i]) $block['provider'][$i] = array(
		'title' => _MB_SYSTEM_SOCIAL_PROVIDER_BOOKMARK.constant('_MB_SYSTEM_SOCIAL_PROVIDER_'.$i),
		'link'  => "'http://linkarena.com/bookmarks/addlink/?url='+encodeURIComponent(location.href)+'&amp;title='+encodeURIComponent(document.title)+'&amp;desc=&amp;tags='",
		'image' => "linkarena.gif"
	);
	$i++;
	if ($options[$i]) $block['provider'][$i] = array(
		'title' => _MB_SYSTEM_SOCIAL_PROVIDER_BOOKMARK.constant('_MB_SYSTEM_SOCIAL_PROVIDER_'.$i),
		'link'  => "'http://digg.com/submit?phase=2&amp;url='+encodeURIComponent(location.href)+'&amp;bodytext=&amp;tags=&amp;title='+encodeURIComponent(document.title)",
		'image' => "digg.gif"
	);
	$i++;
	if ($options[$i]) $block['provider'][$i] = array(
		'title' => _MB_SYSTEM_SOCIAL_PROVIDER_BOOKMARK.constant('_MB_SYSTEM_SOCIAL_PROVIDER_'.$i),
		'link'  => "'http://reddit.com/submit?url='+encodeURIComponent(location.href)+'&amp;title='+encodeURIComponent(document.title)",
		'image' => "reddit.gif"
	);
	$i++;
	if ($options[$i]) $block['provider'][$i] = array(
		'title' => _MB_SYSTEM_SOCIAL_PROVIDER_BOOKMARK.constant('_MB_SYSTEM_SOCIAL_PROVIDER_'.$i),
		'link'  => "'http://www.simpy.com/simpy/LinkAdd.do?title='+encodeURIComponent(document.title)+'&amp;tags=&amp;note=&amp;href='+encodeURIComponent(location.href)",
		'image' => "simpy.gif"
	);
	$i++;
	if ($options[$i]) $block['provider'][$i] = array(
		'title' => _MB_SYSTEM_SOCIAL_PROVIDER_BOOKMARK.constant('_MB_SYSTEM_SOCIAL_PROVIDER_'.$i),
		'link'  => "'http://www.stumbleupon.com/submit?url='+encodeURIComponent(location.href)+'&amp;title='+encodeURIComponent(document.title)",
		'image' => "stumbleupon.gif"
	);
	$i++;
	if ($options[$i]) $block['provider'][$i] = array(
		'title' => _MB_SYSTEM_SOCIAL_PROVIDER_BOOKMARK.constant('_MB_SYSTEM_SOCIAL_PROVIDER_'.$i),
		'link'  => "'http://slashdot.org/bookmark.pl?url='+encodeURIComponent(location.href)+'&amp;title='+encodeURIComponent(document.title)",
		'image' => "slashdot.gif"
	);
	$i++;
	if ($options[$i]) $block['provider'][$i] = array(
		'title' => _MB_SYSTEM_SOCIAL_PROVIDER_BOOKMARK.constant('_MB_SYSTEM_SOCIAL_PROVIDER_'.$i),
		'link'  => "'http://myweb2.search.yahoo.com/myresults/bookmarklet?t='+encodeURIComponent(document.title)+'&amp;d=&amp;tag=&amp;u='+encodeURIComponent(location.href)",
		'image' => "yahoo.gif"
	);
	$i++;
	if ($options[$i]) $block['provider'][$i] = array(
		'title' => _MB_SYSTEM_SOCIAL_PROVIDER_BOOKMARK.constant('_MB_SYSTEM_SOCIAL_PROVIDER_'.$i),
		'link'  => "'http://www.spurl.net/spurl.php?v=3&amp;tags=&amp;title='+encodeURIComponent(document.title)+'&amp;url='+encodeURIComponent(document.location.href)",
		'image' => "spurl.gif"
	);
	$i++;
	if ($options[$i]) $block['provider'][$i] = array(
		'title' => _MB_SYSTEM_SOCIAL_PROVIDER_BOOKMARK.constant('_MB_SYSTEM_SOCIAL_PROVIDER_'.$i),
		'link'  => "'http://www.google.com/bookmarks/mark?op=add&amp;bkmk='+encodeURIComponent(location.href)+'&amp;annotation=&amp;labels=&amp;title='+encodeURIComponent(document.title)",
		'image' => "google.gif"
	);
	$i++;
	if ($options[$i]) $block['provider'][$i] = array(
		'title' => _MB_SYSTEM_SOCIAL_PROVIDER_BOOKMARK.constant('_MB_SYSTEM_SOCIAL_PROVIDER_'.$i),
		'link'  => "'http://www.blinklist.com/index.php?Action=Blink/addblink.php&amp;Description=&amp;Tag=&amp;Url='+encodeURIComponent(location.href)+'&amp;Title='+encodeURIComponent(document.title)",
		'image' => "blinklist.gif"
	);
	$i++;
	if ($options[$i]) $block['provider'][$i] = array(
		'title' => _MB_SYSTEM_SOCIAL_PROVIDER_BOOKMARK.constant('_MB_SYSTEM_SOCIAL_PROVIDER_'.$i),
		'link'  => "'http://blogmarks.net/my/new.php?mini=1&amp;simple=1&amp;url='+encodeURIComponent(location.href)+'&amp;content=&amp;public-tags=&amp;title='+encodeURIComponent(document.title)",
		'image' => "blogmarks.gif"
	);
	$i++;
	if ($options[$i]) $block['provider'][$i] = array(
		'title' => _MB_SYSTEM_SOCIAL_PROVIDER_BOOKMARK.constant('_MB_SYSTEM_SOCIAL_PROVIDER_'.$i),
		'link'  => "'http://www.diigo.com/post?url='+encodeURIComponent(location.href)+'&amp;title='+encodeURIComponent(document.title)+'&amp;tag=&amp;comments='",
		'image' => "diigo.gif"
	);
	$i++;
	if ($options[$i]) $block['provider'][$i] = array(
		'title' => _MB_SYSTEM_SOCIAL_PROVIDER_BOOKMARK.constant('_MB_SYSTEM_SOCIAL_PROVIDER_'.$i),
		'link'  => "'http://technorati.com/faves?add='+encodeURIComponent(location.href)+'&amp;tag='",
		'image' => "technorati.gif"
	);
	$i++;
	if ($options[$i]) $block['provider'][$i] = array(
		'title' => _MB_SYSTEM_SOCIAL_PROVIDER_BOOKMARK.constant('_MB_SYSTEM_SOCIAL_PROVIDER_'.$i),
		'link'  => "'http://www.newsvine.com/_wine/save?popoff=1&amp;u='+encodeURIComponent(location.href)+'&amp;tags=&amp;blurb='+encodeURIComponent(document.title)",
		'image' => "newsvine.gif"
	);
	$i++;
	if ($options[$i]) $block['provider'][$i] = array(
		'title' => _MB_SYSTEM_SOCIAL_PROVIDER_BOOKMARK.constant('_MB_SYSTEM_SOCIAL_PROVIDER_'.$i),
		'link'  => "'http://www.blinkbits.com/bookmarklets/save.php?v=1&amp;title='+encodeURIComponent(document.title)+'&amp;source_url='+encodeURIComponent(location.href)+'&amp;source_image_url=&amp;rss_feed_url=&amp;rss_feed_url=&amp;rss2member=&amp;body='",
		'image' => "blinkbits.gif"
	);
	$i++;
	if ($options[$i]) $block['provider'][$i] = array(
		'title' => _MB_SYSTEM_SOCIAL_PROVIDER_BOOKMARK.constant('_MB_SYSTEM_SOCIAL_PROVIDER_'.$i),
		'link'  => "'http://www.netvouz.com/action/submitBookmark?url='+encodeURIComponent(location.href)+'&amp;description=&amp;tags=&amp;title='+encodeURIComponent(document.title)+'&amp;popup=yes'",
		'image' => "netvouz.gif"
	);
	$i++;
	if ($options[$i]) $block['provider'][$i] = array(
		'title' => _MB_SYSTEM_SOCIAL_PROVIDER_BOOKMARK.constant('_MB_SYSTEM_SOCIAL_PROVIDER_'.$i),
		'link'  => "'http://www.propeller.com/submit/?url='+encodeURIComponent(location.href)+'&amp;description=&amp;tags=&amp;title='+encodeURIComponent(document.title)+'&amp;popup=yes'",
		'image' => "propeller.gif"
	);
	$i++;
	if ($options[$i]) $block['provider'][$i] = array(
		'title' => _MB_SYSTEM_SOCIAL_PROVIDER_BOOKMARK.constant('_MB_SYSTEM_SOCIAL_PROVIDER_'.$i),
		'link'  => "'http://buzz.yahoo.com/submit/?submitUrl='+encodeURIComponent(location.href)+'&amp;submitHeadline='+encodeURIComponent(document.title)+'&amp;popup=yes'",
		'image' => "buzz.gif"
	);
	$i++;
	if ($options[$i]) $block['provider'][$i] = array(
		'title' => _MB_SYSTEM_SOCIAL_PROVIDER_BOOKMARK.constant('_MB_SYSTEM_SOCIAL_PROVIDER_'.$i),
		'link'  => "'http://sphinn.com/submit.php?url='+encodeURIComponent(location.href)+'&amp;title='+encodeURIComponent(document.title)+'&amp;popup=yes'",
		'image' => "sphinn.gif"
	);
	$i++;
	if ($options[$i]) $block['provider'][$i] = array(
		'title' => _MB_SYSTEM_SOCIAL_PROVIDER_BOOKMARK.constant('_MB_SYSTEM_SOCIAL_PROVIDER_'.$i),
		'link'  => "'http://www.jumptags.com/add/?url='+encodeURIComponent(location.href)+'&amp;title='+encodeURIComponent(document.title)+'&amp;popup=yes'",
		'image' => "jumptags.gif"
	);

	$block['imagepath'] = ICMS_IMAGES_URL.'/icons/social/';
	return $block;
}

/**
 * @param array $options block config options
 * @return string $form The edit social bookmarks form HTML string
 */
function b_system_social_edit($options) {
	$form = '<strong>'._MB_SYSTEM_SOCIAL_PROVIDER_SELECT.':</strong><br /><br />';
	$form .= '<table width="100%">';

	for ($i = 0; $i < count($options); $i++) {
		$yesno = new XoopsFormRadioYN('', 'options['.$i.']', $options[$i]);
		$form .= '<tr><td width="25%">'.constant('_MB_SYSTEM_SOCIAL_PROVIDER_'.$i).'</td><td>'.$yesno->render().'</td></tr>';
	}
	$form .= '</table>';

	return $form;
}
?>