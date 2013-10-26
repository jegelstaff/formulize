<?php
/**
 * icms_ipf_Handler
 *
 * This class is responsible for providing data access mechanisms to the data source
 * of derived class objects as well as some basic operations inherant to objects manipulation
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @category	ICMS
 * @package		Ipf
 * @subpackage	Object
 * @since		1.1
 * @author		marcan <marcan@impresscms.org>
 * @author		This was inspired by Mithrandir PersistableObjectHanlder: Jan Keller Pedersen <mithrandir@xoops.org> - IDG Danmark A/S <www.idg.dk>
 * @author		Gustavo Alejandro Pilla (aka nekro) <nekro@impresscms.org> <gpilla@nubee.com.ar>
 * @version		SVN: $Id: Handler.php 21047 2011-03-14 15:52:14Z m0nty_ $
 * @todo		Use language constants for messages
 * @todo		Properly determine visibility for methods and vars (private, protected, public) and apply naming conventions
 */

defined("ICMS_ROOT_PATH") or die("ImpressCMS root path not defined");
/**
 * Persistable Object Handlder
 * @category	ICMS
 * @package		Ipf
 * @subpackage	Object
 * @since		1.1
 * @todo		Properly name the vars using the naming conventions
 */
class icms_ipf_Handler extends icms_core_ObjectHandler {

	/**
	 *
	 * The name of the IPF object
	 * @var string
	 * @todo	Rename using the proper naming convention (this is a public var)
	 */
	public $_itemname;

	/**
	 * Name of the table use to store this {@link icms_ipf_Object}
	 *
	 * Note that the name of the table needs to be free of the database prefix.
	 * For example "smartsection_categories"
	 * @var string
	 */
	public $table;

	/**
	 * Name of the table key that uniquely identify each {@link icms_ipf_Object}
	 *
	 * For example : "categoryid"
	 * @var string
	 */
	public $keyName;

	/**
	 * Name of the class derived from {@link icms_ipf_Object} and which this handler is handling
	 *
	 * Note that this string needs to be lowercase
	 *
	 * For example : "smartsectioncategory"
	 * @var string
	 */
	public $className;

	/**
	 * Name of the field which properly identify the {@link icms_ipf_Object}
	 *
	 * For example : "name" (this will be the category's name)
	 * @var string
	 */
	public $identifierName;

	/**
	 * Name of the field which will be use as a summary for the object
	 *
	 * For example : "summary"
	 * @var string
	 */
	public $summaryName;

	/**
	 * Page name use to basically manage and display the {@link icms_ipf_Object}
	 *
	 * This page needs to be the same in user side and admin side
	 *
	 * For example category.php - we will deduct smartsection/category.php as well as smartsection/admin/category.php
	 * @todo this could probably be automatically deducted from the class name - for example, the class SmartsectionCategory will have "category.php" as it's managing page
	 * @todo	Rename using the proper naming convention - this is a public var
	 *
	 * @var string
	 */
	public $_page;

	/**
	 * Full path of the module using this {@link icms_ipf_Object}
	 *
	 * <code>ICMS_URL . "/modules/smartsection/"</code>
	 * @todo this could probably be automatically deducted from the class name as it is always prefixed with the module name
	 * @var string
	 */
	public $_modulePath;

	public $_moduleUrl;

	/**
	 *
	 * The name of the module for the object
	 * @var string
	 * @todo	Rename using the proper naming convention (This is a public var)
	 */
	public $_moduleName;

	public $uploadEnabled = false;

	public $_uploadUrl;

	public $_uploadPath;

	public $_allowedMimeTypes = 0;

	public $_maxFileSize = 1000000;

	public $_maxWidth = 500;

	public $_maxHeight = 500;

	public $highlightFields = array();

	/**
	 * Array containing the events name and functions
	 *
	 * @var array
	 */
	public $eventArray = array();

	/**
	 * Array containing the permissions that this handler will manage on the objects
	 *
	 * @var array
	 */
	public $permissionsArray = false;

	public $generalSQL = false;

	public $_eventHooks = array();
	public $_disabledEvents = array();

