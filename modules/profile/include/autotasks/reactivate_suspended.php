<?php
/**
 * Autotask functions for the profile module
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		LICENSE.txt
 * @license		GNU General Public License (GPL) http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @package		modules
 * @since		1.4
 * @author		phoenyx
 * @version		$Id: reactivate_suspended.php 20113 2010-09-08 19:12:39Z phoenyx $
 */

defined('ICMS_ROOT_PATH') or die("ImpressCMS root path not defined");

$member_handler = icms::handler('icms_member');
$profile_configs_handler = icms_getModuleHandler('configs', basename(dirname(dirname(dirname(__FILE__)))), 'profile');

$criteria = new icms_db_criteria_Compo();
$criteria->add(new icms_db_criteria_Item('suspension', '1'));
$criteria->add(new icms_db_criteria_Item('status', '1'));
$criteria->add(new icms_db_criteria_Item('end_suspension', time(), '<='));

$configs = $profile_configs_handler->getObjects($criteria);
foreach ($configs as $config) {
	$thisUser = $member_handler->getUser($config->getVar('config_uid'));
	if (is_object($thisUser)) {
		$thisUser->setVar('pass', $config->getVar('backup_password', 'e'), true);
		$thisUser->setVar('email', $config->getVar('backup_email', 'e'));
		$thisUser->setVar('user_sig', $config->getVar('backup_sig', 'e'));
		$member_handler->insertUser($thisUser, true);
	}

	$config->setVar('suspension', 0);
	$config->setVar('backup_password', '');
	$config->setVar('backup_email', '');
	$config->setVar('backup_sig', '');
	$config->setVar('end_suspension', 0);
	$config->setVar('status', 0);
	$profile_configs_handler->insert($config, true);
}
?>
