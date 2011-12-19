<?php
/**
 * The delete comment include file
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	LICENSE.txt
 * @package	core
 * @since	XOOPS
 * @author	http://www.xoops.org The XOOPS Project
 * @author	modified by UnderDog <underdog@impresscms.org>
 * @version	$Id: comment_delete.php 20768 2011-02-06 00:02:25Z skenow $
 */

if (!defined('ICMS_ROOT_PATH') || !is_object($icmsModule)) {
	exit();
}
include_once ICMS_ROOT_PATH . '/include/comment_constants.php';
$op = 'delete';
if (!empty($_POST)) {
	extract($_POST);
	$com_mode = isset($com_mode) ? htmlspecialchars(trim($com_mode), ENT_QUOTES) : 'flat';
	$com_order = isset($com_order) ? (int) $com_order : XOOPS_COMMENT_OLD1ST;
	$com_id = isset($com_id) ? (int) $com_id : 0;
} else {
	$com_mode = isset($_GET['com_mode']) ? htmlspecialchars(trim($_GET['com_mode']), ENT_QUOTES) : 'flat';
	$com_order = isset($_GET['com_order']) ? (int) $_GET['com_order'] : XOOPS_COMMENT_OLD1ST;
	$com_id = isset($_GET['com_id']) ? (int) $_GET['com_id'] : 0;

}

if ('system' == $icmsModule->getVar('dirname')) {
	$comment_handler = icms::handler('icms_data_comment');
	$comment =& $comment_handler->get($com_id);
	$module_handler = icms::handler('icms_module');
	$module =& $module_handler->get($comment->getVar('com_modid'));
	$comment_config = $module->getInfo('comments');
	$com_modid = $module->getVar('mid');
	$redirect_page = ICMS_URL . '/modules/system/admin.php?fct=comments&amp;com_modid=' . $com_modid . '&amp;com_itemid';
	$moddir = $module->getVar('dirname');
	unset($comment);
} else {
	if (XOOPS_COMMENT_APPROVENONE == $icmsModuleConfig['com_rule']) {
		exit();
	}
	$comment_config = $icmsModule->getInfo('comments');
	$com_modid = $icmsModule->getVar('mid');
	$redirect_page = $comment_config['pageName'] . '?';
	$comment_confirm_extra = array();
	if (isset($comment_config['extraParams']) && is_array($comment_config['extraParams'])) {
		foreach ($comment_config['extraParams'] as $extra_param) {
			if (isset(${$extra_param})) {
				$redirect_page .= $extra_param . '=' . ${$extra_param} . '&amp;';

				// for the confirmation page
				$comment_confirm_extra [$extra_param] = ${$extra_param};
			} elseif (isset($_GET[$extra_param])) {
				$redirect_page .= $extra_param . '=' . $_GET[$extra_param] . '&amp;';

				// for the confirmation page
				$comment_confirm_extra [$extra_param] = $_GET[$extra_param];
			}
		}
	}
	$redirect_page .= $comment_config['itemName'];
	$moddir = $icmsModule->getVar('dirname');
}

$accesserror = false;
if (!is_object(icms::$user)) {
	$accesserror = true;
} else {
	if (!icms::$user->isAdmin($com_modid)) {
		$sysperm_handler = icms::handler('icms_member_groupperm');
		if (!$sysperm_handler->checkRight('system_admin', XOOPS_SYSTEM_COMMENT, icms::$user->getGroups())) {
			$accesserror = true;
		}
	}
}

if (false != $accesserror) {
	$ref = xoops_getenv('HTTP_REFERER');
	if ($ref != '') {
		redirect_header($ref, 2, _NOPERM);
	} else {
		redirect_header($redirect_page . '?' . $comment_config['itemName'] . '=' .  (int) $com_itemid, 2, _NOPERM);
	}
	exit();
}

icms_loadLanguageFile('core', 'comment');

