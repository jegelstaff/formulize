<?php
/**
 * Class responsible for managing profile visibility objects
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		LICENSE.txt
 * @license		GNU General Public License (GPL) http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @since		1.4
 * @author		phoenyx
* @package		profile
 * @version		$Id: VisibilityHandler.php 20122 2010-09-09 18:09:55Z phoenyx $
 */

defined("ICMS_ROOT_PATH") or die("ICMS root path not defined");

class mod_profile_VisibilityHandler extends icms_ipf_Handler {
	/**
	 * Constructor
	 *
	 * @param icms_db_legacy_Database $db database object
	 */
	public function __construct(&$db) {
		parent::__construct($db, 'visibility', array('fieldid', 'user_group', 'profile_group'), '', '', basename(dirname(dirname(__FILE__))));
	}

	/**
	 * Get fields visible to the $user_groups on a $profile_groups profile
	 *
	 * @param array $user_groups
	 * @param array $profile_groups
	 * @return array
	 */
	public function getVisibleFields($user_groups, $profile_groups) {
		$profile_groups[] = 0;
		$user_groups[] = 0;
		$rtn = array();
		$criteria = new icms_db_criteria_Compo();
		$criteria->add(new icms_db_criteria_Item('profile_group', '('.implode(',', $profile_groups).')', 'IN'));
		$criteria->add(new icms_db_criteria_Item('user_group', '('.implode(',', $user_groups).')', 'IN'));
		$visibilities = $this->getObjects($criteria);
		foreach ($visibilities as $visibility) $rtn[] = $visibility->getVar('fieldid');
		return array_unique($rtn);
	}
}
?>