<?php
/**
 * Class representing the profile visibility object
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		LICENSE.txt
 * @license		GNU General Public License (GPL) http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @package		profile
 * @since		1.2
 * @author		Jan Pedersen
 * @author		The SmartFactory <www.smartfactory.ca>
 * @author		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version		$Id: Visibility.php 20113 2010-09-08 19:12:39Z phoenyx $
 */

defined("ICMS_ROOT_PATH") or die("ICMS root path not defined");

class mod_profile_Visibility extends icms_ipf_Object  {
	/**
	 * Constructor
	 *
	 * @param mod_profile_VisibilityHandler $handler object handler
	 */
	public function __construct(&$handler) {
		parent::__construct($handler);

		$this->initVar('fieldid', XOBJ_DTYPE_INT);
		$this->initVar('user_group', XOBJ_DTYPE_INT);
		$this->initVar('profile_group', XOBJ_DTYPE_INT);
	}
}
?>