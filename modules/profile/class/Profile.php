<?php
/**
 * Class representing the profile profile object
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		LICENSE.txt
 * @license		GNU General Public License (GPL) http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @package		profile
 * @since		1.2
 * @author		Jan Pedersen
 * @author		The SmartFactory <www.smartfactory.ca>
 * @author		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version		$Id: Profile.php 20113 2010-09-08 19:12:39Z phoenyx $
 */

defined("ICMS_ROOT_PATH") or die("ICMS root path not defined");

class mod_profile_Profile extends icms_ipf_Object {
	/**
	 * Constructor
	 *
	 * @param array $fields
	 */
	public function __construct($fields) {
		$this->initVar('profileid', XOBJ_DTYPE_INT, null, true);
		$this->initVar('newemail', XOBJ_DTYPE_TXTBOX);
		if (is_array($fields) && count($fields) > 0) {
			foreach (array_keys($fields) as $key) {
				$this->initVar($key, $fields[$key]->getVar('field_valuetype'), $fields[$key]->getVar('field_default', 'n'), $fields[$key]->getVar('field_required'), $fields[$key]->getVar('field_maxlength'));
			}
		}
	}
}
?>