	/**
	 * Constructor - called from child classes
	 *
	 * @param object $db Database object {@link XoopsDatabase}
	 * @param string $itemname Object to be managed
	 * @param string $keyname Name of the table key that uniquely identify each {@link icms_ipf_Object}
	 * @param string $idenfierName Name of the field which properly identify the {@link icms_ipf_Object}
	 * @param string $summaryName Name of the field which will be use as a summary for the object
	 * @param string $modulename Name of the module controlling this object
	 * @return object
	 */
	public function __construct(&$db, $itemname, $keyname, $idenfierName, $summaryName, $modulename) {

		parent::__construct($db);

		$this->_itemname = $itemname;
		// Todo: Autodect module
		if ($modulename == '') {
			$this->_moduleName = 'system';
			$this->table = $db->prefix($itemname);
		} else {
			$this->_moduleName = $modulename;
			$this->table = $db->prefix($modulename . "_" . $itemname);
		}
		$this->keyName = $keyname;

		if ($modulename == 'icms')
			$classname = $modulename . '_' . $itemname . '_Object';
		else
			$classname = 'mod_' . $modulename . '_' . ucfirst($itemname);

		/**
		 * @todo this could probably be removed after refactopring is completed
		 * to be evaluated...
		 */
		if (!class_exists($classname))
			$classname = ucfirst($modulename) . ucfirst($itemname);

		$this->className = $classname;
		$this->identifierName = $idenfierName;
		$this->summaryName = $summaryName;
		$this->_page = $itemname . ".php";
		$this->_modulePath = ICMS_ROOT_PATH . "/modules/" . $this->_moduleName . "/";
		$this->_moduleUrl = ICMS_URL . "/modules/" . $this->_moduleName . "/";
		$this->_uploadPath = ICMS_UPLOAD_PATH . "/" . $this->_moduleName . "/";
		$this->_uploadUrl = ICMS_UPLOAD_URL . "/" . $this->_moduleName . "/";
	}

	/**
	 *
	 * @param str $event
	 * @param str $method
	 */
	public function addEventHook($event, $method) {
		$this->_eventHooks[$event] = $method;
	}

	/**
	 * Add a permission that this handler will manage for its objects
	 *
	 * Example : $this->addPermission('view', _AM_SSHOP_CAT_PERM_READ, _AM_SSHOP_CAT_PERM_READ_DSC);
	 *
	 * @param string $perm_name name of the permission
	 * @param string $caption caption of the control that will be displayed in the form
	 * @param string $description description of the control that will be displayed in the form
	 */
	public function addPermission($perm_name, $caption, $description = false) {
		$this->permissionsArray[] = array(
			'perm_name' => $perm_name,
			'caption' => $caption,
			'description' => $description
		);
	}

	/**
	 *
	 * @param obj $criteria
	 * @param str $perm_name
	 */
	public function setGrantedObjectsCriteria(&$criteria, $perm_name) {
		$icmspermissions_handler = new icms_ipf_permission_Handler($this);
		$grantedItems = $icmspermissions_handler->getGrantedItems($perm_name);
		if (count($grantedItems) > 0) {
			$criteria->add(new icms_db_criteria_Item($this->keyName, '(' . implode(', ', $grantedItems) . ')', 'IN'));
			return true;
		} else {
			return false;
		}
	}

	/**
	 * create a new {@link icms_ipf_Object}
	 *
	 * @param bool $isNew Flag the new objects as "new"?
	 *
	 * @return object {@link icms_ipf_Object}
	 */
	public function &create($isNew = true) {
		$obj = new $this->className($this);
		if (!$obj->handler) {
			$obj->handler =& $this;
		}

		if ($isNew === true) {
			$obj->setNew();
		}

		if ($this->uploadEnabled)
		$obj->setImageDir($this->getImageUrl(), $this->getImagePath());

		return $obj;
	}

	/**
	 *
	 */
	public function getImageUrl() {
		return $this->_uploadUrl . $this->_itemname . "/";
	}

	/**
	 *
	 */
	public function getImagePath() {
		$dir = $this->_uploadPath . $this->_itemname;
		if (!file_exists($dir)) {
			icms_core_Filesystem::mkdir($dir);
		}
		return $dir . "/";
	}

