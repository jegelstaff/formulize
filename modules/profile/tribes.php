<?php
/**
* Tribes page
*
* @copyright	GNU General Public License (GPL)
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @since		1.3
* @author		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
* @package		profile
* @version		$Id: tribes.php 22413 2011-08-27 10:21:21Z phoenyx $
*/

/**
 * Edit a Tribeuser
 *
 * @param object $tribesObj mod_profile_Tribes object
 * @param bool $hideForm true if form should be hidden
 * @global mod_profile_TribeuserHandler $profile_tribeuser_handler tribeuser handler
 * @global obj $icmsTpl template
 * @global int $real_uid user id of treated or own user
 * @return void
 */
function edittribeuser($tribesObj, $hideForm = false) {
	global $profile_tribeuser_handler, $icmsTpl, $real_uid;

	// general check
	if (!$profile_tribeuser_handler->userCanSubmit()) return;

	// check tribe security level
	if ($tribesObj->getVar('security') == PROFILE_TRIBES_SECURITY_EVERYBODY || $tribesObj->getVar('security') == PROFILE_TRIBES_SECURITY_APPROVAL) {
		// don't show the form if the current user is the owner of this tribe
		if ($real_uid == $tribesObj->getVar('uid_owner')) return;
		// don't show the form if the user is already a member of this tribe
		$tribeuser_id = $profile_tribeuser_handler->getTribeuserId($tribesObj->getVar('tribes_id'), icms::$user->getVar('uid'));
		if ($tribeuser_id > 0) return;
		$tribeuserObj = $profile_tribeuser_handler->get(0);
		$tribeuserObj->hideFieldFromForm('user_id');
		if ($tribesObj->getVar('security') == PROFILE_TRIBES_SECURITY_APPROVAL) {
			$tribeuserObj->setVar('approved', 0);
		}
	} elseif ($tribesObj->getVar('security') == PROFILE_TRIBES_SECURITY_INVITATION) {
		// don't show the form if the user isn't the owner of the tribe
		if ($real_uid != $tribesObj->getVar('uid_owner')) return;
		$tribeuserObj = $profile_tribeuser_handler->get(0);
		$tribeuserObj->setVar('accepted', 0);
	} else {
		return;
	}

	$tribeuserObj->hideFieldFromForm(array('tribe_id', 'approved', 'accepted'));
	$tribeuserObj->setVar('tribe_id', $tribesObj->getVar('tribes_id'));
	$tribeuserObj->setVar('user_id', icms::$user->getVar('uid'));

	$formtitle = ($real_uid == $tribesObj->getVar('uid_owner')) ? _MD_PROFILE_TRIBEUSER_SUBMIT : _MD_PROFILE_TRIBEUSER_JOIN;
	$sform = $tribeuserObj->getSecureForm($hideForm ? '' : $formtitle, 'addtribeuser');
	$sform->assign($icmsTpl, 'profile_tribeuserform');
	$icmsTpl->assign('hideForm', $hideForm);
	$icmsTpl->assign('lang_tribeuserform_title', $formtitle);
}

/**
 * Edit a Tribe
 *
 * @param object $tribesObj mod_profile_Tribes object to be edited
 * @param bool $hideForm true if the form should be hidden
 * @global mod_profile_TribesHandler $profile_tribes_handler tribes handler
 * @global obj $icmsTpl template object
 * @return void
 */
function edittribes($tribesObj, $hideForm=false) {
	global $profile_tribes_handler, $icmsTpl;

	$icmsTpl->assign('hideForm', $hideForm);
	if (!$tribesObj->isNew()){
		if (!$tribesObj->userCanEditAndDelete()) redirect_header($tribesObj->getItemLink(true), 3, _NOPERM);
		$tribesObj->hideFieldFromForm(array('creation_time', 'uid_owner', 'meta_keywords', 'meta_description', 'short_url'));
		$sform = $tribesObj->getSecureForm($hideForm ? '' : _MD_PROFILE_TRIBES_EDIT, 'addtribes');
		$sform->assign($icmsTpl, 'profile_tribesform');
		$icmsTpl->assign('lang_tribesform_title', _MD_PROFILE_TRIBES_EDIT);
	} else {
		if (!$profile_tribes_handler->userCanSubmit()) redirect_header(PROFILE_URL, 3, _NOPERM);
		$tribesObj->setVar('uid_owner', icms::$user->getVar('uid'));
		$tribesObj->setVar('creation_time', date(_DATESTRING));
		$tribesObj->hideFieldFromForm(array('creation_time', 'uid_owner', 'meta_keywords', 'meta_description', 'short_url'));
		$sform = $tribesObj->getSecureForm($hideForm ? '' : _MD_PROFILE_TRIBES_SUBMIT, 'addtribes');
		$sform->assign($icmsTpl, 'profile_tribesform');
		$icmsTpl->assign('lang_tribesform_title', _MD_PROFILE_TRIBES_SUBMIT);
	}
}

