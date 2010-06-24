<?php
/**
 * Extended User Profile
 *
 *
 * @copyright       The ImpressCMS Project http://www.impresscms.org/
 * @license         LICENSE.txt
 * @license			GNU General Public License (GPL) http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @package         modules
 * @since           1.2
 * @author          Jan Pedersen
 * @author          The SmartFactory <www.smartfactory.ca>
 * @author	   		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version         $Id$
 */

include 'header.php';
xoops_cp_header();

if (!isset($_REQUEST['uid'])) {
    redirect_header("index.php", 2, _PROFILE_AM_NOSELECTION);
}
$member_handler = xoops_gethandler('member');
$user = $member_handler->getUser(intval($_REQUEST['uid']));
if (!$user || $user->isNew()) {
    redirect_header("index.php", 2, _PROFILE_AM_USERDONEXIT);
}

if (in_array(ICMS_GROUP_ADMIN, $user->getGroups())) {
    redirect_header("index.php", 2, _PROFILE_AM_CANNOTDEACTIVATEWEBMASTERS);
}
$user->setVar('level', intval($_REQUEST['level']));
if ($member_handler->insertUser($user)) {
    if ($_REQUEST['level'] == 1) {
        $message = _PROFILE_AM_USER_ACTIVATED;
    }
    else {
        $message = _PROFILE_AM_USER_DEACTIVATED;
    }
}
else {
    if ($_REQUEST['level'] == 1) {
        $message = _PROFILE_AM_USER_NOT_ACTIVATED;
    }
    else {
        $message = _PROFILE_AM_USER_NOT_DEACTIVATED;
    }
}
redirect_header("../userinfo.php?uid=".$user->getVar('uid'), 3, $message); // MPB
?>