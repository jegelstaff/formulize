<?php
/**
 * Where links to a user's account go, such as a name in the Creator column of a list. user.php
 * sends people here when they're already logged in.
 *
 * Your own account opens in edituser.php. Someone else's goes to wherever you can see it, if
 * anywhere, and the permissions decide what you get there. See formulize_userAccountURL().
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		Member
 * @subpackage	User
 */

$xoopsOption['pagetype'] = 'user';
include 'mainfile.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/include/common.php';

$uid = isset($_GET['uid']) ? intval($_GET['uid']) : 0;

if (is_object($xoopsUser) AND $uid == $xoopsUser->getVar('uid')) {
	header('Location: ' . XOOPS_URL . '/edituser.php');
	exit();
}

$member_handler = xoops_gethandler('member');
if ($uid <= 0 OR !is_object($member_handler->getUser($uid))) {
	redirect_header(XOOPS_URL, 3, _US_SELECTNG);
	exit();
}

if ($url = formulize_userAccountURL($uid)) {
	header('Location: ' . $url);
	exit();
}

redirect_header(XOOPS_URL, 3, _NOPERM);