	/**
	 * retrieve a {@link icms_ipf_Object}
	 *
	 * @param mixed $id ID of the object - or array of ids for joint keys. Joint keys MUST be given in the same order as in the constructor
	 * @param bool $as_object whether to return an object or an array
	 * @return mixed reference to the {@link icms_ipf_Object}, FALSE if failed
	 */
	public function &get($id, $as_object = true, $debug = false, $criteria = false) {
		if (!$criteria) {
			$criteria = new icms_db_criteria_Compo();
		}
		if (is_array($this->keyName)) {
			for ($i = 0; $i < count($this->keyName); $i++) {
				/**
				 * In some situations, the $id is not an INTEGER. icms_ipf_ObjectTag is an example.
				 * Is the fact that we removed the intval() represents a security risk ?
				 */
				//$criteria->add(new icms_db_criteria_Item($this->keyName[$i], ($id[$i]), '=', $this->_itemname));
				$criteria->add(new icms_db_criteria_Item($this->keyName[$i], $id[$i], '=', $this->_itemname));
			}
		} else {
			//$criteria = new icms_db_criteria_Item($this->keyName, intval($id), '=', $this->_itemname);
			/**
			 * In some situations, the $id is not an INTEGER. icms_ipf_ObjectTag is an example.
			 * Is the fact that we removed the intval() represents a security risk ?
			 */
			$criteria->add(new icms_db_criteria_Item($this->keyName, $id, '=', $this->_itemname));
		}
		$criteria->setLimit(1);
		if ($debug) {
			$obj_array = $this->getObjectsD($criteria, false, $as_object);
		} else {
			$obj_array = $this->getObjects($criteria, false, $as_object);
			//patch : weird bug of indexing by id even if id_as_key = false;
			if (count($obj_array) && !isset($obj_array[0]) && is_object($obj_array[$id])) {
				$obj_array[0] = $obj_array[$id];
				unset($obj_array[$id]);
				$obj_array[0]->unsetNew();
			}
		}

		if (count($obj_array) != 1) {
			$obj = $this->create();
			return $obj;
		}

		return $obj_array[0];
	}

	/**
	 * retrieve a {@link icms_ipf_Object}
	 *
	 * @param mixed $id ID of the object - or array of ids for joint keys. Joint keys MUST be given in the same order as in the constructor
	 * @param bool $as_object whether to return an object or an array
	 * @return mixed reference to the {@link icms_ipf_Object}, FALSE if failed
	 */
	public function &getD($id, $as_object = true) {
		return $this->get($id, $as_object, true);
	}

	/**
	 * retrieve objects from the database
	 *
	 * @param object $criteria {@link icms_db_criteria_Element} conditions to be met
	 * @param bool $id_as_key use the ID as key for the array?
	 * @param bool $as_object return an array of objects?
	 *
	 * @return array
	 */
	public function getObjects($criteria = null, $id_as_key = false, $as_object = true, $sql = false, $debug = false) {
		$ret = array();
		$limit = $start = 0;

		if ($this->generalSQL) {
			$sql = $this->generalSQL;
		} elseif (!$sql) {
			$sql = 'SELECT * FROM ' . $this->table . " AS " . $this->_itemname;
		}

		if (isset($criteria) && is_subclass_of($criteria, 'icms_db_criteria_Element')) {
			$sql .= ' ' . $criteria->renderWhere();
			if ($criteria->getSort() != '') {
				$sql .= ' ORDER BY ' . $criteria->getSort() . ' ' . $criteria->getOrder();
			}
			$limit = $criteria->getLimit();
			$start = $criteria->getStart();
		}
		if ($debug) {
			icms_core_Debug::message($sql);
		}

		$result = $this->db->query($sql, $limit, $start);
		if (!$result) {
			return $ret;
		}
		return $this->convertResultSet($result, $id_as_key, $as_object);
	}

