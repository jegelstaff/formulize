<?php
/**
 * Class representing the profile regstep object
 *
 * @copyright	The ImpressCMS Project <http://www.impresscms.org>
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @since		1.0
 * @author		Jan Pedersen
 * @author		The SmartFactory <www.smartfactory.ca>
 * @author		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @author		Gustavo Pilla (aka nekro) <nekro@impresscms.org>
 * @package		profile
 * @version		$Id: Regstep.php 20113 2010-09-08 19:12:39Z phoenyx $
 */

defined("ICMS_ROOT_PATH") or die("ICMS root path not defined");

class mod_profile_Regstep extends icms_ipf_Object {
	/**
	 * Constructor
	 *
	 * @param mod_profile_RegstepHandler $handler handler object
	 */
	public function __construct(&$handler) {
		parent::__construct($handler);

		$this->quickInitVar('step_id', XOBJ_DTYPE_INT, true);
		$this->quickInitVar('step_name', XOBJ_DTYPE_TXTBOX, true);
		$this->quickInitVar('step_intro', XOBJ_DTYPE_TXTAREA, false);
		$this->quickInitVar('step_order', XOBJ_DTYPE_TXTBOX, false);
		$this->quickInitVar('step_save', XOBJ_DTYPE_TXTBOX, false);
		
		$this->setControl('step_save', 'yesno');
	}

	/**
	 * Overriding the icms_ipf_Object::getVar method to assign a custom method on some
	 * specific fields to handle the value before returning it
	 *
	 * @param str $key key of the field
	 * @param str $format format that is requested
	 * @return mixed value of the field that is requested
	 */
	public function getVar($key, $format = 's') {
		if ($format == 's' && in_array($key, array())) {
			return call_user_func(array($this,	$key));
		}
		return parent::getVar($key, $format);
	}

	/**
	 * get image for custom step save
	 *
	 * @return str custom step save image
	 */
	public function getCustomStepSave(){
		if ($this->getVar('step_save') == 1) {
			$rtn = '<img src="'.ICMS_IMAGES_SET_URL.'/actions/button_ok.png" alt="1" />';
		} else {
			$rtn = '<img src="'.ICMS_IMAGES_SET_URL.'/actions/button_cancel.png" alt="0" />';
		}
		return $rtn;
	}

	/**
	 * get custom step name
	 *
	 * @return str step name
	 */
	public function getCustomStepName(){
		return $this->getVar('step_name');
	}
}
?>