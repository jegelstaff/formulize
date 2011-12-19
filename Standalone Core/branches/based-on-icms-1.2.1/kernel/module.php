<?php
/**
* Manage of modules
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	LICENSE.txt
* @package	core
* @since	XOOPS
* @author	http://www.xoops.org The XOOPS Project
* @author	modified by UnderDog <underdog@impresscms.org>
* @version	$Id: module.php 9520 2009-11-11 14:32:52Z pesianstranger $
*/

if(!defined('ICMS_ROOT_PATH')){exit();}

/**
* @package 	kernel
* @copyright 	copyright &copy; 2000 XOOPS.org
**/

/**
* A Module
*
* @package	kernel
* @author	Kazumi Ono 	<onokazu@xoops.org>
* @copyright	(c) 2000-2003 The Xoops Project - www.xoops.org
**/
class XoopsModule extends XoopsObject
{
	/**
	* @var string
	*/
	var $modinfo;
	/**
	* AdminMenu of the module
	*
	* @var array
	*/
	var $adminmenu;
	/**
	* Header menu on admin of the module
	*
	* @var array
	*/
	var $adminheadermenu;
	/**
	 * array for messages
	 *
	 * @var array
	 */
	var $messages;
	
	/**
	* Constructor
	*/
	function XoopsModule()
	{
		$this->XoopsObject();
		$this->initVar('mid', XOBJ_DTYPE_INT, null, false);
		$this->initVar('name', XOBJ_DTYPE_TXTBOX, null, true, 150);
		$this->initVar('version', XOBJ_DTYPE_INT, 100, false);
		$this->initVar('last_update', XOBJ_DTYPE_INT, null, false);
		$this->initVar('weight', XOBJ_DTYPE_INT, 0, false);
		$this->initVar('isactive', XOBJ_DTYPE_INT, 1, false);
		$this->initVar('dirname', XOBJ_DTYPE_OTHER, null, true);
		$this->initVar('hasmain', XOBJ_DTYPE_INT, 0, false);
		$this->initVar('hasadmin', XOBJ_DTYPE_INT, 0, false);
		$this->initVar('hassearch', XOBJ_DTYPE_INT, 0, false);
		$this->initVar('hasconfig', XOBJ_DTYPE_INT, 0, false);
		$this->initVar('hascomments', XOBJ_DTYPE_INT, 0, false);
		// RMV-NOTIFY
		$this->initVar('hasnotification', XOBJ_DTYPE_INT, 0, false);
		$this->initVar('dbversion', XOBJ_DTYPE_INT, 0, false);
	}
	
	/**
	* Load module info
	*
	* @param   string  $dirname    Directory Name
	* @param   boolean $verbose
	**/
	function loadInfoAsVar($dirname, $verbose = true)
	{
		if(!isset($this->modinfo)) {$this->loadInfo($dirname, $verbose);}
		$this->setVar('name', $this->modinfo['name'], true);
		$this->setVar('version', intval(100 * ($this->modinfo['version'] + 0.001)), true);
		$this->setVar('dirname', $this->modinfo['dirname'], true);
		$hasmain = (isset($this->modinfo['hasMain']) && $this->modinfo['hasMain'] == 1) ? 1 : 0;
		$hasadmin = (isset($this->modinfo['hasAdmin']) && $this->modinfo['hasAdmin'] == 1) ? 1 : 0;
		$hassearch = (isset($this->modinfo['hasSearch']) && $this->modinfo['hasSearch'] == 1) ? 1 : 0;
		$hasconfig = ((isset($this->modinfo['config']) && is_array($this->modinfo['config'])) || !empty($this->modinfo['hasComments'])) ? 1 : 0;
		$hascomments = (isset($this->modinfo['hasComments']) && $this->modinfo['hasComments'] == 1) ? 1 : 0;
		// RMV-NOTIFY
		$hasnotification = (isset($this->modinfo['hasNotification']) && $this->modinfo['hasNotification'] == 1) ? 1 : 0;
		$this->setVar('hasmain', $hasmain);
		$this->setVar('hasadmin', $hasadmin);
		$this->setVar('hassearch', $hassearch);
		$this->setVar('hasconfig', $hasconfig);
		$this->setVar('hascomments', $hascomments);
		// RMV-NOTIFY
		$this->setVar('hasnotification', $hasnotification);
	}
	
