<?php
/**
* IcmsPersistableObjectHandler
*
* This class is responsible for providing data access mechanisms to the data source
* of derived class objects as well as some basic operations inherant to objects manipulation
*
* @copyright	The ImpressCMS Project http://www.impresscms.org/
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package		IcmsPersistableObject
* @since		1.1
* @author		marcan <marcan@impresscms.org>
* @author		This was inspired by Mithrandir PersistableObjectHanlder: Jan Keller Pedersen <mithrandir@xoops.org> - IDG Danmark A/S <www.idg.dk>
* @version		$Id: icmspersistableobjecthandler.php 9520 2009-11-11 14:32:52Z pesianstranger $
*/

if (!defined("ICMS_ROOT_PATH")) {
	die("ImpressCMS root path not defined");
}
/**
 * Persistable Object Handlder
 * @package IcmsPersistableobject
 * @since   1.1
 */
class IcmsPersistableObjectHandler extends XoopsObjectHandler {

	var $_itemname;

	/**
	 * Name of the table use to store this {@link IcmsPersistableObject}
	 *
	 * Note that the name of the table needs to be free of the database prefix.
	 * For example "smartsection_categories"
	 * @var string
	 */
    var $table;

	/**
	 * Name of the table key that uniquely identify each {@link IcmsPersistableObject}
	 *
	 * For example : "categoryid"
	 * @var string
	 */
    var $keyName;

    /**
	 * Name of the class derived from {@link IcmsPersistableObject} and which this handler is handling
	 *
	 * Note that this string needs to be lowercase
	 *
	 * For example : "smartsectioncategory"
	 * @var string
	 */
    var $className;

    /**
	 * Name of the field which properly identify the {@link IcmsPersistableObject}
	 *
	 * For example : "name" (this will be the category's name)
	 * @var string
	 */
    var $identifierName;

    /**
	 * Name of the field which will be use as a summary for the object
	 *
	 * For example : "summary"
	 * @var string
	 */
    var $summaryName;

    /**
	 * Page name use to basically manage and display the {@link IcmsPersistableObject}
	 *
	 * This page needs to be the same in user side and admin side
	 *
	 * For example category.php - we will deduct smartsection/category.php as well as smartsection/admin/category.php
	 * @todo this could probably be automatically deducted from the class name - for example, the class SmartsectionCategory will have "category.php" as it's managing page
	 * @var string
	 */
    var $_page;

    /**
	 * Full path of the module using this {@link IcmsPersistableObject}
	 *
	 * <code>ICMS_URL . "/modules/smartsection/"</code>
	 * @todo this could probably be automatically deducted from the class name as it is always prefixed with the module name
	 * @var string
	 */
	var $_modulePath;

	var $_moduleUrl;

	var $_moduleName;

	var $uploadEnabled=false;

    var $_uploadUrl;

    var $_uploadPath;

    var $_allowedMimeTypes = 0;

    var $_maxFileSize = 1000000;

    var $_maxWidth = 500;

    var $_maxHeight = 500;

    var $highlightFields = array();

    /**
     * Array containing the events name and functions
     *
     * @var array
     */
    var $eventArray = array();

    /**
     * Array containing the permissions that this handler will manage on the objects
     *
     * @var array
     */
    var $permissionsArray = false;

    var $generalSQL=false;

    var $_eventHooks=array();
    var $_disabledEvents=array();

    /**
     * Constructor - called from child classes
     * 
     * @param object $db Database object {@link XoopsDatabase}
     * @param string $itemname Object to be managed
     * @param string $keyname Name of the table key that uniquely identify each {@link IcmsPersistableObject}
     * @param string $idenfierName Name of the field which properly identify the {@link IcmsPersistableObject}
     * @param string $summaryName Name of the field which will be use as a summary for the object
     * @param string $modulename Name of the module controlling this object
     * @return object
     */
    function IcmsPersistableObjectHandler(&$db, $itemname, $keyname, $idenfierName, $summaryName, $modulename) {

    	$this->XoopsObjectHandler($db);

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
        $this->className = ucfirst($modulename) . ucfirst($itemname);
        $this->identifierName = $idenfierName;
        $this->summaryName = $summaryName;
        $this->_page = $itemname . ".php";
        $this->_modulePath = ICMS_ROOT_PATH . "/modules/" . $this->_moduleName . "/";
        $this->_moduleUrl = ICMS_URL . "/modules/" . $this->_moduleName . "/";
        $this->_uploadPath = ICMS_UPLOAD_PATH . "/" . $this->_moduleName . "/";
        $this->_uploadUrl = ICMS_UPLOAD_URL . "/" . $this->_moduleName . "/";
    }

