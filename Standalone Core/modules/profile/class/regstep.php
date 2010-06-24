<?php
/**
 * Classes responsible for managing profile regstep objects
 *
 * @copyright	The ImpressCMS Project <http://www.impresscms.org>
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @since		1.0
 * @author	  Jan Pedersen
 * @author	  The SmartFactory <www.smartfactory.ca>
 * @author	   	Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @author		Gustavo Pilla (aka nekro) <nekro@impresscms.org>
 * @package		profile
 * @version		$Id$
 */

if (!defined("ICMS_ROOT_PATH"))  die("ICMS root path not defined");

include_once ICMS_ROOT_PATH . '/kernel/icmspersistableseoobject.php';

class ProfileRegstep extends IcmsPersistableObject {

	/**
	 * Constructor
	 *
	 * @param object $handler ProfileRegstepHandler object
	 */
	public function __construct(& $handler) {
		global $icmsConfig;

		$this->IcmsPersistableObject($handler);

		$this->quickInitVar('step_id', XOBJ_DTYPE_INT, true);
		$this->quickInitVar('step_name', XOBJ_DTYPE_TXTBOX, true);
		$this->quickInitVar('step_intro', XOBJ_DTYPE_TXTAREA, false);
		$this->quickInitVar('step_order', XOBJ_DTYPE_TXTBOX, false);
		$this->quickInitVar('step_save', XOBJ_DTYPE_TXTBOX, false);
		
		$this->setControl('step_save', 'yesno');
	}

	/**
	 * Overriding the IcmsPersistableObject::getVar method to assign a custom method on some
	 * specific fields to handle the value before returning it
	 *
	 * @param str $key key of the field
	 * @param str $format format that is requested
	 * @return mixed value of the field that is requested
	 */
	public function getVar($key, $format = 's') {
		if ($format == 's' && in_array($key, array ())) {
			return call_user_func(array ($this,	$key));
		}
		return parent :: getVar($key, $format);
	}
	
	public function getCustomStepSave(){
		if($this->getVar('step_save') == 1)
			$rtn = '<img src="'.ICMS_IMAGES_SET_URL.'/actions/button_ok.png" alt="1"/>';
		else
			$rtn = '<img src="'.ICMS_IMAGES_SET_URL.'/actions/button_cancel.png" alt="0"/>';
		return $rtn;
	}
	
	public function getCustomStepName(){
		$rtn = $this->getVar('step_name');
		return $rtn;	
	}
	
}

class ProfileRegstepHandler extends IcmsPersistableObjectHandler {
	
	/**
	 * Constructor
	 *
	 * @param IcmsDatabase $db
	 */
	public function __construct( & $db) {
		$this->IcmsPersistableObjectHandler($db, 'regstep', 'step_id', 'step_name', 'step_name', 'profile');
	}

	/**
	 * Insert a new object
	 * @see IcmsPersistableObjectHandler::insert()
	 *
	 * @param ProfileRegstep $obj
	 * @param bool $force
	 *
	 * @return bool
	 */
	public function insert($obj, $force = false) {
		if (parent::insert($obj, $force)) {
			if ($obj->getVar('step_save') == 1) {
				return $this->updateAll('step_save', 0, new Criteria('step_id', $obj->getVar('step_id'), "!="));
			}
			return true;
		}
		return false;
	}

	/**
	 * Delete an object from the database
	 * @see IcmsPersistableObjectHandler::delete()
	 *
	 * @param ProfileRegstep $obj
	 * @param bool $force
	 *
	 * @return bool
	 */
	public function delete($obj, $force = false) {
		if (parent::delete($obj, $force)) {
			$field_handler = icms_getmodulehandler( 'field', basename(  dirname(  dirname( __FILE__ ) ) ), 'profile' );
			return $field_handler->updateAll('step_id', 0, new Criteria('step_id', $obj->getVar('step_id')));
		}
		return false;
	}
}
?>
