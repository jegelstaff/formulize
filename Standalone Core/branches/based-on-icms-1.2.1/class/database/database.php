<?php 
/**
 * Database Base Class
 *
 * Defines abstract database wrapper class
 * 
 * @copyright	The XOOPS Project <http://www.xoops.org/>
 * @copyright	XOOPS_copyrights.txt
 * @copyright	The ImpressCMS Project <http://www.impresscms.org/>
 * @license		LICENSE.txt
 * @package		database
 * @since		XOOPS
 * @version		$Id: database.php 8584 2009-04-13 05:58:31Z nekro $
 * @author		The XOOPS Project Community <http://www.xoops.org>
 * @author      Kazumi Ono  <onokazu@xoops.org>
 * @author		modified by UnderDog <underdog@impresscms.org>
 * @author		Gustavo Pilla (aka nekro) <nekro@impresscms.org>
 */

/**
 * Make sure this is only included once!
 */
if ( !defined("XOOPS_C_DATABASE_INCLUDED") ) {
	define("XOOPS_C_DATABASE_INCLUDED",1);

	/**
	 * Abstract base class for Database access classes
	 *
	 * @abstract
	 *
	 * @package database
	 * @subpackage  main
	 *
	 * @author      Gustavo Pilla  (aka nekro) <nekro@impresscms.org>
	 * @copyright   copyright (c) 2000-2003 XOOPS.org
	 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
	 */
  	abstract class IcmsDatabase {
		
		/**
		 * Prefix for tables in the database
		 * @var string
		 */
		public $prefix = '';
		
		/**
		 * reference to a {@link XoopsLogger} object
		 * @see XoopsLogger
		 * @var object XoopsLogger
		 */
		public $logger;
		
		/**
		 * If statements that modify the database are selected
		 * @var boolean
		 */
	 	public $allowWebChanges = false;
	   
	    /**
	     * Constructor
	     *
	     * Will always fail, because this is an abstract class!
	     */
		public function __construct() { /* exit("Cannot instantiate this class directly"); */ }
		
		/**
		 * assign a {@link XoopsLogger} object to the database
		 *
		 * @see XoopsLogger
		 * @param object $logger reference to a {@link XoopsLogger} object
		 */
		public function setLogger(&$logger) {
			$this->logger =& $logger;
		}
	 
		/**
		 * set the prefix for tables in the database
		 *
		 * @param string $value table prefix
		 */
	   	public function setPrefix($value) {
	   		$this->prefix = $value;
	   	}
	
		/**
		 * attach the prefix.'_' to a given tablename
	     *
	     * if tablename is empty, only prefix will be returned
		 *
	     * @param string $tablename tablename
	     * @return string prefixed tablename, just prefix if tablename is empty
		 */
		public function prefix($tablename='') {
			if ( $tablename != '' ) {
				return $this->prefix .'_'. $tablename;
			} else {
				return $this->prefix;
			}
		}
	} 
	
	/**
	 * Abstract base class for Database access classes
	 *
	 * @abstract
	 *
	 * @package database
	 * @subpackage  main
	 * @since XOOPS
	 *
	 * @author      Kazumi Ono  <onokazu@xoops.org>
	 * @copyright   copyright (c) 2000-2003 XOOPS.org
	 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
	 * 
	 * @deprecated
	 */
	abstract class XoopsDatabase extends IcmsDatabase { /* For Backwards compatibility */ }
	
}


/**
 * Only for backward compatibility
 *
 * @package database
 * @subpackage  main
 * @since XOOPS
 *
 * @author      Kazumi Ono  <onokazu@xoops.org>
 * @copyright   copyright (c) 2000-2003 XOOPS.org
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 *
 * @deprecated
 */
class Database {
	static public function &getInstance() {
		return XoopsDatabaseFactory::getDatabaseConnection();
	}
}

?>