	function addEventHook($event, $method) {
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
    function addPermission($perm_name, $caption, $description=false) {
   		$this->permissionsArray[] = array(
   			'perm_name' => $perm_name,
   			'caption' => $caption,
   			'description' => $description
   		);
    }

    function setGrantedObjectsCriteria(&$criteria, $perm_name) {
		$icmspermissions_handler = new IcmsPersistablePermissionHandler($this);
		$grantedItems = $icmspermissions_handler->getGrantedItems($perm_name);
		if (count($grantedItems) > 0) {
			$criteria->add(new Criteria($this->keyName, '(' . implode(', ', $grantedItems) . ')', 'IN'));
			return true;
		} else {
			return false;
		}
    }

    /**
     * create a new {@link IcmsPersistableObject}
     *
     * @param bool $isNew Flag the new objects as "new"?
     *
     * @return object {@link IcmsPersistableObject}
     */
    function &create($isNew = true) {
    	$obj =& new $this->className($this);
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

    function getImageUrl() {
		return $this->_uploadUrl . $this->_itemname . "/";
    }

    function getImagePath() {
    	$dir = $this->_uploadPath . $this->_itemname;
    	if (!file_exists($dir)) {
    		icms_mkdir($dir);
    	}
    	return $dir . "/";
    }

    /**
     * retrieve a {@link IcmsPersistableObject}
     *
     * @param mixed $id ID of the object - or array of ids for joint keys. Joint keys MUST be given in the same order as in the constructor
     * @param bool $as_object whether to return an object or an array
     * @return mixed reference to the {@link IcmsPersistableObject}, FALSE if failed
     */
    function &get($id, $as_object = true, $debug=false, $criteria=false) {
        if (!$criteria) {
        	$criteria = new CriteriaCompo();
        }
        if (is_array($this->keyName)) {
            for ($i = 0; $i < count($this->keyName); $i++) {
	            /**
	             * In some situations, the $id is not an INTEGER. IcmsPersistableObjectTag is an example.
	             * Is the fact that we removed the intval() represents a security risk ?
	             */
                //$criteria->add(new Criteria($this->keyName[$i], ($id[$i]), '=', $this->_itemname));
                $criteria->add(new Criteria($this->keyName[$i], $id[$i], '=', $this->_itemname));
            }
        }
        else {
            //$criteria = new Criteria($this->keyName, intval($id), '=', $this->_itemname);
            /**
             * In some situations, the $id is not an INTEGER. IcmsPersistableObjectTag is an example.
             * Is the fact that we removed the intval() represents a security risk ?
             */
            $criteria->add(new Criteria($this->keyName, $id, '=', $this->_itemname));
        }
        $criteria->setLimit(1);
        if ($debug) {
        	$obj_array = $this->getObjectsD($criteria, false, $as_object);
        } else {
        	$obj_array = $this->getObjects($criteria, false, $as_object);
        	//patch : weird bug of indexing by id even if id_as_key = false;
        	if(count($obj_array) && !isset($obj_array[0]) && is_object($obj_array[$id])){
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
     * retrieve a {@link IcmsPersistableObject}
     *
     * @param mixed $id ID of the object - or array of ids for joint keys. Joint keys MUST be given in the same order as in the constructor
     * @param bool $as_object whether to return an object or an array
     * @return mixed reference to the {@link IcmsPersistableObject}, FALSE if failed
     */
    function &getD($id, $as_object = true) {
		return $this->get($id, $as_object, true);
    }

    /**
     * retrieve objects from the database
     *
     * @param object $criteria {@link CriteriaElement} conditions to be met
     * @param bool $id_as_key use the ID as key for the array?
     * @param bool $as_object return an array of objects?
     *
     * @return array
     */
    function getObjects($criteria = null, $id_as_key = false, $as_object = true, $sql=false, $debug=false)
    {
    	$ret = array();
        $limit = $start = 0;

        if ($this->generalSQL) {
        	$sql = $this->generalSQL;
        } elseif(!$sql) {
	        $sql = 'SELECT * FROM '.$this->table . " AS " . $this->_itemname;
        }

        if (isset($criteria) && is_subclass_of($criteria, 'criteriaelement')) {
            $sql .= ' '.$criteria->renderWhere();
            if ($criteria->getSort() != '') {
                $sql .= ' ORDER BY '.$criteria->getSort().' '.$criteria->getOrder();
            }
            $limit = $criteria->getLimit();
            $start = $criteria->getStart();
        }
        if ($debug) {
        	icms_debug($sql);
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
     * @param object $criteria {@link CriteriaElement} conditions to be met
     * @param bool $force Force the query?
     * @param bool $debug Turn Debug on?
     *
     * @return array
     */
    function query($sql, $criteria, $force=false, $debug=false)
    {
    	$ret = array();

        if (isset($criteria) && is_subclass_of($criteria, 'criteriaelement')) {
            $sql .= ' '.$criteria->renderWhere();
            if ($criteria->groupby) {
				 $sql .= $criteria->getGroupby();
            }
            if ($criteria->getSort() != '') {
                $sql .= ' ORDER BY '.$criteria->getSort().' '.$criteria->getOrder();
            }

        }
        if ($debug) {
        	icms_debug($sql);
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
     * @param object $criteria {@link CriteriaElement} conditions to be met
     * @param bool $id_as_key use the ID as key for the array?
     * @param bool $as_object return an array of objects?
     *
     * @return array
     */
    function getObjectsD($criteria = null, $id_as_key = false, $as_object = true, $sql = false)
    {
        return $this->getObjects($criteria, $id_as_key, $as_object, $sql, true);
    }

    function getObjectsAsArray($arrayObjects) {
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
    function convertResultSet($result, $id_as_key = false, $as_object = true) {
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
 	function getListD($criteria = null, $limit = 0, $start = 0) {
 		return $this->getList($criteria, $limit, $start, true);
 	}

    /**
    * Retrieve a list of objects as arrays - DON'T USE WITH JOINT KEYS
    *
    * @param object $criteria {@link CriteriaElement} conditions to be met
    * @param int   $limit      Max number of objects to fetch
    * @param int   $start      Which record to start at
    *
    * @return array
    */
    function getList($criteria = null, $limit = 0, $start = 0, $debug=false) {
        $ret = array();
        if ($criteria == null) {
            $criteria = new CriteriaCompo();
        }

        if ($criteria->getSort() == '') {
            $criteria->setSort($this->getIdentifierName());
        }

        $sql = 'SELECT '.(is_array($this->keyName) ? implode(', ', $this->keyName) : $this->keyName) ;
        if(!empty($this->identifierName)){
            $sql .= ', '.$this->getIdentifierName();
        }
        $sql .= ' FROM '.$this->table . " AS " . $this->_itemname;
        if (isset($criteria) && is_subclass_of($criteria, 'criteriaelement')) {
            $sql .= ' '.$criteria->renderWhere();
            if ($criteria->getSort() != '') {
                $sql .= ' ORDER BY '.$criteria->getSort().' '.$criteria->getOrder();
            }
            $limit = $criteria->getLimit();
            $start = $criteria->getStart();
        }

        if ($debug) {
        	icms_debug($sql);
        }

        $result = $this->db->query($sql, $limit, $start);
        if (!$result) {
            return $ret;
        }

        $myts =& MyTextSanitizer::getInstance();
        while ($myrow = $this->db->fetchArray($result)) {
            //identifiers should be textboxes, so sanitize them like that
            $ret[$myrow[$this->keyName]] = empty($this->identifierName)?1:$myts->displayTarea($myrow[$this->identifierName]);
        }
        return $ret;
    }

    /**
     * count objects matching a condition
     *
     * @param object $criteria {@link CriteriaElement} to match
     * @return int count of objects
     */
    function getCount($criteria = null)
    {
        $field = "";
        $groupby = false;
        if (isset($criteria) && is_subclass_of($criteria, 'criteriaelement')) {
            if ($criteria->groupby != "") {
                $groupby = true;
                $field = $criteria->groupby.", "; //Not entirely secure unless you KNOW that no criteria's groupby clause is going to be mis-used
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
	    	$sql = 'SELECT '.$field.'COUNT(*) FROM '.$this->table . ' AS ' . $this->_itemname;
        }
        if (isset($criteria) && is_subclass_of($criteria, 'criteriaelement')) {
            $sql .= ' '.$criteria->renderWhere();
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
        }
        else {
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
    function delete(&$obj, $force = false)
    {
        $eventResult = $this->executeEvent('beforeDelete', $obj);
    	if (!$eventResult) {
        	$obj->setErrors("An error occured during the BeforeDelete event");
        	return false;
        }

    	if (is_array($this->keyName)) {
            $clause = array();
            for ($i = 0; $i < count($this->keyName); $i++) {
	            $clause[] = $this->keyName[$i]." = ".$obj->getVar($this->keyName[$i]);
            }
            $whereclause = implode(" AND ", $clause);
        }
        else {
            $whereclause = $this->keyName." = ".$obj->getVar($this->keyName);
        }
        $sql = "DELETE FROM ".$this->table . " WHERE ".$whereclause;
        if (false != $force) {
            $result = $this->db->queryF($sql);
        } else {
            $result = $this->db->query($sql);
        }
        if (!$result) {
            return false;
        }

        $eventResult = $this->executeEvent('afterDelete', $obj);
    	if (!$eventResult) {
        	$obj->setErrors("An error occured during the AfterDelete event");
        	return false;
        }
        return true;
    }

    function disableEvent($event) {
    	if (is_array($event)) {
    		foreach($event as $v) {
    			$this->_disabledEvents[] = $v;
    		}
    	} else {
    		$this->_disabledEvents[] = $event;
    	}
    }

    /**
     * Build an array containing all the ids of an array of objects as array
     *
     * @param array $objectsAsArray array of IcmsPersistableObject
     */
    function getIdsFromObjectsAsArray($objectsAsArray) {
    	$ret = array();
    	foreach($objectsAsArray as $array) {
    		$ret[] = $array[$this->keyName];
    	}
    	return $ret;
    }

    function getPermissions() {
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
    function insert(&$obj, $force = false, $checkObject = true, $debug=false)
    {
    	if ($checkObject != false) {
            if (!is_object($obj)) {
                return false;
            }
            /**
        * @TODO: Change to if (!(class_exists($this->className) && $obj instanceof $this->className)) when going fully PHP5
        */
            if (!is_a($obj, $this->className)) {
            	$obj->setErrors(get_class($obj)." Differs from ".$this->className);
                return false;
            }
            if (!$obj->isDirty()) {
                $obj->setErrors("Not dirty"); //will usually not be outputted as errors are not displayed when the method returns true, but it can be helpful when troubleshooting code - Mith
                return true;
            }
        }

		if ($obj->seoEnabled) {
	        // Auto create meta tags if empty
			$icms_metagen = new IcmsMetagen($obj->title(), $obj->getVar('meta_keywords'), $obj->summary());

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
        	$obj->setErrors("An error occured during the BeforeSave event");
        	return false;
        }

        if ($obj->isNew()) {
	        $eventResult = $this->executeEvent('beforeInsert', $obj);
	    	if (!$eventResult) {
	        	$obj->setErrors("An error occured during the BeforeInsert event");
	        	return false;
	        }

        }	else {
	        $eventResult = $this->executeEvent('beforeUpdate', $obj);
	    	if (!$eventResult) {
	        	$obj->setErrors("An error occured during the BeforeUpdate event");
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
                $cleanvars[$k] = intval($v);
            } elseif (is_array($v) ) {
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

            $sql = "INSERT INTO ".$this->table." (".implode(',', array_keys($fieldsToStoreInDB)).") VALUES (".implode(',', array_values($fieldsToStoreInDB)) .")";

        } else {

            $sql = "UPDATE ".$this->table." SET";
            foreach ($fieldsToStoreInDB as $key => $value) {
                if ((!is_array($this->keyName) && $key == $this->keyName) || (is_array($this->keyName) && in_array($key, $this->keyName))) {
                    continue;
                }
                if (isset($notfirst) ) {
                    $sql .= ",";
                }
                $sql .= " ".$key." = ".$value;
                $notfirst = true;
            }
            if (is_array($this->keyName)) {
                $whereclause = "";
                for ($i = 0; $i < count($this->keyName); $i++) {
                    if ($i > 0) {
                        $whereclause .= " AND ";
                    }
                    $whereclause .= $this->keyName[$i]." = ".$obj->getVar($this->keyName[$i]);
                }
            }
            else {
                $whereclause = $this->keyName." = ".$obj->getVar($this->keyName);
            }
            $sql .= " WHERE ".$whereclause;
        }

        if ($debug) {
        	icms_debug($sql);
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
        	$obj->setErrors("An error occured during the AfterSave event");
        	return false;
        }

        if ($obj->isNew()) {
        	$obj->unsetNew();
	        $eventResult = $this->executeEvent('afterInsert', $obj);
	    	if (!$eventResult) {
	        	$obj->setErrors("An error occured during the AfterInsert event");
	        	return false;
	        }
        } else {
	        $eventResult = $this->executeEvent('afterUpdate', $obj);
	    	if (!$eventResult) {
	        	$obj->setErrors("An error occured during the AfterUpdate event");
	        	return false;
	        }
        }
        return true;
    }

     function insertD(&$obj, $force = false, $checkObject = true, $debug=false)
     {
     	return $this->insert($obj, $force, $checkObject, true);
     }

    /**
     * Change a value for objects with a certain criteria
     *
     * @param   string  $fieldname  Name of the field
     * @param   string  $fieldvalue Value to write
     * @param   object  $criteria   {@link CriteriaElement}
     *
     * @return  bool
     **/
    function updateAll($fieldname, $fieldvalue, $criteria = null, $force = false)
    {
    	$set_clause = $fieldname . ' = ';
    	if ( is_numeric( $fieldvalue ) ) {
    		$set_clause .=  $fieldvalue;
    	} elseif ( is_array( $fieldvalue ) ) {
    		$set_clause .= $this->db->quoteString( implode( ',', $fieldvalue ) );
    	} else {
    		$set_clause .= $this->db->quoteString( $fieldvalue );
    	}
        $sql = 'UPDATE '.$this->table.' SET '.$set_clause;
        if (isset($criteria) && is_subclass_of($criteria, 'criteriaelement')) {
            $sql .= ' '.$criteria->renderWhere();
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
     * @param object $criteria {@link CriteriaElement} with conditions to meet
     * @return bool
     */

    function deleteAll($criteria = null)
    {
        if (isset($criteria) && is_subclass_of($criteria, 'criteriaelement')) {
            $sql = 'DELETE FROM '.$this->table;
            $sql .= ' '.$criteria->renderWhere();
            if (!$this->db->query($sql)) {
                return false;
            }
            $rows = $this->db->getAffectedRows();
            return $rows > 0 ? $rows : true;
        }
        return false;
    }

	function getModuleInfo() {
		return icms_getModuleInfo($this->_moduleName);
	}

	function getModuleConfig() {
		return icms_getModuleConfig($this->_moduleName);
	}

    function getModuleItemString() {
    	$ret = $this->_moduleName . '_' . $this->_itemname;
    	return $ret;
    }

    function updateCounter($object) {
    	if (isset($object->vars['counter'])) {
    		$new_counter = $object->getVar('counter') + 1;
    		$sql = 'UPDATE ' . $this->table . ' SET counter=' . $new_counter . ' WHERE ' . $this->keyName . '=' . $object->id();
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
    function executeEvent($event, &$executeEventObj) {
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

    function getIdentifierName($withprefix=true) {
    	if ($withprefix) {
    		return $this->_itemname . "." . $this->identifierName;
    	} else {
    		return $this->identifierName;
    	}
    }

    function enableUpload($allowedMimeTypes=false, $maxFileSize=false, $maxWidth=false, $maxHeight=false) {
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
    function setUploaderConfig($_uploadPath=false, $_allowedMimeTypes=false, $_maxFileSize=false, $_maxWidth=false, $_maxHeight=false) {
    	$this->uploadEnabled = true;
    	$this->_uploadPath = $_uploadPath ? $_uploadPath : $this->_uploadPath;
    	$this->_allowedMimeTypes = $_allowedMimeTypes ? $_allowedMimeTypes : $this->_allowedMimeTypes;
    	$this->_maxFileSize = $_maxFileSize ? $_maxFileSize : $this->_maxFileSize;
    	$this->_maxWidth = $_maxWidth ? $_maxWidth : $this->_maxWidth;
    	$this->_maxHeight = $_maxHeight ? $_maxHeight : $this->_maxHeight;
    }
}
?>