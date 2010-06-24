<?php
/**
 * ImpressCMS User Info features
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		libraries
 * @since		1.1
 * @author		TheRplima <therplima@impresscms.org>
 * @version		$Id: userinfo.php 8547 2009-04-11 10:36:52Z icmsunderdog $
 */

class IcmsPreloadUserInfo extends IcmsPreloadItem {
	/**
	 * Function to be triggered at the end of the core boot process
	 *
	 * @return	void
	 */
	function eventStartOutputInit() {
		global $xoopsUser,$xoopsTpl;
		if (is_object($xoopsUser)) {
			foreach ( $xoopsUser->vars as $key => $value ) {
				$user [$key] = $value;
			}
			foreach ( $user as $key => $value ) {
				foreach ( $user [$key] as $key1 => $value1 ) {
					if ($key1 == 'value') {
						if ($key == 'last_login') {
							$value1 = formatTimestamp( (isset ( $_SESSION ['xoopsUserLastLogin'] )) ? $_SESSION ['xoopsUserLastLogin'] : time (), 'd/m/Y H:i:s' );
						}
						$user [$key] = $value1;
					}
				}
			}
			$pm_handler = & xoops_gethandler ( 'privmessage' );
			$criteria = new CriteriaCompo ( new Criteria ( 'read_msg', 0 ) );
			$criteria->add ( new Criteria ( 'to_userid', $xoopsUser->getVar ( 'uid' ) ) );
			$user ['new_messages'] = $pm_handler->getCount ( $criteria );
			
			$xoopsTpl->assign ( 'user', $user );
		}
	}
}
?>