<?php
/**
 * @package database
 * @subpackage  main
 * @since XOOPS
 * @version $Id: databasefactory.php 8662 2009-05-01 09:04:30Z pesianstranger $
 *
 * @author		The XOOPS Project Community <http://www.xoops.org>
 * @author      Gustavo Pilla  (aka nekro) <nekro@impresscms.org>
 * @copyright   copyright (c) 2000-2003 XOOPS.org
 * @copyright   The ImpressCMS Project <http://www.impresscms.org>
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 */

/**
 * ImpressCMS Database Factory Class
 *
 * @package database
 * @subpackage  main
 *
 * @author      Gustavo Pilla  (aka nekro) <nekro@impresscms.org>
 * @copyright   The ImpressCMS Project <http://www.impresscms.org>
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 */
class IcmsDatabaseFactory{

	/**
	 * Constructor
	 *
	 * Makes nothing.
	 */
	protected function __construct(){ /* Empty! */ }

	/**
	 * Get a reference to the only instance of database class and connects to DB
	 *
	 * if the class has not been instantiated yet, this will also take
	 * care of that
	 *
	 * @static
	 * @staticvar   object  The only instance of database class
	 * @return      object  Reference to the only instance of database class
	 */
	static public function &getDatabaseConnection(){
		static $instance;
		if (!isset($instance)) {
			$file = ICMS_ROOT_PATH.'/class/database/drivers/'.XOOPS_DB_TYPE.'/database.php';
			require_once $file;
			/* begin DB Layer Trapping patch */
			if ( defined('XOOPS_DB_ALTERNATIVE') && class_exists( XOOPS_DB_ALTERNATIVE ) ) {
				$class = XOOPS_DB_ALTERNATIVE ;
			} else /* end DB Layer Trapping patch */if (!defined('XOOPS_DB_PROXY')) {
				$class = 'Xoops'.ucfirst(XOOPS_DB_TYPE).'DatabaseSafe';
			} else {
				$class = 'Xoops'.ucfirst(XOOPS_DB_TYPE).'DatabaseProxy';
			}
			$instance = new $class();
			$instance->setLogger(XoopsLogger::instance());
			$instance->setPrefix(XOOPS_DB_PREFIX);
			if (!$instance->connect()) {
				trigger_error(_CORE_DB_NOTRACEDB, E_USER_ERROR);
			}
		}
		return $instance;
	}

	/**
	 * Gets a reference to the only instance of database class. Currently
	 * only being used within the installer.
	 *
     * @static
     * @staticvar   object  The only instance of database class
     * @return      object  Reference to the only instance of database class
	 */
	static public function &getDatabase(){
		static $database;
		if (!isset($database)) {
			$file = ICMS_ROOT_PATH.'/class/database/drivers/'.XOOPS_DB_TYPE.'/database.php';
			require_once $file;
			if (!defined('XOOPS_DB_PROXY')) {
				$class = 'Xoops'.ucfirst(XOOPS_DB_TYPE).'DatabaseSafe';
			} else {
				$class = 'Xoops'.ucfirst(XOOPS_DB_TYPE).'DatabaseProxy';
			}
			$database = new $class();
		}
		return $database;
	}

	/**
	 * Gets the databaseupdater object.
	 *
     * @return	object  @link IcmsDatabaseUpdater
     * @static
	 */
	static public function getDatabaseUpdater(){
		$file = ICMS_ROOT_PATH.'/class/database/drivers/'.XOOPS_DB_TYPE.'/databaseupdater.php';
		require_once $file;
		$class = 'Icms'.ucfirst(XOOPS_DB_TYPE).'Databaseupdater';
		$databaseUpdater = new $class();
		return $databaseUpdater;
	}
}

/**
 * XoopsDatabseFactory Class
 *
 * @package database
 * @subpackage  main
 * @since XOOPS
 *
 * @author		The XOOPS Project Community <http://www.xoops.org>
 * @copyright   copyright (c) 2000-2003 XOOPS.org
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * 
 * @deprecated
 */
class XoopsDatabaseFactory extends IcmsDatabaseFactory { /* For backwards compatibility */ } 

?>