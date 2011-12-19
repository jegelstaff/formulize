<?php
/**
* All functions for pm manager are going through here.
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package		core
* @since		XOOPS
* @author		http://www.xoops.org The XOOPS Project
* @author	   Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
* @version		$Id: pmlite.php 8768 2009-05-16 22:48:26Z pesianstranger $
*/

$xoopsOption['pagetype'] = "pmsg";

include "mainfile.php";
$reply = !empty($_GET['reply']) ? 1 : 0;
$send = !empty($_GET['send']) ? 1 : 0;
$send2 = !empty($_GET['send2']) ? 1 : 0;
$to_userid = !empty($_GET['to_userid']) ? intval($_GET['to_userid']) : 0;
$msg_id = !empty($_GET['msg_id']) ? intval($_GET['msg_id']) : 0;
if ( empty($_GET['refresh'] ) && isset($_POST['op']) && $_POST['op'] != "submit" ) {
    $jump = "pmlite.php?refresh=".time()."";
    if ( $send == 1 ) {
        $jump .= "&amp;send=".$send."";
    } elseif ( $send2 == 1 ) {
        $jump .= "&amp;send2=".$send2."&amp;to_userid=".$to_userid."";
    } elseif ( $reply == 1 ) {
        $jump .= "&amp;reply=".$reply."&amp;msg_id=".$msg_id."";
    } else {
    }
    echo "<html><head><meta http-equiv='Refresh' content='0; url=".$jump."' /></head><body></body></html>";
    exit();
}
xoops_header();
if ($icmsUser) {
    $myts =& MyTextSanitizer::getInstance();
    if (isset($_POST['op']) && $_POST['op'] == "submit") {
        if (!$GLOBALS['xoopsSecurity']->check()) {
            $security_error = true;
        }
        $res = $xoopsDB->query("SELECT COUNT(*) FROM ".$xoopsDB->prefix("users")." WHERE uid='".intval($_POST['to_userid'])."'");
        list($count) = $xoopsDB->fetchRow($res);
        if ($count != 1) {
            echo "<br /><br /><div><h4>"._PM_USERNOEXIST."<br />";
            echo _PM_PLZTRYAGAIN."</h4><br />";
            if (isset($security_error) && $security_error == true) {
                echo implode('<br />', $GLOBALS['xoopsSecurity']->getErrors());
            }
            echo "[ <a href='javascript:history.go(-1)'>"._PM_GOBACK."</a> ]</div>";
        } else {
            $pm_handler =& xoops_gethandler('privmessage');
            $pm =& $pm_handler->create();
            $pm->setVar("subject", $_POST['subject']);
            $pm->setVar("msg_text", $_POST['message']);
            $pm->setVar("to_userid", intval($_POST['to_userid']));
            $pm->setVar("from_userid", intval($icmsUser->getVar("uid")));
            if (!$pm_handler->insert($pm)) {
                echo $pm->getHtmlErrors();
                echo "<br /><a href='javascript:history.go(-1)'>"._PM_GOBACK."</a>";
            } else {
				// Send a Private Message email notification
				$userHandler =& xoops_gethandler('user');
				$toUser =& $userHandler->get(intval($_POST['to_userid']));
				// Only send email notif if notification method is mail
				if ($toUser->notify_method() == 2) {
					$xoopsMailer =& getMailer();
					$xoopsMailer->useMail();
					$xoopsMailer->setToEmails($toUser->email());
					if ($icmsUser->getVar('user_viewemail')) {
						$xoopsMailer->setFromEmail($icmsUser->email());
						$xoopsMailer->setFromName($icmsUser->uname());
					} else {
						$xoopsMailer->setFromEmail($icmsConfig['adminmail']);
						$xoopsMailer->setFromName($icmsConfig['sitename']);
					}
					$xoopsMailer->setTemplate('new_pm.tpl');
					$xoopsMailer->assign('X_SITENAME', $icmsConfig['sitename']);
					$xoopsMailer->assign('X_SITEURL', ICMS_URL."/");
					$xoopsMailer->assign('X_ADMINMAIL', $icmsConfig['adminmail']);
					$xoopsMailer->assign('X_UNAME', $toUser->uname());
					$xoopsMailer->assign('X_FROMUNAME', $icmsUser->uname());
					$xoopsMailer->assign('X_SUBJECT', $myts->stripSlashesGPC($_POST['subject']));
					$xoopsMailer->assign('X_MESSAGE', $myts->stripSlashesGPC($_POST['message']));
					$xoopsMailer->assign('X_ITEM_URL', ICMS_URL . "/viewpmsg.php");
					$xoopsMailer->setSubject(sprintf(_PM_MESSAGEPOSTED_EMAILSUBJ, $icmsConfig['sitename']));
					$xoopsMailer->send();
				}
                echo "<br /><br /><div style='text-align:center;'><h4>"._PM_MESSAGEPOSTED."</h4><br /><a href=\"javascript:window.opener.location='".ICMS_URL."/viewpmsg.php';window.close();\">"._PM_CLICKHERE."</a><br /><br /><a href=\"javascript:window.close();\">"._PM_ORCLOSEWINDOW."</a></div>";
            }
        }
    } elseif ($reply == 1 || $send == 1 || $send2 == 1) {
        include_once ICMS_ROOT_PATH."/class/xoopsformloader.php";
        if ($reply == 1) {
            $pm_handler =& xoops_gethandler('privmessage');
            $pm =& $pm_handler->get($msg_id);
            if ($pm->getVar("to_userid") == intval($icmsUser->getVar('uid'))) {
                $pm_uname = XoopsUser::getUnameFromId($pm->getVar("from_userid"));
                $message  = "[quote]\n";
                $message .= sprintf(_PM_USERWROTE,$pm_uname);
                $message .= "\n".$pm->getVar("msg_text", "E")."\n[/quote]";
            } else {
                unset($pm);
                $reply = $send2 = 0;
            }
        }
        echo "<form action='pmlite.php' method='post' name='coolsus'>\n";
            echo "<table width='300' align='center' class='outer'><tr><td class='head' width='25%'>"._PM_TO."</td>";
        if ( $reply == 1 ) {
            echo "<td class='even'><input type='hidden' name='to_userid' value='".$pm->getVar("from_userid")."' />".$pm_uname."</td>";
        } elseif ( $send2 == 1 ) {
            $to_username = XoopsUser::getUnameFromId($to_userid);
            echo "<td class='even'><input type='hidden' name='to_userid' value='".$to_userid."' />".$to_username."</td>";
        } else {
            require_once ICMS_ROOT_PATH."/class/xoopsform/formelement.php";
            require_once ICMS_ROOT_PATH."/class/xoopsform/formselect.php";
            require_once ICMS_ROOT_PATH."/class/xoopsform/formlabel.php";
            require_once ICMS_ROOT_PATH."/class/xoopsform/formselectuser.php";
            $user_sel = new XoopsFormSelectUser("", "to_userid");
            echo "<td class='even'>".$user_sel->render();
            echo "</td>";
        }
        echo "</tr>";
        echo "<tr><td class='head' width='25%'>"._PM_SUBJECTC."</td>";
        if ( $reply == 1 ) {
            $subject = $pm->getVar('subject', 'E');
            if (!preg_match("/^Re:/i",$subject)) {
                $subject = 'Re: '.$subject;
            }
            echo "<td class='even'><input type='text' name='subject' value='".$subject."' size='30' maxlength='100' /></td>";
        } else {
            echo "<td class='even'><input type='text' name='subject' size='30' maxlength='100' /></td>";
        }
        echo "</tr>";
        echo "<tr valign='top'><td class='head' width='25%'>"._PM_MESSAGEC."</td>";
        echo "<td class='even'>";
        if ($reply == 1) {
            $pm_handler =& xoops_gethandler('privmessage');
            $pm =& $pm_handler->get($msg_id);
            if ($pm->getVar("to_userid") == intval($icmsUser->getVar('uid'))) {
                $pm_uname = XoopsUser::getUnameFromId($pm->getVar("from_userid"));
                $message  = "[quote]\n";
                $message .= sprintf(_PM_USERWROTE,$pm_uname);
                $message .= "\n".$pm->getVar("msg_text", "E")."\n[/quote]";
            } else {
                unset($pm);
                $reply = $send2 = 0;
            }
		$textarea = new XoopsFormDhtmlTextArea(_PM_MESSAGEC, 'message', $message);
        }else{
		$textarea = new XoopsFormDhtmlTextArea(_PM_MESSAGEC, 'message', '');
}
		echo $textarea->render();
        echo "</td>";
        echo "</tr>";
        echo "<tr><td class='head'>&nbsp;</td><td class='even'>
        <input type='hidden' name='op' value='submit' />".$GLOBALS['xoopsSecurity']->getTokenHTML()."
        <input type='submit' class='formButton' name='submit' value='"._PM_SUBMIT."' />&nbsp;
        <input type='reset' class='formButton' value='"._PM_CLEAR."' />
        &nbsp;<input type='button' class='formButton' name='cancel' value='"._PM_CANCELSEND."' onclick='javascript:window.close();' />
        </td></tr></table>\n";
        echo "</form>\n";
    }
} else {
    echo "<div>"._PM_SORRY."<br /><br /><a href='".ICMS_URL."/register.php'>"._PM_REGISTERNOW."</a>.</div>";
}

xoops_footer();

?>