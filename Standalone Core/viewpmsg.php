<?php
/**
* @copyright    http://www.xoops.org/ The XOOPS Project
* @copyright    XOOPS_copyrights.txt
* @copyright    http://www.impresscms.org/ The ImpressCMS Project
* @license      http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package      core
* @since        XOOPS
* @author       http://www.xoops.org The XOOPS Project
* @author       Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
* @version      $Id: viewpmsg.php 9591 2009-11-22 23:43:45Z mrtheme $
*/
$xoopsOption['pagetype'] = 'pmsg';
include_once 'mainfile.php';
$module_handler = xoops_gethandler('module');
$messenger_module = $module_handler->getByDirname('messenger');
if($messenger_module && $messenger_module->getVar('isactive'))
{
    header('location: ./modules/messenger/msgbox.php');
    exit();
}

if(!is_object($icmsUser))
{
        $errormessage = _PM_SORRY.'<br />'._PM_PLZREG.'';
        redirect_header('user.php', 2, $errormessage);
}
else
{
    $pm_handler = xoops_gethandler('privmessage');
    if(isset($_POST['delete_messages']) && isset($_POST['msg_id']))
    {
        if(!$GLOBALS['xoopsSecurity']->check())
        {
            echo implode('<br />', $GLOBALS['xoopsSecurity']->getErrors());
            exit();
        }
        $size = count($_POST['msg_id']);
        $msg =& $_POST['msg_id'];
        for($i = 0; $i < $size; $i++)
        {
            $pm =& $pm_handler->get($msg[$i]);
            if($pm->getVar('to_userid') == $icmsUser->getVar('uid'))
            {
                $pm_handler->delete($pm);
            }
            unset($pm);
        }
        redirect_header('viewpmsg.php', 1, _PM_DELETED);
    }
    include ICMS_ROOT_PATH.'/header.php';
    $criteria = new Criteria('to_userid', intval($icmsUser->getVar('uid')));
    $criteria->setOrder('DESC');
    $pm_arr =& $pm_handler->getObjects($criteria);
    echo "<h4 style='text-align:center;'>"._PM_PRIVATEMESSAGE."</h4><br />
        <a href='userinfo.php?uid=".intval($icmsUser->getVar('uid'))."'>". _PM_PROFILE ."</a>&nbsp;
        <span style='font-weight:bold;'>&raquo;&raquo;</span>&nbsp;"._PM_INBOX."<br /><br />";
    echo "<form id='prvmsg' method='post' action='viewpmsg.php'>";
    echo "<table border='0' cellspacing='1' cellpadding='4' width='100%' class='outer'>\n";
    echo "<tr align='center' valign='middle'><th>
        <input name='allbox' id='allbox' onclick='xoopsCheckAll(\"prvmsg\", \"allbox\");'
        type='checkbox' value='Check All' /></th><th>
        <img src='images/download.gif' alt='' /></th><th>&nbsp;</th><th>
        "._PM_FROM."</th><th>"._PM_SUBJECT."</th><th align='center'>"._PM_DATE."</th></tr>\n";
    $total_messages = count($pm_arr);
    if($total_messages == 0)
    {
        echo "<tr><td class='even' colspan='6' align='center'>"._PM_YOUDONTHAVE."</td></tr>";
        $display = 0;
    }
    else
    {
        $display = 1;
    }

    for($i = 0; $i < $total_messages; $i++)
    {
        $class = ($i % 2 == 0) ? 'even' : 'odd';
        echo "<tr align='"._GLOBAL_LEFT."' class='$class'><td style='vertical-align: top; width: 2%; text-align: center;'>
            <input type='checkbox' id='message_".$pm_arr[$i]->getVar('msg_id')."' name='".$pm_arr[$i]->getVar('msg_id')."' value='".$pm_arr[$i]->getVar('msg_id')."' /></td>\n";
        if($pm_arr[$i]->getVar('read_msg') == 1)
        {
            echo "<td style='vertical-align: top; width: 5%; text-align: center;'>&nbsp;</td>\n";
        }
        else
        {
            echo "<td style='vertical-align: top; width: 5%; text-align: center;'>
                <img src='images/read.gif' alt='"._PM_NOTREAD."' /></td>\n";
        }
        echo "<td style='vertical-align: top; width: 5%; text-align: center;'>
            <img src='images/subject/".$pm_arr[$i]->getVar('msg_image', 'E')."' alt='' /></td>\n";
        $postername = XoopsUser::getUnameFromId($pm_arr[$i]->getVar('from_userid'));
        echo "<td style='vertical-align: middle; width: 10%; text-align: center;'>";
        // no need to show deleted users
        if($postername)
        {
            echo "<a href='userinfo.php?uid=".intval($pm_arr[$i]->getVar('from_userid'))."'>".$postername."</a>";
        }
        else
        {
            echo $icmsConfig['anonymous'];
        }
        echo "</td>\n";
        echo "<td valign='middle'>
            <a href='readpmsg.php?start=".intval(($total_messages-$i-1))."&amp;
            total_messages=".intval($total_messages)."'>".$pm_arr[$i]->getVar('subject')."</a></td>";
        echo "<td style='vertical-align: middle; width: 20%; text-align: center;'>"
            .formatTimestamp($pm_arr[$i]->getVar('msg_time'))."</td></tr>";
    }

    if($display == 1)
    {
        echo "<tr class='foot' align='"._GLOBAL_LEFT."'><td colspan='6' align='"._GLOBAL_LEFT."'>
            <input type='button' class='formButton'
            onclick='javascript:openWithSelfMain(\"".ICMS_URL."/pmlite.php?send=1\",\"pmlite\",800,680);'
            value='"._PM_SEND."' />&nbsp;<input type='submit' class='formButton' name='delete_messages'
            value='"._PM_DELETE."' />".$GLOBALS['xoopsSecurity']->getTokenHTML()."</td></tr></table></form>";
    }
    else
    {
        echo "<tr class='bg2' align='"._GLOBAL_LEFT."'><td colspan='6' align='"._GLOBAL_LEFT."'>
            <input type='button' class='formButton'
            onclick='javascript:openWithSelfMain(\"".ICMS_URL."/pmlite.php?send=1\",\"pmlite\",800,680);'
            value='"._PM_SEND."' /></td></tr></table></form>";
    }
    include 'footer.php';
}
?>