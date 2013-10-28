<?php
/**
 * Manage of original Objects
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @category	ICMS
 * @package		Object
 * @version		SVN: $Id: ObjectHandler.php 19775 2010-07-11 18:54:25Z malanciault $
 */

/**
 * Abstract object handler class.
 *
 * This class is an abstract class of handler classes that are responsible for providing
 * data access mechanisms to the data source of its corresponsing data objects
 *
 * @category	ICMS
 * @package		Object
 * @abstract
 * @author  Kazumi Ono <onokazu@xoops.org>
 */
abstract class icms_core_ObjectHandler {

	/**
	 * holds referenced to {@link XoopsDatabase} class object
	 *
	 * @var object
	 * @see XoopsDatabase
	 * @access protected
	 */
	protected $db;

	//
	/**
	* called from child classes only
	*
	* @param object $db reference to the {@link XoopsDatabase} object
	* @access protected
	*/
	function __construct(&$db) {
		$this->db =& $db;
	}

	/**
	 * creates a new object
	 *
	 * @abstract
	 */
	abstract function &create();

	/**
	 * gets a value object
	 *
	 * @param int $int_id
	 * @abstract
	 */
	abstract function &get($int_id);

	/**
	 * insert/update object
	 *
	 * @param object $object
	 * @abstract
	 */
	abstract function insert(&$object);

	/**
	 * delete object from database
	 *
	 * @param object $object
	 * @abstract
	 */
	abstract function delete(&$object);

}