	/**
	* Get module info
	*
	* @param   string  	$name
	* @return  array|string	Array of module information.
	* If {@link $name} is set, returns a single module information item as string.
	**/
	function &getInfo($name = null)
	{
		if(!isset($this->modinfo)) {$this->loadInfo($this->getVar('dirname'));}
		if(isset($name))
		{
			if(isset($this->modinfo[$name])) {return $this->modinfo[$name];}
			$return = false;
			return $return;
		}
		return $this->modinfo;
	}
	
	/**
	* Retreive the database version of this module
	*
	* @return int dbversion
	**/
	function getDBVersion()
	{
		$ret = $this->getVar('dbversion');
		return $ret;
	}
	
	/**
	* Get a link to the modules main page
	*
	* @return	string $ret or FALSE on fail
	*/
	function mainLink()
	{
		if($this->getVar('hasmain') == 1)
		{
			$ret = '<a href="'.ICMS_URL.'/modules/'.$this->getVar('dirname').'/">'.$this->getVar('name').'</a>';
			return $ret;
		}
		return false;
	}
	
	/**
	* Get links to the subpages
	*
	* @return	string $ret
	*/
	function subLink()
	{
		$ret = array();
		if($this->getInfo('sub') && is_array($this->getInfo('sub')))
		{
			foreach($this->getInfo('sub') as $submenu)
			{
				$ret[] = array('name' => $submenu['name'], 'url' => $submenu['url']);
			}
		}
		return $ret;
	}
	
	/**
	* Load the admin menu for the module
	*/
	function loadAdminMenu()
	{
		if($this->getInfo('adminmenu') && $this->getInfo('adminmenu') != '' && file_exists(ICMS_ROOT_PATH.'/modules/'.$this->getVar('dirname').'/'.$this->getInfo('adminmenu')))
		{
			include_once ICMS_ROOT_PATH.'/modules/'.$this->getVar('dirname').'/'.$this->getInfo('adminmenu');
			$this->adminmenu = & $adminmenu;
			if(isset($headermenu)) {$this->adminheadermenu = & $headermenu;}
		}
	}
	
	/**
	* Get the admin menu for the module
	* 
	* @return	string $this->adminmenu
	*/
	function &getAdminMenu()
	{
		if(!isset($this->adminmenu)) {$this->loadAdminMenu();}
		return $this->adminmenu;
	}
	
	/**
	* Get the admin header menu for the module
	* 
	* @return	string $this->adminmenu
	*/
	function &getAdminHeaderMenu()
	{
		if(!isset($this->adminheadermenu)) {$this->loadAdminMenu();}
		return $this->adminheadermenu;
	}
	
	/**
	* Load the module info for this module
	*
	* @param   string  $dirname    Module directory
	* @param   bool    $verbose    Give an error on fail?
	* @return  bool   TRUE if success, FALSE if fail.
	*/
	function loadInfo($dirname, $verbose = true)
	{
		global $icmsConfig;
		icms_loadLanguageFile($dirname, 'modinfo');
		if(file_exists(ICMS_ROOT_PATH.'/modules/'.$dirname.'/icms_version.php'))
		{
			include ICMS_ROOT_PATH.'/modules/'.$dirname.'/icms_version.php';
		}elseif(file_exists(ICMS_ROOT_PATH.'/modules/'.$dirname.'/xoops_version.php'))
		{
			include ICMS_ROOT_PATH.'/modules/'.$dirname.'/xoops_version.php';
		}
		else
		{
			if(false != $verbose) {echo "Module File for $dirname Not Found!";}
			return false;
		}
		$this->modinfo = & $modversion;
		return true;
	}
	
	/**
	* Search contents within a module
	*
	* @param   string  $term
	* @param   string  $andor  'AND' or 'OR'
	* @param   integer $limit
	* @param   integer $offset
	* @param   integer $userid
	* @return  mixed   Search result or False if fail.
	**/
	function search($term = '', $andor = 'AND', $limit = 0, $offset = 0, $userid = 0)
	{
		if($this->getVar('hassearch') != 1) {return false;}
		$search = & $this->getInfo('search');
		if($this->getVar('hassearch') != 1 || !isset($search['file']) || !isset($search['func']) || $search['func'] == '' || $search['file'] == '')
		{
			return false;
		}
		if(file_exists(ICMS_ROOT_PATH."/modules/".$this->getVar('dirname').'/'.$search['file']))
		{
			include_once ICMS_ROOT_PATH.'/modules/'.$this->getVar('dirname').'/'.$search['file'];
		}
		else
		{
			return false;
		}
		if(function_exists($search['func']))
		{
			$func = $search['func'];
			return $func($term, $andor, $limit, $offset, $userid);
		}
		return false;
	}