/**
 * Edit a tribe topic
 *
 * @param int $tribetopic_id id of tribe topic
 * @param int $tribepost_id id of tribe post to be edited
 * @param object $tribesObj mod_profile_Tribes object
 * @param bool $hideForm
 * @global mod_profile_TribetopicHandler $profile_tribetopic_handler tribetopic handler
 * @global mod_profile_TribepostHandler $profile_tribepost_handler tribepost handler
 * @global obj $icmsTpl template object
 * @global bool $isOwner true if current user is owner of this tribe
 * @return void
 */
function edittribepost($tribetopic_id, $tribepost_id, $tribesObj, $hideForm = false) {
	global $profile_tribetopic_handler, $profile_tribepost_handler, $icmsTpl, $isOwner;

	if (!is_object(icms::$user)) return false;

	$icmsTpl->assign('hideForm', $hideForm);
	$tribepostObj = $profile_tribepost_handler->get($tribepost_id);
	if ($tribepostObj->isNew()) {
		if ($tribetopic_id > 0) {
			$formtitle = _MD_PROFILE_TRIBEPOST_SUBMIT;
			$tribepostObj->setFieldAsRequired('title', false);
			$tribepostObj->setVar('topic_id', $tribetopic_id);
		} else {
			$formtitle = _MD_PROFILE_TRIBETOPIC_SUBMIT;
		}
		$tribepostObj->setVar('tribes_id', $tribesObj->getVar('tribes_id'));
		$tribepostObj->setVar('poster_uid', icms::$user->getVar('uid'));
		$tribepostObj->setVar('post_time', date(_DATESTRING));

		$tribepostObj->hideFieldFromForm(array('meta_keywords', 'meta_description', 'short_url'));
		if (icms::$user->getVar('attachsig')) {
			$tribepostObj->hideFieldFromForm('attachsig');
		} else {
			$tribepostObj->setVar('attachsig', 0);
		}

		$sform = $tribepostObj->getSecureForm($hideForm ? '' : $formtitle, 'addtribepost');
		$sform->assign($icmsTpl, 'profile_addpostform');
		$icmsTpl->assign('lang_addpostform_title', $formtitle);
	} else {
		$tribetopicObj = $profile_tribetopic_handler->get($tribetopic_id);
		// check permissions
		if (!($tribepostObj->userCanEditAndDelete() || $isOwner)) redirect_header(icms_getPreviousPage('index.php'), 3, _NOPERM);

		// set topic or post specific options
		if ($tribetopicObj->getVar('post_id') == $tribepost_id) {
			$formtitle = _MD_PROFILE_TRIBETOPIC_EDIT;
		} else {
			$formtitle = _MD_PROFILE_TRIBEPOST_EDIT;
			$tribepostObj->setFieldAsRequired('title', false);
		}

		$tribepostObj->hideFieldFromForm(array('meta_keywords', 'meta_description', 'short_url'));
		if (icms::$user->getVar('attachsig')) {
			$tribepostObj->hideFieldFromForm('attachsig');
		} else {
			$tribepostObj->setVar('attachsig', 0);
		}
		$sform = $tribepostObj->getSecureForm($hideForm ? '' : $formtitle, 'addtribepost');
		$sform->assign($icmsTpl, 'profile_editpostform');
		$icmsTpl->assign('lang_editpostform', $formtitle);
	}
}

$profile_template = 'profile_tribes.html';
include_once 'header.php';

