<?php

// only webmasters can interact with this page!
global $xoopsUser;
if(!$xoopsUser OR !in_array(XOOPS_GROUP_ADMIN, $xoopsUser->getGroups())) {
    return;
}

// if user has submitted all we need to send the message...
if(isset($_POST['body']) AND
    $_POST['body'] AND $_POST['subject'] AND $_POST['groups']) {
    
    // setup mailer
    $xoopsMailer = getMailer();
    $xoopsMailer->useMail();    
    $xoopsMailer->setTemplateDir(XOOPS_ROOT_PATH."/cache/");
    $xoopsMailer->setTemplate('tempmailbody.tpl');
	$member_handler = xoops_gethandler('member');
    // gather emails
    $emails = array();    
    foreach($_POST['groups'] as $group_id) {
        $users = $member_handler->getUsersByGroup($group_id, true); // true returns users as objects
        foreach($users as $user) {
            $emails[] = $user->getVar('email');
        }
    }
    $xoopsMailer->setToEmails($emails);
    $xoopsMailer->setSubject($_POST['subject']);
    
    ob_start();
    
    // add attachment if one specified
    $attachment = $_FILES['attachment']['error'] == UPLOAD_ERR_NO_FILE ? false : true;
    $attachmentSuccess = false;
    if($_FILES['attachment']['error'] == 0) {
        if($moveResult = move_uploaded_file($_FILES['attachment']['tmp_name'],XOOPS_ROOT_PATH."/cache/".$_FILES['attachment']['name'])) {
            $attachmentSuccess = true;
            $xoopsMailer->addAttachment(XOOPS_ROOT_PATH."/cache/".$_FILES['attachment']['name']);
        } else {
            $value = _AM_UPLOAD_LOST;
            print "<p><b>$value</b></p>";
        }
    } else {
        switch($_FILES['attachment']['error']) {
            case UPLOAD_ERR_INI_SIZE:
                $value = _AM_UPLOAD_ERR_INI_SIZE;
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $value = _AM_UPLOAD_ERR_FORM_SIZE . 'Attachment'."'";
                break;
            case UPLOAD_ERR_PARTIAL:
                $value = _AM_UPLOAD_ERR_PARTIAL;
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $value = _AM_UPLOAD_ERR_NO_TMP_DIR;
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $value = _AM_UPLOAD_ERR_CANT_WRITE;
                break;
            case UPLOAD_ERR_EXTENSION:
                $value = _AM_UPLOAD_ERR_EXTENSION;
                break;
        }
        print "<p><b>$value</b></p>";
    }
    
    if(!$attachment OR $attachmentSuccess) {
        file_put_contents(XOOPS_ROOT_PATH."/cache/tempmailbody.tpl", $_POST['body']);
        if(!$success = $xoopsMailer->send()) {
            print $xoopsMailer->getErrors();
        } else {
            print "<b>Mail sent to:</b><br>".implode("<br>", $emails);
        }
        // cleanup files
        unlink(XOOPS_ROOT_PATH."/cache/tempmailbody.tpl");
        if($attachmentSuccess) {
            unlink(XOOPS_ROOT_PATH."/cache/".$_FILES['attachment']['name']);
        }
    }
    
    $mailStatus = ob_get_clean();
    
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