	/**
	* Displays the (good old) adminmenu
	* 
	* @param int  $currentoption  The current option of the admin menu
	* @param string  $breadcrumb  The breadcrumb trail
	* @param bool  $submenus  Show the submenus!
	* @param int  $currentsub  The current submenu
	* 
	* @return datatype  description
	*/
	function displayAdminMenu($currentoption = 0, $breadcrumb = '', $submenus = false, $currentsub = -1)
	{
		global $icmsModule, $icmsConfig;
		include_once ICMS_ROOT_PATH.'/class/template.php';
		icms_loadLanguageFile($icmsModule->getVar('dirname'), 'modinfo');
		icms_loadLanguageFile($icmsModule->getVar('dirname'), 'admin');
		$tpl = new XoopsTpl();
		$tpl->assign(array('headermenu' => $this->getAdminHeaderMenu(), 'adminmenu' => $this->getAdminMenu(), 'current' => $currentoption, 'breadcrumb' => $breadcrumb, 'headermenucount' => count($this->getAdminHeaderMenu()), 'submenus' => $submenus, 'currentsub' => $currentsub, 'submenuscount' => count($submenus)));
		$tpl->display(ICMS_ROOT_PATH.'/modules/system/templates/admin/system_adm_modulemenu.html');
	}


	/**#@+
	* For backward compatibility only!
	* @deprecated
	*/
	function mid() {return $this->getVar('mid');}
	function dirname() {return $this->getVar('dirname');}
	function name() {return $this->getVar('name');}
	function &getByDirName($dirname)
	{
		$modhandler = & xoops_gethandler('module');
		$inst = & $modhandler->getByDirname($dirname);
		return $inst;
	}

	/**
	 * Modules Message Function
	 *
	 * @since ImpressCMS 1.2
	 * @author Sina Asghari (aka stranger) <stranger@impresscms.org>
	 *
	 * @param string $msg	The Error Message
	 * @param string $title	The Error Message title
	 * @param	bool	$render	Whether to echo (render) or return the HTML string
	 *
	 * @todo Make this work with templates ;)
	 */
	function setMessage($msg, $title='', $render = false){
		$ret = '<div class="moduleMsg">';
		if($title != '') {$ret .= '<h4>'.$title.'</h4>';}
		if(is_array($msg))
		{
			foreach($msg as $m) {$ret .= $m.'<br />';}
		}
		else {$ret .= $msg;}
		$ret .= '</div>';
		if($render){
			echo $ret;
		}else{
			return $ret;
		}
	}
}

/**
* XOOPS module handler class.
*
* This class is responsible for providing data access mechanisms to the data source
* of XOOPS module class objects.
*
* @package	kernel
* @author	Kazumi Ono 	<onokazu@xoops.org>
* @copyright	(c) 2000-2003 The Xoops Project - www.xoops.org
**/
class XoopsModuleHandler extends XoopsObjectHandler
{
	/**
	* holds an array of cached module references, indexed by module id
	*
	* @var    array
	* @access private
	**/
	var $_cachedModule_mid = array();
	/**
	* holds an array of cached module references, indexed by module dirname
	*
	* @var    array
	* @access private
	*/
	var $_cachedModule_dirname = array();
	
	/**
	* Create a new {@link XoopsModule} object
	*
	* @param   boolean     $isNew   Flag the new object as "new"
	* @return  object      {@link XoopsModule} 
	**/
	function &create($isNew = true)
	{
		$module = new XoopsModule();
		if($isNew) {$module->setNew();}
		return $module;
	}
	
	/**
	* Load a module from the database
	*
	* @param  	int     $id     ID of the module
	* @return	object  {@link XoopsModule} FALSE on fail
	**/
	function &get($id)
	{
		static $_cachedModule_dirname;
		static $_cachedModule_mid;
		$id = intval($id);
		$module = false;
		if($id > 0)
		{
			if(!empty( $_cachedModule_mid[$id]))
			{
				return $_cachedModule_mid [$id];
			}
			else
			{
				$sql = "SELECT * FROM ".$this->db->prefix('modules')." WHERE mid = '".$id."'";
				if(!$result = $this->db->query($sql)) {return $module;}
				$numrows = $this->db->getRowsNum($result);
				if($numrows == 1)
				{
					$module = new XoopsModule();
					$myrow = $this->db->fetchArray($result);
					$module->assignVars($myrow);
					$_cachedModule_mid[$id] = & $module;
					$_cachedModule_dirname[$module->getVar('dirname')] = & $module;
					return $module;
				}
			}
		}
		return $module;
	}
	