switch ($op) {
	case 'delete_one':
		$comment_handler = icms::handler('icms_data_comment');
		$comment =& $comment_handler->get($com_id);
		if (!$comment_handler->delete($comment)) {
			include ICMS_ROOT_PATH . '/header.php';
			icms_core_Message::error(_CM_COMDELETENG . ' (ID: ' . $comment->getVar('com_id') . ')');
			include ICMS_ROOT_PATH . '/footer.php';
			exit();
		}

		$com_itemid = $comment->getVar('com_itemid');

		// execute updateStat callback function if set
		if (isset($comment_config['callback']['update']) && trim($comment_config['callback']['update']) != '') {
			$skip = false;
			if (!function_exists($comment_config['callback']['update'])) {
				if (isset($comment_config['callbackFile'])) {
					$callbackfile = trim($comment_config['callbackFile']);
					if ($callbackfile != '' && file_exists(ICMS_MODULES_PATH . '/' . $moddir . '/' . $callbackfile)) {
						include_once ICMS_MODULES_PATH . '/' . $moddir . '/' . $callbackfile;
					}
					if (!function_exists($comment_config['callback']['update'])) {
						$skip = true;
					}
				} else {
					$skip = true;
				}
			}
			if (!$skip) {
				$criteria = new icms_db_criteria_Compo(new icms_db_criteria_Item('com_modid', $com_modid));
				$criteria->add(new icms_db_criteria_Item('com_itemid', $com_itemid));
				$criteria->add(new icms_db_criteria_Item('com_status', XOOPS_COMMENT_ACTIVE));
				$comment_count = $comment_handler->getCount($criteria);
				$comment_config['callback']['update']($com_itemid, $comment_count);
			}
		}

		// update user posts if its not an anonymous post
		if ($comment->getVar('com_uid') != 0) {
			$member_handler = icms::handler('icms_member');
			$com_poster =& $member_handler->getUser($comment->getVar('com_uid'));
			if (is_object($com_poster)) {
				$member_handler->updateUserByField($com_poster, 'posts', $com_poster->getVar('posts') - 1);
			}
		}

		// get all comments posted later within the same thread
		$thread_comments =& $comment_handler->getThread($comment->getVar('com_rootid'), $com_id);

		$xot = new icms_ipf_Tree($thread_comments, 'com_id', 'com_pid', 'com_rootid');

		$child_comments =& $xot->getFirstChild($com_id);

		// now set new parent ID for direct child comments
		$new_pid = $comment->getVar('com_pid');
		$errs = array();
		foreach (array_keys($child_comments) as $i) {
			$child_comments[$i]->setVar('com_pid', $new_pid);
			// if the deleted comment is a root comment, need to change root id to own id
			if (false != $comment->isRoot()) {
				$new_rootid = $child_comments[$i]->getVar('com_id');
				$child_comments[$i]->setVar('com_rootid', $child_comments[$i]->getVar('com_id'));
				if (!$comment_handler->insert($child_comments[$i])) {
					$errs[] = sprintf(_CM_COULDNOTCHANGEPIDTOID, icms_conv_nr2local($com_id), icms_conv_nr2local($new_pid), icms_conv_nr2local($new_rootid));
				} else {
					// need to change root id for all its child comments as well
					$c_child_comments =& $xot->getAllChild($new_rootid);
					$cc_count = count($c_child_comments);
					foreach (array_keys($c_child_comments) as $j) {
						$c_child_comments[$j]->setVar('com_rootid', $new_rootid);
						if (!$comment_handler->insert($c_child_comments[$j])) {
							$errs[] = sprintf(_CM_COULDNOTCHANGEROOTID, icms_conv_nr2local($com_id), icms_conv_nr2local($new_rootid));
						}
					}
				}
			} else {
				if (!$comment_handler->insert($child_comments[$i])) {
					$errs[] = sprintf(_CM_COULDNOTCHANGEPAID, icms_conv_nr2local($com_id), icms_conv_nr2local($new_pid));
				}
			}
		}
		if (count($errs) > 0) {
			include ICMS_ROOT_PATH . '/header.php';
			icms_core_Message::error($errs);
			include ICMS_ROOT_PATH . '/footer.php';
			exit();
		}
		redirect_header($redirect_page .'=' . $com_itemid . '&amp;com_order=' . $com_order . '&amp;com_mode=' . $com_mode, 1, _CM_COMDELETED);
		break;

	case 'delete_all':
		$comment_handler = icms::handler('icms_data_comment');
		$comment =& $comment_handler->get($com_id);
		$com_rootid = $comment->getVar('com_rootid');

		// get all comments posted later within the same thread
		$thread_comments =& $comment_handler->getThread($com_rootid, $com_id);

		// construct a comment tree
		$xot = new icms_ipf_Tree($thread_comments, 'com_id', 'com_pid', 'com_rootid');
		$child_comments =& $xot->getAllChild($com_id);
		// add itself here
		$child_comments[$com_id] =& $comment;
		$msgs = array();
		$deleted_num = array();
		$member_handler = icms::handler('icms_member');
		foreach (array_keys($child_comments) as $i) {
			if (!$comment_handler->delete($child_comments[$i])) {
				$msgs[] = _CM_COMDELETENG . ' (ID: ' . icms_conv_nr2local($child_comments[$i]->getVar('com_id')) . ')';
			} else {
				$msgs[] = _CM_COMDELETED . ' (ID: ' . icms_conv_nr2local($child_comments[$i]->getVar('com_id')) . ')';
				// store poster ID and deleted post number into array for later use
				$poster_id = $child_comments[$i]->getVar('com_uid');
				if ($poster_id > 0) {
					$deleted_num[$poster_id] = !isset($deleted_num[$poster_id]) ? 1 : ($deleted_num[$poster_id] + 1);
				}
			}
		}
		foreach ($deleted_num as $user_id => $post_num) {
			// update user posts
			$com_poster = $member_handler->getUser($user_id);
			if (is_object($com_poster)) {
				$member_handler->updateUserByField($com_poster, 'posts', $com_poster->getVar('posts') - $post_num);
			}
		}

		$com_itemid = $comment->getVar('com_itemid');

		// execute updateStat callback function if set
		if (isset($comment_config['callback']['update']) && trim($comment_config['callback']['update']) != '') {
			$skip = false;
			if (!function_exists($comment_config['callback']['update'])) {
				if (isset($comment_config['callbackFile'])) {
					$callbackfile = trim($comment_config['callbackFile']);
					if ($callbackfile != ''
						&& file_exists(ICMS_MODULES_PATH . '/' . $moddir . '/' . $callbackfile)) {
						include_once ICMS_MODULES_PATH . '/' . $moddir . '/' . $callbackfile;
					}
					if (!function_exists($comment_config['callback']['update'])) {
						$skip = true;
					}
				} else {
					$skip = true;
				}
			}
			if (!$skip) {
				$criteria = new icms_db_criteria_Compo(new icms_db_criteria_Item('com_modid', $com_modid));
				$criteria->add(new icms_db_criteria_Item('com_itemid', $com_itemid));
				$criteria->add(new icms_db_criteria_Item('com_status', XOOPS_COMMENT_ACTIVE));
				$comment_count = $comment_handler->getCount($criteria);
				$comment_config['callback']['update']($com_itemid, $comment_count);
			}
		}

		include ICMS_ROOT_PATH . '/header.php';
		icms_core_Message::result($msgs);
		echo '<br /><a href="' . $redirect_page . '=' . $com_itemid . '&amp;com_order=' . $com_order . '&amp;com_mode=' . $com_mode . '">' . _BACK . '</a>';
		include ICMS_ROOT_PATH . '/footer.php';
		break;

	case 'delete':
	default:
		include ICMS_ROOT_PATH . '/header.php';
		$comment_confirm = array('com_id' => $com_id, 'com_mode' => $com_mode, 'com_order' => $com_order, 'op' => array(_CM_DELETEONE => 'delete_one', _CM_DELETEALL => 'delete_all'));
		if (!empty($comment_confirm_extra) && is_array($comment_confirm_extra)) {
			$comment_confirm = $comment_confirm + $comment_confirm_extra;
		}
		icms_core_Message::confirm($comment_confirm, 'comment_delete.php', _CM_DELETESELECT);
		include ICMS_ROOT_PATH . '/footer.php';
		break;
}
