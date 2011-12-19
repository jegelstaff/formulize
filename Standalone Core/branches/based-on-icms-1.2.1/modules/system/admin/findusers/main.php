<?php
// $Id: main.php 8768 2009-05-16 22:48:26Z pesianstranger $
/**
* Administration of finding users, main file
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	LICENSE.txt
* @package	Administration
* @since	XOOPS
* @author	http://www.xoops.org The XOOPS Project
* @author	modified by UnderDog <underdog@impresscms.org>
* @version	$Id: main.php 8768 2009-05-16 22:48:26Z pesianstranger $
*/

if ( !is_object($icmsUser) || !is_object($icmsModule) || !$icmsUser->isAdmin($icmsModule->mid()) ) {
    exit("Access Denied");
}
if(!empty($_POST)) foreach($_POST as $k => $v) ${$k} = StopXSS($v);
if(!empty($_GET)) foreach($_GET as $k => $v) ${$k} = StopXSS($v);
$op = (isset($_GET['op']))?trim(StopXSS($_GET['op'])):((isset($_POST['op']))?trim(StopXSS($_POST['op'])):'form');

xoops_cp_header();
//OpenTable();

if ( $op == "form" ) {
	$member_handler =& xoops_gethandler('member');
	$acttotal = icms_conv_nr2local($member_handler->getUserCount(new Criteria('level', 0, '>')));
	$inacttotal = icms_conv_nr2local($member_handler->getUserCount(new Criteria('level', 0)));
	include_once XOOPS_ROOT_PATH."/class/xoopsformloader.php";
	$group_select = new XoopsFormSelectGroup(_AM_GROUPS, "selgroups", null, false, 5, true);
	$uname_text = new XoopsFormText("", "user_uname", 30, 60);
	$uname_match = new XoopsFormSelectMatchOption("", "user_uname_match");
	$uname_tray = new XoopsFormElementTray(_AM_UNAME, "&nbsp;");
	$uname_tray->addElement($uname_match);
	$uname_tray->addElement($uname_text);
	$name_text = new XoopsFormText("", "user_name", 30, 60);
	$name_match = new XoopsFormSelectMatchOption("", "user_name_match");
	$name_tray = new XoopsFormElementTray(_AM_REALNAME, "&nbsp;");
	$name_tray->addElement($name_match);
	$name_tray->addElement($name_text);
	$email_text = new XoopsFormText("", "user_email", 30, 60);
	$email_match = new XoopsFormSelectMatchOption("", "user_email_match");
	$email_tray = new XoopsFormElementTray(_AM_EMAIL, "&nbsp;");
	$email_tray->addElement($email_match);
	$email_tray->addElement($email_text);
	$login_name_text = new XoopsFormText("", "user_login_name", 30, 60);
	$login_name_match = new XoopsFormSelectMatchOption("", "user_login_name_match");
	$login_name_tray = new XoopsFormElementTray(_AM_LOGINNAME, "&nbsp;");
	$login_name_tray->addElement($login_name_match);
	$login_name_tray->addElement($login_name_text);
	$url_text = new XoopsFormText(_AM_URLC, "user_url", 30, 100);
	//$theme_select = new XoopsFormSelectTheme(_AM_THEME, "user_theme");
	//$timezone_select = new XoopsFormSelectTimezone(_AM_TIMEZONE, "user_timezone_offset");
	$icq_text = new XoopsFormText("", "user_icq", 30, 100);
	$icq_match = new XoopsFormSelectMatchOption("", "user_icq_match");
	$icq_tray = new XoopsFormElementTray(_AM_ICQ, "&nbsp;");
	$icq_tray->addElement($icq_match);
	$icq_tray->addElement($icq_text);
	$aim_text = new XoopsFormText("", "user_aim", 30, 100);
	$aim_match = new XoopsFormSelectMatchOption("", "user_aim_match");
	$aim_tray = new XoopsFormElementTray(_AM_AIM, "&nbsp;");
	$aim_tray->addElement($aim_match);
	$aim_tray->addElement($aim_text);
	$yim_text = new XoopsFormText("", "user_yim", 30, 100);
	$yim_match = new XoopsFormSelectMatchOption("", "user_yim_match");
	$yim_tray = new XoopsFormElementTray(_AM_YIM, "&nbsp;");
	$yim_tray->addElement($yim_match);
	$yim_tray->addElement($yim_text);
	$msnm_text = new XoopsFormText("", "user_msnm", 30, 100);
	$msnm_match = new XoopsFormSelectMatchOption("", "user_msnm_match");
	$msnm_tray = new XoopsFormElementTray(_AM_MSNM, "&nbsp;");
	$msnm_tray->addElement($msnm_match);
	$msnm_tray->addElement($msnm_text);
	$location_text = new XoopsFormText(_AM_LOCATION, "user_from", 30, 100);
	$occupation_text = new XoopsFormText(_AM_OCCUPATION, "user_occ", 30, 100);
	$interest_text = new XoopsFormText(_AM_INTEREST, "user_intrest", 30, 100);

	//$bio_text = new XoopsFormText(_AM_EXTRAINFO, "user_bio", 30, 100);
	$lastlog_more = new XoopsFormText(_AM_LASTLOGMORE, "user_lastlog_more", 10, 5);
	$lastlog_less = new XoopsFormText(_AM_LASTLOGLESS, "user_lastlog_less", 10, 5);
	$reg_more = new XoopsFormText(_AM_REGMORE, "user_reg_more", 10, 5);
	$reg_less = new XoopsFormText(_AM_REGLESS, "user_reg_less", 10, 5);
	$posts_more = new XoopsFormText(_AM_POSTSMORE, "user_posts_more", 10, 5);
	$posts_less = new XoopsFormText(_AM_POSTSLESS, "user_posts_less", 10, 5);
	$mailok_radio = new XoopsFormRadio(_AM_SHOWMAILOK, "user_mailok", "both");
	$mailok_radio->addOptionArray(array("mailok"=>_AM_MAILOK, "mailng"=>_AM_MAILNG, "both"=>_AM_BOTH));
	$type_radio = new XoopsFormRadio(_AM_SHOWTYPE, "user_type", "actv");
	$type_radio->addOptionArray(array("actv"=>_AM_ACTIVE, "inactv"=>_AM_INACTIVE, "both"=>_AM_BOTH));
	$sort_select = new XoopsFormSelect(_AM_SORT, "user_sort");
	$sort_select->addOptionArray(array("uname"=>_AM_UNAME,"login_name"=>_AM_LOGINNAME,"email"=>_AM_EMAIL,"last_login"=>_AM_LASTLOGIN,"user_regdate"=>_AM_REGDATE,"posts"=>_AM_POSTS));
	$order_select = new XoopsFormSelect(_AM_ORDER, "user_order");
	$order_select->addOptionArray(array("ASC"=>_AM_ASC,"DESC"=>_AM_DESC));
	$limit_text = new XoopsFormText(_AM_LIMIT, "limit", 6, 2);
	$fct_hidden = new XoopsFormHidden("fct", "findusers");
	$op_hidden = new XoopsFormHidden("op", "submit");
	$submit_button = new XoopsFormButton("", "user_submit", _SUBMIT, "submit");

	$form = new XoopsThemeForm(_AM_FINDUS, "uesr_findform", "admin.php", 'post', true);
	$form->addElement($uname_tray);
	$form->addElement($name_tray);
	$form->addElement($login_name_tray);
	$form->addElement($email_tray);
	$form->addElement($group_select);
	//$form->addElement($theme_select);
	//$form->addElement($timezone_select);
	$form->addElement($icq_tray);
	$form->addElement($aim_tray);
	$form->addElement($yim_tray);
	$form->addElement($msnm_tray);
	$form->addElement($url_text);
	$form->addElement($location_text);
	$form->addElement($occupation_text);
	$form->addElement($interest_text);
	//$form->addElement($bio_text);
	$form->addElement($lastlog_more);
	$form->addElement($lastlog_less);
	$form->addElement($reg_more);
	$form->addElement($reg_less);
	$form->addElement($posts_more);
	$form->addElement($posts_less);
	$form->addElement($mailok_radio);
	$form->addElement($type_radio);
	$form->addElement($sort_select);
	$form->addElement($order_select);
	$form->addElement($fct_hidden);
	$form->addElement($limit_text);
	$form->addElement($op_hidden);
	// if this is to find users for a specific group
	if ( !empty($_GET['group']) && intval($_GET['group']) > 0 ) {
		$group_hidden = new XoopsFormHidden("group", intval($_GET['group']));
		$form->addElement($group_hidden);
	}
	$form->addElement($submit_button);
	echo '<div class="CPbigTitle" style="background-image: url('.XOOPS_URL.'/modules/system/admin/findusers/images/findusers_big.png)">'._AM_FINDUS.'</div><br />';
	echo "(".sprintf(_AM_ACTUS, "<span style='color:#ff0000;'>$acttotal</span>")." ".sprintf(_AM_INACTUS, "<span style='color:#ff0000;'>$inacttotal</span>").")<br /><br />";
	$form->display();
}