	/**
	* Load a module by its dirname
	*
	* @param	string    $dirname
	* @return	object  {@link XoopsModule} FALSE on fail
	**/
	function &getByDirname($dirname)
	{
		static $_cachedModule_mid;
		static $_cachedModule_dirname;
		if(!empty( $_cachedModule_dirname[$dirname]) && $_cachedModule_dirname[$dirname]->dirname() == $dirname)
		{
			return $_cachedModule_dirname[$dirname];
		}
		else
		{
			$module = false;
			$sql = "SELECT * FROM ".$this->db->prefix('modules')." WHERE dirname = '".trim($dirname)."'";
			if(!$result = $this->db->query($sql)) {return $module;}
			$numrows = $this->db->getRowsNum($result);
			if($numrows == 1)
			{
				$module = new XoopsModule();
				$myrow = $this->db->fetchArray($result);
				$module->assignVars($myrow);
				$_cachedModule_dirname[$dirname] = & $module;
				$_cachedModule_mid[$module->getVar('mid')] = & $module;
			}
			return $module;
		}
	}
	
	/**
	* Inserts a module into the database
	*
	* @param   object  &$module reference to a {@link XoopsModule}
	* @return  bool
	**/
	function insert(&$module)
	{
		if(strtolower(get_class($module)) != 'xoopsmodule') {return false;}
		if(!$module->isDirty()) {return true;}
		if(!$module->cleanVars()) {return false;}
		
		/**
		* Editing the insert and update methods
		* this is temporaray as will soon be based on a persistableObjectHandler
		**/
		$fieldsToStoreInDB = array();
		foreach($module->cleanVars as $k => $v)
		{
			if($k == 'last_update') {$v = time();}
			if($module->vars[$k]['data_type'] == XOBJ_DTYPE_INT)
			{
				$cleanvars[$k] = intval($v);
			}
			elseif(is_array($v))
			{
				$cleanvars[$k] = $this->db->quoteString(implode(',', $v));
			}
			else
			{
				$cleanvars[$k] = $this->db->quoteString($v);
			}
			$fieldsToStoreInDB[$k] = $cleanvars[$k];
		}
		
		if($module->isNew())
		{
			$sql = "INSERT INTO ".$this->db->prefix('modules')." (".implode(',', array_keys($fieldsToStoreInDB)).") VALUES (".implode(',', array_values($fieldsToStoreInDB)).")";
		}
		else
		{
			$sql = "UPDATE ".$this->db->prefix('modules')." SET";
			foreach($fieldsToStoreInDB as $key => $value)
			{
				if(isset($notfirst)) {$sql .= ",";}
				$sql .= " ".$key." = ".$value;
				$notfirst = true;
			}
			$whereclause = 'mid'." = ".$module->getVar('mid');
			$sql .= " WHERE ".$whereclause;
		}
		
		if(!$result = $this->db->query($sql)) {return false;}
		if($module->isNew()) {$module->assignVar('mid', $this->db->getInsertId());}
		if(!empty($this->_cachedModule_dirname[$module->getVar('dirname')])) {unset($this->_cachedModule_dirname[$module->getVar('dirname')]);}
		if(!empty($this->_cachedModule_mid[$module->getVar('mid')])) {unset($this->_cachedModule_mid[$module->getVar('mid')]);}
		return true;
	}
	
