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

$xoopsTpl->assign("profile_adminpage", "<a href='" . ICMS_URL . "/modules/".basename( dirname( __FILE__ ) )."/admin/user.php'>" ._CO_ICMS_ADMIN_PAGE . "</a>");
$profile_isAdmin = icms_userIsAdmin();
$xoopsTpl->assign("profile_isAdmin", $profile_isAdmin);
include ICMS_ROOT_PATH.'/footer.php';
?>