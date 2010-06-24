<?php
/**
* Block Positions manager for the Impress Persistable Framework
*
* Longer description about this page
*
* @copyright      http://www.impresscms.org/ The ImpressCMS Project
* @license         LICENSE.txt
* @package	core
* @since            1.0
* @version		$Id: blockposition.php 9662 2009-12-18 11:21:55Z nekro $
*/

defined('ICMS_ROOT_PATH') or die('ImpressCMS root path not defined');

include_once ICMS_ROOT_PATH . '/kernel/icmspersistableseoobject.php';

/**
 * IcmsBlockposition
 *
 */
class IcmsBlockposition extends IcmsPersistableObject {
	
	/**
	 * Constructor
	 *
	 * @param IcmsBlockpositionHandler $handler
	 */
	public function __construct(& $handler) {
		
		$this->IcmsPersistableObject($handler);
		
		$this->quickInitVar('id', XOBJ_DTYPE_INT);
		$this->quickInitVar('pname', XOBJ_DTYPE_TXTBOX, true);
		$this->quickInitVar('title', XOBJ_DTYPE_TXTBOX, true);
		$this->quickInitVar('description', XOBJ_DTYPE_TXTAREA);
		$this->quickInitVar('block_default', XOBJ_DTYPE_INT);
		$this->quickInitVar('block_type', XOBJ_DTYPE_TXTBOX);
		
	}
	
}

/**
 * IcmsBlockpositionHandler
 *
 */
class IcmsBlockpositionHandler extends IcmsPersistableObjectHandler {
	
	/**
	 * Constructor
	 *
	 * @param IcmsDatabase $db
	 */
	public function __construct(& $db) {
		$this->IcmsPersistableObjectHandler($db, 'blockposition', 'id', 'title', 'description', 'icms');
		$this->table = $this->db->prefix('block_positions');
	}

	/**
	* Inserts block position into the database
	* 
	* @param object  $obj  the block position object
	* @param bool  $force  force the insertion of the object into the database
	* @param bool  $checkObject  Check the object before insertion
	* @param bool  $debug  turn on debug mode?
	* 
	* @return bool  the result of the insert action
	*/
	public function insert(& $obj, $force = false, $checkObject = true, $debug=false){
		$obj->setVar('block_default', 0);
		$obj->setVar('block_type', 'L');
		return parent::insert( $obj, $force, $checkObject, $debug );
	}
	
}


?>