	/**
	 * Delete a module from the database
	 *
	 * @param   object  &$module {@link XoopsModule}
	 * @return  bool
	 **/
	function delete(&$module) {
		if(strtolower(get_class($module)) != 'xoopsmodule') {return false;}
		
		$sql = sprintf("DELETE FROM %s WHERE mid = '%u'", $this->db->prefix('modules'), intval($module->getVar('mid')));
		if(!$result = $this->db->query($sql )) {return false;}
		
		// delete admin permissions assigned for this module
		$sql = sprintf("DELETE FROM %s WHERE gperm_name = 'module_admin' AND gperm_itemid = '%u'", $this->db->prefix('group_permission'), intval($module->getVar ('mid')));
		$this->db->query($sql);
		// delete read permissions assigned for this module
		$sql = sprintf("DELETE FROM %s WHERE gperm_name = 'module_read' AND gperm_itemid = '%u'", $this->db->prefix('group_permission'), intval($module->getVar ('mid')));
		$this->db->query($sql);
		
		$sql = sprintf("SELECT block_id FROM %s WHERE module_id = '%u'", $this->db->prefix('block_module_link'), intval($module->getVar('mid')));
		if($result = $this->db->query($sql))
		{
			$block_id_arr = array();
			while($myrow = $this->db->fetchArray($result)) {array_push($block_id_arr, $myrow['block_id']);}
		}
		
		// loop through block_id_arr
		if(isset($block_id_arr))
		{
			foreach($block_id_arr as $i)
			{
				$sql = sprintf("SELECT block_id FROM %s WHERE module_id != '%u' AND block_id = '%u'", $this->db->prefix('block_module_link'), intval($module->getVar('mid')), intval($i));
				if($result2 = $this->db->query($sql))
				{
					if(0 < $this->db->getRowsNum($result2))
					{
						// this block has other entries, so delete the entry for this module
						$sql = sprintf("DELETE FROM %s WHERE (module_id = '%u') AND (block_id = '%u')", $this->db->prefix('block_module_link'), intval($module->getVar('mid')), intval($i));
						$this->db->query($sql);
					}
					else
					{
						// this block doesnt have other entries, so disable the block and let it show on top page only. otherwise, this block will not display anymore on block admin page!
						$sql = sprintf("UPDATE %s SET visible = '0' WHERE bid = '%u'", $this->db->prefix('newblocks'), intval($i));
						$this->db->query($sql);
						$sql = sprintf("UPDATE %s SET module_id = '-1' WHERE module_id = '%u'", $this->db->prefix('block_module_link'), intval($module->getVar('mid')));
						$this->db->query($sql);
					}
				}
			}
		}
		
		if(!empty($this->_cachedModule_dirname[$module->getVar('dirname')])) {unset($this->_cachedModule_dirname[$module->getVar('dirname')]);}
		if(!empty($this->_cachedModule_mid[$module->getVar('mid')])) {unset($this->_cachedModule_mid[$module->getVar('mid')]);}
		return true;
	}
	
	/**
	* Load some modules
	*
	* @param   object  $criteria   {@link CriteriaElement}
	* @param   boolean $id_as_key  Use the ID as key into the array
	* @return  array
	**/
	function getObjects($criteria = null, $id_as_key = false)
	{
		$ret = array();
		$limit = $start = 0;
		$sql = "SELECT * FROM ".$this->db->prefix('modules');
		if(isset($criteria) && is_subclass_of($criteria, 'criteriaelement'))
		{
			$sql .= " ".$criteria->renderWhere();
			$sql .= " ORDER BY weight ".$criteria->getOrder().", mid ASC";
			$limit = $criteria->getLimit();
			$start = $criteria->getStart();
		}
		$result = $this->db->query($sql, $limit, $start);
		if(!$result) {return $ret;}
		while($myrow = $this->db->fetchArray($result))
		{
			$module = new XoopsModule();
			$module->assignVars($myrow);
			if(!$id_as_key)
			{
				$ret[] = & $module;
			}
			else
			{
				$ret[$myrow['mid']] = & $module;
			}
			unset($module);
		}
		return $ret;
	}
	
	/**
	* Count some modules
	*
	* @param   object  $criteria   {@link CriteriaElement}
	* @return  int
	**/
	function getCount($criteria = null)
	{
		$sql = "SELECT COUNT(*) FROM ".$this->db->prefix('modules');
		if(isset($criteria) && is_subclass_of($criteria, 'criteriaelement')) {$sql .= " ".$criteria->renderWhere();}
		if(!$result = $this->db->query($sql)) {return 0;}
		list($count) = $this->db->fetchRow($result);
		return $count;
	}
	
	/**
	* returns an array of module names
	*
	* @param   bool    $criteria
	* @param   boolean $dirname_as_key
	*      if true, array keys will be module directory names
	*      if false, array keys will be module id
	* @return  array
	**/
	function getList($criteria = null, $dirname_as_key = false)
	{
		$ret = array();
		$modules = & $this->getObjects($criteria, true);
		foreach(array_keys($modules) as $i)
		{
			if(!$dirname_as_key)
			{
				$ret[$i] = $modules[$i]->getVar('name');
			}
			else
			{
				$ret[$modules[$i]->getVar('dirname')] = $modules[$i]->getVar('name');
			}
		}
		return $ret;
	}
}

?>