$profile_tribes_handler = icms_getModuleHandler('tribes', basename(dirname(__FILE__)), 'profile');
$profile_tribeuser_handler = icms_getModuleHandler('tribeuser', basename(dirname(__FILE__)), 'profile');
$profile_tribetopic_handler = icms_getModuleHandler('tribetopic', basename(dirname(__FILE__)), 'profile');
$profile_tribepost_handler = icms_getModuleHandler('tribepost', basename(dirname(__FILE__)), 'profile');

/** Use a naming convention that indicates the source of the content of the variable */
$clean_op = '';
if (isset($_GET['op'])) $clean_op = $_GET['op'];
if (isset($_POST['op'])) $clean_op = $_POST['op'];
$clean_tribes_id = 0;
if (isset($_GET['tribes_id'])) $clean_tribes_id = (int)$_GET['tribes_id'];
if (isset($_POST['tribes_id'])) $clean_tribes_id = (int)$_POST['tribes_id'];

/** Again, use a naming convention that indicates the source of the content of the variable */
$real_uid = is_object(icms::$user) ? (int)icms::$user->getVar('uid') : 0;
$clean_uid = isset($_GET['uid']) ? (int)$_GET['uid'] : $real_uid ;
$tribesObj = $profile_tribes_handler->get($clean_tribes_id);
$userCanEditAndDelete = $real_uid && (($clean_tribes_id && $real_uid == $tribesObj->getVar('uid_owner')) || (!$clean_tribes_id && $real_uid == $uid));

$isAllowed = $profile_configs_handler->userCanAccessSection('tribes', $clean_uid);
if (!$isAllowed || !icms::$module->config['enable_tribes']) redirect_header(icms_getPreviousPage('index.php'), 3, _NOPERM);

// we have to rewrite isOwner here (previously defined in header.php) because we don't pass the uid every time we are running through this code
if (!$tribesObj->isNew()) {
	if (!is_object(icms::$user)) $isOwner = false;
	if (is_object(icms::$user)) $isOwner = $tribesObj->getVar('uid_owner') == icms::$user->getVar('uid');
	$icmsTpl->assign('isOwner', $isOwner);
}

$icmsTpl->assign('uid_owner', $uid);
$icmsTpl->assign('profile_category_path', _MD_PROFILE_TRIBES);

/** Create a whitelist of valid values, be sure to use appropriate types for each value
 * Be sure to include a value for no parameter, if you have a default condition
 */
$valid_op = array('mod', 'addtribeuser', 'edittribeuser', 'deltribeuser', 'addtribepost', 'edittribepost', 'deltribepost', 'toggleclose', 'addtribes', 'del', '');

