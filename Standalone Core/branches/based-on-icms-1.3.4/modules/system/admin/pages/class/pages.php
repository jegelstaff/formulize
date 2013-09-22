<?php
/**
 * Administration of Symlinks
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @package		System
 * @subpackage	Symlinks
 * @version		SVN: $Id: pages.php 11175 2011-04-15 10:53:22Z m0nty_ $
 */

/**
 * Symlinks object
 * 
 * @package		System
 * @subpackage	Symlinks
 */ 
class SystemPages extends icms_data_page_Object {

	/**
	 * Constructor
	 */
	public function __construct(& $handler) {
		parent::__construct($handler);

		$this->setControl('page_status', 'yesno');
		$this->setControl('page_moduleid', array(
			'itemHandler' => 'pages',
			'method' => 'getModulesArray',
			'module' => 'system'
			));
	}

	/**
	 * Custom button for updating the status of a symlink
	 * @return	string
	 */
	public function getCustomPageStatus() {
		if ($this->getVar('page_status') == 1) {
			$rtn = '<a href="' . ICMS_MODULES_URL . '/system/admin.php?fct=pages&amp;op=status&amp;page_id=' . $this->getVar('page_id') 
				. '" title="' . _VISIBLE . '" ><img src="' . ICMS_IMAGES_SET_URL . '/actions/button_ok.png" alt="' . _VISIBLE . '"/></a>';
		} else {
			$rtn = '<a href="' . ICMS_MODULES_URL . '/system/admin.php?fct=pages&amp;op=status&amp;page_id=' . $this->getVar('page_id') 
				. '" title="' . _VISIBLE . '" ><img src="' . ICMS_IMAGES_SET_URL . '/actions/button_cancel.png" alt="' . _VISIBLE . '"/></a>';
		}
		return $rtn;
	}

	/**
	 * Custom control to retrieve parent module for the symlink
	 * @return	array 	Parent module for the symlink
	 */ 
	public function getCustomPageModuleid() {
		$modules = $this->handler->getModulesArray();
		return $modules[$this->getVar('page_moduleid')];
	}

	/**
	 * Retrieve title of the symlink
	 * @return	string
	 */
	public function getAdminViewItemLink() {
		$rtn = $this->getVar('page_title');
		return $rtn;
	}

	/**
	 * Build a link to the page represented by the symlink, if available
	 * @return	string
	 */
	public function getViewItemLink() {
		$url = (substr($this->getVar('page_url', 'e'), 0, 7) == 'http://')
			? $this->getVar('page_url', 'e')
			: ICMS_URL . '/' . $this->getVar('page_url', 'e');
		$url = icms_core_DataFilter::checkVar($url, 'url', 'host');

		if (!$url) {
			$ret = '';
		} else {
			$ret = '<a href="' . $url . '" alt="' . _PREVIEW . '" title="' . _PREVIEW 
				. '" rel="external"><img src="' . ICMS_IMAGES_SET_URL . '/actions/viewmag.png" /></a>';
		}

		return $ret;
	}
}

/**
 * Symlinks handler
 * 
 * @package		System
 * @subpackage	Symlinks
 */
class SystemPagesHandler extends icms_data_page_Handler {

	/** */
	private $modules_name;

	/**
	 * Constructor
	 * 
	 * @param $db
	 */
	public function __construct(& $db) {
		icms_ipf_Handler::__construct($db, 'pages', 'page_id', 'page_title', '' , 'system');
		$this->table = $db->prefix('icmspage');
	}

	/**
	 * Get an array of installed modules
	 * 
	 * @param boolean $full
	 * @return	array
	 */
	public function getModulesArray($full = FALSE) {
		if (!count($this->modules_name)) {
			$icms_module_handler = icms::handler('icms_module');
			$installed_modules =& $icms_module_handler->getObjects();
			foreach ($installed_modules as $module) {
				$this->modules_name[$module->getVar('mid')]['name'] = $module->getVar('name');
				$this->modules_name[$module->getVar('mid')]['dirname'] = $module->getVar('dirname');
			}
		}
		$rtn = $this->modules_name;

		if (!$full) {
			foreach ($this->modules_name as $key => $module) {
				$rtn[$key] = $module['name'];
			}
		}

		return $rtn;
	}

	/**
	 * Change the status of the symlink in the db
	 * 
	 * @param $page_id
	 * @return	boolean	FALSE if failed, TRUE if successful
	 */
	public function changeStatus($page_id) {
		$page = $this->get($page_id);
		$page->setVar('page_status', !$page->getVar('page_status'));
		return $this->insert($page, TRUE);
	}
}
