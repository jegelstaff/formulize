<?php
/**
 * Database Base Class
 *
 * Defines abstract database wrapper class
 *
 * @copyright	The ImpressCMS Project <http://www.impresscms.org/>
 * @license		LICENSE.txt
 * @category	ICMS
 * @package		Database
 * @subpackage	Legacy
 * @author		Kazumi Ono  <onokazu@xoops.org>
 * @author		Gustavo Alejandro Pilla (aka nekro) <nekro@impresscms.org> <gpilla@nubee.com.ar>
 * @version		SVN: $Id: Database.php 22532 2011-09-02 20:16:01Z phoenyx $
 */

defined( 'ICMS_ROOT_PATH' ) or die();
/**
 * Abstract base class for Database access classes
 *
 * @package database
 * @subpackage  main
 * @author		Gustavo Pilla  (aka nekro) <nekro@impresscms.org>
 */
abstract class icms_db_legacy_Database implements icms_db_legacy_IDatabase {
	/**
	 * Prefix for tables in the database
	 * @var string
	 */
	public $prefix = '';

	/**
	 * reference to a {@link icms_core_Logger} object
	 * @see icms_core_Logger
	 * @var object icms_core_Logger
	 */
	public $logger;

	/**
	 * If statements that modify the database are selected
	 * @var boolean
	 */
	public $allowWebChanges = false;

	public function __construct( $connection = null, $allowWebChanges = false ) {
		$this->allowWebChanges = $allowWebChanges;
	}
	public function setLogger($logger) {
		$this->logger = $logger;
	}
	public function setPrefix($value) {
		$this->prefix = $value;
	}
	public function prefix($tablename='') {
		if ( $tablename != '' ) {
			return $this->prefix .'_'. $tablename;
		} else {
			return $this->prefix;
		}
	}
}

