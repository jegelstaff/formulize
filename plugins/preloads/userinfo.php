<?php
/**
 * ImpressCMS User Info features
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		libraries
 * @since		1.1
 * @author		TheRplima <therplima@impresscms.org>
 * @version		$Id: userinfo.php 20424 2010-11-20 19:16:00Z phoenyx $
 */
/**
 *
 * Event triggers for User Info
 * @since	1.2
 *
 */
class IcmsPreloadUserInfo extends icms_preload_Item {
	/**
	 * Function to be triggered at the end of the core boot process
	 *
	 * @return	void
	 */
	function eventStartOutputInit() {
		global $xoopsTpl;
		if (is_object(icms::$user)) {
			foreach (icms::$user->vars as $key => $value) {
				$user[$key] = $value;
			}
			foreach ($user as $key => $value) {
				foreach ($user [$key] as $key1 => $value1) {
					if ($key1 == 'value') {
						if ($key == 'last_login') {
							$value1 = formatTimestamp(
								isset($_SESSION['xoopsUserLastLogin'])
										? $_SESSION['xoopsUserLastLogin']
										: time(),
								_DATESTRING
							);
						}
						$user [$key] = $value1;
					}
				}
			}
			$pm_handler = icms::handler('icms_data_privmessage');
			$criteria = new icms_db_criteria_Compo(new icms_db_criteria_Item('read_msg', 0));
			$criteria->add(new icms_db_criteria_Item('to_userid', icms::$user->getVar('uid')));
			$user['new_messages'] = $pm_handler->getCount($criteria);

			$xoopsTpl->assign('user', $user);
		}
	}
}
