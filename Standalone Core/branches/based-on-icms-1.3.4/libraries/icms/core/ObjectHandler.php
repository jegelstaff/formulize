<?php
/**
 * Manage of original Objects
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @category	ICMS
 * @package		Core
 * @version		SVN: $Id: ObjectHandler.php 12111 2012-11-09 02:11:04Z skenow $
 */

/**
 * Abstract object handler class.
 *
 * This class is an abstract class of handler classes that are responsible for providing
 * data access mechanisms to the data source of its corresponsing data objects
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @license	http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @category	ICMS
 * @package	Core
 * @since		XOOPS
 * @author		Kazumi Ono <onokazu@xoops.org>
 * @abstract
 */
abstract class icms_core_ObjectHandler {

	/**
	 * holds referenced to {@link icms_db_legacy_Database} class object
	 *
	 * @var object
	 * @see icms_db_legacy_Database
	 * @access protected
	 */
	protected $db;

	//
	/**
	* called from child classes only
	*
	* @param object $db reference to the {@link icms_db_legacy_Database} object
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