	/**
	 * query the database with the constructed $criteria object
	 *
	 * @param string $sql The SQL Query
	 * @param object $criteria {@link icms_db_criteria_Element} conditions to be met
	 * @param bool $force Force the query?
	 * @param bool $debug Turn Debug on?
	 *
	 * @return array
	 */
	public function query($sql, $criteria, $force = false, $debug = false) {
		$ret = array();

		if (isset($criteria) && is_subclass_of($criteria, 'icms_db_criteria_Element')) {
			$sql .= ' ' . $criteria->renderWhere();
			if ($criteria->groupby) {
				$sql .= $criteria->getGroupby();
			}
			if ($criteria->getSort() != '') {
				$sql .= ' ORDER BY ' . $criteria->getSort() . ' ' . $criteria->getOrder();
			}

		}
		if ($debug) {
			icms_core_Debug::message($sql);
		}

		if ($force) {
			$result = $this->db->queryF($sql);
		} else {
			$result = $this->db->query($sql);
		}

		if (!$result) {
			return $ret;
		}

		while ($myrow = $this->db->fetchArray($result)) {
			$ret[] = $myrow;
		}

		return $ret;
	}

	/**
	 * retrieve objects with debug mode - so will show the query
	 *
	 * @param object $criteria {@link icms_db_criteria_Element} conditions to be met
	 * @param bool $id_as_key use the ID as key for the array?
	 * @param bool $as_object return an array of objects?
	 *
	 * @return array
	 */
	public function getObjectsD($criteria = null, $id_as_key = false, $as_object = true, $sql = false) {
		return $this->getObjects($criteria, $id_as_key, $as_object, $sql, true);
	}

	/**
	 *
	 * @param arr $arrayObjects
	 */
	public function getObjectsAsArray($arrayObjects) {
		$ret = array();
		foreach ($arrayObjects as $key => $object) {
			$ret[$key] = $object->toArray();
		}
		if (count($ret > 0)) {
			return $ret;
		} else {
			return false;
		}
	}

	/**
	 * Convert a database resultset to a returnable array
	 *
	 * @param object $result database resultset
	 * @param bool $id_as_key - should NOT be used with joint keys
	 * @param bool $as_object
	 *
	 * @return array
	 */
	public function convertResultSet($result, $id_as_key = false, $as_object = true) {
		$ret = array();
		while ($myrow = $this->db->fetchArray($result)) {

			$obj =& $this->create(false);
			$obj->assignVars($myrow);
			if (!$id_as_key) {
				if ($as_object) {
					$ret[] =& $obj;
				}
				else {
					$ret[] = $obj->toArray();
				}
			} else {
				if ($as_object) {
					$value =& $obj;
				}
				else {
					$value = $obj->toArray();
				}
				if ($id_as_key === 'parentid') {
					$ret[$obj->getVar($obj->handler->parentName, 'e')][$obj->getVar($this->keyName)] =& $value;
				} else {
					$ret[$obj->getVar($this->keyName)] = $value;
				}
			}
			unset($obj);
		}

		return $ret;
	}
	/**
	 *
	 * @param object    $criteria
	 * @param int       $limit
	 * @param int       $start
	 * @return array
	 */
	public function getListD($criteria = null, $limit = 0, $start = 0) {
		return $this->getList($criteria, $limit, $start, true);
	}

	/**
	 * Retrieve a list of objects as arrays - DON'T USE WITH JOINT KEYS
	 *
	 * @param object $criteria {@link icms_db_criteria_Element} conditions to be met
	 * @param int   $limit      Max number of objects to fetch
	 * @param int   $start      Which record to start at
	 *
	 * @return array
	 */
	public function getList($criteria = null, $limit = 0, $start = 0, $debug = false) {
		$ret = array();
		if ($criteria == null) {
			$criteria = new icms_db_criteria_Compo();
		}

		if ($criteria->getSort() == '') {
			$criteria->setSort($this->getIdentifierName());
		}

		$sql = 'SELECT ' . (is_array($this->keyName) ? implode(', ', $this->keyName) : $this->keyName) ;
		if (!empty($this->identifierName)) {
			$sql .= ', ' . $this->getIdentifierName();
		}
		$sql .= ' FROM '.$this->table . " AS " . $this->_itemname;
		if (isset($criteria) && is_subclass_of($criteria, 'icms_db_criteria_Element')) {
			$sql .= ' ' . $criteria->renderWhere();
			if ($criteria->getSort() != '') {
				$sql .= ' ORDER BY ' . $criteria->getSort() . ' ' . $criteria->getOrder();
			}
			$limit = $criteria->getLimit();
			$start = $criteria->getStart();
		}

		if ($debug) {
			icms_core_Debug::message($sql);
		}

		$result = $this->db->query($sql, $limit, $start);
		if (!$result) {
			return $ret;
		}

		while ($myrow = $this->db->fetchArray($result)) {
			//identifiers should be textboxes, so sanitize them like that
			$ret[$myrow[$this->keyName]] = empty($this->identifierName) ? 1
				: icms_core_DataFilter::checkVar($myrow[$this->identifierName], 'text', 'output');
		}
		return $ret;
	}

