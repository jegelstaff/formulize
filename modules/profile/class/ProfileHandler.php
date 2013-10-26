<?php
/**
 * Class responsible for managing profile profile objects
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		LICENSE.txt
 * @license		GNU General Public License (GPL) http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @since		1.4
 * @author		phoenyx
 * @package		profile
 * @version		$Id: ProfileHandler.php 20428 2010-11-21 12:38:18Z phoenyx $
 */

defined("ICMS_ROOT_PATH") or die("ICMS root path not defined");

class mod_profile_ProfileHandler extends icms_ipf_Handler {
	private $_fHandler;
	private $_fields = array();

	/**
	 * Constructor
	 *
	 * @param icms_db_legacy_Database $db database connection object
	 */
	public function __construct(&$db) {
		parent::__construct($db, 'profile', 'profileid', '', '', basename(dirname(dirname(__FILE__))));
		$this->_fHandler = icms_getModuleHandler('field', basename(dirname(dirname(__FILE__))), 'profile');
	}

	/**
	 * create a new {@link icms_ipf_Object}
	 *
	 * @param bool $isNew Flag the new objects as "new"?
	 * @return object {@link icms_ipf_Object}
	 */
	public function &create($isNew = true) {
		$obj = new $this->className($this->loadFields());
		$obj->handler = $this;
		if ($isNew === true) $obj->setNew();

		return $obj;
	}

	/**
	* Load field information
	*
	* @return array
	*/
	public function &loadFields() {
		if (count($this->_fields) == 0) $this->_fields = $this->_fHandler->loadFields();
		return $this->_fields;
	}

	/**
	 * insert a new object in the database
	 *
	 * @param object $obj reference to the object
	 * @param bool $force whether to force the query execution despite security settings
	 * @param bool $checkObject check if the object is dirty and clean the attributes
	 * @return bool FALSE if failed, TRUE if already present and unchanged or successful
	 */
	public function insert(&$obj, $force = false, $checkObject = true) {
		$uservars = $this->getUserVars();
		foreach ($uservars as $var) unset($obj->vars[$var]);
		if (count($obj->vars) == 1) return true;
		return parent::insert($obj, $force, $checkObject);
	}

	/**
	 * Get array of standard variable names (user table)
	 *
	 * @return array
	 */
	public function getUserVars() {
		return $this->_fHandler->getUserVars();
	}

	/**
	 * Search profiles and users
	 *
	 * @param icms_db_criteria_Element $criteria
	 * @param array $searchvars searchvars
	 * @return array
	 */
	public function search($criteria, $searchvars) {
		$searchvars2 = array('uid' => false, 'uname' => false, 'email' => false, 'user_viewemail' => false);
		if (is_object(icms::$user)) {
			foreach ($searchvars as $value) $searchvars2[$value] = false;
		} else {
			unset($searchvars2['email'], $searchvars2['user_viewemail']);
			if (isset($searchvars['email'])) unset($searchvars['email']);
		}
		$searchvars2 = array_keys($searchvars2);
		$sql = 'SELECT ';
		$user_handler = icms::handler('icms_member_user');
		$user = $user_handler->create(false);
		$vars = $user->getVars();
		$b = false;
		foreach ($searchvars2 as $field) {
			if ($b) {
				$sql .= ', ';
			} else {
				$b = true;
			}
			if (isset($vars[$field])) {
				$sql .= 'users.';
			} else {
				$sql .= 'profiles.';
			}
			$sql .= $field.' '.$field;
		}
		unset($searchvars2, $field, $b, $value);
		$sql .= ' FROM '.$this->db->prefix("users").' users LEFT JOIN '.$this->table.' profiles ON users.uid=profiles.profileid';
		$sql .= ' '.$criteria->renderWhere();
		if ($criteria->getSort() != '') $sql .= ' ORDER BY '.$criteria->getSort().' '.$criteria->getOrder();
		$users = '';
		$profiles = '';
		$limit = $criteria->getLimit();
		$start = $criteria->getStart();

		$result = $this->db->query($sql, $limit, $start);

		if (!$result) return array(array(), array(), 0);

		$uservars = $this->getUserVars();
		while ($myrow = $this->db->fetchArray($result)) {
			$profile = $this->create(false);
			$user = $user_handler->create(false);

			foreach ($myrow as $name => $value) {
				if (in_array($name, $uservars)) {
					$user->assignVar($name, $value);
				} else {
					$profile->assignVar($name, $value);
				}
			}
			$profiles[$myrow['uid']] = $profile;
			$users[$myrow['uid']] = $user;
		}

		$sql_count  = "SELECT count(*) FROM ".$this->db->prefix("users")." LEFT JOIN ".$this->table." ON uid=profileid";
		$sql_count .= ' '.$criteria->renderWhere();
		$count_res = $this->db->query($sql_count);
		list($count) = $this->db->fetchRow($count_res);

		return array($users, $profiles, $count);
	}
}
?>