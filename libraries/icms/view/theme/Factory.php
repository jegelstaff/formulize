<?php
/**
 * icms_view_theme_Object component class file
 *
 * @license		http://www.fsf.org/copyleft/gpl.html GNU public license
 * @author		Skalpa Keo <skalpa@xoops.org>
 * @category	ICMS
 * @package 	View
 * @subpackage 	Theme
 * @version		SVN: $Id: Factory.php 22529 2011-09-02 19:55:40Z phoenyx $
 */

/**
 * icms_view_theme_Factory
 *
 * @author		Skalpa Keo
 * @category	ICMS
 * @package		View
 * @subpackage	Theme
 */
class icms_view_theme_Factory {

	public $xoBundleIdentifier = 'icms_view_theme_Factory';
	/**
	 * Currently enabled themes (if empty, all the themes in themes/ are allowed)
	 * @public array
	 */
	public $allowedThemes = array();
	/**
	 * Default theme to instanciate if none specified
	 * @public string
	 */
	public $defaultTheme = 'iTheme';
	/**
	 * If users are allowed to choose a custom theme
	 * @public bool
	 */
	public $allowUserSelection = true;

	/**
	 * Instanciate the specified theme
	 */
	public function &createInstance($options = array(), $initArgs = array()) {
		// Grab the theme folder from request vars if present
		if (@empty($options['folderName'])) {
			// xoops_theme_select still exists to keep compatibilitie ...
			if (($req = @$_REQUEST['xoops_theme_select']) && $this->isThemeAllowed($req)) {
				$options['folderName'] = $req;
				if (isset($_SESSION) && $this->allowUserSelection) {
					$_SESSION[$this->xoBundleIdentifier]['defaultTheme'] = $req;
				}
			} elseif (($req = @$_REQUEST['theme_select']) && $this->isThemeAllowed($req)) {
				$options['folderName'] = $req;
				if (isset($_SESSION) && $this->allowUserSelection) {
					$_SESSION[$this->xoBundleIdentifier]['defaultTheme'] = $req;
				}
			} elseif (isset($_SESSION[$this->xoBundleIdentifier]['defaultTheme'])) {
				$options['folderName'] = $_SESSION[$this->xoBundleIdentifier]['defaultTheme'];
			} elseif (@empty($options['folderName'] ) || ! $this->isThemeAllowed($options ['folderName'])) {
				$options['folderName'] = $this->defaultTheme;
			}
			$GLOBALS['icmsConfig']['theme_set'] = $options['folderName'];
		}
		$options['path'] =
			(is_dir(ICMS_MODULES_PATH . '/system/themes/' . $options['folderName']))
			? ICMS_MODULES_PATH . '/system/themes/' . $options['folderName']
			: ICMS_THEME_PATH . '/' . $options['folderName'];
		$inst = new icms_view_theme_Object();
		foreach ($options as $k => $v) {
			$inst->$k = $v;
		}
		$inst->xoInit();
		return $inst;
	}

	/**
	 * Checks if the specified theme is enabled or not
	 * @param string $name
	 * @return bool
	 */
	public function isThemeAllowed($name) {
		return (empty($this->allowedThemes) || in_array($name, $this->allowedThemes));
	}
	
	/**
	 * Gets list of themes folder from themes directory, excluding any directories that do not have theme.html
	 * @return	array
	 */
	static public function getThemesList() {
		$dirtyList = $cleanList = array();
		$dirtyList = icms_core_Filesystem::getDirList(ICMS_THEME_PATH . '/');
		foreach ($dirtyList as $item) {
			if (file_exists(ICMS_THEME_PATH . '/' . $item . '/theme.html')) {
				$cleanList[$item] = $item;
			}
		}
		return $cleanList;
	}
	
	/**
	 * Gets list of administration themes folder from themes directory, excluding any directories that do not have theme_admin.html
	 * @return	array
	 */
	static public function getAdminThemesList() {
		$dirtyList1 = $cleanList1 = array();
		$dirtyList2 = $cleanList2 = array();
		$dirtyList1 = icms_core_Filesystem::getDirList(ICMS_THEME_PATH . '/');
		$dirtyList2 = icms_core_Filesystem::getDirList(ICMS_MODULES_PATH . '/system/themes/');
		foreach ($dirtyList1 as $item1) {
			if (file_exists(ICMS_THEME_PATH . '/' . $item1 . '/theme_admin.html')) {
				$cleanList1[$item1] = $item1;
			}
		}
		foreach ($dirtyList2 as $item2) {
			if (file_exists(ICMS_MODULES_PATH . '/system/themes/' . $item2 . '/theme.html')
				|| file_exists(ICMS_MODULES_PATH . '/system/themes/' . $item2 . '/theme_admin.html')
			) {
				$cleanList2[$item2] = $item2;
			}
		}
		$cleanList = array_merge($cleanList1, $cleanList2);
		return $cleanList;
	}
}