	/**
	 * count objects matching a condition
	 *
	 * @param object $criteria {@link icms_db_criteria_Element} to match
	 * @return int count of objects
	 */
	public function getCount($criteria = null) {
		$field = "";
		$groupby = false;
		if (isset($criteria) && is_subclass_of($criteria, 'icms_db_criteria_Element')) {
			if ($criteria->groupby != "") {
				$groupby = true;
				$field = $criteria->groupby . ", "; //Not entirely secure unless you KNOW that no criteria's groupby clause is going to be mis-used
			}
		}
		/**
		 * if we have a generalSQL, lets used this one.
		 * This needs to be improved...
		 */
		if ($this->generalSQL) {
			$sql = $this->generalSQL;
			$sql = str_replace('SELECT *', 'SELECT COUNT(*)', $sql);
		} else {
			$sql = 'SELECT ' . $field . 'COUNT(*) FROM ' . $this->table . ' AS ' . $this->_itemname;
		}
		if (isset($criteria) && is_subclass_of($criteria, 'icms_db_criteria_Element')) {
			$sql .= ' ' . $criteria->renderWhere();
			if ($criteria->groupby != "") {
				$sql .= $criteria->getGroupby();
			}
		}

		$result = $this->db->query($sql);
		if (!$result) {
			return 0;
		}
		if ($groupby == false) {
			list($count) = $this->db->fetchRow($result);
			return $count;
		} else {
			$ret = array();
			while (list($id, $count) = $this->db->fetchRow($result)) {
				$ret[$id] = $count;
			}
			return $ret;
		}
	}

	/**
	 * delete an object from the database
	 *
	 * @param object $obj reference to the object to delete
	 * @param bool $force
	 * @return bool FALSE if failed.
	 */
	public function delete(&$obj, $force = false) {
		$eventResult = $this->executeEvent('beforeDelete', $obj);
		if (!$eventResult) {
			$obj->setErrors("An error occured during the BeforeDelete event");
			return false;
		}

		if (is_array($this->keyName)) {
			$clause = array();
			for ($i = 0; $i < count($this->keyName); $i++) {
				$clause[] = $this->keyName[$i] . ' = ' . $obj->getVar($this->keyName[$i]);
			}
			$whereclause = implode(" AND ", $clause);
		} else {
			$whereclause = $this->keyName . ' = ' . $obj->getVar($this->keyName);
		}
		$sql = 'DELETE FROM ' . $this->table . ' WHERE ' . $whereclause;
		if (false != $force) {
			$result = $this->db->queryF($sql);
		} else {
			$result = $this->db->query($sql);
		}
		if (!$result) {
			return false;
		}

		foreach ($obj->vars as $key => $var) {
			if ($var["data_type"] == XOBJ_DTYPE_URLLINK) {
				$urllinkObj = $obj->getUrlLinkObj($key);
				$urllinkObj->delete($force);
				unset($urllinkObj);
			}
			if ($var["data_type"] == XOBJ_DTYPE_FILE) {
				$fileObj = $obj->getFileObj($key);
				$fileObj->delete($force);
				unset($fileObj);
			}
		}

		$this->deleteGrantedPermissions($obj);

		$eventResult = $this->executeEvent('afterDelete', $obj);
		if (!$eventResult) {
			$obj->setErrors("An error occured during the AfterDelete event");
			return false;
		}
		return true;
	}

