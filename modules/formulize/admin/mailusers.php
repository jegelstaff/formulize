<?php

// only webmasters can interact with this page!
global $xoopsUser, $xoopsConfig;
if(!$xoopsUser OR !in_array(XOOPS_GROUP_ADMIN, $xoopsUser->getGroups())) {
    return;
}

// if user has submitted all we need to send the message...
if(isset($_POST['body']) AND
    $_POST['body'] AND $_POST['subject'] AND $_POST['groups']) {

		$mailStatus = '';

		// Setup the users we're going to send to
		$uids_to_notify = array();
		if(!isset($_POST['include']) OR $_POST['include'] == 'all') {
			foreach($_POST['groups'] as $group_id) {
				$users = $member_handler->getUsersByGroup($group_id, true); // true returns users as objects
				foreach($users as $user) {
					$uids_to_notify[] = $user->getVar('uid');
				}
			}
		} else {
			foreach($_POST['groups'] as $i=>$group_id) {
				$groupUsers = array();
				$users = $member_handler->getUsersByGroup($group_id, true); // true returns users as objects
				foreach($users as $user) {
						$groupUsers[] = $user->getVar('uid');
				}
				if($i==0) { $uids_to_notify = $groupUsers; } // seed it with values from first group
				$uids_to_notify = array_intersect($uids_to_notify, $groupUsers); // retain previous values only if they are present in this group
			}
		}
		$uids_to_notify = array_unique($uids_to_notify);

		// process any attachment
    $attachment = $_FILES['attachment']['error'] == UPLOAD_ERR_NO_FILE ? false : true;
    $attachmentSuccess = false;
		$attachmentStatus = '';
    if($_FILES['attachment']['error'] == 0) {
			if($moveResult = move_uploaded_file($_FILES['attachment']['tmp_name'],XOOPS_ROOT_PATH."/modules/formulize/temp/".$_FILES['attachment']['name'])) {
				formulize_scandirAndClean(XOOPS_ROOT_PATH.'/modules/formulize/temp/');
				$attachmentSuccess = true;
			} else {
				$attachmentStatus = _AM_UPLOAD_LOST;
			}
    } else {
			switch($_FILES['attachment']['error']) {
				case UPLOAD_ERR_INI_SIZE:
					$attachmentStatus = _AM_UPLOAD_ERR_INI_SIZE;
					break;
				case UPLOAD_ERR_FORM_SIZE:
					$attachmentStatus = _AM_UPLOAD_ERR_FORM_SIZE . 'uploaded files'."'";
					break;
				case UPLOAD_ERR_PARTIAL:
					$attachmentStatus = _AM_UPLOAD_ERR_PARTIAL;
					break;
				case UPLOAD_ERR_NO_TMP_DIR:
					$attachmentStatus = _AM_UPLOAD_ERR_NO_TMP_DIR;
					break;
				case UPLOAD_ERR_CANT_WRITE:
					$attachmentStatus = _AM_UPLOAD_ERR_CANT_WRITE;
					break;
				case UPLOAD_ERR_EXTENSION:
					$attachmentStatus = _AM_UPLOAD_ERR_EXTENSION;
					break;
			}
    }
		$extra_tags = array();
		if($attachment AND !$attachmentSuccess) {
			$mailStatus .= "<p><b>$attachmentStatus</b></p>";
		} elseif($attachment) {
			$extra_tags['ATTACHFILE-'.$_FILES['attachment']['name']] = XOOPS_ROOT_PATH."/modules/formulize/temp/".$_FILES['attachment']['name'];
		}

		if(!$attachment OR $attachmentSuccess) {

			// write the template to a file we will use when the notifications go out
			file_put_contents(XOOPS_ROOT_PATH."/modules/formulize/language/".$xoopsConfig['language']."/mail_template/tempmailbody.tpl", $_POST['body']);

			$event = 'new_entry'; // doesn't matter since we're sending direct mail with its own subject and template
			$extra_tags = $extra_tags;
			$fid = null; // when null, will use the first form in the DB to determine the available notification events, which will match the 'new_entry' event above
			$uids_to_notify = array_unique($uids_to_notify);
			$mid = getFormulizeModId();
			$omit_user = 0;
			$subject = strip_tags($_POST['subject']);
			$template = 'tempmailbody.tpl';
			formulize_processNotification($event, $extra_tags, $fid, $uids_to_notify, $mid, $omit_user, $subject, $template);

			$mailStatus .= "<p><b>Handled mail for ".count($uids_to_notify)." users.</b></p>";
		}
}

$member_handler = xoops_gethandler('member');
$criteria = new criteria();
$criteria->setSort('name');
$criteria->setOrder('ASC');
$groupObjects = $member_handler->getGroups($criteria);
$groupIds = array();
$groupNames = array();
foreach($groupObjects as $group) {
    $groupIds[] = $group->getVar('groupid');
    $groupNames[] = $group->getVar('name');
}

$adminPage['mailStatus'] = $mailStatus;
$adminPage['groupIds'] = $groupIds;
$adminPage['groupNames'] = $groupNames;
$adminPage['groupListSize'] = count((array) $groupNames) < 25 ? count((array) $groupNames) : 25;
$adminPage['template'] = "db:admin/mailusers.html";

$breadcrumbtrail[1]['url'] = "page=home";
$breadcrumbtrail[1]['text'] = "Home";
$breadcrumbtrail[2]['text'] = "Email Users";