elseif ($op == "submit" & $GLOBALS['xoopsSecurity']->check()) {
	$myts =& MyTextSanitizer::getInstance();
	$criteria = new CriteriaCompo();
	if ( !empty($_POST['user_uname']) ) {
		$match = (!empty($_POST['user_uname_match'])) ? intval($_POST['user_uname_match']) : XOOPS_MATCH_START;
		switch ($match) {
		case XOOPS_MATCH_START:
		    $criteria->add(new Criteria('uname', $myts->addSlashes(trim($_POST['user_uname'])).'%', 'LIKE'));
		    break;
		case XOOPS_MATCH_END:
		    $criteria->add(new Criteria('uname', '%'.$myts->addSlashes(trim($_POST['user_uname'])), 'LIKE'));
		    break;
		case XOOPS_MATCH_EQUAL:
		    $criteria->add(new Criteria('uname', $myts->addSlashes(trim($_POST['user_uname']))));
		    break;
		case XOOPS_MATCH_CONTAIN:
		    $criteria->add(new Criteria('uname', '%'.$myts->addSlashes(trim($_POST['user_uname'])).'%', 'LIKE'));
		    break;
		}
	}
	if ( !empty($_POST['user_name']) ) {
		$match = (!empty($_POST['user_name_match'])) ? intval($_POST['user_name_match']) : XOOPS_MATCH_START;
		switch ($match) {
		case XOOPS_MATCH_START:
		    $criteria->add(new Criteria('name', $myts->addSlashes(trim($_POST['user_name'])).'%', 'LIKE'));
		    break;
		case XOOPS_MATCH_END:
		    $criteria->add(new Criteria('name', '%'.$myts->addSlashes(trim($_POST['user_name'])), 'LIKE'));
		    break;
		case XOOPS_MATCH_EQUAL:
		    $criteria->add(new Criteria('name', $myts->addSlashes(trim($_POST['user_name']))));
		    break;
		case XOOPS_MATCH_CONTAIN:
		    $criteria->add(new Criteria('name', '%'.$myts->addSlashes(trim($_POST['user_name'])).'%', 'LIKE'));
		    break;
		}
	}
	if ( !empty($_POST['user_login_name']) ) {
		$match = (!empty($_POST['user_login_name_match'])) ? intval($_POST['user_login_name_match']) : XOOPS_MATCH_START;
		switch ($match) {
		case XOOPS_MATCH_START:
		    $criteria->add(new Criteria('login_name', $myts->addSlashes(trim($_POST['user_login_name'])).'%', 'LIKE'));
		    break;
		case XOOPS_MATCH_END:
		    $criteria->add(new Criteria('login_name', '%'.$myts->addSlashes(trim($_POST['user_login_name'])), 'LIKE'));
		    break;
		case XOOPS_MATCH_EQUAL:
		    $criteria->add(new Criteria('login_name', $myts->addSlashes(trim($_POST['user_login_name']))));
		    break;
		case XOOPS_MATCH_CONTAIN:
		    $criteria->add(new Criteria('login_name', '%'.$myts->addSlashes(trim($_POST['user_login_name'])).'%', 'LIKE'));
		    break;
		}
	}
	if ( !empty($_POST['user_email']) ) {
		$match = (!empty($_POST['user_email_match'])) ? intval($_POST['user_email_match']) : XOOPS_MATCH_START;
		switch ($match) {
		case XOOPS_MATCH_START:
		    $criteria->add(new Criteria('email', $myts->addSlashes(trim($_POST['user_email'])).'%', 'LIKE'));
		    break;
		case XOOPS_MATCH_END:
		    $criteria->add(new Criteria('email', '%'.$myts->addSlashes(trim($_POST['user_email'])), 'LIKE'));
		    break;
		case XOOPS_MATCH_EQUAL:
		    $criteria->add(new Criteria('email', $myts->addSlashes(trim($_POST['user_email']))));
		    break;
		case XOOPS_MATCH_CONTAIN:
		    $criteria->add(new Criteria('email', '%'.$myts->addSlashes(trim($_POST['user_email'])).'%', 'LIKE'));
		    break;
		}
	}
	if ( !empty($_POST['user_url']) ) {
		$url = formatURL(trim($_POST['user_url']));
		$criteria->add(new Criteria('url', $url.'%', 'LIKE'));
	}
	if ( !empty($_POST['user_icq']) ) {
		$match = (!empty($_POST['user_icq_match'])) ? intval($_POST['user_icq_match']) : XOOPS_MATCH_START;
		switch ($match) {
		case XOOPS_MATCH_START:
		    $criteria->add(new Criteria('user_icq', $myts->addSlashes(trim($_POST['user_icq'])).'%', 'LIKE'));
		    break;
		case XOOPS_MATCH_END:
		    $criteria->add(new Criteria('user_icq', '%'.$myts->addSlashes(trim($_POST['user_icq'])), 'LIKE'));
		    break;
		case XOOPS_MATCH_EQUAL:
		    $criteria->add(new Criteria('user_icq', '%'.$myts->addSlashes(trim($_POST['user_icq']))));
		    break;
		case XOOPS_MATCH_CONTAIN:
		    $criteria->add(new Criteria('user_icq', '%'.$myts->addSlashes(trim($_POST['user_icq'])).'%', 'LIKE'));
		    break;
		}
	}
	if ( !empty($_POST['user_aim']) ) {
		$match = (!empty($_POST['user_aim_match'])) ? intval($_POST['user_aim_match']) : XOOPS_MATCH_START;
		switch ($match) {
		case XOOPS_MATCH_START:
		    $criteria->add(new Criteria('user_aim', $myts->addSlashes(trim($_POST['user_aim'])).'%', 'LIKE'));
		    break;
		case XOOPS_MATCH_END:
		    $criteria->add(new Criteria('user_aim', '%'.$myts->addSlashes(trim($_POST['user_aim'])), 'LIKE'));
		    break;
		case XOOPS_MATCH_EQUAL:
		    $criteria->add(new Criteria('user_aim', $myts->addSlashes(trim($_POST['user_aim']))));
		    break;
		case XOOPS_MATCH_CONTAIN:
		    $criteria->add(new Criteria('user_aim', '%'.$myts->addSlashes(trim($_POST['user_aim'])).'%', 'LIKE'));
		    break;
		}
	}
	if ( !empty($_POST['user_yim']) ) {
		$match = (!empty($_POST['user_yim_match'])) ? intval($_POST['user_yim_match']) : XOOPS_MATCH_START;
		switch ($match) {
		case XOOPS_MATCH_START:
		    $criteria->add(new Criteria('user_yim', $myts->addSlashes(trim($_POST['user_yim'])).'%', 'LIKE'));
		    break;
		case XOOPS_MATCH_END:
		    $criteria->add(new Criteria('user_yim', '%'.$myts->addSlashes(trim($_POST['user_yim'])), 'LIKE'));
		    break;
		case XOOPS_MATCH_EQUAL:
		    $criteria->add(new Criteria('user_yim', $myts->addSlashes(trim($_POST['user_yim']))));
		    break;
		case XOOPS_MATCH_CONTAIN:
		    $criteria->add(new Criteria('user_yim', '%'.$myts->addSlashes(trim($_POST['user_yim'])).'%', 'LIKE'));
		    break;
		}
	}
	if ( !empty($_POST['user_msnm']) ) {
		$match = (!empty($_POST['user_msnm_match'])) ? intval($_POST['user_msnm_match']) : XOOPS_MATCH_START;
		switch ($match) {
		case XOOPS_MATCH_START:
		    $criteria->add(new Criteria('user_msnm', $myts->addSlashes(trim($_POST['user_msnm'])).'%', 'LIKE'));
		    break;
		case XOOPS_MATCH_END:
		    $criteria->add(new Criteria('user_msnm', '%'.$myts->addSlashes(trim($_POST['user_msnm'])), 'LIKE'));
		    break;
		case XOOPS_MATCH_EQUAL:
		    $criteria->add(new Criteria('user_msnm', '%'.$myts->addSlashes(trim($_POST['user_msnm']))));
		    break;
		case XOOPS_MATCH_CONTAIN:
		    $criteria->add(new Criteria('user_msnm', '%'.$myts->addSlashes(trim($_POST['user_msnm'])).'%', 'LIKE'));
		    break;
		}
	}
	if ( !empty($_POST['user_from']) ) {
		$criteria->add(new Criteria('user_from', '%'.$myts->addSlashes(trim($_POST['user_from'])).'%', 'LIKE'));
	}
	if ( !empty($_POST['user_intrest']) ) {
		$criteria->add(new Criteria('user_intrest', '%'.$myts->addSlashes(trim($_POST['user_intrest'])).'%', 'LIKE'));
	}
	if ( !empty($_POST['user_occ']) ) {
		$criteria->add(new Criteria('user_occ', '%'.$myts->addSlashes(trim($_POST['user_occ'])).'%', 'LIKE'));
	}
	
	if ( !empty($_POST['user_lastlog_more']) && is_numeric($_POST['user_lastlog_more']) ) {
		$f_user_lastlog_more = intval(trim($_POST['user_lastlog_more']));
		$time = time() - (60 * 60 * 24 * $f_user_lastlog_more);
		if ( $time > 0 ) {
			$criteria->add(new Criteria('last_login', $time, '<'));
		}
	}
	if ( !empty($_POST['user_lastlog_less']) && is_numeric($_POST['user_lastlog_less']) ) {
		$f_user_lastlog_less = intval(trim($_POST['user_lastlog_less']));
		$time = time() - (60 * 60 * 24 * $f_user_lastlog_less);
		if ( $time > 0 ) {
			$criteria->add(new Criteria('last_login', $time, '>'));
		}
	}
	if ( !empty($_POST['user_reg_more']) && is_numeric($_POST['user_reg_more']) ) {
		$f_user_reg_more = intval(trim($_POST['user_reg_more']));
		$time = time() - (60 * 60 * 24 * $f_user_reg_more);
		if ( $time > 0 ) {
			$criteria->add(new Criteria('user_regdate', $time, '<'));
		}
	}
	if ( !empty($_POST['user_reg_less']) && is_numeric($_POST['user_reg_less']) ) {
		$f_user_reg_less = intval($_POST['user_reg_less']);
		$time = time() - (60 * 60 * 24 * $f_user_reg_less);
		if ( $time > 0 ) {
			$criteria->add(new Criteria('user_regdate', $time, '>'));
		}
	}
	if ( !empty($_POST['user_posts_more']) && is_numeric($_POST['user_posts_more']) ) {
		$criteria->add(new Criteria('posts', intval($_POST['user_posts_more']), '>'));
	}
	if ( !empty($_POST['user_posts_less']) && is_numeric($_POST['user_posts_less']) ) {
		$criteria->add(new Criteria('posts', intval($_POST['user_posts_less']), '<'));
	}
	if ( isset($_POST['user_mailok']) ) {
		if ( $_POST['user_mailok'] == "mailng" ) {
			$criteria->add(new Criteria('user_mailok', 0));
		} elseif ( $_POST['user_mailok'] == "mailok" ) {
			$criteria->add(new Criteria('user_mailok', 1));
		} else {
			$criteria->add(new Criteria('user_mailok', 0, '>='));
		}
	}
	if ( isset($_POST['user_type']) ) {
		if ( $_POST['user_type'] == "inactv" ) {
			$criteria->add(new Criteria('level', 0, '='));
		} elseif ( $_POST['user_type'] == "actv" ) {
			$criteria->add(new Criteria('level', 0, '>'));
		} else {
			$criteria->add(new Criteria('level', 0, '>='));
		}
	}
	$groups = empty($_POST['selgroups']) ? array() : array_map("intval", $_POST['selgroups']);
	$validsort = array("uname", "login_name", "email", "last_login", "user_regdate", "posts");
	$sort = (!in_array($_POST['user_sort'], $validsort)) ? "uname" : $_POST['user_sort'];
	$order = "ASC";
	if ( isset($_POST['user_order']) && $_POST['user_order'] == "DESC") {
		$order = "DESC";
	}
	$limit = (!empty($_POST['limit'])) ? intval($_POST['limit']) : 50;
	if ( $limit == 0 || $limit > 50 ) {
		$limit = 50;
	}
	$start = (!empty($_POST['start'])) ? intval($_POST['start']) : 0;
	$member_handler =& xoops_gethandler('member');
	$total = $member_handler->getUserCountByGroupLink($groups, $criteria);
	echo '<div class="CPbigTitle" style="background-image: url('.XOOPS_URL.'/modules/system/admin/findusers/images/findusers_big.png)">'._AM_FINDUS.'</div><br />';
	if ( $total == 0 ) {
		echo "<h4>"._AM_NOFOUND,"</h4>";
	} elseif ( $start < $total ) {
		echo sprintf(_AM_USERSFOUND, icms_conv_nr2local($total))."<br />";
		echo "<form action='admin.php' method='post' name='memberslist' id='memberslist'><input type='hidden' name='op' value='delete_many' />
		<table width='100%' border='0' cellspacing='1' cellpadding='4' class='outer'><tr><th align='center'><input type='checkbox' name='memberslist_checkall' id='memberslist_checkall' onclick='xoopsCheckAll(\"memberslist\", \"memberslist_checkall\");' /></th><th align='center'>"._AM_AVATAR."</th><th align='center'>"._AM_UNAME."</th><th align='center'>"._AM_LOGINNAME."</th><th align='center'>"._AM_REALNAME."</th><th align='center'>"._AM_EMAIL."</th><th align='center'>"._AM_PM."</th><th align='center'>"._AM_URL."</th><th align='center'>"._AM_REGDATE."</th><th align='center'>"._AM_LASTLOGIN."</th><th align='center'>"._AM_POSTS."</th><th align='center'>&nbsp;</th></tr>";
		$criteria->setSort($sort);
		$criteria->setOrder($order);
		$criteria->setLimit($limit);
		$criteria->setStart($start);
		$foundusers =& $member_handler->getUsersByGroupLink($groups, $criteria, true);
		$ucount = 0;
		foreach (array_keys($foundusers) as $j) {
			if ($ucount % 2 == 0) {
				$class = 'even';
			} else {
				$class = 'odd';
			}
			$ucount++;
			$fuser_avatar = $foundusers[$j]->getVar("user_avatar") ? "<img src='".XOOPS_UPLOAD_URL."/".$foundusers[$j]->getVar("user_avatar")."' alt='' />" : "&nbsp;";
			$fuser_name = $foundusers[$j]->getVar("name") ? $foundusers[$j]->getVar("name") : "&nbsp;";
			echo "<tr class='$class'><td align='center'><input type='checkbox' name='memberslist_id[]' id='memberslist_id[]' value='".$foundusers[$j]->getVar("uid")."' /><input type='hidden' name='memberslist_uname[".$foundusers[$j]->getVar("uid")."]' id='memberslist_uname[]' value='".$foundusers[$j]->getVar("uname")."' /></td>";
			echo "<td>$fuser_avatar</td><td><a href='".XOOPS_URL."/userinfo.php?uid=".$foundusers[$j]->getVar("uid")."'>".$foundusers[$j]->getVar("uname")."</a></td><td>".$foundusers[$j]->getVar("login_name")."</td><td>".$fuser_name."</td><td align='center'><a href='mailto:".$foundusers[$j]->getVar("email")."'><img src='".XOOPS_URL."/images/icons/".$GLOBALS["xoopsConfig"]["language"]."/email.gif' border='0' alt='";
			printf(_SENDEMAILTO,$foundusers[$j]->getVar("uname", "E"));
			echo "' /></a></td><td align='center'><a href='javascript:openWithSelfMain(\"".XOOPS_URL."/pmlite.php?send2=1&amp;to_userid=".$foundusers[$j]->getVar("uid")."\",\"pmlite\",800,680);'><img src='".XOOPS_URL."/images/icons/".$GLOBALS["xoopsConfig"]["language"]."/pm.gif' border='0' alt='";
			printf(_SENDPMTO,$foundusers[$j]->getVar("uname", "E"));
			echo "' /></a></td><td align='center'>";
			if ( $foundusers[$j]->getVar("url","E") != "" ) {
				echo "<a href='".$foundusers[$j]->getVar("url","E")."' target='_blank'><img src='".XOOPS_URL."/images/icons/".$GLOBALS["xoopsConfig"]["language"]."/www.gif' border='0' alt='"._VISITWEBSITE."' /></a>";
			} else {
				echo "&nbsp;";
			}
			echo "</td><td align='center'>".formatTimeStamp($foundusers[$j]->getVar("user_regdate"),"s")."</td><td align='center'>";
			if ( $foundusers[$j]->getVar("last_login") != 0 ) {
				echo formatTimeStamp($foundusers[$j]->getVar("last_login"),"m");
			} else {
				echo "&nbsp;";
			}
			echo "</td><td align='center'>".icms_conv_nr2local($foundusers[$j]->getVar("posts"))."</td>";
			echo "<td align='center'><a href='".XOOPS_URL."/modules/system/admin.php?fct=users&amp;uid=".$foundusers[$j]->getVar("uid")."&amp;op=modifyUser'>"._EDIT."</a></td></tr>\n";
		}
		echo "<tr class='foot'><td><select name='fct'><option value='users'>"._DELETE."</option><option value='mailusers'>"._AM_SENDMAIL."</option>";
		$group = !empty($_POST['group']) ? intval($_POST['group']) : 0;
		if ( $group > 0 ) {
			$member_handler =& xoops_gethandler('member');
			$add2group =& $member_handler->getGroup($group);
			echo "<option value='groups' selected='selected'>".sprintf(_AM_ADD2GROUP, $add2group->getVar('name'))."</option>";
		}
		echo "</select>&nbsp;";
		if ( $group > 0 ) {
			echo "<input type='hidden' name='groupid' value='".$group."' />";
		}
		echo "</td><td colspan='12'>".$GLOBALS['xoopsSecurity']->getTokenHTML()."<input type='submit' value='"._SUBMIT."' /></td></tr></table></form>\n";
		$totalpages = ceil($total / $limit);
		if ( $totalpages > 1 ) {
			$hiddenform = "<form name='findnext' action='admin.php' method='post'>";
			$skip_vars = array('selgroups');
			foreach ( $_POST as $k => $v ) {
				if ($k == 'selgroups') {
					foreach( $_POST['selgroups'] as $_group){
						$hiddenform .= "<input type='hidden' name='selgroups[]' value='".$_group."' />\n";
					}
				} elseif ($k == 'XOOPS_TOKEN_REQUEST') {
					// regenerate token value
					$hiddenform .= $GLOBALS['xoopsSecurity']->getTokenHTML()."\n";
				} else {
					$hiddenform .= "<input type='hidden' name='".$myts->htmlSpecialChars($k)."' value='".$myts->htmlSpecialChars($myts->stripSlashesGPC($v))."' />\n";
				}
			}
			if (!isset($_POST['limit'])) {
				$hiddenform .= "<input type='hidden' name='limit' value='".$limit."' />\n";
			}
			if (!isset($_POST['start'])) {
				$hiddenform .= "<input type='hidden' name='start' value='".$start."' />\n";
			}
			$prev = $start - $limit;
			if ( $start - $limit >= 0 ) {
				$hiddenform .= "<a href='#0' onclick='javascript:document.findnext.start.value=".$prev.";document.findnext.submit();'>"._AM_PREVIOUS."</a>&nbsp;\n";
			}
			$counter = 1;
			$currentpage = ($start+$limit) / $limit;
			while ( $counter <= $totalpages ) {
				if ( $counter == $currentpage ) {
					$hiddenform .= "<b>".$counter."</b> ";
				} elseif ( ($counter > $currentpage-4 && $counter < $currentpage+4) || $counter == 1 || $counter == $totalpages ) {
					if ( $counter == $totalpages && $currentpage < $totalpages-4 ) {
						$hiddenform .= "... ";
					}
					$hiddenform .= "<a href='#".$counter."' onclick='javascript:document.findnext.start.value=".($counter-1)*$limit.";document.findnext.submit();'>".$counter."</a> ";
					if ( $counter == 1 && $currentpage > 5 ) {
						$hiddenform .= "... ";
					}
				}
				$counter++;
			}
			$next = $start+$limit;
			if ( $total > $next ) {
				$hiddenform .= "&nbsp;<a href='#".$total."' onclick='javascript:document.findnext.start.value=".$next.";document.findnext.submit();'>"._AM_NEXT."</a>\n";
			}
			$hiddenform .= "</form>";
			echo "<div style='text-align:center'>".$hiddenform."<br />";
			printf(_AM_USERSFOUND, $total);
			echo "</div>";
		}
	}
}
else {
	redirect_header('admin.php?fct=findusers', 3, implode('<br />', $GLOBALS['xoopsSecurity']->getErrors()));
}
//CloseTable();
xoops_cp_footer();

?>