	/**
	 * delete granted permssions for an object
	 *
	 * @param	object	$obj	optional
	 * @return	bool	TRUE
	 */
	private function deleteGrantedPermissions($obj = NULL) {
		$gperm_handler = icms::handler("icms_member_groupperm");
		$module = icms::handler("icms_module")->getByDirname($this->_moduleName);
		$permissions = $this->getPermissions();
		if ($permissions === FALSE) return TRUE;
		foreach ($permissions as $permission) {
			if ($obj != NULL) {
				$gperm_handler->deleteByModule($module->getVar("mid"), $permission["perm_name"], $obj->id());
			} else {
				$gperm_handler->deleteByModule($module->getVar("mid"), $permission["perm_name"]);
			}
		}
		return TRUE;
	}

	/**
	 *
	 * @param arr|str $event
	 */
	public function disableEvent($event) {
		if (is_array($event)) {
			foreach ($event as $v) {
				$this->_disabledEvents[] = $v;
			}
		} else {
			$this->_disabledEvents[] = $event;
		}
	}

	/**
	 * Build an array containing all the ids of an array of objects as array
	 *
	 * @param array $objectsAsArray array of icms_ipf_Object
	 */
	public function getIdsFromObjectsAsArray($objectsAsArray) {
		$ret = array();
		foreach ($objectsAsArray as $array) {
			$ret[] = $array[$this->keyName];
		}
		return $ret;
	}

	/**
	 * Accessor for the permissions array property
	 */
	public function getPermissions() {
		return $this->permissionsArray;
	}

	/**
	 * insert a new object in the database
	 *
	 * @param object $obj reference to the object
	 * @param bool $force whether to force the query execution despite security settings
	 * @param bool $checkObject check if the object is dirty and clean the attributes
	 * @return bool FALSE if failed, TRUE if already present and unchanged or successful
	 */
	public function insert(&$obj, $force = false, $checkObject = true, $debug = false) {
		if ($checkObject != false) {
			if (!is_object($obj)) {
				return false;
			}
			/**
			 * @TODO: Change to if (!(class_exists($this->className) && $obj instanceof $this->className)) when going fully PHP5
			 */
			if (!is_a($obj, $this->className)) {
				$obj->setErrors(get_class($obj) . ' Differs from ' . $this->className);
				return false;
			}
			if (!$obj->isDirty()) {
				$obj->setErrors("Not dirty"); //will usually not be outputted as errors are not displayed when the method returns true, but it can be helpful when troubleshooting code - Mith
				return true;
			}
		}

		if ($obj->seoEnabled) {
			// Auto create meta tags if empty
			$icms_metagen = new icms_ipf_Metagen($obj->title(), $obj->getVar('meta_keywords'), $obj->summary());

			if (!$obj->getVar('meta_keywords') || !$obj->getVar('meta_description')) {

				if (!$obj->meta_keywords()) {
					$obj->setVar('meta_keywords', $icms_metagen->_keywords);
				}

				if (!$obj->meta_description()) {
					$obj->setVar('meta_description', $icms_metagen->_meta_description);
				}
			}

			// Auto create short_url if empty
			if (!$obj->short_url()) {
				$obj->setVar('short_url', $icms_metagen->generateSeoTitle($obj->title('n'), false));
			}
		}

		$eventResult = $this->executeEvent('beforeSave', $obj);
		if (!$eventResult) {
			$obj->setErrors('An error occured during the BeforeSave event');
			return false;
		}

		if ($obj->isNew()) {
			$eventResult = $this->executeEvent('beforeInsert', $obj);
			if (!$eventResult) {
				$obj->setErrors('An error occured during the BeforeInsert event');
				return false;
			}

		}	else {
			$eventResult = $this->executeEvent('beforeUpdate', $obj);
			if (!$eventResult) {
				$obj->setErrors('An error occured during the BeforeUpdate event');
				return false;
			}
		}
		if (!$obj->cleanVars()) {
			$obj->setErrors('Variables were not cleaned properly.');
			return false;
		}
		$fieldsToStoreInDB = array();
		foreach ($obj->cleanVars as $k => $v) {
			if ($obj->vars[$k]['data_type'] == XOBJ_DTYPE_INT) {
				$cleanvars[$k] = (int) ($v);
			} elseif (is_array($v)) {
				$cleanvars[ $k ] = $this->db->quoteString( implode( ',', $v ) );
			} else {
				$cleanvars[$k] = $this->db->quoteString($v);
			}
			if ($obj->vars[$k]['persistent']) {
				$fieldsToStoreInDB[$k] = $cleanvars[$k];
			}

		}
		if ($obj->isNew()) {
			if (!is_array($this->keyName)) {
				if ($cleanvars[$this->keyName] < 1) {
					$cleanvars[$this->keyName] = $this->db->genId($this->table.'_'.$this->keyName.'_seq');
				}
			}

			$sql = 'INSERT INTO ' . $this->table . ' (' . implode(',', array_keys($fieldsToStoreInDB))
				. ') VALUES (' . implode(',', array_values($fieldsToStoreInDB)) . ')';

		} else {

			$sql = 'UPDATE ' . $this->table . ' SET';
			foreach ($fieldsToStoreInDB as $key => $value) {
				if ((!is_array($this->keyName) && $key == $this->keyName)
					|| (is_array($this->keyName) && in_array($key, $this->keyName))) {
					continue;
				}
				if (isset($notfirst)) {
					$sql .= ',';
				}
				$sql .= ' ' . $key . ' = ' . $value;
				$notfirst = true;
			}
			if (is_array($this->keyName)) {
				$whereclause = '';
				for ($i = 0; $i < count($this->keyName); $i++) {
					if ($i > 0) {
						$whereclause .= ' AND ';
					}
					$whereclause .= $this->keyName[$i] . ' = ' . $obj->getVar($this->keyName[$i]);
				}
			} else {
				$whereclause = $this->keyName . ' = ' . $obj->getVar($this->keyName);
			}
			$sql .= ' WHERE ' . $whereclause;
		}

		if ($debug) {
			icms_core_Debug::message($sql);
		}

		if (false != $force) {
			$result = $this->db->queryF($sql);
		} else {
			$result = $this->db->query($sql);
		}

		if (!$result) {
			$obj->setErrors($this->db->error());
			return false;
		}

		if ($obj->isNew() && !is_array($this->keyName)) {
			$obj->assignVar($this->keyName, $this->db->getInsertId());
		}
		$eventResult = $this->executeEvent('afterSave', $obj);
		if (!$eventResult) {
			$obj->setErrors('An error occured during the AfterSave event');
			return false;
		}

		if ($obj->isNew()) {
			$obj->unsetNew();
			$eventResult = $this->executeEvent('afterInsert', $obj);
			if (!$eventResult) {
				$obj->setErrors('An error occured during the AfterInsert event');
				return false;
			}
		} else {
			$eventResult = $this->executeEvent('afterUpdate', $obj);
			if (!$eventResult) {
				$obj->setErrors('n error occured during the AfterUpdate event');
				return false;
			}
		}
		return true;
	}

