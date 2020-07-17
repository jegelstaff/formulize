<?php
/**
 * Administration of mailusers, main mailusers file
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @package		Administration
 * @subpackage	Users
 * @version		SVN: $Id: main.php 21377 2011-03-30 13:43:08Z m0nty_ $
 * @todo	scrub the input arrays (GET and POST)
 */

/* 
 * GET variables
 * --none--
 * 
 * POST variables
 * (str) op				send, form
 * (str) mail_send_to
 * (int) mail_inactive
 * (int) mail_mailok
 * (int) mail_lastlog_min
 * (int) mail_lastlog_max
 * (int) mail_idle_more
 * (int) mail_idle_less
 * (int) mail_regd_min
 * (int) mail_regd_max
 * (array) mail_to_group
 * (array) mail_to_user
 * (int) mail_start
 * (str) mail_fromname
 * (str) mail_subject
 * (str) mail_body
 * 
 */
if (!is_object(icms::$user) || !is_object($icmsModule) || !icms::$user->isAdmin($icmsModule->getVar('mid'))) {
	exit("Access Denied");
} else {
	$op = "form";
	$limit = 100;

	if (!empty($_POST['op']) && $_POST['op'] == "send") {
		$op =  $_POST['op'];
	}

	if (!icms::$security->check() || $op == "form") {
		icms_cp_header();
		echo '<div class="CPbigTitle" style="background-image: url(' . ICMS_MODULES_URL . '/system/admin/mailusers/images/mailusers_big.png)">' . _MD_AM_MLUS . '</div><br />';
		if ($op != "form" && $error_msg = icms::$security->getErrors(TRUE)) {
			echo "<div class='errorMsg'>{$error_msg}</div>";
		}
		$display_criteria = 1;
		include ICMS_MODULES_PATH . "/system/admin/mailusers/mailform.php";
		$form->display();
		icms_cp_footer();
	}

	if ($op == "send" && !empty($_POST['mail_send_to'])) {
		$added = array();
		$added_id = array();
		$criteria = array();
		$count_criteria = 0; // user count via criteria;
		if (!empty($_POST['mail_inactive'])) {
			$criteria[] = "level = 0";
		} else {
			if (!empty($_POST['mail_mailok'])) {
				$criteria[] = 'user_mailok = 1';
			}
			if (!empty($_POST['mail_lastlog_min'])) {
				$f_mail_lastlog_min = trim($_POST['mail_lastlog_min']);
				$time = mktime(0, 0, 0, substr($f_mail_lastlog_min, 5, 2), substr($f_mail_lastlog_min, 8, 2), substr($f_mail_lastlog_min, 0, 4));
				if ($time > 0) {
					$criteria[] = "last_login > $time";
				}
			}
			if (!empty($_POST['mail_lastlog_max'])) {
				$f_mail_lastlog_max = trim($_POST['mail_lastlog_max']);
				$time = mktime(0, 0, 0, substr($f_mail_lastlog_max, 5, 2), substr($f_mail_lastlog_max, 8, 2), substr($f_mail_lastlog_max, 0, 4));
				if ($time > 0) {
					$criteria[] = "last_login < $time";
				}
			}
			if (!empty($_POST['mail_idle_more']) && is_numeric($_POST['mail_idle_more'])) {
				$f_mail_idle_more = (int) ($_POST['mail_idle_more']);
				$time = 60 * 60 * 24 * $f_mail_idle_more;
				$time = time() - $time;
				if ($time > 0) {
					$criteria[] = "last_login < $time";
				}
			}
			if (!empty($_POST['mail_idle_less']) && is_numeric($_POST['mail_idle_less'])) {
				$f_mail_idle_less = (int) ($_POST['mail_idle_less']);
				$time = 60 * 60 * 24 * $f_mail_idle_less;
				$time = time() - $time;
				if ($time > 0) {
					$criteria[] = "last_login > $time";
				}
			}
		}

		if (!empty($_POST['mail_regd_min'])) {
			$f_mail_regd_min = trim($_POST['mail_regd_min']);
			$time = mktime(0, 0, 0, substr($f_mail_regd_min, 5, 2), substr($f_mail_regd_min, 8, 2), substr($f_mail_regd_min, 0, 4));
			if ($time > 0) {
				$criteria[] = "user_regdate > $time";
			}
		}

		if (!empty($_POST['mail_regd_max'])) {
			$f_mail_regd_max = trim($_POST['mail_regd_max']);
			$time = mktime(0, 0, 0, substr($f_mail_regd_max, 5, 2), substr($f_mail_regd_max, 8, 2), substr($f_mail_regd_max, 0, 4));
			if ($time > 0) {
				$criteria[] = "user_regdate < $time";
			}
		}

		if (!empty($criteria) || !empty($_POST['mail_to_group'])) {
			$criteria_object = new icms_db_criteria_Compo();
			$criteria_object->setStart(@$_POST['mail_start']);
			$criteria_object->setLimit($limit);
			foreach ($criteria as $c) {
				list ($field, $op, $value) = explode(' ', $c);
				$crit = new icms_db_criteria_Item($field, $value, $op);
				$crit->prefix = "u";
				$criteria_object->add($crit, 'AND');
			}
			$member_handler = icms::handler('icms_member');
			$groups = empty($_POST['mail_to_group']) ? array() : array_map('intval', $_POST['mail_to_group']);
			$getusers = $member_handler->getUsersByGroupLink($groups, $criteria_object, TRUE);
			$count_criteria = $member_handler->getUserCountByGroupLink($groups, $criteria_object);
			foreach ($getusers as $getuser) {
				if (!in_array($getuser->getVar("uid"), $added_id)) {
					$added[] = $getuser;
					$added_id[] = $getuser->getVar("uid");
				}
			}
		}

		if (!empty($_POST['mail_to_user'])) {
			foreach ($_POST['mail_to_user'] as $to_user) {
				if (!in_array($to_user, $added_id)) {
					$added[] = new icms_member_user_Object($to_user);
					$added_id[] = $to_user;
				}
			}
		}

		$added_count = count($added);
		icms_cp_header();
		echo '<div class="CPbigTitle" style="background-image: url('. ICMS_MODULES_URL . '/system/admin/mailusers/images/mailusers_big.png)">' . _MD_AM_MLUS . '</div><br />';
		if ($added_count > 0) {
			$xoopsMailer = new icms_messaging_Handler();
			for ($i = 0; $i < $added_count; $i++) {
				$xoopsMailer->setToUsers($added[$i]);
			}

			$xoopsMailer->setFromName(icms_core_DataFilter::stripSlashesGPC($_POST['mail_fromname']));
			$xoopsMailer->setFromEmail(icms_core_DataFilter::stripSlashesGPC($_POST['mail_fromemail']));
			$xoopsMailer->setSubject(icms_core_DataFilter::stripSlashesGPC($_POST['mail_subject']));
			$xoopsMailer->setBody(icms_core_DataFilter::stripSlashesGPC($_POST['mail_body']));
			if (in_array("mail", $_POST['mail_send_to'])) {
				$xoopsMailer->useMail();
			}
			if (in_array("pm", $_POST['mail_send_to']) && empty($_POST['mail_inactive'])) {
				$xoopsMailer->usePM();
			}

			$xoopsMailer->send(TRUE);
			echo $xoopsMailer->getSuccess();
			echo $xoopsMailer->getErrors();

			if ($count_criteria > $limit) {
				$form = new icms_form_Theme(_AM_SENDMTOUSERS, "mailusers", "admin.php?fct=mailusers", 'post', TRUE);
				if (!empty($_POST['mail_to_group'])) {
					foreach ($_POST['mail_to_group'] as $mailgroup) {
						$group_hidden = new icms_form_elements_Hidden("mail_to_group[]", $mailgroup);
						$form->addElement($group_hidden);
					}
				}
				$inactive_hidden = new icms_form_elements_Hidden("mail_inactive", @$_POST['mail_inactive']);
				$lastlog_min_hidden = new icms_form_elements_Hidden("mail_lastlog_min", icms_core_DataFilter::checkVar($_POST['mail_lastlog_min'], 'text'));
				$lastlog_max_hidden = new icms_form_elements_Hidden("mail_lastlog_max", icms_core_DataFilter::checkVar($_POST['mail_lastlog_max'], 'text'));
				$regd_min_hidden = new icms_form_elements_Hidden("mail_regd_min", icms_core_DataFilter::checkVar($_POST['mail_regd_min'], 'text'));
				$regd_max_hidden = new icms_form_elements_Hidden("mail_regd_max", icms_core_DataFilter::checkVar($_POST['mail_regd_max'], 'text'));
				$idle_more_hidden = new icms_form_elements_Hidden("mail_idle_more", icms_core_DataFilter::checkVar($_POST['mail_idle_more'], 'text'));
				$idle_less_hidden = new icms_form_elements_Hidden("mail_idle_less", icms_core_DataFilter::checkVar($_POST['mail_idle_less'], 'text'));
				$fname_hidden = new icms_form_elements_Hidden("mail_fromname", icms_core_DataFilter::checkVar($_POST['mail_fromname'], 'text'));
				$femail_hidden = new icms_form_elements_Hidden("mail_fromemail", icms_core_DataFilter::checkVar($_POST['mail_fromemail'], 'text'));
				$subject_hidden = new icms_form_elements_Hidden("mail_subject", icms_core_DataFilter::checkVar($_POST['mail_subject'], 'text'));
				$body_hidden = new icms_form_elements_Hidden("mail_body", icms_core_DataFilter::checkVar($_POST['mail_body'], 'text'));
				$start_hidden = new icms_form_elements_Hidden("mail_start", (int) $_POST['mail_start'] + $limit);
				$mail_mailok_hidden = new icms_form_elements_Hidden("mail_mailok", icms_core_DataFilter::checkVar(@$_POST['mail_mailok']));
				$op_hidden = new icms_form_elements_Hidden("op", "send");
				$submit_button = new icms_form_elements_Button("", "mail_submit", _AM_SENDNEXT, "submit");
				$sent_label = new icms_form_elements_Label(_AM_SENT, sprintf(_AM_SENTNUM, (int) $_POST['mail_start'] + 1, (int) $_POST['mail_start'] + $limit, $count_criteria + $added_count - $limit));
				$form->addElement($sent_label);
				$form->addElement($inactive_hidden);
				$form->addElement($lastlog_min_hidden);
				$form->addElement($lastlog_max_hidden);
				$form->addElement($regd_min_hidden);
				$form->addElement($regd_max_hidden);
				$form->addElement($idle_more_hidden);
				$form->addElement($idle_less_hidden);
				$form->addElement($fname_hidden);
				$form->addElement($femail_hidden);
				$form->addElement($subject_hidden);
				$form->addElement($body_hidden);
				$form->addElement($op_hidden);
				$form->addElement($start_hidden);
				$form->addElement($mail_mailok_hidden);
				if (isset($_POST['mail_send_to']) && is_array($_POST['mail_send_to'])) {
					foreach ($_POST['mail_send_to'] as $v) {
						$form->addElement(new icms_form_elements_Hidden("mail_send_to[]", $v));
					}
				} else {
					$to_hidden = new icms_form_elements_Hidden("mail_send_to", 'mail');
					$form->addElement($to_hidden);
				}
				$form->addElement($submit_button);
				$form->display();
			} else {
				echo "<h4>" . _AM_SENDCOMP . "</h4>";
			}
		} else {
			echo "<h4>" . _AM_NOUSERMATCH . "</h4>";
		}
		icms_cp_footer();
	}
}