/** Only proceed if the supplied operation is a valid operation */
if (in_array($clean_op,$valid_op,true)){
	switch ($clean_op) {
		case "mod":
			if ($clean_tribes_id > 0 && $tribesObj->isNew()) redirect_header(icms_getPreviousPage('index.php'), 3, _NOPERM);
			edittribes($tribesObj);
			break;
		case "addtribeuser":
			if (!icms::$security->check()) redirect_header(icms_getPreviousPage('index.php'), 3, _MD_PROFILE_SECURITY_CHECK_FAILED . implode('<br />', icms::$security->getErrors()));
			$controller = new icms_ipf_Controller($profile_tribeuser_handler);
			$controller->storeFromDefaultForm(_MD_PROFILE_TRIBEUSER_CREATED, _MD_PROFILE_TRIBEUSER_MODIFIED);
			break;
		case "edittribeuser":
			if (!icms::$security->check()) redirect_header(icms_getPreviousPage('index.php'), 3, _MD_PROFILE_SECURITY_CHECK_FAILED . implode('<br />', icms::$security->getErrors()));
			if ($tribesObj->isNew()) redirect_header(icms_getPreviousPage('index.php'), 3, _NOPERM);
			$clean_tribeuser_id = isset($_POST['tribeuser_id']) ? (int)$_POST['tribeuser_id'] : 0;
			$tribeuserObj = $profile_tribeuser_handler->get($clean_tribeuser_id);
			if ($tribeuserObj->isNew()) redirect_header(icms_getPreviousPage('index.php'), 3, _MD_PROFILE_TRIBEUSER_NOTFOUND);
			$store = isset($_POST['store']) ? (int)$_POST['store'] : 0;
			$clean_action = isset($_POST['action']) ? $_POST['action'] : '';
			$valid_action = array ('approved', 'accepted');
			if (in_array($clean_action, $valid_action, true)) {
				if (($clean_action == 'approved' && $real_uid != $tribesObj->getVar('uid_owner')) || ($clean_action == 'accepted' && $real_uid != $tribeuserObj->getVar('user_id'))) redirect_header(icms_getPreviousPage('index.php'), 3, _NOPERM);
				if ($store == 1) {
					$tribeuserObj->setVar($clean_action, 1);
					$profile_tribeuser_handler->insert($tribeuserObj);
				} else {
					// delete this tribeuser object
					$profile_tribeuser_handler->delete($tribeuserObj);
				}
				redirect_header(icms_getPreviousPage('index.php'), 3, _MD_PROFILE_TRIBEUSER_OP_SUCCESS);
			} else {
				redirect_header(icms_getPreviousPage('index.php'), 3, _NOPERM);
			}
			break;
		case "deltribeuser":
			$clean_tribeuser_id = isset($_POST['tribeuser_id']) ? (int)$_POST['tribeuser_id'] : 0;
			$tribeuserObj = $profile_tribeuser_handler->get($clean_tribeuser_id);
			if (!$tribeuserObj->userCanEditAndDelete() && !$userCanEditAndDelete) redirect_header(icms_getPreviousPage('index.php'), 3, _NOPERM);
			if (!icms::$security->check()) redirect_header(icms_getPreviousPage('index.php'), 3, _MD_PROFILE_SECURITY_CHECK_FAILED . implode('<br />', icms::$security->getErrors()));
			$controller = new icms_ipf_Controller($profile_tribeuser_handler);
			$controller->handleObjectDeletionFromUserSide();
			break;
		case "addtribepost":
			if (!icms::$security->check()) redirect_header(icms_getPreviousPage('index.php'), 3, _MD_PROFILE_SECURITY_CHECK_FAILED . implode('<br />', icms::$security->getErrors()));
			
			$clean_post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
			$clean_topic_id = isset($_POST['topic_id']) ? (int)$_POST['topic_id'] : 0;
			$clean_tribes_id = isset($_POST['tribes_id']) ? (int)$_POST['tribes_id'] : 0;
			
			// check permissions and set redirect page
			if ($clean_topic_id == 0 || ($clean_topic_id > 0 && $clean_post_id == 0)) {
				// new topic or post
				if (!is_object(icms::$user) || !$tribesObj->isMember($real_uid)) redirect_header(icms_getPreviousPage('index.php'), 3, _NOPERM);
				$tribetopicObj = $profile_tribetopic_handler->get($clean_topic_id);
				if ($clean_topic_id > 0) {
					if ($tribetopicObj->getVar('closed')) redirect_header(icms_getPreviousPage('index.php'), 3, _NOPERM);
					$start = '';
					if ($tribetopicObj->getVar('replies') + 1 > icms::$module->config['tribepostsperpage'])
						$start = '&amp;start='.(($tribetopicObj->getVar('replies') + 1) - (($tribetopicObj->getVar('replies') + 1) % icms::$module->config['tribepostsperpage']));
					$redirect_page = $tribetopicObj->handler->_moduleUrl.'tribes.php?tribes_id='.$tribetopicObj->getVar('tribes_id').'&amp;topic_id='.$tribetopicObj->getVar('topic_id').$start.'#post'.$tribetopicObj->getVar("last_post_id");;
				} else {
					$redirect_page = $tribetopicObj->handler->_moduleUrl.'tribes.php?tribes_id='.$clean_tribes_id;
				}
			} else {
				// existing topic or post
				$tribepostObj = $profile_tribepost_handler->get($clean_post_id);
				if ($tribepostObj->isNew() || !($tribepostObj->userCanEditAndDelete() || $isOwner)) redirect_header(icms_getPreviousPage('index.php'), 3, _NOPERM);
				$redirect_page = $tribepostObj->handler->_moduleUrl.'tribes.php?tribes_id='.$tribepostObj->getVar('tribes_id').'&amp;topic_id='.$tribepostObj->getVar('topic_id');
			}

			// set redirect messages based on topic id > 0
			if ($clean_topic_id > 0) {
				$createdmessage = _MD_PROFILE_TRIBEPOST_CREATED;
				$modifiedmessage = _MD_PROFILE_TRIBEPOST_MODIFIED;
			} else {
				$createdmessage = _MD_PROFILE_TRIBETOPIC_CREATED;
				$modifiedmessage = _MD_PROFILE_TRIBETOPIC_MODIFIED;
			}

			$controller = new icms_ipf_Controller($profile_tribepost_handler);
			$controller->storeFromDefaultForm($createdmessage, $modifiedmessage, $redirect_page);
			break;
		case "edittribepost":
			$clean_topic_id = isset($_GET['topic_id']) ? (int)$_GET['topic_id'] : 0;
			$clean_post_id = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;
			edittribepost($clean_topic_id, $clean_post_id, $tribesObj);
			$icmsTpl->assign('profile_category_path', '<a href="'.$tribesObj->handler->_moduleUrl.$tribesObj->handler->_page.'?uid='.$uid.'">'._MD_PROFILE_TRIBES.'</a> &raquo;&raquo; <a href="'.$tribesObj->handler->_moduleUrl.$tribesObj->handler->_page.'?tribes_id='.$clean_tribes_id.'">'.$tribesObj->getVar('title').'</a>');
			break;
		case "deltribepost":
			$clean_post_id = 0;
			if (isset($_GET['post_id'])) $clean_post_id = (int)$_GET['post_id'];
			if (isset($_POST['post_id'])) $clean_post_id = (int)$_POST['post_id'];
			$tribepostObj = $profile_tribepost_handler->get($clean_post_id);
			if ($tribepostObj->isNew() || !($tribepostObj->userCanEditAndDelete() || $isOwner)) redirect_header(icms_getPreviousPage('index.php'), 3, _NOPERM);
			if (isset($_POST['confirm']) && !icms::$security->check()) redirect_header(icms_getPreviousPage('index.php'), 3, _MD_PROFILE_SECURITY_CHECK_FAILED . implode('<br />', icms::$security->getErrors()));
			$controller = new icms_ipf_Controller($profile_tribepost_handler);
			$controller->handleObjectDeletionFromUserSide(false, 'deltribepost');
			break;
		case "toggleclose":
			$clean_topic_id = isset($_GET['topic_id']) ? (int)$_GET['topic_id'] : 0;
			$tribetopicObj = $profile_tribetopic_handler->get($clean_topic_id);
			if ($tribetopicObj->isNew() || !($tribetopicObj->userCanEditAndDelete() || $isOwner)) redirect_header(icms_getPreviousPage('index.php'), 3, _NOPERM);
			if ($tribetopicObj->toggleClose()) {
				redirect_header(icms_getPreviousPage('index.php'), 3, _MD_PROFILE_TRIBETOPIC_MODIFIED);
			} else {
				redirect_header(icms_getPreviousPage('index.php'), 3, _CO_ICMS_SAVE_ERROR.$tribetopicObj->getHtmlErrors());
			}
			break;
		case "addtribes":
			if (!icms::$security->check()) redirect_header(icms_getPreviousPage('index.php'), 3, _MD_PROFILE_SECURITY_CHECK_FAILED.implode('<br />', icms::$security->getErrors()));
			$controller = new icms_ipf_Controller($profile_tribes_handler);
			$controller->storeFromDefaultForm(_MD_PROFILE_TRIBES_CREATED, _MD_PROFILE_TRIBES_MODIFIED, $tribesObj->isNew() ? PROFILE_URL.basename(__FILE__) : $tribesObj->getItemLink(true));
			break;
		case "del":
			if (!$tribesObj->userCanEditAndDelete()) redirect_header($tribesObj->getItemLink(true), 3, _NOPERM);
			if (isset($_POST['confirm']) && !icms::$security->check()) redirect_header(icms_getPreviousPage('index.php'), 3, _MD_PROFILE_SECURITY_CHECK_FAILED . implode('<br />', icms::$security->getErrors()));
			$controller = new icms_ipf_Controller($profile_tribes_handler);
			$controller->handleObjectDeletionFromUserSide();
			break;
		default:
			if ($userCanEditAndDelete) edittribes($tribesObj, true);
			if ($clean_tribes_id > 0) {
				if ($tribesObj->isNew()) redirect_header(icms_getPreviousPage('index.php'), 3, _MD_PROFILE_TRIBES_NOTFOUND);

				$icmsTpl->assign('profile_category_path', '<a href="'.$tribesObj->handler->_moduleUrl.$tribesObj->handler->_page.'?uid='.$uid.'">'._MD_PROFILE_TRIBES.'</a>');

				// make tribe form
				edittribeuser($tribesObj, true);

				$clean_topic_id = isset($_GET['topic_id']) ? (int)$_GET['topic_id'] : 0;
				$clean_start = isset($_GET['start']) ? (int)$_GET['start'] : 0;

				$profile_tribes_handler->updateCounter($clean_tribes_id);
				$tribe = $tribesObj->toArray();
				$icmsTpl->assign('profile_tribe', $tribe);
				$tribeOwner = array('tribeuser_avatar'      => $tribe['tribe_sender_avatar'],
								    'tribeuser_sender_link' => $tribe['tribe_sender_link'],
								    'owner'					=> TRUE);
				$tribeMembers = $profile_tribeuser_handler->getTribeusers(0, 0, false, false, $clean_tribes_id, '=', 1, 1);
				$tribeMembers = array_merge(array($tribeOwner), $tribeMembers);
				$icmsTpl->assign('profile_tribe_members', $tribeMembers);
				$icmsTpl->assign('userCanEditAndDelete', $userCanEditAndDelete);
				$icmsTpl->assign('delete_image', ICMS_IMAGES_SET_URL."/actions/editdelete.png");
				$showContent = $tribesObj->isMember($real_uid);
				$icmsTpl->assign('showContent', $showContent);

				icms_makeSmarty(array(
					'lang_members'       => _MD_PROFILE_TRIBES_MEMBERS,
					'lang_topics'        => _MD_PROFILE_TRIBES_TOPICS,
					'lang_discussions'   => _MD_PROFILE_TRIBES_DISCUSSIONS,
					'lang_creation_time' => _MD_PROFILE_TRIBES_CREATION_TIME,
					'lang_views'         => _MD_PROFILE_TRIBES_VIEWS,
					'lang_owner'         => _MD_PROFILE_TRIBES_OWNER,
					'lang_delete'        => _DELETE
				));

				$total_topics_count = $profile_tribetopic_handler->getCount(new icms_db_criteria_Compo(new icms_db_criteria_Item('tribes_id', $clean_tribes_id)));
				$icmsTpl->assign('profile_tribe_topics_count', $total_topics_count);

				if ($showContent) {
					if ($clean_topic_id <= 0) {
						// no topic selected, show list of all topics
						$icmsTpl->assign('profile_tribe_topics', $profile_tribetopic_handler->getTopics($clean_start, icms::$module->config['tribetopicsperpage'], false, $tribesObj->getVar('tribes_id')));
						// make page navigation
						$pagenav = new icms_view_PageNav($total_topics_count, icms::$module->config['tribetopicsperpage'], $clean_start, 'start', 'tribes_id='.$clean_tribes_id);
						$icmsTpl->assign('profile_tribe_topics_pagenav', $pagenav->renderNav());
						// make form
						edittribepost(0, 0, $tribesObj, true);

						icms_makeSmarty(array(
							'lang_topic_title'          => _MD_PROFILE_TRIBETOPIC_TITLE,
							'lang_topic_author'         => _MD_PROFILE_TRIBETOPIC_AUTHOR,
							'lang_topic_replies'        => _MD_PROFILE_TRIBETOPIC_REPLIES,
							'lang_topic_views'          => _MD_PROFILE_TRIBETOPIC_VIEWS,
							'lang_topic_last_post_time' => _MD_PROFILE_TRIBETOPIC_LAST_POST_TIME
						));
					} else {
						// topic selected, show list of posts
						$tribetopicObj = $profile_tribetopic_handler->get($clean_topic_id);
						if ($tribetopicObj->isNew()) redirect_header(icms_getPreviousPage('index.php'), 3, _MD_PROFILE_TRIBETOPIC_NOTFOUND);

						$tribetopicObj->incrementViews();
						$total_posts_count = $profile_tribepost_handler->getCount(new icms_db_criteria_Compo(new icms_db_criteria_Item('topic_id', $clean_topic_id)));

						$icmsTpl->assign('profile_category_path', '<a href="'.$tribesObj->handler->_moduleUrl.$tribesObj->handler->_page.'?uid='.$uid.'">'._MD_PROFILE_TRIBES.'</a> &raquo;&raquo; <a href="'.$tribesObj->handler->_moduleUrl.$tribesObj->handler->_page.'?tribes_id='.$clean_tribes_id.'">'.$tribesObj->getVar('title').'</a>');
						$icmsTpl->assign('profile_tribe_topic', $tribetopicObj->toArray());
						$icmsTpl->assign('profile_tribe_posts', $profile_tribepost_handler->getPosts($clean_start, icms::$module->config['tribepostsperpage'], false, $clean_topic_id));
						// make page navigation
						$pagenav = new icms_view_PageNav($total_posts_count, icms::$module->config['tribepostsperpage'], $clean_start, 'start', 'tribes_id='.$clean_tribes_id.'&topic_id='.$clean_topic_id);
						$icmsTpl->assign('profile_tribe_posts_pagenav', $pagenav->renderNav());
						// make form
						if (!$tribetopicObj->getVar('closed')) edittribepost($clean_topic_id, 0, $tribesObj, true);

						icms_makeSmarty(array(
							'lang_closed' => _MD_PROFILE_TRIBETOPIC_CLOSED,
						));
					}
				} else {
					$icmsTpl->assign('lang_joinfirst', _MD_PROFILE_TRIBES_JOINFIRST);
				}
			} elseif (isset($_POST['search_title'])) {
				$clean_tribes_title = trim(icms_core_DataFilter::checkVar($_POST['search_title'], 'str'));
				$tribes = array();
				$tribes['search'] = $profile_tribes_handler->searchTribes($clean_tribes_title);
				$icmsTpl->assign('profile_tribes', $tribes);
				$icmsTpl->assign('lang_tribes_search_title', sprintf(_MD_PROFILE_TRIBES_SEARCH_TITLE, $clean_tribes_title));
				if (count($tribes['search']) == 0) $icmsTpl->assign('lang_search_noresults', sprintf(_MD_PROFILE_TRIBES_SEARCH_NORESULTS, $clean_tribes_title));
				$icmsTpl->assign('profile_tribes_search', true);
			} elseif ($clean_uid > 0 || $real_uid > 0) {
				$uid = ($clean_uid > 0) ? $clean_uid : $real_uid;

				$tribes = array();
				$tribes['own'] = $profile_tribes_handler->getTribes(false, false, $uid);
				$tribes['member'] = $profile_tribes_handler->getMembershipTribes($uid);
				if ((count($tribes['own']) + count($tribes['member'])) == 0) {
					$icmsTpl->assign('lang_nocontent', _MD_PROFILE_TRIBES_NOCONTENT);
				} else {
					$icmsTpl->assign('profile_tribes', $tribes);
				}
				$icmsTpl->assign('profile_tribes_search', true);
			} else {
				redirect_header(PROFILE_URL);
			}

			icms_makeSmarty(array(
				'lang_tribes_own'           => _MD_PROFILE_TRIBES_OWN,
				'lang_tribes_membership'    => _MD_PROFILE_TRIBES_MEMBERSHIPS,
				'lang_tribes_search'        => _MD_PROFILE_TRIBES_SEARCH,
				'lang_tribes_search_submit' => _SEARCH,
				'rowitems'                  => icms::$module->config['rowitems'],
				'itemwidth'                 => round(100 / icms::$module->config['rowitems'], 0)
			));

			/**
			 * Generating meta information for this page
			 */
			$icms_metagen = new icms_ipf_Metagen($tribesObj->getVar('title'), $tribesObj->getVar('meta_keywords','n'), $tribesObj->getVar('meta_description', 'n'));
			$icms_metagen->createMetaTags();

			break;
	}
}

include_once 'footer.php';