	/**
	 *
	 * @param	obj		$obj
	 * @param	bool	$force
	 * @param	bool	$checkObject
	 * @param	bool	$debug
	 */
	public function insertD(&$obj, $force = false, $checkObject = true, $debug = false) {
		return $this->insert($obj, $force, $checkObject, true);
	}

	/**
	 * Change a value for objects with a certain criteria
	 *
	 * @param   string  $fieldname  Name of the field
	 * @param   string  $fieldvalue Value to write
	 * @param   object  $criteria   {@link icms_db_criteria_Element}
	 *
	 * @return  bool
	 **/
	public function updateAll($fieldname, $fieldvalue, $criteria = null, $force = false) {
		$set_clause = $fieldname . ' = ';
		if (is_numeric($fieldvalue)) {
			$set_clause .=  $fieldvalue;
		} elseif (is_array($fieldvalue)) {
			$set_clause .= $this->db->quoteString(implode(',', $fieldvalue));
		} else {
			$set_clause .= $this->db->quoteString($fieldvalue);
		}
		$sql = 'UPDATE '.$this->table.' SET '.$set_clause;
		if (isset($criteria) && is_subclass_of($criteria, 'icms_db_criteria_Element')) {
			$sql .= ' ' . $criteria->renderWhere();
		}
		if (false != $force) {
			$result = $this->db->queryF($sql);
		} else {
			$result = $this->db->query($sql);
		}
		if (!$result) {
			return false;
		}
		return true;
	}

	/**
	 * delete all objects meeting the conditions
	 *
	 * @param object $criteria {@link icms_db_criteria_Element} with conditions to meet
	 * @return bool
	 */

