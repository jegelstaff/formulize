<?php
/**
 * Administration of comments, mainfile
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @package		Administration
 * @subpackage	Comments
 * @version		SVN: $Id: main.php 22529 2011-09-02 19:55:40Z phoenyx $
 */

if (!is_object(icms::$user) || !is_object($icmsModule) || !icms::$user->isAdmin($icmsModule->getVar('mid'))) {
	exit("Access Denied");
} else {
	if (!empty($_POST)) foreach ($_POST as $k => $v) ${$k} = StopXSS($v);
	if (!empty($_GET)) foreach ($_GET as $k => $v) ${$k} = StopXSS($v);
	$op = (isset($_GET['op'])) ? trim(filter_input(INPUT_GET, 'op'))
		: (isset($_POST['op']) ? trim(filter_input(INPUT_POST, 'op'))
		: 'list');

	switch ($op) {
		case 'list':
			include_once ICMS_ROOT_PATH . '/include/comment_constants.php';
			icms_loadLanguageFile('core', 'comment');
			$limit_array = array(10, 20, 50, 100);
			$status_array = array(
				XOOPS_COMMENT_PENDING => _CM_PENDING,
				XOOPS_COMMENT_ACTIVE => _CM_ACTIVE,
				XOOPS_COMMENT_HIDDEN => _CM_HIDDEN,
				);
			$status_array2 = array(
				XOOPS_COMMENT_PENDING => '<span style="text-decoration: none; font-weight: bold; color: #00ff00;">' . _CM_PENDING . '</span>',
				XOOPS_COMMENT_ACTIVE => '<span style="text-decoration: none; font-weight: bold; color: #ff0000;">' . _CM_ACTIVE . '</span>',
				XOOPS_COMMENT_HIDDEN => '<span style="text-decoration: none; font-weight: bold; color: #0000ff;">' . _CM_HIDDEN . '</span>',
				);
			$start = 0;
			$limit = 0;
			$otherorder = 'DESC';
			$comments = array();
			$status = (!isset($_GET['status']) || !in_array((int) $_GET['status'], array_keys($status_array))) ? 0 : (int) $_GET['status'];
			$module = !isset($_GET['module']) ? 0 : (int) $_GET['module'];
			$module_handler = icms::handler('icms_module');
			$module_array =& $module_handler->getList(new icms_db_criteria_Item('hascomments', 1));
			$comment_handler = icms::handler('icms_data_comment');
			$criteria = new icms_db_criteria_Compo();
			if ($status > 0) {
				$criteria->add(new icms_db_criteria_Item('com_status', $status));
			}
			if ($module > 0) {
				$criteria->add(new icms_db_criteria_Item('com_modid', $module));
			}
			$total = $comment_handler->getCount($criteria);
			if ($total > 0) {
				$start = isset($_GET['start']) ? (int) $_GET['start'] : 0;
				$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 0;
				if (!in_array($limit, $limit_array)) {
					$limit = 50;
				}
				$sort = (!isset($_GET['sort']) || !in_array($_GET['sort'], array('com_modid', 'com_status', 'com_created', 'com_uid', 'com_ip', 'com_title'))) ? 'com_id' : $_GET['sort'];
				if (!isset($_GET['order']) || $_GET['order'] != 'ASC') {
					$order = 'DESC';
					$otherorder = 'ASC';
				} else {
					$order = 'ASC';
					$otherorder = 'DESC';
				}
				$criteria->setSort($sort);
				$criteria->setOrder($order);
				$criteria->setLimit($limit);
				$criteria->setStart($start);
				$comments =& $comment_handler->getObjects($criteria, TRUE);
			}
			$form = '<form action="admin.php" method="get">';
			$form .= '<select name="module">';
			$module_array[0] = _MD_AM_ALLMODS;
			foreach ($module_array as $k => $v) {
				$sel = '';
				if ($k == $module) {
					$sel = ' selected="selected"';
				}
				$form .= '<option value="' . $k . '"' . $sel . '>' . $v . '</option>';
			}
			$form .= '</select>&nbsp;<select name="status">';
			$status_array[0] = _MD_AM_ALLSTATUS;
			foreach ($status_array as $k => $v) {
				$sel = '';
				if (isset($status) && $k == $status) {
					$sel = ' selected="selected"';
				}
				$form .= '<option value="' . $k . '"' . $sel . '>' . $v . '</option>';
			}
			$form .= '</select>&nbsp;<select name="limit">';
			foreach ($limit_array as $k) {
				$sel = '';
				if (isset($limit) && $k == $limit) {
					$sel = ' selected="selected"';
				}
				$form .= '<option value="' . $k . '"' . $sel . '>' . icms_conv_nr2local($k) . '</option>';
			}
			$form .= '</select>&nbsp;<input type="hidden" name="fct" value="comments" /><input type="submit" value="'
				. _GO . '" name="selsubmit" /></form>';

			icms_cp_header();
			echo '<div class="CPbigTitle" style="background-image: url(' . ICMS_URL . '/modules/system/admin/comments/images/comments_big.png)">'
				. _MD_AM_COMMMAN . '</div><br />';
			echo $form . "<br />";
			echo '<table width="100%" class="outer" cellspacing="1"><tr><th colspan="8">'
				. _MD_AM_LISTCOMM . '</th></tr><tr align="center"><td class="head">' . _MD_AM_MESSAGE_ICON . '</td><td class="head" align="'
				. _GLOBAL_LEFT . '"><a href="admin.php?fct=comments&amp;op=list&amp;sort=com_title&amp;order=' . $otherorder
				. '&amp;module=' . $module . '&amp;status=' . $status . '&amp;start=' . $start . '&amp;limit=' . $limit . '">' . _CM_TITLE
				. '</a></td><td class="head"><a href="admin.php?fct=comments&amp;op=list&amp;sort=com_created&amp;order=' . $otherorder
				. '&amp;module=' . $module . '&amp;status=' . $status . '&amp;start=' . $start . '&amp;limit=' . $limit . '">' . _CM_POSTED
				. '</a></td><td class="head"><a href="admin.php?fct=comments&amp;op=list&amp;sort=com_uid&amp;order=' . $otherorder
				. '&amp;module=' . $module . '&amp;status=' . $status . '&amp;start=' . $start . '&amp;limit=' . $limit . '">' . _CM_POSTER
				. '</a></td><td class="head"><a href="admin.php?fct=comments&amp;op=list&amp;sort=com_ip&amp;order=' . $otherorder
				. '&amp;module=' . $module . '&amp;status=' . $status . '&amp;start=' . $start . '&amp;limit=' . $limit
				. '">IP</a></td><td class="head"><a href="admin.php?fct=comments&amp;op=list&amp;sort=com_modid&amp;order=' . $otherorder
				. '&amp;module=' . $module . '&amp;status=' . $status . '&amp;start=' . $start . '&amp;limit=' . $limit . '">' . _MD_AM_MODULE
				. '</a></td><td class="head"><a href="admin.php?fct=comments&amp;op=list&amp;sort=com_status&amp;order=' . $otherorder
				. '&amp;module=' . $module . '&amp;status=' . $status . '&amp;start=' . $start . '&amp;limit=' . $limit . '">' . _CM_STATUS
				. '</a></td><td class="head">' . _MD_AM_ACTION . '</td></tr>';
			$class = 'even';
			foreach (array_keys($comments) as $i) {
				$class = ($class == 'odd') ? 'even' : 'odd';
				$poster_uname = $icmsConfig['anonymous'];
				if ($comments[$i]->getVar('com_uid') > 0) {
					$poster =& $member_handler->getUser($comments[$i]->getVar('com_uid'));
					if (is_object($poster)) {
						$poster_uname = '<a href="' . ICMS_URL . '/userinfo.php?uid=' . $comments[$i]->getVar('com_uid') . '">' . $poster->getVar('uname') . '</a>';
					}
				}
				$icon = $comments[$i]->getVar('com_icon');
				$icon = empty($icon )
					? '/images/icons/' . $GLOBALS["icmsConfig"]["language"] . '/no_posticon.gif'
					: ( '/images/subject/' . htmlspecialchars($icon, ENT_QUOTES ) );
				$icon = '<img src="' . ICMS_URL . $icon  . '" alt="" />';

				echo '<tr align="center"><td class="' . $class . '">' . $icon
					. '</td><td class="' . $class . '" align="' . _GLOBAL_LEFT
					. '"><a href="admin.php?fct=comments&amp;op=jump&amp;com_id=' . $i . '">'
					. $comments[$i]->getVar('com_title') . '</a></td><td class="' . $class . '">'
					. formatTimestamp($comments[$i]->getVar('com_created'), 'm') . '</td><td class="'
					. $class . '">' . $poster_uname . '</td><td class="' . $class . '">'
					. icms_conv_nr2local($comments[$i]->getVar('com_ip')) . '</td><td class="' . $class . '">'
					. $module_array[$comments[$i]->getVar('com_modid')] . '</td><td class="' . $class . '">'
					. $status_array2[$comments[$i]->getVar('com_status')] . '</td><td class="' . $class
					. '" align="' . _CENTER . '"><a href="admin/comments/comment_edit.php?com_id=' . $i . '"><img src="'. ICMS_IMAGES_SET_URL . '/actions/edit.png" alt="' . _EDIT . '" title="' . _EDIT . '" /></a> 
					<a href="admin/comments/comment_delete.php?com_id=' . $i . '"><img src="'. ICMS_IMAGES_SET_URL . '/actions/editdelete.png" alt="' . _DELETE . '" title="' . _DELETE . '" /></a></td></tr>';
			}
			echo '</table>';
			echo '<table style="width: 100%; border: 0; margin: 3px; padding: 3px;"><tr><td>'
				. sprintf(_MD_AM_COMFOUND, '<b>' . icms_conv_nr2local($total) . '</b>');
			if ($total > $limit) {
				$nav = new icms_view_PageNav($total, $limit, $start, 'start', 'fct=comments&amp;op=list&amp;limit=' . $limit . '&amp;sort=' . $sort . '&amp;order=' . $order . '&amp;module=' . $module);
				echo '</td><td align="center">' . $nav->renderNav();
			}
			echo '</td></tr></table>';
			icms_cp_footer();
			break;

		case 'jump':
			$com_id = (isset($_GET['com_id'])) ? (int) $_GET['com_id'] : 0;
			if ($com_id > 0) {
				$comment_handler = icms::handler('icms_data_comment');
				$comment =& $comment_handler->get($com_id);
				if (is_object($comment)) {
					$module_handler = icms::handler('icms_module');
					$module =& $module_handler->get($comment->getVar('com_modid'));
					$comment_config = $module->getInfo('comments');
					header('Location: ' . ICMS_URL . '/modules/' . $module->getVar('dirname') . '/' . $comment_config['pageName']
						. '?' . $comment_config['itemName'] . '=' . $comment->getVar('com_itemid') . '&com_id=' . $comment->getVar('com_id')
						. '&com_rootid=' . $comment->getVar('com_rootid') . '&com_mode=thread&' . str_replace('&amp;', '&', $comment->getVar('com_exparams')) . '#comment'
						. $comment->getVar('com_id'));
					exit();
				}
			}
			redirect_header('admin.php?fct=comments', 1);
			break;

		default:
			break;
	}

}