	public function deleteAll($criteria = NULL) {
		if (isset($criteria) && is_subclass_of($criteria, 'icms_db_criteria_Element')) {
			$rows = 0;
			$objects = $this->getObjects($criteria);
			foreach ($objects as $obj) {
				if ($this->delete($obj, TRUE)) {
					$rows++;
				}
			}
			return $rows > 0 ? $rows : TRUE;
		}
		return FALSE;
	}

	/**
	 *
	 */
	public function getModuleInfo() {
		return icms_getModuleInfo($this->_moduleName);
	}

	/**
	 *
	 */
	public function getModuleConfig() {
		return icms_getModuleConfig($this->_moduleName);
	}

	/**
	 *
	 */
	public function getModuleItemString() {
		$ret = $this->_moduleName . '_' . $this->_itemname;
		return $ret;
	}

	/**
	 *
	 * @param $object
	 */
	public function updateCounter($object) {
		if (isset($object->vars['counter'])) {
			$new_counter = $object->getVar('counter') + 1;
			$sql = 'UPDATE ' . $this->table . ' SET counter=' . $new_counter
				. ' WHERE ' . $this->keyName . '=' . $object->id();
			$this->query($sql, null, true);
		}
	}

	/**
	 * Execute the function associated with an event
	 * This method will check if the function is available
	 *
	 * @param string $event name of the event
	 * @param object $obj $object on which is performed the event
	 * @return mixed result of the execution of the function or FALSE if the function was not executed
	 */
	public function executeEvent($event, &$executeEventObj) {
		if (!in_array($event, $this->_disabledEvents)) {
			if (method_exists($this, $event)) {
				$ret = $this->$event($executeEventObj);
			} else {
				// check to see if there is a hook for this event
				if (isset($this->_eventHooks[$event])) {
					$method = $this->_eventHooks[$event];
					// check to see if the method specified by this hook exists
					if (method_exists($this, $method)) {
						$ret = $this->$method($executeEventObj);

					}
				}
				$ret = true;
			}
			return $ret;
		}
		return true;
	}

	/**
	 *
	 * @param	bool	$withprefix
	 */
	public function getIdentifierName($withprefix = true) {
		if ($withprefix) {
			return $this->_itemname . "." . $this->identifierName;
		} else {
			return $this->identifierName;
		}
	}

	/**
	 *
	 * @param unknown_type $allowedMimeTypes
	 * @param unknown_type $maxFileSize
	 * @param unknown_type $maxWidth
	 * @param unknown_type $maxHeight
	 */
	public function enableUpload($allowedMimeTypes = false, $maxFileSize = false, $maxWidth = false, $maxHeight = false) {
		$this->uploadEnabled = true;
		$this->_allowedMimeTypes = $allowedMimeTypes ? $allowedMimeTypes : $this->_allowedMimeTypes;
		$this->_maxFileSize = $maxFileSize ? $maxFileSize : $this->_maxFileSize;
		$this->_maxWidth = $maxWidth ? $maxWidth : $this->_maxWidth;
		$this->_maxHeight = $maxHeight ? $maxHeight : $this->_maxHeight;
	}

	/********** Deprecated ***************/
	/**
	 * Set the uploader config options.
	 * @deprecated please use enableUpload() instead
	 * @param str $_uploadPath
	 * @param array $_allowedMimeTypes
	 * @param int $_maxFileSize
	 * @param int $_maxFileWidth
	 * @param int $_maxFileHeight
	 * @return VOID
	 */
	public function setUploaderConfig($_uploadPath = false, $_allowedMimeTypes = false, $_maxFileSize = false, $_maxWidth = false, $_maxHeight = false) {
		$this->uploadEnabled = true;
		$this->_uploadPath = $_uploadPath ? $_uploadPath : $this->_uploadPath;
		$this->_allowedMimeTypes = $_allowedMimeTypes ? $_allowedMimeTypes : $this->_allowedMimeTypes;
		$this->_maxFileSize = $_maxFileSize ? $_maxFileSize : $this->_maxFileSize;
		$this->_maxWidth = $_maxWidth ? $_maxWidth : $this->_maxWidth;
		$this->_maxHeight = $_maxHeight ? $_maxHeight : $this->_maxHeight;
	}
}

